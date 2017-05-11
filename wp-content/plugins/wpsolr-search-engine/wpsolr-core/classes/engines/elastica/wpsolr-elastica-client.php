<?php

require_once plugin_dir_path( __FILE__ ) . 'wpsolr-results-elastica-client.php';

/**
 * Some common methods of the Elastica client.
 *
 * @property \Elastica\Client $search_engine_client
 * @property \Elastica\Index $elastica_index
 */
trait WPSolrElasticaClient {

	protected $wpsolr_type = 'wpsolr_types';

	// Unique id to store attached decoded files.
	protected $WPSOLR_DOC_ID_ATTACHMENT = 'wpsolr_doc_id_attachment';

	protected $elastica_index;

	/**
	 * @return \Elastica\Index
	 */
	public function get_elastica_index() {
		return $this->elastica_index;
	}

	/**
	 * @param \Elastica\Index $index
	 */
	public function set_elastica_index( $index ) {
		$this->elastica_index = $index;
	}

	/**
	 * @return \Elastica\Type
	 */
	public function get_elastica_type( $type = '' ) {
		return $this->elastica_index->getType( ( empty( $type ) ) ? $this->wpsolr_type : $type );
	}

	/**
	 * @return \Elastica\Type
	 */
	public function get_elastica_type_attachment() {
		return $this->get_elastica_type( 'wpsolr_type_attachment' );
	}


	protected function create_search_engine_client( $config ) {

		$elastica_config = [
			'transport' => $config['scheme'],
			'host'      => $config['host'],
			'port'      => $config['port'],
			'username'  => $config['username'],
			'password'  => $config['password'],
			'timeout'   => $config['timeout'],
		];

		$client = new Elastica\Client( $elastica_config );

		$this->set_elastica_index( $client->getIndex( $config['index_label'] ) );

		return $client;
	}

	/**
	 * Get index stats
	 * @return \Elastica\Index\Stats
	 */
	protected function elastica_get_index_stats() {
		return $this->get_elastica_index()->getStats();
	}

	/**
	 * Does index exists ?
	 *
	 * @return bool
	 */
	protected function elastica_index_exists() {
		return $this->get_elastica_index()->exists();
	}

}