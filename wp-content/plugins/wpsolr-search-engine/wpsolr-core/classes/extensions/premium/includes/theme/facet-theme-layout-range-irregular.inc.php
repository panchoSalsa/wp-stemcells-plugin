<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RANGE_IRREGULAR_CHECKBOXES; ?> <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RANGE_IRREGULAR_RADIOBOXES; ?>">

    <div class='col_left' style="font-weight: normal">
        One range per row, with 3 columns separated by '|'.</br></br>
        0|9|Range %1$d - %2$d (%3$d)</br>
        10|20|Range 10 TO 20 (%3$d)</br>
        21|100|Range %s => %s (%3$d)</br>
        101|*|More than 100 (%3$d)</br>
    </div>
    <div class='col_right'>
				<textarea type='text' rows="10" style="width:98%"
                          name='wdm_solr_facet_data[<?php echo WPSOLR_Option::FACET_FIELD_CUSTOM_RANGES; ?>][<?php echo $selected_val; ?>]'
                ><?php echo esc_attr( WPSOLR_Global::getOption()->get_facets_range_irregular_ranges( $selected_val ) ); ?></textarea>

    </div>

</div>
