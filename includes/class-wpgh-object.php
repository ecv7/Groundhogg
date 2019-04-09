<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-09
 * Time: 9:18 AM
 */

abstract class WPGH_Object
{

    /**
     * The ID of the object
     *
     * @var int
     */
    public $ID = 0;

    /**
     * The regular data
     *
     * @var array
     */
    protected $data = [];

    /**
     * The meta data
     *
     * @var array
     */
    protected $meta = [];

    /**
     * @var WPGH_DB
     */
    protected $db;

    /**
     * @var WPGH_DB
     */
    protected $meta_db;


    public function __construct($ID)
    {
        $this->ID = intval($ID);
        $object = $this->get_from_db();

        if (!$object)
            return false;

        $this->setup_object($object);
    }

    /**
     * Setup the class
     *
     * @param $object
     * @return bool
     */
    protected function setup_object($object)
    {
        if (!is_object($object)) {
            return false;
        }

        //Lets just make sure we all good here.
        $object = apply_filters( "groundhogg/{$this->get_object_type()}/setup", $object );

        // Setup the main data
        foreach ($object as $key => $value) {
            $this->$key = $value;
        }

        // Get all the meta data.
        $this->meta = $this->get_all_meta();

        $this->post_setup();

        return true;

    }

    /**
     * Do any post setup actions.
     *
     * @return void
     */
    abstract protected function post_setup();

    /**
     * Set an object property.
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }

        $this->data[$name] = $value;
    }

    /**
     * Get an object property
     *
     * @param $name
     * @return bool|mixed
     */
    public function __get($name)
    {
        // Check main data first
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        // Check data array
        if (key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        // Check meta data
        if (key_exists($name, $this->meta)) {
            return $this->meta[$name];
        }

        return false;
    }

    /**
     * Checks if the data from the DB checks out.
     *
     * @return bool
     */
    public function exists()
    {
        $data = implode( '', $this->data );
        return ! empty( $data );
    }

    /**
     * Get the object from the associated db.
     *
     * @return object
     */
    protected function get_from_db(){
        return $this->get_db()->get( $this->ID );
    }

    /**
     * Return the DB instance that is associated with items of this type.
     *
     * @return WPGH_DB
     */
    abstract protected function get_db();


    /**
     * Return a META DB instance associated with items of this type.
     *
     * @return WPGH_DB
     */
    abstract protected function get_meta_db();

    /**
     * A string to represent the object type
     *
     * @return string
     */
    abstract protected function get_object_type();

    /**
     * Update the object
     *
     * @param array $data
     * @return bool
     */
    public function update( $data = [] )
    {
        if ( empty( $data ) ) {
            return false;
        }

        $data = $this->sanitize_columns( $data );

        do_action( "groundhogg/{$this->get_object_type()}/pre_update", $this->ID, $data, $this );

        if ( $updated = $this->get_db()->update( $this->ID, $data, 'ID' ) ) {
            $contact = $this->get_from_db();
            $this->setup_object( $contact );
        }

        do_action( "groundhogg/{$this->get_object_type()}/post_update", $this->ID, $data, $this );

        return $updated;
    }

    /**
     * Sanitize columns when updating the object
     *
     * @param array $data
     * @return array
     */
    protected function sanitize_columns( $data=[] )
    {
        return $data;
    }

    /**
     * Get all the meta data.
     *
     * @return array
     */
    public function get_all_meta()
    {
        if ( ! empty( $this->meta ) ){
            return $this->meta;
        }

        $meta = $this->get_meta_db()->get_meta( $this->ID );

        foreach ( $meta as $meta_key => $array_values ){
            $this->meta[ $meta_key ] = maybe_unserialize( array_shift( $array_values ) );
        }

        return $this->meta;
    }

    /**
     * Get some meta
     *
     * @param $key
     * @return mixed
     */
    public function get_meta( $key )
    {

        if ( key_exists( $key, $this->meta ) ){
            return $this->meta[ $key ];
        }

        $val = $this->get_meta_db()->get_meta( $this->ID, $key, true );

        $this->meta[ $key ] = $val;

        return $val;
    }

    /**
     * Update some meta data
     *
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public function update_meta( $key, $value )
    {
        if ( $this->get_meta_db()->update_meta( $this->ID, $key, $value ) ){
            $this->meta[ $key ] = $value;

            return true;
        }

        return false;
    }

    /**
     * Add some meta
     *
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public function add_meta( $key, $value )
    {
        if ( $this->get_meta_db()->add_meta( $this->ID, $key, $value ) ){
            $this->meta[ $key ] = $value;

            return true;
        }

        return false;
    }


    /**
     * Delete some meta
     *
     * @param $key
     * @return mixed
     */
    public function delete_meta( $key )
    {
        unset( $this->meta[$key] );
        return $this->get_meta_db()->delete_meta( $this->ID, $key );
    }
}