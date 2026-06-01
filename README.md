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
- WordPress 5.0+
- PHP 7.4+

### Getting Started

1. Clone or download the plugin to your WordPress plugins directory
2. Navigate to the plugin directory:
   ```bash
   cd wp-content/plugins/wpdino-portfolio
   ```

3. Install dependencies:
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
wpdino-portfolio/
├── includes/
│   ├── blocks/
│   │   └── portfolio-listing/
│   │       ├── src/                    # Source files (for development)
│   │       │   ├── index.js           # Main block registration
│   │       │   ├── editor.scss        # Editor-specific styles
│   │       │   └── style.scss         # Frontend styles
│   │       ├── block.json             # Block configuration
│   │       ├── portfolio-listing.js   # Built JavaScript (generated)
│   │       ├── portfolio-listing.css  # Built editor styles (generated)
│   │       ├── style-portfolio-listing.css # Built frontend styles (generated)
│   │       └── assets.php            # WordPress dependencies (generated)
│   ├── admin/                        # Admin interface
│   └── classes/                      # PHP classes
├── assets/                          # Static assets
├── templates/                       # Template files
├── package.json                     # Node.js dependencies
├── webpack.config.js               # Build configuration
└── README.md                       # This file
```

### Block Development

The Portfolio Listing block is built using modern React/JSX with WordPress components:

- **Source**: `includes/blocks/portfolio-listing/src/index.js`
- **Editor Styles**: `includes/blocks/portfolio-listing/src/editor.scss`
- **Frontend Styles**: `includes/blocks/portfolio-listing/src/style.scss`

#### Block Features

- **Inspector Controls**: Layout options, display settings, sorting
- **Toolbar Controls**: Quick layout switching
- **Server-Side Rendering**: Dynamic content with WordPress query
- **Responsive Design**: Mobile-first approach

### WordPress Integration

The plugin integrates with WordPress through:

1. **Custom Post Type**: Portfolio items (`portfolio`)
2. **Settings Page**: Admin configuration panel
3. **Gutenberg Block**: Modern block editor integration
4. **Shortcodes**: Legacy support for older themes

### Customization

#### Adding New Layouts

1. Add layout option to `src/index.js`
2. Add corresponding styles to `src/style.scss`
3. Update PHP render logic if needed

#### Styling

- **Editor styles**: Edit `src/editor.scss`
- **Frontend styles**: Edit `src/style.scss`
- **Admin styles**: Edit `includes/admin/assets/css/admin.css`

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

- WordPress 5.0+
- PHP 7.4+
- Gutenberg editor
- Classic editor (via shortcodes)

## Usage

### Adding Portfolio Items

1. Go to Portfolio > Add New in your WordPress admin
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