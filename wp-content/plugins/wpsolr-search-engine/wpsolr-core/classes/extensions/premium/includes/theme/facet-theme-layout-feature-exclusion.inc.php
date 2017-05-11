<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type
     <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RADIOBOXES; ?>
     <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_CHECKBOXES; ?>
     <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RATING_STARS; ?>
     <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RANGE_REGULAR_CHECKBOXES; ?>
     <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RANGE_IRREGULAR_CHECKBOXES; ?>
     <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_COLOR_PICKER; ?>
     <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RANGE_REGULAR_RADIOBOXES; ?>
     <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_DATE_PICKER; ?>
     <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_DROP_DOWN_LIST; ?>
 "
>
	<?php
	$is_exclusion = isset( $selected_facets_is_exclusions[ $selected_val ] );
	?>

    <div class="wdm_row" style="top-margin:5px;">
        <div class='col_left'>
			<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Do not use other items selections to calculate the items count', true ); ?>
        </div>
        <div class='col_right'>
            <input type='checkbox'
                   name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_IS_EXCLUSION; ?>][<?php echo $selected_val; ?>]'
                   value='1'
				<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ); ?>
				<?php echo checked( $is_exclusion ); ?>
            />
            By default, the facet items count is updated when other facet items are selected. Use this option when you
            want
            to show facet items count as if no selections where made.

        </div>
        <div class="clear"></div>
    </div>
</div>
