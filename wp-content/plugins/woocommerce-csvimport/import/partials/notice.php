<?php /**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 22/08/16
 * Time: 10:53
 */
?>

<?php if ( isset($uploaded_file) && is_wp_error($uploaded_file)) : ?>
    <div class="notice notice-error is-dismissible">
        <?php foreach ($uploaded_file->errors[ 'upload_error' ] as $error) : ?>
            <p><?php echo $error ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>