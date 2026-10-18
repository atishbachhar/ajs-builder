<?php
/**
 * Shared helper functions.
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read a value from a settings array with a fallback for empty values.
 *
 * @param array  $settings Settings array.
 * @param string $key      Key.
 * @param mixed  $default  Default.
 * @return mixed
 */
function ajs_builder_get( $settings, $key, $default = '' ) {
	if ( is_array( $settings ) && isset( $settings[ $key ] ) && '' !== $settings[ $key ] && null !== $settings[ $key ] && array() !== $settings[ $key ] ) {
		return $settings[ $key ];
	}
	return $default;
}

/**
 * Whether a value is a CSS gradient function.
 *
 * Background colour controls may hold either a plain colour or a gradient
 * (stored as a genuine CSS value so it can be pasted/read as text). A gradient
 * is an image, so it can never be painted by `background-color`.
 *
 * @param mixed $value Value.
 * @return bool
 */
function ajs_builder_is_gradient( $value ) {
	return is_string( $value ) && (bool) preg_match( '/^\s*(?:repeating-)?(?:linear|radial|conic)-gradient\s*\(/i', $value );
}

/**
 * Promote a gradient stored in a background colour to `background-image`.
 *
 * A gradient value can't be painted by `background-color`, so it is moved to
 * `background-image`. When an image is already present the gradient is layered
 * underneath it, mirroring how a flat background colour sits behind a
 * background image.
 *
 * @param array $rules Map of property => value.
 * @return array
 */
function ajs_builder_bg_rules( array $rules ) {
	if ( isset( $rules['background-color'] ) && ajs_builder_is_gradient( $rules['background-color'] ) ) {
		$gradient = $rules['background-color'];
		unset( $rules['background-color'] );
		$rules['background-image'] = ( isset( $rules['background-image'] ) && '' !== $rules['background-image'] )
			? $rules['background-image'] . ', ' . $gradient
			: $gradient;
	}
	return $rules;
}

/**
 * Build an inline style attribute from a map of CSS rules.
 *
 * @param array $rules Map of property => value.
 * @return string
 */
function ajs_builder_style( array $rules ) {
	$rules = ajs_builder_bg_rules( $rules );
	$out   = array();
	foreach ( $rules as $prop => $value ) {
		if ( '' === $value || null === $value ) {
			continue;
		}
		$out[] = $prop . ':' . $value;
	}
	if ( ! $out ) {
		return '';
	}
	return ' style="' . esc_attr( implode( ';', $out ) ) . '"';
}

/**
 * Turn a box control value into a CSS shorthand string.
 *
 * @param mixed  $value    Array with top/right/bottom/left/unit.
 * @param string $property CSS property name.
 * @return string
 */
function ajs_builder_box_css( $value, $property = 'padding' ) {
	if ( ! is_array( $value ) ) {
		return '';
	}
	$unit = isset( $value['unit'] ) ? $value['unit'] : 'px';
	$t    = isset( $value['top'] ) ? trim( (string) $value['top'] ) : '';
	$r    = isset( $value['right'] ) ? trim( (string) $value['right'] ) : '';
	$b    = isset( $value['bottom'] ) ? trim( (string) $value['bottom'] ) : '';
	$l    = isset( $value['left'] ) ? trim( (string) $value['left'] ) : '';

	if ( '' === $t && '' === $r && '' === $b && '' === $l ) {
		return '';
	}

	$t = ( '' === $t ) ? '0' : $t . $unit;
	$r = ( '' === $r ) ? '0' : $r . $unit;
	$b = ( '' === $b ) ? '0' : $b . $unit;
	$l = ( '' === $l ) ? '0' : $l . $unit;

	if ( $t === $r && $r === $b && $b === $l ) {
		return $property . ':' . $t;
	}
	return $property . ':' . $t . ' ' . $r . ' ' . $b . ' ' . $l;
}

/**
 * Build typography CSS rules from a typography control value.
 *
 * @param mixed $value Typography value.
 * @return array
 */
function ajs_builder_typography_css( $value ) {
	$rules = array();
	if ( ! is_array( $value ) ) {
		return $rules;
	}
	$map = array(
		'font_family'    => 'font-family',
		'font_size'      => 'font-size',
		'font_weight'    => 'font-weight',
		'line_height'    => 'line-height',
		'letter_spacing' => 'letter-spacing',
	);
	foreach ( $map as $key => $prop ) {
		if ( ! empty( $value[ $key ] ) ) {
			$is_num = in_array( $key, array( 'font_size', 'letter_spacing' ), true );
			// The editor only writes *_unit when the unit <select> is touched,
			// so fall back to px - otherwise "font-size:24" is invalid CSS and
			// the size change silently does nothing.
			$unit   = isset( $value[ $key . '_unit'] ) && '' !== $value[ $key . '_unit' ] ? $value[ $key . '_unit' ] : ( $is_num ? 'px' : '' );
			$rules[ $prop ] = $value[ $key ] . ( $is_num ? $unit : '' );
		}
	}
	if ( ! empty( $value['text_transform'] ) ) {
		$rules['text-transform'] = $value['text_transform'];
	}
	if ( ! empty( $value['font_style'] ) ) {
		$rules['font-style'] = $value['font_style'];
	}
	return $rules;
}

/**
 * Sanitize a spacing/box value coming from the editor.
 *
 * @param mixed $value Raw value.
 * @return array
 */
function ajs_builder_sanitize_box( $value ) {
	$value = is_array( $value ) ? $value : array();
	$unit  = isset( $value['unit'] ) ? preg_replace( '/[^a-z%]/', '', (string) $value['unit'] ) : 'px';
	if ( '' === $unit ) {
		$unit = 'px';
	}
	$out = array( 'unit' => $unit );
	foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
		$raw              = isset( $value[ $side ] ) ? $value[ $side ] : '';
		$out[ $side ]     = ( '' === $raw ) ? '' : (float) $raw;
	}
	return $out;
}
