<?php
/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 25/03/16
 * Time: 22:40
 */

namespace Allaerd\Export;


class variable extends simple implements productsInterface
{
    public function __construct ($id, $fields, $writer)
    {
        parent::__construct( $id, $fields, $writer );
    }

    public function setProductType () {
        $this->content[ 'product_type' ] = 'variation_master';
    }

    public function save () {
        $this->writer->write( $this->getContent() );
        $this->children();
    }


    public function children ()
    {
        /* !1.0.1 added post_per_page-1 */
        $variation_ids = get_posts( array (
            'posts_per_page' => -1,
            'post_type'      => 'product_variation',
            'post_parent'    => $this->id,
            'fields'         => 'ids',
        ) );

        foreach ($variation_ids as $variation_id) {
            $variation = new variation( $variation_id , $this->fields, $this->writer);
            $variation->save();
        }
    }
}