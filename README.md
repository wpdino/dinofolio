# DinoFolio Lite

A modern WordPress portfolio plugin with custom post types, Gutenberg block, Elementor widget, and flexible layouts.

> **WordPress.org:** The official plugin directory readme is [`readme.txt`](readme.txt). Use that file when submitting or updating the plugin on WordPress.org.

## Features

- 🎨 **Modern Gutenberg Block** - Portfolio Listing block with live preview
- 📱 **Responsive Design** - Works beautifully on all devices
- 🔧 **Multiple Layouts** - Grid, Masonry, and List layouts
- ⚙️ **Customizable** - Extensive settings and options
- 🚀 **Performance Optimized** - Clean, efficient code
- 🎯 **SEO Friendly** - Proper markup and structure

## Installation

1. Upload the plugin files to `/wp-content/plugins/dinofolio/`
2. Activate the plugin through the **Plugins** screen in WordPress
3. Go to **DinoFolio** in the admin menu to configure global settings
4. Add portfolio items and use the Portfolio Listing block, Elementor widget, shortcode, or WPBakery module

For WordPress.org-style installation and FAQ text, see [`readme.txt`](readme.txt).

## Development Setup

### Prerequisites

- Node.js >= 14.15.0
- npm >= 6.14.8
- WordPress 6.6+
- PHP 7.0+

### Getting Started

1. Clone or download the plugin to your WordPress plugins directory
2. Navigate to the plugin directory:
   ```bash
   cd wp-content/plugins/dinofolio
   ```

3. Install dependencies (optional, for `@wordpress/scripts` tooling):
   ```bash
   npm install
   ```

### Build Commands

#### Development Mode
```bash
npm run start
```
- Starts the development server with hot reloading
- Watches for changes in source files
- Automatically rebuilds when files are modified

#### Production Build
```bash
npm run build
```
- Creates optimized production build
- Minifies JavaScript and CSS
- Generates asset dependency files

#### Code Quality
```bash
npm run lint:js    # Lint JavaScript files
npm run lint:css   # Lint CSS/SCSS files
npm run format     # Format code according to WordPress standards
```

### File Structure

```
dinofolio/
├── dinofolio.php                    # Main plugin bootstrap
├── readme.txt                       # WordPress.org plugin readme
├── includes/
│   ├── class-dinofolio-*.php        # Core: CPT, display, util, meta boxes, menus
│   ├── components/                  # Shared component params (portfolio listing)
│   ├── integrations/
│   │   ├── gutenberg/blocks/portfolio/block.js
│   │   ├── elementor/widgets/portfolio/
│   │   └── wpbakery/                # WPBakery module base
│   ├── elementor/                   # Legacy Elementor widgets & controls
│   └── admin/                       # Settings UI, metabox assets
├── assets/css/                      # Front-end listing & single styles
├── templates/                       # Single portfolio & related project templates
├── languages/dinofolio.pot          # Translation template
├── package.json                     # Optional Node tooling
└── webpack.config.js                # Optional build config
```

### Block Development

The Portfolio Listing Gutenberg block (`dinofolio/portfolio`) is registered from PHP and edited in JavaScript:

- **Block script**: `includes/integrations/gutenberg/blocks/portfolio/block.js`
- **Editor styles**: `assets/css/portfolio-listing-editor.css`
- **Frontend styles**: `assets/css/portfolio-listing.css`
- **Render / query**: `includes/class-dinofolio-display.php` and `includes/components/items/portfolio/class-dinofolio-portfolio-component.php`

#### Block Features

- **Inspector controls**: Display and Query sections (layout, filters, sorting)
- **Server-side render**: Live preview via `@wordpress/server-side-render`
- **REST taxonomies**: Category and tag filters in the block sidebar

### WordPress Integration

The plugin integrates with WordPress through:

1. **Custom post type**: `wpdino_portfolio` (admin label: Portfolio Items)
2. **Taxonomies**: `wpdino_portfolio_category`, `wpdino_portfolio_tag`
3. **Settings page**: **DinoFolio** under the portfolio admin menu
4. **Gutenberg block**: `dinofolio/portfolio` (block category: DinoFolio)
5. **Elementor widget**, **WPBakery module**, and shortcodes `[dinofolio]` / `[dinofolio_portfolio]`

### Customization

#### Styling

- **Listing (front end)**: `assets/css/portfolio-listing.css`
- **Listing (editor preview)**: `assets/css/portfolio-listing-editor.css`
- **Admin / settings**: `includes/admin/assets/css/admin.css`
- **Portfolio metabox**: `includes/admin/assets/css/admin-portfolio-meta.css`

#### Single Portfolio Meta Template Override

The single portfolio meta output (date, attributes, external button, social links, related items) uses a theme-overridable template:

- **Plugin template fallback**: `templates/single-portfolio-meta.php`
- **Theme override path**: `your-theme/dinofolio/single-portfolio-meta.php`
- **Frontend styles file**: `assets/css/single-portfolio-meta.css`

To customize markup, copy the plugin template into your theme override path and edit it there.  
For styling changes, add CSS in your theme stylesheet or dequeue/override the plugin stylesheet as needed.

### Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- IE11+ (with polyfills)
- Mobile browsers (iOS Safari, Chrome Mobile)

### WordPress Compatibility

- WordPress 6.6+
- PHP 7.0+
- Block editor (Gutenberg)
- Shortcodes for classic layouts or page builders without blocks

## Usage

### Adding Portfolio Items

1. Go to **DinoFolio** > **Portfolio Items** > **Add New** in your WordPress admin
2. Add title, content, featured image, and portfolio details
3. Assign categories and tags as needed
4. Publish the portfolio item

### Using the Gutenberg Block

1. Edit a page or post in the block editor
2. Add the "Portfolio Listing" block
3. Configure layout, columns, and display options
4. Preview and publish

### Block Settings

- **Layout**: Grid, Masonry, or List
- **Columns**: 1-6 columns (for grid/masonry)
- **Number of Items**: How many portfolio items to show
- **Image Size**: Thumbnail, Medium, Large, or Full
- **Show Excerpt**: Display portfolio item descriptions
- **Show Read More**: Add read more links
- **Sorting**: Order by date, title, menu order, or random

## Support

For support and documentation, visit [WPDino.com](https://wpdino.com/plugins/dinofolio/)

## License

This plugin is licensed under GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- Portfolio custom post type
- Gutenberg block support
- Admin settings panel
- Multiple layout options
- Responsive design 