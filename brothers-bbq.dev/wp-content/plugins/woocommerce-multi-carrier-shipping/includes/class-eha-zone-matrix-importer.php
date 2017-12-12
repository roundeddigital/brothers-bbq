<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( class_exists( 'WP_Importer' ) ) {
	class WF_Zone_Matrix_Importer_mcp extends WP_Importer {

		var $id;
		var $file_url;
		var $import_page;
		var $delimiter;
		var $posts = array();
		var $imported;
		var $skipped;

		/**
		 * __construct function.
		 */
		public function __construct() {
			$this->import_page = 'multicarriershipping_zone_matrix_csv';
		}

		/**
		 * Registered callback function for the WordPress Importer
		 *
		 * Manages the three separate stages of the CSV import process
		 */
		public function dispatch() {

			$this->header();

			if ( ! empty( $_POST['delimiter'] ) )
				$this->delimiter = stripslashes( trim( $_POST['delimiter'] ) );

			if ( ! $this->delimiter )
				$this->delimiter = ',';

			$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];

			switch ( $step ) {

				case 0:
					$this->greet();
					break;

				case 1:
					check_admin_referer( 'import-upload' );
					if ( $this->handle_upload() ) {

						if ( $this->id )
							$file = get_attached_file( $this->id );
						else
							$file = ABSPATH . $this->file_url;

						add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );

						if ( function_exists( 'gc_enable' ) )
							gc_enable();

						@set_time_limit(0);
						@ob_flush();
						@flush();

						$this->import( $file );
					}
					break;
			}

			$this->footer();
		}

		/**
		 * format_data_from_csv function.
		 *
		 * @param mixed $data
		 * @param string $enc
		 * @return string
		 */
		public function format_data_from_csv( $data, $enc ) {
			return ( $enc == 'UTF-8' ) ? $data : utf8_encode( $data );
		}

		/**
		 * import function.
		 *
		 * @param mixed $file
		 */
		function import( $file ) {
			global $wpdb;

			$this->imported = $this->skipped = 0;

			if ( ! is_file($file) ) {
				echo '<p><strong>' . __( 'Sorry, there has been an error.', 'eha_multi_carrier_shipping' ) . '</strong><br />';
				echo __( 'The file does not exist, please try again.', 'eha_multi_carrier_shipping' ) . '</p>';
				$this->footer();
				die();
			}

			$new_zones = array();

			ini_set( 'auto_detect_line_endings', '1' );

			if ( ( $handle = fopen( $file, "r" ) ) !== FALSE ) {

				$header = fgetcsv( $handle, 0, $this->delimiter );
				if ( sizeof( $header ) == 4 ) {
					
					$zone_matrix = array();					
					
					while ( ( $row = fgetcsv( $handle, 0, $this->delimiter ) ) !== FALSE ) {
						list( $zone_name, $country_list, $state_list, $postal_code ) = $row;
						
						$matrix_row = array();
						$matrix_row['zone_name'] = $zone_name;
						$matrix_row['country_list'] = !empty($country_list) ? explode(";", $country_list) : '';
						$matrix_row['state_list'] = !empty($state_list) ? explode(";", $state_list) : '';
						$matrix_row['postal_code'] = $postal_code;

						$zone_matrix[] = $matrix_row;						
						$this->imported++;
					}
					
					$multi_carrier_shipping_settings = get_option( 'woocommerce_wf_multi_carrier_shipping_area_settings' );
					if(!empty($zone_matrix) && is_array($zone_matrix) && !empty($multi_carrier_shipping_settings) && is_array($multi_carrier_shipping_settings)){
						$multi_carrier_shipping_settings['zone_matrix'] = $zone_matrix;
						update_option('woocommerce_wf_multi_carrier_shipping_area_settings', $multi_carrier_shipping_settings);						
					}
					
				} else {

					echo '<p><strong>' . __( 'Sorry, there has been an error.', 'eha_multi_carrier_shipping' ) . '</strong><br />';
					echo __( 'The CSV is invalid.', 'eha_multi_carrier_shipping' ) . '</p>';
					$this->footer();
					die();

				}

				fclose( $handle );
			}

			// Show Result
			echo '<div class="updated settings-error below-h2"><p>
				'.sprintf( __( 'Import complete - imported <strong>%s</strong> zone Matrix Rules and skipped <strong>%s</strong>.', 'eha_multi_carrier_shipping' ), $this->imported, $this->skipped ).'
			</p></div>';

			$this->import_end();
		}

		/**
		 * Performs post-import cleanup of files and the cache
		 */
		public function import_end() {
			echo '<p>' . __( 'All done!', 'eha_multi_carrier_shipping' ) . ' <a href="' . admin_url('admin.php?page=wc-settings&tab=shipping&section=eha_multi_carrier_shipping_area') . '">' . __( 'View zone Matrix', 'eha_multi_carrier_shipping' ) . '</a>' . '</p>';

			do_action( 'import_end' );
		}

		/**
		 * Handles the CSV upload and initial parsing of the file to prepare for
		 * displaying author import options
		 *
		 * @return bool False if error uploading or invalid file, true otherwise
		 */
		public function handle_upload() {

			if ( empty( $_POST['file_url'] ) ) {

				$file = wp_import_handle_upload();

				if ( isset( $file['error'] ) ) {
					echo '<p><strong>' . __( 'Sorry, there has been an error.', 'eha_multi_carrier_shipping' ) . '</strong><br />';
					echo esc_html( $file['error'] ) . '</p>';
					return false;
				}

				$this->id = (int) $file['id'];

			} else {

				if ( file_exists( ABSPATH . $_POST['file_url'] ) ) {

					$this->file_url = esc_attr( $_POST['file_url'] );

				} else {

					echo '<p><strong>' . __( 'Sorry, there has been an error.', 'eha_multi_carrier_shipping' ) . '</strong></p>';
					return false;

				}

			}

			return true;
		}

		/**
		 * header function.
		 */
		public function header() {
			echo '<div class="wrap"><div class="icon32 icon32-woocommerce-importer" id="icon-woocommerce"><br></div>';
			echo '<h2>' . __( 'Import Multi Carrier Shipping zone Matrix', 'eha_multi_carrier_shipping' ) . '</h2>';
		}

		/**
		 * footer function.
		 */
		public function footer() {
			echo '</div>';
		}

		/**
		 * greet function.
		 */
		public function greet() {

			echo '<div class="narrow">';
			echo '<p>' . __( 'Hi there! Upload a CSV file containing Multi Carrier Shipping zone Matrix to import the contents into your shop. Choose a .csv file to upload, then click "Upload file and import".', 'eha_multi_carrier_shipping' ).'</p>';

			echo '<p>' . sprintf( __( 'Multi Carrier Shipping zone Matrix need to be defined with columns in a specific order (4 columns). <a href="%s">Click here to download a sample</a>.', 'eha_multi_carrier_shipping' ), wf_plugin_url_mcp() . '/sample-data/sample_multi_carrier_shipping_zone_matrix.csv' ) . '</p>';

			$action = 'admin.php?import=multicarriershipping_zone_matrix_csv&step=1';

			$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
			$size = size_format( $bytes );
			$upload_dir = wp_upload_dir();
			if ( ! empty( $upload_dir['error'] ) ) :
				?><div class="error"><p><?php _e( 'Before you can upload your import file, you will need to fix the following error:', 'eha_multi_carrier_shipping' ); ?></p>
				<p><strong><?php echo $upload_dir['error']; ?></strong></p></div><?php
			else :
				?>
				<form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr(wp_nonce_url($action, 'import-upload')); ?>">
					<table class="form-table">
						<tbody>
							<tr>
								<th>
									<label for="upload"><?php _e( 'Choose a file from your computer:', 'eha_multi_carrier_shipping' ); ?></label>
								</th>
								<td>
									<input type="file" id="upload" name="import" size="25" />
									<input type="hidden" name="action" value="save" />
									<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
									<small><?php printf( __('Maximum size: %s', 'eha_multi_carrier_shipping' ), $size ); ?></small>
								</td>
							</tr>
							<tr>
								<th>
									<label for="file_url"><?php _e( 'OR enter path to file:', 'eha_multi_carrier_shipping' ); ?></label>
								</th>
								<td>
									<?php echo ' ' . ABSPATH . ' '; ?><input type="text" id="file_url" name="file_url" size="25" />
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Delimiter', 'eha_multi_carrier_shipping' ); ?></label><br/></th>
								<td><input type="text" name="delimiter" placeholder="," size="2" /></td>
							</tr>
						</tbody>
					</table>
					<p class="submit">
						<input type="submit" class="button" value="<?php esc_attr_e( 'Upload file and import', 'eha_multi_carrier_shipping' ); ?>" />
					</p>
				</form>
				<?php
			endif;

			echo '</div>';
		}

		/**
		 * Added to http_request_timeout filter to force timeout at 60 seconds during import
		 *
		 * @param  int $val
		 * @return int 60
		 */
		public function bump_request_timeout( $val ) {
			return 60;
		}
	}
}