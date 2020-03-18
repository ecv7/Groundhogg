<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;

class Total_Contacts_In_Funnel extends Base_Quick_Stat {


	protected function get_funnel_id() {
		return absint( get_request_var( 'data' )[ 'funnel_id' ] );
	}

	/**
	 * Query the results
	 *
	 * @param $start int
	 * @param $end   int
	 *
	 * @return mixed
	 */
	protected function query( $start, $end ) {


		$funnel = new Funnel( $this->get_funnel_id() );

		$where_events = [
			'relationship' => "AND",
			[ 'col' => 'step_id', 'val' => $funnel->get_first_step(), 'compare' => '=' ],
			[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
			[ 'col' => 'time', 'val' => $start, 'compare' => '>=' ],
			[ 'col' => 'time', 'val' => $end, 'compare' => '<=' ],
		];

		$num_of_contacts = get_db( 'events' )->count( [
			'where'  => $where_events,
			'select' => 'DISTINCT contact_id'
		] );


		return $num_of_contacts;
	}
}
