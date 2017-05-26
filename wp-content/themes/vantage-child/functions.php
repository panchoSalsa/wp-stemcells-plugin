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

	// display stemcell_description in single-product page
	function add_stemcell_descriptions() { 
	echo '<p><strong>Sex</strong>: Female</p>';
	echo '<p><strong>Ethnicity:</strong> Caucasian</p>';
	echo '<p><strong>Sample Source:</strong> Fibroblast</p>';
	echo '<p><strong>iPSC Clones:</strong> 4</p>';
	echo '<p><strong>iPSC Karyotype:</strong> 46 XX normal</p>';
	echo '<p><strong>Initial Syndrome:</strong> Mild Cognitive Impairment</p>';
	echo '<p><strong>Initial Diagnostics:</strong> [ProbAD_P]</p>';
	echo '<p><strong>Current Diagnostics:</strong> [AD_P]</p>';
	echo '<p><strong>Current MCI:</strong> Amnestic Multi-Domain (LAV)</p>';
	};


	// source=https://isabelcastillo.com/woocommerce-product-attributes-functions
	function isa_woo_get_one_pa(){
	  
	    // Edit below with the title of the attribute you wish to display
	    $desired_att = 'major';
	   
	    global $product;
	    $attributes = $product->get_attributes();
	     
	    if ( ! $attributes ) {
	        return;
	    }
	      
	    $out = '';
	   
	    foreach ( $attributes as $attribute ) {
	          
	        if ( $attribute['is_taxonomy'] ) {
	          
	            // sanitize the desired attribute into a taxonomy slug
	            $tax_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $desired_att)));
	          
	            // if this is desired att, get value and label
	            if ( $attribute['name'] == 'pa_' . $tax_slug ) {
	              
	                $terms = wp_get_post_terms( $product->get_id(), $attribute['name'], 'all' );
	                // get the taxonomy
	                $tax = $terms[0]->taxonomy;
	                // get the tax object
	                $tax_object = get_taxonomy( $tax );
	                // get tax label
	                if ( isset ( $tax_object->labels->singular_name ) ) {
	                    $tax_label = $tax_object->labels->singular_name;
	                } elseif ( isset( $tax_object->label ) ) {
	                    $tax_label = $tax_object->label;
	                    // Trim label prefix since WC 3.0
	                    if ( 0 === strpos( $tax_label, 'Product ' ) ) {
	                       $tax_label = substr( $tax_label, 8 );
	                    }
	                }
	                  
	                foreach ( $terms as $term ) {
	       
	                    $out .= $tax_label . ': ';
	                    $out .= $term->name . '<br />';
	                       
	                }           
	              
	            } // our desired att
	              
	        } else {
	          
	            // for atts which are NOT registered as taxonomies
	              
	            // if this is desired att, get value and label
	            if ( $attribute['name'] == $desired_att ) {
	                $out .= $attribute['name'] . ': ';
	                $out .= $attribute['value'];
	            }
	        }       
	          
	      
	    }
	      
	    echo $out;
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


	add_action( 'woocommerce_single_product_summary', 'add_stemcell_descriptions', 15 ); 

	add_action('woocommerce_single_product_summary', 'isa_woo_get_one_pa', 16);

	add_action('init', 'demo_ajax_search');
?>