<?php
/**
 * EU VIES VAT validation and WooCommerce integration.
 *
 * @package Nvm\Timologio
 */

namespace Nvm\Timologio;

use Exception;
use WC_Order;

/**
 * Handles EU VAT validation through VIES.
 *
 * - AJAX: validates and returns business details for autofill.
 * - Server: revalidates on checkout, stores meta, and controls VAT exemption.
 */
class Vies {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->nvm_register_hooks();
	}

	/**
	 * Register hooks and filters.
	 *
	 * @return void
	 */
	public function nvm_register_hooks() {
		// Front-end JS.
		add_action( 'wp_enqueue_scripts', array( $this, 'nvm_enqueue_vat_script' ) );

		// AJAX endpoints.
		add_action( 'wp_ajax_nvm_fetch_vat_details', array( $this, 'nvm_fetch_vat_details' ) );
		add_action( 'wp_ajax_nopriv_nvm_fetch_vat_details', array( $this, 'nvm_fetch_vat_details' ) );
	}

	/**
	 * Enqueue and localize the small VAT JS.
	 *
	 * @return void
	 */
	public function nvm_enqueue_vat_script() {
		if ( function_exists( 'is_checkout' ) && ! is_checkout() ) {
			return;
		}

		wp_enqueue_script( 'jquery' );

		$handle      = 'nvm-vat-vies';
		$inline_js   = "
			jQuery(function($){
				var \$vat = $('#billing_vat');
				if (!\$vat.length) { return; }

				var timer = null;
				var last  = '';

				function nvmMaybeCheck(){
					var vat = String(\$vat.val() || '').trim();
					if (!vat || vat === last) { return; }
					last = vat;

					var country = String($('#billing_country').val() || '').trim();

					$.ajax({
						url: " . wp_json_encode( admin_url( 'admin-ajax.php' ) ) . ",
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'nvm_fetch_vat_details',
							security: " . wp_json_encode( wp_create_nonce( 'nvm_secure_nonce' ) ) . ",
							vat_number: vat,
							billing_country: country
						}
					}).done(function(resp){
						if (resp && resp.success && resp.data && resp.data.valid) {
							var d = resp.data;

							// Fill common Woo fields if empty (don't clobber user input).
							if (d.name && !$('#billing_company').val()) { $('#billing_company').val(d.name); }
							if (d.address && !$('#billing_address_1').val()) { $('#billing_address_1').val(d.address_line1 || d.address); }
							if (d.city && !$('#billing_city').val()) { $('#billing_city').val(d.city); }
							if (d.postcode && !$('#billing_postcode').val()) { $('#billing_postcode').val(d.postcode); }

							// Ensure country (don't force-change user's selection if already set).
							if (d.countryCode && !$('#billing_country').val()) {
								$('#billing_country').val(d.countryCode).trigger('change');
							}

							// Surface a small notice in console for debugging.
							if (window.console) { console.log('[VIES] Valid VAT:', d); }
						} else {
							// Optionally show a non-blocking message.
							if (window.console) { console.warn('[VIES] Invalid VAT or unavailable'); }
						}
					});
				}

				// Validate after blur or when user stops typing.
				\$vat.on('blur', nvmMaybeCheck);
				\$vat.on('input', function(){
					clearTimeout(timer);
					timer = setTimeout(nvmMaybeCheck, 700);
				});
			});
		";
		wp_register_script( $handle, false, array( 'jquery' ), '1.0.0', true );
		wp_add_inline_script( $handle, $inline_js );
		wp_enqueue_script( $handle );
	}

	/**
	 * AJAX: Validate and fetch VAT details from VIES.
	 *
	 * @return void
	 */
	public function nvm_fetch_vat_details() {
		check_ajax_referer( 'nvm_secure_nonce', 'security' );

		$raw_vat   = isset( $_POST['vat_number'] ) ? sanitize_text_field( wp_unslash( $_POST['vat_number'] ) ) : '';
		$fallback  = isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_country'] ) ) : '';

		if ( empty( $raw_vat ) ) {
			wp_send_json_error( array( 'message' => __( 'VAT number not provided.', 'nvm' ) ) );
		}

		$parsed = $this->nvm_parse_vat_input( $raw_vat, $fallback );

		if ( empty( $parsed['countryCode'] ) || empty( $parsed['vatNumber'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not detect country code or VAT number.', 'nvm' ) ) );
		}

		$result = $this->nvm_get_vat_details_vies( $parsed['vatNumber'], $parsed['countryCode'] );

		if ( is_array( $result ) && ! empty( $result['valid'] ) ) {
			// Try to split the multi-line VIES address for better autofill.
			$address_lines = $this->nvm_split_vies_address( $result['address'] );

			wp_send_json_success(
				array(
					'valid'       => true,
					'countryCode' => $result['countryCode'],
					'vatNumber'   => $result['vatNumber'],
					'name'        => $result['name'],
					'address'     => $result['address'],
					'address_line1' => $address_lines['line1'],
					'city'        => $address_lines['city'],
					'postcode'    => $address_lines['postcode'],
					'raw'         => $result,
				)
			);
		}

		// If string, it's an error message from SOAP; if false, it's invalid VAT.
		$message = is_string( $result ) ? $result : __( 'VAT number not valid.', 'nvm' );
		wp_send_json_error( array( 'message' => $message ) );
	}

	/* =========================
	 * Helpers
	 * ========================= */

	/**
	 * Parse user VAT input and detect country prefix.
	 *
	 * @param string $raw_vat Raw string (may contain spaces/dashes/prefix).
	 * @param string $fallback_country Two-letter fallback country (e.g. 'DE').
	 * @return array{countryCode:string,vatNumber:string}
	 */
	public function nvm_parse_vat_input( $raw_vat, $fallback_country = '' ) {
		$vat = strtoupper( preg_replace( '/[^A-Z0-9]/', '', (string) $raw_vat ) );

		$country = '';
		$number  = $vat;

		if ( preg_match( '/^[A-Z]{2}/', $vat ) ) {
			$country = substr( $vat, 0, 2 );
			$number  = substr( $vat, 2 );
		} else {
			$country = strtoupper( substr( preg_replace( '/[^A-Z]/', '', (string) $fallback_country ), 0, 2 ) );
		}

		// Normalize Greece (Woo uses GR in many places; VIES expects EL).
		if ( 'GR' === $country ) {
			$country = 'EL';
		}

		return array(
			'countryCode' => $country,
			'vatNumber'   => $number,
		);
	}

	/**
	 * Validate VAT number and get additional details using VIES.
	 *
	 * @param string $vat_number   The VAT number (no prefix).
	 * @param string $country_code Two-letter country code (VIES format, e.g. 'DE', 'EL').
	 * @return array|false|string  Array on success, false if invalid, or string error message.
	 */
	public function nvm_get_vat_details_vies( $vat_number, $country_code ) {
		if ( empty( $vat_number ) || empty( $country_code ) ) {
			return __( 'Country code and VAT number are required.', 'nvm' );
		}

		try {
			$client = new \SoapClient( 'https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl', array(
				'exceptions' => true,
				'connection_timeout' => 6,
				'cache_wsdl' => WSDL_CACHE_BOTH,
			) );

			$response = $client->checkVat(
				array(
					'countryCode' => strtoupper( $country_code ),
					'vatNumber'   => preg_replace( '/[^0-9A-Z]/', '', (string) $vat_number ),
				)
			);

			if ( ! empty( $response->valid ) ) {
				return array(
					'valid'       => true,
					'countryCode' => (string) $response->countryCode,
					'vatNumber'   => (string) $response->vatNumber,
					'name'        => (string) $response->name,
					'address'     => (string) $response->address,
				);
			}

			return false;
		} catch ( \SoapFault $sf ) {
			// Common transient errors from VIES.
			return sprintf(
				/* translators: %s is SOAP fault message */
				__( 'VIES temporary error: %s', 'nvm' ),
				$sf->getMessage()
			);
		} catch ( Exception $e ) {
			return 'Error: ' . $e->getMessage();
		}
	}

	/**
	 * Split VIES multi-line address into line1/city/postcode best-effort.
	 *
	 * @param string $address Multiline address (lines separated by \n).
	 * @return array{line1:string,city:string,postcode:string}
	 */
	public function nvm_split_vies_address( $address ) {
		$line1   = '';
		$city    = '';
		$post    = '';

		$parts = preg_split( "/\r\n|\n|\r/", (string) $address );
		$parts = array_values( array_filter( array_map( 'trim', $parts ) ) );

		if ( isset( $parts[0] ) ) {
			$line1 = $parts[0];
		}

		// Attempt to find a line with postcode + city (e.g. "12345 Berlin").
		foreach ( array_reverse( $parts ) as $ln ) {
			if ( preg_match( '/^([A-Z]{0,3}\s?\d{3,5})\s+(.+)$/u', $ln, $m ) ) {
				$post = trim( $m[1] );
				$city = trim( $m[2] );
				break;
			}
		}

		return array(
			'line1'   => $line1,
			'city'    => $city,
			'postcode'=> $post,
		);
	}
}