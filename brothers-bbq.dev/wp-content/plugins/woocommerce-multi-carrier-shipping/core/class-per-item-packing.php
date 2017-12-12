<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }
    
    class per_item_packing{
        
            function __construct($product_weight,$product_quantity,$calculator_class) {
                  $this->request=array();
                  $this->flatrate=array();
                  $this->only_flat_rate=false;
                  $this->product_weight=$product_weight;
                  $this->product_quantity=$product_quantity;
                  $this->calculator_class=$calculator_class;
                  $this->debug=$calculator_class->debug;
                  $this->packages=array();
                  $this->seq_no=0;
                  $this->init();
                  /*echo "<pre>";
                  print_r($this->request);
                  echo "</pre>";      /* */
                  //$this->get_Rates_From_Api();
              }
              
            function per_item_packing_add_package_to_request($bucket, $company,$service,$rule_no)
                    {   
                        $this->flatrate[$rule_no]=array();
                        if($company=='flatrate')
                            {    $this->calculator_class->rules_executed_successfully[]=$rule_no;
                                 foreach($bucket as $_pid)
                                 {
                                $this->flatrate[$rule_no][$_pid] = $this->calculator_class->rules_array[$rule_no]['fee'];
                                        if(array_search($_pid, $this->calculator_class->executed_products)===false) 
                                            {                     
                                                 $this->calculator_class->executed_products[]=$_pid;
                                                 //echo "adding $_pid into executed array";
                                            }
                                 }
                            }
                            else
                            {   
                                 foreach($bucket as $_pid){
                                     $this->add_product_to_req($_pid,$rule_no,$company,$service);
                                 }
                            }
                            if(count($this->packages)==0)
                            {
                                $this->only_flat_rate=true;
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
                                                                              /*      array(  'id'=>'1',
                                                                                                'company'=>array($this->comapny),
                                                                                                'Weight_Units' => 'LB',
                                                                                                'Weight_Value' => '50',
                                                                                                'Dimensions_Length' => '108',
                                                                                                'Dimensions_Width' => '5',
                                                                                                'Dimensions_Height' => '5',
                                                                                                'Dimensions_Units' => 'IN',
                                                                                                'TotalWeight_Units' => 'LB',
                                                                                                'TotalWeight_Value' => '50',
                                                                                                "ServiceType"=> $this->service,
                                                                                                //"RateRequestTypes"=> "LIST",
                                                                                            ),
                                                                                    array(  'id'=>'2',
                                                                                                'company'=>array('fedex','ups'),
                                                                                                'Weight_Units' => 'LB',
                                                                                                'Weight_Value' => '40',
                                                                                                'Dimensions_Length' => '108',
                                                                                                'Dimensions_Width' => '5',
                                                                                                'Dimensions_Height' => '5',
                                                                                                'Dimensions_Units' => 'IN',
                                                                                                'TotalWeight_Units' => 'LB',
                                                                                                'TotalWeight_Value' => '40'
                                                                                            ), */
                                                                            ),
                        );
              }
              
              function add_product_to_req($pid,$rule_no,$company,$service)
              {             
                            $this->seq_no+=1;        
                            $package_limit=0;                            
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
                            
                            if($this->product_quantity[$pid]>$package_limit)
                            {
                                $no_of_packages=$this->product_quantity[$pid];
                               while($no_of_packages>0)
                                {
                                    
                                 $packages=array();
                                
                                $package=array(
                                                                    "weight"=>$this->convert_to_lbs($this->product_weight[$pid]),
                                                                    "unit"=> 'LBS' ,
                                                                    'no_of_packages'=>($no_of_packages>$package_limit)?$package_limit:$no_of_packages,
                                                                    'SequenceNumber'=> $this->seq_no,
                                                                );   
                                $packages[]=$package;
                                $this->packages[]=$package;
                                 $this->request['Request_Array'][]=array(    'id'=>"$pid:$rule_no",
                                                                                                        'company'=>array($company),
                                                                                                        'Weight_Units' => 'LB',
                                                                                                        "ServiceType"=> $service,
                                                                                                        "RateRequestTypes"=>"NONE",     // fedex request type for(account rate or list rates)       here writing list is not working correctly it adds both account and list
                                                                                                        //"PackageCount"=>($no_of_packages>$package_limit)?$package_limit:$no_of_packages,
                                                                                                        "packages"=>$packages
                                                                                                    );  
                                                            $this->seq_no++;
                                            if($no_of_packages>$package_limit)                                      
                                                {
                                                $no_of_packages-=$package_limit;
                                                }
                                                else
                                                {
                                                    $no_of_packages=0;
                                                }                                        
                                
                               
                                }
                               $this->debug("<pre> packages print".print_r($packages,true)."</pre>");
                               
               
                            }
                            else
                            {
                                
                                $packages=array();
                                $package=array(
                                                                    "weight"=>$this->convert_to_lbs($this->product_weight[$pid]),
                                                                    "unit"=> 'LBS' ,
                                                                    'no_of_packages'=>$this->product_quantity[$pid],
                                                                    'SequenceNumber'=> $this->seq_no,
                                                                );
                                $packages[]=$package;
                                $this->packages[]=$package;
                                
                                 $this->request['Request_Array'][]=array(    'id'=>"$pid:$rule_no",
                                                                                                        'company'=>array($company),
                                                                                                        'Weight_Units' => 'LB',
                                                                                                        "ServiceType"=> $service,
                                                                                                        "RateRequestTypes"=>"NONE",     // fedex request type for(account rate or list rates)       here writing list is not working correctly it adds both account and list
                                                                                                        //"PackageCount"=>$this->product_quantity[$pid],
                                                                                                        "packages"=>$packages
                                                                                                    );
                            }
                            
//                            echo "<pre>";
//                            print_r($this->request['Request_Array']);
//                            echo "</pre>";
//                            die();
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
                if($this->only_flat_rate==false)
                {   
                $this->debug("<pre>Request".print_r($this->request,true)."</pre>");
                $url = $GLOBALS['eha_API_URL']."/api/shippings/rates";     
                //$url = "http://localhost:3000/api/shippings/rates";     
                
                $content = json_encode($this->request);
                $req=array();
                $req['emailid']=$this->request['Common_Params']['emailid'];
                $req['data']=$this->encode($this->request['Common_Params']['key'],$content);
                $content =json_encode($req);
                $this->debug("<pre>Request".$content."</pre>"); 
//                $curl = curl_init($url);
//                curl_setopt($curl, CURLOPT_HEADER, false);
//                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//                curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
//
//
//                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
//                curl_setopt($curl, CURLOPT_POST, true);
//                curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
//
//                $json_response = curl_exec($curl);
//
//                $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
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
                try
                {

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
                                        {   if(!is_array($data) ) { continue; }
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
                } catch (Exception $ex) {

                }
              }

                 $this->debug("Flat Rate  as( Rule=>( Productid=>Cost)) </br><pre>".print_r($this->flatrate,true)."</pre>");
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