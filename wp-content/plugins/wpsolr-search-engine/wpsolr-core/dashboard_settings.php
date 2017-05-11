<?php

require_once WPSOLR_PLUGIN_DIR . '/classes/utilities/WPSOLR_Help.php';

define( 'WPSOLR_DASHBOARD_NONCE_SELECTOR', 'WPSOLR_DASHBOARD_NONCE_SELECTOR' );
define( 'WPSOLR_NONCE_FOR_DASHBOARD', 'wpsolr_nonce_for_dashboard' );

/**
 * Action to replace the admin footer text
 * @return string
 */
function wpsolr_admin_footer_text( $footer_text ) {
	global $license_manager;

	$current_screen = get_current_screen();

	// Display wpsolr footer only on wpsolr admin pages
	if ( 'solr_settings' === $current_screen->parent_file ) {
		$footer_text = 'If you like WPSOLR, thank you for letting others know with a <a href="https://wordpress.org/support/view/plugin-reviews/wpsolr-search-engine" target="__new">***** review</a>.';
		$footer_text .= ' Else, we\'d like very much your feedbacks throught our <a href="' . $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/' ) . '" target="__new">chat box</a> to improve the plugin.';

		$footer_version = 'You are using the free plugin.';
		if ( defined( 'WPSOLR_PLUGIN_PRO_DIR' ) ) {
			$footer_version = '<a style="color:red" href="?page=solr_settings&tab=solr_plugins&subtab=extension_premium_opt">You did not activate your extensions. Click here to proceed !</a>';
			$licenses       = OptionLicenses::get_activated_licenses_titles();
			if ( is_array( $licenses ) && ! empty( $licenses ) ) {

				$footer_version = sprintf( 'Activated packs: %s.', implode( ', ', $licenses ) );
			}
		}

		if ( ! defined( 'WPSOLR_PLUGIN_PRO_DIR' ) ) {
			$footer_version .= ' Fancy more features with <a href="' . $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/' ) . '" target="__new">WPSOLR PRO</a> ?';
		}

		$footer_text = $footer_version . '<br/>' . $footer_text;

		// Add nonce in all admin screens, for all wpsolr admin ajax calls.
		$footer_text .= sprintf(
			'<input type="hidden" id="%s" value="%s" >',
			esc_attr( WPSOLR_DASHBOARD_NONCE_SELECTOR ),
			esc_attr( wp_create_nonce( WPSOLR_NONCE_FOR_DASHBOARD ) )
		);

	}

	return $footer_text;
}

add_filter( 'admin_footer_text', 'wpsolr_admin_footer_text' );

/**
 * Action to replace the admin footer version
 * @return string
 */
function wpsolr_update_footer( $footer_version ) {

	$current_screen = get_current_screen();

	// Display wpsolr footer version only on wpsolr admin pages
	if ( 'solr_settings' === $current_screen->parent_file ) {
		$footer_version = sprintf( '%s %s', WPSOLR_PLUGIN_SHORT_NAME, WPSOLR_PLUGIN_VERSION );
	}

	return $footer_version;
}

add_filter( 'update_footer', 'wpsolr_update_footer', 11 );

/**
 * GEt the class of an extension license tab.
 *
 * @param $license_code
 *
 * @return string
 */
function wpsolr_get_extension_tab_class( $license_code ) {
	$activated_licenses_titles = OptionLicenses::get_activated_licenses_titles( $license_code );

	return empty( $activated_licenses_titles ) ? 'wpsolr_tab_inactive' : 'wpsolr_tab_active';
}


/*
 *  Route to controllers
 */
WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_MANAGED_SOLR_SERVERS, true );
WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );

switch ( isset( $_POST['wpsolr_action'] ) ? $_POST['wpsolr_action'] : '' ) {
	case 'wpsolr_admin_action_form_temporary_index':
		unset( $response_object );

		if ( isset( $_POST['submit_button_form_temporary_index'] ) ) {
			wpsolr_admin_action_form_temporary_index( $response_object );
		}

		if ( isset( $_POST['submit_button_form_temporary_index_select_managed_solr_service_id'] ) ) {

			$form_data = WpSolrExtensions::extract_form_data( true, array(
					'managed_solr_service_id' => array( 'default_value' => '', 'can_be_empty' => false )
				)
			);

			$managed_solr_server = new OptionManagedSolrServer( $form_data['managed_solr_service_id']['value'] );
			$response_object     = $managed_solr_server->call_rest_create_google_recaptcha_token();

			if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {
				$google_recaptcha_site_key = OptionManagedSolrServer::get_response_result( $response_object, 'siteKey' );
				$google_recaptcha_token    = OptionManagedSolrServer::get_response_result( $response_object, 'token' );
			}

		}

		break;

}

function wpsolr_admin_action_form_temporary_index( &$response_object ) {


	// recaptcha response
	$g_recaptcha_response = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : '';

	// A recaptcha response must be set
	if ( empty( $g_recaptcha_response ) ) {

		return;
	}

	$form_data = WpSolrExtensions::extract_form_data( true, array(
			'managed_solr_service_id' => array( 'default_value' => '', 'can_be_empty' => false )
		)
	);

	$managed_solr_server = new OptionManagedSolrServer( $form_data['managed_solr_service_id']['value'] );
	$response_object     = $managed_solr_server->call_rest_create_solr_index( $g_recaptcha_response );

	if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

		$option_indexes_object = new OptionIndexes();

		$option_indexes_object->create_index(
			$managed_solr_server->get_id(),
			OptionIndexes::STORED_INDEX_TYPE_MANAGED_TEMPORARY,
			OptionManagedSolrServer::get_response_result( $response_object, 'urlCore' ),
			'Test index from ' . $managed_solr_server->get_label(),
			OptionManagedSolrServer::get_response_result( $response_object, 'urlScheme' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'urlDomain' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'urlPort' ),
			'/' . OptionManagedSolrServer::get_response_result( $response_object, 'urlPath' ) . '/' . OptionManagedSolrServer::get_response_result( $response_object, 'urlCore' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'key' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'secret' )
		);

		// Redirect automatically to Solr options if it is the first solr index created
		if ( count( $option_indexes_object->get_indexes() ) === 1 ) {
			$redirect_location = '?page=solr_settings&tab=solr_option';
			header( "Location: $redirect_location", true, 302 ); // wp_redirect() is not found
			exit;
		}
	}

}

function wpsolr_admin_init() {

	WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
	register_setting( OptionIndexes::get_option_name( WpSolrExtensions::OPTION_INDEXES ), OptionIndexes::get_option_name( WpSolrExtensions::OPTION_INDEXES ) );

	WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_LICENSES, true );
	register_setting( OptionIndexes::get_option_name( WpSolrExtensions::OPTION_LICENSES ), OptionLicenses::get_option_name( WpSolrExtensions::OPTION_LICENSES ) );

	register_setting( 'solr_form_options', 'wdm_solr_form_data' );
	register_setting( 'solr_res_options', 'wdm_solr_res_data' );
	register_setting( 'solr_facet_options', 'wdm_solr_facet_data' );
	register_setting( 'solr_search_field_options', WPSOLR_Option::OPTION_SEARCH_FIELDS );
	register_setting( 'solr_sort_options', WPSOLR_Option::OPTION_SORTBY );
	register_setting( 'solr_localization_options', 'wdm_solr_localization_data' );
	register_setting( 'solr_extension_groups_options', 'wdm_solr_extension_groups_data' );
	register_setting( 'solr_extension_s2member_options', 'wdm_solr_extension_s2member_data' );
	register_setting( 'solr_extension_wpml_options', 'wdm_solr_extension_wpml_data' );
	register_setting( 'solr_extension_polylang_options', 'wdm_solr_extension_polylang_data' );
	register_setting( 'solr_extension_qtranslatex_options', 'wdm_solr_extension_qtranslatex_data' );
	register_setting( 'solr_operations_options', 'wdm_solr_operations_data' );
	register_setting( 'solr_extension_woocommerce_options', 'wdm_solr_extension_woocommerce_data' );
	register_setting( 'solr_extension_acf_options', 'wdm_solr_extension_acf_data' );
	register_setting( 'solr_extension_types_options', 'wdm_solr_extension_types_data' );
	register_setting( 'solr_extension_bbpress_options', 'wdm_solr_extension_bbpress_data' );
	register_setting( 'extension_embed_any_document_opt', WPSOLR_Option::OPTION_EMBED_ANY_DOCUMENT );
	register_setting( 'extension_pdf_embedder_opt', WPSOLR_Option::OPTION_PDF_EMBEDDER );
	register_setting( 'extension_google_doc_embedder_opt', WPSOLR_Option::OPTION_GOOGLE_DOC_EMBEDDER );
	register_setting( 'extension_tablepress_opt', WPSOLR_Option::OPTION_TABLEPRESS );
	register_setting( 'extension_geolocation_opt', WPSOLR_Option::OPTION_GEOLOCATION );
	register_setting( 'extension_premium_opt', WPSOLR_Option::OPTION_PREMIUM );
	register_setting( 'extension_theme_opt', WPSOLR_Option::OPTION_THEME );

}

function fun_add_solr_settings() {
	$img_url = plugins_url( 'images/WPSOLRDashicon.png', __FILE__ );
	add_menu_page( WPSOLR_PLUGIN_SHORT_NAME, WPSOLR_PLUGIN_SHORT_NAME, 'manage_options', 'solr_settings', 'fun_set_solr_options', $img_url );
	wp_enqueue_style( 'dashboard_style', plugins_url( 'css/dashboard_css.css', __FILE__ ), array(), WPSOLR_PLUGIN_VERSION );
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script( 'dashboard_js1', plugins_url( 'js/dashboard.js', __FILE__ ),
		array(
			'jquery',
			'jquery-ui-sortable',
		),
		WPSOLR_PLUGIN_VERSION
	);

	wp_localize_script( 'dashboard_js1', 'wpsolr_localize_script_dashboard',
		array(
			'ajax_url'                        => admin_url( 'admin-ajax.php' ),
			'wpsolr_dashboard_nonce_selector' => ( '#' . WPSOLR_DASHBOARD_NONCE_SELECTOR ),
		)
	);

	$plugin_vals = array( 'plugin_url' => plugins_url( 'images/', __FILE__ ) );
	wp_localize_script( 'dashboard_js1', 'plugin_data', $plugin_vals );

	// Google api recaptcha - Used for temporary indexes creation
	wp_enqueue_script( 'google-api-recaptcha', '//www.google.com/recaptcha/api.js', array(), WPSOLR_PLUGIN_VERSION );

	/**
	 * Color picker for facets
	 */
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'iris', admin_url( 'js/iris.min.js' ), array(
		'jquery-ui-draggable',
		'jquery-ui-slider',
		'jquery-touch-punch'
	), false, 1 );
	wp_enqueue_script( 'wp-color-picker', admin_url( 'js/color-picker.min.js' ), array( 'iris' ), false, 1 );
	$colorpicker_l10n = array(
		'clear'         => __( 'Clear' ),
		'defaultString' => __( 'Default' ),
		'pick'          => __( 'Select Color' )
	);
	wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n );

	// Bootstrap tour
	/*
	wp_enqueue_style( 'bootstrap_tour_css', plugins_url( 'css/bootstrap-tour-standalone.css', __FILE__ ), array(), 'v0.10.3' );
	wp_enqueue_script( 'bootstrap_tour_js', plugins_url( 'js/bootstrap-tour-standalone.js', __FILE__ ), array( 'jquery' ), 'v0.10.3' );
	*/
}

function fun_set_solr_options() {
	global $license_manager;

	// Include license activation popup boxes in all admin tabs
	add_thickbox();
	if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		// Do not load in Ajax
		require_once 'classes/extensions/licenses/admin_options.inc.php';
	}

	// Button Index
	if ( isset( $_POST['solr_index_data'] ) ) {

		$solr = WPSolrIndexSolrClient::create();

		try {
			$res = $solr->get_solr_status();

			$val = $solr->index_data();

			if ( count( $val ) == 1 || $val == 1 ) {
				echo "<script type='text/javascript'>
                jQuery(document).ready(function(){
                jQuery('.status_index_message').removeClass('loading');
                jQuery('.status_index_message').addClass('success');
                });
            </script>";
			} else {
				echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
                jQuery('.status_index_message').removeClass('loading');
                jQuery('.status_index_message').addClass('warning');
                });
            </script>";
			}

		} catch ( Exception $e ) {

			$errorMessage = $e->getMessage();

			echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_index_message').removeClass('loading');
               jQuery('.status_index_message').addClass('warning');
               jQuery('.wdm_note').html('<b>Error: <p>{$errorMessage}</p></b>');
            });
            </script>";

		}

	}

	// Button delete
	if ( isset( $_POST['solr_delete_index'] ) ) {
		$solr = WPSolrIndexSolrClient::create();

		try {
			$res = $solr->get_solr_status();

			$val = $solr->delete_documents();

			if ( $val == 0 ) {
				echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('loading');
               jQuery('.status_del_message').addClass('success');
            });
            </script>";
			} else {
				echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('loading');
                              jQuery('.status_del_message').addClass('warning');
            });
            </script>";
			}

		} catch ( Exception $e ) {

			$errorMessage = $e->getMessage();

			echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('loading');
               jQuery('.status_del_message').addClass('warning');
               jQuery('.wdm_note').html('<b>Error: <p>{$errorMessage}</p></b>');
            })
            </script>";
		}
	}


	?>
    <div class="wdm-wrap" xmlns="http://www.w3.org/1999/html">
    <div class="page_title">
        <h1>
            <!--<input id="wpsolr_tour_button_start" type="button" class="button-secondary" value="Resume the Tour" style="align:right">-->
            Power your search with <a href="https://www.elastic.co/" target="_blank">Elasticsearch</a> or <a
                    href="http://lucene.apache.org/solr/" target="_blank">Apache Solr</a>
        </h1>
    </div>

	<?php
	if ( isset ( $_GET['tab'] ) ) {
		wpsolr_admin_tabs( $_GET['tab'] );
	} else {
		wpsolr_admin_tabs( 'solr_presentation' );
	}

	if ( isset ( $_GET['tab'] ) ) {
		$tab = $_GET['tab'];
	} else {
		$tab = 'solr_presentation';
	}

	switch ( $tab ) {
	case 'solr_presentation' :
		?>
        <h2>You will need just 6 steps to configure your search with wpsolr</h2>

        <ol>
            <li>
                Install your <a
                        href="<?php echo $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/install-apache-solr/' ); ?>"
                        target="__wpsolr">Apache Solr server</a>, or install your
                <a
                        href="<?php echo $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/install-elasticsearch/' ); ?>"
                        target="__wpsolr">Elasticsearch server</a>
            </li>
            <li>
                Manually <a
                        href="<?php echo $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/configure-your-indexes/create-configure-apache-solr-index/' ); ?>"
                        target="__wpsolr">create and configure </a> your Apache Solr index. Elasticsearch
                indexes are managed automatically by WPSOLR
            </li>
            <li>
                In tab <a href="?page=solr_settings&tab=solr_indexes">"0. Define your indexes"</a>, select the indexes
                you want to use
            </li>
            <li>
                In tab <a href="?page=solr_settings&tab=solr_plugins">"1. Activate extensions"</a>, activate the
                extensions you need (WPSOLR PRO)
            </li>
            <li>
                In tab <a href="?page=solr_settings&tab=solr_option">"2. Define your search"</a>, select all the
                features you want for your search
            </li>
            <li>
                Finally, in tab <a href="?page=solr_settings&tab=solr_operations">"3. Send you data"</a>, index
                everything you selected in previous tabs
            </li>
        </ol>

        <h2>A few examples from our portfolio with 100Ks posts</h2>
        <iframe width="1020" height="630"
                src="https://www.youtube.com/embed/videoseries?list=PL5aStiCXsx-yHhZ7qixtpSszCcXZTQUyW&hd=1"
                frameborder="0"
                allowfullscreen>
        </iframe>

        <h2>Quick video to watch the setup steps</h2>
        <iframe width="1020" height="630" src="https://www.youtube.com/embed/Di2QExcliCo" frameborder="0"
                allowfullscreen>
        </iframe>
		<?php

		break;

	case 'solr_indexes' :
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::OPTION_INDEXES );
		break;

	case 'solr_option':
		?>
        <div id="solr-option-tab">

			<?php

			$subtabs = array(
				'result_opt'           => '2.1 Settings',
				'index_opt'            => '2.2 Indexed data',
				'field_opt'            => '2.3 Search fields boosts',
				'facet_opt'            => '2.4 Results facets',
				'sort_opt'             => '2.5 Results sort',
				'localization_options' => '2.6 Localization',
			);

			$subtab              = wpsolr_admin_sub_tabs( $subtabs );

			switch ( $subtab ) {
				case 'result_opt':

					WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
					$option_indexes = new OptionIndexes();
					$solr_indexes   = $option_indexes->get_indexes();

					?>
                    <div id="solr-results-options" class="wdm-vertical-tabs-content">
                        <form action="options.php" method="POST" id='res_settings_form'>
							<?php
							settings_fields( 'solr_res_options' );
							$solr_res_options = get_option( 'wdm_solr_res_data', array(
								'default_search'                     => 0,
								'res_info'                           => '0',
								'spellchecker'                       => '0',
								'is_after_autocomplete_block_submit' => '1',
							) );

							?>

                            <div class='wrapper'>
                                <h4 class='head_div'>Result Options</h4>

                                <div class="wdm_note">

                                    In this section, you will choose how to display the results returned by a
                                    query to your Solr instance.

                                </div>
                                <div class="wdm_row">
                                    <div class='col_left'>
                                        Replace WordPress default search by WPSOLR's.<br/><br/>
                                    </div>
                                    <div class='col_right'>
                                        <input type='checkbox' name='wdm_solr_res_data[default_search]'
                                               value='1'
											<?php checked( '1', isset( $solr_res_options['default_search'] ) ? $solr_res_options['default_search'] : '0' ); ?>>
                                        Check this option only after tabs 0-3 are completed. The WordPress search will then be replaced with WPSOLR. <br/><br/>
                                        Warning: permalinks must be activated.
                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class="wdm_row">
                                    <div class='col_left'>Search with this search engine index<br/>

                                    </div>
                                    <div class='col_right'>
                                        <select name='wdm_solr_res_data[default_solr_index_for_search]'>
											<?php
											// Empty option
											echo sprintf( "<option value='%s' %s>%s</option>",
												'',
												'',
												'Your search is not managed by a search engine index. Please select one here.'
											);

											foreach (
												$solr_indexes as $solr_index_indice => $solr_index
											) {

												echo sprintf( "
											<option value='%s' %s>%s</option>
											",
													$solr_index_indice,
													selected( $solr_index_indice, isset( $solr_res_options['default_solr_index_for_search'] ) ?
														$solr_res_options['default_solr_index_for_search'] : '' ),
													isset( $solr_index['index_name'] ) ? $solr_index['index_name'] : 'Unnamed
											Solr index' );

											}
											?>
                                        </select>

                                    </div>
                                    <div class="clear"></div>
                                </div>

								<?php
								if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_MULTI_SITE ) ) ) {
									require_once $file_to_include;
								}
								?>

								<?php
								if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_TEMPLATE ) ) ) {
									require_once $file_to_include;
								}
								?>

								<?php
								if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_PAGE_SLUG ) ) ) {
									require_once $file_to_include;
								}
								?>

                                <div class="wdm_row">
                                    <div class='col_left'>Do not load WPSOLR front-end css.<br/>You can then use
                                        your
                                        own theme css.
                                    </div>
                                    <div class='col_right'>
										<?php $is_prevent_loading_front_end_css = isset( $solr_res_options['is_prevent_loading_front_end_css'] ) ? '1' : '0'; ?>
                                        <input type='checkbox'
                                               name='wdm_solr_res_data[is_prevent_loading_front_end_css]'
                                               value='1'
											<?php checked( '1', $is_prevent_loading_front_end_css ); ?>>
                                    </div>
                                    <div class="clear"></div>
                                </div>

								<?php
								if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_INFINITE_SCROLL ) ) ) {
									require_once $file_to_include;
								}
								?>

								<?php
								if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_SUGGESTIONS ) ) ) {
									require_once $file_to_include;
								}
								?>

								<?php
								if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_SUGGESTIONS_JQUERY_SELECTOR ) ) ) {
									require_once $file_to_include;
								}
								?>

                                <div class="wdm_row">
                                    <div class='col_left'>Do not automatically trigger the search, when a user
                                        clicks on the
                                        autocomplete list
                                    </div>
                                    <div class='col_right'>
										<?php $is_after_autocomplete_block_submit = isset( $solr_res_options['is_after_autocomplete_block_submit'] ) ? '1' : '0'; ?>
                                        <input type='checkbox'
                                               name='wdm_solr_res_data[is_after_autocomplete_block_submit]'
                                               value='1'
											<?php checked( '1', $is_after_autocomplete_block_submit ); ?>>
                                    </div>
                                    <div class="clear"></div>
                                </div>

								<?php
								if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_DID_YOU_MEAN ) ) ) {
									require_once $file_to_include;
								}
								?>

                                <div class="wdm_row">
                                    <div class='col_left'>Display number of results and current page</div>
                                    <div class='col_right'>
                                        <input type='checkbox' name='wdm_solr_res_data[res_info]'
                                               value='res_info'
											<?php checked( 'res_info', isset( $solr_res_options['res_info'] ) ? $solr_res_options['res_info'] : '?' ); ?>>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class="wdm_row">
                                    <div class='col_left'>No. of results per page</div>
                                    <div class='col_right'>
                                        <input type='text' id='number_of_res' name='wdm_solr_res_data[no_res]'
                                               placeholder="Enter a Number"
                                               value="<?php echo empty( $solr_res_options['no_res'] ) ? '20' : $solr_res_options['no_res']; ?>">
                                        <span class='res_err'></span><br>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class="wdm_row">
                                    <div class='col_left'>No. of values to be displayed by filters</div>
                                    <div class='col_right'>
                                        <input type='text' id='number_of_fac' name='wdm_solr_res_data[no_fac]'
                                               placeholder="Enter a Number"
                                               value="<?php echo ( isset( $solr_res_options['no_fac'] ) && ( '' !== trim( $solr_res_options['no_fac'] ) ) ) ? $solr_res_options['no_fac'] : '20'; ?>"><span
                                                class='fac_err'></span>
                                        0 for unlimited values
                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class="wdm_row">
                                    <div class='col_left'>Maximum size of each snippet text in results</div>
                                    <div class='col_right'>
                                        <input type='text' id='highlighting_fragsize'
                                               name='wdm_solr_res_data[highlighting_fragsize]'
                                               placeholder="Enter a Number"
                                               value="<?php echo empty( $solr_res_options['highlighting_fragsize'] ) ? '100' : $solr_res_options['highlighting_fragsize']; ?>"><span
                                                class='highlighting_fragsize_err'></span> <br>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class="wdm_row">
                                    <div class='col_left'>Use partial keyword matches in results</div>
                                    <div class='col_right'>
                                        <input type='checkbox' class='wpsolr_checkbox_mono_wpsolr_is_partial'
                                               name='wdm_solr_res_data[<?php echo WPSOLR_Option::OPTION_SEARCH_ITEM_IS_PARTIAL_MATCHES; ?>]'
                                               value='1'
											<?php checked( isset( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_IS_PARTIAL_MATCHES ] ) ); ?>>
                                        Warning: this will hurt both search performance and search accuracy !
                                        <p>This adds '*' to all keywords.
                                            For instance, 'search apache' will return results
                                            containing 'searching apachesolr'</p>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class="wdm_row">
                                    <div class='col_left'>Use fuzzy keyword matches in results</div>
                                    <div class='col_right'>
                                        <input type='checkbox' class='wpsolr_checkbox_mono_wpsolr_is_partial other'
                                               name='wdm_solr_res_data[<?php echo WPSOLR_Option::OPTION_SEARCH_ITEM_IS_FUZZY_MATCHES; ?>]'
                                               value='1'
											<?php checked( isset( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_IS_FUZZY_MATCHES ] ) ); ?>>
                                        See <a
                                                href="https://cwiki.apache.org/confluence/display/solr/The+Standard+Query+Parser#TheStandardQueryParser-FuzzySearches"
                                                target="_new">Fuzzy description at Solr wiki</a>
                                        <p>The search 'roam' will match terms like roams, foam, & foams. It will
                                            also
                                            match the word "roam" itself.</p>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class='wdm_row'>
                                    <div class="submit">
                                        <input name="save_selected_options_res_form"
                                               id="save_selected_res_options_form" type="submit"
                                               class="button-primary wdm-save" value="Save Options"/>


                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
					<?php
					break;
				case 'index_opt':

					$custom_fields_error_message = '';

					$posts = array( 'post', 'page', 'product' );
					if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_POST_TYPES ) ) ) {
						require_once $file_to_include;
					}

					$args       = array(
						'public'   => true,
						'_builtin' => false

					);
					$output     = 'names'; // or objects
					$operator   = 'and'; // 'and' or 'or'
					$taxonomies = get_taxonomies( $args, $output, $operator );
					global $wpdb;
					$keys = $wpdb->get_col( "
                                                                    SELECT distinct meta_key
                                                                    FROM $wpdb->postmeta
                                                                    WHERE meta_key!='bwps_enable_ssl' 
                                                                    ORDER BY meta_key" );

					try {// Filter custom fields to be indexed.
						$keys = apply_filters( WpSolrFilters::WPSOLR_FILTER_INDEX_CUSTOM_FIELDS, $keys );
					} catch ( Exception $e ) {
						$custom_fields_error_message = $e->getMessage();
					}

					$post_types = array();
					foreach ( $posts as $ps ) {
						if ( $ps != 'attachment' && $ps != 'revision' && $ps != 'nav_menu_item' ) {
							array_push( $post_types, $ps );
						}
					}

					$allowed_attachments_types = get_allowed_mime_types();

					WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
					$option_indexes = new OptionIndexes();
					$solr_indexes   = $option_indexes->get_indexes();
					?>

                    <div id="solr-indexing-options" class="wdm-vertical-tabs-content">
                        <form action="options.php" method="POST" id='settings_form'>
							<?php
							settings_fields( 'solr_form_options' );
							$solr_options = get_option( 'wdm_solr_form_data', array(
								'comments'         => 0,
								'p_types'          => '',
								'taxonomies'       => '',
								'cust_fields'      => '',
								'attachment_types' => ''
							) );
							?>


                            <div class='indexing_option wrapper'>
                                <h4 class='head_div'>Indexing Options</h4>

                                <div class="wdm_note">

                                    In this section, you will choose among all the data stored in your Wordpress
                                    site, which you want to load in your Solr index.

                                </div>

								<?php
								if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_STOP_REAL_TIME ) ) ) {
									require_once $file_to_include;
								}
								?>

                                <div class="wdm_row">
                                    <div class='col_left'>
                                        Index post excerpt.<br/>
                                        Excerpt will be added to the post content, and be searchable, highlighted,
                                        and
                                        autocompleted.
                                    </div>
                                    <div class='col_right'>
                                        <input type='checkbox' name='wdm_solr_form_data[p_excerpt]'
                                               value='1' <?php checked( '1', isset( $solr_options['p_excerpt'] ) ? $solr_options['p_excerpt'] : '' ); ?>>

                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class="wdm_row">
                                    <div class='col_left'>
                                        Index custom fields and categories.<br/>
                                        Custom fields and categories will be added to the post content, and be
                                        searchable, highlighted,
                                        and
                                        autocompleted.
                                    </div>
                                    <div class='col_right'>
                                        <input type='checkbox' name='wdm_solr_form_data[p_custom_fields]'
                                               value='1' <?php checked( '1', isset( $solr_options['p_custom_fields'] ) ? $solr_options['p_custom_fields'] : '' ); ?>>

                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class="wdm_row">
                                    <div class='col_left'>
                                        Expand shortcodes of post content before indexing.<br/>
                                        Else, shortcodes will simply be stripped.
                                    </div>
                                    <div class='col_right'>
                                        <input type='checkbox' name='wdm_solr_form_data[is_shortcode_expanded]'
                                               value='1' <?php checked( '1', isset( $solr_options['is_shortcode_expanded'] ) ? $solr_options['is_shortcode_expanded'] : '' ); ?>>

                                    </div>
                                    <div class="clear"></div>
                                </div>

                                <div class="wdm_row">
                                    <div class='col_left'>
                                        Post types to be indexed
                                    </div>
                                    <div class='col_right'>
                                        <input type='hidden' name='wdm_solr_form_data[p_types]' id='p_types'>
										<?php
										$post_types_opt = $solr_options['p_types'];
										// Sort post types
										asort( $post_types );

										// Selected first
										foreach ( $post_types as $type ) {
											if ( strpos( $post_types_opt, $type ) !== false ) {
												$disabled = '';

												?>
                                                <input type='checkbox' name='post_tys'
                                                       value='<?php echo $type ?>'
													<?php echo $disabled; ?>
                                                       checked> <?php echo $type ?>
                                                <br>
												<?php
											}
										}

										// Unselected 2nd
										foreach ( $post_types as $type ) {
											if ( strpos( $post_types_opt, $type ) === false ) {
												$disabled = '';

												?>
                                                <input type='checkbox' name='post_tys'
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

								<?php
								if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_TAXONOMIES ) ) ) {
									require_once $file_to_include;
								}
								?>

								<?php
								if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_CUSTOM_FIELDS ) ) ) {
									require_once $file_to_include;
								}
								?>

								<?php
								if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_ATTACHMENTS ) ) ) {
									require_once $file_to_include;
								}
								?>

                                <div class="wdm_row">
                                    <div class='col_left'>Index Comments</div>
                                    <div class='col_right'>
                                        <input type='checkbox' name='wdm_solr_form_data[comments]'
                                               value='1' <?php checked( '1', isset( $solr_options['comments'] ) ? $solr_options['comments'] : '' ); ?>>

                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class="wdm_row">
                                    <div class='col_left'>Exclude items (Posts,Pages,...)</div>
                                    <div class='col_right'>
                                        <input type='text' name='wdm_solr_form_data[exclude_ids]'
                                               placeholder="Comma separated ID's list"
                                               value="<?php echo empty( $solr_options['exclude_ids'] ) ? '' : $solr_options['exclude_ids']; ?>">
                                        <br>
                                        (Comma separated ids list)
                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class='wdm_row'>
                                    <div class="submit">
                                        <input name="save_selected_index_options_form"
                                               id="save_selected_index_options_form" type="submit"
                                               class="button-primary wdm-save" value="Save Options"/>


                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
					<?php
					break;

				case 'field_opt':
					if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_BOOSTS ) ) ) {
						require_once $file_to_include;
					} else {
						?>
                        <div id="solr-facets-options" class="wdm-vertical-tabs-content">
                            <div class='wrapper'>
                                <h4 class='head_div'>Boost Options</h4>

                                <div class="wdm_note">

                                    With <?php echo sprintf( '<a href="%s" target="__new">WPSOLR PRO</a>', $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/' ) ) ?>
                                    , you can add boosts (weights) to the fields you think are the most
                                    important.
                                </div>
                            </div>
                        </div>
						<?php
					}
					break;

				case 'facet_opt':
					$solr_options = WPSOLR_Global::getOption()->get_option_index();
					$checked_fls = WPSOLR_Global::getOption()->get_option_index_custom_fields_str() . ',' . WPSOLR_Global::getOption()->get_option_index_taxonomies_str();

					$checked_fields = explode( ',', $checked_fls );
					$img_path       = plugins_url( 'images/plus.png', __FILE__ );
					$minus_path     = plugins_url( 'images/minus.png', __FILE__ );
					$built_in       = array( 'Type', 'Author', 'Categories', 'Tags' );
					$built_in       = array_merge( $built_in, $checked_fields );

					$built_in_can_show_hierarchy = explode( ',', 'Categories' . ',' . WPSOLR_Global::getOption()->get_option_index_taxonomies_str() );
					?>
                    <div id="solr-facets-options" class="wdm-vertical-tabs-content">
                        <form action="options.php" method="POST" id='fac_settings_form'>
							<?php
							settings_fields( 'solr_facet_options' );
							$solr_fac_options                = WPSOLR_Global::getOption()->get_option_facet();
							$selected_facets_value           = WPSOLR_Global::getOption()->get_facets_to_display_str();
							$selected_array                  = WPSOLR_Global::getOption()->get_facets_to_display();
							$selected_facets_is_hierarchy    = ! empty( $solr_fac_options[ WPSOLR_Option::OPTION_FACET_FACETS_TO_SHOW_AS_HIERARCH ] ) ? $solr_fac_options[ WPSOLR_Option::OPTION_FACET_FACETS_TO_SHOW_AS_HIERARCH ] : array();
							$selected_facets_labels          = WPSOLR_Global::getOption()->get_facets_labels();
							$selected_facets_item_labels     = WPSOLR_Global::getOption()->get_facets_items_labels();
							$selected_facets_item_is_default = WPSOLR_Global::getOption()->get_facets_items_is_default();
							$selected_facets_sorts           = WPSOLR_Global::getOption()->get_facets_sort();
							$selected_facets_is_exclusions   = WPSOLR_Global::getOption()->get_facets_is_exclusion();
							$selected_facets_layouts         = WPSOLR_Global::getOption()->get_facets_layout();
							$selected_facets_is_or           = WPSOLR_Global::getOption()->get_facets_is_or();
							?>
                            <div class='wrapper'>
                                <h4 class='head_div'>Filters Options</h4>

                                <div class="wdm_note">

                                    In this section, you will choose which data you want to display as filters in
                                    your search results. filters are extra filters usually seen in the left hand
                                    side of the results, displayed as a list of links. You can add filters only
                                    to data you've selected to be indexed.

                                </div>
                                <div class="wdm_note">
                                    <h4>Instructions</h4>
                                    <ul class="wdm_ul wdm-instructions">
                                        <li>Click on the 'Plus' icon to add the filters</li>
                                        <li>Click on the 'Minus' icon to remove the filters</li>
                                        <li>Sort the items in the order you want to display them by dragging and
                                            dropping them at the desired place
                                        </li>
                                    </ul>
                                </div>

                                <div class="wdm_row">
                                    <div class='avail_fac' style="width:100%">
                                        <h4>Available items for filters</h4>
                                        <input type='hidden' id='select_fac' name='wdm_solr_facet_data[facets]'
                                               value='<?php echo $selected_facets_value ?>'>

                                        <ul id="sortable1" class="wdm_ul connectedSortable">
											<?php

											if ( $selected_facets_value != '' ) {
												foreach ( $selected_array as $selected_val ) {
													if ( $selected_val != '' ) {
														if ( substr( $selected_val, ( strlen( $selected_val ) - 4 ), strlen( $selected_val ) ) == WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ) {
															$dis_text = substr( $selected_val, 0, ( strlen( $selected_val ) - 4 ) );
														} else {
															$dis_text = $selected_val;
														}
														?>
                                                        <li id='<?php echo $selected_val; ?>'
                                                            class='ui-state-default facets facet_selected'>
															<span
                                                                    style="float:left;width: 300px;"><?php echo $dis_text; ?></span>
                                                            <img src='<?php echo $img_path; ?>'
                                                                 class='plus_icon'
                                                                 style='display:none'>
                                                            <img src='<?php echo $minus_path ?>'
                                                                 class='minus_icon'
                                                                 style='display:inline'
                                                                 title='Click to Remove the filter'>
                                                            <br/>

															<?php
															if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_LABEL ) ) ) {
																require $file_to_include;
															}
															?>

															<?php
															if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_THEME_FACET_LAYOUT ) ) ) {
																require $file_to_include;
															}
															?>

                                                        </li>

													<?php }
												}
											}
											foreach ( $built_in as $built_fac ) {
												if ( $built_fac != '' ) {
													$buil_fac = strtolower( $built_fac );
													if ( substr( $buil_fac, ( strlen( $buil_fac ) - 4 ), strlen( $buil_fac ) ) == WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ) {
														$dis_text = substr( $buil_fac, 0, ( strlen( $buil_fac ) - 4 ) );
													} else {
														$dis_text = $buil_fac;
													}

													if ( ! in_array( $buil_fac, $selected_array ) ) {

														echo "<li id='$buil_fac' class='ui-state-default facets'>$dis_text
                                                                                                    <img src='$img_path'  class='plus_icon' style='display:inline' title='Click to Add the Facet'>
                                                                                                <img src='$minus_path' class='minus_icon' style='display:none'></li>";
													}
												}
											}
											?>


                                        </ul>
                                    </div>

                                    <div class="clear"></div>
                                </div>

                                <div class='wdm_row'>
                                    <div class="submit">
                                        <input name="save_facets_options_form" id="save_facets_options_form"
                                               type="submit" class="button-primary wdm-save"
                                               value="Save Options"/>


                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
					<?php
					break;

				case 'sort_opt':
					$img_path = plugins_url( 'images/plus.png', __FILE__ );
					$minus_path  = plugins_url( 'images/minus.png', __FILE__ );

					$built_in = WpSolrSchema::get_sort_fields();
					?>
                    <div id="solr-sort-options" class="wdm-vertical-tabs-content">
                        <form action="options.php" method="POST" id='sort_settings_form'>
							<?php
							settings_fields( 'solr_sort_options' );
							$selected_sort_value    = WPSOLR_Global::getOption()->get_sortby_items();
							$selected_array         = WPSOLR_Global::getOption()->get_sortby_items_as_array();
							$selected_sortby_labels = WPSOLR_Global::getOption()->get_sortby_items_labels();
							?>
                            <div class='wrapper'>
                                <h4 class='head_div'>Sort Options</h4>

                                <div class="wdm_note">

                                    In this section, you will choose which elements will be displayed as sort
                                    criteria for your search results, and in which order.

                                </div>
                                <div class="wdm_note">
                                    <h4>Instructions</h4>
                                    <ul class="wdm_ul wdm-instructions">
                                        <li>Click on the 'Plus' icon to add the sort</li>
                                        <li>Click on the 'Minus' icon to remove the sort</li>
                                        <li>Sort the items in the order you want to display them by dragging and
                                            dropping them at the desired place
                                        </li>
                                    </ul>
                                </div>

                                <div class="wdm_row">
                                    <div class='col_left'>Default when no sort is selected by the user</div>
                                    <div class='col_right'>
                                        <select name="wdm_solr_sortby_data[sort_default]">
											<?php foreach ( apply_filters( WpSolrFilters::WPSOLR_FILTER_DEFAULT_SORT_FIELDS, $built_in ) as $sort ) {
												$selected = WPSOLR_Global::getOption()->get_sortby_default() == $sort['code'] ? 'selected' : '';
												?>
                                                <option
                                                        value="<?php echo $sort['code'] ?>" <?php echo $selected ?> ><?php echo $sort['label'] ?></option>
											<?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="wdm_row">
                                    <div class='avail_fac'>
                                        <h4>Activate/deactivate items in the sort list</h4>
                                        <input type='hidden' id='select_sort' name='wdm_solr_sortby_data[sort]'
                                               value='<?php echo $selected_sort_value ?>'>


                                        <ul id="sortable_sort" class="wdm_ul connectedSortable_sort">
											<?php
											foreach ( $selected_array as $selected_sort ) {
											foreach ( $built_in as $built ) {
											if ( ! empty( $built ) && ( $selected_sort === $built['code'] ) ) {
											$sort_code = $built['code'];
											$dis_text  = $built['label'];

											if ( in_array( $sort_code, $selected_array ) ) {

											?>
                                            <li id='<?php echo $sort_code; ?>'
                                                class='ui-state-default facets sort_selected'>
                                <span
                                        style="float:left;width: 300px;"><?php echo $dis_text; ?></span>
                                                <img src='<?php echo $img_path; ?>'
                                                     class='minus_icon_sort'
                                                     style='display:none'>
                                                <img src='<?php echo $minus_path ?>'
                                                     class='minus_icon_sort'
                                                     style='display:inline'
                                                     title='Click to Remove the sort item'>
                                                <br/>

												<?php
												if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SORT_LABEL ) ) ) {
													require $file_to_include;
												}
												?>

												<?php
												}
												}
												}
												}
												foreach ( $built_in as $built ) {
													if ( $built != '' ) {
														$buil_fac = $built['code'];
														$dis_text = $built['label'];

														if ( ! in_array( $buil_fac, $selected_array ) ) {

															echo "<li id='$buil_fac' class='ui-state-default facets'>$dis_text
                                                                                                    <img src='$img_path'  class='plus_icon_sort' style='display:inline' title='Click to Add the Sort'>
                                                                                                <img src='$minus_path' class='minus_icon_sort' style='display:none'></li>";
														}
													}
												}
												?>
                                            </li>

                                        </ul>
                                    </div>

                                    <div class="clear"></div>
                                </div>

                                <div class='wdm_row'>
                                    <div class="submit">
                                        <input name="save_sort_options_form" id="save_sort_options_form"
                                               type="submit" class="button-primary wdm-save"
                                               value="Save Options"/>


                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
					<?php
					break;

				case 'localization_options':
					WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::OPTION_LOCALIZATION );
					break;

			}

			?>

        </div>
		<?php
		break;

	case 'solr_plugins':
	?>
    <div id="solr-option-tab">

		<?php

		$subtabs = array(
			'extension_theme_opt'               => array(
				'name'  => 'Theme',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_THEME ),
			),
			'extension_premium_opt'             => array(
				'name'  => 'Premium',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_PREMIUM ),
			),
			'extension_geolocation_opt'         => array(
				'name'  => 'Geolocation',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_GEOLOCATION ),
			),
			'extension_woocommerce_opt'         => array(
				'name'  => 'WooCommerce',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_WOOCOMMERCE ),
			),
			'extension_acf_opt'                 => array(
				'name'  => 'ACF',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_ACF ),
			),
			'extension_types_opt'               => array(
				'name'  => 'Toolset Types',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_TYPES ),
			),
			'extension_wpml_opt'                => array(
				'name'  => 'WPML',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_WPML ),
			),
			'extension_polylang_opt'            => array(
				'name'  => 'Polylang',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_POLYLANG ),
			),
			// It seems impossible to map qTranslate X structure (1 post/many languages) in WPSOLR's (1 post/1 language)
			/* 'extension_qtranslatex_opt' => 'qTranslate X', */
			'extension_tablepress_opt'          => array(
				'name'  => 'TablePress',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_TABLEPRESS ),
			),
			'extension_groups_opt'              => array(
				'name'  => 'Groups',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_GROUPS ),
			),
			'extension_s2member_opt'            => array(
				'name'  => 's2Member',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_S2MEMBER ),
			),
			'extension_bbpress_opt'             => array(
				'name'  => 'bbPress',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_BBPRESS ),
			),
			'extension_embed_any_document_opt'  => array(
				'name'  => 'Embed Any Document',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_EMBED_ANY_DOCUMENT ),
			),
			'extension_pdf_embedder_opt'        => array(
				'name'  => 'PDF Embedder',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_PDF_EMBEDDER ),
			),
			'extension_google_doc_embedder_opt' => array(
				'name'  => 'Google Doc Embedder',
				'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_GOOGLE_DOC_EMBEDDER ),
			),
		);

		$subtab = wpsolr_admin_sub_tabs( $subtabs );

		switch ( $subtab ) {
			case 'extension_groups_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_GROUPS );
				break;

			case 'extension_s2member_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_S2MEMBER );
				break;

			case 'extension_wpml_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_WPML );
				break;

			case 'extension_polylang_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_POLYLANG );
				break;

			case 'extension_qtranslatex_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_QTRANSLATEX );
				break;

			case 'extension_woocommerce_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_WOOCOMMERCE );
				break;

			case 'extension_acf_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_ACF );
				break;

			case 'extension_types_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_TYPES );
				break;

			case 'extension_bbpress_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_BBPRESS );
				break;

			case 'extension_embed_any_document_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_EMBED_ANY_DOCUMENT );
				break;

			case 'extension_pdf_embedder_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_PDF_EMBEDDER );
				break;

			case 'extension_google_doc_embedder_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_GOOGLE_DOC_EMBEDDER );
				break;

			case 'extension_tablepress_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_TABLEPRESS );
				break;

			case 'extension_geolocation_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_GEOLOCATION );
				break;

			case 'extension_premium_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_PREMIUM );
				break;

			case 'extension_theme_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::OPTION_THEME );
				break;
		}

		break;

		case 'solr_operations':

			WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
			$option_indexes_object = new OptionIndexes();

			// Create the tabs from the Solr indexes already configured
			$subtabs = array();
			foreach ( $option_indexes_object->get_indexes() as $index_indice => $index ) {
				$subtabs[ $index_indice ] = isset( $index['index_name'] ) ? $index['index_name'] : 'Index with no name';
			}

			if ( empty( $subtabs ) ) {
				echo "Please create a Solr index configuration first.";

				return;
			}

			// Create subtabs on the left side
			$current_index_indice = wpsolr_admin_sub_tabs( $subtabs );
			if ( ! $option_indexes_object->has_index( $current_index_indice ) ) {
				$current_index_indice = key( $subtabs );
			}
			$current_index_name = $subtabs[ $current_index_indice ];


			try {
				$solr                             = WPSolrAbstractIndexClient::create( $current_index_indice );
				$count_nb_documents_to_be_indexed = $solr->get_count_nb_documents_to_be_indexed();
				$count_blacklisted_ids            = $solr->get_count_blacklisted_ids();
			} catch ( Exception $e ) {
				echo '<b>An error occured while trying to connect to the Solr server:</b> <br>' . htmlentities( $e->getMessage() );

				return;
			}

			?>

            <div id="solr-operations-tab"
                 class="wdm-vertical-tabs-content">
                <form action="options.php" method='post' id='solr_actions'>
                    <input type='hidden' id='solr_index_indice' name='wdm_solr_operations_data[solr_index_indice]'
                           value="<?php echo $current_index_indice; ?>">
					<?php

					settings_fields( 'solr_operations_options' );

					$solr_operations_options = get_option( 'wdm_solr_operations_data' );

					$batch_size = empty( $solr_operations_options['batch_size'][ $current_index_indice ] ) ? '100' : $solr_operations_options['batch_size'][ $current_index_indice ];

					?>
                    <input type='hidden' id='adm_path' value='<?php echo admin_url(); ?>'> <!-- for ajax -->
                    <div class='wrapper'>
                        <h4 class='head_div'>Content of the index "<?php echo $current_index_name ?>"</h4>

                        <div class="wdm_note">
                            <div>
								<?php
								try {
									$nb_documents_in_index = $solr->get_count_documents();
									echo sprintf( "<b>A total of %s documents are currently in your index \"%s\"</b>", $nb_documents_in_index, $current_index_name );
								} catch ( Exception $e ) {
									echo '<b>Please check your hosting, an exception occured while calling your search server:</b> <br><br>' . htmlentities( $e->getMessage() );
								}
								?>
                            </div>
							<?php if ( $count_nb_documents_to_be_indexed >= 0 ): ?>
                                <div><b>
										<?php
										echo $count_nb_documents_to_be_indexed;

										// Reset value so it's not displayed next time this page is displayed.
										//$solr->update_count_documents_indexed_last_operation();
										?>
                                    </b> document(s) remain to be indexed. Click on the button "synchronize" to index
                                    them.
                                </div>
							<?php endif ?>
							<?php if ( $count_blacklisted_ids > 0 ): ?>
                                <div><b>
										<?php
										echo $count_blacklisted_ids;
										?>
                                    </b> document(s) will not be indexed, from the 2.2 exclusion list, or from the
                                    wpsolr metabox "do not search"
                                </div>
							<?php endif ?>
                        </div>
                        <div class="wdm_row">
                            <p>The indexing is <b>incremental</b>: only documents updated after the last operation
                                are sent to the index.</p>

                            <p>So, the first operation will index all documents, by batches of
                                <b><?php echo $batch_size; ?></b> documents.</p>

                            <p>If a <b>timeout</b> occurs, you just have to click on the button again: the process
                                will restart from where it stopped.</p>

                            <p>If you need to reindex all again, delete the index first.</p>
                        </div>
                        <div class="wdm_row">
                            <div class='col_left'>Number of documents sent in Solr as a single commit.<br>
                                You can change this number to control indexing's performance.
                            </div>
                            <div class='col_right'>
                                <input type='text' id='batch_size'
                                       name='wdm_solr_operations_data[batch_size][<?php echo $current_index_indice ?>]'
                                       placeholder="Enter a Number"
                                       value="<?php echo $batch_size; ?>">
                                <span class='res_err'></span><br>
                            </div>
                            <div class="clear"></div>

							<?php
							if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_BATCH_DEBUG ) ) ) {
								require $file_to_include;
							}
							?>

							<?php
							if ( file_exists( $file_to_include = apply_filters( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_BATCH_MODE_REPLACE ) ) ) {
								require $file_to_include;
							}
							?>

                        </div>
                        <div class="wdm_row">
                            <div class="submit">
                                <input name="solr_start_index_data" type="submit" class="button-primary wdm-save"
                                       id='solr_start_index_data'
                                       value="Synchronize Wordpress with index '<?php echo $current_index_name ?>' "/>
                                <input name="solr_stop_index_data" type="submit" class="button-primary wdm-save"
                                       id='solr_stop_index_data' value="Click to stop indexing"
                                       style="visibility: hidden;"/>
                                <span class='status_index_icon'></span>

                                <input name="solr_delete_index" type="submit" class="button-primary wdm-save"
                                       id="solr_delete_index"
                                       value="Delete all documents of index '<?php echo $current_index_name ?>' "/>
                                <input name="solr_stop_index_data" type="submit" class="button-primary wdm-save"
                                       id='solr_stop_delete_data' value="Click to stop deleting"
                                       style="visibility: hidden;"/>


                                <span class='status_index_message'></span>
                                <span class='status_debug_message'></span>
                                <span class='status_del_message'></span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
			<?php
			break;

		case 'wpsolr_licenses' :
			WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::OPTION_LICENSES );
			break;

		}
		?>


    </div>
	<?php


}

function wpsolr_admin_tabs( $current = 'solr_indexes' ) {

	// Get default search solr index indice
	WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
	$option_indexes            = new OptionIndexes();
	$default_search_solr_index = $option_indexes->get_default_search_solr_index();

	$nb_indexes        = count( $option_indexes->get_indexes() );
	$are_there_indexes = ( $nb_indexes >= 0 );

	$tabs                      = array();
	$tabs['solr_presentation'] = 'What is WPSOLR ?';
	$tabs['solr_indexes']      = $are_there_indexes ? '0. Connect your indexes' : '1. Connect your index';
	$tabs['solr_plugins']      = '1. Activate extensions';
	$tabs['solr_option']       = sprintf( "2. Define your search with '%s'",
		! isset( $default_search_solr_index )
			? $are_there_indexes ? "<span class='text_error'>No index selected</span>" : ''
			: $option_indexes->get_index_name( $default_search_solr_index ) );
	$tabs['solr_operations']   = '3. Send your data';
	//$tabs['wpsolr_licenses'] = '5. AddOns';

	echo '<div id="icon-themes" class="icon32"><br></div>';
	echo '<h2 class="nav-tab-wrapper wpsolr-tour-navigation-tabs">';
	foreach ( $tabs as $tab => $name ) {
		$class = ( $tab == $current ) ? ' nav-tab-active' : '';
		echo "<a class='nav-tab$class' href='admin.php?page=solr_settings&tab=$tab'>$name</a>";

	}
	echo '</h2>';
}


function wpsolr_admin_sub_tabs( $subtabs, $before = null ) {

	// Tab selected by the user
	$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'solr_presentation';

	if ( isset ( $_GET['subtab'] ) ) {

		$current_subtab = $_GET['subtab'];

	} else {
		// No user selection: use the first subtab in the list
		$current_subtab = key( $subtabs );
	}

	echo '<div id="icon-themes" class="icon32"><br></div>';
	echo '<h2 class="nav-tab-wrapper wdm-vertical-tabs">';

	if ( isset( $before ) ) {
		echo "$before<div style='clear: both;margin-bottom: 10px;'></div>";
	}

	foreach ( $subtabs as $subtab_indice => $subtab ) {
		if ( is_array( $subtab ) ) {
			$name        = $subtab['name'];
			$extra_class = $subtab['class'];
		} else {
			$extra_class = '';
			$name        = $subtab;
		}
		$class = ( $subtab_indice == $current_subtab ) ? ' nav-tab-active' : '';

		if ( false === strpos( $name, 'wpsolr_premium_class' ) ) {
			echo "<a class='nav-tab$class $extra_class' href='admin.php?page=solr_settings&tab=$tab&subtab=$subtab_indice'>$name</a>";
		} else {
			echo $name;
		}

	}

	echo '</h2>';

	return $current_subtab;
}
