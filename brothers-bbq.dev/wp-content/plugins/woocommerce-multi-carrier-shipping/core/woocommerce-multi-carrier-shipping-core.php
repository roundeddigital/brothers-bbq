<?php
		if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}
class eha_multi_carrier_shipping_method extends WC_Shipping_Method {
	function __construct() {
		if(isset($_SERVER['HTTPS']) && isset($_POST['btn_getkey'])) {				// website is https javascript call is not working so php curl is used in that case to generate api key
			$curl = curl_init($GLOBALS['eha_API_URL']."/api/shippings/register");
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER,  array("Content-type: application/json"));
			$content='{"email":"'.$_POST['woocommerce_wf_multi_carrier_shipping_emailid'].'","host":"'.$_SERVER['SERVER_NAME'].'"}';
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
				
			$json_response = curl_exec($curl);

			$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			if ( $status != 200 ) 
			{
				die(print_r("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl)));
			}


			curl_close($curl);			   
			
			
			die('<label Style="color:red;background:yellow;">API Key Sent To Your Inbox</label></br>Please Check Your Email-> Inbox or Spam Folder For The API key  </br></br><a href="https://localhost/wordpress4.7/wp-admin/admin.php?page=wc-settings&tab=shipping&section=wf_multi_carrier_shipping">Click Here To Continue</a> ');
		}

		$plugin_config = wf_plugin_configuration_mcp();
		$this->id		   = $plugin_config['id']; 
		$this->method_title	 = __( $plugin_config['method_title'], 'eha_multi_carrier_shipping' );
		$this->method_description = __( $plugin_config['method_description'], 'eha_multi_carrier_shipping' );
		$this->wf_multi_carrier_shipping_init_form_fields();
		$this->init_settings();
		$this->title 	= $this->settings['title'];
		$this->enabled = $this->settings['enabled'];
		//$this->debug = $this->get_option('debug');				
		$this->tax_status	   		= $this->settings['tax_status'];
		$this->rate_matrix	   		= $this->settings['rate_matrix'];
		$this->multiselect_act_class	=	'multiselect';
		$this->drop_down_style	=	'chosen_select ';			
		$this->debug					= isset( $this->settings['debug'] ) && $this->settings['debug'] == 'yes' ? true : false;
		$this->drop_down_style.=	$this->multiselect_act_class;
		$this->boxes		   = $this->get_option( 'boxes', array( ));
		$this->default_boxes=array();
		$this->shipping_classes =WC()->shipping->get_shipping_classes();
		$this->dimension_unit =strtolower( get_option( 'woocommerce_dimension_unit' ));
		$this->weight_unit = strtolower(strtolower( get_option('woocommerce_weight_unit') ));

		$this->product_category  = get_terms( 'product_cat', array('fields' => 'id=>name'));
		// Save settings in admin
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		//filter to add states for Ireland
		add_filter( 'woocommerce_states', array( $this,'wf_custom_woocommerce_states') );
	}



	public function wf_product_category_dropdown_options( $selected_categories = array()) {
			if ($this->product_category) foreach ( $this->product_category as $product_id=>$product_name) :
					echo '<option value="' . $product_id .'"';
					if (!empty($selected_categories) && in_array($product_id,$selected_categories)) echo ' selected="selected"';
					echo '>' . esc_js( $product_name ) . '</option>';
			endforeach;
	}

	public function wf_shipping_class_dropdown_options( $selected_class = array()) {
			if ($this->shipping_classes) foreach ( $this->shipping_classes as $class) :
					echo '<option value="' . esc_attr($class->slug) .'"';
					if (!empty($selected_class) && in_array($class->slug,$selected_class)) echo ' selected="selected"';
					echo '>' . esc_js( $class->name ) . '</option>';
			endforeach;
	}

	function wf_debug($error_message){
			if($this->debug == 'yes')
				wc_add_notice( $error_message, 'notice' );
	}

	public function generate_activate_box_html() {
		ob_start();
		$plugin_name = 'multicarriershipping';
		include( dirname(__FILE__).'/../includes/wf_api_manager/html/html-wf-activation-window.php' ); //without diname() getting error due to some resone.
		return ob_get_clean();
	}

	function wf_multi_carrier_shipping_init_form_fields() {
		global $woocommerce;
			$this->form_fields = array(
			   'licence'  => array(
					'type'			=> 'activate_box'
				), 
				'enabled'	=> array(
					'title'   => __( 'Enable/Disable', 'eha_multi_carrier_shipping' ),
					'type'	=> 'checkbox',
					'label'   => __( 'Enable this shipping method', 'eha_multi_carrier_shipping' ),
					'default' => 'yes',
				),
				'emailid'	=> array(
					'title'   => __( 'Shipping API Email ID', 'eha_multi_carrier_shipping' ),
					'type'	=> 'text',
					'description'   => __( 'Enter your email id to enable shipping api', 'eha_multi_carrier_shipping' )
				),					
				'apikey'	=> array(
					'title'   => __( 'Shipping API Key', 'eha_multi_carrier_shipping' ),
					'type'	=> 'text',
					'description'   => __( 'Enter your api key received in email', 'eha_multi_carrier_shipping' ),
					'class'=>'keybtn',
				),
				'test_mode'	=> array(
					'title'   => __( 'Test Mode', 'eha_multi_carrier_shipping' ),
					'type'	=> 'checkbox',
					'label'   => __( 'Use test environment in all shipping carriers', 'eha_multi_carrier_shipping' ),
					'default' => 'yes',
				),
				'debug'	=> array(
					'title'   => __( 'Debug', 'eha_multi_carrier_shipping' ),
					'type'	=> 'checkbox',
					'label'   => __( 'Debug this shipping method', 'eha_multi_carrier_shipping' ),
					'default' => 'no',
				),
				'title'	  => array(
					'title'	   => __( 'Method Title', 'eha_multi_carrier_shipping' ),
					'type'		=> 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'eha_multi_carrier_shipping' ),
					'default'	 => __( $this->method_title, 'eha_multi_carrier_shipping' ),
				),
				'shipper_settings'   => array(
					'title'		   => __( 'Shipper Settings', 'eha_multi_carrier_shipping' ),
					'type'			=> 'title',
					'class'			=> 'wf_settings_hidden_tab'
				),					
				'origin_addressline'  => array(
					'title'		   => __( 'Origin Address', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => __( 'Shipping address (ship from address).', 'eha_multi_carrier_shipping' ),
					'default'		 => 'Address Line 1',
					'desc_tip'		=> true
				),
				'origin_city'	  	  => array(
					'title'		   => __( 'Origin City', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => __( 'City (ship from city)', 'eha_multi_carrier_shipping' ),
					'default'		 => 'Los Angeles',
					'desc_tip'		=> true
				),
				'origin_country_state'		=> array(
					'type'			=> 'state_list',
					'desc_tip'		=> true,
				),
				'origin_postcode'	 => array(
					'title'		   => __( 'Origin Postcode', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => __( 'Ship from zip/postcode.', 'eha_multi_carrier_shipping' ),
					'default'		 => '90001',
					'desc_tip'		=> true
				),
				'phone_number'		=> array(
					'title'		   => __( 'Origin Phone Number', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => __( 'Your contact phone number.', 'eha_multi_carrier_shipping' ),
					'default'		 => '5555555555',
					'desc_tip'		=> true
				),					
				'fedex_settings'   => array(
					'title'		   => __( 'Fedex Settings', 'eha_multi_carrier_shipping' ),
					'type'			=> 'title',
					'class'			=> 'wf_settings_hidden_tab'
				),
				'fedex_account_number'		   => array(
					'title'		   => __( 'FedEx Account Number', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => '',
					'default'		 => '',
					'class'=>'fedex_api_setting'
				),
				'fedex_meter_number'		   => array(
					'title'		   => __( 'Fedex Meter Number', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => '',
					'default'		 => '',
					'class'=>'fedex_api_setting'
				),
				'fedex_api_key'		   => array(
					'title'		   => __( 'Fedex Web Services Key', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => '',
					'default'		 => '',
					'custom_attributes' => array(
						'autocomplete' => 'off'
					),
					'class'=>'fedex_api_setting'
				),
				'fedex_api_pass'		   => array(
					'title'		   => __( 'Fedex Web Services Password', 'eha_multi_carrier_shipping' ),
					'type'			=> 'password',
					'description'	 => '',
					'default'		 => '',
					'custom_attributes' => array(
											'autocomplete' => 'off'
					),
					'class'=>'fedex_api_setting'
				),
				'fedex_smartpost_indicia'		   => array(
					'title'		   => __( 'Fedex SmartPost Indicia (Optional)', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => '',
					'default'		 => 'PARCEL_SELECT',
					'custom_attributes' => array(
											'autocomplete' => 'off'
					),
					'class'=>'fedex_api_setting'
				),
				'fedex_smartpost_hubid'		   => array(
					'title'		   => __( 'Fedex Smartpost Hub ID (Optional)', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => '',
					'default'		 => '',
					'custom_attributes' => array(
											'autocomplete' => 'off'
					),
					'class'=>'fedex_api_setting'
				),
				'ups_settings'   => array(
					'title'		   => __( 'UPS Settings', 'eha_multi_carrier_shipping' ),
					'type'			=> 'title',
					'class'			=> 'wf_settings_hidden_tab'
				),
				'ups_user_id'			 => array(
					'title'		   => __( 'UPS User ID', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => __( 'Obtained from UPS after getting an account.', 'eha_multi_carrier_shipping' ),
					'default'		 => '',
																					'desc_tip'		=> true
				),
				'ups_password'			=> array(
					'title'		   => __( 'UPS Password', 'eha_multi_carrier_shipping' ),
					'type'			=> 'password',
					'description'	 => __( 'Obtained from UPS after getting an account.', 'eha_multi_carrier_shipping' ),
					'default'		 => '',
																					'desc_tip'		=> true
				),
				'ups_access_key'		  => array(
					'title'		   => __( 'UPS Access Key', 'eha_multi_carrier_shipping' ),
					'type'			=> 'password',
					'description'	 => __( 'Obtained from UPS after getting an account.', 'eha_multi_carrier_shipping' ),
					'default'		 => '',
																					'desc_tip'		=> true
				),
				'ups_account_number'	  => array(
					'title'		   => __( 'UPS Account Number', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => __( 'Obtained from UPS after getting an account.', 'eha_multi_carrier_shipping' ),
					'default'		 => '',
																					'desc_tip'		=> true
				),
				'usps_settings'   => array(
					'title'		   => __( 'U.S.P.S Settings', 'eha_multi_carrier_shipping' ),
					'type'			=> 'title',
					'class'			=> 'wf_settings_hidden_tab'
				),
				'usps_user_id'			 => array(
					'title'		   => __( 'USPS User ID', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => __( 'Obtained from USPS after getting an account.', 'eha_multi_carrier_shipping' ),
					'default'		 => '',
																					'desc_tip'		=> true
				),
				'usps_password'			=> array(
					'title'		   => __( 'USPS Password', 'eha_multi_carrier_shipping' ),
					'type'			=> 'password',
					'description'	 => __( 'Obtained from USPS after getting an account.', 'eha_multi_carrier_shipping' ),
					'default'		 => '',
																					'desc_tip'		=> true
				),
										
				'stamps_usps_settings'   => array(
					'title'		   => __( 'Stamps USPS Settings', 'eha_multi_carrier_shipping' ),
					'type'			=> 'title',
					'class'			=> 'wf_settings_hidden_tab'
				),
				'stamps_usps_user_id'			 => array(
					'title'		   => __( 'Stamps USPS User ID', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => __( 'Obtained from Stamps after getting an account.', 'eha_multi_carrier_shipping' ),
					'default'		 => '',
																					'desc_tip'		=> true
				),
				'stamps_usps_password'			=> array(
					'title'		   => __( 'Stamps USPS Password', 'eha_multi_carrier_shipping' ),
					'type'			=> 'password',
					'description'	 => __( 'Obtained from Stamps after getting an account.', 'eha_multi_carrier_shipping' ),
					'default'		 => '',
																					'desc_tip'		=> true
				),
										
				'dhl_settings'   => array(
					'title'		   => __( 'DHL Express Settings', 'eha_multi_carrier_shipping' ),
					'type'			=> 'title',
					'class'			=> 'wf_settings_hidden_tab'
				),
				'dhl_account_number'			 => array(
					'title'		   => __( 'DHL Account No.', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => __( 'Obtained from DHL after getting an account.', 'eha_multi_carrier_shipping' ),
					'default'		 => '',
																					'desc_tip'		=> true
				),
				'dhl_siteid'			=> array(
					'title'		   => __( 'DHL Site ID', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'description'	 => __( 'Obtained from DHL after getting an account.', 'eha_multi_carrier_shipping' ),
					'default'		 => '',
																					'desc_tip'		=> true
				),
								 'dhl_password'			=> array(
					'title'		   => __( 'DHL Password', 'eha_multi_carrier_shipping' ),
					'type'			=> 'password',
					'description'	 => __( 'Obtained from DHL after getting an account.', 'eha_multi_carrier_shipping' ),
					'default'		 => '',
																					'desc_tip'		=> true
				),
										
				'rate_matrix_title'   => array(
					'title'		   => __( 'Rule Table', 'eha_multi_carrier_shipping' ),
					'type'			=> 'title'
				),
				
				'rate_matrix' => array(
					'type' 			=> 'rate_matrix'
				),
				'show_shipping_group'	=> array(
					'title'   => __( 'Show Method Groups', 'eha_multi_carrier_shipping' ),
					'type'	=> 'checkbox',
					'label'   => __( '  (Show multiple shipping methods, one for every group)', 'eha_multi_carrier_shipping' ),
					'default' => 'no',
				), 
				'is_recipient_address_residential'			  => array(
					'title'			  => __( 'Recipient is Residential Address', 'eha_multi_carrier_shipping' ),
					'label'			  => __( 'Yes', 'eha_multi_carrier_shipping' ),
					'type'			   => 'checkbox',
					'default'			=> 'no'
				),
				'packing_method'	  => array(
					'title'		   => __( 'Parcel Packing', 'eha_multi_carrier_shipping' ),
					'type'			=> 'select',
					'default'		 => 'weight_based',
					'class'		   => 'packing_method',
					'options'		 => array(
					'per_item'	=> __( 'Default: Pack items individually', 'eha_multi_carrier_shipping' ),
					'box_packing'	=> __( 'Recommended: Pack into boxes with weights and dimensions', 'wf-shipping-fedex' ),
					'weight_based'=> __( 'Weight based: Calculate shipping on the basis of order total weight', 'eha_multi_carrier_shipping' ),
					),
				),
				'box_max_weight'		   => array(
					'title'		   => __( 'Max Package Weight', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'default'		 => '10',
					'class'		   => 'weight_based_option',
					'desc_tip'	=> true,
					'description'	 => __( 'Maximum weight allowed for single box.', 'eha_multi_carrier_shipping' ),
				),
				'weight_packing_process'   => array(
						'title'		   => __( 'Packing Process', 'eha_multi_carrier_shipping' ),
						'type'			=> 'select',
						'default'		 => '',
						'class'		   => 'weight_based_option',
						'options'		 => array(
							'pack_descending'	   => __( 'Pack heavier items first', 'eha_multi_carrier_shipping' ),
							'pack_ascending'		=> __( 'Pack lighter items first.', 'eha_multi_carrier_shipping' ),
							'pack_simple'			   => __( 'Pack purely divided by weight.', 'eha_multi_carrier_shipping' ),
						),
						'desc_tip'	=> true,
						'description'	 => __( 'Select your packing order.', 'eha_multi_carrier_shipping' ),
				),	
				'boxes'  => array(
					'type'			=> 'box_packing'
				),
				'tax_status' => array(
					'title'	   => __( 'Tax Status', 'eha_multi_carrier_shipping' ),
					'type'		=> 'select',
					'description' => '',
					'default'	 => 'none',
					'options'	 => array(
							'taxable' => __( 'Taxable', 'eha_multi_carrier_shipping' ),
							'none'	=> __( 'None', 'eha_multi_carrier_shipping' ),
					),
				),
				'empty_responce_shipping_cost'		   => array(
					'title'		   => __( 'Fallback Rate', 'eha_multi_carrier_shipping' ),
					'type'			=> 'text',
					'default'		 => '50',
					'description'	 => __( 'This Cost will be added for every unit of product if no rule is applied on it', 'eha_multi_carrier_shipping' ),
				),
				'empty_responce_shipping_cost_on'		   => array(
					'title'		   => __( 'Fallback Rate On', 'eha_multi_carrier_shipping' ),
					'type'			=> 'select',
					'default'		 => 'per_unit_weight',
					'description'	 => __( 'This Cost will be added for every unit of product if no rule is applied on it', 'eha_multi_carrier_shipping' ),
					'options'		 => array(
						'per_unit_weight'		=> __( 'Per Unit Weight', 'eha_multi_carrier_shipping' ),
						'per_unit_quantity'	   => __( 'Per Unit Quantity', 'eha_multi_carrier_shipping' ),
							
					),
				),
					
			);

	}

	public function generate_state_list_html() {
			if(!empty($this->settings['origin_country_state']))
			{
				$data=$this->settings['origin_country_state'];
				$countryState=explode(':',$data);
				$country=!empty($countryState[0])?$countryState[0]:'';
				$state=!empty($countryState[1])?$countryState[1]:'';				
			}elseif(!empty($this->settings['origin_country']) && !empty($this->settings['origin_custom_state'])  )
			{
				$country=$this->settings['origin_country'];
				$state=$this->settings['origin_custom_state'];
			}else
			{
				$country='US';
				$state='CA';
			}
			ob_start();
			?><tr valign="top">
					<th scope="row" class="titledesc">
							<label for="origin_country_state"><?php _e( 'Origin State Code', 'eha_multi_carrier_shipping' ); ?></label>
							<?php echo wc_help_tip(  __( 'Specify shipper state province code if state not listed with Origin Country.', 'eha_multi_carrier_shipping' ) ); ?>
					</th>
					<td class=""><select name="origin_country_state" style="min-width:350px;" data-placeholder="<?php esc_attr_e( 'Choose country/state &hellip;', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Country', 'woocommerce' ) ?>" class="wc-enhanced-select">
							<?php WC()->countries->country_dropdown_options( $country,$state ); ?>
					</select> <?php  ?>
					</td>
			</tr><?php
			return ob_get_clean();
	}
	public function validate_state_list_field( $key ) {
			$countryState   = !empty( $_POST['origin_country_state'] ) ? $_POST['origin_country_state'] : 'US:CA';				
			return $countryState;
	}		
	public function generate_box_packing_html() {
			ob_start();
			include( 'html-wf-box-packing.php' );
			return ob_get_clean();
	}
	
	public function validate_box_packing_field( $key ) {
			$box_type	 		= isset( $_POST['box_type'] ) ? $_POST['box_type'] : array();
			$boxes_length	 	= isset( $_POST['boxes_length'] ) ? $_POST['boxes_length'] : array();
			$boxes_width	  	= isset( $_POST['boxes_width'] ) ? $_POST['boxes_width'] : array();
			$boxes_height	 	= isset( $_POST['boxes_height'] ) ? $_POST['boxes_height'] : array();

			$boxes_inner_length	= isset( $_POST['boxes_inner_length'] ) ? $_POST['boxes_inner_length'] : array();
			$boxes_inner_width	= isset( $_POST['boxes_inner_width'] ) ? $_POST['boxes_inner_width'] : array();
			$boxes_inner_height	= isset( $_POST['boxes_inner_height'] ) ? $_POST['boxes_inner_height'] : array();

			$boxes_box_weight 	= isset( $_POST['boxes_box_weight'] ) ? $_POST['boxes_box_weight'] : array();
			$boxes_max_weight 	= isset( $_POST['boxes_max_weight'] ) ? $_POST['boxes_max_weight'] :  array();
			$boxes_enabled		= isset( $_POST['boxes_enabled'] ) ? $_POST['boxes_enabled'] : array();

			$boxes = array();
			if ( ! empty( $boxes_length ) && sizeof( $boxes_length ) > 0 ) {
					for ( $i = 0; $i <= max( array_keys( $boxes_length ) ); $i ++ ) {

							if ( ! isset( $boxes_length[ $i ] ) )
									continue;

							if ( $boxes_length[ $i ] && $boxes_width[ $i ] && $boxes_height[ $i ] ) {

									$boxes[] = array(
											'box_type' 	 => isset( $box_type[ $i ] ) ? $box_type[ $i ] : '',
											'length'	 => floatval( $boxes_length[ $i ] ),
											'width'	  => floatval( $boxes_width[ $i ] ),
											'height'	 => floatval( $boxes_height[ $i ] ),

											/* Old version compatibility: If inner dimensions are not provided, assume outer dimensions as inner.*/
											'inner_length' 	=> isset( $boxes_inner_length[ $i ] ) ? floatval( $boxes_inner_length[ $i ] ) : floatval( $boxes_length[ $i ] ),
											'inner_width' 	=> isset( $boxes_inner_width[ $i ] ) ? floatval( $boxes_inner_width[ $i ] ) : floatval( $boxes_width[ $i ] ), 
											'inner_height' 	=> isset( $boxes_inner_height[ $i ] ) ? floatval( $boxes_inner_height[ $i ] ) : floatval( $boxes_height[ $i ] ),

											'box_weight' => floatval( $boxes_box_weight[ $i ] ),
											'max_weight' => floatval( $boxes_max_weight[ $i ] ),
											'enabled'	=> isset( $boxes_enabled[ $i ] ) ? true : false
									);
							}
					}
			}
			foreach ( $this->default_boxes as $box ) {
					$boxes[ $box['id'] ] = array(
							'enabled' => isset( $boxes_enabled[ $box['id'] ] ) ? true : false
					);
			}
			return $boxes;
	}

	function wf_hidden_matrix_column($column_name){
		$options=get_option('woocommerce_wf_multi_carrier_shipping_settings');
		if($column_name=='shipping_group')
		{
			if(!isset($options['show_shipping_group']) || $options['show_shipping_group']!='no')
			{
				return $column_name;
			}else
			{
				return 'hidden';
			}
		}
			return $column_name;
	}

	public function validate_rate_matrix_field( $key ) {
			$rate_matrix		 = isset( $_POST['rate_matrix'] ) ? $_POST['rate_matrix'] : array();
			return $rate_matrix;
	}

	public function generate_rate_matrix_html() {

				ob_start();		
				$url = $GLOBALS['eha_API_URL']."/api/shippings/fedex-services";   
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER,array("Content-type: application/json","Authorization: "));
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				$json_response = curl_exec($curl);
				$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				if ( $status != 200 ) {
					//die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
				}
				curl_close($curl);
				$cost=0;
				$fedexserviceresponce = json_decode($json_response, true);
				
				$url = $GLOBALS['eha_API_URL']."/api/shippings/ups-services";   
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER,array("Content-type: application/json","Authorization: "));
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				$json_response = curl_exec($curl);
				$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				if ( $status != 200 ) {
					//die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
				}
				curl_close($curl);
				$cost=0;
				$upsserviceresponce = json_decode($json_response, true);
			   
				
				$url = $GLOBALS['eha_API_URL']."/api/shippings/usps-services";   
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER,array("Content-type: application/json","Authorization: "));
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				$json_response = curl_exec($curl);
				$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				if ( $status != 200 ) {
					//die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
				}
				curl_close($curl);
				$cost=0;
				$usps_serviceresponce = json_decode($json_response, true);
				
				
				$url = $GLOBALS['eha_API_URL']."/api/shippings/stamps-usps-services";   
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER,array("Content-type: application/json","Authorization: "));
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				$json_response = curl_exec($curl);
				$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				if ( $status != 200 ) {
					//die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
				}
				curl_close($curl);
				$cost=0;
				$stamps_usps_serviceresponce = json_decode($json_response, true);
				
				
				$url = $GLOBALS['eha_API_URL']."/api/shippings/dhl-services";   
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER,array("Content-type: application/json","Authorization: "));
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				$json_response = curl_exec($curl);
				$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				if ( $status != 200 ) {
					//die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
				}
				curl_close($curl);
				$cost=0;
				$dhl_serviceresponce = json_decode($json_response, true);
			   
			?>
<tr> To get estimated shipping rates for multiple carriers, refer this <a href="http://shippingcalculator.storepep.com/" target="_blank">Shipping Calculator Tool</a></tr>
<tr valign="top" id="packing_rate_matrix">
					<td class="titledesc" colspan="2" style="padding-left:0px">
							<br>
							<style type="text/css">
									.multi_carrier_shipping_boxes .row_data td
									{
											border-bottom: 1pt solid #e1e1e1;
									}

									.multi_carrier_shipping_boxes input, 
									.multi_carrier_shipping_boxes select, 
									.multi_carrier_shipping_boxes textarea,
									.multi_carrier_shipping_boxes .select2-container-multi .select2-choices{
											background-color: #fbfbfb;
											border: 1px solid #e9e9e9;

									}
/*									  .select2-container{
											display: inline !important;		
											max-width:100px !important;
									}*/
									.wf_settings_hidden_tab::after {
																		content: ' \25BC';
																	}
									 .wf_settings_hidden_tab {
																	cursor:pointer;
															   }
									 
									.multi_carrier_shipping_boxes td, .multi_carrier_shipping_services td {
											vertical-align: top;
													padding: 4px 4px;

									}
									.multi_carrier_shipping_boxes th, .multi_carrier_shipping_services th {
											padding: 9px 7px;
									}
									.multi_carrier_shipping_boxes td input {
											margin-right: 4px;
									}
									.multi_carrier_shipping_boxes .check-column {
											vertical-align: top;
											text-align: left;
											padding: 4px 7px;
									}
									.multi_carrier_shipping_services th.sort {
											width: 16px;
									}
									.multi_carrier_shipping_services td.sort {
											cursor: move;
											width: 16px;
											padding: 0 16px;
											cursor: move;
											background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAHUlEQVQYV2O8f//+fwY8gJGgAny6QXKETRgEVgAAXxAVsa5Xr3QAAAAASUVORK5CYII=) no-repeat center;
									}
									@media screen and (min-width: 781px) 
									{
											th.tiny_column
											{
											  width:2em;
											  max-width:2em;
											  min-width:2em;									  
											}
											th.small_column
											{
											   width:4em;	
											   max-width:4em; 	
											   min-width:4em;
											}
											th.smallp_column
											{
											   width:4.5em;	
											   max-width:4.5em; 	
											   min-width:4.5em;
											}
											th.medium_column
											{
											   min-width:40px;	 
											}
											th.big_column
											{
													min-width:100px;
											}									
									}
									th.hidecolumn,
									td.hidecolumn
									{
													display:none;
									}
									.chosen_select
									{
										max-width:50px !important;
										width:50px !important;											
									}
									.select2-selection{			   
										width:auto !important;  
									}
									.woocommerce table.form-table .select2-container {
									min-width: 160px!important;
										}

							</style>

							<table class="multi_carrier_shipping_boxes widefat" style="background-color:#f6f6f6;">
									<thead>
											<tr>
													<th class="check-column tiny_column"><input type="checkbox" /></th>
													<th class="tiny_column <?php echo $this->wf_hidden_matrix_column('shipping_name');?>">
													<?php _e( 'Method Title', 'eha_multi_carrier_shipping' );  ?>
													<img class="help_tip" style="float:none;" data-tip="<?php _e( 'Would you like this shipping rule to have its own shipping service name? If so, please choose a name. Leaving it blank will use Method Title as shipping service name.', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /> 
													</th>
													<th class="tiny_column <?php echo $this->wf_hidden_matrix_column('shipping_group');?>">
													<?php _e( 'Method Group', 'eha_multi_carrier_shipping' );  ?>
													<img class="help_tip" style="float:none;" data-tip="<?php _e( 'Set groups if you want to show more than one shipping methods on cart page.', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /> 
													</th>
													<th class="tiny_column <?php echo $this->wf_hidden_matrix_column('area_list');?>" >
													<?php _e( 'Area List', 'eha_multi_carrier_shipping' );  ?>
													<img class="help_tip" style="float:none;" data-tip="<?php _e( 'You can choose the Areas here once you configured', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /> 
													</th>

													<th class="tiny_column <?php echo $this->wf_hidden_matrix_column('shipping_class');?>" >
													<?php _e( 'Shipping Class', 'eha_multi_carrier_shipping' );  ?>
													<img class="help_tip" style="float:none;" data-tip="<?php _e( 'Select list of shipping class which this rule will be applicable', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /> 
													</th>
													<th class="big_column <?php echo $this->wf_hidden_matrix_column('product_category');?>">
													<?php _e( 'Product Category', 'eha_multi_carrier_shipping' );  ?>
													<img class="help_tip" style="float:none;" data-tip="<?php _e( 'Select list of product category which this rule will be applicable. Only the product category directly assigned to the products will be considered.', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /> 
													</th>
													<th class="small_column <?php echo $this->wf_hidden_matrix_column('cost_based_on');?>">
													<?php _e( 'Based on', 'eha_multi_carrier_shipping' );  ?>
													<img class="help_tip" style="float:none;" data-tip="<?php _e( 'Shipping rate calculation based on Weight/Item/Price.', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /><br>
													</th>
													<th class="medium_column <?php echo $this->wf_hidden_matrix_column('weight');?>" style='	padding-left: 0px;'>
													<?php _e( 'Min-Max', 'eha_multi_carrier_shipping' );  ?>
													<img class="help_tip" style="float:none;" data-tip="<?php _e( 'if the min value entered is .25 and the total category/Shipping Class weight is .24 then this rule will be ignored. if the min value entered is .25 and the total category/Shipping weight is .26 then this rule will be be applicable for calculating shipping cost. if the max value entered is .25 and the total category/Shipping weight is .26 then this rule will be ignored. if the max value entered is .25 and the total category/Shipping weight is .25 or .24 then this rule will be be applicable for calculating shipping cost.', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /><br><?php _e( '(Wt, Price, Qty)', 'eha_multi_carrier_shipping' );  ?> 
													</th>
													<th class="tiny_column <?php echo $this->wf_hidden_matrix_column('fee');?>" style='left: 7px;'>
													<?php _e( 'Cost', 'eha_multi_carrier_shipping' );  ?>
													<img class="help_tip" style="float:none;" data-tip="<?php _e( 'Base/Fixed cost of the shipping irrespective of the weight/item count/price', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /><br><?php _e( '(Flat Rate)', 'eha_multi_carrier_shipping' );  ?> 
													</th>
																															<th class="medium_column <?php echo $this->wf_hidden_matrix_column('fee');?>">
													<?php _e( 'Shipping Option', 'eha_multi_carrier_shipping' );  ?>
													<img class="help_tip" style="float:none;" data-tip="<?php _e( 'Select Shipping Company', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /><br> 
													</th>
													<th class="medium_column <?php echo $this->wf_hidden_matrix_column('fee');?>" style=" text-align: center; ">
														<span style=" text-align: center; "><?php _e( 'Service', 'eha_multi_carrier_shipping' );  ?> </span>
													<img class="help_tip" style="float:none;" data-tip="<?php _e( 'Select Service of Selected Shipping Company', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /><br> 
													</th>




											</tr>
									</thead>
									<tfoot>
											<tr>
													<th colspan="4">
															<a href="#" class="button insert"><?php _e( 'Add rule', 'eha_multi_carrier_shipping' ); ?></a>
															<a href="#" class="button remove"><?php _e( 'Remove rule(es)', 'eha_multi_carrier_shipping' ); ?></a>
															<a href="#" class="button duplicate"><?php _e( 'Duplicate rule(es)', 'eha_multi_carrier_shipping' ); ?></a>
													</th>
													<th colspan="6">
															<small class="description" style="float:right;margin-right: 5px;"><a  style="float:right;margin-right: 10px;" href="<?php echo admin_url( 'admin.php?import=multicarriershipping_rate_matrix_csv' ); ?>" class="button"><?php _e( 'Import CSV', 'eha_multi_carrier_shipping' ); ?></a>
																<a  style="float:right;margin-right: 10px;" href="<?php echo admin_url( 'admin.php?wf_export_multicarriershipping_rate_matrix_csv=true' ); ?>" class="button"><?php _e( 'Export CSV', 'eha_multi_carrier_shipping' ); ?></a>&nbsp; <label  style="float:right;margin-right: 10px;"><?php _e( 'Weight Unit & Dimensions Unit as per WooCommerce settings.', 'eha_multi_carrier_shipping' ); ?></label>
															</small>
													</th>
											</tr>
									</tfoot>
									   <script>
															jQuery(document).ready(function () {																			  
																		var apikey=jQuery('#woocommerce_wf_multi_carrier_shipping_apikey').val();
																		 if(apikey.length!==32)
																		 {
																			var $input = jQuery('<input type="<?php if(isset($_SERVER['HTTPS'])) {echo "submit";}else {echo "button";}?>" id="btn_getkey" name="btn_getkey" value="Get API Key (Free)" />');
																			//$input.appendTo(jQuery("td.forminp:nth-child(2)").append();
																		  jQuery(".keybtn").closest('td').append($input);
																		 
																		 }
																		 <?php if(!isset($_SERVER['HTTPS'])) { ?>
																		 jQuery("#btn_getkey").click(   function(){
																														var fullUrl = window.location.protocol + "//" + window.location.hostname+window.location.port + window.location.pathname;
																														var data={};
																														data.email=jQuery('#woocommerce_wf_multi_carrier_shipping_emailid').val();
																														data.host=fullUrl;
																														jQuery.post(<?php  echo "'". $GLOBALS['eha_API_URL']."/api/shippings/register"."'" ?>, JSON.stringify(data), function(response) {
																																				   alert('Please Check Your Inbox to Get key for this API');
																																				}, 'json');
																															}
																														)   ;
																		<?php } ?>												  
																		function any_shipping_selected(obj)
																		{
																			
																						   // jQuery(this).closest('td').find("li:div:contains('Any Shipping Class')").remove('li.select2-search-choice');
																							 jQuery(obj).closest('td').find("div:contains('Any Shipping Class')").closest('li').siblings(".select2-search-choice").remove();
																							 jQuery(obj).closest('td').find("li.select2-selection__choice:contains('Any Shipping Class')").siblings().remove();
																							 jQuery(obj).closest('td').find("*").removeAttr("selected");
																							 jQuery(obj).closest("td select").find("option[value='any_shipping_class']").attr('selected',true);
																				   
																		}
																		
																		function any_category_selected(obj)
																		{
																							jQuery(obj).closest('td').find("div:contains('Any Product category')").closest('li').siblings(".select2-search-choice").remove();
																							 jQuery(obj).closest('td').find("li.select2-selection__choice:contains('Any Product category')").siblings().remove();
																							 
																							jQuery(obj).closest('td').find("*").removeAttr("selected");
																							 jQuery(obj).closest("td select").find("option[value='any_product_category']").attr('selected',true);
																							
																		}
																		
																		
																		 //jQuery(".woocommerce-save-button").click(function () {
																		jQuery("#mainform").submit(function () {
																			var success=true;
																			var empty_shipping_class=[];
																			var empty_category=[];
																			
																															jQuery('.area_list>.chosen_select.multiselect').not('.select2-container-multi').each(function( index ) {
																															   if(jQuery('option:selected',this).length==0)
																															   {
																																	 success=false;																																		 
																																	alert("Area List is not specified in a rule");	 
																															   }
																															});																												   
																															
																															jQuery('.shipping_class>.chosen_select.multiselect').not('.select2-container-multi').each(function( index ) {
																																		if(jQuery('option:selected',this).length==0 && jQuery(this).attr('disabled')!='disabled')
																																		{
																																				empty_shipping_class[index]=true;																																		 
																																			   //alert("Shipping Class is not specified in a rule");	 
																																			   //return false;
																																		}else
																																		{
																																			empty_shipping_class[index]=false;	 
																																		}
																																	 });	 
																																	  jQuery('.product_category>.chosen_select.multiselect').not('.select2-container-multi').each(function( index ) {
																																		  //alert(jQuery(this).attr('disabled'));
																																		if(jQuery('option:selected',this).length==0 && jQuery(this).attr('disabled')!='disabled')
																																		{
																																				empty_category[index]=true;																																		 
																																			   //alert("Product Category is not specified in a rule");	 
																																			   //return false;
																																		}else
																																		{
																																			empty_category[index]=false;  
																																		}
																																	 }); 
																																	 
																																	 for(var i in empty_category )
																																	 {
																																			if(empty_category[i] ===true && empty_shipping_class[i]===true)
																																			{
																																				alert("Both Shipping Class & Product Category not specified in rule no : "+( parseInt(i) +1) +" , atleast one should be defined");	 
																																				success=false;	
																																			}																																			 
																																	 }

																														return success;  
																														});
																		
																		jQuery('.multiselect').each(function( index ) {
																		
																		if(jQuery( 'option:selected',this).val()=='any_shipping_class')
																		{
																					   any_shipping_selected(this);																																			 
																		}else if(jQuery( 'option:selected',this).val()=='any_product_category')
																		{
																			 any_category_selected(this);
																		}
																	   }
																);
																		jQuery('#rates').on('change','.shipping_class>.multiselect',function () {
																			if (jQuery('option:selected',this).val()== 'any_shipping_class') {
																				 any_shipping_selected(this);	 
																							}																						   
																			else {  
																							jQuery(this).closest('td').next('td').find( "*" ).removeAttr('readonly'); // mark it as read only
																							jQuery(this).closest('td').next('td').next('td').find( "*" ).removeAttr('disabled'); // mark it as read only
																							jQuery(this).closest('td').next('td').next('td').find( "*" ).css('background-color' , ''); // change the background color
																							jQuery(this).closest('td').next('td').find( "*" ).css('background-color' , ''); 
																							
																							 jQuery(this).closest('td').prev('td').find( "*" ).css('background-color' , '');// mark it as read only
																							
																							jQuery(this).closest('td').next('td').find( "*" ).removeAttr("disabled");  // mark it as read only
																						   jQuery(this).closest('td').next('td').next('td').next('td').find( "*" ).removeAttr("disabled");  // mark it as read only
																							 jQuery(this).closest('td').prev('td').find( "*" ).removeAttr("disabled");// mark it as read only																								 
																							jQuery(this).closest('td').next('td').find( "*" ).removeAttr('disabled'); // mark it as read only
																							jQuery(this).closest('td').next('td').find( "*" ).css('background-color' , ''); // change the background color																							   
																							jQuery(this).closest('td').next('td').next('td').find( "*" ).removeAttr("disabled");   // mark it as read only
																			}
																		});
																		 jQuery('#rates').on('change','.product_category>.multiselect',function () {
																			if (jQuery('option:selected',this).val() == 'any_product_category')
																							{
																							any_category_selected(this);
																							}																							  
																			else {  
																							jQuery(this).closest('td').next('td').find( "*" ).removeAttr('readonly'); // mark it as read only
																							jQuery(this).closest('td').next('td').next('td').find( "*" ).removeAttr('disabled'); // mark it as read only
																							jQuery(this).closest('td').next('td').next('td').find( "*" ).css('background-color' , ''); // change the background color
																							jQuery(this).closest('td').next('td').find( "*" ).css('background-color' , ''); 
																							
																							 jQuery(this).closest('td').prev('td').find( "*" ).css('background-color' , '');// mark it as read only
																							
																							jQuery(this).closest('td').next('td').find( "*" ).removeAttr("disabled");  // mark it as read only
																						   jQuery(this).closest('td').next('td').next('td').next('td').find( "*" ).removeAttr("disabled");  // mark it as read only
																							 jQuery(this).closest('td').prev('td').find( "*" ).removeAttr("disabled");// mark it as read only																								 
																							jQuery(this).closest('td').next('td').find( "*" ).removeAttr('disabled'); // mark it as read only
																							jQuery(this).closest('td').next('td').find( "*" ).css('background-color' , ''); // change the background color																							   
																							jQuery(this).closest('td').next('td').next('td').find( "*" ).removeAttr("disabled");   // mark it as read only
																			}
																		});
																		
																		
																		 jQuery('#woocommerce_wf_multi_carrier_shipping_packing_method').change(function () {
																			if (jQuery('#woocommerce_wf_multi_carrier_shipping_packing_method option:selected').val() === 'weight_based') {
																							jQuery('#woocommerce_wf_multi_carrier_shipping_weight_packing_process').show();
																							jQuery(this).closest('tr').next('tr').show();
																							jQuery(this).closest('tr').next('tr').next('tr').show();
																							} else {
																							jQuery(this).closest('tr').next('tr').hide();
																							jQuery(this).closest('tr').next('tr').next('tr').hide();
																			}
																		});
																	   if (jQuery('#woocommerce_wf_multi_carrier_shipping_packing_method').val() === 'per_item') {
																							jQuery('#woocommerce_wf_multi_carrier_shipping_packing_method').closest('tr').next('tr').hide();
																							jQuery('#woocommerce_wf_multi_carrier_shipping_packing_method').closest('tr').next('tr').next('tr').hide();
																			}
																			
																			jQuery('.multi_carrier_shipping_boxes').on("change",".company_select",function () {
																				var fedexservices='';
																				var upsservicees='';
																				
																<?php
																	echo "fedexservices=";
																	$response=$fedexserviceresponce;
																	echo "'";
																	$response=$this->fedexresponcedefault($response);																		
																			foreach($response as  $key2=>$val2)
																				{
																					 echo "<option value=$key2>$val2</option>";
																				} 
																	

																	echo "';";
																	echo "upsservices=";
																	$response=$upsserviceresponce;
																	echo "'";
																	 $response=$this->upsresponcedefault($response);
																	 
																			foreach($response as  $key2=>$val2)
																			{
																				 echo "<option value=$key2>$val2</option>";
																			}																			 
																	 

																	echo "';";
																	
																	echo "usps_services=";
																	$response=$usps_serviceresponce;
																	echo "'";
																	 $response=$this->usps_responcedefault($response);
																	 
																			foreach($response as  $key2=>$val2)
																			{
																				 echo "<option value=$key2>$val2</option>";
																			}																			 
																	 
																	echo "';";
																	echo "stamps_usps_services=";
																	$response=$stamps_usps_serviceresponce;
																	echo "'";
																	 $response=$this->stamps_usps_responcedefault($response);
																	 
																			foreach($response as  $key2=>$val2)
																			{
																				 echo "<option value=$key2>$val2</option>";
																			}																			 
																	 
																	echo "';";
																	echo "dhl_services=";
																	$response=$dhl_serviceresponce;
																	echo "'";
																	 $response=$this->dhl_responcedefault($response);
																	 
																			foreach($response as  $key2=>$val2)
																			{
																				 echo "<option value=$key2>$val2</option>";
																			}																			 
																	 
																	echo "';";
																	
																	?>
																	if (jQuery(this).val() === 'ups') 
																			{   var selectbox=jQuery(this).closest('td').next('td').find('select').empty();
																				selectbox.append(upsservices);
																			} 
																	else if(jQuery(this).val() === 'fedex') 
																	{
																				 var selectbox=jQuery(this).closest('td').next('td').find('select').empty();
																				selectbox.append(fedexservices);
																			}
																	  else if(jQuery(this).val() === 'usps') 
																	{
																				 var selectbox=jQuery(this).closest('td').next('td').find('select').empty();
																				selectbox.append(usps_services);
																			}
																	 else if(jQuery(this).val() === 'stamps_usps') 
																	{
																				 var selectbox=jQuery(this).closest('td').next('td').find('select').empty();
																				selectbox.append(stamps_usps_services);
																			}
																	   else if(jQuery(this).val() === 'dhl') 
																	{
																				 var selectbox=jQuery(this).closest('td').next('td').find('select').empty();
																				selectbox.append(dhl_services);
																			}
																	  else if(jQuery(this).val() === 'flatrate') 
																	{
																			   jQuery(this).closest('td').next('td').find('select').empty();
																			}
																		});
																		   
																			
															});
													</script>
									<tbody id="rates">
									<?php								
									$matrix_rowcount = 0;
									if ( $this->rate_matrix ) {
											foreach ( $this->rate_matrix as $key => $box ) {
													$defined_areas = isset($box['area_list']) ? $box['area_list'] : array();
													$defined_shipping_classes = isset($box['shipping_class']) ? $box['shipping_class'] : array();
													$defined_product_category = isset($box['product_category']) ? $box['product_category'] : array();
													?>
												 
													<tr class="rule_text"><td colspan="6" style="font-style:italic; color:#a8a8a8;"><strong><?php //echo $this->wf_rule_to_text($key ,$box);?></strong></td></tr>
													<tr class="row_data"><td class="check-column"><input type="checkbox" /></td>
													<td class="<?php echo $this->wf_hidden_matrix_column('shipping_name');?>"><input type='text' size='20' name='rate_matrix[<?php echo $key;?>][shipping_name]' placeholder='<?php echo $this->title;?>' title='<?php echo isset($box['shipping_name']) ? $box['shipping_name']:$this->title;?>' value='<?php echo isset($box['shipping_name']) ? $box['shipping_name']:"";?>' /></td>
													<td class="<?php echo $this->wf_hidden_matrix_column('shipping_group');?>"><input type='text' size='20' name='rate_matrix[<?php echo $key;?>][shipping_group]' placeholder='<?php echo 'Primary Group'; ?>'  value='<?php echo isset($box['shipping_group']) ? $box['shipping_group']:"";?>' /></td>


													<td class="<?php echo $this->wf_hidden_matrix_column('area_list');?>" style='overflow:visible'>
													<select id="area_list_combo_<?php echo $key;?>" class="<?php echo $this->drop_down_style;?> enabled" data-identifier="area_list_combo" multiple="true" style="" name='rate_matrix[<?php echo $key;?>][area_list][]'>
																	<?php 
																	$area_list = $this->wf_get_area_list();
																	foreach($area_list as $zoneKey => $zoneValue){ ?>
																	<option value="<?php echo $zoneKey;?>" <?php selected(in_array($zoneKey,$defined_areas),true);?>><?php echo $zoneValue;?>
																	</option>
																	<?php } ?>															
															</select>
													</td>
													<td class="<?php echo $this->wf_hidden_matrix_column('shipping_class');?>" style='overflow:visible;'>
													<select id="shipping_class_combo_<?php echo $key;?>" class="<?php echo $this->drop_down_style;?> enabled" data-identifier="shipping_class_combo" multiple="true" style="" name='rate_matrix[<?php echo $key;?>][shipping_class][]'>
															<option value="any_shipping_class" <?php selected(in_array('any_shipping_class',$defined_shipping_classes),true);?>>Any Shipping Class</option>
															<?php $this->wf_shipping_class_dropdown_options($defined_shipping_classes); ?>															
													</select>
													</td>
													<td class="<?php echo $this->wf_hidden_matrix_column('product_category');?>" style='overflow:visible'>
													<select id="product_category_combo_<?php echo $key;?>" class="<?php echo $this->drop_down_style;?> enabled" data-identifier="product_category_combo"  multiple="true" style="" name='rate_matrix[<?php echo $key;?>][product_category][]'>
															<option value="any_product_category" <?php selected(in_array('any_product_category',$defined_product_category),true);?>>Any Product category</option>
															<?php $this->wf_product_category_dropdown_options($defined_product_category); ?>															
													</select>
													</td>
													 <td class="<?php echo $this->wf_hidden_matrix_column('cost_based_on');?>">
													<select id="cost_based_on_<?php echo $key;?>" class="select singleselect" name="rate_matrix[<?php echo $key;?>][cost_based_on]" data-identifier="cost_based_on">
													<option value="weight" <?php selected(isset($box['cost_based_on']) ? $box['cost_based_on'] : '','weight');?> >Weight</option>
													<option value="item" <?php selected(isset($box['cost_based_on']) ? $box['cost_based_on'] : '','item');?>>Item Qty</option>
													<option value="price" <?php selected(isset($box['cost_based_on']) ? $box['cost_based_on'] : '','price');?>>Price</option></select></td>
													
													<td class="<?php echo $this->wf_hidden_matrix_column('weight');?>"><input type='text' size='3' name='rate_matrix[<?php echo $key;?>][min_weight]' 	style='clear: both;float:left;'	value='<?php  echo isset($box['min_weight']) ? $box['min_weight']:''; ?>' /><input type='text' size='3' name='rate_matrix[<?php echo $key;?>][max_weight]' 	style='clear: both;float:left;'	value='<?php echo isset($box['max_weight']) ? $box['max_weight']:''; ?>' /></td>
												   <td class="<?php echo $this->wf_hidden_matrix_column('fee');?>"><input type='text' size='2' name='rate_matrix[<?php echo $key;?>][fee]'	value='<?php  echo isset($box['fee'])?$box['fee']:''; ?>' /></td>

												 <td class="<?php echo $this->wf_hidden_matrix_column('shipping_companies');?>">
														<select id="shipping_companies_<?php echo $key;?>" class="select singleselect company_select" name="rate_matrix[<?php echo $key;?>][shipping_companies]" data-identifier="shipping_companies">
														<option value="flatrate" <?php selected(isset($box['shipping_companies']) ? $box['shipping_companies'] : '','flatrate');?> >Flat Rate</option>
														<option value="fedex" <?php selected(isset($box['shipping_companies']) ? $box['shipping_companies'] : '','fedex');?> >FEDEX</option>
														<option value="ups" <?php selected(isset($box['shipping_companies']) ? $box['shipping_companies'] : '','ups');?>>UPS</option>
														<option value="usps" <?php selected(isset($box['shipping_companies']) ? $box['shipping_companies'] : '','usps');?>>USPS</option>
														<option value="stamps_usps" <?php selected(isset($box['shipping_companies']) ? $box['shipping_companies'] : '','stamps_usps');?>>Stamps USPS</option>
														<option value="dhl" <?php selected(isset($box['shipping_companies']) ? $box['shipping_companies'] : '','dhl');?>>DHL Express</option>
														
															</select></td>
															<td class="<?php echo $this->wf_hidden_matrix_column('shipping_services');?>">
															<select id="shipping_services_<?php echo $key;?>" class="select singleselect shipping_service" name="rate_matrix[<?php echo $key;?>][shipping_services]" data-identifier="shipping_services" style="width: 150px;">
																<?php
																	if($box['shipping_companies']=='fedex')
																	{
																		$response=$this->fedexresponcedefault($fedexserviceresponce);
																	}
																	elseif($box['shipping_companies']=='ups')
																	{
																		$response=$this->upsresponcedefault($upsserviceresponce);
																	}
																	elseif($box['shipping_companies']=='usps')
																	{
																		$response=$this->usps_responcedefault($usps_serviceresponce);
																	}
																	elseif($box['shipping_companies']=='stamps_usps')
																	{
																		$response=$this->stamps_usps_responcedefault($stamps_usps_serviceresponce);
																	}
																	elseif($box['shipping_companies']=='dhl')
																	{
																		$response=$this->dhl_responcedefault($dhl_serviceresponce);
																	}
																	else
																	{
																		unset($response);
																		$response=array();
																	}
																	 
																	foreach($response as  $key2=>$val)
																	{
																	   echo "<option value='$key2' "; echo selected(isset($box['shipping_services']) ? $box['shipping_services'] : '',$key2); echo " >$val</option>";
																	}
																	?>
															</select></td>
														</tr>
														
													<?php
													if(!empty($key) && $key >= $matrix_rowcount)
															$matrix_rowcount = $key;
											}
									}
									?>
														
									<input type="hidden" id="matrix_rowcount" value="<?php echo$matrix_rowcount;?>" />

									</tbody>

							</table>
							<table>
							<tr class="row_data"><td colspan='8' style="font-size:12px;">   <a target="_blank" href="https://www.usps.com/business/web-tools-apis/rate-calculator-api.pdf" style="color:darkblue;"  ><span  style="color:red;">NOTE : </span>Please check 'Appendix A' of this USPS document for weight based restriction on services (pdf)</a>
						</td>   </tr>
						   <tr class="row_data"><td colspan='8' style="color:darkred;font-size:12px;"  > <span  style="color:red;">KEYS : </span>  *HFP =Hold For Pickup  ,  *CPP =Commercial Plus Rate  , *SH =Special Handling ,  
						</td>   </tr>
							</table>
							<script type="text/javascript">																	
									jQuery(window).load(function(){
										
											jQuery('.wf_settings_hidden_tab').next('table').hide();
											jQuery('.wf_settings_hidden_tab').click(function(){
													jQuery(this).next('table').toggle();
											});									
											jQuery('.multi_carrier_shipping_boxes .insert').click( function() {
													var $tbody = jQuery('.multi_carrier_shipping_boxes').find('tbody');
													var size = $tbody.find('#matrix_rowcount').val();
													if(size){
															size = parseInt(size)+1;
													}
													else
															size = 0;

													var code = '<tr class="new row_data"><td class="check-column"><input type="checkbox" /></td>\
													<td class="<?php echo $this->wf_hidden_matrix_column('shipping_name');?>"><input type="text" size="20" name="rate_matrix['+size+'][shipping_name]" placeholder="<?php echo $this->title;?>" /></td>\
													<td class="<?php echo $this->wf_hidden_matrix_column('shipping_group');?>"><input type="text" size="20" name="rate_matrix['+size+'][shipping_group]" placeholder="<?php echo 'Primary Group'; ?>"  /></td>\n\
													<td class="<?php echo $this->wf_hidden_matrix_column('area_list');?>" style="overflow:visible">\
															<select id="area_list_combo_'+size+'" class="<?php echo $this->drop_down_style;?> enabled" data-identifier="area_list_combo" multiple="true" style="" name="rate_matrix['+size+'][area_list][]">\
																	<?php 
																	$area_list = $this->wf_get_area_list();
																	foreach($area_list as $zoneKey => $zoneValue){ ?><option value="<?php echo esc_attr( $zoneKey ); ?>" ><?php echo esc_attr( $zoneValue ); ?></option>\
																	<?php } ?>
															</select>\
													</td>\
													<td class="<?php echo $this->wf_hidden_matrix_column('shipping_class');?>" style="overflow:visible">\
													<select id="shipping_class_combo_'+size+'" class="<?php echo $this->drop_down_style;?> enabled" data-identifier="shipping_class_combo" multiple="true" style="" name="rate_matrix['+size+'][shipping_class][]">\
													<option value="any_shipping_class">Any Shipping class</option>\
													<?php $this->wf_shipping_class_dropdown_options(); ?></select>\
													</td>\
													<td class="<?php echo $this->wf_hidden_matrix_column('product_category');?>" style="overflow:visible">\
													<select id="product_category_combo_'+size+'" class="<?php echo $this->drop_down_style;?> enabled" data-identifier="product_category_combo"  multiple="true" style="" name="rate_matrix['+size+'][product_category][]">\
													<option value="any_product_category">Any Product category</option>\
													<?php $this->wf_product_category_dropdown_options(); ?></select>\
													</td>\
													<td class="<?php echo $this->wf_hidden_matrix_column('cost_based_on');?>"><select id="cost_based_on_'+size+'" class="select singleselect" data-identifier="cost_based_on" name="rate_matrix['+size+'][cost_based_on]"><option value="weight" selected>Weight</option><option value="item">Item Qty</option><option value="price">Price</option></select></td> \
													<td class="<?php echo $this->wf_hidden_matrix_column('weight');?>"><input type="text" size="3" name="rate_matrix['+size+'][min_weight]"  /><input type="text" size="3" name="rate_matrix['+size+'][max_weight]" /></td> \
													<td class="<?php echo $this->wf_hidden_matrix_column('fee');?>"><input type="text" size="5" name="rate_matrix['+size+'][fee]" /></td>\
													<td class="<?php echo $this->wf_hidden_matrix_column('shipping_companies');?>">\
													<select id="shipping_companies_'+size+'" class="select singleselect company_select" name="rate_matrix['+size+'][shipping_companies]" data-identifier="shipping_companies">\
													 <option value="flatrate" >Flat Rate</option>\
														<option value="fedex"  >FEDEX</option>\
														<option value="ups">UPS</option>\
														<option value="usps" >USPS</option>\
														<option value="stamps_usps" >Stamps USPS</option>\
														<option value="dhl" >DHL Express</option>\
													</select></td>\
													<td class="<?php echo $this->wf_hidden_matrix_column('shipping_services');?>">\
													<select id="shipping_services_'+size+'" class="select singleselect shipping_service" name="rate_matrix['+size+'][shipping_services]" data-identifier="shipping_services" style="width: 150px;">\
												   </select></td>\
													</tr>';
													
													$tbody.append( code );
													if(typeof wc_enhanced_select_params == 'undefined')
															$tbody.find('tr:last').find("select.chosen_select").chosen();
													else
															$tbody.find('tr:last').find("select.chosen_select").trigger( 'wc-enhanced-select-init' );


													$tbody.find('#matrix_rowcount').val(size);
													return false;
											} );

											jQuery('.multi_carrier_shipping_boxes .remove').click(function() {
													var $tbody = jQuery('.multi_carrier_shipping_boxes').find('tbody');

													$tbody.find('.check-column input:checked').each(function() {
															jQuery(this).closest('tr').prev('.rule_text').remove();
															jQuery(this).closest('tr').remove();
															});

													return false;
											});

											jQuery('.multi_carrier_shipping_boxes .duplicate').click(function() {
													var $tbody = jQuery('.multi_carrier_shipping_boxes').find('tbody');

													var new_trs = [];

													$tbody.find('.check-column input:checked').each(function() {
															var $tr	= jQuery(this).closest('tr');
															var $clone = $tr.clone();
															var size = jQuery('#matrix_rowcount').val();
															if(size)
																	size = parseInt(size)+1;
															else
																	size = 0;


															$tr.find('select.multiselect').each(function(i){
																	
																	var selecteddata;
																	if(typeof wc_enhanced_select_params == 'undefined')
																			selecteddata = jQuery(this).chosen().val();
																	else
																			selecteddata = jQuery(this).select2('data');

																	if ( selecteddata ) {
																			var arr = [];
																			jQuery.each( selecteddata, function( id, text ) {
																				  
																					if(typeof wc_enhanced_select_params == 'undefined')
																							arr.push(text);
																					else
																							arr.push(text.id);											
																			});
																			var currentIdentifierAttr = jQuery(this).attr('data-identifier'); 
																			if(currentIdentifierAttr){
																					$clone.find("select[data-identifier='"+currentIdentifierAttr+"']").val(arr);
																					//$clone.find('select#' + this.id).val(arr);
																			}										
																	}
															});

															$tr.find('select.no_multiselect').each(function(i){
																	var selecteddata = [];
																	jQuery.each(jQuery(this).find("option:selected"), function(){		 
																			selecteddata.push(jQuery(this).val());
																	});

																	var currentIdentifierAttr = jQuery(this).attr('data-identifier'); 
																	if(currentIdentifierAttr){
																			$clone.find("select[data-identifier='"+currentIdentifierAttr+"']").val(selecteddata);
																	}
															});

															$tr.find('select.singleselect').each(function(i){
																	var selecteddata = jQuery(this).val();
																	if ( selecteddata ) {
																			var currentIdentifierAttr = jQuery(this).attr('data-identifier'); 
																			if(currentIdentifierAttr){
																					$clone.find("select[data-identifier='"+currentIdentifierAttr+"']").val(selecteddata);
																					//$clone.find('select#' + this.id).val(selecteddata);										
																			}
																	}
															});


															if(typeof wc_enhanced_select_params == 'undefined')
																	$clone.find('div.chosen-container, div.chzn-container').remove();									
															else
																	$clone.find('div.multiselect').remove();								

															$clone.find('.multiselect').show();
															$clone.find('.multiselect').removeClass("enhanced chzn-done");
															// find all the inputs within your new clone and for each one of those
															$clone.find('input[type=text], select').each(function() {
																	var currentNameAttr = jQuery(this).attr('name'); 
																	if(currentNameAttr){
																			var newNameAttr = currentNameAttr.replace(/\d+/, size);
																			jQuery(this).attr('name', newNameAttr);   // set the incremented name attribute 
																	}
																	var currentIdAttr = jQuery(this).attr('id'); 
																	if(currentIdAttr){
																			var currentIdAttr = currentIdAttr.replace(/\d+/, size);
																			jQuery(this).attr('id', currentIdAttr);   // set the incremented name attribute 
																	}
															});
															//$tr.after($clone);
															//$clone.find('select.chosen_select').trigger( 'chosen_select-init' );
															new_trs.push($clone);
															jQuery('#matrix_rowcount').val(size);
															//jQuery("select.chosen_select").trigger( 'chosen_select-init' );							
													});
													if(new_trs)
													{
															var lst_tr	= $tbody.find('.check-column :input:checkbox:checked:last').closest('tr');
															jQuery.each( new_trs.reverse(), function( id, text ) {
																			//adcd.after(text);
																			lst_tr.after(text);
																			if(typeof wc_enhanced_select_params == 'undefined')
																					text.find('select.chosen_select').chosen();			
																			else
																					text.find('select.chosen_select').trigger( 'wc-enhanced-select-init' );																	
																	});
													}
													$tbody.find('.check-column input:checked').removeAttr('checked');
													return false;
											});									
									});
							</script>
					</td>
																		   
			</tr>

													  
			<?php
			return ob_get_clean();
	}

	private function wf_get_zone_list(){
			$zone_list = array();
			if( class_exists('WC_Shipping_Zones') ){
					$zones_obj = new WC_Shipping_Zones;
					$zones = $zones_obj::get_zones();
					//$zone_list[0] = 'Rest of the World'; //rest of the zone always have id 0, which is not available in the method get_zone()
					foreach ($zones as $key => $zone) {
							$zone_list[$key] = $zone['zone_name'];
					}
			}
			return $zone_list;
	}
	private function wf_get_area_list()
			{
					$area_list=array();
					$area_matrix = array();
					$tmp=get_option('woocommerce_wf_multi_carrier_shipping_area_settings');
					$area_matrix=$tmp['area_matrix'];
					if(is_array($area_matrix))
					foreach ($area_matrix as $key => $area) {
							$area_list[$key]=$area['area_name'] ;
					}
					return $area_list;
			}
			
		public function wf_find_zone($package){
				$matching_zones=array();		
				if( class_exists('WC_Shipping_Zones') ){
						$zones_obj = new WC_Shipping_Zones;
						$matches = $zones_obj::get_zone_matching_package($package);
						array_push( $matching_zones, (WC()->version < '2.7.0')?$matches->get_zone_id():$matches->get_id() );
				}
				return $matching_zones;
		}
		
		
		
	function calculate_shipping( $package = array() ) 
			{	 
				
				if(strlen($this->settings['apikey'])===0)
					   {							   
							$this->debug( '<div style="background:red;color:white"> Warning: Multi Carrier Shipping Plugin:  Please Enter API KEY  in (Woocommerce>Settings>Shipping>Multi_Carrier_Shipping Page) </div>');
							return;
					   }
					   elseif(strlen($this->settings['apikey'])!==32)
					   {
							 $this->debug( '<div style="background:red;color:white"> Warning: Multi Carrier Shipping Plugin:  API KEY You Entered is Wrong it should be 32 in length in (Woocommerce>Settings>Shipping>Multi_Carrier_Shipping Page) </div>');
							return;
					   }
					$rules=array();
					if(!class_exists('eha_multi_carrier_shipping_rules_calculator'))
					include('class-eha-multi-carrier-shipping-rules-calculator.php');
					
					$grouped_rules=array();
					$options=get_option('woocommerce_wf_multi_carrier_shipping_settings');
					foreach ( $this->rate_matrix as $key => $box )
						{
								if(!isset($box['shipping_group']) || empty($box['shipping_group']) ||  !isset($options['show_shipping_group']) || $options['show_shipping_group']!='yes' )
								{
									$grouped_rules['primary'][$key]= $box;
								}
								else 
								{   
									$grp=$box['shipping_group'];
									$grouped_rules[$grp][$key]= $box;
								}
						}
					foreach($grouped_rules as $grp=>$rules)
					{   
						$calculator=new eha_multi_carrier_shipping_rules_calculator($rules,$package, $this->debug ,$grp );
						$cost=0;
						$cost=$calculator->calculate_shipping_cost();

						$all_index=array_keys($rules);
						$first_index=$all_index[0];
						$tmp=get_option('woocommerce_wf_multi_carrier_shipping_settings');
						$method_name=( sizeof($rules)==1 && !empty($rules[$first_index]['shipping_name']) ) ? $rules[$first_index]['shipping_name']:$tmp['title'];
						//die(print_r($rules));
						//error_log(sizeof($grouped_rules));
						if($cost>0)
						{
								$this->add_rate( array(
										   'id'		=> '1'.$grp,
										   'label'	 =>$method_name,
										   'cost'	  => $cost,
										   'taxes'	 => '',
										   'calc_tax'  => '0'
											   )
								   );	 
						}				 
					}
			   
	}


	//function to add states to the woocommerce states
	 function fedexresponcedefault($responce)
	{
		if(empty($responce))
		{
			$responce=array(					
"FEDEX_2_DAY"=>"FEDEX 2 DAY",
"FEDEX_2_DAY_AM"=>"FEDEX 2 DAY AM",
"FEDEX_DISTANCE_DEFERRED"=>"FEDEX DISTANCE DEFERRED",
"FEDEX_EXPRESS_SAVER"=>"FEDEX EXPRESS SAVER",
"FEDEX_GROUND"=>"FEDEX GROUND",
"FEDEX_NEXT_DAY_AFTERNOON"=>"FEDEX NEXT DAY AFTERNOON",
"FEDEX_NEXT_DAY_EARLY_MORNING"=>"FEDEX NEXT DAY EARLY MORNING",
"FEDEX_NEXT_DAY_END_OF_DAY"=>"FEDEX NEXT DAY END OF DAY",
"FEDEX_NEXT_DAY_MID_MORNING"=>"FEDEX NEXT DAY MID MORNING",
"FIRST_OVERNIGHT"=>"FIRST OVERNIGHT",
"GROUND_HOME_DELIVERY"=>"GROUND HOME DELIVERY",
"EUROPE_FIRST_INTERNATIONAL_PRIORITY"=>"EUROPE FIRST INTERNATIONAL PRIORITY",
"INTERNATIONAL_ECONOMY"=>"INTERNATIONAL ECONOMY",
"INTERNATIONAL_FIRST"=>"INTERNATIONAL FIRST",
"INTERNATIONAL_PRIORITY"=>"INTERNATIONAL PRIORITY",
"PRIORITY_OVERNIGHT"=>"PRIORITY OVERNIGHT",
"SAME_DAY"=>"SAME DAY",					
"SMART_POST"=>"SMART POST",
"SAME_DAY_CITY"=>"SAME DAY CITY",

"STANDARD_OVERNIGHT"=>"STANDARD OVERNIGHT"
			);
		}
		return $responce;
	}
   function upsresponcedefault($responce)
	{
		if(empty($responce))
		{
			$responce=array(   
											'01'=> 'UPS Next Day Air',
										   '02'=> 'UPS Second Day Air',
										   '03'=> 'UPS Ground',
										   '07'=> 'UPS Worldwide Express',
										   '08'=> 'UPS Worldwide Expedited',
										   '11'=> 'UPS Standard',
										   '12'=> 'UPS Three-Day Select',
										   '13'=> 'UPS Next Day Air Saver',
										   '14'=> 'UPS Next Day Air Early A.M.',
										   '54'=> 'UPS Worldwide Express Plus',
										   '59'=> 'UPS Second Day Air A.M.',
										   '65'=> 'UPS Saver',
										   '82'=> 'UPS Today Standard',
										   '83'=> 'UPS Today Dedicated Courier',
										   '84'=> 'UPS Today Intercity',
										   '85'=> 'UPS Today Express',
										   '86'=> 'UPS Today Express Saver'					 
									 );
		}
		return $responce;
	}
	 function usps_responcedefault($responce)
	{   //echo "<pre> usps"; print_r($responce); echo "</pre>";
		if(empty($responce))
		{
			$responce=array(	   
								'First-Class:LETTER'=>'First-Class:LETTER',
								'First-Class:FLAT'=>'First-Class:FLAT',
								'First-Class:PARCEL'=>'First-Class:PARCEL',
								'First-Class:POSTCARD'=>'First-Class:POSTCARD',
								'First-Class:PACKAGE-SERVICE'=>'First-Class:PACKAGE-SERVICE',
								'First-Class-Commercial:PACKAGE-SERVICE'=>'First-Class-Commercial:PACKAGE-SERVICE',
								'First-Class-HFP-Commercial:PACKAGE-SERVICE'=>'First-Class-HFP-Commercial:PACKAGE-SERVICE',
								'Priority'=>'Priority',
								'Priority-Commercial'=>'Priority-Commercial',
								'Priority-Cpp'=>'Priority-Cpp',
								'Priority-HFP-Commercial'=>'Priority-HFP-Commercial',
								'Priority-HFP-Cpp'=>'Priority-HFP-Cpp',
								'Priority-Mail-Express'=>'Priority-Mail-Express',
								'Priority-Mail-Express-Commercial'=>'Priority-Mail-Express-Commercial',
								'Priority-Mail-Express-Cpp'=>'Priority-Mail-Express-Cpp',
								'Priority-Mail-Express-HFP'=>'Priority-Mail-Express-HFP',
								'Priority-Mail-Express-HFP-Commercial'=>'Priority-Mail-Express-HFP-Commercial',
								'Standard-Post'=>'Standard-Post',
								'Retail-Ground'=>'Retail-Ground',
								'Media'=>'Media',
								'Library'=>'Library',
								'Online-Plus'=>'Online-Plus',
								'12'=>'Global-Express-Guaranteed',
								'1'=>'Priority-Mail-Express-International',
								'2'=>'Priority-Mail-International',
								'9'=>'Priority-Mail-International-Medium-Flat-Rate-Box',
								'11'=>'Priority-Mail-International-Large-Flat-Rate-Box',
								'16'=>'Priority-Mail-International-Small-Flat-Rate-Box',
								'15'=>'First-Class-Package-International-Service'
				);
		}
		return $responce;
	}
	function stamps_usps_responcedefault($responce)
	{   //echo "<pre> usps"; print_r($responce); echo "</pre>";
		if(empty($responce))
		{
			$responce=array(	   
										"US-PM:Package"=>"Priority Mail:Package",
										"US-PM:Postcard"=>"Priority Mail:Postcard",
										"US-PM:Thick.Envelope"=>"Priority Mail:Thick Envelope",
										"US-PM:Large.Package"=>"Priority Mail:Large Package",
										"US-PM:Small.Flat.Rate.Box"=>"Priority Mail:Small Flat Rate Box",
										"US-PM:Flat.Rate.Box"=>"Priority Mail:Flat Rate Box",
										"US-PM:Large.Flat.Rate.Box"=>"Priority Mail:Large Flat Rate Box",
										"US-PM:Flat.Rate.Envelope"=>"Priority Mail:Flat Rate Envelope",
										"US-PM:Flat.Rate.Padded.Envelope"=>"Priority Mail:Flat Rate Padded Envelope",
										"US-PM:Oversized.Package"=>"Priority Mail:Oversized Package",
										"US-XM:Package"=>"Priority Mail Express:Package",
										"US-XM:Postcard"=>"Priority Mail Express:Postcard",
										"US-XM:Thick.Envelope"=>"Priority Mail Express:Thick Envelope",
										"US-XM:Large.Package"=>"Priority Mail Express:Large Package",
										"US-XM:Small.Flat.Rate.Box"=>"Priority Mail Express:Small Flat Rate Box",
										"US-XM:Flat.Rate.Box"=>"Priority Mail Express:Flat Rate Box",
										"US-XM:Large.Flat.Rate.Box"=>"Priority Mail Express:Large Flat Rate Box",
										"US-XM:Flat.Rate.Envelope"=>"Priority Mail Express:Flat Rate Envelope",
										"US-XM:Flat.Rate.Padded.Envelope"=>"Priority Mail Express:Flat Rate Padded Envelope",
										"US-XM:Oversized.Package"=>"Priority Mail Express:Oversized Package",
										"US-FC:Package"=>"First-Class Mail:Package",
										"US-FC:Postcard"=>"First-Class Mail:Postcard",
										"US-FC:Thick.Envelope"=>"First-Class Mail:Thick Envelope",
										"US-FC:Large.Package"=>"First-Class Mail:Large Package",
										"US-FC:Small.Flat.Rate.Box"=>"First-Class Mail:Small Flat Rate Box",
										"US-FC:Flat.Rate.Box"=>"First-Class Mail:Flat Rate Box",
										"US-FC:Large.Flat.Rate.Box"=>"First-Class Mail:Large Flat Rate Box",
										"US-FC:Flat.Rate.Envelope"=>"First-Class Mail:Flat Rate Envelope",
										"US-FC:Flat.Rate.Padded.Envelope"=>"First-Class Mail:Flat Rate Padded Envelope",
										"US-FC:Oversized.Package"=>"First-Class Mail:Oversized Package",
										"US-MM:Package"=>"Media Mail:Package",
										"US-MM:Postcard"=>"Media Mail:Postcard",
										"US-MM:Thick.Envelope"=>"Media Mail:Thick Envelope",
										"US-MM:Large.Package"=>"Media Mail:Large Package",
										"US-MM:Small.Flat.Rate.Box"=>"Media Mail:Small Flat Rate Box",
										"US-MM:Flat.Rate.Box"=>"Media Mail:Flat Rate Box",
										"US-MM:Large.Flat.Rate.Box"=>"Media Mail:Large Flat Rate Box",
										"US-MM:Flat.Rate.Envelope"=>"Media Mail:Flat Rate Envelope",
										"US-MM:Flat.Rate.Padded.Envelope"=>"Media Mail:Flat Rate Padded Envelope",
										"US-MM:Oversized.Package"=>"Media Mail:Oversized Package",
										"US-PP:Package"=>"Parcel Post:Package",
										"US-PP:Postcard"=>"Parcel Post:Postcard",
										"US-PP:Thick.Envelope"=>"Parcel Post:Thick Envelope",
										"US-PP:Large.Package"=>"Parcel Post:Large Package",
										"US-PP:Small.Flat.Rate.Box"=>"Parcel Post:Small Flat Rate Box",
										"US-PP:Flat.Rate.Box"=>"Parcel Post:Flat Rate Box",
										"US-PP:Large.Flat.Rate.Box"=>"Parcel Post:Large Flat Rate Box",
										"US-PP:Flat.Rate.Envelope"=>"Parcel Post:Flat Rate Envelope",
										"US-PP:Flat.Rate.Padded.Envelope"=>"Parcel Post:Flat Rate Padded Envelope",
										"US-PP:Oversized.Package"=>"Parcel Post:Oversized Package",
										"US-PS:Package"=>"Parcel Select Ground:Package",
										"US-PS:Postcard"=>"Parcel Select Ground:Postcard",
										"US-PS:Thick.Envelope"=>"Parcel Select Ground:Thick Envelope",
										"US-PS:Large.Package"=>"Parcel Select Ground:Large Package",
										"US-PS:Small.Flat.Rate.Box"=>"Parcel Select Ground:Small Flat Rate Box",
										"US-PS:Flat.Rate.Box"=>"Parcel Select Ground:Flat Rate Box",
										"US-PS:Large.Flat.Rate.Box"=>"Parcel Select Ground:Large Flat Rate Box",
										"US-PS:Flat.Rate.Envelope"=>"Parcel Select Ground:Flat Rate Envelope",
										"US-PS:Flat.Rate.Padded.Envelope"=>"Parcel Select Ground:Flat Rate Padded Envelope",
										"US-PS:Oversized.Package"=>"Parcel Select Ground:Oversized Package",
										"US-LM:Package"=>"Library Mail:Package",
										"US-LM:Postcard"=>"Library Mail:Postcard",
										"US-LM:Thick.Envelope"=>"Library Mail:Thick Envelope",
										"US-LM:Large.Package"=>"Library Mail:Large Package",
										"US-LM:Small.Flat.Rate.Box"=>"Library Mail:Small Flat Rate Box",
										"US-LM:Flat.Rate.Box"=>"Library Mail:Flat Rate Box",
										"US-LM:Large.Flat.Rate.Box"=>"Library Mail:Large Flat Rate Box",
										"US-LM:Flat.Rate.Envelope"=>"Library Mail:Flat Rate Envelope",
										"US-LM:Flat.Rate.Padded.Envelope"=>"Library Mail:Flat Rate Padded Envelope",
										"US-LM:Oversized.Package"=>"Library Mail:Oversized Package",
										"US-EMI:Package"=>"Priority Mail Express International:Package",
										"US-EMI:Postcard"=>"Priority Mail Express International:Postcard",
										"US-EMI:Thick.Envelope"=>"Priority Mail Express International:Thick Envelope",
										"US-EMI:Large.Package"=>"Priority Mail Express International:Large Package",
										"US-EMI:Small.Flat.Rate.Box"=>"Priority Mail Express International:Small Flat Rate Box",
										"US-EMI:Flat.Rate.Box"=>"Priority Mail Express International:Flat Rate Box",
										"US-EMI:Large.Flat.Rate.Box"=>"Priority Mail Express International:Large Flat Rate Box",
										"US-EMI:Flat.Rate.Envelope"=>"Priority Mail Express International:Flat Rate Envelope",
										"US-EMI:Flat.Rate.Padded.Envelope"=>"Priority Mail Express International:Flat Rate Padded Envelope",
										"US-EMI:Oversized.Package"=>"Priority Mail Express International:Oversized Package",
										"US-PMI:Package"=>"Priority Mail International:Package",
										"US-PMI:Postcard"=>"Priority Mail International:Postcard",
										"US-PMI:Thick.Envelope"=>"Priority Mail International:Thick Envelope",
										"US-PMI:Large.Package"=>"Priority Mail International:Large Package",
										"US-PMI:Small.Flat.Rate.Box"=>"Priority Mail International:Small Flat Rate Box",
										"US-PMI:Flat.Rate.Box"=>"Priority Mail International:Flat Rate Box",
										"US-PMI:Large.Flat.Rate.Box"=>"Priority Mail International:Large Flat Rate Box",
										"US-PMI:Flat.Rate.Envelope"=>"Priority Mail International:Flat Rate Envelope",
										"US-PMI:Flat.Rate.Padded.Envelope"=>"Priority Mail International:Flat Rate Padded Envelope",
										"US-PMI:Oversized.Package"=>"Priority Mail International:Oversized Package",
										"US-FCI:Package"=>"First Class Mail International:Package",
										"US-FCI:Postcard"=>"First Class Mail International:Postcard",
										"US-FCI:Thick.Envelope"=>"First Class Mail International:Thick Envelope",
										"US-FCI:Large.Package"=>"First Class Mail International:Large Package",
										"US-FCI:Small.Flat.Rate.Box"=>"First Class Mail International:Small Flat Rate Box",
										"US-FCI:Flat.Rate.Box"=>"First Class Mail International:Flat Rate Box",
										"US-FCI:Large.Flat.Rate.Box"=>"First Class Mail International:Large Flat Rate Box",
										"US-FCI:Flat.Rate.Envelope"=>"First Class Mail International:Flat Rate Envelope",
										"US-FCI:Flat.Rate.Padded.Envelope"=>"First Class Mail International:Flat Rate Padded Envelope",
										"US-FCI:Oversized.Package"=>"First Class Mail International:Oversized Package",
									 );
		}
		return $responce;
	}
	function dhl_responcedefault($responce)
	{   
		if(empty($responce))
		{
			$responce=array(	   
										"0"=>"DOMESTIC EXPRESS 12=>00",
										"1"=>"B2C",
										"2"=>"B2C",
										"3"=>"JETLINE",
										"4"=>"SPRINTLINE",
										"5"=>"EXPRESS EASY",
										"6"=>"EXPRESS EASY",
										"7"=>"EUROPACK",
										"B"=>"BREAKBULK EXPRESS",
										"C"=>"MEDICAL EXPRESS",
										"D"=>"EXPRESS WORLDWIDE",
										"E"=>"EXPRESS 9=>00",
										"F"=>"FREIGHT WORLDWIDE",
										"G"=>"DOMESTIC ECONOMY SELECT",
										"H"=>"ECONOMY SELECT",
										"I"=>"DOMESTIC EXPRESS 9=>00",
										"J"=>"JUMBO BOX",
										"K"=>"EXPRESS 9=>00",
										"L"=>"EXPRESS 10=>30",
										"M"=>"EXPRESS 10=>30",
										"N"=>"DOMESTIC EXPRESS",
										"O"=>"DOMESTIC EXPRESS 10=>30",
										"P"=>"EXPRESS WORLDWIDE",
										"Q"=>"MEDICAL EXPRESS",
										"R"=>"GLOBALMAIL BUSINESS",
										"S"=>"SAME DAY",
										"T"=>"EXPRESS 12=>00",
										"U"=>"EXPRESS WORLDWIDE",
										"V"=>"EUROPACK",
										"W"=>"ECONOMY SELECT",
										"X"=>"EXPRESS ENVELOPE",
										"Y"=>"EXPRESS 12=>00"
									 );
		}
		return $responce;
	}
	
	public function wf_list_of_states($only_states=false)
	{
		global $woocommerce;
		$countries_obj   = new WC_Countries();
		$states = $countries_obj->get_states();
		$this->wf_custom_woocommerce_states($states);
		if($only_states)
		{
			$res=array();
			foreach($states as  $country=>$statelist)
			{
				if(!empty($statelist))
				{
					$res= array_merge($res,$statelist);
				}

			}
			return $res;
		}
		else
		{
			return $states;
		}
		
		
	}
	public function wf_custom_woocommerce_states( $states ) {
	  $states['IE'] = array(
			'CARLOW' => 'Carlow',
			'CAVAN' => 'Cavan',
			'CLARE' => 'Clare',
			'CORK' => 'Cork',
			'DONEGAL' => 'Donegal',
			'DUBLIN' => 'Dublin',
			'GALWAY' => 'Galway',
			'KERRY' => 'Kerry',
			'KILDARE' => 'Kildare',
			'KILKENNY' => 'Kilkenny',
			'LAOIS' => 'Laois',
			'LEITRIM' => 'Leitrim',
			'LIMERICK' => 'Limerick',
			'LONGFORD' => 'Longford',
			'LOUTH' => 'Louth',
			'MAYO' => 'Mayo',
			'MONAGHAM' => 'Monaghan',
			'OFFALY' => 'Offaly',
			'ROSCOMMON' => 'Roscommon',
			'SLIGO' => 'Sligo',
			'TRIPPERARY' => 'Tipperary',
			'WTERFORD' => 'Waterford',
			'WESTMEATH' => 'Westmeath',
			'WEXFORD' => 'Wexford',
			'WVICKLOW' => 'Wicklow'
	  );
	  return $states;
	}
	
	public  function debug( $message, $type = 'notice' ) {
		if ( $this->debug && !is_admin()) { //WF: is_admin check added.
			if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
				wc_add_notice( $message, $type );
			} else {
				global $woocommerce;
				$woocommerce->add_message( $message );
			}
		}
	}
}