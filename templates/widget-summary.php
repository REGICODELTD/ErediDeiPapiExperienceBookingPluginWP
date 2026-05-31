<?php
/**
 * On-page booking summary + "Prenota" button.
 *
 * @package ErediExperienceBooking
 * @var \ErediExperienceBooking\ProductType\ExperienceProduct $product
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$edp_tiers    = $product->get_price_tiers();
$edp_duration = $product->get_duration();
$edp_min      = $product->get_min_persons();
$edp_max      = $product->get_max_persons();
$edp_classes  = trim( 'edp-widget ' . ( isset( $args['wrapper_class'] ) ? $args['wrapper_class'] : '' ) );
$edp_suffix   = isset( $args['price_suffix'] ) ? $args['price_suffix'] : '';

/**
 * Human label for a tier's pax range.
 *
 * @param array $tier Tier.
 * @return string
 */
$edp_tier_label = static function ( $tier ) {
	$min = (int) $tier['min'];
	$max = (int) $tier['max'];
	if ( 0 === $max ) {
		/* translators: %d: min people */
		return sprintf( __( '%d+ pax', 'eredi-experience-booking' ), $min );
	}
	if ( $min === $max ) {
		/* translators: %d: people */
		return sprintf( __( '%d pax', 'eredi-experience-booking' ), $min );
	}
	/* translators: 1: min people, 2: max people */
	return sprintf( __( '%1$d–%2$d pax', 'eredi-experience-booking' ), $min, $max );
};
?>
<div class="<?php echo esc_attr( $edp_classes ); ?>" data-experience="<?php echo esc_attr( $product->get_id() ); ?>">

	<?php if ( ! empty( $args['show_price'] ) && ! empty( $edp_tiers ) ) : ?>
		<div class="edp-price">
			<?php if ( count( $edp_tiers ) > 1 ) : ?>
				<table class="edp-tier-table">
					<?php foreach ( $edp_tiers as $edp_tier ) : ?>
						<tr>
							<th class="edp-tier-label"><?php echo esc_html( $edp_tier_label( $edp_tier ) ); ?></th>
							<td class="edp-tier-price">
								<span class="edp-price-amount"><?php echo wp_kses_post( wc_price( $edp_tier['price'] ) ); ?></span>
								<?php if ( '' !== $edp_suffix ) : ?>
									<span class="edp-price-suffix"><?php echo esc_html( $edp_suffix ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php else : ?>
				<?php if ( ! empty( $args['price_prefix'] ) ) : ?>
					<span class="edp-price-prefix"><?php echo esc_html( $args['price_prefix'] ); ?></span>
				<?php endif; ?>
				<span class="edp-price-amount"><?php echo wp_kses_post( wc_price( $edp_tiers[0]['price'] ) ); ?></span>
				<?php if ( '' !== $edp_suffix ) : ?>
					<span class="edp-price-suffix"><?php echo esc_html( $edp_suffix ); ?></span>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $edp_duration ) : ?>
		<div class="edp-duration">
			<span class="edp-duration-label"><?php esc_html_e( 'Durata:', 'eredi-experience-booking' ); ?></span>
			<span class="edp-duration-value"><?php echo esc_html( $edp_duration ); ?></span>
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
				printf( esc_html__( 'Da %d persone in su', 'eredi-experience-booking' ), (int) $edp_min );
			}
			?>
		</div>
	<?php endif; ?>

	<button type="button" class="edp-book-btn" data-experience-id="<?php echo esc_attr( $product->get_id() ); ?>">
		<?php echo esc_html( $args['button_text'] ); ?>
	</button>
</div>
