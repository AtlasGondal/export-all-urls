<?php

namespace Export_All_URLs;

defined('ABSPATH') || exit;

/**
 * Plugin-wide constants.
 *
 * NOTE: class constants are declared without a visibility modifier on purpose.
 * `public const` requires PHP 7.1+, but this plugin targets PHP 5.4 onward.
 */
class Constants
{
    const PLUGIN_NAME = 'Export All URLs';
    const PLUGIN_VERSION = '6.0';
    const PLUGIN_SLUG = 'export-all-urls';
    const PLUGIN_FILE = 'export-all-urls/export-all-urls.php';
    const PLUGIN_DIR = 'export-all-urls';
    const PLUGIN_URL = 'https://wordpress.org/plugins/export-all-urls/';
    const PLUGIN_AUTHOR = 'Atlas Gondal';
    const PLUGIN_AUTHOR_URI = 'https://AtlasGondal.com/';
    const PLUGIN_DESCRIPTION = 'Export All URLs';
    const PLUGIN_LICENSE = 'GPLv2 or later';
    const PLUGIN_LICENSE_URI = 'http://www.gnu.org/licenses/gpl-2.0.html';
    const PLUGIN_TEXT_DOMAIN = 'export-all-urls';
    const PLUGIN_SETTINGS_PAGE_CAPABILITY = 'manage_options';
    const PLUGIN_SETTINGS_PAGE_SLUG = 'extract-all-urls-settings';

    /** Minimum supported PHP version. Keep in sync with readme.txt "Requires PHP". */
    const MIN_PHP_VERSION = '5.4';

    /** Nonce action used by the export form. */
    const EXPORT_NONCE_ACTION = 'export_urls';

    /** admin-post.php action used for streamed downloads (CSV / JSON). */
    const EXPORT_ACTION = 'eau_export';

    /** User-meta key that remembers the last-used field selection. */
    const LAST_FIELDS_META = '_eau_last_export_fields';

    /** Posts fetched per batch when exporting (keeps memory bounded on huge sites). */
    const BATCH_SIZE = 500;

    /** Default rows-per-page for the on-screen results table. */
    const DEFAULT_PER_PAGE = 100;

    /* --- Phase 2: snapshots --- */

    /** Custom table base names (the $wpdb prefix is added at runtime). */
    const SNAPSHOTS_TABLE = 'eau_snapshots';
    const SNAPSHOT_ITEMS_TABLE = 'eau_snapshot_items';

    /**
     * Installed DB schema version. Bump whenever the snapshot TABLES change; the
     * admin_init upgrade check then re-runs dbDelta automatically. Adding columns/keys
     * is automatic; for destructive changes (drop/rename) add explicit upgrade code.
     */
    const DB_VERSION_OPTION = 'eau_db_version';
    const DB_VERSION = '1';

    /**
     * Version of the snapshot fingerprint ALGORITHM (how the content/taxonomy/meta
     * hashes in EAU_Snapshots::build_item() are computed). BUMP THIS whenever any of
     * that hashing logic changes: snapshots store this value, and the compare screen
     * warns when two sides were captured with different algorithm versions (their
     * hashes are not comparable, so the user should take a fresh snapshot).
     */
    const SNAPSHOT_FINGERPRINT = '1';

    /** Nonce action shared by the snapshot forms. */
    const SNAPSHOT_NONCE_ACTION = 'eau_snapshot';

    /** admin-post.php actions for the state-changing snapshot operations. */
    const TAKE_SNAPSHOT_ACTION = 'eau_take_snapshot';
    const DELETE_SNAPSHOT_ACTION = 'eau_delete_snapshot';
    const DIFF_DOWNLOAD_ACTION = 'eau_diff_download';

    /** Rows inserted per multi-row INSERT while capturing a snapshot. */
    const SNAPSHOT_INSERT_CHUNK = 100;
}
