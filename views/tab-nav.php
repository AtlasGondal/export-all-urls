<?php

namespace Export_All_URLs;

defined('ABSPATH') || exit;

require_once plugin_dir_path(dirname(__FILE__)) . 'classes/constants.php';

// This template is only ever included from another template that is itself
// included from a class method, so its variables are method-scoped, not global.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab navigation.
$eau_active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'export';
$eau_base = admin_url('tools.php?page=' . Constants::PLUGIN_SETTINGS_PAGE_SLUG);
?>
<h1 class="eau-page-title"><?php echo esc_html(Constants::PLUGIN_NAME); ?></h1>
<nav class="nav-tab-wrapper eau-tab-nav">
    <a href="<?php echo esc_url($eau_base); ?>" class="nav-tab <?php echo ('snapshots' !== $eau_active_tab) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Export', 'export-all-urls'); ?></a>
    <a href="<?php echo esc_url($eau_base . '&tab=snapshots'); ?>" class="nav-tab <?php echo ('snapshots' === $eau_active_tab) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Snapshots', 'export-all-urls'); ?></a>
</nav>
