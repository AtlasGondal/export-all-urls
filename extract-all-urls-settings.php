<?php

namespace Export_All_URLs;

defined('ABSPATH') || exit;

require_once(plugin_dir_path(__FILE__) . 'classes/constants.php');
require_once(plugin_dir_path(__FILE__) . '/eau_functions.php');

/**
 * Generates HTML for the extract-all-urls settings page.
 *
 * @return void
 */
function eau_generate_html()
{
    $eau_functions = new EAU_Functions();

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

    $export_fields = $eau_functions->Eau_Export_fields();

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

        <h2 align="center"><strong><?php echo esc_html__('Export Data from your Site', Constants::PLUGIN_TEXT_DOMAIN); ?></strong></h2>

        <div class="eauWrapper">
            <div id="eauMainContainer" class="postbox eaucolumns">

                <div class="inside">

                    <form id="infoForm" method="post" action="">

                        <table class="form-table">

                            <tr>

                                <th><?php echo esc_html__('Select a Post Type to Extract Data:', Constants::PLUGIN_TEXT_DOMAIN); ?> </th>

                                <td>

                                    <?php foreach ($post_types as $value => $label) : ?>
                                        <label><input type="radio" name="post-type" value="<?php echo $value; ?>" required="required" <?php echo $value == $selected_post_type ? 'checked' : ''; ?>> <?php echo $label; ?></label><br />
                                    <?php endforeach; ?>

                                </td>

                            </tr>

                            <tr>

                                <th><?php echo esc_html__('Export Fields:', Constants::PLUGIN_TEXT_DOMAIN); ?></th>

                                <td>

                                    <?php foreach ($export_fields as $value => $label) : ?>
                                        <label><input type="checkbox" name="export_fields[]" value="<?php echo $value; ?>" <?php echo in_array($value, $selected_export_fields) ? 'checked' : ''; ?>> <?php echo $label; ?></label><br />
                                    <?php endforeach; ?>

                                </td>

                            </tr>

                            <tr>

                                <th><?php echo esc_html__('Post Status:', Constants::PLUGIN_TEXT_DOMAIN); ?></th>

                                <td>

                                    <?php foreach ($post_status as $value => $label) : ?>
                                        <label><input type="radio" name="post-status" value="<?php echo $value; ?>" <?php echo $value == $selected_post_status ? 'checked' : ''; ?>> <?php echo $label; ?></label><br />
                                    <?php endforeach; ?>

                                </td>

                            </tr>

                            <tr>
                                <th></th>
                                <td><a href="#" id="moreFilterOptionsLabel" onclick="moreFilterOptions(); return false;"><?php echo esc_html__('Show Filter Options', Constants::PLUGIN_TEXT_DOMAIN); ?></a></td>
                            </tr>

                            <tr class="filter-options" style="display: none">

                                <th><?php echo esc_html__('Date Range:', Constants::PLUGIN_TEXT_DOMAIN); ?></th>

                                <td>

                                    <label><?php echo esc_html__('From:', Constants::PLUGIN_TEXT_DOMAIN); ?><input type="date" id="posts-from" name="posts-from" onmouseleave="setMinValueForPostsUptoField()" onfocusout="setMinValueForPostsUptoField()" /></label>
                                    <label><?php echo esc_html__('To:', Constants::PLUGIN_TEXT_DOMAIN); ?><input type="date" id="posts-upto" name="posts-upto" /></label><br />


                                </td>

                            </tr>

                            <tr class="filter-options" style="display: none">

                                <th><?php echo esc_html__('By Author:', Constants::PLUGIN_TEXT_DOMAIN); ?></th>

                                <td>

                                    <?php foreach ($users_list as $value => $label) : ?>
                                        <label><input type="radio" name="post-author" value="<?php echo $value; ?>" required="required" <?php echo $value == $selected_user ? 'checked' : ''; ?>> <?php echo $label; ?></label><br />
                                    <?php endforeach; ?>

                                </td>

                            </tr>

                            <tr>
                                <th></th>
                                <td><a href="#" id="advanceOptionsLabel" onclick="showAdvanceOptions(); return false;"><?php echo esc_html__('Show Advanced Options', Constants::PLUGIN_TEXT_DOMAIN); ?></a></td>
                            </tr>


                            <tr class="advance-options" style="display: none">

                                <th><?php echo esc_html__('Exclude Domain URL:', Constants::PLUGIN_TEXT_DOMAIN); ?> </th>

                                <td>

                                    <label><input type="checkbox" name="exclude-domain" value="yes" <?php echo isset($_POST['exclude-domain']) ? 'checked' : ''; ?> /> <?php echo esc_html__('Yes', Constants::PLUGIN_TEXT_DOMAIN); ?> &nbsp;&nbsp;<code><?php echo esc_html__('Enable this option to remove the domain from URLs, e.g., \'example.com/sample-post/\' becomes \'/sample-post/\'', Constants::PLUGIN_TEXT_DOMAIN); ?></code>

                                </td>

                            </tr>

                            <tr class="advance-options" style="display: none">

                                <th><?php echo esc_html__('Number of Posts:', Constants::PLUGIN_TEXT_DOMAIN); ?> <a href="#" title="<?php echo esc_html__('Specify Post Range to Extract, It is very useful in case of Memory Out Error!', Constants::PLUGIN_TEXT_DOMAIN); ?>" onclick="return false">?</a></th>

                                <td>

                                    <label><input type="radio" name="number-of-posts" checked value="all" required="required" onclick="hideRangeFields()" /> <?php echo esc_html__('All', Constants::PLUGIN_TEXT_DOMAIN); ?></label><br />
                                    <label><input type="radio" name="number-of-posts" value="range" required="required" onclick="showRangeFields()" /> <?php echo esc_html__('Specify Range', Constants::PLUGIN_TEXT_DOMAIN); ?></label><br />

                                    <div id="postRange" style="display: none">
                                        <?php echo esc_html__('From:', Constants::PLUGIN_TEXT_DOMAIN); ?> <input type="number" name="starting-point" placeholder="0" value="<?php echo isset($_POST['starting-point']) ? esc_attr($_POST['starting-point']) : ''; ?>">
                                        <?php echo esc_html__('To:', Constants::PLUGIN_TEXT_DOMAIN); ?> <input type="number" name="ending-point" placeholder="500" value="<?php echo isset($_POST['ending-point']) ? esc_attr($_POST['ending-point']) : ''; ?>">
                                    </div>

                                </td>

                            </tr>

                            <tr class="advance-options" style="display: none">

                                <th><?php echo esc_html__('CSV File Name:', Constants::PLUGIN_TEXT_DOMAIN); ?> </th>

                                <td>

                                    <label><input type="text" name="csv-file-name" placeholder="<?php echo esc_html__('An Error Occured', Constants::PLUGIN_TEXT_DOMAIN); ?>" value="<?php echo $file_name; ?>" size="30%" /></label><br />
                                    <code><?php echo $file_path['path']; ?></code>


                                </td>


                            </tr>

                            <tr>

                                <th><?php echo esc_html__('Export Type:', Constants::PLUGIN_TEXT_DOMAIN); ?></th>

                                <td>

                                    <?php foreach ($export_types as $value => $label) : ?>
                                        <label><input type="radio" name="export-type" value="<?php echo $value; ?>" required="required" <?php echo $value == $selected_export_type ? 'checked' : ''; ?>> <?php echo $label; ?></label><br />
                                    <?php endforeach; ?>

                                </td>

                            </tr>

                            <tr>

                                <td></td>

                                <td>
                                    <input type="submit" name="export" class="button button-primary" value="<?php echo esc_html__('Export Now', Constants::PLUGIN_TEXT_DOMAIN); ?>" />
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
                    <h3><?php echo esc_html__('Want to Support?', Constants::PLUGIN_TEXT_DOMAIN); ?></h3>
                    <div class="inside">
                        <p><?php echo esc_html__('If you enjoyed the plugin, and want to support:', Constants::PLUGIN_TEXT_DOMAIN); ?></p>
                        <ul>
                            <li>
                                <a href="https://AtlasGondal.com/contact-me/?utm_source=self&utm_medium=wp&utm_campaign=export-all-urls&utm_term=hire-me" target="_blank"><?php echo esc_html__('Hire me', Constants::PLUGIN_TEXT_DOMAIN); ?></a> <?php echo esc_html__('on a project', Constants::PLUGIN_TEXT_DOMAIN); ?>
                            </li>
                            <li><?php echo esc_html__('Buy me a Coffee', Constants::PLUGIN_TEXT_DOMAIN); ?>
                                <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YWT3BFURG6SGS&source=url" target="_blank"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" /> </a>

                            </li>
                        </ul>
                        <hr>
                        <h3><?php echo esc_html__('Wanna say Thanks?', Constants::PLUGIN_TEXT_DOMAIN); ?></h3>
                        <ul>
                            <li><?php echo esc_html__('Leave', Constants::PLUGIN_TEXT_DOMAIN); ?> <a href="https://wordpress.org/support/plugin/export-all-urls/reviews/?filter=5#new-post" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> <?php echo esc_html__('rating', Constants::PLUGIN_TEXT_DOMAIN); ?>
                            </li>
                            <li><?php echo esc_html__('Tweet me:', Constants::PLUGIN_TEXT_DOMAIN); ?> <a href="https://twitter.com/atlas_gondal" target="_blank">@Atlas_Gondal</a>
                            </li>
                        </ul>
                        <hr>
                        <h3><?php echo esc_html__('Got a Problem?', Constants::PLUGIN_TEXT_DOMAIN); ?></h3>
                        <p><?php echo esc_html__('If you want to report a bug or suggest new feature. You can:', Constants::PLUGIN_TEXT_DOMAIN); ?></p>
                        <ul>
                            <li><?php echo esc_html__('Create', Constants::PLUGIN_TEXT_DOMAIN); ?> <a href="https://wordpress.org/support/plugin/export-all-urls/" target="_blank"><?php echo esc_html__('Support Ticket', Constants::PLUGIN_TEXT_DOMAIN); ?></a></li>

                            <li><?php echo esc_html__('Write me an', Constants::PLUGIN_TEXT_DOMAIN); ?> <a href="https://AtlasGondal.com/contact-me/?utm_source=self&utm_medium=wp&utm_campaign=export-all-urls&utm_term=write-an-email" target="_blank"><?php echo esc_html__('Email', Constants::PLUGIN_TEXT_DOMAIN); ?></a></li>
                        </ul>
                        <strong><?php echo esc_html__('Reporting', Constants::PLUGIN_TEXT_DOMAIN); ?></strong> <?php echo esc_html__('an issue is more effective than giving a', Constants::PLUGIN_TEXT_DOMAIN); ?> <strong>1 star</strong> <?php echo esc_html__('review, as it aids you, me, and the entire community. Kindly consider letting me help prior to leaving negative feedback.', Constants::PLUGIN_TEXT_DOMAIN); ?>
                        <hr>
                        <h4 id="eauDevelopedBy"><?php echo esc_html__('Developed by:', Constants::PLUGIN_TEXT_DOMAIN); ?> <a href="https://AtlasGondal.com/?utm_source=self&utm_medium=wp&utm_campaign=export-all-urls&utm_term=developed-by" target="_blank">Atlas Gondal</a></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>


<?php
    if (isset($_POST['export'])) {


        $eau_functions = new EAU_Functions();

        if (isset($_REQUEST['_wpnonce'])) {
            $nonce = $_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'export_urls')) {
                echo "<div class='notice notice-error' style='width: 93%'>" . __('Security token validation failed!', Constants::PLUGIN_TEXT_DOMAIN) . "</div>";
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
                        echo "<div class='notice notice-error' style='width: 93%'>" . __('Sorry, you didn\'t specify starting and ending post range. Please <strong>Set Post Range</strong> OR <strong>Select All</strong> and try again! :)', Constants::PLUGIN_TEXT_DOMAIN) . "</div>";
                        exit;
                    }

                    $post_per_page = $post_per_page - $offset;
                } else {
                    $offset = 'all';
                    $post_per_page = 'all';
                }

                if ($export_type == 'text') {
                    if (empty($csv_name)) {
                        echo "<div class='notice notice-error' style='width: 93%'>" . __('Invalid/Missing CSV File Name!', Constants::PLUGIN_TEXT_DOMAIN) . "</div>";
                        exit;
                    }
                }

                $posts_from = sanitize_file_name($_POST['posts-from']);
                $posts_upto = sanitize_file_name($_POST['posts-upto']);

                if (!empty($posts_from) && !empty($posts_upto)) {

                    if ($posts_from > $posts_upto) {
                        echo "<div class='notice notice-error' style='width: 93%'>" . __('Sorry, invalid post date range. :)', Constants::PLUGIN_TEXT_DOMAIN) . "</div>";
                        exit;
                    }
                } else {
                    $posts_from = '';
                    $posts_upto = '';
                }

                $eau_functions->eau_generate_output($post_type, $post_status, $post_author, $exclude_domain, $post_per_page, $offset, $export_type, $export_fields, $csv_name, $posts_from, $posts_upto);
            } else {
                echo "<div class='notice notice-error' style='width: 93%'>" . __('Sorry, you missed something, Please recheck above options, especially <strong>Export Fields</strong> and try again! :)', Constants::PLUGIN_TEXT_DOMAIN) . "</div>";
                exit;
            }
        } else {
            echo "<div class='notice notice-error' style='width: 93%'>" . __('Verification token is missing!', Constants::PLUGIN_TEXT_DOMAIN) . "</div>";
            exit;
        }
    } elseif (isset($_REQUEST['del']) && $_REQUEST['del'] == 'y') {
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'])) {
            echo __("You are not authorized to perform this action!", Constants::PLUGIN_TEXT_DOMAIN);
            exit();
        } else {
            $file = base64_decode($_REQUEST['f']);
            $path_info = pathinfo($file);
            $upload_dir = wp_upload_dir();

            if (($path_info['dirname'] == $upload_dir['path']) && ($path_info['extension'] == 'CSV')) {
                echo !empty($file) ? (file_exists($file) ? (!unlink($file) ? "<div class='notice notice-error' style='width: 97%'></div>" . __("Unable to delete file, please delete it manually!", Constants::PLUGIN_TEXT_DOMAIN) : "<div class='updated' style='width: 97%'>" . __("You did great, the file was <strong>Deleted Successfully</strong>!", Constants::PLUGIN_TEXT_DOMAIN) . "</div>") : null) : "<div class='notice notice-error'>" . __("Missing file path.", Constants::PLUGIN_TEXT_DOMAIN) . "</div>";
            } else {
                die("<div class='error' style='width: 95.3%; margin-left: 2px;'>" . __("Sorry, the file verification failed. Arbitrary file removal is not allowed.", Constants::PLUGIN_TEXT_DOMAIN) . "</div>");
            }
        }
    }
}

eau_generate_html();
