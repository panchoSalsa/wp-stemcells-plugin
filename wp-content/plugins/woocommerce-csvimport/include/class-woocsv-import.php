<?php

class woocsv_import
{
    public $logger;

    public $separator;

    public $addons;

    public $api_url = 'http://allaerd.org/api/wc-api/check_for_updates';

    public $upload_dir;

    public $import_log;

    public $options;

    public $header;

    public $headers = array ();

    public $message;

    public $version = '3.1.3';

    public $options_default = array (
        'woocsv_separator'         => ',',
        'woocsv_skip_first_line'   => 1,
        'woocsv_blocksize'         => 1,
        'woocsv_merge_products'    => 1,
        'woocsv_add_to_categories' => 1,
        'woocsv_debug'             => 0,
        'woocsv_match_by'          => 'sku',
        'woocsv_roles'             => array ( 'shop_manager' ),
        'woocsv_match_author_by'   => 'login',
        'woocsv_convert_to_utf8'   => 1,
    );

    public $fields = array (
        0  => 'sku',
        1  => 'post_name',
        2  => 'post_status',
        3  => 'post_title',
        4  => 'post_content',
        5  => 'post_excerpt',
        6  => 'category',
        7  => 'tags',
        8  => 'stock',
        10 => 'regular_price',
        11 => 'sale_price',
        12 => 'weight',
        13 => 'length',
        14 => 'width',
        15 => 'height',
        17 => 'tax_status',
        18 => 'tax_class',
        19 => 'stock_status',    // instock, outofstock
        20 => 'visibility',      // visible, catelog, search, hidden
        21 => 'backorders',      // yes,no
        22 => 'featured',        // yes,no
        23 => 'manage_stock',    // yes,no
        24 => 'featured_image',
        25 => 'product_gallery',
        26 => 'shipping_class',
        27 => 'comment_status',  //closed, open
        28 => 'change_stock',    // +1 -1 + 5 -8
        29 => 'ID',
        30 => 'ping_status',     // open,closed
        31 => 'menu_order',
        32 => 'post_author',     //user name or nice name of an user
        33 => 'post_date',
    );


    public function __construct ()
    {

        $this->logger = new Allaerd\LogToFile();

        //load dependencies
        $this->load_dependenies();

        // activation hook
        register_activation_hook( __FILE__, array ( $this, 'install' ) );

        //load options
        $this->set_options();

        //check install
        $this->check_install();

        //fill header
        $this->set_header();

        //add ajax
        add_action( 'wp_ajax_run_import', array ( $this, 'run_import' ) );

        $this->upload_dir = wp_upload_dir();
        
        $this->fields = apply_filters('allaerd_importer_fields', $this->fields);
    }

    public function load_dependenies ()
    {

        //admin
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woocsv-import-admin.php';
        new woocsv_import_admin();

        //settings
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'include/class-woocsv-import-settings.php';
        new woocsv_import_settings();

        //main product
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'include/class-woocsv-product.php';
    }

    public function set_options ()
    {
        $options = $this->options_default;

        foreach ($options as $key => $value) {
            if (get_option( $key ) !== FALSE) {
                $options[ substr( $key, 7 ) ] = get_option( $key );
            } else {
                update_option( $key, $value );
            }
        }
        $this->options = $options;
    }

    public function check_install ()
    {
        $message = $this->message;

        //new way
        if (!get_option( 'woocsv_options' )) {
            update_option( 'woocsv_options', $this->options );
        }


        $upload_dir = wp_upload_dir();
        $dir = $upload_dir[ 'basedir' ] . '/csvimport/';
        if (!is_dir( $dir )) {
            @mkdir( $dir );
        }

        if (!is_writable( $upload_dir[ 'basedir' ] . '/csvimport/' )) {
            $message .= __( 'Upload directory is not writable, please check you permissions', 'woocommerce-csvimport' );
        }

        $this->message = $message;
        if ($message) {
            add_action( 'admin_notices', array ( $this, 'show_warning' ) );
        }

    }

    public function set_header ()
    {

        $header = get_option( 'woocsv_header' );
        if ($header) {
            $this->header = $header;
        }

        $headers = get_option( 'woocsv_headers' );
        if ($headers) {
            $this->headers = $headers;
        }
    }

    public function get_roles ()
    {
        return get_option( 'woocsv_roles' );
    }

    public function get_match_author_by ()
    {
        return get_option( 'woocsv_match_author_by' );
    }

    public function get_match_by ()
    {
        return get_option( 'woocsv_match_by' );
    }

    public function get_add_to_categories ()
    {
        return get_option( 'woocsv_add_to_categories' );
    }

    public function get_merge_products ()
    {
        return get_option( 'woocsv_merge_products' );
    }

    public function get_blocksize ()
    {
        return get_option( 'woocsv_blocksize' );
    }

    public function get_separator ()
    {
        return get_option( 'woocsv_separator' );
    }

    public function get_convert_to_utf8 ()
    {
        return get_option( 'woocsv_convert_to_utf8' );
    }

    public function install ()
    {
        $upload_dir = wp_upload_dir();
        $dir = $upload_dir[ 'basedir' ] . '/csvimport/';
        @mkdir( $dir );

        //create options
        $this->set_options();
    }

    public function show_warning ()
    {
        global $current_screen;
        if ($current_screen->parent_base == 'woocsv_import') {
            echo '<div class="error"><p>' . $this->message . '</p></div>';
        }
    }

    public function handle_file_upload ($from_location, $filename)
    {
        do_action( 'woocsv_before_csv_upload', $filename );
        $upload_dir = wp_upload_dir();
        $to_location = $upload_dir[ 'basedir' ] . '/csvimport/' . $filename;
        if (@move_uploaded_file( $from_location, $to_location )) {
            do_action( 'woocsv_after_csv_upload', $filename );

            return $to_location;
        } else {
            return FALSE;
        }
    }

    public function run_import ()
    {
        global $woocsv_product;

        /**
         * Are we starting for the first time, than create a batch and continue, else just pick uo the batch code and start where you left
         */
        do_action( 'woocsv_start_import' );

        if (empty($_POST[ 'batch_code' ])) {
            //create a new batch
            $batch_code = woocsv_batches::create();
            if ($batch_code) {

                //get max time we have and set the block size
                $max_execution_time = @ini_get( 'max_execution_time' );
                if ($max_execution_time == 0) {
                    $max_execution_time = 30;
                }

                $block_size = $this->get_blocksize();

                $data = array (
                    'filename'           => $_POST[ 'filename' ],
                    'row'                => 0,
                    'block_size'         => $block_size,
                    'header_name'        => $_POST[ 'header_name' ],
                    'seperator'          => $_POST[ 'seperator' ],
                    'total_rows'         => (int)$_POST[ 'total_rows' ],
                    'start_date'         => time(),
                    'max_execution_time' => $max_execution_time,
                );
                woocsv_batches::update( $batch_code, $data );

                do_action('woocsv_batch_created');

            } else {
                //@todo die nice
            }

            //and get the batch
            $batch = woocsv_batches::get_batch( $batch_code );
        } else {
            $batch_code = $_POST[ 'batch_code' ];
            $batch = woocsv_batches::get_batch( $_POST[ 'batch_code' ] );
        }


        $this->setLoggerFilename( $batch_code );

        //lets check if we are done?
        if ($batch[ 'row' ] >= $batch[ 'total_rows' ]) {
            $batch[ 'end_date' ] = time();
            $batch[ 'status' ] = 'done';
            woocsv_batches::update( $batch_code, $batch );

            do_action( 'woocsv_after_import_finished' );

            //@todo DIE NICE
            $this->die_nicer( $batch_code, $batch );
        }

        // do we need to skip the first line?
        if ($this->get_skip_first_line() == 1 && $batch[ 'row' ] == 0) {
            $batch[ 'row' ] = 1;
            $this->logger->log( __( '-- Skipping the first line', 'woocommerce-csvimport' ) );
        }

        //get the from and till
        $from = $batch[ 'row' ];
        $till = (($batch[ 'row' ] + $batch[ 'block_size' ]) < $batch[ 'total_rows' ]) ? ($batch[ 'row' ] + $batch[ 'block_size' ]) : $batch[ 'total_rows' ];

        //get the lines
        $lines = $this->get_lines_from_file( $batch[ 'filename' ], $from, $till, $batch[ 'seperator' ] );

        //get the header
        $header = $this->get_header_from_name( $batch[ 'header_name' ] );

        $this->toggleCache( TRUE );

        $time_started = microtime( TRUE );

        //loop over the lines and fill,pase and save the lines
        foreach ($lines[ 'lines' ] as $line) {

            //reset time ever time around
            @set_time_limit( 0 );
            //new one and fill in the header and the raw data

            $woocsv_product = new woocsv_import_product ( $this->logger );

            $woocsv_product->header = $header;
            $woocsv_product->raw_data = $line;

            //fill it, parse it and save it
            $woocsv_product->fill_in_data();

            $this->logger->log( __( '-----> Row', 'woocommerce-csvimport' ) );

            $woocsv_product->parse_data();



            $woocsv_product->save();
            if ($woocsv_product->log) {
                $this->logger->log( __( $woocsv_product->log, 'woocommerce-csvimport' ) );
            }



            //write tot log if debug is on
            if ($this->get_debug() == 0) {
                $this->logger->log( '--->debug dump', 'woocommerce-csvimport' );
                $this->logger->log( $woocsv_product, 'woocommerce-csvimport'  );
            }

            $this->logger->log( __( '-----> end row', 'woocommerce-csvimport' ) );

            //goto the next row
            $batch[ 'row' ]++;

            //delete transionts
            if (function_exists( 'wc_delete_product_transients' )) {
                wc_delete_product_transients( $woocsv_product->body[ 'ID' ] );
            }
        }
        $time_finished = microtime( TRUE );
        if (!$this->get_blocksize()) {

            $time_factor = ceil( ($batch[ 'max_execution_time' ] - ($time_finished - $time_started)) / $batch[ 'max_execution_time' ] * 100 );

            switch ($time_factor) {
                case $time_factor > 90:
                    $block_size = 10;
                    break;
                case $time_factor > 50:
                    $block_size = 5;
                    break;
                case $time_factor > 10:
                    $block_size = 1;
                    break;
                default:
                    $block_size = 0;
            }

            $batch[ 'block_size' ] += $block_size;

            woocsv_batches::update( $batch_code, $batch );
        }

        $this->die_nicer( $batch_code, $batch );
    }

    public function die_nicer ($batch_code, $batch)
    {

        $this->toggleCache();

        //are we done?
        if ($batch[ 'status' ] == 'done') {
            $post_data[ 'done' ] = 1;
        } else {
            $post_data[ 'batch_code' ] = $batch_code;
            $post_data[ 'status' ] = 0;
        }

        woocsv_batches::update( $batch_code, $batch );

        //always clean to prevent errors
        @ob_get_clean();

        $post_data[ 'batch' ] = $batch;

        echo json_encode( $post_data );
        unset($post_data);
        wp_die();
    }

    public function get_debug ()
    {
        return get_option( 'woocsv_debug' );
    }

    public function get_skip_first_line ()
    {
        return get_option( 'woocsv_skip_first_line' );
    }

    public function get_lines_from_file ($filename, $from, $till, $seperator, $get_total_rows = FALSE)
    {

        ini_set( "auto_detect_line_endings", TRUE );

        $row = 0;
        $lines = array ();
        $handle = fopen( $filename, "r" );

        while (($line = fgetcsv( $handle, 0, $seperator )) !== FALSE) {

            if ($row >= $from && $row <= $till) {

                if (is_array( $line ) && $this->get_convert_to_utf8()) {
                    foreach ($line as $key => $value) {

                        //setting 1
                        if ($this->get_convert_to_utf8() == 1) {
                            $line[ $key ] = utf8_encode( $value );
                        }

                        //setting 2
                        if ($this->get_convert_to_utf8() == 2 && function_exists( 'mb_convert_encoding' )) {
                            $line[ $key ] = mb_convert_encoding( $value, 'UTF-8', '   auto' );
                        }
                    }
                }

                $lines[] = $line;
            }

            if (!$get_total_rows && $row > $till) {
                //return if we do not need the totals
                fclose( $handle );

                return array ( 'lines' => $lines );
            }

            $row++;
        }

        return array ( 'lines' => $lines, 'total_rows' => $row );
    }


    public function get_header_from_name ($header_name)
    {
        if (isset ($this->headers[ $header_name ])) {
            return $this->headers[ $header_name ];
        } else {
            return FALSE;
        }
    }

    public function setLoggerFilename ($batch_code)
    {
        $this->logger->setFilename( $this->upload_dir[ 'basedir' ] . '/csvimport/' . $batch_code . '.log' );
    }

    public function toggleCache ($status = FALSE)
    {
        if (function_exists( 'wp_suspend_cache_invalidation' )) {
            wp_suspend_cache_invalidation( $status );
        }
        if (function_exists( 'wp_defer_term_counting ' )) {
            wp_defer_term_counting( $status );
        }
    }

}