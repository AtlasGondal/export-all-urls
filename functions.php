<?php

/**
 * Created by PhpStorm.
 * User: Atlas_Gondal
 * Date: 4/9/2016
 * Time: 9:01 AM
 */

function eau_export_fields()
{
    return  array(
        'p_id'          => 'Post ID',
        'title'         => 'Title',
        'url'           => 'URL',
        'categories'    => 'Categories',
        'tags'          => 'Tags',
        'author'        => 'Author',
        'p_date'        => 'Published Date',
        'm_date'        => 'Modified Date',
    );
}

function eau_get_field_labels($selected_fields, $hash = false)
{
    $all_fields = eau_export_fields();
    $extracted_fields = $hash ? array('#') : array();

    foreach ($selected_fields as $key) {
        if (array_key_exists($key, $all_fields)) {
            $extracted_fields[] = $all_fields[$key];
        }
    }

    return $extracted_fields;
}

function eau_extract_relative_url($url)
{
    return preg_replace('/^(http)?s?:?\/\/[^\/]*(\/?.*)$/i', '$2', '' . $url);
}

function eau_is_checked($name, $value)
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
function eau_generate_output($selected_post_type, $post_status, $post_author, $exclude_domain, $post_per_page, $offset, $export_type, $export_fields, $csv_name, $posts_from, $posts_upto)
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

    switch ($post_status) {
        case "all":
            $post_status = array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'trash');
            break;
        case 'publish':
            $post_status = 'publish';
            break;
        case 'pending':
            $post_status = 'pending';
            break;
        case 'draft':
            $post_status = 'draft';
            break;
        case 'future':
            $post_status = 'future';
            break;
        case 'private':
            $post_status = 'private';
            break;
        case 'trash':
            $post_status = 'trash';
            break;
        default:
            $post_status = 'publish';
            break;
    }

    $posts_query = new WP_Query(array(
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
        echo "<div class='notice notice-error' style='width: 93%'>no result found in that range, please <strong>reselect and try again</strong>!</div>";
        return;
    }

    $total_results = $posts_query->found_posts;
    $counter = 1;

    while ($posts_query->have_posts()) {
        $posts_query->the_post();
        $post_id = get_the_ID();
        $post_type = get_post_type($post_id);
        $taxonomies = get_object_taxonomies($post_type);

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
                    $row[] = esc_url($exclude_domain == 'yes' ? eau_extract_relative_url(get_permalink()) : get_permalink());
                    break;
                case 'categories':
                    $categories = array();
                    foreach ($taxonomies as $taxonomy) {
                        if (strpos($taxonomy, 'cat') !== false) {
                            $categories[] = strip_tags(get_the_term_list($post_id, $taxonomy, '', ', '));
                        }
                    }
                    $row[] = implode(', ', $categories);
                    break;
                case 'tags':
                    $tags = array();
                    foreach ($taxonomies as $taxonomy) {
                        if (strpos($taxonomy, 'tag') !== false) {
                            $tags[] = strip_tags(get_the_term_list($post_id, $taxonomy, '', ', '));
                        }
                    }
                    $row[] = implode(', ', $tags);
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

    eau_export_data($data_array, $html_row, $total_results, $export_fields, $export_type, $csv_name);
}


function eau_export_data($data_array, $row, $total_results, $export_fields, $export_type, $csv_name)
{

    $file_path = wp_upload_dir();

    switch ($export_type) {

        case "text":

            $file = $file_path['path'] . "/" . $csv_name . '.CSV';
            $myfile = @fopen($file, "w") or die("<div class='error' style='width: 95.3%; margin-left: 2px;'>Unable to create a file on your server! (either invalid name supplied or permission issue)</div>");
            fprintf($myfile, "\xEF\xBB\xBF");

            $csv_url = esc_url($file_path['url'] . "/" . $csv_name . ".CSV");

            $field_labels = eau_get_field_labels($export_fields);

            fputcsv($myfile, $field_labels);

            foreach ($data_array as $data_row) {
                fputcsv($myfile, $data_row);
            }

            fclose($myfile);

            echo "<div class='updated' style='width: 97%'>Data exported successfully! <a href='" . $csv_url . "' target='_blank'><strong>Click here</strong></a> to Download.</div>";
            echo "<div class='notice notice-warning' style='width: 97%'>Once you have downloaded the file, it is recommended to delete file from the server, for security reasons. <a href='" . wp_nonce_url(admin_url('tools.php?page=extract-all-urls-settings&del=y&f=') . base64_encode($file)) . "' ><strong>Click Here</strong></a> to delete the file. And don't worry, you can always regenerate anytime. :)</div>";
            echo "<div class='notice notice-info' style='width: 97%'><strong>Total</strong> number of links: <strong>" . esc_html($total_results,) . "</strong>.</div>";

            break;

        case "here":

            echo "<h1 align='center' style='padding: 10px 0;'><strong>Below is a list of Exported Data:</strong></h1>";
            echo "<h2 align='center' style='font-weight: normal;'>Total number of links: <strong>" . esc_html($total_results) . "</strong>.</h2>";

            echo '<table id="outputData" class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';

            $field_labels = eau_get_field_labels($export_fields, $hash = true);

            foreach ($field_labels as $label) {
                echo '<th>' . ucfirst($label) . '</th>';
            }

            echo '</tr></thead><tbody>';
            echo $row;
            echo '</tbody></table>';

            break;

        default:

            echo "Sorry, you missed export type, Please <strong>Select Export Type</strong> and try again! :)";
            break;
    }
}
