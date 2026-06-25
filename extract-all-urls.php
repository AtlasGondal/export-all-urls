<?php

/*
Plugin Name: Export All URLs
Plugin URI: https://AtlasGondal.com/
Description: Extract Title, URL, Categories, Tags, Author, Word Count, Excerpt, Featured Image and more for posts, pages and any custom post type. Display the data in the dashboard or download it as CSV or JSON. Useful for migrations, SEO analysis and security audits.
Version: 6.0
Author: Atlas Gondal
Author URI: https://AtlasGondal.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: export-all-urls
Domain Path: /languages
Requires PHP: 5.4
Requires at least: 3.6
*/

namespace Export_All_URLs;

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'classes/constants.php';
require_once plugin_dir_path(__FILE__) . 'classes/class-request.php';
require_once plugin_dir_path(__FILE__) . 'classes/class-functions.php';
require_once plugin_dir_path(__FILE__) . 'classes/class-snapshots.php';
require_once plugin_dir_path(__FILE__) . 'classes/class-snapshot-diff.php';

class ExportAllUrls
{
    public function __construct()
    {
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'extract_all_urls_nav'));
        register_activation_hook(__FILE__, array($this, 'on_activate'));
        add_action('admin_init', array($this, 'redirect_on_activation'));
        add_action('admin_init', array($this, 'maybe_upgrade_db'));
        add_filter('admin_footer_text', array($this, 'footer_text'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_post_' . Constants::EXPORT_ACTION, array($this, 'handle_export'));
        add_action('admin_post_' . Constants::TAKE_SNAPSHOT_ACTION, array($this, 'handle_take_snapshot'));
        add_action('admin_post_' . Constants::DELETE_SNAPSHOT_ACTION, array($this, 'handle_delete_snapshot'));
        add_action('admin_post_' . Constants::DIFF_DOWNLOAD_ACTION, array($this, 'handle_diff_download'));
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('export-all-urls', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function extract_all_urls_nav()
    {
        add_management_page(
            Constants::PLUGIN_NAME,
            Constants::PLUGIN_NAME,
            Constants::PLUGIN_SETTINGS_PAGE_CAPABILITY,
            Constants::PLUGIN_SETTINGS_PAGE_SLUG,
            array($this, 'include_settings_page')
        );
    }

    public function enqueue_scripts($hook)
    {
        if (strpos((string) $hook, Constants::PLUGIN_SETTINGS_PAGE_SLUG) === false) {
            return;
        }

        $dir = plugin_dir_path(__FILE__);
        $css = $dir . 'assets/css/style.css';
        $js = $dir . 'assets/js/script.js';

        // Version assets by file mtime so updated CSS/JS is never served from a stale cache.
        $css_ver = file_exists($css) ? filemtime($css) : Constants::PLUGIN_VERSION;
        $js_ver = file_exists($js) ? filemtime($js) : Constants::PLUGIN_VERSION;

        wp_enqueue_style('eau-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), $css_ver, 'all');
        wp_enqueue_script('eau-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), $js_ver, true);
    }

    public function include_settings_page()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab navigation.
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'export';
        if ('snapshots' === $tab) {
            include plugin_dir_path(__FILE__) . 'views/snapshots-tab.php';
        } else {
            include plugin_dir_path(__FILE__) . 'views/settings-page.php';
        }
    }

    /**
     * admin-post.php: capture a new snapshot, then redirect back to the tab.
     */
    public function handle_take_snapshot()
    {
        if (!current_user_can(Constants::PLUGIN_SETTINGS_PAGE_CAPABILITY)) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'export-all-urls'));
        }
        check_admin_referer(Constants::SNAPSHOT_NONCE_ACTION);

        $label = isset($_POST['snapshot-label']) ? sanitize_text_field(wp_unslash($_POST['snapshot-label'])) : '';

        $snapshots = new EAU_Snapshots();
        $id = $snapshots->create($label);

        $this->redirect_snapshots($id ? 'created' : 'error');
    }

    /**
     * admin-post.php: delete a snapshot, then redirect back to the tab.
     */
    public function handle_delete_snapshot()
    {
        if (!current_user_can(Constants::PLUGIN_SETTINGS_PAGE_CAPABILITY)) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'export-all-urls'));
        }
        check_admin_referer(Constants::SNAPSHOT_NONCE_ACTION);

        $id = isset($_REQUEST['id']) ? absint($_REQUEST['id']) : 0;
        if ($id) {
            $snapshots = new EAU_Snapshots();
            $snapshots->delete($id);
        }

        $this->redirect_snapshots('deleted');
    }

    /**
     * admin-post.php: stream a snapshot diff as CSV or JSON.
     */
    public function handle_diff_download()
    {
        if (!current_user_can(Constants::PLUGIN_SETTINGS_PAGE_CAPABILITY)) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'export-all-urls'));
        }
        check_admin_referer(Constants::SNAPSHOT_NONCE_ACTION);

        $from = isset($_POST['compare-from']) ? absint(wp_unslash($_POST['compare-from'])) : 0;
        $to = isset($_POST['compare-to']) ? sanitize_text_field(wp_unslash($_POST['compare-to'])) : 'live';
        $format = isset($_POST['format']) ? sanitize_text_field(wp_unslash($_POST['format'])) : 'csv';
        if (!in_array($format, array('csv', 'json'), true)) {
            $format = 'csv';
        }

        $differ = new EAU_Snapshot_Diff();
        $diff = ('live' === $to) ? $differ->against_live($from) : $differ->between($from, absint($to));
        $data = $differ->export_rows($diff);

        $exporter = new EAU_Exporter();
        $exporter->stream_rows($data['labels'], $data['rows'], $format, 'export-all-urls-diff');
    }

    private function redirect_snapshots($message)
    {
        wp_safe_redirect(add_query_arg(
            array('page' => Constants::PLUGIN_SETTINGS_PAGE_SLUG, 'tab' => 'snapshots', 'eau_msg' => $message),
            admin_url('tools.php')
        ));
        exit;
    }

    /**
     * admin-post.php handler for streamed CSV / JSON downloads.
     */
    public function handle_export()
    {
        if (!current_user_can(Constants::PLUGIN_SETTINGS_PAGE_CAPABILITY)) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'export-all-urls'));
        }

        check_admin_referer(Constants::EXPORT_NONCE_ACTION);

        $options = EAU_Request::from_post();
        if (null === $options) {
            wp_die(esc_html__('Security token validation failed!', 'export-all-urls'), esc_html__('Export All URLs', 'export-all-urls'), array('back_link' => true));
        }

        $valid = EAU_Request::validate($options);
        if ($valid !== true) {
            wp_die(wp_kses_post($valid), esc_html__('Export All URLs', 'export-all-urls'), array('back_link' => true));
        }

        if ($options['export_type'] !== 'text' && $options['export_type'] !== 'json') {
            wp_die(esc_html__('Invalid export type.', 'export-all-urls'), esc_html__('Export All URLs', 'export-all-urls'), array('back_link' => true));
        }

        update_user_meta(get_current_user_id(), Constants::LAST_FIELDS_META, $options['export_fields']);

        $functions = new EAU_Functions();
        $functions->stream($options);
        exit;
    }

    public function on_activate()
    {
        if (version_compare(PHP_VERSION, Constants::MIN_PHP_VERSION, '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            $plugin_data = get_plugin_data(__FILE__);
            $plugin_version = $plugin_data['Version'];
            $plugin_name = $plugin_data['Name'];
            wp_die(
                '<h1>' . esc_html__('Could not activate plugin: PHP version error', 'export-all-urls') . '</h1>'
                . '<h2>' . esc_html__('PLUGIN:', 'export-all-urls') . ' <i>' . esc_html($plugin_name . ' ' . $plugin_version) . '</i></h2>'
                . '<p><strong>' . esc_html__('You are using PHP version', 'export-all-urls') . ' ' . esc_html(PHP_VERSION) . '</strong>. '
                /* translators: %s: minimum required PHP version. */
                . sprintf(esc_html__('This plugin requires PHP version %s or greater.', 'export-all-urls'), esc_html(Constants::MIN_PHP_VERSION)) . '</p>'
                . '<p>' . esc_html__('WordPress itself recommends using PHP version 7.4 or greater', 'export-all-urls') . ': '
                . '<a href="https://wordpress.org/about/requirements/" target="_blank">' . esc_html__('Official WordPress requirements', 'export-all-urls') . '</a>. '
                . esc_html__('Please upgrade your PHP version or contact your Server administrator.', 'export-all-urls') . '</p>',
                esc_html__('Could not activate plugin: PHP version error', 'export-all-urls'),
                array('back_link' => true)
            );
        }

        EAU_Snapshots::create_tables();

        set_transient('export_all_urls_activation_redirect', true, 30);
    }

    /**
     * Keep the snapshot tables in sync when the plugin is updated without reactivation.
     */
    public function maybe_upgrade_db()
    {
        EAU_Snapshots::maybe_upgrade();
    }

    public function redirect_on_activation()
    {
        if (!get_transient('export_all_urls_activation_redirect')) {
            return;
        }

        delete_transient('export_all_urls_activation_redirect');

        // Don't hijack the screen when several plugins are activated at once.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading WordPress' own activation flag; no state change.
        if (isset($_GET['activate-multi'])) {
            return;
        }

        wp_safe_redirect(add_query_arg(array('page' => Constants::PLUGIN_SETTINGS_PAGE_SLUG), admin_url('tools.php')));
        exit;
    }

    public function footer_text($footer_text)
    {
        $current_screen = get_current_screen();
        $is_plugin_screen = ($current_screen && false !== strpos($current_screen->id, Constants::PLUGIN_SETTINGS_PAGE_SLUG));

        if ($is_plugin_screen) {
            $footer_text = sprintf(
                /* translators: 1: plugin name, 2: five-star rating link. */
                __('Enjoyed %1$s? Please leave us a %2$s rating. We really appreciate your support!', 'export-all-urls'),
                '<strong>Export All URLs</strong>',
                '<a href="https://wordpress.org/support/plugin/export-all-urls/reviews/?filter=5#new-post" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
            );
        }

        return $footer_text;
    }
}

new ExportAllUrls();
