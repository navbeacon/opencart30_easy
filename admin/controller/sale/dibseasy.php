<?php

class ControllerSaleDibseasy extends Controller {

    const ENDPOINT_TEST = 'https://test.api.dibspayment.eu/v1/payments/';
    const ENDPOINT_LIVE = 'https://api.dibspayment.eu/v1/payments/';
    const ENDPOINT_TEST_CHARGES = 'https://test.api.dibspayment.eu/v1/charges/';
    const ENDPOINT_LIVE_CHARGES = 'https://api.dibspayment.eu/v1/charges/';
    const RESPONSE_TYPE = "application/json";
 
    protected $paymentId;
    protected $orderId;
    public $logger;     
    protected $settings;

    public function __construct($registry) {  
        parent::__construct($registry);
		error_reporting(0);
        $this->logger = new Log('dibs.easy.log');
        $this->load->model('setting/setting');
        $this->settings = $this->model_setting_setting->getSetting('payment_dibseasy');
        $this->orderId = $this->request->get['order_id'];
        $PaymentId = $this->getPaymentId($this->orderId);
        if (!empty($PaymentId)) {
            $_SESSION['NetsPaymentID'] = $PaymentId;
        }
        //Handle charge and refund done from portal
        $this->managePortalChargeAndRefund();
        $this->load->model('user/user_group');
        $this->model_user_user_group->addPermission($this->user->getId(), 'access', 'sale/dibseasy');
        $this->model_user_user_group->addPermission($this->user->getId(), 'modify', 'sale/dibseasy');               
        // IF NOT EXISTS !!
        $result = $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "nets_payment (
		`id` int(10) unsigned NOT NULL auto_increment,		
		`payment_id` varchar(50) default NULL,
		`charge_id` varchar(50) default NULL,
		`product_ref` varchar(55) collate latin1_general_ci default NULL,
		`charge_qty` int(11) default NULL,
		`charge_left_qty` int(11) default NULL,
		`updated` int(2) unsigned default '0',
		`created` datetime NOT NULL,
		`timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`)
		)");
    }

    public function index() {
        $this->load->language('extension/payment/dibseasy');
        $this->document->setTitle($this->language->get('heading_title'));
        $data['txt'] = $this->language->all();
        $orderId = (int) $this->orderId;
        if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
            $data['base_url'] = HTTPS_CATALOG;
        } else {
            $data['base_url'] = HTTP_CATALOG;
        }
        $responseItems = $this->checkPartialItems($orderId);
        $_SESSION['responseItems'] = $responseItems;
        $data['responseItems'] = $_SESSION['responseItems'];

        $status = $this->is_easy($this->orderId);
        $data['status'] = $status;
        $data['paymentId'] = $this->paymentId;
        $data['oID'] = $this->orderId;
        $data['user_token'] = $this->request->get['user_token'];

        $api_return = $this->getCurlResponse($this->getApiUrl() . $this->paymentId, 'GET');
        $response = json_decode($api_return, true);
        $response['payment']['checkout'] = "";
        $data['apiGetRequest'] = "Api Get Request: " . print_r($response, true);
        $data['printResponseItems'] = "Response Items: " . print_r($responseItems, true);

        $data['debugMode'] = $this->settings['payment_dibseasy_backend_debug'];
        return $this->load->view('sale/dibseasy', $data);
    }

    /*
     * Function to fetch payment id from databse table oc_nets_payment
     * @param $order_id
     * @return nets payment id
     */

    public function getPaymentId($order_id) {
        $query = $this->db->query("SELECT custom_field FROM `" . DB_PREFIX . "order`  WHERE order_id = '" . (int) $order_id . "'");
        $this->paymentId = $query->row['custom_field'];
        return $this->paymentId;
    }

    /*
     * Function to get order items to pass capture, refund, cancel api
     * @param $orderid opencart order id alphanumeric
     * @return array order items and amount
     */

    public function getOrderItems($orderId) {
        //get order products
        $this->load->model('catalog/product');         
        $taxRateShipping = $order_total = 0;
        $product_query = $this->db->query(
                "SELECT product_id,model,name,price,tax,quantity FROM " . DB_PREFIX . "order_product  WHERE order_id = '" . (int) $orderId . "'"
        );
        if ($product_query->num_rows) {
            foreach ($product_query->rows as $prows) {
                //get product tax rate
                $productArr = $this->model_catalog_product->getProduct($prows['product_id']);
                $tax_query = $this->db->query("SELECT tx.name,tx.rate,tx.type FROM " . DB_PREFIX . "tax_rate as tx INNER JOIN " . DB_PREFIX . "tax_rule as tr ON tx.tax_rate_id = tr.tax_rate_id WHERE tr.tax_class_id = '" . (int) $productArr['tax_class_id'] . "'");
                $taxRate = $taxType = '';
                if ($tax_query->num_rows) {
                    $taxRate = $tax_query->row['rate'];
                    $taxType = $tax_query->row['type'];
                }
                $product = $prows['price'];               
                $product = $prows['price'] + $prows['tax']; // product price incl. VAT in DB format                             

                $quantity = (int) $prows['quantity'];
                $taxRateShipping = $tax = $taxRate; // Tax rate in DB format
                $taxFormat = '1' . str_pad(number_format((float) $tax, 2, '.', ''), 5, '0', STR_PAD_LEFT);
                $unitPrice = round(round(($product * 100) / $taxFormat, 2) * 100);
                $netAmount = round($quantity * $unitPrice);
                $grossAmount = round($quantity * ($product * 100));
                $taxAmount = $grossAmount - $netAmount;

                $taxRate = number_format($taxRate, 2) * 100;
                $itemsArray[] = array(
                    'reference' => $prows['model'],
                    'name' => $prows['name'],
                    'quantity' => $quantity,
                    'unit' => 'pcs',
                    'unitPrice' => $unitPrice,
                    'taxRate' => $taxRate,
                    'taxAmount' => $taxAmount,
                    'grossTotalAmount' => $grossAmount,
                    'netTotalAmount' => $netAmount
                );
            }
        }
        //shipping items
        $shippingCost = '';
        $order_query = $this->db->query(
                "SELECT title,value,code FROM " . DB_PREFIX . "order_total  WHERE order_id = '" . (int) $orderId . "'"
        );
        if ($order_query->num_rows) {
            foreach ($order_query->rows as $orows) {
                if ($orows['code'] == 'shipping' && $orows['value'] > 0) {
                    $shippingCost = $orows['value'];
                }
                if ($orows['code'] == 'total' && $orows['value'] > 0) {
                    $order_total = number_format($orows['value'], 2);
                }
            }
        }
        if (!empty($shippingCost)) {
            //easy calc method  
            $quantity = 1;
            $shipping = (isset($shippingCost)) ? $shippingCost : 0; // shipping price incl. VAT in DB format 
            $tax = (isset($taxRateShipping)) ? $taxRateShipping : 0; // Tax rate in DB format						
            if ($taxType == 'P') {
                $taxAmount = 1 + ($taxRateShipping / 100); // 1.25
                $shipping = $shipping * $taxAmount;
            }
            $taxFormat = '1' . str_pad(number_format((float) $tax, 2, '.', ''), 5, '0', STR_PAD_LEFT);
            $unitPrice = round(round(($shipping * 100) / $taxFormat, 2) * 100);
            $netAmount = round($quantity * $unitPrice);
            $grossAmount = round($quantity * ($shipping * 100));
            $taxAmount = $grossAmount - $netAmount;
            $itemsArray[] = array(
                'reference' => 'Shipping',
                'name' => 'Shipping',
                'quantity' => $quantity,
                'unit' => 'pcs',
                'unitPrice' => $unitPrice,
                'taxRate' => $tax * 100,
                'taxAmount' => $taxAmount,
                'grossTotalAmount' => $grossAmount,
                'netTotalAmount' => $netAmount
            );
        }
        // items total sum
        $itemsGrossPriceSumma = 0;
        foreach ($itemsArray as $total) {
            $itemsGrossPriceSumma += $total['grossTotalAmount'];
        }
        // compile datastring
        $data = array(
            'order' => array(
                'items' => $itemsArray,
                'amount' => $order_total,
                'currency' => $this->session->data['currency']
            )
        );
		
        return $data;
    }

    /*
     * Function to get list of partial charge/refund and reserved items list
     * @param order id
     * @return array of reserved, partial charged,partial refunded items
     */

    public function checkPartialItems($orderId) {
        $orderItems = $this->getOrderItems($orderId);
        $products = [];
        $chargedItems = [];
        $refundedItems = [];
        $cancelledItems = [];
        $failedItems = [];
        $itemsList = [];
        if (!empty($orderItems)) {
            foreach ($orderItems['order']['items'] as $items) {
                $products[$items['reference']] = array(
					'reference' => $items['reference'],
                    'name' => $items['name'],
                    'quantity' => (int) $items['quantity'],
                    'taxRate' => $items['taxRate'],
                    'netprice' => $items['unitPrice'] / 100
                );
            }
            if (isset($orderItems['order']['amount'])) {
                $lists['orderTotalAmount'] = $orderItems['order']['amount'];
            }
        }
		
        $api_return = $this->getCurlResponse($this->getApiUrl() . $this->paymentId, 'GET');
        $response = json_decode($api_return, true);

        if (!empty($response['payment']['charges'])) {
            $qty = 0;
            $netprice = 0;
            $grossprice = 0;

            foreach ($response['payment']['charges'] as $key => $values) {

                for ($i = 0; $i < count($values['orderItems']); $i ++) {

                    if (array_key_exists($values['orderItems'][$i]['reference'], $chargedItems)) {
                        $qty = $chargedItems[$values['orderItems'][$i]['reference']]['quantity'] + $values['orderItems'][$i]['quantity'];
                        $price = $chargedItems[$values['orderItems'][$i]['reference']]['grossprice']  + number_format((float) ($values['orderItems'][$i]['grossTotalAmount'] / 100), 2, '.', '');
                        $priceGross = $price / $qty;						
						//$priceGross = $price  ;
                        $netprice = $values['orderItems'][$i]['unitPrice'] * $qty;
                        $grossprice = $values['orderItems'][$i]['grossTotalAmount'] * $qty;
                        $chargedItems[$values['orderItems'][$i]['reference']] = array(
                            'reference' => $values['orderItems'][$i]['reference'],
                            'name' => $values['orderItems'][$i]['name'],
                            'quantity' => $qty,
                            'taxRate' => $values['orderItems'][$i]['taxRate'] / 100,
                            'grossprice' => $priceGross ,
                            'currency' => $response['payment']['orderDetails']['currency']
                        );
                    } else {
						$grossprice = $values['orderItems'][$i]['grossTotalAmount'] / 100;
						//For charge all
						$pquantity = '';
						foreach ($products as $key => $prod) { 
							if($prod['reference'] == $values['orderItems'][$i]['reference']){
								$pquantity = $prod['quantity'];
							}
						}
						if($pquantity == $values['orderItems'][$i]['quantity']){ 
							$priceOne = $values['orderItems'][$i]['grossTotalAmount'] / $values['orderItems'][$i]['quantity'];
							$grossprice = number_format((float) ($priceOne / 100), 2, '.', '');
						}
                        $chargedItems[$values['orderItems'][$i]['reference']] = array(
                            'reference' => $values['orderItems'][$i]['reference'],
                            'name' => $values['orderItems'][$i]['name'],
                            'quantity' => $values['orderItems'][$i]['quantity'],
                            'taxRate' => $values['orderItems'][$i]['taxRate'] / 100,
							'grossprice' => $grossprice,                           
                            'currency' => $response['payment']['orderDetails']['currency']
                        );
						 
                    }
                }
            }
        }
		 
        if (!empty($response['payment']['refunds'])) {
            $qty = 0;
            $netprice = 0;
            foreach ($response['payment']['refunds'] as $key => $values) {
                for ($i = 0; $i < count($values['orderItems']); $i ++) {
                    if (array_key_exists($values['orderItems'][$i]['reference'], $refundedItems)) {
                        $qty = $refundedItems[$values['orderItems'][$i]['reference']]['quantity'] + $values['orderItems'][$i]['quantity'];
						$netprice = $values['orderItems'][$i]['unitPrice'] * $qty;						
                        $grossprice = ($refundedItems[$values['orderItems'][$i]['reference']]['grossprice'] +  ($values['orderItems'][$i]['grossTotalAmount'] / 100 )) ;
                        $refundedItems[$values['orderItems'][$i]['reference']] = array(
                            'reference' => $values['orderItems'][$i]['reference'],
                            'name' => $values['orderItems'][$i]['name'],
                            'quantity' => $qty,
                            'grossprice' => number_format((float) (($grossprice )), 2, '.', ''),
                            'currency' => $response['payment']['orderDetails']['currency']
                        );
                    } else {
						$grossprice = $values['orderItems'][$i]['grossTotalAmount'] / 100;
						//For charge all
						$pquantity = '';
						foreach ($products as $key => $prod) { 
							if($prod['reference'] == $values['orderItems'][$i]['reference']){
								$pquantity = $prod['quantity'];
							}
						}
						if($pquantity == $values['orderItems'][$i]['quantity']){ 
							$priceOne = $values['orderItems'][$i]['grossTotalAmount'] / $values['orderItems'][$i]['quantity'];
							$grossprice = number_format((float) ($priceOne / 100), 2, '.', '');							
						}
						
                        $refundedItems[$values['orderItems'][$i]['reference']] = array(
                            'reference' => $values['orderItems'][$i]['reference'],
                            'name' => $values['orderItems'][$i]['name'],
                            'quantity' => $values['orderItems'][$i]['quantity'],
                            'grossprice' => $grossprice,
                            'currency' => $response['payment']['orderDetails']['currency']
                        );
                    }
                }
            }
        }
        if (isset($response['payment']['summary']['cancelledAmount'])) {
            foreach ($orderItems['order']['items'] as $items) {
                $cancelledItems[$items['reference']] = array(
                    'name' => $items['name'],
                    'quantity' => (int) $items['quantity'],
                    'netprice' => $items['unitPrice'] / 100
                );
            }
        }
        if (!isset($response['payment']['summary']['reservedAmount'])) {
            foreach ($orderItems['order']['items'] as $items) {
                $failedItems[$items['reference']] = array(
                    'name' => $items['name'],
                    'quantity' => (int) $items['quantity'],
                    'netprice' => $items['unitPrice'] / 100
                );
            }
        }
        // get list of partial charged items and check with quantity and send list for charge rest of items

        foreach ($products as $key => $prod) {
            if (array_key_exists($key, $chargedItems)) {
                $qty = $prod['quantity'] - $chargedItems[$key]['quantity'];
            } else {
                $qty = $prod['quantity'];
            }
            if (array_key_exists($key, $chargedItems) && array_key_exists($key, $refundedItems)) {
                if ($chargedItems[$key]['quantity'] == $refundedItems[$key]['quantity']) {
                    unset($chargedItems[$key]);
                }
            }

            if (array_key_exists($key, $chargedItems) && array_key_exists($key, $refundedItems)) {
                $qty = $chargedItems[$key]['quantity'] - $refundedItems[$key]['quantity'];
                if ($qty > 0)
                    $chargedItems[$key]['quantity'] = $qty;
            }
            if ($qty > 0) {
                $netprice = number_format((float) ($prod['netprice']), 2, '.', '');
                $grossprice = number_format((float) ($prod['netprice'] * ("1." . $prod['taxRate'])), 2, '.', '');
                $itemsList[] = array(
                    'name' => $prod['name'],
                    'reference' => $key,
                    'taxRate' => $prod['taxRate'] / 100,
                    'quantity' => $qty,
                    'netprice' => $netprice,
                    'grossprice' => $grossprice,
                    'currency' => $response['payment']['orderDetails']['currency']
                );
            }
            if ($chargedItems) {
                if ($chargedItems[$key]['quantity'] > $prod['quantity']) {
                    $chargedItems[$key]['quantity'] = $prod['quantity'];
                }
            }
        }
        $reserved = $charged = $cancelled = $refunded = '';
        if (isset($response['payment']['summary']['reservedAmount'])) {
            $reserved = $response['payment']['summary']['reservedAmount'];
        }
        if (isset($response['payment']['summary']['chargedAmount'])) {
            $charged = $response['payment']['summary']['chargedAmount'];
        }
        if (isset($response['payment']['summary']['cancelledAmount'])) {
            $cancelled = $response['payment']['summary']['cancelledAmount'];
        }
        if (isset($response['payment']['summary']['refundedAmount'])) {
            $refunded = $response['payment']['summary']['refundedAmount'];
        }

        if ($reserved != $charged && $reserved != $cancelled) {
            if (count($itemsList) > 0) {
                $lists['reservedItems'] = $itemsList;
            }
        }
        if (count($chargedItems) > 0 && $reserved === $charged) {
            $lists['chargedItems'] = $chargedItems;
        }
        if ($reserved != $charged && $reserved != $cancelled) {
            $lists['chargedItemsOnly'] = $chargedItems;
        }
        if (count($refundedItems) > 0) {
            $lists['refundedItems'] = $refundedItems;
        }
        if (count($cancelledItems) > 0) {
            $lists['cancelledItems'] = $itemsList;
        }
        if (count($failedItems) > 0) {
            $lists['failedItems'] = $itemsList;
        }
        return $lists;
    }

    /**
     * Function to check the nets payment status and display in admin order list backend page
     *
     * @return Payment Status
     */
    public function is_easy($orderId) {
        if (!empty($orderId)) {
            // Get order db status from orders_status_history if cancelled
            $orders_status_id = '';
            $order_query = $this->db->query(
                    "SELECT order_status_id FROM " . DB_PREFIX . "order_history WHERE order_id = '" . (int) $orderId . "' order by order_history_id desc limit 0,1"
            );
            if ($order_query->num_rows) {
                foreach ($order_query->rows as $orows) {
                    $orders_status_id = $orows['order_status_id'];
                }
                // if order is cancelled and payment is not updated as cancelled, call nets cancel payment api
                if ($orders_status_id == '7') {
                    $data = $this->getOrderItems($orderId);
                    // call cancel api here
                    $cancelUrl = $this->getVoidPaymentUrl($this->paymentId);
                    $cancelBody = [
                        'amount' => $data['order']['amount'] * 100,
                        'orderItems' => $data['order']['items']
                    ];
                    try {
                        $this->getCurlResponse($cancelUrl, 'POST', json_encode($cancelBody));
                    } catch (Exception $e) {
                        return $e->getMessage();
                    }
                }
            }
            try {
                // Get payment status from nets payments api
                $api_return = $this->getCurlResponse($this->getApiUrl() . $this->paymentId, 'GET');
                $response = json_decode($api_return, true);
            } catch (Exception $e) {
                return $e->getMessage();
            }
            $dbPayStatus = '';
            $paymentStatus = $cancelled = $reserved = $charged = $refunded = $pending = $chargeid = $chargedate = '';
            if (isset($response['payment']['summary']['cancelledAmount'])) {
                $cancelled = $response['payment']['summary']['cancelledAmount'];
            }
            if (isset($response['payment']['summary']['reservedAmount'])) {
                $reserved = $response['payment']['summary']['reservedAmount'];
            }
            if (isset($response['payment']['summary']['chargedAmount'])) {
                $charged = $response['payment']['summary']['chargedAmount'];
            }
            if (isset($response['payment']['summary']['refundedAmount'])) {
                $refunded = $response['payment']['summary']['refundedAmount'];
            }
            if (isset($response['payment']['refunds'][0]['state']) && $response['payment']['refunds'][0]['state'] == 'Pending') {
                $pending = "Pending";
            }

            $partialc = $reserved - $charged;
            $partialr = $reserved - $refunded;
            if (isset($response['payment']['charges'][0]['chargeId'])) {
                $chargeid = $response['payment']['charges'][0]['chargeId'];
            }
            if (isset($response['payment']['charges'][0]['created'])) {
                $chargedate = $response['payment']['charges'][0]['created'];
            }

            if ($reserved) {
                if ($cancelled) {
                    $langStatus = "cancel";
                    $paymentStatus = "Canceled";
                    $dbPayStatus = 1; // For payment status as cancelled in oc_nets_payment db table
                } elseif ($charged && $pending !== 'Pending') {

                    if ($reserved != $charged) {
                        $paymentStatus = "Partial Charged";
                        $langStatus = "partial_charge";
                        $dbPayStatus = 3; // For payment status as Partial Charged in oc_nets_payment db table                         
                    } else if ($refunded) {
                        if ($reserved != $refunded) {
                            $paymentStatus = "Partial Refunded";
                            $langStatus = "partial_refund";
                            $dbPayStatus = 5; // For payment status as Partial Charged in oc_nets_payment db table                             
                        } else {
                            $paymentStatus = "Refunded";
                            $langStatus = "refunded";
                            $dbPayStatus = 6; // For payment status as Refunded in oc_nets_payment db table
                        }
                    } else {
                        $paymentStatus = "Charged";
                        $langStatus = "charged";
                        $dbPayStatus = 4; // For payment status as Charged in oc_nets_payment db table
                    }
                } else if ($pending) {
                    $paymentStatus = "Refund Pending";
                    $langStatus = "refund_pending";
                } else {
                    $paymentStatus = 'Reserved';
                    $langStatus = "reserved";
                    $dbPayStatus = 2; // For payment status as Authorized in oc_nets_payment db table
                }
            } else {
                $paymentStatus = "Failed";
                $langStatus = "failed";
                $dbPayStatus = 0; // For payment status as Failed in oc_nets_payment db table
            }

            return array(
                'payStatus' => $paymentStatus,
                'langStatus' => $langStatus
            );
        }
    }

    /*
     * Function to capture nets transaction - calls charge API
     * redirects to admin overview listing page
     */

    public function charge() {
        $orderid = $_REQUEST['orderid'];
        $ref = $_REQUEST['reference'];
        $name = $_REQUEST['name'];
        $chargeQty = $_REQUEST['single'];
        $unitPrice = $_REQUEST['price'];
        $taxRate = (int) $_REQUEST['taxrate'];
        $payment_id = $this->getPaymentId($orderid);
        $data = $this->getOrderItems($orderid);
        // call charge api here
        $chargeUrl = $this->getChargePaymentUrl($payment_id);
        if (isset($ref) && isset($chargeQty)) {
            $totalAmount = 0;
            foreach ($data['order']['items'] as $key => $value) {
                if (in_array($ref, $value) && $ref === $value['reference']) {
                    $unitPrice = $value['unitPrice'];
                    $taxAmountPerProduct = $value['taxAmount'] / $value['quantity'];
                    $value['taxAmount'] = $taxAmountPerProduct * $chargeQty;
                    $netAmount = $chargeQty * $unitPrice;
                    $grossAmount = $netAmount + $value['taxAmount'];
                    $value['quantity'] = $chargeQty;
                    $value['netTotalAmount'] = $netAmount;
                    $value['grossTotalAmount'] = $grossAmount;
                    $itemList[] = $value;
                    $totalAmount += $grossAmount;
                }
            }
            $body = [
                'amount' => $totalAmount,
                'orderItems' => $itemList
            ];
        } else {
            $body = [
                'amount' => $data['order']['amount'] * 100,
                'orderItems' => $data['order']['items']
            ];
        }
        $api_return = $this->getCurlResponse($chargeUrl, 'POST', json_encode($body));
        $response = json_decode($api_return, true);
        $this->logger->write("Nets_Order_Overview getorder charge" . $api_return, 'nets');
        //save charge details in db for partial refund
        if (isset($ref) && isset($response['chargeId'])) {
            $charge_query = "insert into " . DB_PREFIX . "nets_payment (`payment_id`, `charge_id`,  `product_ref`, `charge_qty`, `charge_left_qty`,`created`) "
                    . "values ('" . $this->paymentId . "', '" . $response['chargeId'] . "', '" . $ref . "', '" . $chargeQty . "', '" . $chargeQty . "',now())";
            $this->db->query($charge_query);
        } else {
            if (isset($response['chargeId'])) {
                foreach ($data['order']['items'] as $key => $value) {
                    $charge_query = "insert into " . DB_PREFIX . "nets_payment (`payment_id`, `charge_id`,  `product_ref`, `charge_qty`, `charge_left_qty`,`created`) "
                            . "values ('" . $this->paymentId . "', '" . $response['chargeId'] . "', '" . $value['reference'] . "', '" . $value['quantity'] . "', '" . $value['quantity'] . "',now())";
                    $this->db->query($charge_query);
                }
            }
        }
        $this->response->redirect($this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $orderid, true));
    }

    /*
     * Function to refund nets transaction - calls Refund API
     * redirects to admin overview listing page
     */

    public function refund() {
        $orderid = $_REQUEST['orderid'];
        $ref = $_REQUEST['reference'];
        $name = $_REQUEST['name'];
        $refundQty = $_REQUEST['single'];
        $taxRate = (int) $_REQUEST['taxrate'];
        $data = $this->getOrderItems($orderid);
        $api_return = $this->getCurlResponse($this->getApiUrl() . $this->getPaymentId($orderid), 'GET');
        $chargeResponse = json_decode($api_return, true);
        $refundEachQtyArr = array();
        $breakloop = $refExist = false;
        //For partial refund if condition
        if (isset($ref) && isset($refundQty)) {
            foreach ($chargeResponse['payment']['charges'] as $ky => $val) {
                foreach ($val['orderItems'] as $arr) {
                    if ($ref == $arr['reference']) {
                        $refExist = true;
                    }
                }
                if ($refExist) {
                    //from charge tabe deside charge id for refund						
                    $charge_query = $this->db->query(
                            "SELECT `payment_id`, `charge_id`,  `product_ref`, `charge_qty`, `charge_left_qty` FROM " . DB_PREFIX . "nets_payment WHERE payment_id = '" . $this->paymentId . "' AND charge_id = '" . $val['chargeId'] . "' AND product_ref = '" . $ref . "' AND charge_left_qty !=0"
                    );
                    if ($charge_query->num_rows) {
                        foreach ($charge_query->rows as $crows) {
                            $table_charge_left_qty = $refundEachQtyArr[$val['chargeId']] = $crows['charge_left_qty'];
                        }
                    }
                    if ($refundQty <= array_sum($refundEachQtyArr)) {
                        $leftqtyFromArr = array_sum($refundEachQtyArr) - $refundQty;
                        $leftqty = $table_charge_left_qty - $leftqtyFromArr;
                        $refundEachQtyArr[$val['chargeId']] = $leftqty;
                        $breakloop = true;
                    }
                    if ($breakloop) {
                        foreach ($refundEachQtyArr as $key => $value) {
                            $body = $this->getItemForRefund($ref, $value, $data);
                            $refundUrl = $this->getRefundPaymentUrl($key);
                            $this->getCurlResponse($refundUrl, 'POST', json_encode($body));
                            $this->logger->write("Nets_Order_Overview getorder refund" . json_encode($body), 'nets');
                            //update for left charge quantity
                            $singlecharge_query = $this->db->query(
                                    "SELECT  `charge_left_qty` FROM " . DB_PREFIX . "nets_payment WHERE payment_id = '" . $this->paymentId . "' AND charge_id = '" . $key . "' AND product_ref = '" . $ref . "' AND charge_left_qty !=0 "
                            );
                            if ($singlecharge_query->num_rows) {
                                foreach ($singlecharge_query->rows as $scrows) {
                                    $charge_left_qty = $scrows['charge_left_qty'];
                                }
                            }
                            $charge_left_qty = $value - $charge_left_qty;
                            if ($charge_left_qty < 0) {
                                $charge_left_qty = -$charge_left_qty;
                            }
                            $qresult = $this->db->query(
                                    "UPDATE " . DB_PREFIX . "nets_payment SET charge_left_qty = $charge_left_qty WHERE payment_id = '" . $this->paymentId . "' AND charge_id = '" . $key . "' AND product_ref = '" . $ref . "'"
                            );
                        }
                        break;
                    }
                }
            }
        } else {
            //update for left charge quantity
            foreach ($chargeResponse['payment']['charges'] as $ky => $val) {
                $itemsArray = array();
                foreach ($val['orderItems'] as $key => $value) {
                    $itemsArray[] = array(
                        'reference' => $value['reference'],
                        'name' => $value['name'],
                        'quantity' => $value['quantity'],
                        'unit' => 'pcs',
                        'unitPrice' => $value['unitPrice'],
                        'taxRate' => $value['taxRate'],
                        'taxAmount' => $value['taxAmount'],
                        'grossTotalAmount' => $value['grossTotalAmount'],
                        'netTotalAmount' => $value['netTotalAmount'],
                    );
                    $qresult = $this->db->query(
                            "UPDATE " . DB_PREFIX . "nets_payment SET charge_left_qty = 0 WHERE payment_id = '" . $this->paymentId . "' AND charge_id = '" . $val['chargeId'] . "' AND product_ref = '" . $value['reference'] . "'"
                    );
                }
                $itemsGrossPriceSumma = 0;
                foreach ($itemsArray as $total) {
                    $itemsGrossPriceSumma += $total['grossTotalAmount'];
                }
                $body = [
                    'amount' => $itemsGrossPriceSumma,
                    'orderItems' => $itemsArray
                ];
                //For Refund all				                
                $refundUrl = $this->getRefundPaymentUrl($val['chargeId']);
                $api_return = $this->getCurlResponse($refundUrl, 'POST', json_encode($body));
                $response = json_decode($api_return, true);
                $this->logger->write("Nets_Order_Overview getorder refund" . $api_return, 'nets');
            }
        }
        $this->response->redirect($this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $orderid, true));
    }

    /* Get order Items to refund and pass them to refund api */

    public function getItemForRefund($ref, $refundQty, $data) {
        $totalAmount = 0;
        foreach ($data['order']['items'] as $key => $value) {
            if (in_array($ref, $value) && $ref === $value['reference']) {
                $unitPrice = $value['unitPrice'];
                $taxAmountPerProduct = $value['taxAmount'] / $value['quantity'];

                $value['taxAmount'] = $taxAmountPerProduct * $refundQty;
                $netAmount = $refundQty * $unitPrice;
                $grossAmount = $netAmount + $value['taxAmount'];

                $value['quantity'] = $refundQty;
                $value['netTotalAmount'] = $netAmount;
                $value['grossTotalAmount'] = $grossAmount;

                $itemList[] = $value;
                $totalAmount += $grossAmount;
            }
        }
        $body = [
            'amount' => $totalAmount,
            'orderItems' => $itemList
        ];
        return $body;
    }

    /*
     * Function to cancel nets transaction - calls Cancel API
     * redirects to admin overview listing page
     */

    public function cancel() {
        $orderid = $_REQUEST['orderid'];
        $data = $this->getOrderItems($orderid);
        $payment_id = $this->getPaymentId($orderid);
        // call cancel api here
        $cancelUrl = $this->getVoidPaymentUrl($payment_id);
        $body = [
            'amount' => $data['order']['amount'] * 100,
            'orderItems' => $data['order']['items']
        ];
        $api_return = $this->getCurlResponse($cancelUrl, 'POST', json_encode($body));
        $response = json_decode($api_return, true);
        $this->response->redirect($this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $orderid, true));
    }

    public function getCurlResponse($url, $method = "POST", $bodyParams = NULL) {
        $result = '';
        // initiating curl request to call api's
        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, $this->getHeaders());
        if ($method == "POST" || $method == "PUT") {
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, $bodyParams);
        }
        $result = curl_exec($oCurl);
        $info = curl_getinfo($oCurl);
        switch ($info['http_code']) {
            case 401:
                $error_message = 'NETS Easy authorization failed. Check your secret/checkout keys';
                $this->logger->write($error_message, 'nets');
                break;
            case 400:
                $error_message = 'NETS Easy Bad request: Please check request params/headers ';
                $this->logger->write($error_message, 'nets');
                break;
            case 402:
                $error_message = 'Payment Required';
                $this->logger->write($error_message, 'nets');
                break;
            case 500:
                $error_message = 'Unexpected error';
                $this->logger->write($error_message, 'nets');
                break;
        }
        if (!empty($error_message)) {
            $this->logger->write($error_message, 'nets');
        }
        curl_close($oCurl);
        return $result;
    }

    /*
     * Function to fetch charge id from database table oc_nets_payment
     * @param $orderid
     * @return nets charge id
     */

    private function getChargeId($orderid) {
        $api_return = $this->getCurlResponse($this->getApiUrl() . $this->getPaymentId($orderid), 'GET');
        $response = json_decode($api_return, true);
        return $response['payment']['charges'][0]['chargeId'];
    }

    /*
     * Function to fetch payment api url
     *
     * @return payment api url
     */

    public function getApiUrl() {
        if ($this->settings['payment_dibseasy_testmode']) {
            return self::ENDPOINT_TEST;
        } else {
            return self::ENDPOINT_LIVE;
        }
    }

    public function getResponse($oder_id) {
        $api_return = $this->getCurlResponse($this->getApiUrl() . $this->getPaymentId($oder_id), 'GET');
        $response = json_decode($api_return, true);
        $result = json_encode($response, JSON_PRETTY_PRINT);
        return $result;
    }

    /*
     * Function to fetch headers to be passed in guzzle http request
     * @return headers array
     */

    private function getHeaders() {
        return [
            "Content-Type: " . self::RESPONSE_TYPE,
            "Accept: " . self::RESPONSE_TYPE,
            "Authorization: " . $this->getSecretKey()
        ];
    }

    /*
     * Function to fetch secret key to pass as authorization
     * @return secret key
     */

    public function getSecretKey() {
        if ($this->settings['payment_dibseasy_testmode']) {
            $secretKey = $this->settings['payment_dibseasy_testkey'];
        } else {
            $secretKey = $this->settings['payment_dibseasy_livekey'];
        }
        return $secretKey;
    }

    /*
     * Function to fetch charge api url
     * @param $paymentId
     * @return charge api url
     */

    public function getChargePaymentUrl($paymentId) {
        return ($this->settings['payment_dibseasy_testmode'] ) ? self::ENDPOINT_TEST . $paymentId . '/charges' : self::ENDPOINT_LIVE . $paymentId . '/charges';
    }

    /*
     * Function to fetch cancel api url
     * @param $paymentId
     * @return cancel api url
     */

    public function getVoidPaymentUrl($paymentId) {
        return ($this->settings['payment_dibseasy_testmode'] ) ? self::ENDPOINT_TEST . $paymentId . '/cancels' : self::ENDPOINT_LIVE . $paymentId . '/cancels';
    }

    /*
     * Function to fetch refund api url
     * @param $chargeId
     * @return refund api url
     */

    public function getRefundPaymentUrl($chargeId) {
        return ($this->settings['payment_dibseasy_testmode'] ) ? self::ENDPOINT_TEST_CHARGES . $chargeId . '/refunds' : self::ENDPOINT_LIVE_CHARGES . $chargeId . '/refunds';
    }

    /*
     * Function to manage portal charge, refund and auto-charge in admin  
     * @param $this->paymentId	
     * @return null
     */

    public function managePortalChargeAndRefund() {
        //get reqeust response
        $api_return = $this->getCurlResponse($this->getApiUrl() . $this->paymentId, 'GET');
        $response = json_decode($api_return, true);

        if (!empty($response['payment']['charges'])) {

            foreach ($response['payment']['charges'] as $key => $values) {
                $charge_query = $this->db->query(
                        "SELECT `charge_id` FROM " . DB_PREFIX . "nets_payment WHERE payment_id = '" . $this->paymentId . "' AND charge_id = '" . $values['chargeId'] . "' "
                );
                if (empty($charge_query->num_rows)) {
                    for ($i = 0; $i < count($values['orderItems']); $i ++) {
                        $charge_iquery = "insert into " . DB_PREFIX . "nets_payment (`payment_id`, `charge_id`,  `product_ref`, `charge_qty`, `charge_left_qty`,`created`) "
                                . "values ('" . $this->paymentId . "', '" . $values['chargeId'] . "', '" . $values['orderItems'][$i]['reference'] . "', '" . $values['orderItems'][$i]['quantity'] . "', '" . $values['orderItems'][$i]['quantity'] . "',now())";
                        $this->db->query($charge_iquery);
                    }
                }
            }
        }
    }

}
