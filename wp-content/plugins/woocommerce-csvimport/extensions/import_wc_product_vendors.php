<?php

namespace Allaerd\Extensions;

class WC_Product_Vendors
{
    protected $fields = array ('wcpv_product_commission', 'wcpv_product_default_pass_shipping_tax', 'wcpv_vendor');

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
        $meta_key = 'wcpv_product_commission';
        $this->saveMetaKey($post_id, $product, $meta_key);

        $meta_key = 'wcpv_product_default_pass_shipping_tax';
        $this->saveMetaKey($post_id, $product, $meta_key);

        $meta_key = 'wcpv_vendor';
        $key = array_search($meta_key, $product->header);
        if ($key != false) {
            $this->handleVendor($post_id, $product, $key);
        }
    }

    public function handleVendor($post_id, $product, $key)
    {
        $vendor = $product->raw_data[ $key ];
        $vendor_term = term_exists($vendor,'wcpv_product_vendors');
        if ($vendor_term) {
            wp_set_object_terms ($post_id,$vendor,'wcpv_product_vendors',false);
        }
    }

    public function saveMetaKey($post_id, $product, $meta_key)
    {
        $key = array_search($meta_key, $product->header);
        if ($key != false) {
            $value = $product->raw_data[ $key ];
            update_post_meta($post_id, '_' . $meta_key, $value);
        }
    }

}

new WC_Product_Vendors();