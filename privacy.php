<?php
$page_title       = 'Informativa sulla Privacy';
$page_description = 'Informativa sulla privacy di Matchora Tornei ai sensi del GDPR (Reg. UE 2016/679).';

if (session_status() === PHP_SESSION_NONE) {
    require_once './php/helpers/session.php';
    session_secure_start();
}
require_once './conf/app_config.php';
require_once './templates/header.php';
?>

<header class="t-hero">
    <div class="m-container">
        <div class="t-breadcrumb">
            <a href="index.php">Home</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Privacy Policy</span>
        </div>
        <h1>Informativa sulla Privacy</h1>
        <p class="desc">
            Ultimo aggiornamento: <strong><?= PRIVACY_VERSION ?></strong>
            &mdash; ai sensi del Regolamento (UE) 2016/679 (GDPR)
        </p>
    </div>
</header>

<main class="m-page">
    <div class="m-container" style="max-width:860px;">

        <div class="m-alert m-alert--info" style="margin-bottom:var(--m-6);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9"/>
                <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div>
                Questa informativa descrive come Matchora raccoglie, utilizza e protegge i tuoi
                dati personali in conformità al GDPR e alla normativa italiana applicabile.
            </div>
        </div>

        <!-- 1. Titolare -->
        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">1. Titolare del Trattamento</h2>
            <p>
                Il Titolare del trattamento dei dati personali è
                <strong><?= TITOLARE_NOME ?></strong>,
                con sede in <strong><?= TITOLARE_INDIRIZZO ?></strong>,
                raggiungibile all'indirizzo email
                <a href="mailto:<?= TITOLARE_EMAIL ?>"><?= TITOLARE_EMAIL ?></a>.
            </p>
            <p style="margin:0;">
                Il Titolare adotta misure tecniche e organizzative adeguate per garantire
                un livello di sicurezza appropriato al rischio del trattamento,
                ai sensi dell'art.&nbsp;32 GDPR.
            </p>
        </div>

        <!-- 2. Dati raccolti -->
        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">2. Dati Raccolti</h2>
            <p>In funzione del servizio utilizzato, trattiamo le seguenti categorie di dati:</p>
            <div class="m-table-wrap" style="margin-top:var(--m-4);">
                <table class="m-table">
                    <thead>
                        <tr><th>Categoria</th><th>Dati</th><th>Finalità</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Registrazione</strong></td>
                            <td>Nome, cognome, email, password (bcrypt)</td>
                            <td>Creazione e gestione account</td>
                        </tr>
                        <tr>
                            <td><strong>Login Google</strong></td>
                            <td>Nome, cognome, email, Google ID (forniti da Google OAuth)</td>
                            <td>Autenticazione tramite account Google</td>
                        </tr>
                        <tr>
                            <td><strong>Utilizzo</strong></td>
                            <td>Tornei creati, squadre iscritte, risultati inseriti</td>
                            <td>Erogazione del servizio</td>
                        </tr>
                        <tr>
                            <td><strong>Tecnici</strong></td>
                            <td>Indirizzo IP, tipo di browser, data/ora di accesso</td>
                            <td>Sicurezza, prevenzione frodi, log di sistema</td>
                        </tr>
                        <tr>
                            <td><strong>Cookie tecnici</strong></td>
                            <td>Session ID PHP (cookie di sessione)</td>
                            <td>Mantenimento della sessione autenticata</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p style="margin-top:var(--m-4);margin-bottom:0;">
                Non raccogliamo dati appartenenti alle categorie particolari di cui all'art.&nbsp;9
                GDPR, né dati di minori di 16 anni senza il consenso dei genitori o tutori.
            </p>
        </div>

        <!-- 3. Base giuridica -->
        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">3. Base Giuridica del Trattamento</h2>
            <p>Il trattamento è fondato sulle seguenti basi giuridiche ai sensi dell'art.&nbsp;6 GDPR:</p>
            <ul style="margin:var(--m-3) 0;padding-left:var(--m-5);color:var(--m-text-soft);line-height:1.8;">
                <li><strong>Esecuzione di un contratto</strong> (art.&nbsp;6 §1 lett.&nbsp;b): per erogare il servizio richiesto all'atto della registrazione.</li>
                <li><strong>Adempimento di obblighi legali</strong> (art.&nbsp;6 §1 lett.&nbsp;c): per rispettare la normativa fiscale, contabile e di sicurezza informatica.</li>
                <li><strong>Legittimo interesse</strong> (art.&nbsp;6 §1 lett.&nbsp;f): per garantire la sicurezza della piattaforma e prevenire abusi.</li>
                <li><strong>Consenso</strong> (art.&nbsp;6 §1 lett.&nbsp;a): per eventuali comunicazioni promozionali o newsletter, prestato liberamente e revocabile in qualsiasi momento.</li>
            </ul>
        </div>

        <!-- 4. Finalità -->
        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">4. Finalità e Modalità del Trattamento</h2>
            <ul style="margin:var(--m-3) 0;padding-left:var(--m-5);color:var(--m-text-soft);line-height:1.8;">
                <li>Registrazione, autenticazione e gestione dell'account utente.</li>
                <li>Creazione, gestione e partecipazione a tornei sportivi.</li>
                <li>Invio di comunicazioni di servizio (conferma email, recupero password).</li>
                <li>Prevenzione di usi fraudolenti o non autorizzati della piattaforma.</li>
                <li>Adempimento di obblighi di legge e difesa in sede giudiziaria.</li>
            </ul>
            <p style="margin-bottom:0;">
                Il trattamento avviene con strumenti informatici con logiche strettamente correlate
                alle finalità indicate. Non viene effettuato alcun processo decisionale automatizzato
                ai sensi dell'art.&nbsp;22 GDPR.
            </p>
        </div>

        <!-- 5. Conservazione -->
        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">5. Conservazione dei Dati</h2>
            <ul style="margin:var(--m-3) 0;padding-left:var(--m-5);color:var(--m-text-soft);line-height:1.8;">
                <li><strong>Dati dell'account</strong>: per tutta la durata del rapporto e, successivamente, fino a <strong>10 anni</strong> ai fini di legge (art.&nbsp;2220 c.c.).</li>
                <li><strong>Log di accesso e dati tecnici</strong>: massimo <strong>12 mesi</strong>, salvo obblighi di conservazione estesa.</li>
                <li><strong>Dati trattati previo consenso</strong>: fino alla revoca del consenso.</li>
            </ul>
            <p style="margin-bottom:0;">
                Alla scadenza, i dati vengono cancellati in modo sicuro o resi anonimi definitivamente.
            </p>
        </div>

        <!-- 6. Comunicazione e trasferimento -->
        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">6. Comunicazione e Trasferimento dei Dati</h2>
            <p>I tuoi dati non sono venduti né ceduti a terzi a scopo commerciale. Possono essere comunicati a:</p>
            <ul style="margin:var(--m-3) 0;padding-left:var(--m-5);color:var(--m-text-soft);line-height:1.8;">
                <li>Fornitori di servizi tecnici (hosting, database) che operano come <strong>Responsabili del trattamento</strong> ai sensi dell'art.&nbsp;28 GDPR.</li>
                <li><strong>Google LLC</strong>, nella misura strettamente necessaria per il servizio "Accedi con Google" (Google OAuth 2.0). Google può trattare dati negli USA sulla base delle Clausole Contrattuali Standard (SCC) approvate dalla Commissione UE. Per maggiori informazioni: <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Privacy Policy di Google</a>.</li>
                <li>Autorità giudiziarie o amministrative, nei casi previsti dalla legge.</li>
            </ul>
            <p style="margin-bottom:0;">
                I dati sono trattati su server localizzati nell'<strong>Unione Europea</strong>.
                Eventuali trasferimenti verso paesi terzi avvengono nel rispetto degli
                artt.&nbsp;44–49 GDPR.
            </p>
        </div>

        <!-- 7. Sicurezza -->
        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">7. Sicurezza del Trattamento</h2>
            <p>Adottiamo misure tecniche e organizzative adeguate ai sensi dell'art.&nbsp;32 GDPR:</p>
            <ul style="margin:var(--m-3) 0;padding-left:var(--m-5);color:var(--m-text-soft);line-height:1.8;">
                <li>Cifratura delle password tramite bcrypt (hashing sicuro).</li>
                <li>Trasmissione dei dati tramite protocollo <strong>HTTPS/TLS</strong>.</li>
                <li>Cookie di sessione con flag <code>HttpOnly</code>, <code>Secure</code> e <code>SameSite=Lax</code>.</li>
                <li>Protezione CSRF su tutti i form.</li>
                <li>Font e risorse statiche serviti dal nostro server (nessuna connessione a CDN di terzi dal browser dell'utente).</li>
                <li>Accesso al database limitato al solo personale autorizzato.</li>
                <li>Sessioni autenticate con rigenerazione dell'ID e timeout automatico.</li>
                <li>Monitoraggio dei log di accesso per rilevare attività anomale.</li>
            </ul>
            <p style="margin-bottom:0;">
                In caso di violazione dei dati (data breach) con rischio per i diritti degli interessati,
                il Titolare notificherà il Garante entro 72 ore (art.&nbsp;33 GDPR) ed eventualmente
                gli interessati (art.&nbsp;34 GDPR).
            </p>
        </div>

        <!-- 8. Cookie -->
        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">8. Cookie</h2>
            <p>
                Il sito utilizza esclusivamente <strong>cookie tecnici di sessione</strong>,
                necessari al funzionamento del servizio (autenticazione). Non utilizziamo
                cookie di profilazione, di tracciamento o di terze parti a fini pubblicitari.
            </p>
            <p>
                I font tipografici sono scaricati lato server e serviti direttamente dal nostro
                hosting: il browser dell'utente non si connette mai a Google Fonts o ad altri
                CDN di terze parti. Questo garantisce la conformità alla Direttiva ePrivacy e
                ai provvedimenti del Garante italiano.
            </p>
            <p style="margin-bottom:0;">
                I cookie di sessione sono temporanei e vengono eliminati alla chiusura del
                browser o al logout. Non è richiesto il consenso per i cookie tecnici
                strettamente necessari (Considerando 25 Direttiva ePrivacy; Linee Guida Garante).
            </p>
        </div>

        <!-- 9. Diritti -->
        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">9. Diritti dell'Interessato</h2>
            <p>Ai sensi degli artt.&nbsp;15–22 GDPR, hai diritto di:</p>
            <div class="m-statgrid" style="margin:var(--m-4) 0;">
                <div class="m-stat">
                    <div class="m-stat__label">Art.&nbsp;15</div>
                    <div style="font-size:15px;font-weight:600;margin-top:4px;">Accesso</div>
                    <div style="font-size:13px;color:var(--m-text-mute);margin-top:4px;">Ottenere copia dei dati trattati</div>
                </div>
                <div class="m-stat">
                    <div class="m-stat__label">Art.&nbsp;16</div>
                    <div style="font-size:15px;font-weight:600;margin-top:4px;">Rettifica</div>
                    <div style="font-size:13px;color:var(--m-text-mute);margin-top:4px;">Correggere dati inesatti</div>
                </div>
                <div class="m-stat">
                    <div class="m-stat__label">Art.&nbsp;17</div>
                    <div style="font-size:15px;font-weight:600;margin-top:4px;">Cancellazione</div>
                    <div style="font-size:13px;color:var(--m-text-mute);margin-top:4px;">Diritto all'oblio — disponibile nel <a href="profilo.php">Profilo</a></div>
                </div>
                <div class="m-stat">
                    <div class="m-stat__label">Art.&nbsp;18</div>
                    <div style="font-size:15px;font-weight:600;margin-top:4px;">Limitazione</div>
                    <div style="font-size:13px;color:var(--m-text-mute);margin-top:4px;">Limitare il trattamento</div>
                </div>
                <div class="m-stat">
                    <div class="m-stat__label">Art.&nbsp;20</div>
                    <div style="font-size:15px;font-weight:600;margin-top:4px;">Portabilità</div>
                    <div style="font-size:13px;color:var(--m-text-mute);margin-top:4px;">Esporta i tuoi dati JSON dal <a href="profilo.php">Profilo</a></div>
                </div>
                <div class="m-stat">
                    <div class="m-stat__label">Art.&nbsp;21</div>
                    <div style="font-size:15px;font-weight:600;margin-top:4px;">Opposizione</div>
                    <div style="font-size:13px;color:var(--m-text-mute);margin-top:4px;">Opporsi al trattamento</div>
                </div>
            </div>
            <p>
                Per esercitare i tuoi diritti, <a href="contatti.php">contattaci</a>.
                Risponderemo entro 30 giorni dalla ricezione (art.&nbsp;12 GDPR).
            </p>
            <p style="margin-bottom:0;">
                Hai inoltre il diritto di proporre reclamo all'
                <strong>Autorità Garante per la protezione dei dati personali</strong>
                (<a href="https://www.garanteprivacy.it" target="_blank" rel="noopener noreferrer">www.garanteprivacy.it</a>),
                Piazza Venezia 11, 00187 Roma.
            </p>
        </div>

        <!-- 10. Modifiche -->
        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">10. Modifiche alla Privacy Policy</h2>
            <p style="margin-bottom:0;">
                Il Titolare si riserva di aggiornare la presente informativa in caso di variazioni
                normative o modifiche al servizio. La versione aggiornata sarà sempre disponibile
                su questa pagina con la data di ultima revisione.
                In caso di modifiche sostanziali, gli utenti registrati saranno informati via email.
            </p>
        </div>

        <div style="text-align:center;margin-top:var(--m-8);">
            <a href="contatti.php" class="m-btn m-btn--primary">Contattaci per info sui tuoi dati</a>
            <a href="termini.php" class="m-btn m-btn--secondary" style="margin-left:var(--m-3);">Termini di servizio</a>
        </div>

    </div>
</main>

<?php require_once './templates/footer.php'; ?>
