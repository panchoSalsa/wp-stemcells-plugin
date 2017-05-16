<div class="wrap">
    <h2>Export</h2>
    <p class="description">
        Export products to CSV.
    </p>
    <hr>
    <form id="exportForm" method="post">
        <input type="hidden" name="action" value="woocsv_export">
        <?php echo __('Select a header:', 'woocommerce-csvimport'); ?>
        <select id="header_name" name="header_name">
            <?php foreach ($headers as $header) : ?>
                <option value="<?php echo $header; ?>"><?php echo $header; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="button-primary">Export</button>
    </form>
    <progress style="display: none; width: 100%;" id="progressBar" min="0" max="<?php echo $max; ?>" value="0"/>
</div>


<h2>Previous export files</h2>
<?php foreach ($files as $file) : ?>
    <span>
        <a target="_blank"
           href="<?php echo $upload_dir[ 'baseurl' ] . '/' . basename($file) ?>"><?php echo basename($file) ?></a>

        <a class="delete dashicons dashicons-dismiss" href="#" data-file-name="<?php echo $file; ?>" download>
            <span class=""></span>
        </a>

    </span>
    </br>

<?php endforeach ?>


<script>

    jQuery('a.delete').click(function () {
        var data = {
            type: "POST",
            action: 'delete_export_file',
            filename: jQuery(this).data('file-name')
        };


        jQuery.post(ajaxurl, data, function (response) {
            location.reload();
        });


    });

    function doAjaxExport(data) {

        jQuery.ajax(
            {
                type: "POST",
                url: ajaxurl,
                data: data,
                success: function (data) {

                    console.log(data);

                    if (data == 0) {
                        location.reload();
                        return;
                    }

                    jQuery('#progressBar').val(data);

                    var newFormData = {};
                    newFormData.action = 'woocsv_export';
                    doAjaxExport(newFormData);
                },
                error: function (data) {
                    console.log(data);
                    alert(strings.error);
                }
            });

    }


    jQuery(document).ready(function () {

        jQuery('#exportForm').submit(function (e) {
            var formData = jQuery(this).serialize();
            jQuery('#exportForm').toggle();
            jQuery('#progressBar').toggle();
            doAjaxExport(formData);
            e.preventDefault();
        });
    });
</script>