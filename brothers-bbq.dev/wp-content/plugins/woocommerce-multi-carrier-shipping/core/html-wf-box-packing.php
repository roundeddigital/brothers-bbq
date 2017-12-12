		<script type="text/javascript">

			jQuery(window).load(function(){

				jQuery('#woocommerce_wf_multi_carrier_shipping_packing_method').change(function(){

					if ( jQuery(this).val() == 'box_packing' )
						jQuery('#packing_options').show();
					else
						jQuery('#packing_options').hide();

				}).change();
                                                            });
                                        </script>
<tr valign="top" id="packing_options">
	<td class="titledesc" colspan="2" style="padding-left:0px">
	<strong><?php _e( 'Box Sizes', 'wf_fedEx_wooCommerce_shipping' ); ?></strong><br><br>
		<style type="text/css">
			.wf_multi_carrier_shipping_boxes td, .wf_multi_carrier_shipping_services td {
				vertical-align: middle;
				padding: 4px 7px;
			}
			.wf_multi_carrier_shipping_services th, .wf_multi_carrier_shipping_boxes th {
				padding: 9px 7px;
			}
			.wf_multi_carrier_shipping_boxes td input {
				margin-right: 4px;
			}
			.wf_multi_carrier_shipping_boxes .check-column {
				vertical-align: middle;
				text-align: left;
				padding: 0 7px;
			}
			.wf_multi_carrier_shipping_services th.sort {
				width: 16px;
				padding: 0 16px;
			}
			.wf_multi_carrier_shipping_services td.sort {
				cursor: move;
				width: 16px;
				padding: 0 16px;
				cursor: move;
				background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAHUlEQVQYV2O8f//+fwY8gJGgAny6QXKETRgEVgAAXxAVsa5Xr3QAAAAASUVORK5CYII=) no-repeat center;
			}
		</style>
		<table class="wf_multi_carrier_shipping_boxes widefat">
			<thead>
				<tr>
					<th class="check-column"><input type="checkbox" /></th>
					<th><?php _e( 'Name', 'wf_fedEx_wooCommerce_shipping' ); ?></th>
					<th><?php _e( 'Length', 'wf_fedEx_wooCommerce_shipping' ); ?></th>
					<th><?php _e( 'Width', 'wf_fedEx_wooCommerce_shipping' ); ?></th>
					<th><?php _e( 'Height', 'wf_fedEx_wooCommerce_shipping' ); ?></th>
					<th><?php _e( 'Inner Length', 'wf_fedEx_wooCommerce_shipping' ); ?></th>
					<th><?php _e( 'Inner Width', 'wf_fedEx_wooCommerce_shipping' ); ?></th>
					<th><?php _e( 'Inner Height', 'wf_fedEx_wooCommerce_shipping' ); ?></th>
					<th><?php _e( 'Box Weight', 'wf_fedEx_wooCommerce_shipping' ); ?></th>
					<th><?php _e( 'Max Weight', 'wf_fedEx_wooCommerce_shipping' ); ?></th>
					<th><?php _e( 'Enabled', 'wf_fedEx_wooCommerce_shipping' ); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th colspan="3">
						<a href="#" class="button plus insert"><?php _e( 'Add Box', 'wf_fedEx_wooCommerce_shipping' ); ?></a>
						<a href="#" class="button minus remove"><?php _e( 'Remove selected box(es)', 'wf_fedEx_wooCommerce_shipping' ); ?></a>
					</th>
					<th colspan="6">
						<small class="description"><?php _e( 'Items will be packed into these boxes depending based on item dimensions and volume. Dimensions will be passed to FedEx and used for packing. Items not fitting into boxes will be packed individually.', 'wf_fedEx_wooCommerce_shipping' ); ?></small>
					</th>
				</tr>
			</tfoot>
			<tbody id="rates">
				<?php
					if ( $this->default_boxes ) {
						foreach ( $this->default_boxes as $key => $box ) {
							?>
							<tr>
								<td class="check-column"></td>
								<td><?php echo $box['name']; ?></td>
								<td><input type="text" size="5" readonly value="<?php echo esc_attr( $box['length'] ); ?>" /><?php echo $this->dimension_unit;?></td>
								<td><input type="text" size="5" readonly value="<?php echo esc_attr( $box['width'] ); ?>" /><?php echo $this->dimension_unit;?></td>
								<td><input type="text" size="5" readonly value="<?php echo esc_attr( $box['height'] ); ?>" /><?php echo $this->dimension_unit;?></td>
								<td><input type="text" size="5" readonly value="<?php echo esc_attr( $box['inner_length'] ); ?>" /><?php echo $this->dimension_unit;?></td>
								<td><input type="text" size="5" readonly value="<?php echo esc_attr( $box['inner_width'] ); ?>" /><?php echo $this->dimension_unit;?></td>
								<td><input type="text" size="5" readonly value="<?php echo esc_attr( $box['inner_height'] ); ?>" /><?php echo $this->dimension_unit;?></td>
								<td><input type="text" size="5" readonly value="<?php echo esc_attr( $box['box_weight'] ); ?>" /><?php echo $this->weight_unit;?></td>
								<td><input type="text" size="5" readonly value="<?php echo esc_attr( $box['max_weight'] ); ?>" /><?php echo $this->weight_unit;?></td>
								<td><input type="checkbox" name="boxes_enabled[<?php echo $box['id']; ?>]" <?php checked( ! isset( $this->boxes[ $box['id'] ]['enabled'] ) || $this->boxes[ $box['id'] ]['enabled'] == 1, true ); ?> /></td>
							</tr>
							<?php
						}
					}
					if ( $this->boxes ) {

						foreach ( $this->boxes as $key => $box ) {
							if ( ! is_numeric( $key ) )
								continue;
							?>

							<tr>
								<td class="check-column">
									<input type="checkbox" />
									<input type="hidden" name="box_type[]" value="<?php echo !empty($box['box_type']) ? $box['box_type'] : 'defaul_box';?>">
								</td>
								<td><?php echo isset($box['name']) ? $box['name'] : '&nbsp;' ?></td>
								<td><input type="text" size="5" name="boxes_length[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['length'] ); ?>" /><?php echo $this->dimension_unit;?></td>
								<td><input type="text" size="5" name="boxes_width[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['width'] ); ?>" /><?php echo $this->dimension_unit;?></td>
								<td><input type="text" size="5" name="boxes_height[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['height'] ); ?>" /><?php echo $this->dimension_unit;?></td>
								
								<td><input type="text" size="5" name="boxes_inner_length[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_length'] ); ?>" /><?php echo $this->dimension_unit;?></td>
								<td><input type="text" size="5" name="boxes_inner_width[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_width'] ); ?>" /><?php echo $this->dimension_unit;?></td>
								<td><input type="text" size="5" name="boxes_inner_height[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_height'] ); ?>" /><?php echo $this->dimension_unit;?></td>
		
								<td><input type="text" size="5" name="boxes_box_weight[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['box_weight'] ); ?>" /><?php echo $this->weight_unit;?></td>
								<td><input type="text" size="5" name="boxes_max_weight[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['max_weight'] ); ?>" /><?php echo $this->weight_unit;?></td>
								<td><input type="checkbox" name="boxes_enabled[<?php echo $key; ?>]" <?php if( isset($box['enabled']) ) checked( $box['enabled'], true ); ?> /></td>
							</tr>
							<?php
						}
					}
				?>
			</tbody>
		</table>
		<script type="text/javascript">

			jQuery(window).load(function(){

				jQuery('#woocommerce_wf_multi_carrier_shipping_packing_method').change(function(){

					if ( jQuery(this).val() == 'box_packing' )
						jQuery('#packing_options').show();
					else
						jQuery('#packing_options').hide();

				}).change();

				jQuery('.wf_multi_carrier_shipping_boxes .insert').click( function() {
					var $tbody = jQuery('.wf_multi_carrier_shipping_boxes').find('tbody');
					var size = $tbody.find('tr').size();
					var code = '<tr class="new">\
							<td class="check-column"><input type="checkbox" /></td>\
							<td>&nbsp;</td>\
							<td><input type="text" size="5" name="boxes_length[' + size + ']" />in</td>\
							<td><input type="text" size="5" name="boxes_width[' + size + ']" />in</td>\
							<td><input type="text" size="5" name="boxes_height[' + size + ']" />in</td>\
							<td><input type="text" size="5" name="boxes_inner_length[' + size + ']" />in</td>\
							<td><input type="text" size="5" name="boxes_inner_width[' + size + ']" />in</td>\
							<td><input type="text" size="5" name="boxes_inner_height[' + size + ']" />in</td>\
							<td><input type="text" size="5" name="boxes_box_weight[' + size + ']" />lbs</td>\
							<td><input type="text" size="5" name="boxes_max_weight[' + size + ']" />lbs</td>\
							<td><input type="checkbox" name="boxes_enabled[' + size + ']" /></td>\
						</tr>';

					$tbody.append( code );

					return false;
				} );

				jQuery('.wf_multi_carrier_shipping_boxes .remove').click(function() {
					var $tbody = jQuery('.wf_multi_carrier_shipping_boxes').find('tbody');

					$tbody.find('.check-column input:checked').each(function() {
						jQuery(this).closest('tr').hide().find('input').val('');
					});

					return false;
				});

				// Ordering
				jQuery('.wf_multi_carrier_shipping_services tbody').sortable({
					items:'tr',
					cursor:'move',
					axis:'y',
					handle: '.sort',
					scrollSensitivity:40,
					forcePlaceholderSize: true,
					helper: 'clone',
					opacity: 0.65,
					placeholder: 'wc-metabox-sortable-placeholder',
					start:function(event,ui){
						ui.item.css('baclbsround-color','#f6f6f6');
					},
					stop:function(event,ui){
						ui.item.removeAttr('style');
						wf_multi_carrier_shipping_services_row_indexes();
					}
				});

				function wf_multi_carrier_shipping_services_row_indexes() {
					jQuery('.wf_multi_carrier_shipping_services tbody tr').each(function(index, el){
						jQuery('input.order', el).val( parseInt( jQuery(el).index('.wf_multi_carrier_shipping_services tr') ) );
					});
				};

			});

		</script>
	</td>
</tr>