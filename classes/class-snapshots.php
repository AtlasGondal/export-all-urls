<?php

namespace Export_All_URLs;

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'constants.php';

// This whole class is the custom-table data layer. Every query targets the
// plugin's own tables, the table identifiers come from internal constants (never
// user input), and all values are bound via $wpdb->prepare(). The direct-DB and
// caching sniffs are therefore intentionally suppressed for the file.
// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, PluginCheck.Security.DirectDB

/**
 * Stores and retrieves site "snapshots" — a lightweight fingerprint of every
 * post/page/CPT (id, type, status, author, slug, url, modified date, and a
 * SHA-256 hash of content+title+status). Comparing two snapshots reveals what
 * was added, removed or changed — useful for spotting unauthorized/injected
 * content.
 *
 * Only hashes are stored (never full content), so the tables stay small.
 */
class EAU_Snapshots
{
    private function snapshots_table()
    {
        global $wpdb;
        return $wpdb->prefix . Constants::SNAPSHOTS_TABLE;
    }

    private function items_table()
    {
        global $wpdb;
        return $wpdb->prefix . Constants::SNAPSHOT_ITEMS_TABLE;
    }

    /* ----------------------------- schema ------------------------------- */

    /**
     * Create/upgrade the custom tables. Safe to call repeatedly (dbDelta).
     */
    public static function create_tables()
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $snapshots = $wpdb->prefix . Constants::SNAPSHOTS_TABLE;
        $items = $wpdb->prefix . Constants::SNAPSHOT_ITEMS_TABLE;

        $sql_snapshots = "CREATE TABLE $snapshots (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL,
            label varchar(191) NOT NULL DEFAULT '',
            post_count int(10) unsigned NOT NULL DEFAULT 0,
            fingerprint varchar(8) NOT NULL DEFAULT '1',
            PRIMARY KEY  (id),
            KEY created_at (created_at)
        ) $charset_collate;";

        $sql_items = "CREATE TABLE $items (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            snapshot_id bigint(20) unsigned NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            post_type varchar(20) NOT NULL DEFAULT '',
            post_status varchar(20) NOT NULL DEFAULT '',
            author_id bigint(20) unsigned NOT NULL DEFAULT 0,
            slug varchar(200) NOT NULL DEFAULT '',
            url text,
            title text,
            modified_gmt varchar(20) NOT NULL DEFAULT '',
            content_hash char(64) NOT NULL DEFAULT '',
            tax_hash char(64) NOT NULL DEFAULT '',
            meta_hash char(64) NOT NULL DEFAULT '',
            featured_id bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY snapshot_id (snapshot_id),
            KEY snapshot_post (snapshot_id,post_id)
        ) $charset_collate;";

        dbDelta($sql_snapshots);
        dbDelta($sql_items);

        update_option(Constants::DB_VERSION_OPTION, Constants::DB_VERSION);
    }

    /**
     * Run the schema installer when the stored DB version is out of date.
     */
    public static function maybe_upgrade()
    {
        if (get_option(Constants::DB_VERSION_OPTION) !== Constants::DB_VERSION) {
            self::create_tables();
        }
    }

    /**
     * Drop the custom tables and the version option (used on uninstall).
     */
    public static function drop_tables()
    {
        global $wpdb;
        $snapshots = $wpdb->prefix . Constants::SNAPSHOTS_TABLE;
        $items = $wpdb->prefix . Constants::SNAPSHOT_ITEMS_TABLE;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("DROP TABLE IF EXISTS $items");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("DROP TABLE IF EXISTS $snapshots");
        delete_option(Constants::DB_VERSION_OPTION);
    }

    /* --------------------------- snapshots ------------------------------ */

    /**
     * Capture a new snapshot of the whole site.
     *
     * @param string $label Optional human label.
     * @return int New snapshot id, or 0 on failure.
     */
    public function create($label)
    {
        global $wpdb;
        self::maybe_upgrade();

        $inserted = $wpdb->insert(
            $this->snapshots_table(),
            array(
                'created_at'  => current_time('mysql', true),
                'label'       => $label,
                'post_count'  => 0,
                'fingerprint' => Constants::SNAPSHOT_FINGERPRINT,
            ),
            array('%s', '%s', '%d', '%s')
        );
        if (!$inserted) {
            return 0;
        }
        $snapshot_id = (int) $wpdb->insert_id;

        $buffer = array();
        $total = 0;
        $this->walk_posts(function ($item) use (&$buffer, &$total, $snapshot_id) {
            $buffer[] = $item;
            $total++;
            if (count($buffer) >= Constants::SNAPSHOT_INSERT_CHUNK) {
                $this->insert_items($snapshot_id, $buffer);
                $buffer = array();
            }
        });
        if (!empty($buffer)) {
            $this->insert_items($snapshot_id, $buffer);
        }

        $wpdb->update(
            $this->snapshots_table(),
            array('post_count' => $total),
            array('id' => $snapshot_id),
            array('%d'),
            array('%d')
        );

        return $snapshot_id;
    }

    /**
     * Build the current live set of items, keyed by post id (not stored).
     * Used for "compare latest snapshot vs the live site".
     *
     * @return array post_id => item
     */
    public function build_live_set()
    {
        $set = array();
        $this->walk_posts(function ($item) use (&$set) {
            $set[$item['post_id']] = $item;
        });
        return $set;
    }

    /**
     * All snapshots, newest first.
     *
     * @return array Array of row objects.
     */
    public function get_all()
    {
        global $wpdb;
        $table = $this->snapshots_table();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
    }

    /**
     * One snapshot row, or null.
     */
    public function get($id)
    {
        global $wpdb;
        $table = $this->snapshots_table();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Delete a snapshot and its items.
     */
    public function delete($id)
    {
        global $wpdb;
        $id = (int) $id;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete($this->items_table(), array('snapshot_id' => $id), array('%d'));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete($this->snapshots_table(), array('id' => $id), array('%d'));
    }

    /**
     * A snapshot's items, keyed by post id.
     *
     * @return array post_id => item (associative array)
     */
    public function get_items($snapshot_id)
    {
        global $wpdb;
        $table = $this->items_table();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE snapshot_id = %d", (int) $snapshot_id), ARRAY_A);

        $set = array();
        if ($rows) {
            foreach ($rows as $row) {
                $set[(int) $row['post_id']] = $row;
            }
        }
        return $set;
    }

    /* ----------------------------- internals ---------------------------- */

    /**
     * Iterate every public post (all languages, real statuses) in batches and
     * invoke $callback($item) for each.
     */
    private function walk_posts($callback)
    {
        global $wpdb;

        $post_types = array_values(array_diff(array_keys(get_post_types(array('public' => true))), array('attachment')));
        if (empty($post_types)) {
            return;
        }
        $statuses = array('publish', 'future', 'draft', 'pending', 'private');

        $type_ph = implode(',', array_fill(0, count($post_types), '%s'));
        $status_ph = implode(',', array_fill(0, count($statuses), '%s'));

        $batch = Constants::BATCH_SIZE;
        $last_id = 0;

        while (true) {
            // Keyset pagination (WHERE ID > last) — an index seek per batch, unlike
            // OFFSET which re-scans all skipped rows and degrades on large tables.
            $params = array_merge($post_types, $statuses, array($last_id, $batch));
            $id_sql = "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ($type_ph) AND post_status IN ($status_ph) AND ID > %d ORDER BY ID ASC LIMIT %d";
            $ids = $wpdb->get_col($wpdb->prepare($id_sql, $params));
            if (empty($ids)) {
                break;
            }
            $ids = array_map('intval', $ids);

            // Load this batch through WP_Query so post, term and meta caches are
            // primed in bulk — build_item()'s get_the_terms()/get_post_meta()/
            // get_permalink() then hit cache instead of querying per post.
            $query = new \WP_Query(array(
                'post_type'              => $post_types,
                'post_status'            => $statuses,
                'post__in'               => $ids,
                'orderby'                => 'post__in',
                'posts_per_page'         => count($ids),
                'no_found_rows'          => true,
                'update_post_term_cache' => true,
                'update_post_meta_cache' => true,
                // Bypass WPML/Polylang language filtering so the snapshot always
                // captures every post of every type in every language.
                // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- deliberate: an integrity snapshot must include every language, which means bypassing the multilingual plugins' query filters.
                'suppress_filters'       => true,
            ));

            foreach ($query->posts as $post) {
                call_user_func($callback, $this->build_item($post));
            }
            wp_reset_postdata();

            $last_id = (int) end($ids);
            if (count($ids) < $batch) {
                break;
            }
        }
    }

    /**
     * Build a fingerprint item from a post object.
     *
     * IMPORTANT: the *_hash fields below ARE the snapshot fingerprint. If you change
     * how any of them is computed (content_hash, taxonomy_hash, meta_hash, featured_id),
     * existing snapshots become incomparable with new captures and would report
     * spurious "modified" rows. When you make such a change you MUST bump
     * Constants::SNAPSHOT_FINGERPRINT — the compare screen then warns the user to take a
     * fresh snapshot. See ARCHITECTURE.md ("Maintaining the snapshot feature").
     */
    private function build_item($post)
    {
        $modified = (string) $post->post_modified_gmt;
        if ('0000' === substr($modified, 0, 4)) {
            $modified = '';
        }

        return array(
            'post_id'      => (int) $post->ID,
            'post_type'    => $post->post_type,
            'post_status'  => $post->post_status,
            'author_id'    => (int) $post->post_author,
            'slug'         => $post->post_name,
            'url'          => get_permalink($post),
            'title'        => $post->post_title,
            'modified_gmt' => $modified,
            'content_hash' => hash('sha256', $post->post_content . '|' . $post->post_title . '|' . $post->post_status),
            'tax_hash'     => $this->taxonomy_hash($post),
            'meta_hash'    => $this->meta_hash($post->ID),
            'featured_id'  => (int) get_post_thumbnail_id($post->ID),
        );
    }

    /**
     * Stable hash of every term (across all taxonomies) attached to the post.
     */
    private function taxonomy_hash($post)
    {
        $parts = array();
        foreach (get_object_taxonomies($post->post_type) as $taxonomy) {
            $terms = get_the_terms($post->ID, $taxonomy);
            if ($terms && !is_wp_error($terms)) {
                $ids = array();
                foreach ($terms as $term) {
                    $ids[] = (int) $term->term_id;
                }
                sort($ids);
                $parts[] = $taxonomy . ':' . implode(',', $ids);
            }
        }
        sort($parts);
        return empty($parts) ? '' : hash('sha256', implode('|', $parts));
    }

    /**
     * Stable hash of the post's user custom fields.
     *
     * WordPress "protected" meta (keys starting with "_") is skipped: that is where
     * plugins and the editor keep internal, auto-changing data — page-builder CSS
     * caches (e.g. _elementor_css), SEO scores, edit locks, view counters, the
     * featured-image id, etc. Hashing those produced false "custom fields changed"
     * reports. User-defined custom fields (and ACF values) are not prefixed, so they
     * are still tracked.
     */
    private function meta_hash($post_id)
    {
        $meta = get_post_meta($post_id);
        $parts = array();
        if (is_array($meta)) {
            foreach ($meta as $key => $values) {
                if ('' === $key || '_' === $key[0]) {
                    continue;
                }
                $parts[] = $key . '=' . implode(',', array_map('strval', (array) $values));
            }
        }
        sort($parts);
        return empty($parts) ? '' : hash('sha256', implode('|', $parts));
    }

    /**
     * Insert a chunk of items with a single multi-row INSERT.
     */
    private function insert_items($snapshot_id, $items)
    {
        global $wpdb;
        if (empty($items)) {
            return;
        }

        $table = $this->items_table();
        $placeholders = array();
        $values = array();

        foreach ($items as $it) {
            $placeholders[] = '(%d,%d,%s,%s,%d,%s,%s,%s,%s,%s,%s,%s,%d)';
            $values[] = $snapshot_id;
            $values[] = $it['post_id'];
            $values[] = $it['post_type'];
            $values[] = $it['post_status'];
            $values[] = $it['author_id'];
            $values[] = $it['slug'];
            $values[] = (string) $it['url'];
            $values[] = $it['title'];
            $values[] = $it['modified_gmt'];
            $values[] = $it['content_hash'];
            $values[] = $it['tax_hash'];
            $values[] = $it['meta_hash'];
            $values[] = $it['featured_id'];
        }

        $sql = "INSERT INTO $table (snapshot_id, post_id, post_type, post_status, author_id, slug, url, title, modified_gmt, content_hash, tax_hash, meta_hash, featured_id) VALUES "
            . implode(',', $placeholders);

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- values are bound via prepare(); only the internal table name is interpolated.
        $wpdb->query($wpdb->prepare($sql, $values));
    }
}
