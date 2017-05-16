<?php

namespace Allaerd\Export;

/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 25/03/16
 * Time: 09:19
 */
/**
 * Class woocsvExportProduct
 * @package AEM\exporter
 */
class woocsvExportProduct
{

    /**
     * @var array
     */
    public $body = array (
        "ID",
        "post_author",
        "post_date",
        "post_date_gmt",
        "post_content",
        "post_title",
        "post_excerpt",
        "post_status",
        "comment_status",
        "ping_status",
        "post_password",
        "post_name",
        "to_ping",
        "pinged",
        "post_modified",
        "post_modified_gmt",
        "post_content_filtered",
        "post_parent",
        "guid",
        "menu_order",
        "post_type",
        "post_mime_type",
        "comment_count",
        "product_type",
    );

    /**
     * @var array
     */
    public $meta = array (
        '_backorders',
        '_featured',
        '_length',
        '_manage_stock',
        '_price',
        '_product_url',
        '_purchase_note',
        '_regular_price',
        '_sale_price',
        '_shipping_class',
        '_sku',
        '_sold_individually',
        '_stock',
        '_stock_status',
        '_tax_class',
        '_tax_status',
        'total_sales',
        '_virtual',
        '_visibility',
        '_weight',
        '_height',
        '_width',
        '_default_attributes',
        '_variation_description',
    );

    /**
     * @var array
     */
    public $attributes = array ();

    /**
     * @var array
     */
    public $category = array ();

    /**
     * @var array
     */
    public $tags = array ();

    /**
     * @var array
     */
    public $fields_needed_for_the_importer = array (
        'product_type',
        'attributes',
        'variations',
        'categories',
        'tags',
        'product_gallery',
        'product_gallery_name',
        'featured_image',
        'featured_image_name',
    );

    /**
     * woocsvExportProduct constructor.
     */
    public function __construct ()
    {
        $this->fillMeta();

        $this->fillAttributes();
    }

    /**
     *
     */
    public function fillMeta ()
    {
        global $wpdb;
        $metaFields = $wpdb->get_col( "
            select distinct b.meta_key from $wpdb->posts a, $wpdb->postmeta b
            where post_type in ('product')
            and a.ID = b.post_id
            order by b.meta_key;
        " );

        $this->addToMeta( $metaFields );

    }

    /**
     *
     */
    public function fillAttributes ()
    {

        $attributes = $this->getAttributes();

        foreach ($attributes as $attribute) {
            $this->attributes[] = 'pa_' . $attribute;
        }

    }

    /**
     * @param array $body
     */
    public function addToMeta ($value)
    {
        if (is_array( $value )) {
            $this->meta = array_merge( $this->meta, $value );
        }

        if (is_string( $value )) {
            $this->meta[] = $value;
        }

    }

    /**
     * @return array
     */
    public function allFields ()
    {
        return array_unique( array_merge( $this->body, $this->meta, $this->attributes,
            $this->fields_needed_for_the_importer ) );
    }

    /**
     * @param $wpdb
     * @return array
     */
    public function getAttributes ()
    {

        $woocsvAttributes = new woocsvAttributes;

        return $woocsvAttributes->all();
    }

}