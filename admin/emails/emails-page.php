<?php
namespace Groundhogg\Admin\Emails;

use Groundhogg;
use Groundhogg\Admin\Admin_Page;
use Groundhogg\Plugin;
use Groundhogg\Email;
use function Groundhogg\wpgh_email_is_same_domain;
use function Groundhogg\wpgh_create_contact_from_user;
use Groundhogg\Admin\Emails\Blocks;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * View Emails
 *
 * Allow the user to view & edit the emails
 * Contains add, save, delete, etc for the admin functions...
 *
 * @package     Admin
 * @subpackage  Admin/Emails
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */


class Emails_Page extends Admin_Page
{

    protected function add_ajax_actions()
    {
        add_action( 'wp_ajax_gh_update_email', array( $this, 'update_email_ajax' ) );
        add_action( 'wp_ajax_get_my_emails_search_results', array( $this, 'get_my_emails_search_results' ) );
    }

    protected function add_additional_actions()
    {
        if ( $this->get_current_action() === 'edit' ){
            add_action( 'admin_init', [ $this, 'init_blocks' ] );
            add_action( 'in_admin_header' , array( $this, 'prevent_notices' )  );
        }
    }

    public function get_slug()
    {
        return 'gh_emails';
    }

    public function get_name()
    {
        return _x( 'Emails', 'page_title', 'groundhogg' );
    }

    public function get_cap()
    {
        return 'edit_emails';
    }

    public function get_item_type()
    {
        return 'email';
    }


    public function scripts()
    {
        if ( $this->get_current_action() === 'edit' ){

            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-ui-draggable' );

            // adding code mirror
            wp_enqueue_style( 'codemirror' );
            wp_enqueue_script( 'codemirror' );
            wp_enqueue_script( 'codemirror-mode-css' );
            wp_enqueue_script( 'codemirror-mode-xml' );
            wp_enqueue_script( 'codemirror-mode-js'  );
            wp_enqueue_script( 'codemirror-mode-html' );

            wp_enqueue_script( 'sticky-sidebar' );
            //            wp_enqueue_script( 'jquery-sticky-sidebar' );

            // adding beautify js
            wp_enqueue_script( 'beautify-js'  );
            wp_enqueue_script( 'beautify-css' );
            wp_enqueue_script( 'beautify-html' );

            wp_enqueue_style( 'groundhogg-admin-email-editor' );
            wp_enqueue_script( 'groundhogg-admin-email-editor' );

            wp_localize_script( 'groundhogg-admin-email-editor', 'EmailEditor', array(
                'id' => absint( Groundhogg\get_request_var( 'email' ) ),
            ) );

        }

        if ( $this->get_current_action() === 'add' || $this->get_current_action() === 'edit' ){
            wp_enqueue_script( 'groundhogg-admin-iframe' );
            wp_enqueue_style( 'groundhogg-admin-iframe' );
        }

        wp_enqueue_style( 'groundhogg-admin' );
    }


    public function help()
    {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id' => 'gh_overview',
                'title' => __('Overview'),
                'content' => '<p>' . __('Unlike most marketing automation platforms Groundhogg made the decision to <strong>store emails globally</strong>. That means that you can use the same email across different funnels without ever having to re-write it.
                From this screen you can <strong>View/Edit/Delete</strong> emails and see their respective open rates across different funnels.', 'groundhogg') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_add',
                'title' => __('Add New'),
                'content' => '<p>' . __('When you add a new email you can either select a pre-written email template created by our in house digital marketing specialists or you can select one of your own past written emails to copy.', 'groundhogg') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_edit',
                'title' => __('Editing'),
                'content' => '<p>' . __('When you are editing an email, you can drag in new blocks from the right hand side into your email content. You can edit the appearance of the email with the settings on the left hand side of the email content. When designing our
                email builder we made the decision to keep features sparse. Hence no columns and no frilly html stuff. Just the basics. The reason for this being 80% of email is read on mobile, so our email builder is optimized for better mobile consumption.', 'groundhogg') . '</p>' .
                    '<p>' . __('When you believe an email is ready for sending set the status to <strong>Ready</strong> and then you can use it in any broadcast or funnel. An email which is in draft mode will not be sent.') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_test',
                'title' => __('Testing'),
                'content' => '<p>' . __('To test an email simply check off the <strong>Send Test</strong> checkbox and select the user account you\'d like to send the test to. For best results, use a minimal number of images, make text easy to read and write good content. Tests will be sent regardless of the email\'s current status.', 'groundhogg') . '</p>'
            )
        );
    }


    /**
     * Get the title of the current page
     */
    function get_title()
    {
        switch ( $this->get_current_action() ){
            case 'add':
                return _x( 'Add Email' , 'page_title', 'groundhogg' );
                break;
            case 'edit':
                return _x( 'Edit Email' ,'page_title', 'groundhogg' );
                break;
            case 'view':
            default:
                return _x( 'Emails', 'page_title', 'groundhogg' );
                break;
        }
    }

    /**
     * @return array|array[]
     */
    protected function get_title_actions()
    {
        return [
            [
                'link' => $this->admin_url( [ 'action' => 'add' ] ),
                'action' => __( 'Add New', 'groundhogg' ),
                'target' => '_self',
            ],
            [
                'link' => Plugin::$instance->admin->get_page( 'broadcasts' )->admin_url( [ 'action' => 'add', 'type' => 'email' ] ),
                'action' => __( 'Broadcast', 'groundhogg' ),
                'target' => '_self',
            ]
        ];
    }

    public function process_restore()
    {
        if ( ! current_user_can( 'edit_emails' ) ){
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ) //todo
        {
            Plugin::$instance->dbs->get_db('emails')->update( $id , [ 'status' => 'draft' ] );
        }

        $this->add_notice(
            esc_attr( 'restored' ),
            sprintf( "%s %d %s",
                __( 'Restored' ),
                count( $this->get_items() ),
                __( 'Emails', 'groundhogg' ) ),
            'success'
        );

        return false;
    }

    public function process_empty_trash()
    {

        if ( ! current_user_can( 'delete_emails' ) ){
            $this->wp_die_no_access();
        }

        $emails = Plugin::$instance->dbs->get_db('emails')->query( [ 'status' => 'trash' ] );

        foreach ( $emails as $email ){
            Plugin::$instance->dbs->get_db('emails')->delete( $email->ID );
        }

        $this->add_notice(
            esc_attr( 'deleted' ),
            sprintf( "%s %d %s",
                __( 'Deleted' ),
                count( $emails ),
                __( 'Emails', 'groundhogg' ) ),
            'success'
        );

        return false;
    }

    public function process_delete()
    {
        if ( ! current_user_can( 'delete_emails' ) ){
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ){
            if ( ! Plugin::$instance->dbs->get_db( 'emails' )->delete( $id ) ){
                return new \WP_Error( 'unable_to_delete_email', "Something went wrong deleting the email." );
            }
        }

        $this->add_notice(
            esc_attr( 'deleted' ),
            sprintf( "%s %d %s",
                __( 'Deleted' ),
                count( $this->get_items() ),
                __( 'Emails', 'groundhogg' ) ),
            'success'
        );

        return false;
    }

    public function process_trash()
    {
        if ( ! current_user_can( 'edit_emails' ) ){
           $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ) {
            Plugin::$instance->dbs->get_db('emails')->update( $id , [ 'status' => 'trash' ] );
        }

        $this->add_notice(
            esc_attr( 'trashed' ),
            sprintf( "%s %d %s",
                __( 'Trashed' ),
                count( $this->get_items() ),
                __( 'Emails', 'groundhogg' ) ),
            'success'
        );

        return false;
    }

    public function update_email_ajax()
    {

        if ( ! wp_doing_ajax() ){
            return;
        }

        if ( ! $this->verify_action() ){
            wp_send_json_error();
        }

        $result = $this->process_edit();

        if ( is_wp_error( $result ) ){
            $this->add_notice( $result );
        }

        $this->send_ajax_response( $result );

    }

    public function process_edit()
    {
        if ( ! current_user_can( 'edit_emails' ) ){
            $this->wp_die_no_access();
        }

        $id = absint( Groundhogg\get_request_var( 'email' ) );
        $email = Plugin::$instance->utils->get_email( $id );

        $args = array();

        $status = sanitize_text_field( Groundhogg\get_request_var( 'email_status', 'draft' ) );

        if ( $status === 'draft' ) {
            $this->add_notice( 'email-in-draft-mode', __( 'Emails cannot be sent while in DRAFT mode.', 'groundhogg' ), 'warning' );
        }

        $from_user = absint( Groundhogg\get_request_var( 'from_user' ) );

        if ( $from_user > 0 ){
            $user = get_userdata( $from_user );
            if ( !  Groundhogg\email_is_same_domain( $user->user_email ) ){ //todo
                $this->add_notice( 'email-cross-domain-warning', sprintf( __( 'You are sending this email from an email address (%s) which does not belong to this server. This may cause deliverability issues and harm your sender reputation.', 'groundhogg' ), $user->user_email ), 'warning' );
            }
        }

        $subject = sanitize_text_field( Groundhogg\get_request_var( 'subject' ) );
        $pre_header = sanitize_text_field( Groundhogg\get_request_var( 'pre_header' ) );
        $content = apply_filters( 'groundhogg/admin/emails/sanitize_email_content', Groundhogg\get_request_var( 'content' ) );

        $args[ 'status' ] = $status;
        $args[ 'subject' ] = $subject;
        $args[ 'pre_header' ] = $pre_header;
        $args[ 'content' ] = $content;

        $args[ 'last_updated' ] = current_time( 'mysql' );
        $args[ 'is_template' ] = key_exists( 'save_as_template', $_POST ) ? 1 : 0;


        if ( $email->update( $args ) ){
            $this->add_notice( 'email-updated', __( 'Email Updated.', 'groundhogg' ), 'success' );
        } else {
            return new \WP_Error( 'unable_to_update_email', 'Unable to update email!' );
        }

        $email->update_meta( 'alignment', sanitize_text_field( Groundhogg\get_request_var( 'email_alignment' ) ) );
        $email->update_meta( 'browser_view', boolval( Groundhogg\get_request_var( 'browser_view' ) ) );

        if ( Groundhogg\get_request_var( 'update_and_test' ) ){

            if ( ! current_user_can( 'send_emails' ) ){
                $this->wp_die_no_access();
            }

            $contact = new Groundhogg\Contact( [ 'user_id' => get_current_user_id() ] );

            if ( $contact->exists() ){

                $email->enable_test_mode();

                $sent = $email->send( $contact );

                if ( ! $sent || is_wp_error( $sent ) ){
                    return is_wp_error( $sent ) ? $sent : new \WP_Error( 'oops', "Failed to send test." );
                } else {
                    $this->add_notice(
                        esc_attr( 'sent-test' ),
                        sprintf( "%s %s",
                            __( 'Sent test email to', 'groundhogg' ),
                            $contact->get_email() ),
                        'success'
                    );
                }

            } else {
                return new \WP_Error( 'oops', __( 'Failed to send test: No user selected. Please select a user to send the test to.', 'groundhogg' ) );
            }
        }

        return $email->get_as_array();
    }

    /**
     * Create an email and then redirect to the edit page
     */
    public function process_add()
    {
        if ( ! current_user_can( 'add_emails' ) ){
            $this->wp_die_no_access();
        }

        $args = [];

        if ( isset( $_POST[ 'email_template' ] ) ){

            include_once GROUNDHOGG_PATH . '/templates/assets/email-templates.php';
            /**
             * @var $email_templates array
             * @see /templates/email-templates.php
             */
            $args[ 'content' ] = $email_templates[ $_POST[ 'email_template' ] ][ 'content' ];
            $args[ 'subject' ] = $email_templates[ $_POST[ 'email_template' ] ][ 'title' ];

        } else if ( isset( $_POST[ 'email_id' ] ) ) {


            $email = Plugin::$instance->dbs->get_db('emails' )->get( intval( $_POST['email_id'] ) );
            $args[ 'content' ] = $email->content;
            $args[ 'subject' ] = sprintf( "%s - (copy)", $email->subject );
            $args[ 'pre_header' ] = $email->pre_header;

        } else {

            return new \WP_Error( 'ooops',  __( 'Could not create email.', 'groundhogg' ) );
        }

        $args[ 'author' ] = get_current_user_id();
        $args[ 'from_user' ] = get_current_user_id();


        $email_id = Plugin::$instance->dbs->get_db('emails')->add($args);

        if ( ! $email_id ){

            return new \WP_Error( 'ooops',  __( 'Could not create email.', 'groundhogg' ) );
        }

        $return_path = admin_url( 'admin.php?page=gh_emails&action=edit&email=' .  $email_id );

        if ( isset( $_GET['return_step'] ) ){

            /* Make it easy to return back to the funnel editing screen */
            $step_id = intval( $_GET['return_step'] );
            $funnel_id = intval( $_GET['return_funnel'] );
            $return_path .= sprintf( "&return_funnel=%s&return_step=%s", $funnel_id, $step_id );
            Groundhogg\get_db('stepmeta' )->update_meta( $step_id, 'email_id', $email_id );
        }

        return $return_path ;

    }

    public function view()
    {
        if ( ! class_exists( 'Emails_Table' ) ){
            include dirname(__FILE__) . '/emails-table.php';
        }

        $emails_table = new Emails_Table();

        $emails_table->views();

        $this->search_form( __( 'Search Emails', 'groundhogg' ) );

        ?>
        <form method="post">
            <?php $emails_table->prepare_items(); ?>
            <?php $emails_table->display(); ?>
        </form>
        <?php
    }

    /**
     * init the blocks
     *
     * @return array
     */
    public function init_blocks()
    {

        $blocks = array();

        $blocks[] = new Blocks\Text();
        $blocks[] = new Blocks\Image();
        $blocks[] = new Blocks\Button();
        $blocks[] = new Blocks\HTML();
        $blocks[] = new Blocks\Divider();
        $blocks[] = new Blocks\Spacer();
//        $blocks[] = new Blocks\Column();

        return apply_filters( 'groundhogg/admin/emails/blocks/init', $blocks );

    }

    public function edit()
    {
        if ( ! current_user_can( 'edit_emails' ) ){
            $this->wp_die_no_access();
        }

        include dirname(__FILE__) . '/email-editor.php';
    }

    public function page()
    {
        if ( $this->get_current_action() === 'edit' ){
            $this->edit();
            return;
        }

        parent::page(); // TODO: Change the autogenerated stub
    }

    public function add()
    {
        if ( ! current_user_can( 'add_emails' ) ){
            $this->wp_die_no_access();
        }

        include dirname(__FILE__) . '/add-email.php';
    }

    /**
     * Prevent notices from other plugins appearing on the edit funnel screen as the break the format.
     */
    public function prevent_notices()
    {
        remove_all_actions( 'network_admin_notices' );
        remove_all_actions( 'user_admin_notices' );
        remove_all_actions( 'admin_notices' );
    }

    /**
     * Get search results
     */
    public function get_my_emails_search_results()
    {
        ob_start();

        $emails = array_slice( Plugin::$instance->dbs->get_db('emails')->query( [ 'search' => sanitize_text_field( wp_unslash( $_POST[ 's' ] ) ) ] ), 0, 20 );

        if ( empty( $emails ) ):
            ?> <p style="text-align: center;font-size: 24px;"><?php _ex( 'Sorry, no emails were found.', 'notice', 'groundhogg' ); ?></p> <?php
        else:
        ?>
        <?php foreach ( $emails as $email ): ?>
            <div class="postbox" style="margin-right:20px;width: calc( 95% / 2 );max-width: 550px;display: inline-block;">
                <h2 class="hndle"><?php echo $email->subject; ?></h2>
                <div class="inside">
                    <p><?php echo empty( $email->pre_header )? __( 'Custom Email', 'groundhogg' ) :  $email->pre_header; ?></p>
                    <div style="zoom: 85%;height: 500px;overflow: auto;padding: 10px;" id="<?php echo $email->ID; ?> " class="email-container postbox">
                        <?php echo $email->content; ?>
                    </div>
                    <button class="choose-template button-primary" name="email_id" value="<?php echo $email->ID; ?>"><?php _e( 'Start Writing', 'groundhogg' ); ?></button>
                </div>
            </div>
        <?php endforeach;

        endif;

        $response = [ 'html' => ob_get_clean() ];
        wp_send_json( $response );
    }
}