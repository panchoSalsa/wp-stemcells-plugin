<?php

namespace Allaerd\Export;

class woocsvExport
{

    protected static $_instance = NULL;

    public $admin;

    public $product;

    public $runner;

    public $headers;

    public static function instance ()
    {
        if (is_null( self::$_instance )) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct ()
    {
        $this->loadDependenies();

        $this->loadActions();

        $this->loadFilters();
    }

    public function loadActions ()
    {
        //add menu
        add_action( 'admin_menu', array ( $this->admin, 'menu' ),99 );

        //activate
        register_activation_hook( __FILE__, array ( $this, 'activate' ) );

        //run ajax export
        add_action( 'wp_ajax_woocsv_export', array ( $this->runner, 'start' ) );
        add_action( 'wp_ajax_delete_export_file', array ( $this, 'delete_export_file' ) );
    }

    public function loadFilters ()
    {
        //nothing yet
    }

    public function loadDependenies ()
    {
        $this->headers = get_option( 'woocsv_headers' );
        $this->admin = new woocsvExportAdmin();
        $this->product = new woocsvExportProduct();
        $this->runner = new ajaxExport(  new csvWriter() );

    }

    public function activate ()
    {
        //nothing yet
    }

    public function delete_export_file ()
    {

        if (isset($_POST[ 'filename' ])) {
            @unlink( $_POST[ 'filename' ] );
        }
        wp_die( 0 );
    }
}