<?php

namespace Allaerd\Extensions;

/**
 * Class woocommerce_jetpack
 * @package Allaerd\Extensions
 */
class woocommerce_jetpack
{
//        'wcj_wholesale_price_per_product_enabled',
//        'wcj_wholesale_price_discount_type',
//        'wcj_wholesale_price_levels_number',
//        'wcj_wholesale_price_level_min_qty_1',
//        'wcj_wholesale_price_level_discount_1',
//        'wcj_product_open_price_enabled',
//        'wcj_product_open_price_default_price',
//        'wcj_product_open_price_min_price',
//        'wcj_product_open_price_max_price',
//        'wcj_price_by_user_role_per_product_settings_enabled',
//        'wcj_product_price_by_formula_enabled',
//        'wcj_product_price_by_formula_calculation',
//        'wcj_product_price_by_formula_eval',
//        'wcj_product_price_by_formula_total_params',
//        'wcj_product_price_by_formula_param_1',
//        'wcj_multicurrency_per_product_regular_price_EUR',
//        'wcj_multicurrency_per_product_sale_price_EUR',

    /**
     * woocommerce_jetpack constructor.
     */
    public function __construct()
    {
        $this->hooks();
    }

    /**
     *
     */
    public function hooks()
    {
        add_filter('allaerd_importer_fields', array ($this, 'fields'), 10, 1);
        add_filter('allaerd_importer_fields', array ($this, 'addCountryGroupsFields'), 10, 1);
        add_action('woocsv_product_after_body_save', array ($this, 'saveCountryGroupsFields'), 10, 2);
        add_action('woocsv_product_after_body_save', array ($this, 'save'), 10, 2);
    }


    /**
     * @param $post_id
     * @param $product
     */
    public function save($post_id, $product)
    {

        $fields = array( 'wcj_currency_per_product_currency','wcj_multicurrency_base_price_currency');

        foreach ($fields as $meta_key) {
            $this->saveMetaGeneric($post_id,$product,$meta_key);
        }

    }

    /**
     * @param $post_id
     * @param $product
     */
    public function saveCountryGroupsFields($post_id, $product)
    {
        $countryGroups = get_option('wcj_price_by_country_total_groups_number');
        if (!$countryGroups) {
            return;
        }

        for ($x = 1; $x <= $countryGroups; $x++) {

            $meta_key = 'wcj_price_by_country_regular_price_local_' . $x;
            $this->saveMetaGeneric($post_id, $product, $meta_key);

            $meta_key = 'wcj_price_by_country_sale_price_local_' . $x;
            $this->saveMetaGeneric($post_id, $product, $meta_key);

            $meta_key = 'wcj_price_by_country_make_empty_price_local_' . $x;
            $this->saveMetaGeneric($post_id, $product, $meta_key);

        }
    }


    /**
     * @param $fields
     * @return array
     */
    public function fields($fields)
    {

        $fields[] = 'wcj_currency_per_product_currency';
        $fields[] = 'wcj_multicurrency_base_price_currency';

        return $fields;
    }

    /**
     * @param $fields
     * @return array
     */
    public function addCountryGroupsFields($fields)
    {
        $countryGroups = get_option('wcj_price_by_country_total_groups_number');
        for ($x = 0; $x <= $countryGroups; $x++) {
            $fields [] = 'wcj_price_by_country_regular_price_local_' . $x;
            $fields [] = 'wcj_price_by_country_sale_price_local_' . $x;
            $fields [] = 'wcj_price_by_country_make_empty_price_local_' . $x;
        }

        return $fields;
    }

    /**
     * @param $post_id
     * @param $product
     * @param $meta_key
     * @return boolean
     */
    public function saveMetaGeneric($post_id, $product, $meta_key)
    {
        $key = array_search($meta_key, $product->header);
        if ($key != false) {
            $value = $product->raw_data[ $key ];
            update_post_meta($post_id, '_' . $meta_key, $value);

            return true;
        }

        return false;
    }

}

new woocommerce_jetpack();

