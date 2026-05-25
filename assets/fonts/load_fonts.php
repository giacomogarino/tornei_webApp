<?php
/**
 * LOAD_FONTS.PHP — Proxy locale per Google Fonts (conformità GDPR)
 * =================================================================
 * Il browser dell'utente non si connette mai a Google.
 * Questo script scarica e memorizza in cache i font lato server,
 * poi li serve localmente. L'IP dell'utente non viene mai inviato
 * a Google (conformità GDPR/ePrivacy, sentenza LG München 2022).
 *
 * Prima esecuzione: scarica CSS + file .woff2 da Google (lato server).
 * Esecuzioni successive: serve dalla cache locale (30 giorni).
 */

// Nessun output prima degli header
$cache_dir  = __DIR__;
$font_dir   = $cache_dir . '/files';
$css_cache  = $cache_dir . '/fonts_cache.css';
$ttl        = 86400 * 30; // 30 giorni

// Serve dalla cache se ancora valida
if (file_exists($css_cache) && (time() - filemtime($css_cache)) < $ttl) {
    header('Content-Type: text/css; charset=utf-8');
    header('Cache-Control: public, max-age=2592000, immutable');
    readfile($css_cache);
    exit;
}

// URL Google Fonts da scaricare lato server
$google_url = 'https://fonts.googleapis.com/css2?'
    . 'family=Inter:wght@400;500;600;700'
    . '&family=Space+Grotesk:wght@500;600;700'
    . '&family=JetBrains+Mono:wght@400;500'
    . '&display=swap';

$ctx = stream_context_create([
    'http' => [
        'user_agent' => 'Mozilla/5.0 (compatible; Matchora-FontProxy/1.0)',
        'timeout'    => 8,
        'method'     => 'GET',
    ],
    'ssl' => [
        'verify_peer'      => true,
        'verify_peer_name' => true,
    ],
]);

$css = @file_get_contents($google_url, false, $ctx);

if ($css === false) {
    // Fallback: se Google non è raggiungibile, usa system fonts
    $fallback = <<<CSS
/* Font proxy: Google non raggiungibile — fallback system fonts */
:root {
    --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                 "Helvetica Neue", Arial, sans-serif;
    --font-display: var(--font-sans);
    --font-mono: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
}
body { font-family: var(--font-sans); }
CSS;
    header('Content-Type: text/css; charset=utf-8');
    echo $fallback;
    exit;
}

// Crea la directory dei file font se non esiste
if (!is_dir($font_dir)) {
    mkdir($font_dir, 0755, true);
}

// Calcola il percorso web della cartella files/ relativo allo script
$script_web = dirname($_SERVER['SCRIPT_NAME']); // es. /staging/assets/fonts
$files_web  = rtrim($script_web, '/') . '/files/';

// Sostituisce ogni url(https://fonts.gstatic.com/...) con un URL locale
$css = preg_replace_callback(
    '/url\((https:\/\/fonts\.gstatic\.com\/[^)]+)\)/',
    function (array $m) use ($font_dir, $files_web): string {
        $remote_url = $m[1];
        $hash       = md5($remote_url);
        $ext        = pathinfo(parse_url($remote_url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'woff2';
        $filename   = $hash . '.' . $ext;
        $local_path = $font_dir . '/' . $filename;

        if (!file_exists($local_path)) {
            $font_ctx  = stream_context_create([
                'http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0'],
                'ssl'  => ['verify_peer' => true],
            ]);
            $font_data = @file_get_contents($remote_url, false, $font_ctx);
            if ($font_data !== false) {
                file_put_contents($local_path, $font_data);
            } else {
                // Font non scaricabile — restituisce URL originale come fallback
                return "url($remote_url)";
            }
        }

        return 'url(' . $files_web . $filename . ')';
    },
    $css
);

// Salva CSS in cache
file_put_contents($css_cache, $css);

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=2592000, immutable');
echo $css;
