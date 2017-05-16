<?php

/**
 * Class woocsv_schedule_import
 */
class woocsv_schedule_import {

    static function import ($batch_code) {
        $batches = get_option('woocsv_batches');
        //check if the batch exists
        if (!isset($batches[$batch_code]))
            return;

        //it exists!!! lets continue;
        $batch = $batches[$batch_code];

        //see if we can influence the time limit

        //@TODO make an auto function to import as many rows as possible
        $max_execution_time_original = ini_get('max_execution_time');
        set_time_limit(0);
        $max_execution_time_new = ini_get('max_execution_time');

        $block_size = 1;


        //make sure if the row is set
        if (!isset ($batch['row'])) {
            $batch['row'] = 0;
        }

        //start
        if ($batch['row'] == 0 ) {
            woocsv_batches::update ($post_data,'processing row');
        }



    }
}
