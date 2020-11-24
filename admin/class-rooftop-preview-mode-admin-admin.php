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

    public function alter_preview_link($link) {
        global $post;

        $url = "/wp-admin/admin.php?page=rooftop-preview-mode-admin-preview&post=$post->ID&id=$post->ID";

        return $url;
    }

    /**
     * @param $post_id
     *
     */
    public function preview_mode_redirect_page( $post_id ) {
        $endpoint = get_option( 'preview_mode_url' );
        $post = get_post( $post_id );

        $revisions = wp_get_post_revisions( $post->ID );
        $revision_ids = array_reverse( array_keys( $revisions ) );
        $revision_id = array_pop( $revision_ids );

        if( ! $revision_id ) {
            echo "<p>No preview revisions available</p><br/><br/>";
            exit;
        }

        if( ! $endpoint ) {
            $link = "<a href='?page=rooftop-preview-mode-admin-overview'>here</a>";
            echo "<br/><br/>Please configure your preview mode endpoint ${link}";
            return;
        }else {
            echo "<p>Previewing...</p><br/><br/>";
        }

        $post_path = apply_filters( 'rooftop/build_post_path', $post_id );
        $preview_token = apply_filters( 'rooftop/preview_api_key', $revision_id );

        $form = <<<EOF
<form action="{$endpoint->url}$post_path?token=$preview_token&id=$post_id&revision_id=$revision_id" name="rooftop_preview_form" method="GET">
    <input type="hidden" name="id" value="{$post_id}" />
    <input type="hidden" name="revision_id" value="{$revision_id}" />
    <input type="hidden" name="token" value="{$preview_token}" />
</form>

<script type="text/javascript">
    document.rooftop_preview_form.submit()
</script>
EOF;
        echo $form;

        exit;
    }

    /*******
     * Add the Preview mode admin interface
     *******/
    public function preview_menu_links() {
        $rooftop_preview_mode_menu_slug = "rooftop-overview";
        add_menu_page("Preview Mode", "Preview Mode", "manage_options", $this->plugin_name."-overview", function() {
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
        } );

        add_submenu_page($rooftop_preview_mode_menu_slug."hidden", "Preview Mode Redirect", "Preview Mode Redirect", "edit_others_posts", $this->plugin_name."-preview", function() {
            $this->preview_mode_redirect_page($_GET['post'], $_GET['id']);
            exit;
        } );
    }

    public function preview_mode_admin_index() {
        $endpoint = get_option( 'preview_mode_url' );
        require_once plugin_dir_path( __FILE__ ) . 'partials/rooftop-preview-mode-admin-index.php';
    }

    public function update_preview_mode_url() {
        $endpoint = (object)array('url' => $_POST['url']);
        $u = update_option( 'preview_mode_url', $endpoint );
        
        $this->preview_mode_admin_index();
    }

    public function alter_draft_posts_slug( $data, $post_array ) {
        $has_post_name = array_key_exists( 'post_name', $post_array ) && $post_array['post_name'] != "";

        if( $post_array['post_status'] === "draft" && !$has_post_name ) {
            $slug_id = array_key_exists( 'id', $post_array ) ? $post_array['id'] : wp_generate_password( 10, false );
            $slug_parts = array("rt-draft", $slug_id, sanitize_title( $post_array['post_title'] ) );
            $slug       = implode( "-", array_filter( $slug_parts ) );
            $data['post_name'] = wp_unique_post_slug( $slug, $post_array['ID'], $post_array['post_status'], $post_array['post_type'], $post_array['post_parent'] );
        }elseif( $post_array['post_status'] === "publish" && preg_match( '/^rt-draft-/', @$post_array['post_name'] ) ) {
            $data['post_name'] = '';
        }

        return $data;
    }
}
