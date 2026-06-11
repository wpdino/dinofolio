<?php

/**
 * Custom Post Type
 *
 * @package DinoFolio
 * @since 1.0.0
 */

namespace DinoFolio;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for handling custom post type
 */
class Custom_Post {

	/**
	 * This class instance.
	 *
	 * @var Custom_Post
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Provides singleton instance.
	 *
	 * @since 1.0.0
	 * @return self instance
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new Custom_Post();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {

		add_action( 'after_setup_theme', array( $this, 'register_portfolio_post_formats' ), 20 );
		add_action( 'init', array( $this, 'register_custom_post_type' ) );

		// Admin columns
		add_filter( 'manage_wpdino_portfolio_posts_columns', array( $this, 'add_featured_image_column' ) );
		add_action( 'manage_wpdino_portfolio_posts_custom_column', array( $this, 'display_featured_image_column' ), 10, 2 );

		// Featured image AJAX for column actions and admin assets
		add_action( 'wp_ajax_wpdino_portfolio_save_featured_image', array( $this, 'save_featured_image_ajax' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Bulk edit functionality
		add_action( 'bulk_edit_custom_box', array( $this, 'add_featured_image_to_bulk_edit' ), 10, 2 );


	}

	/**
	 * Enable Standard and Gallery post formats for portfolio items.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_portfolio_post_formats() {
		add_theme_support( 'post-formats', array( 'gallery' ) );
	}

	/**
	 * Register post type
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function register_custom_post_type() {

		// Get the portfolio slug and taxonomy slug from the settings
		$settings = DinoFolio_Settings::instance();
		$portfolio_slug     = $settings->get_setting( 'portfolio_slug', 'dinofolio-portfolio' );
		$portfolio_tax_slug = $settings->get_setting( 'portfolio_tax_slug', 'dinofolio-portfolio-category' );
		// Use an explicit white fill so the icon does not flash dark before admin CSS loads (currentColor defaults to black in data-uri SVGs).
		$menu_icon_svg      = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ffffff"><path d="M3 4a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4zm2 1v6h6V5H5zm0 8v6h6v-6H5zm8 6h6v-3h-6v3zm0-5h6V5h-6v9z"/></svg>';

		// Add the portfolio post type
		register_post_type(
			'wpdino_portfolio', 
			array(
				'can_export'          => true,
				'description'         => esc_html__( 'A portfolio type for featuring items in your portfolio.', 'dinofolio' ),
				'has_archive'         => true,
				'hierarchical'        => true,
				'labels'              => array(
					'add_new'                  => esc_html_x( 'Add New', 'portfolio_item', 'dinofolio' ),
					'add_new_item'             => esc_html__( 'Add New', 'dinofolio' ),
					'all_items'                => esc_html__( 'Portfolio Items', 'dinofolio' ),
					'archives'                 => esc_html_x( 'Portfolio Archives', 'The post type archive label used in nav menus. Default "Post Archives". Added in 4.4', 'dinofolio' ),
					'attributes'               => esc_html__( 'Portfolio Post Attributes', 'dinofolio' ),
					'edit_item'                => esc_html__( 'Edit Portfolio Post', 'dinofolio' ),
					'filter_items_list'        => esc_html_x( 'Filter portfolio items list', 'Screen reader text for the filter links heading on the post type listing screen. Default "Filter posts list". Added in 4.4', 'dinofolio' ),
					'insert_into_item'         => esc_html_x( 'Insert into portfolio item', 'Overrides the "Insert into post" phrase (used when inserting media into a post). Added in 4.4', 'dinofolio' ),
					'items_list'               => esc_html_x( 'Portfolio Items list', 'Screen reader text for the items list heading on the post type listing screen. Default "Posts list". Added in 4.4', 'dinofolio' ),
					'items_list_navigation'    => esc_html_x( 'Portfolio Items list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default "Posts list navigation". Added in 4.4', 'dinofolio' ),
					'item_published'           => esc_html__( 'Portfolio Post published.', 'dinofolio' ),
					'item_published_privately' => esc_html__( 'Portfolio Post published privately.', 'dinofolio' ),
					'item_reverted_to_draft'   => esc_html__( 'Portfolio Post reverted to draft.', 'dinofolio' ),
					'item_scheduled'           => esc_html__( 'Portfolio Post scheduled.', 'dinofolio' ),
					'item_updated'             => esc_html__( 'Portfolio Post updated.', 'dinofolio' ),
					'menu_name'                => esc_html_x( 'DinoFolio', 'Admin Menu text', 'dinofolio' ),
					'name'                     => esc_html_x( 'Portfolio', 'Post type general name', 'dinofolio' ),
					'name_admin_bar'           => esc_html_x( 'Portfolio Post', 'Add New on Toolbar', 'dinofolio' ),
					'new_item'                 => esc_html__( 'New Portfolio Post', 'dinofolio' ),
					'not_found'                => esc_html__( 'No portfolio posts found.', 'dinofolio' ),
					'not_found_in_trash'       => esc_html__( 'No portfolio posts found in Trash.', 'dinofolio' ),
					'parent_item_colon'        => esc_html__( 'Parent Portfolio Items:', 'dinofolio' ),
					'search_items'             => esc_html__( 'Search Portfolio Posts', 'dinofolio' ),
					'singular_name'            => esc_html_x( 'Portfolio Post', 'Post type singular name', 'dinofolio' ),
					'uploaded_to_this_item'    => esc_html_x( 'Uploaded to this portfolio item', 'Overrides the "Uploaded to this post" phrase (used when viewing media attached to a post). Added in 4.4', 'dinofolio' ),
					'view_item'                => esc_html__( 'View Portfolio Post', 'dinofolio' ),
					'view_items'               => esc_html__( 'View Portfolio Posts', 'dinofolio' )
				),
			'menu_icon' => 'data:image/svg+xml;base64,' . base64_encode( $menu_icon_svg ),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'taxonomies'          => array(
				'wpdino_portfolio_category',
				'wpdino_portfolio_tag',
			),
			/* The rewrite handles the URL structure. */
			'rewrite' => array(
				'slug'       => $portfolio_slug,
				'with_front' => false,
				'pages'      => true,
				'feeds'      => true,
				'ep_mask'    => EP_PERMALINK,
			),
			'show_in_rest'        => true,
			'supports'            => array( 'author', 'custom-fields', 'editor', 'excerpt', 'post-formats', 'revisions', 'thumbnail', 'title' ),
		) );

		// Add the portfolio categories taxonomy
		register_taxonomy(
			'wpdino_portfolio_category',
			'wpdino_portfolio',
			array(
			'description'        => esc_html__( 'Categories for portfolio items.', 'dinofolio' ),
			'hierarchical'       => true,
			'publicly_queryable' => true,
			'labels'             => array(
				'add_new_item'               => esc_html__( 'Add New Category', 'dinofolio' ),
				'add_or_remove_items'        => esc_html__( 'Add or remove categories', 'dinofolio' ),
				'all_items'                  => esc_html__( 'All Categories', 'dinofolio' ),
				'back_to_items'              => esc_html__( '&larr; Back to Categories', 'dinofolio' ),
				'choose_from_most_used'      => esc_html__( 'Choose from the most used categories', 'dinofolio' ),
				'edit_item'                  => esc_html__( 'Edit Category', 'dinofolio' ),
				'items_list'                 => esc_html__( 'Categories list', 'dinofolio' ),
				'items_list_navigation'      => esc_html__( 'Categories list navigation', 'dinofolio' ),
				'most_used'                  => esc_html_x( 'Most Used', 'categories', 'dinofolio' ),
				'name'                       => esc_html_x( 'Categories', 'taxonomy general name', 'dinofolio' ),
				'new_item_name'              => esc_html__( 'New Category Name', 'dinofolio' ),
				'no_terms'                   => esc_html__( 'No categories', 'dinofolio' ),
				'not_found'                  => esc_html__( 'No categories found.', 'dinofolio' ),
				'parent_item'                => esc_html__( 'Parent Category', 'dinofolio' ),
				'parent_item_colon'          => esc_html__( 'Parent Category:', 'dinofolio' ),
				'popular_items'              => esc_html__( 'Popular Categories', 'dinofolio' ),
				'search_items'               => esc_html__( 'Search Categories', 'dinofolio' ),
				'separate_items_with_commas' => esc_html__( 'Separate categories with commas', 'dinofolio' ),
				'singular_name'              => esc_html_x( 'Category', 'taxonomy singular name', 'dinofolio' ),
				'update_item'                => esc_html__( 'Update Category', 'dinofolio' ),
				'view_item'                  => esc_html__( 'View Category', 'dinofolio' )
			),
			'public'  => true,
			'rewrite' => array(
				'slug'       => $portfolio_tax_slug,
				'with_front' => false,
				'pages'      => true,
				'feeds'      => true,
				'ep_mask'    => EP_PERMALINK,
			),
			'show_in_nav_menus'  => true,
			'show_admin_column' => true,
			'show_in_rest'      => true
		) );

		register_taxonomy(
			'wpdino_portfolio_tag',
			'wpdino_portfolio',
			array(
				'description'        => esc_html__( 'Tags for portfolio items.', 'dinofolio' ),
				'hierarchical'       => false,
				'publicly_queryable' => true,
				'labels'             => array(
					'name'                       => esc_html_x( 'Tags', 'taxonomy general name', 'dinofolio' ),
					'singular_name'              => esc_html_x( 'Tag', 'taxonomy singular name', 'dinofolio' ),
					'search_items'               => esc_html__( 'Search Tags', 'dinofolio' ),
					'popular_items'              => esc_html__( 'Popular Tags', 'dinofolio' ),
					'all_items'                  => esc_html__( 'All Tags', 'dinofolio' ),
					'edit_item'                  => esc_html__( 'Edit Tag', 'dinofolio' ),
					'update_item'                => esc_html__( 'Update Tag', 'dinofolio' ),
					'add_new_item'               => esc_html__( 'Add New Tag', 'dinofolio' ),
					'new_item_name'              => esc_html__( 'New Tag Name', 'dinofolio' ),
					'separate_items_with_commas' => esc_html__( 'Separate tags with commas', 'dinofolio' ),
					'add_or_remove_items'        => esc_html__( 'Add or remove tags', 'dinofolio' ),
					'choose_from_most_used'      => esc_html__( 'Choose from the most used tags', 'dinofolio' ),
					'not_found'                  => esc_html__( 'No tags found.', 'dinofolio' ),
					'no_terms'                   => esc_html__( 'No tags', 'dinofolio' ),
					'items_list_navigation'      => esc_html__( 'Tags list navigation', 'dinofolio' ),
					'items_list'                 => esc_html__( 'Tags list', 'dinofolio' ),
					'back_to_items'              => esc_html__( '&larr; Back to Tags', 'dinofolio' ),
				),
				'public'             => true,
				'rewrite'            => array(
					'slug'       => $portfolio_slug . '-tag',
					'with_front' => false,
				),
				'show_in_nav_menus'  => true,
				'show_admin_column'  => true,
				'show_in_rest'       => true,
			)
		);
	}

	/**
	 * Add featured image column to posts list
	 *
	 * @since 1.0.0
	 * @param array $columns Existing columns
	 * @return array Modified columns
	 */
	public function add_featured_image_column( $columns ) {
		// Insert featured image column before date column
		$new_columns = array();
		foreach ( $columns as $key => $title ) {
			if ( $key === 'date' ) {
				$new_columns['featured_image'] = esc_html__( 'Image', 'dinofolio' );
			}
			$new_columns[ $key ] = $title;
		}
		return $new_columns;
	}

	/**
	 * Display content for featured image column
	 *
	 * @since 1.0.0
	 * @param string $column_name The column name
	 * @param int $post_id The post ID
	 */
	public function display_featured_image_column( $column_name, $post_id ) {
		if ( $column_name === 'featured_image' ) {
			$featured_image_id = get_post_thumbnail_id( $post_id );
			
			if ( $featured_image_id ) {
				$image = wp_get_attachment_image( 
					$featured_image_id, 
					array( 60, 60 ), 
					false, 
					array( 
						'style' => 'max-width: 60px; max-height: 60px; border-radius: 3px; cursor: pointer;',
						'title' => esc_attr__( 'Click to edit featured image', 'dinofolio' ),
						'data-post-id' => $post_id,
						'class' => 'wpdino-featured-image-thumbnail'
					) 
				);
				
                echo '<div class="wpdino-featured-image-wrapper" data-post-id="' . esc_attr( $post_id ) . '">';
				echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe HTML from wp_get_attachment_image().
				echo '<div class="wpdino-featured-image-actions">';
				echo '<a href="#" class="wpdino-change-featured-image" data-post-id="' . esc_attr( $post_id ) . '" title="' . esc_attr__( 'Change featured image', 'dinofolio' ) . '">📝</a>';
				echo '<a href="#" class="wpdino-remove-featured-image" data-post-id="' . esc_attr( $post_id ) . '" title="' . esc_attr__( 'Remove featured image', 'dinofolio' ) . '">🗑️</a>';
				echo '</div>';
				echo '</div>';
			} else {
				echo '<div class="wpdino-featured-image-wrapper wpdino-no-image" data-post-id="' . esc_attr( $post_id ) . '">';
				echo '<div class="wpdino-no-featured-image" style="width: 60px; height: 60px; background: #f0f0f1; border: 2px dashed #c3c4c7; display: flex; align-items: center; justify-content: center; border-radius: 3px; cursor: pointer; font-size: 24px;" title="' . esc_attr__( 'Click to add featured image', 'dinofolio' ) . '">📷</div>';
				echo '<div class="wpdino-featured-image-actions">';
				echo '<a href="#" class="wpdino-add-featured-image" data-post-id="' . esc_attr( $post_id ) . '" title="' . esc_attr__( 'Add featured image', 'dinofolio' ) . '">➕</a>';
				echo '</div>';
				echo '</div>';
			}
		}
	}

	/**
	 * Generate the HTML for the featured image cell (used by AJAX updates)
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 * @return string
	 */
	private function render_featured_image_cell_html( $post_id ) {
		ob_start();
		$featured_image_id = get_post_thumbnail_id( $post_id );
		if ( $featured_image_id ) {
			$image = wp_get_attachment_image(
				$featured_image_id,
				array( 60, 60 ),
				false,
				array(
					'style' => 'max-width: 60px; max-height: 60px; border-radius: 3px; cursor: pointer;',
					'title' => esc_attr__( 'Click to edit featured image', 'dinofolio' ),
					'data-post-id' => $post_id,
					'class' => 'wpdino-featured-image-thumbnail',
				)
			);
			?>
			<div class="wpdino-featured-image-wrapper" data-post-id="<?php echo esc_attr( $post_id ); ?>">
				<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div class="wpdino-featured-image-actions">
					<a href="#" class="wpdino-change-featured-image" data-post-id="<?php echo esc_attr( $post_id ); ?>" title="<?php echo esc_attr__( 'Change featured image', 'dinofolio' ); ?>">📝</a>
					<a href="#" class="wpdino-remove-featured-image" data-post-id="<?php echo esc_attr( $post_id ); ?>" title="<?php echo esc_attr__( 'Remove featured image', 'dinofolio' ); ?>">🗑️</a>
				</div>
			</div>
			<?php
		} else {
			?>
			<div class="wpdino-featured-image-wrapper wpdino-no-image" data-post-id="<?php echo esc_attr( $post_id ); ?>">
				<div class="wpdino-no-featured-image" style="width: 60px; height: 60px; background: #f0f0f1; border: 2px dashed #c3c4c7; display: flex; align-items: center; justify-content: center; border-radius: 3px; cursor: pointer; font-size: 24px;" title="<?php echo esc_attr__( 'Click to add featured image', 'dinofolio' ); ?>">📷</div>
				<div class="wpdino-featured-image-actions">
					<a href="#" class="wpdino-add-featured-image" data-post-id="<?php echo esc_attr( $post_id ); ?>" title="<?php echo esc_attr__( 'Add featured image', 'dinofolio' ); ?>">➕</a>
				</div>
			</div>
			<?php
		}
		return (string) ob_get_clean();
	}

	/**
	 * Add featured image field to bulk edit
	 *
	 * @since 1.0.0
	 * @param string $column_name The column name
	 * @param string $post_type The post type
	 */
	public function add_featured_image_to_bulk_edit( $column_name, $post_type ) {
		if ( $post_type !== 'wpdino_portfolio' || $column_name !== 'featured_image' ) {
			return;
		}
		?>
		<fieldset class="inline-edit-col-left">
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php esc_html_e( 'Image', 'dinofolio' ); ?></span>
					<select name="wpdino_bulk_featured_image_action">
						<option value=""><?php esc_html_e( '— No Change —', 'dinofolio' ); ?></option>
						<option value="remove"><?php esc_html_e( 'Remove Image', 'dinofolio' ); ?></option>
					</select>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * AJAX handler for saving featured image
	 *
	 * @since 1.0.0
	 */
	public function save_featured_image_ajax() {
		check_ajax_referer( 'wpdino_portfolio_featured_image', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action', 'dinofolio' ) );
		}

		$post_id  = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		$image_id = isset( $_POST['image_id'] ) ? absint( wp_unslash( $_POST['image_id'] ) ) : 0;
		$action   = isset( $_POST['action_type'] ) ? sanitize_key( wp_unslash( $_POST['action_type'] ) ) : '';

		if ( ! $post_id ) {
			wp_die( esc_html__( 'Invalid post ID', 'dinofolio' ) );
		}

		// Verify post exists and is the correct type
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'wpdino_portfolio' ) {
			wp_die( esc_html__( 'Invalid post', 'dinofolio' ) );
		}

		if ( $action === 'remove' ) {
			delete_post_thumbnail( $post_id );
			wp_send_json_success( array(
				'message' => esc_html__( 'Featured image removed', 'dinofolio' ),
				'cell_html' => $this->render_featured_image_cell_html( $post_id ),
			) );
		} elseif ( $action === 'set' && $image_id ) {
			if ( set_post_thumbnail( $post_id, $image_id ) ) {
				wp_send_json_success( array( 
					'message' => esc_html__( 'Featured image updated', 'dinofolio' ),
					'cell_html' => $this->render_featured_image_cell_html( $post_id ),
				) );
			} else {
				wp_send_json_error( array( 'message' => esc_html__( 'Failed to set featured image', 'dinofolio' ) ) );
			}
		}

		wp_die();
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @since 1.0.0
	 * @param string $hook The current admin page hook
	 */
	public function enqueue_admin_scripts( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen routing.
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';

		if ( 'edit.php' !== $hook || 'wpdino_portfolio' !== $post_type ) {
			return;
		}

		// Enqueue media scripts
		wp_enqueue_media();

		// Add inline CSS
		wp_add_inline_style( 'wp-admin', '
			.wpdino-featured-image-wrapper {
				position: relative;
				display: inline-block;
			}
			tr:hover .wpdino-featured-image-wrapper .wpdino-featured-image-actions {
				opacity: 1;
			}
			.wpdino-featured-image-actions {
				position: absolute;
				top: -5px;
				right: -5px;
				background: rgba(0,0,0,0.8);
				border-radius: 3px;
				padding: 2px;
				opacity: 0;
				transition: opacity 0.2s;
			}
			.wpdino-featured-image-actions a {
				display: inline-block;
				margin: 0 2px;
				text-decoration: none;
				font-size: 12px;
				line-height: 1;
			}
			.wpdino-no-image .wpdino-featured-image-actions {
				top: 5px;
				right: 5px;
				background: white;
			}
			.wpdino-quick-edit-featured-image img {
				border: 1px solid #ddd;
				border-radius: 3px;
			}
			.column-featured_image {
				width: 80px;
			}
		' );

        // Add inline JavaScript only for column actions (Quick Edit integration removed)
        wp_add_inline_script( 'jquery', '
            jQuery(document).ready(function($) {
                // Add/Change featured image from the column
                $(document).on("click", ".wpdino-add-featured-image, .wpdino-change-featured-image", function(e) {
                    e.preventDefault();
                    var postId = $(this).data("post-id");
                    var mediaFrame = wp.media({
                        title: "' . esc_js( __( 'Select Featured Image', 'dinofolio' ) ) . '",
                        button: { text: "' . esc_js( __( 'Set Featured Image', 'dinofolio' ) ) . '" },
                        multiple: false
                    });
                    mediaFrame.on("select", function() {
                        var attachment = mediaFrame.state().get("selection").first().toJSON();
                        $.post(ajaxurl, {
                            action: "wpdino_portfolio_save_featured_image",
                            post_id: postId,
                            image_id: attachment.id,
                            action_type: "set",
                            nonce: "' . wp_create_nonce( 'wpdino_portfolio_featured_image' ) . '"
                        }, function(response) {
                            if (response.success && response.data && response.data.cell_html) {
                                var $cell = $(e.target).closest("td");
                                $cell.html(response.data.cell_html);
                            } else {
                                alert("' . esc_js( __( 'Error updating featured image', 'dinofolio' ) ) . '");
                            }
                        });
                    });
                    mediaFrame.open();
                });

                // Remove featured image from the column
                $(document).on("click", ".wpdino-remove-featured-image", function(e) {
                    e.preventDefault();
                    var postId = $(this).data("post-id");
                    if (!confirm("' . esc_js( __( 'Are you sure you want to remove the featured image?', 'dinofolio' ) ) . '")) { return; }
                    $.post(ajaxurl, {
                        action: "wpdino_portfolio_save_featured_image",
                        post_id: postId,
                        action_type: "remove",
                        nonce: "' . wp_create_nonce( 'wpdino_portfolio_featured_image' ) . '"
                    }, function(response) {
                        if (response.success && response.data && response.data.cell_html) {
                            var $cell = $(e.target).closest("td");
                            $cell.html(response.data.cell_html);
                        } else {
                            alert("' . esc_js( __( 'Error removing featured image', 'dinofolio' ) ) . '");
                        }
                    });
                });
            });
        ' );
	}

    /**
     * Save Quick Edit featured image changes on server side as a fallback
     *
     * @since 1.0.0
     * @param int     $post_id Post ID
     * @param WP_Post $post    Post object
     * @return void
     */
    public function save_quick_edit_featured_image( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( $post->post_type !== 'wpdino_portfolio' ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( ! isset( $_POST['_inline_edit'] ) ) {
            return;
        }

        $inline_edit_nonce = sanitize_text_field( wp_unslash( $_POST['_inline_edit'] ) );

        if ( ! wp_verify_nonce( $inline_edit_nonce, 'inlineeditnonce' ) ) {
            return;
        }

        if ( ! isset( $_POST['wpdino_featured_image_changed'] ) ) {
            return;
        }

        $featured_image_changed = sanitize_text_field( wp_unslash( $_POST['wpdino_featured_image_changed'] ) );

        if ( '1' !== $featured_image_changed ) {
            return;
        }

        $image_id = isset( $_POST['wpdino_featured_image_id'] ) ? absint( wp_unslash( $_POST['wpdino_featured_image_id'] ) ) : 0;

        if ( $image_id > 0 ) {
            set_post_thumbnail( $post_id, $image_id );
        } else {
            delete_post_thumbnail( $post_id );
        }
    }

    /**
     * AJAX: Get current featured image data for a post (used by Quick Edit)
     *
     * @since 1.0.0
     */
    public function get_featured_image_ajax() {
        check_ajax_referer( 'wpdino_portfolio_featured_image', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action', 'dinofolio' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
        if ( ! $post_id ) {
            wp_die( esc_html__( 'Invalid post ID', 'dinofolio' ) );
        }

        $image_id = get_post_thumbnail_id( $post_id );
        if ( $image_id ) {
            $src = wp_get_attachment_image_src( $image_id, array( 100, 100 ) );
            wp_send_json_success( array(
                'image_id' => $image_id,
                'thumbnail_url' => $src ? $src[0] : '',
            ) );
        } else {
            wp_send_json_success( array(
                'image_id' => 0,
                'thumbnail_url' => '',
            ) );
        }
    }
	
}

Custom_Post::instance();