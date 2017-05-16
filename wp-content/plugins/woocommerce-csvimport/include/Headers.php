<?php
/**
 * User: allaerd
 * Date: 14/02/16
 * Time: 20:49
 */

namespace Allaerd;


/**
 * Class woocsv_headers
 * @package AEM
 */
/**
 * Class Headers
 * @package Allaerd\Woocsv
 */
class Headers
{
    /**
     * @var Headers
     */
    public $headers = array ();

    /**
     * woocsv_headers constructor.
     * @param Headers $headers
     */
    public function __construct ()
    {
        $headers = get_option( 'woocsv_headers' );
        $this->headers = ($headers)?:array();
    }

    /**
     * @return mixed|void
     */
    public function all ()
    {
        return $this->headers;
    }

    /**
     * @param $code
     * @return bool
     */
    public function get ($key)
    {

        if (!array_key_exists( $key, $this->headers )) {
            return FALSE;
        }

        return $this->headers[ $key ];
    }

    /**
     * @param $key
     * @return bool
     */
    public function delete ($key)
    {
        if (!array_key_exists( $key, $this->headers )) {
            return FALSE;
        }

        unset ($this->headers[ $key ]);

        return update_option( 'woocsv_headers', $this->headers );
    }

    /**
     * @param $key
     * @param $header
     * @return bool
     */
    public function save ($key, $header)
    {
        $this->headers[ $key ] = $header;

        return update_option( 'woocsv_headers', $this->headers );
    }
}