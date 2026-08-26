<?php
/**
 * Preset filter - Term options
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Templates\Admin
 * @version 4.0.0
 */

/**
 * Variables available for this template:
 *
 * @var $id int
 * @var $taxonomy string
 * @var $terms array
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly
?>

<div class="terms-wrapper" data-terms="<?php echo wc_esc_json( wp_json_encode( array_values( $terms ) ) ); ?>" >
	<?php
	if ( ! empty( $terms ) ) :
		$counter = 0;
		foreach ( $terms as $term_options ) :
			YITH_WCAN()->admin->filter_term_field( $id, $term_options['term_id'], $term_options );

			if ( ++ $counter >= YITH_WCAN_Presets::TERMS_PER_PAGE ) {
				break;
			}
		endforeach;
	endif;
	?>
</div>

<?php
if ( count( $terms ) > YITH_WCAN_Presets::TERMS_PER_PAGE ) :
	?>
	<a class="show-more-terms" role="button">
		<?php echo esc_html( _x( 'Show more', '[FRONTEND] Show more link on tax filters', 'yith-woocommerce-ajax-navigation' ) ); ?>
	</a>
	<?php
endif;
