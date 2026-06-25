<?php

namespace Export_All_URLs;

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'constants.php';

/**
 * Reads and sanitizes the export form submission into a plain options array,
 * and validates it. Used by both request paths (the in-page "Display Here"
 * handler and the admin-post.php streaming handler) so sanitization lives in
 * exactly one place.
 */
class EAU_Request
{
    /**
     * Build a sanitized options array from a nonce-verified $_POST.
     *
     * The nonce is also checked here (in addition to the caller) so that the
     * superglobal reads are provably guarded for static analysis.
     *
     * @return array|null Options array, or null when the nonce is missing/invalid.
     */
    public static function from_post()
    {
        if (
            !isset($_POST['_wpnonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), Constants::EXPORT_NONCE_ACTION)
        ) {
            return null;
        }

        $post_type     = isset($_POST['post-type']) ? sanitize_text_field(wp_unslash($_POST['post-type'])) : '';
        $export_type   = isset($_POST['export-type']) ? sanitize_text_field(wp_unslash($_POST['export-type'])) : '';
        $export_fields = isset($_POST['export_fields'])
            ? array_values(array_map('sanitize_text_field', (array) wp_unslash($_POST['export_fields'])))
            : array();
        $post_status   = isset($_POST['post-status'])
            ? array_values(array_map('sanitize_text_field', (array) wp_unslash($_POST['post-status'])))
            : array();
        $post_author   = isset($_POST['post-author']) ? sanitize_text_field(wp_unslash($_POST['post-author'])) : 'all';
        $exclude       = isset($_POST['exclude-domain']);
        $number        = isset($_POST['number-of-posts']) ? sanitize_text_field(wp_unslash($_POST['number-of-posts'])) : 'all';
        $csv_name      = isset($_POST['csv-file-name']) ? sanitize_file_name(wp_unslash($_POST['csv-file-name'])) : '';

        if ($number === 'range') {
            $offset        = isset($_POST['starting-point']) ? absint(wp_unslash($_POST['starting-point'])) : 0;
            $ending        = isset($_POST['ending-point']) ? absint(wp_unslash($_POST['ending-point'])) : 0;
            $post_per_page = max(0, $ending - $offset);
        } else {
            $offset        = 'all';
            $post_per_page = 'all';
        }

        $from = isset($_POST['posts-from']) ? sanitize_text_field(wp_unslash($_POST['posts-from'])) : '';
        $upto = isset($_POST['posts-upto']) ? sanitize_text_field(wp_unslash($_POST['posts-upto'])) : '';

        $lang_scope = isset($_POST['lang-scope']) ? sanitize_text_field(wp_unslash($_POST['lang-scope'])) : 'all';
        if ('default' !== $lang_scope) {
            $lang_scope = 'all';
        }

        return array(
            'post_type'      => $post_type,
            'export_type'    => $export_type,
            'export_fields'  => $export_fields,
            'post_status'    => $post_status,
            'post_author'    => $post_author,
            'exclude_domain' => $exclude,
            'number'         => $number,
            'offset'         => $offset,
            'post_per_page'  => $post_per_page,
            'csv_name'       => $csv_name,
            'posts_from'     => $from,
            'posts_upto'     => $upto,
            'lang_scope'     => $lang_scope,
        );
    }

    /**
     * Validate options.
     *
     * @param array $o
     * @return true|string True when valid, otherwise an error message.
     */
    public static function validate($o)
    {
        if ($o['post_type'] === '' || $o['export_type'] === '' || empty($o['export_fields'])
            || empty($o['post_status']) || $o['post_author'] === '') {
            return __('Sorry, you missed something. Please recheck the options, especially <strong>Export Fields</strong>, and try again. :)', 'export-all-urls');
        }

        if ($o['export_type'] === 'text' && $o['csv_name'] === '') {
            return __('Invalid/Missing CSV File Name!', 'export-all-urls');
        }

        if ($o['posts_from'] !== '' && $o['posts_upto'] !== '' && $o['posts_from'] > $o['posts_upto']) {
            return __('Sorry, invalid post date range. :)', 'export-all-urls');
        }

        return true;
    }
}
