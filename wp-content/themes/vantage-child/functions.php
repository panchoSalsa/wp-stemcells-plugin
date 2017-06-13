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



	// source=https://isabelcastillo.com/woocommerce-cart-icon-count-theme-header
	/**
	 * Ensure cart contents update when products are added to the cart via AJAX
	 */
	function my_header_add_to_cart_fragment( $fragments ) {

	    // ChromePhp::log('my_header_add_to_cart_fragment()');
	    ob_start();
	    $count = WC()->cart->cart_contents_count;

	    ChromePhp::log($count);
	    ?><a class="cart-contents" href="<?php echo WC()->cart->get_cart_url(); ?>" title="<?php _e( 'View your shopping cart' ); ?>"><?php
	    if ( $count > 0 ) {
	        ?>
	        <span class="cart-contents-count"><?php echo esc_html( $count ); ?></span>
	        <?php            
	    }
	        ?></a><?php
	 
	    $fragments['a.cart-contents'] = ob_get_clean();
	     
	    return $fragments;
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
					              'operator' => 'IN',
					              'format' => 'checkbox',
					              'term_args' => array('hide_empty' => true, 
                                               'orderby' => 'title', 
                                               'order' => 'DESC')
					              );

		$args['fields'][] = array('type' => 'taxonomy',
					              'taxonomy' => 'ethnicity',
					              'label' => 'Ethnicity',
					              'operator' => 'IN',
					              'format' => 'checkbox',
					              'term_args' => array('hide_empty' => true, 
                                               'orderby' => 'title', 
                                               'order' => 'DESC')
					              );

		$args['fields'][] = array('type' => 'taxonomy',
					              'taxonomy' => 'sample_source',
					              'label' => 'Sample Source',
					              'operator' => 'IN',
					              'format' => 'checkbox',
					              'term_args' => array('hide_empty' => true, 
                                               'orderby' => 'title', 
                                               'order' => 'DESC')
					              );

		$args['fields'][] = array('type' => 'taxonomy',
					              'taxonomy' => 'syndrome_biopsy',
					              'label' => 'Syndrome Biopsy',
					              'operator' => 'IN',
					              'format' => 'checkbox',
					              'term_args' => array('hide_empty' => true, 
                                               'orderby' => 'title', 
                                               'order' => 'DESC')
					              );

		$args['fields'][] = array('type' => 'taxonomy',
					              'taxonomy' => 'current_syndrome',
					              'label' => 'Current Syndrome',
					              'operator' => 'IN',
					              'format' => 'checkbox',
					              'term_args' => array('hide_empty' => true, 
                                               'orderby' => 'title', 
                                               'order' => 'DESC')
					              );

		$args['fields'][] = array('type' => 'taxonomy',
					              'taxonomy' => 'change_in_syndrome',
					              'label' => 'Change in Syndrome',
					              'operator' => 'IN',
					              'format' => 'checkbox',
					              'term_args' => array('hide_empty' => true, 
                                               'orderby' => 'title', 
                                               'order' => 'DESC')
					              );

		// $args['fields'][] = array('type' => 'taxonomy',
		// 			              'taxonomy' => 'current_dx',
		// 			              'label' => 'Current Diagnostics',
		// 			              'operator' => 'IN',
		// 			              'format' => 'checkbox',
		// 			              'term_args' => array('hide_empty' => true, 
  //                                              'orderby' => 'title', 
  //                                              'order' => 'DESC')
		// 			              );

		$args['fields'][] = array('type' => 'taxonomy',
					              'taxonomy' => 'trem',
					              'label' => 'TREM',
					              'operator' => 'IN',
					              'format' => 'checkbox',
					              'term_args' => array('hide_empty' => true, 
                                               'orderby' => 'title', 
                                               'order' => 'DESC')
					              );

		$args['fields'][] = array('type' => 'taxonomy',
					              'taxonomy' => 'apoe',
					              'label' => 'ApoE',
					              'operator' => 'IN',
					              'format' => 'checkbox',
					              'term_args' => array('hide_empty' => true, 
                                               'orderby' => 'title', 
                                               'order' => 'DESC')
					              );

		// Order By Date or Title
		// $args['fields'][] = array( 'type' => 'orderby', 
		//                          'format' => 'select', 
		//                          'label' => 'Order by', 
		//                          'values' => array('title' => 'Title', 'date' => 'Date') );

		// Order By Asc or Desc
		// $args['fields'][] = array( 'type' => 'order', 
		//                          'format' => 'radio', 
		//                          'label' => 'Order', 
		//                          'values' => array('ASC' => 'ASC', 'DESC' => 'DESC'), 
		//                          'default' => 'ASC' );

		// Request Posts Per Page
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

	// shopping cart display
	// source=https://isabelcastillo.com/woocommerce-cart-icon-count-theme-header
	add_filter( 'woocommerce_add_to_cart_fragments', 'my_header_add_to_cart_fragment');
?>