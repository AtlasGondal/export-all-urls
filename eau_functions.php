<?php

/**
 * Created by PhpStorm.
 * User: Atlas_Gondal
 * Date: 4/9/2016
 * Time: 9:01 AM
 */

namespace Export_All_URLs;

defined('ABSPATH') || exit;

require_once(plugin_dir_path(__FILE__) . 'classes/constants.php');

class EAU_Functions
{
    public function Eau_Export_fields()
    {
        return  array(
            'p_id'              => __('Post ID', Constants::PLUGIN_TEXT_DOMAIN),
            'title'             => __('Title', Constants::PLUGIN_TEXT_DOMAIN),
            'url'               => __('URL', Constants::PLUGIN_TEXT_DOMAIN),
            'categories'        => __('Categories', Constants::PLUGIN_TEXT_DOMAIN),
            'category_urls'   => __('Category URLs', Constants::PLUGIN_TEXT_DOMAIN),
            'tags'              => __('Tags', Constants::PLUGIN_TEXT_DOMAIN),
            'tag_urls'         => __('Tag URLs', Constants::PLUGIN_TEXT_DOMAIN),
            'author'            => __('Author', Constants::PLUGIN_TEXT_DOMAIN),
            'p_date'            => __('Published Date', Constants::PLUGIN_TEXT_DOMAIN),
            'm_date'            => __('Modified Date', Constants::PLUGIN_TEXT_DOMAIN),
            'status'            => __('Status', Constants::PLUGIN_TEXT_DOMAIN),
        );
    }

    /**
     * Returns an array of field labels for the selected fields.
     *
     * @param array $selected_fields An array of selected fields.
     * @param bool $hash Optional. Whether to return the field labels as keys in the array. Default false.
     * @return array An array of field labels for the selected fields.
     */
    public function eau_get_field_labels($selected_fields, $hash = false)
    {
        $all_fields = self::Eau_Export_fields();
        $extracted_fields = $hash ? array('#') : array();

        foreach ($selected_fields as $key) {
            if (array_key_exists($key, $all_fields)) {
                $extracted_fields[] = $all_fields[$key];
            }
        }

        return $extracted_fields;
    }

    public function eau_extract_relative_url($url)
    {
        return preg_replace('/^(http)?s?:?\/\/[^\/]*(\/?.*)$/i', '$2', '' . $url);
    }

    public function eau_is_checked($name, $value)
    {
        foreach ($name as $data) {
            if ($data == $value) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param $selected_post_type
     * @param $post_status
     * @param $post_author
     * @param exclude_domain
     * @param $post_per_page
     * @param $offset
     * @param $export_type
     * @param $export_fields
     * @param $csv_name
     * @param $posts_from
     * @param $posts_upto
     */
    public function eau_generate_output($selected_post_type, $post_status, $post_author, $exclude_domain, $post_per_page, $offset, $export_type, $export_fields, $csv_name, $posts_from, $posts_upto)
    {

        $data_array = array();
        $html_row = '';
        $counter = 0;

        if ($post_author == "all") {
            $post_author = "";
        }

        if ($post_per_page == "all" && $offset == "all") {
            $post_per_page = -1;
            $offset = "";
        }

        if (in_array("all", $post_status)) {
            $post_status = array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'trash');
        }
        if (in_array("draft", $post_status)) {
            $post_status[] = 'auto-draft';
        }

        $posts_query = new \WP_Query(array(
            'post_type'         => $selected_post_type,
            'post_status'       => $post_status,
            'author'            => $post_author,
            'posts_per_page'    => $post_per_page,
            'offset'            => $offset,
            'orderby'           => 'ID',
            'order'             => 'ASC',
            'date_query'        => array(
                array(
                    'after' => $posts_from,
                    'before' => $posts_upto,
                    'inclusive' => true,
                ),
            )
        ));

        if (!$posts_query->have_posts()) {
            echo "<div class='notice notice-error' style='width: 93%'>" . __("no result found in that range, please <strong>reselect and try again</strong>!", Constants::PLUGIN_TEXT_DOMAIN) . "</div>";
            return;
        }

        $total_results = $posts_query->found_posts;
        $counter = 1;

        while ($posts_query->have_posts()) {
            $posts_query->the_post();
            $post_id = get_the_ID();
            $post_type = get_post_type($post_id);
            $taxonomies = get_object_taxonomies($post_type);

            $post_terms = [];
            foreach ($taxonomies as $taxonomy) {
                if (strpos($taxonomy, 'cat') !== false || strpos($taxonomy, 'tag') !== false) {
                    $terms = get_the_terms($post_id, $taxonomy);
                    if ($terms && !is_wp_error($terms)) {
                        $post_terms[$taxonomy] = $terms; 
                    }
                }
            }

            $row = array();
            foreach ($export_fields as $field) {
                switch ($field) {
                    case 'p_id':
                        $row[] = $post_id;
                        break;
                    case 'title':
                        $row[] = htmlspecialchars_decode(get_the_title());
                        break;
                    case 'url':
                        $row[] = esc_url($exclude_domain == 'yes' ? self::eau_extract_relative_url(get_permalink()) : get_permalink());
                        break;
                    case 'categories':
                        $categories = array();
                        foreach ($post_terms as $taxonomy => $terms) {
                            if (strpos($taxonomy, 'cat') !== false) {
                                foreach ($terms as $term) {
                                    $categories[] = esc_html($term->name);
                                }
                            }
                        }
                        $row[] = implode(', ', $categories);
                        break;
                    case 'category_urls':
                        $categories_urls = array();
                        foreach ($post_terms as $taxonomy => $terms) {
                            if (strpos($taxonomy, 'cat') !== false) {
                                foreach ($terms as $term) {
                                    $category_url = get_term_link($term);
                                    if (!is_wp_error($category_url)) {
                                        $categories_urls[] = esc_url($category_url);
                                    }
                                }
                            }
                        }
                        $row[] = implode(', ', $categories_urls);
                        break;
                    case 'tags':
                        $tags = array();
                        foreach ($post_terms as $taxonomy => $terms) {
                            if (strpos($taxonomy, 'tag') !== false) {
                                foreach ($terms as $term) {
                                    $tags[] = esc_html($term->name);
                                }
                            }
                        }
                        $row[] = implode(', ', $tags);
                        break;
                    case 'tag_urls':
                        $tags_urls = array();
                        foreach ($post_terms as $taxonomy => $terms) {
                            if (strpos($taxonomy, 'tag') !== false) {
                                foreach ($terms as $term) {
                                    $tags_urls[] = esc_url(get_term_link($term));
                                }
                            }
                        }
                        $row[] = implode(', ', $tags_urls);
                        break;
                    case 'author':
                        $row[] = htmlspecialchars_decode(get_the_author());
                        break;
                    case 'p_date':
                        $row[] = get_the_date('Y-m-d H:i:s', $post_id);
                        break;
                    case 'm_date':
                        $row[] = get_the_modified_date('Y-m-d H:i:s', $post_id);
                        break;
                    case 'status':
                        $row[] = ucfirst(get_post_status($post_id));
                        break;
                }
            }

            if ($export_type == 'text') {
                $data_array[] = $row;
            } else {

                $html_row .= '<tr>';
                $html_row .= '<td>' . $counter . '</td>';
                foreach ($row as $cell) {
                    $html_row .= '<td>' . esc_html($cell) . '</td>';
                }
                $html_row .= '</tr>';
                $counter++;
            }
        }
        wp_reset_postdata();

        self::eau_export_data($data_array, $html_row, $total_results, $export_fields, $export_type, $csv_name);
    }


    public function eau_export_data($data_array, $row, $total_results, $export_fields, $export_type, $csv_name)
    {

        $file_path = wp_upload_dir();

        switch ($export_type) {

            case "text":

                $file = $file_path['path'] . "/" . $csv_name . '.CSV';
                $myfile = @fopen($file, "w") or die("<div class='error' style='width: 95.3%; margin-left: 2px;'>" . __("Unable to create a file on your server! (either invalid name supplied or permission issue)", Constants::PLUGIN_TEXT_DOMAIN) . "</div>");
                fprintf($myfile, "\xEF\xBB\xBF");

                $csv_url = esc_url($file_path['url'] . "/" . $csv_name . ".CSV");

                $field_labels = self::eau_get_field_labels($export_fields);

                fputcsv($myfile, $field_labels);

                foreach ($data_array as $data_row) {
                    fputcsv($myfile, $data_row);
                }

                fclose($myfile);

                echo "<div class='updated' style='width: 97%'>Data exported successfully! <a href='" . $csv_url . "' target='_blank'><strong>" . __('Click here', Constants::PLUGIN_TEXT_DOMAIN) . "</strong></a> " . __('to Download', Constants::PLUGIN_TEXT_DOMAIN) . ".</div>";
                echo "<div class='notice notice-warning' style='width: 97%'>" . __('Once you have downloaded the file, it is recommended to delete file from the server, for security reasons.', Constants::PLUGIN_TEXT_DOMAIN) . " <a href='" . wp_nonce_url(admin_url('tools.php?page=extract-all-urls-settings&del=y&f=') . base64_encode($file)) . "' ><strong>" . __('Click Here', Constants::PLUGIN_TEXT_DOMAIN) . "</strong></a> " . __('to delete the file. And don\'t worry, you can always regenerate anytime. :)', Constants::PLUGIN_TEXT_DOMAIN) . "</div>";
                echo "<div class='notice notice-info' style='width: 97%'><strong>" . __('Total', Constants::PLUGIN_TEXT_DOMAIN) . "</strong> " . __('number of links', Constants::PLUGIN_TEXT_DOMAIN) . ": <strong>" . esc_html($total_results,) . "</strong>.</div>";

                break;

            case "here":

                echo "<h1 align='center' style='padding: 10px 0;'><strong>" . __('Below is a list of Exported Data:', Constants::PLUGIN_TEXT_DOMAIN) . "</strong></h1>";
                echo "<h2 align='center' style='font-weight: normal;'>" . __('Total number of links', Constants::PLUGIN_TEXT_DOMAIN) . ": <strong>" . esc_html($total_results) . "</strong>.</h2>";

                echo '<table id="outputData" class="wp-list-table widefat fixed striped">';
                echo '<thead><tr>';

                $field_labels = self::eau_get_field_labels($export_fields, $hash = true);

                foreach ($field_labels as $label) {
                    echo '<th>' . ucfirst($label) . '</th>';
                }

                echo '</tr></thead><tbody>';
                echo $row;
                echo '</tbody></table>';

                break;

            default:

                echo __('Sorry, you missed export type, Please', Constants::PLUGIN_TEXT_DOMAIN) . " <strong>" . __('Select Export Type', Constants::PLUGIN_TEXT_DOMAIN) . "</strong> " . __('and try again!', Constants::PLUGIN_TEXT_DOMAIN) . " :)";
                break;
        }
    }
}

new EAU_Functions();
