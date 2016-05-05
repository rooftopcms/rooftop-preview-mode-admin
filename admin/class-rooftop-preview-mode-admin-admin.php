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

    public function add_preview_button_metabox( $post_type ) {
        $endpoint = get_site_option( 'preview_mode_url' );

        if( is_admin() && current_user_can( 'manage_options' ) && isset( $endpoint->url ) ) {
            add_meta_box( $this->plugin_name."-metabox", "Preview Mode", array( $this, 'render_preview_mode_metabox' ), $post_type, 'side', 'default' );
        }
    }

    function render_preview_mode_metabox() {
        global $post;
        $endpoint = get_site_option( 'preview_mode_url');
        $id = $post->ID;
        $post_type = $post->post_type;
        $key = apply_filters( 'rooftop_generate_post_preview_key', $post );
        $form = <<<EOF

<input type="submit" name="rooftop-preview-button" value="View in preview mode" class="button button-primary button-large" data-preview="true" data-preview-url="{$endpoint->url}" data-preview-id="{$id}" data-preview-post_type="{$post_type}" data-preview-key="{$key}" />

EOF;
        echo $form;
    }

    /**
     * generate a random salt if we haven't done so already
     */
    public function preview_admin_generate_site_salt() {
        if ( current_user_can( 'manage_options' ) && !get_site_option( 'preview_mode_salt', false ) ) {
            update_site_option( 'preview_mode_salt', uniqid(mt_rand(), true) );
        }
    }

    /**
     * @param $post_id
     *
     * since the preview button is nested in post's <form> tag, we check for 'rooftop-preview-button' in the POST,
     * if we have that, then the preview button was clicked. in this case, since we need to POST to the client, we render a hidden
     * form into the page and immediately submit it with JS.
     */
    public function preview_mode_redirect( $post_id ) {
        if( isset( $_POST['rooftop-preview-button'] ) ) {
            $post = get_post( $post_id );

            $endpoint = get_site_option( 'preview_mode_url');
            $id = $post->ID;
            $post_type = $post->post_type;
            $key = apply_filters( 'rooftop_generate_post_preview_key', $post );
            $form = <<<EOF

<form action="{$endpoint->url}" name="rooftop_preview_form" method="POST">
    <input type="hidden" name="id" value="{$id}" />
    <input type="hidden" name="post_type" value="{$post_type}" />
    <input type="hidden" name="preview_key" value="{$key}" />
</form>

<script type="text/javascript">
document.rooftop_preview_form.submit()
</script>

EOF;
            echo $form;

            exit;
        }
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
        $salt     = get_site_option( 'preview_mode_salt', false );
        require_once plugin_dir_path( __FILE__ ) . 'partials/rooftop-preview-mode-admin-index.php';
    }

    public function update_preview_mode_url() {
        $endpoint = (object)array('url' => $_POST['url']);
        update_site_option( 'preview_mode_url', $endpoint );

        $this->preview_mode_admin_index();
    }
}
