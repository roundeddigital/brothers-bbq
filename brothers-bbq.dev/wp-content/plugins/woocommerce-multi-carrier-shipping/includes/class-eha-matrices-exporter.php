<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WF_Admin_Exporter_mcp' ) ) :

class WF_Admin_Exporter_mcp {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		if (isset($_GET['wf_export_multicarriershipping_rate_matrix_csv'])) {
			add_action('init', array($this, 'wf_export_multicarriershipping_rate_matrix_csv'));
		}
		if (isset($_GET['wf_export_multicarriershipping_zone_matrix_csv'])) {
			add_action('init', array($this, 'wf_export_multicarriershipping_zone_matrix_csv'));
		}	
	}

	public function wf_export_multicarriershipping_rate_matrix_csv(){
		$multi_carrier_shipping_settings = get_option( 'woocommerce_wf_multi_carrier_shipping_settings' );
		$csv_data = 'shipping_name,area_list,shipping_class,product_category,min_weight,max_weight,cost_based_on,fee,shipping_companies,shipping_services'."\n";
		if(!empty($multi_carrier_shipping_settings) && is_array($multi_carrier_shipping_settings) && isset($multi_carrier_shipping_settings['rate_matrix']) && !empty($multi_carrier_shipping_settings['rate_matrix']) ){
			foreach($multi_carrier_shipping_settings['rate_matrix'] as $matrix_row){
				if(!empty($matrix_row) && is_array($matrix_row)){
					$csv_data .= $matrix_row['shipping_name'] . ',';
					$csv_data .= !empty($matrix_row['area_list']) ? implode(";", $matrix_row['area_list']) : '';                                        
					$csv_data .= ',';
					$csv_data .= !empty($matrix_row['shipping_class']) ? implode(";", $matrix_row['shipping_class']) : '';
					$csv_data .= ',';
					$csv_data .= !empty($matrix_row['product_category']) ? implode(";", $matrix_row['product_category']) : '';
					$csv_data .= ',';
					$csv_data .= $matrix_row['min_weight']. ',';
					$csv_data .= $matrix_row['max_weight']. ',';
					$csv_data .= $matrix_row['cost_based_on']. ',';
					$csv_data .= $matrix_row['fee']. ',';
					$csv_data .= $matrix_row['shipping_companies']. ',';
					$csv_data .= $matrix_row['shipping_services']."\n";
				}				
			}
		}	
		header('Content-Type: application/csv');
		header('Content-disposition: attachment; filename="multicarriershippingMatrix-'.date("Y-m-d-H-i-s").'.csv"');
		echo($csv_data); 		
		exit;
	}

	public function wf_export_multicarriershipping_zone_matrix_csv(){
		$multi_carrier_shipping_zones_matrix = get_option( 'woocommerce_wf_multi_carrier_shipping_area_settings' );
		$csv_data = 'zone_name,country_list,state_list,postal_code'."\n";
		if(!empty($multi_carrier_shipping_zones_matrix) && is_array($multi_carrier_shipping_zones_matrix) && isset($multi_carrier_shipping_zones_matrix['zone_matrix']) && !empty($multi_carrier_shipping_zones_matrix['zone_matrix']) ){
			foreach($multi_carrier_shipping_zones_matrix['zone_matrix'] as $matrix_row){
				if(!empty($matrix_row) && is_array($matrix_row)){
					$csv_data .= $matrix_row['zone_name'] . ',';
					$csv_data .= !empty($matrix_row['country_list']) ? implode(";", $matrix_row['country_list']) : '';
					$csv_data .= ',';
					$csv_data .= !empty($matrix_row['state_list']) ? implode(";", $matrix_row['state_list']) : '';
					$csv_data .= ',';
					$csv_data .= $matrix_row['postal_code']."\n";
				}
			}
		}
		header('Content-Type: application/csv');
		header('Content-disposition: attachment; filename="multicarriershippingZoneMatrix-'.date("Y-m-d-H-i-s").'.csv"');
		echo($csv_data);
		exit;
	}
}

endif;

return new WF_Admin_Exporter_mcp();