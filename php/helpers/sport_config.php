<?php
/**
 * sport_config.php
 * Configurazione centralizzata degli sport supportati.
 *
 * Ogni sport definisce:
 *  - label        : nome visualizzato
 *  - ha_pareggio  : se true, la classifica mostra P (pareggi) e PF/PS/DP
 *  - pts_vittoria : punti per una vittoria
 *  - pts_pareggio : punti per un pareggio (usato solo se ha_pareggio = true)
 *  - emoji        : icona testuale per la UI
 *  - score_label  : come si chiama il "punteggio" in quel sport
 *
 * Per padel / tennis / ping_pong / badminton non esistono pareggi:
 * la classifica mostra solo G, V, S, Pts.
 * Il vincitore di una partita è sempre chi ha punti_casa > punti_ospite.
 */

define('SPORT_CONFIG', [

    // ── Sport con pareggio (classifica stile calcio) ──────────────────────
    'calcio' => [
        'label'        => 'Calcio',
        'ha_pareggio'  => true,
        'pts_vittoria' => 3,
        'pts_pareggio' => 1,
        'emoji'        => '⚽',
        'score_label'  => 'Gol',
    ],
    'futsal' => [
        'label'        => 'Futsal',
        'ha_pareggio'  => true,
        'pts_vittoria' => 3,
        'pts_pareggio' => 1,
        'emoji'        => '⚽',
        'score_label'  => 'Gol',
    ],
    'beachvolley' => [
        'label'        => 'Beach Volley',
        'ha_pareggio'  => false,   // Volley: no pareggi, ma mostra set/punti
        'pts_vittoria' => 3,
        'pts_pareggio' => 0,
        'emoji'        => '🏐',
        'score_label'  => 'Set',
    ],
    'basket' => [
        'label'        => 'Basket',
        'ha_pareggio'  => false,
        'pts_vittoria' => 2,
        'pts_pareggio' => 0,
        'emoji'        => '🏀',
        'score_label'  => 'Punti',
    ],
    'rugby' => [
        'label'        => 'Rugby',
        'ha_pareggio'  => true,
        'pts_vittoria' => 4,
        'pts_pareggio' => 2,
        'emoji'        => '🏉',
        'score_label'  => 'Mete',
    ],

    // ── Sport senza pareggio (solo V/S, no PF/PS/DP) ─────────────────────
    'padel' => [
        'label'        => 'Padel',
        'ha_pareggio'  => false,
        'pts_vittoria' => 2,
        'pts_pareggio' => 0,
        'emoji'        => '🎾',
        'score_label'  => 'Punti',
    ],
    'tennis' => [
        'label'        => 'Tennis',
        'ha_pareggio'  => false,
        'pts_vittoria' => 2,
        'pts_pareggio' => 0,
        'emoji'        => '🎾',
        'score_label'  => 'Set',
    ],
    'ping_pong' => [
        'label'        => 'Ping Pong',
        'ha_pareggio'  => false,
        'pts_vittoria' => 2,
        'pts_pareggio' => 0,
        'emoji'        => '🏓',
        'score_label'  => 'Punti',
    ],
    'badminton' => [
        'label'        => 'Badminton',
        'ha_pareggio'  => false,
        'pts_vittoria' => 2,
        'pts_pareggio' => 0,
        'emoji'        => '🏸',
        'score_label'  => 'Punti',
    ],
]);

/**
 * Restituisce la config per uno sport, con fallback su calcio.
 */
function sport_cfg(string $sport): array {
    return SPORT_CONFIG[$sport] ?? SPORT_CONFIG['calcio'];
}

/**
 * Calcola la classifica per un insieme di partite e squadre,
 * tenendo conto delle regole dello sport.
 *
 * @param array  $squadreRaw  Array di ['id' => ..., 'nome' => ...]
 * @param array  $partite     Array di partite (già filtrate per torneo/girone/stato)
 * @param string $sport       Slug dello sport (es. 'calcio', 'padel')
 * @return array              Classifica ordinata
 */
function calcola_classifica(array $squadreRaw, array $partite, string $sport): array {
    $cfg = sport_cfg($sport);

    $classifica = [];
    foreach ($squadreRaw as $sq) {
        $classifica[$sq['id']] = [
            'id'   => $sq['id'],
            'nome' => $sq['nome'],
            'G'    => 0,
            'V'    => 0,
            'P'    => 0,   // pareggi
            'S'    => 0,
            'PF'   => 0,   // punti fatti
            'PS'   => 0,   // punti subiti
            'DP'   => 0,   // differenza punti
            'Pts'  => 0,
        ];
    }

    foreach ($partite as $p) {
        $c  = $p['squadra_casa_id'];
        $o  = $p['squadra_ospite_id'];
        $pc = (int)$p['punti_casa'];
        $po = (int)$p['punti_ospite'];

        if (!isset($classifica[$c]) || !isset($classifica[$o])) continue;

        $classifica[$c]['G']++;
        $classifica[$o]['G']++;
        $classifica[$c]['PF'] += $pc;
        $classifica[$c]['PS'] += $po;
        $classifica[$o]['PF'] += $po;
        $classifica[$o]['PS'] += $pc;

        if ($pc > $po) {
            // Casa vince
            $classifica[$c]['V']++;
            $classifica[$c]['Pts'] += $cfg['pts_vittoria'];
            $classifica[$o]['S']++;
        } elseif ($pc < $po) {
            // Ospite vince
            $classifica[$o]['V']++;
            $classifica[$o]['Pts'] += $cfg['pts_vittoria'];
            $classifica[$c]['S']++;
        } else {
            // Pareggio
            if ($cfg['ha_pareggio']) {
                $classifica[$c]['P']++;
                $classifica[$c]['Pts'] += $cfg['pts_pareggio'];
                $classifica[$o]['P']++;
                $classifica[$o]['Pts'] += $cfg['pts_pareggio'];
            }
            // Se lo sport non ammette pareggi (es. padel), non assegnare punti
            // (situazione anomala, ma gestiamo senza crash)
        }
    }

    foreach ($classifica as &$sq) {
        $sq['DP'] = $sq['PF'] - $sq['PS'];
    }
    unset($sq);

    // Criteri di ordinamento
    if ($cfg['ha_pareggio']) {
        // Calcio-style: Pts → DP → PF → nome
        usort($classifica, fn($a, $b) =>
            $b['Pts'] <=> $a['Pts']
            ?: $b['DP'] <=> $a['DP']
            ?: $b['PF'] <=> $a['PF']
            ?: strcmp($a['nome'], $b['nome'])
        );
    } else {
        // Racket/no-pareggio style: Pts → V → nome
        usort($classifica, fn($a, $b) =>
            $b['Pts'] <=> $a['Pts']
            ?: $b['V'] <=> $a['V']
            ?: strcmp($a['nome'], $b['nome'])
        );
    }

    return array_values($classifica);
}
