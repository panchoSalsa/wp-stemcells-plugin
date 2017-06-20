<?php 
/*
* template-ajax-results.php
* This file should be created in the root of your theme directory
* See http://wpadvancedsearch.com to learn more 
*/



  // source=https://isabelcastillo.com/woocommerce-cart-icon-count-theme-header
  /**
   * Add Cart icon and count to header if WC is active
   */
  function my_wc_cart_count() {

      // ChromePhp::log('my_wc_cart_count()');
   
      if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
   
          $count = WC()->cart->cart_contents_count;
          ?><a class="cart-contents" href="<?php echo WC()->cart->get_cart_url(); ?>" title="<?php _e( 'View your shopping cart' ); ?>"><?php
          if ( $count > 0 ) {
              ?>
              <span class="cart-contents-count"><?php echo esc_html( $count ); ?></span>
              <?php
          }
                  ?></a><?php
      }
   
  }

  // display cart
  add_action( 'vantage-child_header_top', 'my_wc_cart_count' );
  do_action( 'vantage-child_header_top' );

?>

<?php 
   // source=http://wpadvancedsearch.com/docs/methods/
   // get product count
   echo "<h3>" . $wp_query->found_posts . " results found</h3>" 
?>


<?php if (have_posts()) : ?>

   <?php
   // I don't want the sorting anymore
   //do_action('woocommerce_before_shop_loop');
   ?>

   <ul class = "products" >
       <?php while (have_posts()) : the_post(); ?>


           <?php wc_get_template_part('content', 'product'); ?>

       <?php endwhile; // end of the loop.   ?>
   </ul>

   <?php
   /*  woocommerce pagination  */
   // do_action('woocommerce_after_shop_loop');
   ?>

<?php elseif (!woocommerce_product_subcategories(array('before' => woocommerce_product_loop_start(false), 'after' => woocommerce_product_loop_end(false)))) : ?>

   <?php woocommerce_get_template('loop/no-products-found.php'); ?>

<?php endif; ?>