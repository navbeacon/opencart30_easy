<?php

class ControllerExtensionPaymentDibseasy extends Controller {

    public function index() {
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['text_loading'] = $this->language->get('text_loading');
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
            if (!empty($response->payment->paymentDetails->paymentType)) {
                $this->model_extension_payment_dibseasy->createOrder();
                if ($this->config->get('payment_dibseasy_language') == 'sv-SE') {
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

                if (isset($response->payment->paymentDetails->cardDetails->maskedPan)) {
                    $maskedCardNumber = $response->payment->paymentDetails->cardDetails->maskedPan;
                    $cardPostfix = substr($maskedCardNumber, -4);
                    $transactDetails .= "$cardNumberPostfix: <b>$cardPostfix</b>";
                }
                $this->load->model('checkout/order');
                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_dibseasy_order_status_id'), $transactDetails, true);
                //save payment id into order table.custom_field
                //$this->db->query("UPDATE " . DB_PREFIX . "order SET custom_field = '" . $this->session->data['dibseasy']['paymentid'] . "' WHERE order_id = '" . $this->session->data['order_id'] . "'");
                unset($this->session->data['dibseasy']['paymentid']);

                $this->response->redirect($this->url->link('checkout/dibseasy_success', '', true));
            } else {
                $this->response->redirect($this->url->link('checkout/dibseasy', '', true));
            }
        } else {
            $this->response->redirect($this->url->link('checkout/cart', '', true));
        }
    }

    public function redirect() {
        $this->load->model('extension/payment/dibseasy');
        $this->load->language('checkout/dibseasy');
        $paymentid = $this->model_extension_payment_dibseasy->getPaymentId();

        if (!is_null($paymentid)) {
            $transaction = $this->model_extension_payment_dibseasy->getTransactionInfo($paymentid);
            $json['redirect'] = $transaction->payment->checkout->url . '&paymentid=' . $paymentid . '&language=' . $this->config->get('payment_dibseasy_language');
        } else {
            $this->session->data['error'] = $this->language->get('dibseasy_checkout_redirect_error');
            $json['error'] = 1;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function validateRequest() {
        $paymentId = $this->extractPaymentId();
        return !empty($paymentId) ? true : false;
    }

    private function extractPaymentId() {
        $result = null;
        if (isset($this->request->get['paymentid'])) {
            $result = $this->request->get['paymentid'];
        }
        if (isset($this->request->get['paymentId'])) {
            $result = $this->request->get['paymentId'];
        }

        return $result;
    }

}
