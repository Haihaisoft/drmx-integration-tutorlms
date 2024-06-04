<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.haihaisoft.com
 * @since      1.0.0
 *
 * @package    Drmx_Integration_Tutor_Lms
 * @subpackage Drmx_Integration_Tutor_Lms/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Drmx_Integration_Tutor_Lms
 * @subpackage Drmx_Integration_Tutor_Lms/includes
 * @author     Haihaisoft <service@haihaisoft.com>
 */
class Drmx_Integration_Tutor_Lms_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'drmx-integration-tutor-lms',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
