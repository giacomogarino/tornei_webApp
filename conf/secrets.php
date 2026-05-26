<?php
/**
 * SECRETS.PHP — Credenziali sensibili
 * ====================================
 * ⚠️  IMPORTANTE: questo file NON va mai committato su git.
 *     Aggiungi /staging/conf/secrets.php al tuo .gitignore
 *
 * ⚠️  DOPO IL DEPLOY: ruota il Google Client Secret in
 *     https://console.cloud.google.com → Credentials
 *     (era esposto nel codice sorgente precedente).
 *
 * In produzione (fuori staging) sposta questo file FUORI dalla
 * web root o usa variabili d'ambiente del server.
 */

// ── Database ─────────────────────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_USER',     'itpbrgro_wp761');
define('DB_PASSWORD', '36-S@9AQ0].pWj)8');
define('DB_NAME',     'itpbrgro_wp761');

// ── Google OAuth 2.0 ─────────────────────────────────────────────────
define('client ID');

// ⚠️  RUOTA QUESTO SECRET IMMEDIATAMENTE su Google Cloud Console
define('GOOGLE_CLIENT_SECRET', 'client secret');

// Aggiorna anche in Google Cloud Console → OAuth redirect URIs
define('GOOGLE_REDIRECT_URI',
    'https://matchoratorneo.netsons.org/php/google_callback.php');
