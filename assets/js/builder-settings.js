/*!
 * AJS Builder — Settings screen (global styling).
 */
( function ( $ ) {
	'use strict';

	// WordPress' iris picker only understands real colours, so a CSS variable
	// value can't be pushed into the swatch. Hex, rgb/rgba and hsl/hsla can.
	function isColorValue( v ) {
		if ( typeof v !== 'string' ) {
			return false;
		}
		v = $.trim( v );
		return /^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i.test( v )
			|| /^rgba?\(/i.test( v )
			|| /^hsla?\(/i.test( v );
	}

	function setColor( $input, value ) {
		$input.val( value );
		if ( $.fn.wpColorPicker && $input.hasClass( 'wp-color-picker' ) ) {
			$input.wpColorPicker( 'color', value );
		}
	}

	// Append an always-visible colour code field to a colour control and
	// return it so the picker callbacks can keep it in sync.
	function addCodeField( $input ) {
		var $field = $input.closest( '.ajs-style-field' );
		if ( ! $field.length ) {
			$field = $input.parent();
		}

		var $code = $( '<input type="text" class="ajs-color-code" />' );
		$code.val( $input.val() || '' );
		$code.attr( 'placeholder', $input.attr( 'data-default-color' ) || '#000000' );
		$code.attr( 'spellcheck', 'false' );
		$code.attr( 'autocomplete', 'off' );
		$code.attr( 'aria-label', ( $input.attr( 'id' ) || 'colour' ) + ' code' );

		$code.on( 'input', function () {
			var v = $.trim( $code.val() );
			$input.val( v );
			if ( isColorValue( v ) && $.fn.wpColorPicker && $input.hasClass( 'wp-color-picker' ) ) {
				$input.wpColorPicker( 'color', v );
			}
		} );

		$field.append( $code );
		return $code;
	}

	$( function () {
		var $preset = $( '#ajs-style-preset' );

		if ( $.fn.wpColorPicker ) {
			$( '.ajs-color-field' ).each( function () {
				var $input = $( this );
				var $code = addCodeField( $input );

				$input.wpColorPicker( {
					change: function ( e, ui ) {
						if ( document.activeElement !== $code[ 0 ] ) {
							$code.val( ui.color.toString() );
						}
					},
					clear: function () {
						if ( document.activeElement !== $code[ 0 ] ) {
							$code.val( '' );
						}
					}
				} );
			} );
		}

		$preset.on( 'change', function () {
			var raw = $preset.find( 'option:selected' ).attr( 'data-tokens' );
			if ( ! raw ) {
				return;
			}

			var tokens;
			try {
				tokens = JSON.parse( raw );
			} catch ( e ) {
				return;
			}

			Object.keys( tokens ).forEach( function ( key ) {
				var $field = $( '#' + key );
				if ( ! $field.length ) {
					$field = $( '[name="' + key + '"]' );
				}
				if ( ! $field.length ) {
					return;
				}

				var $code = $field.closest( '.ajs-style-field' ).find( '.ajs-color-code' );
				if ( $code.length && document.activeElement !== $code[ 0 ] ) {
					$code.val( tokens[ key ] );
				}

				if ( $field.hasClass( 'ajs-color-field' ) ) {
					setColor( $field, tokens[ key ] );
				}
			} );
		} );
	} );
} )( jQuery );
