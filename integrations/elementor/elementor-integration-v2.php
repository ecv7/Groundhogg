<?php
namespace Groundhogg\Integrations\Elementor;

use Elementor\Controls_Manager;
use ElementorPro\Modules\Forms\Classes\Form_Record;
use ElementorPro\Modules\Forms\Classes\Integration_Base;
use function Groundhogg\after_form_submit_handler;
use Groundhogg\Contact;
use function Groundhogg\generate_contact_with_map;
use function Groundhogg\get_db;
use function Groundhogg\get_mappable_fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Elementor_Integration_V2 extends Integration_Base {

    public function get_name() {
		return 'groundhogg_v2';
	}

	public function get_label() {
		return __( 'Groundhogg (v2)', 'elementor-pro' );
	}

	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_groundhogg_v2',
			[
				'label' => __( 'Groundhogg (v2)', 'elementor-pro' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

        $tags = get_db( 'tags' )->query();

        $tag_options = array();

        $default = 0;
        foreach ( $tags as $tag ){
            if ( ! $default ){$default = $tag->tag_id;}
            $tag_options[ $tag->tag_id ] = $tag->tag_name;
        }

        $widget->add_control(
            'groundhogg_v2_tags',
            [
                'label' => __( 'Apply Tags', 'elementor-pro' ),
                'type' => Controls_Manager::SELECT2,
                'options' => $tag_options,
                'multiple' => true,
                'label_block' => false,
            ]
        );

        $widget->add_control(
			'groundhogg_v2_fields_map',
			[
				'label' => __( 'Field Mapping', 'elementor-pro' ),
				'type' => Field_Mapping::CONTROL_TYPE,
				'separator' => 'before',
                'condition' => [
                    'groundhogg_v2_tags!' => '',
                ],
			]
		);

        $widget->end_controls_section();
	}

	public function on_export( $element ) {
		unset(
			$element['settings']['groundhogg_v2_fields_map'],
			$element['settings']['groundhogg_v2_tags']
		);

		return $element;
	}

	/**
	 * @param Form_Record $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {

		$form_settings = $record->get( 'form_settings' );
		$subscriber = $this->create_subscriber_object( $record );

		if ( ! $subscriber ) {
			$ajax_handler->add_admin_error_message( __( 'Groundhogg integration requires an email field.', 'elementor-pro' ) );
			return;
		}

		if ( '' !== $form_settings['groundhogg_v2_tags'] ) {
			$subscriber->apply_tag( wp_parse_id_list( $form_settings['groundhogg_v2_tags'] ) );
		}
	}

	/**
	 * Create subscriber array from submitted data and form settings
	 * returns a subscriber array or false on error
	 *
	 * @param Form_Record $record
	 *
	 * @return Contact|bool
	 */
	private function create_subscriber_object( Form_Record $record ) {

		$map = $this->get_fields_map( $record );

        if ( ! in_array( 'email', $map ) ) {
            return false;
        }

        $fields = $this->get_normalized_fields( $record );
        $contact = generate_contact_with_map( $fields, $map );

        if ( $contact ){
            after_form_submit_handler( $contact );
        }

		return $contact;
	}

	/**
	 * @param Form_Record $record
	 *
	 * @return array
	 */
	private function get_fields_map( Form_Record $record ) {
		$map = [];

		$fields_map = $record->get_form_settings( 'groundhogg_v2_fields_map' );

		foreach ( $fields_map as $map_item ) {

		    if ( ! empty( $map_item[ 'remote_id' ] ) ){
                $map[ $map_item[ 'local_id' ] ] = $map_item[ 'remote_id' ];
            }

        }

		return $map;
	}

	/**
	 * @param Form_Record $record
	 *
	 * @return array
	 */
	private function get_normalized_fields( Form_Record $record )
	{
		$fields = [];
		$raw_fields = $record->get( 'fields' );
		foreach ( $raw_fields as $id => $field ) {

			$fields[ $id ] = $field['value'];
		}

		return $fields;
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function handle_panel_request( array $data ) {

        $tags = get_db( 'tags' )->query();

        $tag_options = array();

        $default = 0;
        foreach ( $tags as $tag ){
            if ( ! $default ){$default = $tag->tag_id;}
            $tag_options[ $tag->tag_id ] = $tag->tag_name;
        }

		$mappable_fields = get_mappable_fields();
		$fields = [];

		foreach ( $mappable_fields as $field_id => $field_label ){
			$fields[] = [
				'remote_id'         => $field_id,
				'remote_label'      => $field_label,
				'remote_type'       => 'text',
				'remote_required'   => in_array( $field_id, [ 'email' ] ),
			];
		}

		$response = [
			'tags'      => $tag_options,
			'fields'    => $fields
		];

		return $response;
	}
}