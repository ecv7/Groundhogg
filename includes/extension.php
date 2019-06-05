<?php
namespace Groundhogg;
use Groundhogg\Admin\Admin_Menu;
use Groundhogg\DB\Manager;
use Groundhogg\Reporting\Reports\Report;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Extension
 *
 * Helper class for extensions with Groundhogg.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
abstract class Extension
{

    /**
     * TODO Override this static var in child class.
     * @var Extension
     */
    public static $instance = null;

    /**
     * @var Installer
     */
    public $installer;

    /**
     * @var Updater
     */
    public $updater;

    /**
     * @var Roles
     */
    public $roles;

    /**
     * Keep a going array of all the Extensions.
     *
     * @var Extension[]
     */
    public static $extensions = [];

    /**
     * Extension constructor.
     */
    public function __construct()
    {
        $this->register_autoloader();

        if ( ! did_action( 'groundhogg/init' ) ){
            add_action( 'groundhogg/init', [ $this, 'init' ] );
        } else {
            $this->init();
        }

        // Add to main list
        Extension::$extensions[] = $this;
    }

    /**
     * Register autoloader.
     *
     * Groundhogg autoloader loads all the classes needed to run the plugin.
     *
     * @since 1.6.0
     * @access private
     */
    abstract protected function register_autoloader();

    /**
     * @return Extension[]
     */
    public static function get_extensions()
    {
        return self::$extensions;
    }

    /**
     * Add any other components...
     *
     * @return void
     */
    public function init(){

        $this->includes();

        $this->init_components();

        add_action( 'groundhogg/scripts/after_register_admin_scripts', [ $this, 'register_admin_scripts' ], 10, 2 );
        add_action( 'groundhogg/scripts/after_register_admin_styles', [ $this, 'register_admin_styles' ] );
        add_action( 'groundhogg/scripts/after_register_frontend_scripts', [ $this, 'register_frontend_scripts' ], 10, 2 );
        add_action( 'groundhogg/scripts/after_register_frontend_styles', [ $this, 'register_frontend_styles' ] );

        add_action( 'groundhogg/db/manager/init', [ $this, 'register_dbs'] );
        add_action( 'groundhogg/api/v3/pre_init', [ $this, 'register_apis'] );
        add_action( 'groundhogg/bulk_jobs/init',  [ $this, 'register_bulk_jobs' ] );
        add_action( 'groundhogg/admin/init',      [ $this, 'register_admin_pages'] );
        add_action( 'groundhogg/steps/init',      [ $this, 'register_funnel_steps' ] );
        add_action( 'groundhogg/replacements/init', [ $this, 'add_replacements' ] );

        add_filter( 'groundhogg/reporting/reports',       [ $this, 'register_reports' ] );
        add_filter( 'groundhogg/admin/settings/settings', [ $this, 'register_settings' ] );
        add_filter( 'groundhogg/admin/settings/tabs',     [ $this, 'register_settings_tabs' ] );
        add_filter( 'groundhogg/admin/settings/sections', [ $this, 'register_settings_sections' ] );

        add_filter( 'groundhogg/templates/emails', [ $this, 'register_email_templates' ] );
        add_filter( 'groundhogg/templates/funnels', [ $this, 'register_funnel_templates' ] );
    }

    /**
     * Include any files.
     *
     * @return void
     */
    abstract public function includes();

    /**
     * Init any components that need to be added.
     *
     * @return void
     */
    abstract public function init_components();

    /**
     * @param $is_minified bool
     * @param $IS_MINIFIED string
     */
    public function register_admin_scripts( $is_minified, $IS_MINIFIED ){}

    /**
     * @param $is_minified bool
     * @param $IS_MINIFIED string
     */
    public function register_admin_styles(){}

    /**
     * @param $is_minified bool
     * @param $IS_MINIFIED string
     */
    public function register_frontend_scripts( $is_minified, $IS_MINIFIED ){}

    /**
     * @param $is_minified bool
     * @param $IS_MINIFIED string
     */
    public function register_frontend_styles(){}

    /**
     * @param $templates
     * @return mixed
     */
    public function register_funnel_templates( $templates ){ return $templates; }

    /**
     * @param $templates
     * @return mixed
     */
    public function register_email_templates($templates){return $templates;}

    /**
     * @param $replacements Replacements
     */
    public function add_replacements( $replacements ){}

    /**
     * @param $manager \Groundhogg\Steps\Manager
     */
    public function register_funnel_steps( $manager ){}

    /**
     * @param $manager \Groundhogg\Bulk_Jobs\Manager
     */
    public function register_bulk_jobs( $manager ){}

    /**
     * @param $reports Report[]
     * @return array
     */
    public function register_reports( $reports ){ return $reports; }

    /**
     * Add settings to the settings page
     *
     * @param $settings array[]
     * @return array[]
     */
    public function register_settings( $settings ){ return $settings; }

    /**
     * Add settings sections to the settings page
     *
     * @param $sections array[]
     * @return array[]
     */
    public function register_settings_sections( $sections ){ return $sections; }

    /**
     * Add settings tabs to the settings page
     *
     * @param $tabs array[]
     * @return array[]
     */
    public function register_settings_tabs( $tabs ){ return $tabs; }

    /**
     * Register any proprietary DBS
     *
     * @param $db_manager Manager
     */
    public function register_dbs( $db_manager ){}

    /**
     * Register any api endpoints.
     *
     * @param $api_manager
     * @return void
     */
    public function register_apis( $api_manager ){}

    /**
     * Register any new admin pages.
     *
     * @param $admin_menu Admin_Menu
     * @return void
     */
    public function register_admin_pages($admin_menu ){}

    /**
     * Get the version #
     *
     * @return mixed
     */
    abstract public function get_version();

    /**
     * Get the ID number for the download in EDD Store
     *
     * @return int
     */
    abstract public function get_download_id();

    /**
     * @return string
     */
    abstract public function get_display_name();

    /**
     * @return string
     */
    abstract public function get_display_description();

    /**
     * @return string
     */
    abstract public function get_plugin_file();

    /**
     * Get details...
     *
     * @return array|false
     */
    public function get_extension_details()
    {
        return get_array_var( get_option( 'gh_extensions', [] ), $this->get_download_id(), [] );
    }

    /**
     * Get this extension's license key
     *
     * @return string|false
     */
    public function get_license_key()
    {
        return get_array_var( $this->get_extension_details(), 'license' );
    }

    /**
     * @return bool|string
     */
    public function get_expiry()
    {
        return get_array_var( $this->get_extension_details(), 'expiry' );
    }

    /**
     * Get the EDD updater.
     *
     * @return \GH_EDD_SL_Plugin_Updater
     */
    public function get_edd_updater()
    {
        if ( ! class_exists('\GH_EDD_SL_Plugin_Updater') ){
            require_once dirname(__FILE__) . '/lib/edd/GH_EDD_SL_Plugin_Updater.php';
        }

        return new \GH_EDD_SL_Plugin_Updater( License_Manager::$storeUrl, $this->get_plugin_file(), [
            'version' 	=> $this->get_version(),
            'license' 	=> $this->get_license_key(),
            'item_id'   => $this->get_download_id(),
            'url'       => home_url()
        ] );
    }

    /**
     * Instance.
     *
     * Ensures only one instance of the plugin class is loaded or can be loaded.
     *
     * @since 1.0.0
     * @access public
     * @static
     *
     * @return Extension
     */
    public static function instance() {

        $class = get_called_class();

        if ( is_null( $class::$instance ) ) {

            $class::$instance = new $class();
        }

        return $class::$instance;
    }

    final public function __clone() {
        trigger_error("Singleton. No cloning allowed!", E_USER_ERROR);
    }

    final public function __wakeup() {
        trigger_error("Singleton. No serialization allowed!", E_USER_ERROR);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $content = "<div style='width: 400px;margin:10px 10px 10px 0;display: inline-block;vertical-align: top' class='postbox'>";
        $content.= "<h2 class='hndle'>{$this->get_display_name()}</h2>";
        $content.= "<div class=\"inside\">";
        $content.= "<p>" . $this->get_display_description() . "</p>";

        $content.= html()->input( [
            'placeholder'   => __( 'License', 'groundhogg' ),
            'name'          => "license[{$this->get_download_id()}]",
            'value'         => $this->get_license_key()
        ] );

        if ( $this->get_license_key() ){
            $content .= "<p>";
            $content .= sprintf( __( "Your license expires on %s", 'groundhogg' ), $this->get_expiry() );
            $content .= "</p>";

            $content .= html()->wrap( html()->wrap( __( 'Deactivate', 'groundhogg' ), 'a', [
                'class' => 'button button-secondary',
                'href' => admin_url( wp_nonce_url( add_query_arg( [
                    'action' => 'deactivate_license',
                    'extension' => $this->get_download_id()
                ], 'admin.php?page=gh_settings&tab=extensions' ) ) )
            ] ), 'p' );
        } else {
            $content .= html()->wrap( html()->input([
                'type'  => 'submit',
                'name'  => 'activate_license',
                'class' => 'button button-primary',
                'value' => __( 'Activate', 'groundhogg' ),
            ]), 'p' );
        }

        $content.= "</div>";
        $content.= "</div>";

        return $content;
    }

}