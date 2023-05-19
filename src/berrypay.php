<?php

/**
 * BerryPay Payment Gateway Class
 */
class berrypay extends WC_Payment_Gateway {
	function __construct() {
		$this->id = "BerryPay";

		$this->method_title = __( "BerryPay", 'BerryPay' );

		$this->method_description = __( "BerryPay Payment Gateway Plug-in for WooCommerce", 'BerryPay' );

		$this->title = __( "BerryPay", 'BerryPay' );

		$this->environment_mode = 'live';

		if ($this->environment_mode == 'sandbox') 
			$this->icon = 'https://securepay.berrypay.com/assets/img/woocommerce-berrypay.png';
		else
			$this->icon = 'https://securepay.berrypay.com/assets/img/woocommerce-berrypay.png';

		$this->has_fields = true;

		$this->init_form_fields();

		$this->init_settings();

		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}

		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );
		}
	}

	# Build the administration fields for this specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'        => array(
				'title'   => __( 'Enable / Disable', 'BerryPay' ),
				'label'   => __( 'Enable this payment gateway', 'BerryPay' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			'title'          => array(
				'title'    => __( 'Title', 'BerryPay' ),
				'type'     => 'text',
				'desc_tip' => __( 'Payment title the customer will see during the checkout process.', 'BerryPay' ),
				'default'  => __( 'BerryPay', 'BerryPay' ),
			),
			'store'          => array(
				'title'    => __( 'Store Name (No space allowed)', 'BerryPay' ),
				'type'     => 'text',
				'desc_tip' => __( 'Example: merchant_name.', 'BerryPay' ),
				'default'  => __( 'Merchant Name Here', 'BerryPay' ),
			),
			'description'    => array(
				'title'    => __( 'Description', 'BerryPay' ),
				'type'     => 'textarea',
				'desc_tip' => __( 'Payment description the customer will see during the checkout process.', 'BerryPay' ),
				'default'  => __( 'Pay securely using your online banking through BerryPay.', 'BerryPay' ),
				'css'      => 'max-width:350px;'
			),
			'merchant_id' => array(
				'title'    => __( 'Merchant ID', 'BerryPay' ),
				'type'     => 'text',
				'desc_tip' => __( 'This is the merchant ID that you can obtain from profile page in BerryPay', 'BerryPay' ),
			),
			'api_key' => array(
				'title'    => __( 'API Key', 'BerryPay' ),
				'type'     => 'text',
				'desc_tip' => __( 'This is the API Key that you can obtain from API integeration page in BerryPay Dashboard', 'BerryPay' ),
			),
			'secret_key' => array(
				'title'    => __( 'Secret Key', 'BerryPay' ),
				'type'     => 'text',
				'desc_tip' => __( 'This is the Secret Key that you can obtain from API integeration page in BerryPay Dashboard', 'BerryPay' ),
			),
		);
	}

	# Submit payment
	public function process_payment( $order_id ) {
		# Get this order's information so that we know who to charge and how much
		$customer_order = wc_get_order( $order_id );

		# Prepare the data to send to senangPay
		$detail = "Payment for order: " . $order_id;

		$old_wc = version_compare( WC_VERSION, '3.0', '<' );

		if ( $old_wc ) {
			$order_id = $customer_order->id;
			$amount   = $customer_order->order_total;
			$name     = $customer_order->billing_first_name . ' ' . $customer_order->billing_last_name;
			$email    = $customer_order->billing_email;
			$phone    = $customer_order->billing_phone;
		} else {
			$order_id = $customer_order->get_id();
			$amount   = $customer_order->get_total();
			$name     = $customer_order->get_billing_first_name() . ' ' . $customer_order->get_billing_last_name();
			$email    = $customer_order->get_billing_email();
			$phone    = $customer_order->get_billing_phone();
		}
		
		$merchant = $this->store;
	
        $hash_string = $this->api_key."|".$amount."|".$email."|".$name."|".$phone."|".$order_id."|".$detail."|".$merchant;
        
        $signature = hash_hmac('sha256', $hash_string, $this->secret_key);

		$post_args = array(
			'txn_amount' => $amount,
			'txn_order_id' => $order_id,
			'txn_buyer_name' => $name,
			'txn_buyer_email' => $email,
			'txn_buyer_phone' => $phone,
			'txn_product_name' => $merchant,
			'txn_product_desc' => $detail,
			'api_key' => $this->api_key,
			'signature' => $signature,
		);

		# Format it properly using get
		$berrypay_args = '';
		foreach ( $post_args as $key => $value ) {
			if ( $berrypay_args != '' ) {
				$berrypay_args .= '&';
			}
			$berrypay_args .= $key . "=" . $value;
		}
		
        $merchant_id = $this->merchant_id;

		$environment_mode_url = 'https://secure.berrpaystaging.com/api/v2/plugin/payment/'.$merchant_id; // Staging
		// $environment_mode_url = 'https://securepay.berrypay.com/api/v2/plugin/payment/'.$merchant_id; // Production

		$order_note = wc_get_order($order_id);
		
		$order_note->add_order_note('Customer made a payment attempt through BerryPay.
		<br>Order ID: ' . $order_id . '
		<br>You can check the payment status of this order id in BerryPay account.');

		return array(
			'result'   => 'success',
			'redirect' => $environment_mode_url . "?" . $berrypay_args 
		);
	}

	public function check_berrypay_response() {
		if ( isset( $_REQUEST['txn_status_id'] ) && isset( $_REQUEST['txn_order_id'] ) && isset( $_REQUEST['txn_msg'] ) && isset( $_REQUEST['txn_ref_id'] ) ) {
			global $woocommerce;

			$is_callback = isset( $_POST['txn_order_id'] ) ? true : false;

			$order = wc_get_order( $_REQUEST['txn_order_id'] );

			$old_wc = version_compare( WC_VERSION, '3.0', '<' );

			$order_id = $old_wc ? $order->id : $order->get_id();

				if ( $_REQUEST['txn_status_id'] == 1 || $_REQUEST['txn_status_id'] == '1' ) {
					if ( strtolower( $order->get_status() ) == 'pending' || strtolower( $order->get_status() ) == 'processing' ) {
						# only update if order is pending
						if ( strtolower( $order->get_status() ) == 'pending' ) {

							$order->add_order_note( 'Payment successfully made through BerryPay!
							<br>Please check inside in BerryPay Dashboard https://securepay.berrypay.com/
							<br>Ref ID = ' . $_REQUEST['txn_ref_id'] . '
							<br>Order ID: '. $order_id .'
							<br>Reason: '. $_REQUEST['txn_msg']);

							$order->payment_complete();
						}

						if ( $is_callback ) {
							// echo 'OKWHYYOUCOMEHERE';
							// echo 'OK';
							wp_redirect( $order->get_checkout_order_received_url() );
						} else {
							# redirect to order receive page
							wp_redirect( $order->get_checkout_order_received_url() );
							wc_add_notice('Payment was not success.<br>Please contact site admin to get your payment status', 'error');
						}

						exit();
					}
				} elseif ($_REQUEST['txn_status_id'] == 3 || $_REQUEST['txn_status_id'] == '3') {
					if (strtolower($order->get_status()) == 'cancelled' || strtolower($order->get_status()) == 'pending' || strtolower($order->get_status()) == 'processing') {
						# only update if order is pending
						if (strtolower($order->get_status()) == 'cancelled' || strtolower($order->get_status()) == 'pending') {

							$order->payment_complete();

							$order->add_order_note('Payment attempt was in progress.
							<br>Please check in BerryPay for latest transaction update
							<br>Ref. No: '. $_REQUEST['txn_ref_id'].'
							<br>Order ID: '. $order_id);

							$order->update_status('processing');

						}

						if ($is_callback) {
							// echo 'OK';
							wp_redirect(wc_get_checkout_url());
						} else {
							wp_redirect(wc_get_checkout_url());
							wc_add_notice('Payment was in process');
						}
						exit();
					}
				} elseif ($_REQUEST['txn_status_id'] == 2 || $_REQUEST['txn_status_id'] == '2') {
					if (strtolower($order->get_status()) == 'pending' || strtolower($order->get_status()) == 'processing') {
						# only update if order is pending
						if (strtolower($order->get_status()) == 'pending' || strtolower($order->get_status()) == 'processing') {

							$order->add_order_note('Payment attempt was unsuccessful.
							<br>Please check in BerryPay for latest transaction update
							<br>Ref. No: '. $_REQUEST['txn_ref_id'].'
							<br>Order ID: '. $order_id);
							
							$woocommerce->cart->empty_cart();

							$order->update_status('failed');

						}

						if ($is_callback) {
							// echo 'OK';
							wp_redirect(wc_get_checkout_url());
							wc_add_notice('Reason: Payment is decline, please contact site admin to get your payment status
							<br>Please close this window or refresh checkout page before resubmitting your order.');
						} else {
							wp_redirect(wc_get_checkout_url());
							wc_add_notice('Reason: Payment is decline, please contact site admin to get your payment status
							<br>Please close this window or refresh checkout page before resubmitting your order.');
						}
						exit();
					}
				} else {
					if ( strtolower( $order->get_status() ) == 'pending' || strtolower( $order->get_status() ) == 'processing' ) {
						
						$order->payment_complete();

						$order->add_order_note('Payment attempt was unsuccessful.
							<br>Please check in BerryPay for latest transaction update
							<br>Ref. No: '. $_REQUEST['txn_ref_id'].'
							<br>Order ID: '. $order_id);

						$order->update_status('failed');

						if ( ! $is_callback ) {
							wp_redirect(wc_get_checkout_url());
							wc_add_notice('Reason: Payment is decline, please contact site admin to get your payment status
							<br>Please close this window or refresh checkout page before resubmitting your order.');
						} else {
							wp_redirect(wc_get_checkout_url());
							wc_add_notice('Reason: Payment is decline, please contact site admin to get your payment status
							<br>Please close this window or refresh checkout page before resubmitting your order.');
						}
						exit();
					}
				}
			
			}
	}

	# Validate fields, do nothing for the moment
	public function validate_fields() {
		return true;
	}

	# Check if we are forcing SSL on checkout pages, Custom function not required by the Gateway for now
	public function do_ssl_check() {
		if ( $this->enabled == "yes" ) {
			if ( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>" . sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . "</p></div>";
			}
		}
	}

	/**
	 * Check if this gateway is enabled and available in the user's country.
	 * Note: Not used for the time being
	 * @return bool
	 */
	public function is_valid_for_use() {
		return in_array( get_woocommerce_currency(), array( 'MYR' ) );
	}
}
