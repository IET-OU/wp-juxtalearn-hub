<?php

// TODO: Maybe use get_transient for caching?

abstract class JuxtaLearn_Hub_Shortcode {
	var $shortcode = 'juxtalearn_hub_shortcode';
	var $defaults = array();
	var $options = array();
	var $buttons = array();
	
	static $first_button = true;
	
	function __construct() {
		add_shortcode($this->shortcode, array(&$this, 'shortcode'));
		add_filter('the_content', array(&$this, 'pre_add_to_page'));

		add_action('save_post', array(&$this, 'save_post'));
		add_action('trash_post', array(&$this, 'trash_post'));
		
		register_activation_hook(JUXTALEARN_HUB_REGISTER_FILE, array(&$this, 'activate'));
		register_deactivation_hook(JUXTALEARN_HUB_REGISTER_FILE, array(&$this, 'deactivate'));
		
		global $wpdb;
		$wpdb->juxtalearn_hub_shortcode_cache = $wpdb->prefix.'juxtalearn_hub_shortcode_cache';
	}
		
	function shortcode($options) {
		$this->options = shortcode_atts($this->defaults, $options);	
		$this->prep_options();
		
		if (!$content = $this->get_cache()) {
			$content = $this->content();
			$this->cache($content);
		}
		
		return $content;
	}
	
	function pre_add_to_page($content) {
		$options = get_option('juxtalearn_hub_options');
		$options['add_to_page'] = 1;
		return $options['add_to_page'] ? $this->add_to_page($content) : $content;
	}
	
	function add_to_page($content) {
		return $content;
	}
	
	function meta_bar($post, $options){
		$out = array();
		foreach (explode(',', $options) as $type) {
			$type = trim($type);
			$slug = $type."_slug";
			if ($type == 'sb'){
				$tags =wp_get_post_terms( $post['ID'], 'juxtalearn_hub_'.$type);
				$ln = '<span class="meta_label">Stumbling Blocks</span>: ';
				foreach($tags as $tag){
					$ln .= $sep . '<a href="'.get_term_link($tag).'">'.$tag->name.'</a>';
					$sep = ' | ';
				}
				$out[] = $ln;
				
			} elseif (isset($post[$type]) && isset($post[$slug])){
				if (is_array($post[$slug]) && is_array($post[$type])){
					foreach ($post[$slug] as $idx => $post_slug){
						$out[] = $this->get_meta($post, $type, $slug, $idx);
					}
				} else {
					$out[] = $this->get_meta($post, $type, $slug);
				}
			} else {
				$out[] = $this->get_meta($post, $type);
			}
		}
		$out = array_filter($out);
		if(!empty($out)){ 
			echo '<div id="juxtalearn-meta">'.implode(" | ", $out).'</div>';
       }	
	}
	
	function get_meta($post, $type, $slug = false, $idx = false){
		if ($idx){
			$slug_url = get_term_link($post[$slug][$idx],"juxtalearn_hub_".$type);
			$name = $post[$type][$idx];
		} elseif ($slug) {
			$slug_url = get_term_link($post[$slug],"juxtalearn_hub_".$type);
			$name = $post[$type];
		} elseif (isset($post[$type])) {
			$name = $post[$type];
		}

		if (isset($slug_url) && !is_wp_error($slug_url)){
			return __(sprintf('<span class="meta_label">%s</span>: <a href="%s">%s</a>', ucwords(str_replace("_", " ",$type)),$slug_url , ucwords($name)));
		} elseif ($type == 'trickytopic') {
			return  __(sprintf('<span class="meta_label">%s</span>: <a href="%s">%s</a>', ucwords($type),get_permalink($post['trickytopic_id']) , get_the_title($post['trickytopic_id'])));
		} elseif(isset($post[$type]) && ($type=="citation" || $type=="resource_link" || $type == "post_type")) {
			if ($type == "post_type"){
				return __(sprintf('<span class="meta_label">%s</span>: <a href="%s">%s</a>', ucwords(str_replace("_", " ",$type)), get_post_type_archive_link($post[$type]), ucwords($post[$type])));
			} else {
				return __(sprintf('<span class="meta_label">%s</span>: <a href="%s">%s</a>', ucwords(str_replace("_", " ",$type)), $post[$type], $post[$type]));
			}
		} elseif (isset($post[$type])) {
			return __(sprintf('<span class="meta_label">%s</span>: %s', ucwords(str_replace("_", " ",$type)),$post[$type]));
		}
		return NULL;
	}
	
	function prep_options() {
		foreach ($this->options as $key => $value) {
			if (is_string($value)) {
				if ($value == 'true') $this->options[$key] = true;
				if ($value == 'false') $this->options[$key] = false;
			}
		}
	}
	
	abstract function content();

	
	// Caching ----------------------------------------------------------------

	// TODO: doesn't $wpdb need to be globalized in this function?
	function activate() {
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		dbDelta("CREATE TABLE $wpdb->juxtalearn_hub_shortcode_cache (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			shortcode text NOT NULL,
			options text NOT NULL,
			content mediumtext NOT NULL,
			UNIQUE KEY id(id)
		);");
	}
	
	function deactivate() {
		global $wpdb;
		$wpdb->query("drop table $wpdb->juxtalearn_hub_shortcode_cache");
	}
	
	function save_post($post_id) {
		if (!in_array(get_post_type($post_id), JuxtaLearn_Hub::$post_types)) return;
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		self::clear_cache();
	}
		
	function trash_post($post_id) {
		if (!in_array(get_post_type($post_id), JuxtaLearn_Hub::$post_types)) return;
		self::clear_cache();
	}
		
	function get_cache() {
		if (!get_option('juxtalearn_hub_caching')) return false;
		
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare(
			"SELECT content
			from $wpdb->juxtalearn_hub_shortcode_cache
			where shortcode = %s
			and options = %s",
			$this->shortcode,
			serialize($this->options)
		));
	}
	
	function cache($content) {
		global $wpdb;
		$wpdb->insert($wpdb->juxtalearn_hub_shortcode_cache, array(
			'created' => current_time('mysql'),
			'shortcode' => $this->shortcode,
			'options' => serialize($this->options),
			'content' => $content,
		));
	}
	
	static function get_all_cache() {
		global $wpdb;
		return $wpdb->get_results("SELECT shortcode, count(id) AS count FROM $wpdb->juxtalearn_hub_shortcode_cache GROUP BY shortcode", OBJECT);
	}

	static function clear_cache() {
		global $wpdb;
		$wpdb->query("TRUNCATE $wpdb->juxtalearn_hub_shortcode_cache");
	}
}