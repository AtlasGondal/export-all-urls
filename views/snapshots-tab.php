<?php

namespace Export_All_URLs;

defined('ABSPATH') || exit;

require_once plugin_dir_path(dirname(__FILE__)) . 'classes/constants.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-snapshots.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-snapshot-diff.php';

// This template is only ever included from within ExportAllUrls::include_settings_page(),
// so its variables are method-scoped, not global.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

if (!current_user_can(Constants::PLUGIN_SETTINGS_PAGE_CAPABILITY)) {
    wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'export-all-urls'));
}

$snapshots_api = new EAU_Snapshots();
$all = $snapshots_api->get_all();

$eau_nonce_ok = isset($_POST['_wpnonce'])
    && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), Constants::SNAPSHOT_NONCE_ACTION);

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status message after a redirect.
$eau_msg = isset($_GET['eau_msg']) ? sanitize_key(wp_unslash($_GET['eau_msg'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preselect for the compare form.
$cmp_from = isset($_GET['cmp_from']) ? absint(wp_unslash($_GET['cmp_from'])) : 0;

$base = admin_url('tools.php?page=' . Constants::PLUGIN_SETTINGS_PAGE_SLUG . '&tab=snapshots');

$newest_id = !empty($all) ? (int) $all[0]->id : 0;
$prev_id = (count($all) > 1) ? (int) $all[1]->id : $newest_id;
$default_from = $cmp_from ? $cmp_from : $prev_id;
// With a single snapshot, comparing against itself is pointless — default the
// "with" side to the live site instead.
$default_to = (count($all) >= 2) ? $newest_id : 'live';

// Resolve the active comparison and keep the user's choice across submits.
$comparing = $eau_nonce_ok && (isset($_POST['eau_compare']) || isset($_POST['eau_compare_live']));
if (isset($_POST['eau_compare_live'])) {
    $sel_from = $newest_id;
    $sel_to = 'live';
} elseif ($eau_nonce_ok && isset($_POST['compare-from'])) {
    $sel_from = absint(wp_unslash($_POST['compare-from']));
    $sel_to = isset($_POST['compare-to']) ? sanitize_text_field(wp_unslash($_POST['compare-to'])) : 'live';
} else {
    $sel_from = $default_from;
    $sel_to = $default_to;
}

$snap_label = function ($snap) {
    $when = get_date_from_gmt($snap->created_at, 'Y-m-d H:i');
    return $snap->label ? ($when . ' — ' . $snap->label) : $when;
};

$badges = function ($r) {
    $pill = function ($text, $class) {
        return '<span class="eau-badge ' . esc_attr($class) . '">' . esc_html($text) . '</span> ';
    };
    $out = '';
    foreach ($r['changed'] as $code) {
        if ('new' === $code) {
            $out .= $pill(__('New', 'export-all-urls'), 'eau-badge-new');
        } elseif ('removed' === $code) {
            $out .= $pill(__('Removed', 'export-all-urls'), 'eau-badge-removed');
        } elseif ('title' === $code) {
            $out .= $pill(__('Title', 'export-all-urls'), 'eau-badge-neutral');
        } elseif ('slug' === $code) {
            $out .= $pill(__('Slug', 'export-all-urls'), 'eau-badge-neutral');
        } elseif ('content' === $code) {
            $out .= $pill(__('Content', 'export-all-urls'), 'eau-badge-neutral');
        } elseif ('taxonomy' === $code) {
            $out .= $pill(__('Taxonomy', 'export-all-urls'), 'eau-badge-neutral');
        } elseif ('meta' === $code) {
            $out .= $pill(__('Custom fields', 'export-all-urls'), 'eau-badge-neutral');
        } elseif ('featured_image' === $code) {
            $out .= $pill(__('Featured image', 'export-all-urls'), 'eau-badge-neutral');
        } elseif ('status' === $code) {
            $out .= $pill(__('Status', 'export-all-urls') . ': ' . ucfirst($r['post_status']), 'eau-badge-warn');
        } elseif ('author' === $code) {
            $out .= $pill(__('Author', 'export-all-urls') . ': ' . $r['author'], 'eau-badge-danger');
        }
    }
    if (in_array('published', $r['flags'], true) && !in_array('status', $r['changed'], true)) {
        $out .= $pill(__('Published', 'export-all-urls'), 'eau-badge-warn');
    }
    return $out;
};

$render_group = function ($heading, $rows) use ($badges) {
    if (empty($rows)) {
        return;
    }
    echo '<h4 class="eau-diff-heading">' . esc_html($heading) . ' (' . count($rows) . ')</h4>';
    echo '<table class="wp-list-table widefat striped eau-diff-table"><thead><tr>';
    echo '<th class="eau-flag-col"></th>';
    echo '<th>' . esc_html__('Title', 'export-all-urls') . '</th>';
    echo '<th>' . esc_html__('URL', 'export-all-urls') . '</th>';
    echo '<th>' . esc_html__('Post Type', 'export-all-urls') . '</th>';
    echo '<th>' . esc_html__('Author', 'export-all-urls') . '</th>';
    echo '<th>' . esc_html__('Changed', 'export-all-urls') . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr class="' . ($r['suspicious'] ? 'eau-suspicious' : '') . '">';
        echo '<td class="eau-flag-col">' . ($r['suspicious'] ? '<span class="dashicons dashicons-warning" aria-hidden="true"></span>' : '') . '</td>';
        echo '<td>' . esc_html($r['title']) . '</td>';
        echo '<td>' . ($r['url'] ? '<a href="' . esc_url($r['url']) . '" target="_blank" rel="noopener">' . esc_html($r['url']) . '</a>' : '') . '</td>';
        echo '<td>' . esc_html($r['post_type']) . '</td>';
        echo '<td>' . esc_html($r['author']) . '</td>';
        echo '<td>' . wp_kses_post($badges($r)) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
};
?>

<div class="wrap">
    <?php include plugin_dir_path(__FILE__) . 'tab-nav.php'; ?>

    <?php if ('created' === $eau_msg) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Snapshot created.', 'export-all-urls'); ?></p></div>
    <?php elseif ('deleted' === $eau_msg) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Snapshot deleted.', 'export-all-urls'); ?></p></div>
    <?php elseif ('error' === $eau_msg) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Could not create the snapshot.', 'export-all-urls'); ?></p></div>
    <?php endif; ?>

    <div class="eauWrapper">
        <div id="eauMainContainer" class="postbox eaucolumns">
            <div class="inside">

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="eau-snapshot-take">
        <input type="hidden" name="action" value="<?php echo esc_attr(Constants::TAKE_SNAPSHOT_ACTION); ?>">
        <?php wp_nonce_field(Constants::SNAPSHOT_NONCE_ACTION); ?>
        <label class="eau-take-label"><?php esc_html_e('Take a new snapshot', 'export-all-urls'); ?></label>
        <input type="text" name="snapshot-label" placeholder="<?php echo esc_attr__('Optional label, e.g. before migration', 'export-all-urls'); ?>" size="36" />
        <button type="submit" class="button button-primary"><?php esc_html_e('Take snapshot', 'export-all-urls'); ?></button>
        <p class="description"><?php esc_html_e('Records a fingerprint of every post and page (all types, all languages) so you can detect changes later.', 'export-all-urls'); ?></p>
    </form>

    <h3><?php esc_html_e('Saved snapshots', 'export-all-urls'); ?></h3>

    <?php if (empty($all)) : ?>
        <p><?php esc_html_e('No snapshots yet. Take your first snapshot above.', 'export-all-urls'); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Date', 'export-all-urls'); ?></th>
                    <th><?php esc_html_e('Label', 'export-all-urls'); ?></th>
                    <th style="width:80px;"><?php esc_html_e('Posts', 'export-all-urls'); ?></th>
                    <th style="width:160px;"><?php esc_html_e('Actions', 'export-all-urls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all as $snap) : ?>
                    <?php
                    $del_url = wp_nonce_url(
                        admin_url('admin-post.php?action=' . Constants::DELETE_SNAPSHOT_ACTION . '&id=' . (int) $snap->id),
                        Constants::SNAPSHOT_NONCE_ACTION
                    );
                    $cmp_url = esc_url(add_query_arg('cmp_from', (int) $snap->id, $base));
                    ?>
                    <tr>
                        <td><?php echo esc_html(get_date_from_gmt($snap->created_at, 'Y-m-d H:i')); ?></td>
                        <td><?php echo esc_html($snap->label); ?></td>
                        <td><?php echo esc_html(number_format_i18n($snap->post_count)); ?></td>
                        <td>
                            <a href="<?php echo esc_url($cmp_url); ?>"><?php esc_html_e('Compare', 'export-all-urls'); ?></a> |
                            <a href="<?php echo esc_url($del_url); ?>" class="eau-delete-link" onclick="return confirm('<?php echo esc_js(__('Delete this snapshot?', 'export-all-urls')); ?>');"><?php esc_html_e('Delete', 'export-all-urls'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3><?php esc_html_e('Compare', 'export-all-urls'); ?></h3>
        <form method="post" action="" class="eau-compare-form">
            <?php wp_nonce_field(Constants::SNAPSHOT_NONCE_ACTION); ?>
            <label><?php esc_html_e('Compare', 'export-all-urls'); ?>
                <select name="compare-from">
                    <?php foreach ($all as $snap) : ?>
                        <option value="<?php echo (int) $snap->id; ?>" <?php selected((int) $snap->id, (int) $sel_from); ?>><?php echo esc_html($snap_label($snap)); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?php esc_html_e('with', 'export-all-urls'); ?>
                <select name="compare-to">
                    <option value="live" <?php selected('live', (string) $sel_to); ?>><?php esc_html_e('Live site (now)', 'export-all-urls'); ?></option>
                    <?php foreach ($all as $snap) : ?>
                        <option value="<?php echo (int) $snap->id; ?>" <?php selected((string) $snap->id, (string) $sel_to); ?>><?php echo esc_html($snap_label($snap)); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" name="eau_compare" class="button button-primary"><?php esc_html_e('Compare', 'export-all-urls'); ?></button>
            <button type="submit" name="eau_compare_live" class="button"><?php esc_html_e('Latest vs live site', 'export-all-urls'); ?></button>
        </form>
    <?php endif; ?>

            </div>
        </div>
        <div id="eauSideContainer" class="eaucolumns">
            <div class="postbox">
                <div class="inside">
                    <h3><?php esc_html_e('About Snapshots', 'export-all-urls'); ?></h3>
                    <p><?php esc_html_e('A snapshot records a lightweight fingerprint of every post, page and custom post type on your site, so you can detect changes later.', 'export-all-urls'); ?></p>
                    <hr>
                    <h3><?php esc_html_e('How to use', 'export-all-urls'); ?></h3>
                    <ol>
                        <li><?php esc_html_e('Click "Take snapshot" to record the current state.', 'export-all-urls'); ?></li>
                        <li><?php esc_html_e('Later, use "Latest vs live site" to see what changed since then.', 'export-all-urls'); ?></li>
                        <li><?php esc_html_e('Review the highlighted rows; download the differences if needed.', 'export-all-urls'); ?></li>
                    </ol>
                    <hr>
                    <h3><?php esc_html_e('Why it helps', 'export-all-urls'); ?></h3>
                    <ul>
                        <li><?php esc_html_e('Spot unauthorized or injected posts and pages.', 'export-all-urls'); ?></li>
                        <li><?php esc_html_e('Confirm a migration moved everything across.', 'export-all-urls'); ?></li>
                        <li><?php esc_html_e('Keep an audit trail of content changes over time.', 'export-all-urls'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php
    if ($comparing) {

        $from_id = (int) $sel_from;
        $to = (string) $sel_to;

        $differ = new EAU_Snapshot_Diff();
        $diff = ('live' === $to) ? $differ->against_live($from_id) : $differ->between($from_id, (int) $to);

        $from_snap = $snapshots_api->get($from_id);
        $from_text = $from_snap ? $snap_label($from_snap) : ('#' . $from_id);
        if ('live' === $to) {
            $to_text = __('Live site (now)', 'export-all-urls');
        } else {
            $to_snap = $snapshots_api->get((int) $to);
            $to_text = $to_snap ? $snap_label($to_snap) : ('#' . (int) $to);
        }

        // A snapshot's fingerprint records which hashing algorithm captured it. If the
        // two sides differ (e.g. an old snapshot vs the current live site), their hashes
        // are not comparable and the diff may be inaccurate.
        $eau_fp = function ($snap) {
            return (is_object($snap) && isset($snap->fingerprint)) ? $snap->fingerprint : '1';
        };
        $from_fp = $from_snap ? $eau_fp($from_snap) : Constants::SNAPSHOT_FINGERPRINT;
        $to_fp = ('live' === $to) ? Constants::SNAPSHOT_FINGERPRINT : ($to_snap ? $eau_fp($to_snap) : Constants::SNAPSHOT_FINGERPRINT);
        $stale = ($from_fp !== $to_fp);

        $total_changes = $diff['counts']['added'] + $diff['counts']['removed'] + $diff['counts']['modified'];
        ?>

        <hr />
        <h3><?php esc_html_e('Result', 'export-all-urls'); ?></h3>
        <p class="description"><?php echo esc_html__('From:', 'export-all-urls') . ' ' . esc_html($from_text) . ' &rarr; ' . esc_html__('To:', 'export-all-urls') . ' ' . esc_html($to_text); ?></p>

        <?php if ($stale) : ?>
            <div class="notice notice-warning" style="width:97%"><p><?php esc_html_e('This snapshot was captured with an older version of the plugin, so some differences may be inaccurate. Please take a fresh snapshot for reliable results.', 'export-all-urls'); ?></p></div>
        <?php endif; ?>

        <div class="eau-diff-summary">
            <div class="eau-card eau-card-added"><span class="eau-card-label"><?php esc_html_e('Added', 'export-all-urls'); ?></span><span class="eau-card-num">+<?php echo esc_html(number_format_i18n($diff['counts']['added'])); ?></span></div>
            <div class="eau-card eau-card-removed"><span class="eau-card-label"><?php esc_html_e('Removed', 'export-all-urls'); ?></span><span class="eau-card-num">&minus;<?php echo esc_html(number_format_i18n($diff['counts']['removed'])); ?></span></div>
            <div class="eau-card eau-card-modified"><span class="eau-card-label"><?php esc_html_e('Modified', 'export-all-urls'); ?></span><span class="eau-card-num"><?php echo esc_html(number_format_i18n($diff['counts']['modified'])); ?></span></div>
            <div class="eau-card eau-card-suspicious"><span class="eau-card-label"><?php esc_html_e('Notable', 'export-all-urls'); ?></span><span class="eau-card-num"><?php echo esc_html(number_format_i18n($diff['suspicious'])); ?></span></div>
        </div>

        <?php if (0 === $total_changes) : ?>
            <p><?php esc_html_e('No changes detected.', 'export-all-urls'); ?></p>
        <?php else : ?>
            <?php
            $render_group(__('Added', 'export-all-urls'), $diff['added']);
            $render_group(__('Modified', 'export-all-urls'), $diff['modified']);
            $render_group(__('Removed', 'export-all-urls'), $diff['removed']);
            ?>
            <?php if ($diff['suspicious'] > 0) : ?>
                <p class="description eau-suspicious-note"><span class="dashicons dashicons-warning" aria-hidden="true"></span> <?php esc_html_e('Highlighted rows are worth reviewing if you did not make these changes yourself: newly published pages, or a changed author or status.', 'export-all-urls'); ?></p>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="eau-diff-download">
                <?php wp_nonce_field(Constants::SNAPSHOT_NONCE_ACTION); ?>
                <input type="hidden" name="action" value="<?php echo esc_attr(Constants::DIFF_DOWNLOAD_ACTION); ?>">
                <input type="hidden" name="compare-from" value="<?php echo (int) $from_id; ?>">
                <input type="hidden" name="compare-to" value="<?php echo esc_attr($to); ?>">
                <button type="submit" name="format" value="csv" class="button"><?php esc_html_e('Download diff (CSV)', 'export-all-urls'); ?></button>
                <button type="submit" name="format" value="json" class="button"><?php esc_html_e('Download diff (JSON)', 'export-all-urls'); ?></button>
            </form>
        <?php endif; ?>
        <?php
    }
    ?>
</div>
