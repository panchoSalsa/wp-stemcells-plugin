<?php
namespace Allaerd\Import;

class Importer
{
    protected static $_instance = null;

    public $Scheduler;
    public $Admin;
    public $Header;

    public $fields;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct()
    {
        $this->setup();
        $this->hooks();
        $this->filters();
    }

    public function hooks()
    {
        register_activation_hook(__FILE__, array ('Allaerd\Importer', 'install'));
    }

    public function filters()
    {

    }

    static function install()
    {

    }


    public function setup()
    {
        $this->Scheduler = new Scheduler();
        $this->Admin = new Admin();
        $this->Header = new Headers();

        $this->fields = apply_filters('allaerd_importer_fields', $this->fields);
    }

    public function upload_file()
    {
        return media_handle_upload('file', null, array ('post_excerpt' => 'importer', 'post_content' => 'upload file'));
    }

    public function dd($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        die ();
    }

    public function getLines($filename, $from, $till, $separator, $get_total_rows = false)
    {

        ini_set("auto_detect_line_endings", true);

        $row = 0;
        $lines = array ();
        $handle = fopen($filename, "r");

        if ($get_total_rows) {
            while (($line = fgetcsv($handle, 0, $separator)) !== false) {
                $row++;
            }
        } else {
            while (($line = fgetcsv($handle, 0, $separator)) !== false) {

                if ($row >= $from && $row <= $till) {
                    $lines[] = $line;
                } else {
                    break;
                }

                $row++;
            }
        }

        fclose($handle);

        return array ('lines' => $lines, 'total_rows' => $row);
    }


}

