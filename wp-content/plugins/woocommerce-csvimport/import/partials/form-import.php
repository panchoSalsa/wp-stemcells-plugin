<?php
/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 20/08/16
 * Time: 21:38
 */
?>
<form method="post" enctype="multipart/form-data">
    <?php wp_nonce_field ('import_file', 'allaerd' ); ?>
    <input name="action" type="hidden" value="import">
    <table class="form-table">
        <tbody>
        <tr>
            <th>
                <label>File</label>
            </th>
            <td>
                <?php include 'parts/file.php';?>
            </td>
        </tr>
        <tr>
            <th>
                <label>Separator</label>
            </th>
            <td>
                <?php include 'parts/separator.php'; ?>
            </td>
        </tr>
        <tr>
            <th>
                <label>First row is the header</label>
            </th>
            <td>
                <?php include 'parts/firstrow.php'; ?>
            </td>
        </tr>
        <tr>
            <th>
                <label>Header</label>
            </th>
            <td>
                <?php include 'parts/header.php'; ?>
            </td>
        </tr>
        </tbody>
    </table>
    <input type="submit" value="Load" class="button-primary" name="Submit">
</form>