=== Eredi dei Papi – Experience Booking ===
Contributors: regicode
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 10.7
Stable tag: 1.0.3
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

* Tipo prodotto "Esperienza" con prezzo a persona a scaglioni (per n° persone), durata e min/max derivati.
* Upsell multipli per esperienza, ognuno con allestimenti (inclusi o a sovrapprezzo per persona).
* Slot orari configurabili (finestre con intervallo e max prenotazioni per slot).
* Tre modalità di disponibilità: evento / sempre disponibile (settimanale) / intervallo di date,
  con date di chiusura (blackout) e anticipo minimo.
* Strumento "Copia da un'altra esperienza" nella scheda prodotto (sezioni selezionabili: prezzo/persone, disponibilità, upsell).
* Widget Elementor nativo con controlli di stile + shortcode di fallback.
* Modale di prenotazione personalizzabile dal widget (testi dei pulsanti, colori, font e raggi)
  con anteprima live nell'editor, calcolo prezzo live e protezione anti-spam (honeypot + nonce).
* Stati ordine personalizzati e 4 email transazionali (cliente: ricevuta/confermata/rifiutata;
  admin: nuova prenotazione).

== Changelog ==

= 1.0.3 =
* Personalizzazione del modale dal widget Elementor: testo dei pulsanti (invio, chiudi),
  etichetta di chiusura e titolo della sezione upsell; colori (overlay, sfondo finestra,
  accento, pulsante normale/hover), raggi di finestra e campi, font di titoli e testo.
* Anteprima live del modale nell'editor Elementor.
* Nota: i font scelti devono essere già caricati dal tema; un font Google non incluso non
  viene accodato automaticamente (si applica il fallback dello stack tipografico).

= 1.0.2 =
* Prezzo a persona a scaglioni per numero di persone (es. 2 → €40, 3-5 → €35, 6+ → €32).
* Min/max persone derivati dagli scaglioni; nuovo campo "Durata" mostrato nel widget.

= 1.0.1 =
* Fix layout dei pannelli custom nell'editor prodotto (specificità CSS vs WooCommerce).
* Nascosti i tab Attributi, Spedizione e Articoli collegati per il tipo Esperienza.

= 1.0.0 =
* Prima versione.
