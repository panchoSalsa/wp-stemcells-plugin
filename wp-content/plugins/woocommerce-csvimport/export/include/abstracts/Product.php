<?php
namespace Allaerd\Export;

abstract class Product
{

    public abstract function fillProduct ();

    public function fillBasic() {
        $this->body();
        $this->meta();

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