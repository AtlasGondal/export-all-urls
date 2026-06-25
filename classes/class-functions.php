<?php

namespace Export_All_URLs;

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'constants.php';
require_once plugin_dir_path(__FILE__) . 'class-fields.php';
require_once plugin_dir_path(__FILE__) . 'class-multilingual.php';
require_once plugin_dir_path(__FILE__) . 'class-query.php';
require_once plugin_dir_path(__FILE__) . 'class-exporter.php';

/**
 * Orchestrates an export run: query (in batches) -> build rows from the field
 * registry -> stream to the chosen writer.
 *
 * Large sites with limited resources were timing out, so rows are fetched in
 * fixed-size batches and streamed out one at a time. Peak memory stays bounded
 * no matter how many posts match, and term/meta caches are only primed when a
 * field that needs them is actually selected.
 */
class EAU_Functions
{
    /** @var EAU_Fields */
    private $fields;
    /** @var EAU_Exporter */
    private $exporter;
    /** @var EAU_Multilingual */
    private $multilingual;

    public function __construct()
    {
        $this->fields = new EAU_Fields();
        $this->exporter = new EAU_Exporter();
        $this->multilingual = new EAU_Multilingual();
    }

    public function fields()
    {
        return $this->fields;
    }

    public function multilingual()
    {
        return $this->multilingual;
    }

    /**
     * Iterate matched posts in batches, invoking $callback($row) for each row.
     *
     * @param array    $options  Sanitized options.
     * @param callable $callback Receives one row array per matched post.
     * @param int      $max      Optional hard cap on the number of rows produced.
     * @return int Number of rows produced.
     */
    public function each_row($options, $callback, $max = 0)
    {
        $scope = isset($options['lang_scope']) ? $options['lang_scope'] : 'all';
        $this->multilingual->begin_scope($scope);

        $need_terms = $this->needs_terms($options);

        $args_base = EAU_Query::build($options);
        $args_base['no_found_rows'] = true;                 // skip SQL_CALC_FOUND_ROWS (slow on big tables)
        $args_base['update_post_term_cache'] = $need_terms; // only prime caches we actually use
        $args_base['update_post_meta_cache'] = $this->needs_meta($options);

        $lang = $this->multilingual->query_lang($scope);    // Polylang per-query language
        if (null !== $lang) {
            $args_base['lang'] = $lang;
        }

        $batch = Constants::BATCH_SIZE;
        $start = ($options['offset'] === 'all' || $options['offset'] === '') ? 0 : (int) $options['offset'];
        $limit = ($options['post_per_page'] === 'all') ? -1 : (int) $options['post_per_page'];

        $fetched = 0;
        while (true) {
            $this_batch = $batch;
            if ($limit !== -1) {
                $remaining = $limit - $fetched;
                if ($remaining <= 0) {
                    break;
                }
                $this_batch = min($this_batch, $remaining);
            }
            if ($max > 0) {
                $cap_remaining = $max - $fetched;
                if ($cap_remaining <= 0) {
                    break;
                }
                $this_batch = min($this_batch, $cap_remaining);
            }

            $args = $args_base;
            $args['posts_per_page'] = $this_batch;
            $args['offset'] = $start + $fetched;

            $query = new \WP_Query($args);
            if (!$query->have_posts()) {
                wp_reset_postdata();
                break;
            }

            $in_batch = 0;
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $context = $this->build_context($post_id, $need_terms);

                $row = array();
                foreach ($options['export_fields'] as $key) {
                    $row[] = $this->fields->value($key, $post_id, $options, $context);
                }
                call_user_func($callback, $row);

                $in_batch++;
                $fetched++;
                if ($max > 0 && $fetched >= $max) {
                    break;
                }
            }
            wp_reset_postdata();

            if ($max > 0 && $fetched >= $max) {
                break;
            }
            if ($in_batch < $this_batch) {
                break; // last (partial) batch — nothing more to fetch
            }
        }

        $this->multilingual->end_scope();

        return $fetched;
    }

    /**
     * Stream a CSV/JSON download. Must run before any output (admin-post.php).
     *
     * @param array $options
     */
    public function stream($options)
    {
        if (!$this->has_any($options)) {
            wp_die(
                esc_html__('No result found in that range, please reselect and try again!', 'export-all-urls'),
                esc_html__('Export All URLs', 'export-all-urls'),
                array('back_link' => true)
            );
        }

        $labels = $this->fields->labels_for($options['export_fields']);
        $exporter = $this->exporter;

        if ($options['export_type'] === 'json') {
            $exporter->send_download_headers('application/json', $options['csv_name'], 'json');
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download body, not HTML.
            echo '[';
            $first = true;
            $this->each_row($options, function ($row) use ($exporter, $labels, &$first) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download body, not HTML.
                echo ($first ? '' : ',') . $exporter->json_record($labels, $row);
                $first = false;
            });
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download body, not HTML.
            echo ']';
        } else {
            $exporter->send_download_headers('text/csv', $options['csv_name'], 'csv');
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV BOM + header row, not HTML.
            echo "\xEF\xBB\xBF" . $exporter->csv_line($labels);
            $this->each_row($options, function ($row) use ($exporter) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV download body, not HTML.
                echo $exporter->csv_line($row);
            });
        }

        exit;
    }

    /**
     * Render every matched row inline as an HTML table, paginated in the browser.
     *
     * @param array $options
     */
    public function render_here($options)
    {
        $rows = array();
        $this->each_row($options, function ($row) use (&$rows) {
            $rows[] = $row;
        });

        if (empty($rows)) {
            echo "<div class='notice notice-error' style='width: 93%'>" . esc_html__('No result found in that range, please reselect and try again!', 'export-all-urls') . '</div>';
            return;
        }

        $labels = $this->fields->labels_for($options['export_fields'], true);
        $this->exporter->render_html_table($labels, $rows, count($rows));
    }

    /**
     * Cheap existence check (one ID, no caches) so we can fail before sending
     * download headers when nothing matches.
     */
    public function has_any($options)
    {
        $scope = isset($options['lang_scope']) ? $options['lang_scope'] : 'all';
        $this->multilingual->begin_scope($scope);

        $args = EAU_Query::build($options);
        $args['posts_per_page'] = 1;
        $args['fields'] = 'ids';
        $args['no_found_rows'] = true;
        $args['update_post_term_cache'] = false;
        $args['update_post_meta_cache'] = false;
        $args['offset'] = ($options['offset'] === 'all' || $options['offset'] === '') ? 0 : (int) $options['offset'];

        $lang = $this->multilingual->query_lang($scope);
        if (null !== $lang) {
            $args['lang'] = $lang;
        }

        $query = new \WP_Query($args);
        $has = $query->have_posts();
        wp_reset_postdata();

        $this->multilingual->end_scope();

        return $has;
    }

    /**
     * Whether any selected field needs taxonomy terms.
     */
    private function needs_terms($options)
    {
        foreach (array('categories', 'category_urls', 'tags', 'tag_urls') as $field) {
            if (in_array($field, $options['export_fields'], true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Whether any selected field needs post meta (the featured image lives in meta).
     */
    private function needs_meta($options)
    {
        return in_array('featured_image', $options['export_fields'], true);
    }

    /**
     * Precompute the per-post data shared by the field callbacks. Taxonomy terms
     * are only fetched when a taxonomy field is selected.
     */
    private function build_context($post_id, $need_terms)
    {
        $terms = array();
        if ($need_terms) {
            $post_type = get_post_type($post_id);
            foreach (get_object_taxonomies($post_type) as $taxonomy) {
                if (strpos($taxonomy, 'cat') !== false || strpos($taxonomy, 'tag') !== false) {
                    $found = get_the_terms($post_id, $taxonomy);
                    if ($found && !is_wp_error($found)) {
                        $terms[$taxonomy] = $found;
                    }
                }
            }
        }

        return array('terms' => $terms, 'ml' => $this->multilingual);
    }
}
