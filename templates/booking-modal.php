<?php
/**
 * Shared booking modal markup (populated per experience by JS).
 *
 * @package ErediExperienceBooking
 */

defined( 'ABSPATH' ) || exit;

$edp_privacy_url = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
?>
<div class="edp-modal" id="edp-modal" aria-hidden="true">
	<div class="edp-modal__overlay" data-edp-close></div>

	<div class="edp-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="edp-modal-title">
		<button type="button" class="edp-modal__close" data-edp-close aria-label="<?php esc_attr_e( 'Chiudi', 'eredi-experience-booking' ); ?>">&times;</button>

		<h3 class="edp-modal__title" id="edp-modal-title"></h3>

		<form class="edp-form" novalidate>
			<input type="hidden" name="experience_id" value="" />

			<!-- Honeypot (hidden from humans). -->
			<div class="edp-hp" aria-hidden="true">
				<label><?php esc_html_e( 'Lascia vuoto questo campo', 'eredi-experience-booking' ); ?>
					<input type="text" name="edp_hp" tabindex="-1" autocomplete="off" />
				</label>
			</div>

			<div class="edp-grid">
				<div class="edp-f edp-f--persons">
					<label for="edp-persons"><?php esc_html_e( 'Numero di persone', 'eredi-experience-booking' ); ?></label>
					<div class="edp-stepper">
						<button type="button" class="edp-step" data-edp-step="-1" aria-label="-">&minus;</button>
						<input type="number" id="edp-persons" name="persons" min="1" step="1" inputmode="numeric" required />
						<button type="button" class="edp-step" data-edp-step="1" aria-label="+">&plus;</button>
					</div>
					<small class="edp-hint" data-edp-persons-hint></small>
				</div>

				<div class="edp-f">
					<label for="edp-date"><?php esc_html_e( 'Data', 'eredi-experience-booking' ); ?></label>
					<input type="date" id="edp-date" name="date" required />
				</div>

				<div class="edp-f">
					<label for="edp-slot"><?php esc_html_e( 'Orario', 'eredi-experience-booking' ); ?></label>
					<select id="edp-slot" name="slot" required>
						<option value=""><?php esc_html_e( 'Seleziona prima una data', 'eredi-experience-booking' ); ?></option>
					</select>
				</div>
			</div>

			<div class="edp-upsells" data-edp-upsells></div>

			<fieldset class="edp-fieldset">
				<legend><?php esc_html_e( 'I tuoi dati', 'eredi-experience-booking' ); ?></legend>
				<div class="edp-grid">
					<div class="edp-f">
						<label for="edp-first"><?php esc_html_e( 'Nome', 'eredi-experience-booking' ); ?></label>
						<input type="text" id="edp-first" name="first_name" autocomplete="given-name" required />
					</div>
					<div class="edp-f">
						<label for="edp-last"><?php esc_html_e( 'Cognome', 'eredi-experience-booking' ); ?></label>
						<input type="text" id="edp-last" name="last_name" autocomplete="family-name" required />
					</div>
					<div class="edp-f">
						<label for="edp-email"><?php esc_html_e( 'Email', 'eredi-experience-booking' ); ?></label>
						<input type="email" id="edp-email" name="email" autocomplete="email" required />
					</div>
					<div class="edp-f">
						<label for="edp-phone"><?php esc_html_e( 'Telefono', 'eredi-experience-booking' ); ?></label>
						<input type="tel" id="edp-phone" name="phone" autocomplete="tel" required />
					</div>
				</div>
			</fieldset>

			<label class="edp-privacy">
				<input type="checkbox" name="privacy" value="1" required />
				<span>
					<?php
					if ( $edp_privacy_url ) {
						printf(
							/* translators: %s: privacy policy URL */
							wp_kses_post( __( 'Ho letto e accetto l’<a href="%s" target="_blank" rel="noopener">informativa privacy</a>.', 'eredi-experience-booking' ) ),
							esc_url( $edp_privacy_url )
						);
					} else {
						esc_html_e( 'Ho letto e accetto l’informativa privacy.', 'eredi-experience-booking' );
					}
					?>
				</span>
			</label>

			<div class="edp-perperson">
				<span class="edp-perperson-label"><?php esc_html_e( 'Prezzo a persona', 'eredi-experience-booking' ); ?></span>
				<span class="edp-perperson-amount" data-edp-perperson>&mdash;</span>
			</div>

			<div class="edp-total">
				<span class="edp-total-label"><?php esc_html_e( 'Totale', 'eredi-experience-booking' ); ?></span>
				<span class="edp-total-amount" data-edp-total>&mdash;</span>
			</div>

			<div class="edp-message" data-edp-message role="alert" aria-live="polite"></div>

			<button type="submit" class="edp-submit"><?php esc_html_e( 'Invia richiesta di prenotazione', 'eredi-experience-booking' ); ?></button>
		</form>

		<div class="edp-success" data-edp-success hidden>
			<div class="edp-success__icon" aria-hidden="true">&#10003;</div>
			<p class="edp-success__text" data-edp-success-text></p>
			<button type="button" class="edp-submit" data-edp-close><?php esc_html_e( 'Chiudi', 'eredi-experience-booking' ); ?></button>
		</div>
	</div>
</div>
