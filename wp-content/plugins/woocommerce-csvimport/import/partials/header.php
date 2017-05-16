<?php
/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 20/08/16
 * Time: 21:25
 */

$current = allaerd_importer()->Admin->currentTab();
$tabs = allaerd_importer()->Admin->getTabs();

echo '<div id="icon-themes" class="icon32"><br></div>';
echo '<h2 class="nav-tab-wrapper">';
foreach ($tabs as $tab => $name) {
    $class = ($tab == $current) ? ' nav-tab-active' : '';
    echo "<a class='nav-tab$class' href='?page=allaerd-container&tab=$tab'>$name</a>";

}
echo '</h2>';