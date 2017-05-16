<?php

/**
 *Import
 **/
global $woocsv_import;
$headers = array_keys($woocsv_import->headers);
?>
    <!-- This file should primarily consist of HTML with a little bit of PHP. -->
    <div class="wrap">
        <h2><?php echo __('Import', 'woocommerce-csvimport'); ?></h2>
        <ul class="subsubsub">
            <li><a href="<?php echo admin_url('admin.php?page=woocsv_import&amp;tab=import'); ?>" class="current"><?php echo __('Import', 'woocommerce-csvimport'); ?></a> |</li>
            <li><a href="<?php echo admin_url('admin.php?page=woocsv_import&amp;tab=headers'); ?>"><?php echo __('Headers', 'woocommerce-csvimport'); ?></a></li>
        </ul>
        <br class="clear">
        <hr>
        <table class="form-table">
            <form name="upload_header_form" id="upload_header_form" enctype="multipart/form-data" method="POST">
                <tr>
                    <th><?php echo __('Select a file:', 'woocommerce-csvimport'); ?></th>
                    <td><input id="csvfile" name="csvfile" type="file" accept="text/csv"/></td>
                </tr>
                <tr>
                    <th><?php echo __('Select a header:', 'woocommerce-csvimport'); ?></th>
                    <td>
                        <select id="header_name" name="header_name">
                            <?php foreach ($headers as $header) : ?>
                                <option value="<?php echo $header; ?>"><?php echo $header; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>
                        <button type="submit" class="button-primary"><?php echo __('load', 'woocommerce-csvimport'); ?></button>
                    </td>

                </tr>

                <?php wp_nonce_field('upload_import_file', 'upload_import_file'); ?>
                <input type="text" hidden name="action" value="start_import_preview">
            </form>
        </table>

        <h2><?php echo __('Previous Batches', 'woocommerce-csvimport'); ?></h2>
        <hr>
        <?php $batches = get_option('woocsv_batches'); ?>
        <?php if ($batches) : ?>
        <table class="widefat">
            <thead>
            <th><?php echo __('Filename', 'woocommerce-csvimport'); ?></th>
            <th><?php echo __('status', 'woocommerce-csvimport'); ?></th>
            <th><?php echo __('Rows', 'woocommerce-csvimport'); ?></th>
            <th><?php echo __('Total rows', 'woocommerce-csvimport'); ?></th>
            <th><?php echo __('Start time', 'woocommerce-csvimport'); ?></th>
            <th><?php echo __('End Time', 'woocommerce-csvimport'); ?></th>
            <th>Log file</th>

            <th>
                <button class="button-primary delete-all" data-batch="all"><?php echo __('delete all', 'woocommerce-csvimport'); ?></button>
            </th>
            </thead>
            <tbody>
            <?php foreach ($batches as $batch_code => $batch) : ?>

                <tr id="<?php echo $batch_code; ?>">
                    <td><?php echo basename($batch[ 'filename' ]); ?></td>
                    <td><?php echo $batch[ 'status' ]; ?></td>
                    <td><?php echo $batch[ 'row' ]; ?></td>
                    <td><?php echo $batch[ 'total_rows' ]; ?></td>
                    <td><?php echo (isset($batch[ 'start_date' ])) ? aem_helper_date($batch[ 'start_date' ]) : ''; ?></td>
                    <td><?php echo (isset($batch[ 'end_date' ])) ? aem_helper_date($batch[ 'end_date' ]) : ''; ?></td>
                    <td><a href="<?php echo $woocsv_import->upload_dir[ 'baseurl' ] . '/csvimport/' . $batch_code . '.log' ?>" target="_blank"> <?php echo __('Log file',
                                'woocommerce-csvimport'); ?> </a></td>
                    <td>
                        <button class="button-primary delete" data-batch-code="<?php echo $batch_code; ?>"><?php echo __('delete', 'woocommerce-csvimport'); ?></button>
                        <!-- <button class="button-primary start-batch" data-batch-code="<?php echo $batch_code; ?>"><?php echo __('start', 'woocommerce-csvimport'); ?></button> -->
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>


    <script>

        jQuery('button.start-batch').click(function (response) {
            var data = {
                action: 'start_batch',
                batch: jQuery(this).data('batch_code')
            };

            jQuery.post(ajaxurl, data, function (response) {
                console.log(response);
                location.reload(true);
            });
        });

        jQuery('button.delete-all').click(function () {

            var data = {
                action: 'delete_batch_all'
            };

            jQuery.post(ajaxurl, data, function (response) {
                jQuery(('table.widefat')).remove();
            });
        });

        jQuery('button.delete').click(function () {

            var data = {
                action: 'delete_batch',
                batch_code: jQuery(this).data('batch-code')
            };
            console.log(data);

            jQuery.post(ajaxurl, data, function (response) {
                console.log(response);
                jQuery(this).closest('.tr').remove();
                if (response) {
                    jQuery("table tr[id='" + response + "']").remove();
                }

            });
        });
    </script>
<?php else : ?>
    <p><?php echo __('No batches yet', 'woocommerce-csvimport'); ?></p>
<?php endif; ?>