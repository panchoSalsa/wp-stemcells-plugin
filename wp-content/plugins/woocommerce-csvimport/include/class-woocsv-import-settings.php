<?php

class woocsv_import_settings {
	
	public function __construct() {
		
		add_action( 'admin_init', array( $this,'register_settings' ));
			
	}
	
	public function register_settings () {

		//woocsv import section
		add_settings_section('woocsv-settings', '', array($this,'section'), 'woocsv-settings');

		//fields
		//allowed roles
		add_settings_field('woocsv_roles', __('Allowed Roles','woocommerce-csvimport'), array($this,'roles'), 'woocsv-settings','woocsv-settings');
		register_setting( 'woocsv-settings', 'woocsv_roles', array($this,'options_validate') );
		
		//separator
		add_settings_field('woocsv_separator', __('Field separator','woocommerce-csvimport'), array($this,'separator'), 'woocsv-settings','woocsv-settings');
		register_setting( 'woocsv-settings', 'woocsv_separator', array($this,'options_validate') );

		//skip first row
		add_settings_field('woocsv_skip_first_line', __('Skip the first row','woocommerce-csvimport'), array($this,'skip_first_line'), 'woocsv-settings','woocsv-settings');
		register_setting( 'woocsv-settings', 'woocsv_skip_first_line', array($this,'options_validate') );
		
		//categories
		add_settings_field('woocsv_add_to_categories', __('Add products to all categories','woocommerce-csvimport'), array($this,'add_to_categories'), 'woocsv-settings','woocsv-settings');
		register_setting( 'woocsv-settings', 'woocsv_add_to_categories', array($this,'options_validate') );
		
		//blocksize
		add_settings_field('woocsv_blocksize', __('Number of products to process simultaneously','woocommerce-csvimport'), array($this,'blocksize'), 'woocsv-settings','woocsv-settings');
		register_setting( 'woocsv-settings', 'woocsv_blocksize', array($this,'options_validate') );
		
		//merge_products
		add_settings_field('woocsv_merge_products', __('Merge products','woocommerce-csvimport'), array($this,'merge_products'), 'woocsv-settings','woocsv-settings');	
		register_setting( 'woocsv-settings', 'woocsv_merge_products', array($this,'options_validate') );
		
		//debug
		add_settings_field('woocsv_debug', __('Debug','woocommerce-csvimport'), array($this,'debug'), 'woocsv-settings','woocsv-settings');
		register_setting( 'woocsv-settings', 'woocsv_debug', array($this,'options_validate') );
		
		//match_by
		add_settings_field('woocsv_match_by', __('Find product using','woocommerce-csvimport'), array($this,'match_by'), 'woocsv-settings','woocsv-settings');
		register_setting( 'woocsv-settings', 'woocsv_match_by', array($this,'options_validate') );
		
		//match_author_by
		add_settings_field('woocsv_match_author_by', __('Match authors by','woocommerce-csvimport'), array($this,'match_author_by'), 'woocsv-settings','woocsv-settings');
		register_setting( 'woocsv-settings', 'woocsv_match_author_by', array($this,'options_validate') );
		
		//convert to utf8
		add_settings_field('woocsv_convert_to_utf8', __('Convert to UTF-08','woocommerce-csvimport'), array($this,'convert_to_utf8'), 'woocsv-settings','woocsv-settings');
		register_setting( 'woocsv-settings', 'woocsv_convert_to_utf8', array($this,'options_validate') );
		
		//enable CURLOPT_FOLLOWLOCATION
		add_settings_field('woocsv_curl_followlocation', __('Follow to location during image import','woocommerce-csvimport'), array($this,'curl_followlocation'), 'woocsv-settings','woocsv-settings');
		register_setting( 'woocsv-settings', 'curl_followlocation', array($this,'options_validate') );
	
}
	function curl_followlocation () {
		$value = get_option('woocsv_curl_followlocation');
		echo '<select id="woocsv_curl_followlocation" name="woocsv_curl_followlocation">';
			echo '<option '. selected("0",$value).' value="0">'.__('No','woocommerce-csvimport').'</option>';
			echo '<option '. selected("1",$value).' value="1">'.__('Yes','woocommerce-csvimport').'</option>';
		echo '</select>';
		echo '<p class="description">'.__('When you import images using an URL, the server sometimes redirects you to the actual path. This settings enabled that you follow that path. If you have safe_mode or open_basedir enabled, disable this setting.','woocommerce-csvimport').'</p>';		
	}
	
	public function match_author_by () {
		$debug = get_option('woocsv_match_author_by');
		echo '<select id="match_author_by" name="woocsv_match_author_by">';
			echo '<option '. selected("id",$debug).' value="id">'.__('ID','woocommerce-csvimport').'</option>';
			echo '<option '. selected("slug",$debug).' value="slug">'.__('Slug','woocommerce-csvimport').'</option>';
			echo '<option '. selected("email",$debug).' value="email">'.__('Email','woocommerce-csvimport').'</option>';
			echo '<option '. selected("login",$debug).' value="login">'.__('Login','woocommerce-csvimport').'</option>';
		echo '</select>';
		echo '<p class="description">'.__('When you want the products to belong to a user you can select how the uses must be found. Using the ID, slug, email or login name of the user.','woocommerce-csvimport').'</p>';	
	}
	
	public function match_by () {
		$debug = get_option('woocsv_match_by');
		echo '<select id="match_by" name="woocsv_match_by">';
			echo '<option '. selected("sku",$debug).' value="sku">'.__('Sku','woocommerce-csvimport').'</option>';
			echo '<option '. selected("title",$debug).' value="title">'.__('Title','woocommerce-csvimport').'</option>';
		echo '</select>';
		echo '<p class="description">'.__('When you merge products, products are found by there SKU, this is the unique identifier. In some cases, when you have no SKU, the post title could be used.','woocommerce-csvimport').'</p>';	
	}
	
	public function skip_first_line () {
		$debug = get_option('woocsv_skip_first_line');
		echo '<select id="skip_first_line" name="woocsv_skip_first_line">';
			echo '<option '. selected("0",$debug).' value="0">'.__('No','woocommerce-csvimport').'</option>';
			echo '<option '. selected("1",$debug).' value="1">'.__('Yes','woocommerce-csvimport').'</option>';
		echo '</select>';
		echo '<p class="description">'.__('First lines in are often headers you do not want to import.','woocommerce-csvimport').'</p>';	
	}
	
	
	public function debug () {
		$debug = get_option('woocsv_debug');
		echo '<select id="debug" name="woocsv_debug">';
			echo '<option '. selected("0",$debug).' value="0">'.__('No','woocommerce-csvimport').'</option>';
			echo '<option '. selected("1",$debug).' value="1">'.__('Yes','woocommerce-csvimport').'</option>';
		echo '</select>';
		echo '<p class="description">'.__('When you enable debug, the javascript console will hold more information. IF you encounter problems the debug information may be useful in solving them.','woocommerce-csvimport').'</p>';	
	}
	
	public function merge_products () {
		$merge_products = get_option('woocsv_merge_products');
		echo '<select id="woocsv_merge_products" name="woocsv_merge_products">';
			echo '<option '. selected("0",$merge_products).' value="0">'.__('No','woocommerce-csvimport').'</option>';
			echo '<option '. selected("1",$merge_products).' value="1">'.__('Yes','woocommerce-csvimport').'</option>';
		echo '</select>';
		echo '<p class="description">When you merge products, existing values of the product will be preserved. And only values from the CSV will be imported.</p>';
	}
	
	public function blocksize () {
	$blocksize = get_option('woocsv_blocksize');
		echo '<select id="blocksize" name="woocsv_blocksize">';
            echo '<option '. selected("0",$blocksize).' value="0">'.__('auto','woocommerce-csvimport').'</option>';
            echo '<option '. selected("1",$blocksize).' value="1">'.__('1','woocommerce-csvimport').'</option>';
			echo '<option '. selected("10",$blocksize).' value="10">'.__('10','woocommerce-csvimport').'</option>';
			echo '<option '. selected("25",$blocksize).' value="25">'.__('25','woocommerce-csvimport').'</option>';
			echo '<option '. selected("50",$blocksize).' value="50">'.__('50','woocommerce-csvimport').'</option>';
			echo '<option '. selected("100",$blocksize).' value="100">'.__('100','woocommerce-csvimport').'</option>';
			echo '<option '. selected("10000",$blocksize).' value="10000">'.__('10000','woocommerce-csvimport').'</option>';
		echo '</select>';
		echo '<p class="description">'.__('The importing proces works with AJAX calls to avoid timeouts on large CSV files. With this setting you can influence how many rows there are processed every call.','woocommerce-csvimport').'</p>';
	}
	
	public function add_to_categories () {
		$add_to_categories = get_option('woocsv_add_to_categories');
		echo '<select id="add_to_categories" name="woocsv_add_to_categories">';
			echo '<option '. selected("0",$add_to_categories).' value="0">'.__('No','woocommerce-csvimport').'</option>';
			echo '<option '. selected("1",$add_to_categories).' value="1">'.__('Yes','woocommerce-csvimport').'</option>';
		echo '</select>';
		echo '<p class="description">'.__('If you enable this, products will be added to all categories on not only the latest. Example :<code>cat1->subcat1</code> , if the option is enabled, the product belongs to both else it will only belong to the sub categorie.','woocommerce-csvimport').'</p>';
	}
	
	
	public function separator() {
		$separator = get_option('woocsv_separator');
		echo '<select id="woocsv_separator" name="woocsv_separator">';
			echo '<option '. selected(";",$separator).' value=";">;</option>';
			echo '<option '. selected(",",$separator).' value=",">,</option>';
			echo '<option '. selected("~",$separator).' value="~">~</option>';
		echo '</select>';
		echo '<p class="description">When you merge products, existing values of the product will be preserved. And only values from the CSV will be imported.</p>';
	}
	
	function roles() {
		global $wp_roles,$woocsv_import;
		$roles = (array)get_option('woocsv_roles');
		echo '<select size='.count($wp_roles->role_names).' id="woocsv_roles" name="woocsv_roles[]" multiple required>';
		foreach ($wp_roles->role_names as $key=>$value) {
			echo "<option ".selected(true, in_array($key, $roles))." value=$key>$value</option>";
		}
		echo '</select>';
		echo '<p class="description">'.__('These roles are allowed to import and change settings.','woocommerce-csvimport').'</p>';
	}
	
	
	public function convert_to_utf8 () {
		$value = get_option('woocsv_convert_to_utf8');
		echo '<select id="woocsv_convert_to_utf8" name="woocsv_convert_to_utf8">';
			echo '<option '. selected("0",$value).' value="0">'.__('No','woocommerce-csvimport').'</option>';
			echo '<option '. selected("1",$value).' value="1">'.__('utf08 encoding','woocommerce-csvimport').'</option>';
            echo '<option '. selected("2",$value).' value="2">'.__('mb-string conversion','woocommerce-csvimport').'</option>';
		echo '</select>';
		echo '<p class="description">'.__('Convert strings to UTF-08 during import?','woocommerce-csvimport').'</p>';	
	}


	//! validation
	function options_validate($input) {
		//no validation yet
		return $input;
	}
	
		//sections callback
	function section() {
		//no text yet
	}
}	
