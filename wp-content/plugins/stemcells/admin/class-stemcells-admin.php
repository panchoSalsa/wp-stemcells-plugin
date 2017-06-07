<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.mind.uci.edu/
 * @since      1.0.0
 *
 * @package    Stemcells
 * @subpackage Stemcells/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Stemcells
 * @subpackage Stemcells/admin
 * @author     Ken Francisco <frfranco@uci.edu>
 */
class Stemcells_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Stemcells_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Stemcells_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/stemcells-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Stemcells_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Stemcells_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/stemcells-admin.js', array( 'jquery' ), $this->version, false );

		// download papaparser from CDN
		// this js library allows easier csv parsing to json conversion 
		wp_enqueue_script( 'papaparser', 'https://cdnjs.cloudflare.com/ajax/libs/PapaParse/4.3.2/papaparse.js');

	}


	public function add_plugin_admin_menu() {

	    /*
	     * Add a settings page for this plugin to the Settings menu.
	     *
	     * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
	     *
	     *        Administration Menus: http://codex.wordpress.org/Administration_Menus
	     *
	     */


	    // adding menu to admin dashboard
	    // source=https://premium.wpmudev.org/blog/creating-wordpress-admin-pages/?utm_expid=3606929-105.kKHVTz43T_CV513Vo9oSow.0&utm_referrer=https%3A%2F%2Fwww.google.com%2F
	    // source=https://developer.wordpress.org/reference/functions/add_menu_page/

	    //ChromePhp::log('admin_menu()');
	    add_menu_page( 'stemcells', 'stemcells', 'manage_options', $this->plugin_name, array($this, 'display_plugin_setup_page'), 'dashicons-cart');
	}

	public function display_plugin_setup_page() {
		include_once( 'partials/stemcells-admin-display.php' );
	}

	// add dashicons to front end
	//source=http://www.wpsuperstars.net/how-to-use-dashicons/
	public function load_dashicons_front_end() {
		wp_enqueue_style( 'dashicons' );
	}

	public function csv_handler() {
		echo 'csv_handler()';

		$json_array = $this->handleRequest();


		if (is_null($json_array)) {
			echo '$data is NULL';
		}
		else {
			echo 'Variable is not NULL';

			// delete previous products
			$this->deleteProducts();

			// here we will iterate through each row to add products

			foreach($json_array as $item) {
				$this->createProduct($item);
			}

		}

		// this is required to terminate immediately and return a proper response
		wp_die();
	}

	private function deleteProducts() {

		// source=https://codex.wordpress.org/Template_Tags/get_posts

		// delete N posts
		// source=https://developer.wordpress.org/reference/functions/get_posts/
		$args = array(
			'post_type' => 'product'
		);

		// 'numberposts' => -1 to delete every product
		$defaults = array(
			'numberposts' => -1
		);

		$posts_array = get_posts( $args, $defaults );

		foreach($posts_array as $post){
			wp_delete_post( $post->ID, true );
		}
	}

	private function handleRequest() {
		echo 'handleRequest()';

		$data = $_POST['data'];

		// // transforming data into an array
		// // stripslashes is needed to remove '\'
		// // json_decode() will fail if '\' are present
		// // ex:
		// //{\"RecordID\":\"2\",\"name\":\"ken\"}
		// //{"RecordID":"1","name":"francisco"}
		$json_array = json_decode(stripslashes($data), true);

		return $json_array;
	}

	private function createProduct($item) {
		// echo 'createProduct()';

		// source=https://wordpress.stackexchange.com/questions/137501/how-to-add-product-in-woocommerce-with-php-code
		$post_id = wp_insert_post( array(
			// 'post_title' => $item['Record ID'],
			'post_title' => $this->createTitle($item),
			'post_content' => $this->createContent($item),
			'post_status' => 'publish',
			'post_type' => "product"
		) );

		// create terms between taxonomies

		// wp_set_object_terms( $post_id, $item['ipsc_clones'], 'ipsc_clones');
		wp_set_object_terms( $post_id, $item['sex'], 'sex');
		wp_set_object_terms( $post_id, $item['ethnicity'], 'ethnicity');
		wp_set_object_terms( $post_id, $item['sample_source'], 'sample_source');
		wp_set_object_terms( $post_id, $item['apoe'], 'apoe');
		// wp_set_object_terms( $post_id, $item['current_dx'], 'current_dx');


		update_post_meta( $post_id, '_visibility', 'visible' );
		update_post_meta( $post_id, '_stock_status', 'instock');
		update_post_meta( $post_id, 'total_sales', '0' );
		update_post_meta( $post_id, '_downloadable', 'no' );
		update_post_meta( $post_id, '_virtual', 'yes' );
		update_post_meta( $post_id, '_regular_price', '$2.99' );
		update_post_meta( $post_id, '_sale_price', '' );
		update_post_meta( $post_id, '_purchase_note', '' );
		update_post_meta( $post_id, '_featured', 'no' );
		update_post_meta( $post_id, '_weight', '' );
		update_post_meta( $post_id, '_length', '' );
		update_post_meta( $post_id, '_width', '' );
		update_post_meta( $post_id, '_height', '' );
		update_post_meta( $post_id, '_sku', 'test03' );
		update_post_meta( $post_id, '_product_attributes', array() );
		update_post_meta( $post_id, '_sale_price_dates_from', '' );
		update_post_meta( $post_id, '_sale_price_dates_to', '' );
		update_post_meta( $post_id, '_price', '$2.99' );
		update_post_meta( $post_id, '_sold_individually', '' );
		update_post_meta( $post_id, '_manage_stock', 'no' );
		update_post_meta( $post_id, '_backorders', 'no' );
		update_post_meta( $post_id, '_stock', '' );


		// adding image to product

		update_post_meta( $post_id, '_product_image_gallery', '');
		add_post_meta($post_id, '_thumbnail_id', 23);

		// echo 'createProduct() exit';
	}

	private function createTitle($item) {
		$str = "";
		$str .= $item['ipsc_line_name'] . " ";
		$str .= $item['ipsc_id'] . " ";
		$str .= $item['clone_id'] . " ";
		return $str;
	}

	private function createContent($item) {
		$str = "";
		$str .= "<p><strong>Sex:</strong> " . $item['sex'] . "</p>";
		$str .= "<p><strong>Ethnicity:</strong> " . $item['Ethnicity:  (What is the patient\'s ethnicity?)'] . "</p>";
		$str .= "<p><strong>Sample Source:</strong> " . $item['Sample Source:'] . "</p>";
		$str .= "<p><strong>iPSC Clones:</strong> " . $item['ipsc_clones'] . "</p>";
		$str .= "<p><strong>iPSC Karyotype:</strong> " . $item['iPSC Karyotype:  (Please enter the iPSC karyotype)'] . "</p>";
		$str .="<p><strong>Syndrome:</strong> " . $item['Syndrome at time of biopsy:  (What is the Syndrome at the time of biopsy?)'] . "</p>";
		$str .= "<p><strong>MCI:</strong> " . $item['Current MCI Category:  (What is the current MCI diagnostic?)'] . "</p>";
		return $str;
	}

	public function register_taxonomies() {
		// csv import file 
		// header file should contain the name of taxonomies in lowercase and underscore characters

		register_taxonomy('sex', 'product_type');
		// register_taxonomy('ipsc_clones', 'product_type');
		register_taxonomy('ethnicity', 'product_type');
		register_taxonomy('sample_source', 'product_type');
		register_taxonomy('apoe', 'product_type');
		// register_taxonomy('current_dx', 'product_type');
	}
}
