<?php
if(session_status() === PHP_SESSION_NONE)
    session_start();
require_once('templates/header.php');
?>

<header class="t-hero">
    <div class="m-container">
        <div class="t-breadcrumb">
            <a href="index.php">Home</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Privacy Policy</span>
        </div>
        <h1>Informativa sulla Privacy</h1>
        <p class="desc">Ultimo aggiornamento: 23/05/2025 — ai sensi del Regolamento (UE) 2016/679 (GDPR)</p>
    </div>
</header>

<main class="m-page">
    <div class="m-container" style="max-width: 860px;">

        <div class="m-alert m-alert--info" style="margin-bottom: var(--m-6);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div>Questa informativa descrive come Matchora raccoglie, utilizza e protegge i tuoi dati personali in conformità al GDPR e alla normativa italiana applicabile.</div>
        </div>

        <div class="m-card" style="margin-bottom: var(--m-5);">
            <h2 style="font-size: 18px; margin-bottom: var(--m-4);">1. Titolare del Trattamento</h2>
            <p>Il Titolare del trattamento dei dati personali è <strong>Matchora Tornei</strong>, raggiungibile all'indirizzo email indicato nella pagina <a href="contatti.php">Contatti</a>.</p>
            <p style="margin: 0;">Il Titolare adotta misure tecniche e organizzative adeguate per garantire un livello di sicurezza appropriato al rischio del trattamento, ai sensi dell'art. 32 GDPR.</p>
        </div>

        <div class="m-card" style="margin-bottom: var(--m-5);">
            <h2 style="font-size: 18px; margin-bottom: var(--m-4);">2. Dati Raccolti</h2>
            <p>In funzione del servizio utilizzato, trattiamo le seguenti categorie di dati:</p>

            <div class="m-table-wrap" style="margin-top: var(--m-4);">
                <table class="m-table">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th>Dati</th>
                            <th>Finalità</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Dati di registrazione</strong></td>
                            <td>Nome, cognome, indirizzo email, password (cifrata)</td>
                            <td>Creazione e gestione account</td>
                        </tr>
                        <tr>
                            <td><strong>Dati di utilizzo</strong></td>
                            <td>Tornei creati, squadre iscritte, risultati inseriti</td>
                            <td>Erogazione del servizio</td>
                        </tr>
                        <tr>
                            <td><strong>Dati tecnici</strong></td>
                            <td>Indirizzo IP, tipo di browser, data/ora di accesso</td>
                            <td>Sicurezza, prevenzione frodi, log di sistema</td>
                        </tr>
                        <tr>
                            <td><strong>Cookie tecnici</strong></td>
                            <td>Session ID (cookie di sessione PHP)</td>
                            <td>Mantenimento della sessione autenticata</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p style="margin-top: var(--m-4); margin-bottom: 0;">Non raccogliamo dati appartenenti alle categorie particolari di cui all'art. 9 GDPR (dati sanitari, biometrici, relativi alla salute, ecc.), né dati di minori di 16 anni senza il consenso dei genitori o tutori.</p>
        </div>

        <div class="m-card" style="margin-bottom: var(--m-5);">
            <h2 style="font-size: 18px; margin-bottom: var(--m-4);">3. Base Giuridica del Trattamento</h2>
            <p>Il trattamento è fondato sulle seguenti basi giuridiche ai sensi dell'art. 6 GDPR:</p>
            <ul style="margin: var(--m-3) 0; padding-left: var(--m-5); color: var(--m-text-soft); line-height: 1.8;">
                <li><strong>Esecuzione di un contratto</strong> (art. 6 §1 lett. b): per erogare il servizio richiesto all'atto della registrazione.</li>
                <li><strong>Adempimento di obblighi legali</strong> (art. 6 §1 lett. c): per rispettare la normativa fiscale, contabile e di sicurezza informatica applicabile.</li>
                <li><strong>Legittimo interesse</strong> (art. 6 §1 lett. f): per garantire la sicurezza della piattaforma, prevenire abusi e frodi.</li>
                <li><strong>Consenso</strong> (art. 6 §1 lett. a): per eventuali comunicazioni promozionali o newsletter, prestato in modo libero, specifico e revocabile in qualsiasi momento.</li>
            </ul>
        </div>

        <div class="m-card" style="margin-bottom: var(--m-5);">
            <h2 style="font-size: 18px; margin-bottom: var(--m-4);">4. Finalità e Modalità del Trattamento</h2>
            <p>I dati sono trattati per le seguenti finalità:</p>
            <ul style="margin: var(--m-3) 0; padding-left: var(--m-5); color: var(--m-text-soft); line-height: 1.8;">
                <li>Registrazione, autenticazione e gestione dell'account utente.</li>
                <li>Creazione, gestione e partecipazione a tornei sportivi.</li>
                <li>Invio di comunicazioni di servizio (es. conferma email, recupero password).</li>
                <li>Prevenzione di usi fraudolenti o non autorizzati della piattaforma.</li>
                <li>Adempimento di obblighi di legge e difesa in sede giudiziaria.</li>
            </ul>
            <p style="margin-bottom: 0;">Il trattamento avviene con strumenti informatici e telematici, con logiche strettamente correlate alle finalità indicate. Non viene effettuato alcun processo decisionale automatizzato ai sensi dell'art. 22 GDPR.</p>
        </div>

        <div class="m-card" style="margin-bottom: var(--m-5);">
            <h2 style="font-size: 18px; margin-bottom: var(--m-4);">5. Conservazione dei Dati</h2>
            <p>I dati personali sono conservati per il tempo strettamente necessario alle finalità per cui sono stati raccolti:</p>
            <ul style="margin: var(--m-3) 0; padding-left: var(--m-5); color: var(--m-text-soft); line-height: 1.8;">
                <li><strong>Dati dell'account</strong>: per tutta la durata del rapporto contrattuale e, successivamente, per un massimo di <strong>10 anni</strong> ai fini di legge (art. 2220 c.c.).</li>
                <li><strong>Log di accesso e dati tecnici</strong>: massimo <strong>12 mesi</strong> salvo obblighi di conservazione estesa previsti dalla legge.</li>
                <li><strong>Dati trattati previo consenso</strong>: fino alla revoca del consenso da parte dell'interessato.</li>
            </ul>
            <p style="margin-bottom: 0;">Alla scadenza dei termini, i dati vengono cancellati in modo sicuro o resi anonimi in via definitiva.</p>
        </div>

        <div class="m-card" style="margin-bottom: var(--m-5);">
            <h2 style="font-size: 18px; margin-bottom: var(--m-4);">6. Comunicazione e Trasferimento dei Dati</h2>
            <p>I tuoi dati non sono venduti né ceduti a terzi a scopo commerciale. Possono essere comunicati esclusivamente a:</p>
            <ul style="margin: var(--m-3) 0; padding-left: var(--m-5); color: var(--m-text-soft); line-height: 1.8;">
                <li>Fornitori di servizi tecnici (hosting, database) che operano come <strong>Responsabili del trattamento</strong> ai sensi dell'art. 28 GDPR, vincolati da appositi accordi contrattuali.</li>
                <li>Autorità giudiziarie o amministrative, nei casi previsti dalla legge.</li>
            </ul>
            <p style="margin-bottom: 0;">I dati sono trattati e conservati su server localizzati all'interno dell'<strong>Unione Europea</strong>. Qualsiasi eventuale trasferimento verso paesi terzi avverrà nel rispetto degli artt. 44–49 GDPR (clausole contrattuali standard, decisioni di adeguatezza).</p>
        </div>

        <div class="m-card" style="margin-bottom: var(--m-5);">
            <h2 style="font-size: 18px; margin-bottom: var(--m-4);">7. Sicurezza del Trattamento</h2>
            <p>Adottiamo misure tecniche e organizzative adeguate ai sensi dell'art. 32 GDPR, tra cui:</p>
            <ul style="margin: var(--m-3) 0; padding-left: var(--m-5); color: var(--m-text-soft); line-height: 1.8;">
                <li>Cifratura delle password tramite algoritmi di hashing sicuri (bcrypt).</li>
                <li>Trasmissione dei dati tramite protocollo <strong>HTTPS/TLS</strong>.</li>
                <li>Accesso al database limitato al solo personale autorizzato.</li>
                <li>Sessioni autenticate con ID casuali e timeout automatico.</li>
                <li>Procedure di backup periodico e ripristino in caso di incidente.</li>
                <li>Monitoraggio dei log di accesso per rilevare attività anomale.</li>
            </ul>
            <p style="margin-bottom: 0;">In caso di violazione dei dati personali (data breach) che comporti un rischio per i diritti e le libertà degli interessati, il Titolare provvederà alla notifica all'Autorità Garante entro 72 ore ai sensi dell'art. 33 GDPR, e all'eventuale comunicazione agli interessati ai sensi dell'art. 34 GDPR.</p>
        </div>

        <div class="m-card" style="margin-bottom: var(--m-5);">
            <h2 style="font-size: 18px; margin-bottom: var(--m-4);">8. Cookie</h2>
            <p>Il sito utilizza esclusivamente <strong>cookie tecnici di sessione</strong>, necessari al funzionamento del servizio (autenticazione). Non utilizziamo cookie di profilazione, di tracciamento o di terze parti a fini pubblicitari.</p>
            <p style="margin-bottom: 0;">I cookie di sessione sono temporanei e vengono eliminati alla chiusura del browser o al logout dell'utente. Non è richiesto il consenso per i cookie tecnici strettamente necessari ai sensi del Considerando 25 della Direttiva ePrivacy e delle Linee Guida del Garante.</p>
        </div>

        <div class="m-card" style="margin-bottom: var(--m-5);">
            <h2 style="font-size: 18px; margin-bottom: var(--m-4);">9. Diritti dell'Interessato</h2>
            <p>Ai sensi degli artt. 15–22 GDPR, hai diritto di:</p>

            <div class="m-statgrid" style="margin: var(--m-4) 0;">
                <div class="m-stat">
                    <div class="m-stat__label">Art. 15</div>
                    <div style="font-size: 15px; font-weight: 600; margin-top: 4px;">Accesso</div>
                    <div style="font-size: 13px; color: var(--m-text-mute); margin-top: 4px;">Ottenere conferma e copia dei dati trattati</div>
                </div>
                <div class="m-stat">
                    <div class="m-stat__label">Art. 16</div>
                    <div style="font-size: 15px; font-weight: 600; margin-top: 4px;">Rettifica</div>
                    <div style="font-size: 13px; color: var(--m-text-mute); margin-top: 4px;">Correggere dati inesatti o incompleti</div>
                </div>
                <div class="m-stat">
                    <div class="m-stat__label">Art. 17</div>
                    <div style="font-size: 15px; font-weight: 600; margin-top: 4px;">Cancellazione</div>
                    <div style="font-size: 13px; color: var(--m-text-mute); margin-top: 4px;">Diritto all'oblio nei casi previsti</div>
                </div>
                <div class="m-stat">
                    <div class="m-stat__label">Art. 18</div>
                    <div style="font-size: 15px; font-weight: 600; margin-top: 4px;">Limitazione</div>
                    <div style="font-size: 13px; color: var(--m-text-mute); margin-top: 4px;">Limitare il trattamento in certi casi</div>
                </div>
                <div class="m-stat">
                    <div class="m-stat__label">Art. 20</div>
                    <div style="font-size: 15px; font-weight: 600; margin-top: 4px;">Portabilità</div>
                    <div style="font-size: 13px; color: var(--m-text-mute); margin-top: 4px;">Ricevere i dati in formato strutturato</div>
                </div>
                <div class="m-stat">
                    <div class="m-stat__label">Art. 21</div>
                    <div style="font-size: 15px; font-weight: 600; margin-top: 4px;">Opposizione</div>
                    <div style="font-size: 13px; color: var(--m-text-mute); margin-top: 4px;">Opporsi al trattamento per legittimo interesse</div>
                </div>
            </div>

            <p>Per esercitare i tuoi diritti, scrivi a <a href="contatti.php">contattaci</a>. Risponderemo entro 30 giorni dalla ricezione della richiesta, salvo complessità particolari (art. 12 GDPR).</p>
            <p style="margin-bottom: 0;">Hai inoltre il diritto di proporre reclamo all'<strong>Autorità Garante per la protezione dei dati personali</strong> (<a href="https://www.garanteprivacy.it" target="_blank" rel="noopener">www.garanteprivacy.it</a>), Piazza Venezia 11, 00187 Roma.</p>
        </div>

        <div class="m-card" style="margin-bottom: var(--m-5);">
            <h2 style="font-size: 18px; margin-bottom: var(--m-4);">10. Modifiche alla Privacy Policy</h2>
            <p style="margin-bottom: 0;">Il Titolare si riserva di aggiornare la presente informativa in caso di variazioni normative o modifiche al servizio. La versione aggiornata sarà sempre disponibile su questa pagina con indicazione della data di ultima revisione. In caso di modifiche sostanziali, gli utenti registrati saranno informati via email.</p>
        </div>

        <div style="text-align: center; margin-top: var(--m-8);">
            <a href="contatti.php" class="m-btn m-btn--primary">Contattaci per info sui tuoi dati</a>
            <a href="termini.php" class="m-btn m-btn--secondary" style="margin-left: var(--m-3);">Termini di servizio</a>
        </div>

    </div>
</main>

<?php require_once('templates/footer.php'); ?>
