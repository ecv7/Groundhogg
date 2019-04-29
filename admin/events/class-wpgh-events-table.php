<?php
/**
 * Events Table Class
 *
 * This class shows the events queue with bulk options to manage events or 1 at a time.
 *
 * @package     Admin
 * @subpackage  Admin/Emails
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WPGH_Events_Table extends WP_List_Table {

    /**
     * TT_Example_List_Table constructor.
     *
     * REQUIRED. Set up a constructor that references the parent constructor. We
     * use the parent reference to set some default configs.
     */
    public function __construct() {
        // Set parent defaults.
        parent::__construct( array(
            'singular' => 'event',     // Singular name of the listed records.
            'plural'   => 'events',    // Plural name of the listed records.
            'ajax'     => false,       // Does this table support ajax?
        ) );
    }

    /**
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information.
     */
    public function get_columns() {
        $columns = array(
            'cb'        => '<input type="checkbox" />', // Render a checkbox instead of text.
            'contact'   => _x( 'Contact', 'Column label', 'wp-funnels' ),
            'funnel'    => _x( 'Funnel', 'Column label', 'wp-funnels' ),
            'step'      => _x( 'Step', 'Column label', 'wp-funnels' ),
            'time'      => _x( 'Time', 'Column label', 'wp-funnels' ),
            'errors'    => _x( 'Errors', 'Column label', 'wp-funnels' ),
        );

        return apply_filters( 'wpgh_event_columns', $columns );
    }

    /**
     * Get a list of sortable columns. The format is:
     * 'internal-name' => 'orderby'
     * or
     * 'internal-name' => array( 'orderby', true )
     *
     * The second format will make the initial sorting order be descending
     * @return array An associative array containing all the columns that should be sortable.
     */
    protected function get_sortable_columns() {
        $sortable_columns = array(
            'contact'   => array( 'contact_id', false ),
            'funnel'    => array( 'funnel_id', false ),
            'step'      => array( 'step_id', false ),
            'time'      => array( 'time', false ),
        );
        return apply_filters( 'wpgh_event_sortable_columns', $sortable_columns );
    }

    public function single_row($item)
    {
        echo '<tr>';
        $this->single_row_columns( new WPGH_Event( $item->ID ) );
        echo '</tr>';
    }

    /**
     * @param $event WPGH_Event
     * @return string
     */
    protected function column_contact( $event )
    {


        if ( ! $event->contact->exists() )
            return sprintf( "<strong>(%s)</strong>",  _x( 'contact deleted', 'status', 'groundhogg' ) );

        $html = sprintf( "<a class='row-title' href='%s'>%s</a>",
            admin_url( 'admin.php?page=gh_events&view=contact&contact=' . $event->contact->ID ),
            $event->contact->email
        );

        return $html;
    }

    /**
     * @param $event WPGH_Event
     * @return string
     */
    protected function column_funnel( $event )
    {
        if ($event->type) {
            switch ($event->type) {
                default:
                case GROUNDHOGG_FUNNEL_EVENT:
                    $funnel = WPGH()->funnels->get( $event->funnel_id );
                    $title = ( $funnel )? $funnel->title : sprintf( '(%s)', _x( 'funnel deleted', 'status', 'groundhogg' ) ) ;
                    $view = sprintf( "view=funnel&funnel=%d", $event->funnel_id );
                    break;
                case GROUNDHOGG_BROADCAST_EVENT:
                    $title =  sprintf( __( '%s Broadcast', 'groundhogg' ), ucfirst( $event->step->get_type() ) );
                    $view = sprintf( "view=type&type=%d", GROUNDHOGG_BROADCAST_EVENT );
                    break;
                case GROUNDHOGG_EMAIL_NOTIFICATION_EVENT:
                    $title =  __( 'Email Notification', 'groundhogg' );
                    $view = sprintf( "view=type&type=%d", GROUNDHOGG_EMAIL_NOTIFICATION_EVENT );
                    break;
                case GROUNDHOGG_SMS_NOTIFICATION_EVENT:
                    $title =  __( 'SMS Notification', 'groundhogg' );
                    $view = sprintf( "view=type&type=%d", GROUNDHOGG_SMS_NOTIFICATION_EVENT );
                    break;

            }
        } else {
            if ($event->is_broadcast_event()) {
                $title =  __( 'Broadcast Email', 'groundhogg' );
                $view = sprintf( "view=type&type=%d", GROUNDHOGG_BROADCAST_EVENT );
            } else {
                $funnel = WPGH()->funnels->get( $event->funnel_id );
                $title = ( $funnel )? $funnel->title : sprintf( '(%s)', _x( 'funnel deleted', 'status', 'groundhogg' ) ) ;
                $view = sprintf( "view=funnel&funnel=%d", $event->funnel_id );
            }
        }

        return sprintf( "<a href='%s'>%s</a>",
            sprintf( admin_url( 'admin.php?page=gh_events&%s' ) , $view ),
            $title );    }

    /**
     * @param $event WPGH_Event
     * @return string
     */
    protected function column_step( $event )
    {
        if ($event->type) {
            switch ($event->type) {
                default:
                case GROUNDHOGG_FUNNEL_EVENT:
                    $step_title = $event->step->title;
                    break;
                case GROUNDHOGG_BROADCAST_EVENT:
                    $step_title = $event->step->get_title();
                    break;
                case GROUNDHOGG_EMAIL_NOTIFICATION_EVENT:
                    $step_title = $event->step->email->subject;
                    break;
                case GROUNDHOGG_SMS_NOTIFICATION_EVENT:
                    $step_title = $event->step->sms->title;
                    break;
            }
        } else {
            if ($event->is_broadcast_event()) {
                $step_title = $event->step->email->subject;
            } else {
                $step_title = $event->step->title;
            }
        }

        if ( ! $step_title )
            return sprintf( "<strong>(%s)</strong>", _x( 'step deleted', 'status', 'groundhogg' ) );

        return sprintf( "<a href='%s'>%s</a>",
            admin_url( 'admin.php?page=gh_events&view=step&step=' . $event->step->ID ),
            $step_title );

    }

    /**
     * @param $event WPGH_Event
     * @return string
     */
    protected function column_time( $event )
    {
        $p_time = intval( $event->time ) + ( wpgh_get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

        $cur_time = (int) current_time( 'timestamp' );

        $time_diff = $p_time - $cur_time;

        $status = $event->status;

        switch ( $status ){
            default:
            case 'waiting':
                $time_prefix = _x( 'Will run', 'status', 'groundhogg' );
                break;
            case 'cancelled':
                $time_prefix = _x( 'Cancelled', 'status','groundhogg' );
                break;
            case 'skipped':
                $time_prefix = _x( 'Skipped', 'status','groundhogg' );
                break;
            case 'complete':
                $time_prefix = _x( 'Processed', 'status','groundhogg' );
                break;
            case 'failed':
                $time_prefix = _x( 'Failed', 'status', 'groundhogg' );
                break;
        }

        if ( $time_diff < 0 && $status !== 'waiting' ){
            /* The event has passed */
            if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
                $time = date_i18n( 'Y-m-d \@ h:i A', intval( $p_time ) );
            } else {
                $time = sprintf( _x( "%s ago", 'status', 'groundhogg' ), human_time_diff( $p_time, $cur_time ) );
            }
        } else {
            /* the event is scheduled */
            if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
                $time = sprintf( _x( "on %s", 'status', 'groundhogg' ), date_i18n( 'Y-m-d \@ h:i A', intval( $p_time )  ) );
            } else {
                $time = sprintf( _x( "in %s", 'status', 'groundhogg' ), human_time_diff( $p_time, $cur_time ) );
            }
        }

        $html = $time_prefix . '&nbsp;<abbr title="' . date_i18n( DATE_ISO8601, intval( $p_time ) ) . '">' . $time . '</abbr>';
        $html .= sprintf( '<br><i>(%s %s)', date_i18n( 'h:i A', $event->contact->get_local_time( $event->time ) ), __( 'local time' ) ) . '</i>';

        return $html;
    }

    /**
     * @param $event WPGH_Event
     * @return string
     */
    protected function column_errors($event)
    {
        return $event->failure_reason ? $event->failure_reason : '&#x2014;' ;
    }

    protected function extra_tablenav($which)
    {
        $next_run_in = wp_next_scheduled( 'wpgh_process_queue' );
        $next_run_in = human_time_diff( time(), $next_run_in );

        ?>
        <div class="alignleft gh-actions">
            <a class="button action" href="<?php echo add_query_arg( 'process_queue', '1', $_SERVER[ 'REQUEST_URI' ] ); ?>"><?php printf( _x( 'Process Events (Auto Runs In %s)', 'action', 'groundhogg' ), $next_run_in ); ?></a>
        </div>
        <?php
    }

    /**
     * Get default column value.
     * @param WPGH_Event $event        A singular item (one full row's worth of data).
     * @param string $column_name The name/slug of the column to be processed.
     * @return string Text or HTML to be placed inside the column <td>.
     */
    protected function column_default( $event, $column_name ) {

        do_action( 'wpgh_events_custom_column', $event, $column_name );

        return '';

    }

    /**
     * Get value for checkbox column.
     *
     * @param object $event A singular item (one full row's worth of data).
     * @return string Text to be placed inside the column <td>.
     */
    protected function column_cb( $event ) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
            $event->ID           // The value of the checkbox should be the record's ID.
        );
    }

    /**
     * Get an associative array ( option_name => option_title ) with the list
     * of bulk steps available on this table.
     * @return array An associative array containing all the bulk steps.
     */
    protected function get_bulk_actions() {
        $actions = array(
	        'execute' => _x( 'Run', 'List table bulk action', 'wp-funnels'),
	        'cancel' => _x( 'Cancel', 'List table bulk action', 'wp-funnels' ),
        );

        return apply_filters( 'wpgh_event_bulk_actions', $actions );
    }

    protected function get_view()
    {
        return ( isset( $_GET['view'] ) )? $_GET['view'] : 'all';
    }

    protected function get_views()
    {
        $base_url = admin_url( 'admin.php?page=gh_events&view=status&status=' );

        $view = $this->get_view();

        $count = array(
            'waiting'   => WPGH()->events->count( array( 'status' => 'waiting' ) ),
            'skipped'   => WPGH()->events->count( array( 'status' => 'skipped' ) ),
            'cancelled' => WPGH()->events->count( array( 'status' => 'cancelled' ) ),
            'completed' => WPGH()->events->count( array( 'status' => 'complete' ) ),
            'failed' => WPGH()->events->count( array( 'status' => 'failed' ) )
        );

        return apply_filters( 'gh_event_views', array(
            'all'       => "<a class='" . ($view === 'all' ? 'current' : '') . "' href='" . admin_url( 'admin.php?page=gh_events' ) . "'>" . _x( 'All', 'view', 'groundhogg' ) . ' <span class="count">('. array_sum($count) . ')</span>' . "</a>",
            'waiting'   => "<a class='" . ($view === 'waiting' ? 'current' : '') . "' href='" . $base_url . "waiting" . "'>" . _x( 'Waiting', 'view', 'groundhogg' ) . ' <span class="count">('.$count['waiting'].')</span>' . "</a>",
            'skipped'   => "<a class='" . ($view === 'skipped' ? 'current' : '') . "' href='" . $base_url . "skipped" . "'>" . _x( 'Skipped','view','groundhogg') . ' <span class="count">('.$count['skipped'].')</span>' . "</a>",
            'cancelled' => "<a class='" . ($view === 'cancelled' ? 'current' : '') . "' href='" . $base_url . "cancelled" . "'>" . _x( 'Cancelled', 'view', 'groundhogg' ) .' <span class="count">('.$count['cancelled'].')</span>' . "</a>",
            'completed' => "<a class='" . ($view === 'completed' ? 'current' : '') . "' href='" . $base_url . "complete" . "'>" . _x( 'Completed', 'view', 'groundhogg' ). ' <span class="count">('.$count['completed'].')</span>' . "</a>",
            'failed' => "<a class='" . ($view === 'failed' ? 'current' : '') . "' href='" . $base_url . "failed" . "'>" . _x( 'Failed', 'view', 'groundhogg' ). ' <span class="count">('.$count['failed'].')</span>' . "</a>"
        ) );
    }

    /**
     * Prepares the list of items for displaying.
     * @global wpdb $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     */
    function prepare_items() {

        $per_page = 30;

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        switch ( $this->get_view() )
        {
            case 'status':
                if ( isset( $_REQUEST['status'] ) ){
                    $data = WPGH()->events->get_events( array(
                        'status' => $_REQUEST[ 'status' ]
                    ));
                }
                break;
            case 'contact':
	            if ( isset( $_REQUEST['contact'] ) ){
                    $data = WPGH()->events->get_events( array(
                        'contact_id' => $_REQUEST[ 'contact' ]
                    ));
	            }
	            break;
            case 'funnel':
	            if ( isset( $_REQUEST['funnel'] ) ){
                    $data = WPGH()->events->get_events( array(
                        'funnel_id' => $_REQUEST[ 'funnel' ]
                    ));
	            }
	            break;
	        case 'step':
		        if ( isset( $_REQUEST['step'] ) ){
                    $data = WPGH()->events->get_events( array(
                        'step_id' => $_REQUEST[ 'step' ]
                    ));
		        }
		        break;
            case 'type':
                if ( isset( $_REQUEST['type'] ) ){
                    $data = WPGH()->events->get_events( array(
                        'type' => $_REQUEST[ 'type' ]
                    ));
                }
                break;
            default:
                $data = WPGH()->events->get_events();
                break;
        }

        /*
         * Sort the data
         */
        usort( $data, array( $this, 'usort_reorder' ) );

        $current_page = $this->get_pagenum();

        $total_items = count( $data );

        $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

        $this->items = $data;

        $this->set_pagination_args( array(
            'total_items' => $total_items,                     // WE have to calculate the total number of items.
            'per_page'    => $per_page,                        // WE have to determine how many items to show on a page.
            'total_pages' => ceil( $total_items / $per_page ), // WE have to calculate the total number of pages.
        ) );
    }

    /**
     * Callback to allow sorting of example data.
     *
     * @param string $a First value.
     * @param string $b Second value.
     *
     * @return int
     */
    protected function usort_reorder( $a, $b ) {
        $a = (array) $a;
        $b = (array) $b;
        // If no sort, default to title.
        $orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'time'; // WPCS: Input var ok.
        // If no order, default to asc.
        $order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
        // Determine sort order.
        $result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
        return ( 'desc' === $order ) ? $result : - $result;
    }

    /**
     * Generates and displays row action superlinks.
     *
     * @param WPGH_Event $event        Event being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary     Primary column name.
     * @return string Row steps output for posts.
     */
    protected function handle_row_actions( $event, $column_name, $primary ) {

        $actions = [];

        if ( $primary === $column_name ) {

            $actions = array();

            switch ($event->status) {
                case 'waiting':
                    $actions['execute'] = sprintf(
                        '<a href="%s" class="edit" aria-label="%s">%s</a>',
                        /* translators: %s: title */
                        esc_url(wp_nonce_url(admin_url('admin.php?page=gh_events&event=' . $event->ID . '&action=execute'))),
                        esc_attr(_x('Execute', 'action', 'groundhogg')),
                        _x('Run Now', 'action', 'groundhogg')
                    );
                    $actions['delete'] = sprintf(
                        '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
                        esc_url(wp_nonce_url(admin_url('admin.php?page=gh_events&event=' . $event->ID . '&action=cancel'))),
                        /* translators: %s: title */
                        esc_attr(_x('Cancel', 'action', 'groundhogg')),
                        _x('Cancel', 'action', 'groundhogg')
                    );
                    break;
                default:
                    $actions['re_execute'] = sprintf(
                        '<a href="%s" class="edit" aria-label="%s">%s</a>',
                        /* translators: %s: title */
                        esc_url(wp_nonce_url(admin_url('admin.php?page=gh_events&event=' . $event->ID . '&action=execute'))),
                        esc_attr(_x('Run Again', 'action', 'groundhogg')),
                        _x('Run Again', 'action', 'groundhogg')
                    );
                    break;

            }

            if ($event->contact->exists()) {
                $actions['view'] = sprintf("<a class='edit' href='%s' aria-label='%s'>%s</a>",
                    admin_url('admin.php?page=gh_contacts&action=edit&contact=' . $event->contact->ID),
                    esc_attr(_x('View Contact', 'action', 'groundhogg')),
                    _x('View Contact', 'action', 'groundhogg')
                );
            }
        } else if ( $column_name === 'funnel' ){

            if ( $event->is_funnel_event() ){
                $actions['edit'] = sprintf("<a class='edit' href='%s' aria-label='%s'>%s</a>",
                    admin_url('admin.php?page=gh_funnels&action=edit&funnel=' . $event->funnel_id),
                    esc_attr(_x('Edit Funnel', 'action', 'groundhogg')),
                    _x('Edit Funnel', 'action', 'groundhogg')
                );

            }

        } else if ( $column_name === 'step' ){

            if ( $event->is_funnel_event() ){
                $actions['edit'] = sprintf("<a class='edit' href='%s' aria-label='%s'>%s</a>",
                    admin_url( sprintf( 'admin.php?page=gh_funnels&action=edit&funnel=%d#%d', $event->funnel_id, $event->step->ID ) ),
                    esc_attr(_x('Edit Step', 'action', 'groundhogg')),
                    _x('Edit Step', 'action', 'groundhogg')
                );
            }



        }


        return $this->row_actions( apply_filters( 'wpgh_event_row_actions', $actions, $event, $column_name ) );
    }
}