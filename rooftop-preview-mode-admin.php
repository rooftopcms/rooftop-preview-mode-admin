<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://error.agency
 * @since             1.0.0
 * @package           Rooftop_Preview_Mode_Admin
 *
 * @wordpress-plugin
 * Plugin Name:       Rooftop Preview Mode Admin
 * Plugin URI:        http://rooftopcms.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Error
 * Author URI:        http://error.agency
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rooftop-preview-mode-admin
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rooftop-preview-mode-admin-activator.php
 */
function activate_rooftop_preview_mode_admin() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rooftop-preview-mode-admin-activator.php';
	Rooftop_Preview_Mode_Admin_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-rooftop-preview-mode-admin-deactivator.php
 */
function deactivate_rooftop_preview_mode_admin() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rooftop-preview-mode-admin-deactivator.php';
	Rooftop_Preview_Mode_Admin_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_rooftop_preview_mode_admin' );
register_deactivation_hook( __FILE__, 'deactivate_rooftop_preview_mode_admin' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-rooftop-preview-mode-admin.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_rooftop_preview_mode_admin() {

	$plugin = new Rooftop_Preview_Mode_Admin();
	$plugin->run();

}
run_rooftop_preview_mode_admin();
