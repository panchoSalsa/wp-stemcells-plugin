<?php
/*
	** import preview **
*/
?>
<div class="wrap">
    <h2><?php echo __('Import','woocommerce-csvimport'); ?></h2>
    <ul class="subsubsub">
        <li><a href="<?php echo admin_url('admin.php?page=woocsv_import&amp;tab=import');?>" class="current"><?php echo __('Import','woocommerce-csvimport'); ?></a> |</li>
        <li><a href="<?php echo admin_url('admin.php?page=woocsv_import&amp;tab=headers');?>"><?php echo __('Headers','woocommerce-csvimport'); ?></a></li>
    </ul>
    <br class="clear">
    <hr>

    <?php
    global $woocsv_import;

    $filename = $woocsv_import->handle_file_upload($_FILES['csvfile']['tmp_name'], $_FILES['csvfile']['name']);

    if (!$filename) wp_die(__('Could not upload file','woocommerce-csvimport'));

    $lines_from_file = $woocsv_import->get_lines_from_file($filename,0,4, $woocsv_import->get_separator(),true);
    $lines = $lines_from_file['lines'];
    $total_rows = $lines_from_file['total_rows'];


    //set the amount of rows
    $length = count($lines[0]);
    ?>
    <?php if (count($lines[0]) == 1 ) : ?>
        <h2><?php echo __('I think you have the wrong separator', 'woocommerce-csvimport'); ?></h2>
        <p><?php echo __('Please goto the settings page and change your separator!', 'woocommerce-csvimport'); ?></p>
    <?php else: ?>

        <div id="import_preview">
            <h2><?php echo __('Import preview','woocommerce-csvimport'); ?></h2>
            <table class="widefat">
                <thead>
                <tr>
                    <th><?php echo __('Header','woocommerce-csvimport'); ?></th>
                    <th><?php echo __('Row 1','woocommerce-csvimport'); ?></th>
                    <th><?php echo __('Row 2','woocommerce-csvimport'); ?></th>
                    <th><?php echo __('Row 3','woocommerce-csvimport'); ?></th>
                    <th><?php echo __('Row 4','woocommerce-csvimport'); ?></th>
                    <th><?php echo __('Row 5','woocommerce-csvimport'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php for ($i = 0; $i <= $length-1; $i++) : ?>
                    <tr class="<?php echo ($i % 2 == 0)?'':'alt';?>">
                        <td class="row-title"><?php echo (isset($header[$i]))?$header[$i]:''; ?></td>
                        <td><?php if (isset($lines[0][$i])) echo $lines[0][$i];?></td>
                        <td><?php if (isset($lines[1][$i])) echo $lines[1][$i];?></td>
                        <td><?php if (isset($lines[2][$i])) echo $lines[2][$i];?></td>
                        <td><?php if (isset($lines[3][$i])) echo $lines[3][$i];?></td>
                        <td><?php if (isset($lines[4][$i])) echo $lines[4][$i];?></td>
                    </tr>
                <?php endfor;?>
                </tbody>
            </table>
<!--            really import-->
            <form id="start_import"  method="POST">

                <input type="hidden" name="action" value="run_import">
                <input type="hidden" name="filename"        value="<?php echo $filename; ?>" />
                <input type="hidden" name="header_name"     value = "<?php echo $header_name ?>">
                <input type="hidden" name="seperator"       value = "<?php echo $woocsv_import->get_separator(); ?>">
                <input type="hidden" name="total_rows"      value = "<?php echo $total_rows ?>">
                <input type="hidden" name="start_time" value="<?php echo time();?>">

                <?php wp_nonce_field('start_import', 'start_import'); ?>
                <button type="submit" class="button-primary"><?php echo __('start','woocommerce-csvimport'); ?></button>
            </form>
<!--            make batch-->
<!--            <form id="createbatch"  method="POST">-->
<!--                <input type="hidden" name="action"          value="create_batch">-->
<!--                <input type="hidden" name="filename"        value="--><?php //echo $filename; ?><!--" />-->
<!--                <input type="hidden" name="header_name"     value = "--><?php //echo $header_name ?><!--">-->
<!--                <input type="hidden" name="seperator"       value = "--><?php //echo $woocsv_import->get_separator(); ?><!--">-->
<!--                <input type="hidden" name="total_rows"      value = "--><?php //echo $total_rows ?><!--">-->
<!--                <input type="hidden" name="schedule_date"   value = ''>-->
<!--                --><?php //wp_nonce_field('create_batch', 'create_batch'); ?>
<!--                <button type="submit" class="button-primary">--><?php //echo __('create batch','woocommerce-csvimport'); ?><!--</button>-->
<!--            </form>-->
        </div>

        <div id="woocsv_progress" class="postbox" style="display: none; margin:1em 0 0 0;">
            <div class="inside">
                <p id="woocsv_count_rows"></p>
                <progress id="woocsv_import_progress" style="width:100%;" max="<?php echo $total_rows - 1; ?>" value="0"></progress>
                <div id="import_log">
                </div>
            </div>
        </div>
    <?php endif; ?>
