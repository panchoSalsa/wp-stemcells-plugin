<?php

require_once plugin_dir_path( __FILE__ ) . 'wpsolr-abstract-engine-client.php';
require_once plugin_dir_path( __FILE__ ) . '../metabox/wpsolr-metabox.php';

abstract class WPSolrAbstractIndexClient extends WPSolrAbstractEngineClient {


	// Posts table name
	const TABLE_POSTS = 'posts';
	const CONTENT_SEPARATOR = ' ';

	const SQL_DATE_NULL = '1000-01-01 00:00:00';

	protected $solr_indexing_options;

	protected $last_post_infos_to_start = array(
		'date' => self::SQL_DATE_NULL,
		'ID'   => '0',
	);

	/**
	 * Use Tika to extract a file content.
	 *
	 * @param $file
	 *
	 * @return string
	 */
	abstract protected function search_engine_client_extract_document_content( $file );


	/**
	 * Execute a solarium query. Retry 2 times if an error occurs.
	 *
	 * @param $search_engine_client
	 * @param $update_query
	 *
	 * @return mixed
	 */
	protected function execute( $search_engine_client, $update_query ) {


		for ( $i = 0; ; $i ++ ) {

			try {

				$result = $this->search_engine_client_execute( $search_engine_client, $update_query );

				return $result;

			} catch ( Exception $e ) {

				// Catch error here, to retry in next loop, or throw error after enough retries.
				if ( $i >= 3 ) {
					throw $e;
				}

				// Sleep 3 seconds before retrying
				sleep( 3 );
			}

		}

	}


	/**
	 * Retrieve the Solr index for a post (usefull for multi languages extensions).
	 *
	 * @param $post
	 *
	 * @return WPSolrIndexSolrClient
	 */
	static function create_from_post( $post ) {

		// Get the current post language
		$post_language = apply_filters( WpSolrFilters::WPSOLR_FILTER_POST_LANGUAGE, null, $post );

		return static::create( null, $post_language );
	}

	static function create( $solr_index_indice = null, $post_language = null ) {

		// Build Solarium config from the default indexing Solr index
		WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
		$options_indexes = new OptionIndexes();
		$config          = $options_indexes->build_config( $solr_index_indice, $post_language, self::DEFAULT_SEARCH_ENGINE_TIMEOUT_IN_SECOND );

		switch ( ! empty( $config['index_engine'] ) ? $config['index_engine'] : self::ENGINE_SOLR ) {

			case self::ENGINE_ELASTICSEARCH:
				return new WPSolrIndexElasticaClient( $config, $solr_index_indice, $post_language );
				break;

			default:
				return new WPSolrIndexSolrClient( $config, $solr_index_indice, $post_language );
				break;

		}
	}

	public function __construct( $config, $solr_index_indice = null, $language_code = null ) {

		$this->init();

		$path = plugin_dir_path( __FILE__ ) . '../../vendor/autoload.php';
		require_once $path;

		// Load options
		$this->solr_indexing_options = WPSOLR_Global::getOption()->get_option_index();

		$this->index_indice = $solr_index_indice;

		$options_indexes = new OptionIndexes();
		$this->index     = $options_indexes->get_index( $solr_index_indice );

		$this->search_engine_client = $this->create_search_engine_client( $config );
	}


	/**
	 * Delete all documents.
	 *
	 */
	abstract protected function search_engine_client_delete_all_documents();

	public function delete_documents() {

		// Reset docs first
		$this->reset_documents();

		if ( $this->is_in_galaxy ) {
			// Delete only current site content
			//$deleteQuery->addDeleteQuery( sprintf( '%s:"%s"', WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR, $this->galaxy_slave_filter_value ) );
		} else {
			// Delete all content
			$this->search_engine_client_delete_all_documents();
		}

	}

	public function reset_documents() {

		// Store 0 in # of index documents
		self::set_index_indice_option_value( 'solr_docs', 0 );

		// Reset last indexed post date
		self::reset_last_post_date_indexed();

		// Update nb of documents updated/added
		self::set_index_indice_option_value( 'solr_docs_added_or_updated_last_operation', - 1 );

	}

	public function get_hosting_postfixed_option( $option ) {

		$result = $option;

		$solr_options = get_option( 'wdm_solr_conf_data' );

		switch ( $solr_options['host_type'] ) {
			case 'self_hosted':
				$postfix = '_in_self_index';
				break;

			default:
				$postfix = '_in_cloud_index';
				break;
		}

		return $result . $postfix;
	}

	/**
	 * How many documents were updated/added during last indexing operation
	 */
	public function get_count_documents() {

		$nb_documents = $this->search_engine_client_get_count_document();

		// Store 0 in # of index documents
		self::set_index_indice_option_value( 'solr_docs', $nb_documents );

		return $nb_documents;

	}

	/**
	 * Delete a document.
	 *
	 * @param string $document_id
	 *
	 */
	abstract protected function search_engine_client_delete_document( $document_id );

	public function delete_document( $post ) {

		$this->search_engine_client_delete_document( $this->generate_unique_post_id( $post->ID ) );
	}


	public function get_count_documents_indexed_last_operation( $default_value = - 1 ) {

		return $this->get_index_indice_option_value( 'solr_docs_added_or_updated_last_operation', $default_value );

	}

	public function get_last_post_date_indexed() {

		$result = $this->get_index_indice_option_value( 'solr_last_post_date_indexed', $this->last_post_infos_to_start );

		if ( ! is_array( $result ) ) {
			// Change string date value (pre 16.5) to array.
			return array(
				'date' => ! empty( $result ) ? $result : self::SQL_DATE_NULL,
				'ID'   => '0',
			);
		}

		return $result;
	}

	public function reset_last_post_date_indexed() {

		return $this->set_index_indice_option_value( 'solr_last_post_date_indexed', $this->last_post_infos_to_start );

	}

	public function set_last_post_date_indexed( $option_value ) {

		return $this->set_index_indice_option_value( 'solr_last_post_date_indexed', $option_value );
	}

	public function get_index_indice_option_value( $option_name, $option_value ) {

		// Get option value. Replace by default value if undefined.
		$option = get_option( $option_name, null );

		$result = ( isset( $option ) && isset( $option[ $this->index_indice ] ) )
			? $option[ $this->index_indice ]
			: $option_value;

		return $result;
	}

	public function set_index_indice_option_value( $option_name, $option_value ) {

		$option = get_option( $option_name, null );

		if ( ! isset( $option ) ) {
			$option = array();
		}

		$option[ $this->index_indice ] = $option_value;

		update_option( $option_name, $option );

		return $option_value;
	}

	/**
	 * Count nb documents remaining to index for a solr index
	 *
	 * @return integer Nb documents remaining to index
	 */
	public function get_count_nb_documents_to_be_indexed() {

		return $this->index_data( 0, null );

	}

	/**
	 * @param int $batch_size
	 * @param null $post
	 *
	 * @return array
	 * @throws Exception
	 */
	public function index_data( $batch_size = 100, $post = null, $is_debug_indexing = false, $is_only_exclude_ids = false ) {

		global $wpdb;

		// Debug variable containing debug text
		$debug_text = '';

		// Last post date set in previous call. We begin with posts published after.
		// Reset the last post date is reindexing is required.
		$last_post_date_indexed = $this->get_last_post_date_indexed();

		$query_from       = $wpdb->prefix . self::TABLE_POSTS . ' AS ' . self::TABLE_POSTS;
		$query_join_stmt  = '';
		$query_where_stmt = '';

		$post_types = str_replace( ',', "','", $this->solr_indexing_options['p_types'] );
		$exclude_id = $this->solr_indexing_options['exclude_ids'];
		$ex_ids     = explode( ',', $exclude_id );

		// Build the WHERE clause

		// Where clause for post types
		$where_p = " post_type in ('$post_types') ";

		// Build the attachment types clause
		$attachment_types = str_replace( ',', "','", WPSOLR_Global::getOption()->get_option_index_attachment_types_str() );
		if ( isset( $attachment_types ) && ( '' !== $attachment_types ) ) {
			$where_a = " ( post_status='publish' OR post_status='inherit' ) AND post_type='attachment' AND post_mime_type in ('$attachment_types') ";
		}


		if ( isset( $where_p ) ) {
			$index_post_statuses = implode( ',', apply_filters( WpSolrFilters::WPSOLR_FILTER_POST_STATUSES_TO_INDEX, array( 'publish' ) ) );
			$index_post_statuses = str_replace( ',', "','", $index_post_statuses );
			$query_where_stmt    = "post_status IN ('$index_post_statuses') AND ( $where_p )";
			if ( isset( $where_a ) ) {
				$query_where_stmt = "( $query_where_stmt ) OR ( $where_a )";
			}
		} elseif ( isset( $where_a ) ) {
			$query_where_stmt = $where_a;
		}

		if ( 0 === $batch_size ) {
			// count only
			$query_select_stmt = 'count(ID) as TOTAL';
		} else {
			$query_select_stmt = 'ID, post_modified, post_parent, post_type';
		}

		if ( isset( $post ) ) {
			// Add condition on the $post

			$query_where_stmt = " ID = %d AND ( $query_where_stmt ) ";
		} elseif ( $is_only_exclude_ids ) {
			// No condition on the date for $is_only_exclude_ids

			$query_where_stmt = " ( $query_where_stmt ) ";
		} else {
			// Condition on the date only for the batch, not for individual posts

			$query_where_stmt = ' ((post_modified = %s AND ID > %d) OR (post_modified > %s)) ' . " AND ( $query_where_stmt ) ";
		}

		// Excluded ids from SQL
		$blacklisted_ids = $this->get_blacklisted_ids();
		if ( $is_debug_indexing && ( count( $blacklisted_ids ) > 0 ) ) {
			$this->add_debug_line( $debug_text, null, array(
				'Posts excluded from the index' => implode( ',', $blacklisted_ids ),
			) );
		}
		$query_where_stmt .= $this->get_sql_statemetnt_blacklisted_ids( $blacklisted_ids, $is_only_exclude_ids );


		$query_order_by_stmt = 'post_modified ASC, ID ASC';

		// Filter the query
		$query_statements = apply_filters( WpSolrFilters::WPSOLR_FILTER_SQL_QUERY_STATEMENT,
			array(
				'SELECT' => $query_select_stmt,
				'FROM'   => $query_from,
				'JOIN'   => $query_join_stmt,
				'WHERE'  => $query_where_stmt,
				'ORDER'  => $query_order_by_stmt,
				'LIMIT'  => $batch_size,
			),
			array(
				'index_indice' => $this->index_indice,
			)
		);


		// Generate query string from the query statements
		$query = sprintf(
			'SELECT %s FROM %s %s WHERE %s ORDER BY %s %s',
			$query_statements['SELECT'],
			$query_statements['FROM'],
			$query_statements['JOIN'],
			$query_statements['WHERE'],
			$query_statements['ORDER'],
			0 === $query_statements['LIMIT'] ? '' : 'LIMIT ' . $query_statements['LIMIT']
		);


		$documents     = array();
		$doc_count     = 0;
		$no_more_posts = false;
		while ( true ) {

			if ( $is_debug_indexing ) {
				$this->add_debug_line( $debug_text, 'Beginning of new loop (batch size)' );
			}

			// Execute query (retrieve posts IDs, parents and types)
			if ( isset( $post ) ) {

				if ( $is_debug_indexing ) {
					$this->add_debug_line( $debug_text, 'Query document with post->ID', array(
						'Query'   => $query,
						'Post ID' => $post->ID,
					) );
				}

				$ids_array = $wpdb->get_results( $wpdb->prepare( $query, $post->ID ), ARRAY_A );

			} elseif ( $is_only_exclude_ids ) {

				$ids_array = $wpdb->get_results( $query, ARRAY_A );

			} else {

				if ( $is_debug_indexing ) {
					$this->add_debug_line( $debug_text, 'Query documents from last post date', array(
						'Query'          => $query,
						'Last post date' => $last_post_date_indexed['date'],
						'Last post ID'   => $last_post_date_indexed['ID'],
					) );
				}

				$ids_array = $wpdb->get_results( $wpdb->prepare( $query, $last_post_date_indexed['date'], $last_post_date_indexed['ID'], $last_post_date_indexed['date'] ), ARRAY_A );
			}

			if ( 0 === $batch_size ) {

				$nb_docs = $ids_array[0]['TOTAL'];

				if ( $is_debug_indexing ) {
					$this->add_debug_line( $debug_text, 'End of loop', array(
						$is_only_exclude_ids ? 'Number of documents in database excluded from indexing' : 'Number of documents in database to be indexed' => $nb_docs,
					) );
				}

				// Just return the count
				return $nb_docs;
			}


			// Aggregate current batch IDs in one Solr update statement
			$post_count = count( $ids_array );

			if ( 0 === $post_count ) {
				// No more documents to index, stop now by exiting the loop

				if ( $is_debug_indexing ) {
					$this->add_debug_line( $debug_text, 'No more documents, end of document loop' );
				}

				$no_more_posts = true;
				break;
			}

			$client = $this->search_engine_client;

			for ( $idx = 0; $idx < $post_count; $idx ++ ) {
				$postid = $ids_array[ $idx ]['ID'];

				// If post is not an attachment
				if ( 'attachment' !== $ids_array[ $idx ]['post_type'] ) {

					// Count this post
					$doc_count ++;

					// Customize the attachment body, if attachments are linked to the current post
					$post_attachments = apply_filters( WpSolrFilters::WPSOLR_FILTER_GET_POST_ATTACHMENTS, array(), $postid );

					// Get the attachments body with a Solr Tika extract query
					$attachment_body = '';
					foreach ( $post_attachments as $post_attachment ) {
						$attachment_body .= ( empty( $attachment_body ) ? '' : '. ' ) . self::extract_attachment_text_by_calling_solr_tika( $post_attachment );
					}


					// Get the posts data
					$document = self::create_solr_document_from_post_or_attachment( get_post( $postid ), $attachment_body );

					if ( $is_debug_indexing ) {
						$this->add_debug_line( $debug_text, null, array(
							'Post to be sent' => wp_json_encode( $document, JSON_PRETTY_PRINT ),
						) );
					}

					$documents[] = $document;

				} else {
					// Post is of type "attachment"

					if ( $is_debug_indexing ) {
						$this->add_debug_line( $debug_text, null, array(
							'Post ID to be indexed (attachment)' => $postid,
						) );
					}

					// Count this post
					$doc_count ++;

					// Get the attachments body with a Solr Tika extract query
					$attachment_body = self::extract_attachment_text_by_calling_solr_tika( array( 'post_id' => $postid ) );

					// Get the posts data
					$document = self::create_solr_document_from_post_or_attachment( get_post( $postid ), $attachment_body );

					if ( $is_debug_indexing ) {
						$this->add_debug_line( $debug_text, null, array(
							'Attachment to be sent' => wp_json_encode( $document, JSON_PRETTY_PRINT ),
						) );
					}

					$documents[] = $document;

				}
			}

			if ( empty( $documents ) || ! isset( $documents ) ) {
				// No more documents to index, stop now by exiting the loop

				if ( $is_debug_indexing ) {
					$this->add_debug_line( $debug_text, 'End of loop, no more documents' );
				}

				break;
			}

			// Send batch documents to Solr
			try {

				$res_final = $this->send_posts_or_attachments_to_solr_index( $documents );

			} catch ( Exception $e ) {

				if ( $is_debug_indexing ) {
					// Echo debug text now, else it will be hidden by the exception
					echo $debug_text;
				}

				// Continue
				throw $e;
			}

			// Solr error, or only $post to index: exit loop
			if ( ( null === $res_final ) || isset( $post ) ) {
				break;
			}

			if ( ! isset( $post ) ) {
				// Store last post date sent to Solr (for batch only)
				$last_post = end( $ids_array );
				$this->set_last_post_date_indexed( array(
					'date' => $last_post['post_modified'],
					'ID'   => $last_post['ID'],
				) );
			}

			// AJAX: one loop by ajax call
			break;
		}

		$status = ! isset( $res_final ) ? 0 : $res_final;

		return $res_final = array(
			'nb_results'        => $doc_count,
			'status'            => $status,
			'indexing_complete' => $no_more_posts,
			'debug_text'        => $is_debug_indexing ? $debug_text : null,
		);

	}

	/*
	 * Fetch posts and attachments,
	 * Transform them in Solr documents,
	 * Send them in packs to Solr
	 */

	/**
	 * Add a debug line to the current debug text
	 *
	 * @param $is_debug_indexing
	 * @param $debug_text
	 * @param $debug_text_header
	 * @param $debug_text_content
	 */
	public function add_debug_line( &$debug_text, $debug_line_header, $debug_text_header_content = null ) {

		if ( isset( $debug_line_header ) ) {
			$debug_text .= '******** DEBUG ACTIVATED - ' . $debug_line_header . ' *******' . '<br><br>';
		}

		if ( isset( $debug_text_header_content ) ) {

			foreach ( $debug_text_header_content as $key => $value ) {
				$debug_text .= $key . ':' . '<br>' . '<b>' . $value . '</b>' . '<br><br>';
			}
		}
	}

	/**
	 * Transform a string in a date.
	 *
	 * @param $date_str String date to convert from.
	 *
	 * @return mixed
	 */
	abstract public function search_engine_client_format_date( $date_str );

	/**
	 * @param $solarium_update_query
	 * @param $post_to_index
	 * @param null $attachment_body
	 *
	 * @return mixed
	 * @internal param $solr_indexing_options
	 */
	public
	function create_solr_document_from_post_or_attachment(
		$post_to_index, $attachment_body = ''
	) {

		$solarium_document_for_update = array();

		$pid    = $post_to_index->ID;
		$ptitle = $post_to_index->post_title;
		// Post is NOT an attachment: we get the document body from the post object
		$pcontent = $post_to_index->post_content . ( empty( $attachment_body ) ? '' : ( '. ' . $attachment_body ) );

		$pexcerpt   = $post_to_index->post_excerpt;
		$pauth_info = get_userdata( $post_to_index->post_author );
		$pauthor    = isset( $pauth_info ) && isset( $pauth_info->display_name ) ? $pauth_info->display_name : '';
		$pauthor_s  = isset( $pauth_info ) && isset( $pauth_info->user_nicename ) ? get_author_posts_url( $pauth_info->ID, $pauth_info->user_nicename ) : '';

		// Get the current post language
		$post_language = apply_filters( WpSolrFilters::WPSOLR_FILTER_POST_LANGUAGE, null, $post_to_index );
		$ptype         = $post_to_index->post_type;

		$pdate            = solr_format_date( $post_to_index->post_date_gmt );
		$pmodified        = solr_format_date( $post_to_index->post_modified_gmt );
		$pdisplaydate     = $this->search_engine_client_format_date( $post_to_index->post_date );
		$pdisplaymodified = $this->search_engine_client_format_date( $post_to_index->post_modified );
		$purl             = get_permalink( $pid );
		$comments_con     = array();
		$comm             = isset( $this->solr_indexing_options[ WpSolrSchema::_FIELD_NAME_COMMENTS ] ) ? $this->solr_indexing_options[ WpSolrSchema::_FIELD_NAME_COMMENTS ] : '';

		$numcomments = 0;
		if ( $comm ) {
			$comments_con = array();

			$comments = get_comments( "status=approve&post_id={$post_to_index->ID}" );
			foreach ( $comments as $comment ) {
				array_push( $comments_con, $comment->comment_content );
				$numcomments += 1;
			}

		}
		$pcomments    = $comments_con;
		$pnumcomments = $numcomments;


		/*
			Get all custom categories selected for indexing, including 'category'
		*/
		$cats                            = array();
		$categories_flat_hierarchies     = array();
		$categories_non_flat_hierarchies = array();
		$taxo                            = WPSOLR_Global::getOption()->get_option_index_taxonomies_str();
		$aTaxo                           = explode( ',', $taxo );
		$newTax                          = array(); // Add categories by default
		if ( is_array( $aTaxo ) && count( $aTaxo ) ) {
		}
		foreach ( $aTaxo as $a ) {

			if ( WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING === substr( $a, ( strlen( $a ) - 4 ), strlen( $a ) ) ) {
				$a = substr( $a, 0, ( strlen( $a ) - 4 ) );
			}

			// Add only non empty categories
			if ( strlen( trim( $a ) ) > 0 ) {
				array_push( $newTax, $a );
			}
		}


		// Get all categories ot this post
		$terms = wp_get_post_terms( $post_to_index->ID, array( 'category' ), array( 'fields' => 'all_with_object_id' ) );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {

				// Add category and it's parents
				$term_parents_names = array();
				// Add parents in reverse order ( top-bottom)
				$term_parents_ids = array_reverse( get_ancestors( $term->term_id, 'category' ) );
				array_push( $term_parents_ids, $term->term_id );

				foreach ( $term_parents_ids as $term_parent_id ) {
					$term_parent = get_term( $term_parent_id, 'category' );

					array_push( $term_parents_names, $term_parent->name );

					// Add the term to the non-flat hierarchy (for filter queries on all the hierarchy levels)
					array_push( $categories_non_flat_hierarchies, $term_parent->name );
				}

				// Add the term to the flat hierarchy
				array_push( $categories_flat_hierarchies, implode( WpSolrSchema::FACET_HIERARCHY_SEPARATOR, $term_parents_names ) );

				// Add the term to the categories
				array_push( $cats, $term->name );
			}
		}

		// Get all tags of this port
		$tag_array = array();
		$tags      = get_the_tags( $post_to_index->ID );
		if ( ! $tags == null ) {
			foreach ( $tags as $tag ) {
				array_push( $tag_array, $tag->name );

			}
		}

		if ( $this->is_in_galaxy ) {
			$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR ] = $this->galaxy_slave_filter_value;
		}

		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_ID ]       = $this->generate_unique_post_id( $pid );
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_PID ]      = $pid;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_TITLE ]    = $ptitle;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_STATUS_S ] = $post_to_index->post_status;

		if ( isset( $this->solr_indexing_options['p_excerpt'] ) && ( ! empty( $pexcerpt ) ) ) {

			// Index post excerpt, by adding it to the post content.
			// Excerpt can therefore be: searched, autocompleted, highlighted.
			$pcontent .= self::CONTENT_SEPARATOR . $pexcerpt;
		}

		if ( ! empty( $pcomments ) ) {

			// Index post comments, by adding it to the post content.
			// Excerpt can therefore be: searched, autocompleted, highlighted.
			//$pcontent .= self::CONTENT_SEPARATOR . implode( self::CONTENT_SEPARATOR, $pcomments );
		}


		$content_with_shortcodes_expanded_or_stripped = $pcontent;
		if ( isset( $this->solr_indexing_options['is_shortcode_expanded'] ) && ( strpos( $pcontent, '[solr_search_shortcode]' ) === false ) ) {

			// Expand shortcodes which have a plugin active, and are not the search form shortcode (else pb).
			global $post;
			$post                                         = $post_to_index;
			$content_with_shortcodes_expanded_or_stripped = do_shortcode( $pcontent );
		}

		// Remove shortcodes tags remaining, but not their content.
		// strip_shortcodes() does nothing, probably because shortcodes from themes are not loaded in admin.
		// Credit: https://wordpress.org/support/topic/stripping-shortcodes-keeping-the-content.
		// Modified to enable "/" in attributes
		$content_with_shortcodes_expanded_or_stripped = preg_replace( "~(?:\[/?)[^\]]+/?\]~s", '', $content_with_shortcodes_expanded_or_stripped );  # strip shortcodes, keep shortcode content;

		// Remove HTML tags
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ] = strip_tags( $content_with_shortcodes_expanded_or_stripped );

		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_AUTHOR ]             = $pauthor;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_AUTHOR_S ]           = $pauthor_s;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_TYPE ]               = $ptype;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_DATE ]               = $pdate;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_MODIFIED ]           = $pmodified;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_DISPLAY_DATE ]       = $pdisplaydate;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_DISPLAY_MODIFIED ]   = $pdisplaymodified;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_PERMALINK ]          = $purl;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_COMMENTS ]           = $pcomments;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_NUMBER_OF_COMMENTS ] = $pnumcomments;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CATEGORIES_STR ]     = $cats;
		// Hierarchy of categories
		$solarium_document_for_update[ sprintf( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, WpSolrSchema::_FIELD_NAME_CATEGORIES_STR ) ]     = $categories_flat_hierarchies;
		$solarium_document_for_update[ sprintf( WpSolrSchema::_FIELD_NAME_NON_FLAT_HIERARCHY, WpSolrSchema::_FIELD_NAME_CATEGORIES_STR ) ] = $categories_non_flat_hierarchies;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_TAGS ]                                                                    = $tag_array;

		// Index post thumbnail
		$this->index_post_thumbnails( $solarium_document_for_update, $pid );

		// Index post url
		$this->index_post_url( $solarium_document_for_update, $pid );

		$taxonomies = (array) get_taxonomies( array( '_builtin' => false ), 'names' );
		foreach ( $taxonomies as $parent ) {
			if ( in_array( $parent, $newTax, true ) ) {
				$terms = get_the_terms( $post_to_index->ID, $parent );
				if ( (array) $terms === $terms ) {
					$parent    = strtolower( str_replace( ' ', '_', $parent ) );
					$nm1       = $parent . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING;
					$nm2       = $parent . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING1;
					$nm1_array = array();

					$taxonomy_non_flat_hierarchies = array();
					$taxonomy_flat_hierarchies     = array();

					foreach ( $terms as $term ) {

						// Add taxonomy and it's parents
						$term_parents_names = array();
						// Add parents in reverse order ( top-bottom)
						$term_parents_ids = array_reverse( get_ancestors( $term->term_id, $parent ) );
						array_push( $term_parents_ids, $term->term_id );

						foreach ( $term_parents_ids as $term_parent_id ) {
							$term_parent = get_term( $term_parent_id, $parent );

							array_push( $term_parents_names, $term_parent->name );

							// Add the term to the non-flat hierarchy (for filter queries on all the hierarchy levels)
							array_push( $taxonomy_non_flat_hierarchies, $term_parent->name );
						}

						// Add the term to the flat hierarchy
						array_push( $taxonomy_flat_hierarchies, implode( WpSolrSchema::FACET_HIERARCHY_SEPARATOR, $term_parents_names ) );

						// Add the term to the taxonomy
						array_push( $nm1_array, $term->name );

						// Add the term to the categories searchable
						array_push( $cats, $term->name );

					}

					if ( count( $nm1_array ) > 0 ) {
						$solarium_document_for_update[ $nm1 ] = $nm1_array;
						$solarium_document_for_update[ $nm2 ] = $nm1_array;

						$solarium_document_for_update[ sprintf( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, $nm1 ) ]     = $taxonomy_flat_hierarchies;
						$solarium_document_for_update[ sprintf( WpSolrSchema::_FIELD_NAME_NON_FLAT_HIERARCHY, $nm1 ) ] = $taxonomy_non_flat_hierarchies;

					}
				}
			}
		}

		// Set categories and custom taxonomies as searchable
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CATEGORIES ] = $cats;

		// Add custom fields to the document
		$this->set_custom_fields( $solarium_document_for_update, $post_to_index );

		if ( isset( $this->solr_indexing_options['p_custom_fields'] ) && isset( $solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ] ) ) {

			$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ] .= self::CONTENT_SEPARATOR . implode( ". ", $solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ] );
		}

		// Last chance to customize the solarium update document
		$solarium_document_for_update = apply_filters( WpSolrFilters::WPSOLR_FILTER_SOLARIUM_DOCUMENT_FOR_UPDATE,
			$solarium_document_for_update,
			$this->solr_indexing_options,
			$post_to_index,
			$attachment_body,
			$this
		);

		return $solarium_document_for_update;

	}


	/**
	 * Set custom fields to the update document.
	 * HTML and php tags are removed.
	 *
	 * @param $solarium_document_for_update
	 * @param $post
	 */
	function set_custom_fields( &$solarium_document_for_update, $post ) {

		$custom_fields = WPSOLR_Global::getOption()->get_option_index_custom_fields();

		if ( count( $custom_fields ) > 0 ) {
			if ( count( $post_custom_fields = get_post_custom( $post->ID ) ) ) {

				// Apply filters on custom fields
				$post_custom_fields = apply_filters( WpSolrFilters::WPSOLR_FILTER_POST_CUSTOM_FIELDS, $post_custom_fields, $post->ID );

				$existing_custom_fields = isset( $solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ] )
					? $solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ]
					: array();

				foreach ( (array) $custom_fields as $field_name_with_str_ending ) {

					$field_name = WpSolrSchema::get_field_without_str_ending( $field_name_with_str_ending );

					if ( isset( $post_custom_fields[ $field_name ] ) ) {
						$field = (array) $post_custom_fields[ $field_name ];

						$field_name = strtolower( str_replace( ' ', '_', $field_name ) );

						// Add custom field array of values
						//$nm1       = $field_name . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING;
						$nm1       = WpSolrSchema::replace_field_name_extension( $field_name_with_str_ending );
						$nm2       = $field_name . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING1;
						$array_nm1 = array();
						$array_nm2 = array();
						foreach ( $field as $field_value ) {

							$field_value_sanitized = WpSolrSchema::get_sanitized_value( $this, $field_name_with_str_ending, $field_value, $post );

							// Only index the field if it has a value.
							if ( ! empty( $field_value_sanitized ) ) {

								array_push( $array_nm1, $field_value_sanitized );
								array_push( $array_nm2, $field_value_sanitized );

								// Add current custom field values to custom fields search field
								// $field being an array, we add each of it's element
								// Convert values to string, else error in the search engine if number, as a string is expected.
								array_push( $existing_custom_fields, is_array( $field_value_sanitized ) ? $field_value_sanitized : strval( $field_value_sanitized ) );
							}
						}

						$solarium_document_for_update[ $nm1 ] = $array_nm1;
						$solarium_document_for_update[ $nm2 ] = $array_nm2;

					}
				}

				if ( count( $existing_custom_fields ) > 0 ) {
					$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ] = $existing_custom_fields;
				}

			}

		}

	}

	/**
	 * @param array $post_attachement
	 *
	 * @return string
	 * @throws Exception
	 */
	public
	function extract_attachment_text_by_calling_solr_tika(
		$post_attachement
	) {

		try {
			$post_attachement_file = ! empty( $post_attachement['post_id'] ) ? get_attached_file( $post_attachement['post_id'] ) : download_url( $post_attachement['url'] );

			$response = $this->search_engine_client_extract_document_content( $post_attachement_file );

			$attachment_text_extracted_from_tika = preg_replace( '/^.*?\<body\>(.*)\<\/body\>.*$/i', '\1', $response );
			if ( PREG_NO_ERROR !== preg_last_error() ) {
				throw new Exception( sprintf( 'Error code (%s) returned by preg_replace() on the extracted file.', PREG_NO_ERROR ) );
			}

			if ( empty( $attachment_text_extracted_from_tika ) ) {
				// Wrong preg_replace() result,. Use the original text.
				throw new Exception( 'Wrong format returned for the extracted file, cannot extract the <body>.' );
			}

			$attachment_text_extracted_from_tika = str_replace( '\n', ' ', $attachment_text_extracted_from_tika );
		} catch ( Exception $e ) {
			if ( ! empty( $post_attachement['post_id'] ) ) {

				$post = get_post( $post_attachement['post_id'] );

				throw new Exception( 'Error on attached file ' . $post->post_title . ' (ID: ' . $post->ID . ')' . ': ' . $e->getMessage(), $e->getCode() );

			} else {

				throw new Exception( 'Error on attached file ' . $post_attachement['url'] . ': ' . $e->getMessage(), $e->getCode() );
			}
		}

		// Last chance to customize the tika extracted attachment body
		$attachment_text_extracted_from_tika = apply_filters( WpSolrFilters::WPSOLR_FILTER_ATTACHMENT_TEXT_EXTRACTED_BY_APACHE_TIKA, $attachment_text_extracted_from_tika, $post_attachement );

		return $attachment_text_extracted_from_tika;
	}

	/**
	 * @param array $documents
	 *
	 * @return mixed
	 */
	abstract public function send_posts_or_attachments_to_solr_index( $documents );

	/**
	 * Index a post thumbnail
	 *
	 * @param Solarium\QueryType\Update\Query\Document\Document $document Solarium document
	 * @param $post_id
	 *
	 * @return array|false
	 */
	private
	function index_post_thumbnails(
		$solarium_document_for_update, $post_id
	) {

		if ( $this->is_in_galaxy ) {

			// Master must get thumbnails from the index, as the $post_id is not in local database
			$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ) );
			if ( false !== $thumbnail ) {

				$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_POST_THUMBNAIL_HREF_STR ] = $thumbnail[0];
			}
		}

	}

	/**
	 * Index a post url
	 *
	 * @param Solarium\QueryType\Update\Query\Document\Document $document Solarium document
	 * @param $post_id
	 *
	 * @return array|false
	 */
	private
	function index_post_url(
		$solarium_document_for_update, $post_id
	) {

		if ( $this->is_in_galaxy ) {

			// Master must get urls from the index, as the $post_id is not in local database
			$url = get_permalink( $post_id );
			if ( false !== $url ) {

				$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_POST_HREF_STR ] = $url;
			}
		}
	}

	/**
	 * Generate a SQL restriction on all blacklisted post ids
	 *
	 * @param array $blacklisted_ids Array of post ids blaclisted
	 *
	 * @param bool $is_only_exclude_ids Do we find only excluded posts ?
	 *
	 * @return string
	 */
	private function get_sql_statemetnt_blacklisted_ids( $blacklisted_ids, $is_only_exclude_ids = false ) {

		if ( empty( $blacklisted_ids ) ) {

			$result = $is_only_exclude_ids ? ' AND (1 = 2) ' : '';

		} else {

			$result = sprintf( $is_only_exclude_ids ? ' AND ID IN (%s) ' : ' AND ID NOT IN (%s) ', implode( ',', $blacklisted_ids ) );
		}

		return $result;
	}

	/**
	 * Get blacklisted post ids
	 * @return array
	 */
	public function get_blacklisted_ids() {

		$excluded_meta_ids = WPSOLR_Metabox::get_blacklisted_ids();
		$excluded_list_ids = empty( $this->solr_indexing_options['exclude_ids'] ) ? array() : explode( ',', $this->solr_indexing_options['exclude_ids'] );

		$all_excluded_ids = array_merge( $excluded_meta_ids, $excluded_list_ids );

		return $all_excluded_ids;
	}

	/**
	 * Get count of blacklisted post ids
	 * @return int
	 */
	public function get_count_blacklisted_ids() {

		$result = $this->index_data( 0, null, false, true );

		return $result;
	}
}
