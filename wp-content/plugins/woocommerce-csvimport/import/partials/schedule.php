<?php
/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 20/08/16
 * Time: 10:55
 */
?>
<div class="wrap">
    <?php include 'header.php'; ?>
    <?php
    /**
     * Created by PhpStorm.
     * User: allaerd
     * Date: 20/08/16
     * Time: 21:38
     */
    ?>
    <form method="post">
        <?php wp_nonce_field('upload_file', 'upload_file'); ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label>URL</label>
                </th>
                <td>
                    <input placeholder="http://www.example.com/imports/import_file.csv" size="80" name="import_url" type="url">
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
                    <label>Header</label>
                </th>
                <td>
                    <select id="header_name" name="header_name">
                        <?php foreach (allaerd_importer()->Header->all() as $header) : ?>
                            <option value="<?php echo $header; ?>"><?php echo $header; ?></option>
                        <?php endforeach;?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>
                    <label>Schedule</label>
                </th>
                <td>
                    <input size="7" placeholder="Minute" name="minute" type="text">
                    <input size="7" placeholder="Hour" name="hour" type="text">
                    <input size="7" placeholder="Day" name="day" type="text">
                    <input size="7" placeholder="Month" name="month" type="text">
                    <input size="7" placeholder="Weekday" name="weekday" type="text">
                </td>
            </tr>
            </tbody>
        </table>
        <input type="submit" value="Load" class="button-primary" name="Submit">
    </form>
</div>