<?php

/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 25/03/16
 * Time: 11:32
 */
namespace Allaerd\Export;

class ajaxExport implements exportInterface
{
    public $current_run;

    public $file;

    public $writer;

    public function __construct ( writerInterface $writer )
    {
        $this->current_run = get_option ('woocsv_export_current');
        $this->writer = $writer;
    }

    public function start ()
    {
        global $wpdb;

        $this->newRun ();

        $this->current_run[ 'status' ] = 'running';
        $current_id = $this->current_run[ 'current_id' ];

        $posts = $wpdb->get_col ($wpdb->prepare ("select ID from $wpdb->posts where post_type=%s and ID > %d order by ID limit %d", 'product', $current_id, 10));

        if ( !$posts ) {
            delete_option ('woocsv_export_current');
            wp_die (0);
        }

        foreach ( $posts as $post ) {
            $this->exportProduct ($post);
        }

        update_option ('woocsv_export_current', $this->current_run);

        wp_die ($this->current_run[ 'current_row' ]);
    }

    public function exportProduct ( $post )
    {

        $product = wc_get_product ($post);
        $className = "Allaerd\\Export\\" . $product->product_type;

        if ( class_exists ($className) ) {
            $export_product = new  $className ($post, $this->current_run[ 'header' ], $this->writer);
            $export_product->save ();
        }

        $this->current_run[ 'current_id' ] = $post;
        $this->current_run[ 'current_row' ]++;

    }

    public function newRun ()
    {
        if ( $this->current_run ) {
            return;
        }

        $upload_dir = wp_upload_dir ();
        $filename = $upload_dir[ 'basedir' ] . '/woocsv_export_' . time () . '.csv';

        $header = $this->getHeader ();

        $strippedHeader = $this->stripHeader ();

        $this->current_run = array (
            'header'      => $header,
            'filename'    => $filename,
            'status'      => 'new',
            'current_id'  => 0,
            'current_row' => 0,
        );

        update_option ('woocsv_export_current', $this->current_run);

        $this->writer->write ($strippedHeader);


    }

    public function getHeader ()
    {
        $header_name = (isset($_POST[ 'header_name' ])) ? $_POST[ 'header_name' ] : '';
        if ( $header_name == 'All' ) {
            return woocsv_export ()->product->allFields ();
        }

        return (woocsv_export ()->headers[ $header_name ]);
    }


    /**
     * @return array
     */
    public function stripHeader ()
    {
        $header = $this->getHeader ();
        $stripped_header = array ();
        foreach ( $header as $h ) {
            $stripped_header[] = ltrim ($h, '_');
        }

        return $stripped_header;
    }

}