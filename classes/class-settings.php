<?php

/**
 * Set namespace.
 */
namespace Nvm\Timologio;

/**
 * Prevent direct access to the file.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you cannot directly access this file.' );
}

/**
 * Class Settings
 */
class Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register hooks and filters.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_timologio', array( $this, 'output_settings' ) );
		add_action( 'woocommerce_update_options_timologio', array( $this, 'save_settings' ) );
	}

	/**
	 * Add a custom settings tab to WooCommerce settings.
	 *
	 * @param array $tabs Array of existing WooCommerce setting tabs.
	 * @return array Modified array of WooCommerce setting tabs.
	 */
	public function add_settings_tab( $tabs ) {
		$tabs['timologio'] = __( 'Nevma Settings', 'nevma' );
		return $tabs;
	}

	/**
	 * Output the settings for the custom tab.
	 *
	 * @return void
	 */
	public function output_settings() {
		woocommerce_admin_fields( $this->get_settings() );
	}

	/**
	 * Save the settings for the custom tab.
	 *
	 * @return void
	 */
	public function save_settings() {
		woocommerce_update_options( $this->get_settings() );
	}

	/**
	 * Define the settings for the custom tab.
	 *
	 * @return array
	 */
	private function get_settings() {
		return array(
			array(
				'title' => __( 'Timologio Settings - Aade', 'nevma' ),
				'type'  => 'title',
				'id'    => 'timologio_settings',
			),
			array(
				'title'   => __( 'Enable autocomplete from Aade', 'nevma' ),
				'desc'    => __( 'Check this box to enable the feature.', 'nevma' ),
				'id'      => 'timologio_enable_feature',
				'type'    => 'checkbox', // Defines the field as a checkbox.
				'default' => 'no', // Default value: 'yes' or 'no'.
			),
			array(
				'title'   => __( 'Username Aade', 'nevma' ),
				'desc'    => __( 'Please enter the username', 'nevma' ),
				'id'      => 'timologio_aade_user',
				'type'    => 'text',
				'default' => '',
			),
			array(
				'title'   => __( 'Password Aade', 'nevma' ),
				'desc'    => __( 'Please enter the password', 'nevma' ),
				'id'      => 'timologio_aade_pass',
				'type'    => 'text',
				'default' => '',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'timologio_settings',
			),
		);
	}
}
