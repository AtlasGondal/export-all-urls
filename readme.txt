=== Export All URLs ===
Contributors: Atlas_Gondal
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YWT3BFURG6SGS&source=url
Tags: export, urls, csv, json, links
Requires at least: 3.6
Tested up to: 7.0
Stable tag: 6.0
Requires PHP: 5.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Export post, page and custom post type data to CSV or JSON, and snapshot your site to detect added, removed or changed content.

== Description ==

This plugin will add a page called "Export All URLs" under Tools. You can navigate there and can extract data from your site. You can export Posts:

* ID
* Title
* URL
* Status
* Categories
* Category URLs
* Tags
* Tag URLs
* Author
* Published Date
* Modified Date
* Word Count
* Excerpt
* Featured Image URL
* Comment Count
* Slug
* Post Type
* Language (WPML / Polylang)
* Translation URLs (WPML / Polylang)

Fields are organized into collapsible groups with quick-select presets, so the picker stays tidy even with this many columns. The data can be filtered by post type, post status, date range, and author before extraction, and the plugin also provides the option to export using a specific post range. Output can be displayed in the dashboard or downloaded as a CSV or JSON file.

== When we need this plugin? ==

* To check all URLs of your website
* During a migration (and to confirm everything was moved across)
* During a security audit, or to monitor the site for unauthorized or injected content (Snapshots)
* To share all URLs with your SEO consultant
* For handling 301 redirects in htaccess


== Customizable Features ==

* Choose exactly which columns to export, grouped with quick-select presets
* Download as CSV or JSON, or display the results in a paginated table
* Filter by post type, post status, author and date range
* Export a specific post range (helpful on very large sites)
* Exclude the domain from URLs (handy when comparing results after a migration)
* Multilingual support for WPML and Polylang (export all languages or just the default)
* Custom or randomly generated download file name
* Snapshots: capture your site and detect what was added, removed or changed over time

= System requirements =

* PHP version 5.4 or higher
* WordPress version 3.6 or higher


If you encounter any bugs, please report them to me, and I will strive to resolve them as quickly as possible!

== Contact ==

For further information please send me an [email](https://AtlasGondal.com/contact-me/?utm_source=self&utm_medium=wp&utm_campaign=export-all-urls&utm_term=plugin-description).

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'
2. Search for 'Export All URLs'
3. Activate Export All URLs from your Plugins page.

= From WordPress.org =

1. Download Export All URLs.
2. Unzip plugin.
3. Upload the 'Export All URLs' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
4. Activate Export All URLs from your Plugins page.

= Usage =

Exporting data:

1. Go to Tools > Export All URLs.
2. On the Export tab, choose a post type and tick the fields you want (or use a preset such as "SEO basics").
3. Optionally filter by post status, author or date range, and set advanced options (exclude domain, post range, file name, language).
4. Click "Download" to get a CSV or JSON file, or "Display Here" to view the results in a paginated table.

Detecting changes:

1. Open the Snapshots tab and click "Take snapshot".
2. Later, use "Latest vs live site" (or compare any two snapshots) to see what was added, removed or changed.
3. Download the differences as CSV or JSON if you need them.

= Uninstalling: =

1. In the Admin Panel, go to "Plugins" and deactivate the plugin.
2. Click "Delete" on the Plugins page. This removes the plugin files and its snapshot tables automatically.


== Frequently Asked Questions ==

= What data can I export? =

For every post, page or custom post type you can export the Post ID, Title, URL, Status, Categories and Category URLs, Tags and Tag URLs, Author, Published and Modified dates, Word Count, Excerpt, Featured Image URL, Comment Count, Slug, Post Type, and (on multilingual sites) the Language and Translation URLs. Choose exactly the columns you want using the grouped checkboxes and quick-select presets.

= Can I export to JSON as well as CSV? =

Yes. You can download the data as a CSV file, download it as JSON, or display it in a paginated table right inside the dashboard.

= What is the Snapshots feature? =

A snapshot records a lightweight fingerprint (a hash, not a copy) of every post, page and custom post type on your site. Later you can compare two snapshots, or compare your latest snapshot against the live site, to see exactly what was added, removed or modified - including which posts changed in title, content, status, author, slug, taxonomy, custom fields or featured image.

= How can I tell if my site was changed or compromised? =

Take a snapshot when everything looks correct, then run "Latest vs live site" whenever you want to check. Newly published pages and author or status changes are highlighted as "Notable", because those are common signs of injected or unauthorized content. The differences can be downloaded as CSV or JSON.

= Does it work with custom post types and WooCommerce? =

Yes. Both the export and the snapshots cover all public post types - including WooCommerce products and any other custom post type - not just posts and pages.

= Does it support multilingual sites (WPML / Polylang)? =

Yes. The export has a "Languages" option to export all languages or just the default one, regardless of the language selected in the admin bar, and can add Language and Translation URL columns. Snapshots always capture every language.

= Will it work on a large site without timing out? =

The export and snapshot operations read posts in batches and stream results out as they go, so memory stays bounded no matter how many posts you have. Snapshots use keyset pagination and prime caches in bulk to stay fast on large tables. You can also export a specific post range to keep each request small.

= Is the exported CSV safe to open in Excel? =

Yes. Cell values that begin with =, +, - or @ are neutralized, so a malicious value cannot run as a spreadsheet formula when the file is opened.

= Does the plugin modify the database? =

The export feature does not touch the database. The optional Snapshots feature stores hashed fingerprints in two custom tables so it can detect changes over time; those tables are removed automatically when you uninstall the plugin.

= Which PHP and WordPress versions are required? =

PHP 5.4 or higher and WordPress 3.6 or higher. The plugin has been tested up to current versions. WordPress itself [recommends PHP version 7.4 or greater](https://wordpress.org/about/requirements/).

== Screenshots ==

1. The Export tab - pick a post type and choose fields from collapsible groups with quick-select presets
2. Results shown right in the dashboard - every URL in a paginated table
3. The export as a spreadsheet-ready CSV file
4. ...or as structured JSON for scripts and integrations
5. Snapshots compared - added, removed and modified content highlighted, with notable changes flagged
6. The snapshot diff exported to CSV
7. ...and the same diff as JSON



== Changelog ==

= 6.0 =
* New - Snapshots: fingerprint your whole site and compare two snapshots to see what was added, removed or changed - with new pages and author/status changes flagged as possible signs of a compromise. Export diffs to CSV or JSON.
* New - export Word Count, Excerpt, Featured Image URL, Comment Count, Slug and Post Type
* New - multilingual support (WPML / Polylang): export all languages or just the default, plus optional Language and Translation URL columns
* New - JSON download format, alongside CSV and on-screen display
* New - field picker grouped into collapsible sections with quick-select presets; remembers your last selection
* New - on-screen results table now shows every row with pagination and a "Results per page" selector
* New - bundled translations for German, French, Russian, Japanese, Urdu and Pakistani Punjabi; the whole interface is now translatable (POT included)
* Improvement - exports run in batches to prevent timeouts and memory errors on large sites
* Improvement - rewritten on a modular architecture, still PHP 5.4 compatible
* Improvement - the form preserves your inputs and keeps the relevant sections expanded after submit
* Improvement - tidied the support sidebar
* Improvement - CSV and JSON downloads stream straight to the browser, leaving nothing behind in wp-content/uploads
* Security - CSV formula-injection protection
* Security - hardened output escaping, nonce verification and input sanitization across all screens
* Compatibility - resolved WordPress Coding Standards / Plugin Check issues
* Fix - restored genuine PHP 5.4 compatibility

= 5.1 =
* Improvement - strengthened csv file name to prevent unauthorized discovery
* Compatibility - tested with Wordpress 6.9.1

= 5.0 =
* New - additional export fields added (status, category urls, tag urls)
* New - allows multiple post status selection
* Improvement - few backend refinements to improve performance
* Compatibility - tested with Wordpress 6.7.1

= 4.7.1 =
* Compatibility - test with Wordpress 6.7-alpha-58656

= 4.7 = 
* Added - support for the translation
* Compatibility - tested with WordPress 6.4
* Improvement - better organization and several code refinements 

= 4.6 = 
* Fixed - reflected cross-site scripting vulnerability
* Compatibility - tested with WordPress 6.2.2

= 4.5 = 
* New - additional export fields added (tags, author, published, and modified date)
* New - option to retain selected options (no reset to default upon exporting)
* Improvement - backend code refinement
* Compatibility - minor adjustments for PHP 8.1 compatibility
* Compatibility - tested with WordPress 6.2

= 4.4 = 
* Added - additional verification for file removal to patch a security issue
* Compatibility - tested with wordpress 6.0.1

= 4.3 =
* Added - overall security and stability improvements
* Compatibility - tested with wordpress 5.9.2

= 4.2 =
* Fixed - patched a security vulnerability
* Removed - file path customization option
* Compatibility - tested with wordpress 5.9.1 & PHP 8.1

= 4.1 =
* Added - option to remove woo commerce extra attributes from categories
* Tweak - bit of formatting adjustments
* Added - some default settings
* Compatibility - tested with wordpress 5.4.2

= 4.0 =
* Added - export post IDs
* Added - exclude domain URL
* Added - complete support of custom post type categories
* Tweak - small dashboard design improvements
* Added - enables user to delete the file once downloaded
* Compatibility - wordpress 5.4 and php 7.3
* Tweak - migrated under tools options, instead of settings
* Added - displays total number of links
* Added - new easy ways to report problem or bug
* Fixed - conflict with "Security Header" & "Elementor" plugin
* Fixed - typo on settings page
* Added - extra verification checks

= 3.6 =
* Added - filter data by date range
* Tweak - some general activation improvements
* Compatibility - tested with 5.1.1

= 3.5 =
* Added - allow users to customize file path and file name
* Fixed - grammatical mistake
* Compatibility - tested with 4.9.7

= 3.0 =
* Added - filter data by author
* Added - specify post range for extraction
* Added - generates random file name
* Compatibility - tested with 4.9.2

= 2.6 =
* Fixed - variable initialization errors
* Compatibility - tested with 4.9

= 2.5 =
* Added - support for selecting post status
* Compatibility - tested with 4.7.5

= 2.4 =
* Fixed - fatal error bug fixed
* Compatibility - tested with wordpress 4.7.2

= 2.3 =
* Fixed - categories export, (only first category was exporting)
* Compatibility - tested with wordpress 4.7

= 2.2 =
* Added - support for wordpress 4.6.1

= 2.1 =
* Fixed - special character exporting for Polish Language

= 2.0 =
* Added - support for exporting title and categories

= 1.0 =
* initial release

== Upgrade Notice ==

= 6.0 =
Our biggest release in 10 years. Export All URLs has grown from a simple exporter into a security tool too: alongside CSV/JSON exports, 19 fields and WPML/Polylang support, new Snapshots detect added, removed or unauthorized changes to your site. A full modern rewrite, now available in 6 languages, still compatible back to PHP 5.4.

* New - Snapshots: fingerprint your whole site and compare two snapshots to see what was added, removed or changed - with new pages and author/status changes flagged as possible signs of a compromise. Export diffs to CSV or JSON.
* New - export Word Count, Excerpt, Featured Image URL, Comment Count, Slug and Post Type
* New - multilingual support (WPML / Polylang): export all languages or just the default, plus optional Language and Translation URL columns
* New - JSON download format, alongside CSV and on-screen display
* New - field picker grouped into collapsible sections with quick-select presets; remembers your last selection
* New - on-screen results table now shows every row with pagination and a "Results per page" selector
* New - bundled translations for German, French, Russian, Japanese, Urdu and Pakistani Punjabi; the whole interface is now translatable (POT included)
* Improvement - exports run in batches to prevent timeouts and memory errors on large sites
* Improvement - rewritten on a modular architecture, still PHP 5.4 compatible
* Improvement - the form preserves your inputs and keeps the relevant sections expanded after submit
* Improvement - tidied the support sidebar
* Improvement - CSV and JSON downloads stream straight to the browser, leaving nothing behind in wp-content/uploads
* Security - CSV formula-injection protection
* Security - hardened output escaping, nonce verification and input sanitization across all screens
* Compatibility - resolved WordPress Coding Standards / Plugin Check issues
* Fix - restored genuine PHP 5.4 compatibility
