<?php

/**
 * Class woocsv_batches
 */
class woocsv_batches
{

    /**
     * @var array
     */

    static $batch_data = array (
        'row'                => 0,
        'block_size'         => 1,
        'total_rows'         => 0,
        'filename'           => '',
        'status'             => 'new',
        'header_name'        => '',
        'seperator'          => ',',
        'start_date'         => '',
        'end_date'           => '',
        'creation_date'      => '',
        'schedule_date'      => '',
        'max_execution_time' => ''
    );

    /**
     * @param $batch_code
     * @todo improve error handling when batched or headers can not be found
     */
    public static function schedule ( $batch_code ) {
        global $woocsv_import;

        $batches = get_option ( 'woocsv_batches' );
        $headers = get_option ( 'woocsv_headers' );

        if ( isset( $batches[$batch_code] ) ) {
            //get batch
            $batch = $batches[$batch_code];
        } else {
            //delete batch?
            return false;
        }

        if ( isset( $headers[$batch['header_name']] ) ) {
            //get header
            $header = $headers[$batch['header_name']];
        } else {
            //delete header?
            return false;
        }

        //set batch status
        $batch['status'] = 'in progress';

        //skip the first row if needed
        if ( $batch['row'] = 0 && $woocsv_import->get_skip_first_line () ) {
            $batch['row'] = 1;
        }

        //get a few lines
        $lines = $woocsv_import->get_lines_from_file ( $batch['filename'], $batch['row'], $batch['row'] + 5, $batch['seperator'] );

        //loop them and import
        foreach ( $lines as $line ) {

            //create a new product
            $product = new woocsv_product ( $line, $header );

        }

        $batches[$batch_code] = $batch;
        update_option ( 'woocsv_batches', $batches );
    }

    /**
     * @param bool $batch_code
     * @return mixed
     */

    static function get_batch ( $batch_code = false ) {

        $batches = get_option ( 'woocsv_batches' );
        if ( isset ( $batches[$batch_code] ) ) {
            $batch = $batches[$batch_code];
        } else {
            $batch = false;
        }

        return $batch;
    }

    /**
     * @return bool|string
     */

    static function create () {

        //create a unique batch code
        $new_batch_code = self::unique_number ();

        //fill in the data
        $new_batch[$new_batch_code] = self::$batch_data;
        $new_batch[$new_batch_code]['creation_date'] = time ();

        //get the other batches
        $batches = get_option ( 'woocsv_batches' );

        //and prepend the new one
        if ($batches) {
            $batches = $new_batch + $batches;
        } else {
            $batches = $new_batch;
        }



        //return code if succeeded
        if ( update_option ( 'woocsv_batches', $batches ) ) {
            return $new_batch_code;
        } else {
            return false;
        }

    }

    /**
     * @return string
     */
    static function unique_number () {
        return substr ( md5 ( uniqid ( mt_rand (), true ) ), 0, 10 );
    }


    /**
     * @param null $batch_code
     * @param $data
     * @return bool
     */
    static function update ( $batch_code = null, $data ) {

        //is the function call correct?
        if ( !$batch_code && !is_array ( $data ) ) {
            return false;
        }

        //we can continue!

        $batches = get_option ( 'woocsv_batches' );

        if ( isset ( $batches[$batch_code] ) ) {
            $batch = $batches[$batch_code];
        }

        //only update batches keys that are in self data
        foreach ( self::$batch_data as $key => $value ) {

            $batch[$key] = ( isset ( $data[$key] ) ) ? $data[$key] : $value;
        }

        $batches[$batch_code] = $batch;
        update_option ( 'woocsv_batches', $batches );

        return true;
    }

    /**
     * when pressing start in the import screen on a batch, this function is called to schedule the event
     */
    static function start () {
        $batch_code = $_POST['batch'];
        wp_schedule_single_event ( time (), 'woocsv_schedule_batch', array ( $batch_code ) );
        wp_die ( 'done' );
    }

    /**
     * ajax function to delete all batches
     */

    static function delete_all () {
        global $woocsv_import;
        update_option ( 'woocsv_batches', array () );
        wp_die ();
    }

    /**
     * ajax function to delete the selected batch
     */
    static function delete () {
        if ( isset ( $_POST['batch_code'] ) ) {
            $batches = get_option ( 'woocsv_batches' );
            unset ( $batches[$_POST['batch_code']] );
            update_option ( 'woocsv_batches', $batches );
            wp_die ( $_POST['batch_code'] );
        }
        wp_die ( 0 );
    }

}

