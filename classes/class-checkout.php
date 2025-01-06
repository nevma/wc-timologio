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
 * Class Checkout
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
	 */
	public function __construct() {
		$this->required_timologio_fields = array(
			'billing_vat_id'     => __( 'ΑΦΜ', 'nevma' ),
			'billing_tax_office' => __( 'ΔΟΥ', 'nevma' ),
			'billing_company'    => __( 'Επωνυμία εταιρίας', 'nevma' ),
			'billing_activity'   => __( 'Δραστηριότητα', 'nevma' ),

		);

		$this->register_hooks();
	}

	/**
	 * Register hooks and filters.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'template_redirect', array( $this, 'initiate_checkout_actions' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'show_timologio_fields' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_timologio_data' ) );
	}

	/**
	 * Initialize actions for the checkout page.
	 *
	 * @return void
	 */
	public function initiate_checkout_actions() {
		add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'add_timologio_apodeixi' ), 30 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'customize_checkout_fields' ) );
		add_filter( 'woocommerce_coupons_enabled', array( $this, 'disable_coupon_field_in_cart' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_timologio_fields' ) );
	}

	public function display_price_history_metabox() {

		echo '<h3>' . __( 'Price History', 'nvm-product-price-history-inline' ) . '</h3>';
		global $post;
		$product       = wc_get_product( $post->ID );
		$price_history = $product->get_meta( '_nvm_price_history' );

		if ( ! is_array( $price_history ) || empty( $price_history ) ) {
			echo '<p>' . __( 'No price changes recorded.', 'nvm-product-price-history-inline' ) . '</p>';
			return;
		}

		echo '<ul>';
		foreach ( array_reverse( $price_history ) as $entry ) {
			echo '<li>';
			echo esc_html( date( 'd/m/Y H:i', strtotime( $entry['date'] ) ) );
			echo ' - ' . '<strong>' . wc_price( $entry['sale_price'] ) . '</strong>';
			echo '</li>';
		}
		echo '</ul>';
	}



	/**
	 * Customize checkout fields.
	 *
	 * @param array $fields The existing fields.
	 *
	 * @return array
	 */
	public function customize_checkout_fields( $fields ) {

		$chosen = WC()->session->get( 'timologio' );
		$chosen = empty( $chosen ) ? WC()->checkout->get_value( 'delivery' ) : $chosen;
		$chosen = empty( $chosen ) ? '0' : $chosen;

		$radio = woocommerce_form_field(
			'delivery',
			array(
				'type'    => 'radio',
				'class'   => array( 'form-row-wide' ),
				'options' => array(
					self::TYPE_APODEIXI  => __( 'Απόδειξη', 'nevma' ),
					self::TYPE_TIMOLOGIO => __( 'Τιμολόγιο', 'nevma' ),
				),

			),
			$chosen
		);

		// $fields['billing'] = array_merge( $radio, $fields['billing'] );

		// $new_fields = array(
		// 'billing_vat_id'      => $this->get_field_config( __( 'ΑΦΜ', 'nevma' ), array( 'form-row-first' ) ),
		// 'billing_tax_office'  => $this->get_field_config( __( 'ΔΟΥ', 'nevma' ), array( 'form-row-last' ) ),
		// 'billing_company_nvm' => $this->get_field_config( __( 'Επωνυμία Εταιρίας', 'nevma' ), array( 'form-row-first' ) ),
		// 'billing_activity'    => $this->get_field_config( __( 'Δραστηριότητα', 'nevma' ), array( 'form-row-last' ) ),
		// );

		// $fields['billing'] = array_merge( $new_fields, $fields['billing'] );

		return $fields;
	}

	/**
	 * Get field configuration for billing fields.
	 *
	 * @param string $label The field label.
	 *
	 * @return array
	 */
	private function get_field_config( $label, $css_class = array() ) {
		$pre_class = array( 'form-row', 'timologio' );

		if ( ! empty( $css_class ) ) {
			$pre_class = array_merge( $css_class, $pre_class );
		}

		return array(
			'label'    => $label,
			'required' => false,
			'type'     => 'text',
			'class'    => $pre_class,
		);
	}


	/**
	 * Add radio buttons for order type selection.
	 *
	 * @return void
	 */
	public function add_timologio_apodeixi() {
		?>
		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function () {
				const orderTypeRadios = document.querySelectorAll('input[name="<?php echo esc_js( self::FIELD_TYPE_ORDER ); ?>"]');

				function updateDisplay() {
					const selectedValue = document.querySelector('input[name="<?php echo esc_js( self::FIELD_TYPE_ORDER ); ?>"]:checked').value;
					document.querySelectorAll('.timologio').forEach(el => el.style.display = (selectedValue === '<?php echo esc_js( self::TYPE_TIMOLOGIO ); ?>') ? 'block' : 'none');
				}

				updateDisplay();
				orderTypeRadios.forEach(radio => radio.addEventListener('change', updateDisplay));
			});
		</script>

		<style>
			.woocommerce form .form-row label, .woocommerce-page form .form-row label {
				display: inline;
			}
		</style>
		<?php
	}

	/**
	 * Disable coupon functionality in the cart.
	 *
	 * @return bool
	 */
	public function disable_coupon_field_in_cart() {
		return false;
	}

	/**
	 * Validate required fields for timologio.
	 *
	 * @return void
	 */
	public function validate_timologio_fields() {
		if ( isset( $_POST[ self::FIELD_TYPE_ORDER ] ) && self::TYPE_TIMOLOGIO === $_POST[ self::FIELD_TYPE_ORDER ] ) {
			foreach ( $this->required_timologio_fields as $field => $label ) {
				if ( empty( $_POST[ $field ] ) ) {
					wc_add_notice( sprintf( __( 'Please fill in the %s field.', 'nevma' ), esc_html( $label ) ), 'error' );
				}
			}
		}
	}

	/**
	 * Save timologio data to the order.
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return void
	 */
	public function save_timologio_data( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order ) {
			$fields_to_save = array(
				'_billing_company' => 'billing_company_nvm',
				'_type_of_order'   => self::FIELD_TYPE_ORDER,
			);

			foreach ( $fields_to_save as $meta_key => $post_key ) {
				if ( isset( $_POST[ $post_key ] ) ) {
					$order->update_meta_data( $meta_key, sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) );
				}
			}

			$order->save();
		}
	}

	/**
	 * Show timologio fields in the admin order view.
	 *
	 * @param \WC_Order $order The order object.
	 *
	 * @return void
	 */
	public function show_timologio_fields( $order ) {
		$fields_to_display = array(
			'_billing_vat_id'   => __( 'AFM', 'nevma' ),
			'_billing_activity' => __( 'Activity', 'nevma' ),
			'_billing_company'  => __( 'Company Name', 'nevma' ),
		);

		foreach ( $fields_to_display as $meta_key => $label ) {
			$value = $order->get_meta( $meta_key );

			if ( ! empty( $value ) ) {
				printf( '<p><strong>%s:</strong> %s</p>', esc_html( $label ), esc_html( $value ) );
			}
		}
	}
}