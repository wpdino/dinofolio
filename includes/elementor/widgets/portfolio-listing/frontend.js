(function($) {
    'use strict';

    var DinopackBlogLoadMore = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.dinopack-blog-load-more .load-more-btn', this.handleLoadMore);
        },

        handleLoadMore: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $container = $button.closest('.elementor-widget-dinopack-blog');
            var $blogContainer = $container.find('.dinopack-blog-container');
            
            // Get data attributes
            var currentPage = parseInt($button.data('page'));
            var maxPages = parseInt($button.data('max-pages'));
            var postsPerPage = parseInt($button.data('posts-per-page'));
            var postType = $button.data('post-type');
            var orderBy = $button.data('order-by');
            var order = $button.data('order');
            
            // Get read more settings from widget
            var readMoreText = $button.data('read-more-text') || 'Read More';
            var readMoreIcon = $button.data('read-more-icon') || '';
            var readMoreIconPosition = $button.data('read-more-icon-position') || 'after';
            
            // Get excerpt length
            var excerptLength = parseInt($button.data('excerpt-length')) || 20;
            
            // Get content display settings
            var showImage = $button.data('show-image') || 'yes';
            var showTitle = $button.data('show-title') || 'yes';
            var showMeta = $button.data('show-meta') || 'yes';
            var showExcerpt = $button.data('show-excerpt') || 'yes';
            var showReadMore = $button.data('show-read-more') || 'yes';
            var metaData = $button.data('meta-data') || ['date', 'author'];
            
            // Check if we have more pages
            if (currentPage >= maxPages) {
                return;
            }
            
            // Show loading state
            $button.addClass('loading').prop('disabled', true);
            
            // AJAX request
            $.ajax({
                url: dinopack_blog_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dinopack_blog_load_more',
                    page: currentPage + 1,
                    posts_per_page: postsPerPage,
                    post_type: postType,
                    order_by: orderBy,
                    order: order,
                    read_more_text: readMoreText,
                    read_more_icon: readMoreIcon,
                    read_more_icon_position: readMoreIconPosition,
                    excerpt_length: excerptLength,
                    show_image: showImage,
                    show_title: showTitle,
                    show_meta: showMeta,
                    show_excerpt: showExcerpt,
                    show_read_more: showReadMore,
                    meta_data: metaData,
                    nonce: dinopack_blog_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        // Create temporary container for new posts
                        var $tempContainer = $('<div>').html(response.data.html);
                        var $newPosts = $tempContainer.find('.dinopack-blog-item');
                        
                        // Add animation class to new posts
                        $newPosts.addClass('ajax-loaded');
                        
                        // Append new posts
                        $blogContainer.append($newPosts);
                        
                        // For masonry layout, we need to ensure proper positioning
                        if ($blogContainer.hasClass('dinopack-blog-masonry')) {
                            // Force reflow for masonry
                            $blogContainer[0].offsetHeight;
                        }
                        
                        // Trigger animation after a short delay
                        setTimeout(function() {
                            $newPosts.addClass('animate-in');
                        }, 50);
                        
                        // Update button data
                        $button.data('page', currentPage + 1);
                        
                        // Hide button if no more pages
                        if (currentPage + 1 >= maxPages) {
                            $button.hide();
                        }
                        
                        // Trigger custom event for other scripts
                        $container.trigger('dinopack:blog:loaded', [response.data]);
                    } else {
                        console.error('Load more failed:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                },
                complete: function() {
                    // Remove loading state
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        DinopackBlogLoadMore.init();
    });

    // Also initialize for Elementor editor with proper error handling
    $(document).ready(function() {
        // Wait for Elementor to be available
        function waitForElementor() {
            if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
                elementorFrontend.hooks.addAction('frontend/element_ready/dinopack-blog.default', function($scope) {
                    DinopackBlogLoadMore.init();
                });
            } else {
                // Retry after a short delay if Elementor isn't ready yet
                setTimeout(waitForElementor, 100);
            }
        }
        
        waitForElementor();
    });

})(jQuery);