<div id="gluu" class="wrap">
    <?php screen_icon('options-general'); ?>
    <h2>Go Live Update Urls</h2>

    <h4> This will replace all occurrences "in the entire database" of the old URL with the New URL.
    <br />
    Uncheck any tables that you would not like to update. </h4>
    <div class="error fade"><h4> Please Uncheck any Tables which may contain seralized data. The only table which is currently seralized data safe when using this plugin is <?php echo $table_prefix; ?>options.</h4></div>
    <strong><em>Like any other database updating tool, you should always perfrom a backup before running.</em></strong>
    <br>

    <form method="post">
        <?php //Make the boxes to select tables
            echo $this->makeCheckBoxes();
        ?>
        <table class="form-table">
            <tr>
                <th scope="row" style="width:150px;"><b>Old URL</b></th>
                <td>
                <input name="oldurl" type="text" id="oldurl" value="" style="width:300px;" />
                </td>
            </tr>
            <tr>
                <th scope="row" style="width:150px;"><b>New URL</b></th>
                <td>
                <input name="newurl" type="text" id="newurl" value="" style="width:300px;" />
                </td>
            </tr>
        </table>
        <p class="submit">
            <?php submit_button('Make it Happen', 'primary', 'gluu-submit'); ?>
        </p>
    </form>
</div>
