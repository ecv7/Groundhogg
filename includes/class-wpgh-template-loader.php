<?php

/**
 * Template loader for WPGH
 *
 * Only need to specify class properties here.
 *
 */

if ( ! class_exists( 'Gamajo_Template_Loader' ) ){
    require_once dirname( __FILE__ ) . '/lib/class-template-loader.php';
}

class WPGH_Template_Loader extends Gamajo_Template_Loader {

    /**
     * Prefix for filter names.
     *
     * @since 1.0.0
     * @type string
     */
    protected $filter_prefix = 'wpgh';

    /**
     * Directory name where custom templates for this plugin should be found in the theme.
     *
     * @since 1.0.0
     * @type string
     */
    protected $theme_template_directory = 'wpgh-templates';

    /**
     * Reference to the root directory path of this plugin.
     *
     * @since 1.0.0
     * @type string
     */
    protected $plugin_directory = WPGH_PLUGIN_DIR;

}