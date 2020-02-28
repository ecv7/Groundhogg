<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Plugin;
use function Groundhogg\isset_not_empty;

abstract class Base_Time_Chart_Report extends Base_Line_Chart_Report {

	/**
	 * @return string[]
	 */
	protected function get_labels() {
		return [];
	}

	protected function get_options() {
		return [
			'responsive' => true,
			'scales'     => [
				'xAxes' => [
					0 => [
						'type'       => 'time',
						'time'       => [
							'parser'        => "YYY-MM-DD HH:mm:ss",
							'tooltipFormat' => "l HH:mm"
						],
						'scaleLabel' => [
							'display'     => true,
							'labelString' => 'Date',
						]
					]
				],
				'yAxes' => [
					0 => [
						'scaleLabel' => [
							'display'     => true,
							'labelString' => 'Numbers',
						]
					]
				]
			]
		];
	}

	abstract function get_time_from_datum( $datum );

	/**
	 * Get the difference in time between points given a time range...
	 *
	 * @param $range
	 *
	 * @return int
	 */
	protected function get_time_diff( $range ) {

		if ( $range <= DAY_IN_SECONDS ) {
			return HOUR_IN_SECONDS;
		} else if ( $range <= WEEK_IN_SECONDS ) {
			return DAY_IN_SECONDS;
		} else if ( $range <= MONTH_IN_SECONDS ) {
			return WEEK_IN_SECONDS;
		} else if ( $range <= 2 * YEAR_IN_SECONDS ) {
			return MONTH_IN_SECONDS;
		}

		return YEAR_IN_SECONDS;
	}


	/**
	 * Get the time slots for the given time range...
	 *
	 * @return array
	 */
	public function get_date_points( $previous ) {

		$values = $this->get_values($previous);
		$points = $values [ 'points' ];
		$start  = $values[ 'start'];
		$diff = $values ['difference'];

		for ( $i = 0; $i < $points; $i ++ ) {
			$start                 = Plugin::$instance->utils->date_time->round_to( $start, $diff );
			$date_points[ $start ] = [ $start * 1000, 0, date( 'Y-m-d H:i:s', $start ) ];
			$start                 += $diff;
		}

		return $date_points;

	}


	protected function get_values( $previous = false  ) {

		if ($previous) {
			$start = Plugin::$instance->utils->date_time->round_to_day( $this->compare_start );
			$end   = Plugin::$instance->utils->date_time->round_to_day( $this->compare_end + DAY_IN_SECONDS - 1 );
			$range = $end - $start;

		}else {

			$start = Plugin::$instance->utils->date_time->round_to_day( $this->start );
			$end   = Plugin::$instance->utils->date_time->round_to_day( $this->end + DAY_IN_SECONDS - 1 );
			$range = $end - $start;
		}


		return [
			'start'      => $start,
			'end'        => $end,
			'range'      => $range,
			'points'     => ceil( $range / $this->get_time_diff( $range ) ),
			'difference' => $this->get_time_diff( $range ),
		];
	}


	/**
	 * Group the given data into their respective time slots...
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public function group_by_time( $data , $previous  =  false  ) {

		$values = $this->get_values( $previous);

		$times = $this->get_date_points( $previous );


		foreach ( $data as $datum ) {
			$date_point = Plugin::$instance->utils->date_time->round_to(
				$this->get_time_from_datum( $datum ),
				$values [ 'difference' ],
				false
			);


			if ( isset_not_empty( $times, $date_point ) ) {
				$times[ $date_point ][ 1 ] ++;
			}
		}

		return array_values( $times );
	}

}
