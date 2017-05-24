<?php 
/*
* template-ajax-results.php
* This file should be created in the root of your theme directory
* See http://wpadvancedsearch.com to learn more 
*/
if ( have_posts() ) :             
   while ( have_posts() ) : the_post(); 
   $post_type = get_post_type_object($post->post_type);
   ?>
      <article>
         <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
         <p class="info"><strong>Post Type:</strong> <?php echo $post_type->labels->singular_name; ?> &nbsp;&nbsp; <strong>Date added:</strong> <?php the_time('F j, Y'); ?></p>
         <?php the_excerpt(); ?>
      </article>

   <?php 
   endwhile; 
else :
   echo '<p>Sorry, no results matched your search.</p>';
endif; 
wp_reset_query();
?>