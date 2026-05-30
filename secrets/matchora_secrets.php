<?php
/**
 * SECRETS.PHP — Credenziali sensibili Matchora
 * =============================================
 * ⚠️  NON committare questo file su git.
 * ⚠️  NON lasciare questo file nella webroot in produzione.
 *
 * Posizione ideale:  /home/itpbrgro/secrets/matchora_secrets.php
 * (una directory sopra la public_html, non accessibile via HTTP)
 *
 * In db_config.php cambia il require in:
 *   require_once dirname(__DIR__, 2) . '/secrets/matchora_secrets.php';
 */

// ── Database ──────────────────────────────────────────────────────────
// Inserisci qui la NUOVA password generata dal pannello Netsons
define('DB_HOST',     'localhost');
define('DB_USER',     'itpbrgro_wp761');
define('DB_PASSWORD', '36-S@9AQ0].pWj)8');   
define('DB_NAME',     'itpbrgro_wp761');

// ── Google OAuth 2.0 ─────────────────────────────────────────────────
// Client ID: non cambia dopo la rotazione del secret
define('GOOGLE_CLIENT_ID',
    'google id');

// Incolla qui il NUOVO secret da Google Cloud Console
define('GOOGLE_CLIENT_SECRET', 'google secret');  

// Redirect URI (non cambia)
define('GOOGLE_REDIRECT_URI',
    'https://matchoratorneo.netsons.org/php/google_callback.php');
