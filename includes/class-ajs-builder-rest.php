<?php
/**
 * REST endpoints used by the editor.
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API.
 */
class Ajs_Builder_Rest {

	/**
	 * Namespace.
	 */
	const NS = 'ajs-builder/v1';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			self::NS,
			'/document/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'get_document' ),
					'permission_callback' => array( __CLASS__, 'can_edit' ),
					'args'                => array(
						'id' => array( 'sanitize_callback' => 'absint' ),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'save_document' ),
					'permission_callback' => array( __CLASS__, 'can_edit' ),
					'args'                => array(
						'id' => array( 'sanitize_callback' => 'absint' ),
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/render',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'render' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_rest_route(
			self::NS,
			'/templates',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'list_templates' ),
					'permission_callback' => array( __CLASS__, 'can_edit_posts' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'create_template' ),
					'permission_callback' => array( __CLASS__, 'can_edit_posts' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/templates/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( __CLASS__, 'delete_template' ),
				'permission_callback' => array( __CLASS__, 'can_edit_posts' ),
				'args'                => array(
					'id' => array( 'sanitize_callback' => 'absint' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/templates/(?P<id>\d+)/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'export_template' ),
				'permission_callback' => array( __CLASS__, 'can_edit_posts' ),
				'args'                => array(
					'id' => array( 'sanitize_callback' => 'absint' ),
				),
			)
		);
	}

	/**
	 * Permission: can the current user create/edit builder content?
	 *
	 * @return bool
	 */
	public static function can_edit_posts() {
		return current_user_can( 'edit_pages' );
	}

	/**
	 * List reusable section templates.
	 *
	 * @return WP_REST_Response
	 */
	public static function list_templates() {
		$out = array();
		foreach ( Ajs_Builder_Templates::sections() as $tpl ) {
			$doc   = Ajs_Builder_Document::get( $tpl->ID );
			$count = isset( $doc['sections'] ) && is_array( $doc['sections'] ) ? count( $doc['sections'] ) : 0;
			$out[] = array(
				'id'       => (int) $tpl->ID,
				'title'    => $tpl->post_title ? $tpl->post_title : __( '(no title)', 'ajs-builder' ),
				'sections' => $count,
				'enabled'  => Ajs_Builder_Templates::is_enabled( $tpl->ID ),
			);
		}
		return new WP_REST_Response( array( 'templates' => $out ), 200 );
	}

	/**
	 * Save a set of sections as a reusable template.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_template( $request ) {
		$body     = $request->get_json_params();
		$title    = isset( $body['title'] ) ? sanitize_text_field( (string) $body['title'] ) : '';
		$sections = isset( $body['sections'] ) && is_array( $body['sections'] ) ? $body['sections'] : array();

		if ( ! $sections ) {
			return new WP_Error( 'ajs_builder_template_empty', __( 'There is nothing to save.', 'ajs-builder' ), array( 'status' => 400 ) );
		}

		$clean = Ajs_Builder_Document::sanitize( array( 'sections' => $sections ) );
		if ( empty( $clean['sections'] ) ) {
			return new WP_Error( 'ajs_builder_template_empty', __( 'There is nothing to save.', 'ajs-builder' ), array( 'status' => 400 ) );
		}

		$id = Ajs_Builder_Templates::create_section( $title, $clean['sections'] );
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		return new WP_REST_Response(
			array(
				'id'       => (int) $id,
				'title'    => get_the_title( $id ),
				'sections' => count( $clean['sections'] ),
			),
			200
		);
	}

	/**
	 * Export a reusable section template as a portable payload.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function export_template( $request ) {
		$id = (int) $request['id'];
		if ( ! $id || Ajs_Builder_Templates::POST_TYPE !== get_post_type( $id ) ) {
			return new WP_Error( 'ajs_builder_template_missing', __( 'Template not found.', 'ajs-builder' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $id ) ) {
			return new WP_Error( 'ajs_builder_forbidden', __( 'You are not allowed to do this.', 'ajs-builder' ), array( 'status' => 403 ) );
		}

		$payload = Ajs_Builder_Templates::export( $id );
		if ( ! is_array( $payload ) ) {
			return new WP_Error( 'ajs_builder_template_missing', __( 'Nothing to export.', 'ajs-builder' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( $payload, 200 );
	}

	/**
	 * Delete a reusable section template.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function delete_template( $request ) {
		$id = (int) $request['id'];
		if ( ! $id || Ajs_Builder_Templates::POST_TYPE !== get_post_type( $id ) ) {
			return new WP_Error( 'ajs_builder_template_missing', __( 'Template not found.', 'ajs-builder' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'delete_post', $id ) ) {
			return new WP_Error( 'ajs_builder_forbidden', __( 'You cannot delete this template.', 'ajs-builder' ), array( 'status' => 403 ) );
		}
		wp_delete_post( $id, true );
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * Permission: can the current user edit the post?
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public static function can_edit( $request ) {
		$id = (int) $request['id'];
		if ( $id && current_user_can( 'edit_post', $id ) ) {
			return true;
		}
		return new WP_Error( 'ajs_builder_forbidden', __( 'You cannot edit this content.', 'ajs-builder' ), array( 'status' => 403 ) );
	}

	/**
	 * GET a document.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_document( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );

		$template = null;
		if ( Ajs_Builder_Templates::POST_TYPE === get_post_type( $id ) ) {
			$cond     = Ajs_Builder_Templates::conditions( $id );
			$template = array(
				'type'    => Ajs_Builder_Templates::type( $id ),
				'display' => $cond['display'],
				'ids'     => $cond['ids'],
				'sticky'  => Ajs_Builder_Templates::sticky( $id ),
				'classes' => Ajs_Builder_Templates::classes( $id ),
				'enabled' => Ajs_Builder_Templates::is_enabled( $id ),
			);
		}

		return new WP_REST_Response(
			array(
				'id'       => $id,
				'title'    => $post ? $post->post_title : '',
				'data'     => Ajs_Builder_Document::get( $id ),
				'enabled'  => Ajs_Builder_Document::is_enabled( $id ),
				'layout'   => get_post_meta( $id, '_ajs_builder_layout', true ),
				'template' => $template,
				'permalink' => $post ? get_permalink( $id ) : '',
			),
			200
		);
	}

	/**
	 * POST a document.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function save_document( $request ) {
		$id   = (int) $request['id'];
		$body = $request->get_json_params();
		$data = isset( $body['data'] ) ? $body['data'] : array();
		$on   = isset( $body['enabled'] ) ? (bool) $body['enabled'] : true;

		$clean = Ajs_Builder_Document::save( $id, $data, $on );

		if ( isset( $body['title'] ) ) {
			$title = sanitize_text_field( $body['title'] );
			wp_update_post(
				array(
					'ID'         => $id,
					'post_title' => $title,
				)
			);
		}

		if ( isset( $body['layout'] ) ) {
			$layout = ( 'canvas' === $body['layout'] ) ? 'canvas' : 'default';
			update_post_meta( $id, '_ajs_builder_layout', $layout );
		}

		if ( Ajs_Builder_Templates::POST_TYPE === get_post_type( $id ) ) {
			// A section template keeps its type; only header/footer templates
			// can be switched between the two.
			if ( isset( $body['template_type'] ) && 'section' !== Ajs_Builder_Templates::type( $id ) ) {
				$type = ( 'footer' === $body['template_type'] ) ? 'footer' : 'header';
				update_post_meta( $id, Ajs_Builder_Templates::META_TYPE, $type );
			}
			if ( isset( $body['template_enabled'] ) ) {
				Ajs_Builder_Templates::set_enabled( $id, (bool) $body['template_enabled'] );
			}
			if ( isset( $body['template_conditions'] ) && is_array( $body['template_conditions'] ) ) {
				update_post_meta( $id, Ajs_Builder_Templates::META_COND, Ajs_Builder_Templates::sanitize_conditions( $body['template_conditions'] ) );
			}
			if ( isset( $body['template_sticky'] ) ) {
				update_post_meta( $id, Ajs_Builder_Templates::META_STICKY, $body['template_sticky'] ? 1 : 0 );
			}
			if ( isset( $body['template_classes'] ) ) {
				update_post_meta( $id, Ajs_Builder_Templates::META_CLASSES, Ajs_Builder_Templates::sanitize_classes( $body['template_classes'] ) );
			}
		}

		return new WP_REST_Response(
			array(
				'ok'       => true,
				'data'     => $clean,
				'enabled'  => $on,
				'saved_at' => current_time( 'mysql' ),
			),
			200
		);
	}

	/**
	 * POST a document for rendering (editor live preview).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function render( $request ) {
		$body  = $request->get_json_params();
		$raw   = isset( $body['data'] ) ? $body['data'] : array();
		$clean = Ajs_Builder_Document::sanitize( $raw );
		$html  = Ajs_Builder_Render::document( $clean );

		if ( '' === trim( $html ) ) {
			$html = '<div class="ajs-b-empty-section">' . esc_html__( 'Add a section to start building.', 'ajs-builder' ) . '</div>';
		}

		return new WP_REST_Response( array( 'html' => $html ), 200 );
	}
}
