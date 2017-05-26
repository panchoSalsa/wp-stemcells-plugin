<?php 
/*
* template-ajax-results.php
* This file should be created in the root of your theme directory
* See http://wpadvancedsearch.com to learn more 
*/

// if ( have_posts() ) :             
//    while ( have_posts() ) : the_post(); 
//    $post_type = get_post_type_object($post->post_type);
   ?>
<!--       <article>
         <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
         <p class="info"><strong>Post Type:</strong> <?php echo $post_type->labels->singular_name; ?> &nbsp;&nbsp; <strong>Date added:</strong> <?php the_time('F j, Y'); ?></p>
         <?php the_excerpt(); ?>
      </article> -->

<?php 
//    endwhile; 
// else :
//    echo '<p>Sorry, no results matched your search.</p>';
// endif; 

// wp_reset_query();
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