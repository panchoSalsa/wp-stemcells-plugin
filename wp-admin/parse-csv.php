<?php
	// this file is saved under wp-admin

	// enable wp functionality
	function enableWP() {
		// if(!defined(ABSPATH)){
		// 	$pagePath = explode('/wp-content/', dirname(__FILE__));
		// 	include_once(str_replace('wp-content/' , '', $pagePath[0] . '/wp-load.php'));
		// }
		require_once( dirname( __FILE__ ) . '/admin.php' );
	}

	// function handles POST Request
	// converts json string -> json_array
	function handleRequest() {
		$data = $_POST['data'];
		$json_array = json_decode($data, true);
		return $json_array;
	}

	function importProducts($json_array) {
		foreach($json_array as $item) {
			// echo 'Record ID: ' . $item['Record ID'] . ',';
			createProduct($item);
			// echo $item['name'];
		}
	}

	function createCategories() {

	}

	function createCategory() {

	}

	function createProduct($item) {


		// $post_id = wp_insert_post( array(
		// 	'post_name' => 'hello',
		// 	'post_status' => 'publish',
		// 	'post_type' => "product"
		// ) );

		// wp_set_object_terms( $post_id, 'simple', 'product_type' );

		// update_post_meta( $post_id, '_visibility', 'visible' );
		// update_post_meta( $post_id, '_stock_status', 'instock');
		// update_post_meta( $post_id, 'total_sales', '0' );
		// update_post_meta( $post_id, '_downloadable', 'no' );
		// update_post_meta( $post_id, '_virtual', 'yes' );
		// update_post_meta( $post_id, '_regular_price', '$2.99' );
		// update_post_meta( $post_id, '_sale_price', '' );
		// update_post_meta( $post_id, '_purchase_note', '' );
		// update_post_meta( $post_id, '_featured', 'no' );
		// update_post_meta( $post_id, '_weight', '' );
		// update_post_meta( $post_id, '_length', '' );
		// update_post_meta( $post_id, '_width', '' );
		// update_post_meta( $post_id, '_height', '' );
		// update_post_meta( $post_id, '_sku', 'test03' );
		// update_post_meta( $post_id, '_product_attributes', array() );
		// update_post_meta( $post_id, '_sale_price_dates_from', '' );
		// update_post_meta( $post_id, '_sale_price_dates_to', '' );
		// update_post_meta( $post_id, '_price', '$2.99' );
		// update_post_meta( $post_id, '_sold_individually', '' );
		// update_post_meta( $post_id, '_manage_stock', 'no' );
		// update_post_meta( $post_id, '_backorders', 'no' );
		// update_post_meta( $post_id, '_stock', '' );

		echo 'success';

	}

	enableWP();
	$json_array = handleRequest();
	importProducts($json_array);
		$post_id = wp_insert_post( array(
			'post_name' => 'hello',
			'post_status' => 'publish',
			'post_type' => "product"
		) );

		wp_set_object_terms( $post_id, 'simple', 'product_type' );

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
		echo 'success2'


	// wp_insert_term(
	// 	'New Category', // the term 
	// 	'product_cat', // the taxonomy
	// 	array(
	// 		'description'=> 'Category description',
	// 		'slug' => 'new-category'
	// 	)
	// );
// wp_insert_term(
//   'Apple', // the term 
//   'product', // the taxonomy
//   array(
//     'description'=> 'A yummy apple.',
//     'slug' => 'apple'
//   )
// );
?>