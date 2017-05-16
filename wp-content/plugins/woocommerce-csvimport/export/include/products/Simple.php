<?php
namespace Allaerd\Export;

class Simple extends Product
{

    public $id;

    public $content = array ();

    public $fields = array ();

    public $writer;

    public function __construct ($id = NULL, $fields, writerInterface $writer)
    {
        $this->id = $id;
        $this->fields = $fields;
        $this->writer = $writer;

        $this->buildContent( $this->fields );
        $this->setProductType();
        $this->fillProduct();

    }

    public function setProductType ()
    {
        $this->content[ 'product_type' ] = 'simple';
    }

    public function buildContent ($fields)
    {
        foreach ($fields as $field) {
            $this->content[ $field ] = '';
        }
    }

    public function fillProduct ()
    {
        $this->body();
        $this->meta();
        $this->categories();
        $this->tags();
        $this->shippingClass();
        $this->images();
        $this->attributes();
        $this->defaultAttributes();
        $this->setPostparentSku();

    }

    public function body ()
    {
        global $wpdb;
        $body = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID = %d", $this->id ), ARRAY_A );

        foreach ($body as $key => $value) {
            if (array_key_exists( $key, $this->content )) {
                $this->content[ $key ] = $value;
            }
        }
        unset ($body);
    }


    public function meta ()
    {
        global $wpdb;

        $allmeta = $wpdb->get_results( "select meta_key,meta_value from $wpdb->postmeta where post_id= $this->id" );

        foreach ($allmeta as $meta) {
            //if key in field lists add it to row
            if (array_key_exists( $meta->meta_key, $this->content )) {
                $this->content[ $meta->meta_key ] = $meta->meta_value;
            }
        }
    }

    public function categories ()
    {

        $cats = array ();

        $categories = wp_get_object_terms( $this->id, 'product_cat' );
        foreach ($categories as $category) {
            $cats[] = $this->getTermName( $category->term_id );
        }

        $this->content[ 'categories' ] = implode( '|', $cats );

    }

    public function getTermName ($term_id, $taxonomy = 'product_cat')
    {
        $term = get_term( $term_id, $taxonomy );

        if (is_wp_error( $term )) {
            return FALSE;
        }

        if ($term->parent) {
            return $this->getTermName( $term->parent ) . '->' . $term->name;
        }

        return $term->name;
    }

    public function shippingClass ()
    {
        $shippingClass = get_the_terms( $this->id, 'product_shipping_class' );
        if ($shippingClass && !is_wp_error( $shippingClass )) {
            $this->content[ '_shipping_class' ] = current( $shippingClass )->slug;
        }
    }


    public function tags ()
    {
        $tags = wp_get_object_terms( $this->id, 'product_tag', array ( 'fields' => 'names' ) );

        if (is_wp_error( $tags )) {
            return FALSE;
        }

        $this->content[ 'tags' ] = implode( '|', $tags );

        return true;
    }

    public function images ()
    {

        //featured_image
        $featured_image = get_post_meta( $this->id, '_thumbnail_id', TRUE );
        $this->content[ 'featured_image_name' ] = basename( wp_get_attachment_url( $featured_image ) );
        $this->content[ 'featured_image' ] = wp_get_attachment_url( $featured_image );

        //product_gallery
        $product_gallery = array ();
        $allimages = get_post_meta( $this->id, '_product_image_gallery', TRUE );
        $images = explode( ',', $allimages );
        foreach ($images as $image) {
            $product_gallery[] = wp_get_attachment_url( $image );
            $product_gallery_name[] = basename( wp_get_attachment_url( $image ) );
        }

        $this->content[ 'product_gallery' ] = implode( '|', $product_gallery );
        $this->content[ 'product_gallery_name' ] = implode( '|', $product_gallery_name );

    }

    public function attributes ()
    {
        $atts = array ();
        $vars = array ();

        $attributes = get_post_meta( $this->id, '_product_attributes', TRUE );

        if (!$attributes) {
            return;
        }

        foreach ($attributes as $attribute) {

            if ($attribute[ 'is_taxonomy' ] == 0) {
                continue;
            }

            $att = ltrim( $attribute[ 'name' ], 'pa_' ) . '->' . $attribute[ 'is_visible' ];

            if ($attribute[ 'is_variation' ] == 1) {
                $vars[] = $att;
            } else {
                $atts[] = $att;
            }

            $this->fillAttributesValues( $attribute[ 'name' ] );

        }


        if ($atts) {
            $this->content[ 'attributes' ] = implode( '|', $atts );
        }

        if ($vars) {
            $this->content[ 'variations' ] = implode( '|', $vars );
        }
    }

    public function fillAttributesValues ($taxonomy)
    {
        $cats = array ();
        $categories = wp_get_object_terms( $this->id, $taxonomy );
        foreach ($categories as $category) {
            $cats[] = $category->name;
        }

        $this->content[ $taxonomy ] = implode( '|', $cats );

    }


    public function getContent ()
    {
        $content = array();

        foreach ($this->fields as $field) {
            if ( $field == 'skip') {
                $content['skip'] = '';
                continue;
            }

            if ( array_key_exists($field,$this->content)) {
                $content[$field] = $this->content[$field];
            }

        }

        return $content;
    }


    public function save ()
    {
        $this->writer->write( $this->getContent() );
    }


    public function defaultAttributes ()
    {
        $atts = array ();
        $attributes = get_post_meta( $this->id, '_default_attributes', TRUE );

        if (!$attributes) {
            return false;
        }

        foreach ($attributes as $key => $value) {
            $atts[] = substr( $key, 3 ) . '->' . $value;
        }

        $this->content[ '_default_attributes' ] = implode( $atts, '|' );

        return true;
    }

    private function setPostparentSku()
    {
        $parent_id = $this->content[ 'post_parent' ];
        if (empty($parent_id)) {
            return;
        }

        $parent_sku = get_post_meta($parent_id, '_sku',true);
        if ($parent_sku) {
            $this->content[ 'post_parent' ] = $parent_sku;

            return;
        }

        $this->content[ 'post_parent' ] = '';

        return;

    }


}