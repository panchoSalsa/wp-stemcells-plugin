<?php

namespace Allaerd\Export;

function woocsv_export() {
    return woocsvExport::instance();
}

define("WOOCSV_PLUGIN_PATH", dirname(__FILE__));

// Global for backwards compatibility.
$GLOBALS['woocsv_export'] = woocsv_export();