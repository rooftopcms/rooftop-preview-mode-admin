<div class="wrap">
    <h1>Preview Mode URL</h1>

    <form action="" method="post">
        <table class="form-table">
            <tr>
                <th scope="row">
                    URL
                </th>
                <td>
                    <?php
                    $url = ( isset($endpoint) && $endpoint ) ? $endpoint->url : '';
                    ?>

                    <input type="text" name="url" size="50" value="<?php echo $url ?>"/>
                </td>
            </tr>
        </table>

        <?php wp_nonce_field( 'rooftop-preview-mode-admin', 'preview-mode-admin-field-token' ); ?>

        <p class="submit">
            <input type="submit" value="Update URL" class="button button-primary" />
        </p>

    </form>
</div>
