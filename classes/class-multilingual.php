<?php

namespace Export_All_URLs;

defined('ABSPATH') || exit;

// WPML and Polylang expose third-party hooks (wpml_*) whose names are fixed by
// those plugins and cannot be prefixed.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

/**
 * Thin abstraction over WPML and Polylang.
 *
 * Detection happens once at construction. When neither plugin is active the
 * provider is a no-op. Public APIs only (no internal classes), so this stays
 * safe across plugin versions and on PHP 5.4 / WP 3.x.
 *
 * The important job here is language SCOPE: both plugins filter WP_Query by the
 * currently active language, so a normal export would only return posts in the
 * language picked in the admin bar. begin_scope()/query_lang() let the exporter
 * force "all languages" (the default) or "default language only" regardless of
 * what is selected in the admin bar.
 */
class EAU_Multilingual
{
    /** @var string '' | 'wpml' | 'polylang' */
    private $type = '';

    /** @var string Language active when the request started (so we can restore it). */
    private $original_lang = '';

    public function __construct()
    {
        if (function_exists('pll_get_post_language') && function_exists('pll_get_post_translations')) {
            $this->type = 'polylang';
        } elseif (defined('ICL_SITEPRESS_VERSION') || function_exists('icl_object_id')) {
            $this->type = 'wpml';
            $current = apply_filters('wpml_current_language', null);
            $this->original_lang = $current ? $current : '';
        }
    }

    public function is_active()
    {
        return $this->type !== '';
    }

    public function type()
    {
        return $this->type;
    }

    /**
     * The site's default language code/slug.
     */
    public function default_language()
    {
        if ($this->type === 'polylang' && function_exists('pll_default_language')) {
            $code = pll_default_language('slug');
            return $code ? $code : '';
        }
        if ($this->type === 'wpml') {
            $code = apply_filters('wpml_default_language', null);
            return $code ? $code : '';
        }
        return '';
    }

    /**
     * Force the language scope for the queries that follow (WPML only — it works
     * off a global "current language"). $scope is 'all' or 'default'.
     */
    public function begin_scope($scope)
    {
        if ($this->type !== 'wpml') {
            return;
        }
        $lang = ($scope === 'default') ? $this->default_language() : 'all';
        if ($lang) {
            do_action('wpml_switch_language', $lang);
        }
    }

    /**
     * Restore the language WPML was on before begin_scope().
     */
    public function end_scope()
    {
        if ($this->type === 'wpml' && $this->original_lang) {
            do_action('wpml_switch_language', $this->original_lang);
        }
    }

    /**
     * WP_Query 'lang' argument for Polylang (which filters per-query rather than
     * via a global). Returns null when not applicable (e.g. WPML).
     */
    public function query_lang($scope)
    {
        if ($this->type === 'polylang') {
            return ($scope === 'default') ? $this->default_language() : '';
        }
        return null;
    }

    /**
     * Language code/slug for a single post (e.g. "en", "fr"). Empty when unknown.
     */
    public function post_language($post_id)
    {
        if ($this->type === 'polylang') {
            $lang = pll_get_post_language($post_id, 'slug');
            return $lang ? $lang : '';
        }

        if ($this->type === 'wpml') {
            $details = apply_filters('wpml_post_language_details', null, $post_id);
            if (is_array($details) && !empty($details['language_code'])) {
                return $details['language_code'];
            }
        }

        return '';
    }

    /**
     * Permalinks for every translation of a post, keyed by language code.
     *
     * @return array lang_code => permalink (includes the post's own language)
     */
    public function translations($post_id)
    {
        $out = array();

        if ($this->type === 'polylang') {
            $map = pll_get_post_translations($post_id);
            if (is_array($map)) {
                foreach ($map as $lang => $tid) {
                    $url = get_permalink($tid);
                    if ($url) {
                        $out[$lang] = $url;
                    }
                }
            }
            return $out;
        }

        if ($this->type === 'wpml') {
            $languages = apply_filters('wpml_active_languages', null, array('skip_missing' => 0));
            if (is_array($languages)) {
                $post_type = get_post_type($post_id);
                foreach ($languages as $code => $lang) {
                    $tid = apply_filters('wpml_object_id', $post_id, $post_type, false, $code);
                    if (!$tid) {
                        continue;
                    }
                    // Only list a language when a GENUINE translation exists in it. For
                    // post types not set translatable in WPML (e.g. WooCommerce products
                    // without WooCommerce Multilingual), wpml_object_id falls back to the
                    // original post for every language — which would otherwise list every
                    // language pointing at the same default URL.
                    $details = apply_filters('wpml_post_language_details', null, $tid);
                    $actual = (is_array($details) && !empty($details['language_code'])) ? $details['language_code'] : '';
                    if ($actual !== $code) {
                        continue;
                    }
                    $url = get_permalink($tid);
                    if ($url) {
                        $out[$code] = $url;
                    }
                }
            }
        }

        return $out;
    }
}
