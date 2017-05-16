<?php

/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 14/02/16
 * Time: 15:15
 */

namespace Allaerd;

class Options
{
    public $options = array (
        'woocsv_add_to_categories'   => 1,
        'woocsv_blocksize'           => 'auto',
        'woocsv_convert_to_utf8'     => 0,
        'woocsv_debug'               => 0,
        'woocsv_match_author_by'     => 'email',
        'woocsv_match_by'            => 'sku',
        'woocsv_merge_products'      => 1,
        'woocsv_roles'               => array ( 'shop_manager' ),
        'woocsv_separator'           => ';',
        'woocsv_skip_first_line'     => 1,
        'woocsv_curl_followlocation' => 0,
    );

    public function get ( $name )
    {
        if ( !key_exists ( $name, $this->options ) )
        {
            return false;
        }

        return get_option ( $name );
    }

    public function saveOption ( $name, $value )
    {
        if ( in_array ( $name, $this->options ) )
        {
            return false;
        }

        return update_option ( $name, $value );
    }

    public function saveDefaultOptionsAndValues ()
    {
        foreach ( $this->options as $key => $value )
        {
            add_option ( $key, $value );
        }
    }

}



