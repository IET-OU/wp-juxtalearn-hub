<?php

new JuxtaLearn_Hub_Shortcode_TTBar();
class JuxtaLearn_Hub_Shortcode_TTBar extends JuxtaLearn_Hub_Shortcode {
	var $shortcode = 'trickytopic_bar';
	var $defaults = array();

	static $post_types_with_sessions = NULL;

	function content() {
		ob_start();
		extract($this->options);
		
		$errors = array();
		?>
        <style type="text/css">.shared, .bar, .label {
	
		  font-family: "Open Sans", Helvetica, Arial, "Nimbus Sans L", sans-serif;
		}
		.label {
			font-style:italic;
			font-size:90%;
		}
		.malebar, .femalebar {
			opacity: 0.6;
		}
		.malebar {
		  fill: rgb(255, 146, 6);
		}
		.femalebar {
			fill: rgb(85, 85, 85);
		}
		.highlight rect.malebar, .highlight rect.femalebar {
		  opacity: 0.8;
		}
		text.malebar, text.femalebar {
		  display: block;
		}
		.highlight text {
		  display: block;
		  fill: #000;
		}
        .highlight text.shared{ 
		  font-weight:bold;
		}</style>
        <script type="text/javascript">
		var bar_data = 
	<?php 
		$output = array();
	    $args = array('post_type' => 'trickytopic',
					   'orderby' => 'title', 
					   'order' => 'ASC',
					   'posts_per_page' => -1,
					   'post_status' => 'publish',
					   );
		$the_query = new WP_Query($args);	
		if ( $the_query->have_posts() ) {
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$metaquery = array(	array(
											'key' => 'juxtalearn_hub_trickytopic_id',
											'value' => get_the_ID(),
											'compare' => '='
										));
					$output[] = array('name' => get_the_title(),
								  'url' => get_permalink(),
								  'barNeg' => count(get_posts( array(
															'post_type' => 'example', 
															'meta_query' => $metaquery,
															'tax_query' => array(
																				array(
																					'taxonomy' => 'juxtalearn_hub_polarity',
																					'field' => 'slug',
																					'terms' => 'neg',
																				)
																			)
																	)
															)),
								  'barPos' => count(get_posts( array(
															'post_type' => 'example', 
															'meta_query' => $metaquery,
															'tax_query' => array(
																				array(
																					'taxonomy' => 'juxtalearn_hub_polarity',
																					'field' => 'slug',
																					'terms' => 'pos',
																				)
																			)
																	)
															)),															
															
							);
							
				}
		}
		print_r(json_encode($output));
	?>;
	
		tt_w = document.getElementById("content").offsetWidth,
		tt_h = parseInt(tt_w*9/16);
		
		
    </script>
        <script src="<?php echo plugins_url( 'js/tt_bars.js' , JUXTALEARN_HUB_REGISTER_FILE )?>"></script>
        
        <div id="vis"></div>
        <?php
		return ob_get_clean();
	}
}