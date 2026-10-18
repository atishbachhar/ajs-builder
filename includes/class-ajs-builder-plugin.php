<?php
/**
 * Plugin bootstrap.
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ties the pieces together.
 */
class Ajs_Builder_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Ajs_Builder_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Instance accessor.
	 *
	 * @return Ajs_Builder_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		Ajs_Builder_Rest::init();
		Ajs_Builder_Templates::init();
		Ajs_Builder_Frontend::init();

		if ( is_admin() ) {
			Ajs_Builder_Admin::init();
		}
	}

	/**
	 * Post types the builder is available for.
	 *
	 * @return array
	 */
	public static function post_types() {
		$types = Ajs_Builder_Settings::get( 'post_types', array( 'page' ) );
		if ( ! is_array( $types ) || ! $types ) {
			$types = array( 'page' );
		}

		/**
		 * Filter which post types can use the builder.
		 *
		 * @param array $types Post type slugs.
		 */
		return apply_filters( 'ajs_builder_post_types', $types );
	}

	/**
	 * Activation.
	 */
	public static function on_activate() {
		if ( ! get_option( 'ajs_builder_version' ) ) {
			update_option( 'ajs_builder_version', AJS_BUILDER_VERSION );
		}
		if ( ! get_option( Ajs_Builder_Settings::OPTION ) ) {
			update_option( Ajs_Builder_Settings::OPTION, Ajs_Builder_Settings::defaults() );
		}
	}
}
