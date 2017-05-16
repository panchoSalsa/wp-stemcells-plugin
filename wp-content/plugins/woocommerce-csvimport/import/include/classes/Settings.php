<?php
/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 20/08/16
 * Time: 11:03
 */

namespace Allaerd\Import;


class Settings
{

    /**
     * Settings constructor.
     */
    public function __construct()
    {
        add_action('allaerd_importer_settings', array ($this, 'test') );
    }

    public function test () {
        echo '<pre>';

        echo '</pre>';
    }

}