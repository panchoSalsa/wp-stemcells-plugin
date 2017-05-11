<?php

/**
 * Class WPSolrResultsSolrClient
 *
 * @property \Solarium\QueryType\Select\Result\Result $results
 */
class WPSolrResultsSolrClient extends WPSolrAbstractResultsClient {

	/**
	 * WPSolrResultsSolrClient constructor.
	 *
	 * @param \Solarium\QueryType\Select\Result\Result|\Solarium\QueryType\Update\Result $results
	 */
	public function __construct( $results ) {
		$this->results = $results;
	}

	/**
	 * @return mixed
	 */
	public function get_suggestions() {
		return $this->get_results();
	}

	/**
	 * Get nb of results.
	 *
	 * @return int
	 */
	public function get_nb_results() {

		return $this->results->getNumFound();
	}

	/**
	 * Get a facet
	 *
	 * @return mixed
	 */
	public function get_facet( $facet_name ) {
		$facets = $this->results->getFacetSet();

		$results = empty( $facets ) ? null : $this->results->getFacetSet()->getFacet( $facet_name );

		// For ranges, add the before and after counts to the values
		// No need of this section when using intervals.
		if ( method_exists( $results, 'getAfter' ) ) {
			$values_original = $results->getValues();
			$values          = [];

			$before = $results->getBefore();
			if ( ! empty( $before ) ) {
				$values[ sprintf( '*-%s', $results->getStart() ) ] = $before;
			}

			foreach ( $values_original as $value => $count ) {
				$values[ $value ] = $count;
			}

			$after = $results->getAfter();
			if ( ! empty( $after ) ) {
				$values[ sprintf( '%s-*', $results->getEnd() ) ] = $after;
			}

			return $values;
		}

		return $results->getValues();
	}

	/**
	 * Get highlightings of a results.
	 *
	 * @param \Solarium\QueryType\Select\Result\Document $result
	 *
	 * @return array Result highlights.
	 */
	public function get_highlighting( $result ) {

		$highlights = $this->results->getHighlighting();

		if ( $highlights ) {
			$highlight = $highlights->getResult( $result->id );
			if ( $highlight ) {
				return $highlight->getFields();
			}
		}

		return [];
	}

}