<?php
/**
 * @package Hello_Dolly
 * @version 1.6
 */
/*
Plugin Name: Hello Dolly
Plugin URI: http://wordpress.org/plugins/hello-dolly/
Description: This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.
Author: Matt Mullenweg
Version: 1.6
Author URI: http://ma.tt/
*/


// source=http://stackoverflow.com/questions/21028830/woocommerce-get-products
// source=https://developer.wordpress.org/reference/functions/wp_insert_post/
// source=http://lukasznowicki.info/insert-new-woocommerce-product-programmatically/
// source=https://wordpress.stackexchange.com/questions/150231/post-categories-array-using-variable

// function my_message() {
// 	$args = array( 'post_type' => 'product');
// 	$products = get_posts( $args ); 

	// foreach ($products as &$product) {
	// 	echo $product->post_title . "<br>";
	// }
	// if ( !is_page('hook-page') ) {
	// 	//echo print_r($products) . "\n";
	// 	echo '<script type="text/javascript">alert("hook-page")</script>';
	// }

	// $categories = array('17');

	// $post_id = wp_insert_post( array(
	// 	'post_title' => 'Great new product',
	// 	'post_content' => 'Here is content of the post, so this is our great new products description',
	// 	'post_status' => 'publish',
	// 	'post_type' => "product",
	// 	'post_category' => $categories
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

// }

// add_action( 'admin_notices', 'my_message' );


// source=http://stackoverflow.com/questions/22070223/how-can-i-use-is-page-inside-a-plugin
function plugin_is_page() {
	if ( is_page('hook-page') ) {
		// echo '<script type="text/javascript">alert("hook-page")</script>';
		add_filter('the_content','hello_world');
		add_action('wp_footer', 'your_function');

	}
}

function hello_world() {
	$args = array( 'post_type' => 'product');
	$products = get_posts( $args ); 

	foreach ($products as &$product) {
		echo $product->post_title . "<br>";
	}
}

function your_function() {
	$args = array( 'post_type' => 'product');
	$products = get_posts( $args ); 

	foreach ($products as &$product) {
		echo $product->post_title . "<br>";
	}
}


add_action( 'template_redirect', 'plugin_is_page' );

?>