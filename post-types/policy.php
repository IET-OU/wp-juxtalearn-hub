<?php
if(!class_exists('Policy_Template'))
{
	/**
	 * A PostTypeTemplate class that provides 3 additional meta fields
	 */
	class Policy_Template
	{
		const POST_TYPE	= "policy";
		const ARCHIVE_SLUG = "policy"; // use pluralized string if you want an archive page
		const SINGULAR = "Policy";
		const PLURAL = "Policies";
		var $options = array();
		
    	/**
    	 * The Constructor
    	 */
    	public function __construct()
    	{
    		// register actions
    		add_action('init', array(&$this, 'init'));
    		add_action('admin_init', array(&$this, 'admin_init'));
			add_action('manage_edit-'.self::POST_TYPE.'_columns', array(&$this, 'columns'));
			add_action('manage_'.self::POST_TYPE.'_posts_custom_column', array(&$this, 'column'),10 ,2);
			
			JuxtaLearn_Hub::$post_types[] = self::POST_TYPE;
			
    	} // END public function __construct()

    	/**
    	 * hook into WP's init action hook
    	 */
    	public function init()
    	{
    		// Initialize Post Type
    		$this->create_post_type();
    		add_action('save_post', array(&$this, 'save_post'));
    	} // END public function init()
		
		public function columns($columns) {
			$columns = array(
				'cb' => '<input type="checkbox" />',
				'title' => __( self::SINGULAR ),
				'juxtalearn_hub_country' => __( 'Country' ),
				'juxtalearn_hub_sector' => __( 'Sector' ),
				'juxtalearn_hub_locale' => __( 'Locale' ),
				'author' => __( 'Author' ),
				'date' => __( 'Date' )
			);

			return $columns;
		}
		public function column($column, $post_id) {
			global $post;
			switch (str_replace('juxtalearn_hub_', '', $column)) {
				case 'country':
					$location = wp_get_object_terms( $post_id, $column);
					if ( empty( $location ) )
						echo __( 'Empty' );
					else
						printf( __( '%s' ), $location[0]->name  );
					break;
				case 'sector':
					$sector = wp_get_object_terms( $post_id, $column);
					if ( empty( $sector ) ){
						echo __( 'Empty' );	
					} else {
						$out = array();
						foreach ($sector as $s){
							$out[] = $s->name;	
						}
						printf( __( '%s' ), implode(", ", $out ));
					}
					break;
				case 'locale':
					$locale = wp_get_object_terms( $post_id, $column);
					if ( empty( $locale ) ){
						echo __( 'Empty' );	
					} else {
						$out = array();
						foreach ($locale as $l){
							$out[] = $l->name;	
						}
						printf( __( '%s' ), implode(", ", $out ));
					}
					break;
			default :
				break;
			}
		}
		
		
    	/**
    	 * Create the post type
    	 */
    	public function create_post_type()
    	{
    		register_post_type(self::POST_TYPE,
    			array(
    				'labels' => array(
    					'name' => __(sprintf('%ss', ucwords(str_replace("_", " ", self::POST_TYPE)))),
    					'singular_name' => __(ucwords(str_replace("_", " ", self::POST_TYPE)))
    				),
					'labels' => array(
						'name' => __(sprintf('%s', self::PLURAL)),
						'singular_name' => __(sprintf('%s', self::SINGULAR)),
						'add_new' => __(sprintf('Add New %s', self::SINGULAR)),
						'add_new_item' => __(sprintf('Add New %s', self::SINGULAR)),
						'edit_item' => __(sprintf('Edit %s', self::SINGULAR)),
						'new_item' => __(sprintf('New %s', self::SINGULAR)),
						'view_item' => __(sprintf('View %s', self::SINGULAR)),
						'search_items' => __(sprintf('Search %s', self::PLURAL)),
						'not_found' => __(sprintf('No %s found', self::PLURAL)),
						'not_found_in_trash' => __(sprintf('No found in Trash%s', self::PLURAL)),
					),
    				'public' => true,
    				'description' => __("A policy"),
					'taxonomies' => array('post_tag'),
    				'supports' => array(
    					'title', 'editor', 'excerpt', 'author', 
    				),
					'has_archive' => true,
					'rewrite' => array(
						'slug' => self::ARCHIVE_SLUG,
						'with_front' => false,
					),
					'menu_position' => 30,
					'menu_icon' => JUXTALEARN_HUB_URL.'/images/icons/policy.png',
    			)
    		);
			$args = JuxtaLearn_Hub::get_taxonomy_args("Sector","Sectors");
			register_taxonomy( 'juxtalearn_hub_sector', array(self::POST_TYPE, 'student_problem'), $args );
		
			$args = JuxtaLearn_Hub::get_taxonomy_args("Country", "Countries");
			register_taxonomy( 'juxtalearn_hub_country', array(self::POST_TYPE, 'student_problem', 'location'), $args );
			
			$args = JuxtaLearn_Hub::get_taxonomy_args("Locale","Locales");
			register_taxonomy( 'juxtalearn_hub_locale', array(self::POST_TYPE), $args );
			
			$countries = get_terms( 'juxtalearn_hub_country', array( 'hide_empty' => false ) );
			
			// if no terms then lets add our terms
			if( empty( $countries ) ){
				$countries = $this->set_countries();
				foreach( $countries as $country_code => $country_name ){
					if( !term_exists( $country_name, 'juxtalearn_hub_country' ) ){
						wp_insert_term( $country_name, 'juxtalearn_hub_country', array( 'slug' => $country_code ) );
					}
				}
			}
			
			
    	}
	
    	/**
    	 * Save the metaboxes for this custom post type
    	 */
    	public function save_post($post_id)
    	{
            // verify if this is an auto save routine. 
            // If it is our form has not been submitted, so we dont want to do anything
			if (get_post_type($post_id) != self::POST_TYPE) return;
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
			if (isset($_POST['juxtalearn_hub_nonce']) && !wp_verify_nonce($_POST['juxtalearn_hub_nonce'], plugin_basename(__FILE__))) return;
			if (!current_user_can('edit_post', $post_id)) return;

			foreach($this->options as $name => $option)
			{
				// Update the post's meta field
				$field_name = "juxtalearn_hub_$name";
				if (isset($_POST[$field_name])){
					if ($option['save_as'] == 'term'){
						wp_set_object_terms( $post_id, $_POST[$field_name], $field_name);
					} else {
						update_post_meta($post_id, $field_name, $_POST[$field_name]);
					}
				}
			}
    	} // END public function save_post($post_id)

    	/**
    	 * hook into WP's admin_init action hook
    	 */
    	public function admin_init()
    	{			

			$this->options = array_merge($this->options, array(
				'country' => array(
					'type' => 'select',
					'save_as' => 'term',
					'position' => 'side',
					'label' => "Country",
					'options' => get_terms('juxtalearn_hub_country', 'hide_empty=0'),
					),
			));
			$this->options = array_merge($this->options, array(
				'locale' => array(
					'type' => 'select',
					'save_as' => 'term',
					'position' => 'side',
					'quick_edit' => true,
					'label' => 'Locale',
					'options' => get_terms('juxtalearn_hub_locale', 'hide_empty=0&orderby=title'),
					)
			 ));
			$this->options = array_merge($this->options, array(
				'sector' => array(
					'type' => 'multi-select',
					'save_as' => 'term',
					'position' => 'side',
					'quick_edit' => true,
					'label' => 'Sector',
					'options' => get_terms('juxtalearn_hub_sector', 'hide_empty=0&orderby=id'),
					)
			 ));
			 $this->options = array_merge($this->options, array(
				'citation' => array(
					'type' => 'text',
					'save_as' => 'post_meta',
					'position' => 'bottom',
					'label' => 'Citation'
					)
			 ));
				
				

			// Add metaboxes
    		add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
    	} // END public function admin_init()
			

    	/**
    	 * hook into WP's add_meta_boxes action hook
    	 */
    	public function add_meta_boxes()
    	{
// Add this metabox to every selected post
    		add_meta_box( 
    			sprintf('wp_juxtalearn_hub_%s_section', self::POST_TYPE),
    			sprintf('%s Information', ucwords(str_replace("_", " ", self::POST_TYPE))),
    			array(&$this, 'add_inner_meta_boxes'),
    			self::POST_TYPE,
				'normal',
				'high'
    	    );
			
    		add_meta_box( 
    			sprintf('wp_juxtalearn_hub_%s_side_section', self::POST_TYPE),
    			sprintf('%s Information', ucwords(str_replace("_", " ", self::POST_TYPE))),
    			array(&$this, 'add_inner_meta_boxes_side'),
    			self::POST_TYPE,
				'side'
    	    );	
			remove_meta_box('tagsdiv-juxtalearn_hub_sector',self::POST_TYPE,'side');
			remove_meta_box('tagsdiv-juxtalearn_hub_country',self::POST_TYPE,'side');
			remove_meta_box('tagsdiv-juxtalearn_hub_locale',self::POST_TYPE,'side');			
    					
    	} // END public function add_meta_boxes()

		 /**
		 * called off of the add meta box
		 */		
		public function add_inner_meta_boxes_side($post)
		{		
			wp_nonce_field(plugin_basename(__FILE__), 'juxtalearn_hub_nonce');
			$sub_options = JuxtaLearn_Hub::filterOptions($this->options, 'position', 'side');
			include(sprintf("%s/custom_post_metaboxes.php", dirname(__FILE__)));			
		} // END public function add_inner_meta_boxes($post)
		
		/**
		 * called off of the add meta box
		 */		
		public function add_inner_meta_boxes($post)
		{		
			// Render the job order metabox
			$sub_options = JuxtaLearn_Hub::filterOptions($this->options, 'position', 'bottom');
			include(sprintf("%s/custom_post_metaboxes.php", dirname(__FILE__)));			
		} // END public function add_inner_meta_boxes($post)
		
		public function set_countries(){
			$jsonIterator = new RecursiveIteratorIterator(
					 new RecursiveArrayIterator(json_decode(file_get_contents(JUXTALEARN_HUB_PATH."/lib/countries.json"), TRUE)),
					 RecursiveIteratorIterator::SELF_FIRST);
			$countries = array();
			foreach ($jsonIterator as $key => $val) {
				if(!is_array($val)) {
					$countries[$key] = $val;
				} 
			}
			return $countries;
		}
		

	} // END class Post_Type_Template
} // END if(!class_exists('Post_Type_Template'))