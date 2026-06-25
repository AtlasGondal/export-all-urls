<?php

namespace Export_All_URLs;

defined('ABSPATH') || exit;

/**
 * Translates sanitized request options into WP_Query arguments.
 */
class EAU_Query
{
    /**
     * @param array $o Sanitized options (see EAU_Request::from_post()).
     * @return array WP_Query arguments.
     */
    public static function build($o)
    {
        $post_author   = ($o['post_author'] === 'all') ? '' : $o['post_author'];
        $post_status   = self::expand_status($o['post_status']);
        $posts_per_page = ($o['post_per_page'] === 'all') ? -1 : (int) $o['post_per_page'];
        $offset         = ($o['offset'] === 'all') ? '' : (int) $o['offset'];

        $args = array(
            'post_type'      => $o['post_type'],
            'post_status'    => $post_status,
            'author'         => $post_author,
            'posts_per_page' => $posts_per_page,
            'offset'         => $offset,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        );

        if ($o['posts_from'] !== '' && $o['posts_upto'] !== '') {
            $args['date_query'] = array(
                array(
                    'after'     => $o['posts_from'],
                    'before'    => $o['posts_upto'],
                    'inclusive' => true,
                ),
            );
        }

        return $args;
    }

    /**
     * Expand the "all" pseudo-status and include auto-draft alongside draft,
     * preserving the original behavior.
     */
    private static function expand_status($post_status)
    {
        if (in_array('all', $post_status, true)) {
            return array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'trash');
        }

        if (in_array('draft', $post_status, true) && !in_array('auto-draft', $post_status, true)) {
            $post_status[] = 'auto-draft';
        }

        return $post_status;
    }
}
