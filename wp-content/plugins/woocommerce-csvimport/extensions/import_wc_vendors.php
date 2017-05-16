<?php

namespace Allaerd\Extensions;

class wc_vendors
{
    protected $fields = array ('pv_commission_rate');

    public function __construct()
    {
        $this->hooks();
    }

    public function hooks()
    {
        add_filter('allaerd_importer_fields', array ($this, 'fields'));
        add_action('woocsv_product_after_body_save', array ($this, 'save'), 10, 2);
    }

    public function fields($fields)
    {
        foreach ($this->fields as $field) {
            $fields[] = $field;
        }

        return $fields;
    }

    public function save($post_id, $product)
    {
        if (in_array('pv_commission_rate', $product->header)) {
            $value = array_search('pv_commission_rate', $product->header);
            update_post_meta($post_id, 'pv_commission_rate', is_numeric($value) ? (float)$value : false);
        }
    }

}

new wc_vendors();