<?php

class ControllerExtensionPaymentDibseasy extends Controller {
	private $error = array();

	public function index() {
                $this->load->language('extension/payment/dibseasy');
		$this->document->setTitle($this->language->get('heading_title'));
         	$this->load->model('setting/setting');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
                    $this->model_setting_setting->editSetting('payment_dibseasy', $this->request->post);
                    $this->session->data['success'] = $this->language->get('text_success');
                    unset($this->session->data['dibseasy']['paymentid']);
                    $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
                    
		}
                
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
               	$data['entry_dibseasy_checkoutkey_test'] = $this->language->get('entry_dibseasy_checkoutkey');
                $data['entry_dibseasy_checkoutkey_live'] = $this->language->get('entry_dibseasy_checkoutk_live');
                $data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_total'] = $this->language->get('entry_total');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['help_total'] = $this->language->get('help_total');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
                $data['entry_debug'] = $this->language->get('entry_debug');
                $data['entry_testmode'] = $this->language->get('entry_testmode');
                $data['entry_dibseasy_livekey'] = $this->language->get('entry_dibseasy_livekey');
                $data['entry_dibseasy_testkey'] = $this->language->get('entry_dibseasy_testkey');
                $data['entry_testmode_description'] =  $this->language->get('entry_testmode_description');
                $data['entry_debug_description'] = $this->language->get('entry_debug_description');
                $data['entry_language'] = $this->language->get('entry_language');
                $data['entry_dibseasy_terms_and_conditions'] = $this->language->get('entry_dibseasy_terms_and_conditions');
                $data['entry_allowed_customer_type'] = $this->language->get('entry_allowed_customer_type');
                $data['entry_dibseasy_otherpayment_button_url'] = $this->language->get('entry_dibseasy_otherpayment_button_url');
                $data['entry_dibseasy_otherpayment_button_url_comment'] = $this->language->get('entry_dibseasy_otherpayment_button_url_comment');
                $data['text_b2c'] = $this->language->get('text_b2c');
                $data['text_b2b'] = $this->language->get('text_b2b');
                $data['text_b2c_b2b_b2c'] = $this->language->get('text_b2c_b2b_b2c');
                $data['text_b2b_b2c_b2b'] = $this->language->get('text_b2b_b2c_b2b');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=1232123e32e34r', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('extension/extension', 'token=121w21ws32e23e' .  '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/dibseasy', 'token=23es32ed34f34f5', true)
		);

		$data['action'] =  $this->url->link('extension/payment/dibseasy', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_dibseasy_checkoutkey_test'])) {
			$data['payment_dibseasy_checkoutkey_test'] = $this->request->post['payment_dibseasy_checkoutkey_test'];
		} else {
			$data['payment_dibseasy_checkoutkey_test'] = $this->config->get('payment_dibseasy_checkoutkey_test');
		}

                if (isset($this->request->post['dibseasy_checkoutkey_live'])) {
			$data['payment_dibseasy_checkoutkey_live'] = $this->request->post['payment_dibseasy_checkoutkey_live'];
		} else {
			$data['payment_dibseasy_checkoutkey_live'] = $this->config->get('payment_dibseasy_checkoutkey_live');
		}

                if (isset($this->request->post['dibseasy_total'])) {
			$data['dibseasy_total'] = $this->request->post['dibseasy_total'];
		} else {
			$data['dibseasy_total'] = $this->config->get('dibseasy_total');
		}

                if (isset($this->request->post['dibseasy_shipping_method'])) {
			$data['dibseasy_shipping_method'] = $this->request->post['dibseasy_shipping_method'];
		} else {
			$data['dibseasy_shipping_method'] = $this->config->get('dibseasy_shipping_method');
		}

                if (isset($this->request->post['payment_dibseasy_order_status_id'])) {
			$data['payment_dibseasy_order_status_id'] = $this->request->post['payment_dibseasy_order_status_id'];
		} else {
			$data['payment_dibseasy_order_status_id'] = $this->config->get('payment_dibseasy_order_status_id');
		}

                if (isset($this->request->post['payment_dibseasy_testmode'])) {
			$data['payment_dibseasy_testmode'] = $this->request->post['payment_dibseasy_testmode'];
		} else {
			$data['payment_dibseasy_testmode'] = $this->config->get('payment_dibseasy_testmode');
		}

                if (isset($this->request->post['payment_dibseasy_terms_and_conditions'])) {
        		$data['payment_dibseasy_terms_and_conditions'] = $this->request->post['payment_dibseasy_terms_and_conditions'];
		} else {
			$data['payment_dibseasy_terms_and_conditions'] = $this->config->get('payment_dibseasy_terms_and_conditions');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

                if (isset($this->request->post['payment_dibseasy_status'])) {
			$data['payment_dibseasy_status'] = $this->request->post['payment_dibseasy_status'];
		} else {
			$data['payment_dibseasy_status'] = $this->config->get('payment_dibseasy_status');
		}

                if (isset($this->request->post['dibseasy_debug'])) {
			$data['dibseasy_debug'] = $this->request->post['dibseasy_debug'];
		} else {
			$data['dibseasy_debug'] = $this->config->get('dibseasy_debug');
		}

                if (isset($this->request->post['payment_dibseasy_livekey'])) {
			$data['payment_dibseasy_livekey'] = $this->request->post['payment_dibseasy_livekey'];
		} else {
			$data['payment_dibseasy_livekey'] = $this->config->get('payment_dibseasy_livekey');
		}

                if (isset($this->request->post['payment_dibseasy_testkey'])) {
			$data['payment_dibseasy_testkey'] = $this->request->post['payment_dibseasy_testkey'];
		} else {
			$data['payment_dibseasy_testkey'] = $this->config->get('payment_dibseasy_testkey');
		}

                if (isset($this->request->post['payment_dibseasy_language'])) {
			$data['payment_dibseasy_language'] = $this->request->post['payment_dibseasy_language'];
		} else {
			$data['payment_dibseasy_language'] = $this->config->get('payment_dibseasy_language');
		}

                if(isset($this->request->post['dibseasy_debug_mode'])) {
                    $data['dibseasy_debug_mode'] = $this->request->post['dibseasy_debug_mode'];
                } else {
                    $data['dibseasy_debug_mode'] = $this->config->get('dibseasy_debug_mode');
                }

                if(isset($this->request->post['payment_dibseasy_otherpayment_button_url'])) {
                    $data['payment_dibseasy_otherpayment_button_url'] = $this->request->post['payment_dibseasy_otherpayment_button_url'];
                } else {
                    $data['payment_dibseasy_otherpayment_button_url'] = $this->config->get('payment_dibseasy_otherpayment_button_url');
                }

		$data['checkout_key_test'] = '';
                if(isset($this->error['checkout_key_test'])) {
                    $data['checkout_key_test'] = $this->error['checkout_key_test']; 
                }

                $data['checkout_key_live'] = '';
                if(isset($this->error['checkout_key_live'])) {
                    $data['checkout_key_live'] = $this->error['checkout_key_live']; 
                }

                $data['dibseasy_livekey_error'] = '';
                if(isset($this->error['dibseasy_livekey'])) {
                    $data['dibseasy_livekey_error'] = $this->error['dibseasy_livekey'];
                }

                $data['dibseasy_testkey_error'] = '';
                if(isset($this->error['dibseasy_testkey'])) {
                    $data['dibseasy_testkey_error'] = $this->error['dibseasy_testkey'];
                }

                $data['text_english'] = $this->language->get('text_english');
                $data['text_swedish'] = $this->language->get('text_swedish');
                $data['text_norwegian'] = $this->language->get('text_norwegian');
                $data['text_danish'] = $this->language->get('text_danish');

                $data['english_selected'] = '';
                $data['swedish_selected'] = '';
                $data['norwegian_select'] = '';
                $data['danish_select'] = '';

                if($this->config->get('payment_dibseasy_language') == 'sv-SE') {
                   $data['swedish_selected'] = 'selected="selected"';
                }

                if($this->config->get('payment_dibseasy_language') == 'en-GB') {
                   $data['english_selected'] = 'selected="selected"';
                }

                if($this->config->get('payment_dibseasy_language') == 'nb-NO') {
                   $data['norwegian_select'] = 'selected="selected"';
                }

                if($this->config->get('payment_dibseasy_language') == 'da-DK') {
                   $data['danish_select'] = 'selected="selected"';
                }

                if($this->config->get('payment_dibseasy_allowed_customer_type') == 'b2b') {
                    $data['b2c_selected'] = 'selected="selected"';
                }

                if($this->config->get('payment_dibseasy_allowed_customer_type') == 'b2b') {
                    $data['b2b_selected'] = 'selected="selected"';
                }

                if($this->config->get('payment_dibseasy_allowed_customer_type') == 'b2c_b2b_b2c') {
                    $data['b2c_b2b_b2c_selected'] = 'selected="selected"';
                }

                if($this->config->get('payment_dibseasy_allowed_customer_type') == 'b2b_b2c_b2b') {
                    $data['b2b_b2c_b2b_selected'] = 'selected="selected"';
                }

                if(isset($this->error['easy_term_and_conditions'])) {
                    $data['error_easy_term_and_conditions'] = $this->error['easy_term_and_conditions'];
                }

                $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/dibseasy', $data));
	}

	protected function validate() {
           	if (!$this->user->hasPermission('modify', 'extension/payment/dibseasy')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

                if (isset($this->request->post['dibseasy_checkoutkey']) && strlen(trim($this->request->post['dibseasy_checkoutkey'])) == 0) {
                    $this->error['checkout_key'] = $this->language->get('checkout_key'); // "Merchant id is required";
                }

                if(isset($this->request->post['payment_dibseasy_terms_and_conditions']) && !$this->request->post['payment_dibseasy_terms_and_conditions']) {
                    $this->error['easy_term_and_conditions'] = $this->language->get('entry_term_and_conditions_error');
                }

                if(isset($this->request->post['dibseasy_livekey']) && strlen(trim($this->request->post['dibseasy_livekey'])) == 0) {
                    $this->error['dibseasy_livekey'] = $this->language->get('entry_dibseasy_livekey_error');
                }
                if(isset($this->request->post['dibseasy_testkey']) && strlen(trim($this->request->post['dibseasy_testkey'])) == 0) {
                    $this->error['dibseasy_testkey'] = $this->language->get('entry_dibseasy_testkey_error');
                }
                return !$this->error ? true : false;
	}
}