<?php

namespace Export_All_URLs;

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'class-snapshots.php';

/**
 * Compares two snapshots (or a snapshot against the live site) and classifies
 * every post as Added, Removed or Modified — flagging the changes that most
 * often indicate a compromise (newly published pages, author or status flips).
 */
class EAU_Snapshot_Diff
{
    /** @var EAU_Snapshots */
    private $snapshots;

    /** @var array Cache of author id => login. */
    private $author_cache = array();

    public function __construct()
    {
        $this->snapshots = new EAU_Snapshots();
    }

    /**
     * Diff snapshot A (baseline) against snapshot B (newer).
     */
    public function between($id_a, $id_b)
    {
        return $this->diff($this->snapshots->get_items($id_a), $this->snapshots->get_items($id_b));
    }

    /**
     * Diff a snapshot against the current state of the live site.
     */
    public function against_live($id_a)
    {
        return $this->diff($this->snapshots->get_items($id_a), $this->snapshots->build_live_set());
    }

    /**
     * @return array { added[], removed[], modified[], counts{}, suspicious(int) }
     */
    private function diff($a, $b)
    {
        $added = array();
        $removed = array();
        $modified = array();

        foreach ($b as $post_id => $item) {
            if (!isset($a[$post_id])) {
                $added[] = $this->row($item, array('new'), $this->flags_added($item));
            }
        }

        foreach ($a as $post_id => $item) {
            if (!isset($b[$post_id])) {
                $removed[] = $this->row($item, array('removed'), array());
            }
        }

        foreach ($a as $post_id => $old) {
            if (!isset($b[$post_id])) {
                continue;
            }
            $new = $b[$post_id];
            $changed = $this->changed_fields($old, $new);
            if (!empty($changed)) {
                $modified[] = $this->row($new, $changed, $this->flags_modified($old, $new, $changed));
            }
        }

        $suspicious = 0;
        foreach (array($added, $modified) as $group) {
            foreach ($group as $r) {
                if ($r['suspicious']) {
                    $suspicious++;
                }
            }
        }

        return array(
            'added'      => $added,
            'removed'    => $removed,
            'modified'   => $modified,
            'counts'     => array(
                'added'    => count($added),
                'removed'  => count($removed),
                'modified' => count($modified),
            ),
            'suspicious' => $suspicious,
        );
    }

    /**
     * Which fields differ between two versions of the same post.
     */
    private function changed_fields($old, $new)
    {
        $changed = array();

        $title_changed  = ((string) $old['title'] !== (string) $new['title']);
        $status_changed = ($old['post_status'] !== $new['post_status']);
        $author_changed = ((int) $old['author_id'] !== (int) $new['author_id']);
        $slug_changed   = ($old['slug'] !== $new['slug']);

        if ($title_changed) {
            $changed[] = 'title';
        }
        if ($status_changed) {
            $changed[] = 'status';
        }
        if ($author_changed) {
            $changed[] = 'author';
        }
        if ($slug_changed) {
            $changed[] = 'slug';
        }
        // The hash covers content+title+status; if it differs while title and
        // status are unchanged, the body content itself changed.
        if ($old['content_hash'] !== $new['content_hash'] && !$title_changed && !$status_changed) {
            $changed[] = 'content';
        }
        if ((string) $old['tax_hash'] !== (string) $new['tax_hash']) {
            $changed[] = 'taxonomy';
        }
        if ((string) $old['meta_hash'] !== (string) $new['meta_hash']) {
            $changed[] = 'meta';
        }
        if ((int) $old['featured_id'] !== (int) $new['featured_id']) {
            $changed[] = 'featured_image';
        }

        return $changed;
    }

    private function flags_added($item)
    {
        return ('publish' === $item['post_status']) ? array('published') : array();
    }

    private function flags_modified($old, $new, $changed)
    {
        $flags = array();
        if (in_array('status', $changed, true) && 'publish' === $new['post_status']) {
            $flags[] = 'published';
        }
        if (in_array('author', $changed, true)) {
            $flags[] = 'author';
        }
        return $flags;
    }

    private function row($item, $changed, $flags)
    {
        return array(
            'post_id'     => (int) $item['post_id'],
            'title'       => (string) $item['title'],
            'url'         => (string) $item['url'],
            'post_type'   => $item['post_type'],
            'post_status' => $item['post_status'],
            'author'      => $this->author_login((int) $item['author_id']),
            'changed'     => $changed,
            'flags'       => $flags,
            'suspicious'  => !empty($flags),
        );
    }

    private function author_login($author_id)
    {
        if (!isset($this->author_cache[$author_id])) {
            $user = get_userdata($author_id);
            $this->author_cache[$author_id] = $user ? $user->user_login : (string) $author_id;
        }
        return $this->author_cache[$author_id];
    }

    /**
     * Flatten a diff into label + rows for CSV/JSON export.
     *
     * @return array array('labels' => [], 'rows' => [])
     */
    public function export_rows($diff)
    {
        $labels = array(
            __('Change', 'export-all-urls'),
            __('Title', 'export-all-urls'),
            __('URL', 'export-all-urls'),
            __('Post Type', 'export-all-urls'),
            __('Status', 'export-all-urls'),
            __('Author', 'export-all-urls'),
            __('Details', 'export-all-urls'),
            __('Notable', 'export-all-urls'),
        );

        $groups = array(
            'added'    => __('Added', 'export-all-urls'),
            'removed'  => __('Removed', 'export-all-urls'),
            'modified' => __('Modified', 'export-all-urls'),
        );

        $rows = array();
        foreach ($groups as $key => $change_label) {
            foreach ($diff[$key] as $r) {
                $rows[] = array(
                    $change_label,
                    $r['title'],
                    $r['url'],
                    $r['post_type'],
                    ucfirst($r['post_status']),
                    $r['author'],
                    implode(', ', $r['changed']),
                    $r['suspicious'] ? __('Yes', 'export-all-urls') : '',
                );
            }
        }

        return array('labels' => $labels, 'rows' => $rows);
    }
}
