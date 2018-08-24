<?php

/**
 * Check if GDPR is enabled throughout the plugin.
 *
 * @return bool, whether it's enable or not.
 */
function wpfn_is_gdpr()
{
    return in_array( 'on', get_option( 'gh_enable_gdpr', array() ) );
}

/**
 * Output the form html based on the settings.
 *
 * @param $atts array the shortcode attributes
 * @return string the form html
 */
function wpfn_form_shortcode( $atts )
{
    $a = shortcode_atts( array(
        'fields' => 'first,last,email,phone,terms',
        'submit' => __( 'Submit' ),
        'success' => '',
        'labels' => 'on',
        'id' => 0,
        'gdpr' => 'off',
        'classes' => ''
    ), $atts );

    $fields = array_map( 'trim', explode( ',', $a['fields'] ) );

    $form = '<div class="gh-form-wrapper">';

    $form .= "<form method='post' class='gh-form " . $a[ 'classes' ] ."' action='" . esc_url_raw( $a['success'] ) . "'>";

    $form .= wp_nonce_field( 'gh_submit', 'gh_submit_nonce', true, false );

    $form .="<input type='hidden' name='step_id' value='" . $a['id'] . "'>";

    foreach ( $fields as $type ){

        $form .= '<div class="gh-form-field"><p>';

        $id = uniqid( 'gh-' );

        switch ( $type ) {
            case 'first':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' . __( 'First Name', 'groundhogg' );
                $form .= ' <input class="gh-form-input" type="text" name="first_name" id="' . $id . '" pattern="[A-Za-z \-\']+" title="' . __( 'Do not include numbers or special characters.', 'groundhogg' ) . '" placeholder="' . __( 'First Name', 'groundhogg' ). '" required>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'last':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' . __( 'Last Name', 'groundhogg' );
                $form .= ' <input class="gh-form-input" type="text" name="last_name" id="' . $id . '" pattern="[A-Za-z \-\']+" title="' . __( 'Do not include numbers or special characters.', 'groundhogg' ) . '" placeholder="' . __( 'Last Name', 'groundhogg' ). '" required>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'email':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' . __( 'Email', 'groundhogg' );
                $form .= ' <input class="gh-form-input" type="email" name="email" id="' . $id . '" title="' . __( 'Email', 'groundhogg' ) . '" placeholder="' . __( 'Email', 'groundhogg' ). '" required>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'phone':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' . __( 'Phone', 'groundhogg' );
                $form .= ' <input class="gh-form-input" type="tel" name="phone" id="' . $id . '" title="' . __( 'Phone', 'groundhogg' ) . '" placeholder="' . __( 'Phone', 'groundhogg' ). '" required>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'terms':
                $form .= '<label>';
                $form .= ' <input class="gh-form-input" type="checkbox" name="agree_terms" id="' . $id . '" title="' . __( 'Terms Agreement', 'groundhogg' ) . '" required> ';
                $form .=  __( 'I agree to the Terms of Service.' , 'groundhogg' ) . '</label>';
                break;
        }
    }

    if ( wpfn_is_gdpr() )
    {
        $form .= '<div class="gh-consent-field"><p>';

        $form .= '<label>';
        $form .= ' <input class="gh-form-input" type="checkbox" name="gdpr_consent" id="' . $id . '" title="' . __( 'Explicit Consent', 'groundhogg' ) . '" required> ';
        $form .=  __( 'I consent to receive marketing & transactional information from ' . get_bloginfo( 'name' ) . '.' , 'groundhogg' ) . '</label>';

        $form .= '</p></div>';
    }

    $form = apply_filters( 'wpfn_form_shortcode', $form );

    $form .= "<div class='gh-submit-field'><p><input type='submit' name='submit' value='" . $a['submit'] . "'></p></div>";
    $form .= '</form>';
    $form .= '</div>';

    return $form;
}

add_shortcode( 'gh_form', 'wpfn_form_shortcode' );


/**
 * Listens for basic contact information whenever the post variable is exists.
 */
function wpfn_form_submit_listener()
{
    /* verify real user */
    if ( ! isset( $_POST[ 'gh_submit_nonce' ] ) )
        return;

    if( ! wp_verify_nonce( $_POST[ 'gh_submit_nonce' ], 'gh_submit' ) )
        wp_redirect( wp_get_referer() );

    if ( wpfn_is_gdpr() ){
        if ( ! isset( $_POST[ 'gdpr_consent' ] ) )
            wp_redirect( wp_get_referer() );
    }

    /* verify email exists */
    if ( ! isset( $_POST['email'] ) || ! isset( $_POST[ 'step_id' ] ) )
        return;

    if ( isset( $_POST[ 'first_name' ] ) )
        $args['first'] = sanitize_text_field( $_POST[ 'first_name' ] );

    if ( isset( $_POST[ 'last_name' ] ) )
        $args['last'] = sanitize_text_field( $_POST[ 'last_name' ] );

    if ( isset( $_POST[ 'email' ] ) )
        $args['email'] = sanitize_email( $_POST[ 'email' ] );

    if ( isset( $_POST[ 'phone' ] ) )
        $args['phone'] = sanitize_text_field( $_POST[ 'phone' ] );

    if ( ! is_email( $args['email'] ) )
        wp_redirect( wp_get_referer() );

    $args = wp_parse_args( $args, array(
        'first' => '',
        'last'  => '',
        'email' => '',
        'phone' => '',
    ));

    $id = wpfn_quick_add_contact( $args['email'], $args['first'], $args['last'], $args['phone'] );

    $contact = new WPFN_Contact( $id );
    /* if gdpr is enabled, make sure that the consent box is checked */
    if ( wpfn_is_gdpr() )
        wpfn_update_contact_meta( $id, 'gdpr_consent', 'yes' );

    /* Set the IP address of the contact */
    wpfn_update_contact_meta( $id, 'ip_address', wpfn_get_visitor_ip() );

    /* Set the Leadsource if it doesn't exist */
    if ( ! wpfn_get_contact_meta( $id, 'source_page', true) )
        wpfn_update_contact_meta( $id, 'source_page', wp_get_referer() );

    /* if the contact previously unsubscribed, set them to unconfirmed. */
    if ( $contact->get_optin_status() === WPFN_UNSUBSCRIBED )
        wpfn_update_contact( $id, 'optin_status', WPFN_UNCONFIRMED );

    $step = intval( $_POST[ 'step_id' ] );

    /* make sure the funnel for the step is active*/
    if ( ! wpfn_get_funnel_step_by_id( $step ) || ! wpfn_is_funnel_active( wpfn_get_step_funnel( $step ) ) )
        wp_die( __( 'This form is not accepting submissions right now.' ) );

    do_action( 'wpfn_form_submit', $step, $id );

    /* set the contact cookie */
    wpfn_set_the_contact( $id );
    /* set the active funnel cookie*/
    wpfn_set_the_funnel( wpfn_get_step_funnel( $step ) );
    /* set the funnel step cookie*/
    wpfn_set_the_step( $step );

    /* redirect to ensure cookie is set and can be used on the following page*/
    wp_redirect( $_SERVER['REQUEST_URI'] );
    die();
}

add_action( 'init', 'wpfn_form_submit_listener' );

/**
 * Ouput the html for the email preferences form.
 *
 * @return string
 */
function wpfn_email_preferences_form()
{

    $contact = wpfn_get_the_contact();

    if ( ! $contact )
        return __( 'No email to manage.' );

    ob_start();

    ?>
    <div class="gh-form-wrapper">
        <p><?php _e( 'Hi' )?> <strong><?php echo $contact->get_first(); ?></strong>,</p>
        <p><?php _e( 'You are managing your email preferences for the email address: ', 'groundhogg' ) ?> <strong><?php echo $contact->get_email(); ?></strong></p>
        <form class="gh-form" method="post" action="">
            <?php wp_nonce_field( 'change_email_preferences', 'email_preferences_nonce' ) ?>
            <?php if ( ! empty( $_POST ) ):
                ?><div class="gh-notice"><p><?php _e( 'Preferences Updated!', 'groundhogg' ); ?></p></div><?php
            endif;
            ?>
            <div class="gh-form-field">
                <p>
                    <label><input type="radio" name="preference" value="none"> <?php _e( 'I love you guys. Send email whenever you want!' ); ?></label>
                </p>
            </div>
            <div class="gh-form-field">
                <p>
                    <label><input type="radio" name="preference" value="weekly"> <?php _e( 'It\'s a bit much... start sending me emails weekly.' ); ?></label>
                </p>
            </div>
            <div class="gh-form-field">
                <p>
                    <label><input type="radio" name="preference" value="monthly"> <?php _e( 'Distance makes the heart grow fonder. Only send emails monthly.' ); ?></label>
                </p>
            </div>
            <div class="gh-form-field">
                <p>
                    <label><input type="radio" name="preference" value="unsubscribe"> <?php _e( 'I no longer wish to receive any emails. Unsubscribe me!' ); ?></label>
                </p>
            </div>
            <div class="gh-form-field">
                <p>
                    <input type='submit' name='change_preferences' value='<?php _e('Change Preferences','groundhogg'); ?>' >
                    <?php if ( wpfn_is_gdpr() ):?>
                        <input type='submit' name='delete_everything' value='<?php _e('Delete Everything You Know About Me', 'groundhogg'); ?>' >
                    <?php endif; ?>
                </p>
            </div>
        </form>
    </div>

    <?php

    $form = ob_get_contents();

    ob_end_clean();

    return $form;

}

add_shortcode( 'gh_email_preferences', 'wpfn_email_preferences_form' );

/**
 * Process changes to the subscription status of a contact.
 */
function wpfn_process_email_preferences_changes()
{
    if ( ! isset( $_POST[ 'email_preferences_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'email_preferences_nonce' ], 'change_email_preferences' ) )
        return;

    $contact = wpfn_get_the_contact();

    if ( ! $contact )
        return;

    if ( isset( $_POST[ 'delete_everything' ] ) )
    {

        do_action( 'wpfn_delete_everything', $contact->get_id() );

        wpfn_delete_contact( $contact->get_id() );

        $unsub_page = get_permalink( get_option( 'gh_unsubscribe_page' ) );

        do_action( 'wpfn_preference_unsubscribe', $contact->get_id() );

        wp_redirect( $unsub_page );
        die();
    }

    $preference = $_POST[ 'preference' ];

    switch ( $preference ){
        case 'none':

            wpfn_update_contact( $contact->get_id(), 'optin_status', WPFN_CONFIRMED );

            do_action( 'wpfn_preference_none', $contact->get_id() );

            break;
        case 'weekly':

            wpfn_update_contact( $contact->get_id(), 'optin_status', WPFN_WEEKLY );

            do_action( 'wpfn_preference_weekly', $contact->get_id() );

            break;
        case 'monthly':

            wpfn_update_contact( $contact->get_id(), 'optin_status', WPFN_MONTHLY );

            do_action( 'wpfn_preference_monthly', $contact->get_id() );

            break;
        case 'unsubscribe':

            wpfn_update_contact( $contact->get_id(), 'optin_status', WPFN_UNSUBSCRIBED );

            $unsub_page = get_permalink( get_option( 'gh_unsubscribe_page' ) );

            do_action( 'wpfn_preference_unsubscribe', $contact->get_id() );

            wp_redirect( $unsub_page );
            die();
            break;
    }
}

add_action( 'init', 'wpfn_process_email_preferences_changes' );