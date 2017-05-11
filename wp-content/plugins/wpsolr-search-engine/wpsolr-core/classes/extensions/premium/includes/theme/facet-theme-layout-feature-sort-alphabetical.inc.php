<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type
     <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_CHECKBOXES; ?>
     <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RADIOBOXES; ?>
     <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_COLOR_PICKER; ?>
 "
>
	<?php
	$is_sort_alpha = isset( $selected_facets_sorts[ $selected_val ] );
	?>

    <div class="wdm_row" style="top-margin:5px;">
        <div class='col_left'>
			<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Sort content alphabetically', true ); ?>
        </div>
        <div class='col_right'>
            <input type='checkbox'
                   name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_SORT; ?>][<?php echo $selected_val; ?>]'
                   value='1'
				<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ); ?>
				<?php echo checked( $is_sort_alpha ); ?>
            />
            Facet items are sorted by count. Select this option if you want to
            order them
            alphabetically.

        </div>
        <div class="clear"></div>
    </div>
</div>