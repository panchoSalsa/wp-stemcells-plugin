<?php
namespace Allaerd;

/**
 * Class LogToFile
 * @package Allaerd\Woocsv
 */
class LogToFile implements Logger
{
    /**
     * @var string
     */
    public $filename = '';

    /**
     * LogToFile constructor.
     *
     * @param $filename
     */
    public function __construct ( $filename = NULL )
    {
        if ( $filename ) {
            $this->filename = $filename;
        }
    }

    /**
     * @param $batch_code
     * @param $data
     */
    public function log ( $data )
    {
        if ( !$data ) {
            return;
        }

        $fp = fopen ($this->filename, 'a');
        if ( is_array ($data) || is_object ($data) ) {
            $this->writeArray ($data, $fp);
        } elseif ( is_string ($data) ) {
            $this->writeString ($data, $fp);
        } else {
            $this->writeString ('Object could not be converted to a string', $fp);
        }

        fclose ($fp);
    }

    /**
     * @param $data
     * @param $fp
     */
    public function writeArray ( $data, $fp )
    {
        fwrite ($fp, serialize ($data) . PHP_EOL);
    }

    /**
     * @param $data
     * @param $fp
     */
    public function writeString ( $data, $fp )
    {
        fwrite ($fp, $data . PHP_EOL);
    }

    /**
     * @param string $filename
     */
    public function setFilename ( $filename )
    {
        $this->filename = $filename;
    }
}