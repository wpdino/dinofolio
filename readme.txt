=== DinoFolio Lite ===
Contributors: wpdino
Donate link: https://paypal.me/dinostd/10usd
Tags: portfolio, gutenberg, elementor, gallery, showcase
Requires at least: 6.6
Tested up to: 6.9
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modern WordPress portfolio plugin with custom post types, Gutenberg block, Elementor widget, and flexible layouts.

== Description ==

DinoFolio Lite helps creatives, designers, photographers, and businesses showcase their work in a clean, customizable, and professional way. Build portfolio grids on any page using the block editor, Elementor, WPBakery, or shortcodes.

= Key Features =

* **Portfolio Custom Post Type** - Dedicated portfolio items with featured images, excerpts, categories, and tags
* **Portfolio Listing Block** - Native Gutenberg block with live server-side preview and inspector controls
* **Elementor Widget** - Drag-and-drop Portfolio Listing widget with the same options as the block
* **WPBakery Module** - Portfolio listing module for WPBakery Page Builder sites
* **Shortcode Support** - Embed listings anywhere with `[dinofolio]` and `[dinofolio_portfolio]`
* **Multiple Layouts** - Grid, Masonry, and List layouts with responsive columns
* **Query Controls** - Filter by portfolio category or tag, set item count, and control sort order
* **Display Options** - Toggle title, categories, excerpt, read more button, pagination, and view-all link
* **Image Lightbox** - Optional lightbox on thumbnails with zoom icon on hover
* **Category Filter Bar** - Optional front-end filter tabs by portfolio category
* **Single Portfolio Templates** - Rich single-project meta (attributes, social links, related projects) with theme override support
* **Admin Settings** - Global defaults for layout, columns, image size, lightbox, permalinks, and more
* **Translation Ready** - Text domain `dinofolio` with POT file included

= Portfolio Listing Options =

* **Display** - Layout, columns, image size, title, categories, excerpt, read more label, lightbox, category filter, pagination, view-all button
* **Query** - Include categories, include tags, posts to show, order by (date, title, menu order, modified, random), sort direction

= Easy to Use =

* Add portfolio items from the WordPress admin menu
* Insert the Portfolio Listing block in Gutenberg or the Elementor widget on any page
* Customize display and query settings in the sidebar panel
* No coding required for standard use

= Requirements =

* WordPress 6.6 or higher
* PHP 7.0 or higher
* Elementor (optional, for the Elementor widget)
* WPBakery Page Builder (optional, for the WPBakery module)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/dinofolio` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **DinoFolio** in the admin menu to configure global settings (layout, permalinks, defaults).
4. Add portfolio items under **DinoFolio** > **Portfolio Items**.
5. Add the **Portfolio Listing** block in the block editor, or use the **Portfolio Listing** Elementor widget, shortcode, or WPBakery module.

== Frequently Asked Questions ==

= Do I need Elementor to use DinoFolio? =

No. The portfolio post type, Gutenberg block, shortcodes, and front-end templates work without Elementor. Elementor is only required if you want to use the Elementor widget.

= Do I need WPBakery? =

No. WPBakery support is optional and loads only when WPBakery Page Builder is active.

= How do I add a portfolio listing to a page? =

In the block editor, add the **Portfolio Listing** block from the DinoFolio category. In Elementor, search for **Portfolio Listing** in the widget panel. You can also use the shortcode `[dinofolio]` or `[dinofolio_portfolio]` with optional attributes.

= Can I filter projects by category or tag? =

Yes. In the block or widget **Query** panel, use **Categories** and **Tags** to limit which projects appear. Leave them empty to show all published items.

= Can I customize the single portfolio page? =

Yes. Copy `templates/single-portfolio-meta.php` to `your-theme/dinofolio/single-portfolio-meta.php` to override the single-project meta markup. Related project cards use `templates/parts/related-project-card.php`.

= Is the plugin compatible with my theme? =

DinoFolio is built to work with any well-coded WordPress theme. Listing markup uses plugin styles that you can override in your theme CSS.

= Is the plugin mobile-friendly? =

Yes. Grid, masonry, and list layouts are responsive and adapt to smaller screens.

= Do you provide support? =

Support and documentation are available at [wpdino.com](https://www.wpdino.com).

== Screenshots ==

1. Portfolio grid layout on the front end
2. Portfolio Listing block in the Gutenberg editor
3. Display and Query settings in the block sidebar
4. Elementor Portfolio Listing widget controls
5. DinoFolio admin settings page
6. Single portfolio project page with meta and related items

== Changelog ==

= 1.0.0 =
* Initial release on WordPress.org
* Portfolio custom post type with categories and tags
* Portfolio Listing Gutenberg block with server-side render preview
* Elementor Portfolio Listing widget
* WPBakery Portfolio Listing module
* Shortcodes: `[dinofolio]` and `[dinofolio_portfolio]`
* Grid, Masonry, and List layouts
* Query filters: categories, tags, item count, order by, sort direction
* Display toggles: title, categories, excerpt, read more, lightbox, filter bar, pagination, view-all link
* Category icon on listing cards and lightbox zoom icon on hover
* Single portfolio meta template with theme override support
* Admin settings for defaults, permalinks, and features
* Translation-ready with `languages/dinofolio.pot`

== Upgrade Notice ==

= 1.0.0 =
Initial release of DinoFolio Lite. Install to create portfolio items and display them with the block editor, Elementor, WPBakery, or shortcodes.

== Support ==

For support, documentation, and updates, visit [wpdino.com](https://www.wpdino.com).

== Privacy Policy ==

This plugin does not collect, store, or transmit personal data to external servers by default.

**Data stored on your site:**
* Portfolio posts, categories, tags, and media you create in WordPress
* Plugin settings saved in the WordPress options table
* Block and widget settings stored in post content

**Third-party libraries:**
* GLightbox (bundled) is used on the front end when the image lightbox option is enabled. Images are loaded from your own site; no third-party API calls are made for the lightbox.

**Optional integrations:**
* Elementor and WPBakery are optional. When used, their respective editors load only on pages where you build content with those tools.

No analytics, tracking, or account registration is included in this plugin.
