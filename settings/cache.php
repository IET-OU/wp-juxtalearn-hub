<?php


class JuxtaLearn_Hub_Settings_Cache {
	function __construct() {
		register_activation_hook(JUXTALEARN_HUB_REGISTER_FILE, array(&$this, 'activate'));
		register_deactivation_hook(JUXTALEARN_HUB_REGISTER_FILE, array(&$this, 'deactivate'));
		
		add_action('admin_init', array(&$this, 'save'));
		add_action('admin_menu', array(&$this, 'admin_menu'));
	}
	
	function activate() {
		add_option('juxtalearn_hub_caching', true);
	}
	
	function deactivate() {
		delete_option('juxtalearn_hub_caching');
	}
	
	function admin_menu() {
		add_submenu_page(
			'juxtalearn_hub',
			"Caching",
			"Caching",
			'edit_posts',
			'juxtalearn_hub_cache',
			array(&$this, 'page')
		);
	}
	
	function page() {
		if (!current_user_can('edit_posts')) wp_die("You do not have sufficient permissions to access this page.");
		
		$caching = get_option('juxtalearn_hub_caching');
		$cache = JuxtaLearn_Hub_Shortcode::get_all_cache();
		
		?>
		
		<div id="juxtalearn_hub_cache" class="wrap">
			<h2>JuxtaLearn Hub Caching</h2>
			<p>JuxtaLearn Hub caches the content of any of it's shortcodes you use in your site.</p>

			<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
				<?php wp_nonce_field('nonce_juxtalearn_hub_cache'); ?>
				<p>
					<?php if ($caching) { ?>
						<input type="submit" name="juxtalearn_hub_disable_cache" value="Disable Caching" />
					<?php } else { ?>
						<input type="submit" name="juxtalearn_hub_enable_cache" value="Enable Caching" />
					<?php } ?>
					<input type="submit" name="juxtalearn_hub_clear_cache" value="Clear Cache" />
				</p>
				<input type="hidden" name="juxtalearn_hub_cache_settings" value="save" />
			</form>
			
			<?php if ($caching) { ?>
				<h3>Cached Shortcodes</h3>
				<?php if (empty($cache)) { ?>
					<p>No cached shortcodes.</p>
				<?php } else { ?>
					<table>
						<tr>
							<th>count</th>
							<th>shortcode</th>
						</tr>
						<?php foreach ($cache as $shortcode) { ?>
							<tr>
								<td><?php echo $shortcode->count; ?></td>
								<td><?php echo $shortcode->shortcode; ?></td>
							</tr>
						<?php } ?>
					</table>
				<?php } ?>
			<?php } ?>
		</div>
		
		<?php
	}
	
	function save() {
		if (isset($_POST['juxtalearn_hub_cache_settings']) && check_admin_referer('nonce_juxtalearn_hub_cache')) {
			if (isset($_POST['juxtalearn_hub_disable_cache'])) {
				update_option('juxtalearn_hub_caching', false);
				JuxtaLearn_Hub::add_admin_notice("Caching disabled.");
			} else if (isset($_POST['juxtalearn_hub_enable_cache'])) {
				update_option('juxtalearn_hub_caching', true);
				JuxtaLearn_Hub::add_admin_notice("Caching enabled.");
			} else if (isset($_POST['juxtalearn_hub_clear_cache'])) {
				JuxtaLearn_Hub_Shortcode::clear_cache();
				JuxtaLearn_Hub::add_admin_notice("Cache cleared.");
			}
			
			header("Location: ".$_SERVER['REQUEST_URI']);
			die;
		}
	}
}