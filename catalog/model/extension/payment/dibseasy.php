<?php
class ModelExtensionPaymentDibseasy extends Model {

    const METHOD_CODE = 'dibseasy';
    const SHIPPING_CODE = 'free';
    const PAYMENT_API_TEST_URL = 'https://test.api.dibspayment.eu/v1/payments';
    const PAYMENT_API_LIVE_URL = 'https://api.dibspayment.eu/v1/payments';
    const PAYMENT_TRANSACTION_URL_PATTERN_TEST = 'https://test.api.dibspayment.eu/v1/payments/{transactionId}';
    const PAYMENT_TRANSACTION_URL_PATTERN_LIVE = 'https://api.dibspayment.eu/v1/payments/{transactionId}';
    const CHECKOUT_SCRIPT_TEST = 'https://test.checkout.dibspayment.eu/v1/checkout.js?v=1';
    const CHECKOUT_SCRIPT_LIVE = 'https://checkout.dibspayment.eu/v1/checkout.js?v=1';
    protected $products = array();
    protected $logger;
    public $paymentId;

        public function __construct($registry) {
                $this->logger = new Log('dibs.easy.log');
                parent::__construct($registry);
        }

	public function getMethod($address, $total) {
            $this->load->language('extension/payment/dibseasy');
            $status = true;
            $method_data = array();
            if ($status) {
                $method_data = array(
                    'code'       => self::METHOD_CODE,
                    'title'      =>  $this->language->get('text_title'),
                    'terms'      => '',
                    'sort_order' => $this->config->get('dibseasy_sort_order')
                );
            }
           return $method_data;
	}

        /*
         * The data required for checkout paget
         */
        public function getCheckoutData() {
            $data['products'] = array();

            foreach ($this->cart->getProducts() as $product) {
                    $option_data = array();

                    foreach ($product['option'] as $option) {
                            if ($option['type'] != 'file') {
                                    $value = $option['value'];
                            } else {
                                    $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                                    if ($upload_info) {
                                            $value = $upload_info['name'];
                                    } else {
                                            $value = '';
                                    }
                            }

                            $option_data[] = array(
                                    'name'  => $option['name'],
                                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                            );
                    }

                    $recurring = '';

                    if ($product['recurring']) {
                            $frequencies = array(
                                    'day'        => $this->language->get('text_day'),
                                    'week'       => $this->language->get('text_week'),
                                    'semi_month' => $this->language->get('text_semi_month'),
                                    'month'      => $this->language->get('text_month'),
                                    'year'       => $this->language->get('text_year'),
                            );

                            if ($product['recurring']['trial']) {
                                    $recurring = sprintf($this->language->get('text_trial_description'), $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
                            }

                            if ($product['recurring']['duration']) {
                                    $recurring .= sprintf($this->language->get('text_payment_description'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                            } else {
                                    $recurring .= sprintf($this->language->get('text_payment_cancel'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                            }
                    }

                    $data['products'][] = array(
                            'cart_id'    => $product['cart_id'],
                            'product_id' => $product['product_id'],
                            'name'       => $product['name'],
                            'model'      => $product['model'],
                            'option'     => $option_data,
                            'recurring'  => $recurring,
                            'quantity'   => $product['quantity'],
                            'subtract'   => $product['subtract'],
                            'price'      => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
                            'total'      => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'], $this->session->data['currency']),
                            'href'       => $this->url->link('product/product', 'product_id=' . $product['product_id'])
                    );
            }

            $data['checkoutkey'] = trim($this->config->get('dibseasy_checkoutkey'));
            if($this->config->get('dibseasy_testmode') == 0) {
                $data['checkoutkey'] = trim($this->config->get('dibseasy_checkoutkey_live'));
            } else {
                $data['checkoutkey'] =  trim($this->config->get('dibseasy_checkoutkey_test'));
            }
            $data['language'] = $this->config->get('dibseasy_language');

            if($this->config->get('dibseasy_testmode') == 0) {
                 $data['checkout_script'] = self::CHECKOUT_SCRIPT_LIVE;
            } else {
                 $data['checkout_script'] = self::CHECKOUT_SCRIPT_TEST;
            }
           $data['checkoutconfirmurl'] = $this->url->link('extension/payment/dibseasy/confirm', '', true);
           return $data;
        }

        public function createOrder() {
                $this->session->data['comment'] = '';
                
    		// Set totals
                $totals = array();
                $taxes = $this->cart->getTaxes();
                $total = 0;

                // Because __call can not keep var references so we put them into an array.
                $total_data = array(
                        'totals' => &$totals,
                        'taxes'  => &$taxes,
                        'total'  => &$total
                );

                $this->load->model('setting/extension');

                $sort_order = array();

                $results = $this->model_setting_extension->getExtensions('total');

                foreach ($results as $key => $value) {
                        $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
                }

                array_multisort($sort_order, SORT_ASC, $results);

                foreach ($results as $result) {
                        if ($this->config->get('total_' . $result['code'] . '_status')) {
                                $this->load->model('extension/total/' . $result['code']);

                                // We have to put the totals in an array so that they pass by reference.
                                $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                        }
                }

                $sort_order = array();

                foreach ($totals as $key => $value) {
                        $sort_order[$key] = $value['sort_order'];
                }

                array_multisort($sort_order, SORT_ASC, $totals);

                $order_data['totals'] = $totals;

                $this->load->language('checkout/checkout');

		$order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
		$order_data['store_id'] = $this->config->get('config_store_id');
		$order_data['store_name'] = $this->config->get('config_name');

		if ($order_data['store_id']) {
			$order_data['store_url'] = $this->config->get('config_url');
		} else {
			$order_data['store_url'] = HTTP_SERVER;
		}

		if ($this->customer->isLogged()) {
			$this->load->model('account/customer');
			$customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
			$order_data['customer_id'] = $this->customer->getId();
			$order_data['customer_group_id'] = $customer_info['customer_group_id'];
			$order_data['firstname'] = $customer_info['firstname'];
			$order_data['lastname'] = $customer_info['lastname'];
			$order_data['email'] = $customer_info['email'];
			$order_data['telephone'] = $customer_info['telephone'];
			$order_data['custom_field'] = json_decode($customer_info['custom_field'], true);
		} else { //if (isset($this->session->data['guest'])) {    
                    
                                $order_data['customer_id'] = 0;
				$order_data['customer_group_id'] = 1;
				$order_data['firstname'] = $this->session->data['shipping_address']['firstname'];
				$order_data['lastname'] =$this->session->data['shipping_address']['lastname'];
				$order_data['email'] = 'mbe@ciklum.com';
				$order_data['telephone'] = '';
				$order_data['fax'] = '';
                        /*
                        $order_data['customer_id'] = 0;
			$order_data['customer_group_id'] = $this->session->data['guest']['customer_group_id'];
			$order_data['firstname'] = $this->session->data['guest']['firstname'];
			$order_data['lastname'] = $this->session->data['guest']['lastname'];
			$order_data['email'] = $this->session->data['guest']['email'];
			$order_data['telephone'] = $this->session->data['guest']['telephone'];
			$order_data['custom_field'] = $this->session->data['guest']['custom_field'];
                        */
                    
		}

		$order_data['payment_firstname'] = $this->session->data['payment_address']['firstname'];
		$order_data['payment_lastname'] = $this->session->data['payment_address']['lastname'];
		$order_data['payment_company'] = $this->session->data['payment_address']['company'];
		$order_data['payment_address_1'] = $this->session->data['payment_address']['address_1'];
		$order_data['payment_address_2'] = $this->session->data['payment_address']['address_2'];
		$order_data['payment_city'] = $this->session->data['payment_address']['city'];
		$order_data['payment_postcode'] = $this->session->data['payment_address']['postcode'];
		$order_data['payment_zone'] = $this->session->data['payment_address']['zone'];
		$order_data['payment_zone_id'] = $this->session->data['payment_address']['zone_id'];
		$order_data['payment_country'] = $this->session->data['payment_address']['country'];
		$order_data['payment_country_id'] = $this->session->data['payment_address']['country_id'];
		$order_data['payment_address_format'] = $this->session->data['payment_address']['address_format'];
		$order_data['payment_custom_field'] = (isset($this->session->data['payment_address']['custom_field']) ? $this->session->data['payment_address']['custom_field'] : array());

		if (isset($this->session->data['payment_method']['title'])) {
			$order_data['payment_method'] = $this->session->data['payment_method']['title'];
		} else {
			$order_data['payment_method'] = '';
		}

		if (isset($this->session->data['payment_method']['code'])) {
			$order_data['payment_code'] = $this->session->data['payment_method']['code'];
		} else {
			$order_data['payment_code'] = '';
		}

		if ($this->cart->hasShipping()) {
			$order_data['shipping_firstname'] = $this->session->data['shipping_address']['firstname'];
			$order_data['shipping_lastname'] = $this->session->data['shipping_address']['lastname'];
			$order_data['shipping_company'] = $this->session->data['shipping_address']['company'];
			$order_data['shipping_address_1'] = $this->session->data['shipping_address']['address_1'];
			$order_data['shipping_address_2'] = $this->session->data['shipping_address']['address_2'];
			$order_data['shipping_city'] = $this->session->data['shipping_address']['city'];
			$order_data['shipping_postcode'] = $this->session->data['shipping_address']['postcode'];
			$order_data['shipping_zone'] = $this->session->data['shipping_address']['zone'];
			$order_data['shipping_zone_id'] = $this->session->data['shipping_address']['zone_id'];
			$order_data['shipping_country'] = $this->session->data['shipping_address']['country'];
			$order_data['shipping_country_id'] = $this->session->data['shipping_address']['country_id'];
			$order_data['shipping_address_format'] = $this->session->data['shipping_address']['address_format'];
			$order_data['shipping_custom_field'] = (isset($this->session->data['shipping_address']['custom_field']) ? $this->session->data['shipping_address']['custom_field'] : array());

			if (isset($this->session->data['shipping_method']['title'])) {
				$order_data['shipping_method'] = $this->session->data['shipping_method']['title'];
			} else {
				$order_data['shipping_method'] = '';
			}

			if (isset($this->session->data['shipping_method']['code'])) {
				$order_data['shipping_code'] = $this->session->data['shipping_method']['code'];
			} else {
				$order_data['shipping_code'] = '';
			}
		} else {
			$order_data['shipping_firstname'] = '';
			$order_data['shipping_lastname'] = '';
			$order_data['shipping_company'] = '';
			$order_data['shipping_address_1'] = '';
			$order_data['shipping_address_2'] = '';
			$order_data['shipping_city'] = '';
			$order_data['shipping_postcode'] = '';
			$order_data['shipping_zone'] = '';
			$order_data['shipping_zone_id'] = '';
			$order_data['shipping_country'] = '';
			$order_data['shipping_country_id'] = '';
			$order_data['shipping_address_format'] = '';
			$order_data['shipping_custom_field'] = array();
			$order_data['shipping_method'] = '';
			$order_data['shipping_code'] = '';
		}

		$order_data['products'] = array();

		foreach ($this->cart->getProducts() as $product) {
			$option_data = array();

			foreach ($product['option'] as $option) {
				$option_data[] = array(
					'product_option_id'       => $option['product_option_id'],
					'product_option_value_id' => $option['product_option_value_id'],
					'option_id'               => $option['option_id'],
					'option_value_id'         => $option['option_value_id'],
					'name'                    => $option['name'],
					'value'                   => $option['value'],
					'type'                    => $option['type']
				);
			}

			$order_data['products'][] = array(
				'product_id' => $product['product_id'],
				'name'       => $product['name'],
				'model'      => $product['model'],
				'option'     => $option_data,
				'download'   => $product['download'],
				'quantity'   => $product['quantity'],
				'subtract'   => $product['subtract'],
				'price'      => $product['price'],
				'total'      => $product['total'],
				'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
				'reward'     => $product['reward']
			);
		}

		// Gift Voucher
		$order_data['vouchers'] = array();

		if (!empty($this->session->data['vouchers'])) {
			foreach ($this->session->data['vouchers'] as $voucher) {
				$order_data['vouchers'][] = array(
					'description'      => $voucher['description'],
					'code'             => token(10),
					'to_name'          => $voucher['to_name'],
					'to_email'         => $voucher['to_email'],
					'from_name'        => $voucher['from_name'],
					'from_email'       => $voucher['from_email'],
					'voucher_theme_id' => $voucher['voucher_theme_id'],
					'message'          => $voucher['message'],
					'amount'           => $voucher['amount']
				);
			}
		}

		$order_data['comment'] = $this->session->data['comment'];
		$order_data['total'] = $total;

		if (isset($this->request->cookie['tracking'])) {
			$order_data['tracking'] = $this->request->cookie['tracking'];

			$subtotal = $this->cart->getSubTotal();

			// Affiliate
			$this->load->model('affiliate/affiliate');

			$affiliate_info = $this->model_affiliate_affiliate->getAffiliateByCode($this->request->cookie['tracking']);

			if ($affiliate_info) {
				$order_data['affiliate_id'] = $affiliate_info['affiliate_id'];
				$order_data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
			} else {
				$order_data['affiliate_id'] = 0;
				$order_data['commission'] = 0;
			}

			// Marketing
			$this->load->model('checkout/marketing');

			$marketing_info = $this->model_checkout_marketing->getMarketingByCode($this->request->cookie['tracking']);

			if ($marketing_info) {
				$order_data['marketing_id'] = $marketing_info['marketing_id'];
			} else {
				$order_data['marketing_id'] = 0;
			}
		} else {
			$order_data['affiliate_id'] = 0;
			$order_data['commission'] = 0;
			$order_data['marketing_id'] = 0;
			$order_data['tracking'] = '';
		}

		$order_data['language_id'] = $this->config->get('config_language_id');
		$order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
		$order_data['currency_code'] = $this->session->data['currency'];
		$order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
		$order_data['ip'] = $this->request->server['REMOTE_ADDR'];

		if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
			$order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
		} elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
			$order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
		} else {
			$order_data['forwarded_ip'] = '';
		}

		if (isset($this->request->server['HTTP_USER_AGENT'])) {
			$order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
		} else {
			$order_data['user_agent'] = '';
		}

		if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
			$order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
		} else {
			$order_data['accept_language'] = '';
		}
		$this->load->model('checkout/order');
		$this->session->data['order_id'] = $this->model_checkout_order->addOrder($order_data);
	}
 
        protected function setShippingMethodOld() {
            if ($this->validateCart() && $this->cart->hasShipping()) {
                $json['shipping_methods'] = array();
                $this->load->model('setting/extension');
                $results = $this->model_setting_extension->getExtensions('shipping');
                $shippingMethod = $this->config->get('dibseasy_shipping_method') != null ? 
                $this->config->get('dibseasy_shipping_method') : self::SHIPPING_CODE;
                if ($this->config->get('shipping_'. $shippingMethod .'_status')) {
                       $this->load->model('extension/shipping/' . $shippingMethod);
                       $quote = $this->{'model_extension_shipping_' . $shippingMethod}->getQuote(array('country_id'=>0, 'zone_id'=>0));
                       if ($quote) {
                                $json['shipping_methods'][$shippingMethod] = array(
                                        'title'      => $quote['title'],
                                        'quote'      => $quote['quote'],
                                        'sort_order' => $quote['sort_order'],
                                        'error'      => $quote['error']
                                );
                        }
                }
                
                if($json['shipping_methods']) {
                    $this->session->data['shipping_method'] = $json['shipping_methods'][$shippingMethod]['quote'][$shippingMethod];
                }
                
            }
        }

        protected function validateCart() {
            // Validate cart has products and has stock.
            if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
                    $json['redirect'] = $this->url->link('checkout/cart');
                    return false;
            }
            // Validate minimum quantity requirements.
            $products = $this->cart->getProducts();
            foreach ($products as $product) {
                    $product_total = 0;

                    foreach ($products as $product_2) {
                            if ($product_2['product_id'] == $product['product_id']) {
                                    $product_total += $product_2['quantity'];
                            }
                    }
                    if ($product['minimum'] > $product_total) {
                            $json['redirect'] = $this->url->link('checkout/cart');
                            return false;
                            break;
                    }
            }
            return true;
        }

        public function getPaymentId() {
            /*$transactionId = isset($this->session->data['dibseasy']['paymentid']) 
                    ? $this->session->data['dibseasy']['paymentid'] : null;
            if(!$this->getTransactionInfo($transactionId)) {
               unset($this->session->data['dibseasy']['paymentid']);
            }
            */
            
            //unset($this->session->data['dibseasy']['paymentid']);
            
            if(!$this->cart->hasProducts()) {
               unset($this->session->data['dibseasy']['paymentid']);
            } 
            $this->setPaymentMethod();
            if(isset($this->session->data['dibseasy']['paymentid']) && $this->session->data['dibseasy']['paymentid']) {
              return $this->session->data['dibseasy']['paymentid'];
            }
            if($this->config->get('dibseasy_testmode') == 0) {
                $url = self::PAYMENT_API_LIVE_URL;
            } else {
                $url = self::PAYMENT_API_TEST_URL;
            }
            $response = $this->makeCurlRequest($url, $this->createRequestObject());
            if($response && isset($response->paymentId)) {
                $this->session->data['dibseasy']['paymentid'] = $response->paymentId;
                return $response->paymentId;
            } else {
                $this->logger->write($response);
            }
            return false;
        }

        protected function setPaymentMethod() {
             $this->session->data['payment_method'] =  array(
		'code'       => self::METHOD_CODE,
		'title'      => $this->language->get('text_title'),
                'sort_order' => '1');
        }

        /**
         * 
         * @param string $url
         * @param array $data
         * @param type $method
         * @return string
         */
        protected function makeCurlRequest($url, $data = array(), $method = 'POST') {
            $curl = curl_init();
            $header = array();
            $headers[] = 'Content-Type: text/json';
            $headers[] = 'Accept: test/json';
            $headers[] = 'commercePlatformTag: OC30';
            if($this->config->get('dibseasy_testmode') == 1) {
               $headers[] = 'Authorization: ' . str_replace('-', '', trim($this->config->get('dibseasy_testkey')));
            } else {
               $headers[] = 'Authorization: ' . str_replace('-', '', trim($this->config->get('dibseasy_livekey')));
            }
            $postData = $data;
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            if($postData) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData));
                $this->debug('data sended to Easy API', $postData);
                
            }

            if($this->config->get('dibseasy_debug_mode')) {
                   $this->logger->write('Curl request:');
                   $this->logger->write($data);
            } else {
            }

            $response = curl_exec($curl);
            $info = curl_getinfo($curl);

            $this->debug('respose from Easy Api', $response);

            $this->logger->write($info);
            if ($info['http_code'] == 401 || $info['http_code'] == 404 || $info['http_code'] == 403) {
                error_log('Authorization failed, please check your secret key and mode test/live');
                $this->logger->write("Authorization failed, please check your secret key and mode test/live");
            } else {
                if( $response ) {
                   $responseDecoded = json_decode($response);
                   if($this->config->get('dibseasy_debug')) {
                       $this->logger->write('Curl response:');
                       $this->logger->write($response);
                   }
                   return ($responseDecoded) ? $responseDecoded : null;
                }
            }

            if(curl_error($curl)) {
              $this->logger->write('Curl error:');
              $this->logger->write(curl_error($curl));
            }
        }

        protected function getTotalTaxRate($tax_class_id) {
             $totalRate = 0;   
               foreach($this->tax->getRates(0, $tax_class_id) as $tax) {
                   if('P' == $tax['type']) {
                       $totalRate += $tax['rate'];
                   }
               }
              return $totalRate;
        }

        /*
         * Generate request object in json format that will be sended to API
         * @return array
         * 
         */
        public function createRequestObject() {
            $this->load->model('checkout/order');
            // add consumer type
            $customerType = $this->config->get('dibseasy_allowed_customer_type');
            $supportedTypes = array();
            $consumerType = array();
            if(trim($customerType)) {
                $default = null;
                switch($customerType) {
                    case 'b2c' :
                        $supportedTypes = array('B2C');
                        $default = 'B2C';
                        break;
                    case 'b2b':
                        $supportedTypes = array('B2B');
                        $default = 'B2B';
                        break;
                    case 'b2c_b2b_b2c':
                        $supportedTypes = array('B2C', 'B2B');
                        $default = 'B2C';
                        break;
                    case 'b2b_b2c_b2b':
                        $supportedTypes = array('B2C', 'B2B');
                        $default = 'B2B';
                        break;
                }
              $consumerType = array('supportedTypes'=>$supportedTypes,'default'=>$default);
            }

            $data = array(
                'order' => array(
                    'items' => $this->getRequestObjectItems(),
                    'amount' => round($this->currency->format($this->getGrandTotal(), $this->session->data['currency'], '', false) * 100),
                    'currency' => $this->session->data['currency'],
                    'reference' => uniqid('opc_')),
                 'checkout' => array(
                        'url' => $this->url->link('extension/payment/dibseasy/confirm', '', true),
                        'termsUrl' => $this->config->get('dibseasy_terms_and_conditions')));
            if($consumerType) {
                $checkout = $data['checkout'];
                $checkout['consumerType'] = $consumerType;
                $data['checkout'] = $checkout;
            }
            if($this->config->get('dibseasy_debug')) {
                   $this->logger->write("Collected data:");
                   $this->logger->write($data);
            }

            return $data;
        }
 
        public function getRequestObjectItems() {
            $this->load->model('checkout/order');
            $items = array();

            foreach ($this->cart->getProducts() as $product) {
                $netPrice = $this->currency->format($product['price'], $this->session->data['currency'], '', false);
                $grossPrice =  $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'], '', false);     
                $taxAmount = $this->getTaxAmount($netPrice, $product['tax_class_id']);
                $taxRates = $this->tax->getRates($netPrice,  $product['tax_class_id']);
                $this->currency->format($product['price'], $this->session->data['currency'], '', false);
                $taxRate = $this->getTotalTaxRate($product['tax_class_id']);
                $items[] = array(
                    'reference' => $product['product_id'],
                    'name' => $product['name'],
                    'quantity' => $product['quantity'],
                    'unit' => 'pcs',
                    'unitPrice' => round($netPrice * $product['quantity'] * 100),
                    'taxRate' => $taxRate * 100,
                    'taxAmount' => round(($taxAmount * $product['quantity']) * 100),
                    'grossTotalAmount' => round($grossPrice * 100) * $product['quantity'],
                    'netTotalAmount' => round($netPrice * $product['quantity'] * 100));
            }

            $totals = $this->getTotals();
            foreach($totals['totals'] as $total) {
                    $shipping_method = isset($this->session->data['shipping_method']) ? $this->session->data['shipping_method'] : null;
                    $shipping_tax_class = isset($shipping_method['tax_class_id']) ? $shipping_method['tax_class_id'] : 0;
                if( in_array($total['code'], $this->additional_totals()) && abs($total['value']) > 0) {
                    if($total['code'] == 'shipping') {
                        $netPrice = $this->currency->format($total['value'], $this->session->data['currency'], '', false);
                        $grossPrice =  $this->currency->format($this->tax->calculate($total['value'], $shipping_tax_class, $this->config->get('config_tax')), $this->session->data['currency'], '', false);     
                        $taxAmount = $this->tax->getTax($netPrice, $shipping_tax_class);
                        $taxRate = $this->getTotalTaxRate($shipping_tax_class);
                    } else {
                        $grossPrice = $netPrice = $this->currency->format($total['value'], $this->session->data['currency'], '', false);
                        $taxAmount = $taxRate = 0;
                    }
                    $price = $this->currency->format($total['value'], $this->session->data['currency'], '', false);
                    $items[] = array(
                        'reference' => $total['code'],
                        'name' => $total['title'],
                        'quantity' => 1,
                        'unit' => 1,
                        'unitPrice' => round($netPrice * 100),
                        'taxRate' => $taxRate * 100,
                        'taxAmount' => round($taxAmount * 100),
                        'grossTotalAmount' => round($grossPrice * 100),
                        'netTotalAmount' => round($netPrice * 100));
                  }
              }

              $this->debug('totals', $totals['totals']);

              $totalPriceCalculated = 0;
              foreach($items as $total) {
                  $totalPriceCalculated += $total['grossTotalAmount'];
              }

              $this->debug('totals price calculated', $totalPriceCalculated);
              $this->debug('grand total', round($this->getGrandTotal()) * 100);

              //$total = round($this->currency->format($order_info['total'], $order_info['currency_code'], '', false) * 100);
              //$delta = $total - $totalPriceCalculated;

              if(isset($this->session->data['coupon'])) {
                   $items[] = array(
                    'reference' => 'coupon',
                    'name' => 'Coupon',
                    'quantity' => 1,
                    'unit' => 1,
                    'unitPrice' => $delta,
                    'taxRate' => 0,
                    'taxAmount' => 0,
                    'grossTotalAmount' => $delta,
                    'netTotalAmount' => $delta);
              } else {

                //hack to avoid errors in row totals calculation

                $total = round($this->getGrandTotal()) * 100;

                if($total !=  $totalPriceCalculated) {
                    $delta = $total - $totalPriceCalculated;
                    $items[] = array(
                        'reference' => 'rouding',
                        'name' => 'rounding',
                        'quantity' => 1,
                        'unit' => 1,
                        'unitPrice' => $delta,
                        'taxRate' => 0,
                        'taxAmount' => 0,
                        'grossTotalAmount' => $delta,
                        'netTotalAmount' => $delta);
                }

            }

            return $items;
        }

        /**
         * 
         * @param type $transactionId
         * @return string | json object
         */
        public function getTransactionInfo($transactionId) {
             if($this->config->get('dibseasy_testmode') == 1) {
                  $url = str_replace('{transactionId}', $transactionId, self::PAYMENT_TRANSACTION_URL_PATTERN_TEST);
             } else {
                  $url = str_replace('{transactionId}', $transactionId, self::PAYMENT_TRANSACTION_URL_PATTERN_LIVE);
             }
            return $this->makeCurlRequest($url, array(), 'GET');
        }

        public function getCountryByIsoCode3($iso_code_3) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE `iso_code_3` = '" . $this->db->escape($iso_code_3) . "' AND `status` = '1'");
		return $query->row;
	}

        public function setAddresses($order_id, $data) {
            $setFields = '';
            foreach($data as $key => $value) {
               $setFields .= '`'.$key. '`' . "='" . $this->db->escape($value) . "',";
            }
            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET ". $setFields ." date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
        }

        public function getTotals() {
            $this->load->model('setting/extension');
            $totals = array();
            $taxes = $this->cart->getTaxes();
            $total = 0;
            $total_data = array(
                    'totals' => &$totals,
                    'taxes'  => &$taxes,
                    'total'  => &$total
            );
            $sort_order = array();
            $results = $this->model_setting_extension->getExtensions('total');
            foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
            }
            array_multisort($sort_order, SORT_ASC, $results);
            foreach ($results as $result) {
                    if ($this->config->get('total_' . $result['code'] . '_status')) {
                            $this->load->model('extension/total/' . $result['code']);
                            $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                    }
              }

              return $total_data;
        }

       /**
        * 
        * @return string
        */
       public function getGrandTotal() {
           $totals = $this->getTotals();
           $total = 0;
           foreach($totals['totals'] as $total) {
               if ($total['code'] == 'total') {
                   $total = $total['value'];
               }
           }

          return $total;
       }

        protected function additional_totals() {
            return array('shipping');
        }

        public function getTaxAmount($value, $tax_class_id) {
            $amount = 0;
            $tax_rates = $this->tax->getRates($value,  $tax_class_id);
            foreach ($tax_rates as $tax_rate) {
                  if($tax_rate['type'] == 'F') {
                       $amount +=  $this->currency->format($tax_rate['amount'], $this->session->data['currency'], '', false);
                    } else {
                       $amount += $tax_rate['amount'];
                    }
            }
            $decimal_places = $this->currency->getDecimalPlace($this->session->data['currency']);
            if($decimal_places) {
                $amount = round($amount, $this->currency->getDecimalPlace($this->session->data['currency']));
            } else {
                $amount = round($amount);
            }
            return $amount;
	}

        /**
         * Get available shipping methods based on shipping address
         * 
         * @return array
         * @throws Exception
         */
        public function getShippingMethods() {
            $result = array();
            if(!$this->cart->hasShipping()) {
                return $result;
            }

            $this->load->language('checkout/checkout');
            if (isset($this->session->data['shipping_address'])) {
                // Shipping Methods
                $method_data = array();

                $this->load->model('setting/extension');

                $results = $this->model_setting_extension->getExtensions('shipping');

                foreach ($results as $result) {
                        if ($this->config->get('shipping_' . $result['code'] . '_status')) {
                                $this->load->model('extension/shipping/' . $result['code']);

                                $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($this->session->data['shipping_address']);

                                if ($quote) {
                                        $method_data[$result['code']] = array(
                                                'title'      => $quote['title'],
                                                'quote'      => $quote['quote'],
                                                'sort_order' => $quote['sort_order'],
                                                'error'      => $quote['error']
                                        );
                                }
                        }
                }

                $sort_order = array();

                foreach ($method_data as $key => $value) {
                        $sort_order[$key] = $value['sort_order'];
                }

                array_multisort($sort_order, SORT_ASC, $method_data);

                $result = $method_data;

                if($method_data) {

                    $method = current($method_data);
                    $quote = $method['quote'];
                    $current = current($quote);
                    $code = $current['code'];

                    // Set the first available shipping method
                    if(!isset($this->session->data['shipping_method'])) {
                        $this->setShippingMethod($code);
                    }

                    // If shipping from session is not in shippings list 
                    // set the first available shipping method 
                    if(isset($this->session->data['shipping_method'])) {

                        $sessinMethodIsInMethods = false;
                        foreach($method_data as $md) {
                              $quote = $md['quote'];
                              $current = current($quote);
                              $cd = $current['code'];
                              if($cd == $this->session->data['shipping_method']['code']) {
                                  $sessinMethodIsInMethods = true;
                              }
                        }
                        if(!$sessinMethodIsInMethods) {
                             $this->setShippingMethod($code);
                        }
                    }
                }

                if($this->cart->hasShipping() && !$result) {
                    throw new Exception('No shipping methods available for current address');
                }
            }
            return $result;
        }

        /**
         * Set shipping method based on shipping code
         * 
         * @param type $shippingCode
         */
        public function setShippingMethod($shippingCode = null) {
           if ($this->validateCart() && $this->cart->hasShipping()) {

                $json['shipping_methods'] = array();
                $this->load->model('setting/extension');

                $shipping = explode('.', $shippingCode);

                $results = $this->model_setting_extension->getExtensions('shipping');
                if ($this->config->get('shipping_'. $shipping[0] .'_status')) {
                       $this->load->model('extension/shipping/' . $shipping[0] );
                       $quote = $this->{'model_extension_shipping_' . $shipping[0] }->getQuote(array('country_id'=>0, 'zone_id'=>0));
                       if ($quote) {
                                $json['shipping_methods'][$shipping[0] ] = array(
                                        'title'      => $quote['title'],
                                        'quote'      => $quote['quote'],
                                        'sort_order' => $quote['sort_order'],
                                        'error'      => $quote['error']
                                );
                        }
                }

                if($json['shipping_methods']) {
                    $this->session->data['shipping_method'] = $json['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
                    $this->updateCart();
                }
             }
       }

       /**
        * Update cart on Easy side:
        * https://tech.dibspayment.com/easy/api/rest/update-cart
        */
       public function updateCart() 
       {
           $totals = $this->getTotals();
           $requestData = array(
             'amount' => round($this->currency->format($totals['total'], $this->session->data['currency'], '', false) * 100),
             'items' => $this->getRequestObjectItems(),
             'shipping' => ['costSpecified' => true]
           );
           $paymentId = $this->session->data['dibseasy']['paymentid'];
           $this->makeCurlRequest($this->getApiUrlPrefix() . $paymentId . '/orderitems', $requestData, 'PUT');
       }

       /**
        * Change shipping, billing address in quote if customer change it in Easy Window 
        */
       public function saveShippingAddress() {
           
       }

       /**
        * Retrieve payment by id
        */
       public function getPayment($paymentId) {
           $url = $this->getApiUrlPrefix() . $paymentId;
           return $this->makeCurlRequest($url, null, 'GET');
       }

       /**
        * Set shipping address from payment 
        * object and store in session 
        */
       public function setShippingAddress() {
           $paymentid = $this->session->data['dibseasy']['paymentid'];
           $paymentObject = $this->model_extension_payment_dibseasy->getPayment($paymentid);

           if(isset($paymentObject->payment->consumer->privatePerson->firstName)) {
             $this->session->data['shipping_address']['firstname'] = $paymentObject->payment->consumer->privatePerson->firstName; 
           }
           if($paymentObject->payment->consumer->privatePerson->lastName) {
             $this->session->data['shipping_address']['lastname'] = $paymentObject->payment->consumer->privatePerson->lastName;
           }
           $this->session->data['shipping_address']['company'] = 'Ciklum';

           if($paymentObject->payment->consumer->shippingAddress->addressLine1) {
              $this->session->data['shipping_address']['address_1'] = $paymentObject->payment->consumer->shippingAddress->addressLine1;
           }

	   $this->session->data['shipping_address']['address_2'] = '';
	   
           if($paymentObject->payment->consumer->shippingAddress->city) {
              $this->session->data['shipping_address']['city'] = $paymentObject->payment->consumer->shippingAddress->city;
           }

           if($paymentObject->payment->consumer->shippingAddress->postalCode) {
              $this->session->data['shipping_address']['postcode'] = $paymentObject->payment->consumer->shippingAddress->postalCode;
           }

           $this->session->data['shipping_address']['zone'] = 0;

           $this->session->data['shipping_address']['zone_id'] = 0;

           if($paymentObject->payment->consumer->shippingAddress->country) {
               $this->session->data['shipping_address']['country'] = $this->getCountryName($paymentObject->payment->consumer->shippingAddress->country);
               $this->session->data['shipping_address']['country_id'] = $this->getCountryId($paymentObject->payment->consumer->shippingAddress->country);
           }

           $this->setPaymentAddress();
       }

       protected function setPaymentAddress() {
           $paymentid = $this->session->data['dibseasy']['paymentid'];
           $paymentObject = $this->model_extension_payment_dibseasy->getPayment($paymentid);

           if(isset($paymentObject->payment->consumer->privatePerson->firstName)) {
             $this->session->data['payment_address']['firstname'] = $paymentObject->payment->consumer->privatePerson->firstName; 
           }
           if($paymentObject->payment->consumer->privatePerson->lastName) {
             $this->session->data['payment_address']['lastname'] = $paymentObject->payment->consumer->privatePerson->lastName;
           }
           $this->session->data['payment_address']['company'] = 'Ciklum';

           if($paymentObject->payment->consumer->shippingAddress->addressLine1) {
              $this->session->data['payment_address']['address_1'] = $paymentObject->payment->consumer->shippingAddress->addressLine1;
           }

	   $this->session->data['payment_address']['address_2'] = '';

           if($paymentObject->payment->consumer->shippingAddress->city) {
              $this->session->data['payment_address']['city'] = $paymentObject->payment->consumer->shippingAddress->city;
           }

           if($paymentObject->payment->consumer->shippingAddress->postalCode) {
              $this->session->data['payment_address']['postcode'] = $paymentObject->payment->consumer->shippingAddress->postalCode;
           }

           $this->session->data['payment_address']['zone'] = 0;

           $this->session->data['payment_address']['zone_id'] = 0;

           if($paymentObject->payment->consumer->shippingAddress->country) {
               $this->session->data['payment_address']['country'] = $this->getCountryName($paymentObject->payment->consumer->shippingAddress->country);
               $this->session->data['payment_address']['country_id'] = $this->getCountryId($paymentObject->payment->consumer->shippingAddress->country);
           }
       }

       public function start() {
           if(isset($this->session->data['dibseasy']['paymentid'])) {
            $this->updateCart();
           }
       }

       protected function getApiUrlPrefix() {
           $urlPrefix = '';
           if($this->config->get('dibseasy_testmode') == 1) {
              $urlPrefix = 'https://test.api.dibspayment.eu/v1/payments/';
            } else {
                $urlPrefix = 'https://api.dibspayment.eu/v1/payments/';
            }
            return $urlPrefix;
      }

      protected function getCountryId($country_code) {
		$row = $this->db->query("SELECT `country_id` FROM `" . DB_PREFIX . "country` WHERE LOWER(`iso_code_3`) = '" . $this->db->escape(strtolower($country_code)) . "'")->row;

		if (isset($row['country_id']) && !empty($row['country_id'])) {
			return (int)$row['country_id'];
		}
		return 0;
      }

      protected function getCountryName($country_code) {
		$row = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "country` WHERE LOWER(`iso_code_2`) = '" . $this->db->escape(strtolower($country_code)) . "'")->row;

		if (isset($row['name']) && !empty($row['name'])) {
			return $row['name'];
		}

		return '';
      }

      public function debug($prefix = '', $data) {
           ob_start();
           echo $prefix . "\n";
           //echo "<pre>";
           var_dump($data);
           //echo "</pre>";
           $result = ob_get_clean();
           error_log($result);
      }

}
