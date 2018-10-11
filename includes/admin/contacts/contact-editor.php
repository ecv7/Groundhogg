<?php
/**
 * Contact Record
 *
 * Allow the user to edit the contact details and contact fields
 *
 * @package     groundhogg
 * @subpackage  Includes/Contacts
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$id = intval( $_GET[ 'contact' ] );

$contact = new WPGH_Contact( $id );

if ( ! $contact->exists() ) {
    wp_die( __( 'This contact has been deleted.', 'groundhogg' ) );
}

include "class-wpgh-contact-activity-table.php";
include "class-wpgh-contact-events-table.php";

wp_enqueue_script( 'contact-editor', WPGH_ASSETS_FOLDER . 'js/admin/contact-editor.js' )
?>

<!-- Title -->
<span class="hidden" id="new-title"><?php echo $contact->full_name; ?> &lsaquo; </span>
<script>
    document.title = jQuery( '#new-title' ).text() + document.title;
</script>
<!--/ Title -->

<form method="post" class="">
    <?php wp_nonce_field( 'edit' ); ?>

    <!-- GENERAL NAME INFO -->
    <h2><?php _e( 'Name' ) ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="first_name"><?php echo __( 'First Name', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'first_name',
                    'name'  => 'first_name',
                    'value' => $contact->first_name,
                );
            echo WPGH()->html->input( $args ); ?>
            </td>
        </tr>
        <tr>
            <th><label for="last_name"><?php echo __( 'Last Name', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'last_name',
                    'name'  => 'last_name',
                    'value' => $contact->last_name,
                );
                echo WPGH()->html->input( $args ); ?></td>
        </tr>
        <?php do_action( 'wpgh_contact_edit_name', $id ); ?>
        </tbody>
    </table>

    <!-- GENERAL CONTACT INFO -->
    <h2><?php _e( 'Contact Info' ); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="email"><?php echo __( 'Email', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'type'  => 'email',
                    'id'    => 'email',
                    'name'  => 'email',
                    'value' => $contact->email,
                );
                echo WPGH()->html->input( $args ); ?>
                <label><span class="row-actions"><a style="text-decoration: none" target="_blank" href="<?php echo esc_url(substr(  $contact->email, strpos( $contact->email, '@' ) ) ); ?>"><span class="dashicons dashicons-external"></span></a></span>
                <p class="submit"><?php echo '<b>' . __('Email Status', 'groundhogg') . ': </b>' . wpgh_get_optin_status_text( $contact->ID ); ?></p>
                <?php if ( $contact->optin_status !== WPGH_UNSUBSCRIBED ): ?>
                    <input type="checkbox" name="unsubscribe" value="1"><?php _e( 'Mark as unsubscribed.' )?></label>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label for="primary_phone"><?php echo __( 'Primary Phone', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'type'  => 'tel',
                    'id'    => 'primary_phone',
                    'name'  => 'primary_phone',
                    'value' => $contact->get_meta( 'primary_phone' ),
                );
                echo WPGH()->html->input( $args ); ?></td>
        </tr>
        <tr>
            <th><label for="primary_phone_extension"><?php echo __( 'Phone Extension', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'primary_phone_extension',
                    'name'  => 'primary_phone_extension',
                    'value' => $contact->get_meta( 'primary_phone_extension' ),
                );
                echo WPGH()->html->input( $args ); ?></td>
        </tr>
        <?php do_action( 'wpgh_contact_edit_contact_info', $id ); ?>
        </tbody>
    </table>

    <!-- MARKETING COMPLIANCE INFORMATION -->
    <h2><?php _e( 'Compliance' ); ?></h2>
    <table class="form-table">
        <tbody>
            <tr>
                <th><?php _e( 'Agreed To Terms' ); ?></th>
                <td><?php echo (  $contact->get_meta( 'terms_agreement') === 'yes' ) ? sprintf( "%s: %s",  __( 'Agreed' ),  $contact->get_meta( 'terms_agreement_date' ) ): '&#x2014;'; ?></td>
            </tr>
            <?php if ( wpgh_is_gdpr() ): ?>
                <tr>
                    <th><?php _e( 'GDPR Consent' ); ?></th>
                    <td><?php echo (  $contact->get_meta( 'gdpr_consent' ) === 'yes' ) ? sprintf( "%s: %s",  __( 'Agreed' ),  $contact->get_meta( 'gdpr_consent_date' ) ) : '&#x2014;'; ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- SEGMENTATION AND LEADSOURCE -->
    <h2><?php _e( 'Segmentation' ); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><?php _e( 'Owner', 'groundhogg' ); ?></th>
            <td><?php $args = array(
                    'show_option_none'  => __( 'Select an owner' ),
                    'id'                => 'owner',
                    'name'              => 'owner',
                    'role'              => 'administrator',
                    'class'             => 'cowner',
                    'selected'          => $contact->owner
                ); wp_dropdown_users( $args ); ?>
            </td>
        </tr>
        <tr>
            <th><?php _e( 'Source Page', 'groundhogg' ); ?></th>
            <td><?php $args = array(
                    'id'    => 'page_source',
                    'name'  => 'page_source',
                    'value' => $contact->get_meta( 'source_page' ),
                );
                echo WPGH()->html->input( $args ); ?>
                <span class="row-actions">
                    <a style="text-decoration: none" target="_blank" href="<?php echo esc_url( $contact->get_meta( 'source_page' ) ); ?>"><span class="dashicons dashicons-external"></span></a>
                </span>
                <p class="description">
                    <?php _e( "This is the page which the contact first submitted a form.", 'groundhogg' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th><?php _e( 'Lead Source', 'groundhogg' ); ?></th>
            <td><?php $args = array(
                    'id' => 'lead_source',
                    'name' => 'lead_source',
                    'value' => $contact->get_meta( 'lead_source' ),
                );
                echo WPGH()->html->input( $args ); ?>
                <span class="row-actions">
                    <a style="text-decoration: none" target="_blank" href="<?php echo esc_url( $contact->get_meta( 'lead_source' ) ); ?>"><span class="dashicons dashicons-external"></span></a>
                </span>
                <p class="description"><?php _e( "This is where the contact originated from.", 'groundhogg' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="tags"><?php echo __( 'Tags', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'        => 'tags',
                    'name'      => 'tags',
                    'selected'  => $contact->tags,
                ); echo WPGH()->html->tag_picker( $args ); ?>
            </td>
        </tr>
        <?php do_action( 'wpgh_contact_edit_tags', $id ); ?>
        </tbody>
    </table>

    <!-- NOTES -->
    <h2><?php _e( 'Notes' ); ?></h2>
    <table>
        <tbody>
        <tr>
            <td>
                <?php $args = array(
                    'id'    => 'notes',
                    'name'  => 'notes',
                    'value' => $contact->get_meta( 'notes' ),
                );
                echo WPGH()->html->textarea( $args ); ?>
            </td>
        </tr>
        <?php do_action( 'wpgh_contact_edit_notes', $id ); ?>
        </tbody>
    </table>

    <!-- META -->
    <h2><?php _e( 'Custom Meta' ); ?></h2>
    <table class="form-table" >
        <tr>
            <th><label for="edit_meta"><?php _e( 'Edit Meta' ); ?></label></th>
            <td><input type="checkbox" name="edit_meta" id="edit_meta" value="1"></td>
        </tr>
    </table>
    <script>
        jQuery(function($){
            $('#edit_meta').change(function(){
                $('#meta-table').toggleClass( 'hidden' );
            })
        });
    </script>
    <table id='meta-table' class="form-table hidden" >
        <tbody>
        <tr>
            <th>
                <button type="button" class="button-secondary addmeta"><?php _e( 'Add Meta' ); ?></button>
                <div class="hidden">
                    <span class="metakeyplaceholder"><?php esc_attr_e( 'Key' ); ?></span>
                    <span class="metavalueplaceholder"><?php esc_attr_e( 'Value' ); ?></span>
                </div>
            </th>
        </tr>
            <?php
            $meta = WPGH()->contact_meta->get_meta( $contact->ID );
            foreach ( $meta as $meta_key => $value ):
                $value = $value[ 0 ]; ?>
            <tr id="meta-<?php esc_attr_e( $meta_key )?>">
                <th>
                   <?php esc_html_e( $meta_key ); ?>
                    <p class="description">{_<?php esc_html_e( $meta_key ); ?>}</p>
                </th>
                <td>
                    <input type="text" id="<?php esc_attr_e( $meta_key )?>" name="meta[<?php esc_attr_e( $meta_key ); ?>]" class="regular-text" value="<?php esc_attr_e( $value ); ?>">
                    <span class="row-actions"><span class="delete"><a style="text-decoration: none" href="javascript:void(0)" class="deletemeta"><span class="dashicons dashicons-trash"></span></a></span></span>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php do_action( 'wpgh_contact_edit_meta', $id ); ?>
        </tbody>
    </table>
    <?php do_action( 'wpgh_contact_edit_before_history', $id ); ?>

    <!-- UPCOMING EVENTS -->
    <h2><?php _e( 'Upcoming Events' ); ?></h2>
    <?php $events = WPGH()->events->get_events( array( 'contact_id' => $contact->ID, 'status' => 'waiting' ) );

    $table = new WPGH_Contact_Events_Table();
    $table->data = $events;

    $table->prepare_items();
    $table->display(); ?>

    <p class="description"><?php _e( 'Any upcoming funnel steps will show up here. you can choose to cancel them or to run them immediately.', 'groundhogg' ); ?></p>

    <!-- FUNNNEL HISTORY -->
    <h2><?php _e( 'Recent Funnel History' ); ?></h2>
    <?php $events = WPGH()->events->get_events( array( 'contact_id' => $contact->ID, 'status' => 'complete' ) );

    $table = new WPGH_Contact_Events_Table();
    $table->data = $events;

    $table->prepare_items();
    $table->display(); ?>

    <p class="description"><?php _e( 'Any previous funnel steps will show up here. You can choose run them again.<br/>
    This report only shows the 20 most recent events, to see more you can see all this contact\'s history in the event queue.', 'groundhogg' ); ?></p>

    <!-- EMAIL HISTORY -->
    <h2><?php _e( 'Recent Email History' ); ?></h2>
    <?php global $wpdb;

        $events = WPGH()->events->table_name;
        $steps = WPGH()->steps->table_name;

        $events = $wpdb->get_results( $wpdb->prepare(
                "SELECT e.*,s.funnelstep_type FROM $table e 
                        LEFT JOIN $steps s ON e.step_id = s.ID 
                        WHERE e.contact_id = %d AND e.status = %s AND ( s.funnelstep_type = %s OR e.funnel_id = %d )
                        ORDER BY time DESC"
                , $id, 'complete', 'send_email', WPGH_BROADCAST )
        );

        $table = new WPGH_Contact_Activity_Table();
        $table->data = $events;
        $table->prepare_items();
        $table->display(); ?>

    <p class="description"><?php _e( 'This is where you can check if this contact is interacting with your emails.', 'groundhogg' ); ?></p>

    <!-- THE END -->
    <?php do_action( 'wpgh_contact_edit_after', $id ); ?>
    <div class="edit-contact-actions">
        <p class="submit">
            <?php submit_button('Update Contact', 'primary', null, false ); ?>
            <span id="delete-link"><a class="delete" href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gh_contacts&action=delete&contact='. $id ), 'delete'  ) ?>"><?php _e( 'Delete' ); ?></a></span>
        </p>
    </div>
</form>
