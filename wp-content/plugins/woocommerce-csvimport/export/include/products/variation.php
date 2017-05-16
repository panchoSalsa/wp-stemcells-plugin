<?php

/* TODO

attributes
variations

upsell / crosssells

*/
namespace Allaerd\Export;


class variation extends simple
{

    public function __construct($id, $fields, $writer)
    {
        parent::__construct($id, $fields, $writer);

    }

    public function setProductType()
    {
        $this->content[ 'product_type' ] = 'product_variation';
    }

    public function handleVariation()
    {

        $attributes = new woocsvAttributes();
        foreach ($attributes->all() as $atts) {
            $this->content[ 'pa_' . $atts ] = get_post_meta($this->id, 'attribute_pa_' . $atts, true);
        }
    }

    public function save()
    {
        $this->handleVariation();
        $this->writer->write($this->getContent());
    }

}
