<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://error.agency
 * @since      1.0.0
 *
 * @package    Rooftop_Preview_Mode_Admin
 * @subpackage Rooftop_Preview_Mode_Admin/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Rooftop_Preview_Mode_Admin
 * @subpackage Rooftop_Preview_Mode_Admin/admin
 * @author     Error <info@error.agency>
 */
class Rooftop_Preview_Mode_Admin_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
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
		 * defined in Rooftop_Preview_Mode_Admin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rooftop_Preview_Mode_Admin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rooftop-preview-mode-admin-admin.css', array(), $this->version, 'all' );

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
		 * defined in Rooftop_Preview_Mode_Admin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rooftop_Preview_Mode_Admin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/rooftop-preview-mode-admin-admin.js', array( 'jquery' ), $this->version, false );

	}

    /*******
     * Add the Preview mode admin interface
     *******/
    public function preview_menu_links() {
        $rooftop_preview_mode_menu_slug = "rooftop-overview";
        add_submenu_page($rooftop_preview_mode_menu_slug, "Preview Mode", "Preview Mode", "manage_options", $this->plugin_name."-overview", function() {
            if($_POST && array_key_exists('method', $_POST)) {
                $method = strtoupper($_POST['method']);
            }elseif($_POST && array_key_exists('id', $_POST)) {
                $method = 'PATCH';
            }else {
                $method = $_SERVER['REQUEST_METHOD'];
            }

            switch($method) {
                case 'GET':
                    $this->preview_mode_admin_index();
                    break;
                default:
                     if( !isset( $_POST['preview-mode-admin-field-token']) || !wp_verify_nonce( $_POST['preview-mode-admin-field-token'], 'rooftop-preview-mode-admin') ) {
                         echo "Couldn't verify form input";
                         exit;
                     }
                    $this->update_preview_mode_url();
            }
        });
    }

    public function preview_mode_admin_index() {
        $endpoint = get_site_option( 'preview_mode_url' );
        require_once plugin_dir_path( __FILE__ ) . 'partials/rooftop-preview-mode-admin-index.php';
    }

    public function update_preview_mode_url() {
        $endpoint = (object)array('url' => $_POST['url']);
        update_site_option( 'preview_mode_url', $endpoint );

        $this->preview_mode_admin_index();
    }
}
