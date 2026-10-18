<?php
/**
 * Ready-made header & footer templates.
 *
 * Each preset is an ordinary builder document plus the settings a header or
 * footer template needs (type, sticky, display conditions). They are shipped
 * with the plugin so a site owner can import one (or all of them) from the
 * "Headers & Footers" screen and use it as a starting point for testing.
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Built-in demo header/footer library.
 */
class Ajs_Builder_Demo_Templates {

	/**
	 * Cached library.
	 *
	 * @var array|null
	 */
	private static $items = null;

	/**
	 * All demo templates, keyed by a stable id.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$items ) {
			$items = array_merge( self::headers(), self::footers() );

			/**
			 * Filter the built-in demo header/footer templates.
			 *
			 * @param array $items Demo templates keyed by id.
			 */
			self::$items = apply_filters( 'ajs_builder_demo_templates', $items );
		}
		return self::$items;
	}

	/**
	 * Get one demo template.
	 *
	 * @param string $id Demo id.
	 * @return array|null
	 */
	public static function get( $id ) {
		$all = self::all();
		return isset( $all[ $id ] ) ? $all[ $id ] : null;
	}

	/**
	 * Header demos.
	 *
	 * @return array
	 */
	public static function headers() {
		return array(
			'demo-header-classic'  => array(
				'id'          => 'demo-header-classic',
				'title'       => __( 'Classic Store Header (Demo)', 'ajs-builder' ),
				'description' => __( 'Dark announcement bar, logo, centred navigation and search, account, compare and cart actions.', 'ajs-builder' ),
				'type'        => 'header',
				'sticky'      => true,
				'classes'     => 'ajs-demo-header-classic',
				'conditions'  => array(
					'display' => 'entire_site',
					'ids'     => array(),
				),
				'document'    => self::classic_header_doc(),
			),
			'demo-header-centered' => array(
				'id'          => 'demo-header-centered',
				'title'       => __( 'Centered Logo Header (Demo)', 'ajs-builder' ),
				'description' => __( 'Utility strip, a large centred logo with search and actions, and a dark navigation bar underneath.', 'ajs-builder' ),
				'type'        => 'header',
				'sticky'      => true,
				'classes'     => 'ajs-demo-header-centered',
				'conditions'  => array(
					'display' => 'entire_site',
					'ids'     => array(),
				),
				'document'    => self::centered_header_doc(),
			),
			'demo-header-minimal'  => array(
				'id'          => 'demo-header-minimal',
				'title'       => __( 'Minimal Header (Demo)', 'ajs-builder' ),
				'description' => __( 'A single slim row: logo, navigation, search and cart. Great for stores that keep it simple.', 'ajs-builder' ),
				'type'        => 'header',
				'sticky'      => false,
				'classes'     => 'ajs-demo-header-minimal',
				'conditions'  => array(
					'display' => 'entire_site',
					'ids'     => array(),
				),
				'document'    => self::minimal_header_doc(),
			),
		);
	}

	/**
	 * Footer demos.
	 *
	 * @return array
	 */
	public static function footers() {
		return array(
			'demo-footer-store'      => array(
				'id'          => 'demo-footer-store',
				'title'       => __( 'Store Footer (Demo)', 'ajs-builder' ),
				'description' => __( 'Four dark columns with about text, social icons, link lists and contact details, plus a slim bottom bar.', 'ajs-builder' ),
				'type'        => 'footer',
				'sticky'      => false,
				'classes'     => 'ajs-demo-footer-store',
				'conditions'  => array(
					'display' => 'entire_site',
					'ids'     => array(),
				),
				'document'    => self::store_footer_doc(),
			),
			'demo-footer-newsletter' => array(
				'id'          => 'demo-footer-newsletter',
				'title'       => __( 'Newsletter Footer (Demo)', 'ajs-builder' ),
				'description' => __( 'A coloured newsletter call-to-action, four link columns and a legal bottom bar.', 'ajs-builder' ),
				'type'        => 'footer',
				'sticky'      => false,
				'classes'     => 'ajs-demo-footer-newsletter',
				'conditions'  => array(
					'display' => 'entire_site',
					'ids'     => array(),
				),
				'document'    => self::newsletter_footer_doc(),
			),
			'demo-footer-compact'    => array(
				'id'          => 'demo-footer-compact',
				'title'       => __( 'Compact Footer (Demo)', 'ajs-builder' ),
				'description' => __( 'One slim row with the logo, a footer menu and social icons, over a single copyright line.', 'ajs-builder' ),
				'type'        => 'footer',
				'sticky'      => false,
				'classes'     => 'ajs-demo-footer-compact',
				'conditions'  => array(
					'display' => 'entire_site',
					'ids'     => array(),
				),
				'document'    => self::compact_footer_doc(),
			),
		);
	}

	/* ------------------------------------------------------------------ Headers */

	/**
	 * Classic store header document.
	 *
	 * @return array
	 */
	private static function classic_header_doc() {
		$top = self::section(
			'hdc_top',
			array(
				self::col(
					'hdc_top_l',
					array(
						self::w(
							'hdc_top_txt',
							'text',
							array(
								'text'       => __( 'Free shipping on orders over ₹999 · Genuine tools with a 5-year warranty', 'ajs-builder' ),
								'align'      => 'left',
								'color'      => 'rgba(255,255,255,.85)',
								'typography' => array(
									'font_size'      => 13,
									'font_size_unit' => 'px',
								),
							)
						),
					),
					array( 'width' => 62 )
				),
				self::col(
					'hdc_top_r',
					array(
						self::w(
							'hdc_top_soc',
							'social',
							array(
								'items' => array(
									array( 'icon' => 'globe', 'link' => '#' ),
									array( 'icon' => 'mail', 'link' => '#' ),
									array( 'icon' => 'phone', 'link' => '#' ),
								),
								'align' => 'right',
								'shape' => 'none',
								'color' => '#ffffff',
								'size'  => 28,
							)
						),
					),
					array( 'width' => 38 )
				),
			),
			array(
				'content_width' => 'full',
				'bg_color'      => 'var(--ajs-dark)',
				'text_color'    => '#ffffff',
				'padding'       => self::box( 10, 0, 10, 0 ),
				'gap'           => 20,
				'valign'        => 'middle',
			)
		);

		$main = self::section(
			'hdc_main',
			array(
				self::col( 'hdc_brand', array( self::w( 'hdc_logo', 'logo', array( 'source' => 'site', 'width' => 170, 'align' => 'left' ) ) ), array( 'width' => 22, 'css_class' => 'ajs-keep-inline' ) ),
				self::col( 'hdc_nav', array( self::w( 'hdc_nav_w', 'nav', array( 'layout' => 'horizontal', 'submenu' => 'dropdown', 'align' => 'center', 'gap' => 26, 'color' => 'var(--ajs-ink)', 'hover_color' => 'var(--ajs-primary)' ) ) ), array( 'width' => 44 ) ),
				self::col( 'hdc_search', array( self::w( 'hdc_search_w', 'search', array( 'placeholder' => __( 'Search tools…', 'ajs-builder' ), 'mode' => 'icon', 'size' => 'md', 'products_only' => true, 'align' => 'right', 'color' => 'var(--ajs-ink)' ) ) ), array( 'width' => 12, 'css_class' => 'ajs-keep-inline' ) ),
				self::col( 'hdc_acc', array( self::w( 'hdc_acc_w', 'account', array( 'layout' => 'icon', 'align' => 'right', 'color' => 'var(--ajs-ink)', 'hover_color' => 'var(--ajs-primary)' ) ) ), array( 'width' => 8, 'css_class' => 'ajs-keep-inline' ) ),
				self::col( 'hdc_cmp', array( self::w( 'hdc_cmp_w', 'compare', array( 'layout' => 'icon', 'show_count' => true, 'align' => 'right', 'color' => 'var(--ajs-ink)', 'hover_color' => 'var(--ajs-primary)' ) ) ), array( 'width' => 8, 'css_class' => 'ajs-keep-inline' ) ),
				self::col( 'hdc_cart', array( self::w( 'hdc_cart_w', 'cart', array( 'layout' => 'icon', 'label' => __( 'Cart', 'ajs-builder' ), 'show_count' => true, 'align' => 'right', 'color' => 'var(--ajs-ink)' ) ) ), array( 'width' => 6, 'css_class' => 'ajs-keep-inline' ) ),
			),
			array(
				'content_width' => 'boxed',
				'bg_color'      => '#ffffff',
				'text_color'    => 'var(--ajs-ink)',
				'padding'       => self::box( 18, 0, 18, 0 ),
				'gap'           => 18,
				'valign'        => 'middle',
			)
		);

		return self::doc( array( $top, $main ) );
	}

	/**
	 * Centered logo header document.
	 *
	 * @return array
	 */
	private static function centered_header_doc() {
		$util = self::section(
			'hcn_util',
			array(
				self::col(
					'hcn_util_l',
					array(
						self::w(
							'hcn_util_txt',
							'text',
							array(
								'text'  => __( 'Free delivery over ₹999 · 30-day returns · Expert support', 'ajs-builder' ),
								'align' => 'left',
								'color' => 'var(--ajs-muted)',
							)
						),
					),
					array( 'width' => 52 )
				),
				self::col(
					'hcn_util_r',
					array(
						self::w(
							'hcn_util_list',
							'icon-list',
							array(
								'items'      => array(
									array( 'icon' => 'phone', 'text' => __( '1800 123 4567', 'ajs-builder' ), 'link' => 'tel:18001234567' ),
									array( 'icon' => 'clock', 'text' => __( 'Mon–Sat 9am–7pm', 'ajs-builder' ), 'link' => '' ),
									array( 'icon' => 'mail', 'text' => __( 'support@ajstools.in', 'ajs-builder' ), 'link' => 'mailto:support@ajstools.in' ),
								),
								'layout'     => 'horizontal',
								'columns'    => 3,
								'icon_color' => 'var(--ajs-primary)',
								'text_color' => 'var(--ajs-muted)',
								'space'      => 18,
								'align'      => 'right',
							)
						),
					),
					array( 'width' => 48 )
				),
			),
			array(
				'content_width' => 'full',
				'bg_color'      => 'var(--ajs-surface)',
				'text_color'    => 'var(--ajs-muted)',
				'padding'       => self::box( 9, 0, 9, 0 ),
				'gap'           => 20,
				'valign'        => 'middle',
			)
		);

		$brand = self::section(
			'hcn_brand',
			array(
				self::col( 'hcn_search', array( self::w( 'hcn_search_w', 'search', array( 'placeholder' => __( 'Search products…', 'ajs-builder' ), 'mode' => 'inline', 'size' => 'sm', 'products_only' => true, 'align' => 'left' ) ) ), array( 'width' => 24 ) ),
				self::col( 'hcn_logo', array( self::w( 'hcn_logo_w', 'logo', array( 'source' => 'site', 'width' => 190, 'align' => 'center' ) ) ), array( 'width' => 52 ) ),
				self::col( 'hcn_acc', array( self::w( 'hcn_acc_w', 'account', array( 'layout' => 'icon', 'align' => 'right', 'color' => 'var(--ajs-ink)', 'hover_color' => 'var(--ajs-primary)' ) ) ), array( 'width' => 8, 'css_class' => 'ajs-keep-inline' ) ),
				self::col( 'hcn_cmp', array( self::w( 'hcn_cmp_w', 'compare', array( 'layout' => 'icon', 'show_count' => true, 'align' => 'right', 'color' => 'var(--ajs-ink)', 'hover_color' => 'var(--ajs-primary)' ) ) ), array( 'width' => 8, 'css_class' => 'ajs-keep-inline' ) ),
				self::col( 'hcn_cart', array( self::w( 'hcn_cart_w', 'cart', array( 'layout' => 'icon', 'label' => __( 'Cart', 'ajs-builder' ), 'show_count' => true, 'align' => 'right', 'color' => 'var(--ajs-ink)' ) ) ), array( 'width' => 8, 'css_class' => 'ajs-keep-inline' ) ),
			),
			array(
				'content_width' => 'boxed',
				'bg_color'      => '#ffffff',
				'text_color'    => 'var(--ajs-ink)',
				'padding'       => self::box( 22, 0, 18, 0 ),
				'gap'           => 16,
				'valign'        => 'middle',
			)
		);

		$nav = self::section(
			'hcn_nav',
			array(
				self::col(
					'hcn_nav_c',
					array(
						self::w(
							'hcn_nav_w',
							'nav',
							array(
								'layout'      => 'horizontal',
								'submenu'     => 'dropdown',
								'align'       => 'center',
								'gap'         => 30,
								'color'       => '#ffffff',
								'hover_color' => 'var(--ajs-accent)',
							)
						),
					),
					array( 'width' => 100 )
				),
			),
			array(
				'content_width' => 'full',
				'bg_color'      => 'var(--ajs-ink)',
				'text_color'    => '#ffffff',
				'padding'       => self::box( 12, 0, 12, 0 ),
				'gap'           => 0,
				'valign'        => 'middle',
			)
		);

		return self::doc( array( $util, $brand, $nav ) );
	}

	/**
	 * Minimal header document.
	 *
	 * @return array
	 */
	private static function minimal_header_doc() {
		$main = self::section(
			'hmn_main',
			array(
				self::col( 'hmn_brand', array( self::w( 'hmn_logo', 'logo', array( 'source' => 'site', 'width' => 158, 'align' => 'left' ) ) ), array( 'width' => 26, 'css_class' => 'ajs-keep-inline' ) ),
				self::col( 'hmn_nav', array( self::w( 'hmn_nav_w', 'nav', array( 'layout' => 'horizontal', 'submenu' => 'dropdown', 'align' => 'center', 'gap' => 28, 'color' => 'var(--ajs-ink)', 'hover_color' => 'var(--ajs-primary)' ) ) ), array( 'width' => 44 ) ),
				self::col( 'hmn_search', array( self::w( 'hmn_search_w', 'search', array( 'placeholder' => __( 'Search…', 'ajs-builder' ), 'mode' => 'icon', 'size' => 'md', 'products_only' => true, 'align' => 'right', 'color' => 'var(--ajs-ink)' ) ) ), array( 'width' => 12, 'css_class' => 'ajs-keep-inline' ) ),
				self::col( 'hmn_acc', array( self::w( 'hmn_acc_w', 'account', array( 'layout' => 'icon', 'align' => 'right', 'color' => 'var(--ajs-ink)', 'hover_color' => 'var(--ajs-primary)' ) ) ), array( 'width' => 9, 'css_class' => 'ajs-keep-inline' ) ),
				self::col( 'hmn_cart', array( self::w( 'hmn_cart_w', 'cart', array( 'layout' => 'icon', 'label' => __( 'Cart', 'ajs-builder' ), 'show_count' => true, 'align' => 'right', 'color' => 'var(--ajs-ink)' ) ) ), array( 'width' => 9, 'css_class' => 'ajs-keep-inline' ) ),
			),
			array(
				'content_width' => 'boxed',
				'bg_color'      => '#ffffff',
				'text_color'    => 'var(--ajs-ink)',
				'padding'       => self::box( 20, 0, 20, 0 ),
				'gap'           => 18,
				'valign'        => 'middle',
			)
		);

		return self::doc( array( $main ) );
	}

	/* ------------------------------------------------------------------ Footers */

	/**
	 * Store footer document.
	 *
	 * @return array
	 */
	private static function store_footer_doc() {
		$main = self::section(
			'fst_main',
			array(
				self::col(
					'fst_about',
					array(
						self::w( 'fst_logo', 'logo', array( 'source' => 'site', 'width' => 150, 'align' => 'left' ) ),
						self::w(
							'fst_text',
							'text',
							array(
								'text'  => __( 'AJS Tools supplies professional-grade power tools, hand tools and safety equipment to workshops across India.', 'ajs-builder' ),
								'align' => 'left',
								'color' => '#c9cfd8',
							)
						),
						self::w(
							'fst_social',
							'social',
							array(
								'items' => array(
									array( 'icon' => 'globe', 'link' => '#' ),
									array( 'icon' => 'mail', 'link' => '#' ),
									array( 'icon' => 'phone', 'link' => '#' ),
									array( 'icon' => 'heart', 'link' => '#' ),
								),
								'align' => 'left',
								'shape' => 'circle',
								'color' => '#ffffff',
								'bg'    => 'var(--ajs-primary)',
								'size'  => 38,
							)
						),
					),
					array( 'width' => 28 )
				),
				self::col(
					'fst_shop',
					array(
						self::w( 'fst_shop_h', 'heading', array( 'text' => __( 'Shop', 'ajs-builder' ), 'tag' => 'h3', 'align' => 'left', 'color' => '#ffffff' ) ),
						self::w( 'fst_shop_l', 'icon-list', self::link_list( __( 'Power Tools', 'ajs-builder' ) ) ),
					),
					array( 'width' => 24 )
				),
				self::col(
					'fst_help',
					array(
						self::w( 'fst_help_h', 'heading', array( 'text' => __( 'Help', 'ajs-builder' ), 'tag' => 'h3', 'align' => 'left', 'color' => '#ffffff' ) ),
						self::w( 'fst_help_l', 'icon-list', self::link_list( __( 'Contact Us', 'ajs-builder' ) ) ),
					),
					array( 'width' => 24 )
				),
				self::col(
					'fst_contact',
					array(
						self::w( 'fst_contact_h', 'heading', array( 'text' => __( 'Contact', 'ajs-builder' ), 'tag' => 'h3', 'align' => 'left', 'color' => '#ffffff' ) ),
						self::w(
							'fst_contact_l',
							'icon-list',
							array(
								'items'      => array(
									array( 'icon' => 'map-pin', 'text' => __( 'Plot 12, Industrial Estate, Mumbai 400001', 'ajs-builder' ), 'link' => '' ),
									array( 'icon' => 'phone', 'text' => __( '+91 98765 43210', 'ajs-builder' ), 'link' => 'tel:+919876543210' ),
									array( 'icon' => 'mail', 'text' => __( 'support@ajstools.in', 'ajs-builder' ), 'link' => 'mailto:support@ajstools.in' ),
									array( 'icon' => 'clock', 'text' => __( 'Mon–Sat, 9am–7pm', 'ajs-builder' ), 'link' => '' ),
								),
								'layout'     => 'vertical',
								'icon_color' => 'var(--ajs-primary)',
								'text_color' => '#c9cfd8',
								'space'      => 12,
								'align'      => 'left',
							)
						),
					),
					array( 'width' => 24 )
				),
			),
			array(
				'content_width' => 'boxed',
				'bg_color'      => 'var(--ajs-dark)',
				'text_color'    => '#c9cfd8',
				'padding'       => self::box( 56, 0, 40, 0 ),
				'gap'           => 30,
				'valign'        => 'top',
			)
		);

		$bottom = self::section(
			'fst_bottom',
			array(
				self::col( 'fst_copy', array( self::w( 'fst_copy_t', 'text', array( 'text' => __( '© 2026 AJS Tools. All rights reserved.', 'ajs-builder' ), 'align' => 'left', 'color' => 'var(--ajs-muted)' ) ) ), array( 'width' => 55 ) ),
				self::col( 'fst_pay', array( self::w( 'fst_pay_t', 'text', array( 'text' => __( 'Secure payments · Visa · Mastercard · UPI · Net Banking', 'ajs-builder' ), 'align' => 'right', 'color' => 'var(--ajs-muted)' ) ) ), array( 'width' => 45 ) ),
			),
			array(
				'content_width' => 'boxed',
				'bg_color'      => '#0f1216',
				'text_color'    => 'var(--ajs-muted)',
				'padding'       => self::box( 18, 0, 18, 0 ),
				'gap'           => 16,
				'valign'        => 'middle',
			)
		);

		return self::doc( array( $main, $bottom ) );
	}

	/**
	 * Newsletter footer document.
	 *
	 * @return array
	 */
	private static function newsletter_footer_doc() {
		$news = self::section(
			'fnl_news',
			array(
				self::col(
					'fnl_news_t',
					array(
						self::w( 'fnl_news_h', 'heading', array( 'text' => __( 'Join the AJS newsletter', 'ajs-builder' ), 'tag' => 'h2', 'align' => 'left', 'color' => '#ffffff' ) ),
						self::w(
							'fnl_news_p',
							'text',
							array(
								'text'  => __( 'Sign up for tool tips, exclusive offers and first access to new arrivals. Get 10% off your first order.', 'ajs-builder' ),
								'align' => 'left',
								'color' => '#ffffff',
							)
						),
					),
					array( 'width' => 58 )
				),
				self::col(
					'fnl_news_b',
					array(
						self::w(
							'fnl_news_btn',
							'button',
							array(
								'text'          => __( 'Subscribe now', 'ajs-builder' ),
								'link'          => '#',
								'align'         => 'right',
								'size'          => 'lg',
								'variant'       => 'solid',
								'bg_color'      => '#ffffff',
								'text_color'    => 'var(--ajs-primary)',
								'radius'        => 10,
								'icon'          => 'mail',
								'icon_position' => 'left',
							)
						),
					),
					array( 'width' => 42 )
				),
			),
			array(
				'content_width' => 'boxed',
				'bg_color'      => 'var(--ajs-primary)',
				'text_color'    => '#ffffff',
				'padding'       => self::box( 44, 0, 40, 0 ),
				'gap'           => 24,
				'valign'        => 'middle',
			)
		);

		$cols = self::section(
			'fnl_cols',
			array(
				self::col(
					'fnl_brand',
					array(
						self::w( 'fnl_logo', 'logo', array( 'source' => 'site', 'width' => 150, 'align' => 'left' ) ),
						self::w(
							'fnl_text',
							'text',
							array(
								'text'  => __( 'Trusted by workshops, builders and makers across India since 1998.', 'ajs-builder' ),
								'align' => 'left',
								'color' => '#c9cfd8',
							)
						),
						self::w(
							'fnl_social',
							'social',
							array(
								'items' => array(
									array( 'icon' => 'globe', 'link' => '#' ),
									array( 'icon' => 'mail', 'link' => '#' ),
									array( 'icon' => 'phone', 'link' => '#' ),
								),
								'align' => 'left',
								'shape' => 'square',
								'color' => '#ffffff',
								'bg'    => 'var(--ajs-primary)',
								'size'  => 36,
							)
						),
					),
					array( 'width' => 28 )
				),
				self::col(
					'fnl_company',
					array(
						self::w( 'fnl_company_h', 'heading', array( 'text' => __( 'Company', 'ajs-builder' ), 'tag' => 'h3', 'align' => 'left', 'color' => '#ffffff' ) ),
						self::w( 'fnl_company_l', 'icon-list', self::link_list( __( 'About Us', 'ajs-builder' ) ) ),
					),
					array( 'width' => 24 )
				),
				self::col(
					'fnl_cats',
					array(
						self::w( 'fnl_cats_h', 'heading', array( 'text' => __( 'Categories', 'ajs-builder' ), 'tag' => 'h3', 'align' => 'left', 'color' => '#ffffff' ) ),
						self::w( 'fnl_cats_l', 'icon-list', self::link_list( __( 'Power Tools', 'ajs-builder' ) ) ),
					),
					array( 'width' => 24 )
				),
				self::col(
					'fnl_contact',
					array(
						self::w( 'fnl_contact_h', 'heading', array( 'text' => __( 'Get in touch', 'ajs-builder' ), 'tag' => 'h3', 'align' => 'left', 'color' => '#ffffff' ) ),
						self::w(
							'fnl_contact_l',
							'icon-list',
							array(
								'items'      => array(
									array( 'icon' => 'phone', 'text' => __( '+91 98765 43210', 'ajs-builder' ), 'link' => 'tel:+919876543210' ),
									array( 'icon' => 'mail', 'text' => __( 'support@ajstools.in', 'ajs-builder' ), 'link' => 'mailto:support@ajstools.in' ),
									array( 'icon' => 'map-pin', 'text' => __( 'Mumbai, India', 'ajs-builder' ), 'link' => '' ),
								),
								'layout'     => 'vertical',
								'icon_color' => 'var(--ajs-primary)',
								'text_color' => '#c9cfd8',
								'space'      => 12,
								'align'      => 'left',
							)
						),
					),
					array( 'width' => 24 )
				),
			),
			array(
				'content_width' => 'boxed',
				'bg_color'      => 'var(--ajs-dark)',
				'text_color'    => '#c9cfd8',
				'padding'       => self::box( 52, 0, 36, 0 ),
				'gap'           => 30,
				'valign'        => 'top',
			)
		);

		$bottom = self::section(
			'fnl_bottom',
			array(
				self::col( 'fnl_copy', array( self::w( 'fnl_copy_t', 'text', array( 'text' => __( '© 2026 AJS Tools. All rights reserved.', 'ajs-builder' ), 'align' => 'left', 'color' => 'var(--ajs-muted)' ) ) ), array( 'width' => 60 ) ),
				self::col(
					'fnl_legal',
					array(
						self::w(
							'fnl_legal_l',
							'icon-list',
							array(
								'items'      => array(
									array( 'icon' => 'arrow-right', 'text' => __( 'Privacy Policy', 'ajs-builder' ), 'link' => '#' ),
									array( 'icon' => 'arrow-right', 'text' => __( 'Terms', 'ajs-builder' ), 'link' => '#' ),
									array( 'icon' => 'arrow-right', 'text' => __( 'Sitemap', 'ajs-builder' ), 'link' => '#' ),
								),
								'layout'     => 'horizontal',
								'columns'    => 3,
								'icon_color' => 'var(--ajs-muted)',
								'text_color' => 'var(--ajs-muted)',
								'space'      => 18,
								'align'      => 'right',
							)
						),
					),
					array( 'width' => 40 )
				),
			),
			array(
				'content_width' => 'boxed',
				'bg_color'      => '#0f1216',
				'text_color'    => 'var(--ajs-muted)',
				'padding'       => self::box( 18, 0, 18, 0 ),
				'gap'           => 16,
				'valign'        => 'middle',
			)
		);

		return self::doc( array( $news, $cols, $bottom ) );
	}

	/**
	 * Compact footer document.
	 *
	 * @return array
	 */
	private static function compact_footer_doc() {
		$main = self::section(
			'fcm_main',
			array(
				self::col( 'fcm_brand', array( self::w( 'fcm_logo', 'logo', array( 'source' => 'site', 'width' => 150, 'align' => 'left' ) ) ), array( 'width' => 28 ) ),
				self::col( 'fcm_nav', array( self::w( 'fcm_nav_w', 'nav', array( 'layout' => 'horizontal', 'submenu' => 'dropdown', 'align' => 'center', 'gap' => 24, 'color' => '#c9cfd8', 'hover_color' => '#ffffff' ) ) ), array( 'width' => 44 ) ),
				self::col(
					'fcm_social',
					array(
						self::w(
							'fcm_social_w',
							'social',
							array(
								'items' => array(
									array( 'icon' => 'globe', 'link' => '#' ),
									array( 'icon' => 'mail', 'link' => '#' ),
									array( 'icon' => 'phone', 'link' => '#' ),
								),
								'align' => 'right',
								'shape' => 'circle',
								'color' => '#ffffff',
								'bg'    => 'var(--ajs-primary)',
								'size'  => 36,
							)
						),
					),
					array( 'width' => 28 )
				),
			),
			array(
				'content_width' => 'boxed',
				'bg_color'      => 'var(--ajs-dark)',
				'text_color'    => '#c9cfd8',
				'padding'       => self::box( 24, 0, 22, 0 ),
				'gap'           => 20,
				'valign'        => 'middle',
			)
		);

		$bottom = self::section(
			'fcm_bottom',
			array(
				self::col(
					'fcm_copy',
					array(
						self::w(
							'fcm_copy_t',
							'text',
							array(
								'text'  => __( '© 2026 AJS Tools · Built with the AJS Builder', 'ajs-builder' ),
								'align' => 'center',
								'color' => 'var(--ajs-muted)',
							)
						),
					),
					array( 'width' => 100 )
				),
			),
			array(
				'content_width' => 'boxed',
				'bg_color'      => '#0f1216',
				'text_color'    => 'var(--ajs-muted)',
				'padding'       => self::box( 14, 0, 14, 0 ),
				'gap'           => 0,
				'valign'        => 'middle',
			)
		);

		return self::doc( array( $main, $bottom ) );
	}

	/* -------------------------------------------------------------------- Helpers */

	/**
	 * A vertical link list (icon + text) used by the footer demos.
	 *
	 * @param string $first_label First link label (kept in English so editors can spot it).
	 * @return array
	 */
	private static function link_list( $first_label ) {
		return array(
			'items'      => array(
				array( 'icon' => 'arrow-right', 'text' => $first_label, 'link' => '#' ),
				array( 'icon' => 'arrow-right', 'text' => __( 'Hand Tools', 'ajs-builder' ), 'link' => '#' ),
				array( 'icon' => 'arrow-right', 'text' => __( 'Safety Gear', 'ajs-builder' ), 'link' => '#' ),
				array( 'icon' => 'arrow-right', 'text' => __( 'Accessories', 'ajs-builder' ), 'link' => '#' ),
			),
			'layout'     => 'vertical',
			'icon_color' => 'var(--ajs-primary)',
			'text_color' => '#c9cfd8',
			'space'      => 12,
			'align'      => 'left',
		);
	}

	/**
	 * Build a builder section array.
	 *
	 * @param string $id      Element id.
	 * @param array  $columns Columns.
	 * @param array  $settings Section settings.
	 * @return array
	 */
	private static function section( $id, $columns, $settings = array() ) {
		return array(
			'id'       => $id,
			'settings' => $settings,
			'columns'  => $columns,
		);
	}

	/**
	 * Build a builder column array.
	 *
	 * @param string $id       Element id.
	 * @param array  $widgets  Widgets.
	 * @param array  $settings Column settings.
	 * @return array
	 */
	private static function col( $id, $widgets, $settings = array() ) {
		return array(
			'id'       => $id,
			'settings' => $settings,
			'widgets'  => $widgets,
		);
	}

	/**
	 * Build a builder widget array.
	 *
	 * @param string $id       Element id.
	 * @param string $type     Widget type.
	 * @param array  $settings Widget settings.
	 * @return array
	 */
	private static function w( $id, $type, $settings = array() ) {
		return array(
			'id'       => $id,
			'type'     => $type,
			'settings' => $settings,
		);
	}

	/**
	 * A box (padding/margin) value.
	 *
	 * @param int    $top    Top.
	 * @param int    $right  Right.
	 * @param int    $bottom Bottom.
	 * @param int    $left   Left.
	 * @param string $unit   Unit.
	 * @return array
	 */
	private static function box( $top = 0, $right = 0, $bottom = 0, $left = 0, $unit = 'px' ) {
		return array(
			'unit'   => $unit,
			'top'    => $top,
			'right'  => $right,
			'bottom' => $bottom,
			'left'   => $left,
		);
	}

	/**
	 * Wrap sections in a document.
	 *
	 * @param array $sections Sections.
	 * @return array
	 */
	private static function doc( $sections ) {
		return array(
			'version'  => AJS_BUILDER_VERSION,
			'sections' => $sections,
		);
	}
}
