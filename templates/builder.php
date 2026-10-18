<?php
/**
 * Front-end template for builder pages (theme header + footer).
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

$ajs_builder_id = get_queried_object_id();
if ( is_front_page() && ! $ajs_builder_id ) {
	$ajs_builder_id = (int) get_option( 'page_on_front' );
}

get_header();
?>

<main id="main" class="ajs-main ajs-builder-page ajs-builder-main">
	<div class="ajs-builder-content" data-ajs-canvas>
		<?php echo Ajs_Builder_Render::document( Ajs_Builder_Document::get( $ajs_builder_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</main>

<?php
get_footer();
