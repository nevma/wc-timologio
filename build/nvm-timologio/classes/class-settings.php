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
 * Manages plugin settings and WooCommerce integration.
 *
 * @package Nvm\Timologio
 * @since 1.0.0
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
	 * @return array
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

		// Inline admin script to toggle AADE fields when the source changes.
		// Keeps logic close to where the fields render.
		?>
		<script type="text/javascript">
			( function( $ ) {
				function nvm_toggle_timologio_fields() {
					var source = $( '#timologio_source' ).val();

					// Field row IDs come from option 'id' values.
					var $aadeRows = $(
						'#timologio_aade_title,' +
						'#timologio_aade_user,' +
						'#timologio_aade_pass'
					);

					if ( 'aade' === source ) {
						$aadeRows.show();
					} else {
						$aadeRows.hide();
					}
				}

				$( document ).on( 'change', '#timologio_source', nvm_toggle_timologio_fields );
				$( document ).ready( nvm_toggle_timologio_fields );
			} )( jQuery );
		</script>
		<style>
			/* Optional: smoothens hide/show to avoid layout jump. */
			#timologio_aade_title,
			#timologio_aade_user,
			#timologio_aade_pass { transition: all .12s ease-in-out; }
		</style>
		<?php
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
				'title' => __( 'Timologio Settings', 'nevma' ),
				'type'  => 'title',
				'id'    => 'timologio_settings',
			),

			// Single select to choose the data source.
			array(
				'title'   => __( 'Autocomplete Source', 'nevma' ),
				'desc'    => __( 'Choose where to fetch company info from.', 'nevma' ),
				'id'      => 'timologio_source',
				'type'    => 'select',
				'default' => 'vies',
				'options' => array(
					'vies' => __( 'VIES (EU VAT)', 'nevma' ),
					'aade' => __( 'AADE (Greece)', 'nevma' ),
				),
			),

			// --- AADE-only fields (hidden unless source === 'aade') ---

			array(
				// An extra "title" row makes the group clearer; we toggle it as well.
				'title' => __( 'AADE Settings', 'nevma' ),
				'type'  => 'title',
				'id'    => 'timologio_aade_title',
			),

			array(
				'title'   => __( 'AADE Username', 'nevma' ),
				'desc'    => __( 'Enter your AADE username.', 'nevma' ),
				'id'      => 'timologio_aade_user',
				'type'    => 'text',
				'default' => '',
			),

			array(
				'title'   => __( 'AADE Password', 'nevma' ),
				'desc'    => __( 'Enter your AADE password.', 'nevma' ),
				'id'      => 'timologio_aade_pass',
				'type'    => 'password',
				'default' => '',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'timologio_settings',
			),
		);
	}
}