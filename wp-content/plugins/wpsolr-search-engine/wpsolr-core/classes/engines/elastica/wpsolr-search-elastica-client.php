<?php

require_once plugin_dir_path( __FILE__ ) . '../wpsolr-abstract-search-client.php';
require_once plugin_dir_path( __FILE__ ) . 'wpsolr-elastica-client.php';

/**
 * Class WPSolrSearchElasticaClient
 *
 * @property \Elastica\Client $search_engine_client
 * @property \Elastica\Search $query_select
 */
class WPSolrSearchElasticaClient extends WPSolrAbstractSearchClient {
	use WPSolrElasticaClient;

	// Scripts in painless: https://www.elastic.co/guide/en/elasticsearch/reference/current/modules-scripting-painless-syntax.html
	const SCRIPT_LANGUAGE_PAINLESS = 'painless';
	const SCRIPT_PAINLESS_DISTANCE = 'doc[params.field].empty ? params.empty_value : doc[params.field].planeDistance(params.lat,params.lon)*0.001';

	// Index analysis conf file
	const FILE_CONF_INDEX_ANALYSIS = 'wpsolr_index_analysis_5.0.json';
	const FILE_CONF_TYPE_MAPPING = 'wpsolr_type_mapping_5.0.json';

	const ELASTICA_MAPPING_FIELD_DYNAMIC = 'dynamic';
	const ELASTICA_MAPPING_VALUE_DYNAMIC_STRICT = 'strict';
	const ELASTICA_MAPPING_FIELD_DYNAMIC_TEMPLATES = 'dynamic_templates';
	const ELASTICA_MAPPING_FIELD_PROPERTIES = 'properties';
	const ELASTICA_MAPPING_FIELD_PROPERTIES_ATTACHMENT = 'properties_attachment';

	const FIELD_SEARCH_AUTO_COMPLETE = 'autocomplete';
	const FIELD_SEARCH_SPELL = 'spell';
	const SUGGESTER_NAME = 'wpsolr_spellcheck';

	/* @var \Elastica\Query $query */
	protected $query;

	// https://www.elastic.co/guide/en/elasticsearch/reference/5.2/query-dsl-query-string-query.html
	/* @var \Elastica\Query\QueryString $query_string */
	protected $query_string;

	/* @var \Elastica\Query\BoolQuery[] $query_filters */
	protected $query_filters;

	/* @var \Elastica\Query\BoolQuery[] $query_post_filters */
	protected $query_post_filters;

	/* @var \Elastica\Script\ScriptFields $query_script_fields */
	protected $query_script_fields;

	/* @var \Elastica\Aggregation\Terms $facets_terms */
	protected $facets_terms;

	/* @var \Elastica\Aggregation\Range $facets_ranges */
	protected $facets_ranges;

	/* @var \Elastica\Suggest\Completion $completion */
	protected $completion;

	/* @var bool $is_did_you_mean */
	protected $is_did_you_mean = false;

	/* @var bool $is_query_built */
	protected $is_query_built = false;

	/* @var string $boost_field_values */
	protected $boost_field_values;

	/**
	 * Execute an update query with the client.
	 *
	 * @param \Elastica\Client $search_engine_client
	 * @param \Elastica\Search $query
	 *
	 * @return WPSolrResultsElasticaClient
	 */
	public function search_engine_client_execute( $search_engine_client, $query ) {

		$this->search_engine_client_build_query();

		$search = new \Elastica\Search( $this->search_engine_client );
		$search->addIndex( $this->get_elastica_index() );
		$search->addType( $this->get_elastica_type() );
		$search->setQuery( isset( $this->completion ) ? $this->completion : $this->query );

		return new WPSolrResultsElasticaClient( $search->search() );
	}


	/**
	 * Build the query.
	 *
	 */
	public function search_engine_client_build_query() {

		if ( $this->is_query_built ) {
			// Already done.
			return;
		}

		$this->is_query_built = true;

		if ( ! isset( $this->completion ) ) {
			// Normal search.

			if ( $this->is_did_you_mean ) {
				// Add a phrase suggester on keywords.

				$keywords = $this->query_string->getParam( 'query' );
				$keywords = preg_replace( '/(.*):/', '', $keywords ); // keyword => keyword, text:keyword => keyword

				if ( ! empty( $keywords ) && ! strpos( $keywords, '*' ) && WPSOLR_Global::getOption()->get_search_is_did_you_mean() ) {
					// Add did you mean if the keywords are not empty or wilcard
					$suggest_phrase = new Elastica\Suggest\Phrase( self::SUGGESTER_NAME, self::FIELD_SEARCH_SPELL );
					$suggest_phrase->setText( $keywords );
					$suggest_phrase->setSize( 1 ); // First suggestion is enough.
					$suggest = new \Elastica\Suggest();
					$suggest->addSuggestion( $suggest_phrase );
					$this->query->setSuggest( $suggest );
				}
			}

			if ( isset( $this->query_filters ) ) {

				// Only way to get facets correctly with filters: a bool query.
				// https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-filtered-query.html
				$query_filters = new \Elastica\Query\BoolQuery();

				foreach ( $this->query_filters as $query_filter ) {
					$query_filters->addMust( $query_filter );
				}

				$this->query->setQuery( ( new \Elastica\Query\BoolQuery() )->addMust( $this->query_string )->addFilter( $query_filters ) );

			} else {

				// No filters.
				$this->query->setQuery( $this->query_string );

			}

			if ( isset( $this->query_post_filters ) ) {

				$query_post_filters = new \Elastica\Query\BoolQuery();

				foreach ( $this->query_post_filters as $query_post_filter ) {
					$query_post_filters->addMust( $query_post_filter );
				}

				$this->query->setPostFilter( $query_post_filters );
			}

			// Add script fields (for geo distance fields)
			if ( isset( $this->query_script_fields ) ) {

				$this->query->setScriptFields( $this->query_script_fields );
			}

			// Add facets
			if ( isset( $this->facets_terms ) ) {
				foreach ( $this->facets_terms as $facet_term ) {
					$this->query->addAggregation( $facet_term );
				}
			}
		}

	}

	/**
	 * Ping the index.
	 * Create the index if not existing.
	 * Update the index with wpsolr mapping.
	 *
	 */
	public function ping() {

		if ( ! $this->elastica_index_exists() ) {
			// Create the index
			$this->get_elastica_index()->create( $this->get_and_decode_configuration_file( self::FILE_CONF_INDEX_ANALYSIS ), false );
		}

		$this->index_add_mapping_if_missing();
		//$this->index_add_analysis_if_missing();

		// Will throw an error if index does not exist or server cannot be reached.
		$this->elastica_get_index_stats();
	}


	/**
	 * Load the content of a conf file.
	 *
	 * @param string $file
	 *
	 * @return array
	 */
	protected function get_and_decode_configuration_file( $file ) {

		$file_json = file_get_contents( plugin_dir_path( __FILE__ ) . $file );

		return json_decode( $file_json, true );
	}

	/**
	 * Add a mapping to the index if missing.
	 */
	protected function index_add_mapping_if_missing() {

		$elastica_type         = $this->get_elastica_type();
		$elastica_type_mapping = $elastica_type->getMapping();

		if ( empty( $elastica_type_mapping ) ) {

			/**
			 * Define mapping for regular wpsolr data.
			 */
			$mapping = new \Elastica\Type\Mapping();
			$mapping->setType( $elastica_type );

			$mapping_type = $this->get_and_decode_configuration_file( self::FILE_CONF_TYPE_MAPPING );

			// No dynamic field types. Too unpredictable.
			// https://www.elastic.co/guide/en/elasticsearch/guide/current/dynamic-mapping.html
			// $mapping->setParam( self::ELASTICA_MAPPING_FIELD_DYNAMIC, false ); does not work

			// Set dynamic templates for dynamic field types like '%_s'
			$mapping->setParam( self::ELASTICA_MAPPING_FIELD_DYNAMIC_TEMPLATES,
				$mapping_type[ self::ELASTICA_MAPPING_FIELD_DYNAMIC_TEMPLATES ] // defined in include
			);

			// Set properties for field types and analysers
			$mapping->setProperties(
				$mapping_type[ self::ELASTICA_MAPPING_FIELD_PROPERTIES ] // defined in include
			);

			// Send mapping to type
			$mapping->send();

			/**
			 * Define mapping for the attachment file (so it's never in any search results).
			 *
			 */
			$mapping = new \Elastica\Type\Mapping();
			$mapping->setType( $this->get_elastica_type_attachment() );
			$mapping->setSource( [ 'excludes' => [ 'file' ] ] );

			// Set properties for field types and analysers
			$mapping->setProperties(
				$mapping_type[ self::ELASTICA_MAPPING_FIELD_PROPERTIES_ATTACHMENT ]
			);

			// Send mapping to type
			$mapping->send();
		}

	}

	/**
	 * Create a query select.
	 *
	 * @return \Elastica\Query
	 */
	protected function search_engine_client_create_query_select() {

		$this->query = new \Elastica\Query();

		$this->query_string = new \Elastica\Query\QueryString();

		return $this->query;
	}

	/**
	 * Escape special characters in a query keywords.
	 *
	 * @param string $keywords
	 *
	 * @return string
	 */
	protected function search_engine_client_escape_term( $keywords ) {
		return \Elastica\Util::escapeTerm( $keywords );
	}

	/**
	 * Set keywords of a query select.
	 *
	 * @return string
	 */
	protected function search_engine_client_set_query_keywords( $keywords ) {

		$formated_query = $keywords . ( isset( $this->boost_field_values ) ? ( ' ' . $this->boost_field_values ) : '' );

		$this->query_string->setQuery( $formated_query );
	}

	/**
	 * Set query's default operator.
	 *
	 * @param string $operator
	 */
	protected function search_engine_client_set_default_operator( $operator = 'AND' ) {
		$this->query_string->setDefaultOperator( $operator );
	}

	/**
	 * Set query's start.
	 *
	 * @param int $start
	 *
	 */
	protected function search_engine_client_set_start( $start ) {
		$this->query->setFrom( $start );
	}

	/**
	 * Set query's rows.
	 *
	 * @param int $rows
	 *
	 */
	protected function search_engine_client_set_rows( $rows ) {
		$this->query->setSize( $rows );
	}

	/**
	 * Add a sort to the query
	 *
	 * @param string $sort
	 * @param string $sort_by
	 *
	 */
	public function search_engine_client_add_sort( $sort, $sort_by ) {
		$this->query->addSort( [ $sort => [ 'order' => $sort_by ] ] );
	}

	/**
	 * Add a simple filter term.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param $facet_is_or
	 * @param string $field_value
	 * @param string $filter_tag
	 */
	public function search_engine_client_add_filter_term( $filter_name, $field_name, $facet_is_or, $field_value, $filter_tag = '' ) {

		$term = new \Elastica\Query\Term();
		$term->setTerm( $field_name, $field_value );

		$this->search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, $term, $filter_tag );
	}

	/**
	 * Add a negative filter on terms.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @param string $filter_tag
	 *
	 * @internal param array $facet_is_or
	 */
	public
	function search_engine_client_add_filter_not_in_terms(
		$filter_name, $field_name, $field_values, $filter_tag = ''
	) {

		// https://www.elastic.co/guide/en/elasticsearch/reference/5.2/query-dsl-terms-query.html
		$terms = new \Elastica\Query\Terms();
		$terms->setTerms( $field_name, $field_values );

		if ( ! isset( $this->query_filters[ $field_name ] ) ) {
			$this->query_filters[ $field_name ] = new \Elastica\Query\BoolQuery();
		}

		$this->query_filters[ $field_name ]->addMustNot( $terms );
	}

	/**
	 * Add a filter on: empty or in terms.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 * @param string $filter_tag
	 *
	 */
	public function search_engine_client_add_filter_empty_or_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' ) {

		if ( ! isset( $this->query_filters[ $field_name ] ) ) {
			$this->query_filters[ $field_name ] = new \Elastica\Query\BoolQuery();
		}

		// 'IN' terms
		$in_terms = ( new \Elastica\Query\Terms() )->setTerms( $field_name, $field_values );

		// 'empty': not exists
		$empty = ( new \Elastica\Query\BoolQuery() )->addMustNot( new \Elastica\Query\Exists( $field_name ) );

		// 'empty' OR 'IN'
		$this->query_filters[ $field_name ]->addMust( ( new \Elastica\Query\BoolQuery() )->addShould( [
			$empty,
			$in_terms
		] ) );
	}

	/**
	 * Filter fields with values
	 *
	 * @param $filter_name
	 * @param $field_name
	 */
	public function search_engine_client_add_filter_exists( $filter_name, $field_name ) {

		if ( ! isset( $this->query_filters[ $field_name ] ) ) {
			$this->query_filters[ $field_name ] = new \Elastica\Query\BoolQuery();
		}

		// Add 'exists'
		$this->query_filters[ $field_name ]->addMust( new \Elastica\Query\Exists( $field_name ) );
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
	protected
	function search_engine_client_set_highlighting(
		$field_names, $prefix, $postfix, $fragment_size
	) {

		// https://www.elastic.co/guide/en/elasticsearch/reference/5.2/search-request-highlighting.html#_highlight_query
		// https://github.com/ruflin/Elastica/blob/4666078db27d2574171c9fe1bba5d7782b2ae7cf/test/lib/Elastica/Test/Query/HighlightTest.php

		$fields = [];

		// Create an array with elastica's format.
		foreach ( $field_names as $field_name ) {

			$fields[ $field_name ] = [
				'fragment_size'       => $fragment_size,
				'number_of_fragments' => 1,
			];
		}

		$this->query->setHighlight(
			[
				'require_field_match' => false,
				// Show highlighted fields even if they are not part of the query.
				'pre_tags'            => [ $prefix ],
				'post_tags'           => [ $postfix ],
				'fields'              => $fields,
			]
		);


	}

	/**
	 * Get facet terms.
	 *
	 * @return \Elastica\Aggregation\Terms
	 */
	protected
	function get_or_create_facets_field(
		$facet_name
	) {
		if ( ! isset( $this->facets_terms ) ) {

			$this->facets_terms = [];
		}

		if ( isset( $this->facets_terms[ $facet_name ] ) ) {
			return $this->facets_terms[ $facet_name ];
		}

		// Not found. Create the facet.
		$facet = new \Elastica\Aggregation\Terms( $facet_name );
		$facet->setField( $facet_name );


		// Create a filter for the facet. It will be overriden with an exclusion filter if necessary.
		$agg_filter = new Elastica\Aggregation\Filter( $facet_name, new \Elastica\Query\MatchAll() );
		$agg_filter->addAggregation( $facet );

		$this->facets_terms[] = $agg_filter;

		return $facet;
	}


	/**
	 * Set minimum count of facet items to retrieve a facet.
	 *
	 * @param $min_count
	 *
	 */
	protected
	function search_engine_client_set_facets_min_count(
		$facet_name, $min_count
	) {

		$this->get_or_create_facets_field( $facet_name )->setMinimumDocumentCount( $min_count );
	}

	/**
	 * Create a facet field.
	 *
	 * @param $facet_name
	 * @param $field_name
	 *
	 * @return array
	 * @internal param $exclusion
	 *
	 */
	protected
	function search_engine_client_add_facet_field(
		$facet_name, $field_name
	) {

		$this->get_or_create_facets_field( $field_name );
	}

	/**
	 * Set facets limit.
	 *
	 * @param int $limit
	 *
	 */
	protected
	function search_engine_client_set_facets_limit(
		$facet_name, $limit
	) {
		$this->get_or_create_facets_field( $facet_name )->setSize( $limit );
	}

	/**
	 * Sort a facet field alphabetically.
	 *
	 * @param $facet_name
	 *
	 */
	protected
	function search_engine_client_set_facet_sort_alphabetical(
		$facet_name
	) {

		/** @var \Elastica\ $facet */
		$this->get_or_create_facets_field( $facet_name )->setOrder( '_term', 'asc' );

	}

	/**
	 * Set facet field excludes.
	 *
	 * @param string $facet_name
	 * @param string $exclude
	 *
	 */
	protected
	function search_engine_client_set_facet_excludes(
		$facet_name, $exclude
	) {

		/**
		 * Nothing done here.
		 * Done in the facet, and in the filter.
		 *
		 * - Excluded terms are put in the post_filter, and are added as filter to each facet not excluded.
		 */

	}

	/**
	 * Set the fields to be returned by the query.
	 *
	 * @param array $fields
	 *
	 */
	protected
	function search_engine_client_set_fields(
		$fields
	) {
		$this->query->setSource( $fields );
	}

	/**
	 * Get suggestions from the engine.
	 *
	 * @param $query
	 *
	 * @return WPSolrResultsElasticaClient
	 */
	protected function search_engine_client_get_suggestions_keywords( $query ) {

		$this->completion = new \Elastica\Suggest\Completion( self::SUGGESTER_NAME, self::FIELD_SEARCH_AUTO_COMPLETE );
		$this->completion->setText( $query );
		$this->completion->setSize( 5 );

		/*
				$suggest_phrase = new Elastica\Suggest\Phrase( self::SUGGESTER_NAME, self::FIELD_SEARCH_SPELL );
				$suggest_phrase->setText( $query );
				$suggest_phrase->setSize( 5 ); // First suggestions is enough.
				$this->completion = new \Elastica\Suggest();
				$this->completion->addSuggestion( $suggest_phrase );
		*/

		return $this->search_engine_client_execute( $this->search_engine_client, null );
	}


	/**
	 * Get suggestions for did you mean.
	 *
	 * @param string $keywords
	 *
	 * @return string Did you mean keyword
	 */
	protected function search_engine_client_get_did_you_mean_suggestions( $keywords ) {

		$this->is_did_you_mean = true;

		$results = $this->search_engine_client_execute( $this->search_engine_client, null );

		$suggestions = $results->get_suggestions();

		return ! empty( $suggestions ) ? $suggestions[0]['text'] : '';
	}


	/**
	 * Add a geo distance sort.
	 * The field is already in the sorts. It will be replaced with geo sort specific syntax.
	 *
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 */
	public function search_engine_client_add_sort_geolocation_distance( $field_name, $geo_latitude, $geo_longitude ) {

		// http://elastica.io/example/sort/sort.html

		$sorts = $this->query->getParam( 'sort' );
		if ( ! empty( $sorts ) ) {

			foreach ( $sorts as $position => &$sort_item ) {

				if ( ! empty( $sort_item[ $field_name ] ) ) {

					// Replace geo sort
					$sort_item = [
						'_geo_distance' => [
							$field_name     => [ 'lat' => $geo_latitude, 'lon' => $geo_longitude ],
							'order'         => $sort_item[ $field_name ]['order'],
							'unit'          => 'km',
							'mode'          => 'min',
							'distance_type' => 'plane',
						],
					];

					// Replace sorts.
					$this->query->setSort( $sorts );

					// sort found and replaced.
					break;
				}
			}
		}

	}

	/**
	 * Generate a distance script for a field, and name the query
	 *
	 * @param $field_prefix
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 * @return string
	 *
	 */
	public function get_named_geodistance_query_for_field( $field_prefix, $field_name, $geo_latitude, $geo_longitude ) {

		if ( ! isset( $this->query_script_fields ) ) {
			$this->query_script_fields = new \Elastica\Script\ScriptFields();
		}

		// Create the distance field name: field_name1_str => wpsolr_distance_field_name1
		$distance_field_name = $field_prefix . WPSOLR_Regexp::remove_string_at_the_end( $field_name, WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING );

		// Add distance field script to field scripts
		$params = [
			'field'       => WpSolrSchema::replace_field_name_extension( $field_name ),
			// field_name1_str => field_name1_ll
			'empty_value' => 40000,
			'lat'         => floatval( $geo_latitude ),
			'lon'         => floatval( $geo_longitude ),
		];
		$script = new \Elastica\Script\Script( self::SCRIPT_PAINLESS_DISTANCE, $params, self::SCRIPT_LANGUAGE_PAINLESS );
		$this->query_script_fields->addScript( $distance_field_name, $script );

		return $distance_field_name;
	}

	/**
	 * Replace default query field by query fields, with their eventual boost.
	 *
	 * @param array $query_fields
	 *
	 */
	protected function search_engine_client_set_query_fields( array $query_fields ) {
		$this->query_string->setFields( $query_fields );
	}

	/**
	 * Set boosts field values.
	 *
	 * @param array $boost_field_values
	 *
	 */
	protected function search_engine_client_set_boost_field_values( $boost_field_values ) {
		// Store it. Will be added to the query later.
		$this->boost_field_values = $boost_field_values;

		// Do nothing. Cannot find a way to define boost values with boost fields.
	}


	/**
	 * Get facet terms.
	 *
	 * @param string $facet_name
	 * @param string $range_start
	 * @param string $range_end
	 * @param string $range_gap
	 *
	 * @return \Elastica\Aggregation\Range
	 */
	protected
	function get_or_create_facets_range(
		$facet_name, $range_start, $range_end, $range_gap
	) {
		if ( ! isset( $this->facets_ranges ) ) {

			$this->facets_ranges = [];
		}

		if ( isset( $this->facets_ranges[ $facet_name ] ) ) {
			return $this->facets_ranges[ $facet_name ];
		}

		// Not found. Create the facet.
		$facet = new \Elastica\Aggregation\Range( $facet_name );
		$facet->setField( $facet_name );

		// Add a range for values before start
		$facet->addRange( null, $range_start );

		// No gap parameter. We build the ranges manually.
		foreach ( range( $range_start, $range_end, $range_gap ) as $start ) {
			if ( $start < $range_end ) {
				$facet->addRange( $start, $start + $range_gap );
			}
		}

		// Add a range for values after end
		$facet->addRange( $range_end, null );

		// Create a filter for the facet. It will be overriden with an exclusion filter if necessary.
		$agg_filter = new Elastica\Aggregation\Filter( $facet_name, new \Elastica\Query\MatchAll() );
		$agg_filter->addAggregation( $facet );

		$this->facets_terms[] = $agg_filter;

		return $facet;
	}

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
	protected function search_engine_client_add_facet_range_regular( $facet_name, $field_name, $range_start, $range_end, $range_gap ) {

		$this->get_or_create_facets_range( $field_name, $range_start, $range_end, $range_gap );
	}

	/**
	 * Add a filter.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param bool $facet_is_or
	 * @param mixed $filter
	 * @param string $filter_tag
	 */
	public function search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, $filter, $filter_tag = '' ) {

		if ( empty( $filter_tag ) ) {

			// No Tag. Add to the query filters.
			if ( ! isset( $this->query_filters[ $field_name ] ) ) {
				$this->query_filters[ $field_name ] = new \Elastica\Query\BoolQuery();
			}

			if ( $facet_is_or ) {
				$this->query_filters[ $field_name ]->addShould( $filter );
			} else {
				$this->query_filters[ $field_name ]->addMust( $filter );
			}

		} else {

			// Tag. Add to the post query filters.
			if ( ! isset( $this->query_post_filters[ $field_name ] ) ) {
				$this->query_post_filters[ $field_name ] = new \Elastica\Query\BoolQuery();
			}

			if ( $facet_is_or ) {
				$this->query_post_filters[ $field_name ]->addShould( $filter );
			} else {
				$this->query_post_filters[ $field_name ]->addMust( $filter );
			}

			// Add exclusion filter to all facets, but the excluded.
			if ( ! empty( $this->facets_terms ) ) {
				foreach ( $this->facets_terms as $facet_filter ) {
					/** @var Elastica\Aggregation\Filter $facet_filter */

					if ( $field_name !== $facet_filter->getName() ) {
						$facet_filter->setFilter( $filter );
					}
				}
			}
		}
	}

	/**
	 * Add a simple filter range.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param string $facet_is_or
	 * @param string $range_start
	 * @param string $range_end
	 * @param string $filter_tag
	 */
	public function search_engine_client_add_filter_range( $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $filter_tag = '' ) {

		$range = new \Elastica\Query\Range();

		$range_values = [];
		if ( '*' !== $range_start ) {
			$range_values['from'] = $range_start;
		}
		if ( '*' !== $range_end ) {
			$range_values['to'] = $range_end;
		}

		$range->addField( $field_name, $range_values );

		$this->search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, $range, $filter_tag );
	}
}
