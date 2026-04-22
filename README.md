
----------------------------------------------------------------------------------------


**REQUISITI FUNZIONALI**

UTENTI:
- gli utenti devono registrarsi o loggarsi per usufruire del servizio

GESTIONE TORNEI:
- tutti gli utenti possono creare un torneo
- durante la creazione del torneo inserire dati del torneo (tipologia (chiedere a cluchy), descrizione, num max squadre, privato/pubblico ecc.) e si crea il torneo vuoto senza squadre
- i tornei possono essere seguiti per vederli più velocemente con il tasto SEGUI aggiunto in una pagina dei seguiti
- solo il creatore del torneo potrà aggiornare il torneo, vedrà un pulsante con aggiorna e il creatore dovrà inserire a mano i risultati di ogni partita, inserito tutti i risultati potrà premere fine e in base alla tipologia di torneo si aggiornerà la fase del torneo con le squadre che hanno vinto
- quando un torneo è arrivato alla finale il creatore aggiorna il torneo con la squadra vincitrice e lo stato (che prima era IN CORSO) e la fase del torneo diventano entrambi CONCLUSO per indicare che il torneo e finito
- torneo pubblico = seguibile da tutti, torneo privato = seguibile con codice

GESTIONE SQUADRE E GIOCATORI:
- nella pagina dei tornei pubblici ogni torneo avrà due tasti:
    - tasto SEGUI spiegato prima in GESTIONE TORNEO
    - INSERISCI SQUADRA appare un wizard personalizzato in base alle caratteristiche del torneo che va compilato con titolo torneo, i nomi dei giocatori (inseriti cercado gli utente e mettendoli, solo se registrati si possono unire)
- dopo avere inserito la squadra l'organizzatore del torneo deve accettare la richiesta


**PAGINE DA NAVBAR:**
per navigare tra le pagina è presente una navbar (home, profilo, tornei seguiti, tornei in corso, tornei finiti, tornei privati)

- HOME:
    - tasto per creazione torneo
    - visualizza tornei pubblici (usando la pagina VISUALIZZA_LISTA_TORNEI )
    - filtri per filtrare i tornei da visualizzare

- PROFILO: 
    - dati personali
    - tornei creati -> link altra pagina
    - logout
  - TORNEI CREATI:
      - visualizzazione card tornei creati (usando la pagina VISUALIZZA_LISTA_TORNEI)

- TORNEI SEGUITI/ TORNEI IN CORSO/ TORNEI FINITI:
    - tutti usando la pagina VISUALIZZA_LISTA_TORNEI

- TORNEI PRIVATI:
    -  è presente una barra di ricerca per torvare il torneo privato (codice)
    -  mostra l'unico torneo privato corrispondente
    -  per salvarlo aggiungerlo ai seguiti


**PAGINE NON ACCESSIBILI DA NAVBAR:**
-  DETTAGLI TORNEO:
    Utenti classici:
      -  vengono visualizzate le caratteristiche del torneo (show campi torneo)
      -  in base allo stato vengono visualizzate cose differenti:
         -  APERTO PER ISCRIZIONI -> possibilità di aggiungere una squadra (pulsante) + squadre già iscritte
         -  IN CORSO -> mostra le partite del torneo e la suddivisione nelle fasi e la classifica
         -  TERMINATO -> mostra la squadra vincitrice e la classifica eventuale
    Creatore:
      - tasto aggiorna torneo con possibilità inserimento risultati (anche stato o vedere se lo facciamo fare in automatico) 

-  CREAZIONE TORNEO:
      -  wizard con tutti campi del torneo da inserire
      -  tasto fine

-  AGGIUNTA SQUADRA A TORNEO:
      -  nome sqadra
      -  selezione giocatori tramite barra di ricerca (va a cercare tra gli utenti registrati), ricordarsi di quelli in panchina


-----------------------------------------------------------------------

CORETTO DA CLAUDE:

REQUISITI FUNZIONALI
UTENTI

Gli utenti devono registrarsi o loggarsi per usufruire del servizio


GESTIONE TORNEI

- Tutti gli utenti possono creare un torneo
- Durante la creazione del torneo inserire i dati del torneo: tipologia (eliminazione diretta, girone all'italiana, doppia eliminazione), descrizione, numero massimo di squadre, numero minimo di squadre per avviare il torneo, privato/pubblico, data di inizio iscrizioni e data di chiusura iscrizioni; al termine si crea il torneo vuoto senza squadre
- I tornei possono essere seguiti per vederli più velocemente tramite il tasto SEGUI, che li aggiunge alla pagina dei Seguiti
- Solo il creatore del torneo potrà aggiornare il torneo: vedrà un pulsante AGGIORNA e dovrà inserire manualmente i risultati di ogni partita. Il flusso di aggiornamento avviene fase per fase: il creatore inserisce i risultati di tutte le partite della fase corrente e poi preme AVANZA FASE; il sistema calcola automaticamente le squadre qualificate e genera gli accoppiamenti della fase successiva in base alla tipologia di torneo scelta.
- La transizione da stato APERTO PER ISCRIZIONI a IN CORSO avviene manualmente tramite un pulsante AVVIA TORNEO visibile al creatore, disponibile solo se è stato raggiunto il numero minimo di squadre iscritte e accettate. Al momento dell'avvio, il sistema genera automaticamente le partite del primo turno in base alla tipologia di torneo.
- Quando un torneo è arrivato alla finale, il creatore inserisce il risultato dell'ultima partita e preme CONCLUDI TORNEO; lo stato del torneo diventa CONCLUSO e il sistema registra automaticamente la squadra vincitrice. La fase corrente non viene marcata separatamente: è lo stato CONCLUSO del torneo a indicare che tutto è terminato.
- Torneo pubblico = seguibile da tutti; torneo privato = seguibile con codice univoco generato automaticamente alla creazione del torneo
- Il creatore può modificare i dati del torneo (descrizione, numero massimo squadre, date) solo finché lo stato è APERTO PER ISCRIZIONI. Una volta avviato, i dati sono bloccati.
- Il creatore può cancellare il torneo solo finché lo stato è APERTO PER ISCRIZIONI e nessuna squadra è stata accettata.


GESTIONE SQUADRE E GIOCATORI

- Nella pagina dei tornei pubblici ogni torneo avrà due tasti:

SEGUI (spiegato nella sezione GESTIONE TORNEI)
INSERISCI SQUADRA: appare un wizard personalizzato in base alle caratteristiche del torneo, da compilare con:

Nome della squadra
Selezione dei giocatori tramite barra di ricerca tra gli utenti registrati — distinti in titolari e giocatori di panchina. La panchina è facoltativa e serve a registrare riserve che non partecipano attivamente alle partite ma fanno parte del roster. Il numero di titolari deve rispettare i vincoli della tipologia di torneo.
Il campo "titolo torneo" non è presente nel wizard in quanto il torneo è già noto dal contesto




- Un utente registrato non può comparire in più di una squadra nello stesso torneo.
- Dopo aver inserito la squadra, l'organizzatore del torneo deve accettare o rifiutare la richiesta. In entrambi i casi l'utente che ha inviato la richiesta riceve una notifica con l'esito. In caso di rifiuto, è possibile inviare una nuova richiesta.


NOTIFICHE
- Il sistema prevede notifiche in-app per i seguenti eventi:

Accettazione o rifiuto della propria richiesta di iscrizione squadra
Aggiornamento dei risultati di un torneo che si sta seguendo
Avanzamento a una nuova fase di un torneo che si sta seguendo
Conclusione di un torneo che si sta seguendo


PAGINE DA NAVBAR
La navbar contiene le seguenti voci: Home, Profilo, Seguiti, Privati.
Le voci "Tornei in corso" e "Tornei finiti" vengono rimosse dalla navbar in quanto raggiungibili tramite i filtri nella Home o nella pagina Seguiti. Avere voci di navigazione separate per stati del torneo causava ambiguità su cosa venisse mostrato (tutti i tornei pubblici? solo quelli seguiti?).

HOME:

Tasto per la creazione di un torneo
Visualizza i tornei pubblici usando il componente VISUALIZZA_LISTA_TORNEI
Filtri per filtrare i tornei visualizzati: stato (Aperto per iscrizioni, In corso, Concluso), tipologia, data

PROFILO:

Dati personali
Tornei creati → link ad altra pagina
Logout

TORNEI CREATI:

Visualizzazione card tornei creati usando il componente VISUALIZZA_LISTA_TORNEI
I tornei sono raggruppati per stato (Aperto per iscrizioni, In corso, Concluso) per permettere al creatore di gestire rapidamente i tornei attivi

SEGUITI:

Visualizza i tornei seguiti usando il componente VISUALIZZA_LISTA_TORNEI
Filtri per stato (Aperto per iscrizioni, In corso, Concluso) per distinguere rapidamente i tornei attivi da quelli terminati

PRIVATI:

È presente una barra di ricerca per trovare il torneo privato tramite codice
Mostra l'unico torneo privato corrispondente al codice inserito
Il tasto per seguire il torneo privato trovato è SEGUI: lo aggiunge alla pagina Seguiti. I tornei privati seguiti compaiono nella pagina Seguiti insieme a quelli pubblici, distinti da un'apposita etichetta PRIVATO.


PAGINE NON ACCESSIBILI DA NAVBAR
DETTAGLI TORNEO:
Utenti classici:

Vengono visualizzate le caratteristiche del torneo
In base allo stato vengono visualizzate informazioni differenti:

APERTO PER ISCRIZIONI → possibilità di aggiungere una squadra (pulsante INSERISCI SQUADRA) + elenco squadre già iscritte e accettate
IN CORSO → mostra le partite del torneo suddivise per fase, il bracket aggiornato e la classifica (ove prevista dalla tipologia)
CONCLUSO → mostra la squadra vincitrice e il riepilogo delle fasi con tutti i risultati



Creatore:

Tasto AGGIORNA TORNEO con possibilità di inserimento risultati partita per partita
Tasto AVVIA TORNEO (visibile solo se stato = APERTO PER ISCRIZIONI e numero minimo squadre raggiunto)
Tasto CONCLUDI TORNEO (visibile solo dopo l'inserimento del risultato della finale)
Tasto MODIFICA TORNEO (visibile solo se stato = APERTO PER ISCRIZIONI)
Tasto ELIMINA TORNEO (visibile solo se stato = APERTO PER ISCRIZIONI e nessuna squadra accettata)


CREAZIONE TORNEO:

Wizard con tutti i campi del torneo da inserire: tipologia (eliminazione diretta, girone all'italiana, doppia eliminazione), descrizione, numero massimo squadre, numero minimo squadre, data chiusura iscrizioni, privato/pubblico
Tasto CREA TORNEO al termine del wizard


AGGIUNTA SQUADRA A TORNEO:

Nome squadra
Selezione giocatori tramite barra di ricerca tra gli utenti registrati, distinti in titolari e riserve di panchina (facoltative)
Vincolo: un utente già presente in un'altra squadra dello stesso torneo non può essere selezionato
Tasto INVIA RICHIESTA per sottomettere la squadra all'approvazione dell'organizzatore