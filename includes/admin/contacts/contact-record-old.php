<?php
/**
 * Contact Record
 *
 * Allow the user to edit the contact details and contact fields
 *
 * @package     wp-funnels
 * @subpackage  Includes/Contacts
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $_GET['ID'] ) || ! is_numeric( $_GET['ID'] ) )
{
	wp_die( __( 'Contact ID not supplied. Please try again', 'wp-funnels' ), __( 'Error', 'wp-funnels' ) );
}

$contact_id = intval( $_GET['ID'] );

if ( isset( $_POST['update_contact_nonce'] ) && wp_verify_nonce( $_POST['update_contact_nonce'], 'update_contact' ) && current_user_can( 'manage_options' ) )
{
	if ( ! isset( $_POST['email'] ) ){
		?><div class="notice notice-error"><p>An email is required to update the contact.</p></div><?php
	} else {

	    do_action( 'wpfn_contact_update_before' );

	    $email = sanitize_text_field( $_POST['email'] );

	    wpfn_update_contact_email( $contact_id, $email );

		$first_name = ( isset($_POST['first_name']) )? sanitize_text_field( $_POST['first_name'] ) : '';

		wpfn_update_contact( $contact_id, 'first_name', $first_name );

		$last_name =  ( isset($_POST['last_name']) )? sanitize_text_field( $_POST['last_name'] ): '';

		wpfn_update_contact( $contact_id, 'last_name', $last_name );

		if ( isset( $_POST['meta'] ) && is_array( $_POST['meta'] ) )
        {
            foreach ( $_POST['meta'] as $key => $value )
            {
                wpfn_update_contact_meta( $contact_id, $key, $value );
            }
        }

        do_action( 'wpfn_contact_update_after' );

		wpfn_log_contact_activity( $contact_id, 'User ' . wp_get_current_user()->user_login . ' Updated Contact Via Admin.')

		?><div class="notice notice-success"><p>Successfully updated contact!</p></div><?php

	}
}

$contact = new WPFN_Contact( $contact_id );

?>

<div class="wrap">
	<h1><?php printf( '%s', $contact->getFullName() ); ?></h1>
	<?php do_action( 'wpfn_contact_record_before', $contact_id ); ?>
    <form method="post">

        <?php wp_nonce_field('update_contact', 'update_contact_nonce' ); ?>

        <?php do_action( 'wpfn_contact_record_form_before', $contact_id );?>

        <?php
	    if( isset( $_GET[ 'tab' ] ) ) {
		    $active_tab = $_GET[ 'tab' ];
	    } else {
	        $active_tab = 'general';
        }
	    ?>

        <h2 class="nav-tab-wrapper">
            <a href="?page=contacts&ID=<?php echo $contact_id; ?>&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General</a>
            <a href="?page=contacts&ID=<?php echo $contact_id; ?>&tab=activity" class="nav-tab <?php echo $active_tab == 'activity' ? 'nav-tab-active' : ''; ?>">Activity</a>
            <a href="?page=contacts&ID=<?php echo $contact_id; ?>&tab=funnels" class="nav-tab <?php echo $active_tab == 'funnels' ? 'nav-tab-active' : ''; ?>">Funnels</a>
            <a href="?page=contacts&ID=<?php echo $contact_id; ?>&tab=tags" class="nav-tab <?php echo $active_tab == 'tags' ? 'nav-tab-active' : ''; ?>">Tags</a>
            <a href="?page=contacts&ID=<?php echo $contact_id; ?>&tab=orders" class="nav-tab <?php echo $active_tab == 'orders' ? 'nav-tab-active' : ''; ?>">Orders</a>
            <?php do_action('wpfn_contact_record_tabs_after', $contact_id ); ?>
        </h2>

        <?php switch ( $active_tab ):

            case 'general': ?>

            <h3><?php echo __( 'General Information', 'wp-funnels' ); ?></h3>

            <?php do_action( 'wpfn_contact_record_general_before', $contact_id ); ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th><label for="first_name"><?php echo __( 'First Name', 'wp-funnels' )?></label></th>
                        <td><?php echo wpfn_admin_text_input_field( 'first_name', 'first_name', $contact->getFirst() );?></td>
                    </tr>
                    <tr>
                        <th><label for="last_name"><?php echo __( 'Last Name', 'wp-funnels' )?></label></th>
                        <td><?php echo wpfn_admin_text_input_field( 'last_name', 'last_name', $contact->getLast() );?></td>
                    </tr>
                    <tr>
                        <th><label for="email"><?php echo __( 'Email', 'wp-funnels' )?></label></th>
                        <td>
                            <?php echo wpfn_admin_text_input_field( 'email', 'email', $contact->getEmail() );?>
                            <p><?php echo '<b>' . __('Email Status', 'wp-funnels') . ': </b>' . wpfn_get_optin_status_text( $contact->getOptInStatus() ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="primary_phone"><?php echo __( 'Primary Phone', 'wp-funnels' )?></label></th>
                        <td><?php echo wpfn_admin_text_input_field( 'primary_phone', 'meta[primary_phone]', $contact->getPhone() );?></td>
                    </tr>
                    <tr>
                        <th><label for="primary_phone_extension"><?php echo __( 'Phone Extension', 'wp-funnels' )?></label></th>
                        <td><?php echo wpfn_admin_text_input_field( 'primary_phone_extension', 'meta[primary_phone_extension]', $contact->getPhoneExtension() );?></td>
                    </tr>
                </tbody>
            </table>

            <?php do_action( 'wpfn_contact_record_general_after', $contact_id ); ?>

            <?php submit_button( 'Save Changes', 'primary' ); ?>

            <?php break; ?>

            <?php case 'activity': ?>

            <h3><?php echo __( 'Recent Activity', 'wp-funnels' ); ?></h3>

            <table class="wp-list-table widefat striped contact-activity">
                <thead>
                    <tr>
                        <th><?php echo __('Date', 'wp-funnels');?></th>
                        <th><?php echo __('Entry', 'wp-funnels');?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $entries = $contact->getParsedActivity(); ?>
                    <?php if ( $entries ): foreach ( $entries as $entry ): ?>
                    <tr>
                        <?php if ( isset( $entry[0] ) && isset( $entry[1] ) ): ?>
                        <td><?php echo $entry[0];?></td>
                        <td><?php echo $entry[1];?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; endif;?>
                    <?php if ( empty( $entries ) ):?>
                    <tr>
                        <td colspan="2">
	                        <?php echo __( 'No Recent Activity Recorded...', 'wp-funnels' ); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php break; ?>

        <?php case 'funnels': ?>
        <?php break; ?>

        <?php case 'tags': ?>
        <?php break; ?>

        <?php case 'orders': ?>
        <?php break; ?>

        <?php default: ?>

            <?php do_action( 'wpfn_contact_record_tab_' . $active_tab ); ?>

        <?php break; ?>

        <?php endswitch; ?>

    </form>
</div>

<?php