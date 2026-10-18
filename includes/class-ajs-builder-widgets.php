<?php
/**
 * Widget (module) registry.
 *
 * Every module is defined once here: its controls (which drive the editor UI)
 * and a PHP renderer (the single source of truth for front-end output).
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registry of builder modules.
 */
class Ajs_Builder_Widgets {

	/**
	 * Cached definitions.
	 *
	 * @var array|null
	 */
	private static $widgets = null;

	/**
	 * All widget definitions.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$widgets ) {
			$defs = self::definitions();

			/**
			 * Filter the registered builder widgets.
			 *
			 * @param array $defs Widget definitions.
			 */
			self::$widgets = apply_filters( 'ajs_builder_widgets', $defs );
		}
		return self::$widgets;
	}

	/**
	 * Get one widget definition.
	 *
	 * @param string $type Widget type.
	 * @return array|null
	 */
	public static function get( $type ) {
		$all = self::all();
		return isset( $all[ $type ] ) ? $all[ $type ] : null;
	}

	/**
	 * Default settings for a widget type, derived from its controls.
	 *
	 * @param string $type Widget type.
	 * @return array
	 */
	public static function defaults( $type ) {
		$def = self::get( $type );
		if ( ! $def ) {
			return array();
		}
		$out = array();
		foreach ( ( $def['controls'] ?? array() ) as $key => $control ) {
			if ( isset( $control['default'] ) ) {
				$out[ $key ] = $control['default'];
			}
		}
		return $out;
	}

	/**
	 * Widget types whose Content controls can be varied per device.
	 *
	 * @return array
	 */
	public static function content_variant_types() {
		return array( 'heading', 'text', 'image', 'button', 'icon', 'icon-box', 'image-box', 'counter', 'cta', 'html', 'video', 'logo' );
	}

	/**
	 * Control input types that can be varied per device (text/media/link/…).
	 * Structural inputs (repeaters, raw code, switches) are excluded because
	 * they either nest other controls or have no reliable "inherit" state.
	 *
	 * @return array
	 */
	public static function content_variant_control_types() {
		return array( 'text', 'textarea', 'editor', 'media', 'url', 'icon', 'number', 'select', 'wysiwyg' );
	}

	/**
	 * Is a widget + control a per-device content variant?
	 *
	 * @param string $type    Widget type.
	 * @param array  $control Control definition.
	 * @return bool
	 */
	public static function is_content_variant_control( $type, $control ) {
		if ( ! in_array( $type, self::content_variant_types(), true ) ) {
			return false;
		}
		if ( ! is_array( $control ) || ! isset( $control['group'] ) || 'Content' !== $control['group'] ) {
			return false;
		}
		$ctype = isset( $control['type'] ) ? $control['type'] : '';
		return in_array( $ctype, self::content_variant_control_types(), true );
	}

	/**
	 * Groups (panel categories), in order.
	 *
	 * @return array
	 */
	public static function groups() {
		return array(
			'layout'   => __( 'Layout & Basic', 'ajs-builder' ),
			'content'  => __( 'Content', 'ajs-builder' ),
			'blog'     => __( 'Blog & Posts', 'ajs-builder' ),
			'woo'      => __( 'WooCommerce Products', 'ajs-builder' ),
			'woo-tabs' => __( 'Product Category Tabs', 'ajs-builder' ),
			'media'    => __( 'Media', 'ajs-builder' ),
			'header'   => __( 'Header / Footer', 'ajs-builder' ),
			'wp'       => __( 'WordPress Widgets', 'ajs-builder' ),
			'forms'    => __( 'Forms', 'ajs-builder' ),
		);
	}

	/**
	 * A compact payload for the editor UI (titles + controls only).
	 *
	 * @return array
	 */
	public static function export() {
		$disabled = (array) Ajs_Builder_Settings::get( 'disabled_modules', array() );
		$out      = array();
		foreach ( self::all() as $type => $def ) {
			$controls = $def['controls'];
			// Content controls that support per-device values are flagged
			// responsive so the editor renders device tabs for them.
			foreach ( $controls as $ck => $ctrl ) {
				if ( self::is_content_variant_control( $type, $ctrl ) ) {
					$controls[ $ck ]['responsive'] = true;
				}
			}
			$out[ $type ] = array(
				'title'    => $def['title'],
				'group'    => $def['group'],
				'groups'   => isset( $def['groups'] ) && is_array( $def['groups'] ) ? $def['groups'] : array( $def['group'] ),
				'disabled' => in_array( $type, $disabled, true ),
				'controls' => $controls,
			);
		}
		return $out;
	}

	/**
	 * Is a module enabled in the global settings?
	 *
	 * @param string $type Widget type.
	 * @return bool
	 */
	public static function is_enabled( $type ) {
		$disabled = (array) Ajs_Builder_Settings::get( 'disabled_modules', array() );
		return ! in_array( $type, $disabled, true );
	}

	/**
	 * Convert a control default into a typed value for JS.
	 *
	 * @param mixed $default Raw default.
	 * @return mixed
	 */
	private static function typed( $default ) {
		return $default;
	}

	/**
	 * Definitions.
	 *
	 * @return array
	 */
	private static function definitions() {
		$align     = array(
			'type'    => 'choose',
			'label'   => __( 'Alignment', 'ajs-builder' ),
			'group'   => 'Style',
			'default' => 'left',
			'choices' => array(
				'left'   => __( 'Left', 'ajs-builder' ),
				'center' => __( 'Center', 'ajs-builder' ),
				'right'  => __( 'Right', 'ajs-builder' ),
			),
		);
		$text_color = array(
			'type'       => 'color',
			'label'      => __( 'Text colour', 'ajs-builder' ),
			'group'      => 'Style',
			'responsive' => true,
		);
		$typography = array(
			'type'       => 'typography',
			'label'      => __( 'Typography', 'ajs-builder' ),
			'group'      => 'Style',
			'responsive' => true,
		);
		$wp_title   = array(
			'type'  => 'text',
			'label' => __( 'Title', 'ajs-builder' ),
			'group' => 'Content',
		);

		return array(

			/* ---------------------------------------------------------------- Heading */
			'heading'        => array(
				'title'    => __( 'Heading', 'ajs-builder' ),
				'group'    => 'layout',
				'controls' => array(
					'text'       => array(
						'type'    => 'text',
						'label'   => __( 'Title', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Add Your Heading Text Here', 'ajs-builder' ),
					),
					'tag'        => array(
						'type'    => 'select',
						'label'   => __( 'HTML tag', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'h2',
						'choices' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6', 'div' => 'div', 'span' => 'span', 'p' => 'p' ),
					),
					'subtitle'   => array(
						'type'    => 'textarea',
						'label'   => __( 'Subtitle', 'ajs-builder' ),
						'group'   => 'Content',
						'rows'    => 2,
						'default' => '',
					),
					'link'       => array(
						'type'  => 'url',
						'label' => __( 'Link', 'ajs-builder' ),
						'group' => 'Content',
					),
					'align'      => $align,
					'color'      => $text_color,
					'subtitle_color' => array(
						'type'       => 'color',
						'label'      => __( 'Subtitle colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'typography' => array(
						'type'       => 'typography',
						'label'      => __( 'Typography', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
				),
			),

			/* ---------------------------------------------------------------- Text */
			'text'           => array(
				'title'    => __( 'Text Editor', 'ajs-builder' ),
				'group'    => 'layout',
				'controls' => array(
					'text'  => array(
						'type'    => 'editor',
						'label'   => __( 'Text', 'ajs-builder' ),
						'group'   => 'Content',
						'rows'    => 8,
						'default' => __( 'Add your text here. Use the toolbar to add links and basic formatting.', 'ajs-builder' ),
					),
					'align' => $align,
					'color' => $text_color,
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Image */
			'image'          => array(
				'title'    => __( 'Image', 'ajs-builder' ),
				'group'    => 'layout',
				'controls' => array(
					'image'   => array(
						'type'    => 'media',
						'label'   => __( 'Image', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => array( 'id' => 0, 'url' => '' ),
					),
					'caption' => array(
						'type'  => 'text',
						'label' => __( 'Caption', 'ajs-builder' ),
						'group' => 'Content',
					),
					'link'    => array(
						'type'  => 'url',
						'label' => __( 'Link', 'ajs-builder' ),
						'group' => 'Content',
					),
					'align'   => array(
						'type'       => 'choose',
						'label'      => __( 'Alignment', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 'left',
						'choices'    => array( 'left' => __( 'Left', 'ajs-builder' ), 'center' => __( 'Center', 'ajs-builder' ), 'right' => __( 'Right', 'ajs-builder' ) ),
						'responsive' => true,
					),
					'width'    => array(
						'type'       => 'slider',
						'label'      => __( 'Width (%)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 100,
						'min'        => 10,
						'max'        => 100,
						'responsive' => true,
					),
					'px_width' => array(
						'type'        => 'number',
						'label'       => __( 'Width (px)', 'ajs-builder' ),
						'group'       => 'Style',
						'placeholder' => __( 'optional', 'ajs-builder' ),
						'hint'        => __( 'Set a fixed pixel width to resize the image (overrides the % width).', 'ajs-builder' ),
					),
					'height'   => array(
						'type'        => 'number',
						'label'       => __( 'Height (px)', 'ajs-builder' ),
						'group'       => 'Style',
						'placeholder' => __( 'auto', 'ajs-builder' ),
						'responsive'  => true,
					),
					'fit'      => array(
						'type'       => 'select',
						'label'      => __( 'Crop / fit', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 'cover',
						'responsive' => true,
						'choices'    => array( 'cover' => __( 'Cover', 'ajs-builder' ), 'contain' => __( 'Contain', 'ajs-builder' ), 'fill' => __( 'Stretch', 'ajs-builder' ), 'none' => __( 'None', 'ajs-builder' ) ),
					),
					'radius'   => array(
						'type'       => 'number',
						'label'      => __( 'Border radius (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 12,
						'responsive' => true,
					),
					'opacity'  => array(
						'type'       => 'slider',
						'label'      => __( 'Opacity (%)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 100,
						'min'        => 0,
						'max'        => 100,
						'responsive' => true,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Button */
			'button'         => array(
				'title'    => __( 'Button', 'ajs-builder' ),
				'group'    => 'layout',
				'groups'   => array( 'layout', 'header' ),
				'controls' => array(
					'text'          => array(
						'type'    => 'text',
						'label'   => __( 'Label', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Click here', 'ajs-builder' ),
					),
					'link'          => array(
						'type'    => 'url',
						'label'   => __( 'Link', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => '#',
					),
					'target'        => array(
						'type'    => 'switch',
						'label'   => __( 'Open in new tab', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'icon'          => array(
						'type'  => 'icon',
						'label' => __( 'Icon', 'ajs-builder' ),
						'group' => 'Content',
					),
					'icon_position' => array(
						'type'    => 'choose',
						'label'   => __( 'Icon position', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'right',
						'choices' => array( 'left' => __( 'Left', 'ajs-builder' ), 'right' => __( 'Right', 'ajs-builder' ) ),
					),
					'align'         => $align,
					'size'          => array(
						'type'    => 'choose',
						'label'   => __( 'Size', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'md',
						'choices' => array( 'sm' => __( 'S', 'ajs-builder' ), 'md' => __( 'M', 'ajs-builder' ), 'lg' => __( 'L', 'ajs-builder' ) ),
					),
					'variant'       => array(
						'type'    => 'choose',
						'label'   => __( 'Style', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'solid',
						'choices' => array( 'solid' => __( 'Solid', 'ajs-builder' ), 'outline' => __( 'Outline', 'ajs-builder' ), 'link' => __( 'Text', 'ajs-builder' ) ),
					),
					'bg_color'      => array(
						'type'       => 'color',
						'label'      => __( 'Background', 'ajs-builder' ),
						'group'      => 'Style',
						'gradient'   => true,
						'responsive' => true,
					),
					'text_color'    => array(
						'type'       => 'color',
						'label'      => __( 'Text', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'radius'        => array(
						'type'       => 'number',
						'label'      => __( 'Border radius (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 8,
						'responsive' => true,
					),
					'full_width'    => array(
						'type'    => 'switch',
						'label'   => __( 'Full width', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => false,
					),
					'typography'    => $typography,
				),
			),

			/* ---------------------------------------------------------------- Divider */
			'divider'        => array(
				'title'    => __( 'Divider', 'ajs-builder' ),
				'group'    => 'layout',
				'controls' => array(
					'style' => array(
						'type'    => 'select',
						'label'   => __( 'Style', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'solid',
						'choices' => array( 'solid' => __( 'Solid', 'ajs-builder' ), 'dashed' => __( 'Dashed', 'ajs-builder' ), 'dotted' => __( 'Dotted', 'ajs-builder' ), 'double' => __( 'Double', 'ajs-builder' ) ),
					),
					'weight' => array(
						'type'       => 'number',
						'label'      => __( 'Thickness (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 1,
						'responsive' => true,
					),
					'width' => array(
						'type'       => 'slider',
						'label'      => __( 'Width (%)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 100,
						'min'        => 5,
						'max'        => 100,
						'responsive' => true,
					),
					'color' => array(
						'type'       => 'color',
						'label'      => __( 'Colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => '#e5e7eb',
						'responsive' => true,
					),
					'align' => $align,
				),
			),

			/* ---------------------------------------------------------------- Spacer */
			'spacer'         => array(
				'title'    => __( 'Spacer', 'ajs-builder' ),
				'group'    => 'layout',
				'controls' => array(
					'space' => array(
						'type'       => 'slider',
						'label'      => __( 'Height (px)', 'ajs-builder' ),
						'group'      => 'Content',
						'default'    => 40,
						'min'        => 0,
						'max'        => 400,
						'responsive' => true,
					),
				),
			),

			/* ---------------------------------------------------------------- Icon */
			'icon'           => array(
				'title'    => __( 'Icon', 'ajs-builder' ),
				'group'    => 'layout',
				'controls' => array(
					'icon'  => array(
						'type'    => 'icon',
						'label'   => __( 'Icon', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'star',
					),
					'link'  => array(
						'type'  => 'url',
						'label' => __( 'Link', 'ajs-builder' ),
						'group' => 'Content',
					),
					'align' => $align,
					'color' => array(
						'type'       => 'color',
						'label'      => __( 'Colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 'var(--ajs-primary)',
						'responsive' => true,
					),
					'size'  => array(
						'type'       => 'number',
						'label'      => __( 'Size (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 40,
						'responsive' => true,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- HTML */
			'html'           => array(
				'title'    => __( 'HTML', 'ajs-builder' ),
				'group'    => 'layout',
				'controls' => array(
					'html' => array(
						'type'    => 'code',
						'label'   => __( 'HTML code', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => '',
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Icon box */
			'icon-box'       => array(
				'title'    => __( 'Icon Box', 'ajs-builder' ),
				'group'    => 'content',
				'controls' => array(
					'icon'        => array(
						'type'    => 'icon',
						'label'   => __( 'Icon', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'truck',
					),
					'title'       => array(
						'type'    => 'text',
						'label'   => __( 'Title', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'This is the heading', 'ajs-builder' ),
					),
					'text'        => array(
						'type'    => 'textarea',
						'label'   => __( 'Description', 'ajs-builder' ),
						'group'   => 'Content',
						'rows'    => 3,
						'default' => __( 'Add a short description to explain this feature.', 'ajs-builder' ),
					),
					'link'        => array(
						'type'  => 'url',
						'label' => __( 'Link', 'ajs-builder' ),
						'group' => 'Content',
					),
					'position'    => array(
						'type'    => 'choose',
						'label'   => __( 'Icon position', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'top',
						'choices' => array( 'top' => __( 'Top', 'ajs-builder' ), 'left' => __( 'Left', 'ajs-builder' ), 'right' => __( 'Right', 'ajs-builder' ) ),
					),
					'align'       => $align,
					'icon_color'  => array(
						'type'       => 'color',
						'label'      => __( 'Icon colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 'var(--ajs-primary)',
						'responsive' => true,
					),
					'icon_size'   => array(
						'type'       => 'number',
						'label'      => __( 'Icon size (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 34,
						'responsive' => true,
					),
					'icon_shape'  => array(
						'type'    => 'choose',
						'label'   => __( 'Icon shape', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'none',
						'choices' => array(
							'none'    => __( 'None', 'ajs-builder' ),
							'square'  => __( 'Square', 'ajs-builder' ),
							'rounded' => __( 'Rounded', 'ajs-builder' ),
							'circle'  => __( 'Circle', 'ajs-builder' ),
						),
					),
					'icon_bg'     => array(
						'type'       => 'color',
						'label'      => __( 'Icon background', 'ajs-builder' ),
						'group'      => 'Style',
						'gradient'   => true,
						'show_if'    => array( 'icon_shape' => array( 'square', 'rounded', 'circle' ) ),
						'responsive' => true,
					),
					'icon_padding' => array(
						'type'       => 'number',
						'label'      => __( 'Icon padding (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 18,
						'show_if'    => array( 'icon_shape' => array( 'square', 'rounded', 'circle' ) ),
						'responsive' => true,
					),
					'title_color' => array(
						'type'       => 'color',
						'label'      => __( 'Title colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'text_color'  => $text_color,
				'typography' => $typography,
			),
		),

		/* ---------------------------------------------------------------- Image Box */
			'image-box'      => array(
				'title'    => __( 'Image Box', 'ajs-builder' ),
				'group'    => 'content',
				'controls' => array(
					'image'          => array(
						'type'    => 'media',
						'label'   => __( 'Image', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => array( 'id' => 0, 'url' => '' ),
					),
					'title'          => array(
						'type'    => 'text',
						'label'   => __( 'Title', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'This is the heading', 'ajs-builder' ),
					),
					'text'           => array(
						'type'    => 'textarea',
						'label'   => __( 'Description', 'ajs-builder' ),
						'group'   => 'Content',
						'rows'    => 2,
						'default' => __( 'Add a short description that sits over the image.', 'ajs-builder' ),
					),
					'button_text'    => array(
						'type'    => 'text',
						'label'   => __( 'Button text', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => '',
					),
					'button_link'    => array(
						'type'    => 'url',
						'label'   => __( 'Button link', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => '',
					),
					'content_position' => array(
						'type'    => 'choose',
						'label'   => __( 'Content position', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'bottom-left',
						'choices' => array(
							'top'          => __( 'Top', 'ajs-builder' ),
							'top-left'     => __( 'Top left', 'ajs-builder' ),
							'top-right'    => __( 'Top right', 'ajs-builder' ),
							'middle'       => __( 'Middle', 'ajs-builder' ),
							'middle-left'  => __( 'Middle left', 'ajs-builder' ),
							'middle-right' => __( 'Middle right', 'ajs-builder' ),
							'bottom'       => __( 'Bottom', 'ajs-builder' ),
							'bottom-left'  => __( 'Bottom left', 'ajs-builder' ),
							'bottom-right' => __( 'Bottom right', 'ajs-builder' ),
						),
					),
					'align'          => $align,
					'height'         => array(
						'type'       => 'number',
						'label'      => __( 'Height (px)', 'ajs-builder' ),
						'group'      => 'Layout',
						'default'    => 340,
						'min'        => 80,
						'responsive' => true,
					),
					'radius'         => array(
						'type'       => 'number',
						'label'      => __( 'Border radius (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 12,
						'responsive' => true,
					),
					'overlay_color'  => array(
						'type'       => 'color',
						'label'      => __( 'Overlay colour', 'ajs-builder' ),
						'group'      => 'Style',
						'gradient'   => true,
						'default'    => 'rgba(15,23,42,.55)',
						'responsive' => true,
					),
					'overlay_opacity' => array(
						'type'    => 'slider',
						'label'   => __( 'Overlay opacity (%)', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 100,
						'min'     => 0,
						'max'     => 100,
					),
					'title_color'    => array(
						'type'       => 'color',
						'label'      => __( 'Title colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => '#ffffff',
						'responsive' => true,
					),
					'text_color'     => array(
						'type'       => 'color',
						'label'      => __( 'Text colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 'rgba(255,255,255,.88)',
						'responsive' => true,
					),
					'button_bg'      => array(
						'type'       => 'color',
						'label'      => __( 'Button background', 'ajs-builder' ),
						'group'      => 'Style',
						'gradient'   => true,
						'default'    => 'var(--ajs-primary)',
						'responsive' => true,
					),
					'button_color'   => array(
						'type'       => 'color',
						'label'      => __( 'Button text colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => '#ffffff',
						'responsive' => true,
					),
					'typography'     => $typography,
				),
			),

		/* ---------------------------------------------------------------- Icon list */
			'icon-list'      => array(
				'title'    => __( 'Icon List', 'ajs-builder' ),
				'group'    => 'content',
				'controls' => array(
					'items'      => array(
						'type'    => 'repeater',
						'label'   => __( 'Items', 'ajs-builder' ),
						'group'   => 'Content',
						'fields'  => array(
							'icon' => array( 'type' => 'icon', 'label' => __( 'Icon', 'ajs-builder' ), 'default' => 'check' ),
							'text' => array( 'type' => 'text', 'label' => __( 'Text', 'ajs-builder' ) ),
							'link' => array( 'type' => 'url', 'label' => __( 'Link', 'ajs-builder' ) ),
						),
						'default' => array(
							array( 'icon' => 'check', 'text' => __( 'First list item', 'ajs-builder' ), 'link' => '' ),
							array( 'icon' => 'check', 'text' => __( 'Second list item', 'ajs-builder' ), 'link' => '' ),
							array( 'icon' => 'check', 'text' => __( 'Third list item', 'ajs-builder' ), 'link' => '' ),
						),
					),
					'layout'     => array(
						'type'    => 'choose',
						'label'   => __( 'Layout', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'vertical',
						'choices' => array(
							'vertical'   => __( 'Vertical', 'ajs-builder' ),
							'horizontal' => __( 'Horizontal', 'ajs-builder' ),
						),
					),
					'columns'    => array(
						'type'    => 'select',
						'label'   => __( 'Columns', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '3',
						'choices' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
						'show_if' => array( 'layout' => 'horizontal' ),
					),
					'icon_color' => array(
						'type'       => 'color',
						'label'      => __( 'Icon colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 'var(--ajs-primary)',
						'responsive' => true,
					),
					'text_color' => $text_color,
					'typography' => $typography,
					'space'      => array(
						'type'       => 'number',
						'label'      => __( 'Spacing (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 12,
						'responsive' => true,
					),
					'align'      => $align,
				),
			),

			/* ---------------------------------------------------------------- Counter */
			'counter'        => array(
				'title'    => __( 'Counter', 'ajs-builder' ),
				'group'    => 'content',
				'controls' => array(
					'end'          => array(
						'type'    => 'number',
						'label'   => __( 'Ending number', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 250,
					),
					'start'        => array(
						'type'    => 'number',
						'label'   => __( 'Starting number', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 0,
					),
					'prefix'       => array(
						'type'  => 'text',
						'label' => __( 'Prefix', 'ajs-builder' ),
						'group' => 'Content',
					),
					'suffix'       => array(
						'type'    => 'text',
						'label'   => __( 'Suffix', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => '+',
					),
					'title'        => array(
						'type'    => 'text',
						'label'   => __( 'Title', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Happy customers', 'ajs-builder' ),
					),
					'align'        => $align,
					'number_color' => array(
						'type'       => 'color',
						'label'      => __( 'Number colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 'var(--ajs-ink)',
						'responsive' => true,
					),
					'title_color'   => $text_color,
					'icon'          => array(
						'type'  => 'icon',
						'label' => __( 'Icon', 'ajs-builder' ),
						'group' => 'Content',
					),
					'icon_position' => array(
						'type'    => 'choose',
						'label'   => __( 'Icon position', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'top',
						'choices' => array( 'top' => __( 'Above', 'ajs-builder' ), 'left' => __( 'Left', 'ajs-builder' ) ),
					),
					'duration'      => array(
						'type'    => 'number',
						'label'   => __( 'Animation duration (ms)', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 1100,
					),
					'icon_color'    => array(
						'type'       => 'color',
						'label'      => __( 'Icon colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 'var(--ajs-primary)',
						'responsive' => true,
					),
					'icon_size'     => array(
						'type'       => 'number',
						'label'      => __( 'Icon size (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 32,
						'responsive' => true,
					),
					'bg_color'      => array(
						'type'       => 'color',
						'label'      => __( 'Background', 'ajs-builder' ),
						'group'      => 'Style',
						'gradient'   => true,
						'responsive' => true,
					),
					'radius'        => array(
						'type'       => 'number',
						'label'      => __( 'Border radius (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 0,
						'responsive' => true,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Testimonial */
			'testimonial'    => array(
				'title'    => __( 'Testimonial', 'ajs-builder' ),
				'group'    => 'content',
				'controls' => array(
					'mode'        => array(
						'type'    => 'choose',
						'label'   => __( 'Display mode', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'single',
						'choices' => array( 'single' => __( 'Single', 'ajs-builder' ), 'slider' => __( 'Slider', 'ajs-builder' ) ),
					),
					'style'       => array(
						'type'    => 'choose',
						'label'   => __( 'Testimonial style', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'classic',
						'choices' => array(
							'classic' => __( 'Classic', 'ajs-builder' ),
							'card'    => __( 'Card + quote mark', 'ajs-builder' ),
						),
					),
					'title'       => array(
						'type'    => 'text',
						'label'   => __( 'Title', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => '',
						'show_if' => array( 'mode' => 'slider' ),
					),
					'subtitle'    => array(
						'type'    => 'textarea',
						'label'   => __( 'Subtitle', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => '',
						'rows'    => 2,
						'show_if' => array( 'mode' => 'slider' ),
					),
					'testimonials'  => array(
						'type'    => 'repeater',
						'label'   => __( 'Testimonials', 'ajs-builder' ),
						'group'   => 'Content',
						'fields'  => array(
							'content'  => array( 'type' => 'textarea', 'label' => __( 'Quote', 'ajs-builder' ), 'default' => __( 'A genuinely reliable partner for our workshop. The tools are tough and the support is excellent.', 'ajs-builder' ), 'rows' => 4 ),
							'name'     => array( 'type' => 'text', 'label' => __( 'Name', 'ajs-builder' ), 'default' => __( 'Jane Cooper', 'ajs-builder' ) ),
							'role'     => array( 'type' => 'text', 'label' => __( 'Role', 'ajs-builder' ), 'default' => __( 'Workshop Manager', 'ajs-builder' ) ),
							'avatar'   => array( 'type' => 'media', 'label' => __( 'Avatar', 'ajs-builder' ), 'default' => array( 'id' => 0, 'url' => '' ) ),
							'rating'   => array( 'type' => 'slider', 'label' => __( 'Rating', 'ajs-builder' ), 'default' => 5, 'min' => 0, 'max' => 5 ),
						),
						'default' => array(
							array( 'content' => __( 'A genuinely reliable partner for our workshop. The tools are tough and the support is excellent.', 'ajs-builder' ), 'name' => __( 'Jane Cooper', 'ajs-builder' ), 'role' => __( 'Workshop Manager', 'ajs-builder' ), 'avatar' => array( 'id' => 0, 'url' => '' ), 'rating' => 5 ),
							array( 'content' => __( 'Excellent service and quality products. Highly recommended!', 'ajs-builder' ), 'name' => __( 'John Smith', 'ajs-builder' ), 'role' => __( 'Business Owner', 'ajs-builder' ), 'avatar' => array( 'id' => 0, 'url' => '' ), 'rating' => 5 ),
						),
					),
					'slides_desktop' => array(
						'type'    => 'select',
						'label'   => __( 'Slides per view (desktop)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '1',
						'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
						'show_if' => array( 'mode' => 'slider' ),
					),
					'slides_tablet' => array(
						'type'    => 'select',
						'label'   => __( 'Slides per view (tablet)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '1',
						'choices' => array( '1' => '1', '2' => '2', '3' => '3' ),
						'show_if' => array( 'mode' => 'slider' ),
					),
					'slides_mobile' => array(
						'type'    => 'select',
						'label'   => __( 'Slides per view (mobile)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '1',
						'choices' => array( '1' => '1', '2' => '2' ),
						'show_if' => array( 'mode' => 'slider' ),
					),
					'gap'            => array(
						'type'       => 'number',
						'label'      => __( 'Gap (px)', 'ajs-builder' ),
						'group'      => 'Layout',
						'default'    => 24,
						'responsive' => true,
						'show_if'    => array( 'mode' => 'slider' ),
					),
					'autoplay'       => array(
						'type'       => 'switch',
						'label'      => __( 'Autoplay', 'ajs-builder' ),
						'group'      => 'Content',
						'default'    => true,
						'show_if'    => array( 'mode' => 'slider' ),
					),
					'speed'          => array(
						'type'       => 'number',
						'label'      => __( 'Autoplay speed (ms)', 'ajs-builder' ),
						'group'      => 'Content',
						'default'    => 5000,
						'min'        => 1000,
						'show_if'    => array( 'mode' => 'slider', 'autoplay' => true ),
					),
					'loop_slides'    => array(
						'type'       => 'switch',
						'label'      => __( 'Loop slides', 'ajs-builder' ),
						'group'      => 'Content',
						'default'    => true,
						'show_if'    => array( 'mode' => 'slider' ),
					),
					'pause_hover'    => array(
						'type'       => 'switch',
						'label'      => __( 'Pause on hover', 'ajs-builder' ),
						'group'      => 'Content',
						'default'    => true,
						'show_if'    => array( 'mode' => 'slider' ),
					),
					'show_arrows'    => array(
						'type'       => 'switch',
						'label'      => __( 'Show arrows', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => true,
						'show_if'    => array( 'mode' => 'slider' ),
					),
					'show_dots'      => array(
						'type'       => 'switch',
						'label'      => __( 'Show dots', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => true,
						'show_if'    => array( 'mode' => 'slider' ),
					),
					'align'       => $align,
					'quote_color' => array(
						'type'       => 'color',
						'label'      => __( 'Quote colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'name_color'  => array(
						'type'       => 'color',
						'label'      => __( 'Name colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'title_color' => array(
						'type'    => 'color',
						'label'   => __( 'Title colour', 'ajs-builder' ),
						'group'   => 'Style',
						'show_if' => array( 'mode' => 'slider' ),
					),
					'title_size'  => array(
						'type'    => 'number',
						'label'   => __( 'Title size (px)', 'ajs-builder' ),
						'group'   => 'Style',
						'min'     => 12,
						'max'     => 80,
						'show_if' => array( 'mode' => 'slider' ),
					),
					'subtitle_color' => array(
						'type'    => 'color',
						'label'   => __( 'Subtitle colour', 'ajs-builder' ),
						'group'   => 'Style',
						'show_if' => array( 'mode' => 'slider' ),
					),
					'subtitle_size' => array(
						'type'    => 'number',
						'label'   => __( 'Subtitle size (px)', 'ajs-builder' ),
						'group'   => 'Style',
						'min'     => 10,
						'max'     => 60,
						'show_if' => array( 'mode' => 'slider' ),
					),
					'card_bg'     => array(
						'type'     => 'color',
						'label'    => __( 'Card background', 'ajs-builder' ),
						'group'    => 'Style',
						'gradient' => true,
						'show_if'  => array( 'style' => 'card' ),
					),
					'typography'  => $typography,
				),
			),

			/* ---------------------------------------------------------------- CTA */
			'cta'            => array(
				'title'    => __( 'Call to Action', 'ajs-builder' ),
				'group'    => 'content',
				'controls' => array(
					'title'        => array(
						'type'    => 'text',
						'label'   => __( 'Title', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Ready to get started?', 'ajs-builder' ),
					),
					'text'         => array(
						'type'    => 'textarea',
						'label'   => __( 'Text', 'ajs-builder' ),
						'group'   => 'Content',
						'rows'    => 2,
						'default' => __( 'Tell visitors why they should take the next step.', 'ajs-builder' ),
					),
					'button_text'  => array(
						'type'    => 'text',
						'label'   => __( 'Button label', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Get in touch', 'ajs-builder' ),
					),
					'button_link'  => array(
						'type'    => 'url',
						'label'   => __( 'Button link', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => '#',
					),
					'align'        => $align,
					'bg_color'     => array(
						'type'     => 'color',
						'label'    => __( 'Background', 'ajs-builder' ),
						'group'    => 'Style',
						'gradient' => true,
						'default'  => 'var(--ajs-dark)',
					),
					'text_color'   => array(
						'type'    => 'color',
						'label'   => __( 'Text colour', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => '#ffffff',
					),
					'button_bg'    => array(
						'type'     => 'color',
						'label'    => __( 'Button background', 'ajs-builder' ),
						'group'    => 'Style',
						'gradient' => true,
						'default'  => 'var(--ajs-primary)',
					),
					'button_color' => array(
						'type'    => 'color',
						'label'   => __( 'Button text', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => '#ffffff',
					),
					'radius'       => array(
						'type'       => 'number',
						'label'      => __( 'Border radius (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 16,
						'responsive' => true,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Accordion */
			'accordion'      => array(
				'title'    => __( 'Accordion', 'ajs-builder' ),
				'group'    => 'content',
				'controls' => array(
					'items'        => array(
						'type'    => 'repeater',
						'label'   => __( 'Items', 'ajs-builder' ),
						'group'   => 'Content',
						'fields'  => array(
							'title'   => array( 'type' => 'text', 'label' => __( 'Title', 'ajs-builder' ) ),
							'content' => array( 'type' => 'textarea', 'label' => __( 'Content', 'ajs-builder' ), 'rows' => 3 ),
						),
						'default' => array(
							array( 'title' => __( 'What do you make?', 'ajs-builder' ), 'content' => __( 'We manufacture professional hand tools and workshop equipment.', 'ajs-builder' ) ),
							array( 'title' => __( 'Do you ship worldwide?', 'ajs-builder' ), 'content' => __( 'Yes, we ship to over 40 countries.', 'ajs-builder' ) ),
							array( 'title' => __( 'How do I get support?', 'ajs-builder' ), 'content' => __( 'Reach out through the contact page and our team will help.', 'ajs-builder' ) ),
						),
					),
					'open_first'   => array(
						'type'    => 'switch',
						'label'   => __( 'Open first item', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'active_color' => array(
						'type'       => 'color',
						'label'      => __( 'Active colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 'var(--ajs-primary)',
						'responsive' => true,
					),
					'border_color' => array(
						'type'       => 'color',
						'label'      => __( 'Border colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => '#e5e7eb',
						'responsive' => true,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Tabs */
			'tabs'           => array(
				'title'    => __( 'Tabs', 'ajs-builder' ),
				'group'    => 'content',
				'controls' => array(
					'items'        => array(
						'type'    => 'repeater',
						'label'   => __( 'Tabs', 'ajs-builder' ),
						'group'   => 'Content',
						'fields'  => array(
							'title'   => array( 'type' => 'text', 'label' => __( 'Title', 'ajs-builder' ) ),
							'content' => array( 'type' => 'textarea', 'label' => __( 'Content', 'ajs-builder' ), 'rows' => 3 ),
						),
						'default' => array(
							array( 'title' => __( 'Description', 'ajs-builder' ), 'content' => __( 'Explain what this product or service does.', 'ajs-builder' ) ),
							array( 'title' => __( 'Specifications', 'ajs-builder' ), 'content' => __( 'List the technical details here.', 'ajs-builder' ) ),
							array( 'title' => __( 'Reviews', 'ajs-builder' ), 'content' => __( 'Show customer reviews here.', 'ajs-builder' ) ),
						),
					),
					'align'        => array(
						'type'    => 'choose',
						'label'   => __( 'Tab alignment', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'left',
						'choices' => array( 'left' => __( 'Left', 'ajs-builder' ), 'center' => __( 'Center', 'ajs-builder' ), 'right' => __( 'Right', 'ajs-builder' ) ),
					),
					'active_color' => array(
						'type'       => 'color',
						'label'      => __( 'Active colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 'var(--ajs-primary)',
						'responsive' => true,
					),
					'border_color' => array(
						'type'       => 'color',
						'label'      => __( 'Border colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => '#e5e7eb',
						'responsive' => true,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Progress */
			'progress'       => array(
				'title'    => __( 'Progress Bar', 'ajs-builder' ),
				'group'    => 'content',
				'controls' => array(
					'title'        => array(
						'type'    => 'text',
						'label'   => __( 'Title', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Design', 'ajs-builder' ),
					),
					'percent'      => array(
						'type'    => 'slider',
						'label'   => __( 'Percentage', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 75,
						'min'     => 0,
						'max'     => 100,
					),
					'show_percent' => array(
						'type'    => 'switch',
						'label'   => __( 'Show percentage', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'bar_color'    => array(
						'type'     => 'color',
						'label'    => __( 'Bar colour', 'ajs-builder' ),
						'group'    => 'Style',
						'gradient' => true,
						'default'  => 'var(--ajs-primary)',
					),
					'track_color'  => array(
						'type'     => 'color',
						'label'    => __( 'Track colour', 'ajs-builder' ),
						'group'    => 'Style',
						'gradient' => true,
						'default'  => '#eef0f3',
					),
					'height'       => array(
						'type'    => 'number',
						'label'   => __( 'Height (px)', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 10,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Social */
			'social'         => array(
				'title'    => __( 'Social Icons', 'ajs-builder' ),
				'group'    => 'content',
				'groups'   => array( 'content', 'header' ),
				'controls' => array(
					'items'  => array(
						'type'    => 'repeater',
						'label'   => __( 'Icons', 'ajs-builder' ),
						'group'   => 'Content',
						'fields'  => array(
							'icon' => array( 'type' => 'icon', 'label' => __( 'Icon', 'ajs-builder' ), 'default' => 'globe' ),
							'link' => array( 'type' => 'url', 'label' => __( 'Link', 'ajs-builder' ) ),
						),
						'default' => array(
							array( 'icon' => 'globe', 'link' => '#' ),
							array( 'icon' => 'mail', 'link' => '#' ),
							array( 'icon' => 'phone', 'link' => '#' ),
						),
					),
					'align'  => $align,
					'shape'  => array(
						'type'    => 'choose',
						'label'   => __( 'Shape', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'circle',
						'choices' => array( 'none' => __( 'Plain', 'ajs-builder' ), 'circle' => __( 'Circle', 'ajs-builder' ), 'square' => __( 'Square', 'ajs-builder' ) ),
					),
					'color'  => array(
						'type'    => 'color',
						'label'   => __( 'Colour', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => '#ffffff',
					),
					'bg'     => array(
						'type'     => 'color',
						'label'    => __( 'Background', 'ajs-builder' ),
						'group'    => 'Style',
						'gradient' => true,
						'default'  => 'var(--ajs-primary)',
					),
					'size'   => array(
						'type'    => 'number',
						'label'   => __( 'Size (px)', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 40,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Gallery */
			'gallery'        => array(
				'title'    => __( 'Gallery', 'ajs-builder' ),
				'group'    => 'media',
				'controls' => array(
					'images'  => array(
						'type'    => 'repeater',
						'label'   => __( 'Images', 'ajs-builder' ),
						'group'   => 'Content',
						'fields'  => array(
							'image' => array( 'type' => 'media', 'label' => __( 'Image', 'ajs-builder' ), 'default' => array( 'id' => 0, 'url' => '' ) ),
						),
						'default' => array(
							array( 'image' => array( 'id' => 0, 'url' => '' ) ),
							array( 'image' => array( 'id' => 0, 'url' => '' ) ),
							array( 'image' => array( 'id' => 0, 'url' => '' ) ),
						),
					),
					'columns' => array(
						'type'    => 'select',
						'label'   => __( 'Columns', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => '3',
						'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
					),
					'gap'     => array(
						'type'    => 'number',
						'label'   => __( 'Gap (px)', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 12,
					),
					'radius'  => array(
						'type'    => 'number',
						'label'   => __( 'Border radius (px)', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 10,
					),
					'link'    => array(
						'type'    => 'select',
						'label'   => __( 'Click action', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'lightbox',
						'choices' => array( 'none' => __( 'None', 'ajs-builder' ), 'media' => __( 'Media file', 'ajs-builder' ), 'lightbox' => __( 'Lightbox', 'ajs-builder' ) ),
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Video */
			'video'          => array(
				'title'    => __( 'Video', 'ajs-builder' ),
				'group'    => 'media',
				'controls' => array(
					'url'      => array(
						'type'    => 'url',
						'label'   => __( 'Video URL', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => '',
					),
					'ratio'    => array(
						'type'    => 'select',
						'label'   => __( 'Aspect ratio', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => '16:9',
						'choices' => array( '16:9' => '16:9', '4:3' => '4:3', '1:1' => '1:1', '21:9' => '21:9' ),
					),
					'autoplay' => array(
						'type'    => 'switch',
						'label'   => __( 'Autoplay', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Products */
			'products'       => array(
				'title'    => __( 'Products', 'ajs-builder' ),
				'group'    => 'woo',
				'controls' => array(
					'columns'  => array(
						'type'    => 'select',
						'label'   => __( 'Columns', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => '4',
						'choices' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
					),
					'limit'    => array(
						'type'    => 'number',
						'label'   => __( 'Number of products', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 4,
					),
					'category' => array(
						'type'    => 'text',
						'label'   => __( 'Category slug (optional)', 'ajs-builder' ),
						'group'   => 'Content',
					),
					'orderby'  => array(
						'type'    => 'select',
						'label'   => __( 'Order by', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'date',
						'choices' => array( 'date' => __( 'Date', 'ajs-builder' ), 'title' => __( 'Title', 'ajs-builder' ), 'price' => __( 'Price', 'ajs-builder' ), 'rating' => __( 'Rating', 'ajs-builder' ), 'popularity' => __( 'Popularity', 'ajs-builder' ) ),
					),
					'order'    => array(
						'type'    => 'select',
						'label'   => __( 'Order', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'DESC',
						'choices' => array( 'DESC' => __( 'Descending', 'ajs-builder' ), 'ASC' => __( 'Ascending', 'ajs-builder' ) ),
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Products carousel */
			'products-carousel' => array(
				'title'    => __( 'Products Carousel', 'ajs-builder' ),
				'group'    => 'woo',
				'controls' => array(
					'layout'   => array(
						'type'    => 'choose',
						'label'   => __( 'Layout', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'grid',
						'choices' => array( 'grid' => __( 'Grid', 'ajs-builder' ), 'slider' => __( 'Slider', 'ajs-builder' ) ),
					),
					'columns'  => array(
						'type'    => 'select',
						'label'   => __( 'Columns', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => '4',
						'choices' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
					),
					'limit'    => array(
						'type'    => 'number',
						'label'   => __( 'Number of products', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 8,
					),
					'category' => array(
						'type'  => 'text',
						'label' => __( 'Category slug (optional)', 'ajs-builder' ),
						'group' => 'Content',
					),
					'orderby'  => array(
						'type'    => 'select',
						'label'   => __( 'Order by', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'date',
						'choices' => array( 'date' => __( 'Date', 'ajs-builder' ), 'title' => __( 'Title', 'ajs-builder' ), 'price' => __( 'Price', 'ajs-builder' ), 'rating' => __( 'Rating', 'ajs-builder' ), 'popularity' => __( 'Popularity', 'ajs-builder' ) ),
					),
					'order'    => array(
						'type'    => 'select',
						'label'   => __( 'Order', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'DESC',
						'choices' => array( 'DESC' => __( 'Descending', 'ajs-builder' ), 'ASC' => __( 'Ascending', 'ajs-builder' ) ),
					),
					'arrows'   => array(
						'type'    => 'switch',
						'label'   => __( 'Show arrows', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => true,
					),
					'autoplay' => array(
						'type'    => 'switch',
						'label'   => __( 'Autoplay (slider)', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'speed'    => array(
						'type'    => 'number',
						'label'   => __( 'Autoplay speed (ms)', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 4000,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Product category tabs */
			'product-tabs' => array(
				'title'    => __( 'Product Category Tabs', 'ajs-builder' ),
				'group'    => 'woo-tabs',
				'controls' => array(
					'mode'         => array(
						'type'    => 'choose',
						'label'   => __( 'Categories', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'auto',
						'choices' => array( 'auto' => __( 'Top categories', 'ajs-builder' ), 'manual' => __( 'Choose slugs', 'ajs-builder' ) ),
					),
					'categories'   => array(
						'type'  => 'text',
						'label' => __( 'Category slugs (comma separated)', 'ajs-builder' ),
						'group' => 'Content',
						'hint'  => __( 'Used when "Choose slugs" is selected, e.g. hand-tools,wrenches', 'ajs-builder' ),
					),
					'columns'      => array(
						'type'    => 'select',
						'label'   => __( 'Columns', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => '4',
						'choices' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
					),
					'limit'        => array(
						'type'    => 'number',
						'label'   => __( 'Products per tab', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 4,
					),
					'orderby'      => array(
						'type'    => 'select',
						'label'   => __( 'Order by', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'date',
						'choices' => array( 'date' => __( 'Date', 'ajs-builder' ), 'title' => __( 'Title', 'ajs-builder' ), 'price' => __( 'Price', 'ajs-builder' ), 'rating' => __( 'Rating', 'ajs-builder' ), 'popularity' => __( 'Popularity', 'ajs-builder' ) ),
					),
					'order'        => array(
						'type'    => 'select',
						'label'   => __( 'Order', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'DESC',
						'choices' => array( 'DESC' => __( 'Descending', 'ajs-builder' ), 'ASC' => __( 'Ascending', 'ajs-builder' ) ),
					),
					'active_color' => array(
						'type'       => 'color',
						'label'      => __( 'Active colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 'var(--ajs-primary)',
						'responsive' => true,
					),
					'border_color' => array(
						'type'       => 'color',
						'label'      => __( 'Border colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => '#e5e7eb',
						'responsive' => true,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Featured category */
			'featured-category' => array(
				'title'    => __( 'Featured Category', 'ajs-builder' ),
				'group'    => 'woo',
				'controls' => array(
					'source'         => array(
						'type'    => 'choose',
						'label'   => __( 'Categories', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'auto',
						'choices' => array( 'auto' => __( 'Top categories', 'ajs-builder' ), 'manual' => __( 'Choose slugs', 'ajs-builder' ) ),
					),
					'categories'     => array(
						'type'     => 'text',
						'label'    => __( 'Category slugs (comma separated)', 'ajs-builder' ),
						'group'    => 'Content',
						'hint'     => __( 'Used when "Choose slugs" is selected, e.g. hand-tools,wrenches', 'ajs-builder' ),
						'show_if'  => array( 'source' => 'manual' ),
					),
					'limit'          => array(
						'type'     => 'number',
						'label'    => __( 'Number of categories', 'ajs-builder' ),
						'group'    => 'Content',
						'default'  => 6,
						'min'      => 1,
						'max'      => 24,
						'show_if'  => array( 'source' => 'auto' ),
					),
					'orderby'        => array(
						'type'    => 'select',
						'label'   => __( 'Order by', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'count',
						'choices' => array(
							'name'     => __( 'Name', 'ajs-builder' ),
							'count'    => __( 'Product count', 'ajs-builder' ),
							'slug'     => __( 'Slug', 'ajs-builder' ),
							'term_id'  => __( 'ID', 'ajs-builder' ),
						),
						'show_if' => array( 'source' => 'auto' ),
					),
					'order'          => array(
						'type'    => 'select',
						'label'   => __( 'Order', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'DESC',
						'choices' => array( 'DESC' => __( 'Descending', 'ajs-builder' ), 'ASC' => __( 'Ascending', 'ajs-builder' ) ),
						'show_if' => array( 'source' => 'auto' ),
					),
					'show_image'     => array(
						'type'    => 'switch',
						'label'   => __( 'Show category image', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'show_count'     => array(
						'type'    => 'switch',
						'label'   => __( 'Show product count', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'show_description' => array(
						'type'    => 'switch',
						'label'   => __( 'Show description', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'description_length' => array(
						'type'     => 'number',
						'label'    => __( 'Description words', 'ajs-builder' ),
						'group'    => 'Content',
						'default'  => 18,
						'min'      => 3,
						'max'      => 60,
						'show_if'  => array( 'show_description' => true ),
					),
					'show_button'    => array(
						'type'    => 'switch',
						'label'   => __( 'Show link button', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'button_text'    => array(
						'type'    => 'text',
						'label'   => __( 'Button text', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Shop now', 'ajs-builder' ),
						'show_if' => array( 'show_button' => true ),
					),
					'mode'           => array(
						'type'    => 'choose',
						'label'   => __( 'Display mode', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'grid',
						'choices' => array( 'grid' => __( 'Grid', 'ajs-builder' ), 'slider' => __( 'Slider', 'ajs-builder' ) ),
					),
					'columns'        => array(
						'type'    => 'select',
						'label'   => __( 'Columns (desktop)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '4',
						'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
						'show_if' => array( 'mode' => 'grid' ),
					),
					'columns_tablet' => array(
						'type'    => 'select',
						'label'   => __( 'Columns (tablet)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '3',
						'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
						'show_if' => array( 'mode' => 'grid' ),
					),
					'columns_mobile' => array(
						'type'    => 'select',
						'label'   => __( 'Columns (mobile)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '2',
						'choices' => array( '1' => '1', '2' => '2', '3' => '3' ),
						'show_if' => array( 'mode' => 'grid' ),
					),
					'slides_desktop' => array(
						'type'    => 'select',
						'label'   => __( 'Slides per view (desktop)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '4',
						'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
						'show_if' => array( 'mode' => 'slider' ),
					),
					'slides_tablet'  => array(
						'type'    => 'select',
						'label'   => __( 'Slides per view (tablet)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '2',
						'choices' => array( '1' => '1', '2' => '2', '3' => '3' ),
						'show_if' => array( 'mode' => 'slider' ),
					),
					'slides_mobile'  => array(
						'type'    => 'select',
						'label'   => __( 'Slides per view (mobile)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '1',
						'choices' => array( '1' => '1', '2' => '2' ),
						'show_if' => array( 'mode' => 'slider' ),
					),
					'gap'            => array(
						'type'    => 'number',
						'label'   => __( 'Gap (px)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 24,
						'min'     => 0,
						'max'     => 96,
					),
					'image_height'   => array(
						'type'    => 'number',
						'label'   => __( 'Image height (px)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 200,
						'min'     => 60,
						'max'     => 600,
					),
					'autoplay'       => array(
						'type'    => 'switch',
						'label'   => __( 'Autoplay', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
						'show_if' => array( 'mode' => 'slider' ),
					),
					'speed'          => array(
						'type'    => 'number',
						'label'   => __( 'Autoplay speed (ms)', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 5000,
						'min'     => 1000,
						'show_if' => array( 'mode' => 'slider', 'autoplay' => true ),
					),
					'loop_slides'    => array(
						'type'    => 'switch',
						'label'   => __( 'Loop slides', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
						'show_if' => array( 'mode' => 'slider' ),
					),
					'pause_hover'    => array(
						'type'    => 'switch',
						'label'   => __( 'Pause on hover', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
						'show_if' => array( 'mode' => 'slider' ),
					),
					'show_arrows'    => array(
						'type'    => 'switch',
						'label'   => __( 'Show arrows', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => true,
						'show_if' => array( 'mode' => 'slider' ),
					),
					'show_dots'      => array(
						'type'    => 'switch',
						'label'   => __( 'Show dots', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => true,
						'show_if' => array( 'mode' => 'slider' ),
					),
					'title'          => array(
						'type'    => 'text',
						'label'   => __( 'Title', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => '',
						'show_if' => array( 'mode' => 'slider' ),
					),
					'subtitle'       => array(
						'type'    => 'textarea',
						'label'   => __( 'Subtitle', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => '',
						'rows'    => 2,
						'show_if' => array( 'mode' => 'slider' ),
					),
					'align'          => array(
						'type'    => 'choose',
						'label'   => __( 'Alignment', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'center',
						'choices' => array(
							'left'   => __( 'Left', 'ajs-builder' ),
							'center' => __( 'Center', 'ajs-builder' ),
							'right'  => __( 'Right', 'ajs-builder' ),
						),
					),
					'style'          => array(
						'type'    => 'choose',
						'label'   => __( 'Card style', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'card',
						'choices' => array(
							'classic' => __( 'Classic', 'ajs-builder' ),
							'card'    => __( 'Card', 'ajs-builder' ),
						),
					),
					'card_bg'        => array(
						'type'     => 'color',
						'label'    => __( 'Card background', 'ajs-builder' ),
						'group'    => 'Style',
						'gradient' => true,
						'default'  => '#ffffff',
						'show_if'  => array( 'style' => 'card' ),
					),
					'card_radius'    => array(
						'type'    => 'number',
						'label'   => __( 'Card radius (px)', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 16,
						'min'     => 0,
						'max'     => 60,
						'show_if' => array( 'style' => 'card' ),
					),
					'image_radius'   => array(
						'type'    => 'number',
						'label'   => __( 'Image radius (px)', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 12,
						'min'     => 0,
						'max'     => 60,
					),
					'name_color'     => array(
						'type'       => 'color',
						'label'      => __( 'Category name colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'count_color'    => array(
						'type'       => 'color',
						'label'      => __( 'Product count colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'description_color' => array(
						'type'       => 'color',
						'label'      => __( 'Description colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'button_bg'      => array(
						'type'    => 'color',
						'label'   => __( 'Button background', 'ajs-builder' ),
						'group'   => 'Style',
						'show_if' => array( 'show_button' => true ),
					),
					'button_color'   => array(
						'type'    => 'color',
						'label'   => __( 'Button text colour', 'ajs-builder' ),
						'group'   => 'Style',
						'show_if' => array( 'show_button' => true ),
					),
					'typography'     => $typography,
				),
			),

			/* ---------------------------------------------------------------- Logo */
			'logo'         => array(
				'title'    => __( 'Logo', 'ajs-builder' ),
				'group'    => 'layout',
				'controls' => array(
					'source'  => array(
						'type'    => 'choose',
						'label'   => __( 'Source', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'site',
						'choices' => array( 'site' => __( 'Site logo', 'ajs-builder' ), 'custom' => __( 'Custom image', 'ajs-builder' ) ),
					),
					'image'   => array(
						'type'    => 'media',
						'label'   => __( 'Image', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => array( 'id' => 0, 'url' => '' ),
						'hint'    => __( 'Used when "Custom image" is selected.', 'ajs-builder' ),
					),
					'width'   => array(
						'type'    => 'slider',
						'label'   => __( 'Logo width (px)', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 150,
						'min'     => 20,
						'max'     => 600,
						'step'    => 2,
					),
					'link'    => array(
						'type'  => 'url',
						'label' => __( 'Link', 'ajs-builder' ),
						'group' => 'Content',
						'hint'  => __( 'Leave empty to link to the home page.', 'ajs-builder' ),
					),
					'new_tab' => array(
						'type'    => 'switch',
						'label'   => __( 'Open in new tab', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'align'   => $align,
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Image slider */
			'slider'       => array(
				'title'    => __( 'Image Slider', 'ajs-builder' ),
				'group'    => 'media',
				'controls' => array(
					'images'          => array(
						'type'    => 'repeater',
						'label'   => __( 'Slides', 'ajs-builder' ),
						'group'   => 'Content',
						'fields'  => array(
							'image'        => array( 'type' => 'media', 'label' => __( 'Image', 'ajs-builder' ), 'default' => array( 'id' => 0, 'url' => '' ) ),
							'heading'      => array( 'type' => 'text', 'label' => __( 'Heading', 'ajs-builder' ), 'default' => '' ),
							'text'         => array( 'type' => 'textarea', 'label' => __( 'Description', 'ajs-builder' ), 'default' => '', 'rows' => 3 ),
							'btn_text'     => array( 'type' => 'text', 'label' => __( 'Button text', 'ajs-builder' ), 'default' => '' ),
							'btn_link'     => array( 'type' => 'url', 'label' => __( 'Button link', 'ajs-builder' ), 'default' => '' ),
							'btn_target'   => array( 'type' => 'switch', 'label' => __( 'Open button in new tab', 'ajs-builder' ), 'default' => false ),
							'link'         => array( 'type' => 'url', 'label' => __( 'Image link', 'ajs-builder' ), 'default' => '' ),
							'link_target'  => array( 'type' => 'switch', 'label' => __( 'Open image link in new tab', 'ajs-builder' ), 'default' => false ),
						),
						'default' => array(
							array( 'image' => array( 'id' => 0, 'url' => '' ), 'heading' => '', 'text' => '', 'btn_text' => '', 'btn_link' => '', 'btn_target' => false, 'link' => '', 'link_target' => false ),
							array( 'image' => array( 'id' => 0, 'url' => '' ), 'heading' => '', 'text' => '', 'btn_text' => '', 'btn_link' => '', 'btn_target' => false, 'link' => '', 'link_target' => false ),
							array( 'image' => array( 'id' => 0, 'url' => '' ), 'heading' => '', 'text' => '', 'btn_text' => '', 'btn_link' => '', 'btn_target' => false, 'link' => '', 'link_target' => false ),
						),
					),
					'width_mode'      => array(
						'type'    => 'choose',
						'label'   => __( 'Width', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'full',
						'choices' => array( 'full' => __( 'Full width', 'ajs-builder' ), 'fixed' => __( 'Fixed width', 'ajs-builder' ) ),
					),
					'fixed_width'     => array(
						'type'    => 'number',
						'label'   => __( 'Fixed width (px)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 1200,
						'show_if' => array( 'width_mode' => 'fixed' ),
					),
					'height'          => array(
						'type'       => 'number',
						'label'      => __( 'Height (px)', 'ajs-builder' ),
						'group'      => 'Layout',
						'default'    => 0,
						'min'        => 0,
						'hint'       => __( 'Set a fixed height. Use 0 to size the slider by the Aspect ratio instead.', 'ajs-builder' ),
						'responsive' => true,
					),
					'image_fit'       => array(
						'type'    => 'choose',
						'label'   => __( 'Image fit', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'cover',
						'choices' => array( 'cover' => __( 'Cover', 'ajs-builder' ), 'contain' => __( 'Contain', 'ajs-builder' ), 'fill' => __( 'Fill', 'ajs-builder' ) ),
					),
					'ratio'           => array(
						'type'    => 'select',
						'label'   => __( 'Aspect ratio', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => '16:9',
						'hint'    => __( 'Used only when Height is 0. A fixed Height always wins.', 'ajs-builder' ),
						'choices' => array( 'auto' => __( 'Auto (use height)', 'ajs-builder' ), '16:9' => '16:9', '4:3' => '4:3', '3:2' => '3:2', '1:1' => '1:1', '21:9' => '21:9' ),
					),
					'radius'          => array(
						'type'       => 'number',
						'label'      => __( 'Border radius (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 12,
						'responsive' => true,
					),
					'content_align'   => array(
						'type'    => 'choose',
						'label'   => __( 'Content alignment', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'center',
						'choices' => array( 'left' => __( 'Left', 'ajs-builder' ), 'center' => __( 'Center', 'ajs-builder' ), 'right' => __( 'Right', 'ajs-builder' ) ),
					),
					'content_position' => array(
						'type'    => 'select',
						'label'   => __( 'Content position', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'middle',
						'choices' => array(
							'top'          => __( 'Top', 'ajs-builder' ),
							'top-left'     => __( 'Top left', 'ajs-builder' ),
							'top-right'    => __( 'Top right', 'ajs-builder' ),
							'top-center'   => __( 'Top center', 'ajs-builder' ),
							'middle'       => __( 'Middle', 'ajs-builder' ),
							'middle-left'  => __( 'Middle left', 'ajs-builder' ),
							'middle-right' => __( 'Middle right', 'ajs-builder' ),
							'bottom'       => __( 'Bottom', 'ajs-builder' ),
							'bottom-left'  => __( 'Bottom left', 'ajs-builder' ),
							'bottom-right' => __( 'Bottom right', 'ajs-builder' ),
							'bottom-center' => __( 'Bottom center', 'ajs-builder' ),
						),
					),
					'content_width'    => array(
						'type'    => 'select',
						'label'   => __( 'Content width', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'full',
						'choices' => array(
							'full' => __( 'Full width', 'ajs-builder' ),
							'boxed' => __( 'Inside box', 'ajs-builder' ),
						),
					),
					'content_max_width' => array(
						'type'    => 'number',
						'label'   => __( 'Content max width (px)', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 640,
						'min'     => 180,
						'max'     => 1200,
						'show_if' => array( 'content_width' => 'boxed' ),
					),
					'overlay_color'   => array(
						'type'       => 'color',
						'label'      => __( 'Overlay colour', 'ajs-builder' ),
						'group'      => 'Style',
						'gradient'   => true,
						'responsive' => true,
					),
					'overlay_opacity' => array(
						'type'    => 'slider',
						'label'   => __( 'Overlay opacity (%)', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 0,
						'min'     => 0,
						'max'     => 100,
					),
					'heading_color'   => array(
						'type'       => 'color',
						'label'      => __( 'Heading colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'text_color'      => array(
						'type'       => 'color',
						'label'      => __( 'Description colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'margin'          => array(
						'type'       => 'box-model',
						'label'      => __( 'Margin', 'ajs-builder' ),
						'group'      => 'Spacing',
						'default'    => array( 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
						'unit'       => 'px',
						'responsive' => true,
						'sides'      => array( 'top', 'right', 'bottom', 'left' ),
					),
					'padding'         => array(
						'type'       => 'box-model',
						'label'      => __( 'Padding', 'ajs-builder' ),
						'group'      => 'Spacing',
						'default'    => array( 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
						'unit'       => 'px',
						'responsive' => true,
						'sides'      => array( 'top', 'right', 'bottom', 'left' ),
					),
					'button_bg'       => array(
						'type'       => 'color',
						'label'      => __( 'Button background', 'ajs-builder' ),
						'group'      => 'Style',
						'gradient'   => true,
						'responsive' => true,
					),
					'button_color'    => array(
						'type'       => 'color',
						'label'      => __( 'Button text colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'transition'      => array(
						'type'    => 'choose',
						'label'   => __( 'Transition', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'slide',
						'choices' => array( 'slide' => __( 'Slide', 'ajs-builder' ), 'fade' => __( 'Fade', 'ajs-builder' ) ),
					),
					'autoplay'        => array(
						'type'    => 'switch',
						'label'   => __( 'Autoplay', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'speed'           => array(
						'type'    => 'number',
						'label'   => __( 'Autoplay interval (ms)', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 4000,
						'min'     => 1000,
					),
					'loop'            => array(
						'type'    => 'switch',
						'label'   => __( 'Loop slides', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'pause_hover'     => array(
						'type'    => 'switch',
						'label'   => __( 'Pause on hover', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'arrows'          => array(
						'type'    => 'switch',
						'label'   => __( 'Show arrows', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'dots'            => array(
						'type'    => 'switch',
						'label'   => __( 'Show dots', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Row */
			'row'            => array(
				'title'    => __( 'Row', 'ajs-builder' ),
				'group'    => 'layout',
				'controls' => array(
					'columns_count' => array(
						'type'    => 'select',
						'label'   => __( 'Columns', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '2',
						'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
					),
					'gap'           => array(
						'type'    => 'number',
						'label'   => __( 'Columns gap (px)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 24,
					),
					'gap_tablet'    => array(
						'type'        => 'number',
						'label'       => __( 'Columns gap – tablet (px)', 'ajs-builder' ),
						'group'       => 'Layout',
						'device'      => 'tablet',
						'placeholder' => __( 'inherit', 'ajs-builder' ),
					),
					'gap_mobile'    => array(
						'type'        => 'number',
						'label'       => __( 'Columns gap – mobile (px)', 'ajs-builder' ),
						'group'       => 'Layout',
						'device'      => 'mobile',
						'placeholder' => __( 'inherit', 'ajs-builder' ),
					),
					'valign'        => array(
						'type'    => 'choose',
						'label'   => __( 'Vertical align', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'stretch',
						'choices' => array( 'top' => __( 'Top', 'ajs-builder' ), 'middle' => __( 'Middle', 'ajs-builder' ), 'bottom' => __( 'Bottom', 'ajs-builder' ), 'stretch' => __( 'Stretch', 'ajs-builder' ) ),
					),
					'valign_tablet' => array(
						'type'    => 'choose',
						'label'   => __( 'Vertical align – tablet', 'ajs-builder' ),
						'group'   => 'Layout',
						'device'  => 'tablet',
						'default' => '',
						'choices' => array( '' => __( 'Inherit', 'ajs-builder' ), 'top' => __( 'Top', 'ajs-builder' ), 'middle' => __( 'Middle', 'ajs-builder' ), 'bottom' => __( 'Bottom', 'ajs-builder' ), 'stretch' => __( 'Stretch', 'ajs-builder' ) ),
					),
					'valign_mobile' => array(
						'type'    => 'choose',
						'label'   => __( 'Vertical align – mobile', 'ajs-builder' ),
						'group'   => 'Layout',
						'device'  => 'mobile',
						'default' => '',
						'choices' => array( '' => __( 'Inherit', 'ajs-builder' ), 'top' => __( 'Top', 'ajs-builder' ), 'middle' => __( 'Middle', 'ajs-builder' ), 'bottom' => __( 'Bottom', 'ajs-builder' ), 'stretch' => __( 'Stretch', 'ajs-builder' ) ),
					),
					'justify'       => array(
						'type'    => 'choose',
						'label'   => __( 'Horizontal align', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'start',
						'choices' => array( 'start' => __( 'Start', 'ajs-builder' ), 'center' => __( 'Center', 'ajs-builder' ), 'end' => __( 'End', 'ajs-builder' ), 'between' => __( 'Space between', 'ajs-builder' ), 'around' => __( 'Space around', 'ajs-builder' ), 'evenly' => __( 'Space evenly', 'ajs-builder' ) ),
					),
					'wrap'          => array(
						'type'    => 'choose',
						'label'   => __( 'Wrapping', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'wrap',
						'choices' => array( 'wrap' => __( 'Wrap', 'ajs-builder' ), 'nowrap' => __( 'No wrap', 'ajs-builder' ) ),
					),
					'stack_tablet'  => array(
						'type'    => 'switch',
						'label'   => __( 'Stack columns on tablet', 'ajs-builder' ),
						'group'   => 'Layout',
						'device'  => 'tablet',
						'default' => false,
					),
					'stack_mobile'  => array(
						'type'    => 'switch',
						'label'   => __( 'Stack columns on mobile', 'ajs-builder' ),
						'group'   => 'Layout',
						'device'  => 'mobile',
						'default' => true,
					),
					'bg_color'      => array(
						'type'       => 'color',
						'label'      => __( 'Background colour', 'ajs-builder' ),
						'group'      => 'Style',
						'gradient'   => true,
					),
					'padding'       => array(
						'type'       => 'dimensions',
						'label'      => __( 'Padding', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => array( 'unit' => 'px', 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
					),
					'margin'        => array(
						'type'       => 'dimensions',
						'label'      => __( 'Margin', 'ajs-builder' ),
						'group'      => 'Advanced',
						'default'    => array( 'unit' => 'px', 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
					),
					'css_class'     => array(
						'type'  => 'text',
						'label' => __( 'CSS class', 'ajs-builder' ),
						'group' => 'Advanced',
					),
				),
			),

			/* ---------------------------------------------------------------- Navigation */
			'nav'            => array(
				'title'    => __( 'Navigation', 'ajs-builder' ),
				'group'    => 'header',
				'groups'   => array( 'header' ),
				'controls' => array(
					'menu'        => array(
						'type'    => 'select',
						'label'   => __( 'Menu', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => '',
						'choices' => self::menu_choices(),
						'hint'    => __( 'Use the theme Primary menu when empty.', 'ajs-builder' ),
					),
					'layout'      => array(
						'type'    => 'choose',
						'label'   => __( 'Layout', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'horizontal',
						'choices' => array( 'horizontal' => __( 'Horizontal', 'ajs-builder' ), 'vertical' => __( 'Vertical', 'ajs-builder' ) ),
					),
					'submenu'     => array(
						'type'    => 'choose',
						'label'   => __( 'Sub-menus', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'dropdown',
						'choices' => array( 'dropdown' => __( 'Dropdown', 'ajs-builder' ), 'inline' => __( 'Visible', 'ajs-builder' ) ),
					),
					'align'       => $align,
					'gap'         => array(
						'type'       => 'number',
						'label'      => __( 'Link spacing (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 26,
						'responsive' => true,
					),
					'color'       => $text_color,
					'hover_color' => array(
						'type'       => 'color',
						'label'      => __( 'Hover colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 'var(--ajs-primary)',
						'responsive' => true,
					),
					'typography'    => $typography,
				),
			),

			/* ---------------------------------------------------------------- Search */
			'search'         => array(
				'title'    => __( 'Search', 'ajs-builder' ),
				'group'    => 'header',
				'groups'   => array( 'header', 'layout' ),
				'controls' => array(
					'placeholder'   => array(
						'type'    => 'text',
						'label'   => __( 'Placeholder', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Search products…', 'ajs-builder' ),
					),
					'mode'          => array(
						'type'    => 'choose',
						'label'   => __( 'Style', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'inline',
						'choices' => array( 'inline' => __( 'Search box', 'ajs-builder' ), 'icon' => __( 'Icon button', 'ajs-builder' ) ),
					),
					'size'          => array(
						'type'    => 'choose',
						'label'   => __( 'Size', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'md',
						'choices' => array( 'sm' => __( 'S', 'ajs-builder' ), 'md' => __( 'M', 'ajs-builder' ), 'lg' => __( 'L', 'ajs-builder' ) ),
					),
					'products_only' => array(
						'type'    => 'switch',
						'label'   => __( 'Search products only', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'align'         => $align,
					'color'         => $text_color,
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Cart */
			'cart'           => array(
				'title'    => __( 'Cart', 'ajs-builder' ),
				'group'    => 'header',
				'groups'   => array( 'header', 'woo' ),
				'controls' => array(
					'layout'     => array(
						'type'    => 'choose',
						'label'   => __( 'Layout', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'icon',
						'choices' => array( 'icon' => __( 'Icon', 'ajs-builder' ), 'stacked' => __( 'Stacked', 'ajs-builder' ), 'inline' => __( 'Inline', 'ajs-builder' ) ),
					),
					'label'      => array(
						'type'    => 'text',
						'label'   => __( 'Label', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Cart', 'ajs-builder' ),
					),
					'show_count' => array(
						'type'    => 'switch',
						'label'   => __( 'Show item count', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'show_price' => array(
						'type'    => 'switch',
						'label'   => __( 'Show subtotal', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'align'      => $align,
					'color'      => $text_color,
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- My Account */
			'account'        => array(
				'title'    => __( 'My Account', 'ajs-builder' ),
				'group'    => 'header',
				'groups'   => array( 'header', 'woo' ),
				'controls' => array(
					'layout'         => array(
						'type'    => 'choose',
						'label'   => __( 'Layout', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'icon',
						'choices' => array( 'icon' => __( 'Icon', 'ajs-builder' ), 'inline' => __( 'Icon + label', 'ajs-builder' ) ),
					),
					'logged_in'      => array(
						'type'    => 'text',
						'label'   => __( 'Label (logged in)', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'My Account', 'ajs-builder' ),
					),
					'logged_out'     => array(
						'type'    => 'text',
						'label'   => __( 'Label (logged out)', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Login / Register', 'ajs-builder' ),
					),
					'align'          => $align,
					'color'          => $text_color,
					'hover_color'    => array(
						'type'    => 'color',
						'label'   => __( 'Hover colour', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'var(--ajs-primary)',
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Compare */
			'compare'        => array(
				'title'    => __( 'Compare', 'ajs-builder' ),
				'group'    => 'header',
				'groups'   => array( 'header', 'woo' ),
				'controls' => array(
					'link'        => array(
						'type'  => 'url',
						'label' => __( 'Compare page URL', 'ajs-builder' ),
						'group' => 'Content',
						'hint'  => __( 'Leave empty to auto-find a "compare" page.', 'ajs-builder' ),
					),
					'label'       => array(
						'type'    => 'text',
						'label'   => __( 'Label', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Compare', 'ajs-builder' ),
					),
					'layout'      => array(
						'type'    => 'choose',
						'label'   => __( 'Layout', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'icon',
						'choices' => array( 'icon' => __( 'Icon', 'ajs-builder' ), 'inline' => __( 'Icon + label', 'ajs-builder' ) ),
					),
					'show_count'  => array(
						'type'    => 'switch',
						'label'   => __( 'Show count badge', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'align'       => $align,
					'color'       => $text_color,
					'hover_color' => array(
						'type'       => 'color',
						'label'      => __( 'Hover colour', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 'var(--ajs-primary)',
						'responsive' => true,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Google Map */
			'google-map'     => array(
				'title'    => __( 'Google Map', 'ajs-builder' ),
				'group'    => 'media',
				'controls' => array(
					'address'   => array(
						'type'    => 'text',
						'label'   => __( 'Address or place', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'New Delhi, India', 'ajs-builder' ),
						'hint'    => __( 'An address, place name or coordinates (28.6139,77.2090). A full Google Maps link also works.', 'ajs-builder' ),
					),
					'title'     => array(
						'type'    => 'text',
						'label'   => __( 'Map title', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Our location', 'ajs-builder' ),
						'hint'    => __( 'Shown to screen readers and if the map cannot load.', 'ajs-builder' ),
					),
					'zoom'      => array(
						'type'    => 'slider',
						'label'   => __( 'Zoom', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 14,
						'min'     => 1,
						'max'     => 21,
					),
					'map_type'  => array(
						'type'    => 'select',
						'label'   => __( 'Map type', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'roadmap',
						'choices' => array(
							'roadmap'   => __( 'Roadmap', 'ajs-builder' ),
							'satellite' => __( 'Satellite', 'ajs-builder' ),
							'hybrid'    => __( 'Hybrid', 'ajs-builder' ),
							'terrain'   => __( 'Terrain', 'ajs-builder' ),
						),
					),
					'height'    => array(
						'type'    => 'number',
						'label'   => __( 'Height (px)', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 400,
					),
					'radius'    => array(
						'type'       => 'number',
						'label'      => __( 'Border radius (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 12,
						'responsive' => true,
					),
					'grayscale' => array(
						'type'    => 'switch',
						'label'   => __( 'Grayscale', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => false,
					),
				),
			),

			/* ---------------------------------------------------------------- Posts */
			'posts'          => array(
				'title'    => __( 'Posts', 'ajs-builder' ),
				'group'    => 'blog',
				'controls' => array(
					'layout'         => array(
						'type'    => 'choose',
						'label'   => __( 'Layout', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'grid',
						'choices' => array(
							'grid'    => __( 'Grid', 'ajs-builder' ),
							'list'    => __( 'List', 'ajs-builder' ),
							'overlay' => __( 'Overlay', 'ajs-builder' ),
							'minimal' => __( 'Minimal', 'ajs-builder' ),
						),
					),
					'columns'        => array(
						'type'    => 'select',
						'label'   => __( 'Columns', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '3',
						'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
						'show_if' => array( 'layout' => 'grid' ),
					),
					'source'         => array(
						'type'    => 'select',
						'label'   => __( 'Source', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'latest',
						'choices' => array(
							'latest'   => __( 'Latest posts', 'ajs-builder' ),
							'category' => __( 'By category', 'ajs-builder' ),
							'tag'      => __( 'By tag', 'ajs-builder' ),
							'ids'      => __( 'Choose posts', 'ajs-builder' ),
						),
					),
					'category'       => array(
						'type'    => 'text',
						'label'   => __( 'Category slugs', 'ajs-builder' ),
						'group'   => 'Content',
						'hint'    => __( 'Comma separated, e.g. news,tools', 'ajs-builder' ),
						'show_if' => array( 'source' => 'category' ),
					),
					'tag'            => array(
						'type'    => 'text',
						'label'   => __( 'Tag slugs', 'ajs-builder' ),
						'group'   => 'Content',
						'hint'    => __( 'Comma separated, e.g. sale,new', 'ajs-builder' ),
						'show_if' => array( 'source' => 'tag' ),
					),
					'ids'            => array(
						'type'    => 'text',
						'label'   => __( 'Post IDs', 'ajs-builder' ),
						'group'   => 'Content',
						'hint'    => __( 'Comma separated IDs, e.g. 12,34,56', 'ajs-builder' ),
						'show_if' => array( 'source' => 'ids' ),
					),
					'limit'          => array(
						'type'    => 'number',
						'label'   => __( 'Number of posts', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 6,
					),
					'orderby'        => array(
						'type'    => 'select',
						'label'   => __( 'Order by', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'date',
						'choices' => array(
							'date'          => __( 'Date', 'ajs-builder' ),
							'title'         => __( 'Title', 'ajs-builder' ),
							'menu_order'    => __( 'Menu order', 'ajs-builder' ),
							'comment_count' => __( 'Comments', 'ajs-builder' ),
							'rand'          => __( 'Random', 'ajs-builder' ),
						),
					),
					'order'          => array(
						'type'    => 'select',
						'label'   => __( 'Order', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'DESC',
						'choices' => array( 'DESC' => __( 'Descending', 'ajs-builder' ), 'ASC' => __( 'Ascending', 'ajs-builder' ) ),
					),
					'show_image'     => array(
						'type'    => 'switch',
						'label'   => __( 'Show featured image', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'image_ratio'    => array(
						'type'    => 'select',
						'label'   => __( 'Image ratio', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => '16:9',
						'choices' => array( '16:9' => '16:9', '4:3' => '4:3', '3:2' => '3:2', '1:1' => '1:1' ),
						'show_if' => array( 'show_image' => true ),
					),
					'show_category'  => array(
						'type'    => 'switch',
						'label'   => __( 'Show category', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'show_date'      => array(
						'type'    => 'switch',
						'label'   => __( 'Show date', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'show_author'    => array(
						'type'    => 'switch',
						'label'   => __( 'Show author', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'show_comments'  => array(
						'type'    => 'switch',
						'label'   => __( 'Show comments', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'show_excerpt'   => array(
						'type'    => 'switch',
						'label'   => __( 'Show excerpt', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'excerpt_length' => array(
						'type'    => 'number',
						'label'   => __( 'Excerpt length (words)', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 18,
						'show_if' => array( 'show_excerpt' => true ),
					),
					'readmore'       => array(
						'type'    => 'text',
						'label'   => __( 'Read more label', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Read more', 'ajs-builder' ),
					),
					'gap'            => array(
						'type'       => 'number',
						'label'      => __( 'Gap (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 24,
						'responsive' => true,
					),
					'radius'         => array(
						'type'       => 'number',
						'label'      => __( 'Border radius (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 12,
						'responsive' => true,
					),
					'align'          => $align,
					'card_bg'        => array(
						'type'       => 'color',
						'label'      => __( 'Card background', 'ajs-builder' ),
						'group'      => 'Style',
						'gradient'   => true,
						'responsive' => true,
					),
					'card_border'    => array(
						'type'    => 'switch',
						'label'   => __( 'Card border', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => false,
					),
					'title_color'    => array(
						'type'       => 'color',
						'label'      => __( 'Title colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'text_color'     => $text_color,
					'meta_color'     => array(
						'type'       => 'color',
						'label'      => __( 'Meta colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'typography'    => $typography,
				),
			),

			/* ---------------------------------------------------------------- Loop carousel */
			'loop-carousel'  => array(
				'title'    => __( 'Loop Carousel', 'ajs-builder' ),
				'group'    => 'blog',
				'controls' => array(
					'post_type'      => array(
						'type'    => 'select',
						'label'   => __( 'Post type', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'post',
						'choices' => self::post_type_choices(),
					),
					'source'         => array(
						'type'    => 'select',
						'label'   => __( 'Source', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'latest',
						'choices' => array(
							'latest'   => __( 'Latest items', 'ajs-builder' ),
							'category' => __( 'By category', 'ajs-builder' ),
							'tag'      => __( 'By tag', 'ajs-builder' ),
							'ids'      => __( 'Choose items', 'ajs-builder' ),
						),
					),
					'category'       => array(
						'type'    => 'text',
						'label'   => __( 'Category slugs', 'ajs-builder' ),
						'group'   => 'Content',
						'hint'    => __( 'Comma separated slugs.', 'ajs-builder' ),
						'show_if' => array( 'source' => 'category' ),
					),
					'tag'            => array(
						'type'    => 'text',
						'label'   => __( 'Tag slugs', 'ajs-builder' ),
						'group'   => 'Content',
						'hint'    => __( 'Comma separated slugs.', 'ajs-builder' ),
						'show_if' => array( 'source' => 'tag' ),
					),
					'ids'            => array(
						'type'    => 'text',
						'label'   => __( 'Item IDs', 'ajs-builder' ),
						'group'   => 'Content',
						'hint'    => __( 'Comma separated IDs.', 'ajs-builder' ),
						'show_if' => array( 'source' => 'ids' ),
					),
					'limit'          => array(
						'type'    => 'number',
						'label'   => __( 'Number of items', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 8,
					),
					'orderby'        => array(
						'type'    => 'select',
						'label'   => __( 'Order by', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'date',
						'choices' => array(
							'date'          => __( 'Date', 'ajs-builder' ),
							'title'         => __( 'Title', 'ajs-builder' ),
							'menu_order'    => __( 'Menu order', 'ajs-builder' ),
							'comment_count' => __( 'Comments', 'ajs-builder' ),
							'rand'          => __( 'Random', 'ajs-builder' ),
						),
					),
					'order'          => array(
						'type'    => 'select',
						'label'   => __( 'Order', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'DESC',
						'choices' => array( 'DESC' => __( 'Descending', 'ajs-builder' ), 'ASC' => __( 'Ascending', 'ajs-builder' ) ),
					),
					'slides'         => array(
						'type'    => 'select',
						'label'   => __( 'Slides (desktop)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '3',
						'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
					),
					'slides_tablet'  => array(
						'type'    => 'select',
						'label'   => __( 'Slides (tablet)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '2',
						'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
					),
					'slides_mobile'  => array(
						'type'    => 'select',
						'label'   => __( 'Slides (mobile)', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => '1',
						'choices' => array( '1' => '1', '2' => '2' ),
					),
					'gap'            => array(
						'type'       => 'number',
						'label'      => __( 'Gap (px)', 'ajs-builder' ),
						'group'      => 'Layout',
						'default'    => 20,
						'responsive' => true,
					),
					'autoplay'       => array(
						'type'    => 'switch',
						'label'   => __( 'Autoplay', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'speed'          => array(
						'type'    => 'number',
						'label'   => __( 'Autoplay speed (ms)', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 4000,
						'show_if' => array( 'autoplay' => true ),
					),
					'loop'           => array(
						'type'    => 'switch',
						'label'   => __( 'Infinite loop', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'pause'          => array(
						'type'    => 'switch',
						'label'   => __( 'Pause on hover', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'arrows'         => array(
						'type'    => 'switch',
						'label'   => __( 'Show arrows', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => true,
					),
					'dots'           => array(
						'type'    => 'switch',
						'label'   => __( 'Show dots', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => true,
					),
					'show_image'     => array(
						'type'    => 'switch',
						'label'   => __( 'Show featured image', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'image_ratio'    => array(
						'type'    => 'select',
						'label'   => __( 'Image ratio', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => '16:9',
						'choices' => array( '16:9' => '16:9', '4:3' => '4:3', '3:2' => '3:2', '1:1' => '1:1' ),
						'show_if' => array( 'show_image' => true ),
					),
					'show_category'  => array(
						'type'    => 'switch',
						'label'   => __( 'Show category', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'show_date'      => array(
						'type'    => 'switch',
						'label'   => __( 'Show date', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'show_author'    => array(
						'type'    => 'switch',
						'label'   => __( 'Show author', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'show_comments'  => array(
						'type'    => 'switch',
						'label'   => __( 'Show comments', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'show_excerpt'   => array(
						'type'    => 'switch',
						'label'   => __( 'Show excerpt', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => true,
					),
					'excerpt_length' => array(
						'type'    => 'number',
						'label'   => __( 'Excerpt length (words)', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 14,
						'show_if' => array( 'show_excerpt' => true ),
					),
					'readmore'       => array(
						'type'    => 'text',
						'label'   => __( 'Read more label', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Read more', 'ajs-builder' ),
					),
					'card_bg'        => array(
						'type'       => 'color',
						'label'      => __( 'Card background', 'ajs-builder' ),
						'group'      => 'Style',
						'gradient'   => true,
						'responsive' => true,
					),
					'radius'         => array(
						'type'       => 'number',
						'label'      => __( 'Border radius (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 12,
						'responsive' => true,
					),
					'title_color'    => array(
						'type'       => 'color',
						'label'      => __( 'Title colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'text_color'     => $text_color,
					'meta_color'     => array(
						'type'       => 'color',
						'label'      => __( 'Meta colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'align'          => $align,
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Author box */
			'author-box'     => array(
				'title'    => __( 'Author Box', 'ajs-builder' ),
				'group'    => 'blog',
				'controls' => array(
					'source'       => array(
						'type'    => 'choose',
						'label'   => __( 'Source', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'current',
						'choices' => array(
							'current' => __( 'Post author', 'ajs-builder' ),
							'manual'  => __( 'Custom', 'ajs-builder' ),
						),
					),
					'heading'      => array(
						'type'    => 'text',
						'label'   => __( 'Heading', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'About the author', 'ajs-builder' ),
					),
					'layout'       => array(
						'type'    => 'choose',
						'label'   => __( 'Layout', 'ajs-builder' ),
						'group'   => 'Layout',
						'default' => 'left',
						'choices' => array(
							'top'   => __( 'Avatar on top', 'ajs-builder' ),
							'left'  => __( 'Avatar left', 'ajs-builder' ),
							'right' => __( 'Avatar right', 'ajs-builder' ),
						),
					),
					'avatar_shape' => array(
						'type'    => 'choose',
						'label'   => __( 'Avatar shape', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 'circle',
						'choices' => array( 'circle' => __( 'Circle', 'ajs-builder' ), 'square' => __( 'Square', 'ajs-builder' ) ),
					),
					'avatar_size'  => array(
						'type'    => 'slider',
						'label'   => __( 'Avatar size (px)', 'ajs-builder' ),
						'group'   => 'Style',
						'default' => 96,
						'min'     => 40,
						'max'     => 200,
					),
					'name'         => array(
						'type'    => 'text',
						'label'   => __( 'Name', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Jane Cooper', 'ajs-builder' ),
						'show_if' => array( 'source' => 'manual' ),
					),
					'role'         => array(
						'type'    => 'text',
						'label'   => __( 'Role', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => __( 'Founder & CEO', 'ajs-builder' ),
						'show_if' => array( 'source' => 'manual' ),
					),
					'bio'          => array(
						'type'    => 'textarea',
						'label'   => __( 'Biography', 'ajs-builder' ),
						'group'   => 'Content',
						'rows'    => 3,
						'default' => __( 'Share a short biography to build trust with your readers.', 'ajs-builder' ),
						'show_if' => array( 'source' => 'manual' ),
					),
					'avatar'       => array(
						'type'    => 'media',
						'label'   => __( 'Avatar', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => array( 'id' => 0, 'url' => '' ),
						'show_if' => array( 'source' => 'manual' ),
					),
					'link'         => array(
						'type'    => 'url',
						'label'   => __( 'Profile link', 'ajs-builder' ),
						'group'   => 'Content',
						'show_if' => array( 'source' => 'manual' ),
					),
					'align'        => $align,
					'show_social'  => array(
						'type'    => 'switch',
						'label'   => __( 'Show social icons', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'social'       => array(
						'type'    => 'repeater',
						'label'   => __( 'Social links', 'ajs-builder' ),
						'group'   => 'Content',
						'fields'  => array(
							'icon' => array( 'type' => 'icon', 'label' => __( 'Icon', 'ajs-builder' ), 'default' => 'globe' ),
							'link' => array( 'type' => 'url', 'label' => __( 'Link', 'ajs-builder' ) ),
						),
						'default' => array(
							array( 'icon' => 'globe', 'link' => '#' ),
							array( 'icon' => 'mail', 'link' => '#' ),
						),
						'show_if' => array( 'show_social' => true ),
					),
					'bg_color'     => array(
						'type'       => 'color',
						'label'      => __( 'Background', 'ajs-builder' ),
						'group'      => 'Style',
						'gradient'   => true,
						'responsive' => true,
					),
					'name_color'   => array(
						'type'       => 'color',
						'label'      => __( 'Name colour', 'ajs-builder' ),
						'group'      => 'Style',
						'responsive' => true,
					),
					'text_color'   => $text_color,
					'radius'       => array(
						'type'       => 'number',
						'label'      => __( 'Border radius (px)', 'ajs-builder' ),
						'group'      => 'Style',
						'default'    => 16,
						'responsive' => true,
					),
					'typography'      => $typography,
				),
			),

			/* ================================================================
			   WordPress widgets (drag & drop any core widget)
			   ================================================================ */

			/* ---------------------------------------------------------------- Archives */
			'wp-archives'    => array(
				'title'    => __( 'Archives', 'ajs-builder' ),
				'group'    => 'wp',
				'controls' => array(
					'title'    => $wp_title,
					'dropdown' => array(
						'type'    => 'switch',
						'label'   => __( 'Display as dropdown', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'count'    => array(
						'type'    => 'switch',
						'label'   => __( 'Show post counts', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Calendar */
			'wp-calendar'    => array(
				'title'    => __( 'Calendar', 'ajs-builder' ),
				'group'    => 'wp',
				'controls' => array(
					'title' => $wp_title,
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Categories */
			'wp-categories'  => array(
				'title'    => __( 'Categories', 'ajs-builder' ),
				'group'    => 'wp',
				'controls' => array(
					'title'        => $wp_title,
					'dropdown'     => array(
						'type'    => 'switch',
						'label'   => __( 'Display as dropdown', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'count'        => array(
						'type'    => 'switch',
						'label'   => __( 'Show post counts', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'hierarchical' => array(
						'type'    => 'switch',
						'label'   => __( 'Show hierarchy', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Pages */
			'wp-pages'       => array(
				'title'    => __( 'Pages', 'ajs-builder' ),
				'group'    => 'wp',
				'controls' => array(
					'title'   => $wp_title,
					'sortby'  => array(
						'type'    => 'select',
						'label'   => __( 'Sort by', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'menu_order',
						'choices' => array(
							'menu_order'   => __( 'Page order', 'ajs-builder' ),
							'post_title'   => __( 'Page title', 'ajs-builder' ),
							'post_date'    => __( 'Page date', 'ajs-builder' ),
							'post_modified'=> __( 'Page last modified', 'ajs-builder' ),
							'ID'           => __( 'Page ID', 'ajs-builder' ),
							'post_author'  => __( 'Page author', 'ajs-builder' ),
						),
					),
					'exclude' => array(
						'type'        => 'text',
						'label'       => __( 'Exclude page IDs', 'ajs-builder' ),
						'group'       => 'Content',
						'placeholder' => '1,3,7',
						'hint'        => __( 'Comma separated list of page IDs to exclude.', 'ajs-builder' ),
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Meta */
			'wp-meta'        => array(
				'title'    => __( 'Meta', 'ajs-builder' ),
				'group'    => 'wp',
				'controls' => array(
					'title' => $wp_title,
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Recent posts */
			'wp-recent-posts' => array(
				'title'    => __( 'Recent Posts', 'ajs-builder' ),
				'group'    => 'wp',
				'controls' => array(
					'title'     => $wp_title,
					'number'    => array(
						'type'    => 'number',
						'label'   => __( 'Number of posts', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 5,
						'min'     => 1,
						'max'     => 20,
					),
					'show_date' => array(
						'type'    => 'switch',
						'label'   => __( 'Display post date', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Recent comments */
			'wp-recent-comments' => array(
				'title'    => __( 'Recent Comments', 'ajs-builder' ),
				'group'    => 'wp',
				'controls' => array(
					'title'  => $wp_title,
					'number' => array(
						'type'    => 'number',
						'label'   => __( 'Number of comments', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 5,
						'min'     => 1,
						'max'     => 20,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Tag cloud */
			'wp-tag-cloud'   => array(
				'title'    => __( 'Tag Cloud', 'ajs-builder' ),
				'group'    => 'wp',
				'controls' => array(
					'title'    => $wp_title,
					'taxonomy' => array(
						'type'    => 'select',
						'label'   => __( 'Taxonomy', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 'post_tag',
						'choices' => self::taxonomy_choices(),
					),
					'count'    => array(
						'type'    => 'switch',
						'label'   => __( 'Show tag counts', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- RSS */
			'wp-rss'         => array(
				'title'    => __( 'RSS', 'ajs-builder' ),
				'group'    => 'wp',
				'controls' => array(
					'title'        => $wp_title,
					'url'          => array(
						'type'        => 'url',
						'label'       => __( 'Feed URL', 'ajs-builder' ),
						'group'       => 'Content',
						'placeholder' => 'https://example.com/feed/',
					),
					'items'        => array(
						'type'    => 'number',
						'label'   => __( 'Number of items', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => 5,
						'min'     => 1,
						'max'     => 20,
					),
					'show_summary' => array(
						'type'    => 'switch',
						'label'   => __( 'Display item content', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'show_author'  => array(
						'type'    => 'switch',
						'label'   => __( 'Display item author', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'show_date'    => array(
						'type'    => 'switch',
						'label'   => __( 'Display item date', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => false,
					),
					'typography'      => $typography,
				),
			),

			/* ---------------------------------------------------------------- Contact Form 7 */
			'cf7'            => array(
				'title'    => __( 'Contact Form 7', 'ajs-builder' ),
				'group'    => 'forms',
				'controls' => array(
					'form'  => array(
						'type'    => 'select',
						'label'   => __( 'Form', 'ajs-builder' ),
						'group'   => 'Content',
						'default' => '',
						'choices' => self::cf7_form_choices(),
						'hint'    => __( 'Contact Form 7 must be installed and activated.', 'ajs-builder' ),
					),
					'title' => array(
						'type'        => 'text',
						'label'       => __( 'Title', 'ajs-builder' ),
						'group'       => 'Content',
						'placeholder' => __( 'optional', 'ajs-builder' ),
					),
					'align' => $align,
					'typography'      => $typography,
				),
			),
		);
	}

	/**
	 * Registered menus for the Navigation module, keyed by slug.
	 *
	 * @return array
	 */
	private static function menu_choices() {
		$choices = array( '' => __( 'Default (Primary)', 'ajs-builder' ) );
		$menus   = wp_get_nav_menus();
		if ( $menus && ! is_wp_error( $menus ) ) {
			foreach ( $menus as $menu ) {
				$choices[ $menu->slug ] = $menu->name;
			}
		}
		return $choices;
	}

	/**
	 * Public taxonomies for the Tag Cloud module, keyed by name.
	 *
	 * @return array
	 */
	private static function taxonomy_choices() {
		$choices = array();
		$taxes   = get_taxonomies( array( 'public' => true ), 'objects' );
		foreach ( $taxes as $tax ) {
			$choices[ $tax->name ] = $tax->labels->singular_name ? $tax->labels->singular_name : $tax->name;
		}
		if ( ! $choices ) {
			$choices['post_tag'] = __( 'Tags', 'ajs-builder' );
		}
		return $choices;
	}

	/**
	 * Available Contact Form 7 forms, keyed by post ID.
	 *
	 * @return array
	 */
	private static function cf7_form_choices() {
		$choices = array( '' => __( '— Select a form —', 'ajs-builder' ) );
		if ( ! post_type_exists( 'wpcf7_contact_form' ) ) {
			return $choices;
		}
		$forms = get_posts(
			array(
				'post_type'      => 'wpcf7_contact_form',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			)
		);
		foreach ( $forms as $form ) {
			$label = '' !== trim( (string) $form->post_title ) ? $form->post_title : sprintf( /* translators: %d: form ID. */ __( 'Form #%d', 'ajs-builder' ), $form->ID );
			$choices[ (string) $form->ID ] = $label;
		}
		return $choices;
	}

	/**
	 * Public post types for the Posts / Loop Carousel modules, keyed by name.
	 *
	 * @return array
	 */
	private static function post_type_choices() {
		$choices = array();
		$types   = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $types as $type ) {
			if ( 'attachment' === $type->name ) {
				continue;
			}
			$choices[ $type->name ] = $type->labels->singular_name;
		}
		if ( ! $choices ) {
			$choices['post'] = __( 'Post', 'ajs-builder' );
		}
		return $choices;
	}
}
