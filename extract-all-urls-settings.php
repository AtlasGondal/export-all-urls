<?php
require_once (plugin_dir_path(__FILE__) . 'functions.php');

/**
 *
 */
function eau_generate_html()
{

    if (!current_user_can('manage_options'))
    {
        wp_die(__('You do not have sufficient permissions to access this page.', 'export-all-urls'));
    }

    $custom_posts_names = array();
    $custom_posts_labels = array();
    $user_ids = array();
    $user_names = array();

    $args = array(
        'public' => true,
        '_builtin' => false
    );

    $output = 'objects';

    $operator = 'and';

    $post_types = get_post_types($args, $output, $operator);

    foreach ($post_types as $post_type)
    {

        $custom_posts_names[] = $post_type->name;
        $custom_posts_labels[] = $post_type
            ->labels->singular_name;

    }

    $users = get_users();

    foreach ($users as $user)
    {
        $user_ids[] = $user
            ->data->ID;
        $user_names[] = $user
            ->data->user_login;
    }

    $file_path = wp_upload_dir();
    $file_name = 'export-all-urls-' . rand(111111, 999999);

?>

    <div class="wrap">

        <h2 align="center"><?php esc_html_e( 'Export Data from your Site', 'export-all-urls' ); ?></h2>

        <div class="eauWrapper">
            <div id="eauMainContainer" class="postbox eaucolumns">

                <div class="inside">

                    <form id="infoForm" method="post">

                        <table class="form-table">

                            <tr>

                                <th><?php esc_html_e( 'Select a Post Type to Extract Data: ', 'export-all-urls' ); ?></th>

                                <td>

                                    <label><input type="radio" name="post-type" value="any" required="required" checked /><?php esc_html_e( 'All Types (pages, posts, and custom post types)', 'export-all-urls' ); ?></label><br/>
                                    <label><input type="radio" name="post-type" value="page" required="required"/><?php esc_html_e( 'Pages', 'export-all-urls' ); ?></label><br/>
                                    <label><input type="radio" name="post-type" value="post" required="required"/><?php esc_html_e( 'Posts', 'export-all-urls' ); ?></label><br/>

                                    <?php
    if (!empty($custom_posts_names) && !empty($custom_posts_labels))
    {
        for ($i = 0;$i < count($custom_posts_names);$i++)
        {
            echo '<label><input type="radio" name="post-type" value="' . $custom_posts_names[$i] . '" required="required" /> ' . $custom_posts_labels[$i] . ' Posts</label><br>';
        }
    }
?>

                                </td>

                            </tr>

                            <tr>

                                <th><?php esc_html_e( 'Export Fields:', 'export-all-urls' ); ?></th>

                                <td>

                                    <label><input type="checkbox" name="additional-data[]" value="postIDs"/><?php esc_html_e( 'Post IDs', 'export-all-urls' ); ?></label><br/>
                                    <label><input type="checkbox" name="additional-data[]" checked value="title"/><?php esc_html_e( 'Titles', 'export-all-urls' ); ?></label><br/>
                                    <label><input type="checkbox" name="additional-data[]" value="url"/><?php esc_html_e( 'URLs', 'export-all-urls' ); ?></label><br/>
                                    <label><input type="checkbox" name="additional-data[]" value="category"/><?php esc_html_e( 'Categories', 'export-all-urls' ); ?></label><br/>

                                </td>

                            </tr>

                            <tr>

                                <th><?php esc_html_e( 'Post Status:', 'export-all-urls' ); ?></th>

                                <td>

                                    <label><input type="radio" name="post-status" checked value="publish"/><?php esc_html_e( 'Published', 'export-all-urls' ); ?></label><br/>
                                    <label><input type="radio" name="post-status" value="pending"/><?php esc_html_e( 'Pending', 'export-all-urls' ); ?></label><br/>
                                    <label><input type="radio" name="post-status" value="draft"/><?php esc_html_e( 'Draft & Auto Draft', 'export-all-urls' ); ?></label><br/>
                                    <label><input type="radio" name="post-status" value="future"/><?php esc_html_e( 'Future Scheduled', 'export-all-urls' ); ?> </label><br/>
                                    <label><input type="radio" name="post-status" value="private"/><?php esc_html_e( 'Private', 'export-all-urls' ); ?></label><br/>
                                    <label><input type="radio" name="post-status" value="trash"/><?php esc_html_e( 'Trashed', 'export-all-urls' ); ?></label><br/>
                                    <label><input type="radio" name="post-status" value="all"/><?php esc_html_e( 'All (Published, Pending, Draft, Future Scheduled, Private & Trash)', 'export-all-urls' ); ?></label><br/>

                                </td>

                            </tr>

                            <tr>
                                <th></th>
                                <td><a href="#" id="moreFilterOptionsLabel"
                                       onclick="moreFilterOptions(); return false;"><?php esc_html_e( 'Show Filter Options', 'export-all-urls' ); ?></a></td>
                            </tr>

                            <tr class="filter-options" style="display: none">

                                <th><?php esc_html_e( 'Date Range:', 'export-all-urls' ); ?></th>

                                <td>

                                    <label><?php esc_html_e( 'From:', 'export-all-urls' ); ?> <input type="date" id="posts-from" name="posts-from"
                                                       onmouseleave="setMinValueForPostsUptoField()"
                                                       onfocusout="setMinValueForPostsUptoField()"/></label>
                                    <label><?php esc_html_e( 'To:', 'export-all-urls' ); ?> <input type="date" id="posts-upto" name="posts-upto"/></label><br/>


                                </td>

                            </tr>

                            <tr class="filter-options" style="display: none">

                                <th><?php esc_html_e( 'By Author:', 'export-all-urls' ); ?></th>

                                <td>

                                    <label><input type="radio" name="post-author" checked value="all"
                                                  required="required"/><?php echo esc_html_x( 'All', 'By Author','export-all-urls' ); ?></label><br/>
                                    <?php
    if (!empty($user_ids) && !empty($user_names))
    {
        for ($i = 0;$i < count($user_ids);$i++)
        {
            echo '<label><input type="radio" name="post-author" value="' . $user_ids[$i] . '" required="required" /> ' . $user_names[$i] . '</label><br>';
        }
    }
?>

                                </td>

                            </tr>

                            <tr>
                                <th></th>
                                <td><a href="#" id="advanceOptionsLabel" onclick="showAdvanceOptions(); return false;"><?php esc_html_e( 'Show Advanced Options', 'export-all-urls' ); ?></a></td>
                            </tr>

                            <tr class="advance-options" style="display: none">

                                <th><?php esc_html_e( 'Remove WooCommerce Extra Attributes:', 'export-all-urls' ); ?></th>

                                <td>

                                    <label><input type="checkbox" name="remove-woo-attributes" value="yes"/><?php _e( 'Yes &nbsp;&nbsp;<code>WooCommerce stores product attributes along with product categories, by default plugin may extract those attributes and show as categories. That can be fixed by enabling this option.</code>', 'export-all-urls' ); ?>

                                </td>

                            </tr>

                            <tr class="advance-options" style="display: none">

                                <th><?php esc_html_e( 'Exclude Domain URL: ', 'export-all-urls' ); ?></th>

                                <td>

                                    <label><input type="checkbox" name="exclude-domain" value="yes"/><?php _e( 'Yes &nbsp;&nbsp;<code>Enabling this option will use relative URLs, by removing domain url (e.g. example.com/sample-post/ will become /sample-post/)</code>', 'export-all-urls' ); ?>

                                </td>

                            </tr>

                            <tr class="advance-options" style="display: none">

                                <th><?php esc_html_e( 'Number of Posts: ', 'export-all-urls' ); ?><a href="#"
                                                        title="<?php esc_attr_e( 'Specify Post Range to Extract, It is very useful in case of Memory Out Error!', 'export-all-urls' ); ?>"
                                                        onclick="return false">?</a></th>

                                <td>

                                    <label><input type="radio" name="number-of-posts" checked value="all"
                                                  required="required" onclick="hideRangeFields()"/><?php echo esc_html_x( 'All', 'Number of Posts', 'export-all-urls' ); ?></label><br/>
                                    <label><input type="radio" name="number-of-posts" value="range" required="required"
                                                  onclick="showRangeFields()"/><?php esc_html_e( 'Specify Range', 'export-all-urls' ); ?></label><br/>

                                    <div id="postRange" style="display: none">
                                        <?php echo esc_html_x( 'From:', 'Number of Posts', 'export-all-urls' ); ?> <input type="number" name="starting-point" placeholder="0">
                                        <?php echo esc_html_x( 'To:', 'Number of Posts', 'export-all-urls' ); ?> <input type="number" name="ending-point" placeholder="500">
                                    </div>

                                </td>

                            </tr>

                            <tr class="advance-options" style="display: none">

                                <th><?php esc_html_e( 'CSV File Name: ', 'export-all-urls' ); ?></th>

                                <td>

                                    <label><input
                                                type="text" name="csv-file-name" placeholder="<?php esc_attr_e( 'An Error Occured', 'export-all-urls' ); ?>"
                                                value="<?php echo $file_name; ?>"
                                                size="30%"/></label><br/>
                                                <code><?php echo $file_path['path']; ?></code>


                                </td>


                            </tr>

                            <tr>

                                <th><?php esc_html_e( 'Export Type:', 'export-all-urls' ); ?></th>

                                <td>

                                    <label><input type="radio" name="export-type" value="text" required="required"/><?php esc_html_e( 'CSV File', 'export-all-urls' ); ?></label><br/>
                                    <label><input type="radio" name="export-type" value="here" required="required" checked /><?php esc_html_e( 'Output here', 'export-all-urls' ); ?></label><br/>

                                </td>

                            </tr>

                            <tr>

                                <td></td>

                                <td>
                                    <input type="submit" name="export" class="button button-primary"
                                           value="<?php esc_attr_e( 'Export Now', 'export-all-urls' ); ?>"/>
                                </td>

                            </tr>

                        </table>
                        <?php wp_nonce_field('export_urls'); ?>

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
                                <a href="https://AtlasGondal.com/contact-me/?utm_source=self&utm_medium=wp&utm_campaign=export-all-urls&utm_term=hire-me"
                                   target="_blank">Hire me</a> on a project
                            </li>
                            <li>Buy me a Coffee
                                <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YWT3BFURG6SGS&source=url" target="_blank"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif"/> </a>

                            </li>
                        </ul>
                        <hr>
                        <h3>Wanna say Thanks?</h3>
                        <ul>
                            <li>Leave <a
                                        href="https://wordpress.org/support/plugin/export-all-urls/reviews/?filter=5#new-post"
                                        target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> rating
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

                            <li>Write me an <a
                                        href="https://AtlasGondal.com/contact-me/?utm_source=self&utm_medium=wp&utm_campaign=export-all-urls&utm_term=write-an-email"
                                        target="_blank">Email</a></li>
                        </ul>
                        <strong>Reporting</strong> an issue is way better than leaving <strong>1 star</strong> feedback, which does not help you, me, or the community. So, please consider giving me a chance to help before leaving any negative feedback.
                        <hr>
                        <h4 id="eauDevelopedBy">Developed by: <a
                                    href="https://AtlasGondal.com/?utm_source=self&utm_medium=wp&utm_campaign=export-all-urls&utm_term=developed-by"
                                    target="_blank">Atlas Gondal</a></h4>
                    </div>
                </div>
            </div>
        </div>

        <style>.eauWrapper{display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-flex-wrap:wrap;-ms-flex-wrap:wrap;flex-wrap:wrap;overflow:hidden}#eauMainContainer{width:75%;margin-bottom:0}#eauSideContainer{width:24%}#eauSideContainer .postbox:first-child{margin-left:20px;padding-top:15px}.eaucolumns{float:left;display:-webkit-flex;display:-ms-flexbox;display:flex;margin-top:5px}#eauSideContainer .postbox{margin-bottom:0;float:none}#eauSideContainer .inside{margin-bottom:0}#eauSideContainer hr{width:70%;margin:30px auto}#eauSideContainer h3{cursor:default;text-align:center;font-size:16px}#eauSideContainer li{list-style:disclosure-closed;margin-left:25px}#eauSideContainer li a img{display:inline-block;vertical-align:middle}#eauDevelopedBy{text-align:center}#outputData{border-collapse:collapse;width:98%}#outputData tr:nth-child(even){background-color:#fff}#outputData tr:hover{background-color:#ddd}#outputData th{background-color:#000;color:#fff}#outputData td,#outputData th{text-align:left;padding:8px}#outputData th:first-child{width:4%}#outputData #postID{width:6%}#outputData #postTitle{width:25%}#outputData #postURL{width:45%}#outputData #postCategories{width:20%}#eauMainContainer code {font-size: 11px;background-color: #eee;padding-left: 5px;padding-right: 5px;}</style>

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

                document.getElementById('advanceOptionsLabel').innerHTML = "<?php esc_html_e( 'Hide Advanced Options', 'export-all-urls' ); ?>";
                document.getElementById('advanceOptionsLabel').setAttribute("onclick", "javascript: hideAdvanceOptions(); return false;");

            }

            function hideAdvanceOptions() {

                var rows = document.getElementsByClassName('advance-options');

                for (var i = 0; i < rows.length; i++) {
                    rows[i].style.display = 'none';
                }

                document.getElementById('advanceOptionsLabel').innerHTML = "<?php esc_html_e( 'Show Advanced Options', 'export-all-urls' ); ?>";
                document.getElementById('advanceOptionsLabel').setAttribute("onclick", "javascript: showAdvanceOptions(); return false;");

            }

            function moreFilterOptions() {
                var rows = document.getElementsByClassName('filter-options');

                for (var i = 0; i < rows.length; i++) {
                    rows[i].style.display = 'table-row';
                }

                document.getElementById('moreFilterOptionsLabel').innerHTML = "<?php esc_html_e( 'Hide Filter Options', 'export-all-urls' ); ?>";
                document.getElementById('moreFilterOptionsLabel').setAttribute("onclick", "javascript: lessFilterOptions(); return false;");

            }

            function lessFilterOptions() {
                var rows = document.getElementsByClassName('filter-options');

                for (var i = 0; i < rows.length; i++) {
                    rows[i].style.display = 'none';
                }

                document.getElementById('moreFilterOptionsLabel').innerHTML = "<?php esc_html_e( 'Show Filter Options', 'export-all-urls' ); ?>";
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
    if (isset($_POST['export']))
    {

        if (isset($_REQUEST['_wpnonce']))
        {
            $nonce = $_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'export_urls'))
            {
                echo "<div class='notice notice-error' style='width: 93%'>" . esc_html__( 'Security token validation failed!', 'export-all-urls') . "</div>";
                exit;
            }

            if (!empty($_POST['post-type']) && !empty($_POST['export-type']) && !empty($_POST['additional-data']) && !empty($_POST['post-status']) && !empty($_POST['post-author']) && !empty($_POST['number-of-posts']))
            {

                $post_type = sanitize_text_field($_POST['post-type']);
                $export_type = sanitize_text_field($_POST['export-type']);
                $additional_data = map_deep($_POST['additional-data'], 'sanitize_text_field');
                $post_status = sanitize_text_field($_POST['post-status']);
                $post_author = sanitize_text_field($_POST['post-author']);
                $remove_woo_attributes = isset($_POST['remove-woo-attributes']) ? sanitize_text_field($_POST['remove-woo-attributes']) : null;
                $exclude_domain = isset($_POST['exclude-domain']) ? sanitize_text_field($_POST['exclude-domain']) : null;
                $number_of_posts = sanitize_text_field($_POST['number-of-posts']);
                $csv_name = sanitize_file_name($_POST['csv-file-name']);

                if ($number_of_posts == "range")
                {
                    $offset = absint($_POST['starting-point']);
                    $post_per_page = absint($_POST['ending-point']);

                    if (!isset($offset) || !isset($post_per_page))
                    {
                        echo __( "Sorry, you didn't specify starting and ending post range. Please <strong>Set Post Range</strong> OR <strong>Select All</strong> and try again! :)", 'export-all-urls' );
                        exit;
                    }

                    $post_per_page = $post_per_page - $offset;

                }
                else
                {
                    $offset = 'all';
                    $post_per_page = 'all';
                }

                if ($export_type == 'text')
                {
                    if (empty($csv_name))
                    {
                        echo __( "Invalid/Missing CSV File Name!", 'export-all-urls' );
                        exit;
                    }
                }

                $posts_from = sanitize_file_name($_POST['posts-from']);
                $posts_upto = sanitize_file_name($_POST['posts-upto']);

                if (!empty($posts_from) && !empty($posts_upto))
                {

                    if ($posts_from > $posts_upto)
                    {
                        echo __( "Sorry, invalid post date range. :)", 'export-all-urls' );
                        exit;
                    }

                }
                else
                {
                    $posts_from = '';
                    $posts_upto = '';
                }

                $selected_post_type = eau_get_selected_post_type($post_type, $custom_posts_names);

                eau_generate_output($selected_post_type, $post_status, $post_author, $remove_woo_attributes, $exclude_domain, $post_per_page, $offset, $export_type, $additional_data, $csv_name, $posts_from, $posts_upto);

            }
            else
            {
                echo "<div class='notice notice-error' style='width: 93%'>" . __( 'Sorry, you missed something, Please recheck above options, especially <strong>Export Fields</strong> and try again! :)', 'export-all-urls' ) . "</div>";
                exit;
            }
        }
        else
        {
            echo "<div class='notice notice-error' style='width: 93%'>" . __( 'Verification token is missing!', 'export-all-urls' ) . "</div>";
            exit;
        }

    }
    elseif (isset($_REQUEST['del']) && $_REQUEST['del'] == 'y')
    {
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce']))
        {
            echo __( 'You are not authorized to perform this action!', 'export-all-urls' );
            exit();
        }
        else
        {
            $file = base64_decode($_REQUEST['f']);
            $path_info = pathinfo($file);
            $upload_dir = wp_upload_dir();

            if (($path_info['dirname'] == $upload_dir['path']) && ($path_info['extension'] == 'CSV')) {
                echo !empty($file) ? file_exists($file) ? !unlink($file) ? "<div class='notice notice-error' style='width: 97%'></div>" . __( 'Unable to delete file, please delete it manually!', 'export-all-urls' ) : "<div class='updated' style='width: 97%'>" . __( 'You did great, the file was <strong>Deleted Successfully</strong>!', 'export-all-urls' ). "</div>" : null : "<div class='notice notice-error'>" . __( 'Missing file path.', 'export-all-urls' ) . "</div>";
            } else {
                die("<div class='error' style='width: 95.3%; margin-left: 2px;'>" . __( 'Sorry, the file verification failed. Arbitrary file removal is not allowed.', 'export-all-urls' ) . "</div>");
            }
        }

    }
    
}

eau_generate_html();
