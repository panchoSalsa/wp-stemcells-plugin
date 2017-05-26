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

	function demo_ajax_search() {
		$args = array();
		$args['wp_query'] = array('post_type' => array('product'), 
		                         'orderby' => 'title', 
		                         'order' => 'ASC' );
		$args['form'] = array( 'auto_submit' => true );
		$args['form']['ajax'] = array( 'enabled' => true,
		                         'show_default_results' => true,
		                         'results_template' => 'template-ajax-results.php', // This file must exist in your theme root
		                         'button_text' => 'Load More Results');
		$args['fields'][] = array( 'type' => 'search', 
		                         'placeholder' => 'Enter search terms' );
		$args['fields'][] = array( 'type' => 'post_type', 
		                         'format' => 'checkbox', 
		                         'label' => 'Search by post type', 
		                         'values' => array('product' => 'Products') ,
		                         'default_all' => true );
		$args['fields'][] = array( 'type' => 'orderby', 
		                         'format' => 'select', 
		                         'label' => 'Order by', 
		                         'values' => array('title' => 'Title', 'date' => 'Date') );
		$args['fields'][] = array( 'type' => 'order', 
		                         'format' => 'radio', 
		                         'label' => 'Order', 
		                         'values' => array('ASC' => 'ASC', 'DESC' => 'DESC'), 
		                         'default' => 'ASC' );
		$args['fields'][] = array(  'type' => 'taxonomy',
		                          'format' => 'checkbox',
		                          'label' => 'Filter results by category',
		                          'taxonomy' => 'category',
		                          'default_all' => false,
		                          'operator' => 'IN' );
		$args['fields'][] = array( 'type' => 'posts_per_page', 
		                         'format' => 'select', 
		                         'label' => 'Results per page', 
		                         'values' => array(3=>3, 6=>6, 9=>9),  
		                         'default' => 6 );
		$args['fields'][] = array( 'type' => 'reset',
		                         'class' => 'button',
		                         'value' => 'Reset' );
		register_wpas_form('myform', $args);
	}

	add_action( 'wp_enqueue_scripts', 'enqueue_parent_styles' );
	add_action( 'wp_enqueue_scripts', 'vantage_child_scripts' );

	add_action('init', 'demo_ajax_search');
?>