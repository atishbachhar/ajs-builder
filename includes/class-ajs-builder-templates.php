<?php
/**
 * Custom header & footer templates (AJS Builder).
 *
 * Lets users design header/footer templates with the builder and choose the
 * pages each one applies to. Templates are stored as a private `ajs_template`
 * post type; their content is the builder document, and the display rules live
 * in post meta.
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Header/footer template registry + front-end matching.
 */
class Ajs_Builder_Templates {

	/**
	 * Template post type.
	 */
	const POST_TYPE = 'ajs_template';

	/**
	 * Meta key: template type (header|footer).
	 */
	const META_TYPE = '_ajs_template_type';

	/**
	 * Meta key: display conditions.
	 */
	const META_COND = '_ajs_template_conditions';

	/**
	 * Meta key: header sticky (stays on top while scrolling).
	 */
	const META_STICKY = '_ajs_template_sticky';

	/**
	 * Meta key: extra CSS classes added to the template wrapper.
	 */
	const META_CLASSES = '_ajs_template_classes';

	/**
	 * Meta key: id of the built-in demo a template was imported from.
	 */
	const META_SOURCE = '_ajs_template_source';

	/**
	 * Meta key: whether the template is active (applies on the front end).
	 */
	const META_ENABLED = '_ajs_template_enabled';

	/**
	 * Per-request match cache.
	 *
	 * @var array
	 */
	private static $cache = array();

	/**
	 * Recursion guard while rendering a template.
	 *
	 * @var array
	 */
	private static $rendering = array();

	/**
	 * Guards the theme header/footer swap so each location is replaced once.
	 *
	 * @var array
	 */
	private static $overriding = array();

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'template_redirect', array( __CLASS__, 'block_public_view' ) );

		// Any theme: swap the theme header/footer for the matching builder
		// template by intercepting the standard get_header()/get_footer()
		// calls, exactly like Elementor Pro's theme support does.
		add_action( 'wp', array( __CLASS__, 'register_overrides' ) );

		// Block themes (FSE) don't call get_header(); they render a
		// template-part block for the header/footer area instead.
		add_filter( 'render_block_core/template-part', array( __CLASS__, 'filter_block_template_part' ), 10, 2 );
	}

	/**
	 * Hook the header/footer swap on the front end.
	 */
	public static function register_overrides() {
		/**
		 * Disable the automatic header/footer swap (e.g. when the active theme
		 * already calls ajs_builder_header_template()/ajs_builder_footer_template()).
		 *
		 * @param bool $auto Whether to replace the theme header/footer.
		 */
		if ( ! apply_filters( 'ajs_builder_auto_header_footer', true ) ) {
			return;
		}
		if ( is_admin() || is_feed() || is_embed() || wp_doing_ajax() ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		add_action( 'get_header', array( __CLASS__, 'override_header' ) );
		add_action( 'get_footer', array( __CLASS__, 'override_footer' ) );
	}

	/**
	 * Fallback for block themes: replace the header/footer template part.
	 *
	 * @param string $content Rendered block HTML.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public static function filter_block_template_part( $content, $block ) {
		if ( is_admin() || is_feed() || is_embed() || wp_doing_ajax() ) {
			return $content;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $content;
		}
		if ( ! apply_filters( 'ajs_builder_auto_header_footer', true ) ) {
			return $content;
		}

		$area = isset( $block['attrs']['area'] ) ? $block['attrs']['area'] : '';
		if ( '' === $area && isset( $block['attrs']['slug'] ) ) {
			$area = $block['attrs']['slug'];
		}

		if ( 'header' === $area || 'footer' === $area ) {
			$html = self::render( $area );
			if ( '' !== $html ) {
				return $html;
			}
		}

		return $content;
	}

	/**
	 * Replace the theme header with the matching builder header template.
	 *
	 * The theme's header.php normally prints the whole document head
	 * (<!DOCTYPE>, <head>, wp_head, <body>). We therefore print a fresh
	 * document head and the custom header, then pre-load the theme's
	 * header.php into a discarded buffer so that get_header()'s own
	 * (require_once) include becomes a no-op.
	 *
	 * @param string|null $name Header name.
	 */
	public static function override_header( $name ) {
		if ( ! empty( self::$overriding['header'] ) || ! self::find( 'header' ) ) {
			return;
		}
		self::$overriding['header'] = true;

		self::print_document_open();
		echo self::render( 'header' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// The theme header.php is about to be pre-loaded (and discarded);
		// its wp_head()/wp_body_open() must not run a second time.
		remove_all_actions( 'wp_head' );
		remove_all_actions( 'wp_body_open' );

		ob_start();
		locate_template( self::location_templates( 'header', $name ), true );
		ob_get_clean();
	}

	/**
	 * Replace the theme footer with the matching builder footer template.
	 *
	 * @param string|null $name Footer name.
	 */
	public static function override_footer( $name ) {
		if ( ! empty( self::$overriding['footer'] ) || ! self::find( 'footer' ) ) {
			return;
		}
		self::$overriding['footer'] = true;

		echo self::render( 'footer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_footer();
		echo "\n</body>\n</html>\n";

		// The theme footer.php is about to be pre-loaded (and discarded);
		// its wp_footer() must not run a second time.
		remove_all_actions( 'wp_footer' );

		ob_start();
		locate_template( self::location_templates( 'footer', $name ), true );
		ob_get_clean();
	}

	/**
	 * Candidate theme template names for a location.
	 *
	 * @param string      $location header|footer.
	 * @param string|null $name     Specific template name.
	 * @return array
	 */
	private static function location_templates( $location, $name ) {
		$templates = array();
		$name      = (string) $name;
		if ( '' !== $name ) {
			$templates[] = "{$location}-{$name}.php";
		}
		$templates[] = "{$location}.php";
		return $templates;
	}

	/**
	 * Print the document opening the theme header would normally provide.
	 */
	private static function print_document_open() {
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?>
	<title><?php echo wp_get_document_title(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></title>
	<?php endif; ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
		<?php
	}

	/**
	 * Register the (private) template post type.
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Templates', 'ajs-builder' ),
					'singular_name' => __( 'Template', 'ajs-builder' ),
					'add_new_item'  => __( 'Add New Template', 'ajs-builder' ),
					'edit_item'     => __( 'Edit Template', 'ajs-builder' ),
					'search_items'  => __( 'Search Templates', 'ajs-builder' ),
				),
				'public'              => false,
				'publicly_queryable'  => true,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'capability_type'     => 'page',
				'map_meta_cap'        => true,
				'menu_icon'           => 'dashicons-welcome-widgets-menus',
			)
		);
	}

	/**
	 * Templates may only be rendered via the builder preview, never standalone.
	 */
	public static function block_public_view() {
		if ( is_singular( self::POST_TYPE ) && ! Ajs_Builder_Frontend::is_preview() ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}
	}

	/**
	 * Template type.
	 *
	 * @param int $id Post ID.
	 * @return string header|footer
	 */
	public static function type( $id ) {
		$type = get_post_meta( $id, self::META_TYPE, true );
		return in_array( $type, array( 'header', 'footer', 'section' ), true ) ? $type : 'header';
	}

	/**
	 * Section (content block) templates, newest first.
	 *
	 * @return WP_Post[]
	 */
	public static function sections() {
		return get_posts(
			array(
				'post_type'        => self::POST_TYPE,
				'post_status'      => 'publish',
				'posts_per_page'   => 200,
				'orderby'          => 'ID',
				'order'            => 'DESC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'meta_key'         => self::META_TYPE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => 'section', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
	}

	/**
	 * Create a reusable section template from one or more sections.
	 *
	 * @param string $title    Template title.
	 * @param array  $sections Builder sections.
	 * @return int|WP_Error
	 */
	public static function create_section( $title, $sections ) {
		$sections = is_array( $sections ) ? $sections : array();
		$clean    = Ajs_Builder_Document::sanitize( array( 'sections' => $sections ) );

		return self::create(
			array(
				'title'    => $title,
				'type'     => 'section',
				'layout'   => 'canvas',
				'document' => $clean,
			)
		);
	}

	/**
	 * Whether a template is enabled (applies on the front end).
	 *
	 * Templates created before this option existed have no meta row and are
	 * treated as enabled so existing sites keep working unchanged.
	 *
	 * @param int $id Post ID.
	 * @return bool
	 */
	public static function is_enabled( $id ) {
		$value = get_post_meta( $id, self::META_ENABLED, true );
		if ( '' === $value ) {
			return true;
		}
		return (bool) $value;
	}

	/**
	 * Enable or disable a template.
	 *
	 * @param int  $id      Post ID.
	 * @param bool $enabled Whether it should apply.
	 */
	public static function set_enabled( $id, $enabled ) {
		update_post_meta( $id, self::META_ENABLED, $enabled ? '1' : '0' );
	}

	/**
	 * Display conditions for a template.
	 *
	 * @param int $id Post ID.
	 * @return array
	 */
	public static function conditions( $id ) {
		$c = get_post_meta( $id, self::META_COND, true );
		if ( ! is_array( $c ) ) {
			$c = array();
		}
		$c = wp_parse_args(
			$c,
			array(
				'display' => 'entire_site',
				'ids'     => array(),
			)
		);
		$c['ids'] = array_values( array_filter( array_map( 'absint', (array) $c['ids'] ) ) );
		if ( ! array_key_exists( $c['display'], self::displays() ) ) {
			$c['display'] = 'entire_site';
		}
		return $c;
	}

	/**
	 * Sanitize incoming conditions.
	 *
	 * @param mixed $c Raw conditions.
	 * @return array
	 */
	public static function sanitize_conditions( $c ) {
		$c       = is_array( $c ) ? $c : array();
		$display = isset( $c['display'] ) ? sanitize_key( $c['display'] ) : 'entire_site';
		if ( ! array_key_exists( $display, self::displays() ) ) {
			$display = 'entire_site';
		}
		$ids = array();
		if ( isset( $c['ids'] ) && is_array( $c['ids'] ) ) {
			foreach ( $c['ids'] as $id ) {
				$id = absint( $id );
				if ( $id ) {
					$ids[] = $id;
				}
			}
		}
		return array(
			'display' => $display,
			'ids'     => array_values( array_unique( $ids ) ),
		);
	}

	/**
	 * Available "display on" scopes.
	 *
	 * @return array
	 */
	public static function displays() {
		$d = array(
			'entire_site' => __( 'Entire website', 'ajs-builder' ),
			'front_page'  => __( 'Front page', 'ajs-builder' ),
			'pages'       => __( 'All pages', 'ajs-builder' ),
			'posts'       => __( 'All posts', 'ajs-builder' ),
			'archives'    => __( 'All archives', 'ajs-builder' ),
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$d['shop']    = __( 'Shop & product archives', 'ajs-builder' );
			$d['product'] = __( 'Single product pages', 'ajs-builder' );
		}

		$d['specific'] = __( 'Specific pages…', 'ajs-builder' );

		return $d;
	}

	/**
	 * Specificity score for a display scope (higher wins).
	 *
	 * @param string $display Display key.
	 * @return int
	 */
	private static function specificity( $display ) {
		$map = array(
			'specific'    => 60,
			'front_page'  => 50,
			'product'     => 45,
			'shop'        => 40,
			'pages'       => 30,
			'posts'       => 30,
			'archives'    => 20,
			'entire_site' => 10,
		);
		return isset( $map[ $display ] ) ? $map[ $display ] : 0;
	}

	/**
	 * Does a condition set match the current request?
	 *
	 * @param array $cond Conditions.
	 * @return bool
	 */
	public static function matches( $cond ) {
		$display = isset( $cond['display'] ) ? $cond['display'] : 'entire_site';
		$ids     = isset( $cond['ids'] ) ? array_map( 'absint', (array) $cond['ids'] ) : array();

		switch ( $display ) {
			case 'entire_site':
				return true;
			case 'front_page':
				return is_front_page();
			case 'pages':
				return is_page();
			case 'posts':
				return is_single() && 'post' === get_post_type();
			case 'product':
				return function_exists( 'is_product' ) && is_product();
			case 'shop':
				return ( function_exists( 'is_shop' ) && is_shop() )
					|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() );
			case 'archives':
				return ( is_archive() || is_home() )
					&& ! ( function_exists( 'is_shop' ) && is_shop() );
			case 'specific':
				return is_singular() && in_array( (int) get_queried_object_id(), $ids, true );
		}
		return false;
	}

	/**
	 * Find the best matching template for the current request.
	 *
	 * @param string $type header|footer.
	 * @return int Template ID or 0.
	 */
	public static function find( $type ) {
		$type = ( 'footer' === $type ) ? 'footer' : 'header';

		if ( is_singular( self::POST_TYPE ) ) {
			return 0;
		}
		if ( isset( self::$cache[ $type ] ) ) {
			return self::$cache[ $type ];
		}

		$ids = get_posts(
			array(
				'post_type'        => self::POST_TYPE,
				'post_status'      => 'publish',
				'posts_per_page'   => 100,
				'fields'           => 'ids',
				'orderby'          => 'ID',
				'order'            => 'DESC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'meta_key'         => self::META_TYPE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => $type, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		$best       = 0;
		$best_score = -1;
		foreach ( $ids as $id ) {
			if ( ! self::is_enabled( $id ) ) {
				continue;
			}
			if ( ! self::matches( self::conditions( $id ) ) ) {
				continue;
			}
			// A template with no content has no effect: skip it so it neither
			// blanks the site nor shadows a broader template that does render.
			if ( ! self::document_has_content( $id ) ) {
				continue;
			}
			$score = self::specificity( self::conditions( $id )['display'] );
			if ( $score > $best_score ) {
				$best_score = $score;
				$best       = (int) $id;
			}
		}

		self::$cache[ $type ] = $best;
		return $best;
	}

	/**
	 * Whether a template has builder content to render.
	 *
	 * @param int $id Template ID.
	 * @return bool
	 */
	private static function document_has_content( $id ) {
		$doc = Ajs_Builder_Document::get( $id );
		return ! empty( $doc['sections'] );
	}

	/**
	 * Render the matching template for a location.
	 *
	 * @param string $type header|footer.
	 * @return string
	 */
	public static function render( $type ) {
		$type = ( 'footer' === $type ) ? 'footer' : 'header';

		if ( ! empty( self::$rendering[ $type ] ) ) {
			return '';
		}

		$id = self::find( $type );
		if ( ! $id ) {
			return '';
		}

		self::$rendering[ $type ] = true;
		$html = Ajs_Builder_Render::document( Ajs_Builder_Document::get( $id ) );
		self::$rendering[ $type ] = false;

		if ( '' === trim( (string) $html ) ) {
			return '';
		}

		$tag = ( 'footer' === $type ) ? 'footer' : 'header';
		$cls = ( 'footer' === $type ) ? 'ajs-custom-footer' : 'ajs-custom-header';

		$custom = self::classes( $id );
		if ( '' !== $custom ) {
			$cls .= ' ' . $custom;
		}

		$attrs = ' id="ajs-custom-' . $type . '"'
			. ' class="ajs-custom-template ' . esc_attr( $cls ) . '"'
			. ' data-ajs-template="' . esc_attr( $id ) . '"';

		if ( 'header' === $type && self::sticky( $id ) ) {
			$attrs .= ' data-ajs-sticky="1"';
		}

		return '<' . $tag . $attrs . '>'
			. $html
			. '</' . $tag . '>';
	}

	/**
	 * Whether the header template sticks to the top while scrolling.
	 *
	 * @param int $id Template ID.
	 * @return bool
	 */
	public static function sticky( $id ) {
		return (bool) get_post_meta( $id, self::META_STICKY, true );
	}

	/**
	 * Extra CSS classes for a template wrapper (sanitized, space separated).
	 *
	 * @param int $id Template ID.
	 * @return string
	 */
	public static function classes( $id ) {
		return self::sanitize_classes( get_post_meta( $id, self::META_CLASSES, true ) );
	}

	/**
	 * Sanitize a list of CSS class names.
	 *
	 * @param mixed $c Raw class list.
	 * @return string
	 */
	public static function sanitize_classes( $c ) {
		$c = preg_split( '/\s+/', (string) $c, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! $c ) {
			return '';
		}
		$out = array();
		foreach ( $c as $class ) {
			$class = sanitize_html_class( $class );
			if ( '' !== $class ) {
				$out[] = $class;
			}
		}
		return implode( ' ', array_unique( $out ) );
	}

	/**
	 * Whether a custom template renders on the current request.
	 *
	 * @param string $type header|footer.
	 * @return bool
	 */
	public static function is_active( $type ) {
		return (bool) self::find( $type );
	}

	/**
	 * Human description of a template's display rules.
	 *
	 * @param int $id Template ID.
	 * @return string
	 */
	public static function describe( $id ) {
		$cond    = self::conditions( $id );
		$labels  = self::displays();
		$out     = isset( $labels[ $cond['display'] ] ) ? $labels[ $cond['display'] ] : $cond['display'];

		if ( 'specific' === $cond['display'] && $cond['ids'] ) {
			$titles = array();
			foreach ( array_slice( $cond['ids'], 0, 3 ) as $pid ) {
				$titles[] = get_the_title( $pid );
			}
			$extra = count( $cond['ids'] ) > 3 ? ' +' . ( count( $cond['ids'] ) - 3 ) : '';
			$out  .= ' (' . implode( ', ', array_filter( $titles ) ) . $extra . ')';
		}

		return $out;
	}

	/**
	 * Selectable items for the "specific pages" picker, grouped.
	 *
	 * @return array
	 */
	public static function choices() {
		$groups = array();

		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'posts_per_page' => 300,
				'post_status'    => array( 'publish', 'private' ),
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
		if ( $pages ) {
			$groups[ __( 'Pages', 'ajs-builder' ) ] = $pages;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => 300,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
		if ( $posts ) {
			$groups[ __( 'Posts', 'ajs-builder' ) ] = $posts;
		}

		if ( post_type_exists( 'product' ) ) {
			$products = get_posts(
				array(
					'post_type'      => 'product',
					'posts_per_page' => 300,
					'post_status'    => 'publish',
					'orderby'        => 'title',
					'order'          => 'ASC',
					'no_found_rows'  => true,
				)
			);
			if ( $products ) {
				$groups[ __( 'Products', 'ajs-builder' ) ] = $products;
			}
		}

		return $groups;
	}

	/**
	 * Create a header/footer template from structured data.
	 *
	 * Used by the demo importer and the JSON importer. Returns the new post id
	 * or a WP_Error when the post could not be created.
	 *
	 * @param array $args {
	 *     Template arguments.
	 *
	 *     @type string $title      Template title.
	 *     @type string $type       header|footer.
	 *     @type array  $conditions Display conditions.
	 *     @type bool   $sticky     Sticky header.
	 *     @type string $classes    Extra CSS classes.
	 *     @type string $layout     default|canvas.
	 *     @type array  $document   Builder document.
	 *     @type string $source     Demo id this came from (optional).
	 * }
	 * @return int|WP_Error
	 */
	public static function create( $args ) {
		$args = wp_parse_args(
			is_array( $args ) ? $args : array(),
			array(
				'title'      => '',
				'type'       => 'header',
				'conditions' => array(),
				'sticky'     => false,
				'classes'    => '',
				'layout'     => 'canvas',
				'document'   => array(),
				'source'     => '',
				'enabled'    => true,
			)
		);

		$type  = in_array( $args['type'], array( 'header', 'footer', 'section' ), true ) ? $args['type'] : 'header';
		$title = sanitize_text_field( (string) $args['title'] );
		if ( '' === $title ) {
			if ( 'footer' === $type ) {
				$title = __( 'Footer template', 'ajs-builder' );
			} elseif ( 'section' === $type ) {
				$title = __( 'Section template', 'ajs-builder' );
			} else {
				$title = __( 'Header template', 'ajs-builder' );
			}
		}

		$id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		update_post_meta( $id, self::META_TYPE, $type );
		update_post_meta( $id, self::META_COND, self::sanitize_conditions( $args['conditions'] ) );
		update_post_meta( $id, self::META_STICKY, ! empty( $args['sticky'] ) ? 1 : 0 );
		update_post_meta( $id, self::META_CLASSES, self::sanitize_classes( $args['classes'] ) );
		update_post_meta( $id, self::META_ENABLED, ! empty( $args['enabled'] ) ? '1' : '0' );
		update_post_meta( $id, '_ajs_builder_layout', ( 'canvas' === $args['layout'] ) ? 'canvas' : 'default' );

		$source = sanitize_key( (string) $args['source'] );
		if ( '' !== $source ) {
			update_post_meta( $id, self::META_SOURCE, $source );
		}

		Ajs_Builder_Document::save( $id, is_array( $args['document'] ) ? $args['document'] : array(), true );

		return (int) $id;
	}

	/**
	 * Id of the built-in demo a template was imported from (empty when none).
	 *
	 * @param int $id Template ID.
	 * @return string
	 */
	public static function source( $id ) {
		return sanitize_key( (string) get_post_meta( $id, self::META_SOURCE, true ) );
	}

	/**
	 * Export a template as a portable array.
	 *
	 * @param int $id Template ID.
	 * @return array|null
	 */
	public static function export( $id ) {
		$id = absint( $id );
		if ( ! $id || self::POST_TYPE !== get_post_type( $id ) ) {
			return null;
		}

		return array(
			'type'          => 'ajs-builder-template',
			'version'       => AJS_BUILDER_VERSION,
			'title'         => get_the_title( $id ),
			'template_type' => self::type( $id ),
			'sticky'        => self::sticky( $id ),
			'classes'       => self::classes( $id ),
			'enabled'       => self::is_enabled( $id ),
			'conditions'    => self::conditions( $id ),
			'layout'        => ( 'canvas' === get_post_meta( $id, '_ajs_builder_layout', true ) ) ? 'canvas' : 'default',
			'document'      => Ajs_Builder_Document::get( $id ),
		);
	}

	/**
	 * Export a template as a JSON string.
	 *
	 * @param int $id Template ID.
	 * @return string
	 */
	public static function export_json( $id ) {
		$payload = self::export( $id );
		if ( ! $payload ) {
			return '';
		}
		$json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return is_string( $json ) ? $json : '';
	}

	/**
	 * Import a template from an exported payload.
	 *
	 * @param mixed $data Decoded payload.
	 * @return int|WP_Error New template id.
	 */
	public static function import( $data ) {
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'ajs_builder_import_invalid', __( 'That file is not a valid template export.', 'ajs-builder' ) );
		}

		$type = isset( $data['type'] ) ? $data['type'] : '';
		if ( 'ajs-builder-template' !== $type ) {
			return new WP_Error( 'ajs_builder_import_type', __( 'That file is not a AJS Builder template.', 'ajs-builder' ) );
		}

		if ( empty( $data['document'] ) || ! is_array( $data['document'] ) ) {
			return new WP_Error( 'ajs_builder_import_document', __( 'The template export contains no design.', 'ajs-builder' ) );
		}

		$title = isset( $data['title'] ) ? (string) $data['title'] : '';
		if ( '' === trim( $title ) ) {
			$title = __( 'Imported template', 'ajs-builder' );
		}

		return self::create(
			array(
				'title'      => $title,
				'type'       => isset( $data['template_type'] ) ? $data['template_type'] : 'header',
				'conditions' => isset( $data['conditions'] ) ? $data['conditions'] : array(),
				'sticky'     => isset( $data['sticky'] ) ? $data['sticky'] : false,
				'classes'    => isset( $data['classes'] ) ? $data['classes'] : '',
				'layout'     => isset( $data['layout'] ) ? $data['layout'] : 'canvas',
				'document'   => $data['document'],
				'enabled'    => array_key_exists( 'enabled', $data ) ? (bool) $data['enabled'] : true,
			)
		);
	}

	/**
	 * Templates for the admin list.
	 *
	 * @return WP_Post[]
	 */
	public static function all() {
		return get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => 200,
				'post_status'    => array( 'publish', 'draft' ),
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);
	}
}

/**
 * Header template HTML for the current request (empty when none applies).
 *
 * @return string
 */
function ajs_builder_header_template() {
	return Ajs_Builder_Templates::render( 'header' );
}

/**
 * Footer template HTML for the current request (empty when none applies).
 *
 * @return string
 */
function ajs_builder_footer_template() {
	return Ajs_Builder_Templates::render( 'footer' );
}
