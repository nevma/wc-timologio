<?php

/**
 * Set namespace.
 */
namespace Nvm\Timologio;

use Nvm\Timologio as Nvm_Timologio;


/**
 * Prevent direct access to the file.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you cannot directly access this file.' );
}

/**
 * Handles AADE (Greek Tax Authority) VAT validation and data retrieval.
 *
 * This class provides functionality to validate Greek VAT numbers against
 * the AADE SOAP API and automatically populate company information including
 * tax office, company name, business activity, and address details.
 *
 * @package Nvm\Timologio
 * @since 1.0.0
 */
class Aade {

	/**
	 * Constructor.
	 *
	 * Initializes the AADE class and registers WordPress hooks.
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

			add_action( 'wp_head', array( $this, 'classic_vat_number_script' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_scripts' ) );
			add_action( 'wp_ajax_fetch_vat_details', array( $this, 'fetch_vat_details' ) );
			add_action( 'wp_ajax_nopriv_fetch_vat_details', array( $this, 'fetch_vat_details' ) );
	}

	/**
	 * Checks if the current checkout page is using WooCommerce blocks.
	 *
	 * @return bool True if block-based checkout, false otherwise.
	 */
	public function is_block_based_checkout() {
		// Early return if not on checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return false;
		}

		// Check if using WooCommerce block checkout
		if ( function_exists( 'has_block' ) ) {
			global $post;

			// Try to get the checkout page ID
			$checkout_page_id = wc_get_page_id( 'checkout' );

			if ( $checkout_page_id && $checkout_page_id > 0 ) {
				return has_block( 'woocommerce/checkout', $checkout_page_id );
			}

			// Fallback: check current post
			if ( $post instanceof \WP_Post ) {
				return has_block( 'woocommerce/checkout', $post );
			}
		}

		return false;
	}

	/**
	 * Enqueues scripts for checkout pages (both classic and block-based).
	 *
	 * @return void
	 */
	public function enqueue_checkout_scripts() {
		// Only load on checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		// Check if using block-based checkout
		if ( $this->is_block_based_checkout() ) {
			$timologio = new Nvm_Timologio();

			wp_enqueue_script(
				'nvm-checkout-interactivity',
				$timologio::$plugin_url . 'js/nvm-checkout-interactivity.js',
				array( 'wp-interactivity' ),
				$timologio::$plugin_version,
				true
			);

			// Localize script for AJAX
			wp_localize_script(
				'nvm-checkout-interactivity',
				'nvmCheckoutData',
				array(
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'ajax_nonce' => wp_create_nonce( 'nvm_secure_nonce' ),
				)
			);
		}
	}



	public function classic_vat_number_script() {
		if ( ! $this->is_block_based_checkout() ) {

			$ajax_nonce = wp_create_nonce( 'nvm_secure_nonce', 'security' );
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('#billing_vat').on('focusout', function() {
						var vatNumber = $(this).val();

						var numericVat = vatNumber.replace(/\D/g, '');
						if (numericVat.length < 7) {
							return; // Stop the execution if the condition is not met
						}

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
	}

	/**
	 * AJAX handler to fetch VAT details from AADE.
	 *
	 * Validates the provided VAT number against AADE and returns
	 * company information including tax office, name, activity, and address.
	 *
	 * @return void Sends JSON response and terminates.
	 */
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
	 * Extracts firm activity descriptions from AADE XML response.
	 *
	 * Parses the XML response and retrieves all business activity descriptions
	 * associated with the validated VAT number.
	 *
	 * @param string $xmlResponse The XML response from AADE API.
	 * @return array|null Array of activity descriptions or null if not found.
	 */
	function get_aade_firm_act_descr( $xmlResponse ) {

		// Load the XML string into a SimpleXMLElement object
		$xml = new \SimpleXMLElement( $xmlResponse );

		$xml->registerXPathNamespace( 'env', 'http://www.w3.org/2003/05/soap-envelope' );
		$xml->registerXPathNamespace( 'xsd', 'http://www.w3.org/2001/XMLSchema' );
		$xml->registerXPathNamespace( 'xsi', 'http://www.w3.org/2001/XMLSchema-instance' );
		$xml->registerXPathNamespace( 'ns', 'http://rgwspublic2/RgWsPublic2' );

		// Construct the XPath query to find all 'firm_act_descr' elements within 'firm_act_tab'
		$xpathQuery = '//ns:firm_act_tab/ns:item/ns:firm_act_descr';

		// Navigate to the elements using XPath
		$xml_elements = $xml->xpath( $xpathQuery );

		// Check if elements are found and return them as an array
		if ( ! empty( $xml_elements ) ) {
			$descriptions = array();
			foreach ( $xml_elements as $element ) {
				$descriptions[] = (string) $element;
			}
			return $descriptions;
		}

		return null;
	}

	/**
	 * Retrieve a specific element's value from an AADE XML response.
	 *
	 * @param string $xmlResponse The XML response as a string.
	 * @param string $elementName The name of the element to retrieve.
	 * @return string|null The value of the specified element or null if not found.
	 */
	public function get_aade_element( $xmlResponse, $elementName ) {

		// Load the XML string into a SimpleXMLElement object
		$xml = new \SimpleXMLElement( $xmlResponse );

		$xml->registerXPathNamespace( 'env', 'http://www.w3.org/2003/05/soap-envelope' );
		$xml->registerXPathNamespace( 'xsd', 'http://www.w3.org/2001/XMLSchema' );
		$xml->registerXPathNamespace( 'xsi', 'http://www.w3.org/2001/XMLSchema-instance' );
		$xml->registerXPathNamespace( 'ns', 'http://rgwspublic2/RgWsPublic2' );

		// Construct the XPath query to find the specified element within the nested structure
		$xpathQuery = sprintf( '//ns:basic_rec/ns:%s', $elementName );

		// Navigate to the element using XPath
		$xml_elements = $xml->xpath( $xpathQuery );

		// Navigate to the element using XPath
		$xml_elements = $xml->xpath( $xpathQuery );

		if ( ! empty( $xml_elements ) ) {
			return (string) $xml_elements[0];
		}

		return null;
	}

	/**
	 * Validates a Greek VAT number against the AADE API.
	 *
	 * Performs a SOAP request to the Greek tax authority (AADE) to validate
	 * a VAT number and retrieve company details. Results are cached for 1 hour.
	 *
	 * @param string      $vat_id The VAT number to validate.
	 * @param string|null $country Optional country code (not used for AADE).
	 * @return string|false XML response from AADE or false on error.
	 */
	public function check_for_valid_vat_aade( $vat_id, $country = null ) {
		$transient_key = "{$vat_id}_nvm_aade_check";
		$result        = delete_transient( $transient_key );
		$result        = get_transient( $transient_key );
		$username      = get_option( 'timologio_aade_user' );
		$password      = get_option( 'timologio_aade_pass' );

		if ( false === $result ) {
			$url      = 'https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2?WSDL';
			$envelope = <<<XML
			<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:ns1="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:ns2="http://rgwspublic2/RgWsPublic2Service" xmlns:ns3="http://rgwspublic2/RgWsPublic2">
				<env:Header>
					<ns1:Security>
						<ns1:UsernameToken>
							<ns1:Username>{$username}</ns1:Username>
							<ns1:Password>{$password}</ns1:Password>
						</ns1:UsernameToken>
					</ns1:Security>
				</env:Header>
				<env:Body>
					<ns2:rgWsPublic2AfmMethod>
						<ns2:INPUT_REC>
							<ns3:afm_called_by/>
							<ns3:afm_called_for>{$vat_id}</ns3:afm_called_for>
						</ns2:INPUT_REC>
					</ns2:rgWsPublic2AfmMethod>
				</env:Body>
			</env:Envelope>
			XML;

			$response = wp_remote_post(
				$url,
				array(
					'body'    => $envelope,
					'headers' => array(
						'Content-Type'   => '',
						'Content-Length' => strlen( $envelope ),
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$body = wp_remote_retrieve_body( $response );

			$cleaned_response = preg_replace( '/(<\s*)\w+:/', '$1', $body );
			$cleaned_response = preg_replace( '/(<\/\s*)\w+:/', '$1', $cleaned_response );

			$result = $cleaned_response;

			set_transient( $transient_key, $result, 1 * HOUR_IN_SECONDS );
		}

			return $result;
	}
}

