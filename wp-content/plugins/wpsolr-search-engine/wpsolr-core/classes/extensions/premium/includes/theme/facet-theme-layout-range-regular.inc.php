<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RANGE_REGULAR_CHECKBOXES; ?> <?php echo WPSOLR_Option::FACETS_LAYOUT_ID_RANGE_REGULAR_RADIOBOXES; ?>">

    <div class='col_left'>
        Range start
    </div>
    <div class='col_right'>
        <input type='text'
               name='wdm_solr_facet_data[<?php echo WPSOLR_Option::FACET_FIELD_RANGE_START; ?>][<?php echo $selected_val; ?>]'
               placeholder='<?php echo esc_attr( WPSOLR_Global::getOption()->get_facets_range_regular_start( $selected_val ) ); ?>'
               value='<?php echo esc_attr( WPSOLR_Global::getOption()->get_facets_range_regular_start( $selected_val, '' ) ); ?>'/>
    </div>
    <div class='col_left'>
        Range end
    </div>
    <div class='col_right'>
        <input type='text'
               name='wdm_solr_facet_data[<?php echo WPSOLR_Option::FACET_FIELD_RANGE_END; ?>][<?php echo $selected_val; ?>]'
               placeholder='<?php echo esc_attr( WPSOLR_Global::getOption()->get_facets_range_regular_end( $selected_val ) ); ?>'
               value='<?php echo esc_attr( WPSOLR_Global::getOption()->get_facets_range_regular_end( $selected_val, '' ) ); ?>'/>
    </div>
    <div class='col_left'>
        Range gap
    </div>
    <div class='col_right'>
        <input type='text'
               name='wdm_solr_facet_data[<?php echo WPSOLR_Option::FACET_FIELD_RANGE_GAP; ?>][<?php echo $selected_val; ?>]'
               placeholder='<?php echo esc_attr( WPSOLR_Global::getOption()->get_facets_range_regular_gap( $selected_val ) ); ?>'
               value='<?php echo esc_attr( WPSOLR_Global::getOption()->get_facets_range_regular_gap( $selected_val, '' ) ); ?>'/>
    </div>

    <div class='col_left'>
        Facet label template
    </div>
    <div class='col_right'>
        <input type='text'
               name='wdm_solr_facet_data[<?php echo WPSOLR_Option::FACET_FIELD_LABEL_FIRST; ?>][<?php echo $selected_val; ?>]'
               placeholder='<?php echo esc_attr( WPSOLR_Global::getOption()->get_facets_range_regular_template( $selected_val ) ); ?>'
               value='<?php echo esc_attr( WPSOLR_Global::getOption()->get_facets_range_regular_template( $selected_val, '' ) ); ?>'/>

        A global template with variables {{start}}, {{end}} and {{count}} is used to generate a label for every range returned by the search.<br/>
        You can change the global template here.<br/>
        You can also set each range template individually in the localizations. Localizations with no values will use
        the global template.
    </div>

</div>

