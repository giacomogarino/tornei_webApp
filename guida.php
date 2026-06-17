<?php
/**
 * GUIDA.PHP — Pagina di aiuto e documentazione per Matchora Tornei
 */

$page_title       = 'Guida — Matchora Tornei';
$page_description = 'Guida completa su come usare Matchora: registrazione, creazione tornei, iscrizione squadre, gestione partite e FAQ.';

// Nessun extra_css necessario perché usiamo i CSS globali del sito
// $extra_css = ['css/guida.css']; (opzionale, se vuoi stili specifici)

require_once 'templates/header.php';
?>

<header class="t-hero">
    <div class="m-container">
        <div class="t-breadcrumb">
            <a href="/index.php">Home</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Guida</span>
        </div>
        <h1>Guida a Matchora Tornei</h1>
        <p class="desc">
            Tutto quello che devi sapere per organizzare e partecipare a tornei sportivi in modo semplice e gratuito.
        </p>
    </div>
</header>

<main class="m-page">
    
    <div class="m-container" style="max-width: 1000px;">

        <!-- Indice rapido -->
        <div class="guide-index" style="display: flex; flex-wrap: wrap; gap: var(--m-2); margin-bottom: var(--m-6);">
            <a href="#registrazione" class="m-btn m-btn--secondary m-btn--sm">📝 Registrazione</a>
            <a href="#creare-torneo" class="m-btn m-btn--secondary m-btn--sm">🏆 Creare un torneo</a>
            <a href="#tipi-torneo" class="m-btn m-btn--secondary m-btn--sm">📊 Tipi di torneo</a>
            <a href="#iscrivere-squadra" class="m-btn m-btn--secondary m-btn--sm">👥 Iscrivere una squadra</a>
            <a href="#gestire-partite" class="m-btn m-btn--secondary m-btn--sm">⚽ Gestire le partite</a>
            <a href="#tornei-privati" class="m-btn m-btn--secondary m-btn--sm">🔒 Tornei privati</a>
            <a href="#seguire-tornei" class="m-btn m-btn--secondary m-btn--sm">⭐ Seguire un torneo</a>
            <a href="#pranzi" class="m-btn m-btn--secondary m-btn--sm">🍽️ Gestione pranzi</a>
            <a href="#recensioni" class="m-btn m-btn--secondary m-btn--sm">⭐ Recensioni</a>
            <a href="#faq" class="m-btn m-btn--secondary m-btn--sm">❓ FAQ</a>
        </div>

        <!-- 1. Registrazione -->
        <div id="registrazione" class="m-card" style="margin-bottom: var(--m-5); scroll-margin-top: 80px;">
            <h2 style="font-size: 20px; margin-bottom: var(--m-4); display: flex; align-items: center; gap: 8px;">
                <span style="background: var(--m-primary-100); padding: 8px 12px; border-radius: 12px;">📝</span>
                1. Registrazione e primo accesso
            </h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--m-4);">
                <div>
                    <h3 style="font-size: 16px; margin-bottom: var(--m-2);">Registrazione standard</h3>
                    <ol style="margin: 0; padding-left: 18px; color: var(--m-text-soft); line-height: 1.8;">
                        <li>Clicca su <strong>"Registrati"</strong> in alto a destra</li>
                        <li>Inserisci nome, cognome, email e password (min. 8 caratteri)</li>
                        <li>Accetta l'Informativa sulla Privacy</li>
                        <li>Clicca su <strong>"Crea il mio account"</strong></li>
                        <li>Controlla la tua email e clicca sul link di conferma</li>
                    </ol>
                </div>
                <div>
                    <h3 style="font-size: 16px; margin-bottom: var(--m-2);">Registrazione con Google</h3>
                    <ol style="margin: 0; padding-left: 18px; color: var(--m-text-soft); line-height: 1.8;">
                        <li>Clicca su <strong>"Registrati con Google"</strong></li>
                        <li>Scegli il tuo account Google</li>
                        <li>Autorizza l'accesso (email e profilo base)</li>
                        <li>Sei automaticamente registrato e autenticato</li>
                    </ol>
                </div>
            </div>
            <div class="m-alert m-alert--info" style="margin-top: var(--m-4);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                <div>⚠️ Dopo la registrazione devi <strong>confermare l'email</strong> cliccando sul link ricevuto. Senza conferma non puoi accedere.</div>
            </div>
        </div>

        <!-- 2. Creare un torneo -->
        <div id="creare-torneo" class="m-card" style="margin-bottom: var(--m-5); scroll-margin-top: 80px;">
            <h2 style="font-size: 20px; margin-bottom: var(--m-4); display: flex; align-items: center; gap: 8px;">
                <span style="background: var(--m-primary-100); padding: 8px 12px; border-radius: 12px;">🏆</span>
                2. Creare un nuovo torneo
            </h2>
            <p>Per creare un torneo devi essere <strong>registrato e autenticato</strong>.</p>
            
            <h3 style="font-size: 16px; margin: var(--m-4) 0 var(--m-2);">Step 1: Formato</h3>
            <ul style="margin: 0 0 var(--m-3) 18px; color: var(--m-text-soft);">
                <li><strong>Eliminazione diretta</strong> — Chi perde viene eliminato, il vincitore avanza. Ideale per coppe.</li>
                <li><strong>Girone unico</strong> — Tutti contro tutti, classifica finale. Ideale per campionati.</li>
                <li><strong>Gironi + playoff</strong> — Fase a gironi seguita da fase a eliminazione diretta.</li>
                <li><strong>Tipo partita</strong> — Solo andata (una partita) o Andata e ritorno (doppia sfida).</li>
            </ul>

            <h3 style="font-size: 16px; margin: var(--m-4) 0 var(--m-2);">Step 2: Dettagli</h3>
            <ul style="margin: 0 0 var(--m-3) 18px; color: var(--m-text-soft);">
                <li><strong>Nome e descrizione</strong> — Scegli un nome riconoscibile e descrivi il torneo.</li>
                <li><strong>Sport e luogo</strong> — Seleziona dalla lista o digita il luogo dove si svolge.</li>
                <li><strong>Visibilità</strong> — Pubblico (visibile a tutti) o Privato (solo con codice).</li>
                <li><strong>Pranzo</strong> — Se attivi questa opzione, potrai gestire gli orari dei pasti.</li>
                <li><strong>Data chiusura iscrizioni</strong> — Dopo questa data non si possono più iscrivere squadre.</li>
                <li><strong>Locandina</strong> — Opzionale, carica un'immagine JPG/PNG/WebP max 5MB.</li>
            </ul>

            <h3 style="font-size: 16px; margin: var(--m-4) 0 var(--m-2);">Step 3: Squadre e giocatori</h3>
            <ul style="margin: 0 0 var(--m-3) 18px; color: var(--m-text-soft);">
                <li><strong>Numero squadre</strong> — Imposta il minimo e il massimo di squadre partecipanti.</li>
                <li><strong>Giocatori per squadra</strong> — Definisci quanti giocatori può avere ogni squadra.</li>
            </ul>

            <div class="m-alert m-alert--success" style="margin-top: var(--m-4);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                <div>💡 Dopo la creazione, puoi sempre <a href="#modifiche">modificare le impostazioni</a> dalla pagina del torneo (solo se sei l'organizzatore).</div>
            </div>
        </div>

        <!-- 3. Tipi di torneo -->
        <div id="tipi-torneo" class="m-card" style="margin-bottom: var(--m-5); scroll-margin-top: 80px;">
            <h2 style="font-size: 20px; margin-bottom: var(--m-4); display: flex; align-items: center; gap: 8px;">
                <span style="background: var(--m-primary-100); padding: 8px 12px; border-radius: 12px;">📊</span>
                3. Come funzionano i tipi di torneo
            </h2>

            <div style="margin-bottom: var(--m-4);">
                <h3 style="font-size: 16px; margin-bottom: var(--m-2);">🏆 Eliminazione diretta</h3>
                <p>Le squadre si affrontano in turni successivi. Chi perde viene eliminato. Il tabellone viene generato automaticamente quando il torneo passa in "in corso". In caso di numero dispari, una squadra passa direttamente il turno (bye).</p>
            </div>

            <div style="margin-bottom: var(--m-4);">
                <h3 style="font-size: 16px; margin-bottom: var(--m-2);">📋 Girone unico</h3>
                <p>Tutte le squadre si affrontano tra loro. La classifica tiene conto di: punti, differenza reti (se previsto), gol fatti, gol subiti. Alla fine del girone, la squadra con più punti vince il torneo.</p>
            </div>

            <div style="margin-bottom: var(--m-4);">
                <h3 style="font-size: 16px; margin-bottom: var(--m-2);">🔄 Gironi + playoff</h3>
                <p><strong>Fase 1 (gironi)</strong> — Le squadre vengono divise in gironi.<br>
                <strong>Fase 2 (playoff)</strong> — Le prime di ogni girone si qualificano per i playoff a eliminazione diretta.<br>
                Per i tornei con questo formato, puoi scegliere tra <strong>modalità automatica</strong> (sorteggio casuale) o <strong>manuale</strong> (trascini le squadre nei gironi).</p>
            </div>
        </div>

        <!-- 4. Iscrivere una squadra -->
        <div id="iscrivere-squadra" class="m-card" style="margin-bottom: var(--m-5); scroll-margin-top: 80px;">
            <h2 style="font-size: 20px; margin-bottom: var(--m-4); display: flex; align-items: center; gap: 8px;">
                <span style="background: var(--m-primary-100); padding: 8px 12px; border-radius: 12px;">👥</span>
                4. Iscrivere una squadra a un torneo
            </h2>
            
            <h3 style="font-size: 16px; margin-bottom: var(--m-2);">Per il capitano (l'utente che crea la squadra)</h3>
            <ol style="margin: 0 0 var(--m-4) 18px; color: var(--m-text-soft); line-height: 1.8;">
                <li>Entra nella pagina del torneo a cui vuoi partecipare</li>
                <li>Clicca su <strong>"Iscrivi squadra"</strong> (solo se il torneo è ancora aperto)</li>
                <li>Scegli un <strong>nome per la squadra</strong> (deve essere unico per questo torneo)</li>
                <li><strong>Aggiungi giocatori</strong> — cerca per nome, cognome o email (devono essere registrati su Matchora)</li>
                <li>Tu sei automaticamente il capitano</li>
                <li>Invia la richiesta → l'organizzatore riceve una email e deve approvare</li>
            </ol>

            <h3 style="font-size: 16px; margin-bottom: var(--m-2);">Per i giocatori invitati</h3>
            <p>Ricevi una notifica (via email) quando vieni aggiunto a una squadra. Non devi fare nulla, sei automaticamente membro della squadra una volta che l'organizzatore approva la richiesta.</p>

            <div class="m-alert m-alert--warn" style="margin-top: var(--m-4);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                <div>⚠️ Ogni utente può far parte di <strong>una sola squadra</strong> per torneo. Il sistema lo impedisce automaticamente.</div>
            </div>
        </div>

        <!-- 5. Gestire le partite -->
        <div id="gestire-partite" class="m-card" style="margin-bottom: var(--m-5); scroll-margin-top: 80px;">
            <h2 style="font-size: 20px; margin-bottom: var(--m-4); display: flex; align-items: center; gap: 8px;">
                <span style="background: var(--m-primary-100); padding: 8px 12px; border-radius: 12px;">⚽</span>
                5. Gestire le partite e i risultati
            </h2>

            <h3 style="font-size: 16px; margin-bottom: var(--m-2);">Per l'organizzatore</h3>
            <ol style="margin: 0 0 var(--m-4) 18px; color: var(--m-text-soft); line-height: 1.8;">
                <li>Vai alla sezione <strong>"Struttura torneo"</strong> (nel menu sotto il nome del torneo)</li>
                <li>Ogni partita ha un form per inserire i risultati</li>
                <li>Inserisci i gol/punti per la squadra di casa e quella ospite</li>
                <li>Se la partita è finita, clicca su <strong>"Salva risultato"</strong></li>
                <li>Classifiche e tabelloni si aggiornano automaticamente</li>
                <li>Per le partite non ancora giocate, puoi inserire un orario</li>
            </ol>

            <h3 style="font-size: 16px; margin-bottom: var(--m-2);">Gestione automatica</h3>
            <ul style="margin: 0 0 var(--m-4) 18px; color: var(--m-text-soft);">
                <li><strong>Eliminazione diretta</strong> — Dopo ogni partita, il vincente avanza automaticamente al turno successivo</li>
                <li><strong>Girone unico</strong> — La classifica si aggiorna in tempo reale</li>
                <li><strong>Gironi + playoff</strong> — Dopo i gironi, i qualificati passano automaticamente ai playoff</li>
            </ul>
        </div>

        <!-- 6. Tornei privati -->
        <div id="tornei-privati" class="m-card" style="margin-bottom: var(--m-5); scroll-margin-top: 80px;">
            <h2 style="font-size: 20px; margin-bottom: var(--m-4); display: flex; align-items: center; gap: 8px;">
                <span style="background: var(--m-primary-100); padding: 8px 12px; border-radius: 12px;">🔒</span>
                6. Tornei privati e codici di invito
            </h2>
            
            <p>I tornei privati non sono visibili nell'elenco pubblico. Per partecipare serve un <strong>codice di invito</strong> che l'organizzatore ti comunica.</p>

            <h3 style="font-size: 16px; margin: var(--m-4) 0 var(--m-2);">Come partecipare a un torneo privato</h3>
            <ol style="margin: 0 0 var(--m-4) 18px; color: var(--m-text-soft); line-height: 1.8;">
                <li>Vai su <strong>"Privati"</strong> nel menu di navigazione</li>
                <li>Inserisci il codice che hai ricevuto dall'organizzatore</li>
                <li>Clicca su <strong>"Cerca torneo"</strong></li>
                <li>Se il codice è corretto, vedrai il torneo e potrai iscriverti normalmente</li>
            </ol>

            <div class="m-alert m-alert--info">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                <div>💡 Il codice privato viene generato automaticamente alla creazione del torneo. L'organizzatore può vederlo nella pagina del torneo (nei dettagli).</div>
            </div>
        </div>

        <!-- 7. Seguire un torneo -->
        <div id="seguire-tornei" class="m-card" style="margin-bottom: var(--m-5); scroll-margin-top: 80px;">
            <h2 style="font-size: 20px; margin-bottom: var(--m-4); display: flex; align-items: center; gap: 8px;">
                <span style="background: var(--m-primary-100); padding: 8px 12px; border-radius: 12px;">⭐</span>
                7. Seguire un torneo
            </h2>
            
            <p>Se sei interessato a un torneo ma non ci partecipi direttamente, puoi <strong>seguirlo</strong> per ricevere aggiornamenti e averlo sempre a portata di mano.</p>
            
            <ol style="margin: 0 0 var(--m-4) 18px; color: var(--m-text-soft); line-height: 1.8;">
                <li>Entra nella pagina del torneo (pubblico o privato con codice)</li>
                <li>Clicca sul pulsante <strong>"Segui"</strong> (vicino al nome del torneo)</li>
                <li>Il torneo apparirà nella lista <strong>"Seguiti"</strong> (accessibile dal menu di navigazione)</li>
                <li>Riceverai notifiche quando ci sono nuovi risultati o partite importanti</li>
            </ol>
            
            <p>Puoi smettere di seguire un torneo cliccando di nuovo sullo stesso pulsante (ora diventa "Smetti di seguire").</p>
        </div>

        <!-- 8. Gestione pranzi -->
        <div id="pranzi" class="m-card" style="margin-bottom: var(--m-5); scroll-margin-top: 80px;">
            <h2 style="font-size: 20px; margin-bottom: var(--m-4); display: flex; align-items: center; gap: 8px;">
                <span style="background: var(--m-primary-100); padding: 8px 12px; border-radius: 12px;">🍽️</span>
                8. Gestione pranzi
            </h2>
            
            <p>Se il torneo ha l'opzione <strong>"Pranzo"</strong> attivata in fase di creazione, puoi gestire gli orari e il numero di persone che mangeranno.</p>

            <div style="margin-bottom: var(--m-4);">
                <h3 style="font-size: 16px; margin-bottom: var(--m-2);">Per l'organizzatore</h3>
                <ul style="margin: 0 0 var(--m-3) 18px; color: var(--m-text-soft);">
                    <li>Quando il torneo è <strong>in corso</strong>, nella pagina del torneo compare la voce "Gestione pranzi"</li>
                    <li>Cliccaci sopra: vedrai tutte le squadre approvate</li>
                    <li>Per ogni squadra, imposta l'orario del pranzo usando il campo data/ora</li>
                    <li>I capitani possono indicare quante persone della loro squadra mangeranno</li>
                </ul>
            </div>

            <div style="margin-bottom: var(--m-4);">
                <h3 style="font-size: 16px; margin-bottom: var(--m-2);">Per i capitani</h3>
                <ul style="margin: 0 0 var(--m-3) 18px; color: var(--m-text-soft);">
                    <li>Vai alla pagina della <strong>tua squadra</strong> (dalla lista squadre del torneo)</li>
                    <li>Nella sezione "Gestione pranzo" inserisci il numero di persone che mangeranno</li>
                    <li>L'organizzatore vedrà il totale e potrà organizzare il ristorante</li>
                </ul>
            </div>

            <div>
                <h3 style="font-size: 16px; margin-bottom: var(--m-2);">Per i giocatori</h3>
                <ul style="margin: 0 0 var(--m-3) 18px; color: var(--m-text-soft);">
                    <li>Vai su <strong>"Gestione pranzi"</strong> dal menu della pagina del torneo</li>
                    <li>Vedrai l'orario del pranzo della tua squadra (impostato dall'organizzatore)</li>
                </ul>
            </div>
        </div>

        <!-- 9. Recensioni -->
        <div id="recensioni" class="m-card" style="margin-bottom: var(--m-5); scroll-margin-top: 80px;">
            <h2 style="font-size: 20px; margin-bottom: var(--m-4); display: flex; align-items: center; gap: 8px;">
                <span style="background: var(--m-primary-100); padding: 8px 12px; border-radius: 12px;">⭐</span>
                9. Recensioni degli organizzatori
            </h2>
            
            <p>Dopo la fine di un torneo, puoi lasciare una recensione per l'organizzatore. Questo aiuta la community a riconoscere gli organizzatori affidabili e seri.</p>

            <div style="margin-bottom: var(--m-4);">
                <h3 style="font-size: 16px; margin-bottom: var(--m-2);">Come recensire</h3>
                <ol style="margin: 0 0 var(--m-3) 18px; color: var(--m-text-soft); line-height: 1.8;">
                    <li>Il torneo deve essere <strong>completato</strong> (stato "Completato")</li>
                    <li>Devi aver <strong>partecipato</strong> al torneo (come capitano o giocatore di una squadra approvata)</li>
                    <li>Vai al <strong>profilo pubblico</strong> dell'organizzatore (clicca sul nome dalla pagina del torneo)</li>
                    <li>Compila il form con voto (1-5 stelle) e commento (opzionale, max 500 caratteri)</li>
                    <li>Puoi recensire un organizzatore una sola volta per torneo</li>
                </ol>
            </div>

            <div>
                <h3 style="font-size: 16px; margin-bottom: var(--m-2);">Dove vedere le recensioni</h3>
                <p>Il profilo pubblico dell'organizzatore mostra la media voti, la distribuzione delle stelle e tutte le recensioni ricevute, ordinata dalla più recente.</p>
            </div>
        </div>

        <!-- 10. FAQ -->
        <div id="faq" class="m-card" style="margin-bottom: var(--m-5); scroll-margin-top: 80px;">
            <h2 style="font-size: 20px; margin-bottom: var(--m-4); display: flex; align-items: center; gap: 8px;">
                <span style="background: var(--m-primary-100); padding: 8px 12px; border-radius: 12px;">❓</span>
                10. Domande frequenti (FAQ)
            </h2>

            <div style="display: flex; flex-direction: column; gap: var(--m-4);">
                <div>
                    <h3 style="font-size: 16px; margin-bottom: 4px;">🔐 Non ricevo l'email di conferma registrazione. Cosa fare?</h3>
                    <p class="m-muted">Controlla nella cartella <strong>Spam</strong> o <strong>Promozioni</strong> della tua email. Se non arriva dopo 10 minuti, contattaci tramite la pagina <a href="/contatti.php">Contatti</a>.</p>
                </div>
                <div>
                    <h3 style="font-size: 16px; margin-bottom: 4px;">👥 Posso cambiare il capitano della squadra?</h3>
                    <p class="m-muted">Al momento il capitano è l'utente che ha creato la squadra. Per cambiarlo, contatta l'organizzatore che può modificare manualmente il database (operazione rara).</p>
                </div>
                <div>
                    <h3 style="font-size: 16px; margin-bottom: 4px;">🏆 Come si passa un torneo da "aperto" a "in corso"?</h3>
                    <p class="m-muted">L'organizzatore può <strong>chiudere le iscrizioni anticipatamente</strong> dalla pagina del torneo (pulsante "Chiudi ora"). In alternativa, il sistema lo fa automaticamente alla data di chiusura impostata. Se il numero minimo di squadre non è raggiunto, il torneo rimane in attesa.</p>
                </div>
                <div>
                    <h3 style="font-size: 16px; margin-bottom: 4px;">📊 Come funzionano i pareggi nei vari sport?</h3>
                    <p class="m-muted">Per sport come <strong>calcio, futsal, rugby e beach volley</strong> è previsto il pareggio (1 punto a testa). Per sport come <strong>basket, tennis, padel, ping pong e badminton</strong> non esistono pareggi; in caso di parità viene data vittoria alla squadra con più punti in una delle modalità (es. tie-break nel tennis).</p>
                </div>
                <div>
                    <h3 style="font-size: 16px; margin-bottom: 4px;">🔒 Ho dimenticato la password. Come faccio?</h3>
                    <p class="m-muted">Vai su <a href="/recupera_password.php">Recupera password</a>, inserisci la tua email e riceverai un link per reimpostarla. Se hai usato "Accedi con Google", la password è gestita da Google.</p>
                </div>
                <div>
                    <h3 style="font-size: 16px; margin-bottom: 4px;">🗑️ Posso eliminare un torneo che ho creato?</h3>
                    <p class="m-muted">Sì, dalla pagina del torneo come organizzatore trovi il pulsante <strong>"Elimina torneo"</strong> nella sidebar a destra. L'eliminazione è irreversibile: tutte le squadre, partite e dati associati verranno cancellati.</p>
                </div>
                <div>
                    <h3 style="font-size: 16px; margin-bottom: 4px;">👥 Un giocatore può essere in due squadre diverse dello stesso torneo?</h3>
                    <p class="m-muted">No, ogni utente può far parte di <strong>una sola squadra</strong> per torneo. Il sistema lo impedisce automaticamente sia all'iscrizione che all'aggiunta manuale.</p>
                </div>
                <div>
                    <h3 style="font-size: 16px; margin-bottom: 4px;">📱 Matchora è ottimizzato per dispositivi mobili?</h3>
                    <p class="m-muted">Sì, il sito è completamente <strong>responsive</strong> e funziona bene su smartphone, tablet e computer. Puoi gestire tornei e partite anche da mobile.</p>
                </div>
                <div>
                    <h3 style="font-size: 16px; margin-bottom: 4px;">💰 È gratuito?</h3>
                    <p class="m-muted">Sì, Matchora è <strong>completamente gratuito</strong>. Nessun costo nascosto, nessun abbonamento, nessuna pubblicità invasiva.</p>
                </div>
            </div>
        </div>

        <!-- Modifiche e supporto -->
        <div id="modifiche" class="m-card" style="margin-bottom: var(--m-5); scroll-margin-top: 80px;">
            <h2 style="font-size: 20px; margin-bottom: var(--m-4); display: flex; align-items: center; gap: 8px;">
                <span style="background: var(--m-primary-100); padding: 8px 12px; border-radius: 12px;">✏️</span>
                11. Modificare un torneo esistente
            </h2>
            
            <p>Se sei l'organizzatore, puoi modificare le impostazioni del torneo in qualsiasi momento (finché non viene eliminato).</p>
            
            <ol style="margin: 0 0 var(--m-4) 18px; color: var(--m-text-soft); line-height: 1.8;">
                <li>Vai alla pagina del torneo che hai creato</li>
                <li>Nella sidebar a destra, clicca su <strong>"Modifica impostazioni"</strong></li>
                <li>Aggiorna i campi che desideri (nome, descrizione, sport, luogo, limiti squadre/giocatori, ecc.)</li>
                <li>Clicca su <strong>"Salva modifiche"</strong></li>
            </ol>
            
            <p>⚠️ Alcune impostazioni (come il formato del torneo o il tipo di partita) non possono essere modificate dopo la creazione per garantire l'integrità del torneo.</p>
        </div>

        <!-- Contatti per assistenza -->
        <div class="m-card" style="text-align: center; background: linear-gradient(135deg, var(--m-primary-50), var(--m-surface));">
            <h3 style="margin-bottom: var(--m-2);">❓ Non hai trovato quello che cercavi?</h3>
            <p class="m-muted" style="margin-bottom: var(--m-4);">Il nostro team è a disposizione per rispondere alle tue domande o risolvere problemi tecnici.</p>
            <a href="/contatti.php" class="m-btn m-btn--primary">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Contatta il supporto
            </a>
            <p class="m-muted" style="margin-top: var(--m-4); font-size: 12px;">Rispondiamo entro 48 ore lavorative</p>
        </div>

    </div>
</main>

<style>
/* Stili aggiuntivi solo per la pagina guida */
.guide-index {
    scroll-margin-top: 20px;
}
.guide-index a {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.guide-index a:hover {
    transform: translateY(-2px);
    box-shadow: var(--m-sh-2);
}
</style>

<?php require_once 'templates/footer.php'; ?>