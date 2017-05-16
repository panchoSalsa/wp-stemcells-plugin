<?php
/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 20/08/16
 * Time: 11:52
 */

namespace Allaerd\import;

abstract class Product
{

    public $product_type = 'simple';

    public $body = array (
        'ID'             => '',
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'post_title'     => '',
        'post_name'      => '',
        'post_date'      => '',
        'post_date_gmt'  => '',
        'post_content'   => '',
        'post_excerpt'   => '',
        'post_parent'    => 0,
        'post_password'  => '',
        'comment_status' => 'open',
        'ping_status'    => 'open',
        'menu_order'     => 0,
        'post_author'    => '',
    );


    public $meta = array (
        '_backorders'            => 'no',
        '_crosssell_ids'         => array (),
        '_download_expiry'       => '',
        '_download_limit'        => '',
        '_download_type'         => '',
        '_downloadable'          => 'no',
        '_downloadable_files'    => '',
        '_featured'              => 'no',
        '_height'                => '',
        '_length'                => '',
        '_manage_stock'          => 'yes',
        '_price'                 => '',
        '_product_image_gallery' => '',
        '_product_version'       => '',
        '_purchase_note'         => '',
        '_regular_price'         => '',
        '_sale_price'            => '',
        '_sale_price_dates_from' => '',
        '_sale_price_dates_to'   => '',
        '_sku'                   => '',
        '_sold_individually'     => '',
        '_stock'                 => '',
        '_stock_status'          => '',
        '_tax_class'             => '',
        '_tax_status'            => 'taxable',
        '_upsell_ids'            => array (),
        '_virtual'               => 'no',
        '_visibility'            => '',
        '_weight'                => '',
        '_width'                 => '',
    );


    public function __construct()
    {
        $this->body = apply_filters('allaerd_product_body_fields', $this->body);
        $this->meta = apply_filters('allaerd_product_meta_fields', $this->meta);

        $this->setProductType();
    }

    public function saveBody()
    {

        $product_id = wp_update_post($this->body, true);
        if (is_wp_error($product_id)) {
            return false;
        }

        do_action('allaerd_after_body_save', $product_id, $this->body);

        return $product_id;
    }

    public function saveMeta($product_id)
    {
        foreach ($this->meta as $key => $value) {
            update_post_meta($product_id, key, $value);
        }

        do_action('allaerd_after_meta_save', $product_id, $this->meta);
    }

    public function preloadBody($product_id)
    {
        if (!isset($product_id)) {
            return false;
        }

        $post = get_post($product_id, 'ARRAY_A');

        if (!is_wp_error($post)) {
            return false;
        }

        $this->body = $post;
    }

    public function preloadMeta($product_id)
    {
        foreach ($this->meta as $key => $value) {
            $this->meta[ $key ] = get_post_meta($product_id, $key);
        }
    }

    public abstract function  setProductType($product_type);
}
