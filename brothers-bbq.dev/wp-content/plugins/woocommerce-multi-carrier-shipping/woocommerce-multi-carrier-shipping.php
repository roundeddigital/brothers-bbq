<?php
/*
	Plugin Name: Multi-Carrier Shipping Plugin for WooCommerce
	Plugin URI: https://www.xadapter.com/product/multiple-carrier-shipping-plugin-woocommerce/
	Description: Intuitive Rule Based Multi-Carrier Shipping Plugin for WooCommerce. Set shipping rates based on rules based by Country, State, Post Code, Product Category,Shipping Class ,Weight , Shipping Company and Shipping Service.
	Version: 1.3.10
	Author: xadapter
	Author URI: https://www.xadapter.com
	Copyright: 2016-2017 XAdapter.
	WC requires at least: 2.6.0
	WC tested up to: 3.2.5
 */
        if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }
$GLOBALS['eha_API_URL']="http://shippingapi.storepep.com";    
//$GLOBALS['eha_API_URL']="http://localhost:3000";    
load_plugin_textdomain( 'eha_multi_carrier_shipping', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

if (check_if_woocommerce_active()===true) {	
//include_once ( 'includes/class-eha-shipping-ups-admin.php' );
    include( 'eha-multi-carrier-shipping-common.php' );
	if ( is_admin() ) {
		include_once ( 'includes/wf_api_manager/wf-api-manager-config.php' );
                                        }
   
    if (!function_exists('wf_plugin_configuration_mcp')){
       function wf_plugin_configuration_mcp(){
            return array(
                'id' => 'wf_multi_carrier_shipping',
                'method_title' => __('Multi Carrier Shipping', 'eha_multi_carrier_shipping' ),
                'method_description' => __('<strong>*Note: These fields are mandatory - Email ID, API Key, Shipper Settings, Carrier Settings</strong>', 'eha_multi_carrier_shipping' ));		
        }
    }

}

function check_if_woocommerce_active()
{
    $act=get_option( 'active_plugins' );
    foreach($act as $pname)
    {
        if (strpos($pname, 'woocommerce.php') !== false)
        {
            return true;
        }
    }
    return false;
}