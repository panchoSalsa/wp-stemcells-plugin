<?php

require_once plugin_dir_path( __FILE__ ) . 'wpsolr-abstract-engine-client.php';
require_once plugin_dir_path( __FILE__ ) . 'wpsolr-abstract-results-client.php';

/**
 * Class WPSolrSearchAbstractClient
 *
 * @property WPSolrAbstractResultsClient $results
 */
abstract class WPSolrAbstractSearchClient extends WPSolrAbstractEngineClient {

	protected $is_query_wildcard;

	protected $query_select;

	protected $search_engine_client_config;

	// Array of active extension objects
	protected $wpsolr_extensions;

	// Filter query exclusion tag used by facets.
	const FILTER_QUERY_TAG_FACET_EXCLUSION = 'fct_ex_%s';

	// Search template
	const _SEARCH_PAGE_TEMPLATE = 'wpsolr-search-engine/search.php';

	// Search page slug
	const _SEARCH_PAGE_SLUG = 'search-wpsolr';

	// Do not change - Sort by most relevant
	const SORT_CODE_BY_RELEVANCY_DESC = 'sort_by_relevancy_desc';

	// Do not change - Sort by newest
	const SORT_CODE_BY_DATE_DESC = 'sort_by_date_desc';

	// Do not change - Sort by oldest
	const SORT_CODE_BY_DATE_ASC = 'sort_by_date_asc';

	// Do not change - Sort by least comments
	const SORT_CODE_BY_NUMBER_COMMENTS_ASC = 'sort_by_number_comments_asc';

	// Do not change - Sort by most comments
	const SORT_CODE_BY_NUMBER_COMMENTS_DESC = 'sort_by_number_comments_desc';

	// Default maximum number of items returned by facet
	const DEFAULT_MAX_NB_ITEMS_BY_FACET = 10;

	// Defaut minimum count for a facet to be returned
	const DEFAULT_MIN_COUNT_BY_FACET = 1;

	// Default maximum size of highliting fragments
	const DEFAULT_HIGHLIGHTING_FRAGMENT_SIZE = 100;

	// Default highlighting prefix
	const DEFAULT_HIGHLIGHTING_PREFIX = '<b>';

	// Default highlighting postfix
	const DEFAULT_HIGHLIGHTING_POSFIX = '</b>';

	const PARAMETER_HIGHLIGHTING_FIELD_NAMES = 'field_names';
	const PARAMETER_HIGHLIGHTING_FRAGMENT_SIZE = 'fragment_size';
	const PARAMETER_HIGHLIGHTING_PREFIX = 'prefix';
	const PARAMETER_HIGHLIGHTING_POSTFIX = 'postfix';

	const PARAMETER_FACET_FIELD_NAMES = 'field_names';
	const PARAMETER_FACET_LIMIT = 'limit';
	const PARAMETER_FACET_MIN_COUNT = 'min_count';
	const PARAMETER_FACET_SORT_ALPHABETICALLY = 'index';


	const SORT_ASC = 'asc';
	const SORT_DESC = 'desc';

	static protected $bad_statuses = array(
		'draft',
		'pending',
		'trash',
		'future',
		'private',
		'auto-draft',
	);

	/**
	 * Build the query
	 *
	 */
	abstract public function search_engine_client_build_query();

	/**
	 * Ping the index
	 */
	abstract public function ping();


	/**
	 * Create a client using a configuration
	 *
	 * @param string $config
	 *
	 * @return WPSolrSearchElasticaClient|WPSolrSearchSolrClient
	 * @throws Exception
	 */
	static function create_from_config( $config ) {

		switch ( $config['index_engine'] ) {

			case self::ENGINE_ELASTICSEARCH:
				return new WPSolrSearchElasticaClient( $config );

			default:
				return new WPSolrSearchSolrClient( $config );
				break;

		}
	}


	/**
	 * Constructor used by factory WPSOLR_Global
	 * Create using the default index configuration
	 *
	 * @return WPSolrSearchSolrClient
	 */
	static function global_object() {

		return self::create_from_index_indice( null );
	}

	// Create using an index configuration
	static function create_from_index_indice( $index_indice ) {

		// Build config from the default indexing Solr index
		WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
		$options_indexes = new OptionIndexes();
		$config          = $options_indexes->build_config( $index_indice, null, self::DEFAULT_SEARCH_ENGINE_TIMEOUT_IN_SECOND );

		return self::create_from_config( $config );
	}

	public function __construct( $config ) {

		$this->init();

		$this->search_engine_client = $this->create_search_engine_client( $config );
	}

	/**
	 * Get suggestions from Solr (keywords or posts).
	 *
	 * @param string $query Keywords to suggest from
	 *
	 * @return array
	 */
	public function get_suggestions( $query ) {

		$results = array();

		switch ( WPSOLR_Global::getOption()->get_search_suggest_content_type() ) {

			case WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_POSTS:
				$results = $this->get_suggestions_posts( $query );
				break;

			case WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS:
				$results = $this->get_suggestions_keywords( $query );
				break;

			default:
				break;
		}

		return $results;
	}


	/**
	 * Get suggestions from Solr search.
	 *
	 * @param string $query Keywords to suggest from
	 *
	 * @return array
	 */
	public function get_suggestions_posts( $query ) {

		$wpsolr_query = WPSOLR_Global::getQuery();
		$wpsolr_query->set_wpsolr_query( $query );

		$results = WPSOLR_Global::getSolrClient()->display_results( $wpsolr_query );

		return array_slice( $results[3], 0, 5 );
	}


	/**
	 * Get suggestions from the engine.
	 *
	 * @param $query
	 *
	 * @return WPSolrAbstractResultsClient
	 */
	abstract protected function search_engine_client_get_suggestions_keywords( $query );

	/**
	 * Get suggestions.
	 *
	 * @param string $query Keywords to suggest from
	 *
	 * @return array
	 */
	public function get_suggestions_keywords( $query ) {

		$results = array();

		$results_set = $this->search_engine_client_get_suggestions_keywords( $query );

		$suggestions = $results_set->get_suggestions();

		foreach ( $suggestions as $term => $termResult ) {

			foreach ( $termResult as $result ) {

				array_push( $results, $result );
			}
		}

		return $results;
	}

	/**
	 * Retrieve or create the search page
	 */
	static function get_search_page() {

		// Let other plugins (POLYLANG, ...) modify the search page slug
		$search_page_slug = apply_filters( WpSolrFilters::WPSOLR_FILTER_SEARCH_PAGE_SLUG, WPSOLR_Global::getOption()->get_search_ajax_search_page_slug() );

		// Search page is found by it's path (hard-coded).
		$search_page = get_page_by_path( $search_page_slug );

		if ( ! $search_page ) {

			$search_page = self::create_default_search_page();

		} else {

			if ( 'publish' !== $search_page->post_status ) {

				$search_page->post_status = 'publish';

				wp_update_post( $search_page );
			}
		}


		return $search_page;
	}


	/**
	 * Create a default search page
	 *
	 * @return WP_Post The search page
	 */
	static function create_default_search_page() {

		// Let other plugins (POLYLANG, ...) modify the search page slug
		$search_page_slug = apply_filters( WpSolrFilters::WPSOLR_FILTER_SEARCH_PAGE_SLUG, WPSOLR_Global::getOption()->get_search_ajax_search_page_slug() );

		$_search_page = array(
			'post_type'      => 'page',
			'post_title'     => 'Search Results',
			'post_content'   => '[solr_search_shortcode]',
			'post_status'    => 'publish',
			'post_author'    => 1,
			'comment_status' => 'closed',
			'post_name'      => $search_page_slug
		);

		// Let other plugins (POLYLANG, ...) modify the search page
		$_search_page = apply_filters( WpSolrFilters::WPSOLR_FILTER_BEFORE_CREATE_SEARCH_PAGE, $_search_page );

		$search_page_id = wp_insert_post( $_search_page );

		update_post_meta( $search_page_id, 'bwps_enable_ssl', '1' );

		return get_post( $search_page_id );
	}

	/**
	 * Get all sort by options available
	 *
	 * @param string $sort_code_to_retrieve
	 *
	 * @return array
	 */
	public
	static function get_sort_options() {

		$results = array(

			array(
				'code'  => self::SORT_CODE_BY_RELEVANCY_DESC,
				'label' => 'Most relevant',
			),
			array(
				'code'  => self::SORT_CODE_BY_DATE_DESC,
				'label' => 'Newest',
			),
			array(
				'code'  => self::SORT_CODE_BY_DATE_ASC,
				'label' => 'Oldest',
			),
			array(
				'code'  => self::SORT_CODE_BY_NUMBER_COMMENTS_DESC,
				'label' => 'More comments',
			),
			array(
				'code'  => self::SORT_CODE_BY_NUMBER_COMMENTS_ASC,
				'label' => 'Less comments',
			),
		);

		return $results;
	}

	/**
	 * Get all sort by options available
	 *
	 * @param string $sort_code_to_retrieve
	 * @param array $sort_options
	 *
	 * @return array
	 */
	public static function get_sort_option_from_code( $sort_code_to_retrieve, $sort_options = null ) {

		if ( null === $sort_options ) {
			$sort_options = self::get_sort_options();
		}

		if ( null !== $sort_code_to_retrieve ) {
			foreach ( $sort_options as $sort ) {

				if ( $sort['code'] === $sort_code_to_retrieve ) {
					return $sort;
				}
			}
		}


		return array();
	}


	/**
	 * Create a query.
	 *
	 * @return object
	 */
	abstract protected function search_engine_client_create_query_select();

	/**
	 * Set query's default operator.
	 *
	 * @param string $operator
	 *
	 */
	abstract protected function search_engine_client_set_default_operator( $operator = 'AND' );

	/**
	 * Set query's start.
	 *
	 * @param int $start
	 *
	 */
	abstract protected function search_engine_client_set_start( $start );

	/**
	 * Set query's rows.
	 *
	 * @param int $rows
	 *
	 */
	abstract protected function search_engine_client_set_rows( $rows );

	/**
	 * Convert a $wpsolr_query in a query select
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return Object The query select
	 */
	public function set_query_select( WPSOLR_Query $wpsolr_query ) {

		// Create the query
		$this->query_select = $this->search_engine_client_create_query_select();

		// Set the query keywords.
		$this->set_keywords( $wpsolr_query->get_wpsolr_query() );

		// Set default operator
		$this->search_engine_client_set_default_operator( 'AND' );

		// Limit nb of results
		$this->search_engine_client_set_start( $wpsolr_query->get_start() );
		$this->search_engine_client_set_rows( WPSOLR_Global::getOption()->get_search_max_nb_results_by_page() );

		/**
		 * Add sort field(s)
		 */
		$this->add_sort_field( $wpsolr_query );

		/**
		 * Add facet fields
		 */
		$this->add_facet_fields(
			array(
				self::PARAMETER_FACET_FIELD_NAMES => WPSOLR_Global::getOption()->get_facets_to_display(),
				self::PARAMETER_FACET_LIMIT       => WPSOLR_Global::getOption()->get_search_max_nb_items_by_facet(),
				self::PARAMETER_FACET_MIN_COUNT   => self::DEFAULT_MIN_COUNT_BY_FACET,
			)
		);

		/**
		 * Add default filter query parameters
		 */
		$this->add_default_filter_query_fields( $wpsolr_query );

		/**
		 * Add filter query fields
		 */
		$this->add_filter_query_fields( $wpsolr_query->get_filter_query_fields() );

		/**
		 * Add highlighting fields
		 */
		$this->add_highlighting_fields(
			array(
				self::PARAMETER_HIGHLIGHTING_FIELD_NAMES   => array(
					WpSolrSchema::_FIELD_NAME_TITLE,
					WpSolrSchema::_FIELD_NAME_CONTENT,
					WpSolrSchema::_FIELD_NAME_COMMENTS,
				),
				self::PARAMETER_HIGHLIGHTING_FRAGMENT_SIZE => WPSOLR_Global::getOption()->get_search_max_length_highlighting(),
				self::PARAMETER_HIGHLIGHTING_PREFIX        => self::DEFAULT_HIGHLIGHTING_PREFIX,
				self::PARAMETER_HIGHLIGHTING_POSTFIX       => self::DEFAULT_HIGHLIGHTING_POSFIX,
			)
		);

		/**
		 * Add fields
		 */
		$this->add_fields( $wpsolr_query );

		/**
		 * Action to change the solarium query
		 */
		do_action( WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY,
			array(
				WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_WPSOLR_QUERY    => $wpsolr_query,
				WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_QUERY  => $this->query_select,
				WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SEARCH_TERMS    => $wpsolr_query->get_wpsolr_query(),
				WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SEARCH_USER     => wp_get_current_user(),
				WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_CLIENT => $this,
			)
		);


		// Done
		return $this->query_select;
	}

	/**
	 * Execute a WPSOLR query.
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return WPSolrAbstractResultsClient
	 */
	public function execute_wpsolr_query( WPSOLR_Query $wpsolr_query, $is_use_cache = true ) {

		if ( $is_use_cache && isset( $this->results ) ) {
			// Return results already in cache
			return $this->results;
		}

		// Create the query from the wpsolr query
		$this->set_query_select( $wpsolr_query );

		// Perform the query, return the result set
		return $this->execute_query();

	}

	/**
	 * Execute a query.
	 * Used internally, or when fine tuned select query is better than using a WPSOLR query.
	 *
	 * @return WPSolrAbstractResultsClient
	 */
	public function execute_query() {

		// Perform the query, return the result set
		return $this->results = $this->search_engine_client_execute( $this->search_engine_client, $this->query_select );
	}


	/**
	 * Get suggestions for did you mean.
	 *
	 * @param string $keywords
	 *
	 * @return string Did you mean keyword
	 */
	abstract protected function search_engine_client_get_did_you_mean_suggestions( $keywords );

	/**
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return array Array of html
	 */
	public function display_results( WPSOLR_Query $wpsolr_query ) {

		$output        = [];
		$search_result = [];

		// Load options
		$localization_options = OptionLocalization::get_options();

		$result_set = $this->execute_wpsolr_query( $wpsolr_query );
		$nb_results = $result_set->get_nb_results();

		// No results: try a new query if did you mean is activated
		if ( ( 0 === $nb_results ) && ( WPSOLR_Global::getOption()->get_search_is_did_you_mean() ) ) {

			$did_you_mean_keyword = $this->search_engine_client_get_did_you_mean_suggestions( $wpsolr_query->get_wpsolr_query() );

			if ( ! empty( $did_you_mean_keyword ) && ( $did_you_mean_keyword !== $wpsolr_query->get_wpsolr_query() ) ) {

				$err_msg         = sprintf( OptionLocalization::get_term( $localization_options, 'results_header_did_you_mean' ), $did_you_mean_keyword ) . '<br/>';
				$search_result[] = $err_msg;

				// Replace keywords with did you mean keywords
				$wpsolr_query->set_wpsolr_query( $did_you_mean_keyword );

				try {
					$result_set = $this->execute_wpsolr_query( $wpsolr_query, false );
					$nb_results = $result_set->get_nb_results();

				} catch ( Exception $e ) {
					// Sometimes, the spelling query returns errors
					// java.lang.StringIndexOutOfBoundsException: String index out of range: 15\n\tat java.lang.AbstractStringBuilder.charAt(AbstractStringBuilder.java:203)\n\tat
					// java.lang.StringBuilder.charAt(StringBuilder.java:72)\n\tat org.apache.solr.spelling.SpellCheckCollator.getCollation(SpellCheckCollator.java:164)\n\tat

					$nb_results = 0;
				}

			} else {
				$search_result[] = 0;
			}
		} else {
			$search_result[] = 0;
		}

		// Retrieve facets from resultset
		$facets_to_display = WPSOLR_Global::getOption()->get_facets_to_display();
		if ( count( $facets_to_display ) ) {
			foreach ( $facets_to_display as $facet ) {

				$min_count = self::DEFAULT_MIN_COUNT_BY_FACET;

				$fact = $this->get_facet_hierarchy_name( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, $facet );

				// _price_str => _price_f
				// title => title
				$fact = WpSolrSchema::replace_field_name_extension( $fact );

				$facet_res = $result_set->get_facet( "$fact" );

				$facet_data = [];
				$facet_type = $this->get_facet_type( $facet );
				foreach ( ! empty( $facet_res ) ? $facet_res : [] as $value => $count ) {
					if ( $count >= $min_count ) {
						switch ( $facet_type ) {
							case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_RANGE:

								if ( ! isset( $facet_range_start ) ) {
									$facet_range_start = WPSOLR_Global::getOption()->get_facets_range_regular_start( $facet );
								}
								if ( ! isset( $facet_range_gap ) ) {
									$facet_range_gap = WPSOLR_Global::getOption()->get_facets_range_regular_gap( $facet );
								}

								$value = $this->remove_range_empty_decimal( $value );
								$value = ( false === strpos( $value, '-' ) ) ? sprintf( '%s-%s', $value, $value + $facet_range_gap ) : $value;
								break;
						}
						$facet_data['values'][] = [ 'value' => $value, 'count' => $count ];
					}
				}

				if ( ! empty( $facet_data['values'] ) ) {
					$facet_data['facet_type'] = $facet_type;

					if ( WPSOLR_Option::OPTION_FACET_FACETS_TYPE_RANGE === $facet_data['facet_type'] ) {
						$facet_data['facet_range_start'] = $facet_range_start;
						$facet_data['facet_range_end']   = WPSOLR_Global::getOption()->get_facets_range_regular_end( $facet );
						$facet_data['facet_range_gap']   = $facet_range_gap;
						$facet_data['facet_template']    = WPSOLR_Global::getOption()->get_facets_range_regular_template( $facet );
					}

					$output[ $facet ] = $facet_data;
				}
			}
			$search_result[] = $output;

		} else {
			$search_result[] = 0;
		}

		$search_result[] = $nb_results;

		$results = array();

		$i                    = 1;
		$cat_arr              = array();
		$are_comments_indexed = WPSOLR_Global::getOption()->get_index_are_comments_indexed();
		foreach ( $result_set->get_results() as $document ) {

			$post_id = $document->PID;
			$title   = $document->title;
			$content = '';

			$image_url = $this->get_post_thumbnail( $document, $post_id );

			$no_comments = $document->numcomments;
			if ( $are_comments_indexed ) {
				$comments = $document->comments;
			}
			$date = date( 'm/d/Y', strtotime( $document->displaydate ) );

			if ( property_exists( $document, 'categories_str' ) ) {
				$cat_arr = $document->categories_str;
			}


			$cat  = implode( ',', $cat_arr );
			$auth = $document->author;

			$url = $this->get_post_url( $document, $post_id );

			$comm_no         = 0;
			$highlighted_doc = $result_set->get_highlighting( $document );
			if ( $highlighted_doc ) {

				foreach ( $highlighted_doc as $field => $highlight ) {

					if ( WpSolrSchema::_FIELD_NAME_TITLE === $field ) {

						$title = implode( ' (...) ', $highlight );

					} elseif ( WpSolrSchema::_FIELD_NAME_CONTENT === $field ) {

						$content = implode( ' (...) ', $highlight );

					} elseif ( WpSolrSchema::_FIELD_NAME_COMMENTS === $field ) {

						$comments = implode( ' (...) ', $highlight );
						$comm_no  = 1;
					}
				}
			}

			$msg = '';
			$msg .= "<div id='res$i'><div class='p_title'><a href='$url'>$title</a></div>";

			$image_fragment = '';
			// Display first image
			if ( ! empty( $image_url ) ) {
				$image_fragment .= "<img class='wdm_result_list_thumb' src='$image_url' />";
			}

			if ( empty( $content ) ) {
				// Set a default value for content if no highlighting returned.
				$post_to_show = get_post( $post_id );
				if ( isset( $post_to_show ) ) {
					// Excerpt first, or content.
					$content = ( ! empty( $post_to_show->post_excerpt ) ) ? $post_to_show->post_excerpt : $post_to_show->post_content;

					if ( isset( $ind_opt['is_shortcode_expanded'] ) && ( strpos( $content, '[solr_search_shortcode]' ) === false ) ) {

						// Expand shortcodes which have a plugin active, and are not the search form shortcode (else pb).
						global $post;
						$post    = $post_to_show;
						$content = do_shortcode( $content );
					}

					// Remove shortcodes tags remaining, but not their content.
					// strip_shortcodes() does nothing, probably because shortcodes from themes are not loaded in admin.
					// Credit: https://wordpress.org/support/topic/stripping-shortcodes-keeping-the-content.
					// Modified to enable "/" in attributes
					$content = preg_replace( "~(?:\[/?)[^\]]+/?\]~s", '', $content );  # strip shortcodes, keep shortcode content;


					// Strip HTML and PHP tags
					$content = strip_tags( $content );

					$solr_res_options = get_option( 'wdm_solr_res_data', array() );
					if ( isset( $solr_res_options['highlighting_fragsize'] ) && is_numeric( $solr_res_options['highlighting_fragsize'] ) ) {
						// Cut content at the max length defined in options.
						$content = substr( $content, 0, $solr_res_options['highlighting_fragsize'] );
					}
				}
			}


			// Format content text a little bit
			$content = str_replace( '&nbsp;', '', $content );
			$content = str_replace( '  ', ' ', $content );
			$content = ucfirst( trim( $content ) );
			$content .= '...';

			$msg .= "<div class='p_content'>$image_fragment $content</div>";
			if ( $comm_no === 1 ) {
				$comment_link_title = OptionLocalization::get_term( $localization_options, 'results_row_comment_link_title' );
				$msg                .= "<div class='p_comment'>$comments<a href='$url'>$comment_link_title</a></div>";
			}

			// Groups bloc - Bottom right
			$wpsolr_groups_message = apply_filters( WpSolrFilters::WPSOLR_FILTER_SOLR_RESULTS_DOCUMENT_GROUPS_INFOS, get_current_user_id(), $document );
			if ( isset( $wpsolr_groups_message ) ) {

				// Display groups of this user which owns at least one the document capability
				$message = $wpsolr_groups_message['message'];
				$msg     .= "<div class='p_misc'>$message";
				$msg     .= "</div>";
				$msg     .= '<br/>';

			}

			$append_custom_html = apply_filters( WpSolrFilters::WPSOLR_FILTER_SOLR_RESULTS_APPEND_CUSTOM_HTML, '', get_current_user_id(), $document, $wpsolr_query );
			if ( isset( $append_custom_html ) ) {
				$msg .= $append_custom_html;
			}

			// Informative bloc - Bottom right
			$msg .= "<div class='p_misc'>";
			$msg .= "<span class='pauthor'>" . sprintf( OptionLocalization::get_term( $localization_options, 'results_row_by_author' ), $auth ) . "</span>";
			$msg .= empty( $cat ) ? "" : "<span class='pcat'>" . sprintf( OptionLocalization::get_term( $localization_options, 'results_row_in_category' ), $cat ) . "</span>";
			$msg .= "<span class='pdate'>" . sprintf( OptionLocalization::get_term( $localization_options, 'results_row_on_date' ), $date ) . "</span>";
			$msg .= empty( $no_comments ) ? "" : "<span class='pcat'>" . sprintf( OptionLocalization::get_term( $localization_options, 'results_row_number_comments' ), $no_comments ) . "</span>";
			$msg .= "</div>";

			// End of snippet bloc
			$msg .= "</div><hr>";

			array_push( $results, $msg );
			$i = $i + 1;
		}
		//  $msg.='</div>';


		if ( count( $results ) < 0 ) {
			$search_result[] = 0;
		} else {
			$search_result[] = $results;
		}

		$fir = $wpsolr_query->get_start() + 1;

		$last = $wpsolr_query->get_start() + $wpsolr_query->get_nb_results_by_page();
		if ( $last > $nb_results ) {
			$last = $nb_results;
		}

		if ( WPSOLR_Global::getOption()->get_search_is_infinitescroll() ) {

			$information_header = sprintf( OptionLocalization::get_term( $localization_options, 'infinitescroll_results_header_pagination_numbers' ), $nb_results );

		} else {

			$information_header = sprintf( OptionLocalization::get_term( $localization_options, 'results_header_pagination_numbers' ), $fir, $last, $nb_results );
		}

		$search_result[] = "<span class='infor'>" . $information_header . "</span>";


		return $search_result;
	}

	/**
	 * Set minimum count of facet items to retrieve a facet.
	 *
	 * @param $min_count
	 *
	 */
	abstract protected function search_engine_client_set_facets_min_count( $facet_name, $min_count );

	/**
	 * Create a facet field.
	 *
	 * @param $facet_name
	 * @param $field_name
	 *
	 */
	abstract protected function search_engine_client_add_facet_field( $facet_name, $field_name );

	/**
	 * Create a facet range regular.
	 *
	 * @param $facet_name
	 * @param $field_name
	 *
	 * @param string $range_start
	 * @param string $range_end
	 * @param string $range_gap
	 *
	 */
	abstract protected function search_engine_client_add_facet_range_regular( $facet_name, $field_name, $range_start, $range_end, $range_gap );


	/**
	 * Set facets limit.
	 *
	 * @param int $limit
	 *
	 */
	abstract protected function search_engine_client_set_facets_limit( $facet_name, $limit );

	/**
	 * Sort a facet field alphabetically.
	 *
	 * @param $facet_name
	 *
	 */
	abstract protected function search_engine_client_set_facet_sort_alphabetical( $facet_name );

	/**
	 * Set facet field excludes.
	 *
	 * @param string $facet_name
	 * @param string $exclude
	 *
	 */
	abstract protected function search_engine_client_set_facet_excludes( $facet_name, $exclude );

	/**
	 * Add facet fields to the query
	 *
	 * @param $facets_parameters
	 */
	public function add_facet_fields(
		$facets_parameters
	) {

		// Field names
		$field_names = isset( $facets_parameters[ self::PARAMETER_FACET_FIELD_NAMES ] )
			? $facets_parameters[ self::PARAMETER_FACET_FIELD_NAMES ]
			: array();

		// Limit
		$limit = isset( $facets_parameters[ self::PARAMETER_FACET_LIMIT ] )
			? $facets_parameters[ self::PARAMETER_FACET_LIMIT ]
			: self::DEFAULT_MAX_NB_ITEMS_BY_FACET;

		// Min count
		$min_count = isset( $facets_parameters[ self::PARAMETER_FACET_MIN_COUNT ] )
			? $facets_parameters[ self::PARAMETER_FACET_MIN_COUNT ]
			: self::DEFAULT_MIN_COUNT_BY_FACET;


		if ( count( $field_names ) ) {


			foreach ( $field_names as $facet_with_str_extension ) {

				$facet = WpSolrSchema::replace_field_name_extension( $facet_with_str_extension );

				$fact = $this->get_facet_hierarchy_name( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, $facet );

				// Only display facets that contain data
				$this->search_engine_client_set_facets_min_count( $fact, $min_count );

				switch ( $this->get_facet_type( $facet_with_str_extension ) ) {

					case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_RANGE:
						$this->search_engine_client_add_facet_range_regular( $fact, $fact,
							WPSOLR_Global::getOption()->get_facets_range_regular_start( $facet_with_str_extension ),
							WPSOLR_Global::getOption()->get_facets_range_regular_end( $facet_with_str_extension ),
							WPSOLR_Global::getOption()->get_facets_range_regular_gap( $facet_with_str_extension )
						);
						break;

					default:
						// Add the facet
						$this->search_engine_client_add_facet_field( $fact, $fact );

						if ( ! empty( $limit ) ) {

							$this->search_engine_client_set_facets_limit( $fact, $limit );
						}

						if ( $this->is_facet_sorted_alphabetically( $facet_with_str_extension ) ) {

							$this->search_engine_client_set_facet_sort_alphabetical( $fact );
						}

						break;
				}


				if ( $this->is_facet_exclusion( $facet_with_str_extension ) ) {
					// Exclude the tag corresponding to this facet. The tag was set on filter query.
					$this->search_engine_client_set_facet_excludes( $fact, $facet_with_str_extension );
				}

			}
		}

	}

	/**
	 * Set highlighting.
	 *
	 * @param string[] $field_names
	 * @param string $prefix
	 * @param string $postfix
	 * @param int $fragment_size
	 *
	 */
	abstract protected function search_engine_client_set_highlighting( $field_names, $prefix, $postfix, $fragment_size );

	/**
	 * Add highlighting fields to the query
	 *
	 * @param array $highlighting_parameters
	 */
	public
	function add_highlighting_fields(
		$highlighting_parameters
	) {

		if ( $this->is_query_wildcard ) {
			// Wilcard queries does not need highlighting.
			return;
		}

		// Field names
		$field_names = isset( $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_FIELD_NAMES ] )
			? $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_FIELD_NAMES ]
			: array(
				WpSolrSchema::_FIELD_NAME_TITLE,
				WpSolrSchema::_FIELD_NAME_CONTENT,
				WpSolrSchema::_FIELD_NAME_COMMENTS
			);

		// Fragment size
		$fragment_size = isset( $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_FRAGMENT_SIZE ] )
			? $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_FRAGMENT_SIZE ]
			: self::DEFAULT_HIGHLIGHTING_FRAGMENT_SIZE;

		// Prefix
		$prefix = isset( $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_PREFIX ] )
			? $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_PREFIX ]
			: self::DEFAULT_HIGHLIGHTING_PREFIX;

		// Postfix
		$postfix = isset( $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_POSTFIX ] )
			? $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_POSTFIX ]
			: self::DEFAULT_HIGHLIGHTING_POSFIX;


		$this->search_engine_client_set_highlighting( $field_names, $prefix, $postfix, $fragment_size );
	}


	/**
	 * Add default query fields filters.
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 */
	private function add_default_filter_query_fields( WPSOLR_Query $wpsolr_query ) {

		$filter_query_fields = $wpsolr_query->get_filter_query_fields();

		if ( empty( $filter_query_fields ) ) {
			foreach ( WPSOLR_Global::getOption()->get_facets_items_is_default() as $default_facet_name => $default_facet_contents ) {

				if ( ! empty( $default_facet_contents ) ) {
					// The default facet is not yet in the parameters: add it.
					foreach ( array_keys( $default_facet_contents ) as $default_facet_content ) {
						array_push( $filter_query_fields, sprintf( '%s:%s', $default_facet_name, $default_facet_content ) );
					}
				}
			}
			if ( ! empty( $filter_query_fields ) ) {
				$wpsolr_query->set_filter_query_fields( $filter_query_fields );
			}
		}
	}

	/**
	 * Add a simple filter term.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param $facet_is_or
	 * @param string $field_value
	 *
	 * @param string $filter_tag
	 *
	 * @return
	 */
	abstract public function search_engine_client_add_filter_term( $filter_name, $field_name, $facet_is_or, $field_value, $filter_tag = '' );

	/**
	 * Add a simple filter range.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param $facet_is_or
	 * @param string $range_start
	 *
	 * @param string $range_end
	 * @param string $filter_tag
	 *
	 * @return
	 */
	abstract public function search_engine_client_add_filter_range( $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $filter_tag = '' );

	/**
	 * Add a negative filter on terms.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @param string $filter_tag
	 *
	 * @return
	 * @internal param $facet_is_or
	 */
	abstract public function search_engine_client_add_filter_not_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' );

	/**
	 * Add a filter on: empty or in terms.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 * @param string $filter_tag
	 *
	 */
	abstract public function search_engine_client_add_filter_empty_or_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' );

	/**
	 * Filter fields with values
	 *
	 * @param $filter_name
	 * @param $field_name
	 */
	abstract public function search_engine_client_add_filter_exists( $filter_name, $field_name );


	/**
	 * Add filter query fields to the query
	 *
	 * @param array $filter_query_fields
	 */
	private
	function add_filter_query_fields(
		$filter_query_fields = []
	) {

		if ( ! is_admin() ) {
			// Make sure unwanted statuses are not returned by any query.
			$this->search_engine_client_add_filter_not_in_terms( 'bad_statuses', WpSolrSchema::_FIELD_NAME_STATUS_S, self::$bad_statuses, '' );
		}

		if ( $this->is_galaxy_slave ) {
			// Filter results by the slave filter
			array_push( $filter_query_fields, sprintf( '%s:%s', WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR, $this->galaxy_slave_filter_value ) );
		}

		foreach ( $filter_query_fields as $filter_query_field ) {

			if ( ! empty( $filter_query_field ) ) {

				$filter_query_field_array = explode( ':', $filter_query_field, 2 );

				$filter_query_field_name_with_str = strtolower( $filter_query_field_array[0] );
				$filter_query_field_value         = isset( $filter_query_field_array[1] ) ? $filter_query_field_array[1] : '';

				// Escape Solr special caracters
				$filter_query_field_value = $this->escape_solr_special_catacters( $filter_query_field_value );

				if ( ! empty( $filter_query_field_name_with_str ) && ! empty( $filter_query_field_value ) ) {

					$filter_query_field_name_with_str = $this->get_facet_hierarchy_name( WpSolrSchema::_FIELD_NAME_NON_FLAT_HIERARCHY, $filter_query_field_name_with_str );

					// _price_str => _price_f
					// title => title
					$filter_query_field_name = WpSolrSchema::replace_field_name_extension( $filter_query_field_name_with_str );

					$fac_fd = "$filter_query_field_name";

					// In case the facet contains white space, we enclose it with "".
					$filter_query_field_value_escaped = "\"$filter_query_field_value\"";

					// Build the filter query array
					$fq_array = [
						'key'   => "$fac_fd:$filter_query_field_value_escaped",
						'query' => "$fac_fd:$filter_query_field_value_escaped",
					];

					if ( $this->is_facet_exclusion( $filter_query_field_name_with_str ) ) {
						// Add the exclusion tab for the facets excuded.
						$fq_array['tag'] = [ sprintf( self::FILTER_QUERY_TAG_FACET_EXCLUSION, $filter_query_field_name_with_str ) ];
					}

					$facet_is_or = $this->get_facet_is_or( $filter_query_field_name_with_str );
					switch ( $this->get_facet_type( $filter_query_field_name_with_str ) ) {
						case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_RANGE:
							$range = explode( '-', $filter_query_field_value, 2 );
							$this->search_engine_client_add_filter_range( $fq_array['key'], $fac_fd, $facet_is_or, $range[0], $range[1], ! empty( $fq_array['tag'] ) ? $fq_array['tag'] : '' );
							break;

						default:
							$this->search_engine_client_add_filter_term( $fq_array['key'], $fac_fd, $facet_is_or, $filter_query_field_value, ! empty( $fq_array['tag'] ) ? $fq_array['tag'] : '' );
							break;
					}
				}
			}
		}
	}

	/**
	 * Escape Solr special caracters
	 *
	 * @param string $string_to_escape String to escape
	 *
	 * @return mixed
	 */
	function escape_solr_special_catacters( $string_to_escape ) {

		$result = $string_to_escape;

		// Special characters and their escape characters. Add more in the array if necessary.
		$special_characters = array(
			'"' => '\"', // The double quote sends a nasty syntax error in Solr 5/6
		);

		// Caracters never found in any string to escape
		$unique_caracter = 'WPSOLR_MARK_THIS_CARACTERS';

		foreach ( $special_characters as $special_character => $special_character_escaped ) {

			$result = str_replace( $special_character_escaped, $unique_caracter, $string_to_escape ); // do not escape already escaped characters: replace them by a unique character
			$result = str_replace( $special_character, $special_character_escaped, $result ); // Here it is: escape special character
			$result = str_replace( $unique_caracter, $special_character_escaped, $result ); // Replace back already escaped characters
		}

		return $result;
	}

	/**
	 * Add a sort to the query
	 *
	 * @param string $sort
	 * @param string $sort_by
	 *
	 */
	abstract public function search_engine_client_add_sort( $sort, $sort_by );

	/**
	 * Add a geo distance sort.
	 * The field is already in the sorts. It will be replaced with geo sort specific syntax.
	 *
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 */
	abstract public function search_engine_client_add_sort_geolocation_distance( $field_name, $geo_latitude, $geo_longitude );

	/**
	 * Add sort field to the query
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 */
	private
	function add_sort_field(
		WPSOLR_Query $wpsolr_query
	) {

		$sort_field_name = $wpsolr_query->get_wpsolr_sort();

		switch ( $sort_field_name ) {

			case self::SORT_CODE_BY_DATE_DESC:
				$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_DATE, static::SORT_DESC );
				break;

			case self::SORT_CODE_BY_DATE_ASC:
				$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_DATE, static::SORT_ASC );
				break;

			case self::SORT_CODE_BY_NUMBER_COMMENTS_DESC:
				$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_NUMBER_OF_COMMENTS, static::SORT_DESC );
				break;

			case self::SORT_CODE_BY_NUMBER_COMMENTS_ASC:
				$this->search_engine_client_add_sort( WpSolrSchema::_FIELD_NAME_NUMBER_OF_COMMENTS, static::SORT_ASC );
				break;

			case self::SORT_CODE_BY_RELEVANCY_DESC:
				// None is relevancy by default
				break;

			default:
				// A custom field

				// Get field name without _asc or _desc ('price_str_asc' => 'price_str')
				$sort_field_without_order = WpSolrSchema::get_field_without_sort_order_ending( $sort_field_name );

				if ( $this->get_is_field_sortable( $sort_field_without_order ) ) {
					// extract asc or desc ('price_str_asc' => 'asc')
					$sort_field_order = WPSOLR_Regexp::extract_last_separator( $sort_field_name, '_' );

					switch ( $sort_field_order ) {

						case static::SORT_DESC:
						case static::SORT_ASC:

							// Standard sort field
							$this->search_engine_client_add_sort( WpSolrSchema::replace_field_name_extension( $sort_field_without_order ), $sort_field_order );
					}
				}

				break;
		}

		// Let a chance to add custom sort options
		$solarium_query = apply_filters( WpSolrFilters::WPSOLR_FILTER_SORT, $this->query_select, $sort_field_name, $wpsolr_query, $this );
	}

	/**
	 * Is a field sortable ?
	 *
	 * @param string $field_name Field name (like 'price_str')
	 *
	 * @return bool
	 */
	public
	function get_is_field_sortable(
		$field_name
	) {

		return ( ! empty( $this->custom_field_properties[ $field_name ] )
		         && ! empty( $this->custom_field_properties[ $field_name ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ] )
		         && WpSolrSchema::get_solr_dynamic_entension_id_is_sortable( $this->custom_field_properties[ $field_name ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ] )
		);

	}

	/**
	 * Set the fields to be returned by the query.
	 *
	 * @param array $fields
	 *
	 */
	abstract protected function search_engine_client_set_fields( $fields );

	/**
	 * Set fields returned by the query.
	 * We do not ask for 'content', because it can be huge for attachments, and is anyway replaced by highlighting.
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 */
	private
	function add_fields(
		WPSOLR_Query $wpsolr_query
	) {

		// We add '*' to dynamic fields, else they are not returned by Solr (Solr bug ?)
		$this->search_engine_client_set_fields(
			apply_filters(
				WpSolrFilters::WPSOLR_FILTER_FIELDS,
				array(
					WpSolrSchema::_FIELD_NAME_ID,
					WpSolrSchema::_FIELD_NAME_PID,
					WpSolrSchema::_FIELD_NAME_TITLE,
					WpSolrSchema::_FIELD_NAME_NUMBER_OF_COMMENTS,
					WpSolrSchema::_FIELD_NAME_COMMENTS,
					WpSolrSchema::_FIELD_NAME_DISPLAY_DATE,
					WpSolrSchema::_FIELD_NAME_DISPLAY_MODIFIED,
					'*' . WpSolrSchema::_FIELD_NAME_CATEGORIES_STR,
					WpSolrSchema::_FIELD_NAME_AUTHOR,
					'*' . WpSolrSchema::_FIELD_NAME_POST_THUMBNAIL_HREF_STR,
					'*' . WpSolrSchema::_FIELD_NAME_POST_HREF_STR,
				),
				$wpsolr_query,
				$this
			)
		);
	}

	/**
	 * Escape special characters in a query keywords.
	 *
	 * @param string $keywords
	 *
	 * @return string
	 */
	abstract protected function search_engine_client_escape_term( $keywords );

	/**
	 * Set keywords of a query select.
	 *
	 * @param string $keywords
	 *
	 */
	abstract protected function search_engine_client_set_query_keywords( $keywords );


	/**
	 * Replace default query field by query fields, with their eventual boost.
	 *
	 * @param array $query_fields
	 *
	 */
	abstract protected function search_engine_client_set_query_fields( array $query_fields );

	/**
	 * Set boosts field values.
	 *
	 * @param string $boost_field_values
	 *
	 */
	abstract protected function search_engine_client_set_boost_field_values( $boost_field_values );

	/**
	 * Set the query keywords.
	 *
	 * @param string $keywords
	 */
	private
	function set_keywords(
		$keywords
	) {

		$query_field_name = '';

		$keywords = trim( $keywords );

		// Escape special terms causing errors.
		$keywords = $this->search_engine_client_escape_term( $keywords );

		if ( ! WPSOLR_Global::getOption()->get_search_fields_is_active() ) {

			// No search fields selected, use the default search field
			$query_field_name = WpSolrSchema::_FIELD_NAME_DEFAULT_QUERY . ':';

		} else {

			/// Use search fields with their boost defined in qf instead of default field 'text'
			$query_fields = $this->get_query_fields();
			if ( ! empty( $query_fields ) ) {

				$this->search_engine_client_set_query_fields( $query_fields );
			}

			/// Add boosts on field values
			$boost_field_values = $this->get_query_boosts_fields();
			if ( ! empty( $boost_field_values ) ) {

				$this->search_engine_client_set_boost_field_values( $boost_field_values );
			}
		}


		if ( ! empty( $keywords ) ) {
			if ( WPSOLR_Global::getOption()->get_search_is_partial_matches() ) {

				$partial_keywords = '';
				foreach ( explode( ' ', $keywords ) as $word ) {
					$partial_keywords .= sprintf( ' (%s OR %s*)', $word, $word );
				}

				$keywords = $partial_keywords;

				// Use 'OR' to ensure results include the exact keywords also (not only beginning with keywords) if there is one word only
				//$keywords = sprintf( '(%s) OR (%s)', $keywords, $keywords1 );

			} elseif ( WPSOLR_Global::getOption()->get_search_is_fuzzy_matches() ) {

				$keywords = preg_replace( '/(\S+)/i', '$1~1', $keywords ); // keyword => keyword~1
			}
		}

		$this->is_query_wildcard = ( empty( $keywords ) || ( '*' === $keywords ) );

		// Escape Solr special caracters
		$keywords = $this->escape_solr_special_catacters( $keywords );

		$this->search_engine_client_set_query_keywords( sprintf( '%s(%s)', $query_field_name, ( ! $this->is_query_wildcard ? $keywords : '*' ) ) );
	}


	/**
	 * Build a query with boosts values
	 *
	 * @return string
	 */
	private
	function get_query_boosts_fields() {

		$option_search_fields_terms_boosts = WPSOLR_Global::getOption()->get_search_fields_terms_boosts();

		$query_boost_str = '';
		foreach ( $option_search_fields_terms_boosts as $search_field_name => $search_field_term_boost_lines ) {

			$search_field_term_boost_lines = trim( $search_field_term_boost_lines );

			if ( ! empty( $search_field_term_boost_lines ) ) {

				if ( WpSolrSchema::_FIELD_NAME_CATEGORIES === $search_field_name ) {

					// Field 'categories' are now treated as other fields (dynamic string type)
					$search_field_name = WpSolrSchema::_FIELD_NAME_CATEGORIES_STR;
				}

				foreach ( preg_split( "/(\r\n|\n|\r)/", $search_field_term_boost_lines ) as $search_field_term_boost_line ) {

					// Transform apache solr^2 in "apache solr"^2
					$search_field_term_boost_line = preg_replace( "/(.*)\^(.*)/", '"$1"^$2', $search_field_term_boost_line );

					// Add field and it's boost term value.
					$query_boost_str .= sprintf( ' %s:%s ', $search_field_name, $search_field_term_boost_line );
				}

			}
		}

		$query_boost_str = trim( $query_boost_str );

		return $query_boost_str;
	}

	/**
	 * Build a query fields with boosts
	 *
	 * @return array
	 */
	private
	function get_query_fields() {

		$option_search_fields_boosts = WPSOLR_Global::getOption()->get_search_fields_boosts();


		// Build a query fields with boosts
		$query_fields = [];
		foreach ( $option_search_fields_boosts as $search_field_name => $search_field_boost ) {

			if ( WpSolrSchema::_FIELD_NAME_CATEGORIES === $search_field_name ) {

				// Field 'categories' are now treated as other fields (dynamic string type)
				$search_field_name = WpSolrSchema::_FIELD_NAME_CATEGORIES_STR;
			}

			if ( '1' === $search_field_boost ) {

				// Boost of '1' is a default value. No need to add it with it's field.
				$query_fields[] = trim( sprintf( ' %s ', $search_field_name ) );

			} else {

				// Add field and it's (non default) boost value.
				$query_fields[] = trim( sprintf( ' %s^%s ', $search_field_name, $search_field_boost ) );
			}
		}

		return $query_fields;
	}

	/**
	 * Is a facet sorted alphabetically
	 *
	 * @param $facet_name
	 *
	 * @return bool
	 */
	private
	function is_facet_sorted_alphabetically(
		$facet_name
	) {

		$facets_sort = WPSOLR_Global::getOption()->get_facets_sort();

		return ! empty( $facets_sort ) && ! empty( $facets_sort[ $facet_name ] );
	}

	/**
	 * Is a facet exclusion
	 *
	 * @param $facet_name
	 *
	 * @return bool
	 */
	private
	function is_facet_exclusion(
		$facet_name
	) {

		$facets_exclusion = WPSOLR_Global::getOption()->get_facets_is_exclusion();

		return ! empty( $facets_exclusion ) && ! empty( $facets_exclusion[ $facet_name ] );
	}

	/**
	 * Is a facet 'OR'
	 *
	 * @param $facet_name
	 *
	 * @return bool
	 */
	private
	function get_facet_is_or(
		$facet_name
	) {

		$facets_is_or = WPSOLR_Global::getOption()->get_facets_is_or();

		return ! empty( $facets_is_or ) && ! empty( $facets_is_or[ $facet_name ] );
	}


	/**
	 * Does a facet has to be shown as a hierarchy
	 *
	 * @param $facet_name
	 *
	 * @return bool
	 */
	private
	function is_facet_to_show_as_a_hierarchy(
		$facet_name
	) {

		$facets_to_show_as_a_hierarchy = WPSOLR_Global::getOption()->get_facets_to_show_as_hierarchy();

		return ! empty( $facets_to_show_as_a_hierarchy ) && ! empty( $facets_to_show_as_a_hierarchy[ $facet_name ] );
	}

	/**
	 * Get a facet name if it's hierarchy (or not)
	 *
	 * @param $facet_name
	 *
	 * @return string Facet name with hierarch or not
	 */
	public
	function get_facet_hierarchy_name(
		$hierarchy_field_name, $facet_name
	) {

		$facet_name   = strtolower( str_replace( ' ', '_', $facet_name ) );
		$is_hierarchy = $this->is_facet_to_show_as_a_hierarchy( $facet_name );

		if ( WpSolrSchema::_FIELD_NAME_CATEGORIES === $facet_name ) {

			// Field 'categories' are now treated as other fields (dynamic string type)
			$facet_name = WpSolrSchema::_FIELD_NAME_CATEGORIES_STR;
		}

		$result = $is_hierarchy ? sprintf( $hierarchy_field_name, $facet_name ) : $facet_name;

		return $result;
	}

	/**
	 * Retrieve a post thumbnail, from local database, or from the index content.
	 *
	 * @param mixed $document document
	 * @param $post_id
	 *
	 * @return array|false
	 */
	private
	function get_post_thumbnail(
		$document, $post_id
	) {

		if ( $this->is_galaxy_master ) {

			// Master sites must get thumbnails from the index, as the $post_id is not in local database
			$results = $document->post_thumbnail_href_str;

		} else {

			// $post_id is in local database, use the standard way
			$results = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ) );
		}

		return ! empty( $results ) ? $results[0] : null;
	}

	/**
	 * Retrieve a post url, from local database, or from the index content.
	 *
	 * @param mixed $document document
	 * @param $post_id
	 *
	 * @return string
	 */
	private
	function get_post_url(
		$document, $post_id
	) {

		if ( $this->is_galaxy_master ) {

			// Master sites must get thumbnails from the index, as the $post_id is not in local database
			$result = ! empty( $document->post_href_str ) ? $document->post_href_str[0] : null;

		} else {

			// $post_id is in local database, use the standard way
			$result = get_permalink( $post_id );
		}

		return $result;
	}

	/**
	 * Return posts from Solr results post PIDs
	 *
	 * @param $posts_ids
	 *
	 * @return WP_Post[]
	 */
	public
	function get_posts_from_pids() {

		if ( $this->results->get_nb_results() === 0 ) {
			return [ 'posts' => [], 'documents' => [] ];
		}

		// Fetch all posts from the documents ids, in ONE call.
		if ( ! $this->is_galaxy_master ) {
			// Local search: return posts from local database

			$posts_ids = [];
			foreach ( $this->results->get_results() as $document ) {
				$posts_ids[ $document->PID ] = $document;
			}

			if ( empty( $posts_ids ) ) {
				return [];
			}

			$indexed_post_types = WPSOLR_Global::getOption()->get_option_index_post_types();
			array_push( $indexed_post_types, 'attachment' ); // Insure attachments are also returned.
			$posts = get_posts( [
				'numberposts' => count( $posts_ids ),
				'post_type'   => $indexed_post_types,
				'post_status' => 'any',
				'post__in'    => array_keys( $posts_ids ),
				'orderby'     => 'post__in',
				// Get posts in same order as documents in Solr results.
			] );

			$results = [ 'posts' => [], 'documents' => [] ];
			foreach ( $posts as $post ) {
				array_push( $results['posts'], $post );
				array_push( $results['documents'], $posts_ids[ $post->ID ] );
			}

			return $results;
		}

		// Create pseudo posts from Solr results
		$results = [ 'posts' => [], 'documents' => [] ];
		foreach ( $this->results as $document ) {

			unset( $current_post );
			$current_post         = new stdClass();
			$current_post->ID     = $document->id;
			$current_post->filter = 'raw';

			$wp_post = new WP_Post( $current_post );

			array_push( $results['posts'], $wp_post );
			array_push( $results['documents'], $document );
		}

		return $results;
	}


	/**
	 * Generate a distance query for a field, and name the query
	 *
	 * @param $field_prefix
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 * @return string
	 *
	 */
	abstract public function get_named_geodistance_query_for_field( $field_prefix, $field_name, $geo_latitude, $geo_longitude );

	/**
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function get_facet_type( $facet_name ) {

		return apply_filters( WpSolrFilters::WPSOLR_FILTER_FACET_TYPE, WPSOLR_Option::OPTION_FACET_FACETS_TYPE_FIELD, $facet_name );
	}

	/**
	 * Remove extra 0 decimal.
	 * 5.1 => 5.1, 5.0 => 5, * => *
	 *
	 * @param string $value
	 *
	 * @return int|float|string
	 */
	public function remove_empty_decimal( $value ) {

		return is_numeric( $value ) ? $value + 0 : $value;
	}

	/**
	 * Remove extra 0 decimal pf a range.
	 * 5.1-10.2 => 5.1-10.2, 5.0-10.0 => 5-0, 5.0-* => 5-*
	 *
	 * @param string $range
	 *
	 * @return string
	 */
	public function remove_range_empty_decimal( $range ) {

		if ( false === strpos( $range, '-' ) ) {
			return $this->remove_empty_decimal( $range );
		}

		$ranges = explode( '-', $range );

		return sprintf( '%s-%s', $this->remove_empty_decimal( $ranges[0] ), $this->remove_empty_decimal( $ranges[1] ) );
	}
}

