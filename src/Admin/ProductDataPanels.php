<?php
/**
 * Product editor panels for the "experience" product type.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Admin;

use ErediExperienceBooking\ProductType\ExperienceProduct;
use ErediExperienceBooking\Support\Experiences;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the Esperienza / Disponibilità / Upsell tabs and renders their fields.
 */
class ProductDataPanels {

	/**
	 * Italian weekday labels indexed 0=Mon..6=Sun.
	 *
	 * @return string[]
	 */
	private function weekday_labels() {
		return array(
			__( 'Lunedì', 'eredi-experience-booking' ),
			__( 'Martedì', 'eredi-experience-booking' ),
			__( 'Mercoledì', 'eredi-experience-booking' ),
			__( 'Giovedì', 'eredi-experience-booking' ),
			__( 'Venerdì', 'eredi-experience-booking' ),
			__( 'Sabato', 'eredi-experience-booking' ),
			__( 'Domenica', 'eredi-experience-booking' ),
		);
	}

	/**
	 * Hook into the product editor.
	 */
	public function register() {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_tabs' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_panels' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register product data tabs.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public function add_tabs( $tabs ) {
		$classes = array( 'show_if_experience', 'edp-show-if-experience' );

		$tabs['edp_experience'] = array(
			'label'    => __( 'Esperienza', 'eredi-experience-booking' ),
			'target'   => 'edp_experience_data',
			'class'    => $classes,
			'priority' => 8,
		);
		$tabs['edp_availability'] = array(
			'label'    => __( 'Disponibilità', 'eredi-experience-booking' ),
			'target'   => 'edp_availability_data',
			'class'    => $classes,
			'priority' => 9,
		);
		$tabs['edp_upsells'] = array(
			'label'    => __( 'Upsell & Allestimenti', 'eredi-experience-booking' ),
			'target'   => 'edp_upsells_data',
			'class'    => $classes,
			'priority' => 11,
		);

		return $tabs;
	}

	/**
	 * Enqueue admin assets on the product editor only.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( 'edp-admin', EDP_URL . 'assets/css/admin-experience.css', array(), EDP_VERSION );
		wp_enqueue_script( 'edp-admin', EDP_URL . 'assets/js/admin-experience.js', array( 'jquery' ), EDP_VERSION, true );

		wp_localize_script(
			'edp-admin',
			'edpAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'edp_copy_settings' ),
				'i18n'    => array(
					'selectSource'  => __( 'Seleziona un’esperienza sorgente.', 'eredi-experience-booking' ),
					'selectSection' => __( 'Seleziona almeno una sezione da copiare.', 'eredi-experience-booking' ),
					'confirm'       => __( 'Copiare le impostazioni selezionate? Le sezioni scelte di questo prodotto verranno sovrascritte e la scheda verrà ricaricata. Salva prima eventuali altre modifiche.', 'eredi-experience-booking' ),
					'error'         => __( 'Errore durante la copia. Riprova.', 'eredi-experience-booking' ),
				),
			)
		);
	}

	/**
	 * Render the three custom panels.
	 */
	public function render_panels() {
		global $post;
		$product = $post ? wc_get_product( $post->ID ) : null;
		$product = ( $product instanceof ExperienceProduct ) ? $product : null;

		$duration = $product ? $product->get_duration() : '';
		$tiers    = $product ? $product->get_price_tiers() : array();
		$avail    = $product ? $product->get_availability_config() : array();
		$upsells  = $product ? $product->get_upsells_config() : array();

		$this->render_experience_panel( $duration, $tiers );
		$this->render_availability_panel( $avail );
		$this->render_upsells_panel( $upsells );
		$this->render_templates();
	}

	/**
	 * "Copy from another experience" box (top of the Esperienza tab).
	 * Hidden when there is no other experience to copy from.
	 */
	private function render_copy_panel() {
		global $post;
		$current_id = $post ? (int) $post->ID : 0;
		$options    = Experiences::options( $current_id, array( 'publish', 'draft', 'private' ) );
		if ( empty( $options ) ) {
			return;
		}

		$sections = array(
			'pricing'      => __( 'Prezzo, durata e persone', 'eredi-experience-booking' ),
			'availability' => __( 'Disponibilità', 'eredi-experience-booking' ),
			'upsells'      => __( 'Upsell & allestimenti', 'eredi-experience-booking' ),
		);
		?>
		<div class="options_group edp-copy-box">
			<p class="edp-copy-title"><strong><?php esc_html_e( 'Copia da un’altra esperienza', 'eredi-experience-booking' ); ?></strong></p>

			<p class="form-field">
				<select class="edp-copy-source" data-edp-copy-source>
					<option value=""><?php esc_html_e( '— Seleziona esperienza —', 'eredi-experience-booking' ); ?></option>
					<?php foreach ( $options as $id => $label ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<p class="form-field edp-copy-sections">
				<?php foreach ( $sections as $key => $label ) : ?>
					<label><input type="checkbox" data-edp-copy-section value="<?php echo esc_attr( $key ); ?>" checked /> <?php echo esc_html( $label ); ?></label>
				<?php endforeach; ?>
			</p>

			<p class="form-field">
				<button type="button" class="button button-secondary" data-edp-copy><?php esc_html_e( 'Copia impostazioni', 'eredi-experience-booking' ); ?></button>
				<span class="description"><?php esc_html_e( 'Sovrascrive le sezioni selezionate di questo prodotto e ricarica la scheda.', 'eredi-experience-booking' ); ?></span>
			</p>
		</div>
		<?php
	}

	/**
	 * Esperienza tab: duration + per-person price tiers (min/max derived from tiers).
	 *
	 * @param string $duration Free-text duration.
	 * @param array  $tiers    Price tiers.
	 */
	private function render_experience_panel( $duration, array $tiers ) {
		?>
		<div id="edp_experience_data" class="panel woocommerce_options_panel edp-show-if-experience">
			<?php $this->render_copy_panel(); ?>

			<div class="options_group">
				<?php
				woocommerce_wp_text_input(
					array(
						'id'          => '_edp_duration',
						'value'       => $duration,
						'label'       => __( 'Durata', 'eredi-experience-booking' ),
						'placeholder' => __( 'es. 2 ore', 'eredi-experience-booking' ),
						'desc_tip'    => true,
						'description' => __( 'Durata indicativa dell’esperienza (testo libero), mostrata nel widget.', 'eredi-experience-booking' ),
					)
				);
				?>
			</div>

			<div class="options_group">
				<p class="edp-block-desc"><?php esc_html_e( 'Definisci il prezzo a persona per scaglioni di numero di persone. Il minimo e il massimo prenotabili sono derivati dagli scaglioni: l’ultimo scaglione senza valore in "A" significa "nessun limite" (es. 6+).', 'eredi-experience-booking' ); ?></p>
				<span class="edp-block-title"><?php esc_html_e( 'Scaglioni di prezzo (a persona)', 'eredi-experience-booking' ); ?></span>
				<div class="edp-tiers" data-edp-rows>
					<?php
					foreach ( $tiers as $i => $tier ) {
						$this->render_tier_row( $i, $tier );
					}
					?>
				</div>
				<button type="button" class="button edp-add-tier" data-edp-add-tier>
					<?php esc_html_e( '+ Aggiungi scaglione', 'eredi-experience-booking' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single price-tier row.
	 *
	 * @param mixed $index Row index (numeric or placeholder).
	 * @param array $tier  Tier values.
	 */
	private function render_tier_row( $index, $tier = array() ) {
		$min   = isset( $tier['min'] ) && (int) $tier['min'] > 0 ? (int) $tier['min'] : '';
		$max   = isset( $tier['max'] ) && (int) $tier['max'] > 0 ? (int) $tier['max'] : '';
		$price = isset( $tier['price'] ) ? $tier['price'] : '';
		$base  = '_edp_price_tiers[' . $index . ']';
		?>
		<div class="edp-window-row edp-tier-row" data-edp-row>
			<label class="edp-field"><span><?php esc_html_e( 'Da (persone)', 'eredi-experience-booking' ); ?></span>
				<input type="number" min="1" step="1" name="<?php echo esc_attr( $base ); ?>[min]" value="<?php echo esc_attr( $min ); ?>" />
			</label>
			<label class="edp-field"><span><?php esc_html_e( 'A (persone)', 'eredi-experience-booking' ); ?></span>
				<input type="number" min="0" step="1" name="<?php echo esc_attr( $base ); ?>[max]" value="<?php echo esc_attr( $max ); ?>" placeholder="+" />
			</label>
			<label class="edp-field"><span><?php esc_html_e( 'Prezzo a persona', 'eredi-experience-booking' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</span>
				<input type="text" class="wc_input_price" name="<?php echo esc_attr( $base ); ?>[price]" value="<?php echo esc_attr( '' !== $price ? wc_format_localized_price( $price ) : '' ); ?>" />
			</label>
			<button type="button" class="button edp-remove" data-edp-remove title="<?php esc_attr_e( 'Rimuovi', 'eredi-experience-booking' ); ?>">&times;</button>
		</div>
		<?php
	}

	/**
	 * Disponibilità tab.
	 *
	 * @param array $avail Availability config.
	 */
	private function render_availability_panel( array $avail ) {
		$mode           = isset( $avail['mode'] ) ? $avail['mode'] : 'always';
		$event_date     = isset( $avail['event_date'] ) ? $avail['event_date'] : '';
		$range_start    = isset( $avail['range_start'] ) ? $avail['range_start'] : '';
		$range_end      = isset( $avail['range_end'] ) ? $avail['range_end'] : '';
		$min_lead       = isset( $avail['min_lead_hours'] ) ? (int) $avail['min_lead_hours'] : 0;
		$blackout       = isset( $avail['blackout_dates'] ) && is_array( $avail['blackout_dates'] ) ? $avail['blackout_dates'] : array();
		$event_windows  = isset( $avail['event_windows'] ) && is_array( $avail['event_windows'] ) ? $avail['event_windows'] : array();
		$weekly         = isset( $avail['weekly'] ) && is_array( $avail['weekly'] ) ? $avail['weekly'] : array();
		?>
		<div id="edp_availability_data" class="panel woocommerce_options_panel edp-show-if-experience">
			<div class="options_group">
				<p class="form-field">
					<label for="_edp_av_mode"><?php esc_html_e( 'Modalità disponibilità', 'eredi-experience-booking' ); ?></label>
					<select id="_edp_av_mode" name="_edp_availability[mode]" class="select short" data-edp-mode>
						<option value="event" <?php selected( $mode, 'event' ); ?>><?php esc_html_e( 'Evento (data singola)', 'eredi-experience-booking' ); ?></option>
						<option value="always" <?php selected( $mode, 'always' ); ?>><?php esc_html_e( 'Sempre disponibile (per giorno della settimana)', 'eredi-experience-booking' ); ?></option>
						<option value="range" <?php selected( $mode, 'range' ); ?>><?php esc_html_e( 'Intervallo di date (dal… al…)', 'eredi-experience-booking' ); ?></option>
					</select>
				</p>
			</div>

			<!-- EVENT -->
			<div class="options_group edp-mode-block" data-edp-mode-block="event">
				<p class="form-field">
					<label for="_edp_event_date"><?php esc_html_e( 'Data evento', 'eredi-experience-booking' ); ?></label>
					<input type="date" id="_edp_event_date" name="_edp_availability[event_date]" value="<?php echo esc_attr( $event_date ); ?>" />
				</p>
				<div class="edp-windows-block">
					<strong class="edp-block-title"><?php esc_html_e( 'Fasce orarie dell’evento', 'eredi-experience-booking' ); ?></strong>
					<div class="edp-windows" data-edp-rows>
						<?php
						foreach ( $event_windows as $i => $window ) {
							$this->render_window_row( '_edp_availability[event_windows]', $i, $window );
						}
						?>
					</div>
					<button type="button" class="button edp-add-window" data-edp-add-window data-name-prefix="_edp_availability[event_windows]">
						<?php esc_html_e( '+ Aggiungi fascia oraria', 'eredi-experience-booking' ); ?>
					</button>
				</div>
			</div>

			<!-- RANGE bounds -->
			<div class="options_group edp-mode-block" data-edp-mode-block="range">
				<p class="form-field">
					<label for="_edp_range_start"><?php esc_html_e( 'Disponibile dal', 'eredi-experience-booking' ); ?></label>
					<input type="date" id="_edp_range_start" name="_edp_availability[range_start]" value="<?php echo esc_attr( $range_start ); ?>" />
				</p>
				<p class="form-field">
					<label for="_edp_range_end"><?php esc_html_e( 'Disponibile al', 'eredi-experience-booking' ); ?></label>
					<input type="date" id="_edp_range_end" name="_edp_availability[range_end]" value="<?php echo esc_attr( $range_end ); ?>" />
				</p>
			</div>

			<!-- WEEKLY (always + range) -->
			<div class="options_group edp-mode-block" data-edp-mode-block="weekly">
				<p class="edp-block-desc"><?php esc_html_e( 'Configura le fasce orarie per ciascun giorno della settimana. I giorni non spuntati sono chiusi.', 'eredi-experience-booking' ); ?></p>
				<?php
				foreach ( $this->weekday_labels() as $wd => $label ) {
					$day      = isset( $weekly[ $wd ] ) ? $weekly[ $wd ] : array();
					$is_open  = ! empty( $day['open'] );
					$windows  = isset( $day['windows'] ) && is_array( $day['windows'] ) ? $day['windows'] : array();
					$prefix   = '_edp_availability[weekly][' . $wd . '][windows]';
					?>
					<div class="edp-weekday" data-edp-weekday>
						<label class="edp-weekday-toggle">
							<input type="checkbox" name="_edp_availability[weekly][<?php echo esc_attr( $wd ); ?>][open]" value="1" <?php checked( $is_open ); ?> data-edp-day-toggle />
							<strong><?php echo esc_html( $label ); ?></strong>
						</label>
						<div class="edp-weekday-windows" <?php echo $is_open ? '' : 'style="display:none"'; ?>>
							<div class="edp-windows" data-edp-rows>
								<?php
								foreach ( $windows as $i => $window ) {
									$this->render_window_row( $prefix, $i, $window );
								}
								?>
							</div>
							<button type="button" class="button edp-add-window" data-edp-add-window data-name-prefix="<?php echo esc_attr( $prefix ); ?>">
								<?php esc_html_e( '+ Aggiungi fascia oraria', 'eredi-experience-booking' ); ?>
							</button>
						</div>
					</div>
					<?php
				}
				?>
			</div>

			<!-- shared options for always/range/event -->
			<div class="options_group edp-mode-block" data-edp-mode-block="always range event">
				<p class="form-field">
					<label for="_edp_min_lead_hours"><?php esc_html_e( 'Anticipo minimo (ore)', 'eredi-experience-booking' ); ?></label>
					<input type="number" min="0" step="1" id="_edp_min_lead_hours" name="_edp_availability[min_lead_hours]" value="<?php echo esc_attr( $min_lead ); ?>" class="short" />
					<span class="description"><?php esc_html_e( 'Quante ore prima dello slot è ancora possibile prenotare.', 'eredi-experience-booking' ); ?></span>
				</p>
			</div>

			<div class="options_group edp-mode-block" data-edp-mode-block="always range">
				<p class="form-field">
					<label for="_edp_blackout_dates"><?php esc_html_e( 'Date chiuse (blackout)', 'eredi-experience-booking' ); ?></label>
					<textarea id="_edp_blackout_dates" name="_edp_availability[blackout_dates]" rows="3" class="edp-blackout" placeholder="2026-12-25&#10;2026-12-31"><?php echo esc_textarea( implode( "\n", (array) $blackout ) ); ?></textarea>
					<span class="description"><?php esc_html_e( 'Una data per riga, formato AAAA-MM-GG.', 'eredi-experience-booking' ); ?></span>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Upsell & allestimenti tab.
	 *
	 * @param array $upsells Upsells config.
	 */
	private function render_upsells_panel( array $upsells ) {
		?>
		<div id="edp_upsells_data" class="panel woocommerce_options_panel edp-show-if-experience">
			<div class="options_group">
				<p class="edp-block-desc"><?php esc_html_e( 'Aggiungi upsell (es. "Picnic"), ognuno con prezzo a persona e allestimenti opzionali. Ogni allestimento può essere incluso nel prezzo dell’upsell oppure aggiungere un sovrapprezzo a persona.', 'eredi-experience-booking' ); ?></p>
				<div class="edp-upsells" data-edp-upsell-rows>
					<?php
					foreach ( $upsells as $ui => $upsell ) {
						$this->render_upsell_row( $ui, $upsell );
					}
					?>
				</div>
				<button type="button" class="button button-primary edp-add-upsell" data-edp-add-upsell>
					<?php esc_html_e( '+ Aggiungi upsell', 'eredi-experience-booking' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single time-window row.
	 *
	 * @param string $prefix Name prefix (array path up to the windows array).
	 * @param mixed  $index  Row index (numeric or placeholder).
	 * @param array  $window Window values.
	 */
	private function render_window_row( $prefix, $index, $window = array() ) {
		$start    = isset( $window['start'] ) ? $window['start'] : '';
		$end      = isset( $window['end'] ) ? $window['end'] : '';
		$interval = isset( $window['interval'] ) ? (int) $window['interval'] : '';
		$max      = isset( $window['max'] ) ? (int) $window['max'] : '';
		$base     = $prefix . '[' . $index . ']';
		?>
		<div class="edp-window-row" data-edp-row>
			<label class="edp-field"><span><?php esc_html_e( 'Dalle', 'eredi-experience-booking' ); ?></span>
				<input type="time" name="<?php echo esc_attr( $base ); ?>[start]" value="<?php echo esc_attr( $start ); ?>" />
			</label>
			<label class="edp-field"><span><?php esc_html_e( 'Alle', 'eredi-experience-booking' ); ?></span>
				<input type="time" name="<?php echo esc_attr( $base ); ?>[end]" value="<?php echo esc_attr( $end ); ?>" />
			</label>
			<label class="edp-field"><span><?php esc_html_e( 'Intervallo (min)', 'eredi-experience-booking' ); ?></span>
				<input type="number" min="1" step="1" name="<?php echo esc_attr( $base ); ?>[interval]" value="<?php echo esc_attr( $interval ); ?>" />
			</label>
			<label class="edp-field"><span><?php esc_html_e( 'Max prenotaz./slot', 'eredi-experience-booking' ); ?></span>
				<input type="number" min="0" step="1" name="<?php echo esc_attr( $base ); ?>[max]" value="<?php echo esc_attr( $max ); ?>" placeholder="0" />
			</label>
			<button type="button" class="button edp-remove" data-edp-remove title="<?php esc_attr_e( 'Rimuovi', 'eredi-experience-booking' ); ?>">&times;</button>
		</div>
		<?php
	}

	/**
	 * Render a single upsell row (with its nested options).
	 *
	 * @param mixed $ui     Upsell index (numeric or placeholder).
	 * @param array $upsell Upsell values.
	 */
	private function render_upsell_row( $ui, $upsell = array() ) {
		$name    = isset( $upsell['name'] ) ? $upsell['name'] : '';
		$price   = isset( $upsell['price_per_person'] ) ? $upsell['price_per_person'] : '';
		$options = isset( $upsell['options'] ) && is_array( $upsell['options'] ) ? $upsell['options'] : array();
		$base    = '_edp_upsells[' . $ui . ']';
		?>
		<div class="edp-upsell-row" data-edp-upsell>
			<div class="edp-upsell-head">
				<label class="edp-field edp-grow"><span><?php esc_html_e( 'Nome upsell', 'eredi-experience-booking' ); ?></span>
					<input type="text" name="<?php echo esc_attr( $base ); ?>[name]" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php esc_attr_e( 'Es. Picnic', 'eredi-experience-booking' ); ?>" />
				</label>
				<label class="edp-field"><span><?php esc_html_e( 'Prezzo a persona', 'eredi-experience-booking' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</span>
					<input type="text" class="wc_input_price" name="<?php echo esc_attr( $base ); ?>[price_per_person]" value="<?php echo esc_attr( '' !== $price ? wc_format_localized_price( $price ) : '' ); ?>" />
				</label>
				<button type="button" class="button edp-remove edp-remove-upsell" data-edp-remove title="<?php esc_attr_e( 'Rimuovi upsell', 'eredi-experience-booking' ); ?>">&times;</button>
			</div>
			<div class="edp-upsell-options">
				<span class="edp-block-title"><?php esc_html_e( 'Allestimenti', 'eredi-experience-booking' ); ?></span>
				<div class="edp-options" data-edp-rows>
					<?php
					foreach ( $options as $oi => $option ) {
						$this->render_option_row( $ui, $oi, $option );
					}
					?>
				</div>
				<button type="button" class="button edp-add-option" data-edp-add-option data-ui="<?php echo esc_attr( $ui ); ?>">
					<?php esc_html_e( '+ Aggiungi allestimento', 'eredi-experience-booking' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single allestimento option row.
	 *
	 * @param mixed $ui     Upsell index.
	 * @param mixed $oi     Option index.
	 * @param array $option Option values.
	 */
	private function render_option_row( $ui, $oi, $option = array() ) {
		$name  = isset( $option['name'] ) ? $option['name'] : '';
		$mode  = isset( $option['mode'] ) && 'addon' === $option['mode'] ? 'addon' : 'included';
		$extra = isset( $option['extra_per_person'] ) ? $option['extra_per_person'] : '';
		$base  = '_edp_upsells[' . $ui . '][options][' . $oi . ']';
		?>
		<div class="edp-option-row" data-edp-row>
			<label class="edp-field edp-grow"><span><?php esc_html_e( 'Allestimento', 'eredi-experience-booking' ); ?></span>
				<input type="text" name="<?php echo esc_attr( $base ); ?>[name]" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php esc_attr_e( 'Es. Cuscini', 'eredi-experience-booking' ); ?>" />
			</label>
			<label class="edp-field"><span><?php esc_html_e( 'Tipo', 'eredi-experience-booking' ); ?></span>
				<select name="<?php echo esc_attr( $base ); ?>[mode]" data-edp-option-mode>
					<option value="included" <?php selected( $mode, 'included' ); ?>><?php esc_html_e( 'Incluso nel prezzo', 'eredi-experience-booking' ); ?></option>
					<option value="addon" <?php selected( $mode, 'addon' ); ?>><?php esc_html_e( 'Aggiunge prezzo', 'eredi-experience-booking' ); ?></option>
				</select>
			</label>
			<label class="edp-field edp-extra-field" <?php echo 'addon' === $mode ? '' : 'style="display:none"'; ?>><span><?php esc_html_e( 'Sovrapprezzo a persona', 'eredi-experience-booking' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</span>
				<input type="text" class="wc_input_price" name="<?php echo esc_attr( $base ); ?>[extra_per_person]" value="<?php echo esc_attr( '' !== $extra ? wc_format_localized_price( $extra ) : '' ); ?>" />
			</label>
			<button type="button" class="button edp-remove" data-edp-remove title="<?php esc_attr_e( 'Rimuovi', 'eredi-experience-booking' ); ?>">&times;</button>
		</div>
		<?php
	}

	/**
	 * Output JS templates (reusing the same renderers with placeholder indices).
	 */
	private function render_templates() {
		echo '<script type="text/html" id="edp-tpl-window">';
		$this->render_window_row( '__PREFIX__', '__WI__', array() );
		echo '</script>';

		echo '<script type="text/html" id="edp-tpl-tier">';
		$this->render_tier_row( '__TI__', array() );
		echo '</script>';

		echo '<script type="text/html" id="edp-tpl-upsell">';
		$this->render_upsell_row( '__UI__', array() );
		echo '</script>';

		echo '<script type="text/html" id="edp-tpl-option">';
		$this->render_option_row( '__UI__', '__OI__', array() );
		echo '</script>';
	}
}
