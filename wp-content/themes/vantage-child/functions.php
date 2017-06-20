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


	// source=https://nicola.blog/2015/07/20/change-the-return-to-shop-button-url-in-the-cart-page/
	/**
	 * Changes the redirect URL for the Return To Shop button in the cart.
	 *
	 * @return string
	 */
	function wc_empty_cart_redirect_url() {
		// $page = get_page_by_title( 'wp-advanced search' );
		// ChromePhp::log(get_post_permalink($page->ID));

		// need to fix this !!!
		return 'http://localhost:8888/stemcells/wp-advanced-search/';
	}


	//Removes billing from checkout
	//source=https://gist.github.com/BFTrick/7873168
	function remove_checkout_fields( $fields ){
	    unset($fields['billing']['billing_first_name']);
	    unset($fields['billing']['billing_last_name']);
	    unset($fields['billing']['billing_company']);
	    unset($fields['billing']['billing_address_1']);
	    unset($fields['billing']['billing_address_2']);
	    unset($fields['billing']['billing_city']);
	    unset($fields['billing']['billing_postcode']);
	    unset($fields['billing']['billing_country']);
	    unset($fields['billing']['billing_state']);
	    unset($fields['billing']['billing_phone']);
	    unset($fields['billing']['billing_address_2']);
	    unset($fields['billing']['billing_postcode']);
	    unset($fields['billing']['billing_company']);
	    unset($fields['billing']['billing_last_name']);
	    unset($fields['billing']['billing_city']);
	    $fields['billing']['billing_email']['class'] = array('form-row','validate-required', 'validate-email', 'woocommerce-validated');
	    return $fields;
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

		// sort facet fields alphabetically using 'term_args'
		// using 'DESC' because I want [Not Applicable] to be the last field
		// source=http://wpadvancedsearch.com/param/term_args/
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

		$args['fields'][] = array('type' => 'taxonomy',
					              'taxonomy' => 'current_dx',
					              'label' => 'Current Diagnostics',
					              'operator' => 'IN',
					              'format' => 'checkbox',
					              'term_args' => array('hide_empty' => true, 
                                               'orderby' => 'title', 
                                               'order' => 'DESC')
					              );

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

	// change return to shop link
	// source=https://nicola.blog/2015/07/20/change-the-return-to-shop-button-url-in-the-cart-page/
	add_filter( 'woocommerce_return_to_shop_redirect', 'wc_empty_cart_redirect_url' );

	//Removes billing from checkout
	//source=https://gist.github.com/BFTrick/7873168
	add_filter(	'woocommerce_checkout_fields', 'remove_checkout_fields');
?>