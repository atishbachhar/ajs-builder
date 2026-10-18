<?php
/**
 * Global styling tokens, presets and CSS output.
 *
 * Turns the values behind the "Global Styling" settings into the `--ajs-*`
 * custom properties the front-end stylesheet consumes, and provides a small
 * library of ready-made design presets.
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Global style token model.
 */
class Ajs_Builder_Styles {

	/**
	 * Token schema: setting key => value type.
	 *
	 * Types: color|text|int|float|enum|url.
	 *
	 * @return array
	 */
	public static function schema() {
		return array(
			'style_color_primary'       => 'color',
			'style_color_primary_dark'  => 'color',
			'style_color_accent'        => 'color',
			'style_color_ink'           => 'color',
			'style_color_body'          => 'color',
			'style_color_muted'         => 'color',
			'style_color_bg'            => 'color',
			'style_color_surface'       => 'color',
			'style_color_border'        => 'color',
			'style_color_dark'          => 'color',
			'style_color_dark_soft'     => 'color',
			'style_font_body'           => 'text',
			'style_font_heading'        => 'text',
			'style_font_size'           => 'int',
			'style_font_weight_body'    => 'int',
			'style_font_weight_heading' => 'int',
			'style_line_height_body'    => 'float',
			'style_line_height_heading' => 'float',
			'style_link'                => 'color',
			'style_link_hover'          => 'color',
			'style_button_bg'           => 'color',
			'style_button_text'         => 'color',
			'style_button_hover_bg'     => 'color',
			'style_button_radius'       => 'int',
			'style_radius'              => 'int',
			'style_shadow'              => 'enum',
			'style_space'               => 'int',
			'style_webfonts_url'        => 'url',
		);
	}

	/**
	 * Default value for every token.
	 *
	 * @return array
	 */
	public static function defaults() {
		$defaults = array(
			'style_color_primary'       => '#c8102e',
			'style_color_primary_dark'  => '#9b0c23',
			'style_color_accent'        => '#f5a623',
			'style_color_ink'           => '#111418',
			'style_color_body'          => '#4b5563',
			'style_color_muted'         => '#8b93a1',
			'style_color_bg'            => '#ffffff',
			'style_color_surface'       => '#f5f6f8',
			'style_color_border'        => '#e5e7eb',
			'style_color_dark'          => '#14181d',
			'style_color_dark_soft'     => '#1d232a',
			'style_font_body'           => '',
			'style_font_heading'        => '',
			'style_font_size'           => 16,
			'style_font_weight_body'    => 400,
			'style_font_weight_heading' => 700,
			'style_line_height_body'    => 1.6,
			'style_line_height_heading' => 1.25,
			'style_link'                => '#c8102e',
			'style_link_hover'          => '#9b0c23',
			'style_button_bg'           => '#c8102e',
			'style_button_text'         => '#ffffff',
			'style_button_hover_bg'     => '#9b0c23',
			'style_button_radius'       => 8,
			'style_radius'              => 12,
			'style_shadow'              => 'md',
			'style_space'               => 24,
			'style_webfonts_url'        => '',
		);

		/**
		 * Filter the default global style tokens.
		 *
		 * @param array $defaults Token defaults.
		 */
		return apply_filters( 'ajs_builder_style_defaults', $defaults );
	}

	/**
	 * Value type for a token.
	 *
	 * @param string $key Token key.
	 * @return string
	 */
	public static function type( $key ) {
		$schema = self::schema();
		return isset( $schema[ $key ] ) ? $schema[ $key ] : 'text';
	}

	/**
	 * Default value for a single token.
	 *
	 * @param string $key Token key.
	 * @return mixed
	 */
	public static function default_for( $key ) {
		$defaults = self::defaults();
		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	}

	/**
	 * Shadow presets: slug => CSS value.
	 *
	 * @return array
	 */
	public static function shadows() {
		return array(
			'none' => 'none',
			'sm'   => '0 1px 3px rgba(16,24,40,.10)',
			'md'   => '0 8px 22px -10px rgba(16,24,40,.24)',
			'lg'   => '0 18px 40px -18px rgba(16,24,40,.32)',
			'xl'   => '0 30px 60px -22px rgba(16,24,40,.40)',
		);
	}

	/**
	 * Token keys the admin exposes as a color picker.
	 *
	 * @return array
	 */
	public static function color_keys() {
		$out = array();
		foreach ( self::schema() as $key => $type ) {
			if ( 'color' === $type ) {
				$out[] = $key;
			}
		}
		return $out;
	}

	/**
	 * Map token key => CSS custom property.
	 *
	 * @return array
	 */
	public static function var_map() {
		return array(
			'style_color_primary'       => '--ajs-primary',
			'style_color_primary_dark'  => '--ajs-primary-dark',
			'style_color_accent'        => '--ajs-accent',
			'style_color_ink'           => '--ajs-ink',
			'style_color_body'          => '--ajs-body',
			'style_color_muted'         => '--ajs-muted',
			'style_color_bg'            => '--ajs-bg',
			'style_color_surface'       => '--ajs-surface',
			'style_color_border'        => '--ajs-border',
			'style_color_dark'          => '--ajs-dark',
			'style_color_dark_soft'     => '--ajs-dark-soft',
			'style_font_body'           => '--ajs-font',
			'style_font_heading'        => '--ajs-font-heading',
			'style_font_size'           => '--ajs-fs',
			'style_font_weight_body'    => '--ajs-fw-body',
			'style_font_weight_heading' => '--ajs-fw-heading',
			'style_line_height_body'    => '--ajs-lh-body',
			'style_line_height_heading' => '--ajs-lh-heading',
			'style_link'                => '--ajs-link',
			'style_link_hover'          => '--ajs-link-hover',
			'style_button_bg'           => '--ajs-btn-bg',
			'style_button_text'         => '--ajs-btn-text',
			'style_button_hover_bg'     => '--ajs-btn-hover-bg',
			'style_button_radius'       => '--ajs-btn-radius',
			'style_radius'              => '--ajs-radius',
			'style_shadow'              => '--ajs-shadow',
			'style_space'               => '--ajs-space',
		);
	}

	/**
	 * Current token values (settings, merged over defaults).
	 *
	 * @return array
	 */
	public static function tokens() {
		$out = array();
		foreach ( array_keys( self::schema() ) as $key ) {
			$out[ $key ] = Ajs_Builder_Settings::get( $key, self::default_for( $key ) );
		}
		return $out;
	}

	/**
	 * Turn one token into its CSS value (with the right unit).
	 *
	 * @param string $key   Token key.
	 * @param mixed  $value Token value.
	 * @return string Empty string when the token should be skipped.
	 */
	public static function css_value( $key, $value ) {
		switch ( self::type( $key ) ) {
			case 'color':
				$hex = sanitize_hex_color( (string) $value );
				return $hex ? $hex : '';

			case 'text':
				return trim( str_replace( array( '<', '>', '{', '}', ';' ), '', (string) $value ) );

			case 'int':
				$n = (int) $value;
				if ( in_array( $key, array( 'style_font_weight_body', 'style_font_weight_heading' ), true ) ) {
					return (string) $n;
				}
				return $n . 'px';

			case 'float':
				return (string) (float) $value;

			case 'enum':
				$map = self::shadows();
				return isset( $map[ $value ] ) ? $map[ $value ] : '';

			case 'url':
			default:
				return '';
		}
	}

	/**
	 * Selector the global style tokens are scoped to.
	 *
	 * The tokens are emitted on the builder surfaces (content + header/footer
	 * templates) rather than on `:root`, so a theme can own the site-wide
	 * defaults while the builder keeps full control of its own markup.
	 *
	 * @return string
	 */
	public static function selector() {
		/**
		 * Filter the selector the global style tokens are scoped to.
		 *
		 * @param string $selector CSS selector(s).
		 */
		return apply_filters( 'ajs_builder_global_style_selector', '.ajs-builder-content,.ajs-custom-template' );
	}

	/**
	 * Build the scoped custom-property block and append the custom CSS.
	 *
	 * @return string
	 */
	public static function css() {
		$tokens = self::tokens();
		$vars   = array();

		foreach ( self::var_map() as $key => $var ) {
			$value = self::css_value( $key, isset( $tokens[ $key ] ) ? $tokens[ $key ] : '' );
			if ( '' !== $value ) {
				$vars[ $var ] = $value;
			}
		}

		$width = (int) Ajs_Builder_Settings::get( 'container_width', 0 );
		if ( $width > 0 ) {
			$vars['--ajs-container-width'] = $width . 'px';
		}

		$css = '';
		if ( $vars ) {
			$css .= self::selector() . '{';
			foreach ( $vars as $name => $value ) {
				$css .= $name . ':' . $value . ';';
			}
			$css .= '}';
		}

		$custom = (string) Ajs_Builder_Settings::get( 'custom_css', '' );
		if ( '' !== trim( $custom ) ) {
			$css .= "\n" . $custom;
		}

		return $css;
	}

	/**
	 * Optional external font stylesheet URL (e.g. Google Fonts).
	 *
	 * @return string
	 */
	public static function webfonts_url() {
		return (string) Ajs_Builder_Settings::get( 'style_webfonts_url', '' );
	}

	/**
	 * Sanitize a single token value.
	 *
	 * @param string $key   Token key.
	 * @param mixed  $value Raw value.
	 * @return mixed
	 */
	public static function sanitize_value( $key, $value ) {
		switch ( self::type( $key ) ) {
			case 'color':
				$hex = sanitize_hex_color( is_string( $value ) ? trim( $value ) : '' );
				return $hex ? $hex : self::default_for( $key );

			case 'int':
				$n = (int) $value;
				if ( in_array( $key, array( 'style_font_weight_body', 'style_font_weight_heading' ), true ) ) {
					return max( 100, min( 900, $n ) );
				}
				if ( 'style_font_size' === $key ) {
					return max( 8, min( 72, $n ) );
				}
				if ( 'style_space' === $key ) {
					return max( 0, min( 200, $n ) );
				}
				return max( 0, min( 80, $n ) ); // radius values.

			case 'float':
				return round( max( 0.8, min( 3.0, (float) $value ) ), 2 );

			case 'enum':
				$allowed = array_keys( self::shadows() );
				$v       = is_string( $value ) ? strtolower( trim( $value ) ) : '';
				return in_array( $v, $allowed, true ) ? $v : self::default_for( $key );

			case 'url':
				return esc_url_raw( is_string( $value ) ? trim( $value ) : '' );

			case 'text':
			default:
				$v = is_string( $value ) ? $value : '';
				$v = preg_replace( '/[<>{};]/', '', $v );
				return trim( $v );
		}
	}

	/**
	 * Sanitize a saved preset collection.
	 *
	 * @param mixed $raw Preset data.
	 * @return array
	 */
	public static function sanitize_presets( $raw ) {
		$clean = array();
		if ( ! is_array( $raw ) ) {
			return $clean;
		}
		foreach ( $raw as $name => $tokens ) {
			$name = sanitize_text_field( (string) $name );
			if ( '' === $name || ! is_array( $tokens ) ) {
				continue;
			}
			$out = array();
			foreach ( array_keys( self::schema() ) as $key ) {
				if ( array_key_exists( $key, $tokens ) ) {
					$out[ $key ] = self::sanitize_value( $key, $tokens[ $key ] );
				}
			}
			if ( $out ) {
				$clean[ $name ] = $out;
			}
		}
		return $clean;
	}

	/**
	 * A font stack for the presets (safe, no remote dependency).
	 *
	 * @param string $kind body|heading|mono|serif.
	 * @return string
	 */
	private static function font_stack( $kind ) {
		switch ( $kind ) {
			case 'serif':
				return "Georgia,'Times New Roman',Times,serif";
			case 'mono':
				return "ui-monospace,SFMono-Regular,Menlo,Consolas,monospace";
			case 'heading':
				return "system-ui,-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
			case 'body':
			default:
				return "system-ui,-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
		}
	}

	/**
	 * Built-in design presets: name => tokens.
	 *
	 * @return array
	 */
	public static function builtin_presets() {
		$presets = array(
			'Default (Crimson)' => self::defaults(),
			'Corporate Blue'    => array(
				'style_color_primary'       => '#1d4ed8',
				'style_color_primary_dark'  => '#1e40af',
				'style_color_accent'        => '#0ea5e9',
				'style_color_ink'           => '#0f172a',
				'style_color_body'          => '#475569',
				'style_color_muted'         => '#64748b',
				'style_color_bg'            => '#ffffff',
				'style_color_surface'       => '#f1f5f9',
				'style_color_border'        => '#e2e8f0',
				'style_color_dark'          => '#0f172a',
				'style_color_dark_soft'     => '#1e293b',
				'style_link'                => '#1d4ed8',
				'style_link_hover'          => '#1e40af',
				'style_button_bg'           => '#1d4ed8',
				'style_button_text'         => '#ffffff',
				'style_button_hover_bg'     => '#1e40af',
				'style_button_radius'       => 8,
				'style_radius'              => 12,
				'style_shadow'              => 'md',
				'style_space'               => 24,
				'style_font_body'           => self::font_stack( 'body' ),
				'style_font_heading'        => self::font_stack( 'heading' ),
				'style_font_weight_heading' => 700,
				'style_line_height_heading' => 1.25,
			),
			'Emerald Bold'      => array(
				'style_color_primary'       => '#059669',
				'style_color_primary_dark'  => '#047857',
				'style_color_accent'        => '#f59e0b',
				'style_color_ink'           => '#052e26',
				'style_color_body'          => '#3f4b46',
				'style_color_muted'         => '#6b7c76',
				'style_color_bg'            => '#ffffff',
				'style_color_surface'       => '#ecfdf5',
				'style_color_border'        => '#d1fae5',
				'style_color_dark'          => '#052e26',
				'style_color_dark_soft'     => '#064e3b',
				'style_link'                => '#047857',
				'style_link_hover'          => '#065f46',
				'style_button_bg'           => '#059669',
				'style_button_text'         => '#ffffff',
				'style_button_hover_bg'     => '#047857',
				'style_button_radius'       => 999,
				'style_radius'              => 16,
				'style_shadow'              => 'lg',
				'style_space'               => 28,
				'style_font_weight_heading' => 800,
			),
			'Warm Editorial'    => array(
				'style_color_primary'       => '#b45309',
				'style_color_primary_dark'  => '#92400e',
				'style_color_accent'        => '#dc2626',
				'style_color_ink'           => '#1c1917',
				'style_color_body'          => '#44403c',
				'style_color_muted'         => '#78716c',
				'style_color_bg'            => '#fffbf5',
				'style_color_surface'       => '#f5f0e8',
				'style_color_border'        => '#e7ded2',
				'style_color_dark'          => '#231f1c',
				'style_color_dark_soft'     => '#332e2a',
				'style_link'                => '#b45309',
				'style_link_hover'          => '#92400e',
				'style_button_bg'           => '#1c1917',
				'style_button_text'         => '#fffbf5',
				'style_button_hover_bg'     => '#b45309',
				'style_button_radius'       => 4,
				'style_radius'              => 6,
				'style_shadow'              => 'sm',
				'style_space'               => 22,
				'style_font_body'           => self::font_stack( 'serif' ),
				'style_font_heading'        => self::font_stack( 'serif' ),
				'style_font_weight_heading' => 700,
				'style_line_height_body'    => 1.7,
			),
			'Midnight (Dark)'   => array(
				'style_color_primary'       => '#6366f1',
				'style_color_primary_dark'  => '#4f46e5',
				'style_color_accent'        => '#22d3ee',
				'style_color_ink'           => '#f8fafc',
				'style_color_body'          => '#cbd5e1',
				'style_color_muted'         => '#94a3b8',
				'style_color_bg'            => '#0b1120',
				'style_color_surface'       => '#111827',
				'style_color_border'        => '#1f2937',
				'style_color_dark'          => '#020617',
				'style_color_dark_soft'     => '#0f172a',
				'style_link'                => '#818cf8',
				'style_link_hover'          => '#a5b4fc',
				'style_button_bg'           => '#6366f1',
				'style_button_text'         => '#ffffff',
				'style_button_hover_bg'     => '#4f46e5',
				'style_button_radius'       => 10,
				'style_radius'              => 14,
				'style_shadow'              => 'lg',
				'style_space'               => 26,
				'style_font_weight_heading' => 700,
			),
			'Mono Minimal'      => array(
				'style_color_primary'       => '#111418',
				'style_color_primary_dark'  => '#000000',
				'style_color_accent'        => '#6b7280',
				'style_color_ink'           => '#111418',
				'style_color_body'          => '#374151',
				'style_color_muted'         => '#6b7280',
				'style_color_bg'            => '#ffffff',
				'style_color_surface'       => '#f7f7f8',
				'style_color_border'        => '#e5e7eb',
				'style_color_dark'          => '#0b0d10',
				'style_color_dark_soft'     => '#1a1d21',
				'style_link'                => '#111418',
				'style_link_hover'          => '#6b7280',
				'style_button_bg'           => '#111418',
				'style_button_text'         => '#ffffff',
				'style_button_hover_bg'     => '#374151',
				'style_button_radius'       => 2,
				'style_radius'              => 4,
				'style_shadow'              => 'sm',
				'style_space'               => 20,
				'style_font_body'           => self::font_stack( 'mono' ),
				'style_font_heading'        => self::font_stack( 'mono' ),
				'style_font_weight_heading' => 600,
			),
		);

		/**
		 * Filter the built-in design presets.
		 *
		 * @param array $presets Presets keyed by name.
		 */
		return apply_filters( 'ajs_builder_style_presets', $presets );
	}

	/**
	 * Built-in presets plus user-saved ones.
	 *
	 * @return array
	 */
	public static function presets() {
		$saved = Ajs_Builder_Settings::get( 'style_presets', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( self::builtin_presets(), $saved );
	}
}
