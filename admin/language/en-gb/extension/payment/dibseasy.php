<?php

// Heading
$_['heading_title'] = 'Easy Checkout';

// Text
$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Success: You have modified NETS Easy payment module!';
$_['text_edit'] = 'Edit NETS Easy';
$_['text_dibseasy'] = '<a href= "http://www.dibs.se/" target="_blank"><img alt="Dibs" src="view/image/payment/dibs.png" style="width: 86px; height: 30px;"></a>';

// Entry
$_['entry_total'] = 'Total';
$_['entry_order_status'] = 'Order Status';
$_['entry_geo_zone'] = 'Geo Zone';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Sort Order';
$_['entry_debug'] = 'Debug mode';
$_['entry_testmode'] = 'Test';
$_['entry_checkout_type'] = 'Checkout type';
$_['entry_dibseasy_livekey'] = 'Secret key';
$_['entry_dibseasy_testkey'] = 'Test Secret key';
$_['entry_dibseasy_checkoutkey_test'] = 'Test Checkout key';

$_['entry_shipping_method_description'] = 'It is possible to use only 2 types of shipping methods';
$_['entry_testmode_description'] = 'Set it depending on live or test secret key';
$_['entry_debug_description'] = 'Debug info will be written in the log file: system/storage/logs/dibs.easy.log';
$_['entry_language'] = 'Language';
$_['entry_dibseasy_terms_and_conditions'] = ' Terms & conditions';
$_['entry_allowed_customer_type'] = 'Allowed customer type';
$_['entry_dibseasy_sort_order'] = 'Sort order';

$_['text_english'] = 'English';
$_['text_swedish'] = 'Swedish';
$_['text_norwegian'] = 'Norwegian';
$_['text_danish'] = 'Danish';

$_['text_b2c'] = 'B2C only';
$_['text_b2b'] = 'B2B only';
$_['text_b2c_b2b_b2c'] = 'B2C & B2B (defaults to B2C)';
$_['text_b2b_b2c_b2b'] = 'B2B & B2C (defaults to B2B)';
$_['text_checkout_type_hosted'] = 'Payment Window';
$_['text_checkout_type_embedded'] = 'Embedded';
// Help
$_['help_total'] = 'The checkout total the order must reach before this payment method becomes active.';

// Error
$_['entry_dibseasy_merchant'] = 'NETS Merchant ID';
$_['entry_dibseasy_checkoutkey'] = 'Test Checkout key';
$_['entry_dibseasy_checkoutk_live'] = 'Checkout key';
$_['entry_shipping_method'] = 'Shipping method:';
$_['text_free_shipping'] = 'Free shipping';
$_['text_flat_shipping'] = 'Flat shipping';
$_['error_merchant'] = 'Merchant is required';
$_['checkout_key'] = 'Checkout key is required';
$_['entry_dibseasy_livekey_error'] = 'Please fill live secret key';
$_['entry_dibseasy_testkey_error'] = 'Please fill test secret key';
$_['entry_term_and_conditions_error'] = 'Please add terms and conditions';
$_['entry_dibseasy_autocapture'] = 'Auto Capture';
$_['entry_dibseasy_merchant_terms_and_conditions'] = ' Merchant Terms & conditions';
$_['entry_merchant_term_and_conditions_error'] = 'Please add merchant terms and conditions';

$_['entry_dibseasy_wb_url'] = ' Webhook URL';
$_['entry_dibseasy_wb_url_error'] = 'Please add webhook URL';
$_['entry_dibseasy_wb_auth'] = ' Webhook Authorization';
$_['entry_dibseasy_wb_auth_error'] = 'Please add webhook authorization';
$_['entry_dibseasy_frontend_debug'] = ' Frontend Debug Mode';
$_['entry_dibseasy_backend_debug'] = ' Backend Debug Mode';

//For Nets Easy Partial Payment
$_['configuration_heading'] = 'Nets Easy Configuration';
$_['credentials'] = 'API Credentials';
$_['live_secret_key'] = 'Live Secret Key';
$_['live_checkout_key'] = 'Live Checkout Key';
$_['test_secret_key'] = 'Test Secret Key';
$_['test_checkout_key'] = 'Test Checkout Key';
$_['live_secret_placeholder'] = 'live-secret-key-00000000000000000000000000000000';
$_['live_checkout_placeholder'] = 'live-checkout-key-00000000000000000000000000000000';
$_['test_secret_placeholder'] = 'test-secret-key-00000000000000000000000000000000';
$_['test_checkout_placeholder'] = 'test-checkout-key-00000000000000000000000000000000';
$_['settings'] = 'Settings';
$_['checkout_mode'] = 'Checkout Mode';
$_['mode_live'] = 'Live';
$_['mode_test'] = 'Test';
$_['checkout_flow'] = 'Checkout Flow';
$_['mode_redirect'] = 'Hosted Payment Page';
$_['mode_embedded'] = 'Embedded Checkout';
$_['terms_url'] = 'Terms & Conditions URL';
$_['terms_url_placeholder'] = 'Insert your Terms & Conditions URL here';
$_['merchant_url'] = 'Merchant Terms URL';
$_['merchant_url_placeholder'] = 'Insert your Merchant Terms URL here';
$_['auto_capture'] = 'Auto Capture';
$_['misc'] = 'Misc.';
$_['wb_url'] = 'Webhook URL';
$_['wb_url_placeholder'] = 'https://example.com/shop.php?do] =NetsEasyWebhook';
$_['wb_auth'] = 'Webhook Auth';
$_['wb_auth_placeholder'] = 'AZ-1234567890-az';
$_['icon_bar'] = 'Icon Bar';
$_['icon_bar_placeholder'] = 'Insert your Icon Bar URL here';
$_['debug_mode'] = 'Debug Mode';
$_['config_save'] = 'save configuration';
$_['configuration_saved'] = 'Configuration saved';
$_['mode_frontend'] = 'Frontend mode';
$_['mode_backend'] = 'Backend mode';
$_['mode_none'] = 'Select Mode Option';
$_['info'] = 'Information';
$_['version'] = 'Ver.';
$_['portal'] = 'Portal';
$_['easy_portal'] = 'Nets Easy Portal';
$_['website'] = 'Website';
$_['support'] = 'Support';
$_['account'] = 'Get an unlimited and free test account within minutes ';
$_['account_link'] = 'Here';

$_['tooltip_apikeys'] = 'Log in to your Nets Easy account and navigate to :: Company > Integration';
$_['tooltip_apikeys_url'] = 'Nets Easy portal';
$_['tooltip_checkoutmode'] = 'Choose between Test Sandbox or Live production mode';
$_['tooltip_checkoutflow'] = 'Choose between redirect Hosted Payment Page or a nested Embedded Checkout';
$_['tooltip_termsurl'] = 'Please insert the full url to your terms and conditions page';
$_['tooltip_merchanturl'] = 'Please insert the full url to your Privacy Policy page';
$_['tooltip_autocapture'] = 'Auto capture allows you to instant charge your orders upon succesful payment reservation';
$_['tooltip_wb_url'] = 'Webhook URL is set to Nets custom endpoint. Live mode (Production) requires your site to run on SSL.';
$_['tooltip_wb_auth'] = 'Set your Webhook Authorization Key here. Key must be between 8-64 characters. Key can only consist of [A-Z]-[a-z]-[0-9]. Set value to 0 (zero) to turn OFF webhook functionality.';
$_['tooltip_iconbar'] = 'This link loads in a set of payment icons displayed on Nets Easy payment method during checkout. You can generate a custom set';
$_['tooltip_iconbar_link'] = 'here';
$_['tooltip_debug'] = 'When activating Debug mode; Hidden Data will be displayed. This can be emailed to our support to quickly find root cause in case of transaction fails';

$_['not_installed'] = 'Please install our plugin before you can configure the plugin settings';
$_['install_link'] = 'Install plugin';
$_['nets_charge_all'] = 'Charge All';
$_['nets_charge'] = 'Charge';
$_['nets_refund'] = 'Refund';
$_['nets_refund_all'] = 'Refund All';
$_['nets_cancel_payment'] = 'Cancel';
$_['nets_payment_status']='Payment Status';
$_['nets_payment_id']='Payment ID';
$_['nets_quantity']='Quantity';
$_['nets_model']='Model';
$_['nets_product']='Product';
$_['nets_price']='Price';
$_['nets_action']='action';
$_['nets_charged_products']='charged products';
$_['nets_payment_refund_pending']='refund pending';
$_['nets_payment_refunded']='refunded';
$_['nets_payment_cancelled']='cancelled';
$_['nets_refunded_products']='refunded products';
$_['nets_payment_failed']='failed';
$_['nets_payment_reserved']='payment reserved';











