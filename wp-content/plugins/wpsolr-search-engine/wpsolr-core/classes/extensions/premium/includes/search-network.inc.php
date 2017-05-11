<div class="wdm_row">
    <div class='col_left'>
		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'This search is part of a network search', true, true ); ?>
		<?php echo WPSOLR_Help::get_help( WPSOLR_Help::HELP_MULTI_SITE ); ?>
    </div>
    <div class='col_right'>
        <select
                name="wdm_solr_res_data[<?php echo WPSOLR_Option::OPTION_SEARCH_ITEM_GALAXY_MODE; ?>]">
			<?php
			$options = array(
				array(
					'code'  => '',
					'label' => 'No, this is a standalone search'
				),
				array(
					'code'     => WPSOLR_Option::OPTION_SEARCH_ITEM_IS_GALAXY_SLAVE,
					'label'    => 'Yes, as one of local searches (suggestions will not work)',
					'disabled' => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM, true ),
				),
				array(
					'code'     => WPSOLR_Option::OPTION_SEARCH_ITEM_IS_GALAXY_MASTER,
					'label'    => 'Yes, as the global search (only with ajax)',
					'disabled' => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM, true ),
				),
			);

			$search_galaxy_mode = WPSOLR_Global::getOption()->get_search_galaxy_mode();
			foreach ( $options as $option ) {
				$selected = $option['code'] === $search_galaxy_mode ? 'selected' : '';
				$disabled = isset( $option['disabled'] ) ? $option['disabled'] : '';
				?>
                <option
                        value="<?php echo $option['code'] ?>"
					<?php echo $selected ?>
					<?php echo $disabled ?>>
					<?php echo $option['label'] ?>
                </option>
			<?php } ?>

        </select>
        <ul>
            <li>- The global site searches in all local sites data</li>
            <li>- Each local site searches in it's own data</li>
        </ul>
    </div>
    <div class="clear"></div>
</div>
