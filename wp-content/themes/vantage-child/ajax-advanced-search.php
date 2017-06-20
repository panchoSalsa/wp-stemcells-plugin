<?php

/**
 * Template Name: Ajax Advanced Search Page
 */

	get_header();

	$search = new WP_Advanced_Search('myform');
?>

<div class="row search-section scrollable-container">
   <div id="sidebar" class="large-3 columns scrollable-section">
      <?php $search->the_form(); ?>
   </div>

   <div class="search-results large-9 columns scrollable-section">
   		 <?php 
   		 	//$wp_query = $search->query();
   		 	// source=https://stackoverflow.com/questions/3034530/php-print-all-properties-of-an-object
   		 	// var_dump($search);
   		 	// echo "<h3>" . $wp_query->found_posts . " results found</h3>" 
   		 ?>
         <div id="wpas-results"></div> <!-- This is where our results will be loaded -->
   </div>
</div>

<?php 
	get_footer(); 
?>
