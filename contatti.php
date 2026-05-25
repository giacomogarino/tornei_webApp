<?php
$page_title       = 'Contatti';
$page_description = 'Contatta il team di Matchora per supporto, segnalazioni o richieste relative alla privacy.';

if (session_status() === PHP_SESSION_NONE) {
    require_once './php/helpers/session.php';
    session_secure_start();
}
require_once './templates/header.php';

$msgs = [
    'inviato'              => ['type' => 'success', 'text' => 'Messaggio inviato! Riceverai anche una email di conferma. Ti risponderemo entro 48 ore lavorative.'],
    'campiVuoti'           => ['type' => 'danger',  'text' => 'Compila tutti i campi obbligatori.'],
    'emailNonValida'       => ['type' => 'danger',  'text' => 'L\'indirizzo email inserito non è valido.'],
    'messaggioTroppoCorto' => ['type' => 'danger',  'text' => 'Il messaggio è troppo breve. Descrivi la tua richiesta.'],
    'privacyNonAccettata'  => ['type' => 'danger',  'text' => 'Devi accettare l\'Informativa sulla Privacy per inviare il messaggio.'],
    'oggettoNonValido'     => ['type' => 'danger',  'text' => 'Seleziona un argomento valido.'],
    'errMsg'               => ['type' => 'danger',  'text' => 'Errore nell\'invio. Riprova o scrivici direttamente via email.'],
];
$msg = $_GET['msg'] ?? null;
?>

<header class="t-hero">
    <div class="m-container">
        <div class="t-breadcrumb">
            <a href="index.php">Home</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Contatti</span>
        </div>
        <h1>Contattaci</h1>
        <p class="desc">Hai domande, segnalazioni o richieste? Siamo qui per aiutarti.</p>
    </div>
</header>

<main class="m-page">
    <div class="m-container">

        <?php if ($msg && isset($msgs[$msg])): $m = $msgs[$msg]; ?>
            <div class="m-alert m-alert--<?= $m['type'] ?>" style="margin-bottom:var(--m-5);">
                <?php if ($m['type'] === 'success'): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                <?php else: ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                <?php endif; ?>
                <div><?= htmlspecialchars($m['text'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <div class="c-grid">
            <!-- Form contatti -->
            <div>
                <div class="m-card">
                    <h2 style="font-size:20px;margin-bottom:var(--m-2);">Inviaci un messaggio</h2>
                    <p style="margin-bottom:var(--m-5);">Risponderemo entro 48 ore lavorative.</p>

                    <form method="POST" action="php/invia_contatti.php"
                          style="display:flex;flex-direction:column;gap:var(--m-4);">
                        <?= csrf_field() ?>

                        <div class="c-name-grid">
                            <div class="m-field">
                                <label class="m-label" for="nome">Nome *</label>
                                <input class="m-input" type="text" id="nome" name="nome"
                                       placeholder="Mario" required>
                            </div>
                            <div class="m-field">
                                <label class="m-label" for="cognome">Cognome *</label>
                                <input class="m-input" type="text" id="cognome" name="cognome"
                                       placeholder="Rossi" required>
                            </div>
                        </div>

                        <div class="m-field">
                            <label class="m-label" for="email">Email *</label>
                            <input class="m-input" type="email" id="email" name="email"
                                   placeholder="mario.rossi@email.com" required>
                        </div>

                        <div class="m-field">
                            <label class="m-label" for="oggetto">Oggetto *</label>
                            <select class="m-select" id="oggetto" name="oggetto" required>
                                <option value="" disabled selected>Seleziona un argomento</option>
                                <option value="supporto">Supporto tecnico</option>
                                <option value="account">Problema con l'account</option>
                                <option value="torneo">Problema con un torneo</option>
                                <option value="privacy">Richiesta relativa alla privacy</option>
                                <option value="segnalazione">Segnalazione abuso</option>
                                <option value="altro">Altro</option>
                            </select>
                        </div>

                        <div class="m-field">
                            <label class="m-label" for="messaggio">Messaggio *</label>
                            <textarea class="m-textarea" id="messaggio" name="messaggio"
                                      placeholder="Descrivi la tua richiesta nel dettaglio..."
                                      required style="min-height:140px;"></textarea>
                        </div>

                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--m-text-soft);">
                            <input class="m-checkbox" type="checkbox" id="privacy_ok" name="privacy_ok"
                                   value="1" required style="margin-top:2px;flex-shrink:0;">
                            <label for="privacy_ok">
                                Ho letto e accetto l'<a href="privacy.php">Informativa sulla Privacy</a>.
                                I miei dati saranno trattati esclusivamente per rispondere alla mia
                                richiesta ai sensi dell'art.&nbsp;6 §1 lett.&nbsp;b) GDPR.
                            </label>
                        </div>

                        <div>
                            <button type="submit" class="m-btn m-btn--primary m-btn--lg"
                                    style="width:100%;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13"/>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                </svg>
                                Invia messaggio
                            </button>
                            <p style="margin:var(--m-3) 0 0;font-size:12px;color:var(--m-text-mute);text-align:center;">
                                * Campi obbligatori — Risposta entro 48 ore lavorative
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Colonna info -->
            <div style="display:flex;flex-direction:column;gap:var(--m-4);">
                <div class="m-card">
                    <div style="display:flex;align-items:flex-start;gap:var(--m-3);">
                        <div style="width:44px;height:44px;border-radius:12px;background:var(--m-primary-50);color:var(--m-primary-600);display:grid;place-items:center;flex-shrink:0;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--m-text-mute);margin-bottom:4px;">Email</div>
                            <a href="mailto:matchora.torneo@gmail.com" style="font-weight:600;font-size:15px;">matchora.torneo@gmail.com</a>
                            <div style="font-size:12px;color:var(--m-text-mute);margin-top:3px;">Per richieste generali</div>
                        </div>
                    </div>
                </div>

                <div class="m-card">
                    <div style="display:flex;align-items:flex-start;gap:var(--m-3);">
                        <div style="width:44px;height:44px;border-radius:12px;background:var(--m-info-50);color:var(--m-info-500);display:grid;place-items:center;flex-shrink:0;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--m-text-mute);margin-bottom:4px;">Privacy &amp; Dati GDPR</div>
                            <a href="mailto:matchora.torneo@gmail.com" style="font-weight:600;font-size:15px;">matchora.torneo@gmail.com</a>
                            <div style="font-size:12px;color:var(--m-text-mute);margin-top:3px;">Esercizio diritti GDPR artt.&nbsp;15–22</div>
                        </div>
                    </div>
                </div>

                <div class="m-card">
                    <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--m-text-mute);margin-bottom:var(--m-3);">Tempi di risposta</div>
                    <div style="display:flex;flex-direction:column;gap:var(--m-3);">
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:14px;">
                            <span style="color:var(--m-text-soft);">Supporto tecnico</span>
                            <span class="m-badge m-badge--info">48 ore</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:14px;">
                            <span style="color:var(--m-text-soft);">Richieste privacy (GDPR)</span>
                            <span class="m-badge m-badge--success">30 giorni</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:14px;">
                            <span style="color:var(--m-text-soft);">Segnalazioni abuso</span>
                            <span class="m-badge m-badge--warn">24 ore</span>
                        </div>
                    </div>
                </div>

                <div class="m-card">
                    <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--m-text-mute);margin-bottom:var(--m-3);">Link utili</div>
                    <div style="display:flex;flex-direction:column;gap:var(--m-2);">
                        <a href="team.php" style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;padding:6px 0;color:var(--m-text-soft);">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                            Il nostro Team
                        </a>
                        <a href="privacy.php" style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;padding:6px 0;color:var(--m-text-soft);">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                            Informativa sulla Privacy
                        </a>
                        <a href="termini.php" style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;padding:6px 0;color:var(--m-text-soft);">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                            Termini di Servizio
                        </a>
                        <a href="https://www.garanteprivacy.it" target="_blank" rel="noopener noreferrer"
                           style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;padding:6px 0;color:var(--m-text-soft);">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                            Garante Privacy (GPDP)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.c-grid { display:grid; grid-template-columns:1fr 380px; gap:var(--m-6); align-items:start; }
.c-name-grid { display:grid; grid-template-columns:1fr 1fr; gap:var(--m-4); }
@media (max-width:860px) { .c-grid { grid-template-columns:1fr; } }
@media (max-width:480px) { .c-name-grid { grid-template-columns:1fr; } }
</style>

<?php require_once './templates/footer.php'; ?>
