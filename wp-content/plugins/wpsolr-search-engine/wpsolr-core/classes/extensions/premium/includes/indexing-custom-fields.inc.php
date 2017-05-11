<div class="wdm_row">
	<div class='col_left'>
		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Custom Fields to be indexed', true ); ?>
	</div>

	<div class='col_right'>
		<?php
		if ( ! empty( $custom_fields_error_message ) ) {
			echo sprintf( '<div class="error-message">%s</div>', $custom_fields_error_message );
		}
		?>

		<input type='hidden' name='wdm_solr_form_data[cust_fields]'
		       id='field_types'>

		<div class='cust_fields'><!--new div class given-->
			<?php
			$field_types_opt         = $solr_options['cust_fields'];
			$custom_field_properties = WPSOLR_Global::getOption()->get_option_index_custom_field_properties();
			$disabled                = $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM );
			if ( count( $keys ) > 0 ) {
				// sort custom fields
				asort( $keys );

				// Show selected first
				foreach ( $keys as $key ) {
					if ( strpos( $field_types_opt, $key . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ) !== false ) {
						$is_indexed_custom_field = true;
						?>

						<div class="wpsolr_custom_field_selected">
							<input type='checkbox' name='cust_fields'
							       value='<?php echo $key . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ?>'
								<?php echo $disabled; ?>
								   checked>

							<b><?php echo $key ?></b>

							<br/>
							<div style="margin-left:30px;">
								<select
									<?php
									$solr_dynamic_types = WpSolrSchema::get_solr_dynamic_entensions();
									$field_solr_type    = ! empty( $custom_field_properties[ $key . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ] ) && ! empty( $custom_field_properties[ $key . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ] )
										? $custom_field_properties[ $key . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ]
										: WpSolrSchema::get_solr_dynamic_entension_id_by_default();
									if ( $disabled ) {
										echo ' disabled ';
									}
									?>
									name="<?php echo sprintf( '%s[%s][%s][%s]', 'wdm_solr_form_data', WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTIES, $key . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING, WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ); ?>">
									<?php
									foreach ( $solr_dynamic_types as $solr_dynamic_type_id => $solr_dynamic_type_array ) {
										echo sprintf( '<option value="%s" %s %s>%s</option>',
											$solr_dynamic_type_id,
											selected( $field_solr_type, $solr_dynamic_type_id, false ),
											$solr_dynamic_type_array['disabled'],
											WpSolrSchema::get_solr_dynamic_entension_label( $solr_dynamic_type_array )
										);
									}
									?>
								</select>

								<?php //echo WPSOLR_Help::get_help( $solr_dynamic_types[ $field_solr_type ]['help_id'] ); ?>

								<select
									<?php
									$field_action_id = ! empty( $custom_field_properties[ $key . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ] ) && ! empty( $custom_field_properties[ $key . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION ] )
										? $custom_field_properties[ $key . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION ]
										: WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION_IGNORE_FIELD;
									if ( $disabled ) {
										echo ' disabled ';
									}
									?>
									name="<?php echo sprintf( '%s[%s][%s][%s]', 'wdm_solr_form_data', WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTIES, $key . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING, WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION ); ?>">
									<?php
									foreach (
										array(
											WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION_IGNORE_FIELD => 'Use empty value if conversion error',
											WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION_THROW_ERROR  => 'Stop indexing at first conversion error',
										) as $action_id => $action_text
									) {
										echo sprintf( '<option value="%s" %s>%s</option>', $action_id, selected( $field_action_id, $action_id, false ), $action_text );
									}
									?>
								</select>
							</div>

						</div>

						<?php
					}
				}

				// Show unselected 2nd
				foreach ( $keys as $key ) {
					if ( strpos( $field_types_opt, $key . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ) === false ) {
						?>

						<input type='checkbox' name='cust_fields'
						       value='<?php echo $key . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ?>'
							<?php echo $disabled; ?>
						> <?php echo $key ?>
						<br>
						<?php
					}
				}

			} else {
				echo 'None';
			}
			?>
		</div>
	</div>
	<div class="clear"></div>
</div>
