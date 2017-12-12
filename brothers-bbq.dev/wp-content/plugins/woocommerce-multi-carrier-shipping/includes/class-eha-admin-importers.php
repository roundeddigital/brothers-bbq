<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WF_Admin_Importers_mcp' ) ) :

class WF_Admin_Importers_mcp {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_importers' ) );
		add_action( 'import_start', array( $this, 'post_importer_compatibility' ) );		
	}

	/**
	 * Add menu items
	 */
	public function register_importers() {
		register_importer( 'multicarriershipping_rate_matrix_csv', __( 'Multi Carrier Shipping Rate Matrix (CSV)', 'eha_multi_carrier_shipping' ), __( 'Import <strong>Multi Carrier Shipping Rate Matrix</strong> to your store via a csv file.', 'eha_multi_carrier_shipping'), array( $this, 'rate_matrix_importer' ) );

		register_importer( 'multicarriershipping_zone_matrix_csv', __( 'Multi Carrier Shipping Rate Matrix (CSV)', 'eha_multi_carrier_shipping' ), __( 'Import <strong>Multi Carrier Shipping Rate Matrix</strong> to your store via a csv file.', 'eha_multi_carrier_shipping'), array( $this, 'zone_matrix_importer' ) );
	}

	/**
	 * Add menu item
	 */
	public function rate_matrix_importer() {
		// Load Importer API
		require_once ABSPATH . 'wp-admin/includes/import.php';

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
			if ( file_exists( $class_wp_importer ) )
				require $class_wp_importer;
		}

		// includes
		require 'class-eha-rate-matrix-importer.php';

		// Dispatch
		$importer = new WF_Rate_Matrix_Importer_mcp();
		$importer->dispatch();
	}	

	public function zone_matrix_importer() {
		// Load Importer API
		require_once ABSPATH . 'wp-admin/includes/import.php';

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
			if ( file_exists( $class_wp_importer ) )
				require $class_wp_importer;
		}

		// includes
		require 'class-eha-zone-matrix-importer.php';

		// Dispatch
		$importer = new WF_Zone_Matrix_Importer_mcp();
		$importer->dispatch();
	}	
}

endif;

return new WF_Admin_Importers_mcp();