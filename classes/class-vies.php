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
 * Class adae
 */
class Vies {

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

			add_action( 'wp_head', array( $this, 'vat_number_script' ) );
			add_action( 'wp_ajax_fetch_vat_details', array( $this, 'fetch_vat_details' ) );
			add_action( 'wp_ajax_nopriv_fetch_vat_details', array( $this, 'fetch_vat_details' ) );
	}

	public function vat_number_script() {
		$ajax_nonce = wp_create_nonce( 'nvm_secure_nonce' );
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#billing_vat').on('blur', function() {
					var vatNumber = $(this).val();

					if (vatNumber) {
						$.ajax({
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
							type: 'POST',
							data: {
								action: 'fetch_vat_details',
								vat_number: vatNumber,
								security: '<?php echo esc_js( $ajax_nonce ); ?>'
							},
							success: function(response) {
								if (response.success) {
									$('#billing_irs').val(response.data.doy);
									$('#billing_company').val(response.data.epwnymia);
									$('#billing_activity').val(response.data.drastiriotita);
									$('#billing_address_1').val(response.data.address);
									$('#billing_country').val(response.data.country);
									$('#billing_city').val(response.data.city);
									$('#billing_postcode').val(response.data.postcode);

									console.log(response.data);

								} else {
									alert('Invalid VAT number or unable to fetch details.');
								}
							}
						});
					}
				});
			});
		</script>
		<?php
	}

	function fetch_vat_details() {

		check_ajax_referer( 'nvm_secure_nonce', 'security' );

		if ( isset( $_POST['vat_number'] ) ) {
			$vat_number  = sanitize_text_field( $_POST['vat_number'] );
			$vat_number  = str_replace( 'EL', '', $vat_number );
			$xmlResponse = $this->check_for_valid_vat_aade( $vat_number );

			$flag = $this->get_aade_element( $xmlResponse, 'deactivation_flag' );

			if ( ! empty( $flag ) ) {
				wp_send_json_success(
					array(
						'doy'           => $this->get_aade_element( $xmlResponse, 'doy_descr' ),
						'epwnymia'      => $this->get_aade_element( $xmlResponse, 'onomasia' ),
						'drastiriotita' => $this->get_aade_firm_act_descr( $xmlResponse ),
						'address'       => $this->get_aade_element( $xmlResponse, 'postal_address' ) . ' ' . $this->get_aade_element( $xmlResponse, 'postal_address_no' ),
						'country'       => 'GR',
						'city'          => $this->get_aade_element( $xmlResponse, 'postal_area_description' ),
						'postcode'      => $this->get_aade_element( $xmlResponse, 'postal_zip_code' ),

					)
				);
			} else {
				wp_send_json_error( 'VAT number not valid.' );
			}
		} else {
			wp_send_json_error( 'VAT number not provided.' );
		}

		wp_die();
	}

	/**
	 * Validate VAT number and get additional details using VIES.
	 *
	 * @param string $vat_number The VAT number to validate.
	 * @param string $country_code The two-letter country code (e.g., "DE" for Germany).
	 * @return array|string An array with VAT details if valid, False if invalid, or an error message.
	 */
	function nvm_get_vat_details_vies( $vat_number, $country_code ) {
		if ( empty( $vat_number ) || empty( $country_code ) ) {
			return 'Country code and VAT number are required.';
		}

		try {
			// Initialize the SOAP client
			$client = new SoapClient( 'https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl' );

			// Perform the VAT check
			$response = $client->checkVat(
				array(
					'countryCode' => strtoupper( $country_code ),
					'vatNumber'   => $vat_number,
				)
			);

			// Check if the VAT number is valid
			if ( $response->valid ) {
				return array(
					'valid'       => true,
					'countryCode' => $response->countryCode,
					'vatNumber'   => $response->vatNumber,
					'name'        => $response->name,
					'address'     => $response->address,
				);
			} else {
				return false; // VAT number is invalid
			}
		} catch ( Exception $e ) {
			return 'Error: ' . $e->getMessage();
		}
	}
}

