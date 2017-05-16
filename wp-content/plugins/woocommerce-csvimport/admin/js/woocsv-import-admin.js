'use strict';

function doAjaxImport(formData)
{
	jQuery.ajax(
	{
		type: "POST",
		url: ajaxurl,
		data: formData,
		success: function(data)
		{ 
			try {
				var newFormData = JSON.parse(data);
                console.log(newFormData);
			} 
			catch (err) {
                console.log (data);
				jQuery('#import_log').prepend('<div>'+ data +'</div>');
                jQuery('#import_log').prepend('<p><h2>Something went wrong. The stack trace is printed below</h2></p>');
			}

			if (newFormData.done !=1)
			{
                jQuery('#woocsv_import_progress').val(newFormData.batch.row);
                jQuery('#woocsv_count_rows').html( 'row: ' + newFormData.batch.row + ' / ' + newFormData.batch.total_rows + '  |  Processing ' + newFormData.batch.block_size + ' rows simultaneously');

                newFormData.action = 'run_import';
                doAjaxImport(newFormData);
			}
			else
			{	
				if (newFormData.log && newFormData.log.length > 0) {
					jQuery.each(newFormData.log, function( index, value ) {
						jQuery('#import_log').prepend('<p> '+value+' </p>');
						
						jQuery('#woocsv_import_progress').val(newFormData.batch.row);
					});
				}

                location.reload(true);
			}
		},
		error: function(data)
		{
			console.log(data);
			alert(strings.error);
		}
	});
}

jQuery(document).ready(function()
{
	jQuery('#start_import').submit(function(e)
	{
		jQuery('html, body').animate(
		{
			scrollTop: 0
		}, 'slow');

        jQuery('#woocsv_progress').show();
		jQuery('#import_preview').slideUp();

		var formData = jQuery(this).serialize();
		doAjaxImport(formData);
		e.preventDefault();
	});
});
