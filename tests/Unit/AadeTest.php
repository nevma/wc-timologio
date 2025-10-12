<?php
/**
 * Tests for the Aade class
 *
 * @package Nvm\Timologio\Tests\Unit
 */

namespace Nvm\Timologio\Tests\Unit;

use Nvm\Timologio\Aade;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test case for Aade class
 */
class AadeTest extends TestCase {

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
	 * Test that Aade class can be instantiated
	 */
	public function test_can_instantiate_aade_class() {
		// Mock WordPress functions
		Functions\when( 'add_action' )->justReturn( true );

		$aade = new Aade();
		$this->assertInstanceOf( Aade::class, $aade );
	}

	/**
	 * Test is_block_based_checkout returns false when not on checkout page
	 */
	public function test_is_block_based_checkout_returns_false_when_not_checkout() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'is_checkout' )->justReturn( false );
		Functions\expect( 'has_block' )->never();

		$aade   = new Aade();
		$result = $aade->is_block_based_checkout();

		$this->assertFalse( $result );
	}

	/**
	 * Test is_block_based_checkout returns true when on block checkout
	 */
	public function test_is_block_based_checkout_returns_true_when_block_checkout() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'is_checkout' )->justReturn( true );
		Functions\when( 'has_block' )->justReturn( true );

		$aade   = new Aade();
		$result = $aade->is_block_based_checkout();

		$this->assertTrue( $result );
	}

	/**
	 * Test get_aade_element extracts correct value from XML
	 */
	public function test_get_aade_element_extracts_correct_value() {
		Functions\when( 'add_action' )->justReturn( true );

		$xml_response = '<?xml version="1.0" encoding="UTF-8"?>
		<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope">
			<env:Body>
				<ns:rgWsPublic2AfmMethodResponse xmlns:ns="http://rgwspublic2/RgWsPublic2">
					<ns:result>
						<ns:basic_rec>
							<ns:doy_descr>DOY EXAMPLE</ns:doy_descr>
							<ns:onomasia>Test Company Name</ns:onomasia>
							<ns:postal_address>Test Street</ns:postal_address>
							<ns:postal_zip_code>12345</ns:postal_zip_code>
						</ns:basic_rec>
					</ns:result>
				</ns:rgWsPublic2AfmMethodResponse>
			</env:Body>
		</env:Envelope>';

		$aade   = new Aade();
		$result = $aade->get_aade_element( $xml_response, 'onomasia' );

		$this->assertEquals( 'Test Company Name', $result );
	}

	/**
	 * Test get_aade_element returns null when element not found
	 */
	public function test_get_aade_element_returns_null_when_not_found() {
		Functions\when( 'add_action' )->justReturn( true );

		$xml_response = '<?xml version="1.0" encoding="UTF-8"?>
		<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope">
			<env:Body>
				<ns:rgWsPublic2AfmMethodResponse xmlns:ns="http://rgwspublic2/RgWsPublic2">
					<ns:result>
						<ns:basic_rec>
							<ns:onomasia>Test Company</ns:onomasia>
						</ns:basic_rec>
					</ns:result>
				</ns:rgWsPublic2AfmMethodResponse>
			</env:Body>
		</env:Envelope>';

		$aade   = new Aade();
		$result = $aade->get_aade_element( $xml_response, 'nonexistent_field' );

		$this->assertNull( $result );
	}

	/**
	 * Test get_aade_firm_act_descr extracts activity descriptions
	 */
	public function test_get_aade_firm_act_descr_extracts_activities() {
		Functions\when( 'add_action' )->justReturn( true );

		$xml_response = '<?xml version="1.0" encoding="UTF-8"?>
		<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope">
			<env:Body>
				<ns:rgWsPublic2AfmMethodResponse xmlns:ns="http://rgwspublic2/RgWsPublic2">
					<ns:result>
						<ns:firm_act_tab>
							<ns:item>
								<ns:firm_act_descr>Activity 1</ns:firm_act_descr>
							</ns:item>
							<ns:item>
								<ns:firm_act_descr>Activity 2</ns:firm_act_descr>
							</ns:item>
						</ns:firm_act_tab>
					</ns:result>
				</ns:rgWsPublic2AfmMethodResponse>
			</env:Body>
		</env:Envelope>';

		$aade   = new Aade();
		$result = $aade->get_aade_firm_act_descr( $xml_response );

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
		$this->assertEquals( 'Activity 1', $result[0] );
		$this->assertEquals( 'Activity 2', $result[1] );
	}

	/**
	 * Test get_aade_firm_act_descr returns null when no activities found
	 */
	public function test_get_aade_firm_act_descr_returns_null_when_empty() {
		Functions\when( 'add_action' )->justReturn( true );

		$xml_response = '<?xml version="1.0" encoding="UTF-8"?>
		<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope">
			<env:Body>
				<ns:rgWsPublic2AfmMethodResponse xmlns:ns="http://rgwspublic2/RgWsPublic2">
					<ns:result>
					</ns:result>
				</ns:rgWsPublic2AfmMethodResponse>
			</env:Body>
		</env:Envelope>';

		$aade   = new Aade();
		$result = $aade->get_aade_firm_act_descr( $xml_response );

		$this->assertNull( $result );
	}

	/**
	 * Test check_for_valid_vat_aade returns cached result when available
	 */
	public function test_check_for_valid_vat_aade_returns_cached_result() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( 'test_value' );
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'get_transient' )->justReturn( '<cached>response</cached>' );
		Functions\expect( 'wp_remote_post' )->never();

		$aade   = new Aade();
		$result = $aade->check_for_valid_vat_aade( '123456789' );

		$this->assertEquals( '<cached>response</cached>', $result );
	}

	/**
	 * Test check_for_valid_vat_aade makes remote request when no cache
	 */
	public function test_check_for_valid_vat_aade_makes_remote_request() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( 'test_value' );
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( '<soap>response</soap>' );
		Functions\when( 'set_transient' )->justReturn( true );

		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn( array( 'body' => '<soap>response</soap>' ) );

		$aade   = new Aade();
		$result = $aade->check_for_valid_vat_aade( '123456789' );

		$this->assertIsString( $result );
	}

	/**
	 * Test check_for_valid_vat_aade returns false on error
	 */
	public function test_check_for_valid_vat_aade_returns_false_on_error() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( 'test_value' );
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'is_wp_error' )->justReturn( true );

		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn( new \WP_Error( 'error', 'Connection failed' ) );

		$aade   = new Aade();
		$result = $aade->check_for_valid_vat_aade( '123456789' );

		$this->assertFalse( $result );
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

		$aade = new Aade();
		$aade->fetch_vat_details();
	}

	/**
	 * Test register_hooks adds required WordPress actions
	 */
	public function test_register_hooks_adds_actions() {
		Functions\expect( 'add_action' )
			->times( 5 )
			->with(
				Mockery::anyOf( 'wp_head', 'wp_enqueue_scripts', 'enqueue_block_assets', 'wp_ajax_fetch_vat_details', 'wp_ajax_nopriv_fetch_vat_details' ),
				Mockery::type( 'array' )
			)
			->andReturn( true );

		$aade = new Aade();
		$aade->register_hooks();
	}
}
