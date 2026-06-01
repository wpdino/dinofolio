jQuery(window).on('elementor/frontend/init', () => {
    const addHandler = ($element) => {
        dinopackInitBlogWidget($element);
    };

    // Check if elementorFrontend is available before using it
    if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
        elementorFrontend.hooks.addAction('frontend/element_ready/dinopopack-blog.default', addHandler);
    }
});

function dinopackInitBlogWidget($element) {
    const $container = $element.find('.dinopack-blog-container');
    
    // Function to update layout classes
    const updateLayout = () => {
        const layout = $element.find('.elementor-control-layout select').val();
        const columns = $element.find('.elementor-control-columns select').val();
        
        $container.removeClass('dinopack-blog-grid dinopack-blog-cards dinopack-blog-timeline');
        $container.removeClass((index, className) => {
            return (className.match(/(^|\s)dinopack-blog-columns-\S+/g) || []).join(' ');
        });
        
        if (layout === 'grid' || layout === 'masonry') {
            $container.addClass('dinopack-blog-grid');
            $container.addClass('dinopack-blog-columns-' + columns);
            $container.css('grid-template-columns', `repeat(${columns}, 1fr)`);
        } else if (layout === 'cards') {
            $container.addClass('dinopack-blog-cards');
            $container.addClass('dinopack-blog-columns-' + columns);
            $container.css('grid-template-columns', `repeat(${columns}, 1fr)`);
        } else if (layout === 'timeline') {
            $container.addClass('dinopack-blog-timeline');
            $container.css('grid-template-columns', '');
        }
    };

    // Initial layout update
    updateLayout();

    // Listen for layout and column changes
    $element.find('.elementor-control-layout select, .elementor-control-columns select').on('change', updateLayout);
} 