<?php
	if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
	}
class eha_multi_carrier_shipping_area extends WC_Shipping_Method {
	function __construct() {
		//$plugin_config = wf_plugin_configuration_mcp();

		$this->id = 'wf_multi_carrier_shipping_area';
		$this->method_title		= __( 'Shipping Area Management', 'eha_multi_carrier_shipping' );

		$this->wf_multi_carrier_shipping_init_form_fields();
		$this->init_settings();

		$this->area_matrix			= isset( $this->settings['area_matrix'] ) ? $this->settings['area_matrix'] : array();
		$this->multiselect_act_class	=	'multiselect';
		$this->drop_down_style	=	'chosen_select ';			
		$this->shipping_countries = wf_get_shipping_countries_mcp();
						
		$this->col_count = 2;
		$this->title = 'Enter Area Name';

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

	}
	
	function wf_get_zone_list(){
		$zone_list = array();
		if( class_exists('WC_Shipping_Zones') ){
			$zones_obj = new WC_Shipping_Zones;
			$zones = $zones_obj::get_zones();
			$zone_list[0] = 'Rest of the World'; //rest of the zone always have id 0, which is not available in the method get_zone()
			foreach ($zones as $key => $zone) {
				$zone_list[$key] = $zone['zone_name'];
			}
		}
		return $zone_list;
	}

	public function admin_options() {
		echo '<h3>' . ( ! empty( $this->method_title ) ? $this->method_title : __( 'Settings', 'eha_multi_carrier_shipping' ) ) . '</h3>';
		echo ( ! empty( $this->method_description ) ) ? wpautop( $this->method_description ) : '';
		?>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		<?php
	}

	public function wf_multi_carrier_shipping_init_form_fields(){
		$this->form_fields = array(
			'area_matrix' => array(
				'type' 		=> 'area_matrix'
			),
		);
	}

	public function validate_area_matrix_field( $key ) {
		
		$area_matrix	 = isset( $_POST['area_matrix'] ) ? $_POST['area_matrix'] : array();
		 //die(print_r($area_matrix));
		 foreach ($area_matrix as $index => $value) {
			 if(empty($value['area_name']))
			 {
				$error='<div class="error notice">  
				<p>'.'Area Name Should not be empty!!'.'</p>
						</div>';
				echo $error;
				// unset($area_matrix[$index]);
				// $this->errors[]='Area Name Should not be empty!!';
				$e=new Exception('Area Name Should not be empty!!');
				throw $e;
				return;
			}
		 }
		return $area_matrix;
	}

	public function generate_area_matrix_html() {
		$this->shipping_zones = $this->wf_get_zone_list();
		ob_start();				
		
		?>
		<tr valign="top" id="packing_area_matrix">
			<td class="titledesc"  style="padding-left:0px">
				<strong><?php _e( 'Zone matrix:', 'eha_multi_carrier_shipping' ); ?></strong><br><br>
				<style type="text/css">
					.multi_carrier_shipping_area_matrix .row_data td
					{
						border-bottom: 1pt solid #e1e1e1;
					}
					
					.multi_carrier_shipping_area_matrix input, 
					.multi_carrier_shipping_area_matrix select, 
					.multi_carrier_shipping_area_matrix textarea,
					.multi_carrier_shipping_area_matrix .select2-container-multi .select2-choices{
						background-color: #fbfbfb;
						border: 1px solid #e9e9e9;
					}
					 					
					.multi_carrier_shipping_area_matrix td, .multi_carrier_shipping_services td {
						vertical-align: top;
							padding: 4px 7px;
							
					}
					.multi_carrier_shipping_area_matrix th, .multi_carrier_shipping_services th {
						padding: 9px 7px;
					}
					.multi_carrier_shipping_area_matrix td input {
						margin-right: 4px;
					}
					.multi_carrier_shipping_area_matrix .check-column {
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
							min-width:90px;	 
						}
						th.big_column
						{
							min-width:250px;
						}									
					}
					th.hidecolumn,
					td.hidecolumn
					{
							display:none;
					}
								
				</style>
				<table class="multi_carrier_shipping_area_matrix widefat" style="background-color:#f6f6f6;">
					<thead>
						<tr>
							<th class="check-column tiny_column"><input type="checkbox" /></th>
							<th class="medium_column">
								<?php _e( 'Area Name', 'eha_multi_carrier_shipping' );  ?>
								<img class="help_tip" style="float:none;" data-tip="<?php _e( 'Area Name', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /> 
							</th>
							<th class="widefat">
								<?php _e( 'List', 'eha_multi_carrier_shipping' );  ?>
								<img class="help_tip" style="float:none;" data-tip="<?php _e( 'Select list of Zones,Country,State,Postal Code to create a Area', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /> 
							</th>
						<!--------	<th class="big_column">
								<?php _e( 'Country list', 'eha_multi_carrier_shipping' );  ?>
								<img class="help_tip" style="float:none;" data-tip="<?php _e( 'Select list of countries which this rule will be applicable', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /> 
							</th>
							<th class="big_column">
								<?php _e( 'State list', 'eha_multi_carrier_shipping' );  ?>
								<img class="help_tip" style="float:none;" data-tip="<?php _e( 'Select list of states which this rule will be applicable', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /> 
							</th>
							<th class="small_column">
								<?php _e( 'Postal code', 'eha_multi_carrier_shipping' );  ?>
								<img class="help_tip" style="float:none;" data-tip="<?php _e( 'Post/Zip code for this rule. Semi-colon (;) separate multiple values. Leave blank to apply to all areas. Wildcards (*) can be used. Ranges for numeric postcodes (e.g. 12345-12350) will be expanded into individual postcodes.', 'eha_multi_carrier_shipping' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /><br>
							</th>
													 -----!---->
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="2">
								<a href="#TB_inline?width=100&height=160&inlineId=add-new-area" class="button thickbox" ><?php _e( 'Add', 'eha_multi_carrier_shipping' ); ?></a>
								<a href="#" class="button remove"><?php _e( 'Remove', 'eha_multi_carrier_shipping' ); ?></a>
								<a href="#" class="button duplicate"><?php _e( 'Duplicate', 'eha_multi_carrier_shipping' ); ?></a>
							</th>
							<th >
								<!---<small class="description"><a href="<?php echo admin_url( 'admin.php?import=multicarriershipping_area_matrix_csv' ); ?>" class="button" style="float:right;	margin-right: 10px;"><?php _e( 'Import CSV', 'eha_multi_carrier_shipping' ); ?></a>
								<a href="<?php echo admin_url( 'admin.php?wf_export_multicarriershipping_area_matrix_csv=true' ); ?>" class="button" style="float:right;	margin-right: 10px;"><?php _e( 'Export CSV', 'eha_multi_carrier_shipping' ); ?></a>&nbsp;
								</small> ----!-->
							</th>
						</tr>
					</tfoot>
					<tbody id="rates">
					<?php		
						
													add_thickbox(); 
													?>
						
													<div id="add-new-area" style="display:none;">
													<div id="a" name="a" style="margin-left: auto; margin-right: auto; display: table; overflow: hidden;">
													 <h3 for="areatype" class="label" >This New Area is a :</h3>
													</br>
													<select id="areatype" name="areatype">
														<option value ="zone_list" selected>Zone  List</option>
														<option value ="country_list" >Country  List</option>
														 <option value ="state_list" >State  List</option>
														 <option value ="postal_code_list" >Postal Code  List</option>
														
													</select>	  
													</br></br>
													<input type="submit" name="submit2" id="submit2" class="button submit2" value=" Add New Area " />
													</div>
														 
														
													</div>
													
													<?php
					$matrix_rowcount = 0;
					if ( $this->area_matrix ) {
						foreach ( $this->area_matrix as $key => $box ) {		
							
						//echo "<pre>"; print_r($this->area_matrix );	?>
																<tr class="rule_text"><td  style="font-style:italic; color:#a8a8a8;"></td></tr>
							<tr class="row_data"><td class="check-column"><input type="checkbox" /></td>
							<td class=""><input type='text' size='20' name='area_matrix[<?php echo $key;?>][area_name]' placeholder='<?php echo $this->title;?>' title='<?php echo isset($box['area_name']) ? $box['area_name']:$this->title;?>' value='<?php echo isset($box['area_name']) ? $box['area_name']:"";?>' /></td>
							<?php 

																if(isset($box['zone_list']) && !empty($box['zone_list']))
																{	  
																	$defined_zones = isset($box['zone_list']) ? $box['zone_list'] : array();		
																	?>
																<td class="" style='overflow:visible;width: 100%;'>
							<select id="zone_list_combo_<?php echo $key;?>" class="<?php echo $this->drop_down_style;?> enabled" data-identifier="zone_list_combo" multiple="true" style="width:100%;" name='area_matrix[<?php echo $key;?>][zone_list][]'>
									<?php 
									$zone_list =$this->shipping_zones;
									foreach($zone_list as $zoneKey => $zoneValue){ ?>
									<option value="<?php echo $zoneKey;?>" <?php selected(in_array($zoneKey,$defined_zones),true);?>><?php echo $zoneValue;?>
									</option>
									<?php } ?>															
								</select>
							</td>
																	<?php 
																}
																	elseif(isset($box['country_list']) && !empty($box['country_list']))
																{
																	$defined_countries = isset($box['country_list']) ? $box['country_list'] : array();																	
																	  ?>
																	<td class="" style='overflow:visible;width: 100%;'>
								<select id="country_list_combo_<?php echo $key;?>" class="<?php echo $this->drop_down_style;?> enabled" data-identifier="country_list_combo" multiple="true" style="width:100%;" name='area_matrix[<?php echo $key;?>][country_list][]'>
									<?php foreach($this->shipping_countries as $countryKey => $countryValue){ ?>
									<option value="<?php echo $countryKey;?>" <?php selected(in_array($countryKey,$defined_countries),true);?>><?php echo $countryValue;?></option>
									<?php } ?>															
								</select>
							</td>
																	<?php 
																}
																	elseif(isset($box['state_list']) && !empty($box['state_list']))
																{
																	$defined_states = isset($box['state_list']) ? $box['state_list'] : array();
																	  ?>
																	<td class="" style='overflow:visible;width: 100%;'>
								<select id="state_list_combo_<?php echo $key;?>" class="<?php echo $this->drop_down_style;?> enabled" data-identifier="state_list_combo" multiple="true" style="width:100%;" name='area_matrix[<?php echo $key;?>][state_list][]'>
									<option value="any_state" <?php selected(in_array('any_state',$defined_states),true);?>>Any State</option>
									<option value="rest_country" <?php selected(in_array('rest_country',$defined_states),true);?>>Rest of the country</option>
									<?php $this->wf_state_dropdown_options($defined_states); ?>															
								</select>
							</td>
																	<?php 
																}
																elseif(isset($box['postal_code']) && !empty($box['postal_code']))
																{
																	  ?>
																	<td class="" style='width: 100%;'><input type='text' style='width: 100%;'size='10' name='area_matrix[<?php echo $key;?>][postal_code]' 		value='<?php echo isset($box['postal_code']) ? $box['postal_code']:'';?>' /></td>
							
																	<?php 
																}
																else
																{
																	
																}
																////$defined_shipping_classes = isset($box['shipping_class']) ? $box['shipping_class'] : array();
							//$defined_product_category = isset($box['product_category']) ? $box['product_category'] : array();
							?>
											
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
			</td>
		</tr>
		<script>
			jQuery(window).load(function(){							
				//jQuery('.multi_carrier_shipping_area_matrix .insert').click( function() {
													jQuery('.submit2').click( function() {
													var areatype=jQuery('#areatype').find(":selected").val();
													jQuery("#areatype").val("")
					var $tbody = jQuery('.multi_carrier_shipping_area_matrix').find('tbody');
					var size = $tbody.find('#matrix_rowcount').val();
					if(size){
						size = parseInt(size)+1;
					}
					else
						size = 0;
						
						
					var code = '<tr class="new row_data" style=";width: 100%;"><td class="check-column"><input type="checkbox" /></td>\
					<td class=""><input type="text" size="20" name="area_matrix['+size+'][area_name]" placeholder="<?php echo $this->title;?>" /></td>\\n\  ';
					if(areatype=='zone_list')
					{
					code=code+'<td class="" style="overflow:visible;width: 100%;">\
						<select placeholder="Select Multiple Zones" id="zone_list_combo_'+size+'" class="<?php echo $this->drop_down_style;?> enabled" data-identifier="zone_list_combo" multiple="true" style="width:100%;" name="area_matrix['+size+'][zone_list][]">\
						<!--option value="any_zone">Any Zone</option><option value="rest_world">Rest of the world</option-->\
						<?php foreach($this->shipping_zones as $zoneKey => $zoneValue){ ?><option value="<?php echo esc_attr( $zoneKey ); ?>" ><?php echo esc_attr( $zoneValue ); ?></option>\
						<?php } ?></select></td>\\n  ' ;
					}
					else if(areatype=='country_list')
					{
					code=code+'<td class="" style="overflow:visible;width: 100%;">\
						<select placeholder="Select Multiple Country" id="country_list_combo_'+size+'" class="<?php echo $this->drop_down_style;?> enabled" data-identifier="country_list_combo" multiple="true" style="width:100%;" name="area_matrix['+size+'][country_list][]">\
						<!--option value="any_country">Any Country</option><option value="rest_world">Rest of the world</option-->\
						<?php foreach($this->shipping_countries as $countryKey => $countryValue){ ?><option value="<?php echo esc_attr( $countryKey ); ?>" ><?php echo esc_attr( $countryValue ); ?></option>\
						<?php } ?></select>\
														</td>\\n\ ' ;
					}
					else if(areatype=='state_list')
					{
					code=code+' <td class="" style="overflow:visible;width: 100%;"> \
						<select placeholder="Select Multiple States" id="state_list_combo_'+size+'" class="<?php echo $this->drop_down_style;?> enabled" data-identifier="state_list_combo"  multiple="true" style="width:100%;" name="area_matrix['+size+'][state_list][]">\
						<option value="any_state">Any State</option><option value="rest_country">Rest of the Country</option>\
						<?php $this->wf_state_dropdown_options(array(),true); ?></select>\
														</td>' ;
					}
					else if(areatype=='postal_code_list')
					{
						code=code+'<td class="" style="overflow:visible;width: 100%;"><input class ="" placeholder="Enter Multiple Postal Codes" type="text" size="10" name="area_matrix['+size+'][postal_code]"  /></td> ' ;
					}
					else
					{
						alert('Wrong Area Type Selected');
						return false;
					}
					
					  //jQuery('#add-new-area').fadeOut();
												self.parent.tb_remove();  
					
					code=code+'</tr>';										
					$tbody.append( code );
					if(typeof wc_enhanced_select_params == 'undefined')
						$tbody.find('tr:last').find("select.chosen_select").chosen();
					else
						$tbody.find('tr:last').find("select.chosen_select").trigger( 'wc-enhanced-select-init' );
					
						
					$tbody.find('#matrix_rowcount').val(size);
											
					return false;
				});

				jQuery('.multi_carrier_shipping_area_matrix .remove').click(function() {
					var $tbody = jQuery('.multi_carrier_shipping_area_matrix').find('tbody');

					$tbody.find('.check-column input:checked').each(function() {
						jQuery(this).closest('tr').prev('.rule_text').remove();
						jQuery(this).closest('tr').remove();
						});

					return false;
				});

				
				jQuery('.multi_carrier_shipping_area_matrix .duplicate').click(function() {
					var $tbody = jQuery('.multi_carrier_shipping_area_matrix').find('tbody');

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
								jQuery(this).attr('name', newNameAttr);	// set the incremented name attribute 
							}
							var currentIdAttr = jQuery(this).attr('id'); 
							if(currentIdAttr){
								var currentIdAttr = currentIdAttr.replace(/\d+/, size);
								jQuery(this).attr('id', currentIdAttr);	// set the incremented name attribute 
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
		<?php
		return ob_get_clean();		
	}
	
	public function wf_state_dropdown_options( $selected_states = array(), $escape = false ) {
		if ( $this->shipping_countries ) foreach ( $this->shipping_countries as $key=>$value) :
			if ( $states =  WC()->countries->get_states( $key ) ) :
				echo '<optgroup label="' . esc_attr( $value ) . '">';
					foreach ($states as $state_key=>$state_value) :
						echo '<option value="' . esc_attr( $key ) . ':'.$state_key.'"';
						if (!empty($selected_states) && in_array(esc_attr( $key ) . ':'.$state_key,$selected_states)) echo ' selected="selected"';
						//echo '>'.$value.' &mdash; '. ($escape ? esc_js($state_value) : $state_value) .'</option>';
						echo '>'. ($escape ? esc_js($state_value) : $state_value) .'</option>';
					endforeach;
				echo '</optgroup>';
			endif;
		endforeach;
	}
}