<?php
/**
 * DinoPack Utils
 *
 * @package DinoPack
 * @since 1.0.0
 */

namespace DinoPack\Utils;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * DinoPack Utils Class
 *
 * @since 1.0.0
 */
class DinoPack_Utils {

    /**
     * Initialize the utils class
     *
     * @since 1.0.0
     */
    public static function init() {
        // Image sizes are now registered in the main plugin file
        // This method is kept for future utility initialization
    }


    /**
     * Get image aspect ratio
     *
     * @since 1.0.0
     * @param int $attachment_id Attachment ID
     * @return float|false Aspect ratio or false if not found
     */
    public static function get_image_aspect_ratio($attachment_id) {
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        if (!$metadata || !isset($metadata['width']) || !isset($metadata['height'])) {
            return false;
        }
        
        if ($metadata['height'] == 0) {
            return false;
        }
        
        return $metadata['width'] / $metadata['height'];
    }

    /**
     * Check if image is landscape, portrait, or square
     *
     * @since 1.0.0
     * @param int $attachment_id Attachment ID
     * @return string 'landscape', 'portrait', or 'square'
     */
    public static function get_image_orientation($attachment_id) {
        $aspect_ratio = self::get_image_aspect_ratio($attachment_id);
        
        if ($aspect_ratio === false) {
            return 'unknown';
        }
        
        if ($aspect_ratio > 1.1) {
            return 'landscape';
        } elseif ($aspect_ratio < 0.9) {
            return 'portrait';
        } else {
            return 'square';
        }
    }
}

// Initialize the utils class
DinoPack_Utils::init(); 