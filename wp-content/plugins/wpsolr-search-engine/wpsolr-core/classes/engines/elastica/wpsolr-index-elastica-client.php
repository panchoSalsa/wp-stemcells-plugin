<?php

require_once plugin_dir_path( __FILE__ ) . '../wpsolr-abstract-index-client.php';
require_once plugin_dir_path( __FILE__ ) . 'wpsolr-elastica-client.php';
require_once plugin_dir_path( __FILE__ ) . '../../metabox/wpsolr-metabox.php';

/**
 * Class WPSolrIndexElasticaClient
 *
 * @property \Elastica\Client $search_engine_client
 */
class WPSolrIndexElasticaClient extends WPSolrAbstractIndexClient {
	use WPSolrElasticaClient;

	/**
	 * @inheritDoc
	 */
	public function search_engine_client_execute( $search_engine_client, $query ) {
		// Nothing here.
	}


	/**
	 * @param array $documents
	 */
	protected function search_engine_client_prepare_documents_for_update( array $documents ) {

		$formatted_document = array();

		$type = $this->get_elastica_type();

		foreach ( $documents as $document ) {
			$upsert_document = new \Elastica\Document( $document['id'], $document, $type );
			$upsert_document->setDocAsUpsert( true );

			$formatted_document[] = $upsert_document;
		}

		return $formatted_document;
	}

	/**
	 * Use Tika to extract a file content.
	 *
	 * @param $file
	 *
	 * @return string
	 */
	protected function search_engine_client_extract_document_content( $file ) {

		// Decoded value
		$decoded_attached_value = '';

		$document = new Elastica\Document( $this->WPSOLR_DOC_ID_ATTACHMENT, array(), $this->get_elastica_type_attachment() );
		$document->addFile( 'file', $file );
		$document->setDocAsUpsert( true );
		$result = $this->get_elastica_type_attachment()->updateDocument( $document );

		if ( ! $result->hasError() ) {
			$attached_document = $this->get_elastica_type_attachment()->getDocument( $this->WPSOLR_DOC_ID_ATTACHMENT, array( 'stored_fields' => 'file.content' ) );

			$decoded_attached_array = $attached_document->get( 'file.content' );
			if ( ! empty( $decoded_attached_array ) ) {
				$decoded_attached_value = $decoded_attached_array[0];
			}
		}

		return sprintf( '<body>%s</body>', $decoded_attached_value );
	}

	/**
	 * @param array[] $documents
	 *
	 * @return int|mixed
	 */
	public function send_posts_or_attachments_to_solr_index( $documents ) {

		$formatted_docs = $this->search_engine_client_prepare_documents_for_update( $documents );

		$results = $this->get_elastica_type()->updateDocuments( $formatted_docs );

		return $results->hasError();
	}

	/**
	 * Delete all documents.
	 *
	 */
	protected function search_engine_client_delete_all_documents() {

		$this->get_elastica_type()->deleteByQuery( new Elastica\Query\MatchAll() );
	}

	/**
	 * @return int
	 */
	protected function search_engine_client_get_count_document() {

		$nb_documents = $this->get_elastica_type()->count();

		return $nb_documents;
	}

	/**
	 * Transform a string in a date.
	 *
	 * @param $date_str String date to convert from.
	 *
	 * @return string
	 */
	public function search_engine_client_format_date( $date_str ) {
		return Elastica\Util::convertDate( $date_str );
	}

	/**
	 * Delete a document.
	 *
	 * @param string $document_id
	 *
	 */
	protected function search_engine_client_delete_document( $document_id ) {

		$term = new \Elastica\Query\Term();
		$term->setTerm( 'id', $document_id );

		$this->get_elastica_type()->deleteByQuery( $term );
	}
}
