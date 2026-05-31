<?php
/**
 * On-page booking summary + "Prenota" button.
 *
 * @package ErediExperienceBooking
 * @var \ErediExperienceBooking\ProductType\ExperienceProduct $product
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$edp_price   = (float) $product->get_price_per_person();
$edp_min     = $product->get_min_persons();
$edp_max     = $product->get_max_persons();
$edp_classes = trim( 'edp-widget ' . ( isset( $args['wrapper_class'] ) ? $args['wrapper_class'] : '' ) );
?>
<div class="<?php echo esc_attr( $edp_classes ); ?>" data-experience="<?php echo esc_attr( $product->get_id() ); ?>">

	<?php if ( ! empty( $args['show_price'] ) && $edp_price > 0 ) : ?>
		<div class="edp-price">
			<?php if ( ! empty( $args['price_prefix'] ) ) : ?>
				<span class="edp-price-prefix"><?php echo esc_html( $args['price_prefix'] ); ?></span>
			<?php endif; ?>
			<span class="edp-price-amount"><?php echo wp_kses_post( wc_price( $edp_price ) ); ?></span>
			<?php if ( ! empty( $args['price_suffix'] ) ) : ?>
				<span class="edp-price-suffix"><?php echo esc_html( $args['price_suffix'] ); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $args['show_persons'] ) ) : ?>
		<div class="edp-persons-note">
			<?php
			if ( $edp_max > 0 ) {
				/* translators: 1: min people, 2: max people */
				printf( esc_html__( 'Da %1$d a %2$d persone', 'eredi-experience-booking' ), (int) $edp_min, (int) $edp_max );
			} else {
				/* translators: %d: min people */
				printf( esc_html__( 'Minimo %d persone', 'eredi-experience-booking' ), (int) $edp_min );
			}
			?>
		</div>
	<?php endif; ?>

	<button type="button" class="edp-book-btn" data-experience-id="<?php echo esc_attr( $product->get_id() ); ?>">
		<?php echo esc_html( $args['button_text'] ); ?>
	</button>
</div>
