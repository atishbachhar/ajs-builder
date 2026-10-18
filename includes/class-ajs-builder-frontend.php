<?php
/**
 * Front-end integration.
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Front-end behaviour: template, assets and body classes.
 */
class Ajs_Builder_Frontend {

	/**
	 * Query var flagging an editor preview request.
	 */
	const PREVIEW_VAR = 'ajs_builder';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template' ), 99 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'get_the_excerpt', array( __CLASS__, 'excerpt' ), 10, 2 );
	}

	/**
	 * Is the current request an editor preview?
	 *
	 * @return bool
	 */
	public static function is_preview() {
		return isset( $_GET[ self::PREVIEW_VAR ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Is the current singular view an allowed builder post type?
	 *
	 * @return bool
	 */
	public static function is_allowed_singular() {
		if ( ! is_singular() && ! is_front_page() ) {
			return false;
		}
		$id = self::queried_id();
		if ( ! $id ) {
			return false;
		}
		$type = get_post_type( $id );
		return $type && ( in_array( $type, Ajs_Builder_Plugin::post_types(), true ) || Ajs_Builder_Templates::POST_TYPE === $type );
	}

	/**
	 * Is the current singular view built with the builder?
	 *
	 * @return bool
	 */
	public static function is_builder_view() {
		if ( ! self::is_allowed_singular() ) {
			return false;
		}
		return Ajs_Builder_Document::is_enabled( self::queried_id() );
	}

	/**
	 * Queried post id (works on the front page too).
	 *
	 * @return int
	 */
	private static function queried_id() {
		if ( is_front_page() && ! is_home() ) {
			$front = (int) get_option( 'page_on_front' );
			if ( $front ) {
				return $front;
			}
		}
		return (int) get_queried_object_id();
	}

	/**
	 * Load our template for builder pages.
	 *
	 * The plugin owns the front-end template by default, but a theme can opt
	 * out (via `ajs_builder_render_frontend`) or swap the file (via
	 * `ajs_builder_template`) so a standalone theme can render builder pages
	 * its own way while staying compatible.
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public static function template( $template ) {
		if ( ! self::is_builder_view() && ! ( self::is_preview() && self::is_allowed_singular() ) ) {
			return $template;
		}

		/**
		 * Filter whether the plugin supplies the front-end template for a
		 * builder view. Return false to let the active theme handle it.
		 *
		 * @param bool   $render   Default true.
		 * @param string $template The theme template WordPress picked.
		 */
		if ( ! apply_filters( 'ajs_builder_render_frontend', true, $template ) ) {
			return $template;
		}

		// Header/footer templates always render on a blank canvas so the user
		// designs the header/footer content on its own (never recursively).
		if ( Ajs_Builder_Templates::POST_TYPE === get_post_type( self::queried_id() ) ) {
			$canvas = AJS_BUILDER_DIR . 'templates/builder-canvas.php';
			$custom = file_exists( $canvas ) ? $canvas : $template;
		} else {
			$layout = get_post_meta( self::queried_id(), '_ajs_builder_layout', true );
			$file   = ( 'canvas' === $layout ) ? 'builder-canvas.php' : 'builder.php';
			$path   = AJS_BUILDER_DIR . 'templates/' . $file;
			$custom = file_exists( $path ) ? $path : $template;
		}

		/**
		 * Filter the front-end template chosen for a builder view.
		 *
		 * @param string $custom   Template the plugin selected.
		 * @param string $template The theme template WordPress picked.
		 */
		return apply_filters( 'ajs_builder_template', $custom, $template );
	}

	/**
	 * Should front-end builder assets load for the current request?
	 *
	 * @return bool
	 */
	private static function needs_assets() {
		if ( self::is_builder_view() || self::is_preview() ) {
			return true;
		}
		return Ajs_Builder_Templates::is_active( 'header' ) || Ajs_Builder_Templates::is_active( 'footer' );
	}

	/**
	 * Enqueue front-end assets.
	 */
	public static function assets() {
		if ( ! self::needs_assets() ) {
			return;
		}

		wp_enqueue_style( 'ajs-builder', AJS_BUILDER_URL . 'assets/css/builder-frontend.css', array(), AJS_BUILDER_VERSION );

		wp_enqueue_script( 'ajs-builder', AJS_BUILDER_URL . 'assets/js/builder-frontend.js', array(), AJS_BUILDER_VERSION, true );

		$webfonts = Ajs_Builder_Styles::webfonts_url();
		if ( '' !== $webfonts ) {
			wp_enqueue_style( 'ajs-builder-webfonts', $webfonts, array(), null );
		}

		$css = self::global_css();
		if ( '' !== $css ) {
			wp_add_inline_style( 'ajs-builder', $css );
		}
	}

	/**
	 * Global CSS from the settings: style tokens + container width + custom CSS.
	 *
	 * @return string
	 */
	private static function global_css() {
		return Ajs_Builder_Styles::css();
	}

	/**
	 * Body classes.
	 *
	 * @param array $classes Classes.
	 * @return array
	 */
	public static function body_class( $classes ) {
		if ( self::is_builder_view() || ( self::is_preview() && self::is_allowed_singular() ) ) {
			$classes[] = 'ajs-builder-page';
		}
		if ( self::is_preview() ) {
			$classes[] = 'ajs-builder-preview';
		}
		if ( Ajs_Builder_Templates::is_active( 'header' ) ) {
			$classes[] = 'ajs-has-custom-header';
		}
		if ( Ajs_Builder_Templates::is_active( 'footer' ) ) {
			$classes[] = 'ajs-has-custom-footer';
		}
		return $classes;
	}

	/**
	 * Replace a builder page excerpt with a text summary.
	 *
	 * @param string  $excerpt Excerpt.
	 * @param WP_Post $post    Post.
	 * @return string
	 */
	public static function excerpt( $excerpt, $post = null ) {
		$post = $post ? $post : get_post();
		if ( ! $post || ! Ajs_Builder_Document::is_enabled( $post->ID ) ) {
			return $excerpt;
		}
		$text = self::collect_text( Ajs_Builder_Document::get( $post->ID ) );
		if ( '' === $text ) {
			return $excerpt;
		}
		return wp_trim_words( $text, 40 );
	}

	/**
	 * Pull the first bit of text out of a document (for excerpts/search).
	 *
	 * @param array $data Document.
	 * @return string
	 */
	private static function collect_text( $data ) {
		$out = '';
		if ( empty( $data['sections'] ) || ! is_array( $data['sections'] ) ) {
			return $out;
		}
		foreach ( $data['sections'] as $section ) {
			if ( empty( $section['columns'] ) ) {
				continue;
			}
			foreach ( $section['columns'] as $column ) {
				if ( empty( $column['widgets'] ) ) {
					continue;
				}
				foreach ( $column['widgets'] as $widget ) {
					foreach ( array( 'text', 'title', 'content', 'html' ) as $key ) {
						if ( ! empty( $widget['settings'][ $key ] ) && is_string( $widget['settings'][ $key ] ) ) {
							$out .= ' ' . wp_strip_all_tags( $widget['settings'][ $key ] );
						}
					}
					if ( strlen( trim( $out ) ) > 300 ) {
						return trim( $out );
					}
				}
			}
		}
		return trim( $out );
	}
}
