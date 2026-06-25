<?php

namespace Export_All_URLs;

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'constants.php';

/**
 * Output helpers for the three export targets: streamed CSV, streamed JSON,
 * and an in-page HTML table.
 *
 * The streaming helpers expose header senders plus per-row encoders so the
 * orchestrator can echo one row at a time (bounded memory) instead of building
 * the whole file in memory. No fopen/fwrite/fputcsv — the body is echoed.
 */
class EAU_Exporter
{
    /**
     * Neutralize spreadsheet formula injection.
     *
     * A cell beginning with = + - @ (or a leading tab/carriage return) can be
     * read as a formula by Excel / Google Sheets. Prefixing a single quote
     * forces it to be treated as text. Applied to CSV only.
     *
     * @param string $value
     * @return string
     */
    public function neutralize($value)
    {
        $value = (string) $value;
        if ($value === '') {
            return $value;
        }

        $first = substr($value, 0, 1);
        if (in_array($first, array('=', '+', '-', '@', "\t", "\r"), true)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * Send the HTTP headers for a file download.
     *
     * @param string $content_type e.g. 'text/csv'.
     * @param string $filename     Base filename (no extension).
     * @param string $ext          Extension to append.
     */
    public function send_download_headers($content_type, $filename, $ext)
    {
        $filename = sanitize_file_name($filename);
        if ($filename === '') {
            $filename = 'export-all-urls';
        }

        // On sites with display_errors on, WordPress or other plugins can emit PHP
        // notices/warnings while we query. Anything already echoed sits in
        // PHP's output buffer and would otherwise be flushed into the file,
        // breaking the JSON parser and littering the CSV. Discard every open
        // buffer right before we send headers, and stop further "doing it wrong"
        // notices from printing into the body for the rest of this request (the
        // download exits immediately afterwards, so nothing legitimate is lost).
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        // 'doing_it_wrong_trigger_error' exists since WP 6.1; on older cores the
        // filter is simply never applied, so adding it is harmless. __return_false
        // (WP 3.0+) suppresses the underlying trigger_error() call.
        add_filter('doing_it_wrong_trigger_error', '__return_false', 99);

        nocache_headers();
        header('Content-Type: ' . $content_type . '; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.' . $ext . '"');
    }

    /**
     * Encode one CSV record (RFC 4180): every field quoted, inner quotes doubled.
     *
     * @param array $fields
     * @return string
     */
    public function csv_line($fields)
    {
        $cells = array();
        foreach ($fields as $field) {
            $value = $this->neutralize($field);
            $cells[] = '"' . str_replace('"', '""', (string) $value) . '"';
        }
        return implode(',', $cells) . "\r\n";
    }

    /**
     * Encode one row as a JSON object keyed by the column labels.
     *
     * @param array $labels
     * @param array $row
     * @return string
     */
    public function json_record($labels, $row)
    {
        $record = array();
        foreach ($labels as $i => $label) {
            $record[(string) $label] = isset($row[$i]) ? $row[$i] : '';
        }
        // json_encode() (PHP 5.2+) is used directly so the plugin supports WordPress 3.6 (wp_json_encode is 4.1+).
        // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- intentional for WordPress 3.6 compatibility.
        return json_encode($record);
    }

    /**
     * Stream an already-materialized set of rows as a CSV or JSON download and exit.
     * Used for the (small, in-memory) snapshot diff export.
     *
     * @param array  $labels   Header labels.
     * @param array  $rows     Array of row arrays.
     * @param string $format   'csv' or 'json'.
     * @param string $filename Base filename (no extension).
     */
    public function stream_rows($labels, $rows, $format, $filename)
    {
        if ('json' === $format) {
            $this->send_download_headers('application/json', $filename, 'json');
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download body, not HTML.
            echo '[';
            $first = true;
            foreach ($rows as $row) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download body, not HTML.
                echo ($first ? '' : ',') . $this->json_record($labels, $row);
                $first = false;
            }
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download body, not HTML.
            echo ']';
        } else {
            $this->send_download_headers('text/csv', $filename, 'csv');
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV BOM + header row, not HTML.
            echo "\xEF\xBB\xBF" . $this->csv_line($labels);
            foreach ($rows as $row) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV download body, not HTML.
                echo $this->csv_line($row);
            }
        }
        exit;
    }

    /**
     * Render the on-page results table with client-side pagination controls.
     *
     * @param array $labels Header labels (already prefixed with the '#' column).
     * @param array $rows   Array of row arrays.
     * @param int   $total  Number of rows being shown.
     */
    public function render_html_table($labels, $rows, $total)
    {
        echo "<h1 align='center' style='padding: 10px 0;'><strong>" . esc_html__('Below is a list of Exported Data:', 'export-all-urls') . '</strong></h1>';
        echo "<h2 align='center' style='font-weight: normal;'>" . esc_html__('Total number of links', 'export-all-urls') . ': <strong>' . esc_html($total) . '</strong>.</h2>';

        // Results-per-page selector (handled in the browser by script.js).
        echo '<div class="eau-results-toolbar">';
        echo '<label class="eau-perpage-label">' . esc_html__('Results per page:', 'export-all-urls') . ' ';
        echo '<select class="eau-perpage">';
        foreach (array('100', '250', '500', '750', '1000') as $size) {
            echo '<option value="' . esc_attr($size) . '"' . selected($size, (string) Constants::DEFAULT_PER_PAGE, false) . '>' . esc_html($size) . '</option>';
        }
        echo '<option value="all">' . esc_html__('All', 'export-all-urls') . '</option>';
        echo '</select></label>';
        echo '</div>';

        echo '<table id="outputData" class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        foreach ($labels as $label) {
            echo '<th>' . esc_html(ucfirst($label)) . '</th>';
        }
        echo '</tr></thead><tbody>';

        $counter = 1;
        foreach ($rows as $row) {
            echo '<tr><td>' . (int) $counter . '</td>';
            foreach ($row as $cell) {
                echo '<td>' . esc_html($cell) . '</td>';
            }
            echo '</tr>';
            $counter++;
        }

        echo '</tbody></table>';

        // Pagination controls — wired up and shown by script.js only when needed.
        echo '<div class="eau-pagination" style="display:none">';
        echo '<button type="button" class="button eau-prev">' . esc_html__('Previous', 'export-all-urls') . '</button>';
        echo '<span class="eau-page-indicator"></span>';
        echo '<button type="button" class="button eau-next">' . esc_html__('Next', 'export-all-urls') . '</button>';
        echo '</div>';
    }
}
