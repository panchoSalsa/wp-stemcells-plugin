<?php
/*
Plugin Name: my-plugin
Plugin URI:
Description: first plugin
Author: Francisco Franco
Author URI:localhost
Version: 0.1
*/

add_action("admin_menu", "addMenu");

function addMenu() {
    add_menu_page("Example Options", "Example Options", 5, "example-options", "exampleMenu");
}

