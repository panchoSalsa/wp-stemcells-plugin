<?php

require_once plugin_dir_path( __FILE__ ) . 'wpsolr-results-solr-client.php';

/**
 * Some common methods of the Solr client.
 * @property \Solarium\Client $search_engine_client
 */
trait WPSolrSolrClient {


	/**
	 * Execute an update query with the client.
	 *
	 * @param \Solarium\Client $search_engine_client
	 * @param \Solarium\Core\Query\QueryInterface $update_query
	 *
	 * @return WPSolrResultsSolrClient
	 */
	public function search_engine_client_execute( $search_engine_client, $update_query ) {

		$this->search_engine_client_pre_execute();

		return new WPSolrResultsSolrClient( $search_engine_client->execute( $update_query ) );
	}

	/**
	 * Prepare query execute
	 */
	abstract public function search_engine_client_pre_execute();


	/**
	 * @param $config
	 *
	 * @return \Solarium\Client
	 */
	protected function create_search_engine_client( $config ) {

		$solarium_config = array(
			'endpoint' => array(
				'localhost1' => array(
					'scheme'   => $config['scheme'],
					'host'     => $config['host'],
					'port'     => $config['port'],
					'path'     => $config['path'],
					'username' => $config['username'],
					'password' => $config['password'],
					'timeout'  => $config['timeout'],
				),
			),
		);

		return new Solarium\Client( $solarium_config );
	}

}