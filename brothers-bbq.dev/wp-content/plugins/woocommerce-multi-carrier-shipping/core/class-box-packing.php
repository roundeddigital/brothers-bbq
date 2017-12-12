<?php
    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

class box_packing{
private $mode='volume_based';
    function __construct($product_quantity,$product_rule_mapping,$boxes,$calculator_class) {
        $this->only_flat_rate=false;
        $this->dim_unit="IN";
        $this->weight_unit="LBS";
        $this->flatrate=array();
        $this->product_quantity=$product_quantity;
        $this->calculator_class=$calculator_class;
        $this->debug=$calculator_class->debug;
        $this->seq_no=0;
        $this->packages=array();
        $this->totalcost=0;
        $this->init();
        $this->process_packing( $product_rule_mapping,$boxes,$calculator_class );
        $this->create_req_from_packages();
        if(count($this->packages)>0) $this->debug( '<span style="background:red;color:white;" >Rule Group='.$this->calculator_class->group_name.' </span> and Packages: <pre>' . print_r( $this->packages , true ) . '</pre>' );
    }
    
      private function process_packing( $product_rule_mapping,$boxes,$calculator_class ) 
	  {
			global $woocommerce;
			$pre_packed_contents = array();
			include('class-wf-legacy.php');
			if ( ! class_exists( 'WF_Boxpack' ) ) {
				include_once 'box_packing/class-wf-packing.php';
			}
			if ( ! class_exists( 'WF_Boxpack_Stack' ) ) {
				include_once 'box_packing/class-wf-packing-stack.php';
			}

			volume_based:
			if(isset($this->mode) && $this->mode=='stack_first'){
				$boxpack = new WF_Boxpack_Stack();
			}
			else{
				$boxpack = new WF_Boxpack($this->mode);
			}

			if ( ! empty( $boxes ) ) 
			{
				foreach ( $boxes as $box ) {
						$newbox = $boxpack->add_box( $box['length'], $box['width'], $box['height'], $box['box_weight'] );				
						$newbox->set_inner_dimensions( $box['inner_length'], $box['inner_width'], $box['inner_height'] );
						if ( $box['max_weight'] )
						$newbox->set_max_weight( $box['max_weight'] );
				}
			}
						// Add items
			$ctr = 0;
		  foreach ( $product_rule_mapping as $rule_no => $mapping )
		  { 
			$company= $this->calculator_class->rules_array[$rule_no]['shipping_companies'];
			foreach ( $mapping as $key=>$pid )                                                    
			{ 			
				if(!isset($this->flatrate[$rule_no])  || !is_array($this->flatrate[$rule_no] ))
				{
					$this->flatrate[$rule_no]=array();
				}
				
				if($company=='flatrate')
				{     foreach($mapping as $_key=>$_pid)
                                                                                        {
                                                                                                if($_key!=="validforall" && !in_array($_pid,$this->calculator_class->executed_products))
                                                                                                {
                                                                                                        $this->flatrate[$rule_no][$_pid] = $this->calculator_class->rules_array[$rule_no]['fee'];
                                                                                                         if(array_search($_pid, $this->calculator_class->executed_products)===false) 
                                                                                                                           {                     
                                                                                                                                        $this->calculator_class->executed_products[]=$_pid;
                                                                                                                           } 									
                                                                                                }

                                                                                        }              
				}
				else
				{
				if(array_search($pid, $calculator_class->executed_products)===false  && $key!=="validforall") 
					{                                           
					$calculator_class->executed_products[]=$pid;
					$product = new wf_product($pid);
					$qnty=$this->product_quantity[$pid];
					$ctr++;
					$skip_product = apply_filters('wf_multi_carrier_shipping_skip_product',false, $pid);
					if($skip_product) {    continue;   }
					
					if ( !( $qnty > 0 && $product->needs_shipping() ) ) 
					{
							$this->debug( sprintf( __( 'Product #%d is virtual. Skipping.', 'eha_multi_carrier_shipping' ), $ctr ) );
							continue;
					}
					if ( $product->length && $product->height && $product->width && $product->weight ) {
					
							$dimensions = array( $product->length, $product->height, $product->width );

							for ( $i = 0; $i < $qnty; $i ++ ) {
									$boxpack->add_item(
											number_format( wc_get_dimension( $dimensions[2], $this->dim_unit ), 2, '.', ''),
											number_format( wc_get_dimension( $dimensions[1], $this->dim_unit ), 2, '.', ''),
											number_format( wc_get_dimension( $dimensions[0], $this->dim_unit ), 2, '.', ''),
											number_format( wc_get_weight( (float)$product->get_weight(), $this->weight_unit ), 2, '.', ''),
											$product->get_price(),
											$product // Adding Item as meta
									);
							}

					} else {
					$this->debug( sprintf( __( 'Parcel Packing Method is set to Pack into Boxes. Product #%d is missing dimensions. Aborting.', 'eha_multi_carrier_shipping' ), $ctr ), 'error' );
							return;
					}
				}
			  }
			 }
			   
			// Pack it
			$boxpack->pack();
			// Get packages
			$box_packages = $boxpack->get_packages();

			if(isset($this->mode) && $this->mode=='stack_first')
			{ 
				foreach($box_packages as $key => $box_package)
				{  
					$box_volume=$box_package->length * $box_package->width * $box_package->height ;
					$box_used_volume=$box_package->volume;
					$box_used_volume_percentage=($box_used_volume * 100 )/$box_volume;
					if(isset($box_used_volume_percentage) && $box_used_volume_percentage<44)
					{   
						$this->mode='volume_based';
						$this->debug( '(FALLBACK) : Stack First Option changed to Volume Based' );
						goto volume_based;
						break;
					}
				}
			}
			$ctr=0;
			foreach ( $box_packages as $key => $box_package ) 
			{                   
				$ctr++;
				//$this->debug( "PACKAGE " . $ctr . " (" . $key . ")\n<pre>" . print_r( $box_package,true ) . "</pre>", 'error' );
				$weight     = $box_package->weight;
				$dimensions = array( $box_package->length, $box_package->width, $box_package->height );
				sort( $dimensions );
                                                                                $service='';
				if($this->calculator_class->rules_array[$rule_no]['shipping_companies']!=='flatrate')
                                                                                {
                                                                                    $service= $this->calculator_class->rules_array[$rule_no]['shipping_services'];
                                                                                    $this->add_to_package($mapping,$weight,$dimensions[0],$dimensions[1],$dimensions[2],$company,$service,1,$rule_no);
                                                                                    $this->seq_no=$this->seq_no+1;
                                                                                    // Getting packed items
                                                                                    $packed_items	=	array();
                                                                                    if(!empty($box_package->packed) && is_array($box_package->packed))
                                                                                    {				
                                                                                            foreach( $box_package->packed as $item ) {
                                                                                                    $item_product	=	$item->meta;
                                                                                                    $packed_items[] = $item_product;					
                                                                                            }
                                                                                    }
                                                                                }
                                                                                


			}
		}			
		


		//die("<pre>".print_r($this->request,true)."</pre>");
    }

    private  function add_to_package($in_box_products_pids,$box_weight,$length,$width,$height,$company,$service,$no_of_package,$rule_no)
    {
        //echo "<pre> adding these products into package";print_r($in_box_products_pids);echo "</pre>";
        if(!isset($this->flatrate[$rule_no])  || !is_array($this->flatrate[$rule_no] ))
        {
            $this->flatrate[$rule_no]=array();
        }
        
        if($company=='flatrate')
        {     foreach($in_box_products_pids as $_pid)
                    {
                            $this->flatrate[$rule_no][$_pid] = $this->calculator_class->rules_array[$rule_no]['fee'];
                             if(array_search($_pid, $this->calculator_class->executed_products)===false) 
                                       {                     
                                            $this->calculator_class->executed_products[]=$_pid;
                                       }                           
                    }              
        }
        else
        {
                                $this->packages[]=array(
                                                                                "pid"=> implode(",", $in_box_products_pids),
                                                                                "weight"=>$box_weight,
																				"length"=>$length,
																				"width"=>$width,
																				"height"=>$height,
                                                                                "company"=>$company,
                                                                                "service"=>$service,
                                                                                "no_of_packages"=>$no_of_package,
                                                                                "rule_no"=>$rule_no
                                                                            ); 
                                foreach($in_box_products_pids as $_pid)
                               {
                                        if(array_search($_pid, $this->calculator_class->executed_products)===false) 
                                                  {                     
                                                       $this->calculator_class->executed_products[]=$_pid;                                                       
                                                  }                           
                               } 
        }      
    }


	  private function init()
	  {   
		  $settings= $this->calculator_class->settings;
		   $is_residential='false';
		  if(isset($settings['is_recipient_address_residential']))
		  {
			  $is_residential=$settings['is_recipient_address_residential']=='yes'?'true':'false';
		  }
		if($this->calculator_class->is_residential==1)
		  {
			  $is_residential='true';
		  }
		  
		  if($settings['test_mode']=="yes")
		  {
			  $environment='sandbox';
		  }
		  else
		  {
			  $environment='live';
		  }
		  $cdate=getdate();
                    if(!empty($settings['origin_country_state']))
                    {
                        $data=$settings['origin_country_state'];
                        $countryState=explode(':',$data);
                        $country=!empty($countryState[0])?$countryState[0]:'';
                        $state=!empty($countryState[1])?$countryState[1]:'';                
                    }elseif(!empty($settings['origin_country']) && !empty($settings['origin_custom_state'])  )
                    {
                        $country=$settings['origin_country'];
                        $state=$settings['origin_custom_state'];
                    }else
                    {
                        $country='US';
                        $state='CA';
                    }
		  $this->request=array(
							'Common_Params'=>array(
							'environment' => $environment,
							'emailid'=>$settings['emailid'],
							'key'=>$settings['apikey'],
							'host'=>    $_SERVER['HTTP_HOST']." ".$cdate['mday'],
							'os'=>php_uname() ,
							'currency'=>get_option('woocommerce_currency'),
							'Shipper_PersonName' =>  '',
							'Shipper_CompanyName' => '',
							'Shipper_PhoneNumber' => $settings['phone_number'],
							'Shipper_Address_StreetLines' => $settings['origin_addressline'],
							'Shipper_Address_City' => $settings['origin_city'],
							'Shipper_Address_StateOrProvinceCode' => $state,
							'Shipper_Address_PostalCode' => $settings['origin_postcode'],
							'Shipper_Address_CountryCode' => $country,
							'Recipient_PersonName' => 'Recipient Name',
							'Recipient_CompanyName' => '',
							'Recipient_PhoneNumber' => '',
							'Recipient_Address_StreetLines' => '',
							'Recipient_Address_City' =>'',
							'Recipient_Address_StateOrProvinceCode' => $this->calculator_class->state,
							'Recipient_Address_PostalCode' =>$this->calculator_class->postalcode,
							'Recipient_Address_CountryCode' =>$this->calculator_class->country,
							'Recipient_Address_CountryName' =>WC()->countries->countries[$this->calculator_class->country],
							'Recipient_Address_Residential' => $is_residential,
							 'fedex_key' => $settings['fedex_api_key'],
							'fedex_password' => $settings['fedex_api_pass'],
							'fedex_account_number' => $settings['fedex_account_number'],
							'fedex_meter_number' => $settings['fedex_meter_number'],
							'fedex_smartpost_indicia' => isset($settings['fedex_smartpost_indicia'])?$settings['fedex_smartpost_indicia']:'',
							'fedex_smartpost_hubid' => isset($settings['fedex_smartpost_hubid'])?$settings['fedex_smartpost_hubid']:'',
							'ups_key' => $settings['ups_access_key'],
							'ups_password' => $settings['ups_password'],
							'ups_account_number' => $settings['ups_account_number'],
							'ups_username'=>$settings['ups_user_id'],
							'usps_username'=>$settings['usps_user_id'],
							'usps_password'=>$settings['usps_password'],
							'stamps_usps_username'=>$settings['stamps_usps_user_id'],
							'stamps_usps_password'=>$settings['stamps_usps_password'],
							'dhl_account_number'=>$settings['dhl_account_number'],
							'dhl_siteid'=>$settings['dhl_siteid'],
							'dhl_password'=>$settings['dhl_password'],                                                              
							),

							'Request_Array'=>array(
																	),
				);
	  }

              
    function create_req_from_packages()
          {
                $this->seq_no+=1;
                $packages=array();
                $seq_no=0;
                $PackageCount=0;
                if(count($this->packages)==0)
                {
                    $this->only_flat_rate=true;
                    return 0;
                }
                foreach( $this->packages as $_package)
                {   unset($packages);
                    $packages=array();
                    $seq_no+=1;
                    $PackageCount+=1;
                    $no_of_packages=$_package['no_of_packages'];
                    $company=$_package['company'];
                    $service=$_package['service'];
                    $weight=$_package['weight'];
                    $length=(int)$_package['length'];
                    $width=(int)$_package['width'];
                    $height=(int)$_package['height'];
                    $pid=$_package['pid'];
                    $rule_no=$_package['rule_no'];
                    
                        $package_limit=25;
                            if($company=='ups')
                            {
                                $package_limit=200;
                            }
                            elseif($company=='fedex')
                            {
                                $package_limit=999;
                            }elseif($company=='usps')
                            {
                                $package_limit=25;
                            }
                            elseif($company=='stamps_usps')
                            {
                                $package_limit=100;
                            }
                            elseif($company=='dhl')
                            {
                                $package_limit=99;
                            }
                            
                            //echo "no of package=$no_of_package  and package limit=$package_limit";
                            //if($no_of_package>$package_limit)
                            //{
                                while($no_of_packages>0)
                                {   
                                        $packages=array();
                                        $packages[]=array(
                                                            "weight"=>$this->convert_to_lbs($weight),
                                                            "unit"=> 'LBS' ,                //ups
                                                            "Description"=>"My Package",
															"length"=>$length,
															"width"=>$width,
															"height"=>$height,
                                                            'no_of_packages'=> ($no_of_packages>$package_limit)?$package_limit:$no_of_packages,
                                                            'SequenceNumber'=>$seq_no
                                                            );
                                        $this->request['Request_Array'][]=array(    'id'=>"$pid:$rule_no",
                                                                                    'company'=>array($company),
                                                                                    'Weight_Units' => 'LB',             //fedex
                                                                                    "ServiceType"=> $service,
                                                                                    "RateRequestTypes"=>"NONE",     // fedex request type for(account rate or list rates)       here writing list is not working correctly it adds both account and list
                                                                                    //"PackageCount"=>$PackageCount,
                                                                                    "packages"=>$packages
                                                                                );
                                        $seq_no=$seq_no+1;
                                   
                                            if($no_of_packages>$package_limit)                                      
                                                {
                                                $no_of_packages-=$package_limit;
                                                }
                                                else
                                                {
                                                    $no_of_packages=0;
                                                }                                        
                                }
                            //}
                    
                      
                }
                               

              }
            
              
            function convert_to_lbs($weight)
              {
                  $shop_unit=get_option('woocommerce_weight_unit');
                  if($shop_unit=="lbs")
                  {
                      return $weight;
                  }
                  elseif($shop_unit=="kg")
                  {
                      $weight=floatval($weight) * 2.20462262185;
                  }
                  elseif($shop_unit=="g")
                  {
                      $weight=floatval($weight) * 0.00220462262185;
                  }
                  elseif($shop_unit=="oz")
                  {
                      $weight=floatval($weight) / floatval(16);
                  }
                  return $weight;
              }
              function get_Rates_From_Api()
              {     
                $cost=0;
                if($this->only_flat_rate==false)
                {
                        //if($this->debug=='yes') {echo "<pre>Request"; print_r($this->request); echo "</pre>"; }
                        $this->debug( 'Request: <pre>' . print_r( $this->request , true ) . '</pre>' );
                        $url = $GLOBALS['eha_API_URL']."/api/shippings/rates";     
                        //$url = "http://localhost:3000/api/shippings/rates";     
                        $content = json_encode($this->request);
                        $this->debug("JSON Request<pre>Request: ".$content." </pre>"); 
                        $req=array();
                        $req['emailid']=$this->request['Common_Params']['emailid'];
                        $req['data']=$this->encode($this->request['Common_Params']['key'],$content);
                        $content =json_encode($req);
                        $this->debug( "Encoded Request<pre>Request ".$content." </pre>"); 
//                        $curl = curl_init($url);
//                        curl_setopt($curl, CURLOPT_HEADER, false);
//                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//                        curl_setopt($curl, CURLOPT_HTTPHEADER,  array("Content-type: application/json"));
//
//
//                        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//                        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
//                        curl_setopt($curl, CURLOPT_POST, true);
//                        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
//
//                        $json_response = curl_exec($curl);
//
//                        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                $response=wp_remote_post( $url, array(
                    'method' => 'POST',
                    'timeout' => 10,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'body' => $content,
                    'cookies' => array(),
                    'sslverify' => false
                    ) );
		if ( is_wp_error( $response ) ) {	
                        $error_string = $response->get_error_message();
                        $this->debug( 'Response Failed: <pre>' . print_r( htmlspecialchars( $error_string ), true ) . '</pre>' );
			return 0;
                }
                $status=wp_remote_retrieve_response_code($response);
                $tmp=$response['body'];
                $json_response=json_decode($tmp, true);
                $response=$json_response;
                if ( $status != 200 ) 
                {
                    if(isset($json_response['error'])) $this->debug($json_response['error']); return 0;
                    $this->debug("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
                }
                $cost=0;
                $this->debug("<pre>Responce".print_r($response,true)."</pre>");

                         if(isset($response['rates']))
                        {

                                    foreach($response['rates'] as $PidAndRuleNo=>$rate)
                                    {

                                        //echo $PidAndRuleNo;
                                       $exp=explode(":",$PidAndRuleNo);
                                        $pid=$exp[0];
                                        if(isset($exp[1]))
                                        {
                                            $rule_no=$exp[1];
                                        }
                                            if(is_array($rate))
                                            {   foreach($rate as $irate)
                                            {    
                                               if(is_array($irate))
                                               {  foreach($irate as $data)
                                                {  if(!is_array($data) ) { continue; }
                                                foreach($data as $package_rate)
                                                {    
                                                        if(isset($package_rate['TotalNetChargeWithDutiesAndTaxes']))
                                                            {
                                                                $cost+=$package_rate['TotalNetChargeWithDutiesAndTaxes']['Amount'];
                                                                foreach(explode(',',$pid) as $p)
                                                                {
                                                                      $this->calculator_class->executed_products[]=$p;
                                                                }
                                                                $this->calculator_class->rules_executed_successfully[]=$rule_no;
                                                            }
                                                }
                                                        }
                                               }
                                            }                         
                                        }
                                    }
                        }                   
                }                
                if(count(current($this->flatrate))>0) $this->debug( '<span style="background:red;color:white;" >Rule Group='.$this->calculator_class->group_name.' </span> and Flat Rate Calculation : <pre>' . print_r( $this->flatrate , true ) . '</pre>' );
                foreach($this->flatrate as $f_pids)
                {
                    foreach($f_pids as $f_pid=>$f_rate)
                    {
                        $cost+=($this->product_quantity[$f_pid] * $f_rate );
                    }
                }
                return $cost;
              }
              
        function encode($key,$data)
            {
                $encryptionMethod = "AES-256-CBC";
                $iv = substr($key, 0, 16);
                if (version_compare(phpversion(), '5.3.2', '>')) {
                    $encryptedMessage = openssl_encrypt($data, $encryptionMethod,$key,OPENSSL_RAW_DATA,$iv);                    
                }else
                {
                    $encryptedMessage = openssl_encrypt($data, $encryptionMethod,$key,OPENSSL_RAW_DATA);                    
                }
                return bin2hex($encryptedMessage);
            }
            
        public  function debug( $message, $type = 'notice' ) 
                {
    	if ( $this->debug && !is_admin()) 
                            { 
    		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
    			wc_add_notice( $message, $type );
    		} else {
    			global $woocommerce;
    			$woocommerce->add_message( $message );
    		}
                            }
                }
}