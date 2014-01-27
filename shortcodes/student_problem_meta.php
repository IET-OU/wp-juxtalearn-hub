<?php

new JuxtaLearn_Hub_Shortcode_Student_Problem_Meta();
class JuxtaLearn_Hub_Shortcode_Example_Meta extends JuxtaLearn_Hub_Shortcode {
	var $shortcode = 'student_problem_meta';
	var $defaults = array(
		'post_id' => false,
		'post_ids' => false,
		'title' => false,
		'location' => 'header',
		'header_terms' => 'type,education_level,country,trickytopic',
		'footer_terms' => 'citation',
		'no_example_message' => "There is no meta data for this example",
		'title_tag' => 'h4',
	);

	

	static $post_types_with_example = array('student_problem');
	
	function add_to_page($content) {
		if (in_array(get_post_type(), self::$post_types_with_example)) {
			$content = do_shortcode('[example_meta location="header"]').$content.do_shortcode('[example_meta location="footer"]');
		}
		return $content;
	}
	
	function prep_options() {
		// Turn csv into array
		if (!is_array($this->options['post_ids'])) $this->options['post_ids'] = array();
		if (!empty($this->options['post_ids'])) $this->options['post_ids'] = explode(',', $this->options['post_ids']);

		// add post_id to post_ids and get rid of it
		if ($this->options['post_id']) $this->options['post_ids'] = array_merge($this->options['post_ids'], explode(',', $this->options['post_id']));
		unset($this->options['post_id']);
		
		// fallback to current post if nothing specified
		if (empty($this->options['post_ids']) && $GLOBALS['post']->ID) $this->options['post_ids'] = array($GLOBALS['post']->ID);
		
		// unique list
		$this->options['post_ids'] = array_unique($this->options['post_ids']);
	}

	function content() {
		ob_start();
		extract($this->options);
		
		$errors = array();

		if (empty($post_ids)) $errors[] = "No posts ID provided";
		
		foreach ($post_ids as $post_id) {
			$post = JuxtaLearn_Hub::add_meta($post_id);
			$post['type'] = get_post_type($post_id);
			if (!$post) {
				$errors[] = "$post_id is not a valid post ID";
			} else if (!in_array($post['type'], self::$post_types_with_example)) {
				$errors[] = "<a href='".get_permalink($post_id)."'>".get_the_title($post_id)."</a> is not the correct type of post";
			} else if ($location=="header") { 
				$this->meta_bar($post, $header_terms);
			} else if ($location=="footer") { 
	  			$this->meta_bar($post, $footer_terms);
			}
		}
		
		if (count($errors)) return "[Shortcode errors (".$this->shortcode."): ".implode(', ', $errors)."]";
		
		return ob_get_clean();
	}
}