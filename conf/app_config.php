<?php
/**
 * APP_CONFIG.PHP — Costanti applicative (non sensibili)
 * ======================================================
 * Modifica BASE_URL quando passi da staging a produzione.
 */

// URL base del sito (senza slash finale)
define('BASE_URL', 'https://matchoratorneo.netsons.org');

// Email operativa del team
define('MAIL_FROM',  'noreply@matchoratorneo.netsons.org');
define('MAIL_ADMIN', 'matchora.torneo@gmail.com');

// Nome del sito
define('SITE_NAME', 'Matchora Tornei');

// Versione privacy/termini (data fissa, aggiornare manualmente ad ogni revisione)
define('PRIVACY_VERSION', '25/05/2026');
define('TERMS_VERSION',   '25/05/2026');

// Titolare del trattamento GDPR
define('TITOLARE_NOME',     'Matchora Tornei');
define('TITOLARE_EMAIL',    'matchora.torneo@gmail.com');
// ⚠️  Inserisci l'indirizzo fisico reale del titolare prima di pubblicare
define('TITOLARE_INDIRIZZO', 'Via [Da completare], [CAP] [Città], Italia');
