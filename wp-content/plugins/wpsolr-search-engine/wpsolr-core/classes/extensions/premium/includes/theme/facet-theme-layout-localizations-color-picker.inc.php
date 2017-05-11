<div style="display:none;"
     class="wpsolr-remove-if-hidden wpsolr_facet_type
         <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_COLOR_PICKER; ?>
">
    <input type='text' class="wpsolr-remove-if-empty wpsolr-color-picker"
           name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_ITEMS_LABEL; ?>][<?php echo $selected_val; ?>][<?php echo $facet_item_label; ?>]'
           value='<?php echo esc_attr( $facet_label ); ?>'
		<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ); ?>
    />
    <p>
		<?php if ( empty( $facet_label ) ) { ?>
            Select a color to associate to "<?php echo $facet_item_label; ?>".
		<?php } else { ?>
            Color "<?php echo $facet_label; ?>" is associated to "<?php echo $facet_item_label; ?>".
		<?php } ?>
    </p>
</div>