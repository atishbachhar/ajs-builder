<?php
/**
 * Front-end template for builder pages using the blank "canvas" layout
 * (no theme header/footer, nothing but wp_head/wp_footer).
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

$ajs_builder_id = get_queried_object_id();
if ( is_front_page() && ! $ajs_builder_id ) {
	$ajs_builder_id = (int) get_option( 'page_on_front' );
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'ajs-builder-canvas' ); ?>>
<?php wp_body_open(); ?>

<main id="main" class="ajs-main ajs-builder-page ajs-builder-page--canvas">
	<div class="ajs-builder-content" data-ajs-canvas>
		<?php echo Ajs_Builder_Render::document( Ajs_Builder_Document::get( $ajs_builder_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</main>

<?php wp_footer(); ?>
</body>
</html>
