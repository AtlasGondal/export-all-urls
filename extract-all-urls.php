<?php

/*
Plugin Name: Export All URLs
Plugin URI: https://AtlasGondal.com/
Description: This plugin enables you to extract information such as Title, URL, Categories, Tags, Author, as well as Published and Modified dates for built-in post types (e.g., post, page) or any other custom post types present on your site. You have the option to display the output in the dashboard or export it as a CSV file. This can be highly beneficial for tasks like migration, SEO analysis, and security audits.
Version: 5.0
Author: Atlas Gondal
Author URI: https://AtlasGondal.com/
License: GPL v2 or higher
License URI: License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

namespace Export_All_URLs;

defined('ABSPATH') || exit;

require_once(plugin_dir_path(__FILE__) . 'classes/constants.php');

class ExportAllUrls
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'extract_all_urls_nav'));
        register_activation_hook(__FILE__, array($this, 'on_activate'));
        add_action('admin_init', array($this, 'redirect_on_activation'));
        add_filter('admin_footer_text', array($this, 'footer_text'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function extract_all_urls_nav()
    {
        add_management_page(Constants::PLUGIN_NAME, Constants::PLUGIN_NAME, Constants::PLUGIN_SETTINGS_PAGE_CAPABILITY, Constants::PLUGIN_SETTINGS_PAGE_SLUG, array($this, 'include_settings_page'));
    }

    public function enqueue_scripts()
        {            
            wp_enqueue_style('eau-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), '1.0.0', 'all');
            wp_enqueue_script('eau-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), '1.0.0', true);
        }

    public function include_settings_page()
    {
        include(plugin_dir_path(__FILE__) . 'extract-all-urls-settings.php');
    }

    public function on_activate()
    {
        if (version_compare(PHP_VERSION, '5.4', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            $plugin_data = get_plugin_data(__FILE__);
            $plugin_version = $plugin_data['Version'];
            $plugin_name = $plugin_data['Name'];
            wp_die('<h1>' . __('Could not activate plugin: PHP version error', Constants::PLUGIN_TEXT_DOMAIN) . '</h1><h2>' . __('PLUGIN:', Constants::PLUGIN_TEXT_DOMAIN) . ' <i>' . $plugin_name . ' ' . $plugin_version . '</i></h2><p><strong>' . __('You are using PHP version', Constants::PLUGIN_TEXT_DOMAIN) . ' ' . PHP_VERSION . '</strong>. ' . __('This plugin has been tested with PHP versions 5.4 and greater.', Constants::PLUGIN_TEXT_DOMAIN) . '</p><p>' . __('WordPress itself recommends using PHP version 7.4 or greater', Constants::PLUGIN_TEXT_DOMAIN) . ': <a href="https://wordpress.org/about/requirements/" target="_blank">' . __('Official WordPress requirements', Constants::PLUGIN_TEXT_DOMAIN) . '</a>' . '. ' . __('Please upgrade your PHP version or contact your Server administrator.', Constants::PLUGIN_TEXT_DOMAIN) . '</p>', __('Could not activate plugin: PHP version error', Constants::PLUGIN_TEXT_DOMAIN), array('back_link' => true));
        }
        set_transient('export_all_urls_activation_redirect', true, 30);
    }

    function redirect_on_activation()
    {

        if (!get_transient('export_all_urls_activation_redirect')) {
            return;
        }

        delete_transient('export_all_urls_activation_redirect');

        wp_safe_redirect(add_query_arg(array('page' => 'extract-all-urls-settings'), admin_url('tools.php')));
    }

    function footer_text($footer_text)
    {
        $current_screen = get_current_screen();
        $is_export_all_urls_screen = ($current_screen && false !== strpos($current_screen->id, 'extract-all-urls-settings'));

        if ($is_export_all_urls_screen) {
            $footer_text = sprintf(__('Enjoyed %s? Please leave us a %s rating. We really appreciate your support!', Constants::PLUGIN_TEXT_DOMAIN), '<strong>Export All URLs</strong>', '<a href="https://wordpress.org/support/plugin/export-all-urls/reviews/?filter=5#new-post" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>');
        }

        return $footer_text;
    }
}

new ExportAllUrls();
