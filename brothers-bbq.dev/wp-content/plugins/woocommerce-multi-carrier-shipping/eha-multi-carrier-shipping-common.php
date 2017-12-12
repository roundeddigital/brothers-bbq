<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }



    if (!function_exists('wf_get_settings_url_mcp')){
		function wf_get_settings_url_mcp(){
			return version_compare(WC()->version, '2.1', '>=') ? "wc-settings" : "woocommerce_settings";
		}
	}
	
	if (!function_exists('wf_plugin_override')){
		add_action( 'plugins_loaded', 'wf_plugin_override' );
		function wf_plugin_override() {
			if (!function_exists('WC')){
				function WC(){
					return $GLOBALS['woocommerce'];
				}
			}
		}
	}

	if (!function_exists('wf_get_shipping_countries_mcp')){
		function wf_get_shipping_countries_mcp(){
			$woocommerce = WC();
			$shipping_countries = method_exists($woocommerce->countries, 'get_shipping_countries')
					? $woocommerce->countries->get_shipping_countries()
					: $woocommerce->countries->countries;
			return $shipping_countries;
		}
	}
	
	//add_action( 'admin_enqueue_scripts', 'wf_scripts' );
	//if (!function_exists('wf_scripts')){
        //function wf_scripts() {
           //wp_enqueue_script( 'jquery' );
        //}
    //}
    
    if (!function_exists('wf_plugin_url_mcp')){
        function wf_plugin_url_mcp() {
            return untrailingslashit( plugins_url( '/', __FILE__ ) );
        }
    }

    if (!function_exists('wf_plugin_basename_mcp')){
        function wf_plugin_basename_mcp() {
            return 'woocommerce-multi-carrier-shipping/woocommerce-multi-carrier-shipping.php';
        }
    }

    
    if (!class_exists('eha_multi_carrier_shipping_setup')) {
        class eha_multi_carrier_shipping_setup {
            public function __construct() {
                if ( is_admin() ) {
                    add_action( 'init', array( $this, 'wf_admin_includes' ) );
                }
                add_filter( 'plugin_action_links_' . wf_plugin_basename_mcp(), array( $this, 'plugin_action_links_mcp' ) );
                add_action( 'woocommerce_shipping_init', array( $this, 'eha_multi_carrier_shipping_init' ) );
                add_filter( 'woocommerce_shipping_methods', array( $this, 'wf_add_woocommerce_multi_carrier_shipping_init' ) );
                add_filter( 'woocommerce_shipping_methods', array( $this, 'wf_add_woocommerce_shipping_zone_init' ) );
                
               add_filter('woocommerce_shipping_fields',array( $this, 'wf_add_checkout_page_field' ) );
               add_filter('woocommerce_billing_fields',array( $this, 'wf_add_checkout_page_field' ) );
               add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'woocommerce_cart_shipping_packages' ));
               include_once( 'includes/class-eha-matrices-exporter.php' );			
            }
            function woocommerce_cart_shipping_packages($shipping)
            {
                if(isset($_POST['post_data']))
                {
                    parse_str($_POST['post_data'],$str);
                    if(isset($str['eha_is_residential']))
                    {
                        
                        foreach($shipping as $key=>$val)
                        {
                            $shipping[$key]['destination']['is_residential']=true;
                        }
                    }
                    else
                    {
                        foreach($shipping as $key=>$val)
                        {
                            $shipping[$key]['destination']['is_residential']=false;
                        }
                    }
                }
                return $shipping;
            }

            public function wf_add_checkout_page_field($fields)
            {
                //echo "<pre>" ;  print_r($fields);   echo "</pre>";
            $out_f=array();

                $done=false;
                foreach($fields as $key=>$val)
                {
                    if($key=='billing_address_2' || $key=='shipping_address_2')
                    {
                        $out_f[$key]=$val;
                        $out_f['eha_is_residential']=array(
                                                                                       'type' => 'checkbox',
                                                                                       'label' => __('This is a Residential Address', 'woocommerce'),
                                                                                       'class'         => array('my-field-class form-row-wide','update_totals_on_change'),
                                                                                       'priority'=>1
                                                                                       );                  
                        $done=true;
                    }else
                    {
                        $out_f[$key]=$val;
                    }
                   
                }
                if(!$done)
                {
                     $out_f['eha_is_residential']=array(
                                                                                       'type' => 'checkbox',
                                                                                       'label' => __('This is a Residential Address', 'woocommerce'),
                                                                                       'class'         => array('my-field-class form-row-wide','update_totals_on_change'),
                                                                                       'priority'=>1
                                                                                       );                  
                        $done=true;
                }
             return $out_f;   
            }
            
            public function wf_admin_includes() {
                if ( defined( 'WP_LOAD_IMPORTERS' ) ) {
                    include( 'includes/class-eha-admin-importers.php' );
                }
            }

            public function eha_multi_carrier_shipping_init() {
                if ( ! class_exists( 'eha_multi_carrier_shipping_method' ) ) {
                    include_once( 'core/woocommerce-multi-carrier-shipping-core.php' );
                }
                
                if ( ! class_exists( 'eha_multi_carrier_shipping_area' ) ) {
                    include_once( 'core/eha-woocommerce-multi-carrier-shipping-area.php' );
                }       
            }

            public function wf_add_woocommerce_multi_carrier_shipping_init( $methods ){
                $methods[] = 'eha_multi_carrier_shipping_method';
                return $methods;
            }

            public function wf_add_woocommerce_shipping_zone_init( $methods ){
                $methods[] = 'eha_multi_carrier_shipping_area';
                return $methods;
            }

            public function plugin_action_links_mcp( $links ) {
                $plugin_links = array(
                    '<a href="' . admin_url( 'admin.php?page=' . wf_get_settings_url_mcp() . '&tab=shipping&section=wf_multi_carrier_shipping' ) . '">' . __( 'Settings', 'eha_multi_carrier_shipping' ) . '</a>',
                    '<a href="' . admin_url( 'admin.php?page=' . wf_get_settings_url_mcp() . '&tab=shipping&section=wf_multi_carrier_shipping_area' ) . '">' . __( 'Shipping Area', 'eha_multi_carrier_shipping' ) . '</a>',
                    '<a href="https://www.xadapter.com/category/documentation/multiple-carrier-shipping-plugin-for-woocommerce/" target="_blank">' . __('Documentation', 'eha_multi_carrier_shipping') . '</a>',
                    '<a href="https://www.xadapter.com/support/forum/multiple-carrier-shipping-plugin-woocommerce/" target="_blank">' . __('Support', 'eha_multi_carrier_shipping') . '</a>'
                );
                return array_merge( $plugin_links, $links );
            }				
        }
        new eha_multi_carrier_shipping_setup();
    }