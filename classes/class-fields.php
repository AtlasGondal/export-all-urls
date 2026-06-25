<?php

namespace Export_All_URLs;

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'constants.php';

/**
 * Single source of truth for export columns.
 *
 * Every field is declared once with its label, UI group and a value callback.
 * The export loop and the header row both iterate the *same* selected-key list,
 * so columns and headers can never drift out of alignment (the old code kept
 * labels and data in two parallel structures, which was fragile).
 *
 * Field keys are unchanged from v5.1 so existing translations keep working;
 * new keys are appended.
 */
class EAU_Fields
{
    /**
     * UI groups, in display order. The Multilingual group is only shown when a
     * multilingual plugin is active (handled by the view).
     *
     * @return array group_key => array(label, collapsed)
     */
    public function groups()
    {
        return array(
            'core'         => array('label' => __('Core', 'export-all-urls'),           'collapsed' => false),
            'taxonomy'     => array('label' => __('Taxonomy', 'export-all-urls'),       'collapsed' => true),
            'author_dates' => array('label' => __('Author & Dates', 'export-all-urls'), 'collapsed' => true),
            'content'      => array('label' => __('Content', 'export-all-urls'),        'collapsed' => true),
            'multilingual' => array(
                'label'       => __('Multilingual', 'export-all-urls'),
                'collapsed'   => true,
                'description' => __('The "Language" column is each post\'s own language. The "Translation URLs" column lists the URL of every available translation of that post.', 'export-all-urls'),
            ),
        );
    }

    /**
     * Field presets surfaced as quick-select buttons in the UI.
     *
     * @return array preset_key => array(label, fields[])
     */
    public function presets()
    {
        return array(
            'seo'  => array('label' => __('SEO basics', 'export-all-urls'), 'fields' => array('title', 'url')),
            'full' => array('label' => __('Full audit', 'export-all-urls'), 'fields' => array_keys($this->fields())),
            'none' => array('label' => __('None', 'export-all-urls'),       'fields' => array()),
        );
    }

    /**
     * The ordered field registry.
     *
     * @return array key => array(label, group, cb)
     */
    public function fields()
    {
        return array(
            // --- Core (preserves original order: id, title, url, ... status) ---
            'p_id'            => array('label' => __('Post ID', 'export-all-urls'),           'group' => 'core',         'cb' => 'f_id'),
            'title'           => array('label' => __('Title', 'export-all-urls'),             'group' => 'core',         'cb' => 'f_title'),
            'url'             => array('label' => __('URL', 'export-all-urls'),               'group' => 'core',         'cb' => 'f_url'),
            'status'          => array('label' => __('Status', 'export-all-urls'),            'group' => 'core',         'cb' => 'f_status'),

            // --- Taxonomy ---
            'categories'      => array('label' => __('Categories', 'export-all-urls'),        'group' => 'taxonomy',     'cb' => 'f_categories'),
            'category_urls'   => array('label' => __('Category URLs', 'export-all-urls'),     'group' => 'taxonomy',     'cb' => 'f_category_urls'),
            'tags'            => array('label' => __('Tags', 'export-all-urls'),              'group' => 'taxonomy',     'cb' => 'f_tags'),
            'tag_urls'        => array('label' => __('Tag URLs', 'export-all-urls'),          'group' => 'taxonomy',     'cb' => 'f_tag_urls'),

            // --- Author & Dates ---
            'author'          => array('label' => __('Author', 'export-all-urls'),            'group' => 'author_dates', 'cb' => 'f_author'),
            'p_date'          => array('label' => __('Published Date', 'export-all-urls'),    'group' => 'author_dates', 'cb' => 'f_published_date'),
            'm_date'          => array('label' => __('Modified Date', 'export-all-urls'),     'group' => 'author_dates', 'cb' => 'f_modified_date'),

            // --- Content (new) ---
            'word_count'      => array('label' => __('Word Count', 'export-all-urls'),        'group' => 'content',      'cb' => 'f_word_count'),
            'excerpt'         => array('label' => __('Excerpt', 'export-all-urls'),           'group' => 'content',      'cb' => 'f_excerpt'),
            'featured_image'  => array('label' => __('Featured Image URL', 'export-all-urls'),'group' => 'content',      'cb' => 'f_featured_image'),
            'comment_count'   => array('label' => __('Comment Count', 'export-all-urls'),     'group' => 'content',      'cb' => 'f_comment_count'),
            'slug'            => array('label' => __('Slug', 'export-all-urls'),              'group' => 'content',      'cb' => 'f_slug'),
            'post_type'       => array('label' => __('Post Type', 'export-all-urls'),         'group' => 'content',      'cb' => 'f_post_type'),

            // --- Multilingual (new; empty unless WPML/Polylang active) ---
            'language'        => array('label' => __('Language', 'export-all-urls'),         'group' => 'multilingual', 'cb' => 'f_language'),
            'translated_urls' => array('label' => __('Translation URLs', 'export-all-urls'), 'group' => 'multilingual', 'cb' => 'f_translated_urls'),
        );
    }

    /**
     * Labels for the selected fields, in the order given.
     *
     * @param array $selected_fields Selected field keys.
     * @param bool  $hash            Prepend a '#' counter column (HTML table).
     * @return array
     */
    public function labels_for($selected_fields, $hash = false)
    {
        $all = $this->fields();
        $labels = $hash ? array('#') : array();

        foreach ($selected_fields as $key) {
            if (isset($all[$key])) {
                $labels[] = $all[$key]['label'];
            }
        }

        return $labels;
    }

    /**
     * Resolve a single field's value for a post.
     *
     * @param string $key     Field key.
     * @param int    $post_id Post ID (loop is active, globals are set).
     * @param array  $options Sanitized request options.
     * @param array  $context Per-post precomputed data (terms, multilingual, ...).
     * @return string
     */
    public function value($key, $post_id, $options, $context)
    {
        $all = $this->fields();
        if (!isset($all[$key])) {
            return '';
        }

        return call_user_func(array($this, $all[$key]['cb']), $post_id, $options, $context);
    }

    /* --------------------------------------------------------------------- */
    /* Value callbacks. Signature: ($post_id, $options, $context) => string. */
    /* --------------------------------------------------------------------- */

    public function f_id($id, $o, $c)
    {
        return (string) $id;
    }

    public function f_title($id, $o, $c)
    {
        return htmlspecialchars_decode(get_the_title($id));
    }

    public function f_url($id, $o, $c)
    {
        $permalink = get_permalink($id);
        if (!empty($o['exclude_domain'])) {
            $permalink = $this->relative_url($permalink);
        }
        return esc_url($permalink);
    }

    public function f_status($id, $o, $c)
    {
        return ucfirst(get_post_status($id));
    }

    public function f_categories($id, $o, $c)
    {
        return $this->join_term_names($c, 'cat');
    }

    public function f_category_urls($id, $o, $c)
    {
        return $this->join_term_urls($c, 'cat');
    }

    public function f_tags($id, $o, $c)
    {
        return $this->join_term_names($c, 'tag');
    }

    public function f_tag_urls($id, $o, $c)
    {
        return $this->join_term_urls($c, 'tag');
    }

    public function f_author($id, $o, $c)
    {
        return htmlspecialchars_decode(get_the_author());
    }

    public function f_published_date($id, $o, $c)
    {
        return get_the_date('Y-m-d H:i:s', $id);
    }

    public function f_modified_date($id, $o, $c)
    {
        return get_the_modified_date('Y-m-d H:i:s', $id);
    }

    public function f_word_count($id, $o, $c)
    {
        $text = get_post_field('post_content', $id);
        $text = strip_shortcodes($text);
        $text = wp_strip_all_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        $matches = array();
        // Unicode-aware: runs of letters/digits across scripts. str_word_count()
        // is NOT multibyte-safe and corrupts non-Latin text, so it is avoided.
        $count = preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);

        return ($count === false) ? '0' : (string) $count;
    }

    public function f_excerpt($id, $o, $c)
    {
        $post = get_post($id);
        $excerpt = $post ? $post->post_excerpt : '';

        if ($excerpt === '' && $post) {
            $body = html_entity_decode(wp_strip_all_tags(strip_shortcodes($post->post_content)), ENT_QUOTES, 'UTF-8');
            if (function_exists('wp_trim_words')) {
                $excerpt = wp_trim_words($body, 55, '');
            } else {
                $words = preg_split('/\s+/u', trim($body));
                $excerpt = implode(' ', array_slice($words, 0, 55));
            }
        }

        return htmlspecialchars_decode($excerpt);
    }

    public function f_featured_image($id, $o, $c)
    {
        $thumb_id = get_post_thumbnail_id($id);
        if (!$thumb_id) {
            return '';
        }
        // wp_get_attachment_url() is available since WP 2.1 (wp_get_attachment_image_url is 4.4+).
        $url = wp_get_attachment_url($thumb_id);
        return $url ? esc_url($url) : '';
    }

    public function f_comment_count($id, $o, $c)
    {
        return (string) get_comments_number($id);
    }

    public function f_slug($id, $o, $c)
    {
        return get_post_field('post_name', $id);
    }

    public function f_post_type($id, $o, $c)
    {
        return get_post_type($id);
    }

    public function f_language($id, $o, $c)
    {
        return isset($c['ml']) ? $c['ml']->post_language($id) : '';
    }

    public function f_translated_urls($id, $o, $c)
    {
        if (!isset($c['ml'])) {
            return '';
        }

        $pairs = array();
        foreach ($c['ml']->translations($id) as $lang => $url) {
            $pairs[] = $lang . ':' . esc_url($url);
        }

        return implode(', ', $pairs);
    }

    /* ----------------------------- helpers ------------------------------- */

    /**
     * Strip the scheme + host from a URL, leaving the path (and query/fragment).
     * Preserved verbatim from the original eau_extract_relative_url().
     */
    public function relative_url($url)
    {
        return preg_replace('/^(http)?s?:?\/\/[^\/]*(\/?.*)$/i', '$2', '' . $url);
    }

    /**
     * Join term names whose taxonomy name contains $needle ('cat' or 'tag').
     * Mirrors the original substring-match behavior.
     */
    private function join_term_names($context, $needle)
    {
        $names = array();
        if (!empty($context['terms'])) {
            foreach ($context['terms'] as $taxonomy => $terms) {
                if (strpos($taxonomy, $needle) !== false) {
                    foreach ($terms as $term) {
                        $names[] = esc_html($term->name);
                    }
                }
            }
        }
        return implode(', ', $names);
    }

    private function join_term_urls($context, $needle)
    {
        $urls = array();
        if (!empty($context['terms'])) {
            foreach ($context['terms'] as $taxonomy => $terms) {
                if (strpos($taxonomy, $needle) !== false) {
                    foreach ($terms as $term) {
                        $link = get_term_link($term);
                        if (!is_wp_error($link)) {
                            $urls[] = esc_url($link);
                        }
                    }
                }
            }
        }
        return implode(', ', $urls);
    }
}
