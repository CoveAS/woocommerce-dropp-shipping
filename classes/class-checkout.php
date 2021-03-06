<?php
/**
 * Ajax
 *
 * @package dropp-for-woocommerce
 */

namespace Dropp;

/**
 * Checkout
 */
class Checkout {
	/**
	 * Setup
	 */
	public static function setup() {
		add_action( 'wp_enqueue_scripts', __CLASS__ . '::checkout_javascript' );
		add_action( 'woocommerce_checkout_order_processed', __CLASS__ . '::tag_order', 10, 3 );
		add_action( 'dropp_schedule_add_new', 'Dropp\Checkout::add_new', 10, 0 );
	}

	/**
	 * Tag order for processing
	 *
	 * @param integer  $order_id    Order ID.
	 * @param array    $posted_data POST data.
	 * @param WC_Order $order Order Order.
	 */
	public static function tag_order( $order_id, $posted_data, $order ) {
		$adapter = new Order_Adapter(
			$order
		);
		if ( ! $adapter->is_dropp() ) {
			return;
		}

		// Tag the order and schedule a new event.
		$order->update_meta_data( '_dropp_added', 0 );
		$order->save();

		if ( ! wp_next_scheduled( 'dropp_schedule_add_new' ) ) {
			// Schedule adding new items.
			wp_schedule_single_event( time() + 5, 'dropp_schedule_add_new' );
		}
	}

	/**
	 * Add new
	 */
	public static function add_new() {
		global $wpdb;

		// API request to add new.
		$post_ids = $wpdb->get_col(
			"SELECT ID FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_ID
			AND pm.meta_key = \"_dropp_added\"
			AND pm.meta_value = \"0\""
		);

		foreach ( $post_ids as $post_ID ) {
			$order   = wc_get_order( $post_ID );
			$adapter = new Order_Adapter( $order );

			// @TODO: Consider logging events where $adapter->add_new() returns false.
			$adapter->add_new();

			$order->update_meta_data( '_dropp_added', '1' );
			$order->save();
		}
	}

	/**
	 * Load checkout javascript
	 */
	public static function checkout_javascript() {
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			// Add styles.
			wp_register_style(
				'dropp-for-woocommerce',
				plugins_url( 'assets/css/dropp.css', __DIR__ ),
				[],
				Dropp::VERSION
			);
			wp_enqueue_style( 'dropp-for-woocommerce' );

			// Add javascript.
			wp_register_script(
				'dropp-for-woocommerce',
				plugins_url( 'assets/js/dropp.js', __DIR__ ),
				array( 'jquery' ),
				Dropp::VERSION,
				true
			);
			wp_enqueue_script( 'dropp-for-woocommerce' );

			$shipping_method = new Shipping_Method\Dropp();
			// Add javascript variables.
			wp_localize_script(
				'dropp-for-woocommerce',
				'_dropp',
				[
					'ajaxurl'           => admin_url( 'admin-ajax.php' ),
					'storeid'           => $shipping_method->store_id,
					'dropplocationsurl' => 'https://app.dropp.is/dropp-locations.min.js',
					'i18n'              => [
						'error_loading' => esc_html__( 'Could not load the location selector. Someone from the store will contact you regarding the delivery location.', 'dropp-for-woocommerce' ),
					],
				]
			);
		}
	}
}
