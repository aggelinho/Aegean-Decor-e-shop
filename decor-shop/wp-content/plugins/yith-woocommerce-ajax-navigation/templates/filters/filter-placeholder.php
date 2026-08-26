<?php
/**
 * Filter Placeholder Template
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Templates\Filters
 * @version 4.16.0
 */

/**
 * Variables available for this template:
 *
 * @var $preset YITH_WCAN_Preset
 * @var $filter YITH_WCAN_Filter_Tax
 * @var $term WP_Term
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

$filter_type   = $filter->get_type();
$filter_design = $filter->get_filter_design();
?>


<div
	class="yith-wcan-filter <?php echo esc_attr( $filter->get_additional_classes() ); ?>"
	id="filter_<?php echo esc_attr( $preset->get_id() ); ?>_<?php echo esc_attr( $filter->get_id() ); ?>"
	data-filter-type="<?php echo esc_attr( $filter_type ); ?>"
	data-filter-id="<?php echo esc_attr( $filter->get_id() ); ?>"
>
	<?php echo $filter->render_title(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<div class="filter-content">
	<?php
	if ( 'price_slider' === $filter_type ) :
		?>
		<span class="price-slider slider-placeholder">
			<span class="irs-handle from"></span>
			<span class="item-placeholder"></span>
			<span class="irs-handle to"></span>
		</span>
		<?php
	elseif ( 'select' === $filter_design ) :
		?>
		<span class="select-placeholder"></span>
		<?php
	else :
		?>
		<ul class="filter-items">
			<?php for ( $i = 0; $i < 4; $i++ ) : ?>
			<li class="filter-item">
				<?php if ( 'tax' !== $filter_type || 'checkbox' === $filter_design ) : ?>
					<input type="checkbox" disabled>
				<?php elseif ( 'radio' === $filter_design ) : ?>
					<input type="radio" disabled>
				<?php endif; ?>

				<span class="item-placeholder"></span>
			</li>
			<?php endfor; ?>
		</ul>
		<?php
	endif;
	?>
	</div>
</div>
