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

    public function previewable_content_types() {
        global $_wp_post_type_features;

        $post_types = get_post_types( array( 'public' => true, 'show_in_rest' => true ) );
        $types_with_revisons = array_filter( $post_types, function( $type ) use ( $_wp_post_type_features ) {
            $type_exists = isset( $_wp_post_type_features[$type] );
            $type_has_revisions_capability = ( $type_exists && @$_wp_post_type_features[$type]['revisions'] == true );

            $supports_revisions = $type_exists && $type_has_revisions_capability;

            return $supports_revisions;
        } );

        return $types_with_revisons;
    }

    public function add_preview_route( $server ) {
        $post_types = apply_filters( 'rooftop_previewable_content_types', array() );

        $routes = $server->get_routes();

        foreach( $post_types as $type ) {
            array_filter( array_keys( $routes ), function( $route ) use ( $type, $routes ) {
                $pluralized_type = "${type}s";

                if( preg_match( "/\/${pluralized_type}$/", $route, $matches ) ) {
                    $rest_base     = preg_replace( "/\/${pluralized_type}$/", "", $route );
                    $rest_base     = preg_replace( "/^\//", "", $rest_base );

                    $preview_route = "/${pluralized_type}/(?P<parent_id>[\d]+)/preview";

                    register_rest_route( $rest_base, $preview_route, array(
                        array(
                            'methods'             => WP_REST_Server::READABLE,
                            'callback'            => array( $this, 'get_preview_version' ),
                            'permission_callback' => array( $this, 'check_preview_permission')
                        )
                    ) );
                }
            } );
        }
    }

    function get_preview_version( $request ) {
        global $post;
        $post = get_post( $request['parent_id'] );

        $preview_post = wp_get_post_autosave( $post->ID );

        if( !$preview_post ) {
            $preview_post = $post;
        }

        if( $preview_post ) {
            $method = "GET";
            $route  = $request->get_route();

            $preview_request = new WP_REST_Request($method, $route);

            // first get the response we would send back for the parent post (we'll need some of its attributes, like 'links')
            $post_data = $this->prepare_item_for_response( $post, $post->post_type, $preview_request );
            $post_response = rest_ensure_response( $post_data );

            $preview_data = $this->prepare_item_for_response( $preview_post, $post->post_type, $preview_request );
            $preview_response = rest_ensure_response( $preview_data );
            $original_post_links = WP_REST_Server::get_compact_response_links( $post_response );

            $rooftop_links_filter = "rooftop_prepare_{$post->post_type}_links";
            $rooftop_preview_links = apply_filters( $rooftop_links_filter, $this->prepare_links( $post ), $post );
            $rooftop_links = array_merge( $original_post_links, $rooftop_preview_links );
            $preview_response->add_links( $rooftop_links );

            return $preview_response;
        }else {
            return new Custom_WP_Error( 'rest_no_route', 'This post has no revisions available to preview', array( 'status' => 404 ) );
        }
    }

    function check_preview_permission( $request ) {
        $post = get_post( $request['parent_id'] );
        $post_type = get_post_type_object( $post->post_type );
        $post_status_obj = get_post_status_object( $post->post_status );

        if( current_user_can( $post_type->cap->edit_post, $post->ID ) ) {
            return true;
        }

        // Can we read the post?
        if ( 'publish' === $post->post_status || current_user_can( $post_type->cap->read_post, $post->ID ) ) {
            return true;
        }

        if ( $post_status_obj && $post_status_obj->public ) {
            return true;
        }

        if ( 'inherit' === $post->post_status && $post->post_parent > 0 ) {
            $parent = get_post( $post->post_parent );

            if ( $this->check_preview_permission( $parent ) ) {
                return true;
            }
        }

        // If we don't have a parent, but the status is set to inherit, assume
        // it's published (as per get_post_status()).
        if ( 'inherit' === $post->post_status ) {
            return true;
        }
        
        return false;
    }

    function prepare_item_for_response( $preview_post, $type, $preview_request ) {
        global $post;

        setup_postdata( $post );

        // Base fields for every post.
        $preview_data = array(
            'id'             => $post->ID,
            'author'         => (int)$post->post_author,
            'comment_status' => $post->comment_status,
            'preview_key'    => apply_filters( 'rooftop_generate_post_preview_key', $post ),
            'guid'           => array(
                /** This filter is documented in wp-includes/post-template.php */
                'rendered'   => apply_filters( 'get_the_guid', $preview_post->guid ),
                'raw'        => $preview_post->guid,
            ),
            'date'           => $post->post_date,
            'date_gmt'       => $post->post_date_gmt,
            'featured_media' => $featured_media = (int)get_post_thumbnail_id( $post->ID ),
            'menu_order'     => $post->menu_order,
            'modified'       => $post->post_modified,
            'modified_gmt'   => $post->post_modified_gmt,
            'parent'         => $post->post_parent,
            'password'       => $post->post_password,
            'ping_status'    => $post->ping_status,
            'slug'           => $post->post_name,
            'status'         => $post->post_status,
            'type'           => $post->post_type,
            'template'       => $post->page_template,
            'title'          => array( 'rendered' => $preview_post->post_title ),
            'link'           => get_permalink( $preview_post->ID ),
        );

        $preview_data = $this->add_additional_fields_to_object( $preview_data, $preview_request );
        // Wrap the data in a response object.
        $preview_response = rest_ensure_response( $preview_data );

        /**
         * Filter the post data for a response.
         *
         * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
         * prepared for the response.
         *
         * @param WP_REST_Response   $response   The response object.
         * @param WP_Post            $post       Post object.
         * @param WP_REST_Request    $request    Request object.
         */

        /*
         * hooks that we call as part of the rest_prepare_$post_type hooks will expect the post we're passing in
         * to be of a certain type. ie, we add a hook on rest_prepare_page, but if we dont override preview_post's post_type,
         * the hook will be rest_prepare_revision
         */
        $preview_post->post_type   = $post->post_type;   // call the right hooks
        $preview_post->post_parent = $post->post_parent; // ensure we have the same parent/child hierarchy

        $prepared = apply_filters( 'rest_prepare_'.$type, $preview_response, $preview_post, $preview_request );

        return $prepared;
    }

    function generate_post_preview_key( $original_post ) {
        $post = wp_get_post_autosave( $original_post->ID );
        
        if( !$post ) {
            $post = $original_post;
        }

        $components = [$post->post_type, $post->ID, $post->post_modified];
        $key = md5(implode("-", $components));

        return $key;
    }

    /**
     * Add the values from additional fields to a data object.
     *
     * @param array  $object
     * @param WP_REST_Request $request
     * @return array modified object with additional fields.
     */
    protected function add_additional_fields_to_object( $object, $request ) {

        $additional_fields = $this->get_additional_fields( $object['type'] );

        foreach ( $additional_fields as $field_name => $field_options ) {

            if ( ! $field_options['get_callback'] ) {
                continue;
            }

            $object[ $field_name ] = call_user_func( $field_options['get_callback'], $object, $field_name, $request, $object['type'] );
        }

        return $object;
    }
    /**
     * Get all the registered additional fields for a given object-type.
     *
     * @param  string $object_type
     * @return array
     */
    protected function get_additional_fields( $object_type = null ) {

        if ( ! $object_type ) {
            return array();
        }

        global $wp_rest_additional_fields;

        if ( ! $wp_rest_additional_fields || ! isset( $wp_rest_additional_fields[ $object_type ] ) ) {
            return array();
        }

        return $wp_rest_additional_fields[ $object_type ];
    }

    protected function prepare_links( $post ) {
        $base = $post->post_type."s";
        $post_type = $post->post_type;

        // Entity meta
        $links = array(
            'self' => array(
                'href'   => rest_url( trailingslashit( $base ) . $post->ID ),
            ),
            'collection' => array(
                'href'   => rest_url( $base ),
            ),
            'about'      => array(
                'href'   => rest_url( '/wp/v2/types/' . $post_type ),
            ),
        );

        if ( ( in_array( $post->post_type, array( 'post', 'page' ) ) || post_type_supports( $post->post_type, 'author' ) )
            && ! empty( $post->post_author ) ) {
            $links['author'] = array(
                'href'       => rest_url( '/wp/v2/users/' . $post->post_author ),
                'embeddable' => true,
            );
        };

        if ( in_array( $post->post_type, array( 'post', 'page' ) ) || post_type_supports( $post->post_type, 'comments' ) ) {
            $replies_url = rest_url( '/wp/v2/comments' );
            $replies_url = add_query_arg( 'post', $post->ID, $replies_url );
            $links['replies'] = array(
                'href'         => $replies_url,
                'embeddable'   => true,
            );
        }

        if ( in_array( $post->post_type, array( 'post', 'page' ) ) || post_type_supports( $post->post_type, 'revisions' ) ) {
            $links['version-history'] = array(
                'href' => rest_url( trailingslashit( $base ) . $post->ID . '/revisions' ),
            );
        }
        $post_type_obj = get_post_type_object( $post->post_type );
        if ( $post_type_obj->hierarchical && ! empty( $post->post_parent ) ) {
            $links['up'] = array(
                'href'       => rest_url( trailingslashit( $base ) . (int) $post->post_parent ),
                'embeddable' => true,
            );
        }

        // If we have a featured media, add that.
        if ( $featured_media = get_post_thumbnail_id( $post->ID ) ) {
            $image_url = rest_url( 'wp/v2/media/' . $featured_media );
            $links['https://api.w.org/featuredmedia'] = array(
                'href'       => $image_url,
                'embeddable' => true,
            );
        }
        if ( ! in_array( $post->post_type, array( 'attachment', 'nav_menu_item', 'revision' ) ) ) {
            $attachments_url = rest_url( 'wp/v2/media' );
            $attachments_url = add_query_arg( 'parent', $post->ID, $attachments_url );
            $links['https://api.w.org/attachment'] = array(
                'href'       => $attachments_url,
            );
        }

        $taxonomies = get_object_taxonomies( $post->post_type );
        if ( ! empty( $taxonomies ) ) {
            $links['https://api.w.org/term'] = array();

            foreach ( $taxonomies as $tax ) {
                $taxonomy_obj = get_taxonomy( $tax );
                // Skip taxonomies that are not public.
                if ( empty( $taxonomy_obj->show_in_rest ) ) {
                    continue;
                }

                $tax_base = ! empty( $taxonomy_obj->rest_base ) ? $taxonomy_obj->rest_base : $tax;
                $terms_url = add_query_arg(
                    'post',
                    $post->ID,
                    rest_url( 'wp/v2/' . $tax_base )
                );

                $links['https://api.w.org/term'][] = array(
                    'href'       => $terms_url,
                    'taxonomy'   => $tax,
                    'embeddable' => true,
                );
            }
        }

        return $links;
    }
}
