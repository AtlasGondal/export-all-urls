[![export-all-urls](https://socialify.git.ci/AtlasGondal/export-all-urls/image?description=1&forks=1&issues=1&language=1&logo=https%3A%2F%2Fraw.githubusercontent.com%2Fdevicons%2Fdevicon%2Fmaster%2Ficons%2Fwordpress%2Fwordpress-original.svg&owner=1&pattern=Diagonal%20Stripes&pulls=1&stargazers=1)][plugin-url]

# Export All URLs

Export posts, pages & custom post types to **CSV or JSON** — and **snapshot** your site to detect added, removed or changed content.

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/export-all-urls?label=version)][plugin-url]
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/export-all-urls?label=downloads)][plugin-url]
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/r/export-all-urls?label=rating)][plugin-url]
[![Tested up to](https://img.shields.io/wordpress/plugin/tested/export-all-urls?label=tested%20up%20to)][plugin-url]
[![Requires PHP](https://img.shields.io/wordpress/plugin/required-php/export-all-urls?label=php)][plugin-url]
[![License: GPL v2](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## Description

This plugin adds a page called **“Export All URLs”** under **Tools**. From there you can extract data from your site and either view it in a paginated table, or download it as a **CSV** or **JSON** file.

For every post, page or custom post type you can **export** up to **19 fields**:

- ID
- Title
- URL
- Status
- Categories
- Category URLs
- Tags
- Tag URLs
- Author
- Published Date
- Modified Date
- Word Count
- Excerpt
- Featured Image URL
- Comment Count
- Slug
- Post Type
- Language *(WPML / Polylang)*
- Translation URLs *(WPML / Polylang)*

Fields are organized into **collapsible groups with quick-select presets**, so the picker stays tidy even with this many columns. The data can be filtered by post type, post status, date range and author before extraction, and you can also export a specific **post range**.

## ⭐ New in v6.0: Snapshots

Version 6.0 is a complete ground-up rewrite that grows Export All URLs from an exporter into a **site integrity / change-monitoring** tool, too.

A **snapshot** records a lightweight fingerprint — a hash, not a copy — of every post, page and custom post type on your site. Later you can compare **two snapshots**, or compare your **latest snapshot against the live site**, to see exactly what changed:

- **Added** and **Removed** content
- **Modified** content, with the specific change named: title, body, status, author, slug, taxonomy, custom fields or featured image

Newly published pages and author/status changes are flagged as **“Notable”**, because those are common signs of injected or unauthorized content — a simple answer to the question *“Did something change that I didn’t change?”* Differences can be downloaded as **CSV or JSON**.

## When do we need this plugin?

- To check all URLs of your website
- During a migration (and to confirm everything was moved across)
- During a security audit, or to monitor a site for unauthorized or injected content (**Snapshots**)
- To share all URLs with your SEO consultant
- For handling 301 redirects in `.htaccess`

## Customizable Features

- Choose exactly which columns to export, grouped with quick-select presets
- Download as **CSV** or **JSON**, or display results in a paginated table
- Filter by post type, post status, author and date range
- Export a specific **post range** (helpful on very large sites)
- **Exclude the domain** from URLs (handy when comparing results after a migration)
- **Multilingual support** for WPML and Polylang (export all languages or just the default)
- Custom or randomly generated download file name
- **Snapshots**: capture your site and detect what was added, removed or changed over time
- **CSV-injection protection** (values starting with `= + - @` are neutralized)
- Batched, streamed processing so it stays fast on large sites

## 🌍 Multilingual

The entire interface is translatable and ships **translated into six languages** — German, French, Russian, Japanese, Urdu and **Pakistani Punjabi** — so more people can use it comfortably in their own language.

## Installation

**From your WordPress dashboard**

1. Visit **Plugins → Add New**
2. Search for **Export All URLs**
3. Activate it from your Plugins page

**From WordPress.org**

1. Download Export All URLs
2. Unzip and upload the `export-all-urls` directory to `/wp-content/plugins/`
3. Activate it from your Plugins page

## Usage

**Exporting data**

1. Go to **Tools → Export All URLs**.
2. On the **Export** tab, choose a post type and tick the fields you want (or use a preset such as “SEO basics”).
3. Optionally filter by status, author or date range, and set advanced options (exclude domain, post range, file name, language).
4. Click **Download** for a CSV/JSON file, or **Display Here** to view the results in a paginated table.

**Detecting changes**

1. Open the **Snapshots** tab and click **Take snapshot**.
2. Later, use **Latest vs live site** (or compare any two snapshots) to see what was added, removed or changed.
3. Download the differences as CSV or JSON if you need them.

### System requirements

- PHP version **5.4** or higher
- WordPress version **3.6** or higher

If you find any bug then [report here][contact], I’ll try to fix it as soon as possible!

**\* Screenshots and a further installation guide can be found on the [WordPress repository][plugin-url].**

## Connect with me:

[<img align="left" alt="AtlasGondal.com" width="22px" src="https://raw.githubusercontent.com/iconic/open-iconic/master/svg/globe.svg" />][website]
[<img align="left" alt="AtlasGondal | YouTube" width="22px" src="https://cdn.jsdelivr.net/npm/simple-icons@v3/icons/youtube.svg" />][youtube]
[<img align="left" alt="Atlas_Gondal | Twitter" width="22px" src="https://cdn.jsdelivr.net/npm/simple-icons@v3/icons/twitter.svg" />][twitter]
[<img align="left" alt="AtlasGondal | LinkedIn" width="22px" src="https://cdn.jsdelivr.net/npm/simple-icons@v3/icons/linkedin.svg" />][linkedin]
[<img align="left" alt="Atlas_Gondal | Instagram" width="22px" src="https://cdn.jsdelivr.net/npm/simple-icons@v3/icons/instagram.svg" />][instagram]

<br/><br/>


[contact]: https://atlasgondal.com/contact-me/?utm_source=self&utm_medium=github&utm_campaign=export-all-urls&utm_term=description
[website]: https://atlasgondal.com/?utm_source=self&utm_medium=github&utm_campaign=export-all-urls&utm_term=description
[github]: https://github.com/AtlasGondal/
[twitter]: https://twitter.com/Atlas_Gondal/
[youtube]: https://www.youtube.com/AtlasGondal/
[instagram]: https://www.instagram.com/Atlas_Gondal/
[linkedin]: https://www.linkedin.com/in/AtlasGondal/
[plugin-url]: https://wordpress.org/plugins/export-all-urls/
