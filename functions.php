<?php
/**
 * Created by PhpStorm.
 * User: Atlas_Gondal
 * Date: 4/9/2016
 * Time: 9:01 AM
 */

function eau_get_selected_post_type($post_type, $custom_posts_names)
{

    switch ($post_type) {

        case "any":

            $type = "any";
            break;

        case "page":

            $type = "page";
            break;

        case "post":

            $type = "post";
            break;

        default:

            for ($i = 0; $i < count($custom_posts_names); $i++) {

                if ($post_type == $custom_posts_names[$i]) {

                    $type = $custom_posts_names[$i];

                }

            }

    }

    return $type;


}

function eau_extract_relative_url ($url)
{
    return preg_replace ('/^(http)?s?:?\/\/[^\/]*(\/?.*)$/i', '$2', '' . $url);
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
 * @param $post_per_page
 * @param $offset
 * @param $export_type
 * @param $additional_data
 * @param $csv_name
 * @param $posts_from
 * @param $posts_upto
 */
function eau_generate_output($selected_post_type, $post_status, $post_author, $remove_woo_attributes, $exclude_domain, $post_per_page, $offset, $export_type, $additional_data, $csv_name, $posts_from, $posts_upto)
{

    $html = array();
    $counter = 0;

    if ($export_type == "here") {
        $line_break = "<br/>";
    } else {
        $line_break = "";
    }

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
        'post_type' => $selected_post_type,
        'post_status' => $post_status,
        'author' => $post_author,
        'posts_per_page' => $post_per_page,
        'offset' => $offset,
        'orderby' => 'title',
        'order' => 'ASC',
        'date_query' => array(
            array(
                'after' => $posts_from,
                'before' => $posts_upto,
                'inclusive' => true,
            ),
        )
    ));

    if (!$posts_query->have_posts()) {
        echo __( 'no result found in that range, please <strong>reselect and try again</strong>!', 'export-all-urls' );
        return;
    }

    if (eau_is_checked($additional_data, 'postIDs')) {

        while ($posts_query->have_posts()):

            $html['post_id'][$counter] = (isset($html['post_id'][$counter]) ? "" : null);

            $posts_query->the_post();
            $html['post_id'][$counter] .= get_the_ID() . $line_break;
            $counter++;

        endwhile;

        $counter = 0;

    }

    if (eau_is_checked($additional_data, 'url')) {

        while ($posts_query->have_posts()):

            $html['url'][$counter] = (isset($html['url'][$counter]) ? "" : null);

            $posts_query->the_post();
            $html['url'][$counter] .= esc_url( $exclude_domain == 'yes' ? eau_extract_relative_url(get_permalink()) : get_permalink() ) . $line_break;
            $counter++;

        endwhile;

        $counter = 0;

    }

    if (eau_is_checked($additional_data, 'title')) {

        while ($posts_query->have_posts()):

            $html['title'][$counter] = (isset($html['title'][$counter]) ? "" : null);

            $posts_query->the_post();
            $html['title'][$counter] .= esc_html( get_the_title() ) . $line_break;
            $counter++;

        endwhile;

        $counter = 0;

    }

    if (eau_is_checked($additional_data, 'category')) {

        while ($posts_query->have_posts()):

            $html['category'][$counter] = (isset($html['category'][$counter]) ? "" : null);
            $html['taxonomy'][$counter] = (isset($html['taxonomy'][$counter]) ? "" : null);

            $categories = '';
            $taxonomies_list = '';
            $posts_query->the_post();
            $cats = get_the_category();
            $post_type = get_post_type(get_the_ID());
            $taxonomies = get_object_taxonomies($post_type);
            $taxonomy_names = wp_get_object_terms(get_the_ID(), $taxonomies, array("fields" => "names"));
            if (!empty($cats)) :
                foreach ($cats as $index => $cat) :
                    $categories .= !empty($cat) ? $index == 0 ? $cat->name : ", " . $cat->name : '';
                endforeach;
            endif;

            if ($remove_woo_attributes == 'yes' && $post_type == 'product') {
                $terms = get_the_terms( get_the_ID(), 'product_cat' );
                if(isset($terms) && !is_wp_error($terms)) {
                    foreach ($terms as $index => $term) {
                        $taxonomies_list .= !empty($term->name) ? $index == 0 ? $term->name : ", " . $term->name : '';
                    }
                }
            }else{
                if (!empty($taxonomy_names)) {
                    foreach ($taxonomy_names as $index => $tax_name) :
                        $taxonomies_list .= !empty($tax_name) ? $index == 0 ? $tax_name : ", " . $tax_name : '';
                    endforeach;
                }
            }

            $html['category'][$counter] .= !empty($categories) ? $categories . $line_break : '';
            $html['taxonomy'][$counter] .= !empty($taxonomies_list) ? $taxonomies_list . $line_break : '';

            $counter++;

        endwhile;

        $counter = 0;

    }
    eau_export_data($html, $export_type, $csv_name);

    wp_reset_postdata();
}

function eau_export_data($urls, $export_type, $csv_name)
{

    $file_path = wp_upload_dir();

    $count = 0;
    foreach ($urls as $item) {
        $count = count($item);
    }


    switch ($export_type) {

        case "text":

            $data = '';
            $headers = array();

            $file = $file_path['path'] . "/" . $csv_name . '.CSV';
            $myfile = @fopen($file, "w") or die("<div class='error' style='width: 95.3%; margin-left: 2px;'>" . esc_html__( 'Unable to create a file on your server! (either invalid name supplied or permission issue)', 'export-all-urls' ) . "</div>");
            fprintf($myfile, "\xEF\xBB\xBF");

            $csv_url = esc_url($file_path['url'] . "/" . $csv_name . ".CSV");

            $headers[] = __( 'Post ID', 'export-all-urls' );
            $headers[] = __( 'Title', 'export-all-urls' );
            $headers[] = __( 'URLs', 'export-all-urls' );
            $headers[] = __( 'Categories', 'export-all-urls' );

            fputcsv($myfile, $headers);

            for ($i = 0; $i < $count; $i++) {
                $data = array(
                    isset($urls['post_id']) ? $urls['post_id'][$i] : "",
                    isset($urls['title']) ? htmlspecialchars_decode($urls['title'][$i]) : "",
                    isset($urls['url']) ? $urls['url'][$i] : "",
                    isset($urls['category']) ? !empty($urls['category'][$i]) || !empty($urls['taxonomy'][$i]) ? $urls['category'][$i] . $urls['taxonomy'][$i] : "" : ""
                );

                fputcsv($myfile, $data);
            }

            fclose($myfile);

            echo "<div class='updated' style='width: 97%'>" . esc_html__( 'Data exported successfully! ', 'export-all-urls' ) . "<a href='" . $csv_url . "' target='_blank'><strong>" . esc_html__( 'Click here', 'export-all-urls' ) . "</strong></a>" . esc_html__( ' to Download.', 'export-all-urls' ) . "</div>";
            echo "<div class='notice notice-warning' style='width: 97%'>" . esc_html__( 'Once you have downloaded the file, it is recommended to delete file from the server, for security reasons. ', 'export-all-urls' ) . "<a href='".wp_nonce_url(admin_url('tools.php?page=extract-all-urls-settings&del=y&f=').base64_encode($file))."' ><strong>" . esc_html__( 'Click Here', 'export-all-urls' ) . "</strong></a>" . esc_html__( ' to delete the file. And don\'t worry, you can always regenerate anytime. :)', 'export-all-urls' ) . "</div>";
            echo "<div class='notice notice-info' style='width: 97%'><strong>" . esc_html__( 'Total', 'export-all-urls' ) . "</strong>" . esc_html__( ' number of links: ', 'export-all-urls' ) . "<strong>".esc_html($count)."</strong></div>";

            break;

        case "here":

            echo "<h1 align='center' style='padding: 10px 0;'><strong>" . esc_html__( 'Below is a list of Exported Data:', 'export-all-urls' ) . "</strong></h1>";
            echo "<h2 align='center' style='font-weight: normal;'>" . esc_html__( 'Total number of links: ', 'export-all-urls' ) . "<strong>".esc_html($count)."</strong></h2>";
            echo "<table class='form-table' id='outputData'>";
            echo "<tr><th>#</th>";
            echo isset($urls['post_id']) ? "<th id='postID'>" . esc_html__( 'Post ID', 'export-all-urls' ) . "</th>" : null;
            echo isset($urls['title']) ? "<th id='postTitle'>" . esc_html__( 'Title', 'export-all-urls' ) . "</th>" : null;
            echo isset($urls['url']) ? "<th id='postURL'>" . esc_html__( 'URLs', 'export-all-urls' ) . "</th>" : null;
            echo isset($urls['category']) ? "<th id='postCategories'>" . esc_html__( 'Categories', 'export-all-urls' ) . "</th>" : null;

            echo "</tr>";

            for ($i = 0; $i < $count; $i++) {

                $id = $i + 1;
                echo "<tr><td>" . $id . "</td>";
                echo isset($urls['post_id']) ? "<td>".$urls['post_id'][$i]."</td>" : null;
                echo isset($urls['title']) ? "<td>" . $urls['title'][$i] . "</td>" : null;
                echo isset($urls['url']) ? "<td>" . $urls['url'][$i] . "</td>" : null;
                echo isset($urls['category']) ?  "<td>".$urls['category'][$i] . $urls['taxonomy'][$i] . "</td>" : null;

                echo "</tr>";
            }

            echo "</table>";

            break;

        default:

            echo "Sorry, you missed export type, Please <strong>Select Export Type</strong> and try again! :)";
            break;


    }


}
