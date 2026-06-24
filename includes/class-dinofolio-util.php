<?php
/**
 * Shared integration utilities.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Converts unified component params into builder-specific formats.
 */
class Util {

	/**
	 * Map param type to Elementor control type.
	 *
	 * @param string $type Param type.
	 * @return string
	 */
	public static function map_control_type( $type ) {
		if ( ! class_exists( Controls_Manager::class ) ) {
			return 'text';
		}

		$mapping = array(
			'textfield'   => Controls_Manager::TEXT,
			'textarea'    => Controls_Manager::TEXTAREA,
			'dropdown'    => Controls_Manager::SELECT,
			'multiselect' => Controls_Manager::SELECT2,
			'taxonomy'    => Controls_Manager::SELECT2,
			'colorpicker' => Controls_Manager::COLOR,
			'number'      => Controls_Manager::NUMBER,
			'checkbox'    => Controls_Manager::SWITCHER,
		);

		return isset( $mapping[ $type ] ) ? $mapping[ $type ] : Controls_Manager::TEXT;
	}

	/**
	 * Map param type to WPBakery field type.
	 *
	 * @param string $type Param type.
	 * @return string
	 */
	public static function map_vc_type( $type ) {
		$mapping = array(
			'textfield'   => 'textfield',
			'textarea'    => 'textarea',
			'dropdown'    => 'dropdown',
			'multiselect' => 'autocomplete',
			'taxonomy'    => 'autocomplete',
			'colorpicker' => 'colorpicker',
			'number'      => 'textfield',
			'checkbox'    => 'checkbox',
		);

		return isset( $mapping[ $type ] ) ? $mapping[ $type ] : 'textfield';
	}

	/**
	 * Map param type to Gutenberg attribute type.
	 *
	 * @param string $type Param type.
	 * @return string
	 */
	public static function map_block_attribute_type( $type ) {
		$mapping = array(
			'number'      => 'number',
			'checkbox'    => 'boolean',
			'multiselect' => 'array',
			'taxonomy'    => 'array',
		);

		return isset( $mapping[ $type ] ) ? $mapping[ $type ] : 'string';
	}

	/**
	 * Build Elementor control args from a unified param definition.
	 *
	 * @param array $param Param definition.
	 * @return array|null
	 */
	public static function prepare_control_args( $param ) {
		$type       = isset( $param['type'] ) ? $param['type'] : 'textfield';
		$param_name = isset( $param['param_name'] ) ? $param['param_name'] : '';

		if ( empty( $param_name ) ) {
			return null;
		}

		$heading     = isset( $param['heading'] ) ? $param['heading'] : '';
		$value       = isset( $param['value'] ) ? $param['value'] : '';
		$description = isset( $param['description'] ) ? $param['description'] : '';

		if ( 'taxonomy' === $type && empty( $value ) && ! empty( $param['taxonomy'] ) ) {
			$value = self::get_taxonomy_term_options( $param['taxonomy'] );
		}

		$default_value = '';
		if ( isset( $param['std'] ) ) {
			$default_value = $param['std'];
		} elseif ( isset( $param['default'] ) ) {
			$default_value = $param['default'];
		} elseif ( ! is_array( $value ) && '' !== $value ) {
			$default_value = $value;
		}

		$control_args = array(
			'label'       => $heading,
			'type'        => self::map_control_type( $type ),
			'description' => $description,
		);

		if ( 'checkbox' === $type ) {
			$control_args['label_on']     = esc_html__( 'Yes', 'dinofolio' );
			$control_args['label_off']    = esc_html__( 'No', 'dinofolio' );
			$control_args['return_value'] = 'yes';

			if ( true === $default_value || '1' === $default_value || 'yes' === strtolower( (string) $default_value ) || 'true' === strtolower( (string) $default_value ) ) {
				$control_args['default'] = 'yes';
			} else {
				$control_args['default'] = 'no';
			}
		} else {
			if ( '' !== $default_value && null !== $default_value ) {
				$control_args['default'] = $default_value;
			}

			if ( 'dropdown' === $type && is_array( $value ) ) {
				$control_args['options'] = $value;
			}

			if ( 'multiselect' === $type && is_array( $value ) ) {
				$control_args['options']  = $value;
				$control_args['multiple'] = true;
				$control_args['label_block'] = true;
			}

			if ( 'taxonomy' === $type && is_array( $value ) ) {
				$control_args['options']  = $value;
				$control_args['multiple'] = true;
				$control_args['label_block'] = true;
			}

			if ( 'number' === $type ) {
				if ( isset( $param['min'] ) ) {
					$control_args['min'] = $param['min'];
				}
				if ( isset( $param['max'] ) ) {
					$control_args['max'] = $param['max'];
				}
			}
		}

		if ( ! empty( $param['dependency']['element'] ) && isset( $param['dependency']['value'] ) ) {
			$dependency_value = $param['dependency']['value'];
			$control_args['condition'] = array(
				$param['dependency']['element'] => is_array( $dependency_value ) ? $dependency_value : $dependency_value,
			);
		}

		return array(
			'name' => $param_name,
			'args' => $control_args,
		);
	}

	/**
	 * Build WPBakery param from unified param definition.
	 *
	 * @param array $param    Param definition.
	 * @param array $sections Optional section labels keyed by slug.
	 * @return array
	 */
	public static function prepare_vc_param( $param, $sections = array() ) {
		$type = isset( $param['type'] ) ? $param['type'] : 'textfield';

		$canonical_name = isset( $param['param_name'] ) ? (string) $param['param_name'] : '';

		$vc_param = array(
			'type'        => self::map_vc_type( $type ),
			'heading'     => isset( $param['heading'] ) ? $param['heading'] : '',
			'param_name'  => self::get_vc_param_name( $canonical_name ),
			'description' => isset( $param['description'] ) ? $param['description'] : '',
		);

		if ( empty( $vc_param['param_name'] ) ) {
			return array();
		}

		if ( ! empty( $param['section'] ) && ! empty( $sections[ $param['section'] ] ) ) {
			$vc_param['group'] = $sections[ $param['section'] ];
		}

		if ( 'checkbox' === $type ) {
			$vc_param['value']       = array( esc_html__( 'Yes', 'dinofolio' ) => 'yes' );
			$vc_param['save_always'] = true;
		}

		if ( 'dropdown' === $type ) {
			$vc_param['save_always'] = true;
		}

		if ( 'number' === $type ) {
			$vc_param['save_always'] = true;

			if ( isset( $param['std'] ) && is_numeric( $param['std'] ) ) {
				$vc_param['std'] = (string) (int) $param['std'];
			}

			$vc_param['description'] = self::append_number_range_description(
				$vc_param['description'],
				$param
			);
		}

		if ( isset( $param['std'] ) && 'number' !== $type ) {
			if ( is_array( $param['std'] ) ) {
				$vc_param['std'] = '';
			} elseif ( 'checkbox' === $type ) {
				$vc_param['std'] = in_array( $param['std'], array( true, 'yes', 'true', '1', 1 ), true ) ? 'yes' : '';
			} elseif ( 'dropdown' === $type && is_numeric( $param['std'] ) ) {
				$vc_param['std'] = (string) $param['std'];
			} else {
				$vc_param['std'] = $param['std'];
			}
		} elseif ( isset( $param['value'] ) && ! is_array( $param['value'] ) ) {
			$vc_param['value'] = $param['value'];
		}

		if ( 'dropdown' === $type && isset( $param['value'] ) && is_array( $param['value'] ) ) {
			$vc_param['value'] = array();
			foreach ( $param['value'] as $slug => $label ) {
				$vc_param['value'][ $label ] = $slug;
			}
		}

		if ( in_array( $type, array( 'multiselect', 'taxonomy' ), true ) ) {
			$vc_param['settings'] = array(
				'multiple'       => true,
				'min_length'     => 0,
				'unique_values'  => true,
				'display_inline' => true,
				'delay'          => 300,
			);
		}

		if ( ! empty( $param['dependency']['element'] ) && isset( $param['dependency']['value'] ) ) {
			$dependency_value = $param['dependency']['value'];
			$dependency_value = is_array( $dependency_value ) ? $dependency_value : array( $dependency_value );
			$normalized       = array();

			foreach ( $dependency_value as $dep_val ) {
				if ( true === $dep_val || 'yes' === $dep_val || 'true' === $dep_val || '1' === $dep_val || 1 === $dep_val ) {
					$normalized[] = 'yes';
					continue;
				}

				$normalized[] = (string) $dep_val;
			}

			$vc_param['dependency'] = array(
				'element' => self::get_vc_param_name( (string) $param['dependency']['element'] ),
				'value'   => array_values( array_unique( $normalized ) ),
			);
		}

		return $vc_param;
	}

	/**
	 * WPBakery param_name for a canonical component attribute.
	 *
	 * WPBakery + WordPress lowercases shortcode keys, so camelCase param names
	 * produce duplicate attributes on save. Snake_case avoids collisions.
	 *
	 * @param string $canonical_name Component param name.
	 * @return string
	 */
	public static function get_vc_param_name( $canonical_name ) {
		$canonical_name = (string) $canonical_name;

		if ( '' === $canonical_name ) {
			return '';
		}

		return self::camel_to_snake( $canonical_name );
	}

	/**
	 * Append a min/max hint to a number field description.
	 *
	 * @param string $description Existing description.
	 * @param array  $param       Param definition.
	 * @return string
	 */
	public static function append_number_range_description( $description, $param ) {
		$has_min = isset( $param['min'] ) && is_numeric( $param['min'] );
		$has_max = isset( $param['max'] ) && is_numeric( $param['max'] );

		if ( ! $has_min && ! $has_max ) {
			return (string) $description;
		}

		if ( $has_min && $has_max ) {
			$range = sprintf(
				/* translators: 1: minimum allowed value, 2: maximum allowed value */
				esc_html__( 'Allowed range: %1$d–%2$d.', 'dinofolio' ),
				(int) $param['min'],
				(int) $param['max']
			);
		} elseif ( $has_min ) {
			$range = sprintf(
				/* translators: %d: minimum allowed value */
				esc_html__( 'Minimum: %d.', 'dinofolio' ),
				(int) $param['min']
			);
		} else {
			$range = sprintf(
				/* translators: %d: maximum allowed value */
				esc_html__( 'Maximum: %d.', 'dinofolio' ),
				(int) $param['max']
			);
		}

		return trim( (string) $description . ' ' . $range );
	}

	/**
	 * Build Gutenberg attributes from unified params.
	 *
	 * @param array $params   Param definitions.
	 * @param array $defaults Component defaults.
	 * @return array
	 */
	public static function params_to_block_attributes( $params, $defaults = array() ) {
		$attributes = array();

		foreach ( (array) $params as $param ) {
			$param_name = isset( $param['param_name'] ) ? $param['param_name'] : '';
			$type       = isset( $param['type'] ) ? $param['type'] : 'textfield';

			if ( empty( $param_name ) ) {
				continue;
			}

			$default = isset( $defaults[ $param_name ] ) ? $defaults[ $param_name ] : '';
			if ( isset( $param['std'] ) ) {
				$default = $param['std'];
			}

			if ( 'checkbox' === $type ) {
				if ( isset( $defaults[ $param_name ] ) ) {
					$default = self::to_bool( $defaults[ $param_name ] );
				} else {
					$default = self::to_bool( $default );
				}
			}

			if ( 'multiselect' === $type || 'taxonomy' === $type ) {
				$default = isset( $defaults[ $param_name ] ) && is_array( $defaults[ $param_name ] )
					? self::sanitize_taxonomy_term_ids( $defaults[ $param_name ] )
					: array();
			}

			$attribute = array(
				'type'    => self::map_block_attribute_type( $type ),
				'default' => $default,
			);

			if ( 'number' === $attribute['type'] ) {
				$attribute['default'] = is_numeric( $default ) ? (int) $default : 0;
			}

			if ( 'string' === $attribute['type'] && 'dropdown' === $type ) {
				$attribute['default'] = (string) $default;
			}

			if ( 'array' === $attribute['type'] ) {
				$attribute['default'] = is_array( $default ) ? $default : array();
			}

			$attributes[ $param_name ] = $attribute;
		}

		return $attributes;
	}

	/**
	 * Build Gutenberg editor control definitions from unified params.
	 *
	 * @param array $params   Param definitions.
	 * @param array $defaults Component defaults.
	 * @return array
	 */
	public static function params_to_editor_controls( $params, $defaults = array() ) {
		$controls = array();

		foreach ( (array) $params as $param ) {
			$name = isset( $param['param_name'] ) ? $param['param_name'] : '';
			$type = isset( $param['type'] ) ? $param['type'] : 'textfield';

			if ( empty( $name ) ) {
				continue;
			}

			$default = isset( $defaults[ $name ] ) ? $defaults[ $name ] : '';
			if ( isset( $param['std'] ) ) {
				$default = $param['std'];
			}

			$control = array(
				'name'        => $name,
				'label'       => isset( $param['heading'] ) ? $param['heading'] : '',
				'type'        => $type,
				'section'     => isset( $param['section'] ) ? $param['section'] : 'content',
				'description' => isset( $param['description'] ) ? $param['description'] : '',
			);

			if ( 'checkbox' === $type ) {
				$control['default'] = self::to_bool( $default );
			} elseif ( 'number' === $type ) {
				$control['default'] = is_numeric( $default ) ? (int) $default : 0;
				if ( isset( $param['min'] ) ) {
					$control['min'] = (int) $param['min'];
				}
				if ( isset( $param['max'] ) ) {
					$control['max'] = (int) $param['max'];
				}
			} elseif ( 'multiselect' === $type || 'taxonomy' === $type ) {
				$control['default'] = is_array( $default ) ? self::sanitize_taxonomy_term_ids( $default ) : array();
			} else {
				$control['default'] = (string) $default;
			}

			if ( ( 'dropdown' === $type || 'multiselect' === $type ) && isset( $param['value'] ) && is_array( $param['value'] ) ) {
				$options = array();
				foreach ( $param['value'] as $value => $label ) {
					$options[] = array(
						'label' => $label,
						'value' => $value,
					);
				}
				$control['options'] = $options;
			}

			if ( 'taxonomy' === $type ) {
				$control['taxonomy']     = isset( $param['taxonomy'] ) ? $param['taxonomy'] : '';
				$control['hierarchical'] = ! empty( $param['hierarchical'] );
			}

			$controls[] = $control;
		}

		return $controls;
	}

	/**
	 * Whitelist and cast block attributes to match the registered schema.
	 *
	 * @param array $attributes Raw block attributes.
	 * @param array $schema     Block attribute schema from get_block_attributes().
	 * @return array
	 */
	public static function sanitize_block_attributes( $attributes, $schema ) {
		$sanitized = array();

		if ( ! is_array( $attributes ) || ! is_array( $schema ) ) {
			return $sanitized;
		}

		foreach ( $schema as $key => $definition ) {
			$type    = isset( $definition['type'] ) ? $definition['type'] : 'string';
			$default = isset( $definition['default'] ) ? $definition['default'] : ( 'boolean' === $type ? false : ( 'array' === $type ? array() : ( 'number' === $type ? 0 : '' ) ) );

			if ( ! array_key_exists( $key, $attributes ) || null === $attributes[ $key ] ) {
				$sanitized[ $key ] = $default;
				continue;
			}

			$value = $attributes[ $key ];

			switch ( $type ) {
				case 'boolean':
					$sanitized[ $key ] = self::to_bool( $value );
					break;
				case 'array':
					$sanitized[ $key ] = self::sanitize_taxonomy_term_ids( $value );
					break;
				case 'number':
				case 'integer':
					$sanitized[ $key ] = is_numeric( $value ) ? (int) $value : (int) $default;
					break;
				default:
					$sanitized[ $key ] = (string) $value;
					break;
			}
		}

		return $sanitized;
	}

	/**
	 * Normalize integration settings for component render().
	 *
	 * @param array           $atts      Raw attributes.
	 * @param Component_Base  $component Component instance.
	 * @param string          $source    Integration source.
	 * @return array
	 */
	public static function normalize_atts( $atts, Component_Base $component, $source = 'generic' ) {
		$atts       = is_array( $atts ) ? $atts : array();
		$params     = $component->get_params();
		$atts       = self::normalize_attribute_keys( $atts, $params );
		$raw_atts   = $atts;
		$defaults   = $component->get_defaults();
		$has_values = ! empty( $raw_atts );

		foreach ( $params as $param ) {
			$param_name = isset( $param['param_name'] ) ? $param['param_name'] : '';
			$type       = isset( $param['type'] ) ? $param['type'] : '';

			if ( empty( $param_name ) ) {
				continue;
			}

			if ( 'dropdown' === $type && isset( $atts[ $param_name ] ) && ! empty( $param['value'] ) && is_array( $param['value'] ) ) {
				$atts[ $param_name ] = self::resolve_dropdown_value( $atts[ $param_name ], $param['value'] );
			}

			if ( 'checkbox' === $type ) {
				if ( ! array_key_exists( $param_name, $raw_atts ) ) {
					if ( 'wpbakery' === $source || $has_values ) {
						$atts[ $param_name ] = false;
					} elseif ( isset( $defaults[ $param_name ] ) ) {
						$atts[ $param_name ] = self::to_bool( $defaults[ $param_name ] );
					} else {
						$atts[ $param_name ] = false;
					}
					continue;
				}

				$atts[ $param_name ] = self::to_bool( $atts[ $param_name ] );
				continue;
			}

			if ( 'number' === $type ) {
				$value = array_key_exists( $param_name, $atts ) ? $atts[ $param_name ] : null;

				if ( null === $value || '' === $value || ! is_numeric( $value ) ) {
					$atts[ $param_name ] = isset( $defaults[ $param_name ] ) ? (int) $defaults[ $param_name ] : 0;
				} else {
					$atts[ $param_name ] = (int) $value;
				}

				if ( isset( $param['min'] ) && is_numeric( $param['min'] ) ) {
					$atts[ $param_name ] = max( (int) $param['min'], $atts[ $param_name ] );
				}

				if ( isset( $param['max'] ) && is_numeric( $param['max'] ) ) {
					$atts[ $param_name ] = min( (int) $param['max'], $atts[ $param_name ] );
				}

				continue;
			}

			if ( 'colorpicker' === $type && isset( $atts[ $param_name ] ) ) {
				$color = sanitize_hex_color( $atts[ $param_name ] );
				$atts[ $param_name ] = $color ? $color : '';
				continue;
			}

			if ( 'multiselect' === $type || 'taxonomy' === $type ) {
				if ( ! isset( $atts[ $param_name ] ) ) {
					$atts[ $param_name ] = isset( $defaults[ $param_name ] ) ? $defaults[ $param_name ] : array();
					continue;
				}

				$atts[ $param_name ] = self::sanitize_taxonomy_term_ids( $atts[ $param_name ] );
				continue;
			}
		}

		if ( 'elementor' === $source ) {
			foreach ( $atts as $key => $value ) {
				if ( 'yes' === $value ) {
					$atts[ $key ] = true;
				} elseif ( 'no' === $value ) {
					$atts[ $key ] = false;
				}
			}
		}

		$merged = 'wpbakery' === $source
			? array_merge( $defaults, $atts )
			: wp_parse_args( $atts, $defaults );

		return apply_filters(
			'dinofolio_normalize_component_atts',
			$merged,
			$atts,
			$component,
			$source
		);
	}

	/**
	 * Map parsed shortcode attributes to WPBakery vc_map param names.
	 *
	 * vc_map_get_attributes() only recognizes keys that match param_name in the map.
	 *
	 * @param array $atts   Raw shortcode attributes.
	 * @param array $params Component param definitions.
	 * @return array
	 */
	public static function map_atts_to_vc_param_names( $atts, $params ) {
		if ( ! is_array( $atts ) || empty( $atts ) ) {
			return array();
		}

		$lookup = self::build_param_name_lookup( $params );
		$mapped = array();

		foreach ( $atts as $key => $value ) {
			$canonical = self::resolve_canonical_param_name( (string) $key, $lookup );
			$vc_name   = self::get_vc_param_name( $canonical );

			if ( '' !== $vc_name ) {
				$mapped[ $vc_name ] = $value;
				continue;
			}

			$mapped[ (string) $key ] = $value;
		}

		return $mapped;
	}

	/**
	 * Map lowercased or snake_case attribute keys to canonical param names.
	 *
	 * WordPress lowercases shortcode attribute names, so camelCase params such as
	 * paginationMode and showTitle must be restored before reading saved values.
	 *
	 * @param array $atts   Raw attributes.
	 * @param array $params Component param definitions.
	 * @return array
	 */
	public static function normalize_attribute_keys( $atts, $params ) {
		if ( ! is_array( $atts ) || empty( $atts ) ) {
			return array();
		}

		$lookup = self::build_param_name_lookup( $params );
		$normalized = array();

		foreach ( $atts as $key => $value ) {
			$canonical = self::resolve_canonical_param_name( (string) $key, $lookup );

			if ( ! array_key_exists( $canonical, $normalized ) ) {
				$normalized[ $canonical ] = $value;
			}
		}

		return $normalized;
	}

	/**
	 * Build a lookup table for canonical param names.
	 *
	 * @param array $params Component param definitions.
	 * @return array
	 */
	public static function build_param_name_lookup( $params ) {
		$lookup = array();

		foreach ( (array) $params as $param ) {
			$name = isset( $param['param_name'] ) ? (string) $param['param_name'] : '';

			if ( '' === $name ) {
				continue;
			}

			$lookup[ $name ]                         = $name;
			$lookup[ strtolower( $name ) ]           = $name;
			$lookup[ self::camel_to_snake( $name ) ] = $name;
			$lookup[ self::get_vc_param_name( $name ) ] = $name;
		}

		return $lookup;
	}

	/**
	 * Resolve any attribute alias to the canonical param name.
	 *
	 * @param string $name   Raw attribute name.
	 * @param array  $lookup Lookup from build_param_name_lookup().
	 * @return string
	 */
	public static function resolve_canonical_param_name( $name, $lookup ) {
		$name = (string) $name;

		if ( isset( $lookup[ $name ] ) ) {
			return $lookup[ $name ];
		}

		if ( isset( $lookup[ strtolower( $name ) ] ) ) {
			return $lookup[ strtolower( $name ) ];
		}

		$snake = self::camel_to_snake( $name );

		if ( isset( $lookup[ $snake ] ) ) {
			return $lookup[ $snake ];
		}

		return $name;
	}

	/**
	 * Parse shortcode attribute pairs from a raw attribute string.
	 *
	 * Preserves declaration order so the first value wins when deduplicating.
	 *
	 * @param string $attr_string Attribute string from inside a shortcode tag.
	 * @return array<int, array{name:string, value:string}>
	 */
	public static function parse_shortcode_attribute_pairs( $attr_string ) {
		$pairs       = array();
		$attr_string = self::normalize_shortcode_attribute_string( $attr_string );

		if ( '' === $attr_string ) {
			return $pairs;
		}

		$pattern = '/([a-zA-Z0-9_\-]+)\s*=\s*(?:"((?:[^"\\\\]|\\\\.)*)"|\'((?:[^\'\\\\]|\\\\.)*)\')/';

		if ( preg_match_all( $pattern, $attr_string, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$value = '' !== $match[2] ? $match[2] : ( isset( $match[3] ) ? $match[3] : '' );

				$pairs[] = array(
					'name'  => $match[1],
					'value' => stripcslashes( $value ),
				);
			}
		}

		return $pairs;
	}

	/**
	 * Normalize a shortcode attribute string before parsing.
	 *
	 * @param string $attr_string Raw attribute string.
	 * @return string
	 */
	public static function normalize_shortcode_attribute_string( $attr_string ) {
		$attr_string = trim( wp_unslash( (string) $attr_string ) );

		if ( '' === $attr_string ) {
			return '';
		}

		return html_entity_decode( $attr_string, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Whether ordered shortcode attributes contain duplicate canonical params.
	 *
	 * @param array $pairs  Ordered attribute pairs.
	 * @param array $params Component param definitions.
	 * @return bool
	 */
	public static function shortcode_attribute_pairs_have_duplicates( $pairs, $params ) {
		$lookup = self::build_param_name_lookup( $params );
		$seen   = array();

		foreach ( (array) $pairs as $pair ) {
			if ( empty( $pair['name'] ) ) {
				continue;
			}

			$canonical = self::resolve_canonical_param_name( $pair['name'], $lookup );

			if ( isset( $seen[ $canonical ] ) ) {
				return true;
			}

			$seen[ $canonical ] = true;
		}

		return false;
	}

	/**
	 * Deduplicate shortcode attributes, keeping the first declaration per param.
	 *
	 * @param array $pairs  Ordered attribute pairs.
	 * @param array $params Component param definitions.
	 * @return array<string, string>
	 */
	public static function dedupe_shortcode_attribute_pairs( $pairs, $params ) {
		$lookup  = self::build_param_name_lookup( $params );
		$deduped = array();

		foreach ( (array) $pairs as $pair ) {
			if ( empty( $pair['name'] ) ) {
				continue;
			}

			$canonical = self::resolve_canonical_param_name( $pair['name'], $lookup );

			if ( ! array_key_exists( $canonical, $deduped ) ) {
				$deduped[ $canonical ] = isset( $pair['value'] ) ? (string) $pair['value'] : '';
			}
		}

		return $deduped;
	}

	/**
	 * Rebuild a WPBakery portfolio shortcode tag with deduplicated snake_case attrs.
	 *
	 * @param string $tag         Shortcode base.
	 * @param string $attr_string Raw attribute string.
	 * @param array  $params      Component param definitions.
	 * @return string
	 */
	public static function rebuild_wpbakery_shortcode_tag( $tag, $attr_string, $params, $original = '' ) {
		$attr_string_raw = (string) $attr_string;
		$pairs           = self::parse_shortcode_attribute_pairs( $attr_string_raw );

		if ( '' !== $original ) {
			$fallback = $original;
		} else {
			$fallback = '[' . sanitize_key( (string) $tag ) . $attr_string_raw . ']';
		}

		// Never strip attributes when parsing fails on a non-empty attribute string.
		if ( empty( $pairs ) ) {
			return '' !== trim( $attr_string_raw ) ? $fallback : '[' . sanitize_key( (string) $tag ) . ']';
		}

		// Only rewrite shortcodes that still contain duplicate canonical params.
		if ( ! self::shortcode_attribute_pairs_have_duplicates( $pairs, $params ) ) {
			return $fallback;
		}

		$deduped = self::dedupe_shortcode_attribute_pairs( $pairs, $params );
		$output  = '[' . sanitize_key( (string) $tag );

		foreach ( $deduped as $canonical => $value ) {
			$output .= ' ' . self::get_vc_param_name( $canonical ) . '="' . esc_attr( $value ) . '"';
		}

		$output .= ']';

		return $output;
	}

	/**
	 * Clean duplicate WPBakery portfolio shortcode attributes in post content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function sanitize_wpbakery_portfolio_shortcodes_in_content( $content ) {
		if ( ! is_string( $content ) || false === strpos( $content, '[dinofolio_portfolio' ) ) {
			return $content;
		}

		$component = Components::instance()->get( 'portfolio' );

		if ( ! $component ) {
			return $content;
		}

		$params = $component->get_params();
		$tag    = $component->get_wpbakery_shortcode_base();
		$pattern = '/\[' . preg_quote( $tag, '/' ) . '([^\]]*)\]/';

		return preg_replace_callback(
			$pattern,
			static function ( $matches ) use ( $tag, $params ) {
				return self::rebuild_wpbakery_shortcode_tag( $tag, $matches[1], $params, $matches[0] );
			},
			$content
		);
	}

	/**
	 * Convert camelCase param names to snake_case.
	 *
	 * @param string $name Param name.
	 * @return string
	 */
	public static function camel_to_snake( $name ) {
		$snake = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', (string) $name ) );

		return str_replace( '-', '_', $snake );
	}

	/**
	 * Resolve a dropdown value from slug or human-readable label.
	 *
	 * @param mixed $value   Raw saved value.
	 * @param array $options Slug => label option map.
	 * @return string
	 */
	public static function resolve_dropdown_value( $value, $options ) {
		$value = sanitize_text_field( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		if ( isset( $options[ $value ] ) ) {
			return $value;
		}

		foreach ( $options as $slug => $label ) {
			if ( (string) $label === $value ) {
				return (string) $slug;
			}
		}

		return sanitize_key( $value );
	}

	/**
	 * Convert mixed truthy values to boolean.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public static function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		$value = strtolower( trim( (string) $value ) );

		return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
	}

	/**
	 * Convert component slug to integration class suffix.
	 *
	 * @param string $slug Component slug.
	 * @return string
	 */
	public static function slug_to_class_suffix( $slug ) {
		return str_replace( ' ', '', ucwords( str_replace( '-', ' ', $slug ) ) );
	}

	/**
	 * Get taxonomy term options for multiselect controls.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array Term ID => label pairs.
	 */
	public static function get_taxonomy_term_options( $taxonomy ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$options = array();

		foreach ( $terms as $term ) {
			$options[ (string) $term->term_id ] = $term->name;
		}

		return $options;
	}

	/**
	 * Normalize mixed values into an array of integers.
	 *
	 * @param mixed $value Raw value.
	 * @return array
	 */
	public static function sanitize_int_array( $value ) {
		if ( ! is_array( $value ) ) {
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				$value = explode( ',', $value );
			} else {
				return array();
			}
		}

		return array_values(
			array_filter(
				array_map( 'intval', $value ),
				function ( $item ) {
					return $item > 0;
				}
			)
		);
	}

	/**
	 * Normalize taxonomy term IDs, ignoring "All" (-1) sentinel values.
	 *
	 * @param mixed $value Raw value.
	 * @return array
	 */
	public static function sanitize_taxonomy_term_ids( $value ) {
		if ( ! is_array( $value ) ) {
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				$value = explode( ',', $value );
			} else {
				return array();
			}
		}

		$ids = array();

		foreach ( $value as $term_id ) {
			if ( '-1' === (string) $term_id || -1 === $term_id ) {
				continue;
			}

			$term_id = (int) $term_id;
			if ( $term_id > 0 ) {
				$ids[] = $term_id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * WPBakery autocomplete suggestions for a taxonomy field.
	 *
	 * @param string $search_string Search query.
	 * @param string $taxonomy      Taxonomy slug.
	 * @return array
	 */
	public static function vc_autocomplete_taxonomy_suggestions( $search_string, $taxonomy ) {
		$taxonomy = sanitize_key( (string) $taxonomy );

		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'search'     => (string) $search_string,
				'number'     => 20,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$suggestions = array();

		foreach ( $terms as $term ) {
			if ( function_exists( 'vc_get_term_object' ) ) {
				$suggestions[] = vc_get_term_object( $term );
				continue;
			}

			$suggestions[] = array(
				'label' => $term->name,
				'value' => (string) $term->term_id,
			);
		}

		return $suggestions;
	}

	/**
	 * WPBakery autocomplete render label for a stored taxonomy term ID.
	 *
	 * @param array  $data     Value data with value/label keys.
	 * @param string $taxonomy Taxonomy slug.
	 * @return array
	 */
	public static function vc_autocomplete_taxonomy_render( $data, $taxonomy ) {
		$taxonomy = sanitize_key( (string) $taxonomy );

		if ( empty( $data['value'] ) || empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			return $data;
		}

		$term = get_term( absint( $data['value'] ), $taxonomy );

		if ( $term && ! is_wp_error( $term ) ) {
			$data['label'] = $term->name;
			$data['value'] = (string) $term->term_id;
		}

		return $data;
	}

	/**
	 * Registered image size options for listing controls.
	 *
	 * @return array
	 */
	public static function get_image_size_options() {
		$options = array();

		foreach ( get_intermediate_image_sizes() as $size ) {
			$options[ $size ] = ucwords( str_replace( array( '-', '_' ), ' ', $size ) );
		}

		$options['full'] = esc_html__( 'Full Size', 'dinofolio' );

		return $options;
	}

	/**
	 * Whether a portfolio item uses the gallery post format.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_portfolio_gallery_format( $post_id ) {
		$post_id = absint( $post_id );

		if ( $post_id < 1 ) {
			return false;
		}

		if ( 'gallery' === get_post_format( $post_id ) ) {
			return true;
		}

		return 'gallery' === get_post_meta( $post_id, '_wpdino_portfolio_format', true );
	}

	/**
	 * Get stored gallery attachment IDs for a portfolio post.
	 *
	 * @param int $post_id Post ID.
	 * @return int[]
	 */
	public static function get_portfolio_gallery_image_ids( $post_id ) {
		$stored = get_post_meta( absint( $post_id ), '_wpdino_gallery_images', true );

		if ( ! is_array( $stored ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $stored as $image_id ) {
			$image_id = absint( $image_id );

			if ( $image_id < 1 || ! wp_attachment_is_image( $image_id ) ) {
				continue;
			}

			if ( in_array( $image_id, $sanitized, true ) ) {
				continue;
			}

			$sanitized[] = $image_id;
		}

		return $sanitized;
	}

	/**
	 * Get the attachment ID used as a portfolio item preview image.
	 *
	 * Gallery-format items use the first gallery image; others use the featured image.
	 *
	 * @param int $post_id Post ID.
	 * @return int Attachment ID or 0 when none is available.
	 */
	public static function get_portfolio_preview_image_id( $post_id ) {
		$post_id = absint( $post_id );

		if ( $post_id < 1 ) {
			return 0;
		}

		if ( self::is_portfolio_gallery_format( $post_id ) ) {
			$gallery_ids = self::get_portfolio_gallery_image_ids( $post_id );

			if ( ! empty( $gallery_ids ) ) {
				return (int) $gallery_ids[0];
			}
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );

		return $thumbnail_id ? (int) $thumbnail_id : 0;
	}
}
