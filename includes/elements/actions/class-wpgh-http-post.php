<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-04
 * Time: 5:42 PM
 */

class WPGH_HTTP_Post extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'http_post';

    /**
     * @var string
     */
    public $group   = 'action';

    /**
     * @var string
     */
    public $icon    = 'http-post.png' ;

    /**
     * @var string
     */
    public $name    = 'HTTP Post';

    /**
     * Display the settings
     *
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {
        $post_keys      = $step->get_meta( 'post_keys' );
        $post_values    = $step->get_meta( 'post_values' );
        $post_url       = esc_url_raw( $step->get_meta( 'post_url' ) );

        if ( ! is_array( $post_keys ) || ! is_array( $post_values ) ){
            $post_keys = array( '' ); //empty to show first option.
            $post_values = array( '' ); //empty to show first option.
        }

        ?>

        <table class="form-table" id="meta-table-<?php echo $step->ID ; ?>">
            <tbody>
            <tr>
                <td>
                    <strong><?php _e( 'Post Url:', 'groundhogg' ); ?></strong>
                </td>
                <td colspan="2">
                    <?php $args = array(
                        'type'  => 'url',
                        'name'  => $step->prefix( 'post_url' ),
                        'id'    => $step->prefix( 'post_url' ),
                        'value' => $post_url
                    );

                    echo WPGH()->html->input( $args ); ?>
                </td>
            </tr>
            <?php foreach ( $post_keys as $i => $post_key): ?>
                <tr>
                    <td>
                        <label><strong><?php _e( 'Key: ' ); ?></strong>

                            <?php $args = array(
                                'name'  => $step->prefix( 'post_keys' ) . '[]',
                                'value' => sanitize_key( $post_key )
                            );

                            echo WPGH()->html->input( $args ); ?>

                        </label>
                    </td>
                    <td>
                        <label><strong><?php _e( 'Value: ' ); ?></strong> <?php $args = array(
                                'name'  => $step->prefix( 'post_values' ) . '[]',
                                'value' => esc_html( $post_values[$i] )
                            );

                            echo WPGH()->html->input( $args ); ?></label>
                    </td>
                    <td>
                    <span class="row-actions">
                        <span class="add"><a style="text-decoration: none" href="javascript:void(0)" class="addmeta"><span class="dashicons dashicons-plus"></span></a></span> |
                        <span class="delete"><a style="text-decoration: none" href="javascript:void(0)" class="deletemeta"><span class="dashicons dashicons-trash"></span></a></span>
                    </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <script>
            jQuery(function($){
                var table = $( "#meta-table-<?php echo $step->ID; ?>" );
                table.click(function ( e ){
                    var el = $(e.target);
                    if ( el.closest( '.addmeta' ).length ) {
                        el.closest('tr').last().clone().appendTo( el.closest('tr').parent() );
                        el.closest('tr').parent().children().last().find( ':input' ).val( '' );
                    } else if ( el.closest( '.deletemeta' ).length ) {
                        el.closest( 'tr' ).remove();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Save the settings
     *
     * @param $step WPGH_Step
     */
    public function save( $step )
    {

        if ( isset( $_POST[ $step->prefix( 'post_url' ) ] ) ){
            $step->update_meta( 'post_url', esc_url_raw( $_POST[ $step->prefix( 'post_url' ) ] ) );
        }

        if ( isset( $_POST[ $step->prefix(  'post_keys' ) ]  ) ){
            $post_keys = $_POST[ $step->prefix(  'post_keys' ) ];
            $post_values = $_POST[ $step->prefix( 'post_values' ) ];

            if ( ! is_array( $post_keys ) )
                return;

            $post_keys = array_map( 'sanitize_key', $post_keys );
            $post_values = array_map( 'sanitize_text_field', $post_values );

            $step->update_meta( 'post_keys', $post_keys );
            $step->update_meta( 'post_values', $post_values );
        }

    }

    /**
     * Process the http post step...
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return bool|object
     */
    public function run( $contact, $event )
    {

        $post_keys = $event->step->get_meta( 'post_keys' );
        $post_values = $event->step->get_meta( 'post_values' );

        if ( ! is_array( $post_keys ) || ! is_array( $post_values ) || empty( $post_keys ) || empty( $post_values ) ){
            return false;
        }

        $post_array = array();

        foreach ( $post_keys as $i => $key )
        {
            if ( ! empty( $key ) ){
                $post_array[ sanitize_key( $key ) ] = WPGH()->replacements->do( sanitize_text_field( $post_values[ $i ] ), $contact->ID );
            }
        }

        $post_url = $event->step->get_meta( 'post_url' );
        $post_url = WPGH()->replacements->do( esc_url_raw( $post_url ), $contact->ID );

        $response = wp_remote_post( $post_url, array(
            'body' => $post_array
        ) );

        if ( is_wp_error( $response ) ) {
            $contact->add_note( sanitize_text_field( $response->get_error_message() ) );
        }

        return $response;

    }


}