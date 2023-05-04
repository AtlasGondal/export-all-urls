<?php
require_once(plugin_dir_path(__FILE__) . 'functions.php');

/**
 *
 */
function eau_generate_html()
{

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $post_types = array(
        'any' => 'All Types (pages, posts, and custom post types)',
        'page' => 'Pages',
        'post' => 'Posts'
    );

    $post_status = array(
        'publish'   => 'Published',
        'pending'   => 'Pending',
        'draft'     => 'Draft & Auto Draft',
        'future'    => 'Future Scheduled',
        'private'   => 'Private',
        'trash'     => 'Trashed',
        'all'       => 'All (Published, Pending, Draft, Future Scheduled, Private & Trash)'
    );

    $export_types = array(
        'text'   => 'CSV File',
        'here'   => 'Display Here',
    );

    $users_list = array(
        'all'   => 'All'
    );

    $args = array(
        'public' => true,
        '_builtin' => false
    );

    $output = 'objects';

    $operator = 'and';

    $custom_post_types = get_post_types($args, $output, $operator);

    foreach ($custom_post_types as $post_type) {
        $post_types[$post_type->name] = $post_type->labels->singular_name;
    }

    $users = get_users();

    foreach ($users as $user) {
        $users_list[$user->data->ID] = $user->data->user_login;
    }

    $export_fields = eau_export_fields();

    $form_submitted = isset($_POST['form_submitted']) ? true : false;
    $selected_post_type = isset($_POST['post-type']) ? $_POST['post-type'] : 'any';
    $selected_export_fields = isset($_POST['export_fields']) ? $_POST['export_fields'] : ($form_submitted ? array() : array('url', 'title'));
    $selected_post_status = isset($_POST['post-status']) ? $_POST['post-status'] : 'publish';
    $selected_user = isset($_POST['post-author']) ? $_POST['post-author'] : 'all';
    $selected_export_type = isset($_POST['export-type']) ? $_POST['export-type'] : 'here';

    $file_path = wp_upload_dir();
    $file_name = 'export-all-urls-' . rand(111111, 999999);

?>

    <div class="wrap">

        <h2 align="center">Export Data from your Site</h2>

        <div class="eauWrapper">
            <div id="eauMainContainer" class="postbox eaucolumns">

                <div class="inside">

                    <form id="infoForm" method="post" action="">

                        <table class="form-table">

                            <tr>

                                <th>Select a Post Type to Extract Data: </th>

                                <td>

                                    <?php foreach ($post_types as $value => $label) : ?>
                                        <label><input type="radio" name="post-type" value="<?php echo $value; ?>" required="required" <?php echo $value == $selected_post_type ? 'checked' : ''; ?>> <?php echo $label; ?></label><br />
                                    <?php endforeach; ?>

                                </td>

                            </tr>

                            <tr>

                                <th>Export Fields:</th>

                                <td>

                                    <?php foreach ($export_fields as $value => $label) : ?>
                                        <label><input type="checkbox" name="export_fields[]" value="<?php echo $value; ?>" <?php echo in_array($value, $selected_export_fields) ? 'checked' : ''; ?>> <?php echo $label; ?></label><br />
                                    <?php endforeach; ?>

                                </td>

                            </tr>

                            <tr>

                                <th>Post Status:</th>

                                <td>

                                    <?php foreach ($post_status as $value => $label) : ?>
                                        <label><input type="radio" name="post-status" value="<?php echo $value; ?>" <?php echo $value == $selected_post_status ? 'checked' : ''; ?>> <?php echo $label; ?></label><br />
                                    <?php endforeach; ?>

                                </td>

                            </tr>

                            <tr>
                                <th></th>
                                <td><a href="#" id="moreFilterOptionsLabel" onclick="moreFilterOptions(); return false;">Show Filter Options</a></td>
                            </tr>

                            <tr class="filter-options" style="display: none">

                                <th>Date Range:</th>

                                <td>

                                    <label>From:<input type="date" id="posts-from" name="posts-from" onmouseleave="setMinValueForPostsUptoField()" onfocusout="setMinValueForPostsUptoField()" /></label>
                                    <label>To:<input type="date" id="posts-upto" name="posts-upto" /></label><br />


                                </td>

                            </tr>

                            <tr class="filter-options" style="display: none">

                                <th>By Author:</th>

                                <td>

                                    <?php foreach ($users_list as $value => $label) : ?>
                                        <label><input type="radio" name="post-author" value="<?php echo $value; ?>" required="required" <?php echo $value == $selected_user ? 'checked' : ''; ?>> <?php echo $label; ?></label><br />
                                    <?php endforeach; ?>

                                </td>

                            </tr>

                            <tr>
                                <th></th>
                                <td><a href="#" id="advanceOptionsLabel" onclick="showAdvanceOptions(); return false;">Show
                                        Advanced Options</a></td>
                            </tr>


                            <tr class="advance-options" style="display: none">

                                <th>Exclude Domain URL: </th>

                                <td>

                                    <label><input type="checkbox" name="exclude-domain" value="yes" <?php echo isset($_POST['exclude-domain']) ? 'checked' : ''; ?> /> Yes &nbsp;&nbsp;<code>Enable this option to remove the domain from URLs, e.g., 'example.com/sample-post/' becomes '/sample-post/</code>

                                </td>

                            </tr>

                            <tr class="advance-options" style="display: none">

                                <th>Number of Posts: <a href="#" title="Specify Post Range to Extract, It is very useful in case of Memory Out Error!" onclick="return false">?</a></th>

                                <td>

                                    <label><input type="radio" name="number-of-posts" checked value="all" required="required" onclick="hideRangeFields()" /> All</label><br />
                                    <label><input type="radio" name="number-of-posts" value="range" required="required" onclick="showRangeFields()" /> Specify Range</label><br />

                                    <div id="postRange" style="display: none">
                                        From: <input type="number" name="starting-point" placeholder="0" value="<?php echo isset($_POST['starting-point']) ? $_POST['starting-point'] : ''; ?>">
                                        To: <input type="number" name="ending-point" placeholder="500" value="<?php echo isset($_POST['ending-point']) ? $_POST['ending-point'] : ''; ?>">
                                    </div>

                                </td>

                            </tr>

                            <tr class="advance-options" style="display: none">

                                <th>CSV File Name: </th>

                                <td>

                                    <label><input type="text" name="csv-file-name" placeholder="An Error Occured" value="<?php echo $file_name; ?>" size="30%" /></label><br />
                                    <code><?php echo $file_path['path']; ?></code>


                                </td>


                            </tr>

                            <tr>

                                <th>Export Type:</th>

                                <td>

                                    <?php foreach ($export_types as $value => $label) : ?>
                                        <label><input type="radio" name="export-type" value="<?php echo $value; ?>" required="required" <?php echo $value == $selected_export_type ? 'checked' : ''; ?>> <?php echo $label; ?></label><br />
                                    <?php endforeach; ?>

                                </td>

                            </tr>

                            <tr>

                                <td></td>

                                <td>
                                    <input type="submit" name="export" class="button button-primary" value="Export Now" />
                                </td>

                            </tr>

                        </table>
                        <?php wp_nonce_field('export_urls'); ?>
                        <input type="hidden" name="form_submitted" value="1">

                    </form>


                </div>

            </div>
            <div id="eauSideContainer" class="eaucolumns">
                <div class="postbox">
                    <h3>Want to Support?</h3>
                    <div class="inside">
                        <p>If you enjoyed the plugin, and want to support:</p>
                        <ul>
                            <li>
                                <a href="https://AtlasGondal.com/contact-me/?utm_source=self&utm_medium=wp&utm_campaign=export-all-urls&utm_term=hire-me" target="_blank">Hire me</a> on a project
                            </li>
                            <li>Buy me a Coffee
                                <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YWT3BFURG6SGS&source=url" target="_blank"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" /> </a>

                            </li>
                        </ul>
                        <hr>
                        <h3>Wanna say Thanks?</h3>
                        <ul>
                            <li>Leave <a href="https://wordpress.org/support/plugin/export-all-urls/reviews/?filter=5#new-post" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> rating
                            </li>
                            <li>Tweet me: <a href="https://twitter.com/atlas_gondal" target="_blank">@Atlas_Gondal</a>
                            </li>
                        </ul>
                        <hr>
                        <h3>Got a Problem?</h3>
                        <p>If you want to report a bug or suggest new feature. You can:</p>
                        <ul>
                            <li>Create <a href="https://wordpress.org/support/plugin/export-all-urls/" target="_blank">Support
                                    Ticket</a></li>

                            <li>Write me an <a href="https://AtlasGondal.com/contact-me/?utm_source=self&utm_medium=wp&utm_campaign=export-all-urls&utm_term=write-an-email" target="_blank">Email</a></li>
                        </ul>
                        <strong>Reporting</strong> an issue is more effective than giving a <strong>1 star</strong> review, as it aids you, me, and the entire community. Kindly consider letting me help prior to leaving negative feedback.
                        <hr>
                        <h4 id="eauDevelopedBy">Developed by: <a href="https://AtlasGondal.com/?utm_source=self&utm_medium=wp&utm_campaign=export-all-urls&utm_term=developed-by" target="_blank">Atlas Gondal</a></h4>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .eauWrapper {
                display: -webkit-flex;
                display: -ms-flexbox;
                display: flex;
                -webkit-flex-wrap: wrap;
                -ms-flex-wrap: wrap;
                flex-wrap: wrap;
                overflow: hidden
            }

            #eauMainContainer {
                width: 75%;
                margin-bottom: 0
            }

            #eauSideContainer {
                width: 24%
            }

            #eauSideContainer .postbox:first-child {
                margin-left: 20px;
                padding-top: 10%;
                display: grid;
            }

            .eaucolumns {
                float: left;
                display: -webkit-flex;
                display: -ms-flexbox;
                display: flex;
                margin-top: 5px
            }

            #eauSideContainer .postbox {
                margin-bottom: 0;
                float: none
            }

            #eauSideContainer .inside {
                margin-bottom: 0
            }

            #eauSideContainer hr {
                width: 70%;
                margin: 30px auto
            }

            #eauSideContainer h3 {
                cursor: default;
                text-align: center;
                font-size: 16px
            }

            #eauSideContainer li {
                list-style: disclosure-closed;
                margin-left: 25px
            }

            #eauSideContainer li a img {
                display: inline-block;
                vertical-align: middle
            }

            #eauDevelopedBy {
                text-align: center
            }

            #outputData {
                border-collapse: collapse;
                width: 98%
            }

            #outputData tr:nth-child(even) {
                background-color: #fff
            }

            #outputData tr:hover {
                background-color: #ddd
            }

            #outputData th {
                background-color: #000;
                color: #fff;
                font-weight: bold;
            }

            #outputData td,
            #outputData th {
                text-align: left;
                padding: 8px
            }

            #outputData th:first-child {
                width: 4%
            }

            #outputData #postID {
                width: 6%
            }

            #outputData #postTitle {
                width: 25%
            }

            #outputData #postURL {
                width: 45%
            }

            #outputData #postCategories {
                width: 20%
            }

            #eauMainContainer code {
                font-size: 11px;
                background-color: #eee;
                padding-left: 5px;
                padding-right: 5px;
            }
        </style>

        <script type="text/javascript">
            function showRangeFields() {
                document.getElementById('postRange').style.display = 'block';
            }

            function hideRangeFields() {
                document.getElementById('postRange').style.display = 'none';
            }

            function showAdvanceOptions() {

                var rows = document.getElementsByClassName('advance-options');

                for (var i = 0; i < rows.length; i++) {
                    rows[i].style.display = 'table-row';
                }

                document.getElementById('advanceOptionsLabel').innerHTML = "Hide Advanced Options";
                document.getElementById('advanceOptionsLabel').setAttribute("onclick", "javascript: hideAdvanceOptions(); return false;");

            }

            function hideAdvanceOptions() {

                var rows = document.getElementsByClassName('advance-options');

                for (var i = 0; i < rows.length; i++) {
                    rows[i].style.display = 'none';
                }

                document.getElementById('advanceOptionsLabel').innerHTML = "Show Advanced Options";
                document.getElementById('advanceOptionsLabel').setAttribute("onclick", "javascript: showAdvanceOptions(); return false;");

            }

            function moreFilterOptions() {
                var rows = document.getElementsByClassName('filter-options');

                for (var i = 0; i < rows.length; i++) {
                    rows[i].style.display = 'table-row';
                }

                document.getElementById('moreFilterOptionsLabel').innerHTML = "Hide Filter Options";
                document.getElementById('moreFilterOptionsLabel').setAttribute("onclick", "javascript: lessFilterOptions(); return false;");

            }

            function lessFilterOptions() {
                var rows = document.getElementsByClassName('filter-options');

                for (var i = 0; i < rows.length; i++) {
                    rows[i].style.display = 'none';
                }

                document.getElementById('moreFilterOptionsLabel').innerHTML = "Show Filter Options";
                document.getElementById('moreFilterOptionsLabel').setAttribute("onclick", "javascript: moreFilterOptions(); return false;");

            }

            function setMinValueForPostsUptoField() {
                console.log(document.getElementById('posts-from').value);
                if (document.getElementById('posts-from').value != "") {
                    document.getElementById('posts-upto').setAttribute('min', document.getElementById('posts-from').value);
                }

            }
        </script>


    </div>


<?php
    if (isset($_POST['export'])) {

        if (isset($_REQUEST['_wpnonce'])) {
            $nonce = $_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'export_urls')) {
                echo "<div class='notice notice-error' style='width: 93%'>Security token validation failed!</div>";
                exit;
            }

            if (!empty($_POST['post-type']) && !empty($_POST['export-type']) && !empty($_POST['export_fields']) && !empty($_POST['post-status']) && !empty($_POST['post-author']) && !empty($_POST['number-of-posts'])) {

                $post_type = sanitize_text_field($_POST['post-type']);
                $export_type = sanitize_text_field($_POST['export-type']);
                $export_fields = map_deep($_POST['export_fields'], 'sanitize_text_field');
                $post_status = sanitize_text_field($_POST['post-status']);
                $post_author = sanitize_text_field($_POST['post-author']);
                $exclude_domain = isset($_POST['exclude-domain']) ? sanitize_text_field($_POST['exclude-domain']) : null;
                $number_of_posts = sanitize_text_field($_POST['number-of-posts']);
                $csv_name = sanitize_file_name($_POST['csv-file-name']);

                if ($number_of_posts == "range") {
                    $offset = absint($_POST['starting-point']);
                    $post_per_page = absint($_POST['ending-point']);

                    if (!isset($offset) || !isset($post_per_page)) {
                        echo "<div class='notice notice-error' style='width: 93%'>Sorry, you didn't specify starting and ending post range. Please <strong>Set Post Range</strong> OR <strong>Select All</strong> and try again! :)</div>";
                        exit;
                    }

                    $post_per_page = $post_per_page - $offset;
                } else {
                    $offset = 'all';
                    $post_per_page = 'all';
                }

                if ($export_type == 'text') {
                    if (empty($csv_name)) {
                        echo "<div class='notice notice-error' style='width: 93%'>Invalid/Missing CSV File Name!</div>";
                        exit;
                    }
                }

                $posts_from = sanitize_file_name($_POST['posts-from']);
                $posts_upto = sanitize_file_name($_POST['posts-upto']);

                if (!empty($posts_from) && !empty($posts_upto)) {

                    if ($posts_from > $posts_upto) {
                        echo "<div class='notice notice-error' style='width: 93%'>Sorry, invalid post date range. :)</div>";
                        exit;
                    }
                } else {
                    $posts_from = '';
                    $posts_upto = '';
                }

                eau_generate_output($post_type, $post_status, $post_author, $exclude_domain, $post_per_page, $offset, $export_type, $export_fields, $csv_name, $posts_from, $posts_upto);
            } else {
                echo "<div class='notice notice-error' style='width: 93%'>Sorry, you missed something, Please recheck above options, especially <strong>Export Fields</strong> and try again! :)</div>";
                exit;
            }
        } else {
            echo "<div class='notice notice-error' style='width: 93%'>Verification token is missing!</div>";
            exit;
        }
    } elseif (isset($_REQUEST['del']) && $_REQUEST['del'] == 'y') {
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'])) {
            echo "You are not authorized to perform this action!";
            exit();
        } else {
            $file = base64_decode($_REQUEST['f']);
            $path_info = pathinfo($file);
            $upload_dir = wp_upload_dir();

            if (($path_info['dirname'] == $upload_dir['path']) && ($path_info['extension'] == 'CSV')) {
                echo !empty($file) ? (file_exists($file) ? (!unlink($file) ? "<div class='notice notice-error' style='width: 97%'></div>Unable to delete file, please delete it manually!" : "<div class='updated' style='width: 97%'>You did great, the file was <strong>Deleted Successfully</strong>!</div>") : null) : "<div class='notice notice-error'>Missing file path.</div>";
            } else {
                die("<div class='error' style='width: 95.3%; margin-left: 2px;'>Sorry, the file verification failed. Arbitrary file removal is not allowed.</div>");
            }
        }
    }
}

eau_generate_html();
