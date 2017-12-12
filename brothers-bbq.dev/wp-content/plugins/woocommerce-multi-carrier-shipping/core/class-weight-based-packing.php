<?php
    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

class weight_based_packing{
    function __construct($product_weight,$product_quantity,$packing_process,$box_max_weight,$calculator_class) {
               
        if(empty($box_max_weight))
        {
            $box_max_weight=99999;
        }
        
        $this->only_flat_rate=false;
        $this->flatrate=array();
        $this->product_weight=$product_weight;
        $this->product_quantity=$product_quantity;
        $this->packing_process=$packing_process;
        $this->calculator_class=$calculator_class;
        $this->box_max_weight=$box_max_weight;
        $this->debug=$calculator_class->debug;
        $this->seq_no=0;
        $this->packages=array();
        $this->totalcost=0;
        $this->sort_weight_by_packing_process($packing_process);
        $this->init();
        //var_dump($packing_process);
        foreach($this->calculator_class->product_rule_mapping  as $rule_no=>$pids)
            {
                $company= $this->calculator_class->rules_array[$rule_no]['shipping_companies'];
                if($company=='flatrate')
                {
                         $service= '';
                }
                else {
                         $service= $this->calculator_class->rules_array[$rule_no]['shipping_services'];
                }
               
                if(isset($pids['validforall'])) 
                {
                    unset($pids['validforall']);
                }
                $this->createPackageAndAddToRequest($rule_no,$pids,$box_max_weight,$company,$service,$packing_process);
            }
           
        
         $this->create_req_from_packages();
         $this->debug( 'Packages: <pre>' . print_r( $this->packages , true ) . '</pre>' );
    }
    
    function createPackageAndAddToRequest($rule_no,$pids,$box_wt_max,$company,$service,$packing_process)
    {
           
            $package=array();
            $box_wt_empty=$box_wt_max;
             if($packing_process=='pack_simple')  
             {
                    $total_weight=0.0;
                    foreach($pids as $key=>$pid)
                    {   
                        if(array_search($pid, $this->calculator_class->executed_products)===false) 
                          {                     
                                $qnty=$this->product_quantity[$pid];
                                $wt=$this->product_weight[$pid];
                                $total_weight+=$wt * $qnty * 1.0;                                                    
                          }else
                          {
                              unset($pids[$key]);
                          }
                    }
                    
                    if($rule_no!==NULL)
                    { 
                            $this->create_packages_purely_divided_by_weight($pids,$total_weight,$company,$service,$rule_no,$box_wt_max);
                    }
             }
             else
             {     
                    foreach($pids as $key=>$pid)
                    {   
                        if(array_search($pid, $this->calculator_class->executed_products)!==false) 
                        {
                            unset($pids[$key]);
                            continue;
                        }
                        $qnty=$this->product_quantity[$pid];
                        $wt=$this->product_weight[$pid];
                        if($wt>=$box_wt_max)
                        {  
                            $this->add_to_package(array($pid),$wt,$company,$service,$qnty,$rule_no);
                        }
                        else
                        {   
                            for($i=0;$i<$qnty;$i++)
                            {
                                    if(!isset($package[$pid])) {  $package[$pid]=0;  }

                                    if($box_wt_empty>=$wt)
                                    {
                                        $package[$pid]++;
                                        $box_wt_empty-=$wt;
                                        $check_if_last_key=array_keys($pids);
                                        if($i+1==$qnty && end($check_if_last_key)==$key)
                                        {
                                           $box_wt=$box_wt_max - $box_wt_empty;
                                           $this->add_to_package(array_keys($package) ,$box_wt,$company,$service,1,$rule_no);
                                           $box_wt_empty=$box_wt_max;
                                           $package=array();                                       
                                        }
                                    }
                                    else
                                    {
                                        $box_wt=$box_wt_max - $box_wt_empty;
                                        $this->add_to_package(array_keys($package) ,$box_wt,$company,$service,1,$rule_no);
                                        $box_wt_empty=$box_wt_max;
                                        $package=array();
                                    }
                            }
                        }
                        
                    }
             }
    }
    private  function add_to_package($in_box_products_pids,$box_weight,$company,$service,$no_of_package,$rule_no)
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
                       
    function create_packages_purely_divided_by_weight($pids,$total_weight,$company,$service,$rule_no,$box_max_weight)
    {   
            //$unit_weight=($total_weight * 1.0)/($no_of_box * 1.0);
            
        if($company=='flatrate')
        {     foreach($pids as $_pid)
                    {
                            $this->flatrate[$rule_no][$_pid] = $this->calculator_class->rules_array[$rule_no]['fee'];                     
                    }              
        }
        else
        {
            while($total_weight>0)
            {
                $wt=($total_weight>$box_max_weight)?$box_max_weight:$total_weight;
                $total_weight-=$wt;
                 $this->packages[]=array(
                                                            "pid"=>implode(',',$pids),
                                                            "weight"=>round($wt, 2),
                                                            "company"=>$company,
                                                            "service"=>$service,
                                                            "no_of_packages"=>1,
                                                            "rule_no"=>$rule_no
                                                        );
                 
            }            
        }
         foreach($pids as $_pid)
         {
                if(array_search($_pid, $this->calculator_class->executed_products)===false) 
                 {                     
                      $this->calculator_class->executed_products[]=$_pid;
                 }                  
         }

           
        
    }

    function sort_weight_by_packing_process()
    {
        if($this->packing_process=='pack_descending')         // heavier first
        {
            arsort($this->product_weight);
        }
        elseif($this->packing_process=='pack_ascending')       // lighter first
        {
             asort($this->product_weight);
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

       /*        function add_product_to_req($pid,$rule_no,$company,$service)
              {             $this->seq_no+=1;
                            //$id=count($this->request['Request_Array']);                        
                            //$id+=1;
                                $packages=array();
                                $packages[]=array(
                                    "weight"=>$this->product_weight[$pid],
                                    "unit"=> 'LBS' ,                //ups
                                    "Description"=>"My Package",
                                    'no_of_packages'=>$this->product_quantity[$pid],
                                    'SequenceNumber'=> $this->seq_no,
                                );
                                
                                 $this->request['Request_Array'][]=array(    'id'=>"$pid:$rule_no",
                                                                'company'=>array($company),
                                                                'Weight_Units' => 'LB',             //fedex
                                                                "ServiceType"=> $service,
                                                                "RateRequestTypes"=>"NONE",     // fedex request type for(account rate or list rates)       here writing list is not working correctly it adds both account and list
                                                                "PackageCount"=>$this->product_quantity[$pid],
                                                                "packages"=>$packages
                                                            );
              }
              */
              
              
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
                {                //if($this->debug=='yes') {echo "<pre>Request"; print_r($this->request); echo "</pre>"; }
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
//                $curl = curl_init($url);
//                curl_setopt($curl, CURLOPT_HEADER, false);
//                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//                curl_setopt($curl, CURLOPT_HTTPHEADER,  array("Content-type: application/json"));
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
                                    if(is_array($rate)){

                                      foreach($rate as $irate){ 

                                      if(is_array($irate)){

                                        foreach($irate as $data){
                                          if(!is_array($data) ) {
                                            continue; 
                                          }

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
                 $this->debug( 'Flat Rate Calculation: <pre>' . print_r( $this->flatrate , true ) . '</pre>' );
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