<?php
/**
 * Customer
 *
 * @package dropp-for-woocommerce
 */

namespace Dropp;

/**
 * Dropp Customer
 */
class Dropp_Customer extends Model {

	public $name;
	public $email_address;
	public $address;
	public $social_security_number;
	public $phone_number;

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * Json decode
	 *
	 * @param  string              $json JSON string.
	 * @return Dropp_Customer|null       Customer object.
	 */
	public static function json_decode( $json ) {
		$data = json_decode( $json, true );

		if ( ! is_array( $data ) || empty( $data ) ) {
			return null;
		}

		return ( new self() )->fill( $data );
	}

	/**
	 * Fill
	 *
	 * @return array             Customer array.
	 */
	public function fill( $data ) {
		$data = wp_parse_args(
			$data,
			[
				'name'                 => '',
				'emailAddress'         => '',
				'socialSecurityNumber' => '1234567890',
				'address'              => '',
				'phoneNumber'          => '',
			]
		);
		$this->name                   = $data['name'];
		$this->email_address          = $data['emailAddress'];
		$this->social_security_number = $data['socialSecurityNumber'];
		$this->address                = $data['address'];
		$this->phone_number           = $data['phoneNumber'];
		return $this;
	}

	/**
	 * To array
	 *
	 * @return array             Customer array.
	 */
	public function to_array() {
		return [
			'name'                 => $this->name,
			'emailAddress'         => $this->email_address,
			'socialSecurityNumber' => $this->social_security_number,
			'address'              => $this->address,
			'phoneNumber'          => $this->phone_number,
		];
	}

	/**
	 * From shipping address
	 *
	 * @param  array $shipping_address Shipping address.
	 * @return array                   Customer array.
	 */
	public static function from_shipping_address( $shipping_address ) {
		$customer = new self();
		$address  = $shipping_address['address_1'];
		if ( $shipping_address['address_2'] ) {
			$address .= ' ' . $shipping_address['address_2'];
		}
		$address .= ', ' . $shipping_address['postcode'];
		$address .= ' ' . $shipping_address['city'];
		$customer->fill( [
			'name'                 => $shipping_address['first_name'] . ' ' . $shipping_address['last_name'],
			'emailAddress'         => $shipping_address['email'],
			// 'socialSecurityNumber' => '1234567890',
			'address'              => $address,
			'phoneNumber'          => $shipping_address['phone'],
		] );
		return $customer;
	}
}
