<?php

/**
 *Import
 **/
global $woocsv_import;
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <h2><?php echo __('Headers','woocommerce-csvimport'); ?></h2>
    <ul class="subsubsub">
        <li><a href="<?php echo admin_url('admin.php?page=woocsv_import&amp;tab=import');?>"><?php echo __('Import','woocommerce-csvimport'); ?></a> |</li>
        <li><a href="<?php echo admin_url('admin.php?page=woocsv_import&amp;tab=headers');?>" class="current"><?php echo __('Headers','woocommerce-csvimport'); ?></a></li>
    </ul>
    <br class="clear">
    <hr>
    <p class="description">
        <?php echo __('Headers are the mappings between your CSV columns and the actual fields of woocommerce. It is essential that you make a header before you import. You can make multiple headers and use them for different CSV files or for the same. Example: one header to import new products and one to only merge prices and stock.','woocommerce-csvimport'); ?>
        <?php echo __('Upload a CSV file to create a new header.','woocommerce-csvimport'); ?>
    </p>
    <hr>
    <form name="upload_header_form" id="upload_header_form" enctype="multipart/form-data" method="POST">
            <input id="csvfile" name="csvfile" type="file" accept="text/csv" />
            <input type="text" hidden name="action" value="start_header_preview">
            <button type="submit" class="button-primary"><?php echo __('load','woocommerce-csvimport'); ?></button>
            <?php wp_nonce_field('upload_header_file', 'upload_header_file'); ?>
    </form>
    <hr>
    <table id="headertable" class="widefat">
        <thead>
        <tr>
            <th><?php echo __('name','woocommerce-csvimport'); ?></th>
            <th><?php echo __('header','woocommerce-csvimport'); ?></th>
            <!--
                <th>&nbsp;</th>
            -->
            <th>&nbsp;</th>
        </tr>
        </thead>
        <tbody>
        <?php $i=0; ?>
        <?php foreach ($woocsv_import->headers as $key => $value) : ?>
            <tr class="<?php echo ($i % 2 == 0)?'':'alt';?>" id="<?php echo $key;?>">
                <td><?php echo $key; ?></td>
                <td><?php echo implode($woocsv_import->get_separator().' ', $value);?></td>
                <!--
			<td><span class="dashicons dashicons-arrow-up-alt2 up" data-header-name="<?php echo $key; ?>"></span></td>
			<td><span class="dashicons dashicons-arrow-down-alt2 down" data-header-name="<?php echo $key; ?>"></span></td>
			-->
                <td><button class="button-primary delete" data-header-name="<?php echo $key; ?>"><?php echo __('delete','woocommerce-csvimport'); ?></button></td>
            </tr>
            <?php $i++; ?>
        <?php endforeach;?>

        </tbody>
    </table>
</div>

<script>
//
//    //down
//    jQuery ('td span.dashicons.down').click(function() {
//
//    });
//
//    //up
//    jQuery ('td span.dashicons.up').click(function() {
//
//    });

    jQuery('td button.delete').click(function() {

        var data = {
            action: 'delete_header',
            header_name: jQuery(this).data('header-name')
        };

        jQuery.post(ajaxurl, data, function(response) {

            jQuery(this).closest('.tr').remove();
            if (response) {
                jQuery("table#headertable tr[id='"+response+"']").remove();
            }

        });
    });
</script>