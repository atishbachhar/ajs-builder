<?php
/**
 * Admin: menu, editor screen and assets.
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin UI.
 */
class Ajs_Builder_Admin {

	/**
	 * Editor page slug.
	 */
	const SLUG = 'ajs-builder';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'page_row_actions', array( __CLASS__, 'row_action' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_action' ), 10, 2 );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'submitbox' ) );
		add_action( 'admin_post_ajs_builder_new', array( __CLASS__, 'handle_new' ) );
		add_action( 'admin_post_ajs_builder_new_template', array( __CLASS__, 'handle_new_template' ) );
		add_action( 'admin_post_ajs_builder_delete_template', array( __CLASS__, 'handle_delete_template' ) );
		add_action( 'admin_post_ajs_builder_toggle_template', array( __CLASS__, 'handle_toggle_template' ) );
		add_action( 'admin_post_ajs_builder_export_template', array( __CLASS__, 'handle_export_template' ) );
		add_action( 'admin_post_ajs_builder_import_template', array( __CLASS__, 'handle_import_template' ) );
		add_action( 'admin_post_ajs_builder_import_demo', array( __CLASS__, 'handle_import_demo' ) );
		add_action( 'admin_post_ajs_builder_import_all_demos', array( __CLASS__, 'handle_import_all_demos' ) );
		add_action( 'admin_post_ajs_builder_save_settings', array( __CLASS__, 'handle_save_settings' ) );
		add_action( 'admin_post_ajs_builder_send_message', array( __CLASS__, 'handle_send_message' ) );
	}

	/**
	 * Editor URL for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function editor_url( $post_id ) {
		return add_query_arg(
			array(
				'page'   => self::SLUG,
				'action' => 'edit',
				'post'   => (int) $post_id,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Register the admin menu.
	 */
	public static function menu() {
		add_menu_page(
			__( 'AJS Builder', 'ajs-builder' ),
			__( 'AJS Builder', 'ajs-builder' ),
			'edit_pages',
			self::SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-layout',
			58
		);

		add_submenu_page(
			self::SLUG,
			__( 'Builder Pages', 'ajs-builder' ),
			__( 'Builder Pages', 'ajs-builder' ),
			'edit_pages',
			self::SLUG,
			array( __CLASS__, 'render_page' )
		);

		add_submenu_page(
			self::SLUG,
			__( 'Headers & Footers', 'ajs-builder' ),
			__( 'Headers & Footers', 'ajs-builder' ),
			'edit_pages',
			self::SLUG . '-templates',
			array( __CLASS__, 'render_templates' )
		);

		add_submenu_page(
			self::SLUG,
			__( 'Sections', 'ajs-builder' ),
			__( 'Sections', 'ajs-builder' ),
			'edit_pages',
			self::SLUG . '-sections',
			array( __CLASS__, 'render_sections' )
		);

		add_submenu_page(
			self::SLUG,
			__( 'AJS Builder Settings', 'ajs-builder' ),
			__( 'Settings', 'ajs-builder' ),
			'manage_options',
			self::SLUG . '-settings',
			array( __CLASS__, 'render_settings' )
		);

		add_submenu_page(
			self::SLUG,
			__( 'About the Author', 'ajs-builder' ),
			__( 'About', 'ajs-builder' ),
			'manage_options',
			self::SLUG . '-about',
			array( __CLASS__, 'render_about' )
		);
	}

	/**
	 * Is the current screen the builder editor?
	 *
	 * @return bool
	 */
	private static function is_editor() {
		return isset( $_GET['page'] ) && self::SLUG === $_GET['page'] && isset( $_GET['action'] ) && 'edit' === $_GET['action']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Add a body class on the editor screen.
	 *
	 * @param string $classes Classes.
	 * @return string
	 */
	public static function body_class( $classes ) {
		if ( self::is_editor() ) {
			$classes .= ' ajs-builder-editor';
		}
		return $classes;
	}

	/**
	 * Render the routed admin page.
	 */
	public static function render_page() {
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'edit' === $action ) {
			self::render_editor();
			return;
		}
		self::render_list();
	}

	/**
	 * Editor screen.
	 */
	public static function render_editor() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post || ! current_user_can( 'edit_post', $post_id ) ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Page not found', 'ajs-builder' ) . '</h1><p><a href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ) . '">' . esc_html__( 'Back to Builder Pages', 'ajs-builder' ) . '</a></p></div>';
			return;
		}

		$is_template = ( Ajs_Builder_Templates::POST_TYPE === $post->post_type );
		$layout      = get_post_meta( $post_id, '_ajs_builder_layout', true );
		$layout      = $layout ? $layout : ( $is_template ? 'canvas' : 'default' );
		?>
		<div id="ajs-app" class="ajs-app" data-post="<?php echo esc_attr( $post_id ); ?>" data-layout="<?php echo esc_attr( $layout ); ?>">
			<header class="ajs-bar">
				<div class="ajs-bar__left">
					<a class="ajs-bar__logo" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>" title="<?php esc_attr_e( 'AJS Builder', 'ajs-builder' ); ?>">
						<span class="ajs-bar__mark" aria-hidden="true">
							<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="9" x2="9" y2="21"/></svg>
						</span>
						<span class="screen-reader-text"><?php esc_html_e( 'AJS Builder', 'ajs-builder' ); ?></span>
					</a>
					<button type="button" class="ajs-bar__btn" id="ajs-toggle-panel" title="<?php esc_attr_e( 'Toggle panel', 'ajs-builder' ); ?>">
						<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
					</button>
					<input type="text" class="ajs-bar__title" id="ajs-title" value="<?php echo esc_attr( $post->post_title ); ?>" aria-label="<?php esc_attr_e( 'Title', 'ajs-builder' ); ?>" />
				</div>
				<div class="ajs-bar__right">
					<div class="ajs-devices" id="ajs-devices">
						<button type="button" class="is-active" data-device="desktop" title="<?php esc_attr_e( 'Desktop', 'ajs-builder' ); ?>">
							<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="13" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
						</button>
						<button type="button" data-device="tablet" title="<?php esc_attr_e( 'Tablet', 'ajs-builder' ); ?>">
							<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>
						</button>
						<button type="button" data-device="mobile" title="<?php esc_attr_e( 'Mobile', 'ajs-builder' ); ?>">
							<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="2" width="10" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>
						</button>
					</div>
					<label class="ajs-layout"<?php echo $is_template ? ' style="display:none"' : ''; ?>>
						<span><?php esc_html_e( 'Layout', 'ajs-builder' ); ?></span>
						<select id="ajs-layout">
							<option value="default" <?php selected( $layout, 'default' ); ?>><?php esc_html_e( 'Theme header & footer', 'ajs-builder' ); ?></option>
							<option value="canvas" <?php selected( $layout, 'canvas' ); ?>><?php esc_html_e( 'Canvas (blank)', 'ajs-builder' ); ?></option>
						</select>
					</label>
					<button type="button" class="ajs-bar__btn" id="ajs-structure-toggle" title="<?php esc_attr_e( 'Structure', 'ajs-builder' ); ?>" aria-pressed="false">
						<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="4" cy="6" r="1.4"/><circle cx="4" cy="12" r="1.4"/><circle cx="4" cy="18" r="1.4"/></svg>
					</button>
					<?php if ( ! $is_template ) : ?>
					<button type="button" class="ajs-bar__btn" id="ajs-templates-toggle" title="<?php esc_attr_e( 'Templates', 'ajs-builder' ); ?>" aria-pressed="false">
						<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
					</button>
					<?php endif; ?>
					<a class="ajs-bar__btn" id="ajs-preview" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank" rel="noopener" title="<?php esc_attr_e( 'Preview', 'ajs-builder' ); ?>">
						<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
					</a>
					<span class="ajs-save-state" id="ajs-state"></span>
					<button type="button" class="ajs-bar__save" id="ajs-save">
						<?php esc_html_e( 'Save', 'ajs-builder' ); ?>
					</button>
				</div>
			</header>

			<?php if ( $is_template ) : ?>
				<?php
				$ajs_tpl_type = Ajs_Builder_Templates::type( $post_id );
				$ajs_tpl_hf   = ( 'section' !== $ajs_tpl_type );
				$ajs_tpl_on   = Ajs_Builder_Templates::is_enabled( $post_id );
				?>
				<div class="ajs-tplbar" id="ajs-tplbar" data-tpl-type="<?php echo esc_attr( $ajs_tpl_type ); ?>">
					<span class="ajs-tplbar__badge" id="ajs-tpl-badge">
						<?php
						if ( 'footer' === $ajs_tpl_type ) {
							esc_html_e( 'Footer template', 'ajs-builder' );
						} elseif ( 'section' === $ajs_tpl_type ) {
							esc_html_e( 'Section template', 'ajs-builder' );
						} else {
							esc_html_e( 'Header template', 'ajs-builder' );
						}
						?>
					</span>
					<?php if ( $ajs_tpl_hf ) : ?>
						<div class="ajs-tplbar__field ajs-tplbar__enabled" id="ajs-tpl-enabled-wrap">
							<span><?php esc_html_e( 'Enabled', 'ajs-builder' ); ?></span>
							<label class="ajs-switch" title="<?php esc_attr_e( 'Turn this template on or off across the site.', 'ajs-builder' ); ?>">
								<input type="checkbox" id="ajs-tpl-enabled" <?php checked( $ajs_tpl_on ); ?> />
								<span class="ajs-switch__track"></span>
								<span><?php esc_html_e( 'Active', 'ajs-builder' ); ?></span>
							</label>
						</div>
						<label class="ajs-tplbar__field">
							<span><?php esc_html_e( 'Type', 'ajs-builder' ); ?></span>
							<select id="ajs-tpl-type">
								<option value="header" <?php selected( $ajs_tpl_type, 'header' ); ?>><?php esc_html_e( 'Header', 'ajs-builder' ); ?></option>
								<option value="footer" <?php selected( $ajs_tpl_type, 'footer' ); ?>><?php esc_html_e( 'Footer', 'ajs-builder' ); ?></option>
							</select>
						</label>
						<label class="ajs-tplbar__field">
							<span><?php esc_html_e( 'Display on', 'ajs-builder' ); ?></span>
							<select id="ajs-tpl-display">
								<?php $ajs_cond = Ajs_Builder_Templates::conditions( $post_id ); ?>
								<?php foreach ( Ajs_Builder_Templates::displays() as $ajs_key => $ajs_lab ) : ?>
									<option value="<?php echo esc_attr( $ajs_key ); ?>" <?php selected( $ajs_cond['display'], $ajs_key ); ?>><?php echo esc_html( $ajs_lab ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<div class="ajs-tplbar__field ajs-tplbar__pages" id="ajs-tpl-pages-wrap" hidden>
							<span><?php esc_html_e( 'Pages', 'ajs-builder' ); ?></span>
							<div class="ajs-dd" id="ajs-tpl-pages">
								<button type="button" class="ajs-dd__toggle" id="ajs-tpl-pages-toggle" aria-expanded="false" aria-haspopup="true">
									<span class="ajs-dd__value" id="ajs-tpl-pages-label"><?php esc_html_e( 'Select pages…', 'ajs-builder' ); ?></span>
									<svg class="ajs-dd__chev" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
								</button>
								<div class="ajs-dd__menu" id="ajs-tpl-pages-menu" hidden>
									<input type="search" class="ajs-dd__search" id="ajs-tpl-pages-search" placeholder="<?php esc_attr_e( 'Search pages…', 'ajs-builder' ); ?>" />
									<div class="ajs-dd__list">
										<?php foreach ( Ajs_Builder_Templates::choices() as $ajs_group => $ajs_items ) : ?>
											<div class="ajs-dd__groupblock">
												<div class="ajs-dd__group"><?php echo esc_html( $ajs_group ); ?></div>
												<?php foreach ( $ajs_items as $ajs_item ) : ?>
													<label class="ajs-dd__opt" data-label="<?php echo esc_attr( strtolower( $ajs_item->post_title ) ); ?>">
														<input type="checkbox" value="<?php echo esc_attr( $ajs_item->ID ); ?>" <?php checked( in_array( (int) $ajs_item->ID, $ajs_cond['ids'], true ) ); ?> />
														<span><?php echo esc_html( $ajs_item->post_title ? $ajs_item->post_title : __( '(no title)', 'ajs-builder' ) ); ?></span>
													</label>
												<?php endforeach; ?>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
						</div>
						<div class="ajs-tplbar__field ajs-tplbar__sticky" id="ajs-tpl-sticky-wrap"<?php echo 'header' === $ajs_tpl_type ? '' : ' hidden'; ?>>
							<span><?php esc_html_e( 'Sticky', 'ajs-builder' ); ?></span>
							<label class="ajs-switch" title="<?php esc_attr_e( 'Keep this header pinned to the top while scrolling', 'ajs-builder' ); ?>">
								<input type="checkbox" id="ajs-tpl-sticky" <?php checked( Ajs_Builder_Templates::sticky( $post_id ) ); ?> />
								<span class="ajs-switch__track"></span>
								<span><?php esc_html_e( 'Stick to top', 'ajs-builder' ); ?></span>
							</label>
						</div>
						<label class="ajs-tplbar__field ajs-tplbar__class">
							<span><?php esc_html_e( 'CSS class', 'ajs-builder' ); ?></span>
							<input type="text" id="ajs-tpl-class" value="<?php echo esc_attr( Ajs_Builder_Templates::classes( $post_id ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. site-header, is-dark', 'ajs-builder' ); ?>" spellcheck="false" />
						</label>
						<span class="ajs-tplbar__hint"><?php esc_html_e( 'Design the template below, choose where it appears, then Save to apply.', 'ajs-builder' ); ?></span>
					<?php else : ?>
						<span class="ajs-tplbar__hint"><?php esc_html_e( 'A reusable block. Save it, then insert it from the Templates panel on any page.', 'ajs-builder' ); ?></span>
					<?php endif; ?>
				</div>

			<?php endif; ?>

			<div class="ajs-body">
				<aside class="ajs-panel" id="ajs-panel">
					<div class="ajs-panel__head">
						<button type="button" class="ajs-panel__back" id="ajs-back" hidden>
							<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
						</button>
						<span class="ajs-panel__title" id="ajs-panel-title"><?php esc_html_e( 'Modules', 'ajs-builder' ); ?></span>
						<input type="search" class="ajs-panel__search" id="ajs-search" placeholder="<?php esc_attr_e( 'Search…', 'ajs-builder' ); ?>" />
					</div>
					<div class="ajs-panel__body" id="ajs-panel-body"></div>
				</aside>

				<main class="ajs-canvas" id="ajs-canvas">
					<div class="ajs-canvas__frame" id="ajs-frame-wrap">
						<iframe id="ajs-frame" title="<?php esc_attr_e( 'Page preview', 'ajs-builder' ); ?>"></iframe>
						<div class="ajs-canvas__loading" id="ajs-loading"><span class="ajs-spinner"></span></div>
					</div>
				</main>

				<div class="ajs-structure" id="ajs-structure" hidden>
					<div class="ajs-structure__head">
						<span class="ajs-structure__title"><?php esc_html_e( 'Structure', 'ajs-builder' ); ?></span>
						<button type="button" class="ajs-structure__close" id="ajs-structure-close" aria-label="<?php esc_attr_e( 'Close', 'ajs-builder' ); ?>">
							<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg>
						</button>
					</div>
					<div class="ajs-structure__body" id="ajs-structure-body"></div>
				</div>

				<?php if ( ! $is_template ) : ?>
				<div class="ajs-lib" id="ajs-lib" hidden>
					<div class="ajs-lib__head">
						<span class="ajs-lib__title"><?php esc_html_e( 'Templates', 'ajs-builder' ); ?></span>
						<button type="button" class="ajs-lib__close" id="ajs-lib-close" aria-label="<?php esc_attr_e( 'Close', 'ajs-builder' ); ?>">
							<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg>
						</button>
					</div>
					<div class="ajs-lib__hint"><?php esc_html_e( 'Insert a saved section template into this page, or import one from a JSON file to reuse on any page.', 'ajs-builder' ); ?></div>
					<div class="ajs-lib__body" id="ajs-lib-body"></div>
					<div class="ajs-lib__foot">
						<label class="ajs-lib__import">
							<input type="file" id="ajs-lib-import" accept="application/json,.json" hidden />
							<span class="ajs-lib__importbtn"><?php esc_html_e( 'Import JSON…', 'ajs-builder' ); ?></span>
						</label>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<div class="ajs-toast" id="ajs-toast" hidden></div>
		</div>
		<?php
	}

	/**
	 * List screen.
	 */
	public static function render_list() {
		$types = Ajs_Builder_Plugin::post_types();
		$pages = get_posts(
			array(
				'post_type'      => $types,
				'posts_per_page' => 100,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);
		?>
		<div class="wrap ajs-list">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'AJS Builder', 'ajs-builder' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ajs-list__new">
				<input type="hidden" name="action" value="ajs_builder_new" />
				<?php wp_nonce_field( 'ajs_builder_new' ); ?>
				<input type="text" name="title" placeholder="<?php esc_attr_e( 'New page title…', 'ajs-builder' ); ?>" required />
				<button type="submit" class="page-title-action"><?php esc_html_e( 'Create & edit', 'ajs-builder' ); ?></button>
			</form>
			<hr class="wp-header-end" />

			<p class="ajs-list__intro"><?php esc_html_e( 'Build beautiful pages visually with drag & drop modules. Click a page below to open the visual editor.', 'ajs-builder' ); ?></p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Title', 'ajs-builder' ); ?></th>
						<th><?php esc_html_e( 'Type', 'ajs-builder' ); ?></th>
						<th><?php esc_html_e( 'Builder', 'ajs-builder' ); ?></th>
						<th><?php esc_html_e( 'Modified', 'ajs-builder' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'ajs-builder' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $pages ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No pages yet. Create one above to get started.', 'ajs-builder' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $pages as $page ) : ?>
							<?php $on = Ajs_Builder_Document::is_enabled( $page->ID ); ?>
							<tr>
								<td><strong><a href="<?php echo esc_url( self::editor_url( $page->ID ) ); ?>"><?php echo esc_html( $page->post_title ? $page->post_title : __( '(no title)', 'ajs-builder' ) ); ?></a></strong></td>
								<td><?php echo esc_html( $page->post_type ); ?></td>
								<td>
									<?php if ( $on ) : ?>
										<span class="ajs-badge ajs-badge--on"><?php esc_html_e( 'Active', 'ajs-builder' ); ?></span>
									<?php else : ?>
										<span class="ajs-badge"><?php esc_html_e( 'Not built', 'ajs-builder' ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( get_the_modified_date( 'M j, Y', $page ) ); ?></td>
								<td>
									<a class="button button-primary button-small" href="<?php echo esc_url( self::editor_url( $page->ID ) ); ?>"><?php esc_html_e( 'Edit with Builder', 'ajs-builder' ); ?></a>
									<?php if ( $on ) : ?>
										<a class="button button-small" href="<?php echo esc_url( get_permalink( $page->ID ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'ajs-builder' ); ?></a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Settings screen.
	 */
	public static function render_settings() {
		$s             = Ajs_Builder_Settings::all();
		$post_types    = Ajs_Builder_Settings::available_post_types();
		$enabled_types = (array) $s['post_types'];
		$disabled      = (array) $s['disabled_modules'];
		$modules       = Ajs_Builder_Widgets::all();
		$groups        = Ajs_Builder_Widgets::groups();
		$updated       = isset( $_GET['updated'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap ajs-admin">
			<h1><?php esc_html_e( 'AJS Builder — Settings', 'ajs-builder' ); ?></h1>

			<?php if ( $updated ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'ajs-builder' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ajs_builder_save_settings" />
				<?php wp_nonce_field( 'ajs_builder_settings' ); ?>

				<div class="ajs-card">
					<h2><?php esc_html_e( 'General', 'ajs-builder' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="ajs-container-width"><?php esc_html_e( 'Site container width', 'ajs-builder' ); ?></label></th>
							<td>
								<input type="number" min="0" max="4000" step="10" id="ajs-container-width" name="container_width" value="<?php echo esc_attr( $s['container_width'] ); ?>" class="small-text" placeholder="1200" />
								<span class="ajs-unit">px</span>
								<p class="description"><?php esc_html_e( 'Max width of the "boxed" area used by builder sections. Set to 0 to keep the theme default (1200px).', 'ajs-builder' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ajs-default-width"><?php esc_html_e( 'Default content width', 'ajs-builder' ); ?></label></th>
							<td>
								<select id="ajs-default-width" name="default_content_width">
									<option value="boxed" <?php selected( 'boxed', $s['default_content_width'] ); ?>><?php esc_html_e( 'Boxed', 'ajs-builder' ); ?></option>
									<option value="full" <?php selected( 'full', $s['default_content_width'] ); ?>><?php esc_html_e( 'Full width', 'ajs-builder' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Applied to new sections when no content width is chosen.', 'ajs-builder' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ajs-custom-css"><?php esc_html_e( 'Global custom CSS', 'ajs-builder' ); ?></label></th>
							<td>
								<textarea id="ajs-custom-css" name="custom_css" rows="7" class="large-text code" spellcheck="false" placeholder=".ajs-b-container { border-radius: 16px; }"><?php echo esc_textarea( $s['custom_css'] ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Output on every page that uses the builder (or a builder header/footer).', 'ajs-builder' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<?php self::render_design_presets_card(); ?>

				<?php self::render_styling_cards(); ?>

				<div class="ajs-card">
					<h2><?php esc_html_e( 'Post types', 'ajs-builder' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Choose where the builder and its modules can be used. The "Edit with Builder" link appears for these.', 'ajs-builder' ); ?></p>
					<div class="ajs-posttypes">
						<?php foreach ( $post_types as $slug => $label ) : ?>
							<label class="ajs-check">
								<input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $enabled_types, true ) ); ?> />
								<span><?php echo esc_html( $label ); ?> <code><?php echo esc_html( $slug ); ?></code></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="ajs-card">
					<h2><?php esc_html_e( 'Available modules', 'ajs-builder' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Uncheck a module to hide it from the editor. Pages already using it keep rendering it.', 'ajs-builder' ); ?></p>
					<?php foreach ( $groups as $gkey => $glabel ) : ?>
						<?php
						$in_group = array();
						foreach ( $modules as $type => $def ) {
							$def_group = isset( $def['group'] ) ? $def['group'] : 'layout';
							if ( $def_group === $gkey ) {
								$in_group[ $type ] = $def;
							}
						}
						if ( ! $in_group ) {
							continue;
						}
						?>
						<details class="ajs-modgroup" open>
							<summary class="ajs-modgroup__summary">
								<span class="ajs-modgroup__title"><?php echo esc_html( $glabel ); ?></span>
								<span class="ajs-modgroup__count"><?php echo (int) count( $in_group ); ?></span>
								<span class="ajs-modgroup__chev" aria-hidden="true"></span>
							</summary>
							<div class="ajs-modgrid">
								<?php foreach ( $in_group as $type => $def ) : ?>
									<?php $on = ! in_array( $type, $disabled, true ); ?>
									<label class="ajs-modcard<?php echo $on ? '' : ' is-off'; ?>">
										<input type="checkbox" name="enabled_modules[]" value="<?php echo esc_attr( $type ); ?>" <?php checked( $on ); ?> />
										<span class="ajs-modcard__ico"><?php echo Ajs_Builder_Icons::module_svg( $type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
										<span class="ajs-modcard__name"><?php echo esc_html( $def['title'] ); ?></span>
										<span class="ajs-modcard__dot" aria-hidden="true"></span>
									</label>
								<?php endforeach; ?>
							</div>
						</details>
					<?php endforeach; ?>
				</div>

				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'ajs-builder' ); ?></button>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the "Design Presets" settings card.
	 */
	private static function render_design_presets_card() {
		?>
		<div class="ajs-card">
			<h2><?php esc_html_e( 'Design Presets', 'ajs-builder' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Apply a ready-made look to every field below, then click Save. You can also save your current styling as a reusable preset.', 'ajs-builder' ); ?></p>
			<div class="ajs-flex-row">
				<select id="ajs-style-preset" class="regular-text">
					<option value=""><?php esc_html_e( '— Apply a preset —', 'ajs-builder' ); ?></option>
					<?php foreach ( Ajs_Builder_Styles::presets() as $name => $preset ) : ?>
						<option value="<?php echo esc_attr( $name ); ?>" data-tokens="<?php echo esc_attr( wp_json_encode( $preset ) ); ?>"><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="text" name="preset_name" class="regular-text" placeholder="<?php esc_attr_e( 'New preset name', 'ajs-builder' ); ?>" />
				<label class="ajs-inline-check"><input type="checkbox" name="save_preset" value="1" /> <?php esc_html_e( 'Save current styling as this preset', 'ajs-builder' ); ?></label>
			</div>
			<?php
			$saved = Ajs_Builder_Settings::get( 'style_presets', array() );
			if ( is_array( $saved ) && $saved ) :
				?>
				<div class="ajs-flex-row">
					<select id="ajs-delete-preset" name="delete_preset">
						<option value=""><?php esc_html_e( '— Delete a saved preset —', 'ajs-builder' ); ?></option>
						<?php foreach ( array_keys( $saved ) as $name ) : ?>
							<option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="description"><?php esc_html_e( 'Selected preset is removed when you save.', 'ajs-builder' ); ?></span>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the "Global Styling" settings cards.
	 */
	private static function render_styling_cards() {
		$tokens = Ajs_Builder_Styles::tokens();

		$colors = array(
			'style_color_primary'      => __( 'Primary', 'ajs-builder' ),
			'style_color_primary_dark' => __( 'Primary dark / hover', 'ajs-builder' ),
			'style_color_accent'       => __( 'Accent', 'ajs-builder' ),
			'style_color_ink'          => __( 'Headings', 'ajs-builder' ),
			'style_color_body'         => __( 'Body text', 'ajs-builder' ),
			'style_color_muted'        => __( 'Muted text', 'ajs-builder' ),
			'style_color_bg'           => __( 'Page background', 'ajs-builder' ),
			'style_color_surface'      => __( 'Surfaces / cards', 'ajs-builder' ),
			'style_color_border'       => __( 'Borders', 'ajs-builder' ),
			'style_color_dark'         => __( 'Dark / footer', 'ajs-builder' ),
			'style_color_dark_soft'    => __( 'Dark soft', 'ajs-builder' ),
		);

		$weights = array( 300 => '300', 400 => '400', 500 => '500', 600 => '600', 700 => '700', 800 => '800', 900 => '900' );

		$font_stacks = array(
			'system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
			'Georgia, "Times New Roman", Times, serif',
			'"Helvetica Neue", Helvetica, Arial, sans-serif',
			'Verdana, Geneva, Tahoma, sans-serif',
			'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace',
		);
		?>
		<datalist id="ajs-font-stacks">
			<?php foreach ( $font_stacks as $stack ) : ?>
				<option value="<?php echo esc_attr( $stack ); ?>"></option>
			<?php endforeach; ?>
		</datalist>

		<div class="ajs-card">
			<h2><?php esc_html_e( 'Global Colors', 'ajs-builder' ); ?></h2>
			<p class="description"><?php esc_html_e( 'These colors become the site palette for builder pages and header/footer templates (buttons, links, accents, borders and more).', 'ajs-builder' ); ?></p>
			<div class="ajs-style-grid">
				<?php foreach ( $colors as $key => $label ) : ?>
					<div class="ajs-style-field">
						<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
						<input
							type="text"
							class="ajs-color-field"
							id="<?php echo esc_attr( $key ); ?>"
							name="<?php echo esc_attr( $key ); ?>"
							value="<?php echo esc_attr( $tokens[ $key ] ); ?>"
							data-default-color="<?php echo esc_attr( Ajs_Builder_Styles::default_for( $key ) ); ?>"
						/>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="ajs-card">
			<h2><?php esc_html_e( 'Typography', 'ajs-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="style_font_body"><?php esc_html_e( 'Body font family', 'ajs-builder' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="style_font_body" name="style_font_body" list="ajs-font-stacks" value="<?php echo esc_attr( $tokens['style_font_body'] ); ?>" placeholder="<?php esc_attr_e( 'Inherit from theme', 'ajs-builder' ); ?>" />
						<p class="description"><?php esc_html_e( 'Leave blank to use the theme font. Pick a safe stack from the list or paste your own.', 'ajs-builder' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="style_font_heading"><?php esc_html_e( 'Heading font family', 'ajs-builder' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="style_font_heading" name="style_font_heading" list="ajs-font-stacks" value="<?php echo esc_attr( $tokens['style_font_heading'] ); ?>" placeholder="<?php esc_attr_e( 'Inherit from body', 'ajs-builder' ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="style_font_size"><?php esc_html_e( 'Base font size', 'ajs-builder' ); ?></label></th>
					<td>
						<input type="number" min="8" max="72" step="1" class="small-text" id="style_font_size" name="style_font_size" value="<?php echo esc_attr( $tokens['style_font_size'] ); ?>" />
						<span class="ajs-unit">px</span>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Font weight & line height', 'ajs-builder' ); ?></th>
					<td>
						<div class="ajs-inline-fields">
							<label class="ajs-inline-field">
								<span><?php esc_html_e( 'Body weight', 'ajs-builder' ); ?></span>
								<select name="style_font_weight_body">
									<?php foreach ( $weights as $value => $text ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( (int) $tokens['style_font_weight_body'], $value ); ?>><?php echo esc_html( $text ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="ajs-inline-field">
								<span><?php esc_html_e( 'Heading weight', 'ajs-builder' ); ?></span>
								<select name="style_font_weight_heading">
									<?php foreach ( $weights as $value => $text ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( (int) $tokens['style_font_weight_heading'], $value ); ?>><?php echo esc_html( $text ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="ajs-inline-field">
								<span><?php esc_html_e( 'Body line height', 'ajs-builder' ); ?></span>
								<input type="number" min="0.8" max="3" step="0.05" name="style_line_height_body" value="<?php echo esc_attr( $tokens['style_line_height_body'] ); ?>" />
							</label>
							<label class="ajs-inline-field">
								<span><?php esc_html_e( 'Heading line height', 'ajs-builder' ); ?></span>
								<input type="number" min="0.8" max="3" step="0.05" name="style_line_height_heading" value="<?php echo esc_attr( $tokens['style_line_height_heading'] ); ?>" />
							</label>
						</div>
					</td>
				</tr>
			</table>
		</div>

		<div class="ajs-card">
			<h2><?php esc_html_e( 'Links & Buttons', 'ajs-builder' ); ?></h2>
			<div class="ajs-style-grid">
				<div class="ajs-style-field">
					<label for="style_link"><?php esc_html_e( 'Link', 'ajs-builder' ); ?></label>
					<input type="text" class="ajs-color-field" id="style_link" name="style_link" value="<?php echo esc_attr( $tokens['style_link'] ); ?>" data-default-color="<?php echo esc_attr( Ajs_Builder_Styles::default_for( 'style_link' ) ); ?>" />
				</div>
				<div class="ajs-style-field">
					<label for="style_link_hover"><?php esc_html_e( 'Link hover', 'ajs-builder' ); ?></label>
					<input type="text" class="ajs-color-field" id="style_link_hover" name="style_link_hover" value="<?php echo esc_attr( $tokens['style_link_hover'] ); ?>" data-default-color="<?php echo esc_attr( Ajs_Builder_Styles::default_for( 'style_link_hover' ) ); ?>" />
				</div>
				<div class="ajs-style-field">
					<label for="style_button_bg"><?php esc_html_e( 'Button background', 'ajs-builder' ); ?></label>
					<input type="text" class="ajs-color-field" id="style_button_bg" name="style_button_bg" value="<?php echo esc_attr( $tokens['style_button_bg'] ); ?>" data-default-color="<?php echo esc_attr( Ajs_Builder_Styles::default_for( 'style_button_bg' ) ); ?>" />
				</div>
				<div class="ajs-style-field">
					<label for="style_button_hover_bg"><?php esc_html_e( 'Button hover', 'ajs-builder' ); ?></label>
					<input type="text" class="ajs-color-field" id="style_button_hover_bg" name="style_button_hover_bg" value="<?php echo esc_attr( $tokens['style_button_hover_bg'] ); ?>" data-default-color="<?php echo esc_attr( Ajs_Builder_Styles::default_for( 'style_button_hover_bg' ) ); ?>" />
				</div>
				<div class="ajs-style-field">
					<label for="style_button_text"><?php esc_html_e( 'Button text', 'ajs-builder' ); ?></label>
					<input type="text" class="ajs-color-field" id="style_button_text" name="style_button_text" value="<?php echo esc_attr( $tokens['style_button_text'] ); ?>" data-default-color="<?php echo esc_attr( Ajs_Builder_Styles::default_for( 'style_button_text' ) ); ?>" />
				</div>
				<div class="ajs-style-field">
					<label for="style_button_radius"><?php esc_html_e( 'Button radius', 'ajs-builder' ); ?></label>
					<input type="number" min="0" max="80" step="1" id="style_button_radius" name="style_button_radius" value="<?php echo esc_attr( $tokens['style_button_radius'] ); ?>" />
					<span class="ajs-unit">px</span>
				</div>
			</div>
		</div>

		<div class="ajs-card">
			<h2><?php esc_html_e( 'Borders, Shadow & Spacing', 'ajs-builder' ); ?></h2>
			<div class="ajs-style-grid">
				<div class="ajs-style-field">
					<label for="style_radius"><?php esc_html_e( 'Corner radius', 'ajs-builder' ); ?></label>
					<input type="number" min="0" max="80" step="1" id="style_radius" name="style_radius" value="<?php echo esc_attr( $tokens['style_radius'] ); ?>" />
					<span class="ajs-unit">px</span>
				</div>
				<div class="ajs-style-field">
					<label for="style_space"><?php esc_html_e( 'Base spacing', 'ajs-builder' ); ?></label>
					<input type="number" min="0" max="200" step="1" id="style_space" name="style_space" value="<?php echo esc_attr( $tokens['style_space'] ); ?>" />
					<span class="ajs-unit">px</span>
				</div>
				<div class="ajs-style-field">
					<label for="style_shadow"><?php esc_html_e( 'Shadow', 'ajs-builder' ); ?></label>
					<select id="style_shadow" name="style_shadow">
						<?php foreach ( Ajs_Builder_Styles::shadows() as $slug => $value ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $tokens['style_shadow'], $slug ); ?>><?php echo esc_html( ucfirst( $slug ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>

		<div class="ajs-card">
			<h2><?php esc_html_e( 'External Fonts', 'ajs-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="style_webfonts_url"><?php esc_html_e( 'Font stylesheet URL', 'ajs-builder' ); ?></label></th>
					<td>
						<input type="url" class="large-text code" id="style_webfonts_url" name="style_webfonts_url" value="<?php echo esc_attr( $tokens['style_webfonts_url'] ); ?>" placeholder="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" />
						<p class="description"><?php esc_html_e( 'Optional. Loaded on builder pages and header/footer templates only, so the default stays dependency-free. Then use the same family in the fields above.', 'ajs-builder' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * About screen: plugin intro, author details and contact form.
	 */
	public static function render_about() {
		$sent   = isset( $_GET['sent'] ) ? sanitize_key( wp_unslash( $_GET['sent'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$author = array(
			'name'  => 'Atish Bachhar',
			'role'  => 'Web Developer',
			'email' => 'atishbachhar@gmail.com',
			'url'   => 'https://atish.co.in',
			'phone' => '+91 7362977273',
		);
		$to     = 'atishbachhar@gmail.com';
		?>
		<div class="wrap ajs-admin">
			<h1><?php esc_html_e( 'About', 'ajs-builder' ); ?></h1>

			<div class="ajs-card ajs-about-intro">
				<h2><?php esc_html_e( 'About Plugin', 'ajs-builder' ); ?></h2>
				<p><?php esc_html_e( 'AJS Builder is a lightweight, dependency-free visual page builder for WordPress. Drag sections, columns and modules onto a live canvas and design pages visually — no shortcodes, no coding and no external services.', 'ajs-builder' ); ?></p>
				<p><a href="<?php echo esc_url( AJS_BUILDER_URL . 'docs/index.html' ); ?>" class="button button-primary" target="_blank" rel="noopener"><?php esc_html_e( 'View Documentation', 'ajs-builder' ); ?></a></p>
				<ul class="ajs-about-features">
					<li><?php esc_html_e( 'Drag & drop sections, columns and 24+ ready-made modules', 'ajs-builder' ); ?></li>
					<li><?php esc_html_e( 'Live preview with per-device visibility controls', 'ajs-builder' ); ?></li>
					<li><?php esc_html_e( 'Reusable headers & footers with display conditions', 'ajs-builder' ); ?></li>
					<li><?php esc_html_e( 'WooCommerce modules: product grid, carousel and category tabs', 'ajs-builder' ); ?></li>
					<li><?php esc_html_e( 'Global colors, typography, buttons and reusable design presets', 'ajs-builder' ); ?></li>
					<li><?php esc_html_e( 'Global container width, custom CSS and module on/off controls', 'ajs-builder' ); ?></li>
					<li><?php esc_html_e( 'Works with pages, posts and custom post types', 'ajs-builder' ); ?></li>
				</ul>
			</div>

			<div class="ajs-about">
				<div class="ajs-about__card">
					<div class="ajs-about__avatar"><?php echo esc_html( strtoupper( substr( $author['name'], 0, 1 ) ) ); ?></div>
					<h2><?php echo esc_html( $author['name'] ); ?></h2>
					<p class="ajs-about__role"><?php echo esc_html( $author['role'] ); ?></p>
					<ul class="ajs-about__list">
						<li><span class="dashicons dashicons-email"></span><a href="mailto:<?php echo esc_attr( $author['email'] ); ?>"><?php echo esc_html( $author['email'] ); ?></a></li>
						<li><span class="dashicons dashicons-admin-links"></span><a href="<?php echo esc_url( $author['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $author['url'] ); ?></a></li>
						<li><span class="dashicons dashicons-phone"></span><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $author['phone'] ) ); ?>"><?php echo esc_html( $author['phone'] ); ?></a></li>
					</ul>
					<p class="ajs-about__version"><?php printf( esc_html__( 'AJS Builder v%s', 'ajs-builder' ), esc_html( AJS_BUILDER_VERSION ) ); ?></p>
				</div>

				<div class="ajs-card">
					<h2><?php esc_html_e( 'Send a message', 'ajs-builder' ); ?></h2>
					<p class="description">
						<?php
						printf(
							/* translators: %s: recipient email address. */
							esc_html__( 'Messages are emailed to %s.', 'ajs-builder' ),
							'<strong>' . esc_html( $to ) . '</strong>'
						);
						?>
					</p>
					<?php if ( '1' === $sent ) : ?>
						<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Thanks! Your message was sent.', 'ajs-builder' ); ?></p></div>
					<?php elseif ( '0' === $sent ) : ?>
						<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The message could not be sent. Please check the site email configuration.', 'ajs-builder' ); ?></p></div>
					<?php endif; ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ajs-contact">
						<input type="hidden" name="action" value="ajs_builder_send_message" />
						<?php wp_nonce_field( 'ajs_builder_contact' ); ?>
						<div class="ajs-contact__grid">
							<label><?php esc_html_e( 'Your name', 'ajs-builder' ); ?><input type="text" name="msg_name" required /></label>
							<label><?php esc_html_e( 'Your email', 'ajs-builder' ); ?><input type="email" name="msg_email" required /></label>
						</div>
						<label><?php esc_html_e( 'Subject', 'ajs-builder' ); ?><input type="text" name="msg_subject" required /></label>
						<label><?php esc_html_e( 'Message', 'ajs-builder' ); ?><textarea name="msg_body" rows="6" required></textarea></label>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Send message', 'ajs-builder' ); ?></button>
					</form>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Persist the settings form.
	 */
	public static function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'ajs-builder' ) );
		}
		check_admin_referer( 'ajs_builder_settings' );

		$all      = array_keys( Ajs_Builder_Widgets::all() );
		$enabled  = isset( $_POST['enabled_modules'] ) ? array_map( 'sanitize_key', (array) $_POST['enabled_modules'] ) : array();
		$disabled = array_values( array_diff( $all, $enabled ) );

		$style = array();
		foreach ( array_keys( Ajs_Builder_Styles::schema() ) as $style_key ) {
			if ( isset( $_POST[ $style_key ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised in Settings::sanitize().
				$style[ $style_key ] = wp_unslash( $_POST[ $style_key ] );
			}
		}

		$presets = Ajs_Builder_Settings::get( 'style_presets', array() );
		$presets = is_array( $presets ) ? $presets : array();

		$delete = isset( $_POST['delete_preset'] ) ? sanitize_text_field( wp_unslash( $_POST['delete_preset'] ) ) : '';
		if ( '' !== $delete && isset( $presets[ $delete ] ) ) {
			unset( $presets[ $delete ] );
		}

		$preset_name = isset( $_POST['preset_name'] ) ? sanitize_text_field( wp_unslash( $_POST['preset_name'] ) ) : '';
		$want_save   = ! empty( $_POST['save_preset'] );
		if ( $want_save && '' !== $preset_name ) {
			$presets[ $preset_name ] = $style;
		}
		$style['style_presets'] = $presets;

		Ajs_Builder_Settings::update(
			array_merge(
				$style,
				array(
					'container_width'       => isset( $_POST['container_width'] ) ? $_POST['container_width'] : 0, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised in Settings::sanitize().
					'default_content_width' => isset( $_POST['default_content_width'] ) ? $_POST['default_content_width'] : 'boxed', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					'custom_css'            => isset( $_POST['custom_css'] ) ? $_POST['custom_css'] : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					'post_types'            => isset( $_POST['post_types'] ) ? (array) $_POST['post_types'] : array(), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					'disabled_modules'      => $disabled,
				)
			)
		);

		wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'admin.php?page=' . self::SLUG . '-settings' ) ) );
		exit;
	}

	/**
	 * Send the About contact message.
	 */
	public static function handle_send_message() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'ajs-builder' ) );
		}
		check_admin_referer( 'ajs_builder_contact' );

		$name    = isset( $_POST['msg_name'] ) ? sanitize_text_field( wp_unslash( $_POST['msg_name'] ) ) : '';
		$email   = isset( $_POST['msg_email'] ) ? sanitize_email( wp_unslash( $_POST['msg_email'] ) ) : '';
		$subject = isset( $_POST['msg_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['msg_subject'] ) ) : '';
		$body    = isset( $_POST['msg_body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['msg_body'] ) ) : '';

		$to = Ajs_Builder_Settings::get( 'contact_email' );
		if ( ! $to ) {
			$to = get_option( 'admin_email' );
		}

		$headers  = array( 'Content-Type: text/plain; charset=UTF-8' );
		$from     = $email ? $name . ' <' . $email . '>' : get_option( 'admin_email' );
		$headers[] = 'Reply-To: ' . $from;

		$message = sprintf(
			"%s\n%s\n\n%s",
			$name,
			$email,
			$body
		);

		$sent = wp_mail( $to, $subject ? $subject : __( 'Message from AJS Builder', 'ajs-builder' ), $message, $headers );

		wp_safe_redirect( add_query_arg( 'sent', $sent ? '1' : '0', admin_url( 'admin.php?page=' . self::SLUG . '-about' ) ) );
		exit;
	}

	/**
	 * Headers & Footers list screen.
	 */
	public static function render_templates() {
		$templates = Ajs_Builder_Templates::all();
		$demos     = Ajs_Builder_Demo_Templates::all();
		$imported  = array();
		foreach ( $templates as $tpl ) {
			$src = Ajs_Builder_Templates::source( $tpl->ID );
			if ( '' !== $src ) {
				$imported[ $src ] = (int) $tpl->ID;
			}
		}

		// Section templates live on the dedicated Sections screen.
		$hf = array_values(
			array_filter(
				$templates,
				function ( $tpl ) {
					return 'section' !== Ajs_Builder_Templates::type( $tpl->ID );
				}
			)
		);

		$notice = isset( $_GET['ajs_notice'] ) ? sanitize_key( wp_unslash( $_GET['ajs_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap ajs-admin ajs-templates">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Headers & Footers', 'ajs-builder' ); ?></h1>
			<hr class="wp-header-end" />

			<?php if ( 'imported' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Template imported.', 'ajs-builder' ); ?></p></div>
			<?php elseif ( 'imported_all' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Demo templates imported.', 'ajs-builder' ); ?></p></div>
			<?php elseif ( 'import_error' === $notice ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The template could not be imported. Please check the file and try again.', 'ajs-builder' ); ?></p></div>
			<?php elseif ( 'file_error' === $notice ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The uploaded file is not a valid template export.', 'ajs-builder' ); ?></p></div>
			<?php endif; ?>

			<p class="ajs-list__intro"><?php esc_html_e( 'Design custom headers and footers with the visual builder, then choose which pages each one appears on. Templates with more specific rules win over broad ones. For reusable content blocks, use the Sections screen instead.', 'ajs-builder' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ajs-list__new">
				<input type="hidden" name="action" value="ajs_builder_new_template" />
				<?php wp_nonce_field( 'ajs_builder_new_template' ); ?>
				<input type="text" name="title" placeholder="<?php esc_attr_e( 'Template name…', 'ajs-builder' ); ?>" required />
				<select name="type">
					<option value="header"><?php esc_html_e( 'Header', 'ajs-builder' ); ?></option>
					<option value="footer"><?php esc_html_e( 'Footer', 'ajs-builder' ); ?></option>
				</select>
				<select name="display">
					<?php foreach ( Ajs_Builder_Templates::displays() as $ajs_key => $ajs_lab ) : ?>
						<option value="<?php echo esc_attr( $ajs_key ); ?>"><?php echo esc_html( $ajs_lab ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="page-title-action"><?php esc_html_e( 'Create & design', 'ajs-builder' ); ?></button>
			</form>

			<!-- Existing templates -->
			<div class="ajs-card">
				<h2><?php esc_html_e( 'Your templates', 'ajs-builder' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
								<tr>
									<th><?php esc_html_e( 'Name', 'ajs-builder' ); ?></th>
									<th><?php esc_html_e( 'Type', 'ajs-builder' ); ?></th>
									<th><?php esc_html_e( 'Status', 'ajs-builder' ); ?></th>
									<th><?php esc_html_e( 'Display on', 'ajs-builder' ); ?></th>
									<th><?php esc_html_e( 'Modified', 'ajs-builder' ); ?></th>
								</tr>
					</thead>
					<tbody>
						<?php if ( ! $hf ) : ?>
							<tr><td colspan="5"><?php esc_html_e( 'No templates yet. Create one above, or import a starter template below.', 'ajs-builder' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $hf as $tpl ) : ?>
								<?php
								$export_url = wp_nonce_url( admin_url( 'admin-post.php?action=ajs_builder_export_template&id=' . (int) $tpl->ID ), 'ajs_builder_export_template_' . (int) $tpl->ID );
								$tpl_type   = Ajs_Builder_Templates::type( $tpl->ID );
								$tpl_on     = Ajs_Builder_Templates::is_enabled( $tpl->ID );
								$tpl_toggle = wp_nonce_url( admin_url( 'admin-post.php?action=ajs_builder_toggle_template&id=' . (int) $tpl->ID ), 'ajs_builder_toggle_template_' . (int) $tpl->ID );
								?>
								<tr>
									<td>
										<strong><a href="<?php echo esc_url( self::editor_url( $tpl->ID ) ); ?>"><?php echo esc_html( $tpl->post_title ? $tpl->post_title : __( '(no title)', 'ajs-builder' ) ); ?></a></strong>
										<?php if ( Ajs_Builder_Templates::source( $tpl->ID ) ) : ?>
											<span class="ajs-badge ajs-badge--demo"><?php esc_html_e( 'Demo', 'ajs-builder' ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( 'footer' === $tpl_type ) : ?>
											<span class="ajs-badge"><?php esc_html_e( 'Footer', 'ajs-builder' ); ?></span>
										<?php elseif ( 'section' === $tpl_type ) : ?>
											<span class="ajs-badge ajs-badge--section"><?php esc_html_e( 'Section', 'ajs-builder' ); ?></span>
										<?php else : ?>
											<span class="ajs-badge ajs-badge--on"><?php esc_html_e( 'Header', 'ajs-builder' ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( 'section' === $tpl_type ) : ?>
											<span class="ajs-badge ajs-badge--section"><?php esc_html_e( 'Reusable block', 'ajs-builder' ); ?></span>
										<?php elseif ( $tpl_on ) : ?>
											<span class="ajs-badge ajs-badge--on"><?php esc_html_e( 'Enabled', 'ajs-builder' ); ?></span>
											<a class="button-link" href="<?php echo esc_url( $tpl_toggle ); ?>"><?php esc_html_e( 'Disable', 'ajs-builder' ); ?></a>
										<?php else : ?>
											<span class="ajs-badge ajs-badge--muted"><?php esc_html_e( 'Disabled', 'ajs-builder' ); ?></span>
											<a class="button-link" href="<?php echo esc_url( $tpl_toggle ); ?>"><?php esc_html_e( 'Enable', 'ajs-builder' ); ?></a>
										<?php endif; ?>
									</td>
									<td><?php echo 'section' === $tpl_type ? esc_html__( 'Reusable block', 'ajs-builder' ) : esc_html( Ajs_Builder_Templates::describe( $tpl->ID ) ); ?></td>
									<td><?php echo esc_html( get_the_modified_date( 'M j, Y', $tpl ) ); ?></td>
									<td>
										<a class="button button-primary button-small" href="<?php echo esc_url( self::editor_url( $tpl->ID ) ); ?>"><?php esc_html_e( 'Edit with Builder', 'ajs-builder' ); ?></a>
										<a class="button button-small" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export', 'ajs-builder' ); ?></a>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this template?', 'ajs-builder' ) ); ?>');">
											<input type="hidden" name="action" value="ajs_builder_delete_template" />
											<input type="hidden" name="id" value="<?php echo esc_attr( $tpl->ID ); ?>" />
											<?php wp_nonce_field( 'ajs_builder_delete_template_' . $tpl->ID ); ?>
											<button type="submit" class="button button-small"><?php esc_html_e( 'Delete', 'ajs-builder' ); ?></button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Starter / demo templates -->
			<div class="ajs-card">
				<div class="ajs-tpl-head">
					<div>
						<h2><?php esc_html_e( 'Starter templates', 'ajs-builder' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Six responsive demo headers and footers. Import one to copy it into your own editable template — original demos are always kept.', 'ajs-builder' ); ?></p>
					</div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="ajs_builder_import_all_demos" />
						<?php wp_nonce_field( 'ajs_builder_import_all_demos' ); ?>
						<button type="submit" class="button"><?php esc_html_e( 'Import all demos', 'ajs-builder' ); ?></button>
					</form>
				</div>

				<?php foreach ( array( 'header' => __( 'Header templates', 'ajs-builder' ), 'footer' => __( 'Footer templates', 'ajs-builder' ) ) as $ajs_group => $ajs_group_label ) : ?>
					<h3 class="ajs-tpl-grouptitle"><?php echo esc_html( $ajs_group_label ); ?></h3>
					<div class="ajs-tpl-grid">
						<?php foreach ( $demos as $demo ) : ?>
							<?php if ( $demo['type'] !== $ajs_group ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<?php $already = isset( $imported[ $demo['id'] ] ) ? $imported[ $demo['id'] ] : 0; ?>
							<div class="ajs-tpl-card">
								<span class="ajs-tpl-thumb ajs-tpl-thumb--<?php echo esc_attr( $demo['type'] ); ?>" aria-hidden="true">
									<span class="ajs-tpl-thumb__bar"></span>
									<span class="ajs-tpl-thumb__row">
										<span></span><span></span><span></span><span></span>
									</span>
									<?php if ( 'footer' === $demo['type'] ) : ?>
										<span class="ajs-tpl-thumb__cols"><span></span><span></span><span></span></span>
									<?php endif; ?>
								</span>
								<h3><?php echo esc_html( $demo['title'] ); ?></h3>
								<p><?php echo esc_html( $demo['description'] ); ?></p>
								<div class="ajs-tpl-actions">
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="ajs_builder_import_demo" />
										<input type="hidden" name="demo" value="<?php echo esc_attr( $demo['id'] ); ?>" />
										<?php wp_nonce_field( 'ajs_builder_import_demo' ); ?>
										<button type="submit" class="button button-primary"><?php esc_html_e( 'Import & edit', 'ajs-builder' ); ?></button>
									</form>
									<?php if ( $already ) : ?>
										<a class="button button-small" href="<?php echo esc_url( self::editor_url( $already ) ); ?>"><?php esc_html_e( 'Open imported copy', 'ajs-builder' ); ?></a>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Import from file -->
			<div class="ajs-card">
				<h2><?php esc_html_e( 'Import a template', 'ajs-builder' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Upload a template exported from another site (.json). It is added as a new template — nothing existing is changed.', 'ajs-builder' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="ajs-import">
					<input type="hidden" name="action" value="ajs_builder_import_template" />
					<?php wp_nonce_field( 'ajs_builder_import_template' ); ?>
					<input type="file" name="template_file" accept="application/json,.json" required />
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Upload & import', 'ajs-builder' ); ?></button>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Sections (reusable blocks) list screen.
	 */
	public static function render_sections() {
		$sections = Ajs_Builder_Templates::sections();

		$notice = isset( $_GET['ajs_notice'] ) ? sanitize_key( wp_unslash( $_GET['ajs_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap ajs-admin ajs-templates">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Sections', 'ajs-builder' ); ?></h1>
			<hr class="wp-header-end" />

			<?php if ( 'imported' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Section imported.', 'ajs-builder' ); ?></p></div>
			<?php elseif ( 'import_error' === $notice ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The section could not be imported. Please check the file and try again.', 'ajs-builder' ); ?></p></div>
			<?php elseif ( 'file_error' === $notice ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The uploaded file is not a valid template export.', 'ajs-builder' ); ?></p></div>
			<?php endif; ?>

			<p class="ajs-list__intro"><?php esc_html_e( 'Reusable section templates you can insert into any page from the builder’s Templates panel. Create one here, or save a section from the Structure panel while editing a page.', 'ajs-builder' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ajs-list__new">
				<input type="hidden" name="action" value="ajs_builder_new_template" />
				<input type="hidden" name="type" value="section" />
				<?php wp_nonce_field( 'ajs_builder_new_template' ); ?>
				<input type="text" name="title" placeholder="<?php esc_attr_e( 'Section name…', 'ajs-builder' ); ?>" required />
				<button type="submit" class="page-title-action"><?php esc_html_e( 'Create & design', 'ajs-builder' ); ?></button>
			</form>

			<!-- Existing sections -->
			<div class="ajs-card">
				<h2><?php esc_html_e( 'Your sections', 'ajs-builder' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'ajs-builder' ); ?></th>
							<th><?php esc_html_e( 'Sections', 'ajs-builder' ); ?></th>
							<th><?php esc_html_e( 'Modified', 'ajs-builder' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'ajs-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! $sections ) : ?>
							<tr><td colspan="4"><?php esc_html_e( 'No sections yet. Create one above, or import one below.', 'ajs-builder' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $sections as $tpl ) : ?>
								<?php
								$export_url  = wp_nonce_url( admin_url( 'admin-post.php?action=ajs_builder_export_template&id=' . (int) $tpl->ID ), 'ajs_builder_export_template_' . (int) $tpl->ID );
								$doc         = Ajs_Builder_Document::get( $tpl->ID );
								$sec_count   = isset( $doc['sections'] ) && is_array( $doc['sections'] ) ? count( $doc['sections'] ) : 0;
								?>
								<tr>
									<td><strong><a href="<?php echo esc_url( self::editor_url( $tpl->ID ) ); ?>"><?php echo esc_html( $tpl->post_title ? $tpl->post_title : __( '(no title)', 'ajs-builder' ) ); ?></a></strong></td>
									<td><?php echo esc_html( sprintf( _n( '%d section', '%d sections', $sec_count, 'ajs-builder' ), $sec_count ) ); ?></td>
									<td><?php echo esc_html( get_the_modified_date( 'M j, Y', $tpl ) ); ?></td>
									<td>
										<a class="button button-primary button-small" href="<?php echo esc_url( self::editor_url( $tpl->ID ) ); ?>"><?php esc_html_e( 'Edit with Builder', 'ajs-builder' ); ?></a>
										<a class="button button-small" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export', 'ajs-builder' ); ?></a>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this section?', 'ajs-builder' ) ); ?>');">
											<input type="hidden" name="action" value="ajs_builder_delete_template" />
											<input type="hidden" name="id" value="<?php echo esc_attr( $tpl->ID ); ?>" />
											<?php wp_nonce_field( 'ajs_builder_delete_template_' . $tpl->ID ); ?>
											<button type="submit" class="button button-small"><?php esc_html_e( 'Delete', 'ajs-builder' ); ?></button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Import -->
			<div class="ajs-card">
				<h2><?php esc_html_e( 'Import a section', 'ajs-builder' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Upload a section exported from another site (.json). It is added as a new section — nothing existing is changed.', 'ajs-builder' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="ajs-import">
					<input type="hidden" name="action" value="ajs_builder_import_template" />
					<?php wp_nonce_field( 'ajs_builder_import_template' ); ?>
					<input type="file" name="template_file" accept="application/json,.json" required />
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Upload & import', 'ajs-builder' ); ?></button>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Create a header/footer template and open the editor.
	 */
	public static function handle_new_template() {
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'ajs-builder' ) );
		}
		check_admin_referer( 'ajs_builder_new_template' );

		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'header';
		$display = isset( $_POST['display'] ) ? sanitize_key( wp_unslash( $_POST['display'] ) ) : 'entire_site';

		if ( ! in_array( $type, array( 'header', 'footer', 'section' ), true ) ) {
			$type = 'header';
		}
		if ( ! array_key_exists( $display, Ajs_Builder_Templates::displays() ) ) {
			$display = 'entire_site';
		}
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
				'post_type'   => Ajs_Builder_Templates::POST_TYPE,
				'post_status' => 'publish',
			)
		);

		if ( is_wp_error( $id ) ) {
			wp_die( esc_html( $id->get_error_message() ) );
		}

		update_post_meta( $id, Ajs_Builder_Templates::META_TYPE, $type );
		update_post_meta(
			$id,
			Ajs_Builder_Templates::META_COND,
			array(
				'display' => 'section' === $type ? 'entire_site' : $display,
				'ids'     => array(),
			)
		);
		update_post_meta( $id, '_ajs_builder_layout', 'canvas' );

		wp_safe_redirect( self::editor_url( $id ) );
		exit;
	}

	/**
	 * Delete a header/footer template.
	 */
	public static function handle_delete_template() {
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id || Ajs_Builder_Templates::POST_TYPE !== get_post_type( $id ) ) {
			wp_die( esc_html__( 'Template not found.', 'ajs-builder' ) );
		}
		if ( ! current_user_can( 'delete_post', $id ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'ajs-builder' ) );
		}
		check_admin_referer( 'ajs_builder_delete_template_' . $id );

		$is_section = ( 'section' === Ajs_Builder_Templates::type( $id ) );
		wp_delete_post( $id, true );

		wp_safe_redirect(
			admin_url( 'admin.php?page=' . self::SLUG . ( $is_section ? '-sections' : '-templates' ) )
		);
		exit;
	}

	/**
	 * Toggle a header/footer template on or off.
	 */
	public static function handle_toggle_template() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( ! $id || Ajs_Builder_Templates::POST_TYPE !== get_post_type( $id ) ) {
			wp_die( esc_html__( 'Template not found.', 'ajs-builder' ) );
		}
		if ( ! current_user_can( 'edit_post', $id ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'ajs-builder' ) );
		}
		check_admin_referer( 'ajs_builder_toggle_template_' . $id );

		Ajs_Builder_Templates::set_enabled( $id, ! Ajs_Builder_Templates::is_enabled( $id ) );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '-templates' ) );
		exit;
	}

	/**
	 * Export a template as a downloadable JSON file.
	 */
	public static function handle_export_template() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( ! $id || Ajs_Builder_Templates::POST_TYPE !== get_post_type( $id ) ) {
			wp_die( esc_html__( 'Template not found.', 'ajs-builder' ) );
		}
		if ( ! current_user_can( 'edit_post', $id ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'ajs-builder' ) );
		}
		check_admin_referer( 'ajs_builder_export_template_' . $id );

		$json = Ajs_Builder_Templates::export_json( $id );
		if ( '' === $json ) {
			wp_die( esc_html__( 'Nothing to export.', 'ajs-builder' ) );
		}

		$slug     = sanitize_title( get_the_title( $id ) );
		$filename = ( '' !== $slug ? $slug : 'ajs-template' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $json ) );
		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw JSON download.
		exit;
	}

	/**
	 * Import a template from an uploaded JSON export.
	 */
	public static function handle_import_template() {
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'ajs-builder' ) );
		}
		check_admin_referer( 'ajs_builder_import_template' );

		if ( empty( $_FILES['template_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['template_file']['tmp_name'] ) ) {
			self::redirect_templates( 'file_error' );
		}

		$file = $_FILES['template_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		// Guard against absurdly large uploads (a template document is tiny).
		if ( isset( $file['size'] ) && (int) $file['size'] > 2 * MB_IN_BYTES ) {
			self::redirect_templates( 'import_error' );
		}

		$raw = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw ) {
			self::redirect_templates( 'file_error' );
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			self::redirect_templates( 'file_error' );
		}

		$id = Ajs_Builder_Templates::import( $data );
		if ( is_wp_error( $id ) ) {
			self::redirect_templates( 'import_error' );
		}

		wp_safe_redirect( self::editor_url( $id ) );
		exit;
	}

	/**
	 * Import one built-in demo template.
	 */
	public static function handle_import_demo() {
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'ajs-builder' ) );
		}
		check_admin_referer( 'ajs_builder_import_demo' );

		$demo = isset( $_POST['demo'] ) ? sanitize_key( wp_unslash( $_POST['demo'] ) ) : '';
		$id   = self::import_demo( $demo );
		if ( is_wp_error( $id ) ) {
			wp_die( esc_html( $id->get_error_message() ) );
		}

		wp_safe_redirect( self::editor_url( $id ) );
		exit;
	}

	/**
	 * Import every built-in demo template (skipping ones already imported).
	 */
	public static function handle_import_all_demos() {
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'ajs-builder' ) );
		}
		check_admin_referer( 'ajs_builder_import_all_demos' );

		$demos    = Ajs_Builder_Demo_Templates::all();
		$existing = array();
		foreach ( Ajs_Builder_Templates::all() as $tpl ) {
			$src = Ajs_Builder_Templates::source( $tpl->ID );
			if ( '' !== $src ) {
				$existing[ $src ] = true;
			}
		}

		foreach ( $demos as $demo ) {
			if ( isset( $existing[ $demo['id'] ] ) ) {
				continue;
			}
			self::import_demo( $demo['id'], $demo );
		}

		self::redirect_templates( 'imported_all' );
	}

	/**
	 * Create a template from a built-in demo preset.
	 *
	 * @param string     $demo_id Demo id.
	 * @param array|null $demo    Pre-resolved demo (optional).
	 * @return int|WP_Error
	 */
	private static function import_demo( $demo_id, $demo = null ) {
		if ( null === $demo ) {
			$demo = Ajs_Builder_Demo_Templates::get( $demo_id );
		}
		if ( ! $demo ) {
			return new WP_Error( 'ajs_builder_demo_missing', __( 'That demo template could not be found.', 'ajs-builder' ) );
		}

		return Ajs_Builder_Templates::create(
			array(
				'title'      => $demo['title'],
				'type'       => $demo['type'],
				'conditions' => $demo['conditions'],
				'sticky'     => ! empty( $demo['sticky'] ),
				'classes'    => isset( $demo['classes'] ) ? $demo['classes'] : '',
				'layout'     => 'canvas',
				'document'   => $demo['document'],
				'source'     => $demo['id'],
			)
		);
	}

	/**
	 * Redirect back to the templates screen with a notice flag.
	 *
	 * @param string $notice Notice key.
	 */
	private static function redirect_templates( $notice ) {
		wp_safe_redirect(
			add_query_arg(
				'ajs_notice',
				sanitize_key( $notice ),
				admin_url( 'admin.php?page=' . self::SLUG . '-templates' )
			)
		);
		exit;
	}

	/**
	 * Row action link.
	 *
	 * @param array   $actions Actions.
	 * @param WP_Post $post    Post.
	 * @return array
	 */
	public static function row_action( $actions, $post ) {
		if ( in_array( $post->post_type, Ajs_Builder_Plugin::post_types(), true ) && current_user_can( 'edit_post', $post->ID ) ) {
			$actions['ajs_builder'] = '<a href="' . esc_url( self::editor_url( $post->ID ) ) . '">' . esc_html__( 'Edit with Builder', 'ajs-builder' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Button in the classic submit box.
	 */
	public static function submitbox() {
		$post = get_post();
		if ( ! $post || ! in_array( $post->post_type, Ajs_Builder_Plugin::post_types(), true ) ) {
			return;
		}
		$on = Ajs_Builder_Document::is_enabled( $post->ID );
		?>
		<div class="misc-pub-section ajs-submitbox">
			<a class="button button-primary button-large" style="width:100%;text-align:center;" href="<?php echo esc_url( self::editor_url( $post->ID ) ); ?>">
				<?php echo $on ? esc_html__( 'Edit with AJS Builder', 'ajs-builder' ) : esc_html__( 'Build with AJS Builder', 'ajs-builder' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Handle "create & edit".
	 */
	public static function handle_new() {
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'ajs-builder' ) );
		}
		check_admin_referer( 'ajs_builder_new' );

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		if ( '' === $title ) {
			$title = __( 'Untitled', 'ajs-builder' );
		}

		$id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_type'   => 'page',
				'post_status' => 'draft',
			)
		);

		if ( is_wp_error( $id ) ) {
			wp_die( esc_html( $id->get_error_message() ) );
		}

		wp_safe_redirect( self::editor_url( $id ) );
		exit;
	}

	/**
	 * Enqueue editor assets.
	 */
	public static function enqueue() {
		if ( ! self::is_editor() ) {
			$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $page, array( self::SLUG . '-settings', self::SLUG . '-about', self::SLUG . '-templates', self::SLUG . '-sections' ), true ) ) {
				wp_enqueue_style( 'ajs-builder-settings', AJS_BUILDER_URL . 'assets/css/builder-settings.css', array(), AJS_BUILDER_VERSION );
			}
			if ( self::SLUG . '-settings' === $page ) {
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_script( 'ajs-builder-settings', AJS_BUILDER_URL . 'assets/js/builder-settings.js', array( 'jquery', 'wp-color-picker' ), AJS_BUILDER_VERSION, true );
			}
			return;
		}

		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_editor();
		$admin_css_file = AJS_BUILDER_DIR . 'assets/css/builder-admin.css';
		$admin_js_file  = AJS_BUILDER_DIR . 'assets/js/builder-admin.js';
		$admin_css_ver  = AJS_BUILDER_VERSION . '.' . ( file_exists( $admin_css_file ) ? filemtime( $admin_css_file ) : 0 );
		$admin_js_ver   = AJS_BUILDER_VERSION . '.' . ( file_exists( $admin_js_file ) ? filemtime( $admin_js_file ) : 0 );
		wp_enqueue_style( 'ajs-builder-admin', AJS_BUILDER_URL . 'assets/css/builder-admin.css', array( 'wp-color-picker', 'editor-buttons' ), $admin_css_ver );
		wp_enqueue_script( 'ajs-builder-admin', AJS_BUILDER_URL . 'assets/js/builder-admin.js', array( 'jquery', 'wp-color-picker', 'editor', 'quicktags', 'wplink' ), $admin_js_ver, true );

		$icons = array(
			'labels' => Ajs_Builder_Icons::labels(),
			'paths'  => Ajs_Builder_Icons::paths(),
		);

		wp_localize_script(
			'ajs-builder-admin',
			'ajsBuilder',
			array(
				'restUrl'      => esc_url_raw( rest_url( 'ajs-builder/v1' ) ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'postId'       => $post_id,
				'widgets'      => Ajs_Builder_Widgets::export(),
				'groups'       => Ajs_Builder_Widgets::groups(),
				'moduleIcons'  => Ajs_Builder_Icons::module_icons(),
				'icons'        => $icons,
				'elements'     => self::element_controls(),
				'advanced'     => self::advanced_controls(),
				'previewUrl'   => add_query_arg( Ajs_Builder_Frontend::PREVIEW_VAR, 'preview', get_permalink( $post_id ) ),
				'frontUrl'     => get_permalink( $post_id ),
				'adminUrl'     => admin_url(),
				'builderUrl'   => admin_url( 'admin.php?page=' . self::SLUG ),
				'canHtml'      => current_user_can( 'unfiltered_html' ),
				'isTemplate'   => ( Ajs_Builder_Templates::POST_TYPE === get_post_type( $post_id ) ),
				'i18n'         => array(
					'saved'          => __( 'All changes saved', 'ajs-builder' ),
					'saving'         => __( 'Saving…', 'ajs-builder' ),
					'unsaved'        => __( 'Unsaved changes', 'ajs-builder' ),
					'error'          => __( 'Something went wrong. Please try again.', 'ajs-builder' ),
					'modules'        => __( 'Modules', 'ajs-builder' ),
					'settings'       => __( 'Settings', 'ajs-builder' ),
					'edit'           => __( 'Edit', 'ajs-builder' ),
					'duplicate'      => __( 'Duplicate', 'ajs-builder' ),
					'delete'         => __( 'Delete', 'ajs-builder' ),
					'addSection'     => __( 'Add Section', 'ajs-builder' ),
					'addColumn'      => __( 'Add Column', 'ajs-builder' ),
					'maxColumns'     => __( 'A section can have up to %d columns.', 'ajs-builder' ),
					'deleteSection'  => __( 'Delete section?', 'ajs-builder' ),
					'deleteColumn'   => __( 'Delete column?', 'ajs-builder' ),
					'deleteWidget'   => __( 'Delete module?', 'ajs-builder' ),
					'moveLeft'       => __( 'Move column left', 'ajs-builder' ),
					'moveRight'      => __( 'Move column right', 'ajs-builder' ),
					'deviceDesktop'  => __( 'Desktop', 'ajs-builder' ),
					'deviceTablet'   => __( 'Tablet', 'ajs-builder' ),
					'deviceMobile'   => __( 'Mobile', 'ajs-builder' ),
					'responsiveHint' => __( 'You are editing %s values. Switch device in the top bar to set tablet and mobile.', 'ajs-builder' ),
					'inherit'        => __( 'Inherit', 'ajs-builder' ),
					'emptyCanvas'    => __( 'Add your first section to start building this page.', 'ajs-builder' ),
					'chooseImage'    => __( 'Choose image', 'ajs-builder' ),
					'replaceImage'   => __( 'Replace', 'ajs-builder' ),
					'removeImage'    => __( 'Remove', 'ajs-builder' ),
					'chooseIcon'     => __( 'Choose icon', 'ajs-builder' ),
					'searchIcons'    => __( 'Search icons…', 'ajs-builder' ),
					'noIconsFound'   => __( 'No icons found.', 'ajs-builder' ),
					'addItem'        => __( 'Add item', 'ajs-builder' ),
					'reset'          => __( 'Reset', 'ajs-builder' ),
					'resetColor'     => __( 'Reset to default colour', 'ajs-builder' ),
					'noModules'      => __( 'No modules match your search.', 'ajs-builder' ),
					'columnsTitle'   => __( 'Choose column layout', 'ajs-builder' ),
					'link'           => __( 'Link', 'ajs-builder' ),
					'content'        => __( 'Content', 'ajs-builder' ),
					'style'          => __( 'Style', 'ajs-builder' ),
					'advanced'       => __( 'Advanced', 'ajs-builder' ),
					'layout'         => __( 'Layout', 'ajs-builder' ),
					'addingTo'       => __( 'New modules go into the selected column.', 'ajs-builder' ),
					'newSectionHint' => __( 'New section', 'ajs-builder' ),
					'dragHint'       => __( 'Tip: drag a module onto the page, or click to add it.', 'ajs-builder' ),
					'headerTemplate' => __( 'Header template', 'ajs-builder' ),
					'footerTemplate' => __( 'Footer template', 'ajs-builder' ),
					'pagesSelected'  => __( '%d pages selected', 'ajs-builder' ),
					'pageSelected'   => __( '1 page selected', 'ajs-builder' ),
					'section'        => __( 'Section', 'ajs-builder' ),
					'column'         => __( 'Column', 'ajs-builder' ),
					'templates'      => __( 'Templates', 'ajs-builder' ),
					'saveTemplate'   => __( 'Save as template', 'ajs-builder' ),
					'templateName'   => __( 'Name this template', 'ajs-builder' ),
					'templateSaved'  => __( 'Template saved', 'ajs-builder' ),
					'insertTemplate' => __( 'Insert', 'ajs-builder' ),
					'inserted'       => __( 'Template inserted', 'ajs-builder' ),
					'export'         => __( 'Export', 'ajs-builder' ),
					'exported'       => __( 'Template exported', 'ajs-builder' ),
					'exportError'    => __( 'Could not export that template.', 'ajs-builder' ),
					'noTemplates'    => __( 'No section templates yet. Save a section from the Structure panel to reuse it here.', 'ajs-builder' ),
					'deleteTemplateConfirm' => __( 'Delete this template?', 'ajs-builder' ),
					'sectionCount'   => __( '%d sections', 'ajs-builder' ),
					'importTemplate' => __( 'Import JSON…', 'ajs-builder' ),
					'importError'    => __( 'That file is not a valid template.', 'ajs-builder' ),
					'enabled'        => __( 'Enabled', 'ajs-builder' ),
				),
			)
		);
	}

	/**
	 * Prepend an "Inherit" choice to a set of editor choices.
	 *
	 * @param array $choices Choices map.
	 * @return array
	 */
	private static function inherit_choices( array $choices ) {
		return array_merge( array( '' => __( 'Inherit', 'ajs-builder' ) ), $choices );
	}

	/**
	 * Section & column control schemas (drive the editor and match the renderer).
	 *
	 * @return array
	 */
	public static function element_controls() {
		$controls = array(
			'section' => array(
				'columns_count' => array(
					'type'    => 'select',
					'label'   => __( 'Columns', 'ajs-builder' ),
					'group'   => 'Layout',
					'default' => '1',
					'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
				),
				'content_width' => array(
					'type'    => 'choose',
					'label'   => __( 'Content width', 'ajs-builder' ),
					'group'   => 'Layout',
					'default' => Ajs_Builder_Settings::get( 'default_content_width', 'boxed' ),
					'choices' => array( 'boxed' => __( 'Boxed', 'ajs-builder' ), 'full' => __( 'Full', 'ajs-builder' ) ),
				),
				'content_custom_width' => array(
					'type'        => 'number',
					'label'       => __( 'Custom width (px)', 'ajs-builder' ),
					'group'       => 'Layout',
					'placeholder' => __( 'e.g. 1140', 'ajs-builder' ),
					'show_if'     => array( 'content_width' => 'boxed' ),
					'hint'        => __( 'Leave empty to use the site container width.', 'ajs-builder' ),
				),
				'gap'           => array(
					'type'    => 'number',
					'label'   => __( 'Columns gap (px)', 'ajs-builder' ),
					'group'   => 'Layout',
					'default' => 24,
				),
				'gap_tablet'    => array(
					'type'        => 'number',
					'label'       => __( 'Gap – tablet (px)', 'ajs-builder' ),
					'group'       => 'Layout',
					'device'      => 'tablet',
					'placeholder' => __( 'inherit', 'ajs-builder' ),
				),
				'gap_mobile'    => array(
					'type'        => 'number',
					'label'       => __( 'Gap – mobile (px)', 'ajs-builder' ),
					'group'       => 'Layout',
					'device'      => 'mobile',
					'placeholder' => __( 'inherit', 'ajs-builder' ),
				),
				'valign'        => array(
					'type'    => 'choose',
					'label'   => __( 'Vertical align', 'ajs-builder' ),
					'group'   => 'Layout',
					'default' => 'top',
					'choices' => array( 'top' => __( 'Top', 'ajs-builder' ), 'middle' => __( 'Middle', 'ajs-builder' ), 'bottom' => __( 'Bottom', 'ajs-builder' ) ),
				),
				'valign_tablet' => array(
					'type'    => 'choose',
					'label'   => __( 'Vertical align – tablet', 'ajs-builder' ),
					'group'   => 'Layout',
					'device'  => 'tablet',
					'default' => '',
					'choices' => self::inherit_choices( array( 'top' => __( 'Top', 'ajs-builder' ), 'middle' => __( 'Middle', 'ajs-builder' ), 'bottom' => __( 'Bottom', 'ajs-builder' ) ) ),
				),
				'valign_mobile' => array(
					'type'    => 'choose',
					'label'   => __( 'Vertical align – mobile', 'ajs-builder' ),
					'group'   => 'Layout',
					'device'  => 'mobile',
					'default' => '',
					'choices' => self::inherit_choices( array( 'top' => __( 'Top', 'ajs-builder' ), 'middle' => __( 'Middle', 'ajs-builder' ), 'bottom' => __( 'Bottom', 'ajs-builder' ) ) ),
				),
				'justify'       => array(
					'type'    => 'choose',
					'label'   => __( 'Horizontal align', 'ajs-builder' ),
					'group'   => 'Layout',
					'default' => 'start',
					'choices' => array( 'start' => __( 'Start', 'ajs-builder' ), 'center' => __( 'Center', 'ajs-builder' ), 'end' => __( 'End', 'ajs-builder' ), 'between' => __( 'Space between', 'ajs-builder' ), 'around' => __( 'Space around', 'ajs-builder' ), 'evenly' => __( 'Space evenly', 'ajs-builder' ) ),
				),
				'justify_tablet' => array(
					'type'    => 'choose',
					'label'   => __( 'Horizontal align – tablet', 'ajs-builder' ),
					'group'   => 'Layout',
					'device'  => 'tablet',
					'default' => '',
					'choices' => self::inherit_choices( array( 'start' => __( 'Start', 'ajs-builder' ), 'center' => __( 'Center', 'ajs-builder' ), 'end' => __( 'End', 'ajs-builder' ), 'between' => __( 'Space between', 'ajs-builder' ), 'around' => __( 'Space around', 'ajs-builder' ), 'evenly' => __( 'Space evenly', 'ajs-builder' ) ) ),
				),
				'justify_mobile' => array(
					'type'    => 'choose',
					'label'   => __( 'Horizontal align – mobile', 'ajs-builder' ),
					'group'   => 'Layout',
					'device'  => 'mobile',
					'default' => '',
					'choices' => self::inherit_choices( array( 'start' => __( 'Start', 'ajs-builder' ), 'center' => __( 'Center', 'ajs-builder' ), 'end' => __( 'End', 'ajs-builder' ), 'between' => __( 'Space between', 'ajs-builder' ), 'around' => __( 'Space around', 'ajs-builder' ), 'evenly' => __( 'Space evenly', 'ajs-builder' ) ) ),
				),
				'wrap'          => array(
					'type'    => 'choose',
					'label'   => __( 'Wrapping', 'ajs-builder' ),
					'group'   => 'Layout',
					'default' => 'wrap',
					'choices' => array( 'wrap' => __( 'Wrap', 'ajs-builder' ), 'nowrap' => __( 'No wrap', 'ajs-builder' ) ),
				),
				'wrap_tablet'   => array(
					'type'    => 'choose',
					'label'   => __( 'Wrapping – tablet', 'ajs-builder' ),
					'group'   => 'Layout',
					'device'  => 'tablet',
					'default' => '',
					'choices' => self::inherit_choices( array( 'wrap' => __( 'Wrap', 'ajs-builder' ), 'nowrap' => __( 'No wrap', 'ajs-builder' ) ) ),
				),
				'wrap_mobile'   => array(
					'type'    => 'choose',
					'label'   => __( 'Wrapping – mobile', 'ajs-builder' ),
					'group'   => 'Layout',
					'device'  => 'mobile',
					'default' => '',
					'choices' => self::inherit_choices( array( 'wrap' => __( 'Wrap', 'ajs-builder' ), 'nowrap' => __( 'No wrap', 'ajs-builder' ) ) ),
				),
				'stack_tablet'  => array(
					'type'    => 'switch',
					'label'   => __( 'Stack columns on tablet', 'ajs-builder' ),
					'group'   => 'Layout',
					'device'  => 'tablet',
					'default' => false,
					'hint'    => __( 'When off, columns stay side by side on tablet.', 'ajs-builder' ),
				),
				'stack_mobile'  => array(
					'type'    => 'switch',
					'label'   => __( 'Stack columns on mobile', 'ajs-builder' ),
					'group'   => 'Layout',
					'device'  => 'mobile',
					'default' => true,
					'hint'    => __( 'Turn off to keep 2–3 columns inline on phones.', 'ajs-builder' ),
				),
				'bg_color'      => array(
					'type'       => 'color',
					'label'      => __( 'Background colour', 'ajs-builder' ),
					'group'      => 'Style',
					'gradient'   => true,
					'responsive' => true,
				),
				'bg_image'      => array(
					'type'       => 'media',
					'label'      => __( 'Background image', 'ajs-builder' ),
					'group'      => 'Style',
					'default'    => array( 'id' => 0, 'url' => '' ),
					'responsive' => true,
				),
				'bg_opacity'    => array(
					'type'    => 'slider',
					'label'   => __( 'Background opacity (%)', 'ajs-builder' ),
					'group'   => 'Style',
					'default' => 100,
					'min'     => 0,
					'max'     => 100,
					'hint'    => __( 'Fades the background colour/image only — the section content stays fully opaque.', 'ajs-builder' ),
				),
				'bg_overlay'    => array(
					'type'  => 'color',
					'label' => __( 'Background overlay', 'ajs-builder' ),
					'group' => 'Style',
				),
				'bg_overlay_opacity' => array(
					'type'    => 'slider',
					'label'   => __( 'Overlay opacity (%)', 'ajs-builder' ),
					'group'   => 'Style',
					'default' => 50,
					'min'     => 0,
					'max'     => 100,
				),
				'text_color'    => array(
					'type'       => 'color',
					'label'      => __( 'Text colour', 'ajs-builder' ),
					'group'      => 'Style',
					'responsive' => true,
				),
				'padding'       => array(
					'type'           => 'dimensions',
					'label'          => __( 'Padding', 'ajs-builder' ),
					'group'          => 'Style',
					'responsive'     => true,
					'device_control' => true,
					'default'        => array( 'unit' => 'px', 'top' => 48, 'right' => 0, 'bottom' => 48, 'left' => 0 ),
				),
				'margin'        => array(
					'type'           => 'dimensions',
					'label'          => __( 'Margin', 'ajs-builder' ),
					'group'          => 'Advanced',
					'responsive'     => true,
					'device_control' => true,
					'default'        => array( 'unit' => 'px', 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
				),
				'css_class'     => array(
					'type'  => 'text',
					'label' => __( 'CSS class', 'ajs-builder' ),
					'group' => 'Advanced',
				),
				'css_id'        => array(
					'type'  => 'text',
					'label' => __( 'CSS ID', 'ajs-builder' ),
					'group' => 'Advanced',
				),
			),
			'column'  => array(
				'width'      => array(
					'type'        => 'number',
					'label'       => __( 'Column width (%)', 'ajs-builder' ),
					'group'       => 'Layout',
					'placeholder' => __( 'auto', 'ajs-builder' ),
				),
				'width_tablet' => array(
					'type'        => 'number',
					'label'       => __( 'Width – tablet (%)', 'ajs-builder' ),
					'group'       => 'Layout',
					'device'      => 'tablet',
					'placeholder' => __( 'inherit', 'ajs-builder' ),
				),
				'width_mobile' => array(
					'type'        => 'number',
					'label'       => __( 'Width – mobile (%)', 'ajs-builder' ),
					'group'       => 'Layout',
					'device'      => 'mobile',
					'placeholder' => __( 'inherit', 'ajs-builder' ),
				),
				'v_align'    => array(
					'type'    => 'choose',
					'label'   => __( 'Content align', 'ajs-builder' ),
					'group'   => 'Layout',
					'default' => '',
					'choices' => array( '' => __( 'Default', 'ajs-builder' ), 'top' => __( 'Top', 'ajs-builder' ), 'center' => __( 'Middle', 'ajs-builder' ), 'bottom' => __( 'Bottom', 'ajs-builder' ) ),
				),
				'v_align_tablet' => array(
					'type'    => 'choose',
					'label'   => __( 'Content align – tablet', 'ajs-builder' ),
					'group'   => 'Layout',
					'device'  => 'tablet',
					'default' => '',
					'choices' => self::inherit_choices( array( 'top' => __( 'Top', 'ajs-builder' ), 'center' => __( 'Middle', 'ajs-builder' ), 'bottom' => __( 'Bottom', 'ajs-builder' ) ) ),
				),
				'v_align_mobile' => array(
					'type'    => 'choose',
					'label'   => __( 'Content align – mobile', 'ajs-builder' ),
					'group'   => 'Layout',
					'device'  => 'mobile',
					'default' => '',
					'choices' => self::inherit_choices( array( 'top' => __( 'Top', 'ajs-builder' ), 'center' => __( 'Middle', 'ajs-builder' ), 'bottom' => __( 'Bottom', 'ajs-builder' ) ) ),
				),
				'direction'  => array(
					'type'         => 'choose',
					'label'        => __( 'Modules layout', 'ajs-builder' ),
					'group'        => 'Layout',
					'default'      => 'column',
					'responsive'   => true,
					'min_children' => 2,
					'hint'         => __( 'Stack the modules vertically or place them side by side (inline).', 'ajs-builder' ),
					'choices'      => array(
						'column' => __( 'Vertical', 'ajs-builder' ),
						'row'    => __( 'Horizontal', 'ajs-builder' ),
					),
				),
				'bg_color'   => array(
					'type'       => 'color',
					'label'      => __( 'Background colour', 'ajs-builder' ),
					'group'      => 'Style',
					'gradient'   => true,
					'responsive' => true,
				),
				'bg_image'   => array(
					'type'       => 'media',
					'label'      => __( 'Background image', 'ajs-builder' ),
					'group'      => 'Style',
					'default'    => array( 'id' => 0, 'url' => '' ),
					'responsive' => true,
				),
				'text_color' => array(
					'type'       => 'color',
					'label'      => __( 'Text colour', 'ajs-builder' ),
					'group'      => 'Style',
					'responsive' => true,
				),
				'padding'    => array(
					'type'           => 'dimensions',
					'label'          => __( 'Padding', 'ajs-builder' ),
					'group'          => 'Style',
					'responsive'     => true,
					'device_control' => true,
					'default'        => array( 'unit' => 'px', 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
				),
				'margin'     => array(
					'type'           => 'dimensions',
					'label'          => __( 'Margin', 'ajs-builder' ),
					'group'          => 'Advanced',
					'responsive'     => true,
					'device_control' => true,
					'default'        => array( 'unit' => 'px', 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
				),
				'css_class'  => array(
					'type'  => 'text',
					'label' => __( 'CSS class', 'ajs-builder' ),
					'group' => 'Advanced',
				),
				'css_id'     => array(
					'type'  => 'text',
					'label' => __( 'CSS ID', 'ajs-builder' ),
					'group' => 'Advanced',
				),
			),
		);

		foreach ( array( 'section', 'column' ) as $ajs_type ) {
			$controls[ $ajs_type ] = array_merge( $controls[ $ajs_type ], self::visibility_fields() );
		}

		return $controls;
	}

	/**
	 * Device visibility switches shared by every element.
	 *
	 * @return array
	 */
	public static function visibility_fields() {
		return array(
			'hide_desktop' => array(
				'type'    => 'switch',
				'label'   => __( 'Hide on desktop', 'ajs-builder' ),
				'group'   => 'Responsive',
				'default' => false,
			),
			'hide_tablet'  => array(
				'type'    => 'switch',
				'label'   => __( 'Hide on tablet', 'ajs-builder' ),
				'group'   => 'Responsive',
				'default' => false,
			),
			'hide_mobile'  => array(
				'type'    => 'switch',
				'label'   => __( 'Hide on mobile', 'ajs-builder' ),
				'group'   => 'Responsive',
				'default' => false,
			),
		);
	}

	/**
	 * Shared "Advanced" controls added to every widget.
	 *
	 * @return array
	 */
	public static function advanced_controls() {
		return array_merge(
			array(
				'padding'   => array(
					'type'           => 'dimensions',
					'label'          => __( 'Padding', 'ajs-builder' ),
					'group'          => 'Advanced',
					'responsive'     => true,
					'device_control' => true,
					'default'        => array( 'unit' => 'px', 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
				),
				'margin'    => array(
					'type'           => 'dimensions',
					'label'          => __( 'Margin', 'ajs-builder' ),
					'group'          => 'Advanced',
					'responsive'     => true,
					'device_control' => true,
					'default'        => array( 'unit' => 'px', 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ),
				),
				'css_class' => array(
					'type'  => 'text',
					'label' => __( 'CSS class', 'ajs-builder' ),
					'group' => 'Advanced',
				),
				'css_id'    => array(
					'type'  => 'text',
					'label' => __( 'CSS ID', 'ajs-builder' ),
					'group' => 'Advanced',
				),
				'custom_css' => array(
					'type'  => 'code',
					'label' => __( 'Custom CSS', 'ajs-builder' ),
					'group' => 'Advanced',
					'rows'  => 5,
					'hint'  => __( 'Enter custom CSS for this element. Use the selector "THIS" to target this element (e.g. THIS { color: red; }).', 'ajs-builder' ),
				),
			),
			self::visibility_fields()
		);
	}
}
