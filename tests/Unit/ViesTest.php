<?php
/**
 * Tests for the Vies class
 *
 * @package Nvm\Timologio\Tests\Unit
 */

namespace Nvm\Timologio\Tests\Unit;

use Nvm\Timologio\Vies;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test case for Vies class
 */
class ViesTest extends TestCase {

	/**
	 * Set up the test environment before each test
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down the test environment after each test
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test that Vies class can be instantiated
	 */
	public function test_can_instantiate_vies_class() {
		// Mock WordPress functions
		Functions\when( 'add_action' )->justReturn( true );

		$vies = new Vies();
		$this->assertInstanceOf( Vies::class, $vies );
	}

	/**
	 * Test register_hooks adds required WordPress actions
	 */
	public function test_register_hooks_adds_actions() {
		Functions\expect( 'add_action' )
			->times( 3 )
			->with(
				Mockery::anyOf( 'wp_head', 'wp_ajax_fetch_vat_details', 'wp_ajax_nopriv_fetch_vat_details' ),
				Mockery::type( 'array' )
			)
			->andReturn( true );

		$vies = new Vies();
		$vies->register_hooks();
	}

	/**
	 * Test nvm_get_vat_details_vies returns error when country code is empty
	 */
	public function test_nvm_get_vat_details_vies_returns_error_when_country_code_empty() {
		Functions\when( 'add_action' )->justReturn( true );

		$vies   = new Vies();
		$result = $vies->nvm_get_vat_details_vies( '123456789', '' );

		$this->assertEquals( 'Country code and VAT number are required.', $result );
	}

	/**
	 * Test nvm_get_vat_details_vies returns error when VAT number is empty
	 */
	public function test_nvm_get_vat_details_vies_returns_error_when_vat_number_empty() {
		Functions\when( 'add_action' )->justReturn( true );

		$vies   = new Vies();
		$result = $vies->nvm_get_vat_details_vies( '', 'DE' );

		$this->assertEquals( 'Country code and VAT number are required.', $result );
	}

	/**
	 * Test fetch_vat_details sends error when VAT number not provided
	 */
	public function test_fetch_vat_details_error_when_vat_not_provided() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'check_ajax_referer' )->justReturn( true );
		Functions\expect( 'wp_send_json_error' )
			->once()
			->with( 'VAT number not provided.' );
		Functions\expect( 'wp_die' )->once();

		$_POST = array();

		$vies = new Vies();
		$vies->fetch_vat_details();
	}

	/**
	 * Test fetch_vat_details sanitizes VAT number input
	 */
	public function test_fetch_vat_details_sanitizes_vat_number() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'check_ajax_referer' )->justReturn( true );
		Functions\when( 'sanitize_text_field' )->justReturn( '123456789' );

		// Mock the check_for_valid_vat_aade method to return empty XML
		$xml_response = '<?xml version="1.0" encoding="UTF-8"?>
		<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope">
			<env:Body>
			</env:Body>
		</env:Envelope>';

		Functions\expect( 'wp_send_json_error' )->once();
		Functions\expect( 'wp_die' )->once();

		$_POST['vat_number'] = 'EL123456789';

		$vies = Mockery::mock( Vies::class )->makePartial();
		$vies->shouldReceive( 'check_for_valid_vat_aade' )->andReturn( $xml_response );
		$vies->shouldReceive( 'get_aade_element' )->andReturn( null );

		$vies->fetch_vat_details();
	}

	/**
	 * Test fetch_vat_details removes EL prefix from VAT number
	 */
	public function test_fetch_vat_details_removes_el_prefix() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'check_ajax_referer' )->justReturn( true );

		$original_vat = 'EL123456789';
		$expected_vat = '123456789';

		Functions\when( 'sanitize_text_field' )->justReturn( $original_vat );

		$xml_response = '<?xml version="1.0" encoding="UTF-8"?>
		<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope">
			<env:Body>
			</env:Body>
		</env:Envelope>';

		Functions\expect( 'wp_send_json_error' )->once();
		Functions\expect( 'wp_die' )->once();

		$_POST['vat_number'] = $original_vat;

		$vies = Mockery::mock( Vies::class )->makePartial();
		$vies->shouldReceive( 'check_for_valid_vat_aade' )
			->once()
			->with( $expected_vat )
			->andReturn( $xml_response );
		$vies->shouldReceive( 'get_aade_element' )->andReturn( null );

		$vies->fetch_vat_details();
	}

	/**
	 * Test fetch_vat_details sends success with valid data
	 */
	public function test_fetch_vat_details_sends_success_with_valid_data() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'check_ajax_referer' )->justReturn( true );
		Functions\when( 'sanitize_text_field' )->justReturn( '123456789' );

		$xml_response = '<?xml version="1.0" encoding="UTF-8"?>
		<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope">
			<env:Body>
			</env:Body>
		</env:Envelope>';

		Functions\expect( 'wp_send_json_success' )
			->once()
			->with( Mockery::type( 'array' ) );
		Functions\expect( 'wp_die' )->once();

		$_POST['vat_number'] = '123456789';

		$vies = Mockery::mock( Vies::class )->makePartial();
		$vies->shouldReceive( 'check_for_valid_vat_aade' )->andReturn( $xml_response );
		$vies->shouldReceive( 'get_aade_element' )
			->with( $xml_response, 'deactivation_flag' )
			->andReturn( '1' );
		$vies->shouldReceive( 'get_aade_element' )->andReturn( 'Test Value' );
		$vies->shouldReceive( 'get_aade_firm_act_descr' )->andReturn( array( 'Activity' ) );

		$vies->fetch_vat_details();
	}

	/**
	 * Test vat_number_script outputs inline JavaScript
	 */
	public function test_vat_number_script_outputs_javascript() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'wp_create_nonce' )->justReturn( 'test_nonce' );
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'admin_url' )->justReturn( 'http://example.com/wp-admin/admin-ajax.php' );
		Functions\when( 'esc_js' )->returnArg();

		$vies = new Vies();

		ob_start();
		$vies->vat_number_script();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<script', $output );
		$this->assertStringContainsString( 'billing_vat', $output );
		$this->assertStringContainsString( 'fetch_vat_details', $output );
		$this->assertStringContainsString( 'jQuery', $output );
	}
}
