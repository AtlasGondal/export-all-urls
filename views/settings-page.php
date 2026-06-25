<?php

namespace Export_All_URLs;

defined('ABSPATH') || exit;

require_once plugin_dir_path(dirname(__FILE__)) . 'classes/constants.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-functions.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-request.php';

// This template is only ever included from within ExportAllUrls::include_settings_page(),
// so its variables are method-scoped, not global.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

if (!current_user_can(Constants::PLUGIN_SETTINGS_PAGE_CAPABILITY)) {
    wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'export-all-urls'));
}

$eau_functions = new EAU_Functions();
$eau_fields = $eau_functions->fields();
$eau_ml_active = $eau_functions->multilingual()->is_active();

/* ---------------------------------------------------------------------- */
/* Trust $_POST only after the nonce verifies. Every sticky value below is */
/* read once here (after verification) and reused when rendering the form. */
/* ---------------------------------------------------------------------- */

$eau_nonce_ok = isset($_POST['_wpnonce'])
    && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), Constants::EXPORT_NONCE_ACTION);
$eau_posted = $eau_nonce_ok;
$form_submitted = $eau_posted && isset($_POST['form_submitted']);

$last_fields = get_user_meta(get_current_user_id(), Constants::LAST_FIELDS_META, true);
if (!is_array($last_fields) || empty($last_fields)) {
    $last_fields = array('url', 'title');
}

$selected_post_type = ($eau_posted && isset($_POST['post-type'])) ? sanitize_text_field(wp_unslash($_POST['post-type'])) : 'any';
$selected_fields    = ($eau_posted && isset($_POST['export_fields']))
    ? array_map('sanitize_text_field', (array) wp_unslash($_POST['export_fields']))
    : ($form_submitted ? array() : $last_fields);
$selected_status    = ($eau_posted && isset($_POST['post-status']))
    ? array_map('sanitize_text_field', (array) wp_unslash($_POST['post-status']))
    : ($form_submitted ? array() : array('publish'));
$selected_user      = ($eau_posted && isset($_POST['post-author'])) ? sanitize_text_field(wp_unslash($_POST['post-author'])) : 'all';
$selected_format    = ($eau_posted && isset($_POST['export-type'])) ? sanitize_text_field(wp_unslash($_POST['export-type'])) : 'text';
$lang_scope         = ($eau_posted && isset($_POST['lang-scope'])) ? sanitize_text_field(wp_unslash($_POST['lang-scope'])) : 'all';

$posts_from      = ($eau_posted && isset($_POST['posts-from'])) ? sanitize_text_field(wp_unslash($_POST['posts-from'])) : '';
$posts_upto      = ($eau_posted && isset($_POST['posts-upto'])) ? sanitize_text_field(wp_unslash($_POST['posts-upto'])) : '';
$exclude_domain  = ($eau_posted && isset($_POST['exclude-domain']));
$number_of_posts = ($eau_posted && isset($_POST['number-of-posts'])) ? sanitize_text_field(wp_unslash($_POST['number-of-posts'])) : 'all';
$starting_point  = ($eau_posted && isset($_POST['starting-point'])) ? absint(wp_unslash($_POST['starting-point'])) : '';
$ending_point    = ($eau_posted && isset($_POST['ending-point'])) ? absint(wp_unslash($_POST['ending-point'])) : '';

$default_name = 'export-all-urls-' . wp_generate_password(20, false);
$posted_name = ($eau_posted && isset($_POST['csv-file-name'])) ? sanitize_file_name(wp_unslash($_POST['csv-file-name'])) : '';
$csv_name = ('' !== $posted_name) ? $posted_name : $default_name;

/* ---------------------------------------------------------------------- */
/* Form option sources                                                    */
/* ---------------------------------------------------------------------- */

$post_types = array(
    'any'  => __('All Types (pages, posts, and custom post types)', 'export-all-urls'),
    'page' => __('Pages', 'export-all-urls'),
    'post' => __('Posts', 'export-all-urls'),
);
foreach (get_post_types(array('public' => true, '_builtin' => false), 'objects', 'and') as $cpt) {
    $post_types[$cpt->name] = $cpt->labels->singular_name;
}

$post_status = array(
    'publish' => __('Published', 'export-all-urls'),
    'pending' => __('Pending', 'export-all-urls'),
    'draft'   => __('Draft & Auto Draft', 'export-all-urls'),
    'future'  => __('Future Scheduled', 'export-all-urls'),
    'private' => __('Private', 'export-all-urls'),
    'trash'   => __('Trashed', 'export-all-urls'),
    'all'     => __('All (Published, Pending, Draft, Future Scheduled, Private & Trash)', 'export-all-urls'),
);

$export_formats = array(
    'text' => __('CSV File', 'export-all-urls'),
    'json' => __('JSON File', 'export-all-urls'),
);

$users_list = array('all' => __('All', 'export-all-urls'));
foreach (get_users() as $user) {
    $users_list[$user->ID] = $user->user_login;
}

/* Registry-driven field groups. */
$groups = $eau_fields->groups();
$fields = $eau_fields->fields();
$presets = $eau_fields->presets();

$fields_by_group = array();
$group_has_selected = array();
foreach ($fields as $key => $def) {
    $fields_by_group[$def['group']][$key] = $def;
    if (in_array($key, $selected_fields, true)) {
        $group_has_selected[$def['group']] = true;
    }
}

/* Collapsible section state — keep open whatever the user was working with. */
$show_filters  = ('' !== $posts_from || '' !== $posts_upto || ('all' !== $selected_user && '' !== $selected_user));
$show_advanced = ($exclude_domain || 'range' === $number_of_posts);
$show_range    = ('range' === $number_of_posts);

$filter_label   = $show_filters ? __('Hide Filter Options', 'export-all-urls') : __('Show Filter Options', 'export-all-urls');
$filter_onclick = $show_filters ? 'lessFilterOptions()' : 'moreFilterOptions()';
$advanced_label   = $show_advanced ? __('Hide Advanced Options', 'export-all-urls') : __('Show Advanced Options', 'export-all-urls');
$advanced_onclick = $show_advanced ? 'hideAdvanceOptions()' : 'showAdvanceOptions()';

$admin_post_url = admin_url('admin-post.php');
?>

<div class="wrap">

    <?php include plugin_dir_path(__FILE__) . 'tab-nav.php'; ?>

    <h2 align="center"><strong><?php esc_html_e('Export Data from your Site', 'export-all-urls'); ?></strong></h2>

    <div class="eauWrapper">
        <div id="eauMainContainer" class="postbox eaucolumns">
            <div class="inside">

                <form id="infoForm" method="post" action="">

                    <table class="form-table">

                        <tr>
                            <th><?php esc_html_e('Select a Post Type to Extract Data:', 'export-all-urls'); ?></th>
                            <td>
                                <?php foreach ($post_types as $value => $label) : ?>
                                    <label><input type="radio" name="post-type" value="<?php echo esc_attr($value); ?>" required="required" <?php checked($value, $selected_post_type); ?>> <?php echo esc_html($label); ?></label><br />
                                <?php endforeach; ?>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e('Export Fields:', 'export-all-urls'); ?></th>
                            <td>
                                <div class="eau-presets">
                                    <span class="eau-presets-label"><?php esc_html_e('Presets:', 'export-all-urls'); ?></span>
                                    <?php foreach ($presets as $preset) : ?>
                                        <button type="button" class="button button-secondary eau-preset" data-eau-preset="<?php echo esc_attr(implode(',', $preset['fields'])); ?>"><?php echo esc_html($preset['label']); ?></button>
                                    <?php endforeach; ?>
                                </div>

                                <?php foreach ($groups as $group_key => $group) : ?>
                                    <?php
                                    if ('multilingual' === $group_key && !$eau_ml_active) {
                                        continue;
                                    }
                                    if (empty($fields_by_group[$group_key])) {
                                        continue;
                                    }
                                    $open = empty($group['collapsed']) || !empty($group_has_selected[$group_key]);
                                    ?>
                                    <details class="eau-field-group" <?php echo $open ? 'open' : ''; ?>>
                                        <summary><?php echo esc_html($group['label']); ?></summary>
                                        <?php if (!empty($group['description'])) : ?>
                                            <p class="description eau-group-description"><?php echo esc_html($group['description']); ?></p>
                                        <?php endif; ?>
                                        <div class="eau-group-tools">
                                            <a href="#" class="eau-group-all" data-eau-group="<?php echo esc_attr($group_key); ?>"><?php esc_html_e('Select all', 'export-all-urls'); ?></a>
                                            &nbsp;|&nbsp;
                                            <a href="#" class="eau-group-none" data-eau-group="<?php echo esc_attr($group_key); ?>"><?php esc_html_e('None', 'export-all-urls'); ?></a>
                                        </div>
                                        <div class="eau-field-grid">
                                            <?php foreach ($fields_by_group[$group_key] as $key => $def) : ?>
                                                <label><input type="checkbox" name="export_fields[]" value="<?php echo esc_attr($key); ?>" data-eau-group="<?php echo esc_attr($group_key); ?>" <?php checked(in_array($key, $selected_fields, true)); ?>> <?php echo esc_html($def['label']); ?></label>
                                            <?php endforeach; ?>
                                        </div>
                                    </details>
                                <?php endforeach; ?>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e('Post Status:', 'export-all-urls'); ?></th>
                            <td>
                                <?php foreach ($post_status as $value => $label) : ?>
                                    <label><input type="checkbox" name="post-status[]" value="<?php echo esc_attr($value); ?>" <?php checked(in_array($value, $selected_status, true)); ?>> <?php echo esc_html($label); ?></label><br />
                                <?php endforeach; ?>
                            </td>
                        </tr>

                        <?php if ($eau_ml_active) : ?>
                        <tr>
                            <th><?php esc_html_e('Languages:', 'export-all-urls'); ?></th>
                            <td>
                                <label><input type="radio" name="lang-scope" value="all" <?php checked($lang_scope, 'all'); ?>> <?php esc_html_e('All languages', 'export-all-urls'); ?></label><br />
                                <label><input type="radio" name="lang-scope" value="default" <?php checked($lang_scope, 'default'); ?>> <?php esc_html_e('Default language only', 'export-all-urls'); ?></label>
                                <p class="description"><?php esc_html_e('Controls which languages are exported, regardless of the language selected in the admin bar.', 'export-all-urls'); ?></p>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <th></th>
                            <td><a href="#" id="moreFilterOptionsLabel" onclick="<?php echo esc_attr($filter_onclick); ?>; return false;"><?php echo esc_html($filter_label); ?></a></td>
                        </tr>

                        <tr class="filter-options" style="display: <?php echo $show_filters ? 'table-row' : 'none'; ?>">
                            <th><?php esc_html_e('Date Range:', 'export-all-urls'); ?></th>
                            <td>
                                <label><?php esc_html_e('From:', 'export-all-urls'); ?><input type="date" id="posts-from" name="posts-from" value="<?php echo esc_attr($posts_from); ?>" onmouseleave="setMinValueForPostsUptoField()" onfocusout="setMinValueForPostsUptoField()" /></label>
                                <label><?php esc_html_e('To:', 'export-all-urls'); ?><input type="date" id="posts-upto" name="posts-upto" value="<?php echo esc_attr($posts_upto); ?>" /></label><br />
                            </td>
                        </tr>

                        <tr class="filter-options" style="display: <?php echo $show_filters ? 'table-row' : 'none'; ?>">
                            <th><?php esc_html_e('By Author:', 'export-all-urls'); ?></th>
                            <td>
                                <?php foreach ($users_list as $value => $label) : ?>
                                    <label><input type="radio" name="post-author" value="<?php echo esc_attr($value); ?>" required="required" <?php checked($value, $selected_user); ?>> <?php echo esc_html($label); ?></label><br />
                                <?php endforeach; ?>
                            </td>
                        </tr>

                        <tr>
                            <th></th>
                            <td><a href="#" id="advanceOptionsLabel" onclick="<?php echo esc_attr($advanced_onclick); ?>; return false;"><?php echo esc_html($advanced_label); ?></a></td>
                        </tr>

                        <tr class="advance-options" style="display: <?php echo $show_advanced ? 'table-row' : 'none'; ?>">
                            <th><?php esc_html_e('Exclude Domain URL:', 'export-all-urls'); ?></th>
                            <td>
                                <label><input type="checkbox" name="exclude-domain" value="yes" <?php checked($exclude_domain); ?> /> <?php esc_html_e('Yes', 'export-all-urls'); ?></label>
                                &nbsp;&nbsp;<code><?php esc_html_e("Enable this option to remove the domain from URLs, e.g., 'example.com/sample-post/' becomes '/sample-post/'", 'export-all-urls'); ?></code>
                            </td>
                        </tr>

                        <tr class="advance-options" style="display: <?php echo $show_advanced ? 'table-row' : 'none'; ?>">
                            <th><?php esc_html_e('Number of Posts:', 'export-all-urls'); ?> <a href="#" title="<?php echo esc_attr__('Specify Post Range to Extract, It is very useful in case of Memory Out Error!', 'export-all-urls'); ?>" onclick="return false">?</a></th>
                            <td>
                                <label><input type="radio" name="number-of-posts" value="all" required="required" onclick="hideRangeFields()" <?php checked($number_of_posts, 'all'); ?> /> <?php esc_html_e('All', 'export-all-urls'); ?></label><br />
                                <label><input type="radio" name="number-of-posts" value="range" required="required" onclick="showRangeFields()" <?php checked($number_of_posts, 'range'); ?> /> <?php esc_html_e('Specify Range', 'export-all-urls'); ?></label><br />
                                <div id="postRange" style="display: <?php echo $show_range ? 'block' : 'none'; ?>">
                                    <?php esc_html_e('From:', 'export-all-urls'); ?> <input type="number" name="starting-point" placeholder="0" value="<?php echo esc_attr($starting_point); ?>">
                                    <?php esc_html_e('To:', 'export-all-urls'); ?> <input type="number" name="ending-point" placeholder="500" value="<?php echo esc_attr($ending_point); ?>">
                                </div>
                            </td>
                        </tr>

                        <tr class="advance-options" style="display: <?php echo $show_advanced ? 'table-row' : 'none'; ?>">
                            <th><?php esc_html_e('Download File Name:', 'export-all-urls'); ?></th>
                            <td>
                                <label><input type="text" name="csv-file-name" value="<?php echo esc_attr($csv_name); ?>" size="40" /></label>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e('Download Format:', 'export-all-urls'); ?></th>
                            <td>
                                <?php foreach ($export_formats as $value => $label) : ?>
                                    <label><input type="radio" name="export-type" value="<?php echo esc_attr($value); ?>" <?php checked($value, $selected_format); ?>> <?php echo esc_html($label); ?></label><br />
                                <?php endforeach; ?>
                            </td>
                        </tr>

                        <tr>
                            <td></td>
                            <td>
                                <button type="submit" name="export" class="button button-primary" formaction="<?php echo esc_url($admin_post_url); ?>"><?php esc_html_e('Download', 'export-all-urls'); ?></button>
                                <button type="submit" name="display" class="button button-secondary"><?php esc_html_e('Display Here', 'export-all-urls'); ?></button>
                            </td>
                        </tr>

                    </table>

                    <?php wp_nonce_field(Constants::EXPORT_NONCE_ACTION); ?>
                    <input type="hidden" name="action" value="<?php echo esc_attr(Constants::EXPORT_ACTION); ?>">
                    <input type="hidden" name="form_submitted" value="1">

                </form>

            </div>
        </div>

        <div id="eauSideContainer" class="eaucolumns">
            <div class="postbox">
                <h3><?php esc_html_e('Want to Support?', 'export-all-urls'); ?></h3>
                <div class="inside">
                    <p><?php esc_html_e('If you enjoyed the plugin, and want to support:', 'export-all-urls'); ?></p>
                    <ul>
                        <li><a href="https://AtlasGondal.com/contact-me/?utm_source=self&utm_medium=wp&utm_campaign=export-all-urls&utm_term=hire-me" target="_blank"><?php esc_html_e('Hire me', 'export-all-urls'); ?></a> <?php esc_html_e('on a project', 'export-all-urls'); ?></li>
                        <li><a class="eau-paypal-btn" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YWT3BFURG6SGS&source=url" target="_blank"><span class="screen-reader-text"><?php esc_html_e('Buy me a Coffee (donate via PayPal)', 'export-all-urls'); ?></span></a></li>
                    </ul>
                    <hr>
                    <h3><?php esc_html_e('Wanna say Thanks?', 'export-all-urls'); ?></h3>
                    <ul>
                        <li><?php esc_html_e('Leave', 'export-all-urls'); ?> <a href="https://wordpress.org/support/plugin/export-all-urls/reviews/?filter=5#new-post" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> <?php esc_html_e('rating', 'export-all-urls'); ?></li>
                        <li><?php esc_html_e('Follow me on X:', 'export-all-urls'); ?> <a href="https://x.com/atlas_gondal" target="_blank">@Atlas_Gondal</a></li>
                    </ul>
                    <hr>
                    <h3><?php esc_html_e('Got a Problem?', 'export-all-urls'); ?></h3>
                    <p><?php esc_html_e('Want to report a bug or suggest a feature? You can:', 'export-all-urls'); ?></p>
                    <ul>
                        <li><?php esc_html_e('Create', 'export-all-urls'); ?> <a href="https://wordpress.org/support/plugin/export-all-urls/" target="_blank"><?php esc_html_e('Support Ticket', 'export-all-urls'); ?></a></li>
                        <li><?php esc_html_e('Write me an', 'export-all-urls'); ?> <a href="https://AtlasGondal.com/contact-me/?utm_source=self&utm_medium=wp&utm_campaign=export-all-urls&utm_term=write-an-email" target="_blank"><?php esc_html_e('Email', 'export-all-urls'); ?></a></li>
                    </ul>
                    <hr>
                    <h4 id="eauDevelopedBy"><?php esc_html_e('Developed by:', 'export-all-urls'); ?> <a href="https://AtlasGondal.com/?utm_source=self&utm_medium=wp&utm_campaign=export-all-urls&utm_term=developed-by" target="_blank">Atlas Gondal</a></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/* ---------------------------------------------------------------------- */
/* In-page handler: "Display Here" only. CSV/JSON downloads are routed to  */
/* admin-post.php (streamed) by the Download button's formaction.          */
/* ---------------------------------------------------------------------- */
if (isset($_POST['display'])) {

    if (!$eau_nonce_ok) {
        echo "<div class='notice notice-error' style='width: 93%'>" . esc_html__('Security token validation failed!', 'export-all-urls') . '</div>';
        return;
    }

    $options = EAU_Request::from_post();
    if (null === $options) {
        echo "<div class='notice notice-error' style='width: 93%'>" . esc_html__('Security token validation failed!', 'export-all-urls') . '</div>';
        return;
    }

    $options['export_type'] = 'here';

    $valid = EAU_Request::validate($options);
    if ($valid !== true) {
        echo "<div class='notice notice-error' style='width: 93%'>" . wp_kses_post($valid) . '</div>';
        return;
    }

    update_user_meta(get_current_user_id(), Constants::LAST_FIELDS_META, $options['export_fields']);

    $eau_functions->render_here($options);
}
