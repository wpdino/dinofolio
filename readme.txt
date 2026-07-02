=== DinoFolio ===
Contributors: wpdino
Donate link: https://paypal.me/dinostd/10usd
Tags: portfolio, gutenberg, elementor, gallery, showcase
Requires at least: 6.6
Tested up to: 7.0
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress portfolio plugin with a custom post type, Gutenberg block, Elementor widget, shortcodes, and archive templates.

== Description ==

DinoFolio adds a portfolio custom post type to WordPress and gives you several ways to display projects on your site: the block editor, Elementor, WPBakery, shortcodes, and built-in category or tag archive templates.

After activation, you can add portfolio items, organize them with categories and tags, configure global defaults in the settings screen, and embed a portfolio listing on any page or post.

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

= Requirements =

* WordPress 6.6 or higher
* PHP 7.0 or higher
* Elementor (optional, for the Elementor widget)
* WPBakery Page Builder (optional, for the WPBakery module)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/dinofolio` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.

== Getting Started ==

Follow these steps after a fresh install:

1. **Open settings** — In the admin menu, go to **DinoFolio** > **Settings**. Review the **General** tab for permalink slugs and default portfolio item options. Save your changes.
2. **Refresh permalinks** — If you change the portfolio or taxonomy slug, visit **Settings** > **Permalinks** in WordPress and click **Save Changes** once so archive URLs work correctly.
3. **Add categories (optional)** — Go to **DinoFolio** > **Categories** and create the groups you want to use for filtering and archives.
4. **Create portfolio items** — Go to **DinoFolio** > **Portfolio Items** > **Add New**. Add a title, featured image, excerpt, categories, and any project details in the portfolio meta boxes.
5. **Display a listing on a page** — Edit a page or post and add the **Portfolio Listing** block (block editor), the **Portfolio Listing** Elementor widget, the WPBakery module, or a shortcode such as `[dinofolio layout="grid" columns="3"]`.
6. **Adjust listing options** — In the block or widget sidebar, use the **Display** and **Query** panels to choose layout, columns, filters, sorting, and what information appears on each card.
7. **Configure archive templates (optional)** — In **DinoFolio** > **Settings** > **Taxonomy Archive**, enable the plugin taxonomy template and set listing options for category and tag archive pages.

Single portfolio pages use your theme template plus the plugin’s portfolio meta output. To customize that markup, copy `templates/single-portfolio-meta.php` to `your-theme/dinofolio/single-portfolio-meta.php`.

== Frequently Asked Questions ==

= Do I need Elementor to use DinoFolio? =

No. The portfolio post type, Gutenberg block, shortcodes, and front-end templates work without Elementor. Elementor is only required if you want to use the Elementor widget.

= Do I need WPBakery? =

No. WPBakery support is optional and loads only when WPBakery Page Builder is active.

= How do I add a portfolio listing to a page? =

In the block editor, add the **Portfolio Listing** block from the DinoFolio category. In Elementor, search for **Portfolio Listing** in the widget panel. You can also use the shortcode `[dinofolio]` or `[dinofolio_portfolio]` with optional attributes, for example: `[dinofolio layout="grid" columns="3" posts_to_show="9" categories="design"]`.

= Where do I change global defaults? =

Go to **DinoFolio** > **Settings**. The **General** tab controls permalink slugs, default values for new portfolio items, and which Elementor widgets are enabled. The **Taxonomy Archive** tab controls category and tag archive listings. Use the **Tools** tab to export or import settings.

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
Initial release of DinoFolio. Install to create portfolio items and display them with the block editor, Elementor, WPBakery, or shortcodes.

== Support ==

For support, documentation, and updates, visit [wpdino.com](https://www.wpdino.com).

== External services ==

DinoFolio can connect to third-party video services (YouTube and Vimeo) when you use its optional video features. It does not send any data to external services unless you add a YouTube or Vimeo video to a portfolio item. No data is sent for portfolio items that do not use a video.

= YouTube (provided by Google LLC) =

The plugin uses YouTube in two situations:

* **Fetching a video thumbnail (admin).** When you add a YouTube video to a portfolio item and choose to use its thumbnail as the featured image, the plugin sends the video ID to YouTube by requesting thumbnail image URLs (for example `https://img.youtube.com/vi/VIDEO_ID/maxresdefault.jpg`) and, where available, the WordPress oEmbed endpoint, to determine the best available thumbnail. The image is then downloaded to your own Media Library. This happens only when you trigger the thumbnail action in the editor.
* **Playing a video (front end).** When a visitor opens a portfolio item that uses a YouTube video in the lightbox player, their browser loads the YouTube embed directly from YouTube. This sends the visitor's IP address and player/browser data to YouTube, subject to YouTube's own policies.

YouTube terms of service: https://www.youtube.com/t/terms
Google privacy policy: https://policies.google.com/privacy

= Vimeo (provided by Vimeo.com, Inc.) =

The plugin uses Vimeo in two situations:

* **Fetching a video thumbnail (admin).** When you add a Vimeo video to a portfolio item and choose to use its thumbnail as the featured image, the plugin sends the video URL to Vimeo through the WordPress oEmbed endpoint to retrieve the thumbnail URL. The image is then downloaded to your own Media Library. This happens only when you trigger the thumbnail action in the editor.
* **Playing a video (front end).** When a visitor opens a portfolio item that uses a Vimeo video in the lightbox player, their browser loads the Vimeo embed directly from Vimeo. This sends the visitor's IP address and player/browser data to Vimeo, subject to Vimeo's own policies.

Vimeo terms of service: https://vimeo.com/terms
Vimeo privacy policy: https://vimeo.com/privacy

== Privacy Policy ==

By default, this plugin does not collect, store, or transmit personal data to external servers. External requests are made only when you use the optional video features described in the **External services** section above.

**Data stored on your site:**
* Portfolio posts, categories, tags, and media you create in WordPress
* Plugin settings saved in the WordPress options table
* Block and widget settings stored in post content

**Third-party libraries:**
* GLightbox (bundled) is used on the front end when the image lightbox option is enabled. For image lightboxes, images are loaded from your own site and no third-party API calls are made. For video lightboxes, GLightbox loads the YouTube or Vimeo embed from those services (see the **External services** section).

**Optional integrations:**
* Elementor and WPBakery are optional. When used, their respective editors load only on pages where you build content with those tools.

No analytics, tracking, or account registration is included in this plugin.

== Third-Party Libraries ==

This plugin bundles the following third-party libraries:

* GLightbox - https://github.com/biati-digital/glightbox - MIT License
* Plyr - https://github.com/sampotts/plyr - MIT License
* Isotope - https://isotope.metafizzy.co/ - Commercial / GPLv3 dual license
* imagesLoaded - https://imagesloaded.desandro.com/ - MIT License
