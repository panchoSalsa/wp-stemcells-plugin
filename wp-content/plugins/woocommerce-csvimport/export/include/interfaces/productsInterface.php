<?php
/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 25/03/16
 * Time: 14:16
 */

namespace Allaerd\Export;


interface productsInterface
{
    public function getContent ();

    public function save ();
}