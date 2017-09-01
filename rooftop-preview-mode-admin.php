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
 * Plugin Name:       Rooftop Preview Mode
 * Plugin URI:        http://github.com/rooftopcms/rooftop-preview-mode-admin
 * Description:       The rooftop-preview-mode-admin plugin add's the ability to preview content without publishing it when Rooftop is properly configured and the client site is built with preview support.
 * Version:           1.2.1
 * Author:            RooftopCMS
 * Author URI:        http://rooftopcms.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
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
