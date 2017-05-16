<?php
use Allaerd\UploadErrorMessages;

class woocsv_import_admin
{

	public function __construct() {
		//add menu's and css
		add_action('admin_menu', array($this,'menu'));
		
		//add ajax methode
		//delete header
		add_action( 'wp_ajax_delete_header', array( $this,'delete_header' ) );

        //move header up
		//@todo
		//add_action( 'wp_ajax_header_up', array( $this,'header_up' ) );
	
		//move header down
		//@todo
		//add_action( 'wp_ajax_header_down', array( $this,'header_down' ) );		
	}

	public function header_down() {
		
	}

	public function header_up() {
	}


	public function delete_header() {
		global $woocsv_import;

		if ( isset ( $_POST['header_name'] ) ) {
			unset ( $woocsv_import->headers[ $_POST[ 'header_name' ] ] );
			update_option( 'woocsv_headers' , $woocsv_import->headers );	
			wp_die( $_POST['header_name'] );
		}
		
		wp_die( 0 );
		
	}

	public function menu()
	{
		global $woocsv_import;
		
		//======================================
		//role support 
		//======================================
		$user = wp_get_current_user();
		$current_role = $user->roles;
		$allowed_roles = $woocsv_import->get_roles();
		
		if (is_admin() &&  ( count( array_intersect($current_role, $allowed_roles)) > 0 || current_user_can( 'manage_options' ) ) ) {

			//main page
			$page = add_menu_page('CSV Import', __('CSV Import','woocommerce-csvimport') , current($current_role), 'woocsv_import', array($this,'import'), 'dashicons-randomize', 
			'58.15011976');			
			
			//settings page
			add_submenu_page( 'woocsv_import', 'Settings', __('Settings','woocommerce-csvimport'), current($current_role), 'woocsv-settings', array($this, 'settings'));
		
			//add-ons
			add_submenu_page( 'woocsv_import', 'Docs', __('Docs','woocommerce-csvimport'), current($current_role), 'woocsv-docs', array($this, 'docs'));
			
			//documentation
			add_submenu_page( 'woocsv_import', 'Add-ons', '<span style="color:#ef6c00">'.__('Add-ons','woocommerce-csvimport').'</span>', current($current_role), 'woocsv-addons', array($this, 'addons'));
		
			//js and css
			add_action( 'admin_print_scripts-' .$page, array( $this,'enqueue_scripts' ) );
			add_action( 'admin_print_styles', array( $this,'enqueue_styles' ) );	
		}
	}
	
	public function docs () {
		include dirname( __FILE__ ) . '/partials/woocsv-import-admin-docs.php';
	}	
	
	public function addons () {
		include dirname( __FILE__ ) . '/partials/woocsv-import-admin-addons.php';
	}
	
	public function import () {
		global $woocsv_import;
		
		/* ! HEADER */
        if ( isset ($_FILES['csvfile']) &&  $_FILES['csvfile']['error'] != 0 ) {

			$fileErrorMessage = ($_FILES['csvfile']['error'])?:'unknown';
            echo '<div class="error"><p>';
            echo sprintf( __('Something went wrong during the file upload, error message: %s','woocommerce-csvimport'), UploadErrorMessages::convertErrorToMessage($_FILES[ 'csvfile'][ 'error']) );
            echo '</p></div>';
        }

		//save header
		if (	isset( $_POST['action'] ) && 
				$_POST['action'] === 'save_header_preview' && 
				check_admin_referer('save_header_preview', 'save_header_preview') 
			) {
			$this->save_header();
			include dirname( __FILE__ ) . '/partials/woocsv-import-admin-header.php';
			return;	
		}
		
		//preview the header
		if (	isset( $_POST['action'] ) &&
				$_POST['action'] === 'start_header_preview' &&
				@is_uploaded_file( $_FILES['csvfile']['tmp_name'] ) &&
				check_admin_referer( 'upload_header_file' , 'upload_header_file' ) 
			) {
			include dirname( __FILE__ ) . '/partials/woocsv-import-admin-header-preview.php';
			return;
		}
		
		//header page
		if (	(
					isset( $_GET['page'], $_GET['tab']) &&  
					$_GET['page'] == 'woocsv_import' && 
					$_GET['tab'] == 'headers'
				) || 
				empty($woocsv_import->headers) 
			) {
			include dirname( __FILE__ ) . '/partials/woocsv-import-admin-header.php';
			return;
		}

		/* ! IMPORT */
		//preview the import
		if (	isset( $_POST['action'] ) &&
				$_POST['action'] === 'start_import_preview' &&
				@is_uploaded_file( $_FILES['csvfile']['tmp_name'] ) &&
				check_admin_referer( 'upload_import_file' , 'upload_import_file' ) 
			) {
//			@since 3.1.0
            //@todo create batch allready!
			//set the active header
			$header = $woocsv_import->headers[$_POST['header_name']];
            $header_name = $_POST['header_name'];
			update_option('woocsv_header', $woocsv_import->headers[$_POST['header_name']] );
			$woocsv_import->header = $header;

			include dirname( __FILE__ ) . '/partials/woocsv-import-admin-import-preview.php';
			return;
		}

        //we are creating a batch
        if (	isset( $_POST['action'] ) &&
                $_POST['action'] === 'create_batch' &&
                check_admin_referer( 'create_batch' , 'create_batch' )
        ) {

            //create a new batch
            $new_batch_code = woocsv_batches::create();

            if ($new_batch_code) {
                $data = array(
                    'filename' => $_POST['filename'],
                    'row' => 0,
                    'header_name' => $_POST['header_name'],
                    'speperator' => $_POST['seperator'],
                    'total_rows' => $_POST['total_rows'],
                );
                woocsv_batches::update($new_batch_code, $data);

            }
        }

		//else just load the regular import screen
		include dirname( __FILE__ ) . '/partials/woocsv-import-admin-import.php';			
	}

	public static function save_header()
	{
		global $woocsv_import;
		$headerOrder = array();
		foreach ($_POST as $key=>$value) {
			if (preg_match("/fields_[0-9]/", $key, $matches)) {
				$headerOrder[] = $value;
			}
		}
		$headers = $woocsv_import->headers;
		$headers[$_POST['header_name']] = $headerOrder;
		update_option('woocsv_headers', $headers);
		$woocsv_import->headers = $headers;
	}


	public function settings () {
		include dirname( __FILE__ ) . '/partials/woocsv-import-admin-settings.php';
	}

	public function enqueue_scripts()
	{
	
		//======================================
		// register the scripts
		//======================================
		wp_enqueue_script('jquery');
		wp_register_script( 'woocsv-script', plugin_dir_url( __FILE__ ). '/js/woocsv-import-admin.js' );


		//======================================
		// localize javascript
		//======================================
		$strings = array (
			'error' => __( 'Something went wrong. We could not make a connection with the server.','woocommerce-csvimport' ),
			'done' 	=> __( 'Done','woocommerce-csvimport' ),
			'start' => __( 'Starting','woocommerce-csvimport'),
		);
		wp_localize_script( 'woocsv-script', 'strings', $strings );
	
	
		//======================================
		// enqueue the javascript
		//======================================
		wp_enqueue_script( 'woocsv-script' );
	}

	public function enqueue_styles()
	{
		wp_register_style( 'woocsv-css', plugin_dir_url( __FILE__ ).'/css/woocsv-import-admin.css' );
		wp_enqueue_style( 'woocsv-css' );
	}

}
