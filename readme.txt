=== Eredi dei Papi – Experience Booking ===
Contributors: regicode
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 10.7
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sistema di prenotazione esperienze per WooCommerce, con widget Elementor, slot orari configurabili e gestione conferma/rifiuto.

== Description ==

Aggiunge un tipo di prodotto "Esperienza" con prezzo a persona, upsell con allestimenti
(inclusi o a sovrapprezzo per persona), numero min/max di persone e slot orari configurabili
per esperienza in tre modalità: evento (data singola), sempre disponibile (per giorno della
settimana) o intervallo di date.

I clienti prenotano da un widget Elementor (o dallo shortcode `[edp_experience_booking id="123"]`):
il pulsante apre un modale dove scelgono data, orario, numero di persone e upsell. All'invio viene
creato un ordine WooCommerce in stato "Prenotazione: In attesa" (nessun pagamento online) e il
cliente riceve un'email di ricezione; l'amministrazione conferma o rifiuta dalla scheda ordine e il
cliente riceve l'email con l'esito. La capienza di ogni slot conta solo le prenotazioni confermate.

Compatibile con HPOS (High-Performance Order Storage).

== Caratteristiche ==

* Tipo prodotto "Esperienza" con prezzo a persona e min/max persone.
* Upsell multipli per esperienza, ognuno con allestimenti (inclusi o a sovrapprezzo per persona).
* Slot orari configurabili (finestre con intervallo e max prenotazioni per slot).
* Tre modalità di disponibilità: evento / sempre disponibile (settimanale) / intervallo di date,
  con date di chiusura (blackout) e anticipo minimo.
* Widget Elementor nativo con controlli di stile + shortcode di fallback.
* Modale di prenotazione con calcolo prezzo live e protezione anti-spam (honeypot + nonce).
* Stati ordine personalizzati e 4 email transazionali (cliente: ricevuta/confermata/rifiutata;
  admin: nuova prenotazione).

== Changelog ==

= 1.0.0 =
* Prima versione.
