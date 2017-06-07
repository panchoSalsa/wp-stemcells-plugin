<?php

	require_once('wp-advanced-search/wpas.php');

	function enqueue_parent_styles() {
		wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );
	}

	function vantage_child_scripts() {
		// adding css/* files
		// source=https://premium.wpmudev.org/blog/how-to-create-wordpress-child-theme/
		wp_enqueue_style( 'vantage-child-foundation', get_stylesheet_directory_uri() . '/css/foundation.css' );
		wp_enqueue_style( 'vantage-child-prism', get_stylesheet_directory_uri() . '/css/prism.css' );
	}

	function product_content(){
	    global $product;
	    echo the_content();
	}

	function demo_ajax_search() {
		$args = array();
		$args['wp_query'] = array('post_type' => array('product'), 
		                         'orderby' => 'title',
		                         // set number of posts to display here
		                         // i had to remove post_per_page field
		                         // because results were reappering
		                         // when loading more posts
		                         'posts_per_page' => 100, 
		                         'order' => 'ASC' );

		$args['form'] = array( 'auto_submit' => true );

		$args['form']['ajax'] = array( 'enabled' => true,
		                         'show_default_results' => true,
		                         'results_template' => 'template-ajax-results.php', // This file must exist in your theme root
		                         'button_text' => 'Load More Results');

		$args['fields'][] = array( 'type' => 'search', 
		                         'placeholder' => 'Enter search terms' );

		$args['fields'][] = array('type' => 'taxonomy',
					              'taxonomy' => 'sex',
					              'label' => 'Sex',
					              'format' => 'checkbox');
		
		// $args['fields'][] = array('type' => 'taxonomy',
		// 			              'taxonomy' => 'ipsc_clones',
		// 			              'label' => 'iPSC Clones',
		// 			              'format' => 'checkbox');

		$args['fields'][] = array('type' => 'taxonomy',
					              'taxonomy' => 'ethnicity',
					              'label' => 'Ethnicity',
					              'format' => 'checkbox');

		$args['fields'][] = array('type' => 'taxonomy',
					              'taxonomy' => 'sample_source',
					              'label' => 'Sample Source',
					              'format' => 'checkbox');

		$args['fields'][] = array('type' => 'taxonomy',
					              'taxonomy' => 'apoe',
					              'label' => 'Apoe',
					              'format' => 'checkbox');

		// $args['fields'][] = array('type' => 'taxonomy',
		// 			              'taxonomy' => 'current_dx',
		// 			              'label' => 'Current Dx',
		// 			              'format' => 'checkbox');

		$args['fields'][] = array( 'type' => 'orderby', 
		                         'format' => 'select', 
		                         'label' => 'Order by', 
		                         'values' => array('title' => 'Title', 'date' => 'Date') );
		$args['fields'][] = array( 'type' => 'order', 
		                         'format' => 'radio', 
		                         'label' => 'Order', 
		                         'values' => array('ASC' => 'ASC', 'DESC' => 'DESC'), 
		                         'default' => 'ASC' );
		// $args['fields'][] = array( 'type' => 'posts_per_page', 
		//                          'format' => 'select', 
		//                          'label' => 'Results per page', 
		//                          'values' => array(3=>3, 6=>6, 9=>9),  
		//                          'default' => -1 );
		$args['fields'][] = array( 'type' => 'reset',
		                         'class' => 'button',
		                         'value' => 'Reset' );
		register_wpas_form('myform', $args);
	}

	add_action( 'wp_enqueue_scripts', 'enqueue_parent_styles' );
	add_action( 'wp_enqueue_scripts', 'vantage_child_scripts' );

	// show product content
	add_action( 'woocommerce_single_product_summary', 'product_content', 17 );

	// remove woocommerce single product meta data
	remove_action( 'woocommerce_single_product_summary','woocommerce_template_single_meta', 40 );

	// remove woocommerce additional data and reviews tab
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );

	add_action( 'init', 'demo_ajax_search' );
?>