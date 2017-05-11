<?php

/**
 * Manage options.
 */
class WPSOLR_Option {

	// Cache of options already retrieved from database.
	private $cached_options;

	/**
	 * WPSOLR_Option constructor.
	 */
	public function __construct() {
		$this->cached_options = array();

		/*
		add_filter( WpSolrFilters::WPSOLR_FILTER_AFTER_GET_OPTION_VALUE, array(
					$this,
					'debug',
				), 10, 2 );
		*/

	}

	/**
	 * Test filter WpSolrFilters::WPSOLR_FILTER_AFTER_GET_OPTION_VALUE
	 *
	 * @param $option_value
	 * @param $option
	 *
	 * @return string
	 */
	function test_filter( $option_value, $option ) {

		echo sprintf( "%s('%s') = '%s'<br/>", $option['option_name'], $option['$option_key'], $option_value );

		return $option_value;
	}

	/**
	 * Retrieve and cache an option
	 *
	 * @param string $option_name
	 *
	 * @param mixed $option_default_value
	 *
	 * @return array
	 */
	private function get_option( $option_name, $option_default_value = array() ) {

		// Retrieve option in cache, or in database
		if ( isset( $this->cached_options[ $option_name ] ) ) {

			// Retrieve option from cache
			$option = $this->cached_options[ $option_name ];

		} else {

			// Not in cache, retrieve option from database
			$option = get_option( $option_name, $option_default_value );

			// Add option to cached options
			$this->cached_options[ $option_name ] = $option;
		}

		return $option;
	}

	private function get_option_value( $caller_function_name, $option_name, $option_key, $option_default = null ) {

		if ( ! empty( $caller_function_name ) ) {
			// Filter before retrieving an option value
			$result = apply_filters( WpSolrFilters::WPSOLR_FILTER_BEFORE_GET_OPTION_VALUE, null, array(
				'option_name'     => $caller_function_name,
				'$option_key'     => $option_key,
				'$option_default' => $option_default,
			) );
			if ( ! empty( $result ) ) {
				return $result;
			}
		}

		// Retrieve option from cache or databse
		$option = $this->get_option( $option_name );

		// Retrieve option value from option
		if ( isset( $option ) ) {

			$result = isset( $option[ $option_key ] ) ? $option[ $option_key ] : $option_default;

		} else {

			// undefined
			$result = null;
		}

		if ( ! empty( $caller_function_name ) ) {
			// Filter after retrieving an option value
			return apply_filters( WpSolrFilters::WPSOLR_FILTER_AFTER_GET_OPTION_VALUE, $result, array(
				'option_name'     => $caller_function_name,
				'$option_key'     => $option_key,
				'$option_default' => $option_default,
			) );
		}
	}

	/**
	 * Convert a string to integer
	 *
	 * @param $string
	 * @param $object_name
	 *
	 * @return int
	 * @throws Exception
	 */
	private function to_integer( $string, $object_name ) {
		if ( is_numeric( $string ) ) {

			return intval( $string );

		} else {
			throw new Exception( sprintf( 'Option "%s" with value "%s" should be an integer.', $object_name, $string ) );
		}

	}

	/**
	 * Is value empty ?
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	private function is_empty( $value ) {
		return empty( $value );
	}

	/**
	 * Explode a comma delimited string in array.
	 * Returns empty array if string is empty
	 *
	 * @param $string
	 *
	 * @return array
	 */
	private function explode( $string ) {
		return empty( $string ) ? array() : explode( ',', $string );
	}

	/***************************************************************************************************************
	 *
	 * Sort by option and items
	 *
	 **************************************************************************************************************/
	const OPTION_SORTBY = 'wdm_solr_sortby_data';
	const OPTION_SORTBY_ITEM_DEFAULT = 'sort_default';
	const OPTION_SORTBY_ITEM_ITEMS = 'sort';
	const OPTION_SORTBY_ITEM_LABELS = 'sort_labels';


	/**
	 * Get sortby options array
	 * @return array
	 */
	public function get_option_sortby() {
		return self::get_option( self::OPTION_SORTBY );
	}

	/**
	 * Default sort by option
	 * @return string
	 */
	public function get_sortby_default() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_SORTBY, self::OPTION_SORTBY_ITEM_DEFAULT, WPSolrSearchSolrClient::SORT_CODE_BY_RELEVANCY_DESC );
	}

	/**
	 * Comma separated string of items selectable in sort by
	 * @return string Items
	 */
	public function get_sortby_items() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_SORTBY, self::OPTION_SORTBY_ITEM_ITEMS, WPSolrSearchSolrClient::SORT_CODE_BY_RELEVANCY_DESC );
	}

	/**
	 * Array of items selectable in sort by
	 * @return array Array of items
	 */
	public function get_sortby_items_as_array() {
		return $this->explode( $this->get_sortby_items() );
	}

	/**
	 * Array of sort items labels
	 * @return string[] Sort items labels
	 */
	public function get_sortby_items_labels() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_SORTBY, self::OPTION_SORTBY_ITEM_LABELS, array() );
	}

	public function get_option_installation() {

		if ( ! get_option( self::OPTION_INSTALLATION, false ) ) {

			$search = $this->get_option_search();
			if ( empty( $search ) ) {

				update_option( self::OPTION_INSTALLATION, true );
			}
		}

	}

	/***************************************************************************************************************
	 *
	 * Search results option and items
	 *
	 **************************************************************************************************************/
	const OPTION_SEARCH = 'wdm_solr_res_data';
	const OPTION_SEARCH_ITEM_REPLACE_WP_SEARCH = 'default_search';
	const OPTION_SEARCH_ITEM_SEARCH_METHOD = 'search_method';
	const OPTION_SEARCH_ITEM_IS_INFINITESCROLL = 'infinitescroll';
	const OPTION_SEARCH_ITEM_IS_INFINITESCROLL_REPLACE_JS = 'infinitescroll_is_js';
	const OPTION_SEARCH_ITEM_IS_PREVENT_LOADING_FRONT_END_CSS = 'is_prevent_loading_front_end_css';
	const OPTION_SEARCH_ITEM_is_after_autocomplete_block_submit = 'is_after_autocomplete_block_submit';
	const OPTION_SEARCH_ITEM_is_display_results_info = 'res_info';
	const OPTION_SEARCH_ITEM_max_nb_results_by_page = 'no_res';
	const OPTION_SEARCH_ITEM_max_nb_items_by_facet = 'no_fac';
	const OPTION_SEARCH_ITEM_highlighting_fragsize = 'highlighting_fragsize';
	const OPTION_SEARCH_ITEM_is_spellchecker = 'spellchecker';
	const OPTION_SEARCH_ITEM_IS_PARTIAL_MATCHES = 'is_partial_matches';
	const OPTION_SEARCH_ITEM_GALAXY_MODE = 'galaxy_mode';
	const OPTION_SEARCH_ITEM_IS_GALAXY_MASTER = 'is_galaxy_master';
	const OPTION_SEARCH_ITEM_IS_GALAXY_SLAVE = 'is_galaxy_slave';
	const OPTION_SEARCH_ITEM_IS_FUZZY_MATCHES = 'is_fuzzy_matches';
	const OPTION_SEARCH_SUGGEST_CONTENT_TYPE = 'suggest_content_type';
	const OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS = 'suggest_content_type_keywords';
	const OPTION_SEARCH_SUGGEST_CONTENT_TYPE_POSTS = 'suggest_content_type_posts';
	const OPTION_SEARCH_SUGGEST_CONTENT_TYPE_NONE = 'suggest_content_type_none';
	const OPTION_SEARCH_SUGGEST_JQUERY_SELECTOR = 'suggest_jquery_selector';
	const OPTION_SEARCH_SUGGEST_CLASS_DEFAULT = 'search-field';
	const OPTION_SEARCH_AJAX_SEARCH_PAGE_SLUG = 'ajax-search-slug';
	const OPTION_SEARCH_MODE_AJAX = 'ajax';
	const OPTION_SEARCH_MODE_THEME = 'use_current_theme_search_template';
	const OPTION_SEARCH_MODE_THEME_AJAX = 'use_current_theme_search_template_with_ajax';
	const OPTION_SEARCH_MODE_AJAX_WITH_PARAMETERS = 'ajax_with_parameters';

	/**
	 * Get search options array
	 * @return array
	 */
	public function get_option_search() {
		return self::get_option( self::OPTION_SEARCH );
	}

	/**
	 * Replace default WP search form and search results by WPSOLR's.
	 * @return boolean
	 */
	public function get_search_is_replace_default_wp_search() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_REPLACE_WP_SEARCH ) );
	}

	/**
	 * Search method
	 * @return boolean
	 */
	public function get_search_method() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_SEARCH_METHOD, self::OPTION_SEARCH_MODE_AJAX_WITH_PARAMETERS );
	}

	/**
	 * Show search parameters in url ?
	 * @return boolean
	 */
	public function get_search_is_show_url_parameters() {
		$search_mode = $this->get_search_method();

		return ( self::OPTION_SEARCH_MODE_AJAX !== $search_mode );
	}

	/**
	 * Redirect url on facets click ?
	 * @return boolean
	 */
	public function get_search_is_use_current_theme_search_template() {
		$search_mode = $this->get_search_method();

		return ( ( self::OPTION_SEARCH_MODE_THEME === $search_mode ) || ( self::OPTION_SEARCH_MODE_THEME_AJAX === $search_mode ) );
	}

	/**
	 * Use current search with ajax ?
	 * @return boolean
	 */
	public function get_search_is_use_current_theme_with_ajax() {
		$search_mode = $this->get_search_method();

		return ( self::OPTION_SEARCH_MODE_THEME !== $search_mode );
	}

	/**
	 * Show results with Infinitescroll pagination ?
	 * @return boolean
	 */
	public function get_search_is_infinitescroll() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_IS_INFINITESCROLL ) );
	}

	/**
	 * Load Infinitescroll js file ?
	 * @return boolean
	 */
	public function get_search_is_infinitescroll_replace_js() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_IS_INFINITESCROLL_REPLACE_JS ) );
	}

	/**
	 * Prevent loading WPSOLR default front-end css files. It's then easier to use current theme css.
	 * @return boolean
	 */
	public function get_search_is_prevent_loading_front_end_css() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_IS_PREVENT_LOADING_FRONT_END_CSS ) );
	}

	/**
	 * Do not trigger a search after selecting an item in the autocomplete list.
	 * @return string '1 for yes
	 */
	public function get_search_after_autocomplete_block_submit() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_is_after_autocomplete_block_submit, '0' );
	}

	/**
	 * Display results information, or not
	 * @return boolean
	 */
	public function get_search_is_display_results_info() {
		return ( 'res_info' === $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_is_display_results_info, 'res_info' ) );
	}

	/**
	 * Maximum number of results displayed on a page
	 * @return integer
	 */
	public function get_search_max_nb_results_by_page() {
		return $this->to_integer( $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_max_nb_results_by_page, 20 ), 'Max results by page' );
	}

	/**
	 * Maximum number of facet items displayed in any facet
	 * @return integer
	 */
	public function get_search_max_nb_items_by_facet() {
		return $this->to_integer( $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_max_nb_items_by_facet, 10 ), 'Max items by facet' );
	}

	/**
	 * Maximum length of highligthing text
	 * @return integer
	 */
	public function get_search_max_length_highlighting() {
		return $this->to_integer( $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_highlighting_fragsize, 100 ), 'Max length of highlighting' );
	}

	/**
	 * Is "Did you mean?" activated ?
	 * @return boolean
	 */
	public function get_search_is_did_you_mean() {
		return ( 'spellchecker' === $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_is_spellchecker, false ) );
	}

	/**
	 * Is "Partial matches?" activated ?
	 * @return boolean
	 */
	public function get_search_is_partial_matches() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_IS_PARTIAL_MATCHES ) );
	}


	/**
	 * Get galaxy mode
	 * @return boolean
	 */
	public function get_search_galaxy_mode() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_GALAXY_MODE, '' );
	}

	/**
	 * Is site in a galaxy ?
	 * @return boolean
	 */
	public function get_search_is_galaxy_mode() {
		return ! $this->is_empty( $this->get_search_galaxy_mode() );
	}

	/**
	 * Is site a galaxy slave search ?
	 * @return boolean
	 */
	public function get_search_is_galaxy_slave() {
		return ( self::OPTION_SEARCH_ITEM_IS_GALAXY_SLAVE === $this->get_search_galaxy_mode() );
	}

	/**
	 * Is site a galaxy master search ?
	 * @return boolean
	 */
	public function get_search_is_galaxy_master() {
		return ( self::OPTION_SEARCH_ITEM_IS_GALAXY_MASTER === $this->get_search_galaxy_mode() );
	}

	/**
	 * Is "Fuzzy matches?" activated ?
	 * @return boolean
	 */
	public function get_search_is_fuzzy_matches() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_ITEM_IS_FUZZY_MATCHES ) );
	}

	/**
	 * Search suggestions content
	 * @return string
	 */
	public function get_search_suggest_content_type() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_SUGGEST_CONTENT_TYPE, self::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS );
	}

	/**
	 * Search suggestions jquery selector
	 * @return string
	 */
	public function get_search_suggest_jquery_selector() {

		$result = $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_SUGGEST_JQUERY_SELECTOR, '' );

		$default_selector = '.' . self::OPTION_SEARCH_SUGGEST_CLASS_DEFAULT;

		if ( empty( $result ) ) {

			$result = $default_selector;

		} else {

			$result = $default_selector . ',' . $result;
		}

		return $result;
	}

	/**
	 * Ajax search page slug
	 * @return string
	 */
	public function get_search_ajax_search_page_slug() {
		$result = $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH, self::OPTION_SEARCH_AJAX_SEARCH_PAGE_SLUG, WPSolrSearchSolrClient::_SEARCH_PAGE_SLUG );

		return ! empty( $result ) ? $result : WPSolrSearchSolrClient::_SEARCH_PAGE_SLUG;
	}

	/***************************************************************************************************************
	 *
	 * Installation
	 *
	 **************************************************************************************************************/
	const OPTION_INSTALLATION = 'wpsolr_install';

	/***************************************************************************************************************
	 *
	 * Facets option and items
	 *
	 **************************************************************************************************************/
	const OPTION_FACET = 'wdm_solr_facet_data';
	const OPTION_FACET_FACETS = 'facets';
	const OPTION_FACET_FACETS_TO_SHOW_AS_HIERARCH = 'facets_show_hierarchy';
	const OPTION_FACET_FACETS_LABEL = 'facets_label';
	const OPTION_FACET_FACETS_ITEMS_LABEL = 'facets_item_label';
	const OPTION_FACET_FACETS_SORT = 'facets_sort';
	const OPTION_FACET_FACETS_ITEMS_IS_DEFAULT = 'facets_item_is_default';
	const OPTION_FACET_FACETS_IS_EXCLUSION = 'facets_is_exclusion';
	const OPTION_FACET_FACETS_LAYOUT = 'facets_layout';
	const OPTION_FACET_FACETS_TYPE = 'facet_type';
	const OPTION_FACET_FACETS_IS_OR = 'facets_is_or';
	const OPTION_FACET_FACETS_GRID = 'facets_grid';

	const OPTION_FACET_GRID_HORIZONTAL = 'h';
	const OPTION_FACET_GRID_1_COLUMN = 'c1';
	const OPTION_FACET_GRID_2_COLUMNS = 'c2';
	const OPTION_FACET_GRID_3_COLUMNS = 'c3';

	const OPTION_FACET_FACETS_TYPE_FIELD = 'facet_type_field';
	const OPTION_FACET_FACETS_TYPE_RANGE = 'facet_type_range';
	const OPTION_FACET_FACETS_TYPE_MIN_MAX = 'facet_type_min_max';

	const FACET_FIELD_LABEL_MIDDLE = 'facet_label_middle'; // Facet label
	const FACET_FIELD_LABEL_FIRST = 'facet_label_first'; // Label of the first label element
	const FACET_FIELD_LABEL_LAST = 'facet_label_last'; // Label of the last label element
	const FACET_FIELD_RANGE_START = 'facet_range_start'; // Start of the range
	const FACET_FIELD_RANGE_END = 'facet_range_end'; // End of the range
	const FACET_FIELD_RANGE_GAP = 'facet_range_gap'; // Gap of the range
	const FACET_FIELD_CUSTOM_RANGES = 'facet_custom_ranges'; // Custom ranges

	const FACETS_LAYOUT_ID_COLOR_PICKER = 'id_color_picker';
	const FACETS_LAYOUT_ID_SLIDER = 'id_slider';
	const FACETS_LAYOUT_ID_RADIOBOXES = 'id_radioboxes';
	const FACETS_LAYOUT_ID_DATE_PICKER = 'id_date_picker';
	const FACETS_LAYOUT_ID_CHECKBOXES = 'id_checkboxes';
	const FACETS_LAYOUT_ID_RANGE_REGULAR_CHECKBOXES = 'id_range_regular_checkboxes';
	const FACETS_LAYOUT_ID_RANGE_IRREGULAR_CHECKBOXES = 'id_range_irregular_checkboxes';
	const FACETS_LAYOUT_ID_RATING_STARS = 'id_rating_stars';
	const FACETS_LAYOUT_ID_RANGE_REGULAR_RADIOBOXES = 'id_range_regular_radioboxes';
	const FACETS_LAYOUT_ID_RANGE_IRREGULAR_RADIOBOXES = 'id_range_irregular_radioboxes';
	const FACETS_LAYOUT_ID_DROP_DOWN_LIST = 'id_drop_down_list';

	const FACET_LABEL_TEMPLATE_RANGE = '{{start}} - {{end}} ({{count}})';
	const FACET_LABEL_TEMPLATE = '%1$s (%2$s)';
	const FACET_LABEL_TEMPLATE_MIN_MAX = 'From %1$s to %2$s (%3$d)';
	const FACET_LABEL_TEMPLATE_RANGES = '0|10|%1$s - %2$s (%3$d)';


	/**
	 * Get facet options array
	 * @return array
	 */
	public function get_option_facet() {
		return self::get_option( self::OPTION_FACET, array() );
	}

	/**
	 * Comma separated facets
	 * @return string
	 */
	public function get_facets_to_display_str() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS, '' );
	}

	/**
	 * Facets
	 * @return array ["type","author","categories","tags","acf2_str"]
	 */
	public function get_facets_to_display() {
		return $this->explode( $this->get_facets_to_display_str() );
	}

	/**
	 * Facets to show as a hierarcy
	 *
	 * @return array Facets names
	 */
	public function get_facets_to_show_as_hierarchy() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_TO_SHOW_AS_HIERARCH, array() );
	}

	/**
	 * Facets labels
	 *
	 * @return array Facets names
	 */
	public function get_facets_labels() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_LABEL, array() );
	}

	/**
	 * Facets items labels
	 *
	 * @return array Facets items names
	 */
	public function get_facets_items_labels() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_ITEMS_LABEL, array() );
	}

	/**
	 * Facets items is default
	 *
	 * @return array Facets items names
	 */
	public function get_facets_items_is_default() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_ITEMS_IS_DEFAULT, array() );
	}

	/**
	 * Facets sort
	 * @return boolean
	 */
	public function get_facets_sort() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_SORT, array() );
	}

	/**
	 * Facets is OR
	 * @return boolean
	 */
	public function get_facets_is_or() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_IS_OR, [] );
	}

	/**
	 * Facets is exclusion
	 * @return boolean
	 */
	public function get_facets_is_exclusion() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_IS_EXCLUSION, array() );
	}

	/**
	 * Facets layout
	 * @return boolean
	 */
	public function get_facets_layout() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_LAYOUT, array() );
	}


	/**
	 * Get a facet option value
	 *
	 * @return mixed
	 */
	public function get_facets_value( $facet_option, $facet_name, $facet_default_value ) {
		$facets = $this->get_option_value( __FUNCTION__, self::OPTION_FACET, $facet_option, '' );

		return ( ! empty( $facets ) && ! empty( $facets[ $facet_name ] ) )
			? $facets[ $facet_name ]
			: $facet_default_value;
	}

	/**
	 * Get first label of a range regular facet
	 *
	 * @param string $facet_name
	 * @param string $default_value
	 *
	 * @return string
	 */
	public function get_facets_range_regular_template( $facet_name, $default_value = null ) {
		return $this->get_facets_value( self::FACET_FIELD_LABEL_FIRST, $facet_name, isset( $default_value ) ? $default_value : self::FACET_LABEL_TEMPLATE_RANGE );
	}

	/**
	 * Get layout id of a facet
	 *
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function get_facets_layout_id( $facet_name ) {
		return $this->get_facets_value( self::OPTION_FACET_FACETS_LAYOUT, $facet_name, '' );
	}

	/**
	 * Get start of a range regular facet
	 *
	 * @param string $facet_name
	 * @param string $default_value
	 *
	 * @return string
	 */
	public function get_facets_range_regular_start( $facet_name, $default_value = null ) {
		return $this->get_facets_value( self::FACET_FIELD_RANGE_START, $facet_name, isset( $default_value ) ? $default_value : '0' );
	}


	/**
	 * Get end of a range regular facet
	 *
	 * @param string $facet_name
	 * @param string $default_value
	 *
	 * @return string
	 */
	public function get_facets_range_regular_end( $facet_name, $default_value = null ) {
		return $this->get_facets_value( self::FACET_FIELD_RANGE_END, $facet_name, isset( $default_value ) ? $default_value : '100' );
	}


	/**
	 * Get gap of a range regular facet
	 *
	 * @param string $facet_name
	 * @param string $default_value
	 *
	 * @return string
	 */
	public function get_facets_range_regular_gap( $facet_name, $default_value = null ) {
		return $this->get_facets_value( self::FACET_FIELD_RANGE_GAP, $facet_name, isset( $default_value ) ? $default_value : '10' );
	}

	/**
	 * Get ranges of a range irregular facet
	 *
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function get_facets_range_irregular_ranges( $facet_name ) {
		return $this->get_facets_value( self::FACET_FIELD_CUSTOM_RANGES, $facet_name, self::FACET_LABEL_TEMPLATE_RANGES );
	}

	/**
	 * Facets grid
	 *
	 * @return array
	 */
	public function get_facets_grid() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_FACET, self::OPTION_FACET_FACETS_GRID, array() );
	}

	/**
	 * Get grid of a facet
	 *
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function get_facets_grid_value( $facet_name ) {
		return $this->get_facets_value( self::OPTION_FACET_FACETS_GRID, $facet_name, self::OPTION_FACET_GRID_1_COLUMN );
	}

	/***************************************************************************************************************
	 *
	 * Indexing option and items
	 *
	 **************************************************************************************************************/
	const OPTION_INDEX = 'wdm_solr_form_data';
	const OPTION_INDEX_ARE_COMMENTS_INDEXED = 'comments';
	const OPTION_INDEX_IS_REAL_TIME = 'is_real_time';
	const OPTION_INDEX_POST_TYPES = 'p_types';
	const OPTION_INDEX_ATTACHMENT_TYPES = 'attachment_types';
	const OPTION_INDEX_CUSTOM_FIELD_PROPERTIES = 'custom_field_properties'; // array
	const OPTION_INDEX_CUSTOM_FIELDS = 'cust_fields'; // array
	const OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE = 'solr_dynamic_type'; // string
	const OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION = 'conversion_error_action'; // string
	const OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION_THROW_ERROR = 'conversion_error_action_throw_error';
	const OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION_IGNORE_FIELD = 'conversion_error_action_ignore_field';
	const OPTION_INDEX_TAXONOMIES = 'taxonomies';

	/**
	 * Get indexing options array
	 * @return array
	 */
	public function get_option_index() {
		return self::get_option( self::OPTION_INDEX, array() );
	}

	/**
	 * Index comments ?
	 * @return boolean
	 */
	public function get_index_are_comments_indexed() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_ARE_COMMENTS_INDEXED ) );
	}

	/**
	 * Index real-time (on save) ?
	 * @return boolean
	 */
	public function get_index_is_real_time() {
		return $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_IS_REAL_TIME ) );
	}

	/**
	 * Is installed
	 * @return bool
	 */
	public function get_option_is_installed() {

		return get_option( self::OPTION_INSTALLATION, false );
	}

	/**
	 * @return array Post types
	 */
	public function get_option_index_post_types() {
		return $this->explode( $this->get_option_value( __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_POST_TYPES, '' ) );
	}

	/**
	 * @return string Post types
	 */
	public function get_option_index_attachment_types_str() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_ATTACHMENT_TYPES, '' );
	}

	/**
	 * @return array Post types
	 */
	public function get_option_index_attachment_types() {
		return $this->explode( $this->get_option_index_attachment_types_str() );
	}

	/**
	 * @return array Active custom fields
	 */
	public function get_option_index_custom_fields() {
		return $this->explode( $this->get_option_index_custom_fields_str() );
	}

	/**
	 * @return string Active custom fields
	 */
	public function get_option_index_custom_fields_str() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_CUSTOM_FIELDS, '' );
	}

	/**
	 * @return string Taxonomies indexed
	 */
	public function get_option_index_taxonomies_str() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_TAXONOMIES, '' );
	}

	/**
	 * @return array Taxonomies indexed
	 */
	public function get_option_index_taxonomies() {
		return $this->explode( $this->get_option_index_taxonomies_str() );
	}

	/**
	 * @return array Array of field's properties
	 */
	public function get_option_index_custom_field_properties() {
		$custom_field_properties = $this->get_option_value( __FUNCTION__, self::OPTION_INDEX, self::OPTION_INDEX_CUSTOM_FIELD_PROPERTIES, array() );

		// Filter $custom_field_properties with only active custom fields
		$has_been_filtered    = false;
		$active_custom_fields = $this->get_option_index_custom_fields();
		foreach ( $custom_field_properties as $custom_field_name => $custom_field_property ) {

			if ( ! in_array( $custom_field_name, $active_custom_fields, true ) ) {
				unset( $custom_field_properties[ $custom_field_name ] );
				$has_been_filtered = true;
			}
		}

		if ( $has_been_filtered ) {
			// Save the filtered properties to prevent filtering again and again
			$option                                               = get_option( self::OPTION_INDEX, array() );
			$option[ self::OPTION_INDEX_CUSTOM_FIELD_PROPERTIES ] = $custom_field_properties;
			update_option( self::OPTION_INDEX, $option );
		}

		return $custom_field_properties;
	}

	/***************************************************************************************************************
	 *
	 * Localization option and items
	 *
	 **************************************************************************************************************/
	const OPTION_LOCALIZATION = 'wdm_solr_localization_data';
	const OPTION_LOCALIZATION_LOCALIZATION_METHOD = 'localization_method';

	/**
	 * Get localization options array
	 * @return array
	 */
	public function get_option_localization() {
		return self::get_option( self::OPTION_LOCALIZATION );
	}

	/**
	 * @return bool
	 */
	public function get_localization_is_internal() {
		return ( 'localization_by_admin_options' === $this->get_option_value( __FUNCTION__, self::OPTION_LOCALIZATION, self::OPTION_LOCALIZATION_LOCALIZATION_METHOD, 'localization_by_admin_options' ) );
	}

	/***************************************************************************************************************
	 *
	 * Search fields option and items
	 *
	 **************************************************************************************************************/
	const OPTION_SEARCH_FIELDS = 'wdm_solr_search_field_data';
	const OPTION_SEARCH_FIELDS_IS_ACTIVE = 'search_fields_is_active';
	const OPTION_SEARCH_FIELDS_FIELDS = 'search_fields';
	const OPTION_SEARCH_FIELDS_BOOST = 'search_field_boost';
	const OPTION_SEARCH_FIELDS_TERMS_BOOST = 'search_field_terms_boosts';

	/**
	 * @return string Comma separated Fields
	 */
	public function get_option_search_fields_str() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH_FIELDS, self::OPTION_SEARCH_FIELDS_FIELDS, '' );
	}

	/**
	 * @return array Array of fields
	 */
	public function get_option_search_fields() {
		return $this->explode( $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH_FIELDS, self::OPTION_SEARCH_FIELDS_FIELDS, '' ) );
	}

	/**
	 * Field boosts
	 *
	 * @return array Field boosts
	 */
	public function get_search_fields_boosts() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH_FIELDS, self::OPTION_SEARCH_FIELDS_BOOST, array() );
	}


	/**
	 * Field terms boosts
	 *
	 * @return array Field term boosts
	 */
	public function get_search_fields_terms_boosts() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH_FIELDS, self::OPTION_SEARCH_FIELDS_TERMS_BOOST, array() );
	}

	/**
	 * Is search fields options active ?
	 *
	 * @return boolean
	 */
	public function get_search_fields_is_active() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_SEARCH_FIELDS, self::OPTION_SEARCH_FIELDS_IS_ACTIVE ) );
	}


	/*
	 * Domains used in multi-language string plugins to store dynamic wpsolr translations
	 */
	const TRANSLATION_DOMAIN_FACET_LABEL = 'wpsolr facet label'; // Do not change
	const TRANSLATION_DOMAIN_SORT_LABEL = 'wpsolr sort label'; // Do not change
	const TRANSLATION_DOMAIN_GEOLOCATION_LABEL = 'wpsolr geolocation label'; // Do not change


	/***************************************************************************************************************
	 *
	 * Plugin Embed any document
	 *
	 **************************************************************************************************************/
	const OPTION_EMBED_ANY_DOCUMENT = 'wdm_solr_extension_embed_any_document_data';
	const OPTION_EMBED_ANY_DOCUMENT_IS_EMBED_DOCUMENTS = 'is_do_embed_documents';

	/**
	 * Is search embedded documents options active ?
	 *
	 * @return boolean
	 */
	public function get_embed_any_document_is_do_embed_documents() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_EMBED_ANY_DOCUMENT, self::OPTION_EMBED_ANY_DOCUMENT_IS_EMBED_DOCUMENTS ) );
	}

	/***************************************************************************************************************
	 *
	 * Plugin Pdf Embedder
	 *
	 **************************************************************************************************************/
	const OPTION_PDF_EMBEDDER = 'wdm_solr_extension_pdf_embedder_data';
	const OPTION_PDF_EMBEDDER_IS_EMBED_DOCUMENTS = 'is_do_embed_documents';

	/**
	 * Is search embedded documents options active ?
	 *
	 * @return boolean
	 */
	public function get_pdf_embedder_is_do_embed_documents() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_PDF_EMBEDDER, self::OPTION_PDF_EMBEDDER_IS_EMBED_DOCUMENTS ) );
	}

	/***************************************************************************************************************
	 *
	 * Plugin Google Doc Embedder
	 *
	 **************************************************************************************************************/
	const OPTION_GOOGLE_DOC_EMBEDDER = 'wdm_solr_extension_google_doc_embedder_data';
	const OPTION_GOOGLE_DOC_EMBEDDER_IS_EMBED_DOCUMENTS = 'is_do_embed_documents';

	/**
	 * Is search embedded documents options active ?
	 *
	 * @return boolean
	 */
	public function get_google_doc_embedder_is_do_embed_documents() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_GOOGLE_DOC_EMBEDDER, self::OPTION_GOOGLE_DOC_EMBEDDER_IS_EMBED_DOCUMENTS ) );
	}

	/***************************************************************************************************************
	 *
	 * Plugin TablePress
	 *
	 **************************************************************************************************************/
	const OPTION_TABLEPRESS = 'wdm_solr_extension_tablepress_data';
	const OPTION_TABLEPRESS_IS_INDEX_SHORTCODES = 'is_index_shortcodes';

	/**
	 * Index TablePress shortcodes ?
	 *
	 * @return boolean
	 */
	public function get_tablepress_is_index_shortcodes() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_TABLEPRESS, self::OPTION_TABLEPRESS_IS_INDEX_SHORTCODES ) );
	}

	/***************************************************************************************************************
	 *
	 * Geolocation options
	 *
	 **************************************************************************************************************/
	const OPTION_GEOLOCATION = 'wdm_solr_geolocation';
	const OPTION_GEOLOCATION_IS_ACTIVE = 'is_extension_active';
	const OPTION_GEOLOCATION_JQUERY_SELECTOR = 'geo_jquery_selector';
	const OPTION_GEOLOCATION_JQUERY_SELECTOR_USER_AGREEMENT = 'geo_jquery_selector_user_agreement';
	const OPTION_GEOLOCATION_IS_SHOW_USER_AGREEMENT_AJAX = 'geo_is_show_user_agreement_ajax';
	const OPTION_GEOLOCATION_IS_SHOW_USER_AGREEMENT_AJAX_IS_DEFAULT_YES = 'geo_is_show_user_agreement_ajax_is_default_yes';
	const OPTION_GEOLOCATION_USER_AGREEMENT_LABEL = 'geo_user_agreement_label';
	const OPTION_GEOLOCATION_DEFAULT_SORT = 'geo_default_sort';
	const OPTION_GEOLOCATION_RESULT_DISTANCE_LABEL = 'geo_result_distance_label';
	const OPTION_GEOLOCATION_IS_FILTER_EMPTY_COORDINATES = 'geo_is_filter_empty_coordinates';

	/**
	 * Get geolocation options array
	 * @return array
	 */
	public function get_option_geolocation() {
		return self::get_option( self::OPTION_GEOLOCATION );
	}

	/**
	 * Do show a user agreement checkbox on the ajax template ?
	 * @return boolean
	 */
	public function get_option_geolocation_is_show_user_agreement_ajax() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_GEOLOCATION, self::OPTION_GEOLOCATION_IS_SHOW_USER_AGREEMENT_AJAX ) );
	}

	/**
	 * User agreement checkbox on the ajax template is preselected ?
	 * @return boolean
	 */
	public function get_option_geolocation_is_show_user_agreement_ajax_is_default_yes() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_GEOLOCATION, self::OPTION_GEOLOCATION_IS_SHOW_USER_AGREEMENT_AJAX_IS_DEFAULT_YES ) );
	}

	/**
	 * Geolocation jquery selector of search box(es)
	 * @return string
	 */
	public function get_option_geolocation_jquery_selector() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_GEOLOCATION, self::OPTION_GEOLOCATION_JQUERY_SELECTOR, '' );
	}

	/**
	 * Geolocation jquery selector of user agreement checkbox
	 * @return string
	 */
	public function get_option_geolocation_selector_user_aggreement() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_GEOLOCATION, self::OPTION_GEOLOCATION_JQUERY_SELECTOR_USER_AGREEMENT, '' );
	}

	/**
	 * Geolocation user agreement label
	 * @return string
	 */
	public function get_option_geolocation_user_aggreement_label() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_GEOLOCATION, self::OPTION_GEOLOCATION_USER_AGREEMENT_LABEL, '' );
	}

	/**
	 * Geolocation default sort
	 * @return string
	 */
	public function get_option_geolocation_default_sort() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_GEOLOCATION, self::OPTION_GEOLOCATION_DEFAULT_SORT, '' );
	}

	/**
	 * Geolocation text used to show distance on each result
	 * @return string
	 */
	public function get_option_geolocation_result_distance_label() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_GEOLOCATION, self::OPTION_GEOLOCATION_RESULT_DISTANCE_LABEL, '' );
	}

	/**
	 * Remove empty coordinates from results ?
	 * @return boolean
	 */
	public function get_option_geolocation_is_filter_results_with_empty_coordinates() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_GEOLOCATION, self::OPTION_GEOLOCATION_IS_FILTER_EMPTY_COORDINATES ) );
	}

	/***************************************************************************************************************
	 *
	 * Plugin Woocommerce
	 *
	 **************************************************************************************************************/
	const OPTION_PLUGIN_WOOCOMMERCE = 'wdm_solr_extension_woocommerce_data';
	const OPTION_PLUGIN_WOOCOMMERCE_IS_REPLACE_ADMIN_ORDERS_SEARCH = 'is_replace_admin_orders_search';
	const OPTION_PLUGIN_WOOCOMMERCE_IS_REPLACE_SORT_ITEMS = 'is_replace_sort_items';
	const OPTION_PLUGIN_WOOCOMMERCE_IS_REPLACE_PRODUCT_CATEGORY_SEARCH = 'is_replace_product_category_search';

	/**
	 * Get all WooCommerce options
	 *
	 * @param array $default_value
	 *
	 * @return array
	 */
	public function get_option_plugin_woocommerce() {
		return self::get_option( self::OPTION_PLUGIN_WOOCOMMERCE, array() );
	}

	/**
	 * Replace the WooCommerce orders search ?
	 *
	 * @return bool
	 */
	public function get_option_plugin_woocommerce_is_replace_admin_orders_search() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_PLUGIN_WOOCOMMERCE, self::OPTION_PLUGIN_WOOCOMMERCE_IS_REPLACE_ADMIN_ORDERS_SEARCH ) );
	}

	/**
	 * Replace the WooCommerce sort items ?
	 *
	 * @return bool
	 */
	public function get_option_plugin_woocommerce_is_replace_sort_items() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_PLUGIN_WOOCOMMERCE, self::OPTION_PLUGIN_WOOCOMMERCE_IS_REPLACE_SORT_ITEMS ) );
	}


	/**
	 * Replace the WooCommerce product category search ?
	 *
	 * @return bool
	 */
	public function get_option_plugin_woocommerce_is_replace_product_category_search() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_PLUGIN_WOOCOMMERCE, self::OPTION_PLUGIN_WOOCOMMERCE_IS_REPLACE_PRODUCT_CATEGORY_SEARCH ) );
	}

	/***************************************************************************************************************
	 *
	 * Plugin Acf
	 *
	 **************************************************************************************************************/
	const OPTION_PLUGIN_ACF = 'wdm_solr_extension_acf_data';
	const OPTION_PLUGIN_ACF_GOOGLE_MAP_API_KEY = 'google_map_api_key';

	/**
	 * Get the google map api used by ACF for it's fields
	 * @return string
	 */
	public function get_plugin_acf_google_map_api_key() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_PLUGIN_ACF, self::OPTION_PLUGIN_ACF_GOOGLE_MAP_API_KEY, '' );
	}

	/***************************************************************************************************************
	 *
	 * Premium options
	 *
	 **************************************************************************************************************/
	const OPTION_PREMIUM = 'wdm_solr_premium';

	/**
	 * Get premium options array
	 * @return array
	 */
	public function get_option_premium() {
		return self::get_option( self::OPTION_PREMIUM, array() );
	}

	/***************************************************************************************************************
	 *
	 * Updates
	 *
	 **************************************************************************************************************/
	const OPTION_UPDATES = 'wdm_updates';
	const OPTION_UPDATES_LAST_ERROR = 'last_error';
	const OPTION_UPDATES_LAST_README_TXT = 'last_readme_txt';

	/**
	 * Get premium options array
	 * @return array
	 */
	public function get_option_updates() {
		return self::get_option( self::OPTION_UPDATES, array() );
	}

	/**
	 * Get last update error
	 * @return string
	 */
	public function get_update_last_error() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_UPDATES, self::OPTION_UPDATES_LAST_ERROR, '' );
	}

	/**
	 * Get last readme.txt
	 * @return string
	 */
	public function get_update_last_readme_txt() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_UPDATES_LAST_README_TXT, self::OPTION_UPDATES_LAST_README_TXT, '' );
	}


	/***************************************************************************************************************
	 *
	 * Theme options
	 *
	 **************************************************************************************************************/
	const OPTION_THEME = 'wdm_theme';
	const OPTION_THEME_FACET_IS_COLLAPSE = 'is_facet_collapse';
	const OPTION_THEME_FACET_CSS = 'facet_css';
	const OPTION_THEME_AJAX_FADE_JQUERY_SELECTOR = 'ajax_fade_jquery_selector';
	const OPTION_THEME_AJAX_LOADER_CSS = 'ajax_loader_css';
	const OPTION_THEME_AJAX_PAGINATION_JQUERY_SELECTOR = 'ajax_pagination_jquery_selector';
	const OPTION_THEME_AJAX_PAGINATION_PAGE_JQUERY_SELECTOR = 'ajax_page_jquery_selector';
	const OPTION_THEME_AJAX_RESULTS_COUNT_JQUERY_SELECTOR = 'ajax_results_count_jquery_selector';
	const OPTION_THEME_AJAX_RESULTS_JQUERY_SELECTOR = 'ajax_results_jquery_selector';
	const OPTION_THEME_AJAX_PAGE_TITLE_JQUERY_SELECTOR = 'ajax_title_jquery_selector';
	const OPTION_THEME_AJAX_SORT_JQUERY_SELECTOR = 'ajax_sort_jquery_selector';
	const OPTION_THEME_AJAX_RESULTS_JQUERY_SELECTOR_DEFAULT = '.products,.results-by-facets';
	const OPTION_THEME_AJAX_PAGINATION_JQUERY_SELECTOR_DEFAULT = 'nav.woocommerce-pagination,.paginate_div';
	const OPTION_THEME_AJAX_PAGINATION_PAGE_JQUERY_SELECTOR_DEFAULT = 'a.page-numbers,a.paginate';
	const OPTION_THEME_AJAX_RESULTS_COUNT_JQUERY_SELECTOR_DEFAULT = '.woocommerce-result-count,.res_info';
	const OPTION_THEME_AJAX_PAGE_TITLE_JQUERY_SELECTOR_DEFAULT = '.page-title';
	const OPTION_THEME_AJAX_SORT_JQUERY_SELECTOR_DEFAULT = '.woocommerce-ordering';
	const OPTION_THEME_AJAX_DELAY_MS = 'ajax_delay_ms';

	/**
	 * Get theme options array
	 * @return array
	 */
	public function get_option_theme() {
		return self::get_option( self::OPTION_THEME, array() );
	}

	/**
	 * Collapse facet hierarchies ?
	 *
	 * @return bool
	 */
	public function get_option_theme_facet_is_collapse() {
		return ! $this->is_empty( $this->get_option_value( __FUNCTION__, self::OPTION_THEME, self::OPTION_THEME_FACET_IS_COLLAPSE ) );
	}

	/**
	 * Get facets css
	 *
	 * @return string
	 */
	public function get_option_theme_facet_css() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_THEME, self::OPTION_THEME_FACET_CSS, '' );
	}


	/**
	 * Ajax search fade jquery selectors
	 * @return string
	 */
	public function get_option_theme_ajax_fade_jquery_selectors() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_THEME, self::OPTION_THEME_AJAX_FADE_JQUERY_SELECTOR, '' );
	}

	/**
	 * Ajax search pagination jquery selectors
	 * @return string
	 */
	public function get_option_theme_ajax_pagination_jquery_selectors() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_THEME, self::OPTION_THEME_AJAX_PAGINATION_JQUERY_SELECTOR, '' );
	}

	/**
	 * Ajax search pagination page jquery selectors
	 * @return string
	 */
	public function get_option_theme_ajax_pagination_page_jquery_selectors() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_THEME, self::OPTION_THEME_AJAX_PAGINATION_PAGE_JQUERY_SELECTOR, '' );
	}

	/**
	 * Ajax search results count jquery selectors
	 * @return string
	 */
	public function get_option_theme_ajax_results_count_jquery_selectors() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_THEME, self::OPTION_THEME_AJAX_RESULTS_COUNT_JQUERY_SELECTOR, '' );
	}

	/**
	 * Ajax search results jquery selectors
	 * @return string
	 */
	public function get_option_theme_ajax_results_jquery_selectors() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_THEME, self::OPTION_THEME_AJAX_RESULTS_JQUERY_SELECTOR, '' );
	}

	/**
	 * Ajax search page title jquery selectors
	 * @return string
	 */
	public function get_option_theme_ajax_page_title_jquery_selectors() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_THEME, self::OPTION_THEME_AJAX_PAGE_TITLE_JQUERY_SELECTOR, '' );
	}

	/**
	 * Ajax search sort jquery selectors
	 * @return string
	 */
	public function get_option_theme_ajax_sort_jquery_selectors() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_THEME, self::OPTION_THEME_AJAX_SORT_JQUERY_SELECTOR, '' );
	}

	/**
	 * Delay in ms beforing calling ajax
	 * @return string
	 */
	public function get_option_theme_ajax_delay_ms() {
		return $this->get_option_value( __FUNCTION__, self::OPTION_THEME, self::OPTION_THEME_AJAX_DELAY_MS, '' );
	}

}
