<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://chrzescijanskarandka.pl
 * @since             1.0.0
 * @package           Chd_Mobile_Api
 *
 * @wordpress-plugin
 * Plugin Name:       Christian Date Mobile API
 * Plugin URI:        http://chrzescijanskarandka.pl
 * Description:       Plugin adds Rest API for mobile application
 * Version:           1.0.0
 * Author:            Filip Rak
 * Author URI:        http://filiprak.tk/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       chd-mobile-api
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CHD_MOBILE_API_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-chd-mobile-api-activator.php
 */
function activate_chd_mobile_api() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-chd-mobile-api-activator.php';
	Chd_Mobile_Api_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-chd-mobile-api-deactivator.php
 */
function deactivate_chd_mobile_api() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-chd-mobile-api-deactivator.php';
	Chd_Mobile_Api_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_chd_mobile_api' );
register_deactivation_hook( __FILE__, 'deactivate_chd_mobile_api' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-chd-mobile-api.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_chd_mobile_api() {

	$plugin = new Chd_Mobile_Api();
	$plugin->run();

}
run_chd_mobile_api();
