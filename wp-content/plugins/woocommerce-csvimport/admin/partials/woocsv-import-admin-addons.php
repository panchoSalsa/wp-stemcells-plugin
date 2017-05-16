<?php
/**
 * Add-ons page
 *
 */
?>

<div class="wrap">
    <h2><?php echo __('Add-ons','woocommerce-csvimport'); ?></h2>
    <hr>
    <p class="description">
	 	<?php echo sprintf(__('You can find the latest version of the add-ons in your %s account page %s .','woocommerce-csvimport'),'<a href="https://allaerd.org/my-account/" target= "blank">','</a>'); ?>
	 </p>
    
<?php
	global $woocsv_import;
	
		 if ( isset ( $woocsv_import->addons ) ) {
			 echo "<h3><?php echo __('Installed Add-on's','woocommerce-csvimport'); ?></h3>";
			 echo '<table class="widefat"><thead><tr><th class="row-title">'.__('Add-on','woocommerce-csvimport') .'</th><th>'. __('Version','woocommerce-csvimport') .'</th></tr></thead><tbody>';
			 foreach ( $woocsv_import->addons as $addon  ) {
				 ?>
				 <tr>
				 	<td><?php echo $addon->name;?></td>
				 	<td><?php echo $addon->version;?></td> 	
				 <?php 				 
/* will hold future update stuff
				 $url = 'http://localhost/api/wc-api/check_for_updates';
				 $response = wp_remote_post( $woocsv_import->api_url, 
				 	array('body' => array( 'remote_slug' => $addon->remote_slug, 'version'=> $addon->version ))
				 );
				 
				 //if error stop
				 if ( is_wp_error($response) ) {
					 echo '<td&nbsp;</td></tr>';
				 } else {
 					 $response_body = json_decode( $response['body'] );
					 if ( isset ( $response_body->version ) && version_compare($response_body->version,$addon->version) === 1) {
						?>
						<td>version <?php echo $response_body->version;?> available. Please login into your account on <a href="http://allaerd.org/my-account">allaerd.org</a> to download the latest version</td>
						<?php	
					 }
					 echo '</tr>';
				 }
*/				 
				echo '</tr>';
			 }
			 echo '<tbody></table>';
		 }
?>

<h3><?php echo __('Available add-ons','woocommerce-csvimport'); ?></h3>
<ul class="addons">
			<li class="addon">
				<a href="http://allaerd.org/shop/get-them-all">
					<h3><?php echo __('Get Them (almost) all','woocommerce-csvimport'); ?></h3>
					<p>
						<?php echo __('You get a discount for the add-ons in the bundle and updates are included for 360 days!','woocommerce-csvimport'); ?> 
					</p>
				</a>
			</li>
			<li class="addon">
				<a href="https://allaerd.org/shop/import-taxonomies/">
					<h3><?php echo __('Taxonomies','woocommerce-csvimport'); ?></h3>
					<p>
						<?php echo __('With this add-on you can import additional taxonomies into Woocommerce. Custom taxonomies are used a lot in themes or extensions to add functionality to woocommerce. Brands is a custom taxonomy that might sound familiar.','woocommerce-csvimport'); ?>
 
					</p>
				</a>
			</li>
			<li class="addon">
				<a href="http://allaerd.org/shop/woocommerce-import-variable-products">
					<h3><?php echo __('Variable products','woocommerce-csvimport'); ?></h3>
					<p>
						<?php echo __('Import and manage your variable products and set up attributes used for variations. Variable products are products like t-shirts, you have the min different colours and sizes.','woocommerce-csvimport'); ?>
					</p>
				</a>
			</li>
			<li class="addon">
				<a href="http://allaerd.org/shop/import-downloadable-external-grouped-products">
					<h3><?php echo __('Downloadable, external, grouped products','woocommerce-csvimport'); ?></h3>
					<p>
						<?php echo __('With this add-on you can import Downloadable products, Grouped Products, External/Affiliate products and some additional fields like cross-sells and up-sells.','woocommerce-csvimport'); ?>
					</p>
				</a>
			</li>
			<li class="addon">
				<a href="http://allaerd.org/shop/woocommerce-import-attributes">
					<h3><?php echo __('Attributes','woocommerce-csvimport'); ?></h3>
					<p>
						<?php echo __('Import global attributes into Woocommerce. Import one or multiple. Control there visibility and attach multiple at once to your product.','woocommerce-csvimport'); ?>
					</p>
				</a>
			</li>
			<li class="addon">
				<a href="http://allaerd.org/shop/woocommerce-import-custom-fields">
					<h3><?php echo __('Custom Fields','woocommerce-csvimport'); ?></h3>
					<p>
						<?php echo __('Custom fields are used to store all kind of information. Lots of other extensions and other plugins use it to store their data. Use this add-on to fill all that data using your CSV.','woocommerce-csvimport'); ?>
					</p>
				</a>
			</li>
		</ul>
</div>