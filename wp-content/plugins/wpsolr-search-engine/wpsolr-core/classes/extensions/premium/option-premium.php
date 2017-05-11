<?php

/**
 * Class OptionPremium
 *
 * Manage Premium Pack
 */
class OptionPremium extends WpSolrExtensions {

	/*
	 * Constructor
	 * Subscribe to actions
	 */

	/**
	 * Constructor.
	 */
	function __construct() {

		add_filter( WpSolrFilters::WPSOLR_FILTER_INCLUDE_FILE, array( $this, 'wpsolr_filter_include_file' ), 10, 1 );
	}


	/**
	 * Include the file containing the help feature.
	 *
	 * @param int $help_id
	 *
	 * @return string File name & path
	 */
	public function wpsolr_filter_include_file( $help_id ) {

		switch ( $help_id ) {
			case WPSOLR_Help::HELP_MULTI_SITE:
				$file_name = 'search-network.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_TEMPLATE:
				$file_name = 'search-template.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_PAGE_SLUG:
				$file_name = 'search-page-slug.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_INFINITE_SCROLL:
				$file_name = 'search-infinite-scroll.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_SUGGESTIONS:
				$file_name = 'search-suggestions.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_SUGGESTIONS_JQUERY_SELECTOR:
				$file_name = 'search-suggestions-jquery-selectors.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_DID_YOU_MEAN:
				$file_name = 'search-did-you-mean.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_STOP_REAL_TIME:
				$file_name = 'indexing-stop-real-time.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_POST_TYPES:
				$file_name = 'indexing-post-types.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_TAXONOMIES:
				$file_name = 'indexing-taxonomies.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_CUSTOM_FIELDS:
				$file_name = 'indexing-custom-fields.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_ATTACHMENTS:
				$file_name = 'indexing-attachments.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_BOOSTS:
				$file_name = 'search-boosts.inc.php';
				break;

			case WPSOLR_Help::HELP_FACET_LABEL:
				$file_name = 'search-facet-label.inc.php';
				break;

			case WPSOLR_Help::HELP_SORT_LABEL:
				$file_name = 'search-sort-label.inc.php';
				break;

			case WPSOLR_Help::HELP_BATCH_DEBUG:
				$file_name = 'batch-debug.inc.php';
				break;

			case WPSOLR_Help::HELP_BATCH_MODE_REPLACE:
				$file_name = 'batch-mode-replace.inc.php';
				break;

			case WPSOLR_Help::HELP_ACF_FIELD_FILE:
				$file_name = 'acf-field-file.inc.php';
				break;

			case WPSOLR_Help::HELP_LOCALIZE:
				$file_name = 'localize.inc.php';
				break;

			case WPSOLR_Help::HELP_MULTI_INDEX:
				$file_name = 'multi-index.inc.php';
				break;

			case WPSOLR_Help::HELP_TOOLSET_FIELD_FILE:
				$file_name = 'toolset-field-file.inc.php';
				break;

			case WPSOLR_Help::HELP_THEME_FACET_LAYOUT:
				$file_name = '/theme/facet-theme-layout.inc.php';
				break;

			default:
				$file_name = '';
		}

		return ! empty( $file_name ) ? sprintf( '%s/includes/%s', dirname( __FILE__ ), $file_name ) : $help_id;
	}
}