<?php
namespace Groundhogg\Admin\Contacts;
use Groundhogg\Admin\Admin_Page;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Page gh_contacts
 *
 * This class registers the page with the admin menu, contains the private scripts to add contacts,
 * delete contacts, and manage contacts in the admin area
 *
 * There are several hooks you can use to add your own functionality to manage a contact in the default admin view.
 * The most relevant will likely be the following...
 *
 * add_action( 'wpgh_admin_update_contact_after', 'my_save_function' ); ($id)
 *
 * When saving custom information or doing something else. Runs after the admin saves a contact via the admin screen.
 *
 * @package     Admin
 * @subpackage  Admin/Contacts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Contacts_Page extends Admin_Page
{

    /**
     * @var WPGH_Notices
     */
    public $notices;

    /**
     * @var WPGH_Bulk_Contact_Manager
     */
    private $exporter;

    public $order = 5;

    public function __construct()
    {

        add_action('admin_menu', array($this, 'register'), $this->order);
        add_action('wp_ajax_wpgh_inline_save_contacts', array($this, 'save_inline'));

        if (isset($_GET['page']) && $_GET['page'] === 'gh_contacts') {
            add_action('admin_enqueue_scripts', array($this, 'scripts'));
            add_action('init', array($this, 'process_action'));
            $this->notices = WPGH()->notices;
        }

        if ((isset($_GET['page']) && $_GET['page'] === 'gh_contacts') || wp_doing_ajax()) {
            $this->exporter = new WPGH_Bulk_Contact_Manager();
        }
    }

    public function register()
    {
        $page = add_submenu_page(
            'groundhogg',
            _x('Contacts', 'page_title', 'groundhogg'),
            _x('Contacts', 'page_title', 'groundhogg'),
            'view_contacts',
            'gh_contacts',
            array($this, 'page')
        );

        add_action("load-" . $page, array($this, 'help'));
    }

    /**
     * Get the scripts in there
     */
    public function scripts()
    {
        if ($this->get_action() === 'edit' || $this->get_action() === 'add' || $this->get_action() === 'form' ) {
            wp_enqueue_style('groundhogg-admin-contact-editor' );
            wp_enqueue_script('groundhogg-admin-contact-editor' );
        } else {
            wp_enqueue_style('select2' );
            wp_enqueue_script('select2' );
            wp_enqueue_style('groundhogg-admin-contact-inline' );
            wp_enqueue_script('groundhogg-admin-contact-inline' );
        }
    }
    /* Register the page */

    /* help bar */
    public function help()
    {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id' => 'gh_overview',
                'title' => __('Overview'),
                'content' => '<p>' . __("This is where you can manage and view your contacts. Click the quick edit to quickly change contact details.", 'groundhogg') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_edit',
                'title' => __('Editing'),
                'content' => '<p>' . __("While editing a contact you can modify any of their personal information. There are several points of interest...", 'groundhogg') . '</p>'
                    . '<ul> '
                    . '<li>' . __('Manually unsubscribe a contact by checking the "mark as unsubscribed" button.', 'groundhogg') . '</li>'
                    . '<li>' . __('Make sure your in compliance by ensuring the terms of agreement and GDPR consent are both checked under the compliance section.', 'groundhogg') . '</li>'
                    . '<li>' . __('View the origin of the contact by looking at the lead source field.', 'groundhogg') . '</li>'
                    . '<li>' . __('Add or remove custom information about the contact by enabling the "Edit Meta" section. Each meta also includes a replacement code to include it in an email.', 'groundhogg') . '</li>'
                    . '<li>' . __('Re-run or cancel events for this contact by viewing the "Upcoming Events" or "Recent History" Section', 'groundhogg') . '</li>'
                    . '<li>' . __('Monitor their engagement by looking in the "Recent Email History" section.', 'groundhogg') . '</li>'
                    . '</ul>'
            )
        );
    }


    /**
     * Get the affected contacts
     *
     * @return array|bool
     */
    private function get_contacts()
    {
        $contacts = isset($_REQUEST['contact']) ? $_REQUEST['contact'] : null;

        if (!$contacts)
            return false;

        return is_array($contacts) ? array_map('intval', $contacts) : array(intval($contacts));
    }

    /**
     * Get the current action
     *
     * @return bool
     */
    private function get_action()
    {
        if (isset($_REQUEST['filter_action']) && !empty($_REQUEST['filter_action']))
            return false;

        if (isset($_REQUEST['action']) && -1 != $_REQUEST['action'])
            return $_REQUEST['action'];

        if (isset($_REQUEST['action2']) && -1 != $_REQUEST['action2'])
            return $_REQUEST['action2'];

        return false;
    }

    /**
     * Get the previous action
     *
     * @return mixed
     */
    private function get_previous_action()
    {
        $action = get_transient('gh_last_action');

        delete_transient('gh_last_action');

        return $action;
    }

    /**
     * Get the screen title
     */
    private function get_title()
    {
        switch ($this->get_action()) {
            case 'add':
                _ex('Add Contact', 'page_title', 'groundhogg');
                break;
            case 'edit':
                $contacts = $this->get_contacts();
                $contact = wpgh_get_contact(array_shift($contacts));
                if ($contact) {
                    printf(_x('Edit Contact: %s', 'page_title', 'groundhogg'), $contact->full_name);
                } else {
                    _ex('Oops!', 'page_title', 'groundhogg');
                }

                break;
            case 'form':

                if ( key_exists( 'contact', $_GET ) ){
                    $contacts = $this->get_contacts();
                    $contact = wpgh_get_contact(array_shift($contacts));
                    printf( _x('Submit Form For %s', 'page_title', 'groundhogg'), $contact->full_name );
                } else{
                    _ex( 'Submit Form', 'page_title', 'groundhogg' );
                }

                break;
            case 'search':
                _ex('Search Contacts', 'page_title', 'groundhogg');
                break;
            default:
                _ex('Contacts', 'page_title', 'groundhogg');
        }
    }

    /**
     * Process the given action
     */
    public function process_action()
    {

        if (!$this->get_action() || !$this->verify_action())
            return;

        $base_url = remove_query_arg(array('_wpnonce', 'action'), wp_get_referer());

        switch ($this->get_action()) {
            case 'add':

                if (!current_user_can('add_contacts')) {
                    wp_die(WPGH()->roles->error('add_contacts'));
                }

                if (!empty($_POST)) {
                    $this->add_contact();
                }

                break;

            case 'edit':

                if (!current_user_can('edit_contacts')) {
                    wp_die(WPGH()->roles->error('edit_contacts'));
                }

                if (!empty($_POST)) {

                    $this->update_contact();

                }

                break;

            case 'spam':

                if (!current_user_can('edit_contacts')) {
                    wp_die(WPGH()->roles->error('edit_contacts'));
                }

                foreach ($this->get_contacts() as $id) {

                    $contact = wpgh_get_contact($id);
                    $contact->change_marketing_preference( WPGH_SPAM );

                    $ip_address = $contact->get_meta('ip_address');

                    if ($ip_address) {
                        $blacklist = wpgh_get_option('blacklist_keys');
                        $blacklist .= "\n" . $ip_address;
                        $blacklist = sanitize_textarea_field($blacklist);
                        update_option('blacklist_keys', $blacklist);
                    }

                    do_action('wpgh_contact_marked_as_spam', $id);
                }

                $this->notices->add(
                    esc_attr('spammed'),
                    sprintf(_nx('Marked %d contact as spam.', 'Marked %d contact as spam.', count($this->get_contacts()), 'notice', 'groundhogg'), count($this->get_contacts())),
                    'success'
                );

                do_action('wpgh_spam_contacts');

                break;

            case 'delete':

                if (!current_user_can('delete_contacts')) {
                    wp_die(WPGH()->roles->error('delete_contacts'));
                }

                foreach ($this->get_contacts() as $id) {

                    do_action('wpgh_pre_admin_delete_contact', $id);

                    $result = WPGH()->contacts->delete($id);

                    if ($result) {
                        do_action('wpgh_post_admin_delete_contact', $id);
                    }

                }

                $this->notices->add(
                    esc_attr('deleted'),
                    sprintf(_nx('Deleted %d contact', 'Deleted %d contacts', count($this->get_contacts()), 'notice', 'groundhogg'), count($this->get_contacts())),
                    'success'
                );

                do_action('wpgh_delete_contacts');

                break;

            case 'unspam':

                if (!current_user_can('edit_contacts')) {
                    wp_die(WPGH()->roles->error('edit_contacts'));
                }

                foreach ($this->get_contacts() as $id) {
                    $contact = wpgh_get_contact($id);
                    $contact->change_marketing_preference( WPGH_UNCONFIRMED );
                }

                $this->notices->add(
                    esc_attr('unspam'),
                    sprintf(_nx('Approved %d contact', 'Approved %d contacts', count($this->get_contacts()), 'notice', 'groundhogg'), count($this->get_contacts())),
                    'success'
                );

                do_action('wpgh_unspam_contacts');

                break;

            case 'unbounce':

                if (!current_user_can('edit_contacts')) {
                    wp_die(WPGH()->roles->error('edit_contacts'));
                }

                foreach ($this->get_contacts() as $id) {
                    $contact = wpgh_get_contact($id);
                    $contact->change_marketing_preference( WPGH_UNCONFIRMED );
                }

                $this->notices->add(
                    esc_attr('unbounce'),
                    sprintf(_nx('Approved %d contact', 'Approved %d contacts', count($this->get_contacts()), 'notice', 'groundhogg'), count($this->get_contacts())),
                    'success'
                );

                do_action('wpgh_unbounce_contacts');

                break;

            case 'search':

                if (!current_user_can('edit_contacts')) {
                    wp_die(WPGH()->roles->error('edit_contacts'));
                }

                if (!empty($_POST)) {
                    $search = $this->do_search();
                }

                break;

            case 'apply_tag':
                if (!current_user_can('edit_contacts')) {
                    wp_die(WPGH()->roles->error('edit_contacts'));
                }

                if ( ! empty( $_POST[ 'bulk_tags' ] ) ){

                    $tags = $_POST[ 'bulk_tags' ];

                    foreach ($this->get_contacts() as $id) {
                        $contact = wpgh_get_contact( $id );
                        $contact->apply_tag( $tags );
                    }

                    $this->notices->add(
                        esc_attr('applied_tags'),
                        sprintf(_nx('Applied %d tags to %d contact', 'Applied %d tags to %d contacts', count($this->get_contacts()), 'notice', 'groundhogg'), count( $tags ), count($this->get_contacts())),
                        'success'
                    );

                }

                break;

            case 'remove_tag':
                if (!current_user_can('edit_contacts')) {
                    wp_die(WPGH()->roles->error('edit_contacts'));
                }

                if ( ! empty( $_POST[ 'bulk_tags' ] ) ){

                    $tags = $_POST[ 'bulk_tags' ];

                    foreach ($this->get_contacts() as $id) {
                        $contact = wpgh_get_contact( $id );
                        $contact->remove_tag( $tags );
                    }

                    $this->notices->add(
                        esc_attr('removed_tags'),
                        sprintf(_nx('Removed %d tags from %d contact', 'Removed %d tags from %d contacts', count($this->get_contacts()), 'notice', 'groundhogg'), count( $tags ), count($this->get_contacts())),
                        'success'
                    );

                }

                break;

        }

        set_transient('gh_last_action', $this->get_action(), 30);

        if ($this->get_action() === 'edit' || $this->get_action() === 'add') {
            return true;
        }

        $base_url = add_query_arg('ids', urlencode(implode(',', $this->get_contacts())), $base_url);

        wp_redirect($base_url);
        die();
    }

    /**
     * Create a contact via the admin area
     */
    private function add_contact()
    {
        if (!current_user_can('add_contacts')) {
            wp_die(WPGH()->roles->error('add_contacts'));
        }

        do_action('wpgh_admin_add_contact_before');

        if (!isset($_POST['email'])) {
            $this->notices->add('NO_EMAIL', _x("Please enter a valid email address.", 'notice', 'groundhogg'), 'error');
            return;
        }

        if (isset($_POST['first_name']))
            $args['first_name'] = sanitize_text_field($_POST['first_name']);

        if (isset($_POST['last_name']))
            $args['last_name'] = sanitize_text_field($_POST['last_name']);

        if (isset($_POST['email'])) {

            $email = sanitize_email($_POST['email']);

            if (!WPGH()->contacts->exists($email)) {
                $args['email'] = $email;
            } else {
                $this->notices->add('email_exists', sprintf(_x('Sorry, the email %s already belongs to another contact.', 'notice', 'groundhogg'), $email), 'error');
                return;
            }

        }

        if (!is_email($args['email'])) {
            $this->notices->add('NO_EMAIL', _x("Please enter a valid email address.", 'notice', 'groundhogg'), 'error');
            return;
        }

        if (isset($_POST['owner_id'])) {
            $args['owner_id'] = intval($_POST['owner_id']);
        }

        $id = WPGH()->contacts->add($args);

        $contact = wpgh_get_contact($id);

        if (isset($_POST['primary_phone'])) {
            $contact->update_meta('primary_phone', sanitize_text_field($_POST['primary_phone']));
        }

        if (isset($_POST['primary_phone_extension'])) {
            $contact->update_meta('primary_phone_extension', sanitize_text_field($_POST['primary_phone_extension']));
        }

        if (isset($_POST['notes'])) {
            $contact->add_note($_POST['notes']);
        }

        if (isset($_POST['tags'])) {
            $contact->add_tag($_POST['tags']);
        }

        $this->notices->add('created', _x("Contact created!", 'notice', 'groundhogg'), 'success');

        do_action('wpgh_admin_add_contact_after', $id);

        wp_redirect(admin_url('admin.php?page=gh_contacts&action=edit&contact=' . $id));
        die();
    }

    /**
     * Update the contact via the admin screen
     */
    private function update_contact()
    {

        if (!current_user_can('edit_contacts')) {
            wp_die(WPGH()->roles->error('edit_contacts'));
        }

        $id = intval($_GET['contact']);

        if (!$id) {
            return;
        }

        $contact = wpgh_get_contact($id);

        do_action('wpgh_admin_update_contact_before', $id);

        //todo security check

        /* Save the meta first... as actual fields might overwrite it later... */
        $cur_meta = WPGH()->contact_meta->get_meta($id);

        $exclude_meta_list = array(
            'files',
            'notes'
        );

        if (isset($_POST['meta'])) {
            $posted_meta = $_POST['meta'];
            foreach ($cur_meta as $key => $value) {
                if (isset($posted_meta[$key])) {
                    $contact->update_meta($key, sanitize_text_field($posted_meta[$key]));
                } else {
                    if (!in_array($key, $exclude_meta_list)) {
                        $contact->delete_meta($key);
                    }
                }
            }
        }

        /* add new meta */
        if (isset($_POST['newmetakey']) && isset($_POST['newmetavalue'])) {

            $new_meta_keys = $_POST['newmetakey'];
            $new_meta_vals = $_POST['newmetavalue'];

            foreach ($new_meta_keys as $i => $new_meta_key) {
                if (strpos($new_meta_vals[$i], PHP_EOL) !== false) {
                    $contact->update_meta(sanitize_key($new_meta_key), sanitize_textarea_field(stripslashes($new_meta_vals[$i])));
                } else {
                    $contact->update_meta(sanitize_key($new_meta_key), sanitize_text_field(stripslashes($new_meta_vals[$i])));
                }
            }

        }

        $args = array();

        if (isset($_POST['email'])) {

            $email = sanitize_email($_POST['email']);

            //check if it's the current email address.
            if ($contact->email !== $email) {
                //check if another email address like it exists...
                if (!WPGH()->contacts->exists($email)) {
                    $args['email'] = $email;
                    //update new optin status to unconfirmed
                    $contact->change_marketing_preference( WPGH_UNCONFIRMED );
                    $this->notices->add('optin_status_updated', sprintf(_x('The email address of this contact has been changed to %s. Their optin status has been changed to [unconfirmed] to reflect the change as well.', 'notice', 'groundhogg'), $email), 'error');
                } else {
                    $this->notices->add('email_exists', sprintf(_x('Sorry, the email %s already belongs to another contact.', 'notice', 'groundhogg'), $email), 'error');
                }
            }
        }

        if (isset($_POST['first_name'])) {
            $args['first_name'] = sanitize_text_field($_POST['first_name']);
        }

        if (isset($_POST['last_name'])) {
            $args['last_name'] = sanitize_text_field($_POST['last_name']);
        }

        if (isset($_POST['owner_id'])) {
            $args['owner_id'] = intval($_POST['owner_id']);
        }

        if (isset($_POST['user'])) {
            $args['user_id'] = intval($_POST['user']);
        }

        if ( isset( $_POST[ 'unlink_user' ]) ){
            $args['user_id'] = null;
        }

        $args = array_map('stripslashes', $args);
        $contact->update($args);

        $basic_text_fields = [
           'primary_phone',
           'primary_phone_extension',
           'company_name',
           'job_title',
           'company_address',
           'street_address_1',
           'street_address_2',
           'city',
           'postal_zip',
           'region',
           'country',
           'lead_source',
           'source_page',
           'ip_address',
           'time_zone',
        ];

        $basic_text_fields = apply_filters( 'groundhogg/contact/update/basic_fields', $basic_text_fields, $contact );

        foreach ( $basic_text_fields as $field ){
            if (isset($_POST[$field]) ) {
                $contact->update_meta($field, sanitize_text_field(stripslashes($_POST[$field])));
            }
        }

        if ( isset( $_POST[ 'extrapolate_location' ] ) ){
            if ( $contact->extrapolate_location() ){
                $this->notices->add('location_updated', _x( 'Location updated.', 'notice', 'groundhogg' ), 'info');
            }
        }

        if (isset($_POST['tags'])) {

            $tags = WPGH()->tags->validate($_POST['tags']);

            $cur_tags = $contact->tags;
            $new_tags = $tags;

            $delete_tags = array_diff($cur_tags, $new_tags);
            if (!empty($delete_tags)) {
                $contact->remove_tag($delete_tags);
            }

            $add_tags = array_diff($new_tags, $cur_tags);
            if (!empty($add_tags)) {

//                print_r( $add_tags );

                $result = $contact->add_tag($add_tags);

                if (!$result) {
                    $this->notices->add('bad-tag', _x('Hmm, looks like we could not add the new tags...', 'notice', 'groundhogg'));
                }
            }
        } else {
            $contact->remove_tag($contact->tags);
        }

        /* Update Main Contact Information */

        //Do after tags get updated for compatibility with new optin status change.

        if (isset($_POST['unsubscribe'])) {

            $contact->unsubscribe();

            $this->notices->add(
                esc_attr('unsubscribed'),
                _x('This contact will no longer receive marketing.', 'notice', 'groundhogg'),
                'info'
            );
        }

        if ( isset( $_POST['manual_confirm'] ) ) {
            if ( isset( $_POST[ 'confirmation_reason' ] ) && ! empty( $_POST[ 'confirmation_reason' ] ) ){
                $contact->change_marketing_preference( WPGH_CONFIRMED );
                $contact->update_meta( 'manual_confirmation_reason', sanitize_textarea_field( stripslashes( $_POST[ 'confirmation_reason' ] ) ) );
                $this->notices->add(
                    esc_attr('confirmed'),
                    _x('This contact\'s email address has been confirmed.', 'notice', 'groundhogg'),
                    'info'
                );
            } else {
                $this->notices->add(
                    esc_attr('manual_confirmation_error'),
                    _x('A reason is required to change the email confirmation status.', 'notice', 'groundhogg'),
                    'error'
                );
            }
        }

        if ( isset( $_POST[ 'add_new_note' ] ) ){
            $contact->add_note( $_POST[ 'add_note' ] );
        }

        if (isset($_POST['send_email']) && isset($_POST['email_id']) && current_user_can('send_emails')) {

            $mail_id = intval( $_POST['email_id'] );

            if( wpgh_send_email_notification( $mail_id, $contact->ID ) ){
                $this->notices->add( 'email_queued', _x( 'The email has been added to the queue and will send shortly.', 'notice', 'groundhogg' ) );
            }
        }

        /* USE the same email priviledges */
        if (isset($_POST['send_sms']) && isset($_POST['sms_id']) && current_user_can('send_emails')) {

            $sms_id = intval( $_POST['sms_id'] );

            if( wpgh_send_sms_notification( $sms_id, $contact->ID ) ){
                $this->notices->add( 'sms_queued', _x( 'The sms has been added to the queue and will send shortly.', 'notice', 'groundhogg' ) );
            }
        }

        if (isset($_POST['start_funnel']) && isset($_POST['add_contacts_to_funnel_step_picker']) && current_user_can('edit_contacts')) {

            $step = wpgh_get_funnel_step(intval($_POST['add_contacts_to_funnel_step_picker']));
            if ($step->enqueue($contact)) {
                $this->notices->add('started', _x("Contact added to funnel.", 'notice', 'groundhogg'), 'info');
            }
        }

        $this->notices->add('update', _x("Contact updated!", 'notice', 'groundhogg'), 'success');

        if (!empty($_FILES['files']['tmp_name'][0])) {
            $this->upload_files();
        }

        do_action('wpgh_admin_update_contact_after', $id);
    }

    /**
     * Upload files to a contact if uploaded from the admin page
     */
    private function upload_files()
    {
        $id = intval($_GET['contact']);
        $contact = wpgh_get_contact($id);

        if (!isset($_FILES['files']['tmp_name'][0]) || empty($_FILES['files']['tmp_name'][0])) {
            return false;
        }

        $files = $_FILES['files'];

        $num_files = count($files['name']);

        $upload_overrides = array('test_form' => false);

        for ($i = 0; $i < $num_files; $i++) {

            $ifile = array(
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            );

            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . '/wp-admin/includes/file.php');
            }

            WPGH()->submission->contact = $contact;
            WPGH()->submission->set_upload_dirs();

            add_filter('upload_dir', array(WPGH()->submission, 'files_upload_dir'));
            $mfile = wp_handle_upload($ifile, $upload_overrides);
            remove_filter('upload_dir', array(WPGH()->submission, 'files_upload_dir'));

            if (isset($mfile['error'])) {
                if (empty($mfile['error'])) {
                    $mfile['error'] = __('Could not upload file.', 'notice', 'groundhogg');
                }
                $this->notices->add('BAD_UPLOAD', $mfile['error'], 'error');
            } else {
                $files = $contact->get_meta('files');
                if (!$files) {
                    $files = array();
                }
                $j = count($files) + 1;
                $mfile['key'] = $j;
                $mfile = array_map('wp_normalize_path', $mfile);
                $files[$j] = $mfile;
                $contact->update_meta('files', $files);
                /* Compat for local host WP filesystems */
            }

        }

//        wp_die();

        return true;
    }

    /**
     * Save the contact during inline edit
     */
    public function save_inline()
    {

        if (!wp_doing_ajax()) {
            return;
        }

        if (!current_user_can('edit_contacts')) {
            wp_die(WPGH()->roles->error('edit_contacts'));
        }

        $id = (int)$_POST['ID'];

        $contact = wpgh_get_contact($id);

        do_action('wpgh_inline_update_contact_before', $id);

        $email = sanitize_email($_POST['email']);

        $args['first_name'] = sanitize_text_field($_POST['first_name']);
        $args['last_name'] = sanitize_text_field($_POST['last_name']);
        $args['owner_id'] = intval($_POST['owner']);

        $err = array();

        if (!$email) {
            $err[] = _x('Email cannot be blank.', 'notice', 'groundhogg');
        } else if (!is_email($email)) {
            $err[] = _x('Invalid email address.', 'notice', 'groundhogg');
        }

        //check if it's the current email address.
        if ($contact->email !== $email) {

            //check if another email address like it exists...
            if (!WPGH()->contacts->exists($email)) {
                $args['email'] = $email;

                //update new optin status to unconfirmed
                $contact->change_marketing_preference( WPGH_UNCONFIRMED );
                $err[] = sprintf(_x('The email address of this contact has been changed to %s. Their optin status has been changed to [unconfirmed] to reflect the change as well.', 'notice', 'groundhogg'), $email );

            } else {

                $err[] = sprintf(_x('Sorry, the email %s already belongs to another contact.', 'notice', 'groundhogg'), $email);

            }

        }

        if (!$args['first_name']) {
            $err[] = _x('First name cannot be blank.', 'notice', 'groundhogg');
        }

        if ($err) {
            echo implode(', ', $err);
            exit;
        }

        $args = array_map('stripslashes', $args);

        $contact->update($args);

        $tags = WPGH()->tags->validate($_POST['tags']);

        $cur_tags = $contact->tags;
        $new_tags = $tags;

        $delete_tags = array_diff($cur_tags, $new_tags);
        if (!empty($delete_tags)) {
            $contact->remove_tag($delete_tags);
        }

        $add_tags = array_diff($new_tags, $cur_tags);
        if (!empty($add_tags)) {
            $contact->add_tag($add_tags);

        }

        do_action('wpgh_inline_update_contact_after', $id);

        if (!class_exists('WPGH_Contacts_Table')) {
            include_once 'class-wpgh-contacts-table.php';
        }

        $contactTable = new WPGH_Contacts_Table;
        $contactTable->single_row(WPGH()->contacts->get($id));

        wp_die();
    }

    /**
     * Verify that the current user can perform the action
     *
     * @return bool
     */
    function verify_action()
    {
        if (!isset($_REQUEST['_wpnonce']) && !isset($_REQUEST['_edit_contact_nonce']))
            return false;

        return wp_verify_nonce($_REQUEST['_wpnonce']) || wp_verify_nonce($_REQUEST['_wpnonce'], $this->get_action()) || wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-contacts') || wp_verify_nonce($_REQUEST['_edit_contact_nonce'], $this->get_action());
    }

    /**
     * Display the contact table
     */
    function table()
    {

        if (!current_user_can('view_contacts')) {
            wp_die(WPGH()->roles->error('view_contacts'));
        }

        if (!class_exists('WPGH_Contacts_Table')) {
            include dirname(__FILE__) . '/class-wpgh-contacts-table.php';
        }

        $contacts_table = new WPGH_Contacts_Table();

        $contacts_table->views(); ?>
        <form method="post" class="search-form wp-clearfix">
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e('Search Contacts', 'groundhogg'); ?>
                    :</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button"
                       value="<?php _e('Search Contacts', 'groundhogg'); ?>">
            </p>
            <?php $contacts_table->prepare_items(); ?>
            <?php $contacts_table->display(); ?>
            <?php
            if ($contacts_table->has_items())
                $contacts_table->inline_edit();
            ?>
        </form>

        <?php
    }

    /**
     * Display the edit screen
     */
    function edit()
    {

        if (!current_user_can('view_contacts')) {
            wp_die(WPGH()->roles->error('view_contacts'));
        }

        include dirname(__FILE__) . '/contact-editor.php';

    }

    /**
     * Display the add screen
     */
    function add()
    {
        if (!current_user_can('add_contacts')) {
            wp_die(WPGH()->roles->error('add_contacts'));
        }

        include dirname(__FILE__) . '/add-contact.php';
    }

    function search()
    {
        if (!current_user_can('view_contacts')) {
            wp_die(WPGH()->roles->error('view_contacts'));
        }

        include dirname(__FILE__) . '/search.php';
    }

    public function form()
    {
        if (!current_user_can('edit_contacts')) {
            wp_die(WPGH()->roles->error('edit_contacts'));
        }

        include dirname(__FILE__) . '/form-admin-submit.php';
    }

    /**
     * @param $key
     * @param $comp
     * @param $value
     *
     * @return string
     */
    private function generate_comparison_statement( $key, $comp, $value )
    {
        global $wpdb;

        if ( is_array( $value ) ){
            $value = sprintf( '(%s)', implode( ',', $value ) );
        } else if ( is_numeric( $value ) ){
            $value = intval( $value );
        }

        $insert = is_int( $value ) ? '%d' : '%s';

        switch ( $comp ){
            default:
            case '=':
                $statement = $wpdb->prepare( "$key = $insert", $value );
                break;
            case '!=':
                $statement = $wpdb->prepare( "$key = $insert", $value );
                break;
            case 'LIKE sw':
                $statement = $wpdb->prepare( "$key LIKE '%s'", $value . '%' );
                break;
            case 'LIKE ew':
                $statement = $wpdb->prepare( "$key LIKE '%s'", '%' . $value );
                break;
            case 'LIKE c':
                $statement = $wpdb->prepare( "$key LIKE '%s'", '%' . $value . '%' );
                break;
            case 'NOT LIKE c':
                $statement = $wpdb->prepare( "$key NOT LIKE '%s'", '%' . $value . '%' );
                break;
            case 'EMPTY':
                $statement = "$key IS EMPTY";
                break;
            case 'NOT EMPTY':
                $statement =  "$key IS NOT EMPTY";
                break;
        }

        return $statement;


    }

    /**
     * From the search.php page access the POST and generate a WHERE clause...
     */
    private function do_search()
    {

        global $wpdb;

        $contacts       = WPGH()->contacts->table_name;
        $contact_meta   = WPGH()->contact_meta->table_name;
        $tags           = WPGH()->tag_relationships->table_name;

        $SELECT = "SELECT DISTINCT c.* FROM $contacts AS c LEFT JOIN $contact_meta AS meta ON c.ID = meta.contact_id LEFT JOIN $tags AS tags ON c.ID = tags.contact_id";
        $WHERE = "WHERE ";
        $CLAUSES = array();

        $general = $_POST[ 'c' ];
        $meta    = $_POST[ 'meta' ];
        $custom  = $_POST[ 'c_meta' ];
        $tags    = $_POST[ 'tags' ];
//        $tags_2    = $_POST[ 'tags_2' ];

        foreach ( $general as $key => $args ){

            if ( ! empty( $args[ 'search' ] ) && ! empty( $args[ 'comp' ] ) ){

                $search = $wpdb->esc_like( sanitize_text_field( stripslashes( $args[ 'search' ] ) ) );
                $CLAUSES[] = $this->generate_comparison_statement( 'c.' . $key, $args[ 'comp' ], $search );

            }

        }

        foreach ( $meta as $key => $args ){

            if ( ! empty( $args[ 'search' ] ) && ! empty( $args[ 'comp' ] ) ){

                $search = $wpdb->esc_like( sanitize_text_field( stripslashes( $args[ 'search' ] ) ) );
                $CLAUSES[] = $this->generate_comparison_statement( 'meta.meta_key', '=', sanitize_key( $key ) );
                $CLAUSES[] = $this->generate_comparison_statement( 'meta.meta_value', $args[ 'comp' ], $search );

            }

        }

        foreach ( $custom as $key => $args ){

            if ( ! empty( $args[ 'key' ] ) && ! empty( $args[ 'search' ] ) && ! empty( $args[ 'comp' ] ) ){

                $search = $wpdb->esc_like( sanitize_text_field( stripslashes( $args[ 'search' ] ) ) );
                $CLAUSES[] = $this->generate_comparison_statement( 'meta.meta_key', '=', sanitize_key( $args[ 'key' ] ) );
                $CLAUSES[] = $this->generate_comparison_statement( 'meta.meta_value', $args[ 'comp' ], $search );

            }

        }

        $tags_1 = wp_parse_id_list( $tags[ 'tags_1' ]['tags'] );
        $tags_2 = wp_parse_id_list( $tags[ 'tags_2' ]['tags'] );

        $SQL = sprintf( '%s %s %s', $SELECT, $WHERE, implode( ' AND ', $CLAUSES ) );

        var_dump($SQL);
        $results = $wpdb->get_results( $SQL );
        var_dump( $results );
        die();

    }

    /**
     * Display the title and dependent action include the appropriate page content
     */
    function page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1>
            <a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_contacts&action=add' ); ?>"><?php _ex( 'Add New', 'page_title_action', 'groundhogg' ); ?></a>
            <a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_tools&tab=import&action=add' ); ?>"><?php _ex( 'Import', 'page_title_action', 'groundhogg' ); ?></a>
            <?php $this->notices->notices(); ?>
            <hr class="wp-header-end">
            <?php switch ( $this->get_action() ){
                case 'add':
                    $this->add();
                    break;
                case 'edit':
                    $this->edit();
                    break;
                case 'search':
                    $this->search();
                    break;
                case 'form':
                    $this->form();
                    break;
                default:
                    $this->table();
            } ?>
        </div>
        <?php
    }

    /**
     * Add Ajax actions...
     *
     * @return mixed
     */
    protected function add_ajax_actions()
    {
        // TODO: Implement add_ajax_actions() method.
    }

    /**
     * Adds additional actions.
     *
     * @return mixed
     */
    protected function add_additional_actions()
    {
        // TODO: Implement add_additional_actions() method.
    }

    /**
     * Get the page slug
     *
     * @return string
     */
    public function get_slug()
    {
        // TODO: Implement get_slug() method.
    }

    /**
     * Get the menu name
     *
     * @return string
     */
    public function get_name()
    {
        // TODO: Implement get_name() method.
    }

    /**
     * The required minimum capability required to load the page
     *
     * @return string
     */
    public function get_cap()
    {
        // TODO: Implement get_cap() method.
    }

    /**
     * Get the item type for this page
     *
     * @return mixed
     */
    public function get_item_type()
    {
        // TODO: Implement get_item_type() method.
    }

    /**
     * Output the basic view.
     *
     * @return mixed
     */
    public function view()
    {
        // TODO: Implement view() method.
    }
}