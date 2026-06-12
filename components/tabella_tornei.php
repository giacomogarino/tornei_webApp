<?php
/* tabella_tornei  componente riusato da:
   - index.php
   - tornei_seguiti.php
   - mostra_tornei_creati.php
   Si aspetta una variabile $result (mysqli_result). */

if (!isset($result)) {
    die("Nessun torneo trovato");
}

/* Iniziali per avatar */
function tabella_tornei_iniziali($nome) {
    $out = '';
    foreach (preg_split('/\s+/', trim($nome)) as $p) {
        if ($p === '') continue;
        $out .= strtoupper(mb_substr($p, 0, 1));
        if (mb_strlen($out) >= 2) break;
    }
    return $out !== '' ? $out : 'T';
}

/* Etichette leggibili per i formati e stato */
$formato_label = [
    'girone_unico'         => 'Girone unico',
    'eliminazione_diretta' => 'Elim. diretta',
    'gironi_playoff'       => 'Gironi + playoff',
];
$stato_label = [
    'aperto'     => 'Aperto',
    'in_corso'   => 'In corso',
    'completato' => 'Completato',
];

/* Gradient cover variato per dare ritmo alla griglia */
$cover_styles = [
    "background: radial-gradient(120% 80% at 80% 20%, rgba(243,156,18,0.22) 0%, transparent 50%), linear-gradient(135deg, var(--m-primary-500) 0%, var(--m-primary-700) 100%);",
    "background: radial-gradient(120% 80% at 80% 20%, rgba(243,156,18,0.22) 0%, transparent 50%), linear-gradient(135deg, #6e3ad1 0%, #3a2fa1 100%);",
    "background: radial-gradient(120% 80% at 80% 20%, rgba(255,255,255,0.10) 0%, transparent 50%), linear-gradient(135deg, #4a3cc2 0%, #1d1856 100%);",
    "background: radial-gradient(120% 80% at 80% 20%, rgba(243,156,18,0.30) 0%, transparent 50%), linear-gradient(135deg, #5b4cdb 0%, #2d2483 100%);",
    "background: radial-gradient(120% 80% at 80% 20%, rgba(243,156,18,0.18) 0%, transparent 50%), linear-gradient(135deg, #7a66f0 0%, #3a2fa1 100%);",
    "background: radial-gradient(120% 80% at 80% 20%, rgba(243,156,18,0.25) 0%, transparent 50%), linear-gradient(135deg, #5b4cdb 0%, #4338b3 100%);",
];

$sport_display = [
    'calcio'      => '⚽ Calcio',
    'futsal'      => '⚽ Futsal',
    'beachvolley' => '🏐 Beach Volley',
    'rugby'       => '🏉 Rugby',
    'padel'       => '🎾 Padel',
    'tennis'      => '🎾 Tennis',
    'ping_pong'   => '🏓 Ping Pong',
    'badminton'   => '🏸 Badminton',
    'basket'      => '🏀 Basket',
];
?>

<?php if ($result->num_rows > 0): ?>
    <div class="m-tcard-grid">
        <?php $idx = 0; while ($row = $result->fetch_assoc()): ?>
            <?php
                $cover = $cover_styles[$idx % count($cover_styles)];
                $idx++;
                $stato_key = $row['stato'];
                $stato_class = 'm-state-' . htmlspecialchars($stato_key);
                $stato_txt = $stato_label[$stato_key] ?? ucfirst($stato_key);
                $formato_txt = $formato_label[$row['formato']] ?? ucfirst(str_replace('_', ' ', $row['formato']));
                $dettagli_url = 'dettagli_torneo.php?id=' . urlencode($row['id']);
            ?>
            <a href="<?= $dettagli_url ?>" class="m-tcard">
                <div class="m-tcard__cover" style="<?= $cover ?>">
                    <span class="m-tcard__sport">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v18M3 12h18M5 7l14 10"/></svg>
                        <?= htmlspecialchars($sport_display[$row['sport']] ?? ucfirst(str_replace('_', ' ', $row['sport']))) ?>
                    </span>
                    <span class="m-tcard__state m-badge m-badge--dot <?= $stato_class ?>"><?= htmlspecialchars($stato_txt) ?></span>
                </div>
                <div class="m-tcard__body">
                    <h3 class="m-tcard__title"><?= htmlspecialchars($row['nome']) ?></h3>
                    <div class="m-tcard__meta">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?= htmlspecialchars($row['luogo']) ?>
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 4 7 4 7 20 3 20"/><polyline points="11 4 15 4 15 14 11 14"/><polyline points="19 4 21 4 21 10 19 10"/></svg>
                            <?= htmlspecialchars($formato_txt) ?>
                        </span>
                    </div>
                </div>
                <div class="m-tcard__footer">
                    
                    <span class="m-tcard__cta">Apri </span>
                </div>
            </a>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="m-empty">
        <div class="m-empty__icon">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="20" y1="20" x2="16.65" y2="16.65"/></svg>
        </div>
        <h3>Nessun torneo trovato</h3>
        <p class="m-muted">Prova a cambiare i filtri o crea il tuo primo torneo.</p>
    </div>
<?php endif; ?>
