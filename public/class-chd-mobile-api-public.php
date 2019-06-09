<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Chd_Mobile_Api
 * @subpackage Chd_Mobile_Api/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Chd_Mobile_Api
 * @subpackage Chd_Mobile_Api/public
 * @author     Your Name <email@example.com>
 */
class Chd_Mobile_Api_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $chd_mobile_api The ID of this plugin.
     */
    private $chd_mobile_api;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    private $api_controllers;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $chd_mobile_api The name of the plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct($chd_mobile_api, $version)
    {

        $this->chd_mobile_api = $chd_mobile_api;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

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

        wp_enqueue_style($this->chd_mobile_api, plugin_dir_url(__FILE__) . 'css/chd-mobile-api-public.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

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

        wp_enqueue_script($this->chd_mobile_api, plugin_dir_url(__FILE__) . 'js/chd-mobile-api-public.js', array('jquery'), $this->version, false);

    }

    public function add_api_controller($controller)
    {
        if (is_array($this->api_controllers)) {
            $this->api_controllers[] = $controller;
        } else {
            $this->api_controllers = array($controller);
        }
    }

    public function init_rest_api()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/controllers/activities.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/controllers/messages.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/controllers/threads.php';

        $this->add_api_controller(new Activities_Route());
        $this->add_api_controller(new Messages_Route());
        $this->add_api_controller(new Threads_Route());

        if (is_array($this->api_controllers)) {
            foreach ($this->api_controllers as $controller) {
                $controller->register_routes();
            }
        }
    }

}
