<?php
/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 25/03/16
 * Time: 23:14
 */

namespace Allaerd\Export;


class csvWriter implements writerInterface
{

    private $current_run;

    public function load ()
    {
        $this->current_run = get_option( 'woocsv_export_current' );
    }

    public function write ($data) {
        $this->load();

        if ( ! $this->current_run) {
            return;
        }

        $fp = fopen( $this->current_run[ 'filename' ], 'a' );
        fputcsv( $fp, $data );
        fclose( $fp );
    }

}