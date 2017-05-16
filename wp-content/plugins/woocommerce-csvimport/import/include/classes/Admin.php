<?php

namespace Allaerd\Import;

/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 10/08/16
 * Time: 13:01
 */

class Admin
{
    public $settings;

    public function __construct()
    {
        add_action('admin_menu', array ($this, 'menu'));
        $this->settings = new Settings();
    }


    public function menu()
    {
        add_menu_page('Importer', 'Importer', 'manage_woocommerce', 'allaerd-container', array ($this, 'import'), null, '58.1505323321');
    }

    public function import()
    {
        $this->handleRequest();
    }

    public function handleRequest()
    {

        if (isset($_POST[ 'action' ])) {
            $this->handlePostRequest();
        } else {
            include(ALLAERD_IMPORTER_PLUGIN_PATH . '/import/partials/' . $this->currentTab() . '.php');
        }
    }

    public function handlePostRequest()
    {
        $action = $_POST[ 'action' ];

        if ($action == 'import' && check_admin_referer('import_file', 'allaerd')) {
            return $this->importFile();
        }
        if ($action == 'schedule') {
            return $this->scheduleFile();
        }

        echo '....you should not be here....';
    }

    public function importFile()
    {
        $uploaded_file = allaerd_importer()->upload_file();

        if (is_wp_error($uploaded_file)) {
            include(ALLAERD_IMPORTER_PLUGIN_PATH . '/import/partials/import.php');
            return;
        }

        $file = get_post($uploaded_file);
        $header_key = (isset($_POST[ 'header' ]))?$_POST[ 'header' ]:'';
        if ($header_key) {
            $header = allaerd_importer()->Header->get($header_key);
        } else {
            $header = array ();
        }



        if ($file->post_mime_type == 'text/csv' ) {
            $separator = $_POST[ 'separator' ];
            $lines = allaerd_importer()->getLines($file->guid, 0, 5, $separator);
        }


        include(ALLAERD_IMPORTER_PLUGIN_PATH . '/import/partials/preview.php');
    }

    public function scheduleFile()
    {

    }

    public function currentTab()
    {
        return (isset($_GET[ 'tab' ]) && array_key_exists($_GET[ 'tab' ], $this->getTabs())) ? $_GET[ 'tab' ] : 'import';
    }

    public function getTabs()
    {
        return array ('import' => 'Import', 'schedule' => 'Schedule', 'settings' => 'Settings');
    }


}