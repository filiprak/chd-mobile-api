<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Chd_Mobile_Api
 * @subpackage Chd_Mobile_Api/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Chd_Mobile_Api
 * @subpackage Chd_Mobile_Api/admin
 * @author     Your Name <email@example.com>
 */
class Chd_Mobile_Api_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $chd_mobile_api    The ID of this plugin.
	 */
	private $chd_mobile_api;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $chd_mobile_api       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $chd_mobile_api, $version ) {

		$this->chd_mobile_api = $chd_mobile_api;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Chd_Mobile_Api_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Chd_Mobile_Api_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->chd_mobile_api, plugin_dir_url( __FILE__ ) . 'css/chd-mobile-api-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Chd_Mobile_Api_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Chd_Mobile_Api_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->chd_mobile_api, plugin_dir_url( __FILE__ ) . 'js/chd-mobile-api-admin.js', array( 'jquery' ), $this->version, false );

	}

}
