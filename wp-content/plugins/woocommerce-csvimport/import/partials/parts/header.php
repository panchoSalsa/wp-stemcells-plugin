<?php
/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 22/08/16
 * Time: 10:07
 */
?>

<select name="header">
    <?php foreach (allaerd_importer()->Header->all() as $header) : ?>
        <option value="<?php echo $header; ?>"><?php echo $header; ?></option>
    <?php endforeach;?>
</select>
