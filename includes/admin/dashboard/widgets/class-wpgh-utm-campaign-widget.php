<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class WPGH_UTM_Campaign_Widget extends WPGH_Reporting_Widget
{

    /**
     * WPGH_Report_V2 constructor.
     */
    public function __construct()
    {
        $this->wid = 'groundhogg_utm_campaign_widget';
        $this->name = _x( 'UTM Campaign Report', 'widget_name', 'groundhogg' );

        parent::__construct();
    }

    /**
     * Get table of lead sources
     */
    public function widget()
    {
        global $wpdb;

        $table = WPGH()->contacts->table_name;
        $start_date = date('Y-m-d H:i:s', $this->start_time);
        $end_date = date('Y-m-d H:i:s', $this->end_time);

        $contacts = $wpdb->get_results("SELECT ID FROM $table WHERE '$start_date' <= date_created AND date_created <= '$end_date'");

        $sources = array();

        foreach ( $contacts as $contact ){

            $utm = array();

            $utm[ 'campaign' ] = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_campaign', true );
            $utm[ 'source' ]   = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_source', true );
            $utm[ 'medium' ]   = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_medium', true );
            $utm[ 'content' ]  = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_content', true );
            $utm[ 'term' ]     = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_term', true );

            $utm_string = implode( '|', $utm );

            if ( empty( $utm_string ) ){

                if ( isset($sources[$utm_string]) ){
                    $sources[$utm_string]++;
                } else {
                    $sources[$utm_string] = 1;
                }

            }

        }

        if ( empty( $sources ) ){
            printf( '<p class="description">%s</p>', _x( 'Nothing new to report.', 'notice', 'groundhogg' ) );
            return;
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        ?>
        <table class="chart-summary">
        <thead>
        <tr>
            <th><?php _ex( 'Campaign','column_title', 'groundhogg' ); ?></th>
            <th><?php _ex( 'Source', 'column_title','groundhogg' ); ?></th>
            <th><?php _ex( 'Medium', 'column_title','groundhogg' ); ?></th>
            <th><?php _ex( 'Content','column_title', 'groundhogg' ); ?></th>
            <th><?php _ex( 'Term', 'column_title','groundhogg' ); ?></th>
            <th><?php _ex( 'Contacts','column_title', 'groundhogg' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php

        foreach ( $sources as $source => $num_contacts ):

            $utm = explode( '|', $source );

            ?>
            <tr>
                <td><?php printf( '%s', $utm[0] ); ?></td>
                <td><?php printf( '%s', $utm[1] ); ?></td>
                <td><?php printf( '%s', $utm[2] ); ?></td>
                <td><?php printf( '%s', $utm[3] ); ?></td>
                <td><?php printf( '%s', $utm[4] ); ?></td>
                <td class="summary-total"><?php printf( '%d', $num_contacts ); ?></td>
            </tr>
        <?php

        endforeach;

        ?>
        </tbody>
        </table>
        <?php

        $this->export_button();
    }

    protected function get_export_data()
    {
        global $wpdb;

        $table = WPGH()->contacts->table_name;
        $start_date = date('Y-m-d H:i:s', $this->start_time);
        $end_date = date('Y-m-d H:i:s', $this->end_time);

        $contacts = $wpdb->get_results("SELECT ID FROM $table WHERE '$start_date' <= date_created AND date_created <= '$end_date'");

        $sources = array();

        foreach ( $contacts as $contact ){

            $utm = array();

            $utm[ 'campaign' ] = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_campaign', true );
            $utm[ 'source' ]   = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_source', true );
            $utm[ 'medium' ]   = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_medium', true );
            $utm[ 'content' ]  = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_content', true );
            $utm[ 'term' ]     = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_term', true );

            $utm_string = implode( '|', $utm );

            if ( empty( $utm_string ) ){

                if ( isset($sources[$utm_string]) ){
                    $sources[$utm_string]++;
                } else {
                    $sources[$utm_string] = 1;
                }

            }

        }

        if ( empty( $sources ) ){
            return _x( 'Nothing new to report.','notice','groundhogg' );
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        $export_info = array();

        foreach ( $sources as $source => $num_contacts ):

            $utm = explode( '|', $source );

            $export_info[] = array(
                _x( 'Campaign', 'column_title','groundhogg' )  => $utm[0],
                _x( 'Source', 'column_title','groundhogg' )    => $utm[1],
                _x( 'Medium', 'column_title','groundhogg' )    => $utm[2],
                _x( 'Content','column_title', 'groundhogg' )   => $utm[3],
                _x( 'Term', 'column_title','groundhogg' )      => $utm[4],
                _x( 'Contacts','column_title', 'groundhogg' )  => $num_contacts,
           );

        endforeach;

        return $export_info;


    }


}