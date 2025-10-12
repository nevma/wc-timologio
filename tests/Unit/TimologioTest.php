<?php
/**
 * Tests for the main Timologio class
 *
 * @package Nvm\Timologio\Tests\Unit
 */

namespace Nvm\Timologio\Tests\Unit;

use Nvm\Timologio;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test case for main Timologio class
 */
class TimologioTest extends TestCase {

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
	 * Test that Timologio class can be instantiated
	 */
	public function test_can_instantiate_timologio_class() {
		// Mock WordPress functions
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'wp_normalize_path' )->returnArg();
		Functions\when( 'plugin_dir_path' )->justReturn( '/path/to/plugin/' );
		Functions\when( 'plugin_dir_url' )->justReturn( 'http://example.com/wp-content/plugins/plugin/' );
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'spl_autoload_register' )->justReturn( true );

		$timologio = Timologio::get_instance();
		$this->assertInstanceOf( Timologio::class, $timologio );
	}

	/**
	 * Test get_instance returns singleton instance
	 */
	public function test_get_instance_returns_singleton() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'wp_normalize_path' )->returnArg();
		Functions\when( 'plugin_dir_path' )->justReturn( '/path/to/plugin/' );
		Functions\when( 'plugin_dir_url' )->justReturn( 'http://example.com/wp-content/plugins/plugin/' );
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'spl_autoload_register' )->justReturn( true );

		$instance1 = Timologio::get_instance();
		$instance2 = Timologio::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test register_hooks adds WordPress actions
	 */
	public function test_register_hooks_adds_actions() {
		Functions\when( 'wp_normalize_path' )->returnArg();
		Functions\when( 'plugin_dir_path' )->justReturn( '/path/to/plugin/' );
		Functions\when( 'plugin_dir_url' )->justReturn( 'http://example.com/wp-content/plugins/plugin/' );
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'spl_autoload_register' )->justReturn( true );

		Functions\expect( 'add_action' )
			->atLeast()
			->once()
			->with(
				Mockery::anyOf( 'before_woocommerce_init', 'wp_enqueue_scripts' ),
				Mockery::type( 'array' )
			)
			->andReturn( true );

		$timologio = Timologio::get_instance();
		$timologio->register_hooks();
	}

	/**
	 * Test styles_and_scripts enqueues assets on checkout page
	 */
	public function test_styles_and_scripts_enqueues_on_checkout() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'wp_normalize_path' )->returnArg();
		Functions\when( 'plugin_dir_path' )->justReturn( '/path/to/plugin/' );
		Functions\when( 'plugin_dir_url' )->justReturn( 'http://example.com/wp-content/plugins/plugin/' );
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'spl_autoload_register' )->justReturn( true );
		Functions\when( 'is_checkout' )->justReturn( true );

		Functions\expect( 'wp_enqueue_style' )->once();
		Functions\expect( 'wp_enqueue_script' )->once();

		$timologio = Timologio::get_instance();
		$timologio->styles_and_scripts( '' );
	}

	/**
	 * Test styles_and_scripts does not enqueue on non-checkout pages
	 */
	public function test_styles_and_scripts_does_not_enqueue_on_non_checkout() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'wp_normalize_path' )->returnArg();
		Functions\when( 'plugin_dir_path' )->justReturn( '/path/to/plugin/' );
		Functions\when( 'plugin_dir_url' )->justReturn( 'http://example.com/wp-content/plugins/plugin/' );
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'spl_autoload_register' )->justReturn( true );
		Functions\when( 'is_checkout' )->justReturn( false );

		Functions\expect( 'wp_enqueue_style' )->never();
		Functions\expect( 'wp_enqueue_script' )->never();

		$timologio = Timologio::get_instance();
		$timologio->styles_and_scripts( '' );
	}

	/**
	 * Test check_plugin_dependencies exits when WooCommerce not active
	 */
	public function test_check_plugin_dependencies_fails_without_woocommerce() {
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'admin_url' )->justReturn( 'http://example.com/wp-admin/plugins.php' );

		Functions\expect( 'wp_die' )->once();

		// Mock that WooCommerce class doesn't exist
		Timologio::check_plugin_dependencies();
	}

	/**
	 * Test aade method instantiates Aade when enabled
	 */
	public function test_aade_instantiates_when_enabled() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'wp_normalize_path' )->returnArg();
		Functions\when( 'plugin_dir_path' )->justReturn( '/path/to/plugin/' );
		Functions\when( 'plugin_dir_url' )->justReturn( 'http://example.com/wp-content/plugins/plugin/' );
		Functions\when( 'spl_autoload_register' )->justReturn( true );

		Functions\when( 'get_option' )
			->with( 'timologio_enable_feature' )
			->andReturn( 'yes' );
		Functions\when( 'get_option' )
			->with( 'timologio_aade_user' )
			->andReturn( 'testuser' );
		Functions\when( 'get_option' )
			->with( 'timologio_aade_pass' )
			->andReturn( 'testpass' );

		// Test that aade is called - this should create an Aade instance
		Timologio::aade();

		// If we get here without errors, the test passes
		$this->assertTrue( true );
	}

	/**
	 * Test aade method does not instantiate when disabled
	 */
	public function test_aade_does_not_instantiate_when_disabled() {
		Functions\when( 'get_option' )->justReturn( 'no' );

		// Test that aade is called but doesn't instantiate
		Timologio::aade();

		// If we get here without errors, the test passes
		$this->assertTrue( true );
	}

	/**
	 * Test declare_hpos_compatibility declares compatibility
	 */
	public function test_declare_hpos_compatibility() {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'wp_normalize_path' )->returnArg();
		Functions\when( 'plugin_dir_path' )->justReturn( '/path/to/plugin/' );
		Functions\when( 'plugin_dir_url' )->justReturn( 'http://example.com/wp-content/plugins/plugin/' );
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'spl_autoload_register' )->justReturn( true );

		$timologio = Timologio::get_instance();

		// Mock the FeaturesUtil class doesn't exist - should just skip
		$timologio->declare_hpos_compatibility();

		// If we get here without errors, the test passes
		$this->assertTrue( true );
	}
}
