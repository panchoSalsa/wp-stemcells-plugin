<?php


/**
 * Show help links on admin screens.
 *
 * Class WPSOLR_Help
 */
class WPSOLR_Help {

	// Url of help
	const _SEARCH_URL = '<a class="wpsolr-help" href="%s" target="_help"></a>';
	const _SEARCH_URL_HREF = 'https://www.wpsolr.com/?s=&wpsolr_fq[]=wpsolr_feature_str:%s';

	// Help ids
	const HELP_GEOLOCATION = 1;
	const HELP_MULTI_SITE = 2;
	const HELP_SEARCH_TEMPLATE = 3;
	const HELP_JQUERY_SELECTOR = 4;
	const HELP_SEARCH_ORDERS = 5;
	const HELP_ACF_REPEATERS_AND_FLEXIBLE_CONTENT_LAYOUTS = 6;
	const HELP_WOOCOMMERCE_REPLACE_SORT = 7;
	const HELP_ACF_GOOGLE_MAP = 8;
	const HELP_SCHEMA_TYPE_DATE = 9;
	const HELP_WOOCOMMERCE_REPLACE_CATEGORY_SEARCH = 10;
	const HELP_SEARCH_PAGE_SLUG = 11;
	const HELP_SEARCH_INFINITE_SCROLL = 12;
	const HELP_SEARCH_SUGGESTIONS = 13;
	const HELP_SEARCH_SUGGESTIONS_JQUERY_SELECTOR = 14;
	const HELP_SEARCH_DID_YOU_MEAN = 15;
	const HELP_INDEXING_STOP_REAL_TIME = 16;
	const HELP_INDEXING_POST_TYPES = 17;
	const HELP_INDEXING_CUSTOM_FIELDS = 18;
	const HELP_INDEXING_TAXONOMIES = 19;
	const HELP_INDEXING_ATTACHMENTS = 20;
	const HELP_SEARCH_BOOSTS = 21;
	const HELP_FACET_LABEL = 22;
	const HELP_FACET_HIERARCHY = 23;
	const HELP_FACET_POST_TYPE = 24;
	const HELP_SORT_LABEL = 25;
	const HELP_BATCH_DEBUG = 26;
	const HELP_BATCH_MODE_REPLACE = 27;
	const HELP_ACF_FIELD_FILE = 28;
	const HELP_LOCALIZE = 29;
	const HELP_MULTI_INDEX = 30;
	const HELP_THEME = 31;
	const HELP_FACET_DEFINITION = 32;
	const HELP_THEME_FACET_COLLAPSING = 33;
	const HELP_THEME_FACET_CSS = 34;
	const HELP_THEME_FACET_LAYOUT = 35;
	const HELP_TOOLSET_FIELD_FILE = 36;
	const HELP_THEME_AJAX_SEARCH_RESULTS_JQUERY_SELECTORS = 37;
	const HELP_THEME_AJAX_SEARCH_LOADER_CSS = 38;
	const HELP_THEME_AJAX_SEARCH_PAGINATION_JQUERY_SELECTORS = 39;
	const HELP_THEME_AJAX_SEARCH_RESULTS_COUNT_JQUERY_SELECTORS = 40;
	const HELP_THEME_AJAX_SEARCH_PAGE_TITLE_JQUERY_SELECTORS = 41;
	const HELP_THEME_AJAX_SEARCH_SORT_JQUERY_SELECTORS = 42;
	const HELP_THEME_AJAX_SEARCH_PAGINATION_PAGE_JQUERY_SELECTORS = 43;
	const HELP_THEME_AJAX_DELAY_MS = 44;

	/**
	 * Show a help_id description
	 *
	 * @param $help_id
	 *
	 * @return string
	 */
	public static function get_help( $help_id ) {
		global $license_manager;

		$url = sprintf( self::_SEARCH_URL, $license_manager->add_campaign_to_url( sprintf( self::_SEARCH_URL_HREF, $help_id ) ) );

		return $url;
	}
}