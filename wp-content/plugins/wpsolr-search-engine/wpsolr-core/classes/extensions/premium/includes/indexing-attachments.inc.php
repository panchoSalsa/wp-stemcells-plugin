<div class="wdm_row">
	<div class='col_left'>
		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Attachment types to be indexed', true ); ?>
	</div>
	<div class='col_right'>
		<input type='hidden' name='wdm_solr_form_data[attachment_types]'
		       id='attachment_types'>
		<?php
		$attachment_types_opt = $solr_options['attachment_types'];
		$disabled             = $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM );
		// sort attachments
		asort( $allowed_attachments_types );

		// Selected first
		foreach ( $allowed_attachments_types as $type ) {
			if ( strpos( $attachment_types_opt, $type ) !== false ) {
				?>
				<input type='checkbox' name='attachment_types'
				       value='<?php echo $type ?>'
					<?php echo $disabled; ?>
					   checked> <?php echo $type ?>
				<br>
				<?php
			}
		}

		// Unselected 2nd
		foreach ( $allowed_attachments_types as $type ) {
			if ( strpos( $attachment_types_opt, $type ) === false ) {
				?>
				<input type='checkbox' name='attachment_types'
				       value='<?php echo $type ?>'
					<?php echo $disabled; ?>
				> <?php echo $type ?>
				<br>
				<?php
			}
		}

		?>
	</div>
	<div class="clear"></div>
</div>
