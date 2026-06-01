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

		return array(
			'name' => $param_name,
			'args' => $control_args,
		);
	}

	/**
	 * Build WPBakery param from unified param definition.
	 *
	 * @param array $param Param definition.
	 * @return array
	 */
	public static function prepare_vc_param( $param ) {
		$type = isset( $param['type'] ) ? $param['type'] : 'textfield';

		$vc_param = array(
			'type'        => self::map_vc_type( $type ),
			'heading'     => isset( $param['heading'] ) ? $param['heading'] : '',
			'param_name'  => isset( $param['param_name'] ) ? $param['param_name'] : '',
			'description' => isset( $param['description'] ) ? $param['description'] : '',
		);

		if ( isset( $param['std'] ) ) {
			$vc_param['std'] = $param['std'];
		} elseif ( isset( $param['value'] ) && ! is_array( $param['value'] ) ) {
			$vc_param['value'] = $param['value'];
		}

		if ( 'dropdown' === $type && isset( $param['value'] ) && is_array( $param['value'] ) ) {
			$vc_param['value'] = array();
			foreach ( $param['value'] as $slug => $label ) {
				$vc_param['value'][ $label ] = $slug;
			}
		}

		if ( 'multiselect' === $type && isset( $param['value'] ) && is_array( $param['value'] ) ) {
			$vc_param['settings'] = array(
				'multiple'       => true,
				'min_length'     => 0,
				'unique_values'  => true,
				'display_inline' => true,
				'values'         => $param['value'],
			);
		}

		if ( 'taxonomy' === $type ) {
			$term_options = ! empty( $param['taxonomy'] ) ? self::get_taxonomy_term_options( $param['taxonomy'] ) : array();

			$vc_param['settings'] = array(
				'multiple'       => true,
				'min_length'     => 0,
				'unique_values'  => true,
				'display_inline' => true,
				'values'         => $term_options,
			);
		}

		return $vc_param;
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
		$atts     = is_array( $atts ) ? $atts : array();
		$defaults = $component->get_defaults();
		$params   = $component->get_params();

		foreach ( $params as $param ) {
			$param_name = isset( $param['param_name'] ) ? $param['param_name'] : '';
			$type       = isset( $param['type'] ) ? $param['type'] : '';

			if ( empty( $param_name ) ) {
				continue;
			}

			if ( 'checkbox' === $type ) {
				if ( ! isset( $atts[ $param_name ] ) ) {
					$atts[ $param_name ] = isset( $defaults[ $param_name ] ) ? $defaults[ $param_name ] : false;
					continue;
				}

				$atts[ $param_name ] = self::to_bool( $atts[ $param_name ] );
				continue;
			}

			if ( 'number' === $type && isset( $atts[ $param_name ] ) && is_numeric( $atts[ $param_name ] ) ) {
				$atts[ $param_name ] = intval( $atts[ $param_name ] );
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

		return wp_parse_args( $atts, $defaults );
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
}
