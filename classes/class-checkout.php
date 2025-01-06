<?php //phpcs:ignore - \r\n issue

/**
 * Set namespace.
 */
namespace Nvm\Timologio;

/**
 * Check that the file is not accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/**
 * Class Admin_Menu.
 */
class Checkout {

	// Constants
	const FIELD_TYPE_ORDER = 'type_of_order';
	const TYPE_TIMOLOGIO   = 'timologio';
	const TYPE_APODEIXI    = 'apodeixi';

	/**
	 * Required fields for timologio (invoice) form.
	 *
	 * @var array
	 */
	private $required_timologio_fields;

	/**
	 * Constructor.
	 *
	 * Initializes the checkout functionality by registering necessary hooks.
	 */
	public function __construct() {
		$this->required_timologio_fields = array(
			'billing_vat_id'      => __( 'ΑΦΜ', 'nevma' ),
			'billing_activity'    => __( 'Δραστηριότητα', 'nevma' ),
			'billing_company_nvm' => __( 'Επωνυμία εταιρίας', 'nevma' ),
		);

		$this->register_hooks();
	}

	/**
	 * Register hooks and filters for the checkout functionality.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'template_redirect', array( $this, 'initiate_checkout_actions' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'show_timologio_fields' ), 10, 1 );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_timologio_data' ) );

		add_action( 'woocommerce_init', array( $this, 'block_add_timologio_fields' ) );
	}


	public function style_checkout() {
		?>

		<style>
			.woocommerce form .form-row label {
				line-height: 2;
				position: absolute;
				font-size: 14px;
				color: #2b2d2f;
				padding-left: 10px;
				padding-top: 5px;
			}
		</style>
		<?php
	}
	/**
	 * Initialize actions for the checkout page.
	 */
	public function initiate_checkout_actions() {

		// if ( is_checkout() && $this->initiate_redirect_template() ) {
			// Add timologio
			add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'add_timologio_apodeixi' ) );
			add_filter( 'woocommerce_checkout_fields', array( $this, 'custom_woocommerce_billing_fields' ), 30 );

			add_filter( 'woocommerce_checkout_fields', array( $this, 'customize_checkout_fields' ), 20 );

			// remove Coupon if had donor product
			add_filter( 'woocommerce_coupons_enabled', array( $this, 'remove_coupon_code_field_cart' ), 10 );

			add_action( 'woocommerce_checkout_process', array( $this, 'timologio_process' ) );

		// }
	}

	/**
	 * Disable coupon functionality in cart.
	 *
	 * @return bool
	 */
	public function remove_coupon_code_field_cart() {
		return false;
	}

	public function customize_checkout_fields( $fields ) {

		unset( $fields['billing']['billing_company'] );
		unset( $fields['billing']['billing_address_2'] );
		unset( $fields['billing']['billing_state'] );

		return $fields;
	}

	/**
	 * Add radio buttons for selecting between 'Timologio' and 'Apodeixi'.
	 *
	 * @return void
	 */
	public function add_timologio_apodeixi() {
		?>
	<div class="choose-timologio">
		<input type="radio" id="apodeiksi" name="type_of_order" value="apodeiksi" checked> Απόδειξη
		<input type="radio" id="timologio" name="type_of_order" value="timologio"> Τιμολόγιο
	</div>

	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function () {
			const orderTypeRadios = document.querySelectorAll('input[name="type_of_order"]');

			function updateDisplay() {
				const selectedValue = document.querySelector('input[name="type_of_order"]:checked').value;
				const displayStyle = selectedValue === 'timologio' ? 'block' : 'none';
				document.querySelectorAll('.timologio').forEach(el => el.style.display = displayStyle);
			}

			// Check initially before any clicks
			updateDisplay();

			// Update display on radio button change
			orderTypeRadios.forEach(radio => radio.addEventListener('click', updateDisplay));
		});
	</script>
		<?php
	}


	/**
	 * Filter the cart template path to use cart.php in this plugin instead of the one in WooCommerce.
	 *
	 * @param string $template      Default template file path.
	 * @param string $template_name Template file slug.
	 * @param string $template_path Template file name.
	 *
	 * @return string The new Template file path.
	 */
	function intercept_wc_template( $template, $template_name, $template_path ) {

		$plugin_path = plugin_dir_path( __DIR__ );

		if ( 'form-checkout.php' === basename( $template ) ) {
			$template = trailingslashit( $plugin_path ) . 'woocommerce/checkout/form-checkout.php';
		} elseif ( 'payment.php' === basename( $template ) ) {
			$template = trailingslashit( $plugin_path ) . 'woocommerce/checkout/payment.php';
		} elseif ( 'review-order.php' === basename( $template ) ) {
			$template = trailingslashit( $plugin_path ) . 'woocommerce/checkout/review-order.php';
		}

		return $template;
	}

	/**
	 * Customizes WooCommerce billing fields.
	 *
	 * @param array $fields The existing billing fields.
	 * @return array Modified billing fields.
	 */
	public function custom_woocommerce_billing_fields( $fields ) {

		// Define new fields.
		$new_fields = array(
			'billing_vat_id'      => array(
				'label'    => __( 'ΑΦΜ', 'nevma' ),
				'required' => false,
				'clear'    => false,
				'type'     => 'text',
				'class'    => array( 'donation-section', 'form-row-first', 'timologio' ),
			),
			'billing_tax_office'  => array(
				'label'    => __( 'ΔΟΥ', 'nevma' ),
				'required' => false,
				'clear'    => false,
				'type'     => 'text',
				'class'    => array( 'donation-section', 'form-row-last', 'timologio' ),
			),
			'billing_company_nvm' => array(
				'label'    => __( 'Επωνυμία Εταιρίας', 'nevma' ),
				'required' => false,
				'clear'    => false,
				'type'     => 'text',
				'class'    => array( 'donation-section', 'form-row-first', 'timologio' ),
			),
			'billing_activity'    => array(
				'label'    => __( 'Δραστηριότητα', 'nevma' ),
				'required' => false,
				'clear'    => false,
				'type'     => 'text',
				'class'    => array( 'donation-section', 'form-row-last', 'timologio' ),
			),
		);

		// Merge new fields with existing billing fields.
		$fields['billing'] = array_merge( $new_fields, $fields['billing'] );

		return $fields;
	}

	public function timologio_process() {
		if ( $_POST[ self::FIELD_TYPE_ORDER ] !== self::TYPE_TIMOLOGIO ) {
			return;
		}

		foreach ( $this->required_timologio_fields as $field => $label ) {
			if ( empty( $_POST[ $field ] ) ) {
				wc_add_notice( sprintf( __( 'Συμπληρώστε το πεδίο %s', 'nevma' ), $label ), 'error' );
			}
		}
	}

	public function save_timologio_data( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$fields_to_save = array(
			'_billing_company' => 'billing_company_nvm',
			'_type_of_order'   => self::FIELD_TYPE_ORDER,
		);

		foreach ( $fields_to_save as $meta_key => $post_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				$order->update_meta_data( $meta_key, sanitize_text_field( $_POST[ $post_key ] ) );
			}
		}

		$order->save();
	}


	public function show_timologio_fields( $order ) {
		if ( $order->get_meta( '_billing_vat_id' ) != '' ) {
			echo '<p><strong>' . esc_html__( 'AFM:', 'nevma' ) . '</strong> ' . esc_html( $order->get_meta( '_billing_vat_id' ) ) . '</p>';
			echo '<p><strong>' . esc_html__( 'Δραστηριότητα:', 'nevma' ) . '</strong> ' . esc_html( $order->get_meta( '_billing_activity' ) ) . '</p>';
			echo '<p><strong>' . esc_html__( 'Επωνυμία Εταιρίας:', 'nevma' ) . '</strong> ' . esc_html( $order->get_meta( '_billing_company' ) ) . '</p>';
		}
	}

	public function initiate_redirect_template() {

		$nvm_product = new Nvm_Product();

		$has_donor_product = false;
		$target_product_id = $nvm_product->get_donor_product();

		foreach ( \WC()->cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['data'] ) && $cart_item['data']->get_id() === $target_product_id ) {
				$has_donor_product = true;
				break;
			}
		}

		// If the cart contains the "donor" product, remove specific billing fields
		if ( $has_donor_product ) {
			return true;
		}
		return false;
	}



	public function block_add_timologio_fields() {
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'namespace/nvm-timologio',
				'label'    => 'Τιμολόγιο',
				'location' => 'address',
				'required' => false,
				'type'     => 'checkbox',
				'required' => false,
				'priority' => 1,
			),
		);
	}
}
