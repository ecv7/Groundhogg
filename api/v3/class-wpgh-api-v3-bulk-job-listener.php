<?php
/**
 * Groundhogg API Emails
 *
 * This class provides a front-facing JSON API that makes it possible to
 * query data from the other application application.
 *
 * @package     WPGH
 * @subpackage  Classes/API
 *
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPGH_API_V3_EMAILS Class
 *
 * Renders API returns as a JSON
 *
 * @since  1.5
 */
class WPGH_API_V3_BULK_JOB_LISTENER extends WPGH_API_V3_BASE
{

    /**
     * @var
     */
    private $broadcast_bulk_job;

    public function __construct()
    {
        if ( ! class_exists( 'WPGH_Broadcast_Bulk_Job' ) ){
            include_once WPGH_PLUGIN_DIR . 'includes/admin/broadcasts/class-wpgh-broadcast-bulk-job.php';
        }

        $this->broadcast_bulk_job = new WPGH_Broadcast_Bulk_Job();

        parent::__construct();
    }

    public function register_routes()
    {

        $auth_callback = $this->get_auth_callback();

        register_rest_route('gh/v3', '/bulk-jobs', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_init_data' ],
                'permission_callback' => $auth_callback,
                'args' => [
                    'bulk_action' => [
                        'required' => true
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'ajax' ],
                'permission_callback' => $auth_callback,
                'args' => [
                    'bulk_action' => [
                        'required' => true
                    ],
                    'items' => [
                        'required' => true
                    ]
                ]
            ],
        ] );
    }

    /**
     * @return null|string|string[]|WP_Error
     */
    private function get_bulk_action( )
    {
        // Sanitize the bulk action
        // Permitted Characters 0-9, A-z, _, -, / to keep inline with the Groundhogg Action Structure. No spaces.
        $bulk_action = preg_replace( '/[^0-9A-z_\-\/]/', '', $_GET[ 'bulk_action' ] );

        if ( ! $bulk_action ){
            return self::ERROR_403( 'invalid_action', 'Invalid bulk action provided.' );
        }

        return $bulk_action;
    }

    public function query( WP_REST_Request $request )
    {
        $items = apply_filters( "groundhogg/bulk_job/{$this->get_bulk_action()}/query", [] );
        return self::SUCCESS_RESPONSE( [ 'items' => $items ] );
    }

    public function max_items( WP_REST_Request $request )
    {
        $items = apply_filters( "groundhogg/bulk_job/{$this->get_bulk_action()}/query", [] );
        $max_items = apply_filters( "groundhogg/bulk_job/{$this->get_bulk_action()}/max_items", 25, $items );
        return self::SUCCESS_RESPONSE( [ 'max_items' => $max_items ] );
    }

    public function get_init_data( WP_REST_Request $request )
    {
        $items = apply_filters( "groundhogg/bulk_job/{$this->get_bulk_action()}/query", [] );
        $max_items = apply_filters( "groundhogg/bulk_job/{$this->get_bulk_action()}/max_items", 25, $items );
        return self::SUCCESS_RESPONSE( [ 'items' => $items, 'max_items' => $max_items, 'config' => get_transient( 'gh_get_broadcast_config' ) ] );
    }

    /**
     * Get a list of broadcast.
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function ajax( WP_REST_Request $request )
    {
        if ( ! current_user_can( 'perform_bulk_actions' ) ){
            return self::ERROR_INVALID_PERMISSIONS();
        }

        // Normalize required global args from JSON body.
        $_GET[ 'bulk_action' ]  = $request->get_param('bulk_action' );
        $_POST[ 'items' ]       = $request->get_param('items' );
        $_POST[ 'the_end' ]     = $request->get_param('the_end' );

        //Double check and that everything is okay.
        $action = $this->get_bulk_action();

//        wp_send_json( [ 'action' => $action  ] );

        if ( is_wp_error( $action ) ){
            return $action;
        }

        $action = sanitize_text_field( "groundhogg/bulk_job/{$action}/ajax" );

        do_action( $action );

        return self::ERROR_403( 'invalid_action', 'Invalid bulk action provided.' );

    }

}