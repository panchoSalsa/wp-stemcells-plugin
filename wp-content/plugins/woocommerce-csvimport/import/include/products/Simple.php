<?php
namespace Allaerd\Import;

class Simple extends Product
{
    public function setProductType ($product_type) {
        $this->product_type = $product_type;
    }

}