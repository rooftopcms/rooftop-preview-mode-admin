<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://error.agency
 * @since      1.0.0
 *
 * @package    Rooftop_Preview_Mode_Admin
 * @subpackage Rooftop_Preview_Mode_Admin/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Rooftop_Preview_Mode_Admin
 * @subpackage Rooftop_Preview_Mode_Admin/public
 * @author     Error <info@error.agency>
 */
class Rooftop_Preview_Mode_Admin_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Rooftop_Preview_Mode_Admin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rooftop_Preview_Mode_Admin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rooftop-preview-mode-admin-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Rooftop_Preview_Mode_Admin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rooftop_Preview_Mode_Admin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/rooftop-preview-mode-admin-public.js', array( 'jquery' ), $this->version, false );

	}

    /**
     *
     * Called by rest_api_init hook
     * Use register_rest_field to add a field to the response, with a
     * value populated by the given callback method (get_callback).
     *
     */
    public function add_fields() {
        $endpoint = get_site_option( 'preview_mode_url' );
        if( !isset( $endpoint->url ) ) {return;}


        $types = get_post_types(array(
            'public' => true
        ));

        foreach($types as $key => $type) {
            register_rest_field( $type,
                'preview_key',
                array(
                    'get_callback'    => array( $this, 'add_preview_key_field' ),
                    'update_callback' => null,
                    'schema'          => null,
                )
            );
        }
    }

    /**
     * @param $object
     * @param $field
     * @param $request
     * @return string
     */
    function add_preview_key_field($object, $field, $request) {
        global $post;
        $key = apply_filters( 'rooftop_generate_post_preview_key', $post );

        return $key;
    }

    function generate_post_preview_key( $post ) {
        $components = [$post->post_type, $post->ID, $post->post_modified];
        $key = md5(implode("-", $components));

        return $key;
    }
}
