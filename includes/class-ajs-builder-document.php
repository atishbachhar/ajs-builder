<?php
/**
 * Document storage + sanitisation.
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads, writes and validates builder documents.
 */
class Ajs_Builder_Document {

	/**
	 * An empty document.
	 *
	 * @return array
	 */
	public static function blank() {
		return array(
			'version'  => AJS_BUILDER_VERSION,
			'sections' => array(),
		);
	}

	/**
	 * Get a document for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get( $post_id ) {
		$raw = get_post_meta( $post_id, AJS_BUILDER_META, true );
		if ( is_string( $raw ) && '' !== $raw ) {
			$raw = json_decode( $raw, true );
		}
		if ( ! is_array( $raw ) || ! isset( $raw['sections'] ) ) {
			return self::blank();
		}
		return $raw;
	}

	/**
	 * Whether the builder is active for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_enabled( $post_id ) {
		return (bool) get_post_meta( $post_id, AJS_BUILDER_ENABLED_META, true );
	}

	/**
	 * Save a document.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Raw document.
	 * @param bool  $enabled Enable the builder.
	 * @return array Sanitised document.
	 */
	public static function save( $post_id, $data, $enabled = true ) {
		$clean = self::sanitize( $data );
		// update_post_meta() runs wp_unslash(), so slash the JSON to preserve
		// escape sequences such as "\n" in stored strings.
		update_post_meta( $post_id, AJS_BUILDER_META, wp_slash( (string) wp_json_encode( $clean ) ) );
		update_post_meta( $post_id, AJS_BUILDER_ENABLED_META, $enabled ? '1' : '' );
		return $clean;
	}

	/**
	 * Sanitize a full document.
	 *
	 * @param mixed $data Raw document.
	 * @return array
	 */
	public static function sanitize( $data ) {
		if ( ! is_array( $data ) ) {
			return self::blank();
		}

		$widgets  = Ajs_Builder_Widgets::all();
		$sections = array();

		if ( isset( $data['sections'] ) && is_array( $data['sections'] ) ) {
			foreach ( $data['sections'] as $section ) {
				if ( ! is_array( $section ) ) {
					continue;
				}
				$clean_columns = array();
				if ( isset( $section['columns'] ) && is_array( $section['columns'] ) ) {
					foreach ( $section['columns'] as $column ) {
						if ( ! is_array( $column ) ) {
							continue;
						}
						$clean_columns[] = self::sanitize_column( $column, $widgets );
					}
				}
				if ( ! $clean_columns ) {
					$clean_columns[] = array(
						'id'       => self::id( '' ),
						'settings' => array(),
						'widgets'  => array(),
					);
				}
				$sections[] = array(
					'id'       => self::id( isset( $section['id'] ) ? $section['id'] : '' ),
					'settings' => self::sanitize_settings( isset( $section['settings'] ) ? $section['settings'] : array() ),
					'columns'  => $clean_columns,
				);
			}
		}

		return array(
			'version'  => AJS_BUILDER_VERSION,
			'sections' => $sections,
		);
	}

	/**
	 * Sanitize a single column (and its widgets, including nested Row columns).
	 *
	 * @param array $column  Raw column.
	 * @param array $widgets Known widget definitions.
	 * @return array
	 */
	private static function sanitize_column( $column, $widgets ) {
		$clean_widgets = array();
		if ( isset( $column['widgets'] ) && is_array( $column['widgets'] ) ) {
			foreach ( $column['widgets'] as $widget ) {
				if ( ! is_array( $widget ) || empty( $widget['type'] ) ) {
					continue;
				}
				$type = sanitize_key( $widget['type'] );
				if ( ! isset( $widgets[ $type ] ) ) {
					continue;
				}
				$clean = array(
					'id'       => self::id( isset( $widget['id'] ) ? $widget['id'] : '' ),
					'type'     => $type,
					'settings' => self::sanitize_settings( isset( $widget['settings'] ) ? $widget['settings'] : array() ),
				);
				// Row widgets hold nested columns of their own.
				if ( 'row' === $type && isset( $widget['columns'] ) && is_array( $widget['columns'] ) ) {
					$clean_columns = array();
					foreach ( $widget['columns'] as $sub_column ) {
						if ( ! is_array( $sub_column ) ) {
							continue;
						}
						$clean_columns[] = self::sanitize_column( $sub_column, $widgets );
					}
					$clean['columns'] = $clean_columns ? $clean_columns : array(
						array(
							'id'       => self::id( '' ),
							'settings' => array(),
							'widgets'  => array(),
						),
					);
				}
				$clean_widgets[] = $clean;
			}
		}
		return array(
			'id'       => self::id( isset( $column['id'] ) ? $column['id'] : '' ),
			'settings' => self::sanitize_settings( isset( $column['settings'] ) ? $column['settings'] : array() ),
			'widgets'  => $clean_widgets,
		);
	}

	/**
	 * Sanitize an element id.
	 *
	 * @param mixed $id Raw id.
	 * @return string
	 */
	public static function id( $id ) {
		$id = is_string( $id ) ? preg_replace( '/[^a-zA-Z0-9_\-]/', '', $id ) : '';
		if ( '' === $id ) {
			$id = 'ajs_' . substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 8 );
		}
		return $id;
	}

	/**
	 * Recursively sanitize a settings array.
	 *
	 * @param mixed $settings Raw settings.
	 * @return array
	 */
	public static function sanitize_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return array();
		}
		$out = array();
		foreach ( $settings as $key => $value ) {
			if ( ! is_string( $key ) && ! is_int( $key ) ) {
				continue;
			}
			$key = (string) $key;
			if ( ! preg_match( '/^[a-zA-Z0-9_\-]+$/', $key ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$out[ $key ] = self::sanitize_settings( $value );
			} elseif ( is_bool( $value ) ) {
				$out[ $key ] = $value;
			} elseif ( is_int( $value ) || is_float( $value ) ) {
				$out[ $key ] = $value;
			} elseif ( is_string( $value ) ) {
				if ( is_numeric( $value ) ) {
					$out[ $key ] = ( false !== strpos( $value, '.' ) ) ? (float) $value : (int) $value;
				} else {
					$out[ $key ] = wp_kses_post( $value );
				}
			}
		}
		return $out;
	}
}
