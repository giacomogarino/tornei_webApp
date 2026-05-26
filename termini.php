<?php
$page_title       = 'Termini di Servizio';
$page_description = 'Termini e condizioni di utilizzo della piattaforma Matchora Tornei.';

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
            <span>Termini di Servizio</span>
        </div>
        <h1>Termini di Servizio</h1>
        <p class="desc">
            Ultimo aggiornamento: <strong><?= TERMS_VERSION ?></strong>
            &mdash; leggili con attenzione prima di utilizzare Matchora.
        </p>
    </div>
</header>

<main class="m-page">
    <div class="m-container" style="max-width:860px;">

        <div class="m-alert m-alert--warn" style="margin-bottom:var(--m-6);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9"/>
                <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div>
                Utilizzando Matchora dichiari di aver letto, compreso e accettato integralmente
                i presenti Termini di Servizio. Se non li accetti, ti invitiamo a non utilizzare
                il servizio.
            </div>
        </div>

        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">1. Il Servizio</h2>
            <p>
                <strong>Matchora Tornei</strong> è una piattaforma online che consente agli utenti
                registrati di creare, gestire e partecipare a tornei sportivi amatoriali.
                Il servizio è fornito così com'è, a titolo gratuito nella versione base,
                ed è destinato a un utilizzo personale, ricreativo e non commerciale.
            </p>
            <p style="margin-bottom:0;">
                Matchora si riserva il diritto di modificare, sospendere o interrompere il servizio
                in qualsiasi momento, con o senza preavviso, senza responsabilità nei confronti
                degli utenti.
            </p>
        </div>

        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">2. Registrazione e Account</h2>
            <p>
                Per accedere alle funzionalità principali è necessario registrarsi fornendo
                dati veritieri, accurati e aggiornati. Ogni utente può possedere un solo account.
            </p>
            <p>
                L'utente è responsabile della <strong>riservatezza delle proprie credenziali</strong>
                e di tutte le attività svolte tramite il proprio account. In caso di accesso
                non autorizzato, è obbligatorio notificarlo tempestivamente tramite la pagina
                <a href="contatti.php">Contatti</a>.
            </p>
            <p style="margin-bottom:0;">
                Matchora si riserva il diritto di sospendere o cancellare account che violino
                i presenti Termini, previa o senza comunicazione a seconda della gravità.
            </p>
        </div>

        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">3. Regole di Utilizzo</h2>
            <p>Utilizzando Matchora l'utente si impegna a:</p>
            <ul style="margin:var(--m-3) 0;padding-left:var(--m-5);color:var(--m-text-soft);line-height:1.9;">
                <li>Non inserire dati falsi, fuorvianti o relativi a terzi senza consenso.</li>
                <li>Non utilizzare la piattaforma per scopi illegali, offensivi o discriminatori.</li>
                <li>Non tentare di accedere in modo non autorizzato ai sistemi, ai database o agli account altrui.</li>
                <li>Non utilizzare bot, scraper o strumenti automatizzati per interagire con il servizio.</li>
                <li>Non caricare o trasmettere malware, virus o codice dannoso.</li>
                <li>Non ostacolare il normale funzionamento della piattaforma (es. attacchi DoS/DDoS).</li>
                <li>Non violare i diritti di proprietà intellettuale di Matchora o di terzi.</li>
                <li>Rispettare le regole sportive e il fair play nelle competizioni gestite tramite la piattaforma.</li>
            </ul>
            <p style="margin-bottom:0;">
                La violazione comporta la sospensione immediata dell'account e, nei casi più gravi,
                la segnalazione alle autorità competenti.
            </p>
        </div>

        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">4. Contenuti degli Utenti</h2>
            <p>
                Gli utenti sono <strong>unici responsabili</strong> dei contenuti inseriti sulla
                piattaforma (nomi di tornei, squadre, risultati, descrizioni). Inserendo contenuti,
                l'utente dichiara di averne il diritto e garantisce che non violano diritti di terzi
                né la normativa vigente.
            </p>
            <p style="margin-bottom:0;">
                Matchora si riserva il diritto di rimuovere qualsiasi contenuto inappropriato
                o in violazione dei presenti Termini, senza obbligo di preavviso.
            </p>
        </div>

        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">5. Proprietà Intellettuale</h2>
            <p>
                Tutti i diritti relativi a Matchora — logo, design, codice sorgente, interfaccia
                grafica e testi — sono di esclusiva proprietà di
                <strong><?= TITOLARE_NOME ?></strong>
                e sono protetti dalla normativa sul diritto d'autore.
            </p>
            <p style="margin-bottom:0;">
                È vietata qualsiasi riproduzione, distribuzione o utilizzo commerciale del
                materiale di Matchora senza autorizzazione scritta.
            </p>
        </div>

        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">6. Limitazione di Responsabilità</h2>
            <p>Matchora non è responsabile per:</p>
            <ul style="margin:var(--m-3) 0;padding-left:var(--m-5);color:var(--m-text-soft);line-height:1.9;">
                <li>Interruzioni o malfunzionamenti dovuti a cause tecniche o eventi fuori dal controllo di Matchora.</li>
                <li>Perdita di dati derivante da guasti tecnici, purché siano state adottate misure ragionevoli di backup.</li>
                <li>Contenuti inseriti dagli utenti e danni derivanti dalla condotta di altri utenti.</li>
                <li>Danni indiretti, consequenziali o perdite di profitto.</li>
                <li>Risultati sportivi contestati o controversie tra partecipanti.</li>
            </ul>
            <p style="margin-bottom:0;">
                La responsabilità complessiva di Matchora non potrà in alcun caso superare
                l'importo eventualmente pagato dall'utente negli ultimi 12 mesi.
            </p>
        </div>

        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">7. Privacy e Dati Personali</h2>
            <p style="margin-bottom:0;">
                Il trattamento dei dati personali è disciplinato dalla nostra
                <a href="privacy.php">Informativa sulla Privacy</a>,
                redatta in conformità al Regolamento (UE) 2016/679 (GDPR).
                L'Informativa è parte integrante dei presenti Termini.
            </p>
        </div>

        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">8. Modifiche ai Termini</h2>
            <p>
                Matchora si riserva il diritto di modificare i presenti Termini in qualsiasi momento.
                Le modifiche saranno pubblicate su questa pagina con la data di aggiornamento.
                Per modifiche sostanziali, gli utenti registrati saranno informati via email con
                almeno <strong>15 giorni di preavviso</strong>.
            </p>
            <p style="margin-bottom:0;">
                Continuare ad utilizzare il servizio dopo la pubblicazione delle modifiche
                costituisce accettazione dei nuovi Termini. Se non accetti le modifiche,
                puoi cancellare il tuo account dalla pagina <a href="profilo.php">Profilo</a>.
            </p>
        </div>

        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">9. Legge Applicabile e Foro Competente</h2>
            <p style="margin-bottom:0;">
                I presenti Termini sono regolati dalla legge italiana. Per qualsiasi controversia
                sarà competente il Tribunale del luogo di residenza del Titolare, fatto salvo
                quanto previsto dalla normativa a tutela del consumatore (D.Lgs. 206/2005 —
                Codice del Consumo).
            </p>
        </div>

        <div class="m-card" style="margin-bottom:var(--m-5);">
            <h2 style="font-size:18px;margin-bottom:var(--m-4);">10. Contatti</h2>
            <p style="margin-bottom:0;">
                Per qualsiasi domanda sui presenti Termini,
                <a href="contatti.php">contattaci</a> tramite la pagina dedicata.
            </p>
        </div>

        <div style="text-align:center;margin-top:var(--m-8);">
            <a href="privacy.php" class="m-btn m-btn--secondary">Privacy Policy</a>
            <a href="contatti.php" class="m-btn m-btn--primary" style="margin-left:var(--m-3);">Contattaci</a>
        </div>

    </div>
</main>

<?php require_once './templates/footer.php'; ?>
