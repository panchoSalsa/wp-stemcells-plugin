<?php

require_once plugin_dir_path( __FILE__ ) . '../wpsolr-abstract-search-client.php';
require_once plugin_dir_path( __FILE__ ) . 'wpsolr-solr-client.php';

/**
 * Class WPSolrSearchSolrClient
 *
 * @property \Solarium\QueryType\Select\Query\Query $query_select
 * @property \Solarium\Client $search_engine_client
 */
class WPSolrSearchSolrClient extends WPSolrAbstractSearchClient {
	use WPSolrSolrClient;

	// Constants for filter patterns
	const FILTER_PATTERN_EMPTY_OR_IN = '(*:* -%s:[* TO *]) OR %s:(%s)';
	const FILTER_PATTERN_EXISTS = '%s:*';

	// Template for the geolocation distance field(s)
	const TEMPLATE_NAMED_GEODISTANCE_QUERY_FOR_FIELD = '%s%s:%s';

	// Function to calculate distance
	const GEO_DISTANCE = 'geodist()';

	// Template for the geolocation distance sort field(s)
	const TEMPLATE_ANONYMOUS_GEODISTANCE_QUERY_FOR_FIELD = 'geodist(%s,%s,%s)'; // geodist between field and 'lat,long'

	/* @var string[] $filter_queries_or */
	protected $filter_queries_or;

	/**
	 * Prepare query execute
	 */
	public function search_engine_client_pre_execute() {

		if ( ! empty( $this->filter_queries_or ) ) {

			foreach ( $this->filter_queries_or as $field_name => $filter_query_or ) {

				$this->query_select->addFilterQuery(
					[
						'key'   => $field_name,
						'query' => $filter_query_or['query'],
						'tag'   => $filter_query_or['tag'],
					]
				);
			}

			// Used: clear it.
			$this->filter_queries_or = [];
		}

	}


	/**
	 * Ping the index
	 *
	 * @return \Solarium\QueryType\Ping\Result
	 */
	public function ping() {

		return $this->search_engine_client->ping( $this->search_engine_client->createPing() );
	}

	/**
	 * Create a select query.
	 *
	 * @return \Solarium\QueryType\Select\Query\Query
	 */
	protected function search_engine_client_create_query_select() {
		return $this->search_engine_client->createSelect();
	}

	/**
	 * Escape special characters in a query keywords.
	 *
	 * @param string $keywords
	 *
	 * @return string
	 */
	protected function search_engine_client_escape_term( $keywords ) {
		return $this->query_select->getHelper()->escapeTerm( $keywords );
	}

	/**
	 * Set keywords of a query select.
	 *
	 * @return string
	 */
	protected function search_engine_client_set_query_keywords( $keywords ) {
		$this->query_select->setQuery( $keywords );
	}

	/**
	 * Set query's default operator.
	 *
	 * @param string $operator
	 */
	protected function search_engine_client_set_default_operator( $operator = 'AND' ) {
		$this->query_select->setQueryDefaultOperator( $operator );
	}

	/**
	 * Set query's start.
	 *
	 * @param int $start
	 *
	 */
	protected function search_engine_client_set_start( $start ) {
		$this->query_select->setStart( $start );
	}

	/**
	 * Set query's rows.
	 *
	 * @param int $rows
	 *
	 */
	protected function search_engine_client_set_rows( $rows ) {
		$this->query_select->setRows( $rows );
	}

	/**
	 * Add a sort to the query
	 *
	 * @param string $sort
	 * @param string $sort_by
	 *
	 */
	public function search_engine_client_add_sort( $sort, $sort_by ) {
		$this->query_select->addSort( $sort, $sort_by );
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

		// In case the facet contains white space, we enclose it with "".
		$field_value_escaped = "\"$field_value\"";

		$this->search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, "$field_name:$field_value_escaped", $filter_tag );
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
	public function search_engine_client_add_filter_not_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' ) {

		$this->query_select->addFilterQuery(
			[
				'key'   => $filter_name,
				'query' => sprintf( '-%s:(%s)', $field_name, implode( ' OR ', $field_values ) ),
			]
		);

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

		$this->query_select->addFilterQuery(
			[
				'key'   => $filter_name,
				'query' => sprintf( self::FILTER_PATTERN_EMPTY_OR_IN, $field_name, $field_name, implode( ' OR ', $field_values ) ),
			]
		);
	}

	/**
	 * Filter fields with values
	 *
	 * @param $filter_name
	 * @param $field_name
	 */
	public function search_engine_client_add_filter_exists( $filter_name, $field_name ) {

		$this->query_select->addFilterQuery(
			[
				'key'   => $filter_name,
				'query' => sprintf( self::FILTER_PATTERN_EXISTS, $field_name ),
			]
		);
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
	protected function search_engine_client_set_highlighting( $field_names, $prefix, $postfix, $fragment_size ) {

		$highlighting = $this->query_select->getHighlighting();

		foreach ( $field_names as $field_name ) {

			$highlighting->getField( $field_name )->setSimplePrefix( $prefix )->setSimplePostfix( $postfix );

			// Max size of each highlighting fragment for post content
			$highlighting->getField( $field_name )->setFragSize( $fragment_size );
		}

	}


	/**
	 * Set minimum count of facet items to retrieve a facet.
	 *
	 * @param $min_count
	 *
	 */
	protected function search_engine_client_set_facets_min_count( $facet_name, $min_count ) {

		// Only display facets that contain data
		$this->query_select->getFacetSet()->setMinCount( $min_count );
	}

	/**
	 * Create a facet field.
	 *
	 * @param $facet_name
	 * @param $field_name
	 *
	 * @internal param $exclusion
	 */
	protected function search_engine_client_add_facet_field( $facet_name, $field_name ) {

		$this->query_select->getFacetSet()->createFacetField( "$facet_name" )->setField( "$field_name" );
	}

	/**
	 * Set facets limit.
	 *
	 * @param int $limit
	 *
	 */
	protected function search_engine_client_set_facets_limit( $facet_name, $limit ) {

		$this->query_select->getFacetSet()->setLimit( $limit );
	}

	/**
	 * @param string $facet_name
	 *
	 * @return null|\Solarium\QueryType\Select\Query\Component\Facet\AbstractFacet|\Solarium\QueryType\Select\Query\Component\Facet\Facet
	 */
	protected function get_facet( $facet_name ) {

		$facets = $this->query_select->getFacetSet()->getFacets();

		if ( ! empty( $facets[ $facet_name ] ) ) {
			return $facets[ $facet_name ];
		}

		return null;
	}

	/**
	 * Sort a facet field alphabetically.
	 *
	 * @param $facet_name
	 *
	 */
	protected function search_engine_client_set_facet_sort_alphabetical( $facet_name ) {

		/** @var \Solarium\QueryType\Select\Query\Component\Facet\Field $facet */
		$facet = $this->get_facet( $facet_name );

		if ( $facet ) {
			$facet->setSort( self::PARAMETER_FACET_SORT_ALPHABETICALLY );
		}

	}

	/**
	 * Set facet field excludes.
	 *
	 * @param string $facet_name
	 * @param string $exclude
	 *
	 */
	protected function search_engine_client_set_facet_excludes( $facet_name, $exclude ) {

		/** @var \Solarium\QueryType\Select\Query\Component\Facet\Field $facet */
		$facet = $this->get_facet( $facet_name );

		if ( $facet ) {
			$facet->setExcludes( [ sprintf( self::FILTER_QUERY_TAG_FACET_EXCLUSION, $exclude ) ] );
		}

	}

	/**
	 * Set the fields to be returned by the query.
	 *
	 * @param array $fields
	 *
	 */
	protected function search_engine_client_set_fields( $fields ) {
		$this->query_select->setFields( $fields );
	}

	/**
	 * Get suggestions from the engine.
	 *
	 * @param $query
	 *
	 * @return WPSolrResultsSolrClient
	 */
	protected function search_engine_client_get_suggestions_keywords( $query ) {

		$suggester_query = $this->search_engine_client->createSuggester();
		$suggester_query->setHandler( 'suggest' );
		$suggester_query->setDictionary( 'suggest' );
		$suggester_query->setQuery( $query );
		$suggester_query->setCount( 5 );
		$suggester_query->setCollate( true );
		$suggester_query->setOnlyMorePopular( true );

		return $this->search_engine_client_execute( $this->search_engine_client, $suggester_query );
	}

	/**
	 * Get suggestions for did you mean.
	 *
	 * @param string $keywords
	 *
	 * @return string Did you mean keyword
	 */
	protected function search_engine_client_get_did_you_mean_suggestions( $keywords ) {

		// Add spellcheck to current query
		$spell_check = $this->query_select->getSpellcheck();
		$spell_check->setCount( 10 );
		$spell_check->setCollate( true );
		$spell_check->setCollateExtendedResults( true );
		$spell_check->setExtendedResults( true );
		$spell_check->setQuery( $keywords ); // Mandatory for Solr >= 5.5

		// Excecute the query modified
		$result_set = $this->execute_query();

		// Parse spell check results
		$spell_check_results = $result_set->get_results()->getSpellcheck();

		$did_you_mean_keyword = ''; // original query

		if ( $spell_check_results && ! $spell_check_results->getCorrectlySpelled() ) {

			$collations = $spell_check_results->getCollations();
			foreach ( $collations as $collation ) {

				foreach ( $collation->getCorrections() as $input => $correction ) {
					$did_you_mean_keyword = str_replace( $input, is_array( $correction ) ? $correction[0] : $correction, $keywords );
					break;
				}
			}
		}

		return $did_you_mean_keyword;
	}

	/**
	 * Build the query
	 *
	 */
	public function search_engine_client_build_query() {
		// Nothing. Query is built incrementally.
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

		$sorts = $this->query_select->getSorts();
		if ( ! empty( $sorts ) && ! empty( $sorts[ $field_name ] ) ) {

			// Use the sort by distance
			$this->query_select->addSort( $this->get_anonymous_geodistance_query_for_field( $field_name, $geo_latitude, $geo_longitude ), $sorts[ $field_name ] );

			// Filter out results without coordinates
			/*
			 * does not work with some Solr versions
			 $solarium_query->addFilterQuery(
				array(
					'key'   => 'geo_exclude_empty',
					'query' => sprintf( '%s:[-90,-180 TO 90,180]', $sort_field_name ),
				)
			);*/

		}

		// Remove the field from the sorts, as we use a function instead,
		// or we do not use the field as sort because geolocation is missing.
		$this->query_select->removeSort( $field_name );

	}

	/**
	 * Generate a distance query for a field
	 * 'field_name1' => geodist(field_name1_ll, center_point_lat, center_point_long)
	 *
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 * @return string
	 *
	 */
	public function get_anonymous_geodistance_query_for_field( $field_name, $geo_latitude, $geo_longitude ) {
		return sprintf( self::TEMPLATE_ANONYMOUS_GEODISTANCE_QUERY_FOR_FIELD,
			WpSolrSchema::replace_field_name_extension( $field_name ),
			$geo_latitude,
			$geo_longitude
		);
	}

	/**
	 * Generate a distance query for a field, and name the query
	 * 'field_name1' => wpsolr_distance_field_name1:geodist(field_name1_ll, center_point_lat, center_point_long)
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
		return sprintf( self::TEMPLATE_NAMED_GEODISTANCE_QUERY_FOR_FIELD,
			$field_prefix,
			WPSOLR_Regexp::remove_string_at_the_end( $field_name, WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ),
			$this->get_anonymous_geodistance_query_for_field( $field_name, $geo_latitude, $geo_longitude )
		);
	}

	/**
	 * Replace default query field by query fields, with their eventual boost.
	 *
	 * @param array $query_fields
	 *
	 */
	protected function search_engine_client_set_query_fields( array $query_fields ) {

		$this->query_select->getEDisMax()->setQueryFields( implode( ' ', $query_fields ) );
	}

	/**
	 * Set boosts field values.
	 *
	 * @param string $boost_field_values
	 *
	 */
	protected function search_engine_client_set_boost_field_values( $boost_field_values ) {

		$this->query_select->getEDisMax()->setBoostQuery( $boost_field_values );
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

		/**
		 * https://cwiki.apache.org/confluence/display/solr/Faceting#Faceting-IntervalFaceting
		 * https://cwiki.apache.org/confluence/display/solr/DocValues
		 *
		 * Intervals are requiring docValues and Solr 4.10. We're therefore using ranges with before and after sections.
		 */
		$this->query_select->getFacetSet()
		                   ->createFacetRange( "$facet_name" )
		                   ->setField( "$field_name" )
		                   ->setStart( $range_start )
		                   ->setEnd( $range_end )
		                   ->setGap( $range_gap )
		                   ->setInclude( 'lower' )
		                   ->setOther( 'all' );


		/*
		$intervals = [];

		// Add a range for values before start
		$intervals[ sprintf( '%s-%s', '*', $range_start ) ] = sprintf( '[%s,%s)', '*', $range_start );

		// No gap parameter. We build the ranges manually.
		for ( $start = $range_start; $start < $range_end; $start += $range_gap ) {
			$intervals[ sprintf( '%s-%s', $start, $start + $range_gap ) ] = sprintf( '[%s,%s)', $start, $start + $range_gap );
		}

		// Add a range for values after end
		$intervals[ sprintf( '%s-%s', $range_end, '*' ) ] = sprintf( '[%s,%s)', $range_end, '*' );


		$this->query_select->getFacetSet()
		                   ->createFacetInterval( "$facet_name" )
		                   ->setField( "$field_name" )
		                   ->setSet( $intervals );
		*/


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

		$this->search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, sprintf( '%s:[%s TO %s}', $field_name, $range_start, $range_end ), $filter_tag );
	}

	/**
	 * Add a simple filter range.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param string $facet_is_or
	 * @param string $filter_query
	 * @param string $filter_tag
	 */
	public function search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, $filter_query, $filter_tag = '' ) {

		if ( $facet_is_or ) {

			if ( ! isset( $this->filter_queries_or[ $field_name ] ) ) {
				$this->filter_queries_or[ $field_name ] = [ 'query' => '', 'tag' => $filter_tag ];
			}

			$this->filter_queries_or[ $field_name ]['query'] .= sprintf( ' %s %s ', empty( $this->filter_queries_or[ $field_name ]['query'] ) ? '' : ' OR ', $filter_query );

		} else {

			$this->query_select->addFilterQuery(
				[
					'key'   => $filter_name,
					'query' => $filter_query,
					'tag'   => $filter_tag,
				]
			);
		}
	}
}
