=== Export All URLs ===
Contributors: Atlas_Gondal
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YWT3BFURG6SGS&source=url
Tags: extract urls, export urls, links, get links, get urls, custom post type urls, see links, extract title, export title, export post title, export title and url, export category, utilities, export, csv
Requires at least: 3.1
Tested up to: 6.4
Stable tag: 4.7
Requires PHP: 5.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin enables you to extract information such as Title, URL, Categories, Tags, Author, as well as Published and Modified dates for built-in post types (e.g., post, page) or any other custom post types present on your site. You have the option to display the output in the dashboard or export it as a CSV file. This can be highly beneficial for tasks like migration, SEO analysis, and security audits.

== Description ==

This plugin will add a page called "Export All URLs" under Tools. You can navigate there and can extract data from your site. You can export Posts:

* ID
* Title
* URL
* Categories
* Tags
* Author
* Published Date
* Modified Date

The data can be filtered by post type, post status, date range, and author before extraction, and the plugin also provides the option to export using a specific post range.

== When we need this plugin? ==

* To check all URLs of your website
* During migration
* During security audit
* Need to share All URLs with SEO guy
* 301 Redirects handling using htaccess


== Customizable Features ==

* Filter by Author
* Filter by Date Range
* Exclude domain URL (very helpful in comparing results after migration)
* Set post range (very beneficial in case of timeout/memory out error)
* Generates CSV file name randomly (sensitive data protection for security reasons)
* Set preferred CSV file name (provides more control)

= System requirements =

* PHP version 5.4 or higher
* Wordpress version 3.1.0 or higher


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
2. Upload the 'Export All URLs' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate Export All URLs from your Plugins page.

= Usage =

1. Go to Tools > Export All URLs to export URLs of your website.
2. Select Post Type
3. Choose Data (e.g Post ID, Title, URL, Categories, Tags, Author, Published Date, Modified Date)
4. Apply Filters (e.g Post Status, Author, Post Range)
5. Configure advance options (e.g exclude domain url, number of posts)
5. Finally Select Export type and click on Export Now.

= Uninstalling: =

1. In the Admin Panel, go to "Plugins" and deactivate the plugin.
2. Go to the "plugins" folder of your WordPress directory and delete the files/folder for this plugin.


== Frequently Asked Questions ==

= About Plugin Support? =

Post your question on support forum and we will try to answer your question as quick as possible.

= Why did you make this plugin?  =

We couldn't find a plugin that would export all URLs, titles and categories in a simplest possible way. So, we decided to take step further to fill this gap.

= Why the file name is randomly generated?  =

Exporting a file with a static name makes it easier for malicious attackers to discover, potentially leading to the leakage of sensitive information. Therefore, we chose to generate random names, which are more difficult to guess. Nonetheless, the plugin offers full control over the file name if needed.

= Can I delete generated CSV file?  =

Yes, absolutely. It is highly recommended, once the file is generated, there is a direct link to delete the generated file.

= Does Export All URLs make changes to the database? =

No, the plugin does not interact with the database, as it does not have any settings or configurations to store.

= How can I check out if the plugin works for me? =

Install and activate. Go to Tools / Export All URLs. Select all options and download CSV file.

= Which PHP version do I need? =

This plugin has been tested and works with PHP versions 5.4 and greater. WordPress itself [recommends using PHP version 7.3 or greater](https://wordpress.org/about/requirements/). If you're using a PHP version lower than 5.4 please upgrade your PHP version or contact your Server administrator.

= Are there any known incompatibilities? =

Nope, there were some issues in past, but they were fixed in version 4.0.

= Are there any server requirements? =

Yes. The plugin requires a PHP version 5.4 or higher and Wordpress version 3.1.0 or higher.

== Screenshots ==

1. Admin screenshot of Export All URLs
2. Exported data in the dashboard
3. Exported data to a CSV file
4. CSV File Preview


== Changelog ==

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

= 4.6 = 
* Fixed - reflected cross-site scripting vulnerability
* Compatibility - tested with WordPress 6.2.2
