🏆 Tournament Manager — Specifiche Funzionali

1. ARCHITETTURA GENERALE
Stack consigliato
Frontend: React + TypeScript
Backend: Node.js + Express (oppure Next.js full-stack)
Database: PostgreSQL (dati strutturati) + Redis (sessioni e cache real-time)
Auth: JWT + refresh token (oppure OAuth con Google)
Storage: AWS S3 o Cloudinary (loghi squadre, avatar)
Real-time: WebSocket o Server-Sent Events per aggiornamenti live dei risultati
Deploy
Frontend: Vercel o Netlify
Backend: Railway o Render
DB: Supabase o PlanetScale

2. AUTENTICAZIONE & PROFILO UTENTE
Registrazione
Email + password (con verifica email)
OAuth Google / Apple
Username univoco, nome, cognome, avatar
Login
Email/password o OAuth
"Ricordami" con refresh token (30 giorni)
Reset password via email
Profilo utente
Avatar e dati personali modificabili
Storico tornei creati
Storico tornei a cui ha partecipato (con risultati finali)
Statistiche personali: vittorie, sconfitte, pareggi (aggregate da tutti i tornei)
Notifiche in-app (risultati, inviti, inizio torneo)
Ruoli
admin — gestione piattaforma
organizer — chi crea il torneo, ha controllo totale su di esso
captain — chi crea/gestisce la squadra
player — partecipante semplice

3. HOME PAGE
Sezione "Scopri Tornei"
Griglia/lista di tutti i tornei pubblici
Filtri combinabili:
Sport (Calcio, Beach Volley, tutti)
Formato (Gironi, Eliminazione, ecc.)
Status (In Arrivo, In Corso, Concluso)
Anno (menu a tendina dinamico con gli anni disponibili)
Visibilità (pubblici, tutti se loggato)
Ordinamento: data creazione, data inizio, popolarità (n° iscritti)
Barra di ricerca per nome torneo
Card torneo con: nome, sport, formato, stato (badge colorato), n° squadre iscritte / max squadre, data inizio
Sezione "I Miei Tornei" (solo loggati)
Tornei creati dall'utente
Tornei a cui partecipa
Tornei in attesa di conferma iscrizione
Accesso rapido torneo privato
Campo "Inserisci ID Torneo" sempre visibile in home e navbar
Redirect diretto alla pagina del torneo privato

4. CREAZIONE TORNEO
Step 1 — Info generali
Nome torneo (obbligatorio, max 60 caratteri)
Sport: Calcio ⚽ / Beach Volley 🏐 (estendibile)
Descrizione opzionale (max 500 caratteri)
Logo/immagine torneo (upload opzionale)
Visibilità: Pubblico / Privato
Località (campo testo + integrazione Google Maps opzionale)
Step 2 — Formato
Tipo di torneo:
Gironi: numero di gironi, squadre per girone, partite di andata/ritorno, criteri di classifica (punti, differenza reti, scontri diretti)
Eliminazione Diretta: con/senza 3° posto, andata/ritorno o gara secca
Gironi + Eliminazione: quante squadre passano da ogni girone
Round Robin (tutti contro tutti): utile per tornei piccoli
Numero massimo di squadre (da 2 a 64)
Numero minimo e massimo di giocatori per squadra (es. 5v5, 6v6 per calcetto; 2v2 per beach volley)
Step 3 — Date e regole
Data e ora di inizio iscrizioni
Data e ora di chiusura iscrizioni
Data e ora di inizio torneo
Durata stimata (opzionale)
Regolamento personalizzato (textarea)
Punteggi personalizzabili: punti per vittoria, pareggio, sconfitta (default 3/1/0)
Step 4 — Riepilogo e conferma
Preview di tutte le impostazioni
Bottone "Crea Torneo"
Generazione automatica: ID Torneo (es. TRN-A3F9K2) copiabile con un click
QR Code condivisibile (per tornei privati soprattutto)
Link di condivisione diretto

5. GESTIONE TORNEO (vista organizer)
Dashboard organizer
Panoramica: squadre iscritte, posti rimanenti, partite giocate, partite da giocare
Stato iscrizioni: aperte / chiuse / in attesa
Lista squadre con: nome, capitano, n° giocatori, stato (approvata / in attesa / rifiutata)
Gestione iscrizioni
Per tornei pubblici: approvazione automatica o manuale (a scelta dell'organizer)
Per tornei privati: accesso solo via ID, approvazione opzionale
Possibilità di rimuovere squadre o singoli giocatori
Possibilità di aggiungere squadre manualmente (senza che si iscrivano)
Gestione partite
Generazione automatica del calendario partite (dopo chiusura iscrizioni)
Inserimento risultati partita per partita
Modifica risultati (con log delle modifiche)
Possibilità di aggiungere marcatori / statistiche (gol, set vinti, ecc.)
Calendario visivo delle partite (con filtro per data, girone, turno)
Comunicazioni
Annunci interni al torneo (notifica a tutti i partecipanti)
Chat del torneo (bacheca messaggi pubblica tra partecipanti)
Chiudi/Archivia torneo
Possibilità di terminare manualmente il torneo
Assegnazione automatica posizioni finali
Generazione classifica finale esportabile (PDF)

6. ISCRIZIONE AL TORNEO (vista giocatore)
Torneo pubblico
Pagina pubblica del torneo visibile a tutti (anche non loggati, in sola lettura)
Loggati: bottone "Iscriviti"
Scelta:
Crea squadra: inserisci nome squadra → ricevi ID Squadra (es. SQD-7TK2M) da condividere
Unisciti a una squadra: inserisci ID Squadra del capitano
Dopo l'iscrizione: stato "In attesa di approvazione" (se approvazione manuale) o "Iscritto" (se automatica)
Torneo privato
Non appare nella home pubblica
Accesso solo tramite ID Torneo (inserito in home o ricevuto tramite link)
Stessa flow di creazione/unione squadra del torneo pubblico
Abbandono squadra/torneo
Il giocatore può abbandonare la squadra prima dell'inizio
Il capitano può sciogliere la squadra prima dell'inizio
Dopo l'inizio del torneo: solo l'organizer può gestire rimozioni

7. PAGINA TORNEO (vista pubblica)
Tab "Info"
Nome, sport, formato, visibilità, date, regolamento, organizer
Tab "Squadre"
Lista squadre con nome, capitano, n° giocatori, logo (opzionale)
Click su squadra → mostra roster completo
Tab "Calendario"
Lista partite ordinate per data/turno
Ogni partita: squadra A vs squadra B, data/ora, risultato (se disponibile)
Filtro per girone o turno
Tab "Classifiche"
Classifica gironi (punti, partite giocate, vittorie, pareggi, sconfitte, gol fatti/subiti, differenza reti)
Tabellone eliminazione (bracket visivo)
Aggiornamento in tempo reale al salvataggio di un risultato
Tab "Statistiche" (opzionale ma molto utile)
Classifica marcatori (gol per il calcio, punti/ace per il beach volley)
Squadra con più vittorie, miglior attacco, miglior difesa

8. NOTIFICHE
In-app (campanella in navbar): nuova partita programmata, risultato inserito, iscrizione approvata/rifiutata, annuncio organizer
Email: conferma iscrizione, inizio torneo, fine torneo, risultati partite (opt-in)
(Opzionale futuro) Push notification via PWA

9. ADMIN PANEL
Lista tutti gli utenti (con ruolo, data registrazione, n° tornei)
Lista tutti i tornei (con stato, organizer, n° squadre)
Possibilità di disabilitare utenti o tornei
Statistiche piattaforma: tornei attivi, utenti registrati, sport più giocato

10. FUNZIONALITÀ FUTURE (roadmap)
Sistema di rating ELO per giocatori e squadre, aggiornato torneo per torneo
Pagamenti: quota d'iscrizione al torneo gestita via Stripe
App mobile (React Native o PWA)
Multi-sport estendibile (padel, basket, ecc.)
Arbitri: ruolo dedicato con accesso all'inserimento risultati
Live scoring: aggiornamento risultati in tempo reale durante la partita
Sponsors: banner e loghi sponsor visualizzati nella pagina torneo


Testo a mano:

requisiti funzionali:
- i client devono registrarsi o loggarsi
- tutti possono creare o seguire un torneo (un solo tipo di utente per semplificare)
- torneo pubblico = seguibile da tutti, torneo privato = seguibile con codice

GESTIONE TORNEI
- tutti gli utenti possono creare un torneo
- durante la creazione del torneo inserire dati del torneo (tipologia (chiedere a cluchy), descrizione, num max squadre, privato/pubblico ecc.) e si crea il torneo
- i tornei possono essere seguiti per vederli più velocemente con il tasto SEGUI aggiunto in una pagina tornei seguiti

GESTIONE SQUADRE E GIOCATORI 
- nella pagina dei tornei pubblici ogni torneo avrà due tasti:
    - tasto SEGUI spiegato prima in GESTIONE TORNEO
    - INSERISCI SQUADRA appare un wizard personalizzato in base alle caratteristiche del torneo che va compilato con titolo torneo, i nomi dei giocatori (inseriti cercado gli utente e mettendoli, solo se registrati si possono unire)
- dopo avere inserito la squadra l'organizzatore del torneo deve accettare la richiesta

PAGINE:
in tutte le pagine c'è una navbar con home, profilo, tornei seguiti, tornei in corso, tornei finiti
- HOME:
    - tasto per creazione torneo
    - visualizza tornei pubblici con filtri
- CREAZIONE TORNEO:
    - 


- 3 PAGINE:
- pagina iniziale con tutti i tornei pubblici + casella di testo per visualizzare tornei privati
- pagina dei tornei seguiti
- pagina tornei conclusi (con stato == CONCLUSO)
- quando premiamo sul torneo nella pagina seguiti si apre il torneo con sopra la fase del torneo (quarti di finale/finale) e nel centro della pagina verranno visualizzate le coppie di squadre che si affronteranno
- quando premiamo sul torneo nella pagina dei seguiti ci sarà, oltre alla fase del torneo attuale, una sottopagina RISULTATI con lo storico del torneo con i risultati di ogni partita
- solo il creatore del torneo potrà aggiornare il torneo, vedrà un pulsante con aggiorna e il creatore dovrà inserire a mano i risultati di ogni partita, inserito tutti i risultati potrà premere fine e in base alla tipologia di torneo si aggiornerà la fase del torneo con le squadre che hanno vinto
- quando un torneo è arrivato alla finale il creatore aggiorna il torneo con la squadra vincitrice e lo stato (che prima era IN CORSO) e la fase del torneo diventano entrambi CONCLUSO per indicare che il torneo e finito
