<?php

$product_name = 'multicarriershipping'; // name should match with 'Software Title' configured in server, and it should not contains white space
$product_version = '1.3.10';
$product_slug = 'woocommerce-multi-carrier-shipping/woocommerce-multi-carrier-shipping.php'; //product base_path/file_name
$serve_url = 'https://www.xadapter.com/';
$plugin_settings_url = admin_url('admin.php?page=wc-settings&tab=shipping&section=wf_multi_carrier_shipping');

//include api manager
include_once ( 'wf_api_manager.php' );
new WF_API_Manager($product_name, $product_version, $product_slug, $serve_url, $plugin_settings_url);