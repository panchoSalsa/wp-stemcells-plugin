<?php
global $is_facet_js_loaded;

$current_layout_id       = isset( $selected_facets_layouts[ $selected_val ] ) ? $selected_facets_layouts[ $selected_val ] : WPSOLR_Option::FACETS_LAYOUT_ID_CHECKBOXES;
$facets_layout_available = apply_filters( WpSolrFilters::WPSOLR_FILTER_GET_FIELD_TYPE_LAYOUTS,
	[
		WPSOLR_Option::FACETS_LAYOUT_ID_CHECKBOXES => [
			'label'          => 'Check boxes',
			'facet_type'     => WPSOLR_Option::OPTION_FACET_FACETS_TYPE_FIELD,
			'types'          => [], // All field types ok,
			'enabled'        => true,
			'multiselection' => true,
		],
	],
	WpSolrSchema::get_custom_field_solr_type( $selected_val ) );
?>

<?php if ( ! isset( $is_facet_js_loaded ) ) { ?>
    <script>
        jQuery(document).ready(function () {

            // Initiate color pickers
            jQuery('.wpsolr-color-picker').wpColorPicker();

            function display_facet_types(layout_element) {
                layout_element.parent().find(".wpsolr_facet_type").hide(); // hide all facet type sections
                layout_element.parent().find(".wpsolr_facet_type." + layout_element.val()).show(); // show facet section type of the selected layout
            }

            // Display facet sections depending on the select layout facet type
            jQuery(".wpsolr_layout_select").each(function () {
                display_facet_types(jQuery(this));
            });

            // Change facet layout selection
            jQuery(".wpsolr_layout_select").on("change", function (event) {
                display_facet_types(jQuery(this));
            });

        });
    </script>

	<?php
	// Load script once only
	$is_facet_js_loaded = true;
}
?>

<div class="wdm_row" style="top-margin:5px;">
    <div class='col_left'>
		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_THEME, 'Layout', true ); ?>
    </div>
    <div class='col_right'>
        <select name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_LAYOUT; ?>][<?php echo $selected_val; ?>]'
                class="wpsolr-remove-if-empty wpsolr_layout_select"
                data-wpsolr-empty-value="<?php echo WPSOLR_Option::FACETS_LAYOUT_ID_CHECKBOXES; ?>">
			<?php foreach ( $facets_layout_available as $layout_id => $layout ) { ?>
                <option value="<?php echo $layout_id; ?>" <?php echo selected( $current_layout_id, $layout_id ); ?> <?php echo disabled( ! $layout['enabled'] ); ?>><?php echo $layout['label']; ?></option>
			<?php } ?>
        </select>
        Choose a layout to display your facet.


		<?php include 'facet-theme-layout-feature-grid.inc.php'; ?>
		<?php include 'facet-theme-layout-feature-hierarchy.inc.php'; ?>
		<?php include 'facet-theme-layout-feature-or.inc.php'; ?>
		<?php include 'facet-theme-layout-feature-sort-alphabetical.inc.php'; ?>
		<?php include 'facet-theme-layout-feature-exclusion.inc.php'; ?>
		<?php include 'facet-theme-layout-range-regular.inc.php'; ?>
		<?php include 'facet-theme-layout-range-irregular.inc.php'; ?>
		<?php include 'facet-theme-layout-color-picker.inc.php'; ?>

		<?php include 'facet-theme-layout-localizations.inc.php'; ?>

    </div>
    <div class="clear"></div>
</div>
