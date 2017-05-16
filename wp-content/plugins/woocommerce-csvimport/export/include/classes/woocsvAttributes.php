<?php
/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 03/04/16
 * Time: 10:44
 */

namespace Allaerd\Export;


class woocsvAttributes
{

    public function all ()
    {
        global $wpdb;
        $attributes = $wpdb->get_col( "
            select attribute_name
            from {$wpdb->prefix}woocommerce_attribute_taxonomies
        " );

        return $attributes;
    }

}