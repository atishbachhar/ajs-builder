<?php
/**
 * Global builder settings.
 *
 * A single option holds every plugin-wide preference: the site container
 * width used by builder sections, the default section content width, which
 * post types may use the builder, which modules are enabled, global custom
 * CSS and the About page author/contact details.
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read/write access to the global settings option.
 */
class Ajs_Builder_Settings {

	/**
	 * Option name.
	 */
	const OPTION = 'ajs_builder_settings';

	/**
	 * Cached settings.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Default values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array_merge(
			Ajs_Builder_Styles::defaults(),
			array(
				'container_width'       => 0,
				'default_content_width' => 'boxed',
				'custom_css'            => '',
				'post_types'            => array( 'page', 'post' ),
				'disabled_modules'      => array(),
				'style_presets'         => array(),
				'contact_email'         => 'atishbachhar@gmail.com',
				'author_name'           => 'Atish Bachhar',
				'author_role'           => __( 'Web Developer', 'ajs-builder' ),
				'author_company'        => '',
				'author_email'          => 'atishbachhar@gmail.com',
				'author_url'            => 'https://atish.co.in',
				'author_phone'          => '+91 7362977273',
				'author_socials'        => array(),
			)
		);
	}

	/**
	 * All settings, merged with defaults.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$cache ) {
			$saved = get_option( self::OPTION, array() );
			if ( ! is_array( $saved ) ) {
				$saved = array();
			}
			self::$cache = wp_parse_args( $saved, self::defaults() );
		}
		return self::$cache;
	}

	/**
	 * One setting.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Fallback when missing.
	 * @return mixed
	 */
	public static function get( $key, $fallback = null ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}

	/**
	 * Save settings.
	 *
	 * @param array $data Raw (possibly partial) settings.
	 * @return array Sanitised settings.
	 */
	public static function update( $data ) {
		$clean = self::sanitize( $data );
		update_option( self::OPTION, $clean );
		self::$cache = null;
		return $clean;
	}

	/**
	 * Sanitise a settings payload, keeping current values for missing keys.
	 *
	 * @param array $data Raw settings.
	 * @return array
	 */
	public static function sanitize( $data ) {
		$out = self::all();

		if ( array_key_exists( 'container_width', $data ) ) {
			$out['container_width'] = max( 0, min( 4000, absint( $data['container_width'] ) ) );
		}
		if ( isset( $data['default_content_width'] ) ) {
			$out['default_content_width'] = in_array( $data['default_content_width'], array( 'boxed', 'full' ), true )
				? $data['default_content_width']
				: 'boxed';
		}
		if ( array_key_exists( 'custom_css', $data ) ) {
			$out['custom_css'] = wp_strip_all_tags( (string) $data['custom_css'] );
		}
		if ( isset( $data['post_types'] ) ) {
			$valid = array_keys( self::available_post_types() );
			$types = array_map( 'sanitize_key', (array) $data['post_types'] );
			$types = array_values( array_intersect( $types, $valid ) );
			$out['post_types'] = $types ? $types : array( 'page' );
		}
		if ( isset( $data['disabled_modules'] ) ) {
			$valid = array_keys( Ajs_Builder_Widgets::all() );
			$mods  = array_map( 'sanitize_key', (array) $data['disabled_modules'] );
			$out['disabled_modules'] = array_values( array_intersect( $mods, $valid ) );
		}

		foreach ( array_keys( Ajs_Builder_Styles::schema() ) as $style_key ) {
			if ( array_key_exists( $style_key, $data ) ) {
				$out[ $style_key ] = Ajs_Builder_Styles::sanitize_value( $style_key, $data[ $style_key ] );
			}
		}
		if ( isset( $data['style_presets'] ) ) {
			$out['style_presets'] = Ajs_Builder_Styles::sanitize_presets( $data['style_presets'] );
		}

		if ( isset( $data['contact_email'] ) ) {
			$out['contact_email'] = sanitize_email( $data['contact_email'] );
		}
		foreach ( array( 'author_name', 'author_role', 'author_company', 'author_phone' ) as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$out[ $key ] = sanitize_text_field( $data[ $key ] );
			}
		}
		if ( isset( $data['author_email'] ) ) {
			$out['author_email'] = sanitize_email( $data['author_email'] );
		}
		if ( isset( $data['author_url'] ) ) {
			$out['author_url'] = esc_url_raw( $data['author_url'] );
		}
		if ( isset( $data['author_socials'] ) && is_array( $data['author_socials'] ) ) {
			$socials = array();
			foreach ( $data['author_socials'] as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$label = isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '';
				$url   = isset( $row['url'] ) ? esc_url_raw( $row['url'] ) : '';
				if ( '' === $label && '' === $url ) {
					continue;
				}
				$socials[] = array(
					'label' => $label,
					'url'   => $url,
				);
			}
			$out['author_socials'] = $socials;
		}

		return $out;
	}

	/**
	 * Public post types the builder can be enabled for.
	 *
	 * @return array slug => singular label
	 */
	public static function available_post_types() {
		$objects = get_post_types( array( 'public' => true ), 'objects' );
		$skip    = array( 'attachment', 'ajs_template', 'wp_block', 'wp_navigation', 'wp_template', 'wp_template_part' );
		$out     = array();
		foreach ( $objects as $name => $object ) {
			if ( in_array( $name, $skip, true ) ) {
				continue;
			}
			$label       = isset( $object->labels->singular_name ) && $object->labels->singular_name
				? $object->labels->singular_name
				: $object->label;
			$out[ $name ] = $label;
		}
		return $out;
	}
}
