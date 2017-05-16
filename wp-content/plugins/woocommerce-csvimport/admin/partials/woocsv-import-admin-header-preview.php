
<?php
/*
	*
	* header preview
	*
*/
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <h2><?php echo __( 'Headers', 'woocommerce-csvimport' ); ?></h2>
    <ul class="subsubsub">
        <li><a href="<?php echo admin_url( 'admin.php?page=woocsv_import&amp;tab=import' ); ?>"><?php echo __( 'Import',
                    'woocommerce-csvimport' ); ?></a> |
        </li>
        <li><a href="<?php echo admin_url( 'admin.php?page=woocsv_import&amp;tab=headers' ); ?>"
               class="current"><?php echo __( 'Headers', 'woocommerce-csvimport' ); ?></a></li>
    </ul>
    <br class="clear">
    <hr>
    <?php
    global $woocsv_import;

    $filename = $woocsv_import->handle_file_upload( $_FILES[ 'csvfile' ][ 'tmp_name' ],
        $_FILES[ 'csvfile' ][ 'name' ] );
    $headers = get_option( 'woocsv_headers' );
    if ($headers &&  array_key_exists( $_FILES[ 'csvfile' ][ 'name' ], $headers )) {
        $saved_fields = $headers[ $_FILES[ 'csvfile' ][ 'name' ] ];
    } else {
        $saved_fields = array ();
    }

    if (!$filename) {
        wp_die( __( 'Could not upload file', 'woocommerce-csvimport' ) );
    }

    $lines_from_file = $woocsv_import->get_lines_from_file( $filename, 0, 4, $woocsv_import->get_separator(), TRUE );
    $lines = $lines_from_file[ 'lines' ];
    $total_rows = $lines_from_file[ 'total_rows' ];


    //set the amount of rows
    $length = count( $lines[ 0 ] );


    //check to see if the we might have the wrong separator
    if (count( $lines[ 0 ] ) == 1) {
        echo '<h2>' . __( 'I think you have the wrong separator', 'woocommerce-csvimport' ) . '</h2>';
        echo '<p>' . __( 'Please goto the settings page and change your separator!', 'woocommerce-csvimport' ) . '</p>';

        return;
    }

    //hook after header is done
    do_action( 'woocsv_header_preview', $woocsv_import->header );

    function woocsv_echo_header_preview_option ($field, $lines, $i, $saved_fields)
    {
        // if field of is the same as the saved field in the same place -> selected
        // if field is the same header field --> selected
        $original = $field;
        $field = trim( strtolower( $field ) );
        $saved_field = isset($saved_fields[ $i ]) ? trim( strtolower( $saved_fields[ $i ] ) ) : '';
        $header_field = trim( strtolower( $lines[ 0 ][ $i ] ) );

        if ($field == $saved_field) {
            return '<option value="' . $original . '" selected>';
        }

        if ($field == $header_field) {
            return '<option value="' . $original . '" selected>';
        }

        return '<option value="' . $original . '">';
    }


    ?>
    <h2><?php echo __( 'Header preview', 'woocommerce-csvimport' ); ?></h2>
    <form id="header_prieview_form" method="post">
        <table class="widefat">
            <thead>
            <tr>
                <th><?php echo __( 'Fields', 'woocommerce-csvimport' ); ?></th>
                <th><?php echo __( 'Row 1', 'woocommerce-csvimport' ); ?></th>
                <th><?php echo __( 'Row 2', 'woocommerce-csvimport' ); ?></th>
                <th><?php echo __( 'Row 3', 'woocommerce-csvimport' ); ?></th>
                <th><?php echo __( 'Row 4', 'woocommerce-csvimport' ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php for ($i = 0; $i <= $length - 1; $i++) : ?>
                <tr class="<?php echo ($i % 2 == 0) ? '' : 'alt'; ?>">
                    <td>
                        <select name="fields_<?php echo $i; ?>">
                            <option value="skip"><?php echo __( 'Skip', 'woocommerce-csvimport' ); ?></option>
                            <?php
                            // loop through the fields and check if the match the defined ones.
                            foreach (array_unique( $woocsv_import->fields ) as $field) : ?>
                                <?php echo woocsv_echo_header_preview_option( $field, $lines, $i, $saved_fields ); ?>
                                <?php echo trim( $field ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><?php if (isset($lines[ 0 ][ $i ])) {
                            echo $lines[ 0 ][ $i ];
                        } ?></td>
                    <td><?php if (isset($lines[ 1 ][ $i ])) {
                            echo $lines[ 1 ][ $i ];
                        } ?></td>
                    <td><?php if (isset($lines[ 2 ][ $i ])) {
                            echo $lines[ 2 ][ $i ];
                        } ?></td>
                    <td><?php if (isset($lines[ 3 ][ $i ])) {
                            echo $lines[ 3 ][ $i ];
                        } ?></td>
                </tr>
            <?php endfor; ?>
            </tbody>
            <tfoot>
            <tr>
                <!-- @since 3.0.5 use the filename as a default value for the header name -->
                <th><input required type="text" class="regular-text" name="header_name" id="header_name"
                           value="<?php echo $_FILES[ 'csvfile' ][ 'name' ] ?>" placeholder="The name of your header">
                </th>
                <th>
                    <button type="submit" class="button-primary"><?php echo __( 'save', 'woocsv-import' ); ?></button>
                </th>
            </tr>
            </tfoot>
        </table>
        <input id="text" name="action" type="hidden" value="save_header_preview"/>
        <?php wp_nonce_field( 'save_header_preview', 'save_header_preview' ); ?>
    </form>

</div>

<script>
    <!--@since 3.0.5 use keyup instead of keypress to take backspace into account-->

    jQuery('#header_name, select').keyup(function (e) {
        var chars = jQuery('#header_name').val();

        if (chars.length > 0) {
            jQuery('button').prop("disabled", false);
        } else {
            jQuery('button').prop("disabled", true);
        }

        if (e.which == 13) {
            e.preventDefault();
        }
    });

</script>

