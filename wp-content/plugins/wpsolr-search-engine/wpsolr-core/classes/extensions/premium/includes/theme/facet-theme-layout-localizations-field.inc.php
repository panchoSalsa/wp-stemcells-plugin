<div style="display:none;"
     class="wpsolr-remove-if-hidden wpsolr_facet_type
         <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_DROP_DOWN_LIST; ?>
         <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RANGE_REGULAR_RADIOBOXES; ?>
         <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RANGE_IRREGULAR_CHECKBOXES; ?>
         <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RANGE_REGULAR_CHECKBOXES; ?>
         <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_CHECKBOXES; ?>
         <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RADIOBOXES; ?>
         <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RANGE_IRREGULAR_RADIOBOXES; ?>
">
    <input type='text' class="wpsolr-remove-if-empty"
           name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_ITEMS_LABEL; ?>][<?php echo $selected_val; ?>][<?php echo $facet_item_label; ?>]'
           value='<?php echo esc_attr( $facet_label ); ?>'
		<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ); ?>
    />
    <p>
        Will be shown on the front-end (and
        translated in WPML/POLYLANG string
        modules).
        Leave empty if you wish to use the
        current facet
        value "<?php echo $facet_item_label; ?>".
    </p>
</div>