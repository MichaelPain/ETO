# Guida Utente - Esports Tournament Organizer (ETO)

## Introduzione

Benvenuto nella guida utente di **Esports Tournament Organizer (ETO)**, la soluzione completa per la gestione di tornei di esports. Questo documento fornisce istruzioni dettagliate su come utilizzare tutte le funzionalità del sistema, sia come amministratore che come utente standard.

ETO è progettato per semplificare l'organizzazione e la gestione di tornei di esports, offrendo strumenti per la creazione di tornei, la gestione di team, la pianificazione di match e molto altro.

## Indice dei Contenuti

1. [Panoramica del Sistema](#panoramica-del-sistema)
2. [Accesso e Registrazione](#accesso-e-registrazione)
3. [Dashboard Utente](#dashboard-utente)
4. [Gestione Tornei](#gestione-tornei)
5. [Gestione Team](#gestione-team)
6. [Gestione Match](#gestione-match)
7. [Visualizzazione Pubblica](#visualizzazione-pubblica)
8. [Funzionalità Avanzate](#funzionalità-avanzate)
9. [Risoluzione Problemi](#risoluzione-problemi)
10. [Domande Frequenti (FAQ)](#domande-frequenti-faq)

## Panoramica del Sistema

ETO è strutturato in tre componenti principali:

1. **Area Amministrativa**: Accessibile solo agli amministratori, permette la gestione completa del sistema.
2. **Area Utente**: Accessibile a tutti gli utenti registrati, permette la gestione dei propri team e la partecipazione ai tornei.
3. **Area Pubblica**: Accessibile a tutti, mostra informazioni sui tornei, team e match.

Il sistema è basato su un'architettura MVC (Model-View-Controller) che garantisce una separazione chiara tra dati, logica di business e interfaccia utente.

## Accesso e Registrazione

### Registrazione di un Nuovo Account

1. Dalla homepage, clicca sul pulsante "Registrati".
2. Compila il modulo con le tue informazioni personali:
   - Nome utente (univoco)
   - Indirizzo email
   - Password (almeno 8 caratteri, con lettere maiuscole, minuscole e numeri)
3. Accetta i termini e le condizioni.
4. Clicca su "Registrati".
5. Riceverai un'email di conferma. Clicca sul link nell'email per attivare il tuo account.

### Accesso al Sistema

1. Dalla homepage, clicca sul pulsante "Accedi".
2. Inserisci il tuo nome utente o indirizzo email.
3. Inserisci la tua password.
4. Clicca su "Accedi".

### Recupero Password

1. Dalla pagina di accesso, clicca su "Password dimenticata?".
2. Inserisci l'indirizzo email associato al tuo account.
3. Clicca su "Invia".
4. Riceverai un'email con un link per reimpostare la password.
5. Clicca sul link e segui le istruzioni per creare una nuova password.

## Dashboard Utente

La dashboard è la pagina principale che vedi dopo l'accesso. Da qui puoi accedere a tutte le funzionalità del sistema.

### Elementi della Dashboard

- **Riepilogo**: Mostra un riepilogo delle tue attività recenti.
- **I Miei Tornei**: Elenco dei tornei a cui partecipi o che hai creato.
- **I Miei Team**: Elenco dei team di cui sei membro o capitano.
- **Prossimi Match**: Elenco dei match programmati per i tuoi team.
- **Notifiche**: Avvisi su eventi importanti (nuovi match, risultati, ecc.).

### Personalizzazione della Dashboard

Puoi personalizzare la dashboard secondo le tue preferenze:

1. Clicca sull'icona "Impostazioni" nell'angolo in alto a destra.
2. Seleziona "Personalizza Dashboard".
3. Trascina e rilascia i widget per riorganizzarli.
4. Attiva o disattiva i widget che desideri visualizzare.
5. Clicca su "Salva" per confermare le modifiche.

## Gestione Tornei

### Visualizzazione Tornei

1. Dal menu principale, seleziona "Tornei".
2. Vedrai un elenco di tutti i tornei disponibili.
3. Puoi filtrare i tornei per:
   - Stato (In attesa, Attivo, Completato, Annullato)
   - Gioco
   - Data di inizio
4. Clicca su un torneo per visualizzarne i dettagli.

### Creazione di un Torneo (solo Amministratori)

1. Dal menu principale, seleziona "Tornei" > "Aggiungi Nuovo".
2. Compila il modulo con le informazioni del torneo:
   - Nome del torneo
   - Descrizione
   - Gioco
   - Formato (Eliminazione singola, Doppia eliminazione, Girone all'italiana, ecc.)
   - Date di inizio e fine
   - Date di apertura e chiusura delle registrazioni
   - Numero minimo e massimo di team
   - Regolamento
   - Premi
   - Immagine in evidenza
3. Clicca su "Crea Torneo".

### Modifica di un Torneo (solo Amministratori)

1. Dal menu principale, seleziona "Tornei".
2. Trova il torneo che desideri modificare e clicca su "Modifica".
3. Aggiorna le informazioni necessarie.
4. Clicca su "Aggiorna Torneo".

### Eliminazione di un Torneo (solo Amministratori)

1. Dal menu principale, seleziona "Tornei".
2. Trova il torneo che desideri eliminare e clicca su "Elimina".
3. Conferma l'eliminazione.

### Iscrizione a un Torneo

1. Dal menu principale, seleziona "Tornei".
2. Trova il torneo a cui desideri iscriverti e clicca su "Dettagli".
3. Clicca sul pulsante "Iscriviti".
4. Seleziona il team con cui desideri partecipare (devi essere il capitano di almeno un team).
5. Conferma l'iscrizione.

## Gestione Team

### Visualizzazione Team

1. Dal menu principale, seleziona "Team".
2. Vedrai un elenco di tutti i team disponibili.
3. Puoi filtrare i team per:
   - Gioco
   - Nome
4. Clicca su un team per visualizzarne i dettagli.

### Creazione di un Team

1. Dal menu principale, seleziona "Team" > "Crea Team".
2. Compila il modulo con le informazioni del team:
   - Nome del team
   - Descrizione
   - Gioco
   - Logo (opzionale)
   - Email di contatto
   - Sito web (opzionale)
   - Social media (opzionale)
3. Clicca su "Crea Team".
4. Sarai automaticamente impostato come capitano del team.

### Modifica di un Team

1. Dal menu principale, seleziona "Team".
2. Trova il team che desideri modificare e clicca su "Modifica" (devi essere il capitano del team).
3. Aggiorna le informazioni necessarie.
4. Clicca su "Aggiorna Team".

### Gestione Membri del Team

1. Dal menu principale, seleziona "Team".
2. Trova il team di cui desideri gestire i membri e clicca su "Dettagli".
3. Clicca sulla scheda "Membri".
4. Da qui puoi:
   - Invitare nuovi membri: inserisci l'indirizzo email o il nome utente e clicca su "Invita".
   - Rimuovere membri: clicca su "Rimuovi" accanto al membro che desideri rimuovere.
   - Cambiare ruoli: clicca su "Modifica Ruolo" accanto al membro e seleziona il nuovo ruolo.
   - Trasferire la capitananza: clicca su "Trasferisci Capitananza" accanto al membro che desideri promuovere a capitano.

### Eliminazione di un Team

1. Dal menu principale, seleziona "Team".
2. Trova il team che desideri eliminare e clicca su "Elimina" (devi essere il capitano del team).
3. Conferma l'eliminazione.

## Gestione Match

### Visualizzazione Match

1. Dal menu principale, seleziona "Match".
2. Vedrai un elenco di tutti i match disponibili.
3. Puoi filtrare i match per:
   - Torneo
   - Team
   - Stato (In attesa, In corso, Completato, Annullato)
   - Data
4. Clicca su un match per visualizzarne i dettagli.

### Creazione di un Match (solo Amministratori)

1. Dal menu principale, seleziona "Match" > "Aggiungi Nuovo".
2. Compila il modulo con le informazioni del match:
   - Torneo
   - Team 1
   - Team 2
   - Round
   - Numero del match
   - Data e ora programmata
   - Stato
   - URL dello streaming (opzionale)
   - Note (opzionale)
3. Clicca su "Crea Match".

### Modifica di un Match (solo Amministratori)

1. Dal menu principale, seleziona "Match".
2. Trova il match che desideri modificare e clicca su "Modifica".
3. Aggiorna le informazioni necessarie.
4. Clicca su "Aggiorna Match".

### Registrazione dei Risultati

1. Dal menu principale, seleziona "Match".
2. Trova il match di cui desideri registrare i risultati e clicca su "Registra Risultati" (devi essere un amministratore o il capitano di uno dei team).
3. Inserisci i punteggi per entrambi i team.
4. Clicca su "Salva Risultati".
5. Se sei il capitano di un team, il risultato sarà contrassegnato come "In attesa di conferma" fino a quando non sarà confermato dall'altro capitano o da un amministratore.

## Visualizzazione Pubblica

### Pagina Tornei

La pagina pubblica dei tornei mostra tutti i tornei disponibili, con filtri per gioco e stato. Per ogni torneo vengono mostrate le informazioni principali come nome, gioco, date e stato.

### Pagina Dettaglio Torneo

La pagina di dettaglio di un torneo mostra tutte le informazioni sul torneo, inclusi:

- Descrizione
- Regolamento
- Premi
- Team partecipanti
- Bracket o tabellone dei match
- Risultati

### Pagina Team

La pagina pubblica dei team mostra tutti i team registrati, con filtri per gioco. Per ogni team vengono mostrate le informazioni principali come nome, gioco e capitano.

### Pagina Dettaglio Team

La pagina di dettaglio di un team mostra tutte le informazioni sul team, inclusi:

- Descrizione
- Membri
- Tornei a cui partecipa
- Prossimi match
- Risultati recenti

### Pagina Match

La pagina pubblica dei match mostra tutti i match programmati, con filtri per torneo, team e stato. Per ogni match vengono mostrate le informazioni principali come i team partecipanti, la data e lo stato.

### Pagina Dettaglio Match

La pagina di dettaglio di un match mostra tutte le informazioni sul match, inclusi:

- Team partecipanti
- Data e ora
- Stato
- Risultati (se disponibili)
- Link allo streaming (se disponibile)
- Note

## Funzionalità Avanzate

### Generazione Automatica dei Match

ETO può generare automaticamente i match per un torneo in base al formato selezionato:

1. Dal menu principale, seleziona "Tornei".
2. Trova il torneo per cui desideri generare i match e clicca su "Dettagli".
3. Clicca sulla scheda "Match".
4. Clicca sul pulsante "Genera Match".
5. Seleziona le opzioni di generazione:
   - Formato (se diverso da quello del torneo)
   - Seeding (casuale o manuale)
   - Data di inizio
   - Intervallo tra i match
6. Clicca su "Genera".

### Notifiche

ETO invia notifiche agli utenti per eventi importanti:

- Inviti a un team
- Iscrizione a un torneo confermata
- Match programmati
- Risultati dei match
- Modifiche ai tornei o ai match

Puoi gestire le tue preferenze di notifica:

1. Clicca sul tuo nome utente nell'angolo in alto a destra.
2. Seleziona "Impostazioni".
3. Clicca sulla scheda "Notifiche".
4. Seleziona quali notifiche desideri ricevere e come (email, sito, entrambi).
5. Clicca su "Salva".

### Esportazione Dati

Puoi esportare i dati dei tornei, team e match in vari formati:

1. Dalla pagina di un torneo, team o match, clicca sul pulsante "Esporta".
2. Seleziona il formato desiderato (CSV, PDF, JSON).
3. Clicca su "Esporta".

### Integrazione con Streaming

ETO supporta l'integrazione con piattaforme di streaming:

1. Dalla pagina di modifica di un match, inserisci l'URL dello streaming.
2. Seleziona la piattaforma (Twitch, YouTube, ecc.).
3. Clicca su "Aggiorna Match".

Il player dello streaming sarà automaticamente incorporato nella pagina del match.

## Risoluzione Problemi

### Problemi di Accesso

**Non riesco ad accedere al mio account**

1. Verifica di utilizzare le credenziali corrette.
2. Assicurati che il tuo account sia stato attivato.
3. Se hai dimenticato la password, utilizza la funzione "Password dimenticata?".
4. Se continui ad avere problemi, contatta l'amministratore.

**Ho ricevuto un errore "Account bloccato"**

1. Attendi 30 minuti e riprova.
2. Se il problema persiste, contatta l'amministratore.

### Problemi con i Tornei

**Non riesco a iscrivermi a un torneo**

1. Verifica che le registrazioni siano aperte.
2. Assicurati di essere il capitano di un team.
3. Verifica che il tuo team soddisfi i requisiti del torneo.
4. Se il problema persiste, contatta l'amministratore.

**Non vedo i match del mio torneo**

1. Verifica che i match siano stati generati.
2. Se sei l'amministratore del torneo, genera i match manualmente.
3. Se il problema persiste, contatta l'amministratore.

### Problemi con i Team

**Non riesco a creare un team**

1. Verifica di aver compilato tutti i campi obbligatori.
2. Assicurati che il nome del team non sia già in uso.
3. Se il problema persiste, contatta l'amministratore.

**Non riesco a invitare membri al mio team**

1. Verifica di essere il capitano del team.
2. Assicurati di utilizzare un indirizzo email o un nome utente valido.
3. Verifica che l'utente non sia già stato invitato.
4. Se il problema persiste, contatta l'amministratore.

### Problemi con i Match

**Non riesco a registrare i risultati di un match**

1. Verifica di essere il capitano di uno dei team o un amministratore.
2. Assicurati che il match sia nello stato "In corso" o "In attesa".
3. Se il problema persiste, contatta l'amministratore.

**I risultati del mio match non sono stati confermati**

1. Attendi che l'altro capitano o un amministratore confermi i risultati.
2. Se sono passate più di 24 ore, contatta l'amministratore.

## Domande Frequenti (FAQ)

**Posso partecipare a più tornei contemporaneamente?**

Sì, puoi partecipare a quanti tornei desideri, a condizione che i tuoi team soddisfino i requisiti di ciascun torneo.

**Posso essere membro di più team?**

Sì, puoi essere membro di più team, ma puoi essere capitano di un solo team per gioco.

**Come posso diventare amministratore?**

Gli amministratori sono designati dall'amministratore principale del sistema. Contatta l'amministratore principale se desideri diventare amministratore.

**Posso modificare i risultati di un match dopo che sono stati confermati?**

No, una volta che i risultati sono stati confermati, non possono essere modificati. Contatta l'amministratore se ritieni che ci sia stato un errore.

**Come posso segnalare un comportamento scorretto?**

Dalla pagina di un torneo, team o match, clicca sul pulsante "Segnala" e compila il modulo con i dettagli della segnalazione.

**Posso personalizzare l'aspetto del mio team o torneo?**

Sì, puoi caricare un logo per il tuo team e un'immagine in evidenza per il tuo torneo. In futuro, potrebbero essere aggiunte ulteriori opzioni di personalizzazione.

---

Per ulteriori informazioni o assistenza, contatta il supporto all'indirizzo support@eto-esports.com o utilizza il modulo di contatto sul sito.

© 2025 Esports Tournament Organizer (ETO). Tutti i diritti riservati.
