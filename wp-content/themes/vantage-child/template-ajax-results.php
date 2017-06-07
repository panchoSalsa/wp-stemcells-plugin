<?php 
/*
* template-ajax-results.php
* This file should be created in the root of your theme directory
* See http://wpadvancedsearch.com to learn more 
*/
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