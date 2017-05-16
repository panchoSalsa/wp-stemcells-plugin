<?php
/**
 	** Documentation page
**/
 
?>
<div class="wrap">
	<h2><?php echo __('Documentation','woocommerce-csvimport'); ?></h2>
	<hr>
	<h2><?php echo __('Woocommerce CSV importer','woocommerce-csvimport'); ?></h2>
	<?php echo sprintf(__('Documentation is available in the %s knowledgebase %s. If you still have problems, feel free to drop me a mail at %s','woocommerce-csvimport'),'<a href="https://allaerd.org/knowledgebase">','</a>','<a href-"mailto:support@allaerd.org">support@allaerd.org</a>'); ?>	
	<h3><?php echo __('Common questions','woocommerce-csvimport'); ?></h3>
	<h4><?php echo __('Text is is cut during import or when i use special characters.','woocommerce-csvimport'); ?></h4>
 	<p class="description">
	 	<?php echo __('Make sure you encode you file in UTF-8.','woocommerce-csvimport'); ?>
	 </p>
 	<h4>My images imported double</h4>
 	<p class="description">
	 	<?php echo __('When you import with images with url\'s the images are imported everytime','woocommerce-csvimport'); ?>
	 </p>
	
	<h3><?php echo __('What fields are available','woocommerce-csvimport'); ?></h3>
		<h4>SKU</h4>
			<p class="description"><?php echo __('This is the unique identifier of your product. If a SKU is present it will be used to update!','woocommerce-csvimport'); ?></p>
		<h4><?php echo __('ID','woocommerce-csvimport'); ?></h4>
			<p class="description"><?php echo __('This is the unique identifier of your product in the database. If a ID is present it will be used to update! Only use this if you know are really sure, best use the sku!','woocommerce-csvimport'); ?></p>
		<h4><?php echo __('post_status','woocommerce-csvimport'); ?></h4> 
	 		<p class="description"><?php echo sprintf(__('%s<code>%s</code>','woocommerce-csvimport'),
		 			'The status of you product, values:',
		 			'publish, pending, draft, private, trash
		 			'); ?></p>
	 	<h4><?php echo __('post_title (mandatory)','woocommerce-csvimport'); ?></h4>
	 		<p class="description"><?php echo __('The title of your product','woocommerce-csvimport'); ?></p>
	 	<h4><?php echo __('post_content','woocommerce-csvimport'); ?></h4>
	 		<p class="description"><?php echo __('The description of your product','woocommerce-csvimport'); ?></p>
	 	<h4><?php echo __('post_excerpt','woocommerce-csvimport'); ?></h4>
	 		<p class="description"><?php echo __('The short description of your product','woocommerce-csvimport'); ?></p>
		<h4><?php echo __('category','woocommerce-csvimport'); ?></h4>
			<p class="description"><?php echo sprintf(__('The category of your product (or multiple). You can have multiple categories by separating them with a pipe %s. You can make subcategories with -> <code>cat1->subcat1</code> and you can mix them all <code>cat1->subcat1|cat2|cat3->subcat2->subsubcat1</code>.','woocommerce-csvimport'),
				'<code>cat1|cat2</code>',
				''
				); ?>
				</p>
		<h4><?php echo __('tags','woocommerce-csvimport'); ?></h4>
			<p class="description"><?php echo __('You can add tags or multiple with the pipe separator.','woocommerce-csvimport'); ?><code>tag1|tag2|tag3</code></p>
		<h4><?php echo __('manage_stock','woocommerce-csvimport'); ?></h4>
			<p class="description"><?php echo __('Enable or disable management of stock. Values:','woocommerce-csvimport'); ?><code>yes, no</code></p>
		<h4><?php echo __('stock_status','woocommerce-csvimport'); ?></h4>
			<p class="description"><?php echo __('The stock status of your product. You have to set it yourself if you want. Values:','woocommerce-csvimport'); ?><code>instock, outofstock</code></p>
		<h4><?php echo __('backorders','woocommerce-csvimport'); ?></h4>
			<p class="description"><?php echo __('If you want tot allow backorders for your product. Values:','woocommerce-csvimport'); ?><code>yes, no, notify</code></p>
		<h4><?php echo __('stock','woocommerce-csvimport'); ?></h4>
			<p class="description"><?php echo __('The actual stock of your product.','woocommerce-csvimport'); ?></p>
		<h4><?php echo __('regular_price, sale_price','woocommerce-csvimport'); ?></h4>
			<p class="description"><?php echo __('If you have a normal price. You can fill in regular price. If your product is on sale, you should fill in regular price and sale price. The sale price should be lower than the regular price.','woocommerce-csvimport'); ?></p>
		<h4><?php echo __('weight, length, width, height','woocommerce-csvimport'); ?></h4>
			<p class="description"><?php echo __('You can fill in the dimensions and weight of your product using these fields.','woocommerce-csvimport'); ?></p>
		<h4><?php echo __('tax_status','woocommerce-csvimport'); ?></h4>
			<p class="description"><?php echo __('The status of your product tax. Values:','woocommerce-csvimport'); ?><code>taxable, shipping, none</code></p>
		<h4><?php echo __('tax_class','woocommerce-csvimport'); ?></h4>
			<p class="description"><?php echo __('If you made any additional tax classes you can use this fields.','woocommerce-csvimport'); ?></p>
		<h4><?php echo __('visibility','woocommerce-csvimport'); ?></h4>
			<p class="description"><?php echo __('Determines if your product is visible or not and where. Values:','woocommerce-csvimport'); ?><code>visible, catalog, search, hidden</code></p>
		<h4><?php echo __('featured','woocommerce-csvimport'); ?></h4>
			<p class="description"><?php echo __('Determines if your product should be visible in any features lists or widgets. Values:','woocommerce-csvimport'); ?><code>yes, no</code></p>
		<h4><?php echo __('featured_image','woocommerce-csvimport'); ?></h4>
		<p class="description"><?php echo __('You can add the featured image by adding the URL to it or the filename. If you enter the filename, you MUST upload it in advance with the media manager of wordpress','woocommerce-csvimport'); ?></p>
		<h4><?php echo __('product_gallery','woocommerce-csvimport'); ?></h4>
		<p class="description"><?php echo __('You can add multiple images to your product gallery. Add them in a pipe separated list. <code>image1.jpg|image2.jpg</code> You can put in valid URL\'s or filenames.','woocommerce-csvimport'); ?></p>
		<h4><?php echo __('shipping_class','woocommerce-csvimport'); ?></h4>
		<p class="description"><?php echo __('You can add your custom shipping class. If the class does not exists it will be added.','woocommerce-csvimport'); ?></p>
		<h4><?php echo __('comment_status','woocommerce-csvimport'); ?></h4>
		<p class="description"><?php echo __('You can set the enable/disable review with the following values:','woocommerce-csvimport'); ?><code>open, closed</code></p>
		<h4><?php echo __('ping_status','woocommerce-csvimport'); ?></h4>
		<p class="description"><?php echo __('You can enable the ping status the following values:','woocommerce-csvimport'); ?><code>open, closed</code></p>
		<h4><?php echo __('menu_order','woocommerce-csvimport'); ?></h4>
		<p class="description"><?php echo __('you can change the menu order with an given number. the default is','woocommerce-csvimport'); ?><code>0</code></p>
		<h4><?php echo __('change_stock','woocommerce-csvimport'); ?></h4>
		<p class="description">
		<?php echo __('Here you can enter the stock adjustment. It will be used to calculate the stock of an existing product. It does not work for new products. If you want to decrease the stock by 2 you enter','woocommerce-csvimport'); ?><code>-2</code>.
		</p>
		<h4><?php echo __('post_author','woocommerce-csvimport'); ?></h4>
		<p class="description">
			<?php echo sprintf(__('%s <code> % s</code> %s','woocommerce-csvimport'),
				'You can attach an author to a product by matching him by',
				'id, slug, email, login.',
				'ID is the actual id in the users table, slug is the nice name of the user, email is the email adres of the user and login is the login name.'
				
				
				); ?> 
		</p>
		<?php do_action ('woocsv_documentation')?>
</div>
