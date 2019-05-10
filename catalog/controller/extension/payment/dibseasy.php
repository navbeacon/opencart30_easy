<?php
class ControllerExtensionPaymentDibseasy extends Controller {
	public function index() {
          	$data['button_confirm'] = $this->language->get('button_confirm');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['continue'] = $this->url->link('checkout/success');
   		return $this->load->view('extension/payment/dibseasy', $data);
	}
        protected $logger;

        public function __construct($registry) {
            parent::__construct($registry);
            $this->logger = new Log('dibs.easy.log');
         }

	public function confirm() {
             if ($this->validateRequest()) {
                       $this->load->model('extension/payment/dibseasy');
                       $response = $this->model_extension_payment_dibseasy->getTransactionInfo(($this->extractPaymentId()));
                       $this->session->data['dibseasy_transaction'] = $this->extractPaymentId();
                         if(isset($response->payment->paymentDetails->paymentType) && $response->payment->paymentDetails->paymentType) {
                            $this->model_extension_payment_dibseasy->createOrder();
                            if($this->config->get('payment_dibseasy_language') == 'sv-SE') {
                                $paymentType = 'Betalnings typ';
                                $paymentMethod = 'Betalningsmetod';
                                $transactionId = 'Betalnings ID';
                                $cardNumberPostfix = 'Kreditkort de sista 4 siffrorna';
                            } else {
                                $paymentType = 'Payment type';
                                $paymentMethod = 'Payment Method';
                                $transactionId = 'Payment ID';
                                $cardNumberPostfix = 'Credit card last 4 digits';
                            }
                            $transactDetails = "$transactionId: <b>{$response->payment->paymentId}</b> <br>"
                                             . "$paymentType:   <b>{$response->payment->paymentDetails->paymentType}</b> <br>"
                                             . "$paymentMethod: <b>{$response->payment->paymentDetails->paymentMethod}</b> <br>";

                             if(isset($response->payment->paymentDetails->cardDetails->maskedPan)) {
                                 $maskedCardNumber = $response->payment->paymentDetails->cardDetails->maskedPan;
                                $cardPostfix = substr($maskedCardNumber, -4);
                                $transactDetails .= "$cardNumberPostfix: <b>$cardPostfix</b>";
                             }
                            $this->load->model('checkout/order');
                            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_dibseasy_order_status_id'), $transactDetails, true);
                            unset($this->session->data['dibseasy']['paymentid']);
                            $this->response->redirect($this->url->link('checkout/dibseasy_success', '', true));
                        } else {
                            $this->logger->write('-===============Error during finishiong order==============-----');
                            $this->logger->write('Transactionid: ' . $_GET['paymentId']);
                            $this->logger->write('Order was not registered in Opencart');
                            $this->logger->write('Orderid: ' . $this->session->data['order_id']);
                            $this->logger->write('You can fing order details in DB table: `' . DB_PREFIX . 'order`');
                            $this->logger->write('================================================================');
                            $this->response->redirect($this->url->link('checkout/dibseasy', '', true));
                        }
        	} else {
                    $this->response->redirect($this->url->link('checkout/dibseasy', '', true));
                }
   	}

        public function redirect() {
             $this->load->model('extension/payment/dibseasy');
             $paymentid = $this->model_extension_payment_dibseasy->getPaymentId();
             if($paymentid) {
                $transaction = $this->model_extension_payment_dibseasy->getTransactionInfo($paymentid);
         	$json['redirect'] = $transaction->payment->checkout->url;
             } else {
                $this->session->data['error'] = 'Error during payment initialization, try anoter payment method';
                $json['error'] = 1;
             }
             $this->response->addHeader('Content-Type: application/json');
          $this->response->setOutput(json_encode($json));
        }

        private function validateRequest() {
             if (isset($this->session->data['payment_method']['code'])
                       && $this->session->data['payment_method']['code'] == 'dibseasy'
                       && !empty($this->extractPaymentId())) {
                 return true;
             }
             return false;
        }

        private function extractPaymentId()
        {
            $result = null;
            if(isset($this->request->get['paymentid'])) {
                $result = $this->request->get['paymentid'];
            }
            if(isset($this->request->get['paymentId'])) {
                $result = $this->request->get['paymentId'];
            }
            return $result;
        }
}
