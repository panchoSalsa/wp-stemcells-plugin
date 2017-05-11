<?php

$facet_name_standard = ( 'categories' === $dis_text ) ? 'category' : ( 'tags' === $dis_text ? 'post_tag' : $dis_text );

// Let others a chance to tell us what facet items are.
$facet_items = apply_filters( WpSolrFilters::WPSOLR_FILTER_FACET_ITEMS, [], $facet_name_standard, $selected_val );

// Well, it's up to us then.
if ( empty( $facet_items ) ) {
	if ( taxonomy_exists( $facet_name_standard ) ) {
		$facet_items = get_terms( array( 'taxonomy' => $facet_name_standard, 'fields' => 'names', 'number' => '50' ) );

	} elseif ( 'type' === $selected_val ) {

		$post_types  = get_post_types();
		$facet_items = array( 'attachment' );
		foreach ( $post_types as $post_type ) {
			if ( 'attachment' !== $post_type && 'revision' !== $post_type && 'nav_menu_item' !== $post_type ) {
				array_push( $facet_items, $post_type );
			}
		}
	} else {
		// Custom fields
		global $wpdb;

		$facet_items = $wpdb->get_col( $wpdb->prepare( "
                              SELECT distinct meta_value
                                  FROM {$wpdb->prefix}postmeta
                                  WHERE meta_key = %s
                                  ORDER BY meta_value ASC
                                  LIMIT 50
                                  ", $dis_text )
		);

	}
}

?>

<?php if ( ! empty( $facet_items ) ) {

	$button_open_localizations = empty( $facets_layout_available[ $current_layout_id ]['button_localize_label'] ) ? 'Override each item label' : $facets_layout_available[ $current_layout_id ]['button_localize_label'];
	?>

    <input name="collapser" type="button" class="button-primary wpsolr_collapser"
           value="<?php echo $button_open_localizations; ?>">

    <div class="wpsolr_collapsed">

		<?php foreach ( $facet_items as $facet_item_label ) {
			if ( ! empty( $facet_item_label ) ) {
				?>

                <div class="wdm_row" style="top-margin:5px;">
                    <div class='col_left'>
						<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, sprintf( '%s', ucfirst( $facet_item_label ) ), true ); ?>
                    </div>
					<?php
					$facet_label = ( ! empty( $selected_facets_item_labels[ $selected_val ] ) && ! empty( $selected_facets_item_labels[ $selected_val ][ $facet_item_label ] ) )
						? $selected_facets_item_labels[ $selected_val ][ $facet_item_label ] : '';
					?>
                    <div class='col_right'>
						<?php include 'facet-theme-layout-localizations-field.inc.php'; ?>
						<?php include 'facet-theme-layout-localizations-color-picker.inc.php'; ?>
                    </div>
                    <div class="clear"></div>
                </div>

                <div class="wdm_row" style="top-margin:5px;">
                    <div class='col_left'>
                    </div>
					<?php
					$is_default = ( ! empty( $selected_facets_item_is_default[ $selected_val ] ) && ! empty( $selected_facets_item_is_default[ $selected_val ][ $facet_item_label ] )
					                && ! empty( $selected_facets_item_is_default[ $selected_val ][ $facet_item_label ] ) );
					?>
                    <div class='col_right'>
                        <input type='checkbox' class="wpsolr-remove-if-empty"
                               name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_ITEMS_IS_DEFAULT; ?>][<?php echo $selected_val; ?>][<?php echo $facet_item_label; ?>]'
                               value='1'
							<?php echo checked( $is_default ); ?>
							<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ); ?>
                        />
                        Pre-select "<?php echo $facet_item_label; ?>".

                    </div>
                    <div class="clear"></div>
                </div>
			<?php }
		} ?>
    </div>
<?php } ?>
