<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

class eha_multi_carrier_shipping_rules_calculator {

     function __construct($rules_array,$package,$debug,$group_name='') 
             {
                $this->group_name=$group_name;
                $this->debug=$debug;
                $this->rule_product_mapping=array();
                $this->rules_array=$rules_array;
                $this->country=$package['destination']['country'];
                $this->state=$package['destination']['state'];
                $this->postalcode=$package['destination']['postcode'];
                if( class_exists('WC_Shipping_Zones') ){
                $this->zone=WC_Shipping_Zones::get_zone_matching_package($package);
                }
                $this->shipping_class=array();
                $this->category=array();
                $this->line_items=$package['contents'];
                $this->total_units=0;
                $this->total_weight=0;
                $this->total_price=0;
                $this->product_weight=array();
                $this->product_quantity=array();
                $this->product_price=array();
                $this->rules_executed_successfully= array();
                $this->empty_responce_shipping_cost=50;
                $this->settings=get_option('woocommerce_wf_multi_carrier_shipping_settings');
                $this->executed_products=array();
                $this->product_rule_mapping=array();
                $post_data=array();
                if ( isset( $_POST['post_data'] ) ) 
                {
                    parse_str( $_POST['post_data'], $post_data );
                } 
                $recipient_addresss_residential=-1;
                if(is_array($post_data) && isset($post_data['eha_is_residential']))
                {   
                    $recipient_addresss_residential=$post_data['eha_is_residential'];
                }
                $this->is_residential=$recipient_addresss_residential;
                foreach($this->line_items as $line_item)
                {   
                            $product=$line_item['data'];
                            $quantity=$line_item['quantity'];
                            $weight=(float)$product->get_weight();
                            $price=$product->get_price();                            
                            $pid=(WC()->version < '2.7.0')?$product->id:$product->get_id();  
                            $unit_qnty= apply_filters( 'wf_multi_carrier_item_quantity', $quantity,$pid);
                            $cur_total_weight= $weight * $quantity;
                            $cur_total_price=$price * $quantity;
                            $this->total_units+=$unit_qnty;
                            $this->total_weight+=$weight * $quantity;
                            $this->total_price+=$cur_total_price;
                            
                                 
                            if(!isset($this->product_weight[$pid]))
                            {
                                $this->product_weight[$pid]=0;
                            }
                            if(!isset($this->product_quantity[$pid]))
                            {
                                $this->product_quantity[$pid]=0;
                            }
                            if(!isset($this->product_price[$pid]))
                            {
                                $this->product_price[$pid]=0;
                            }
                            $this->product_weight[$pid]=$weight;
                            $this->product_quantity[$pid]+=$quantity;
                            $this->product_price[$pid]=$price;
                            if ( $product->needs_shipping() ) 
                                {
                                    $cur_shipping_class='';
                                    try {
                                            $cur_shipping_class=$product->get_shipping_class();
                                    } catch (Exception $e) {

                                    }                                    

                                    if(empty($cur_shipping_class) && ($product instanceof WC_Product_Variation))
                                    {
                                            $parent_data=$product->get_parent_data();	
                                            $cur_shipping_class_id='';
                                            if(isset($parent_data['shipping_class_id']))
                                            {
                                                    $cur_shipping_class_id=$parent_data['shipping_class_id'];
                                            }

                                            $term = get_term_by( 'id', $cur_shipping_class_id, 'product_shipping_class' );
                                            if ( $term && ! is_wp_error( $term ) ) 
                                            {
                                            $cur_shipping_class= $term->slug;
                                            }

                                    }

                                    $id=(WC()->version < '2.7.0')?$product->id:$product->get_id();
                                    $cur_category_list=wp_get_post_terms( $id, 'product_cat', array( "fields" => "ids" ) );
                                    $this->add_meta_in_shipping_class($cur_shipping_class,$cur_total_weight,$cur_total_price,$unit_qnty,$pid);
                                    $this->add_meta_in_category($cur_category_list,$cur_total_weight,$cur_total_price,$unit_qnty,$pid);  
                                }
                    }
                $this->filter();
        }
     
        function add_meta_in_shipping_class($cur_shipping_class,$weight,$price,$quantity,$pid)
        {
            if(!isset($this->shipping_class[$cur_shipping_class]))
                            {
                                $this->shipping_class[$cur_shipping_class]['weight']=0;
                                $this->shipping_class[$cur_shipping_class]['price']=0;
                                $this->shipping_class[$cur_shipping_class]['item']=0;                                
                            }
                                $this->shipping_class[$cur_shipping_class]['weight']+=$weight;
                                $this->shipping_class[$cur_shipping_class]['price']+=$price;
                                $this->shipping_class[$cur_shipping_class]['item']+= $quantity;            
                                $this->shipping_class[$cur_shipping_class]['pid'][]=$pid;
        }
         function add_meta_in_category($cur_category_list,$weight,$price,$quantity,$pid)
        {
                foreach($cur_category_list as $category)
               {
                    if(!isset($this->category[$category]))
                    {
                        $this->category[$category]['weight']=0;
                        $this->category[$category]['price']=0;
                        $this->category[$category]['item']=0;                                
                    }
                    $this->category[$category]['weight']+=$weight;
                    $this->category[$category]['price']+=$price;
                    $this->category[$category]['item']+= $quantity;        
                    $this->category[$category]['pid'][]= $pid;              
               }
               /*echo "<pre>";
               print_r($this->category);
               echo "</pre>";
                * 
                */
          
        }
        function remove_meta_in_shipping_class($cur_shipping_class,$weight,$price,$quantity,$pid)
        {
                                $this->shipping_class[$cur_shipping_class]['weight']-=$weight;
                                $this->shipping_class[$cur_shipping_class]['price']-=$price;
                                $this->shipping_class[$cur_shipping_class]['item']-= $quantity;    
                                if(($key = array_search($pid, $this->shipping_class[$cur_shipping_class]['pid'])) !== false) 
                                {
                                    unset($this->shipping_class[$cur_shipping_class]['pid'][$key]);
                                }
        }
         function remove_meta_in_category($cur_category_list,$weight,$price,$quantity,$pid)
        {
                foreach($cur_category_list as $category)
               {
                                $this->category[$category]['weight']-=$weight;
                                $this->category[$category]['price']-=$price;
                                $this->category[$category]['item']-= $quantity;    
                                if(($key = array_search($pid, $this->category[$category]['pid'])) !== false) 
                                        {
                                            unset($this->category[$category]['pid'][$key]);
                                        }
               }
        }
        
      function filter() 
             {
                    $order_total=array();
                    $order_total['weight']=$this->total_weight;
                    $order_total['item']=$this->total_weight;
                    $order_total['price']=$this->total_price;
                    
                    foreach($this->rules_array as $key=>$rule)
                        {
                        $area_list=$rule['area_list'];
                        if(empty($area_list))
                        {
                            continue;
                        }
                        foreach($area_list as $_area_key)
                        { 
                           if( $this->check_rule_area($_area_key,$this->country,$this->state,$this->postalcode)===true)
                           {   
                               if(isset($rule['cost_based_on']))
                               {
                                   $based_on=$rule['cost_based_on'];
                               }
                               else
                               {
                                   $based_on='weight';
                               }
                               if(!isset($rule['min_weight'] )  || empty($rule['min_weight']))
                               {
                                   $rule['min_weight']=0.0;
                               }
                                if(!isset($rule['max_weight'] ) || empty($rule['max_weight'] ))
                               {
                                   $rule['max_weight'] =9999;
                               }
                               if(isset( $rule['shipping_class']) && !empty($rule['shipping_class']) )
                               {    $shipping_class_list=$rule['shipping_class'];
                                    foreach($shipping_class_list as $_shipping_class)
                                       {    
                                            if($_shipping_class=='any_shipping_class')
                                            {       
                                                if(!empty($rule['min_weight']) || !empty($rule['max_weight']))
                                                {
                                                    if((isset($rule['min_weight']) && $order_total[$based_on]>=$rule['min_weight'])!==true)
                                                    {
                                                        continue(3); 
                                                    }
                                                    if((isset($rule['max_weight'])  && !empty($rule['max_weight'])  && $order_total[$based_on]<=$rule['max_weight'])!==true)
                                                    {
                                                        continue(3); 
                                                    }                                               
                                                }
                                                $this->product_rule_mapping[$key]['validforall']=1;
                                                $this->product_rule_mapping[$key]=array_merge( $this->product_rule_mapping[$key],array_keys($this->product_weight));
                                                continue(3);  
                                            }
                                           if(isset($this->shipping_class[$_shipping_class]))
                                           {
                                              //echo "based on val=".$this->shipping_class[$_shipping_class][$based_on]." min=".$rule['min_weight']." max=".$rule['max_weight'];
                                               if($this->shipping_class[$_shipping_class][$based_on]>=$rule['min_weight'] &&
                                                   $this->shipping_class[$_shipping_class][$based_on]<=$rule['max_weight'])
                                                    {
                                                         if(!isset($this->product_rule_mapping[$key]))
                                                        {                                           
                                                            $this->product_rule_mapping[$key]=array();
                                                        }
                                                         $this->product_rule_mapping[$key]=array_merge( $this->product_rule_mapping[$key],$this->shipping_class[$_shipping_class]['pid']);
                                                    }
                                          }
                                       } //end for
                               }  // end if

                               
                               
                               if(isset($rule['product_category']) && !empty($rule['product_category']))
                            {   $category_class_list=$rule['product_category'];
                                    foreach($category_class_list as $_category)
                                       {
                                        //echo $this->category[$_category][$based_on].">=".$rule['min_weight'] .",</br>";
                                        //echo "&&".$this->category[$_category][$based_on]."<=".$rule['max_weight'] .",</br>";
                                        if($_category=='any_product_category')
                                            {
                                                if(!empty($rule['min_weight']) || !empty($rule['max_weight']))
                                                {
                                                    if((isset($rule['min_weight']) &&  $order_total[$based_on]>=$rule['min_weight'])!==true)
                                                    {
                                                        continue(3); 
                                                    }
                                                    if((isset($rule['max_weight'])  && !empty($rule['max_weight'])  && $order_total[$based_on]<=$rule['max_weight'])!==true)
                                                        {
                                                        continue(3); 
                                                    }                                               
                                                }
                                                $this->product_rule_mapping[$key]['validforall']=1;
                                                $this->product_rule_mapping[$key]=array_merge( $this->product_rule_mapping[$key],array_keys($this->product_weight));                                                
                                                continue(3);
                                            }
                                            if(isset($this->category[$_category]))
                                               {    
                                                if($this->category[$_category][$based_on]>=$rule['min_weight'] &&
                                                   $this->category[$_category][$based_on]<=$rule['max_weight'])                                                       
                                                       {         //echo "</br>again in</br>";                          
                                                          if(!isset($this->product_rule_mapping[$key]))
                                                           {                                           
                                                           $this->product_rule_mapping[$key]=array();
                                                           }
                                                           $this->product_rule_mapping[$key]=array_merge( $this->product_rule_mapping[$key],$this->category[$_category]['pid']);
                                                       }
                                               }
                                       }
                               }
                               if(empty($shipping_class_list) && empty($category_class_list))
                               {
                                   $this->product_rule_mapping[$key]['validforall']=1;
                               }
                               continue(2);
                           }
                        } // end for
                        //echo "product rule mapping";
                        //print_r($this->product_rule_mapping[$key]);
                    }
             }
     
      
    function check_rule_area($rule_area_key,$country,$state,$postalcode)
    {   
            $tmp=get_option('woocommerce_wf_multi_carrier_shipping_area_settings');
            $area_matrix=$tmp['area_matrix'];
            $area=$area_matrix[$rule_area_key];
            $selected_areas=array();
            if(isset($area['zone_list']) && !empty($area['zone_list']) )
            {   //echo "zone_list";
                $selected_areas=$area['zone_list'];
            }
            elseif(isset($area['country_list']) && !empty($area['country_list']))
            {//echo "country_list";
                $selected_areas=$area['country_list'];
            }
            elseif(isset($area['state_list']) && !empty($area['state_list']))
            {//echo "state_list";
                $selected_areas=$area['state_list'];
            }
            elseif(isset($area['postal_code']) && !empty($area['postal_code']))
            {//echo "postal_code";
                $selected_areas=explode(',',$area['postal_code']);
               // print_r($area['postal_code']);              //1156651
            }else{}
            
            $package_zone=-1;
            if(isset($this->zone))
                {
                            $package_zone=(string)((WC()->version < '2.7.0')?$this->zone->get_zone_id():$this->zone->get_id()) ;
                }

            if(in_array($country, $selected_areas) || in_array($country.':'.$state, $selected_areas) || in_array($postalcode, $selected_areas)  || in_array($package_zone, $selected_areas)!==false  )
            {
                return true;
            }
            
            return false;
            
    }
     
    function calculate_shipping_cost($rules=array(),$product_rule_mapping=array())
    {
        $totalcost=0;

        if(empty($rules))
        {
            $rules=$this->rules_array;
        }
        if(empty($product_rule_mapping))
        {
            $product_rule_mapping=$this->product_rule_mapping;
        }
        //echo "here";
        //var_dump($this->product_rule_mapping);
        $this->empty_responce_shipping_cost=$this->settings['empty_responce_shipping_cost'];
        $this->empty_responce_shipping_cost_on=$this->settings['empty_responce_shipping_cost_on'];

         if(!empty($product_rule_mapping))
         {   //print_r($product_rule_mapping);   
                //var_dump($this->settings['weight_packing_process']);
               $packing_method=$this->settings['packing_method'];
               $packing_process=$this->settings['weight_packing_process'];
               $box_max_weight=$this->settings['box_max_weight'];
               if($packing_method=='per_item')
               {
                   if(!class_exists('per_item_packing')) {include('class-per-item-packing.php');}
                   $pack=new per_item_packing($this->product_weight,$this->product_quantity,$this);
                   
                            foreach($product_rule_mapping as $rule_no=>$pids)
                            {   
                                //creating packages according to form field packaging process and getting rates from api
                                $company= $this->rules_array[$rule_no]['shipping_companies']? $this->rules_array[$rule_no]['shipping_companies']:'';
                                $service= isset($this->rules_array[$rule_no]['shipping_services'])?$this->rules_array[$rule_no]['shipping_services']:'';
                                $bucket=array();
                                if(isset($product_rule_mapping[$rule_no]['validforall']))
                                {
                                    $pids= array_keys($this->product_quantity);
                                }
                                foreach($pids as $_pid)
                                {
                                       if(array_search($_pid, $this->executed_products)===false) 
                                       {                                           
                                            $this->executed_products[]=$_pid;
                                            $bucket[]=$_pid;
                                       }
                                }
                                $pack->per_item_packing_add_package_to_request($bucket, $company,$service,$rule_no);
                            }

                   $totalcost= $pack->get_Rates_From_Api();
                   //print_r($this->executed_products);
                   //return $total_cost;
               }
               elseif($packing_method=='weight_based')
               {

                     if(!class_exists('weight_based_packing'))  include('class-weight-based-packing.php');
                     
                     $pack=new weight_based_packing($this->product_weight,$this->product_quantity,$packing_process,$box_max_weight,$this);
                     $totalcost= $pack->get_Rates_From_Api();
                    
               }
               elseif($packing_method=='box_packing')
               {
                    if(!class_exists('box_packing'))  include('class-box-packing.php');
                    $boxes=$this->settings['boxes'];
                    $pack=new box_packing($this->product_quantity,$product_rule_mapping,$boxes,$this); 
                    $totalcost= $pack->get_Rates_From_Api();
               }
               else
               {
                    new WP_Error( 'broke', __( "No Packing Method Found!!", "eha_multi_carrier_shipping" ) );
               }
         }
         else
         {
             new WP_Error( 'broke', __( "No Rules Applied", "eha_multi_carrier_shipping" ) );
             //echo "@No Rules Applied";
         }
        
        if(!empty($product_rule_mapping))
        {
                if($totalcost<=0 && isset($this->empty_responce_shipping_cost) && is_numeric($this->empty_responce_shipping_cost))
                {  
                    
                    if($this->empty_responce_shipping_cost_on=='per_unit_weight')
                    {   
                        $totalcost=$this->empty_responce_shipping_cost  *  $this->total_weight;
                    }
                    else if($this->empty_responce_shipping_cost_on=='per_unit_quantity')
                    {   
                        $totalcost=$this->empty_responce_shipping_cost  *  $this->total_units;
                    }
                }
                else
                {
                    foreach($this->product_quantity as $_pid=>$_qnty)
                    {      
                        if(array_search($_pid, $this->executed_products)===false && $this->executed_products[0]!='*')
                        {  
                            $totalcost+=$this->empty_responce_shipping_cost * $_qnty;
                        }
                    }
                }            
        }

        return $this->check_executed_rules_and_add_base_cost($totalcost);
    }
    
    function check_executed_rules_and_add_base_cost($totalcost)
    {
        if($totalcost<=0 && empty($this->rules_executed_successfully) && isset($this->settings['show_shipping_group']) && $this->settings['show_shipping_group']=='yes' )
        {      
                return 0;
        }
         foreach($this->rules_executed_successfully as $rule_no)
        {       
                $rule= $this->rules_array[$rule_no];
                if(!empty($rule['fee']) && is_numeric($rule['fee'])  && $rule['shipping_companies']!=='flatrate')
                {
                    $totalcost+=$rule['fee'];
                }  
         }
         return $totalcost;
    }
    
}