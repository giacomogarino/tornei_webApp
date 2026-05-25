<?php
require_once 'php/helpers/session.php';
require_once 'php/helpers/csrf.php';
session_secure_start();
include("conf/db_config.php");

$torneo_id = (int)($_GET['id'] ?? 0);
if (!$torneo_id) { header("Location: index.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM torneo WHERE id = ?");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$torneo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$torneo) { header("Location: index.php"); exit; }

$isOrganizzatore = isset($_SESSION['id_utente']) && $_SESSION['id_utente'] == $torneo['creato_da'];
if (!$isOrganizzatore) { header("Location: dettagli_torneo.php?id=$torneo_id"); exit; }

if ($torneo['formato'] !== 'gironi_playoff' || ($torneo['gironi_mode'] ?? 'auto') !== 'manuale') {
    header("Location: dettagli_torneo.php?id=$torneo_id"); exit;
}

$gironiEsistenti = (int)$conn->query("SELECT COUNT(*) as c FROM partita WHERE torneo_id = $torneo_id AND girone IS NOT NULL")->fetch_assoc()['c'];
if ($gironiEsistenti > 0) {
    header("Location: struttura_torneo.php?id=$torneo_id&view=gironi");
    exit;
}

$squadre = $conn->query("SELECT id, nome FROM squadra WHERE torneo_id = $torneo_id AND stato = 'approvata' ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);

/* ── POST: salva gironi ───────────────────────────────────────────── */
$errore = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gironi_data'])) {
    csrf_verify();
    $gironiData = json_decode($_POST['gironi_data'], true);

    if (!$gironiData || count($gironiData) < 1) {
        $errore = "Devi creare almeno un girone con squadre.";
    } else {
        $assegnate = [];
        $ok = true;

        foreach ($gironiData as $sq_ids) {
            if (count($sq_ids) < 2) {
                $errore = "Ogni girone deve avere almeno 2 squadre.";
                $ok = false; break;
            }
            foreach ($sq_ids as $sq_id) {
                if (in_array($sq_id, $assegnate)) {
                    $errore = "La stessa squadra non può essere in più gironi.";
                    $ok = false; break 2;
                }
                $assegnate[] = (int)$sq_id;
            }
        }

        if ($ok) {
            $tipo = $torneo['tipo_partita'];
            foreach ($gironiData as $numGirone => $sq_ids) {
                $g   = $numGirone + 1;
                $sq  = array_map('intval', $sq_ids);
                $tot = count($sq);
                $partite = [];
                for ($i = 0; $i < $tot; $i++) {
                    for ($j = $i + 1; $j < $tot; $j++) {
                        $partite[] = [$sq[$i], $sq[$j]];
                        if ($tipo === 'andata_ritorno')
                            $partite[] = [$sq[$j], $sq[$i]];
                    }
                }
                shuffle($partite);
                foreach ($partite as [$casa, $ospite]) {
                    $stmt = $conn->prepare("INSERT INTO partita (torneo_id, squadra_casa_id, squadra_ospite_id, girone) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiii", $torneo_id, $casa, $ospite, $g);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            // ← Redirect alla struttura torneo, non a dettagli
            header("Location: struttura_torneo.php?id=$torneo_id&view=gironi");
            exit;
        }
    }
}

require_once('templates/header_riservato.php');
?>

<style>
.cg-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: var(--m-5);
    align-items: start;
}
@media (max-width: 768px) { .cg-layout { grid-template-columns: 1fr; } }

.cg-pool {
    background: var(--m-surface);
    border: 1px solid var(--m-border);
    border-radius: var(--m-radius-lg);
    padding: var(--m-4);
    position: sticky;
    top: 80px;
}
.cg-pool__search { width: 100%; margin-bottom: var(--m-3); }
.cg-pool__list {
    display: flex; flex-direction: column; gap: 6px;
    min-height: 60px; max-height: 420px; overflow-y: auto;
}
.cg-squad {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px;
    background: var(--m-surface);
    border: 1px solid var(--m-border);
    border-radius: var(--m-radius);
    cursor: grab; user-select: none;
    transition: box-shadow .15s, border-color .15s, opacity .15s;
    font-size: 14px; font-weight: 500;
}
.cg-squad:active { cursor: grabbing; }
.cg-squad.dragging { opacity: .4; }
/* Squadra assegnata nel pool: sbiadita e non cliccabile */
.cg-squad.cg-squad--assigned {
    opacity: .38;
    cursor: not-allowed;
    pointer-events: none;
    background: var(--m-surface-2, #f3f4f6);
}
.cg-squad__avatar {
    width: 30px; height: 30px; border-radius: 6px;
    background: var(--m-primary-100); color: var(--m-primary-600);
    font-size: 11px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-family: var(--m-font-display);
}
/* Squadra dentro un girone: ha una X per rimuoverla */
.cg-squad--in-girone {
    cursor: default;
    background: var(--m-primary-50, #eff6ff);
    border-color: var(--m-primary-200, #bfdbfe);
}
.cg-squad--in-girone:hover { border-color: var(--m-primary-400); }
.cg-squad__remove {
    margin-left: auto; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    width: 20px; height: 20px; border-radius: 50%;
    background: none; border: none;
    color: var(--m-text-mute);
    cursor: pointer; transition: background .15s, color .15s;
    padding: 0;
}
.cg-squad__remove:hover { background: #fee2e2; color: #dc2626; }

.cg-gironi { display: flex; flex-direction: column; gap: var(--m-4); }
.cg-girone {
    border: 2px dashed var(--m-border);
    border-radius: var(--m-radius-lg);
    padding: var(--m-4);
    transition: border-color .2s, background .2s;
}
.cg-girone.drag-over { border-color: var(--m-primary-400); background: var(--m-primary-50); }
.cg-girone__header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: var(--m-3); gap: 8px;
}
.cg-girone__title {
    font-family: var(--m-font-display); font-weight: 700; font-size: 15px;
    display: flex; align-items: center; gap: 8px;
}
.cg-girone__drop { min-height: 70px; display: flex; flex-direction: column; gap: 6px; }
.cg-girone__empty {
    display: flex; align-items: center; justify-content: center;
    min-height: 60px; color: var(--m-text-mute); font-size: 13px;
    border: 1.5px dashed var(--m-border); border-radius: var(--m-radius);
    font-style: italic;
}
.cg-actions { display: flex; gap: var(--m-3); align-items: center; flex-wrap: wrap; margin-top: var(--m-4); }
.cg-counter { font-size: 13px; color: var(--m-text-soft); }
.cg-counter b { color: var(--m-text); }
</style>

<header class="t-hero">
    <div class="m-container">
        <div class="t-breadcrumb">
            <a href="index.php">Home</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <a href="dettagli_torneo.php?id=<?= $torneo_id ?>"><?= htmlspecialchars($torneo['nome']) ?></a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Crea gironi</span>
        </div>
        <h1>Composizione gironi</h1>
        <p class="desc">Trascina le squadre nei gironi oppure cliccaci sopra. Clicca la <b>×</b> su una squadra per spostarla di nuovo nel pool.</p>
    </div>
</header>

<main class="m-page">
    <div class="m-container">

        <?php if ($errore): ?>
            <div class="m-alert m-alert--danger m-mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                <div><?= htmlspecialchars($errore) ?></div>
            </div>
        <?php endif; ?>

        <?php if (count($squadre) < 2): ?>
            <div class="m-empty">
                <div class="m-empty__icon">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                </div>
                <h3>Nessuna squadra approvata</h3>
                <p class="m-muted">Approva almeno 2 squadre prima di creare i gironi.</p>
                <a href="dettagli_torneo.php?id=<?= $torneo_id ?>" class="m-btn m-btn--primary">Torna al torneo</a>
            </div>
        <?php else: ?>

        <div class="cg-layout">

            <!-- POOL -->
            <div class="cg-pool">
                <h4 class="m-profile-section-label" style="margin-bottom:var(--m-3);">
                    Squadre disponibili
                    <span class="m-badge m-badge--neutral" id="badge-libere"><?= count($squadre) ?></span>
                </h4>
                <input class="m-input cg-pool__search" type="text" placeholder="Cerca squadra…" id="search-input" autocomplete="off">
                <div class="cg-pool__list" id="pool">
                    <?php foreach ($squadre as $sq): ?>
                    <div class="cg-squad"
                         draggable="true"
                         data-id="<?= (int)$sq['id'] ?>"
                         data-nome="<?= htmlspecialchars(strtolower($sq['nome'])) ?>">
                        <span class="cg-squad__avatar"><?= strtoupper(mb_substr($sq['nome'], 0, 2)) ?></span>
                        <span><?= htmlspecialchars($sq['nome']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- GIRONI -->
            <div>
                <div class="cg-gironi" id="gironi-container"></div>

                <div class="cg-actions">
                    <button type="button" class="m-btn m-btn--secondary" id="btn-aggiungi-girone">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Aggiungi girone
                    </button>
                    <button type="button" class="m-btn m-btn--ghost m-btn--sm" id="btn-reset">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        Azzera tutto
                    </button>
                    <span class="cg-counter">
                        <b id="count-assegnate">0</b> / <?= count($squadre) ?> squadre assegnate
                    </span>
                </div>

                <form method="POST" id="form-salva" style="margin-top:var(--m-5);">
                    <?= csrf_field() ?>
                    <input type="hidden" name="gironi_data" id="gironi-data-input">
                    <div style="display:flex; gap:var(--m-3); align-items:center; flex-wrap:wrap;">
                        <button type="submit" class="m-btn m-btn--primary">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Conferma e genera partite
                        </button>
                        <a href="dettagli_torneo.php?id=<?= $torneo_id ?>" class="m-btn m-btn--ghost">Annulla</a>
                        <span id="salva-warning" class="m-muted" style="font-size:13px; display:none;">
                            ⚠ Tutte le <?= count($squadre) ?> squadre devono essere assegnate.
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <?php endif; ?>
    </div>
</main>

<script>
const TOTAL_SQUADRE = <?= count($squadre) ?>;
const LETTERE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
let gironeCount = 0;

/* ═══════════════════════════════════════════════════════════════
   UTILITY: restituisce l'elemento pool originale per un dato id
═══════════════════════════════════════════════════════════════ */
function getPoolCard(id) {
    return document.querySelector(`#pool [data-id="${id}"]`);
}

/* ═══════════════════════════════════════════════════════════════
   CREA CARD SQUADRA PER UN GIRONE (con bottone ×)
═══════════════════════════════════════════════════════════════ */
function creaCardGirone(id, nome, avatar) {
    const card = document.createElement('div');
    card.className = 'cg-squad cg-squad--in-girone';
    card.draggable = true;
    card.dataset.id   = id;
    card.dataset.nome = nome.toLowerCase();
    card.innerHTML = `
        <span class="cg-squad__avatar">${avatar}</span>
        <span>${nome}</span>
        <button type="button" class="cg-squad__remove" title="Rimuovi dal girone">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>`;

    /* Bottone × → rimuove dal girone e riabilita nel pool */
    card.querySelector('.cg-squad__remove').addEventListener('click', () => {
        const zone = card.closest('[data-drop-zone]');
        card.remove();
        pulisciEmpty(zone);
        const poolCard = getPoolCard(id);
        if (poolCard) poolCard.classList.remove('cg-squad--assigned');
        aggiornaContatori();
    });

    /* Drag dalla card nel girone verso altra zona */
    setupDraggable(card);
    return card;
}

/* ═══════════════════════════════════════════════════════════════
   CREA GIRONE
═══════════════════════════════════════════════════════════════ */
function creaGirone() {
    gironeCount++;
    const idx     = gironeCount;
    const lettera = LETTERE[idx - 1] ?? `G${idx}`;

    const el = document.createElement('div');
    el.className  = 'cg-girone';
    el.dataset.girone = idx;
    el.innerHTML = `
        <div class="cg-girone__header">
            <div class="cg-girone__title">
                <span class="m-badge m-badge--gold">Girone ${lettera}</span>
                <span class="cg-counter" style="font-size:12px;"><span class="count-sq">0</span> squadre</span>
            </div>
            <button type="button" class="m-btn m-btn--ghost m-btn--sm btn-rimuovi-girone" title="Rimuovi girone">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="cg-girone__drop" data-drop-zone="${idx}">
            <div class="cg-girone__empty">Trascina qui le squadre oppure cliccale</div>
        </div>`;

    document.getElementById('gironi-container').appendChild(el);
    setupDropZone(el.querySelector('[data-drop-zone]'));
    aggiornaContatori();
}

/* ═══════════════════════════════════════════════════════════════
   DRAG & DROP
═══════════════════════════════════════════════════════════════ */
let draggedEl  = null;  // l'elemento che si sta trascinando
let dragSource = null;  // 'pool' | id-zona

function setupDraggable(el) {
    el.addEventListener('dragstart', e => {
        draggedEl  = el;
        dragSource = el.closest('[data-drop-zone]')?.dataset.dropZone ?? 'pool';
        setTimeout(() => el.classList.add('dragging'), 0);
    });
    el.addEventListener('dragend', () => {
        el.classList.remove('dragging');
        draggedEl = null;
    });
}

function setupDropZone(zone) {
    zone.addEventListener('dragover', e => {
        e.preventDefault();
        zone.closest('.cg-girone').classList.add('drag-over');
    });
    zone.addEventListener('dragleave', e => {
        if (!zone.contains(e.relatedTarget))
            zone.closest('.cg-girone').classList.remove('drag-over');
    });
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.closest('.cg-girone').classList.remove('drag-over');
        if (!draggedEl) return;

        const destId   = zone.dataset.dropZone;
        const sourceId = dragSource;

        if (sourceId === destId) return; // stesso girone, nulla da fare

        const sqId   = draggedEl.dataset.id;
        const sqNome = draggedEl.querySelector('span:not(.cg-squad__avatar),.cg-squad__avatar')
                        ? draggedEl.querySelectorAll('span')[1]?.textContent ?? ''
                        : '';
        const sqAv   = draggedEl.querySelector('.cg-squad__avatar')?.textContent ?? '';

        if (sourceId !== 'pool') {
            /* Stava in un altro girone → rimuovi da lì */
            draggedEl.remove();
            const oldZone = document.querySelector(`[data-drop-zone="${sourceId}"]`);
            if (oldZone) pulisciEmpty(oldZone);
            /* Crea nuova card per la destinazione */
            const poolCard = getPoolCard(sqId);
            const nome = poolCard?.querySelector('span:last-child')?.textContent ?? sqNome;
            const av   = poolCard?.querySelector('.cg-squad__avatar')?.textContent ?? sqAv;
            nascondiEmpty(zone);
            zone.appendChild(creaCardGirone(sqId, nome, av));
        } else {
            /* Viene dal pool */
            const poolCard = getPoolCard(sqId);
            if (!poolCard) return;
            const nome = poolCard.querySelector('span:last-child').textContent;
            const av   = poolCard.querySelector('.cg-squad__avatar').textContent;
            nascondiEmpty(zone);
            zone.appendChild(creaCardGirone(sqId, nome, av));
            poolCard.classList.add('cg-squad--assigned');
        }

        aggiornaContatori();
    });
}

/* ═══════════════════════════════════════════════════════════════
   CLIC SUL POOL → scegli girone
═══════════════════════════════════════════════════════════════ */
document.getElementById('pool').addEventListener('click', e => {
    const card = e.target.closest('.cg-squad:not(.cg-squad--assigned)');
    if (!card) return;

    const zones = document.querySelectorAll('[data-drop-zone]');
    if (zones.length === 0) { alert('Prima crea almeno un girone.'); return; }
    if (zones.length === 1) { spostaInGirone(card, zones[0]); return; }

    /* mini chooser */
    document.getElementById('inline-chooser')?.remove();
    const chooser = document.createElement('div');
    chooser.id = 'inline-chooser';
    chooser.style.cssText = 'position:absolute;background:var(--m-surface);border:1px solid var(--m-border);border-radius:var(--m-radius);padding:8px;z-index:200;box-shadow:0 4px 16px rgba(0,0,0,.15);min-width:160px;';

    zones.forEach((zone, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'm-btn m-btn--ghost m-btn--sm';
        btn.style.cssText = 'display:block;width:100%;text-align:left;margin-bottom:4px;';
        btn.textContent = `→ Girone ${LETTERE[i] ?? (i + 1)}`;
        btn.addEventListener('click', () => { spostaInGirone(card, zone); chooser.remove(); });
        chooser.appendChild(btn);
    });
    const annulla = document.createElement('button');
    annulla.type = 'button';
    annulla.className = 'm-btn m-btn--ghost m-btn--sm';
    annulla.style.cssText = 'display:block;width:100%;font-size:11px;color:var(--m-text-mute);';
    annulla.textContent = 'Annulla';
    annulla.addEventListener('click', () => chooser.remove());
    chooser.appendChild(annulla);

    card.style.position = 'relative';
    card.appendChild(chooser);
    setTimeout(() => {
        document.addEventListener('click', function h(ev) {
            if (!chooser.contains(ev.target)) { chooser.remove(); document.removeEventListener('click', h); }
        });
    }, 10);
});

function spostaInGirone(poolCard, zone) {
    const sqId = poolCard.dataset.id;
    const nome = poolCard.querySelector('span:last-child').textContent;
    const av   = poolCard.querySelector('.cg-squad__avatar').textContent;
    nascondiEmpty(zone);
    zone.appendChild(creaCardGirone(sqId, nome, av));
    poolCard.classList.add('cg-squad--assigned');
    aggiornaContatori();
}

/* ═══════════════════════════════════════════════════════════════
   EMPTY STATE
═══════════════════════════════════════════════════════════════ */
function nascondiEmpty(zone) {
    zone.querySelector('.cg-girone__empty')?.remove();
}
function pulisciEmpty(zone) {
    if (!zone.querySelector('.cg-squad')) {
        if (!zone.querySelector('.cg-girone__empty')) {
            const empty = document.createElement('div');
            empty.className = 'cg-girone__empty';
            empty.textContent = 'Trascina qui le squadre oppure cliccale';
            zone.appendChild(empty);
        }
    }
    aggiornaContatori();
}

/* ═══════════════════════════════════════════════════════════════
   CONTATORI
═══════════════════════════════════════════════════════════════ */
function aggiornaContatori() {
    let assegnate = 0;
    document.querySelectorAll('[data-drop-zone]').forEach(zone => {
        const n = zone.querySelectorAll('.cg-squad').length;
        assegnate += n;
        const cnt = zone.closest('.cg-girone')?.querySelector('.count-sq');
        if (cnt) cnt.textContent = n;
    });
    document.getElementById('count-assegnate').textContent = assegnate;
    document.getElementById('badge-libere').textContent    = TOTAL_SQUADRE - assegnate;
}

/* ═══════════════════════════════════════════════════════════════
   RIMUOVI GIRONE
═══════════════════════════════════════════════════════════════ */
document.getElementById('gironi-container').addEventListener('click', e => {
    const btn = e.target.closest('.btn-rimuovi-girone');
    if (!btn) return;
    const gironeEl = btn.closest('.cg-girone');
    /* Riabilita tutte le squadre nel pool */
    gironeEl.querySelectorAll('.cg-squad[data-id]').forEach(sq => {
        const poolCard = getPoolCard(sq.dataset.id);
        if (poolCard) poolCard.classList.remove('cg-squad--assigned');
    });
    gironeEl.remove();
    aggiornaContatori();
});

/* ═══════════════════════════════════════════════════════════════
   RICERCA
═══════════════════════════════════════════════════════════════ */
document.getElementById('search-input').addEventListener('input', e => {
    const q = e.target.value.toLowerCase().trim();
    document.querySelectorAll('#pool .cg-squad').forEach(el => {
        el.style.display = (!q || el.dataset.nome.includes(q)) ? '' : 'none';
    });
});

/* ═══════════════════════════════════════════════════════════════
   AZZERA TUTTO
═══════════════════════════════════════════════════════════════ */
document.getElementById('btn-reset').addEventListener('click', () => {
    if (!confirm('Vuoi svuotare tutti i gironi?')) return;
    /* Rimuovi tutte le card dai gironi */
    document.querySelectorAll('[data-drop-zone] .cg-squad').forEach(sq => sq.remove());
    /* Ripristina empty state */
    document.querySelectorAll('[data-drop-zone]').forEach(zone => pulisciEmpty(zone));
    /* Riabilita tutte le card nel pool */
    document.querySelectorAll('#pool .cg-squad').forEach(el => el.classList.remove('cg-squad--assigned'));
    aggiornaContatori();
});

/* ═══════════════════════════════════════════════════════════════
   INIZIALIZZAZIONE
═══════════════════════════════════════════════════════════════ */
document.getElementById('btn-aggiungi-girone').addEventListener('click', creaGirone);
/* Le card del pool sono solo draggable (non cg-squad--in-girone) */
document.querySelectorAll('#pool .cg-squad').forEach(el => setupDraggable(el));
/* Primo girone di default */
creaGirone();

/* ═══════════════════════════════════════════════════════════════
   SALVATAGGIO
═══════════════════════════════════════════════════════════════ */
document.getElementById('form-salva').addEventListener('submit', e => {
    e.preventDefault();
    const zones     = document.querySelectorAll('[data-drop-zone]');
    const gironiData = [];

    zones.forEach(zone => {
        const ids = Array.from(zone.querySelectorAll('.cg-squad[data-id]'))
                        .map(el => parseInt(el.dataset.id));
        if (ids.length > 0) gironiData.push(ids);
    });

    const assegnate = gironiData.flat().length;
    const warning   = document.getElementById('salva-warning');

    if (assegnate < TOTAL_SQUADRE) { warning.style.display = ''; return; }
    warning.style.display = 'none';

    for (const g of gironiData) {
        if (g.length < 2) { alert('Ogni girone deve avere almeno 2 squadre.'); return; }
    }

    document.getElementById('gironi-data-input').value = JSON.stringify(gironiData);
    e.target.submit();
});
</script>

<?php require_once('templates/footer.php'); ?>