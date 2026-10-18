<?php
/**
 * Server-side renderer. This is the single source of truth for front-end
 * markup: the editor preview, the REST render endpoint and the live site all
 * use exactly the same code path.
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rendering engine.
 */
class Ajs_Builder_Render {

	/**
	 * Render a whole document.
	 *
	 * @param array $data Document data.
	 * @return string
	 */
	public static function document( $data ) {
		$sections = isset( $data['sections'] ) && is_array( $data['sections'] ) ? $data['sections'] : array();
		$html     = '';
		foreach ( $sections as $section ) {
			$html .= self::section( $section );
		}
		return $html;
	}

	/**
	 * Render a section.
	 *
	 * @param array $section Section data.
	 * @return string
	 */
	public static function section( $section ) {
		$id       = isset( $section['id'] ) ? $section['id'] : '';
		$settings = isset( $section['settings'] ) ? $section['settings'] : array();

		$width = ajs_builder_get( $settings, 'content_width', Ajs_Builder_Settings::get( 'default_content_width', 'boxed' ) );

		$rules = self::box_rules( $settings );

		$bg_url   = self::media_url( ajs_builder_get( $settings, 'bg_image', array() ) );
		$bg_color = ajs_builder_get( $settings, 'bg_color', '' );

		// When a background opacity below 100% is requested the background is
		// painted on a dedicated layer so the section's content stays opaque.
		$bg_layer = self::section_bg_layer( $settings );

		$bg_rules = array();
		if ( $bg_color ) {
			$bg_rules['background-color'] = $bg_color;
		}
		if ( $bg_url ) {
			$bg_rules['background-image']    = 'url(' . esc_url_raw( $bg_url ) . ')';
			$bg_rules['background-position'] = ajs_builder_get( $settings, 'bg_position', 'center center' );
			$bg_rules['background-repeat']   = ajs_builder_get( $settings, 'bg_repeat', 'no-repeat' );
			$bg_rules['background-size']     = ajs_builder_get( $settings, 'bg_size', 'cover' );
		}

		if ( ajs_builder_get( $settings, 'text_color', '' ) ) {
			$rules['color'] = ajs_builder_get( $settings, 'text_color', '' );
		}

		if ( $bg_layer ) {
			$bg_rules['opacity'] = min( 100, max( 0, (float) ajs_builder_get( $settings, 'bg_opacity', 100 ) ) ) / 100;
		} else {
			$rules = array_merge( $rules, $bg_rules );
		}

		$class = 'ajs-b-section ajs-b-section--' . esc_attr( $width );
		if ( ajs_builder_get( $settings, 'css_class', '' ) ) {
			$class .= ' ' . sanitize_text_field( ajs_builder_get( $settings, 'css_class', '' ) );
		}
		$class .= self::visibility_class( $settings );

		$attrs = '';
		if ( $id ) {
			$attrs .= ' data-id="' . esc_attr( $id ) . '"';
			$attrs .= ' data-ajs-id="' . esc_attr( $id ) . '"';
		}
		if ( ajs_builder_get( $settings, 'css_id', '' ) ) {
			$attrs .= ' id="' . esc_attr( sanitize_text_field( ajs_builder_get( $settings, 'css_id', '' ) ) ) . '"';
		}

		// Overlay layer.
		$overlay = '';
		$ov      = ajs_builder_get( $settings, 'bg_overlay', '' );
		$ov_op   = ajs_builder_get( $settings, 'bg_overlay_opacity', '' );
		if ( $bg_url && ( $ov || '' !== $ov_op ) ) {
			$opacity = ( '' === $ov_op ) ? 0.5 : ( (float) $ov_op / 100 );
			$overlay = '<span class="ajs-b-section__overlay"' . ajs_builder_style(
				array(
					'background-color' => $ov ? $ov : '#000000',
					'opacity'          => $opacity,
				)
			) . '></span>';
		}

		// Optional background layer (used when background opacity < 100%).
		$bg_html = $bg_layer ? '<span class="ajs-b-section__bg"' . ajs_builder_style( $bg_rules ) . '></span>' : '';

		$columns = isset( $section['columns'] ) && is_array( $section['columns'] ) ? $section['columns'] : array();
		$cols    = '';
		foreach ( $columns as $column ) {
			$cols .= self::column( $column );
		}

		$container_rules = array();
		if ( 'boxed' === $width ) {
			$cw = ajs_builder_get( $settings, 'content_custom_width', '' );
			if ( '' !== $cw && null !== $cw && (float) $cw > 0 ) {
				$container_rules['max-width'] = (float) $cw . 'px';
			}
		}

		$row_rules = self::row_rules( $settings );

		$inner = '<div class="ajs-b-container ajs-b-container--' . esc_attr( $width ) . '"' . ajs_builder_style( $container_rules ) . '>'
			. '<div class="ajs-b-row"' . self::row_data_attrs( $settings ) . ajs_builder_style( $row_rules ) . '>'
			. $cols
			. '</div></div>';

		return self::responsive_block( 'section', $id, $settings )
			. '<section class="' . esc_attr( $class ) . '" data-type="section" data-element="section"' . $attrs . ajs_builder_style( $rules ) . '>'
			. $bg_html . $overlay . $inner . '</section>'
			. self::render_custom_css( $id, ajs_builder_get( $settings, 'custom_css', '' ) );
	}

	/**
	 * Render a column.
	 *
	 * @param array $column Column data.
	 * @return string
	 */
	public static function column( $column ) {
		$id       = isset( $column['id'] ) ? $column['id'] : '';
		$settings = isset( $column['settings'] ) ? $column['settings'] : array();

		$rules = self::box_rules( $settings );
		$rules = array_merge( $rules, self::col_width_rules( $settings ) );

		if ( ajs_builder_get( $settings, 'bg_color', '' ) ) {
			$rules['background-color'] = ajs_builder_get( $settings, 'bg_color', '' );
		}
		if ( ajs_builder_get( $settings, 'text_color', '' ) ) {
			$rules['color'] = ajs_builder_get( $settings, 'text_color', '' );
		}

		$col_bg_url = self::media_url( ajs_builder_get( $settings, 'bg_image', array() ) );
		if ( $col_bg_url ) {
			$rules['background-image']    = 'url(' . esc_url_raw( $col_bg_url ) . ')';
			$rules['background-position'] = ajs_builder_get( $settings, 'bg_position', 'center center' );
			$rules['background-repeat']   = ajs_builder_get( $settings, 'bg_repeat', 'no-repeat' );
			$rules['background-size']     = ajs_builder_get( $settings, 'bg_size', 'cover' );
		}

		$rules['--ajs-col-justify'] = self::col_justify_map( ajs_builder_get( $settings, 'v_align', '' ) );
		if ( ! empty( $settings['v_align_tablet'] ) ) {
			$rules['--ajs-col-justify-t'] = self::col_justify_map( $settings['v_align_tablet'] );
		}
		if ( ! empty( $settings['v_align_mobile'] ) ) {
			$rules['--ajs-col-justify-m'] = self::col_justify_map( $settings['v_align_mobile'] );
		}

		// Lay the column's modules out vertically (default) or side by side.
		if ( 'row' === ajs_builder_get( $settings, 'direction', '' ) ) {
			$rules = array_merge( $rules, self::col_dir_rules( 'row' ) );
		}

		$class = 'ajs-b-col';
		if ( ajs_builder_get( $settings, 'css_class', '' ) ) {
			$class .= ' ' . sanitize_text_field( ajs_builder_get( $settings, 'css_class', '' ) );
		}
		$class .= self::visibility_class( $settings );
		$attrs = '';
		if ( $id ) {
			$attrs .= ' data-id="' . esc_attr( $id ) . '"';
			$attrs .= ' data-ajs-id="' . esc_attr( $id ) . '"';
		}
		if ( ajs_builder_get( $settings, 'css_id', '' ) ) {
			$attrs .= ' id="' . esc_attr( sanitize_text_field( ajs_builder_get( $settings, 'css_id', '' ) ) ) . '"';
		}

		$widgets = isset( $column['widgets'] ) && is_array( $column['widgets'] ) ? $column['widgets'] : array();
		$html    = '';
		foreach ( $widgets as $widget ) {
			$html .= self::widget( $widget );
		}
		if ( '' === $html ) {
			$html = '<div class="ajs-b-empty">' . esc_html__( 'Drag a module here', 'ajs-builder' ) . '</div>';
		}

		return self::responsive_block( 'column', $id, $settings )
			. '<div class="' . esc_attr( $class ) . '" data-type="column" data-element="column"' . $attrs . ajs_builder_style( $rules ) . '>' . $html . '</div>'
			. self::render_custom_css( $id, ajs_builder_get( $settings, 'custom_css', '' ) );
	}

	/**
	 * Render a widget.
	 *
	 * @param array $widget Widget data.
	 * @return string
	 */
	public static function widget( $widget ) {
		$id       = isset( $widget['id'] ) ? $widget['id'] : '';
		$type     = isset( $widget['type'] ) ? $widget['type'] : '';
		$settings = isset( $widget['settings'] ) ? $widget['settings'] : array();

		$inner = self::widget_inner( $type, $settings, $widget );
		if ( '' === $inner ) {
			return '';
		}

		$rules = self::box_rules( $settings );
		// Row widgets paint their own padding + background on the inner
		// .ajs-b-row so padding sits inside the background, not outside it.
		// Margin stays on the wrapper (outside the background by design).
		if ( 'row' === $type ) {
			unset( $rules['padding'] );
		}
		// Typography is applied at the widget wrapper so every module inherits
		// the chosen font family/size/weight across all of its text content.
		$typo = ajs_builder_typography_css( ajs_builder_get( $settings, 'typography', array() ) );
		if ( $typo ) {
			$rules = array_merge( $rules, $typo );
		}
		if ( ajs_builder_get( $settings, 'text_color', '' ) && ! in_array( $type, array( 'heading', 'button', 'cta' ), true ) ) {
			$rules['color'] = ajs_builder_get( $settings, 'text_color', '' );
		}

		$class = 'ajs-b-widget ajs-b-widget--' . sanitize_html_class( $type );
		if ( ajs_builder_get( $settings, 'css_class', '' ) ) {
			$class .= ' ' . sanitize_text_field( ajs_builder_get( $settings, 'css_class', '' ) );
		}
		$class .= self::visibility_class( $settings );
		$attrs = ' data-type="widget" data-element="widget" data-widget="' . esc_attr( $type ) . '"';
		if ( $id ) {
			$attrs .= ' data-id="' . esc_attr( $id ) . '"';
			$attrs .= ' data-ajs-id="' . esc_attr( $id ) . '"';
		}
		if ( ajs_builder_get( $settings, 'css_id', '' ) ) {
			$attrs .= ' id="' . esc_attr( sanitize_text_field( ajs_builder_get( $settings, 'css_id', '' ) ) ) . '"';
		}

		$custom_css = self::render_custom_css( $id, ajs_builder_get( $settings, 'custom_css', '' ) );

		// Content that cannot be expressed as a CSS override (text, image
		// source, links) is re-rendered once per device and toggled with CSS.
		$variants_css = '';
		list( $variant_html, $variants_css ) = self::content_variants( $type, $settings, $widget, $id );
		if ( '' !== $variant_html ) {
			$inner = $variant_html;
		}

		return self::responsive_block( 'widget', $id, $settings, $type )
			. '<div class="' . esc_attr( $class ) . '"' . $attrs . ajs_builder_style( $rules ) . '>' . $inner . '</div>'
			. $variants_css
			. $custom_css;
	}

	/**
	 * Visibility classes for device-specific show/hide.
	 *
	 * @param array $settings Settings.
	 * @return string
	 */
	private static function visibility_class( $settings ) {
		$class = '';
		if ( ajs_builder_get( $settings, 'hide_desktop', false ) ) {
			$class .= ' ajs-hide-desktop';
		}
		if ( ajs_builder_get( $settings, 'hide_tablet', false ) ) {
			$class .= ' ajs-hide-tablet';
		}
		if ( ajs_builder_get( $settings, 'hide_mobile', false ) ) {
			$class .= ' ajs-hide-mobile';
		}
		return $class;
	}

	/**
	 * Render custom CSS for an element, replacing "THIS" with the element's selector.
	 *
	 * @param string $id        Element ID.
	 * @param string $custom_css Raw custom CSS.
	 * @return string
	 */
	private static function render_custom_css( $id, $custom_css ) {
		if ( ! $id || ! $custom_css || ! trim( $custom_css ) ) {
			return '';
		}
		$selector = '[data-ajs-id="' . esc_attr( $id ) . '"]';
		$css = str_replace( 'THIS', $selector, $custom_css );
		return '<style>' . $css . '</style>';
	}

	/**
	 * Merge per-device overrides (e.g. padding_tablet) back onto their base keys
	 * so the regular rule builders produce the values for that device.
	 *
	 * @param array  $settings Settings.
	 * @param string $device   tablet|mobile.
	 * @return array
	 */
	private static function device_settings( $settings, $device ) {
		if ( ! is_array( $settings ) ) {
			return array();
		}
		if ( 'tablet' !== $device && 'mobile' !== $device ) {
			return $settings;
		}
		$suffix = '_' . $device;
		$len    = strlen( $suffix );
		$out    = $settings;
		foreach ( $settings as $key => $value ) {
			if ( is_string( $key ) && strlen( $key ) > $len && substr( $key, -$len ) === $suffix ) {
				$out[ substr( $key, 0, -$len ) ] = $value;
			}
		}
		return $out;
	}

	/**
	 * Compute the CSS value for every responsive setting key.
	 *
	 * The result is keyed by the *setting* (e.g. title_color, text_color)
	 * rather than by the CSS property so that two controls that share a
	 * property (title_color + text_color both map to `color`) no longer
	 * overwrite each other. Numeric controls that are stored as bare
	 * numbers get the unit declared in responsive_selectors().
	 *
	 * @param string $scope    section|column|widget.
	 * @param array  $settings Settings.
	 * @param string $type     Widget type (only used when scope is widget).
	 * @return array setting key => CSS value string.
	 */
	private static function responsive_device_values( $scope, $settings, $type = '' ) {
		$map   = self::responsive_control_map( $scope, $type );
		$sels  = ( 'widget' === $scope ) ? self::responsive_selectors( $type ) : array();
		$values = array();

		foreach ( $map as $setting_key => $css_prop ) {
			$value = ajs_builder_get( $settings, $setting_key, '' );
			if ( '' === $value ) {
				continue;
			}
			// Box model (padding/margin) is stored as an array.
			if ( in_array( $setting_key, array( 'padding', 'margin' ), true ) ) {
				$box = self::box_value( $value );
				if ( '' !== $box ) {
					$values[ $setting_key ] = $box;
				}
				continue;
			}
			// Numeric controls store bare numbers; restore their unit.
			if ( isset( $sels[ $setting_key ]['unit'] ) && '' !== $sels[ $setting_key ]['unit'] && is_numeric( $value ) ) {
				$value .= $sels[ $setting_key ]['unit'];
			}
			$values[ $setting_key ] = $value;
		}

		return $values;
	}

	/**
	 * Target element (and optional CSS property/unit) for each responsive
	 * widget control.
	 *
	 * Desktop styles are written inline on the specific child element that
	 * carries them (e.g. title_color on .ajs-w-iconbox__title). A per-device
	 * override must therefore target that same element - emitting `color` on
	 * the widget wrapper would be ignored by the child (it has its own
	 * declaration) while unrelated children would inherit the colour.
	 *
	 * Keys without an entry keep the previous behaviour and are applied to
	 * the wrapper itself.
	 *
	 * @param string $type Widget type.
	 * @return array setting key => array( sel, prop?, unit? ).
	 */
	private static function responsive_selectors( $type ) {
		$map = array(
			'heading' => array(
				'color' => array( 'sel' => '.ajs-w-heading' ),
				'subtitle_color' => array( 'sel' => '.ajs-w-heading__subtitle' ),
			),
			'text' => array(
				'color' => array( 'sel' => '.ajs-w-text' ),
			),
			'image' => array(
				'width'   => array( 'sel' => '.ajs-w-image', 'unit' => '%' ),
				'radius'  => array( 'sel' => '.ajs-w-image__img', 'unit' => 'px' ),
				'opacity' => array( 'sel' => '.ajs-w-image__img' ),
				'height'  => array( 'sel' => '.ajs-w-image__img', 'prop' => 'height', 'unit' => 'px' ),
				'fit'     => array( 'sel' => '.ajs-w-image__img', 'prop' => 'object-fit' ),
			),
			'button' => array(
				'bg_color'   => array( 'sel' => '.ajs-btn' ),
				'text_color' => array( 'sel' => '.ajs-btn' ),
				'radius'     => array( 'sel' => '.ajs-btn', 'unit' => 'px' ),
			),
			'divider' => array(
				'weight' => array( 'sel' => '.ajs-w-divider__line', 'prop' => 'border-top-width', 'unit' => 'px' ),
				'width'  => array( 'sel' => '.ajs-w-divider__line', 'unit' => '%' ),
				'color'  => array( 'sel' => '.ajs-w-divider__line', 'prop' => 'border-color' ),
			),
			'spacer' => array(
				'space' => array( 'sel' => '.ajs-w-spacer', 'prop' => 'height', 'unit' => 'px' ),
			),
			'icon' => array(
				'color' => array( 'sel' => '.ajs-w-icon' ),
				'size'  => array( 'sel' => '.ajs-w-icon', 'unit' => 'px' ),
			),
			'icon-box' => array(
				'icon_color'   => array( 'sel' => '.ajs-w-iconbox__icon' ),
				'icon_size'    => array( 'sel' => '.ajs-w-iconbox__icon', 'unit' => 'px' ),
				'icon_bg'      => array( 'sel' => '.ajs-w-iconbox__icon' ),
				'icon_padding' => array( 'sel' => '.ajs-w-iconbox__icon', 'unit' => 'px' ),
				'title_color'  => array( 'sel' => '.ajs-w-iconbox__title' ),
				'text_color'   => array( 'sel' => '.ajs-w-iconbox__text' ),
			),
			'icon-list' => array(
				'icon_color' => array( 'sel' => '.ajs-w-iconlist__icon' ),
				'text_color' => array( 'sel' => '.ajs-w-iconlist__text' ),
				'space'      => array( 'sel' => '.ajs-w-iconlist', 'unit' => 'px' ),
			),
			'image-box' => array(
				'height'        => array( 'sel' => '.ajs-w-imgbox', 'prop' => 'height', 'unit' => 'px' ),
				'radius'        => array( 'sel' => '.ajs-w-imgbox', 'prop' => 'border-radius', 'unit' => 'px' ),
				'overlay_color' => array( 'sel' => '.ajs-w-imgbox__overlay', 'prop' => 'background' ),
				'title_color'   => array( 'sel' => '.ajs-w-imgbox__title' ),
				'text_color'    => array( 'sel' => '.ajs-w-imgbox__text' ),
				'button_bg'     => array( 'sel' => '.ajs-w-imgbox__btn', 'prop' => 'background-color' ),
				'button_color'  => array( 'sel' => '.ajs-w-imgbox__btn' ),
			),
			'counter' => array(
				'number_color' => array( 'sel' => '.ajs-w-counter__num' ),
				'title_color'  => array( 'sel' => '.ajs-w-counter__title' ),
				'icon_color'   => array( 'sel' => '.ajs-w-counter__icon' ),
				'icon_size'    => array( 'sel' => '.ajs-w-counter__icon', 'unit' => 'px' ),
				'bg_color'     => array( 'sel' => '.ajs-w-counter' ),
				'radius'       => array( 'sel' => '.ajs-w-counter', 'unit' => 'px' ),
			),
			'testimonial' => array(
				'gap'         => array( 'sel' => '.ajs-w-testimonial--slider', 'prop' => '--ajs-testimonial-gap', 'unit' => 'px' ),
				'quote_color' => array( 'sel' => '.ajs-w-testimonial__quote' ),
				'name_color'  => array( 'sel' => '.ajs-w-testimonial__name' ),
			),
			'cta' => array(
				'radius' => array( 'sel' => '.ajs-w-cta', 'unit' => 'px' ),
			),
			'accordion' => array(
				'active_color' => array( 'sel' => '.ajs-w-acc', 'prop' => '--ajs-acc-active' ),
				'border_color' => array( 'sel' => '.ajs-w-acc', 'prop' => '--ajs-acc-border' ),
			),
			'tabs' => array(
				'active_color' => array( 'sel' => '.ajs-w-tabs', 'prop' => '--ajs-tabs-active' ),
				'border_color' => array( 'sel' => '.ajs-w-tabs', 'prop' => '--ajs-tabs-border' ),
			),
			'product-tabs' => array(
				'active_color' => array( 'sel' => '.ajs-w-ptabs', 'prop' => '--ajs-tabs-active' ),
				'border_color' => array( 'sel' => '.ajs-w-ptabs', 'prop' => '--ajs-tabs-border' ),
			),
			'slider' => array(
				'height'        => array( 'sel' => '.ajs-w-slider', 'prop' => '--ajs-slider-height', 'unit' => 'px' ),
				'radius'        => array( 'sel' => '.ajs-w-slider', 'prop' => '--ajs-slider-radius', 'unit' => 'px' ),
				'overlay_color' => array( 'sel' => '.ajs-w-slider__overlay' ),
				'heading_color' => array( 'sel' => '.ajs-w-slider__heading' ),
				'text_color'    => array( 'sel' => '.ajs-w-slider__text' ),
				'button_bg'     => array( 'sel' => '.ajs-w-slider__btn' ),
				'button_color'  => array( 'sel' => '.ajs-w-slider__btn' ),
			),
			// The Row paints padding/background on the inner .ajs-b-row, so its
			// per-device overrides must target that element too (margin stays on
			// the wrapper).
			'row' => array(
				'padding'  => array( 'sel' => '.ajs-b-row' ),
				'bg_color' => array( 'sel' => '.ajs-b-row' ),
				'gap'      => array( 'sel' => '.ajs-b-row', 'prop' => 'gap', 'unit' => 'px' ),
			),
			'nav' => array(
				'gap'         => array( 'sel' => '.ajs-w-nav', 'prop' => '--ajs-nav-gap', 'unit' => 'px' ),
				'color'       => array( 'sel' => '.ajs-w-nav' ),
				'hover_color' => array( 'sel' => '.ajs-w-nav', 'prop' => '--ajs-nav-hover' ),
			),
			'search' => array(
				'color' => array( 'sel' => '.ajs-w-search' ),
			),
			'cart' => array(
				'color' => array( 'sel' => '.ajs-w-cart__link' ),
			),
			'account' => array(
				'color'       => array( 'sel' => '.ajs-w-account__link' ),
				'hover_color' => array( 'sel' => '.ajs-w-account__link', 'prop' => '--ajs-acc-hover' ),
			),
			'compare' => array(
				'color'       => array( 'sel' => '.ajs-w-compare__link' ),
				'hover_color' => array( 'sel' => '.ajs-w-compare__link', 'prop' => '--ajs-cmp-hover' ),
			),
			'google-map' => array(
				'radius' => array( 'sel' => '.ajs-w-gmap', 'unit' => 'px' ),
			),
			'posts' => array(
				'gap'         => array( 'sel' => '.ajs-w-posts', 'prop' => '--ajs-posts-gap', 'unit' => 'px' ),
				'radius'      => array( 'sel' => '.ajs-w-posts', 'prop' => '--ajs-posts-radius', 'unit' => 'px' ),
				'card_bg'     => array( 'sel' => '.ajs-post' ),
				'title_color' => array( 'sel' => '.ajs-post__title' ),
				'text_color'  => array( 'sel' => '.ajs-post__excerpt' ),
				'meta_color'  => array( 'sel' => '.ajs-post__meta' ),
			),
			'loop-carousel' => array(
				'gap'         => array( 'sel' => '.ajs-w-loop', 'prop' => '--ajs-loop-gap', 'unit' => 'px' ),
				'radius'      => array( 'sel' => '.ajs-w-loop', 'prop' => '--ajs-posts-radius', 'unit' => 'px' ),
				'card_bg'     => array( 'sel' => '.ajs-post' ),
				'title_color' => array( 'sel' => '.ajs-post__title' ),
				'text_color'  => array( 'sel' => '.ajs-post__excerpt' ),
				'meta_color'  => array( 'sel' => '.ajs-post__meta' ),
			),
			'author-box' => array(
				'bg_color'   => array( 'sel' => '.ajs-authorbox' ),
				'name_color' => array( 'sel' => '.ajs-authorbox__name' ),
				'text_color' => array( 'sel' => '.ajs-authorbox__bio' ),
				'radius'     => array( 'sel' => '.ajs-authorbox', 'unit' => 'px' ),
			),
		);

		return isset( $map[ $type ] ) ? $map[ $type ] : array();
	}

	/**
	 * Does a section paint its background on a dedicated opacity layer?
	 *
	 * Returns true when the Background opacity control is below 100%, in which
	 * case the background colour/image lives on `.ajs-b-section__bg` instead of
	 * on the <section> itself.
	 *
	 * @param array $settings Section settings.
	 * @return bool
	 */
	private static function section_bg_layer( $settings ) {
		if ( ! is_array( $settings ) ) {
			return false;
		}
		$op = ajs_builder_get( $settings, 'bg_opacity', '' );
		return ( '' !== $op && null !== $op && (float) $op < 100 );
	}

	/**
	 * Does the settings array contain any per-device override keys?
	 *
	 * @param array $settings Settings.
	 * @return bool
	 */
	private static function has_device_overrides( $settings ) {
		if ( ! is_array( $settings ) ) {
			return false;
		}
		foreach ( $settings as $key => $value ) {
			if ( is_string( $key ) && ( '_tablet' === substr( $key, -7 ) || '_mobile' === substr( $key, -7 ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Margin rules that reproduce the desktop alignment of the image widget.
	 *
	 * @param string $align left|center|right.
	 * @return array
	 */
	private static function image_align_rules( $align ) {
		if ( 'center' === $align ) {
			return array( 'margin-left' => 'auto', 'margin-right' => 'auto' );
		}
		if ( 'right' === $align ) {
			return array( 'margin-left' => 'auto', 'margin-right' => '0' );
		}
		if ( 'left' === $align ) {
			return array( 'margin-left' => '0', 'margin-right' => '0' );
		}
		return array();
	}

	/**
	 * Is a stored per-device override value a real value (not "inherit")?
	 *
	 * @param mixed $value Stored value.
	 * @return bool
	 */
	private static function has_override_value( $value ) {
		if ( is_array( $value ) ) {
			return ( ! empty( $value['url'] ) || ! empty( $value['id'] ) );
		}
		if ( is_bool( $value ) ) {
			return $value;
		}
		return ( '' !== $value && null !== $value );
	}

	/**
	 * Widget "Content" controls that must be re-rendered (not CSS-overridden)
	 * to change per device: text, media, links, icons, ...
	 *
	 * @param string $type Widget type.
	 * @return array Setting keys.
	 */
	private static function content_variant_keys( $type ) {
		static $cache = array();
		if ( isset( $cache[ $type ] ) ) {
			return $cache[ $type ];
		}
		$keys = array();
		$safe = Ajs_Builder_Widgets::content_variant_types();
		if ( in_array( $type, $safe, true ) ) {
			$def = Ajs_Builder_Widgets::get( $type );
			if ( $def && ! empty( $def['controls'] ) && is_array( $def['controls'] ) ) {
				foreach ( $def['controls'] as $key => $control ) {
					if ( Ajs_Builder_Widgets::is_content_variant_control( $type, $control ) ) {
						$keys[] = $key;
					}
				}
			}
		}
		$cache[ $type ] = $keys;
		return $keys;
	}

	/**
	 * Merge a content key's per-device value onto the base value.
	 *
	 * @param array  $settings Settings.
	 * @param array  $keys     Content keys.
	 * @param string $device   tablet|mobile.
	 * @return array
	 */
	private static function merge_content_overrides( $settings, $keys, $device ) {
		$suffix = '_' . $device;
		$out    = $settings;
		foreach ( $keys as $key ) {
			$dk = $key . $suffix;
			if ( isset( $settings[ $dk ] ) && self::has_override_value( $settings[ $dk ] ) ) {
				$out[ $key ] = $settings[ $dk ];
			}
		}
		return $out;
	}

	/**
	 * Render a widget's content once per device and emit visibility CSS so only
	 * the matching version shows. Used for content that cannot be expressed as
	 * a CSS override (text, image source, links, ...).
	 *
	 * @param string $type     Widget type.
	 * @param array  $settings Settings.
	 * @param array  $widget   Widget data.
	 * @param string $id       Widget id.
	 * @return array array( html, css ).
	 */
	private static function content_variants( $type, $settings, $widget, $id ) {
		$keys = self::content_variant_keys( $type );
		if ( ! $keys || ! $id ) {
			return array( '', '' );
		}

		$has_t = false;
		$has_m = false;
		foreach ( $keys as $key ) {
			if ( isset( $settings[ $key . '_tablet' ] ) && self::has_override_value( $settings[ $key . '_tablet' ] ) ) {
				$has_t = true;
			}
			if ( isset( $settings[ $key . '_mobile' ] ) && self::has_override_value( $settings[ $key . '_mobile' ] ) ) {
				$has_m = true;
			}
		}
		if ( ! $has_t && ! $has_m ) {
			return array( '', '' );
		}

		$html = '<div class="ajs-rv ajs-rv--desktop">' . self::widget_inner( $type, $settings, $widget ) . '</div>';
		if ( $has_t ) {
			$html .= '<div class="ajs-rv ajs-rv--tablet">' . self::widget_inner( $type, self::merge_content_overrides( $settings, $keys, 'tablet' ), $widget ) . '</div>';
		}
		if ( $has_m ) {
			$html .= '<div class="ajs-rv ajs-rv--mobile">' . self::widget_inner( $type, self::merge_content_overrides( self::merge_content_overrides( $settings, $keys, 'tablet' ), $keys, 'mobile' ), $widget ) . '</div>';
		}

		$sel  = '[data-ajs-id="' . esc_attr( $id ) . '"]';
		$hide = array();
		if ( $has_t ) {
			$hide[] = $sel . ' .ajs-rv--tablet';
		}
		if ( $has_m ) {
			$hide[] = $sel . ' .ajs-rv--mobile';
		}
		$css = implode( ',', $hide ) . '{display:none!important;}';

		if ( $has_t ) {
			$css .= '@media (max-width:1024px){'
				. $sel . ' .ajs-rv--desktop{display:none!important;}'
				. $sel . ' .ajs-rv--tablet{display:block!important;}'
				. '}';
		}
		if ( $has_m ) {
			$css .= '@media (max-width:767px){'
				. $sel . ' .ajs-rv--desktop{display:none!important;}'
				. ( $has_t ? $sel . ' .ajs-rv--tablet{display:none!important;}' : '' )
				. $sel . ' .ajs-rv--mobile{display:block!important;}'
				. '}';
		}

		return array( $html, '<style>' . $css . '</style>' );
	}

	/**
	 * Build a scoped <style> block that applies per-device overrides. The base
	 * (desktop) values stay inline on the element; the tablet/mobile rules are
	 * emitted as media queries and flagged !important so they win over the
	 * inline styles.
	 *
	 * @param string $scope    section|column|widget.
	 * @param string $id       Element id (used for the scoped selector).
	 * @param array  $settings Settings.
	 * @param string $type     Widget type (only used when scope is widget).
	 * @return string
	 */
	private static function responsive_block( $scope, $id, $settings, $type = '' ) {
		if ( ! $id || ! self::has_device_overrides( $settings ) ) {
			return '';
		}

		$map  = self::responsive_control_map( $scope, $type );
		$root = '[data-ajs-id="' . esc_attr( $id ) . '"]';

		if ( 'widget' === $scope ) {
			$sels = self::responsive_selectors( $type );
		} elseif ( 'section' === $scope && self::section_bg_layer( $settings ) ) {
			// The background lives on the opacity layer, so per-device
			// background colours must target that layer too.
			$sels = array( 'bg_color' => array( 'sel' => '.ajs-b-section__bg' ) );
		} else {
			$sels = array();
		}

		$tablet = self::responsive_device_values( $scope, self::device_settings( $settings, 'tablet' ), $type );
		$mobile = self::responsive_device_values( $scope, self::device_settings( $settings, 'mobile' ), $type );

		$t_over = self::collect_device_overrides( self::device_override_keys( $settings, 'tablet' ), $map, $tablet, $sels, $root );
		$m_over = self::collect_device_overrides( self::device_override_keys( $settings, 'mobile' ), $map, $mobile, $sels, $root );

		// Typography produces several CSS properties at once, so it is handled
		// separately from the flat key => property map.
		if ( 'widget' === $scope ) {
			if ( isset( $settings['typography_tablet'] ) ) {
				$typo = ajs_builder_typography_css( $settings['typography_tablet'] );
				if ( $typo ) {
					$t_over = self::add_rules( $t_over, $root, $typo );
				}
			}
			if ( isset( $settings['typography_mobile'] ) ) {
				$typo = ajs_builder_typography_css( $settings['typography_mobile'] );
				if ( $typo ) {
					$m_over = self::add_rules( $m_over, $root, $typo );
				}
			}
		}

		// Column module direction maps to several flex properties, so it is
		// handled separately from the flat key => property map.
		if ( 'column' === $scope ) {
			if ( isset( $settings['direction_tablet'] ) ) {
				$t_over = self::add_rules( $t_over, $root, self::col_dir_rules( $settings['direction_tablet'] ) );
			}
			if ( isset( $settings['direction_mobile'] ) ) {
				$m_over = self::add_rules( $m_over, $root, self::col_dir_rules( $settings['direction_mobile'] ) );
			}
		}

		// Image alignment is stored as figure margins rather than a single
		// property, so it is handled separately for the image widget.
		if ( 'widget' === $scope && 'image' === $type ) {
			if ( ! empty( $settings['align_tablet'] ) ) {
				$t_over = self::add_rules( $t_over, $root . ' .ajs-w-image', self::image_align_rules( $settings['align_tablet'] ) );
			}
			if ( ! empty( $settings['align_mobile'] ) ) {
				$m_over = self::add_rules( $m_over, $root . ' .ajs-w-image', self::image_align_rules( $settings['align_mobile'] ) );
			}
		}

		// Section/column background images can be swapped per device.
		if ( 'section' === $scope || 'column' === $scope ) {
			$bg_sel = ( 'section' === $scope && self::section_bg_layer( $settings ) )
				? $root . ' .ajs-b-section__bg'
				: $root;
			foreach ( array( 'tablet', 'mobile' ) as $bg_dev ) {
				$bg_url = self::media_url( ajs_builder_get( $settings, 'bg_image_' . $bg_dev, array() ) );
				if ( ! $bg_url ) {
					continue;
				}
				$bg_rules = array(
					'background-image'    => 'url(' . esc_url_raw( $bg_url ) . ')',
					'background-position' => ajs_builder_get( $settings, 'bg_position', 'center center' ),
					'background-repeat'   => ajs_builder_get( $settings, 'bg_repeat', 'no-repeat' ),
					'background-size'     => ajs_builder_get( $settings, 'bg_size', 'cover' ),
				);
				if ( 'tablet' === $bg_dev ) {
					$t_over = self::add_rules( $t_over, $bg_sel, $bg_rules );
				} else {
					$m_over = self::add_rules( $m_over, $bg_sel, $bg_rules );
				}
			}
		}

		if ( ! $t_over && ! $m_over ) {
			return '';
		}

		$css = '';
		if ( $t_over ) {
			$css .= '@media (max-width:1024px){' . self::overrides_css( $t_over ) . '}';
		}
		if ( $m_over ) {
			$css .= '@media (max-width:767px){' . self::overrides_css( $m_over ) . '}';
		}
		return '<style>' . $css . '</style>';
	}

	/**
	 * Merge a set of declarations onto a selector bucket.
	 *
	 * @param array  $buckets Selector => rules.
	 * @param string $selector Selector.
	 * @param array  $rules    Declarations.
	 * @return array
	 */
	private static function add_rules( $buckets, $selector, $rules ) {
		if ( ! $rules ) {
			return $buckets;
		}
		$buckets[ $selector ] = isset( $buckets[ $selector ] )
			? array_merge( $buckets[ $selector ], $rules )
			: $rules;
		return $buckets;
	}

	/**
	 * Turn selector buckets into a CSS string.
	 *
	 * @param array $buckets Selector => rules.
	 * @return string
	 */
	private static function overrides_css( $buckets ) {
		$out = '';
		foreach ( $buckets as $selector => $rules ) {
			$decl = self::css_declarations( $rules );
			if ( '' !== $decl ) {
				$out .= $selector . '{' . $decl . '}';
			}
		}
		return $out;
	}

	/**
	 * The base keys that have an explicit override for a device.
	 *
	 * @param array  $settings Settings.
	 * @param string $device   tablet|mobile.
	 * @return array Key => true.
	 */
	private static function device_override_keys( $settings, $device ) {
		if ( ! is_array( $settings ) ) {
			return array();
		}
		$suffix = '_' . $device;
		$len    = strlen( $suffix );
		$keys   = array();
		foreach ( $settings as $key => $value ) {
			if ( is_string( $key ) && strlen( $key ) > $len && substr( $key, -$len ) === $suffix ) {
				$keys[ substr( $key, 0, -$len ) ] = true;
			}
		}
		return $keys;
	}

	/**
	 * Map explicitly overridden settings to selector => declarations using the
	 * computed values for that device. Each setting keeps its own target
	 * element so controls that share a CSS property (title_color /
	 * text_color) do not clobber one another.
	 *
	 * @param array  $keys   Override keys (key => true).
	 * @param array  $map    settings key => CSS property map.
	 * @param array  $values settings key => CSS value for the device.
	 * @param array  $sels   settings key => target info (sel/prop/unit).
	 * @param string $root   Root selector for the element.
	 * @return array selectors => declarations.
	 */
	private static function collect_device_overrides( $keys, $map, $values, $sels, $root ) {
		$over = array();
		foreach ( $keys as $key => $unused ) {
			if ( ! isset( $map[ $key ] ) || ! isset( $values[ $key ] ) || '' === $values[ $key ] ) {
				continue;
			}
			$info = isset( $sels[ $key ] ) ? $sels[ $key ] : array();
			$prop = ! empty( $info['prop'] ) ? $info['prop'] : $map[ $key ];
			$sel  = ! empty( $info['sel'] ) ? $root . ' ' . $info['sel'] : $root;
			$over = self::add_rules( $over, $sel, array( $prop => $values[ $key ] ) );
		}
		return $over;
	}

	/**
	 * Comprehensive map of responsive control keys to CSS properties.
	 * Extends the base map with widget-specific style controls.
	 *
	 * @param string $scope Element scope (section|column|widget).
	 * @param string $type  Widget type (for widget scope).
	 * @return array
	 */
	private static function responsive_control_map( $scope, $type = '' ) {
		$map = array(
			// Box model
			'padding'     => 'padding',
			'margin'      => 'margin',
			// Colors
			'bg_color'    => 'background-color',
			'text_color'  => 'color',
			'heading_color' => 'color',
			'overlay_color' => 'background-color',
			// Widget-specific responsive controls
			// Heading
			'color'       => 'color',
			'subtitle_color' => 'color',
			// Typography is handled separately via ajs_builder_typography_css
			// Image
			'width'       => 'width',
			'radius'      => 'border-radius',
			'opacity'     => 'opacity',
			'fit'         => 'object-fit',
			// Button
			'bg_color'    => 'background-color',
			'text_color'  => 'color',
			// Divider
			'weight'      => 'border-width',
			'color'       => 'border-color',
			// Spacer
			'space'       => 'height',
			// Icon
			'color'       => 'color',
			'size'        => 'font-size',
			// Icon Box
			'icon_color'  => 'color',
			'icon_size'   => 'font-size',
			'icon_bg'     => 'background-color',
			'icon_padding'=> 'padding',
			'title_color' => 'color',
			// Icon List
			'icon_color'  => 'color',
			'text_color'  => 'color',
			'space'       => 'gap',
			// Counter
			'number_color'=> 'color',
			'title_color' => 'color',
			'icon_color'  => 'color',
			'icon_size'   => 'font-size',
			'bg_color'    => 'background-color',
			'radius'      => 'border-radius',
			// Testimonial
			'quote_color' => 'color',
			'name_color'  => 'color',
			// CTA
			'bg_color'    => 'background-color',
			'text_color'  => 'color',
			'button_bg'   => 'background-color',
			'button_color'=> 'color',
			'radius'      => 'border-radius',
			// Accordion/Tabs
			'active_color'=> 'color',
			'border_color'=> 'border-color',
			// Progress
			'bar_color'   => 'background-color',
			'track_color' => 'background-color',
			'height'      => 'height',
			// Social
			'color'       => 'color',
			'bg'          => 'background-color',
			// Gallery
			'radius'      => 'border-radius',
			// Nav
			'gap'         => 'gap',
			'color'       => 'color',
			'hover_color' => 'color',
			// Search
			// Account/Compare
			'hover_color' => 'color',
			// Posts/Loop
			'gap'         => 'gap',
			'radius'      => 'border-radius',
			'card_bg'     => 'background-color',
			'title_color' => 'color',
			'meta_color'  => 'color',
			// Author Box
			'bg_color'    => 'background-color',
			'name_color'  => 'color',
			'radius'      => 'border-radius',
			// Google Map
			'height'      => 'height',
			'radius'      => 'border-radius',
		);

		return $map;
	}

	/**
	 * Render a rule map as !important CSS declarations.
	 *
	 * @param array $rules Rules.
	 * @return string
	 */
	private static function css_declarations( $rules ) {
		$out = '';
		foreach ( $rules as $prop => $value ) {
			if ( '' === $value || null === $value ) {
				continue;
			}
			if ( 'background-color' === $prop && ajs_builder_is_gradient( $value ) ) {
				$prop = 'background-image';
			}
			$out .= $prop . ':' . esc_attr( $value ) . '!important;';
		}
		return $out;
	}

	/**
	 * Box (margin/padding) rules for an element.
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	private static function box_rules( $settings ) {
		$rules = array();
		if ( ! empty( $settings['padding'] ) ) {
			$v = self::box_value( $settings['padding'] );
			if ( '' !== $v ) {
				$rules['padding'] = $v;
			}
		}
		if ( ! empty( $settings['margin'] ) ) {
			$v = self::box_value( $settings['margin'] );
			if ( '' !== $v ) {
				$rules['margin'] = $v;
			}
		}
		return $rules;
	}

	/**
	 * Box value shorthand.
	 *
	 * @param mixed $value Box value.
	 * @return string
	 */
	private static function box_value( $value ) {
		if ( ! is_array( $value ) ) {
			return '';
		}
		$unit = isset( $value['unit'] ) ? $value['unit'] : 'px';
		$get  = function ( $k ) use ( $value, $unit ) {
			return ( isset( $value[ $k ] ) && '' !== $value[ $k ] ) ? $value[ $k ] . $unit : '0';
		};
		$t = $get( 'top' );
		$r = $get( 'right' );
		$b = $get( 'bottom' );
		$l = $get( 'left' );
		if ( $t === '0' && $r === '0' && $b === '0' && $l === '0' ) {
			return '';
		}
		if ( $t === $r && $r === $b && $b === $l ) {
			return $t;
		}
		return $t . ' ' . $r . ' ' . $b . ' ' . $l;
	}

	/**
	 * Map a vertical-align choice to a CSS align-items value.
	 *
	 * "top" historically meant the browser default (stretch) — keep that so
	 * existing documents do not change appearance.
	 *
	 * @param string $value Choice.
	 * @return string
	 */
	private static function align_map( $value ) {
		$map = array(
			''        => 'stretch',
			'top'     => 'stretch',
			'stretch' => 'stretch',
			'middle'  => 'center',
			'center'  => 'center',
			'bottom'  => 'flex-end',
			'end'     => 'flex-end',
			'start'   => 'flex-start',
		);
		return isset( $map[ $value ] ) ? $map[ $value ] : 'stretch';
	}

	/**
	 * Map a horizontal-align choice to a CSS justify-content value.
	 *
	 * @param string $value Choice.
	 * @return string
	 */
	private static function justify_map( $value ) {
		$map = array(
			'start'   => 'flex-start',
			'center'  => 'center',
			'end'     => 'flex-end',
			'between' => 'space-between',
			'around'  => 'space-around',
			'evenly'  => 'space-evenly',
		);
		return isset( $map[ $value ] ) ? $map[ $value ] : 'flex-start';
	}

	/**
	 * CSS rules that lay the modules inside a column out vertically (stacked)
	 * or horizontally (inline). "column" is the stylesheet default, so the
	 * explicit reset values are only meaningful when overriding a desktop row
	 * from a per-device media query.
	 *
	 * @param string $dir column|row.
	 * @return array
	 */
	private static function col_dir_rules( $dir ) {
		if ( 'row' === $dir ) {
			return array(
				'flex-direction' => 'row',
				'flex-wrap'      => 'wrap',
				'align-items'    => 'flex-start',
				'column-gap'     => '16px',
				'row-gap'        => '16px',
			);
		}
		return array(
			'flex-direction' => 'column',
			'flex-wrap'      => 'nowrap',
			'align-items'    => 'stretch',
			'column-gap'     => '0',
			'row-gap'        => '0',
		);
	}

	/**
	 * Map a column content-align choice to a CSS justify-content value.
	 *
	 * @param string $value Choice.
	 * @return string
	 */
	private static function col_justify_map( $value ) {
		$map = array(
			''       => 'flex-start',
			'top'    => 'flex-start',
			'center' => 'center',
			'bottom' => 'flex-end',
		);
		return isset( $map[ $value ] ) ? $map[ $value ] : 'flex-start';
	}

	/**
	 * CSS custom properties that drive the responsive row layout. Values are
	 * consumed by the media queries in builder-frontend.css.
	 *
	 * @param array $settings Section settings.
	 * @return array
	 */
	private static function row_rules( $settings ) {
		$rules = array();

		$gap                    = ajs_builder_get( $settings, 'gap', 24 );
		$rules['--ajs-row-gap'] = (int) $gap . 'px';
		if ( isset( $settings['gap_tablet'] ) && '' !== $settings['gap_tablet'] ) {
			$rules['--ajs-row-gap-t'] = (int) $settings['gap_tablet'] . 'px';
		}
		if ( isset( $settings['gap_mobile'] ) && '' !== $settings['gap_mobile'] ) {
			$rules['--ajs-row-gap-m'] = (int) $settings['gap_mobile'] . 'px';
		}

		$rules['--ajs-row-align'] = self::align_map( ajs_builder_get( $settings, 'valign', 'top' ) );
		if ( ! empty( $settings['valign_tablet'] ) ) {
			$rules['--ajs-row-align-t'] = self::align_map( $settings['valign_tablet'] );
		}
		if ( ! empty( $settings['valign_mobile'] ) ) {
			$rules['--ajs-row-align-m'] = self::align_map( $settings['valign_mobile'] );
		}

		$rules['--ajs-row-justify'] = self::justify_map( ajs_builder_get( $settings, 'justify', 'start' ) );
		if ( ! empty( $settings['justify_tablet'] ) ) {
			$rules['--ajs-row-justify-t'] = self::justify_map( $settings['justify_tablet'] );
		}
		if ( ! empty( $settings['justify_mobile'] ) ) {
			$rules['--ajs-row-justify-m'] = self::justify_map( $settings['justify_mobile'] );
		}

		$wrap                    = ajs_builder_get( $settings, 'wrap', 'wrap' );
		$rules['--ajs-row-wrap'] = ( 'nowrap' === $wrap ) ? 'nowrap' : 'wrap';
		if ( isset( $settings['wrap_tablet'] ) && in_array( $settings['wrap_tablet'], array( 'wrap', 'nowrap' ), true ) ) {
			$rules['--ajs-row-wrap-t'] = $settings['wrap_tablet'];
		}
		if ( isset( $settings['wrap_mobile'] ) && in_array( $settings['wrap_mobile'], array( 'wrap', 'nowrap' ), true ) ) {
			$rules['--ajs-row-wrap-m'] = $settings['wrap_mobile'];
		}

		return $rules;
	}

	/**
	 * Per-device "stack columns" flags as data attributes on the row. Stacking
	 * on mobile defaults to on to preserve the previous behaviour.
	 *
	 * @param array $settings Section settings.
	 * @return string
	 */
	private static function row_data_attrs( $settings ) {
		$tablet = isset( $settings['stack_tablet'] ) ? (bool) $settings['stack_tablet'] : false;
		$mobile = isset( $settings['stack_mobile'] ) ? (bool) $settings['stack_mobile'] : true;
		return ' data-ajs-stack-t="' . ( $tablet ? '1' : '0' ) . '" data-ajs-stack-m="' . ( $mobile ? '1' : '0' ) . '"';
	}

	/**
	 * Responsive flex/max-width custom properties for a column.
	 *
	 * @param array $settings Column settings.
	 * @return array
	 */
	private static function col_width_rules( $settings ) {
		$rules = array();
		$width = ajs_builder_get( $settings, 'width', '' );
		if ( '' !== $width && (float) $width > 0 ) {
			$rules['--ajs-col-flex'] = '0 0 ' . (float) $width . '%';
			$rules['--ajs-col-max']  = (float) $width . '%';
		}
		if ( isset( $settings['width_tablet'] ) && '' !== $settings['width_tablet'] && (float) $settings['width_tablet'] > 0 ) {
			$rules['--ajs-col-flex-t'] = '0 0 ' . (float) $settings['width_tablet'] . '%';
			$rules['--ajs-col-max-t']  = (float) $settings['width_tablet'] . '%';
		}
		if ( isset( $settings['width_mobile'] ) && '' !== $settings['width_mobile'] && (float) $settings['width_mobile'] > 0 ) {
			$rules['--ajs-col-flex-m'] = '0 0 ' . (float) $settings['width_mobile'] . '%';
			$rules['--ajs-col-max-m']  = (float) $settings['width_mobile'] . '%';
		}
		return $rules;
	}

	/**
	 * Resolve a media control value to a URL.
	 *
	 * @param mixed $value Media value array.
	 * @return string
	 */
	private static function media_url( $value ) {
		if ( is_string( $value ) && $value ) {
			return $value;
		}
		if ( is_array( $value ) && ! empty( $value['url'] ) ) {
			return $value['url'];
		}
		return '';
	}

	/**
	 * Resolve a media value to an attachment id.
	 *
	 * @param mixed $value Media value array.
	 * @return int
	 */
	private static function media_id( $value ) {
		if ( is_array( $value ) && ! empty( $value['id'] ) ) {
			return (int) $value['id'];
		}
		return 0;
	}

	/* =====================================================================
	 * Widget renderers
	 * ===================================================================== */

	/**
	 * Dispatch a widget to its renderer.
	 *
	 * @param string $type     Widget type.
	 * @param array  $s        Settings.
	 * @param array  $widget   Widget data.
	 * @return string
	 */
	public static function widget_inner( $type, $s, $widget ) {
		$method = 'w_' . str_replace( '-', '_', $type );
		if ( method_exists( __CLASS__, $method ) ) {
			return (string) self::$method( $s, $widget );
		}
		return '';
	}

	private static function w_row( $s, $widget = array() ) {
		$rules = self::row_rules( $s );

		// Padding lives here (not on the widget wrapper) so it paints inside
		// the row background.
		$box = self::box_rules( $s );
		if ( ! empty( $box['padding'] ) ) {
			$rules['padding'] = $box['padding'];
		}

		if ( ajs_builder_get( $s, 'bg_color', '' ) ) {
			$rules['background-color'] = ajs_builder_get( $s, 'bg_color', '' );
			// A gradient cannot be painted by background-color.
			if ( ajs_builder_is_gradient( $rules['background-color'] ) ) {
				$rules['background-image'] = $rules['background-color'];
				unset( $rules['background-color'] );
			}
		}

		$columns = isset( $widget['columns'] ) && is_array( $widget['columns'] ) ? $widget['columns'] : array();
		$cols = '';
		foreach ( $columns as $column ) {
			$cols .= self::column( $column );
		}
		if ( '' === $cols ) {
			$cols = '<div class="ajs-b-empty">' . esc_html__( 'Drag a module here', 'ajs-builder' ) . '</div>';
		}

		return '<div class="ajs-b-row ajs-w-row"' . self::row_data_attrs( $s ) . ajs_builder_style( $rules ) . '>' . $cols . '</div>';
	}

	private static function w_heading( $s ) {
		$text     = ajs_builder_get( $s, 'text', '' );
		$subtitle = ajs_builder_get( $s, 'subtitle', '' );
		$tag  = ajs_builder_get( $s, 'tag', 'h2' );
		if ( ! in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ), true ) ) {
			$tag = 'h2';
		}
		$align = ajs_builder_get( $s, 'align', 'left' );
		$rules = array( 'text-align' => $align );
		if ( ajs_builder_get( $s, 'color', '' ) ) {
			$rules['color'] = ajs_builder_get( $s, 'color', '' );
		}
		$rules = array_merge( $rules, ajs_builder_typography_css( ajs_builder_get( $s, 'typography', array() ) ) );

		$inner = esc_html( $text );
		if ( ajs_builder_get( $s, 'link', '' ) ) {
			$inner = '<a href="' . esc_url( ajs_builder_get( $s, 'link', '' ) ) . '">' . $inner . '</a>';
		}
		$heading = '<' . $tag . ' class="ajs-w-heading ajs-align-' . esc_attr( $align ) . '"' . ajs_builder_style( $rules ) . '>' . $inner . '</' . $tag . '>';

		if ( '' === (string) $subtitle ) {
			return $heading;
		}

		$sub_rules = array();
		if ( ajs_builder_get( $s, 'subtitle_color', '' ) ) {
			$sub_rules['color'] = ajs_builder_get( $s, 'subtitle_color', '' );
		}
		$sub = '<p class="ajs-w-heading__subtitle"' . ajs_builder_style( $sub_rules ) . '>' . esc_html( $subtitle ) . '</p>';

		return '<div class="ajs-w-heading-wrap ajs-align-' . esc_attr( $align ) . '">' . $heading . $sub . '</div>';
	}

	private static function w_text( $s ) {
		$align = ajs_builder_get( $s, 'align', 'left' );
		$rules = array( 'text-align' => $align );
		if ( ajs_builder_get( $s, 'color', '' ) ) {
			$rules['color'] = ajs_builder_get( $s, 'color', '' );
		}
		return '<div class="ajs-w-text"' . ajs_builder_style( $rules ) . '>' . wpautop( wp_kses_post( ajs_builder_get( $s, 'text', '' ) ) ) . '</div>';
	}

	private static function w_image( $s ) {
		$url      = self::media_url( ajs_builder_get( $s, 'image', array() ) );
		$id       = self::media_id( ajs_builder_get( $s, 'image', array() ) );
		$align    = ajs_builder_get( $s, 'align', 'left' );
		$width    = (float) ajs_builder_get( $s, 'width', 100 );
		$px_width = (float) ajs_builder_get( $s, 'px_width', 0 );
		$height   = (float) ajs_builder_get( $s, 'height', 0 );
		$fit      = ajs_builder_get( $s, 'fit', 'cover' );
		$radius   = (int) ajs_builder_get( $s, 'radius', 12 );
		$opacity  = (float) ajs_builder_get( $s, 'opacity', 100 ) / 100;
		$caption  = ajs_builder_get( $s, 'caption', '' );
		$link     = ajs_builder_get( $s, 'link', '' );

		$alt = $caption;
		if ( $id ) {
			$stored = get_post_meta( $id, '_wp_attachment_image_alt', true );
			if ( $stored ) {
				$alt = $stored;
			}
		}

		$fig = array( 'max-width' => '100%' );
		if ( $px_width > 0 ) {
			$fig['width'] = $px_width . 'px';
		} else {
			$fig['width'] = $width . '%';
		}
		if ( 'center' === $align ) {
			$fig['margin-inline'] = 'auto';
		} elseif ( 'right' === $align ) {
			$fig['margin-left'] = 'auto';
		}

		$img_rules = array( 'border-radius' => $radius . 'px', 'opacity' => $opacity );
		if ( $px_width > 0 || $height > 0 ) {
			$img_rules['width'] = '100%';
		}
		if ( $height > 0 ) {
			$img_rules['height']    = $height . 'px';
			$img_rules['object-fit'] = $fit;
		}

		if ( $url ) {
			$img = '<img class="ajs-w-image__img" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async"' . ajs_builder_style( $img_rules ) . '>';
			if ( $link ) {
				$img = '<a class="ajs-w-image__link" href="' . esc_url( $link ) . '">' . $img . '</a>';
			}
		} else {
			$img = '<span class="ajs-w-image__ph" aria-hidden="true">' . Ajs_Builder_Icons::svg( 'grid' ) . '<em>' . esc_html__( 'Choose an image', 'ajs-builder' ) . '</em></span>';
		}
		$cap = $caption ? '<figcaption class="ajs-w-image__cap">' . esc_html( $caption ) . '</figcaption>' : '';

		return '<figure class="ajs-w-image ajs-w-image--' . esc_attr( $align ) . '"' . ajs_builder_style( $fig ) . '>' . $img . $cap . '</figure>';
	}

	private static function w_button( $s ) {
		$text     = ajs_builder_get( $s, 'text', '' );
		$url      = ajs_builder_get( $s, 'link', '#' );
		$target   = ajs_builder_get( $s, 'target', false );
		$size     = ajs_builder_get( $s, 'size', 'md' );
		$variant  = ajs_builder_get( $s, 'variant', 'solid' );
		$radius   = (int) ajs_builder_get( $s, 'radius', 8 );
		$align    = ajs_builder_get( $s, 'align', 'left' );
		$icon     = ajs_builder_get( $s, 'icon', '' );
		$icon_pos = ajs_builder_get( $s, 'icon_position', 'right' );
		$full     = ajs_builder_get( $s, 'full_width', false );
		$bg       = ajs_builder_get( $s, 'bg_color', '' );
		$color    = ajs_builder_get( $s, 'text_color', '' );

		$rules = array( 'border-radius' => $radius . 'px' );
		if ( 'outline' === $variant ) {
			$rules['border']       = '2px solid ' . ( $bg ? $bg : 'currentColor' );
			$rules['background']   = 'transparent';
			$rules['color']        = $color ? $color : ( $bg ? $bg : '' );
		} elseif ( 'link' === $variant ) {
			$rules['color'] = $color ? $color : ( $bg ? $bg : '' );
		} else {
			if ( $bg ) {
				$rules['background-color'] = $bg;
			}
			if ( $color ) {
				$rules['color'] = $color;
			}
		}
		if ( $full ) {
			$rules['width']           = '100%';
			$rules['justify-content'] = 'center';
		}

		$ic    = $icon ? Ajs_Builder_Icons::svg( $icon, 'ajs-btn__icon' ) : '';
		$inner = ( 'left' === $icon_pos ) ? $ic . '<span class="ajs-btn__label">' . esc_html( $text ) . '</span>'
			: '<span class="ajs-btn__label">' . esc_html( $text ) . '</span>' . $ic;

		$t = $target ? ' target="_blank" rel="noopener noreferrer"' : '';

		return '<div class="ajs-w-button ajs-w-button--' . esc_attr( $align ) . '"><a class="ajs-btn ajs-btn--' . esc_attr( $variant ) . ' ajs-btn--' . esc_attr( $size ) . '" href="' . esc_url( $url ? $url : '#' ) . '"' . $t . ajs_builder_style( $rules ) . '>' . $inner . '</a></div>';
	}

	private static function w_divider( $s ) {
		$style  = ajs_builder_get( $s, 'style', 'solid' );
		$weight = (float) ajs_builder_get( $s, 'weight', 1 );
		$width  = (float) ajs_builder_get( $s, 'width', 100 );
		$color  = ajs_builder_get( $s, 'color', '#e5e7eb' );
		$align  = ajs_builder_get( $s, 'align', 'left' );

		$rules = array(
			'border-top-style' => $style,
			'border-top-width' => $weight . 'px',
			'border-color'     => $color,
			'width'            => $width . '%',
		);
		if ( 'center' === $align ) {
			$rules['margin-inline'] = 'auto';
		} elseif ( 'right' === $align ) {
			$rules['margin-left'] = 'auto';
		}
		return '<div class="ajs-w-divider"><span class="ajs-w-divider__line"' . ajs_builder_style( $rules ) . '></span></div>';
	}

	private static function w_spacer( $s ) {
		$space = (int) ajs_builder_get( $s, 'space', 40 );
		return '<div class="ajs-w-spacer"' . ajs_builder_style( array( 'height' => $space . 'px' ) ) . ' aria-hidden="true"></div>';
	}

	private static function w_icon( $s ) {
		$icon  = ajs_builder_get( $s, 'icon', 'star' );
		$align = ajs_builder_get( $s, 'align', 'left' );
		$color = ajs_builder_get( $s, 'color', 'var(--ajs-primary)' );
		$size  = (int) ajs_builder_get( $s, 'size', 40 );
		$link  = ajs_builder_get( $s, 'link', '' );

		$svg = Ajs_Builder_Icons::svg( $icon );
		if ( $link ) {
			$svg = '<a href="' . esc_url( $link ) . '">' . $svg . '</a>';
		}
		return '<div class="ajs-w-icon ajs-w-icon--' . esc_attr( $align ) . '"' . ajs_builder_style( array( 'color' => $color, 'font-size' => $size . 'px' ) ) . '>' . $svg . '</div>';
	}

	private static function w_html( $s ) {
		return '<div class="ajs-w-html">' . wp_kses_post( ajs_builder_get( $s, 'html', '' ) ) . '</div>';
	}

	private static function w_icon_box( $s ) {
		$icon        = ajs_builder_get( $s, 'icon', 'star' );
		$title       = ajs_builder_get( $s, 'title', '' );
		$text        = ajs_builder_get( $s, 'text', '' );
		$link        = ajs_builder_get( $s, 'link', '' );
		$position    = ajs_builder_get( $s, 'position', 'top' );
		$align       = ajs_builder_get( $s, 'align', 'left' );
		$icon_color  = ajs_builder_get( $s, 'icon_color', 'var(--ajs-primary)' );
		$icon_size   = (int) ajs_builder_get( $s, 'icon_size', 34 );
		$icon_shape  = ajs_builder_get( $s, 'icon_shape', 'none' );
		$icon_bg     = ajs_builder_get( $s, 'icon_bg', '' );
		$icon_pad    = (int) ajs_builder_get( $s, 'icon_padding', 18 );
		$title_color = ajs_builder_get( $s, 'title_color', '' );
		$text_color  = ajs_builder_get( $s, 'text_color', '' );

		$icon_style = array(
			'color'     => $icon_color,
			'font-size' => $icon_size . 'px',
		);
		$icon_class = 'ajs-w-iconbox__icon';
		if ( in_array( $icon_shape, array( 'square', 'rounded', 'circle' ), true ) ) {
			$icon_class              .= ' ajs-w-iconbox__icon--' . $icon_shape;
			$icon_style['background'] = '' !== $icon_bg ? $icon_bg : 'color-mix(in srgb, var(--ajs-primary, #c8102e) 12%, transparent)';
			$icon_style['padding']    = $icon_pad . 'px';
		}

		$svg = '<span class="' . esc_attr( $icon_class ) . '"' . ajs_builder_style( $icon_style ) . '>' . Ajs_Builder_Icons::svg( $icon ) . '</span>';

		$body = '<div class="ajs-w-iconbox__body">';
		if ( $title ) {
			$body .= '<h3 class="ajs-w-iconbox__title"' . ajs_builder_style( array( 'color' => $title_color ) ) . '>' . esc_html( $title ) . '</h3>';
		}
		if ( $text ) {
			$body .= '<p class="ajs-w-iconbox__text"' . ajs_builder_style( array( 'color' => $text_color ) ) . '>' . esc_html( $text ) . '</p>';
		}
		$body .= '</div>';

		$inner = $svg . $body;
		if ( $link ) {
			$inner = '<a class="ajs-w-iconbox__link" href="' . esc_url( $link ) . '">' . $inner . '</a>';
		}

		return '<div class="ajs-w-iconbox ajs-w-iconbox--' . esc_attr( $position ) . ' ajs-align-' . esc_attr( $align ) . '">' . $inner . '</div>';
	}

	/**
	 * Image Box: an image with title/description/button layered on top at one
	 * of nine content positions.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_image_box( $s ) {
		$url       = self::media_url( ajs_builder_get( $s, 'image', array() ) );
		$title     = ajs_builder_get( $s, 'title', '' );
		$text      = ajs_builder_get( $s, 'text', '' );
		$btn_text  = ajs_builder_get( $s, 'button_text', '' );
		$btn_link  = ajs_builder_get( $s, 'button_link', '' );
		$position  = ajs_builder_get( $s, 'content_position', 'bottom-left' );
		$align     = ajs_builder_get( $s, 'align', 'left' );
		$height    = (int) ajs_builder_get( $s, 'height', 340 );
		$radius    = (int) ajs_builder_get( $s, 'radius', 12 );
		$overlay   = ajs_builder_get( $s, 'overlay_color', 'rgba(15,23,42,.55)' );
		$overlay_o = max( 0, min( 100, (float) ajs_builder_get( $s, 'overlay_opacity', 100 ) ) ) / 100;

		$positions = array( 'top', 'top-left', 'top-right', 'middle', 'middle-left', 'middle-right', 'bottom', 'bottom-left', 'bottom-right' );
		if ( ! in_array( $position, $positions, true ) ) {
			$position = 'bottom-left';
		}

		$media = $url
			? '<img class="ajs-w-imgbox__img" src="' . esc_url( $url ) . '" alt="' . esc_attr( $title ) . '" loading="lazy" decoding="async">'
			: '<span class="ajs-w-imgbox__ph" aria-hidden="true">' . Ajs_Builder_Icons::svg( 'image' ) . '</span>';

		$overlay_attr = '';
		if ( '' !== $overlay && $overlay_o > 0 ) {
			$overlay_attr = ajs_builder_style(
				array(
					'background' => $overlay,
					'opacity'    => $overlay_o,
				)
			);
		}

		$content = '';
		if ( '' !== (string) $title ) {
			$content .= '<h3 class="ajs-w-imgbox__title"' . ajs_builder_style( array( 'color' => ajs_builder_get( $s, 'title_color', '' ) ) ) . '>' . esc_html( $title ) . '</h3>';
		}
		if ( '' !== (string) $text ) {
			$content .= '<p class="ajs-w-imgbox__text"' . ajs_builder_style( array( 'color' => ajs_builder_get( $s, 'text_color', '' ) ) ) . '>' . esc_html( $text ) . '</p>';
		}
		if ( '' !== (string) $btn_text ) {
			$btn = '<span class="ajs-w-imgbox__btn"' . ajs_builder_style(
				array(
					'background-color' => ajs_builder_get( $s, 'button_bg', '' ),
					'color'            => ajs_builder_get( $s, 'button_color', '' ),
				)
			) . '>' . esc_html( $btn_text ) . '</span>';
			if ( $btn_link ) {
				$btn = '<a class="ajs-w-imgbox__link" href="' . esc_url( $btn_link ) . '">' . $btn . '</a>';
			}
			$content .= $btn;
		}

		$rules = array(
			'height'        => max( 0, $height ) . 'px',
			'border-radius' => max( 0, $radius ) . 'px',
		);

		return '<div class="ajs-w-imgbox ajs-w-imgbox--' . esc_attr( $position ) . ' ajs-align-' . esc_attr( $align ) . '"' . ajs_builder_style( $rules ) . '>'
			. '<div class="ajs-w-imgbox__media">' . $media
			. ( '' !== $overlay_attr ? '<span class="ajs-w-imgbox__overlay"' . $overlay_attr . '></span>' : '' )
			. '</div>'
			. ( '' !== $content ? '<div class="ajs-w-imgbox__content">' . $content . '</div>' : '' )
			. '</div>';
	}

	private static function w_icon_list( $s ) {
		$items      = ajs_builder_get( $s, 'items', array() );
		$icon_color = ajs_builder_get( $s, 'icon_color', 'var(--ajs-primary)' );
		$text_color = ajs_builder_get( $s, 'text_color', '' );
		$space      = (int) ajs_builder_get( $s, 'space', 12 );
		$align      = ajs_builder_get( $s, 'align', 'left' );
		$layout     = ajs_builder_get( $s, 'layout', 'vertical' );
		$columns    = max( 1, min( 6, (int) ajs_builder_get( $s, 'columns', 3 ) ) );

		if ( ! is_array( $items ) ) {
			return '';
		}
		$out = '';
		foreach ( $items as $item ) {
			$text = isset( $item['text'] ) ? $item['text'] : '';
			$link = isset( $item['link'] ) ? $item['link'] : '';
			$icon = isset( $item['icon'] ) ? $item['icon'] : 'check';
			$label = '<span class="ajs-w-iconlist__text"' . ajs_builder_style( array( 'color' => $text_color ) ) . '>' . esc_html( $text ) . '</span>';
			if ( $link ) {
				$label = '<a href="' . esc_url( $link ) . '">' . $label . '</a>';
			}
			$out .= '<li class="ajs-w-iconlist__item"><span class="ajs-w-iconlist__icon"' . ajs_builder_style( array( 'color' => $icon_color ) ) . '>' . Ajs_Builder_Icons::svg( $icon ) . '</span>' . $label . '</li>';
		}

		$rules = array( 'gap' => $space . 'px' );
		if ( 'horizontal' === $layout ) {
			$rules['--ajs-iconlist-cols'] = $columns;
		}

		return '<ul class="ajs-w-iconlist ajs-w-iconlist--' . esc_attr( $layout ) . ' ajs-align-' . esc_attr( $align ) . '"' . ajs_builder_style( $rules ) . '>' . $out . '</ul>';
	}

	private static function w_counter( $s ) {
		$end       = ajs_builder_get( $s, 'end', 250 );
		$start     = ajs_builder_get( $s, 'start', 0 );
		$prefix    = ajs_builder_get( $s, 'prefix', '' );
		$suffix    = ajs_builder_get( $s, 'suffix', '' );
		$title     = ajs_builder_get( $s, 'title', '' );
		$align     = ajs_builder_get( $s, 'align', 'left' );
		$num_col   = ajs_builder_get( $s, 'number_color', 'var(--ajs-ink)' );
		$ttl_col   = ajs_builder_get( $s, 'title_color', '' );
		$icon      = ajs_builder_get( $s, 'icon', '' );
		$icon_col  = ajs_builder_get( $s, 'icon_color', 'var(--ajs-primary)' );
		$icon_size = (int) ajs_builder_get( $s, 'icon_size', 32 );
		$icon_pos  = ajs_builder_get( $s, 'icon_position', 'top' );
		$duration  = max( 200, (int) ajs_builder_get( $s, 'duration', 1100 ) );
		$bg        = ajs_builder_get( $s, 'bg_color', '' );
		$radius    = (int) ajs_builder_get( $s, 'radius', 0 );

		$wrap_rules = array();
		if ( $bg ) {
			$wrap_rules['background-color'] = $bg;
		}
		if ( $radius > 0 ) {
			$wrap_rules['border-radius'] = $radius . 'px';
		}

		$ic = $icon
			? '<span class="ajs-w-counter__icon"' . ajs_builder_style( array( 'color' => $icon_col, 'font-size' => $icon_size . 'px' ) ) . '>' . Ajs_Builder_Icons::svg( $icon ) . '</span>'
			: '';

		$num = '<span class="ajs-w-counter__num" data-counter data-start="' . esc_attr( $start ) . '" data-end="' . esc_attr( $end ) . '" data-prefix="' . esc_attr( $prefix ) . '" data-suffix="' . esc_attr( $suffix ) . '" data-duration="' . esc_attr( $duration ) . '"' . ajs_builder_style( array( 'color' => $num_col ) ) . '>' . esc_html( $prefix . $end . $suffix ) . '</span>';

		$body = '<div class="ajs-w-counter__body">' . $num
			. ( $title ? '<span class="ajs-w-counter__title"' . ajs_builder_style( array( 'color' => $ttl_col ) ) . '>' . esc_html( $title ) . '</span>' : '' )
			. '</div>';

		return '<div class="ajs-w-counter ajs-w-counter--icon-' . esc_attr( $icon_pos ) . ' ajs-align-' . esc_attr( $align ) . '"' . ajs_builder_style( $wrap_rules ) . '>' . $ic . $body . '</div>';
	}

	
	/**
	 * Author avatar markup. Falls back to the author's first initial inside a
	 * circle when no photo is set.
	 *
	 * @param string $avatar Avatar URL.
	 * @param string $name   Author name.
	 * @return string
	 */
	private static function testimonial_avatar( $avatar, $name ) {
		if ( $avatar ) {
			return "<img class=\"ajs-w-testimonial__avatar\" src=\"" . esc_url( $avatar ) . "\" alt=\"" . esc_attr( $name ) . "\" loading=\"lazy\">";
		}
		$name = trim( (string) $name );
		if ( "" === $name ) {
			return "";
		}
		$initial = function_exists( "mb_substr" ) ? mb_substr( $name, 0, 1 ) : substr( $name, 0, 1 );
		return "<span class=\"ajs-w-testimonial__avatar ajs-w-testimonial__avatar--initial\" aria-hidden=\"true\">" . esc_html( $initial ) . "</span>";
	}

	private static function w_testimonial( $s ) {
		$mode        = ajs_builder_get( $s, "mode", "single" );
		$style       = ajs_builder_get( $s, "style", "classic" );
		$testimonials = ajs_builder_get( $s, "testimonials", array() );
		$align       = ajs_builder_get( $s, "align", "left" );
		$quote_color = ajs_builder_get( $s, "quote_color", "" );
		$name_color  = ajs_builder_get( $s, "name_color", "" );
		$typography  = ajs_builder_get( $s, "typography", array() );
		$title       = ajs_builder_get( $s, "title", "" );
		$subtitle    = ajs_builder_get( $s, "subtitle", "" );
		$title_color = ajs_builder_get( $s, "title_color", "" );
		$title_size  = ajs_builder_get( $s, "title_size", "" );
		$sub_color   = ajs_builder_get( $s, "subtitle_color", "" );
		$sub_size    = ajs_builder_get( $s, "subtitle_size", "" );
		$card_bg     = ajs_builder_get( $s, "card_bg", "" );

		// "Card" style = the Classic markup wrapped in a bordered card with a
		// decorative quote mark. "classic" keeps the original output.
		$is_card   = ( "card" === $style );
		$style_mod = $is_card ? " ajs-w-testimonial--card" : "";
		$mark      = $is_card ? '<span class="ajs-w-testimonial__mark" aria-hidden="true">&ldquo;</span>' : "";
		$card_attr = $is_card ? ajs_builder_style( array( "background" => $card_bg ) ) : "";

		$title_attr = ajs_builder_style(
			array(
				"color"     => $title_color,
				"font-size" => ( "" !== $title_size && is_numeric( $title_size ) ) ? ( (int) $title_size ) . "px" : "",
			)
		);
		$sub_attr   = ajs_builder_style(
			array(
				"color"     => $sub_color,
				"font-size" => ( "" !== $sub_size && is_numeric( $sub_size ) ) ? ( (int) $sub_size ) . "px" : "",
			)
		);

		// Slider options
		$slides_desk  = max( 1, min( 4, (int) ajs_builder_get( $s, "slides_desktop", 1 ) ) );
		$slides_tab   = max( 1, min( 3, (int) ajs_builder_get( $s, "slides_tablet", 1 ) ) );
		$slides_mob   = max( 1, min( 2, (int) ajs_builder_get( $s, "slides_mobile", 1 ) ) );
		$gap          = max( 0, (int) ajs_builder_get( $s, "gap", 24 ) );
		$autoplay     = ajs_builder_get( $s, "autoplay", true );
		$speed        = max( 1000, (int) ajs_builder_get( $s, "speed", 5000 ) );
		$loop         = ajs_builder_get( $s, "loop_slides", true );
		$pause_hover  = ajs_builder_get( $s, "pause_hover", true );
		$arrows       = ajs_builder_get( $s, "show_arrows", true );
		$dots         = ajs_builder_get( $s, "show_dots", true );

		if ( ! is_array( $testimonials ) ) {
			$testimonials = array();
		}
		// Backward compatibility: older testimonial widgets stored a single
		// quote in top-level content/name/role keys instead of the repeater.
		if ( empty( $testimonials ) ) {
			$legacy = array(
				'content' => ajs_builder_get( $s, 'content', '' ),
				'name'    => ajs_builder_get( $s, 'name', '' ),
				'role'    => ajs_builder_get( $s, 'role', '' ),
				'avatar'  => ajs_builder_get( $s, 'avatar', array() ),
				'rating'  => ajs_builder_get( $s, 'rating', 5 ),
			);
			if ( '' !== $legacy['content'] || '' !== $legacy['name'] ) {
				$testimonials = array( $legacy );
			}
		}
		if ( empty( $testimonials ) ) {
			return "<div class=\"ajs-w-notice\">" . esc_html__( "No testimonials added.", "ajs-builder" ) . "</div>";
		}

		$typo_css = ajs_builder_typography_css( $typography );

		if ( "slider" === $mode ) {
			$slider_rules = array(
				"--ajs-testimonial-gap"     => max( 0, (int) ajs_builder_get( $s, "gap", 24 ) ) . "px",
				"--ajs-slides-desktop"      => max( 1, min( 4, (int) ajs_builder_get( $s, "slides_desktop", 1 ) ) ),
				"--ajs-slides-tablet"       => max( 1, min( 3, (int) ajs_builder_get( $s, "slides_tablet", 1 ) ) ),
				"--ajs-slides-mobile"       => max( 1, min( 2, (int) ajs_builder_get( $s, "slides_mobile", 1 ) ) ),
				"--ajs-testimonial-autoplay" => $autoplay ? "1" : "0",
				"--ajs-testimonial-speed"   => max( 1000, (int) ajs_builder_get( $s, "speed", 5000 ) ),
				"--ajs-testimonial-loop"    => $loop ? "1" : "0",
				"--ajs-testimonial-pause"   => $pause_hover ? "1" : "0",
			);

			$slide_html = "";
			$dot_html = "";
			$i = 0;
			foreach ( $testimonials as $item ) {
				$content   = ajs_builder_get( $item, "content", "" );
				$name      = ajs_builder_get( $item, "name", "" );
				$role      = ajs_builder_get( $item, "role", "" );
				$rating    = (int) ajs_builder_get( $item, "rating", 5 );
				$avatar    = self::media_url( ajs_builder_get( $item, "avatar", array() ) );

				$stars = "";
				for ( $j = 0; $j < 5; $j++ ) {
					$stars .= "<span class=\"ajs-w-testimonial__star" . ( $j < $rating ? " is-on" : "" ) . "\">" . Ajs_Builder_Icons::svg( "star" ) . "</span>";
				}

				$ava = self::testimonial_avatar( $avatar, $name );

				$item_rules = array();
				if ( $typography ) {
					$item_rules = array_merge( $item_rules, ajs_builder_typography_css( $typography ) );
				}

				$slide_html .= "<div class=\"ajs-testimonial__slide\"" . ajs_builder_style( $item_rules ) . ">"
					. "<div class=\"ajs-w-testimonial" . $style_mod . " ajs-align-" . esc_attr( $align ) . "\"" . $card_attr . ">"
					. $mark
					. "<div class=\"ajs-w-testimonial__stars\">" . $stars . "</div>"
					. "<p class=\"ajs-w-testimonial__quote\"" . ajs_builder_style( array( "color" => $quote_color ) ) . ">" . esc_html( $content ) . "</p>"
					. "<footer class=\"ajs-w-testimonial__author\">" . $ava
					. "<span class=\"ajs-w-testimonial__meta\"><cite class=\"ajs-w-testimonial__name\"" . ajs_builder_style( array( "color" => $name_color ) ) . ">" . esc_html( $name ) . "</cite>"
					. ( $role ? "<span class=\"ajs-w-testimonial__role\">" . esc_html( $role ) . "</span>" : "" )
					. "</span></footer>"
					. "</div>"
					. "</div>";
				$dot_html .= "<button type=\"button\" class=\"ajs-testimonial__dot" . ( 0 === $i ? " is-active" : "" ) . "\" data-index=\"" . $i . "\" aria-label=\"" . esc_attr( sprintf( __( "Go to testimonial %d", "ajs-builder" ), $i + 1 ) ) . "\"></button>";
				$i++;
			}

			$has_arrows = ( $arrows && count( $testimonials ) > 1 );
			$has_dots   = ( $dots && count( $testimonials ) > 1 );

			$nav = "";
			if ( $has_arrows ) {
				$nav = "<button type=\"button\" class=\"ajs-testimonial__nav ajs-testimonial__nav--prev\" aria-label=\"" . esc_attr__( "Previous", "ajs-builder" ) . "\">" . Ajs_Builder_Icons::svg( "arrow-right" ) . "</button>"
					. "<button type=\"button\" class=\"ajs-testimonial__nav ajs-testimonial__nav--next\" aria-label=\"" . esc_attr__( "Next", "ajs-builder" ) . "\">" . Ajs_Builder_Icons::svg( "arrow-right" ) . "</button>";
			}

			$dot_html = $has_dots ? "<div class=\"ajs-testimonial__dots\">" . $dot_html . "</div>" : "";

			// Slider header: title/subtitle on the left and both arrows in the
			// top-right corner (aligned with the heading), slides underneath.
			$heading = ( "" !== $title ? "<h3 class=\"ajs-testimonial__title\"" . $title_attr . ">" . esc_html( $title ) . "</h3>" : "" )
				. ( "" !== $subtitle ? "<p class=\"ajs-testimonial__subtitle\"" . $sub_attr . ">" . esc_html( $subtitle ) . "</p>" : "" );
			$header  = "";
			if ( "" !== $heading || $has_arrows ) {
				$header = "<div class=\"ajs-testimonial__header\">"
					. ( "" !== $heading ? "<div class=\"ajs-testimonial__heading\">" . $heading . "</div>" : "" )
					. ( $has_arrows ? "<div class=\"ajs-testimonial__navwrap\">" . $nav . "</div>" : "" )
					. "</div>";
			}

			// The arrows live in the header (top-right) rather than floating on
			// the sides, so no side gutters are reserved.
			$mods = "";
			if ( $has_arrows ) {
				$mods .= " ajs-w-testimonial--nav-header";
			}
			if ( $has_dots ) {
				$mods .= " ajs-w-testimonial--dots";
			}

			$slider_rules["--ajs-slides-desktop"] = max( 1, min( 4, (int) ajs_builder_get( $s, "slides_desktop", 1 ) ) );
			$slider_rules["--ajs-slides-tablet"]  = max( 1, min( 3, (int) ajs_builder_get( $s, "slides_tablet", 1 ) ) );
			$slider_rules["--ajs-slides-mobile"]  = max( 1, min( 2, (int) ajs_builder_get( $s, "slides_mobile", 1 ) ) );
			$slider_rules["--ajs-testimonial-gap"] = max( 0, (int) ajs_builder_get( $s, "gap", 24 ) ) . "px";
			$slider_rules["--ajs-testimonial-autoplay"] = $autoplay ? "1" : "0";
			$slider_rules["--ajs-testimonial-speed"] = max( 1000, (int) ajs_builder_get( $s, "speed", 5000 ) );
			$slider_rules["--ajs-testimonial-loop"] = $loop ? "1" : "0";
			$slider_rules["--ajs-testimonial-pause"] = $pause_hover ? "1" : "0";

			return "<div class=\"ajs-w-testimonial ajs-w-testimonial--slider" . $mods . " ajs-align-" . esc_attr( $align ) . "\" data-testimonial-slider" . ajs_builder_style( $slider_rules ) . ">"
				. $header
				. "<div class=\"ajs-testimonial__viewport\"><div class=\"ajs-testimonial__track\">" . $slide_html . "</div></div>"
				. $dot_html
				. "</div>";
		}

		// Single mode (original behavior)
		$item = $testimonials[0] ?? array();
		$content     = ajs_builder_get( $item, "content", "" );
		$name        = ajs_builder_get( $item, "name", "" );
		$role        = ajs_builder_get( $item, "role", "" );
		$rating      = (int) ajs_builder_get( $item, "rating", 5 );
		$avatar      = self::media_url( ajs_builder_get( $item, "avatar", array() ) );

		$stars = "";
		for ( $i = 0; $i < 5; $i++ ) {
			$stars .= "<span class=\"ajs-w-testimonial__star" . ( $i < $rating ? " is-on" : "" ) . "\">" . Ajs_Builder_Icons::svg( "star" ) . "</span>";
		}

		$ava = self::testimonial_avatar( $avatar, $name );

		$item_rules = array();
		if ( $typography ) {
			$item_rules = ajs_builder_typography_css( $typography );
		}

		return "<div class=\"ajs-w-testimonial" . $style_mod . " ajs-align-" . esc_attr( $align ) . "\" data-testimonial" . $card_attr . ajs_builder_style( $item_rules ) . ">"
			. $mark
			. "<div class=\"ajs-w-testimonial__stars\">" . $stars . "</div>"
			. "<p class=\"ajs-w-testimonial__quote\"" . ajs_builder_style( array( "color" => $quote_color ) ) . ">" . esc_html( $content ) . "</p>"
			. "<footer class=\"ajs-w-testimonial__author\">" . $ava
			. "<span class=\"ajs-w-testimonial__meta\"><cite class=\"ajs-w-testimonial__name\"" . ajs_builder_style( array( "color" => $name_color ) ) . ">" . esc_html( $name ) . "</cite>"
			. ( $role ? "<span class=\"ajs-w-testimonial__role\">" . esc_html( $role ) . "</span>" : "" )
			. "</span></footer></div>";
	}
	private static function w_cta( $s ) {
		$title    = ajs_builder_get( $s, 'title', '' );
		$text     = ajs_builder_get( $s, 'text', '' );
		$btn      = ajs_builder_get( $s, 'button_text', '' );
		$link     = ajs_builder_get( $s, 'button_link', '#' );
		$align    = ajs_builder_get( $s, 'align', 'left' );
		$bg       = ajs_builder_get( $s, 'bg_color', 'var(--ajs-dark)' );
		$color    = ajs_builder_get( $s, 'text_color', '#ffffff' );
		$btn_bg   = ajs_builder_get( $s, 'button_bg', 'var(--ajs-primary)' );
		$btn_col  = ajs_builder_get( $s, 'button_color', '#ffffff' );
		$radius   = (int) ajs_builder_get( $s, 'radius', 16 );

		$rules = array( 'background-color' => $bg, 'color' => $color, 'border-radius' => $radius . 'px', 'text-align' => $align );

		$html = '<div class="ajs-w-cta"' . ajs_builder_style( $rules ) . '><div class="ajs-w-cta__content">';
		if ( $title ) {
			$html .= '<h3 class="ajs-w-cta__title">' . esc_html( $title ) . '</h3>';
		}
		if ( $text ) {
			$html .= '<p class="ajs-w-cta__text">' . esc_html( $text ) . '</p>';
		}
		if ( $btn ) {
			$html .= '<a class="ajs-btn ajs-btn--md" href="' . esc_url( $link ? $link : '#' ) . '"' . ajs_builder_style( array( 'background-color' => $btn_bg, 'color' => $btn_col ) ) . '>' . esc_html( $btn ) . '</a>';
		}
		$html .= '</div></div>';
		return $html;
	}

	private static function w_accordion( $s ) {
		$items        = ajs_builder_get( $s, 'items', array() );
		$open_first   = ajs_builder_get( $s, 'open_first', true );
		$active_color = ajs_builder_get( $s, 'active_color', 'var(--ajs-primary)' );
		$border_color = ajs_builder_get( $s, 'border_color', '#e5e7eb' );

		if ( ! is_array( $items ) ) {
			return '';
		}
		$out = '';
		$i   = 0;
		foreach ( $items as $item ) {
			$title   = isset( $item['title'] ) ? $item['title'] : '';
			$content = isset( $item['content'] ) ? $item['content'] : '';
			$open    = ( 0 === $i && $open_first );
			$out    .= '<div class="ajs-acc__item' . ( $open ? ' is-open' : '' ) . '">'
				. '<button type="button" class="ajs-acc__head"><span class="ajs-acc__title">' . esc_html( $title ) . '</span><span class="ajs-acc__chev">' . Ajs_Builder_Icons::svg( 'arrow-right' ) . '</span></button>'
				. '<div class="ajs-acc__body"><div class="ajs-acc__inner">' . wpautop( wp_kses_post( $content ) ) . '</div></div>'
				. '</div>';
			$i++;
		}
		return '<div class="ajs-w-acc" data-accordion' . ajs_builder_style( array( '--ajs-acc-active' => $active_color, '--ajs-acc-border' => $border_color ) ) . '>' . $out . '</div>';
	}

	private static function w_tabs( $s ) {
		$items        = ajs_builder_get( $s, 'items', array() );
		$align        = ajs_builder_get( $s, 'align', 'left' );
		$active_color = ajs_builder_get( $s, 'active_color', 'var(--ajs-primary)' );
		$border_color = ajs_builder_get( $s, 'border_color', '#e5e7eb' );

		if ( ! is_array( $items ) || ! $items ) {
			return '';
		}
		$nav   = '';
		$panes = '';
		$i     = 0;
		foreach ( $items as $item ) {
			$title   = isset( $item['title'] ) ? $item['title'] : '';
			$content = isset( $item['content'] ) ? $item['content'] : '';
			$active  = ( 0 === $i );
			$nav    .= '<button type="button" class="ajs-tabs__tab' . ( $active ? ' is-active' : '' ) . '" data-tab="' . $i . '">' . esc_html( $title ) . '</button>';
			$panes  .= '<div class="ajs-tabs__pane' . ( $active ? ' is-active' : '' ) . '" data-pane="' . $i . '">' . wpautop( wp_kses_post( $content ) ) . '</div>';
			$i++;
		}
		return '<div class="ajs-w-tabs" data-tabs' . ajs_builder_style( array( '--ajs-tabs-active' => $active_color, '--ajs-tabs-border' => $border_color ) ) . '>'
			. '<div class="ajs-tabs__nav ajs-tabs__nav--' . esc_attr( $align ) . '" role="tablist">' . $nav . '</div>'
			. '<div class="ajs-tabs__panes">' . $panes . '</div></div>';
	}

	private static function w_progress( $s ) {
		$title   = ajs_builder_get( $s, 'title', '' );
		$percent = (int) ajs_builder_get( $s, 'percent', 75 );
		$show    = ajs_builder_get( $s, 'show_percent', true );
		$bar     = ajs_builder_get( $s, 'bar_color', 'var(--ajs-primary)' );
		$track   = ajs_builder_get( $s, 'track_color', '#eef0f3' );
		$height  = (int) ajs_builder_get( $s, 'height', 10 );

		$head = '<div class="ajs-w-progress__head">';
		if ( $title ) {
			$head .= '<span class="ajs-w-progress__title">' . esc_html( $title ) . '</span>';
		}
		if ( $show ) {
			$head .= '<span class="ajs-w-progress__pct">' . (int) $percent . '%</span>';
		}
		$head .= '</div>';

		return '<div class="ajs-w-progress">' . $head
			. '<div class="ajs-w-progress__track"' . ajs_builder_style( array( 'height' => $height . 'px', 'background-color' => $track ) ) . '>'
			. '<span class="ajs-w-progress__bar" data-percent="' . esc_attr( $percent ) . '"' . ajs_builder_style( array( 'width' => $percent . '%', 'background-color' => $bar ) ) . '></span>'
			. '</div></div>';
	}

	private static function w_social( $s ) {
		$items = ajs_builder_get( $s, 'items', array() );
		$align = ajs_builder_get( $s, 'align', 'left' );
		$shape = ajs_builder_get( $s, 'shape', 'circle' );
		$color = ajs_builder_get( $s, 'color', '#ffffff' );
		$bg    = ajs_builder_get( $s, 'bg', 'var(--ajs-primary)' );
		$size  = (int) ajs_builder_get( $s, 'size', 40 );

		if ( ! is_array( $items ) ) {
			return '';
		}
		$out = '';
		foreach ( $items as $item ) {
			$icon = isset( $item['icon'] ) ? $item['icon'] : 'globe';
			$link = isset( $item['link'] ) ? $item['link'] : '';
			$out .= '<li class="ajs-w-social__item"><a class="ajs-w-social__link ajs-w-social__link--' . esc_attr( $shape ) . '" href="' . esc_url( $link ? $link : '#' ) . '"' . ajs_builder_style( array( 'color' => $color, 'background-color' => ( 'none' === $shape ? 'transparent' : $bg ), 'width' => $size . 'px', 'height' => $size . 'px' ) ) . '>' . Ajs_Builder_Icons::svg( $icon ) . '</a></li>';
		}
		return '<ul class="ajs-w-social ajs-w-social--' . esc_attr( $align ) . '">' . $out . '</ul>';
	}

	private static function w_gallery( $s ) {
		$images  = ajs_builder_get( $s, 'images', array() );
		$columns = (int) ajs_builder_get( $s, 'columns', 3 );
		$gap     = (int) ajs_builder_get( $s, 'gap', 12 );
		$radius  = (int) ajs_builder_get( $s, 'radius', 10 );
		$link    = ajs_builder_get( $s, 'link', 'lightbox' );

		if ( ! is_array( $images ) ) {
			return '';
		}
		$out = '';
		foreach ( $images as $item ) {
			$img = isset( $item['image'] ) ? $item['image'] : array();
			$url = self::media_url( $img );
			$id  = self::media_id( $img );
			$alt = $id ? get_post_meta( $id, '_wp_attachment_image_alt', true ) : '';
			if ( $url ) {
				$inner = '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy"' . ajs_builder_style( array( 'border-radius' => $radius . 'px' ) ) . '>';
				if ( 'lightbox' === $link || 'media' === $link ) {
					$inner = '<a href="' . esc_url( $url ) . '" class="ajs-gal__link" ' . ( 'lightbox' === $link ? 'data-lightbox' : '' ) . '>' . $inner . '</a>';
				}
			} else {
				$inner = '<span class="ajs-gal__ph" aria-hidden="true">' . Ajs_Builder_Icons::svg( 'grid' ) . '</span>';
			}
			$out .= '<figure class="ajs-gal__item"' . ajs_builder_style( array( 'border-radius' => $radius . 'px' ) ) . '>' . $inner . '</figure>';
		}
		return '<div class="ajs-w-gallery ajs-w-gallery--c' . $columns . '"' . ajs_builder_style( array( '--ajs-gal-gap' => $gap . 'px' ) ) . '>' . $out . '</div>';
	}

	private static function w_video( $s ) {
		$url      = ajs_builder_get( $s, 'url', '' );
		$ratio    = ajs_builder_get( $s, 'ratio', '16:9' );
		$autoplay = ajs_builder_get( $s, 'autoplay', false );

		$ratios = array( '16:9' => 56.25, '4:3' => 75, '1:1' => 100, '21:9' => 42.85 );
		$pt     = isset( $ratios[ $ratio ] ) ? $ratios[ $ratio ] : 56.25;

		if ( ! $url ) {
			return '<div class="ajs-w-video ajs-w-video--empty"' . ajs_builder_style( array( 'padding-top' => $pt . '%' ) ) . '><span>' . Ajs_Builder_Icons::svg( 'download' ) . '</span></div>';
		}

		$embed = '';
		if ( preg_match( '#(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([\w-]+)#i', $url, $m ) ) {
			$src   = 'https://www.youtube.com/embed/' . $m[1] . ( $autoplay ? '?autoplay=1&mute=1' : '' );
			$embed = '<iframe src="' . esc_url( $src ) . '" title="' . esc_attr__( 'Video', 'ajs-builder' ) . '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
		} elseif ( preg_match( '#vimeo\.com/(\d+)#i', $url, $m ) ) {
			$src   = 'https://player.vimeo.com/video/' . $m[1] . ( $autoplay ? '?autoplay=1&muted=1' : '' );
			$embed = '<iframe src="' . esc_url( $src ) . '" title="' . esc_attr__( 'Video', 'ajs-builder' ) . '" allow="autoplay; fullscreen" allowfullscreen></iframe>';
		} elseif ( preg_match( '#\.(mp4|webm|ogg)(\?.*)?$#i', $url ) ) {
			$embed = '<video src="' . esc_url( $url ) . '" controls' . ( $autoplay ? ' autoplay muted loop playsinline' : '' ) . '></video>';
		} else {
			$embed = '<iframe src="' . esc_url( $url ) . '" title="' . esc_attr__( 'Video', 'ajs-builder' ) . '" allowfullscreen></iframe>';
		}

		return '<div class="ajs-w-video"' . ajs_builder_style( array( 'padding-top' => $pt . '%' ) ) . '>' . $embed . '</div>';
	}

	private static function w_products( $s ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return self::woo_notice();
		}
		return '<div class="ajs-w-products woocommerce">' . self::products_shortcode( $s ) . '</div>';
	}

	/**
	 * WooCommerce is not active notice.
	 *
	 * @return string
	 */
	private static function woo_notice() {
		return '<div class="ajs-w-notice">' . esc_html__( 'WooCommerce is not active.', 'ajs-builder' ) . '</div>';
	}

	/**
	 * Build and run the WooCommerce [products] shortcode.
	 *
	 * WooCommerce's products shortcode reads the global $post; make sure it
	 * exists while the shortcode runs and restore it afterwards.
	 *
	 * @param array $s         Settings source.
	 * @param array $overrides Extra shortcode attributes.
	 * @return string
	 */
	private static function products_shortcode( $s, $overrides = array() ) {
		$args = array(
			'limit'   => (int) ajs_builder_get( $s, 'limit', 4 ),
			'columns' => (int) ajs_builder_get( $s, 'columns', 4 ),
			'orderby' => ajs_builder_get( $s, 'orderby', 'date' ),
			'order'   => ajs_builder_get( $s, 'order', 'DESC' ),
		);
		$cat = ajs_builder_get( $s, 'category', '' );
		if ( $cat ) {
			$args['category'] = $cat;
		}
		$args = array_merge( $args, $overrides );

		$sc = '[products';
		foreach ( $args as $key => $value ) {
			$sc .= ' ' . $key . '="' . esc_attr( $value ) . '"';
		}
		$sc .= ']';

		$had_post        = array_key_exists( 'post', $GLOBALS );
		$previous_post   = $had_post ? $GLOBALS['post'] : null;
		$GLOBALS['post'] = get_post();

		$html = do_shortcode( $sc );

		if ( $had_post ) {
			$GLOBALS['post'] = $previous_post;
		} else {
			unset( $GLOBALS['post'] );
		}

		return $html;
	}

	/**
	 * Product carousel with grid / slider layouts.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_products_carousel( $s ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return self::woo_notice();
		}
		if ( 'slider' !== ajs_builder_get( $s, 'layout', 'grid' ) ) {
			return self::w_products( $s );
		}

		$columns  = max( 1, min( 6, (int) ajs_builder_get( $s, 'columns', 4 ) ) );
		$arrows   = ajs_builder_get( $s, 'arrows', true );
		$autoplay = ajs_builder_get( $s, 'autoplay', false );
		$speed    = (int) ajs_builder_get( $s, 'speed', 4000 );
		$speed    = $speed < 1000 ? 1000 : $speed;

		$inner = self::products_shortcode( $s, array( 'columns' => $columns ) );

		$nav = '';
		if ( $arrows ) {
			$nav = '<button type="button" class="ajs-pslider__nav ajs-pslider__nav--prev" aria-label="' . esc_attr__( 'Previous', 'ajs-builder' ) . '">' . Ajs_Builder_Icons::svg( 'arrow-right' ) . '</button>'
				. '<button type="button" class="ajs-pslider__nav ajs-pslider__nav--next" aria-label="' . esc_attr__( 'Next', 'ajs-builder' ) . '">' . Ajs_Builder_Icons::svg( 'arrow-right' ) . '</button>';
		}

		return '<div class="ajs-w-pslider ajs-w-pslider--c' . $columns . ' woocommerce" data-pslider data-autoplay="' . ( $autoplay ? '1' : '0' ) . '" data-speed="' . esc_attr( $speed ) . '">'
			. '<div class="ajs-w-pslider__track">' . $inner . '</div>'
			. $nav
			. '</div>';
	}

	/**
	 * Product category tabs.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_product_tabs( $s ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return self::woo_notice();
		}
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return self::woo_notice();
		}

		$mode    = ajs_builder_get( $s, 'mode', 'auto' );
		$columns = max( 1, min( 6, (int) ajs_builder_get( $s, 'columns', 4 ) ) );
		$limit   = (int) ajs_builder_get( $s, 'limit', 4 );
		$orderby = ajs_builder_get( $s, 'orderby', 'date' );
		$order   = ajs_builder_get( $s, 'order', 'DESC' );
		$active  = ajs_builder_get( $s, 'active_color', 'var(--ajs-primary)' );
		$border  = ajs_builder_get( $s, 'border_color', '#e5e7eb' );

		$terms = array();
		if ( 'manual' === $mode ) {
			$slugs = array_filter( array_map( 'trim', explode( ',', (string) ajs_builder_get( $s, 'categories', '' ) ) ) );
			foreach ( $slugs as $slug ) {
				$term = get_term_by( 'slug', $slug, 'product_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$terms[] = $term;
				}
			}
		} else {
			$found = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'parent'     => 0,
					'number'     => 6,
					'orderby'    => 'name',
				)
			);
			if ( ! is_wp_error( $found ) ) {
				$terms = $found;
			}
		}

		if ( ! $terms ) {
			return '';
		}

		$nav   = '';
		$panes = '';
		$i     = 0;
		foreach ( $terms as $term ) {
			$is_active = ( 0 === $i );
			$nav      .= '<button type="button" class="ajs-tabs__tab' . ( $is_active ? ' is-active' : '' ) . '" data-tab="' . $i . '">' . esc_html( $term->name ) . '</button>';
			$products  = self::products_shortcode(
				array(
					'limit'   => $limit,
					'columns' => $columns,
					'orderby' => $orderby,
					'order'   => $order,
				),
				array( 'category' => $term->slug )
			);
			$panes    .= '<div class="ajs-tabs__pane' . ( $is_active ? ' is-active' : '' ) . '" data-pane="' . $i . '">' . $products . '</div>';
			$i++;
		}

		return '<div class="ajs-w-ptabs ajs-w-tabs woocommerce" data-tabs' . ajs_builder_style( array( '--ajs-tabs-active' => $active, '--ajs-tabs-border' => $border ) ) . '>'
			. '<div class="ajs-tabs__nav ajs-tabs__nav--left" role="tablist">' . $nav . '</div>'
			. '<div class="ajs-tabs__panes">' . $panes . '</div></div>';
	}

	/**
	 * Featured product categories, laid out as a grid or a carousel.
	 *
	 * The card mirrors the Testimonial card: media on top, then the name and
	 * supporting lines underneath, with the avatar/quote replaced by the
	 * category thumbnail, name, product count and description.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_featured_category( $s ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return self::woo_notice();
		}
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return self::woo_notice();
		}

		$mode    = ajs_builder_get( $s, 'mode', 'grid' );
		$style   = ajs_builder_get( $s, 'style', 'card' );
		$source  = ajs_builder_get( $s, 'source', 'auto' );
		$align   = ajs_builder_get( $s, 'align', 'center' );
		$gap     = max( 0, (int) ajs_builder_get( $s, 'gap', 24 ) );
		$img_h   = max( 0, (int) ajs_builder_get( $s, 'image_height', 200 ) );
		$radius  = (int) ajs_builder_get( $s, 'image_radius', 12 );

		$show_img   = ajs_builder_get( $s, 'show_image', true );
		$show_count = ajs_builder_get( $s, 'show_count', true );
		$show_desc  = ajs_builder_get( $s, 'show_description', false );
		$desc_len   = max( 3, (int) ajs_builder_get( $s, 'description_length', 18 ) );
		$show_btn   = ajs_builder_get( $s, 'show_button', false );
		$btn_text   = ajs_builder_get( $s, 'button_text', __( 'Shop now', 'ajs-builder' ) );

		$name_color  = ajs_builder_get( $s, 'name_color', '' );
		$count_color = ajs_builder_get( $s, 'count_color', '' );
		$desc_color  = ajs_builder_get( $s, 'description_color', '' );
		$card_bg     = ajs_builder_get( $s, 'card_bg', '' );
		$card_radius = (int) ajs_builder_get( $s, 'card_radius', 16 );
		$btn_bg      = ajs_builder_get( $s, 'button_bg', '' );
		$btn_color   = ajs_builder_get( $s, 'button_color', '' );

		$is_card   = ( 'card' === $style );
		$is_slider = ( 'slider' === $mode );

		// Resolve the terms, mirroring Product Category Tabs.
		$terms = array();
		if ( 'manual' === $source ) {
			$slugs = array_filter( array_map( 'trim', explode( ',', (string) ajs_builder_get( $s, 'categories', '' ) ) ) );
			foreach ( $slugs as $slug ) {
				$term = get_term_by( 'slug', $slug, 'product_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$terms[] = $term;
				}
			}
		} else {
			$found = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'parent'     => 0,
					'number'     => max( 1, min( 24, (int) ajs_builder_get( $s, 'limit', 6 ) ) ),
					'orderby'    => ajs_builder_get( $s, 'orderby', 'count' ),
					'order'      => ajs_builder_get( $s, 'order', 'DESC' ),
				)
			);
			if ( ! is_wp_error( $found ) ) {
				$terms = $found;
			}
		}

		if ( ! $terms ) {
			return '<div class="ajs-w-notice">' . esc_html__( 'No product categories found.', 'ajs-builder' ) . '</div>';
		}

		$card_attr = $is_card
			? ajs_builder_style(
				array(
					'background-color' => $card_bg ? $card_bg : '#ffffff',
					'border-radius'    => $card_radius . 'px',
				)
			)
			: '';

		$name_attr  = ajs_builder_style( array( 'color' => $name_color ) );
		$count_attr = ajs_builder_style( array( 'color' => $count_color ) );
		$desc_attr  = ajs_builder_style( array( 'color' => $desc_color ) );
		$btn_attr   = ajs_builder_style(
			array(
				'background-color' => $btn_bg,
				'color'            => $btn_color,
			)
		);

		$items   = '';
		$dots    = '';
		$i       = 0;
		$count_n = count( $terms );

		foreach ( $terms as $term ) {
			$name = $term->name;
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				$link = '';
			}

			$media = '';
			if ( $show_img ) {
				$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
				$src      = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
				$inner    = $src
					? '<img class="ajs-w-fcat__img" src="' . esc_url( $src ) . '" alt="' . esc_attr( $name ) . '" loading="lazy" decoding="async">'
					: '<span class="ajs-w-fcat__ph" aria-hidden="true">' . Ajs_Builder_Icons::svg( 'grid' ) . '</span>';
				$media    = '<span class="ajs-w-fcat__media"' . ajs_builder_style( array( 'height' => $img_h . 'px', 'border-radius' => $radius . 'px' ) ) . '>' . $inner . '</span>';
			}

			$count = (int) $term->count;
			$meta  = '';
			if ( $show_count ) {
				$meta .= '<span class="ajs-w-fcat__count"' . $count_attr . '>'
					. esc_html( sprintf( _n( '%s product', '%s products', $count, 'ajs-builder' ), number_format_i18n( $count ) ) )
					. '</span>';
			}
			if ( $show_desc && $term->description ) {
				$desc = wp_trim_words( wp_strip_all_tags( (string) $term->description ), $desc_len, '…' );
				if ( $desc ) {
					$meta .= '<span class="ajs-w-fcat__desc"' . $desc_attr . '>' . esc_html( $desc ) . '</span>';
				}
			}
			if ( $show_btn && $link && $btn_text ) {
				$meta .= '<span class="ajs-w-fcat__btn"' . $btn_attr . '>' . esc_html( $btn_text ) . Ajs_Builder_Icons::svg( 'arrow-right', 'ajs-w-fcat__btnicon' ) . '</span>';
			}

			$card = '<div class="ajs-w-fcat__card' . ( $is_card ? ' ajs-w-fcat__card--card' : ' ajs-w-fcat__card--classic' ) . '"' . $card_attr . '>'
				. $media
				. '<div class="ajs-w-fcat__body">'
				. '<span class="ajs-w-fcat__name"' . $name_attr . '>' . esc_html( $name ) . '</span>'
				. ( $meta ? '<div class="ajs-w-fcat__meta">' . $meta . '</div>' : '' )
				. '</div></div>';

			$item = $link
				? '<a class="ajs-w-fcat__item ajs-w-fcat__item--link" href="' . esc_url( $link ) . '">' . $card . '</a>'
				: '<div class="ajs-w-fcat__item">' . $card . '</div>';

			if ( $is_slider ) {
				$items .= '<div class="ajs-fcat__slide">' . $item . '</div>';
				$dots  .= '<button type="button" class="ajs-fcat__dot' . ( 0 === $i ? ' is-active' : '' ) . '" data-index="' . $i . '" aria-label="' . esc_attr( sprintf( __( 'Go to category %d', 'ajs-builder' ), $i + 1 ) ) . '"></button>';
			} else {
				$items .= $item;
			}
			$i++;
		}

		if ( $is_slider ) {
			$slides_desk = max( 1, min( 4, (int) ajs_builder_get( $s, 'slides_desktop', 4 ) ) );
			$slides_tab  = max( 1, min( 3, (int) ajs_builder_get( $s, 'slides_tablet', 2 ) ) );
			$slides_mob  = max( 1, min( 2, (int) ajs_builder_get( $s, 'slides_mobile', 1 ) ) );
			$autoplay    = ajs_builder_get( $s, 'autoplay', true );
			$loop        = ajs_builder_get( $s, 'loop_slides', true );
			$pause       = ajs_builder_get( $s, 'pause_hover', true );
			$arrows      = ajs_builder_get( $s, 'show_arrows', true );
			$dots_on     = ajs_builder_get( $s, 'show_dots', true );

			$rules = array(
				'--ajs-fcat-gap'       => $gap . 'px',
				'--ajs-slides-desktop' => $slides_desk,
				'--ajs-slides-tablet'  => $slides_tab,
				'--ajs-slides-mobile'  => $slides_mob,
			);

			// mountCarousel() reads its runtime options from data attributes.
			$speed = max( 1000, (int) ajs_builder_get( $s, 'speed', 5000 ) );
			$data  = ' data-autoplay="' . ( $autoplay ? '1' : '0' ) . '"'
				. ' data-speed="' . esc_attr( $speed ) . '"'
				. ' data-loop="' . ( $loop ? '1' : '0' ) . '"'
				. ' data-pause="' . ( $pause ? '1' : '0' ) . '"';

			$has_arrows = ( $arrows && $count_n > 1 );
			$has_dots   = ( $dots_on && $count_n > 1 );

			$nav = '';
			if ( $has_arrows ) {
				$nav = '<button type="button" class="ajs-fcat__nav ajs-fcat__nav--prev" aria-label="' . esc_attr__( 'Previous', 'ajs-builder' ) . '">' . Ajs_Builder_Icons::svg( 'arrow-right' ) . '</button>'
					. '<button type="button" class="ajs-fcat__nav ajs-fcat__nav--next" aria-label="' . esc_attr__( 'Next', 'ajs-builder' ) . '">' . Ajs_Builder_Icons::svg( 'arrow-right' ) . '</button>';
			}

			$title    = ajs_builder_get( $s, 'title', '' );
			$subtitle = ajs_builder_get( $s, 'subtitle', '' );
			$heading  = ( '' !== $title ? '<h3 class="ajs-fcat__title">' . esc_html( $title ) . '</h3>' : '' )
				. ( '' !== $subtitle ? '<p class="ajs-fcat__subtitle">' . esc_html( $subtitle ) . '</p>' : '' );
			$header   = '';
			if ( '' !== $heading || $has_arrows ) {
				$header = '<div class="ajs-fcat__header">'
					. ( '' !== $heading ? '<div class="ajs-fcat__heading">' . $heading . '</div>' : '' )
					. ( $has_arrows ? '<div class="ajs-fcat__navwrap">' . $nav . '</div>' : '' )
					. '</div>';
			}

			$mods = '';
			if ( $has_arrows ) {
				$mods .= ' ajs-w-fcat--nav-header';
			}
			if ( $has_dots ) {
				$mods .= ' ajs-w-fcat--dots';
			}

			return '<div class="ajs-w-fcat ajs-w-fcat--' . esc_attr( $style ) . ' ajs-w-fcat--slider' . $mods . ' ajs-align-' . esc_attr( $align ) . '" data-fcat-slider' . $data . ajs_builder_style( $rules ) . '>'
				. $header
				. '<div class="ajs-fcat__viewport"><div class="ajs-fcat__track">' . $items . '</div></div>'
				. ( $has_dots ? '<div class="ajs-fcat__dots">' . $dots . '</div>' : '' )
				. '</div>';
		}

		$rules = array(
			'--ajs-fcat-gap'        => $gap . 'px',
			'--ajs-fcat-cols'       => max( 1, min( 6, (int) ajs_builder_get( $s, 'columns', 4 ) ) ),
			'--ajs-fcat-cols-tablet' => max( 1, min( 4, (int) ajs_builder_get( $s, 'columns_tablet', 3 ) ) ),
			'--ajs-fcat-cols-mobile' => max( 1, min( 3, (int) ajs_builder_get( $s, 'columns_mobile', 2 ) ) ),
		);

		return '<div class="ajs-w-fcat ajs-w-fcat--' . esc_attr( $style ) . ' ajs-w-fcat--grid ajs-align-' . esc_attr( $align ) . '"' . ajs_builder_style( $rules ) . '>'
			. '<div class="ajs-fcat__grid">' . $items . '</div>'
			. '</div>';
	}

	/**
	 * Logo with a resize option.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_logo( $s ) {
		$source  = ajs_builder_get( $s, 'source', 'site' );
		$width   = (int) ajs_builder_get( $s, 'width', 150 );
		$width   = $width < 10 ? 10 : $width;
		$align   = ajs_builder_get( $s, 'align', 'left' );
		$link    = ajs_builder_get( $s, 'link', '' );
		$new_tab = ajs_builder_get( $s, 'new_tab', false );

		$url = '';
		$id  = 0;
		if ( 'custom' === $source ) {
			$url = self::media_url( ajs_builder_get( $s, 'image', array() ) );
			$id  = self::media_id( ajs_builder_get( $s, 'image', array() ) );
		}
		if ( ! $url && function_exists( 'ajs_opt' ) ) {
			$theme_logo = ajs_opt( 'header_logo_image' );
			if ( $theme_logo ) {
				$url = $theme_logo;
				$tid = attachment_url_to_postid( $theme_logo );
				if ( $tid ) {
					$id = $tid;
				}
			}
		}
		if ( ! $url ) {
			$logo_id = (int) get_theme_mod( 'custom_logo' );
			if ( $logo_id ) {
				$url = wp_get_attachment_image_url( $logo_id, 'full' );
				$id  = $logo_id;
			}
		}

		$name = get_bloginfo( 'name' );
		$text = function_exists( 'ajs_opt' ) ? ajs_opt( 'header_logo_text', $name ) : $name;
		if ( ! $text ) {
			$text = $name;
		}
		if ( $url ) {
			$alt = $id ? get_post_meta( $id, '_wp_attachment_image_alt', true ) : '';
			if ( ! $alt ) {
				$alt = $name;
			}
			$media = '<img class="ajs-w-logo__img" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '"' . ajs_builder_style( array( 'width' => $width . 'px' ) ) . '>';
		} else {
			$media = '<span class="ajs-w-logo__text">' . esc_html( $text ) . '</span>';
		}

		if ( ! $link ) {
			$link = home_url( '/' );
		}
		$target = $new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';

		return '<div class="ajs-w-logo ajs-w-logo--' . esc_attr( $align ) . '">'
			. '<a class="ajs-w-logo__link" href="' . esc_url( $link ) . '"' . $target . '>' . $media . '</a>'
			. '</div>';
	}

	/**
	 * Image slider.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_slider( $s ) {
		$images       = ajs_builder_get( $s, 'images', array() );
		$width_mode   = ajs_builder_get( $s, 'width_mode', 'full' );
		$fixed_width  = (int) ajs_builder_get( $s, 'fixed_width', 1200 );
		$height       = (int) ajs_builder_get( $s, 'height', 0 );
		$image_fit    = ajs_builder_get( $s, 'image_fit', 'cover' );
		$ratio        = ajs_builder_get( $s, 'ratio', '16:9' );
		$radius       = (int) ajs_builder_get( $s, 'radius', 12 );
		$align        = ajs_builder_get( $s, 'content_align', 'center' );
		$pos          = ajs_builder_get( $s, 'content_position', 'middle' );
		$positions    = array( 'top', 'top-left', 'top-right', 'top-center', 'middle', 'middle-left', 'middle-right', 'bottom', 'bottom-left', 'bottom-right', 'bottom-center' );
		if ( ! in_array( $pos, $positions, true ) ) {
			$pos = 'middle';
		}

		$content_width    = ajs_builder_get( $s, 'content_width', 'full' );
		$content_max_w    = (int) ajs_builder_get( $s, 'content_max_width', 640 );
		$inner_boxed      = ( 'boxed' === $content_width && $content_max_w >= 180 );
		$overlay      = ajs_builder_get( $s, 'overlay_color', '' );
		$overlay_opac = (int) ajs_builder_get( $s, 'overlay_opacity', 0 );
		$heading_col  = ajs_builder_get( $s, 'heading_color', '' );
		$text_col     = ajs_builder_get( $s, 'text_color', '' );
		$btn_bg       = ajs_builder_get( $s, 'button_bg', '' );
		$btn_col      = ajs_builder_get( $s, 'button_color', '' );
		$transition   = ajs_builder_get( $s, 'transition', 'slide' );
		$autoplay     = ajs_builder_get( $s, 'autoplay', true );
		$speed        = max( 1000, (int) ajs_builder_get( $s, 'speed', 4000 ) );
		$loop         = ajs_builder_get( $s, 'loop', true );
		$pause_hover  = ajs_builder_get( $s, 'pause_hover', true );
		$arrows       = ajs_builder_get( $s, 'arrows', true );
		$dots         = ajs_builder_get( $s, 'dots', true );
		$typography   = ajs_builder_get( $s, 'typography', array() );

		if ( ! is_array( $images ) ) {
			$images = array();
		}

		// Height wins over Aspect ratio. The ratio is only honoured while no
		// explicit height is set (base or per-device); otherwise the fixed
		// height would be silently overridden by `aspect-ratio` in CSS.
		$has_custom_height = ( $height > 0 );
		foreach ( array( 'height_tablet', 'height_mobile' ) as $hkey ) {
			$hval = ajs_builder_get( $s, $hkey, '' );
			if ( '' !== $hval && null !== $hval && (int) $hval > 0 ) {
				$has_custom_height = true;
			}
		}
		$use_ratio = ( ! $has_custom_height && 'auto' !== $ratio && '' !== $ratio );

		$typo_css = ajs_builder_typography_css( $typography );

		$slides  = '';
		$dotbtns = '';
		$i       = 0;
		foreach ( $images as $item ) {
			$media       = isset( $item['image'] ) ? $item['image'] : array();
			$url         = self::media_url( $media );
			$mid         = self::media_id( $media );
			$alt         = $mid ? get_post_meta( $mid, '_wp_attachment_image_alt', true ) : '';
			$heading     = isset( $item['heading'] ) ? $item['heading'] : '';
			$text        = isset( $item['text'] ) ? $item['text'] : '';
			$btn_text    = isset( $item['btn_text'] ) ? $item['btn_text'] : '';
			$btn_link    = isset( $item['btn_link'] ) ? $item['btn_link'] : '';
			$btn_target  = ! empty( $item['btn_target'] );
			$img_link    = isset( $item['link'] ) ? $item['link'] : '';
			$img_target  = ! empty( $item['link_target'] );

			if ( $url ) {
				$pic = '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async">';
			} else {
				$pic = '<span class="ajs-w-slider__ph" aria-hidden="true">' . Ajs_Builder_Icons::svg( 'grid' ) . '</span>';
			}
			if ( $url && $img_link ) {
				$t   = $img_target ? ' target="_blank" rel="noopener"' : '';
				$pic = '<a class="ajs-w-slider__media" href="' . esc_url( $img_link ) . '"' . $t . '>' . $pic . '</a>';
			} else {
				$pic = '<span class="ajs-w-slider__media">' . $pic . '</span>';
			}

			$overlay_html = '';
			if ( $overlay && $overlay_opac > 0 ) {
				$overlay_html = '<span class="ajs-w-slider__overlay"' . ajs_builder_style(
					array(
						'background-color' => $overlay,
						'opacity'          => $overlay_opac / 100,
					)
				) . '></span>';
			}

			$content = '';
			if ( $heading || $text || ( $btn_text && $btn_link ) ) {
				$content = '<div class="ajs-w-slider__content ajs-align-' . esc_attr( $align ) . ' ajs-w-slider__content--' . esc_attr( $pos ) . ( $inner_boxed ? ' ajs-w-slider__content--boxed' : '' ) . '"' . ( $inner_boxed ? ajs_builder_style( array( 'max-width' => $content_max_w . 'px' ) ) : '' ) . '>';
				if ( $heading ) {
					$hs = array( 'color' => $heading_col );
					if ( $typo_css ) {
						$hs = array_merge( $hs, $typo_css );
					}
					$content .= '<h3 class="ajs-w-slider__heading"' . ajs_builder_style( $hs ) . '>' . esc_html( $heading ) . '</h3>';
				}
				if ( $text ) {
					$ts = array( 'color' => $text_col );
					if ( $typo_css ) {
						$ts = array_merge( $ts, $typo_css );
					}
					$content .= '<p class="ajs-w-slider__text"' . ajs_builder_style( $ts ) . '>' . wp_kses_post( $text ) . '</p>';
				}
				if ( $btn_text && $btn_link ) {
					$bs = array();
					if ( $btn_bg ) {
						$bs['background-color'] = $btn_bg;
					}
					if ( $btn_col ) {
						$bs['color'] = $btn_col;
					}
					$bt = $btn_target ? ' target="_blank" rel="noopener"' : '';
					$content .= '<a class="ajs-btn ajs-btn--md ajs-w-slider__btn" href="' . esc_url( $btn_link ) . '"' . $bt . ajs_builder_style( $bs ) . '>' . esc_html( $btn_text ) . '</a>';
				}
				$content .= '</div>';
			}

			$slides  .= '<div class="ajs-w-slider__slide' . ( 0 === $i ? ' is-active' : '' ) . '">' . $pic . $overlay_html . $content . '</div>';
			$dotbtns .= '<button type="button" class="ajs-w-slider__dot' . ( 0 === $i ? ' is-active' : '' ) . '" data-slide="' . $i . '" aria-label="' . esc_attr( sprintf( /* translators: %d: slide number. */ __( 'Go to slide %d', 'ajs-builder' ), $i + 1 ) ) . '"></button>';
			$i++;
		}

		if ( '' === $slides ) {
			return '';
		}

		$nav = '';
		if ( $arrows && $i > 1 ) {
			$nav .= '<button type="button" class="ajs-w-slider__nav ajs-w-slider__nav--prev" aria-label="' . esc_attr__( 'Previous slide', 'ajs-builder' ) . '">' . Ajs_Builder_Icons::svg( 'arrow-right' ) . '</button>';
			$nav .= '<button type="button" class="ajs-w-slider__nav ajs-w-slider__nav--next" aria-label="' . esc_attr__( 'Next slide', 'ajs-builder' ) . '">' . Ajs_Builder_Icons::svg( 'arrow-right' ) . '</button>';
		}
		$dots_html = ( $dots && $i > 1 ) ? '<div class="ajs-w-slider__dots">' . $dotbtns . '</div>' : '';

		$rules = array(
			'--ajs-slider-fit'    => $image_fit,
			'--ajs-slider-radius' => $radius . 'px',
		);
		if ( ! $use_ratio && $height > 0 ) {
			$rules['--ajs-slider-height'] = $height . 'px';
		}
		if ( $use_ratio ) {
			$rules['--ajs-slider-ratio'] = str_replace( ':', ' / ', $ratio );
		}
		if ( 'fixed' === $width_mode ) {
			$rules['max-width']    = $fixed_width . 'px';
			$rules['margin-left']  = 'auto';
			$rules['margin-right'] = 'auto';
		}

		return '<div class="ajs-w-slider" data-slider data-transition="' . esc_attr( $transition ) . '" data-ratio="' . esc_attr( $use_ratio ? $ratio : 'auto' ) . '"'
			. ' data-autoplay="' . ( $autoplay ? '1' : '0' ) . '" data-speed="' . esc_attr( $speed ) . '"'
			. ' data-loop="' . ( $loop ? '1' : '0' ) . '" data-pause="' . ( $pause_hover ? '1' : '0' ) . '"'
			. ajs_builder_style( $rules ) . '>'
			. '<div class="ajs-w-slider__slides">' . $slides . '</div>'
			. $nav . $dots_html
			. '</div>';
	}

	/**
	 * Navigation menu module.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_nav( $s ) {
		$menu    = ajs_builder_get( $s, 'menu', '' );
		$layout  = ajs_builder_get( $s, 'layout', 'horizontal' );
		$submenu = ajs_builder_get( $s, 'submenu', 'dropdown' );
		$align   = ajs_builder_get( $s, 'align', 'left' );
		$gap     = max( 0, (int) ajs_builder_get( $s, 'gap', 26 ) );
		$color   = ajs_builder_get( $s, 'color', '' );
		$hover   = ajs_builder_get( $s, 'hover_color', 'var(--ajs-primary)' );

		$args = array(
			'echo'        => false,
			'container'   => false,
			'menu_class'  => 'ajs-w-nav__list',
			'fallback_cb' => false,
			'depth'       => 3,
		);
		if ( $menu ) {
			$args['menu'] = $menu;
		} else {
			$args['theme_location'] = 'primary';
		}
		$html = wp_nav_menu( $args );

		if ( ! $html ) {
			$html = '<ul class="ajs-w-nav__list"><li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'ajs-builder' ) . '</a></li></ul>';
		}

		$rules = array( '--ajs-nav-gap' => $gap . 'px', '--ajs-nav-hover' => $hover );
		if ( $color ) {
			$rules['color'] = $color;
		}

		return '<nav class="ajs-w-nav ajs-w-nav--' . esc_attr( $layout ) . ' ajs-w-nav--' . esc_attr( $submenu ) . ' ajs-align-' . esc_attr( $align ) . '"' . ajs_builder_style( $rules ) . '>'
			. '<button type="button" class="ajs-w-nav__toggle" aria-expanded="false">' . Ajs_Builder_Icons::svg( 'menu' ) . '<span>' . esc_html__( 'Menu', 'ajs-builder' ) . '</span></button>'
			. $html
			. '</nav>';
	}

	/**
	 * Search module.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_search( $s ) {
		$placeholder   = ajs_builder_get( $s, 'placeholder', __( 'Search products…', 'ajs-builder' ) );
		$mode          = ajs_builder_get( $s, 'mode', 'inline' );
		$size          = ajs_builder_get( $s, 'size', 'md' );
		$align         = ajs_builder_get( $s, 'align', 'left' );
		$products_only = ajs_builder_get( $s, 'products_only', true );
		$color         = ajs_builder_get( $s, 'color', '' );

		$pt = ( $products_only && class_exists( 'WooCommerce' ) ) ? '<input type="hidden" name="post_type" value="product" />' : '';

		$field = '<input type="search" name="s" class="ajs-w-search__input" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( get_search_query() ) . '" aria-label="' . esc_attr__( 'Search', 'ajs-builder' ) . '" />'
			. $pt
			. '<button type="submit" class="ajs-w-search__btn" aria-label="' . esc_attr__( 'Submit search', 'ajs-builder' ) . '">' . Ajs_Builder_Icons::svg( 'search' ) . '</button>';

		$rules = array();
		if ( $color ) {
			$rules['color'] = $color;
		}

		if ( 'icon' === $mode ) {
			$inner = '<button type="button" class="ajs-w-search__toggle" data-ajs-search-toggle aria-expanded="false" aria-label="' . esc_attr__( 'Open search', 'ajs-builder' ) . '">' . Ajs_Builder_Icons::svg( 'search' ) . '</button>'
				. '<form class="ajs-w-search__panel" role="search" method="get" action="' . esc_url( home_url( '/' ) ) . '">' . $field . '</form>';
			return '<div class="ajs-w-search ajs-w-search--icon ajs-w-search--' . esc_attr( $size ) . ' ajs-align-' . esc_attr( $align ) . '"' . ajs_builder_style( $rules ) . '>' . $inner . '</div>';
		}

		return '<div class="ajs-w-search ajs-w-search--inline ajs-w-search--' . esc_attr( $size ) . ' ajs-align-' . esc_attr( $align ) . '"' . ajs_builder_style( $rules ) . '>'
			. '<form role="search" method="get" class="ajs-w-search__form" action="' . esc_url( home_url( '/' ) ) . '">' . $field . '</form>'
			. '</div>';
	}

	/**
	 * Cart module.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_cart( $s ) {
		$layout     = ajs_builder_get( $s, 'layout', 'icon' );
		$label      = ajs_builder_get( $s, 'label', __( 'Cart', 'ajs-builder' ) );
		$show_count = ajs_builder_get( $s, 'show_count', true );
		$show_price = ajs_builder_get( $s, 'show_price', false );
		$align      = ajs_builder_get( $s, 'align', 'left' );
		$color      = ajs_builder_get( $s, 'color', '' );

		$has_woo = class_exists( 'WooCommerce' ) && function_exists( 'WC' ) && WC() && WC()->cart;

		$url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';
		if ( ! $url ) {
			$url = home_url( '/' );
		}

		$count = $has_woo ? (int) WC()->cart->get_cart_contents_count() : 0;
		$total = $has_woo ? WC()->cart->get_cart_subtotal() : '';

		$badge = $show_count ? '<span class="ajs-w-cart__count">' . $count . '</span>' : '';

		$rules = array();
		if ( $color ) {
			$rules['color'] = $color;
		}

		$link = '<a class="ajs-w-cart__link" href="' . esc_url( $url ) . '"' . ajs_builder_style( $rules ) . '>';
		$price = ( $show_price && $total ) ? '<span class="ajs-w-cart__price">' . wp_kses_post( $total ) . '</span>' : '';

		if ( 'inline' === $layout ) {
			$inner = $link . '<span class="ajs-w-cart__ic">' . Ajs_Builder_Icons::svg( 'cart' ) . $badge . '</span>'
				. '<span class="ajs-w-cart__label">' . esc_html( $label ) . '</span>'
				. $price . '</a>';
		} elseif ( 'stacked' === $layout ) {
			$inner = $link . '<span class="ajs-w-cart__top">' . Ajs_Builder_Icons::svg( 'cart' ) . $badge . '</span>'
				. '<span class="ajs-w-cart__label">' . esc_html( $label ) . '</span>'
				. $price . '</a>';
		} else {
			$inner = $link . '<span class="ajs-w-cart__ic">' . Ajs_Builder_Icons::svg( 'cart' ) . $badge . '</span>' . $price . '</a>';
		}

		return '<div class="ajs-w-cart ajs-w-cart--' . esc_attr( $layout ) . ' ajs-align-' . esc_attr( $align ) . '">' . $inner . '</div>';
	}

	/**
	 * My Account module.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_account( $s ) {
		$layout     = ajs_builder_get( $s, 'layout', 'icon' );
		$logged_in  = ajs_builder_get( $s, 'logged_in', __( 'My Account', 'ajs-builder' ) );
		$logged_out = ajs_builder_get( $s, 'logged_out', __( 'Login / Register', 'ajs-builder' ) );
		$align      = ajs_builder_get( $s, 'align', 'left' );
		$color      = ajs_builder_get( $s, 'color', '' );
		$hover      = ajs_builder_get( $s, 'hover_color', 'var(--ajs-primary)' );

		$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';
		if ( ! $url ) {
			$url = is_user_logged_in() ? admin_url( 'profile.php' ) : wp_login_url();
		}

		$rules = array( '--ajs-acc-hover' => $hover );
		if ( $color ) {
			$rules['color'] = $color;
		}

		$label = is_user_logged_in() ? $logged_in : $logged_out;

		return '<div class="ajs-w-account ajs-w-account--' . esc_attr( $layout ) . ' ajs-align-' . esc_attr( $align ) . '">'
			. '<a class="ajs-w-account__link" href="' . esc_url( $url ) . '"' . ajs_builder_style( $rules ) . '>'
			. '<span class="ajs-w-account__ic">' . Ajs_Builder_Icons::svg( 'user' ) . '</span>'
			. ( 'inline' === $layout ? '<span class="ajs-w-account__label">' . esc_html( $label ) . '</span>' : '' )
			. '</a></div>';
	}

	/**
	 * Compare module.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_compare( $s ) {
		$link       = ajs_builder_get( $s, 'link', '' );
		$label      = ajs_builder_get( $s, 'label', __( 'Compare', 'ajs-builder' ) );
		$layout     = ajs_builder_get( $s, 'layout', 'icon' );
		$show_count = ajs_builder_get( $s, 'show_count', true );
		$align      = ajs_builder_get( $s, 'align', 'left' );
		$color      = ajs_builder_get( $s, 'color', '' );
		$hover      = ajs_builder_get( $s, 'hover_color', 'var(--ajs-primary)' );

		if ( ! $link ) {
			$compare = get_page_by_path( 'compare' );
			$link    = $compare ? get_permalink( $compare ) : '';
		}
		if ( ! $link ) {
			$link = home_url( '/' );
		}

		$count = 0;
		if ( $show_count && isset( $_COOKIE['ajs_compare'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
			$ids   = array_filter( array_map( 'absint', explode( ',', (string) wp_unslash( $_COOKIE['ajs_compare'] ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
			$count = count( $ids );
		}
		$badge = $show_count ? '<span class="ajs-w-compare__count">' . $count . '</span>' : '';

		$rules = array( '--ajs-cmp-hover' => $hover );
		if ( $color ) {
			$rules['color'] = $color;
		}

		return '<div class="ajs-w-compare ajs-w-compare--' . esc_attr( $layout ) . ' ajs-align-' . esc_attr( $align ) . '">'
			. '<a class="ajs-w-compare__link" href="' . esc_url( $link ) . '"' . ajs_builder_style( $rules ) . '>'
			. '<span class="ajs-w-compare__ic">' . Ajs_Builder_Icons::svg( 'compare' ) . $badge . '</span>'
			. ( 'inline' === $layout ? '<span class="ajs-w-compare__label">' . esc_html( $label ) . '</span>' : '' )
			. '</a></div>';
	}

	/* =====================================================================
	 * Google Map
	 * ===================================================================== */

	/**
	 * Google Map embed (no API key required).
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_google_map( $s ) {
		$address   = trim( (string) ajs_builder_get( $s, 'address', '' ) );
		$title     = ajs_builder_get( $s, 'title', __( 'Our location', 'ajs-builder' ) );
		$zoom      = max( 1, min( 21, (int) ajs_builder_get( $s, 'zoom', 14 ) ) );
		$map_type  = ajs_builder_get( $s, 'map_type', 'roadmap' );
		$height    = max( 80, (int) ajs_builder_get( $s, 'height', 400 ) );
		$radius    = (int) ajs_builder_get( $s, 'radius', 12 );
		$grayscale = ajs_builder_get( $s, 'grayscale', false );

		$types = array( 'roadmap' => 'm', 'satellite' => 'k', 'hybrid' => 'h', 'terrain' => 'p' );
		$t     = isset( $types[ $map_type ] ) ? $types[ $map_type ] : 'm';

		$rules = array(
			'height'        => $height . 'px',
			'border-radius' => $radius . 'px',
		);
		if ( $grayscale ) {
			$rules['filter'] = 'grayscale(1) contrast(1.05)';
		}

		if ( '' === $address ) {
			return '<div class="ajs-w-gmap ajs-w-gmap--empty"' . ajs_builder_style( $rules ) . '>'
				. '<span class="ajs-w-gmap__ph">' . Ajs_Builder_Icons::svg( 'map-pin' ) . '<em>' . esc_html__( 'Enter an address to show the map', 'ajs-builder' ) . '</em></span></div>';
		}

		if ( preg_match( '#^https?://#i', $address ) ) {
			$src = $address;
		} else {
			$src = 'https://www.google.com/maps?q=' . rawurlencode( $address ) . '&z=' . $zoom . '&t=' . $t . '&output=embed';
		}

		return '<div class="ajs-w-gmap"' . ajs_builder_style( $rules ) . '>'
			. '<iframe class="ajs-w-gmap__frame" src="' . esc_url( $src ) . '" title="' . esc_attr( $title ) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>'
			. '</div>';
	}

	/* =====================================================================
	 * Posts / Loop Carousel (shared query + card)
	 * ===================================================================== */

	/**
	 * Resolve the taxonomy used by a category/tag source for a post type.
	 *
	 * @param string $post_type Post type.
	 * @param string $source    category|tag.
	 * @return string
	 */
	private static function source_taxonomy( $post_type, $source ) {
		$preferred = ( 'tag' === $source ) ? array( 'post_tag', 'product_tag' ) : array( 'category', 'product_cat' );
		foreach ( $preferred as $tax ) {
			if ( taxonomy_exists( $tax ) && is_object_in_taxonomy( $post_type, $tax ) ) {
				return $tax;
			}
		}
		$taxes = get_object_taxonomies( $post_type );
		return $taxes ? $taxes[0] : '';
	}

	/**
	 * Build a WP_Query from a Posts / Loop Carousel settings array.
	 *
	 * @param array  $s            Settings.
	 * @param string $default_type Default post type.
	 * @return WP_Query
	 */
	private static function post_query( $s, $default_type = 'post' ) {
		$post_type = ajs_builder_get( $s, 'post_type', $default_type );
		if ( ! post_type_exists( $post_type ) ) {
			$post_type = $default_type;
		}

		$source  = ajs_builder_get( $s, 'source', 'latest' );
		$limit   = max( 1, (int) ajs_builder_get( $s, 'limit', 6 ) );
		$orderby = ajs_builder_get( $s, 'orderby', 'date' );
		$order   = strtoupper( (string) ajs_builder_get( $s, 'order', 'DESC' ) );
		$order   = ( 'ASC' === $order ) ? 'ASC' : 'DESC';

		$allowed = array( 'date', 'title', 'menu_order', 'comment_count', 'rand', 'ID', 'modified' );
		if ( ! in_array( $orderby, $allowed, true ) ) {
			$orderby = 'date';
		}

		$args = array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'orderby'             => $orderby,
			'order'               => $order,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		if ( 'ids' === $source ) {
			$ids = array_filter( array_map( 'absint', explode( ',', (string) ajs_builder_get( $s, 'ids', '' ) ) ) );
			if ( $ids ) {
				$args['post__in'] = $ids;
				$args['orderby']  = 'post__in';
			}
		} elseif ( 'category' === $source || 'tag' === $source ) {
			$key   = ( 'tag' === $source ) ? 'tag' : 'category';
			$slugs = array_filter( array_map( 'trim', explode( ',', (string) ajs_builder_get( $s, $key, '' ) ) ) );
			$tax   = $slugs ? self::source_taxonomy( $post_type, $source ) : '';
			if ( $tax ) {
				$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => $tax,
						'field'    => 'slug',
						'terms'    => $slugs,
					),
				);
			}
		}

		return new WP_Query( $args );
	}

	/**
	 * Render one post card shared by the Posts and Loop Carousel modules.
	 *
	 * @param int    $post_id Post id.
	 * @param array  $s       Settings.
	 * @param string $layout  Layout override.
	 * @return string
	 */
	private static function post_card( $post_id, $s, $layout = '' ) {
		$layout    = $layout ? $layout : ajs_builder_get( $s, 'layout', 'grid' );
		$ratio     = ajs_builder_get( $s, 'image_ratio', '16:9' );
		$permalink = get_permalink( $post_id );
		$title     = get_the_title( $post_id );

		$title_color = ajs_builder_get( $s, 'title_color', '' );
		$text_color  = ajs_builder_get( $s, 'text_color', '' );
		$meta_color  = ajs_builder_get( $s, 'meta_color', '' );

		$media = '';
		if ( ajs_builder_get( $s, 'show_image', true ) && 'minimal' !== $layout ) {
			$ar = esc_attr( str_replace( ':', ' / ', $ratio ) );
			$img = get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'ajs-post__img', 'loading' => 'lazy', 'decoding' => 'async' ) );
			if ( $img ) {
				$media = '<a class="ajs-post__media" href="' . esc_url( $permalink ) . '" style="aspect-ratio:' . $ar . '">' . $img . '</a>';
			} else {
				$media = '<a class="ajs-post__media ajs-post__media--ph" href="' . esc_url( $permalink ) . '" style="aspect-ratio:' . $ar . '">' . Ajs_Builder_Icons::svg( 'grid' ) . '</a>';
			}
		}

		$bits = array();
		if ( ajs_builder_get( $s, 'show_category', true ) ) {
			$cats = get_the_category_list( ' / ', '', $post_id );
			if ( ! $cats ) {
				$terms = get_the_terms( $post_id, self::source_taxonomy( get_post_type( $post_id ), 'category' ) );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$links = array();
					foreach ( $terms as $term ) {
						$tlink = get_term_link( $term );
						if ( is_wp_error( $tlink ) ) {
							continue;
						}
						$links[] = '<a href="' . esc_url( $tlink ) . '">' . esc_html( $term->name ) . '</a>';
					}
					$cats = implode( ' / ', $links );
				}
			}
			if ( $cats ) {
				$bits[] = '<span class="ajs-post__cat">' . wp_kses_post( $cats ) . '</span>';
			}
		}
		if ( ajs_builder_get( $s, 'show_date', true ) ) {
			$bits[] = '<span class="ajs-post__date">' . esc_html( get_the_date( '', $post_id ) ) . '</span>';
		}
		if ( ajs_builder_get( $s, 'show_author', false ) ) {
			$bits[] = '<span class="ajs-post__author">' . esc_html( get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ) ) . '</span>';
		}
		if ( ajs_builder_get( $s, 'show_comments', false ) ) {
			$count  = (int) get_comments_number( $post_id );
			$bits[] = '<span class="ajs-post__comments">' . esc_html( sprintf( /* translators: %s: comment count. */ _n( '%s comment', '%s comments', $count, 'ajs-builder' ), number_format_i18n( $count ) ) ) . '</span>';
		}

		$meta = '';
		if ( $bits ) {
			$meta = '<div class="ajs-post__meta"' . ajs_builder_style( array( 'color' => $meta_color ) ) . '>'
				. implode( '<span class="ajs-post__sep" aria-hidden="true">·</span>', $bits ) . '</div>';
		}

		$excerpt = '';
		if ( ajs_builder_get( $s, 'show_excerpt', true ) ) {
			$len  = max( 3, (int) ajs_builder_get( $s, 'excerpt_length', 18 ) );
			$text = get_the_excerpt( $post_id );
			if ( '' === trim( (string) $text ) ) {
				$text = wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) );
			}
			$excerpt = '<p class="ajs-post__excerpt"' . ajs_builder_style( array( 'color' => $text_color ) ) . '>' . esc_html( wp_trim_words( $text, $len, '…' ) ) . '</p>';
		}

		$readmore = ajs_builder_get( $s, 'readmore', '' );
		$more     = $readmore
			? '<a class="ajs-post__more" href="' . esc_url( $permalink ) . '">' . esc_html( $readmore ) . Ajs_Builder_Icons::svg( 'arrow-right' ) . '</a>'
			: '';

		$title_html = '<h3 class="ajs-post__title"' . ajs_builder_style( array( 'color' => $title_color ) ) . '><a href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></h3>';

		$body = '<div class="ajs-post__body">' . $meta . $title_html . $excerpt . $more . '</div>';

		$style = array();
		$bg    = ajs_builder_get( $s, 'card_bg', '' );
		if ( $bg ) {
			$style['background-color'] = $bg;
		}
		if ( ajs_builder_get( $s, 'card_border', false ) ) {
			$style['border'] = '1px solid #e5e7eb';
		}

		return '<article class="ajs-post ajs-post--' . esc_attr( $layout ) . '"' . ajs_builder_style( $style ) . '>' . $media . $body . '</article>';
	}

	/**
	 * Posts module — grid, list, overlay and minimal layouts.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_posts( $s ) {
		$q = self::post_query( $s, 'post' );
		if ( ! $q->have_posts() ) {
			return '<div class="ajs-w-notice">' . esc_html__( 'No posts found.', 'ajs-builder' ) . '</div>';
		}

		$layout  = ajs_builder_get( $s, 'layout', 'grid' );
		$columns = max( 1, min( 4, (int) ajs_builder_get( $s, 'columns', 3 ) ) );
		$gap     = max( 0, (int) ajs_builder_get( $s, 'gap', 24 ) );
		$radius  = (int) ajs_builder_get( $s, 'radius', 12 );
		$align   = ajs_builder_get( $s, 'align', 'left' );

		$out = '';
		while ( $q->have_posts() ) {
			$q->the_post();
			$out .= self::post_card( get_the_ID(), $s, $layout );
		}
		wp_reset_postdata();

		return '<div class="ajs-w-posts ajs-w-posts--' . esc_attr( $layout ) . ' ajs-w-posts--c' . $columns . ' ajs-align-' . esc_attr( $align ) . '"'
			. ajs_builder_style( array( '--ajs-posts-gap' => $gap . 'px', '--ajs-posts-radius' => $radius . 'px' ) ) . '>' . $out . '</div>';
	}

	/**
	 * Loop Carousel module — a scroll-snapping carousel of post cards.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_loop_carousel( $s ) {
		$q = self::post_query( $s, 'post' );
		if ( ! $q->have_posts() ) {
			return '<div class="ajs-w-notice">' . esc_html__( 'No items found.', 'ajs-builder' ) . '</div>';
		}

		$desktop  = max( 1, min( 6, (int) ajs_builder_get( $s, 'slides', 3 ) ) );
		$tablet   = max( 1, min( 6, (int) ajs_builder_get( $s, 'slides_tablet', 2 ) ) );
		$mobile   = max( 1, min( 6, (int) ajs_builder_get( $s, 'slides_mobile', 1 ) ) );
		$gap      = max( 0, (int) ajs_builder_get( $s, 'gap', 20 ) );
		$autoplay = ajs_builder_get( $s, 'autoplay', true );
		$speed    = max( 1000, (int) ajs_builder_get( $s, 'speed', 4000 ) );
		$loop     = ajs_builder_get( $s, 'loop', true );
		$pause    = ajs_builder_get( $s, 'pause', true );
		$arrows   = ajs_builder_get( $s, 'arrows', true );
		$dots     = ajs_builder_get( $s, 'dots', true );
		$align    = ajs_builder_get( $s, 'align', 'left' );

		$count = 0;
		$items = '';
		while ( $q->have_posts() ) {
			$q->the_post();
			$items .= '<div class="ajs-loop__item">' . self::post_card( get_the_ID(), $s, 'grid' ) . '</div>';
			$count++;
		}
		wp_reset_postdata();

		$nav = '';
		if ( $arrows && $count > 1 ) {
			$nav = '<button type="button" class="ajs-loop__nav ajs-loop__nav--prev" aria-label="' . esc_attr__( 'Previous', 'ajs-builder' ) . '">' . Ajs_Builder_Icons::svg( 'arrow-right' ) . '</button>'
				. '<button type="button" class="ajs-loop__nav ajs-loop__nav--next" aria-label="' . esc_attr__( 'Next', 'ajs-builder' ) . '">' . Ajs_Builder_Icons::svg( 'arrow-right' ) . '</button>';
		}

		$dots_html = '';
		if ( $dots && $count > 1 ) {
			$d = '';
			for ( $i = 0; $i < $count; $i++ ) {
				$d .= '<button type="button" class="ajs-loop__dot' . ( 0 === $i ? ' is-active' : '' ) . '" data-index="' . $i . '" aria-label="' . esc_attr( sprintf( /* translators: %d: item number. */ __( 'Go to item %d', 'ajs-builder' ), $i + 1 ) ) . '"></button>';
			}
			$dots_html = '<div class="ajs-loop__dots">' . $d . '</div>';
		}

		$rules = array(
			'--ajs-loop-cols'        => $desktop,
			'--ajs-loop-cols-tablet' => $tablet,
			'--ajs-loop-cols-mobile' => $mobile,
			'--ajs-loop-gap'         => $gap . 'px',
			'--ajs-posts-radius'      => (int) ajs_builder_get( $s, 'radius', 12 ) . 'px',
		);

		return '<div class="ajs-w-loop ajs-align-' . esc_attr( $align ) . '" data-loop data-autoplay="' . ( $autoplay ? '1' : '0' ) . '" data-speed="' . esc_attr( $speed ) . '" data-loop-scroll="' . ( $loop ? '1' : '0' ) . '" data-pause="' . ( $pause ? '1' : '0' ) . '"' . ajs_builder_style( $rules ) . '>'
			. '<div class="ajs-loop__track">' . $items . '</div>'
			. $nav . $dots_html
			. '</div>';
	}

	/**
	 * Author Box module.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_author_box( $s ) {
		$source  = ajs_builder_get( $s, 'source', 'current' );
		$heading = ajs_builder_get( $s, 'heading', '' );
		$layout  = ajs_builder_get( $s, 'layout', 'left' );
		$align   = ajs_builder_get( $s, 'align', 'left' );
		$shape   = ajs_builder_get( $s, 'avatar_shape', 'circle' );
		$size    = max( 40, min( 200, (int) ajs_builder_get( $s, 'avatar_size', 96 ) ) );

		$name   = '';
		$role   = '';
		$bio    = '';
		$link   = '';
		$avatar = '';

		if ( 'current' === $source ) {
			$author_id = (int) get_post_field( 'post_author', get_the_ID() );
			if ( ! $author_id ) {
				$author_id = get_current_user_id();
			}
			if ( $author_id ) {
				$name   = (string) get_the_author_meta( 'display_name', $author_id );
				$bio    = (string) get_the_author_meta( 'description', $author_id );
				$link   = get_author_posts_url( $author_id );
				$avatar = (string) get_avatar_url( $author_id, array( 'size' => $size ) );
			}
		}

		if ( 'manual' === $source || ( '' === $name && '' === $role ) ) {
			if ( '' === $name ) {
				$name = ajs_builder_get( $s, 'name', '' );
			}
			$role = ajs_builder_get( $s, 'role', '' );
			if ( '' === $bio ) {
				$bio = ajs_builder_get( $s, 'bio', '' );
			}
			if ( '' === $link ) {
				$link = ajs_builder_get( $s, 'link', '' );
			}
			$manual_avatar = self::media_url( ajs_builder_get( $s, 'avatar', array() ) );
			if ( $manual_avatar ) {
				$avatar = $manual_avatar;
			}
		}

		if ( $avatar ) {
			$ava_html = '<img class="ajs-authorbox__avatar ajs-authorbox__avatar--' . esc_attr( $shape ) . '" src="' . esc_url( $avatar ) . '" alt="' . esc_attr( $name ) . '" loading="lazy"'
				. ajs_builder_style( array( 'width' => $size . 'px', 'height' => $size . 'px' ) ) . '>';
		} else {
			$ava_html = '<span class="ajs-authorbox__avatar ajs-authorbox__avatar--' . esc_attr( $shape ) . ' ajs-authorbox__avatar--ph"' . ajs_builder_style( array( 'width' => $size . 'px', 'height' => $size . 'px' ) ) . '>' . Ajs_Builder_Icons::svg( 'user' ) . '</span>';
		}

		$name_html = '';
		if ( $name ) {
			$inner     = $link ? '<a href="' . esc_url( $link ) . '">' . esc_html( $name ) . '</a>' : esc_html( $name );
			$name_html = '<h3 class="ajs-authorbox__name"' . ajs_builder_style( array( 'color' => ajs_builder_get( $s, 'name_color', '' ) ) ) . '>' . $inner . '</h3>';
		}

		$social = '';
		if ( ajs_builder_get( $s, 'show_social', false ) ) {
			$items = ajs_builder_get( $s, 'social', array() );
			if ( is_array( $items ) && $items ) {
				$labels = Ajs_Builder_Icons::labels();
				$links  = '';
				foreach ( $items as $item ) {
					$icon  = isset( $item['icon'] ) ? $item['icon'] : 'globe';
					$url   = isset( $item['link'] ) ? $item['link'] : '';
					$label = isset( $labels[ $icon ] ) ? $labels[ $icon ] : $icon;
					$links .= '<a class="ajs-authorbox__social" href="' . esc_url( $url ? $url : '#' ) . '" aria-label="' . esc_attr( $label ) . '">' . Ajs_Builder_Icons::svg( $icon ) . '</a>';
				}
				$social = '<div class="ajs-authorbox__socials">' . $links . '</div>';
			}
		}

		$rules = array( 'border-radius' => (int) ajs_builder_get( $s, 'radius', 16 ) . 'px' );
		if ( ajs_builder_get( $s, 'bg_color', '' ) ) {
			$rules['background-color'] = ajs_builder_get( $s, 'bg_color', '' );
		}

		return '<div class="ajs-authorbox ajs-authorbox--' . esc_attr( $layout ) . ' ajs-align-' . esc_attr( $align ) . '"' . ajs_builder_style( $rules ) . '>'
			. '<div class="ajs-authorbox__avatar-wrap">' . $ava_html . '</div>'
			. '<div class="ajs-authorbox__body">'
			. ( $heading ? '<span class="ajs-authorbox__heading">' . esc_html( $heading ) . '</span>' : '' )
			. $name_html
			. ( $role ? '<span class="ajs-authorbox__role">' . esc_html( $role ) . '</span>' : '' )
			. ( $bio ? '<p class="ajs-authorbox__bio"' . ajs_builder_style( array( 'color' => ajs_builder_get( $s, 'text_color', '' ) ) ) . '>' . esc_html( $bio ) . '</p>' : '' )
			. $social
			. '</div></div>';
	}

	/* =====================================================================
	 * WordPress widgets (core) + Contact Form 7
	 *
	 * These modules render the output of a registered WordPress widget by
	 * calling its own widget() method, so any core (or plugin) widget keeps
	 * working with its native markup.
	 * ===================================================================== */

	/**
	 * Find a registered widget instance by its id_base.
	 *
	 * @param string $id_base Widget id base (e.g. "archives").
	 * @return WP_Widget|null
	 */
	private static function find_wp_widget( $id_base ) {
		global $wp_widget_factory;
		if ( empty( $wp_widget_factory ) || empty( $wp_widget_factory->widgets ) ) {
			return null;
		}
		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( is_object( $widget ) && isset( $widget->id_base ) && $widget->id_base === $id_base ) {
				return $widget;
			}
		}
		return null;
	}

	/**
	 * Render a registered WordPress widget.
	 *
	 * @param string $id_base Widget id base.
	 * @param array  $instance Instance settings.
	 * @param string $label   Human readable label for notices.
	 * @return string
	 */
	private static function render_wp_widget( $id_base, $instance, $label = '' ) {
		$widget = self::find_wp_widget( $id_base );
		if ( ! $widget ) {
			if ( '' === $label ) {
				$label = $id_base;
			}
			return '<div class="ajs-w-notice">' . sprintf( /* translators: %s: widget name. */ esc_html__( 'The %s widget is not available.', 'ajs-builder' ), esc_html( $label ) ) . '</div>';
		}

		$instance = is_array( $instance ) ? $instance : array();

		$args = array(
			'name'          => __( 'Sidebar', 'ajs-builder' ),
			'id'            => 'ajs-builder',
			'description'   => '',
			'class'         => '',
			'before_widget' => '<div class="ajs-w-widget ajs-w-widget--' . esc_attr( $id_base ) . '">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="ajs-w-widget__title">',
			'after_title'   => '</h2>',
			'widget_id'     => 'ajs-w-' . $id_base,
			'widget_name'   => isset( $widget->name ) ? $widget->name : $id_base,
		);

		$had_post      = array_key_exists( 'post', $GLOBALS );
		$previous_post = $had_post ? $GLOBALS['post'] : null;

		ob_start();
		try {
			$widget->widget( $args, $instance );
		} catch ( \Throwable $e ) {
			// Never let a single widget break the whole page.
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			return '<div class="ajs-w-notice">' . esc_html__( 'This widget could not be displayed.', 'ajs-builder' ) . '</div>';
		}
		$html = ob_get_clean();

		if ( $had_post ) {
			$GLOBALS['post'] = $previous_post;
		} elseif ( isset( $GLOBALS['post'] ) ) {
			unset( $GLOBALS['post'] );
		}

		if ( '' === trim( (string) $html ) ) {
			return '';
		}

		return $html;
	}

	/**
	 * Archives widget.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_wp_archives( $s ) {
		return self::render_wp_widget(
			'archives',
			array(
				'title'    => ajs_builder_get( $s, 'title', '' ),
				'dropdown' => ajs_builder_get( $s, 'dropdown', false ) ? 1 : 0,
				'count'    => ajs_builder_get( $s, 'count', false ) ? 1 : 0,
			),
			__( 'Archives', 'ajs-builder' )
		);
	}

	/**
	 * Calendar widget.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_wp_calendar( $s ) {
		return self::render_wp_widget(
			'calendar',
			array( 'title' => ajs_builder_get( $s, 'title', '' ) ),
			__( 'Calendar', 'ajs-builder' )
		);
	}

	/**
	 * Categories widget.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_wp_categories( $s ) {
		return self::render_wp_widget(
			'categories',
			array(
				'title'        => ajs_builder_get( $s, 'title', '' ),
				'dropdown'     => ajs_builder_get( $s, 'dropdown', false ) ? 1 : 0,
				'count'        => ajs_builder_get( $s, 'count', false ) ? 1 : 0,
				'hierarchical' => ajs_builder_get( $s, 'hierarchical', false ) ? 1 : 0,
			),
			__( 'Categories', 'ajs-builder' )
		);
	}

	/**
	 * Pages widget.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_wp_pages( $s ) {
		return self::render_wp_widget(
			'pages',
			array(
				'title'   => ajs_builder_get( $s, 'title', '' ),
				'sortby'  => ajs_builder_get( $s, 'sortby', 'menu_order' ),
				'exclude' => ajs_builder_get( $s, 'exclude', '' ),
			),
			__( 'Pages', 'ajs-builder' )
		);
	}

	/**
	 * Meta widget.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_wp_meta( $s ) {
		return self::render_wp_widget(
			'meta',
			array( 'title' => ajs_builder_get( $s, 'title', '' ) ),
			__( 'Meta', 'ajs-builder' )
		);
	}

	/**
	 * Recent Posts widget.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_wp_recent_posts( $s ) {
		return self::render_wp_widget(
			'recent-posts',
			array(
				'title'     => ajs_builder_get( $s, 'title', '' ),
				'number'    => max( 1, min( 20, (int) ajs_builder_get( $s, 'number', 5 ) ) ),
				'show_date' => ajs_builder_get( $s, 'show_date', false ) ? 1 : 0,
			),
			__( 'Recent Posts', 'ajs-builder' )
		);
	}

	/**
	 * Recent Comments widget.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_wp_recent_comments( $s ) {
		return self::render_wp_widget(
			'recent-comments',
			array(
				'title'  => ajs_builder_get( $s, 'title', '' ),
				'number' => max( 1, min( 20, (int) ajs_builder_get( $s, 'number', 5 ) ) ),
			),
			__( 'Recent Comments', 'ajs-builder' )
		);
	}

	/**
	 * Tag Cloud widget.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_wp_tag_cloud( $s ) {
		return self::render_wp_widget(
			'tag_cloud',
			array(
				'title'    => ajs_builder_get( $s, 'title', '' ),
				'taxonomy' => ajs_builder_get( $s, 'taxonomy', 'post_tag' ),
				'count'    => ajs_builder_get( $s, 'count', false ) ? 1 : 0,
			),
			__( 'Tag Cloud', 'ajs-builder' )
		);
	}

	/**
	 * RSS widget.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_wp_rss( $s ) {
		$url = ajs_builder_get( $s, 'url', '' );
		if ( '' === $url ) {
			return '<div class="ajs-w-notice">' . esc_html__( 'Add a feed URL to display this RSS widget.', 'ajs-builder' ) . '</div>';
		}
		return self::render_wp_widget(
			'rss',
			array(
				'title'        => ajs_builder_get( $s, 'title', '' ),
				'url'          => $url,
				'items'        => max( 1, min( 20, (int) ajs_builder_get( $s, 'items', 5 ) ) ),
				'show_summary' => ajs_builder_get( $s, 'show_summary', false ) ? 1 : 0,
				'show_author'  => ajs_builder_get( $s, 'show_author', false ) ? 1 : 0,
				'show_date'    => ajs_builder_get( $s, 'show_date', false ) ? 1 : 0,
			),
			__( 'RSS', 'ajs-builder' )
		);
	}

	/**
	 * Contact Form 7 module.
	 *
	 * @param array $s Settings.
	 * @return string
	 */
	private static function w_cf7( $s ) {
		$active = defined( 'WPCF7_VERSION' ) || class_exists( 'WPCF7' ) || function_exists( 'wpcf7' );
		$form   = ajs_builder_get( $s, 'form', '' );
		$title  = ajs_builder_get( $s, 'title', '' );
		$align  = ajs_builder_get( $s, 'align', 'left' );

		$heading = '' !== $title ? '<h3 class="ajs-w-cf7__title">' . esc_html( $title ) . '</h3>' : '';

		if ( ! $active ) {
			// Only tell editors that the plugin is missing; keep the live site clean.
			$body = current_user_can( 'edit_posts' )
				? '<div class="ajs-w-notice">' . esc_html__( 'Contact Form 7 is not active. Install and activate the plugin to use this module.', 'ajs-builder' ) . '</div>'
				: '';
			return '<div class="ajs-w-cf7 ajs-align-' . esc_attr( $align ) . '">' . $heading . $body . '</div>';
		}

		if ( '' === $form ) {
			$body = current_user_can( 'edit_posts' )
				? '<div class="ajs-w-notice">' . esc_html__( 'Select a Contact Form 7 form.', 'ajs-builder' ) . '</div>'
				: '';
			return '<div class="ajs-w-cf7 ajs-align-' . esc_attr( $align ) . '">' . $heading . $body . '</div>';
		}

		$shortcode = '[contact-form-7 id="' . absint( $form ) . '"]';
		$html      = do_shortcode( $shortcode );

		return '<div class="ajs-w-cf7 ajs-align-' . esc_attr( $align ) . '">' . $heading . $html . '</div>';
	}
}
