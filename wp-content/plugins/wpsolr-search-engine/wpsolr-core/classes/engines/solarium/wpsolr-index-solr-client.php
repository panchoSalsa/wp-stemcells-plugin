<?php

require_once plugin_dir_path( __FILE__ ) . '../wpsolr-abstract-index-client.php';
require_once plugin_dir_path( __FILE__ ) . 'wpsolr-solr-client.php';
require_once plugin_dir_path( __FILE__ ) . '../../metabox/wpsolr-metabox.php';


/**
 * Class WPSolrIndexSolrClient
 *
 * @property \Solarium\Client $search_engine_client
 */
class WPSolrIndexSolrClient extends WPSolrAbstractIndexClient {
	use WPSolrSolrClient;

	/* @var \Solarium\Core\Query\Helper $helper */
	protected $helper;

	/**
	 * @param \Solarium\QueryType\Update\Query\Query $solarium_update_query
	 * @param array $documents
	 */
	protected function search_engine_client_prepare_documents_for_update( $solarium_update_query, array $documents ) {

		$formatted_document = array();

		foreach ( $documents as $document ) {
			$formatted_document[] = $solarium_update_query->createDocument( $document );
		}

		return $formatted_document;
	}

	/**
	 * @param array $documents
	 *
	 * @return mixed
	 */
	public function send_posts_or_attachments_to_solr_index( $documents ) {

		$solarium_update_query = $this->search_engine_client->createUpdate();

		$formatted_docs = $this->search_engine_client_prepare_documents_for_update( $solarium_update_query, $documents );

		$solarium_update_query->addDocuments( $formatted_docs );
		$solarium_update_query->addCommit();
		$result = $this->execute( $this->search_engine_client, $solarium_update_query );

		return $result->get_results()->getStatus();
	}

	/**
	 * @return int
	 */
	protected function search_engine_client_get_count_document() {

		$query = $this->search_engine_client->createSelect();
		$query->setQuery( '*:*' );
		$query->setRows( 0 );
		$result_set = $this->search_engine_client_execute( $this->search_engine_client, $query );

		return $result_set->get_nb_results();
	}

	/**
	 * Delete all documents.
	 *
	 */
	protected function search_engine_client_delete_all_documents() {

		// Execute delete query
		$delete_query = $this->search_engine_client->createUpdate();

		$delete_query->addDeleteQuery( 'id:*' );

		$delete_query->addCommit();

		$this->search_engine_client_execute( $this->search_engine_client, $delete_query );
	}


	/**
	 * Use Tika to extract a file content.
	 *
	 * @param $file
	 *
	 * @return string
	 */
	protected function search_engine_client_extract_document_content( $file ) {

		$solarium_extract_query = $this->search_engine_client->createExtract();

		// Set URL to attachment
		$solarium_extract_query->setFile( $file );
		$doc1 = $solarium_extract_query->createDocument();
		$solarium_extract_query->setDocument( $doc1 );
		// We don't want to add the document to the solr index now
		$solarium_extract_query->addParam( 'extractOnly', 'true' );
		// Try to extract the document body
		$client   = $this->search_engine_client;
		$results  = $this->execute( $client, $solarium_extract_query );
		$response = $results->get_results()->getResponse()->getBody();

		return $response;
	}

	/**
	 * Transform a string in a date.
	 *
	 * @param $date_str String date to convert from.
	 *
	 * @return string
	 */
	public function search_engine_client_format_date( $date_str ) {

		if ( null === $this->helper ) {
			$this->helper = new Solarium\Core\Query\Helper( $this );
		}

		return $this->helper->formatDate( $date_str );
	}

	/**
	 * Prepare query execute
	 */
	public function search_engine_client_pre_execute() {
		// TODO: Implement search_engine_client_pre_execute() method.
	}

	/**
	 * Delete a document.
	 *
	 * @param string $document_id
	 *
	 */
	protected function search_engine_client_delete_document( $document_id ) {

		$deleteQuery = $this->search_engine_client->createUpdate();
		$deleteQuery->addDeleteQuery( 'id:' . $document_id );
		$deleteQuery->addCommit();

		$this->execute( $this->search_engine_client, $deleteQuery );
	}
}
