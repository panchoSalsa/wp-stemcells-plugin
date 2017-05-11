<?php
$subtabs['new_index'] = count( $option_object->get_indexes() ) > 0 ? $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Configure another index', false ) : 'Configure your first index';
