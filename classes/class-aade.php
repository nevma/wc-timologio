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
class Aade {

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
				$('#billing_vat, #contact-nvm-billing_vat').on('focusout', function() {
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

									// $('#contact-nvm-billing_irs').val(response.data.doy);
									// $('#contact-nvm-billing_company').val(response.data.epwnymia);
									// $('#contact-nvm-billing_activity').val(response.data.drastiriotita);

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
	 * Retrieve a specific element's value from an AADE XML response .
	 *
	 * @param string $xmlResponse The XML response as a string .
	 * @param string $elementName The name of the element to retrieve .
	 *
	 * @return string | null The value of the specified element or null if not found .
	 * */
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

