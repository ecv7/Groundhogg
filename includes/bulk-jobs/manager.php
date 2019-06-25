<?php
namespace Groundhogg\Bulk_Jobs;

use function Groundhogg\get_array_var;
use function Groundhogg\isset_not_empty;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-15
 * Time: 3:24 PM
 */

class Manager
{

    protected $jobs = [];

    /**
     * Manager constructor.
     */
    public function __construct()
    {
        $this->init_jobs();
    }

    public function init_jobs()
    {
        $this->broadcast_scheduler  = new Broadcast_Scheduler();
        $this->delete_contacts      = new Delete_Contacts();
        $this->export_contacts      = new Export_Contacts();
        $this->import_contacts      = new Import_Contacts();
        $this->sync_contacts        = new Sync_Contacts();

        do_action( 'groundhogg/bulk_jobs/init', $this );
    }


    /**
     * Magic get method
     *
     * @param $key string
     * @return bool|Bulk_Job
     */
    public function __get( $key )
    {
        return get_array_var( $this->jobs, $key, false );
    }

    /**
     * Set the data to the given value
     *
     * @param $key string
     * @param $value Bulk_Job
     */
    public function __set( $key, $value )
    {
        $this->jobs[ $key ] = $value;
    }


}