<?php

/**
 * Class WPSolrAbstractResultsClient
 *
 * Abstract class for search results.
 */
abstract class WPSolrAbstractResultsClient {

	protected $results;

	/**
	 * @return mixed
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * @return mixed
	 */
	abstract public function get_suggestions();

	/**
	 * Get nb of results.
	 *
	 * @return int
	 * @throws Exception
	 */
	abstract public function get_nb_results();

	/**
	 * Get a facet
	 *
	 * @return mixed
	 */
	abstract public function get_facet( $facet_name );

	/**
	 * Get highlighting
	 *
	 * @param \Solarium\QueryType\Select\Result\Document|Elastica\Result $result
	 *
	 * @return array
	 */
	abstract public function get_highlighting( $result );

}