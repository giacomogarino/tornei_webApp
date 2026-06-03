<?php
/**
 * COMPONENTS/SEGNALA_MODAL.PHP — Modal di segnalazione riutilizzabile
 * =====================================================================
 * Include questo component nelle pagine dove vuoi abilitare le segnalazioni.
 *
 * Variabili richieste prima dell'include:
 *   $modal_target_tipo  — 'utente' | 'torneo' | 'squadra'
 *   $modal_target_id    — ID della risorsa
 *   $modal_redirect     — URL di ritorno dopo la segnalazione
 *   $modal_label        — Testo del bottone (es. "Segnala torneo")
 *
 * Esempio d'uso in dettagli_torneo.php:
 *   <?php
 *   $modal_target_tipo = 'torneo';
 *   $modal_target_id   = $torneo['id'];
 *   $modal_redirect    = '/dettagli_torneo.php?id=' . $torneo['id'];
 *   $modal_label       = 'Segnala torneo';
 *   include 'components/segnala_modal.php';
 *   ?>
 */

$_modal_id = 'modal-segn-' . htmlspecialchars($modal_target_tipo) . '-' . (int)$modal_target_id;
$_motivi = [
    'Contenuto inappropriato',
    'Comportamento scorretto',
    'Spam o pubblicità',
    'Informazioni false',
    'Altro',
];
?>

<!-- Bottone trigger -->
<?php if (isset($_SESSION['login'])): ?>
<button type="button"
        class="m-btn m-btn--ghost m-btn--sm"
        style="color:var(--m-danger,#dc2626); border-color:var(--m-danger,#dc2626);"
        onclick="document.getElementById('<?= $_modal_id ?>').showModal()">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor"
         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
        <line x1="4" y1="22" x2="4" y2="15"/>
    </svg>
    <?= htmlspecialchars($modal_label ?? 'Segnala') ?>
</button>

<!-- Dialog modal -->
<dialog id="<?= $_modal_id ?>" class="m-dialog">
    <form method="post" action="/php/invia_segnalazione.php">
        <?= csrf_field() ?>
        <input type="hidden" name="target_tipo" value="<?= htmlspecialchars($modal_target_tipo) ?>">
        <input type="hidden" name="target_id"   value="<?= (int)$modal_target_id ?>">
        <input type="hidden" name="redirect"     value="<?= htmlspecialchars($modal_redirect ?? '/index.php') ?>">

        <div class="m-dialog__header">
            <h3 class="m-dialog__title">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                    <line x1="4" y1="22" x2="4" y2="15"/>
                </svg>
                <?= htmlspecialchars($modal_label ?? 'Invia segnalazione') ?>
            </h3>
            <button type="button" class="m-dialog__close"
                    onclick="document.getElementById('<?= $_modal_id ?>').close()"
                    aria-label="Chiudi">✕</button>
        </div>

        <div class="m-dialog__body">
            <p style="font-size:.875rem;color:var(--m-text-secondary);margin:0 0 var(--m-4);">
                La segnalazione sarà esaminata dal nostro team. Non è anonima verso gli admin.
            </p>

            <label style="display:block;font-weight:600;font-size:.875rem;margin-bottom:var(--m-2);">
                Motivo
            </label>
            <!-- Selezione rapida -->
            <div class="m-segn-chips">
                <?php foreach ($_motivi as $m): ?>
                    <button type="button" class="m-segn-chip"
                            onclick="document.getElementById('motivo-<?= $_modal_id ?>').value = this.dataset.val;
                                     document.querySelectorAll('#<?= $_modal_id ?> .m-segn-chip')
                                       .forEach(b=>b.classList.remove('m-segn-chip--active'));
                                     this.classList.add('m-segn-chip--active');"
                            data-val="<?= htmlspecialchars($m) ?>">
                        <?= htmlspecialchars($m) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <!-- Campo testo libero -->
            <textarea id="motivo-<?= $_modal_id ?>"
                      name="motivo" rows="3" maxlength="200" required
                      placeholder="Descrivi il problema (max 200 caratteri)…"
                      class="m-input m-segn-textarea"
                      oninput="document.getElementById('cnt-<?= $_modal_id ?>').textContent=this.value.length"></textarea>
            <div style="text-align:right;font-size:.75rem;color:var(--m-text-secondary);">
                <span id="cnt-<?= $_modal_id ?>">0</span>/200
            </div>
        </div>

        <div class="m-dialog__footer">
            <button type="button" class="m-btn m-btn--ghost"
                    onclick="document.getElementById('<?= $_modal_id ?>').close()">Annulla</button>
            <button type="submit" class="m-btn"
                    style="background:var(--m-danger,#dc2626);color:#fff;border-color:transparent;">
                Invia segnalazione
            </button>
        </div>
    </form>
</dialog>

<style>
/* ── Dialog ──────────────────────────────────────────────────────── */
.m-dialog {
    border: 1px solid var(--m-border);
    border-radius: var(--m-radius-lg);
    padding: 0;
    max-width: 440px;
    width: 92vw;
    box-shadow: 0 8px 32px rgba(0,0,0,.15);
    background: var(--m-surface);
}
.m-dialog::backdrop { background: rgba(0,0,0,.45); }
.m-dialog__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--m-4) var(--m-5);
    border-bottom: 1px solid var(--m-border);
}
.m-dialog__title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
}
.m-dialog__close {
    background: none;
    border: none;
    font-size: 1rem;
    cursor: pointer;
    color: var(--m-text-secondary);
    line-height: 1;
    padding: 4px;
}
.m-dialog__body   { padding: var(--m-5); }
.m-dialog__footer {
    display: flex;
    justify-content: flex-end;
    gap: var(--m-3);
    padding: var(--m-4) var(--m-5);
    border-top: 1px solid var(--m-border);
}
/* ── Chip motivi ─────────────────────────────────────────────────── */
.m-segn-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: var(--m-3);
}
.m-segn-chip {
    padding: 4px 12px;
    border: 1px solid var(--m-border);
    border-radius: 999px;
    background: none;
    font-size: .78rem;
    cursor: pointer;
    color: var(--m-text);
    transition: all .12s;
}
.m-segn-chip:hover,
.m-segn-chip--active {
    background: #fee2e2;
    border-color: #dc2626;
    color: #dc2626;
    font-weight: 600;
}
/* ── Textarea ────────────────────────────────────────────────────── */
.m-segn-textarea {
    width: 100%;
    box-sizing: border-box;
    margin-top: var(--m-2);
    padding: 8px 12px;
    border: 1px solid var(--m-border);
    border-radius: var(--m-radius);
    font-family: inherit;
    font-size: .875rem;
    resize: vertical;
    background: var(--m-surface);
    color: var(--m-text);
}
.m-segn-textarea:focus {
    outline: none;
    border-color: var(--m-primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}
</style>
<?php endif; // isset($_SESSION['login']) ?>