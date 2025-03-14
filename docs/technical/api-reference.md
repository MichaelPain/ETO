# Riferimento API - Esports Tournament Organizer (ETO)

## Introduzione

Questo documento fornisce una documentazione tecnica completa dell'API REST di Esports Tournament Organizer (ETO). L'API consente l'accesso programmatico a tutte le funzionalità del sistema, permettendo l'integrazione con applicazioni di terze parti, lo sviluppo di client mobili e l'automazione di vari processi.

## Informazioni Generali

### Endpoint Base

Tutti gli endpoint API sono disponibili al seguente URL base:

```
https://your-wordpress-site.com/wp-json/eto/v1/
```

### Formati di Risposta

L'API restituisce dati in formato JSON. Le risposte hanno la seguente struttura generale:

```json
{
  "data": {
    // I dati richiesti
  },
  "meta": {
    "total": 42,       // Numero totale di elementi
    "pages": 5,        // Numero totale di pagine
    "current_page": 1  // Pagina corrente
  }
}
```

In caso di errore, la risposta avrà la seguente struttura:

```json
{
  "code": "error_code",
  "message": "Descrizione dell'errore",
  "data": {
    "status": 400  // Codice di stato HTTP
  }
}
```

### Autenticazione

L'API utilizza chiavi API per l'autenticazione. Le richieste autenticate devono includere l'header `X-ETO-API-Key`.

Esempio:

```
X-ETO-API-Key: your_api_key_here
```

Le chiavi API possono essere generate nell'interfaccia amministrativa di ETO, nella sezione "Impostazioni" > "API Keys".

### Livelli di Accesso

Le chiavi API hanno diversi livelli di accesso:

- **read**: Consente solo operazioni di lettura
- **write**: Consente operazioni di lettura e scrittura
- **admin**: Consente tutte le operazioni, incluse quelle amministrative

### Paginazione

Le richieste che restituiscono più elementi supportano la paginazione. Utilizza i parametri `page` e `per_page` per controllare la paginazione:

```
GET /wp-json/eto/v1/tournaments?page=2&per_page=20
```

Le informazioni sulla paginazione sono incluse nell'oggetto `meta` della risposta e negli header HTTP:

- `X-WP-Total`: Numero totale di elementi
- `X-WP-TotalPages`: Numero totale di pagine

### Filtraggio

Molti endpoint supportano il filtraggio dei risultati. I filtri sono specificati come parametri query:

```
GET /wp-json/eto/v1/tournaments?status=active&game=League%20of%20Legends
```

### Ordinamento

È possibile specificare l'ordinamento dei risultati utilizzando i parametri `orderby` e `order`:

```
GET /wp-json/eto/v1/tournaments?orderby=start_date&order=desc
```

### Inclusione di Relazioni

Alcuni endpoint supportano l'inclusione di dati correlati nella risposta. Utilizza il parametro `include` per specificare le relazioni da includere:

```
GET /wp-json/eto/v1/tournaments/123?include=teams,matches
```

### Limitazione dei Campi

È possibile limitare i campi inclusi nella risposta utilizzando il parametro `fields`:

```
GET /wp-json/eto/v1/tournaments?fields=id,name,start_date
```

### Codici di Stato HTTP

L'API utilizza i seguenti codici di stato HTTP:

- `200 OK`: La richiesta è stata completata con successo
- `201 Created`: La risorsa è stata creata con successo
- `400 Bad Request`: La richiesta non è valida
- `401 Unauthorized`: Autenticazione richiesta
- `403 Forbidden`: Non hai i permessi necessari
- `404 Not Found`: La risorsa richiesta non esiste
- `405 Method Not Allowed`: Il metodo HTTP non è supportato per questo endpoint
- `429 Too Many Requests`: Hai superato il limite di richieste
- `500 Internal Server Error`: Errore interno del server

## Endpoint API

### Tornei

#### Ottieni Tutti i Tornei

```
GET /wp-json/eto/v1/tournaments
```

**Parametri**:

| Nome | Tipo | Descrizione | Default |
|------|------|-------------|---------|
| page | integer | Numero di pagina | 1 |
| per_page | integer | Elementi per pagina | 10 |
| status | string | Filtra per stato (pending, active, completed, cancelled) | tutti |
| game | string | Filtra per gioco | tutti |
| search | string | Cerca nei nomi e nelle descrizioni | - |
| orderby | string | Campo per l'ordinamento (id, name, start_date, end_date, created_at) | id |
| order | string | Direzione dell'ordinamento (asc, desc) | desc |
| include | string | Relazioni da includere (teams, matches) | - |
| fields | string | Campi da includere nella risposta | tutti |

**Risposta**:

```json
{
  "data": [
    {
      "id": 123,
      "name": "Torneo di League of Legends",
      "description": "Descrizione del torneo",
      "game": "League of Legends",
      "format": "single_elimination",
      "start_date": "2025-04-01T10:00:00",
      "end_date": "2025-04-03T18:00:00",
      "registration_start": "2025-03-01T00:00:00",
      "registration_end": "2025-03-30T23:59:59",
      "status": "active",
      "min_teams": 8,
      "max_teams": 16,
      "rules": "Regolamento del torneo...",
      "prizes": "Premi del torneo...",
      "featured_image": "https://example.com/images/tournament123.jpg",
      "created_at": "2025-02-15T14:30:00",
      "updated_at": "2025-02-20T09:15:00",
      "teams": [
        {
          "id": 456,
          "name": "Team Alpha"
        },
        {
          "id": 457,
          "name": "Team Beta"
        }
      ]
    },
    // Altri tornei...
  ],
  "meta": {
    "total": 42,
    "pages": 5,
    "current_page": 1
  }
}
```

#### Ottieni un Singolo Torneo

```
GET /wp-json/eto/v1/tournaments/{id}
```

**Parametri URL**:

| Nome | Tipo | Descrizione |
|------|------|-------------|
| id | integer | ID del torneo |

**Parametri Query**:

| Nome | Tipo | Descrizione | Default |
|------|------|-------------|---------|
| include | string | Relazioni da includere (teams, matches) | - |
| fields | string | Campi da includere nella risposta | tutti |

**Risposta**:

```json
{
  "data": {
    "id": 123,
    "name": "Torneo di League of Legends",
    "description": "Descrizione del torneo",
    "game": "League of Legends",
    "format": "single_elimination",
    "start_date": "2025-04-01T10:00:00",
    "end_date": "2025-04-03T18:00:00",
    "registration_start": "2025-03-01T00:00:00",
    "registration_end": "2025-03-30T23:59:59",
    "status": "active",
    "min_teams": 8,
    "max_teams": 16,
    "rules": "Regolamento del torneo...",
    "prizes": "Premi del torneo...",
    "featured_image": "https://example.com/images/tournament123.jpg",
    "created_at": "2025-02-15T14:30:00",
    "updated_at": "2025-02-20T09:15:00",
    "teams": [
      {
        "id": 456,
        "name": "Team Alpha",
        "logo_url": "https://example.com/images/team456.jpg"
      },
      {
        "id": 457,
        "name": "Team Beta",
        "logo_url": "https://example.com/images/team457.jpg"
      }
    ],
    "matches": [
      {
        "id": 789,
        "team1_id": 456,
        "team2_id": 457,
        "scheduled_date": "2025-04-01T14:00:00",
        "status": "pending"
      },
      // Altri match...
    ]
  }
}
```

#### Crea un Nuovo Torneo

```
POST /wp-json/eto/v1/tournaments
```

**Autenticazione**: Richiesta con livello di accesso `write` o `admin`

**Parametri**:

| Nome | Tipo | Descrizione | Obbligatorio |
|------|------|-------------|-------------|
| name | string | Nome del torneo | Sì |
| description | string | Descrizione del torneo | No |
| game | string | Gioco | Sì |
| format | string | Formato del torneo | Sì |
| start_date | string | Data di inizio (formato ISO 8601) | Sì |
| end_date | string | Data di fine (formato ISO 8601) | Sì |
| registration_start | string | Data di inizio registrazioni (formato ISO 8601) | No |
| registration_end | string | Data di fine registrazioni (formato ISO 8601) | No |
| status | string | Stato (pending, active, completed, cancelled) | No (default: pending) |
| min_teams | integer | Numero minimo di team | No |
| max_teams | integer | Numero massimo di team | No |
| rules | string | Regolamento del torneo | No |
| prizes | string | Premi del torneo | No |
| featured_image | string | URL dell'immagine in evidenza | No |

**Risposta**:

```json
{
  "data": {
    "id": 124,
    "name": "Nuovo Torneo",
    "description": "Descrizione del nuovo torneo",
    "game": "Fortnite",
    "format": "single_elimination",
    "start_date": "2025-05-01T10:00:00",
    "end_date": "2025-05-03T18:00:00",
    "registration_start": "2025-04-01T00:00:00",
    "registration_end": "2025-04-30T23:59:59",
    "status": "pending",
    "min_teams": 8,
    "max_teams": 16,
    "rules": "Regolamento del torneo...",
    "prizes": "Premi del torneo...",
    "featured_image": "https://example.com/images/tournament124.jpg",
    "created_at": "2025-03-14T14:30:00",
    "updated_at": "2025-03-14T14:30:00"
  }
}
```

#### Aggiorna un Torneo Esistente

```
PUT /wp-json/eto/v1/tournaments/{id}
```

**Autenticazione**: Richiesta con livello di accesso `write` o `admin`

**Parametri URL**:

| Nome | Tipo | Descrizione |
|------|------|-------------|
| id | integer | ID del torneo |

**Parametri Body**: Gli stessi di POST, tutti opzionali

**Risposta**:

```json
{
  "data": {
    "id": 123,
    "name": "Torneo di League of Legends (Aggiornato)",
    "description": "Descrizione aggiornata del torneo",
    "game": "League of Legends",
    "format": "single_elimination",
    "start_date": "2025-04-01T10:00:00",
    "end_date": "2025-04-03T18:00:00",
    "registration_start": "2025-03-01T00:00:00",
    "registration_end": "2025-03-30T23:59:59",
    "status": "active",
    "min_teams": 8,
    "max_teams": 16,
    "rules": "Regolamento aggiornato del torneo...",
    "prizes": "Premi aggiornati del torneo...",
    "featured_image": "https://example.com/images/tournament123_updated.jpg",
    "created_at": "2025-02-15T14:30:00",
    "updated_at": "2025-03-14T15:45:00"
  }
}
```

#### Elimina un Torneo

```
DELETE /wp-json/eto/v1/tournaments/{id}
```

**Autenticazione**: Richiesta con livello di accesso `admin`

**Parametri URL**:

| Nome | Tipo | Descrizione |
|------|------|-------------|
| id | integer | ID del torneo |

**Risposta**:

```json
{
  "data": {
    "message": "Torneo eliminato con successo",
    "id": 123
  }
}
```

### Team

#### Ottieni Tutti i Team

```
GET /wp-json/eto/v1/teams
```

**Parametri**:

| Nome | Tipo | Descrizione | Default |
|------|------|-------------|---------|
| page | integer | Numero di pagina | 1 |
| per_page | integer | Elementi per pagina | 10 |
| game | string | Filtra per gioco | tutti |
| tournament_id | integer | Filtra per torneo | tutti |
| search | string | Cerca nei nomi e nelle descrizioni | - |
| orderby | string | Campo per l'ordinamento (id, name, created_at) | id |
| order | string | Direzione dell'ordinamento (asc, desc) | desc |
| include | string | Relazioni da includere (members, tournaments) | - |
| fields | string | Campi da includere nella risposta | tutti |

**Risposta**:

```json
{
  "data": [
    {
      "id": 456,
      "name": "Team Alpha",
      "description": "Descrizione del team",
      "game": "League of Legends",
      "captain_id": 789,
      "logo_url": "https://example.com/images/team456.jpg",
      "email": "contact@teamalpha.com",
      "website": "https://teamalpha.com",
      "social_media": {
        "twitter": "https://twitter.com/teamalpha",
        "facebook": "https://facebook.com/teamalpha"
      },
      "created_at": "2025-01-10T12:00:00",
      "updated_at": "2025-02-05T16:30:00"
    },
    // Altri team...
  ],
  "meta": {
    "total": 35,
    "pages": 4,
    "current_page": 1
  }
}
```

#### Ottieni un Singolo Team

```
GET /wp-json/eto/v1/teams/{id}
```

**Parametri URL**:

| Nome | Tipo | Descrizione |
|------|------|-------------|
| id | integer | ID del team |

**Parametri Query**:

| Nome | Tipo | Descrizione | Default |
|------|------|-------------|---------|
| include | string | Relazioni da includere (members, tournaments) | - |
| fields | string | Campi da includere nella risposta | tutti |

**Risposta**:

```json
{
  "data": {
    "id": 456,
    "name": "Team Alpha",
    "description": "Descrizione del team",
    "game": "League of Legends",
    "captain_id": 789,
    "logo_url": "https://example.com/images/team456.jpg",
    "email": "contact@teamalpha.com",
    "website": "https://teamalpha.com",
    "social_media": {
      "twitter": "https://twitter.com/teamalpha",
      "facebook": "https://facebook.com/teamalpha"
    },
    "created_at": "2025-01-10T12:00:00",
    "updated_at": "2025-02-05T16:30:00",
    "members": [
      {
        "id": 789,
        "user_id": 101,
        "username": "player1",
        "role": "captain",
        "joined_date": "2025-01-10T12:00:00"
      },
      {
        "id": 790,
        "user_id": 102,
        "username": "player2",
        "role": "member",
        "joined_date": "2025-01-12T14:30:00"
      }
    ],
    "tournaments": [
      {
        "id": 123,
        "name": "Torneo di League of Legends",
        "status": "active"
      }
    ]
  }
}
```

#### Crea un Nuovo Team

```
POST /wp-json/eto/v1/teams
```

**Autenticazione**: Richiesta con livello di accesso `write` o `admin`

**Parametri**:

| Nome | Tipo | Descrizione | Obbligatorio |
|------|------|-------------|-------------|
| name | string | Nome del team | Sì |
| description | string | Descrizione del team | No |
| game | string | Gioco principale | Sì |
| captain_id | integer | ID dell'utente capitano | Sì |
| logo_url | string | URL del logo | No |
| email | string | Email di contatto | No |
| website | string | Sito web | No |
| social_media | object | Oggetto con i social media | No |

**Risposta**:

```json
{
  "data": {
    "id": 458,
    "name": "Nuovo Team",
    "description": "Descrizione del nuovo team",
    "game": "Fortnite",
    "captain_id": 103,
    "logo_url": "https://example.com/images/team458.jpg",
    "email": "contact@newteam.com",
    "website": "https://newteam.com",
    "social_media": {
      "twitter": "https://twitter.com/newteam",
      "facebook": "https://facebook.com/newteam"
    },
    "created_at": "2025-03-14T16:00:00",
    "updated_at": "2025-03-14T16:00:00"
  }
}
```

#### Aggiorna un Team Esistente

```
PUT /wp-json/eto/v1/teams/{id}
```

**Autenticazione**: Richiesta con livello di accesso `write` o `admin`

**Parametri URL**:

| Nome | Tipo | Descrizione |
|------|------|-------------|
| id | integer | ID del team |

**Parametri Body**: Gli stessi di POST, tutti opzionali

**Risposta**:

```json
{
  "data": {
    "id": 456,
    "name": "Team Alpha (Aggiornato)",
    "description": "Descrizione aggiornata del team",
    "game": "League of Legends",
    "captain_id": 789,
    "logo_url": "https://example.com/images/team456_updated.jpg",
    "email": "updated@teamalpha.com",
    "website": "https://teamalpha.com",
    "social_media": {
      "twitter": "https://twitter.com/teamalpha",
      "facebook": "https://facebook.com/teamalpha",
      "instagram": "https://instagram.com/teamalpha"
    },
    "created_at": "2025-01-10T12:00:00",
    "updated_at": "2025-03-14T16:15:00"
  }
}
```

#### Elimina un Team

```
DELETE /wp-json/eto/v1/teams/{id}
```

**Autenticazione**: Richiesta con livello di accesso `admin`

**Parametri URL**:

| Nome | Tipo | Descrizione |
|------|------|-------------|
| id | integer | ID del team |

**Risposta**:

```json
{
  "data": {
    "message": "Team eliminato con successo",
    "id": 456
  }
}
```

### Match

#### Ottieni Tutti i Match

```
GET /wp-json/eto/v1/matches
```

**Parametri**:

| Nome | Tipo | Descrizione | Default |
|------|------|-------------|---------|
| page | integer | Numero di pagina | 1 |
| per_page | integer | Elementi per pagina | 10 |
| tournament_id | integer | Filtra per torneo | tutti |
| team_id | integer | Filtra per team | tutti |
| status | string | Filtra per stato (pending, in_progress, completed, cancelled) | tutti |
| orderby | string | Campo per l'ordinamento (id, scheduled_date, round) | scheduled_date |
| order | string | Direzione dell'ordinamento (asc, desc) | asc |
| fields | string | Campi da includere nella risposta | tutti |

**Risposta**:

```json
{
  "data": [
    {
      "id": 789,
      "tournament_id": 123,
      "team1_id": 456,
      "team2_id": 457,
      "round": 1,
      "match_number": 1,
      "scheduled_date": "2025-04-01T14:00:00",
      "status": "pending",
      "team1_score": null,
      "team2_score": null,
      "stream_url": "https://twitch.tv/tournament123/match1",
      "notes": "Match di apertura del torneo",
      "created_at": "2025-02-20T10:00:00",
      "updated_at": "2025-02-20T10:00:00"
    },
    // Altri match...
  ],
  "meta": {
    "total": 28,
    "pages": 3,
    "current_page": 1
  }
}
```

#### Ottieni un Singolo Match

```
GET /wp-json/eto/v1/matches/{id}
```

**Parametri URL**:

| Nome | Tipo | Descrizione |
|------|------|-------------|
| id | integer | ID del match |

**Parametri Query**:

| Nome | Tipo | Descrizione | Default |
|------|------|-------------|---------|
| fields | string | Campi da includere nella risposta | tutti |

**Risposta**:

```json
{
  "data": {
    "id": 789,
    "tournament_id": 123,
    "tournament_name": "Torneo di League of Legends",
    "team1_id": 456,
    "team1_name": "Team Alpha",
    "team1_logo": "https://example.com/images/team456.jpg",
    "team2_id": 457,
    "team2_name": "Team Beta",
    "team2_logo": "https://example.com/images/team457.jpg",
    "round": 1,
    "match_number": 1,
    "scheduled_date": "2025-04-01T14:00:00",
    "status": "pending",
    "team1_score": null,
    "team2_score": null,
    "stream_url": "https://twitch.tv/tournament123/match1",
    "notes": "Match di apertura del torneo",
    "created_at": "2025-02-20T10:00:00",
    "updated_at": "2025-02-20T10:00:00"
  }
}
```

#### Crea un Nuovo Match

```
POST /wp-json/eto/v1/matches
```

**Autenticazione**: Richiesta con livello di accesso `write` o `admin`

**Parametri**:

| Nome | Tipo | Descrizione | Obbligatorio |
|------|------|-------------|-------------|
| tournament_id | integer | ID del torneo | Sì |
| team1_id | integer | ID del team 1 | Sì |
| team2_id | integer | ID del team 2 | Sì |
| round | integer | Numero del round | No (default: 1) |
| match_number | integer | Numero del match | No (default: 1) |
| scheduled_date | string | Data programmata (formato ISO 8601) | Sì |
| status | string | Stato (pending, in_progress, completed, cancelled) | No (default: pending) |
| stream_url | string | URL dello streaming | No |
| notes | string | Note sul match | No |
| team1_score | integer | Punteggio team 1 | No |
| team2_score | integer | Punteggio team 2 | No |

**Risposta**:

```json
{
  "data": {
    "id": 790,
    "tournament_id": 123,
    "team1_id": 458,
    "team2_id": 459,
    "round": 1,
    "match_number": 2,
    "scheduled_date": "2025-04-01T16:00:00",
    "status": "pending",
    "team1_score": null,
    "team2_score": null,
    "stream_url": "https://twitch.tv/tournament123/match2",
    "notes": "Secondo match del torneo",
    "created_at": "2025-03-14T16:30:00",
    "updated_at": "2025-03-14T16:30:00"
  }
}
```

#### Aggiorna un Match Esistente

```
PUT /wp-json/eto/v1/matches/{id}
```

**Autenticazione**: Richiesta con livello di accesso `write` o `admin`

**Parametri URL**:

| Nome | Tipo | Descrizione |
|------|------|-------------|
| id | integer | ID del match |

**Parametri Body**: Gli stessi di POST, tutti opzionali

**Risposta**:

```json
{
  "data": {
    "id": 789,
    "tournament_id": 123,
    "team1_id": 456,
    "team2_id": 457,
    "round": 1,
    "match_number": 1,
    "scheduled_date": "2025-04-01T15:00:00",
    "status": "in_progress",
    "team1_score": null,
    "team2_score": null,
    "stream_url": "https://twitch.tv/tournament123/match1_updated",
    "notes": "Match di apertura del torneo (orario aggiornato)",
    "created_at": "2025-02-20T10:00:00",
    "updated_at": "2025-03-14T16:45:00"
  }
}
```

#### Aggiorna i Risultati di un Match

```
PUT /wp-json/eto/v1/matches/{id}/results
```

**Autenticazione**: Richiesta con livello di accesso `write` o `admin`

**Parametri URL**:

| Nome | Tipo | Descrizione |
|------|------|-------------|
| id | integer | ID del match |

**Parametri Body**:

| Nome | Tipo | Descrizione | Obbligatorio |
|------|------|-------------|-------------|
| team1_score | integer | Punteggio team 1 | Sì |
| team2_score | integer | Punteggio team 2 | Sì |
| status | string | Stato del match | No (default: completed) |

**Risposta**:

```json
{
  "data": {
    "id": 789,
    "tournament_id": 123,
    "team1_id": 456,
    "team2_id": 457,
    "round": 1,
    "match_number": 1,
    "scheduled_date": "2025-04-01T15:00:00",
    "status": "completed",
    "team1_score": 3,
    "team2_score": 2,
    "stream_url": "https://twitch.tv/tournament123/match1_updated",
    "notes": "Match di apertura del torneo (orario aggiornato)",
    "created_at": "2025-02-20T10:00:00",
    "updated_at": "2025-03-14T17:00:00"
  }
}
```

#### Elimina un Match

```
DELETE /wp-json/eto/v1/matches/{id}
```

**Autenticazione**: Richiesta con livello di accesso `admin`

**Parametri URL**:

| Nome | Tipo | Descrizione |
|------|------|-------------|
| id | integer | ID del match |

**Risposta**:

```json
{
  "data": {
    "message": "Match eliminato con successo",
    "id": 789
  }
}
```

### API Keys

#### Ottieni Tutte le Chiavi API

```
GET /wp-json/eto/v1/api-keys
```

**Autenticazione**: Richiesta con livello di accesso `admin`

**Parametri**:

| Nome | Tipo | Descrizione | Default |
|------|------|-------------|---------|
| page | integer | Numero di pagina | 1 |
| per_page | integer | Elementi per pagina | 10 |
| user_id | integer | Filtra per utente | tutti |
| access_level | string | Filtra per livello di accesso | tutti |

**Risposta**:

```json
{
  "data": [
    {
      "id": 1,
      "user_id": 101,
      "username": "admin",
      "description": "Chiave API per l'integrazione con l'app mobile",
      "access_level": "write",
      "last_used": "2025-03-10T09:45:00",
      "expires_at": "2026-03-14T00:00:00",
      "created_at": "2025-01-01T00:00:00",
      "updated_at": "2025-03-10T09:45:00"
    },
    // Altre chiavi API...
  ],
  "meta": {
    "total": 5,
    "pages": 1,
    "current_page": 1
  }
}
```

#### Crea una Nuova Chiave API

```
POST /wp-json/eto/v1/api-keys
```

**Autenticazione**: Richiesta con livello di accesso `admin`

**Parametri**:

| Nome | Tipo | Descrizione | Obbligatorio |
|------|------|-------------|-------------|
| user_id | integer | ID dell'utente associato | Sì |
| description | string | Descrizione della chiave | No |
| access_level | string | Livello di accesso (read, write, admin) | No (default: read) |
| expires_at | string | Data di scadenza (formato ISO 8601) | No |

**Risposta**:

```json
{
  "data": {
    "id": 6,
    "user_id": 102,
    "username": "manager",
    "api_key": "eto_api_abcdef123456789",
    "description": "Chiave API per l'integrazione con il sito web",
    "access_level": "read",
    "last_used": null,
    "expires_at": "2026-03-14T00:00:00",
    "created_at": "2025-03-14T17:15:00",
    "updated_at": "2025-03-14T17:15:00"
  }
}
```

#### Revoca una Chiave API

```
DELETE /wp-json/eto/v1/api-keys/{id}
```

**Autenticazione**: Richiesta con livello di accesso `admin`

**Parametri URL**:

| Nome | Tipo | Descrizione |
|------|------|-------------|
| id | integer | ID della chiave API |

**Risposta**:

```json
{
  "data": {
    "message": "Chiave API revocata con successo",
    "id": 6
  }
}
```

### Utenti

#### Ottieni Tutti gli Utenti

```
GET /wp-json/eto/v1/users
```

**Autenticazione**: Richiesta con livello di accesso `read`, `write` o `admin`

**Parametri**:

| Nome | Tipo | Descrizione | Default |
|------|------|-------------|---------|
| page | integer | Numero di pagina | 1 |
| per_page | integer | Elementi per pagina | 10 |
| search | string | Cerca nei nomi utente e nelle email | - |
| role | string | Filtra per ruolo | tutti |
| orderby | string | Campo per l'ordinamento (id, username, registered_date) | id |
| order | string | Direzione dell'ordinamento (asc, desc) | desc |

**Risposta**:

```json
{
  "data": [
    {
      "id": 101,
      "username": "admin",
      "email": "admin@example.com",
      "first_name": "Admin",
      "last_name": "User",
      "roles": ["administrator"],
      "registered_date": "2025-01-01T00:00:00"
    },
    // Altri utenti...
  ],
  "meta": {
    "total": 50,
    "pages": 5,
    "current_page": 1
  }
}
```

#### Ottieni un Singolo Utente

```
GET /wp-json/eto/v1/users/{id}
```

**Autenticazione**: Richiesta con livello di accesso `read`, `write` o `admin`

**Parametri URL**:

| Nome | Tipo | Descrizione |
|------|------|-------------|
| id | integer | ID dell'utente |

**Risposta**:

```json
{
  "data": {
    "id": 101,
    "username": "admin",
    "email": "admin@example.com",
    "first_name": "Admin",
    "last_name": "User",
    "roles": ["administrator"],
    "registered_date": "2025-01-01T00:00:00",
    "teams": [
      {
        "id": 456,
        "name": "Team Alpha",
        "role": "captain"
      }
    ]
  }
}
```

## Esempi di Utilizzo

### Esempio: Ottenere Tutti i Tornei Attivi

**Richiesta**:

```bash
curl -X GET "https://your-wordpress-site.com/wp-json/eto/v1/tournaments?status=active" \
     -H "X-ETO-API-Key: your_api_key"
```

**Risposta**:

```json
{
  "data": [
    {
      "id": 123,
      "name": "Torneo di League of Legends",
      "game": "League of Legends",
      "format": "single_elimination",
      "start_date": "2025-04-01T10:00:00",
      "end_date": "2025-04-03T18:00:00",
      "status": "active"
    },
    {
      "id": 125,
      "name": "Torneo di Fortnite",
      "game": "Fortnite",
      "format": "double_elimination",
      "start_date": "2025-04-05T12:00:00",
      "end_date": "2025-04-07T20:00:00",
      "status": "active"
    }
  ],
  "meta": {
    "total": 2,
    "pages": 1,
    "current_page": 1
  }
}
```

### Esempio: Creare un Nuovo Torneo

**Richiesta**:

```bash
curl -X POST "https://your-wordpress-site.com/wp-json/eto/v1/tournaments" \
     -H "X-ETO-API-Key: your_api_key" \
     -H "Content-Type: application/json" \
     -d '{
       "name": "Torneo di Valorant",
       "description": "Il primo torneo di Valorant della stagione",
       "game": "Valorant",
       "format": "single_elimination",
       "start_date": "2025-05-01T10:00:00",
       "end_date": "2025-05-03T18:00:00",
       "registration_start": "2025-04-01T00:00:00",
       "registration_end": "2025-04-30T23:59:59",
       "status": "pending",
       "min_teams": 8,
       "max_teams": 16,
       "rules": "Regolamento del torneo...",
       "prizes": "Premi del torneo..."
     }'
```

**Risposta**:

```json
{
  "data": {
    "id": 126,
    "name": "Torneo di Valorant",
    "description": "Il primo torneo di Valorant della stagione",
    "game": "Valorant",
    "format": "single_elimination",
    "start_date": "2025-05-01T10:00:00",
    "end_date": "2025-05-03T18:00:00",
    "registration_start": "2025-04-01T00:00:00",
    "registration_end": "2025-04-30T23:59:59",
    "status": "pending",
    "min_teams": 8,
    "max_teams": 16,
    "rules": "Regolamento del torneo...",
    "prizes": "Premi del torneo...",
    "created_at": "2025-03-14T17:30:00",
    "updated_at": "2025-03-14T17:30:00"
  }
}
```

### Esempio: Aggiornare i Risultati di un Match

**Richiesta**:

```bash
curl -X PUT "https://your-wordpress-site.com/wp-json/eto/v1/matches/789/results" \
     -H "X-ETO-API-Key: your_api_key" \
     -H "Content-Type: application/json" \
     -d '{
       "team1_score": 3,
       "team2_score": 2,
       "status": "completed"
     }'
```

**Risposta**:

```json
{
  "data": {
    "id": 789,
    "tournament_id": 123,
    "team1_id": 456,
    "team2_id": 457,
    "round": 1,
    "match_number": 1,
    "scheduled_date": "2025-04-01T15:00:00",
    "status": "completed",
    "team1_score": 3,
    "team2_score": 2,
    "stream_url": "https://twitch.tv/tournament123/match1_updated",
    "notes": "Match di apertura del torneo (orario aggiornato)",
    "created_at": "2025-02-20T10:00:00",
    "updated_at": "2025-03-14T17:45:00"
  }
}
```

## Gestione degli Errori

### Errori Comuni

#### Autenticazione Fallita

```json
{
  "code": "eto_api_authentication_failed",
  "message": "Autenticazione fallita: chiave API non valida o mancante",
  "data": {
    "status": 401
  }
}
```

#### Permessi Insufficienti

```json
{
  "code": "eto_api_insufficient_permissions",
  "message": "Permessi insufficienti per eseguire questa operazione",
  "data": {
    "status": 403
  }
}
```

#### Risorsa Non Trovata

```json
{
  "code": "eto_api_resource_not_found",
  "message": "La risorsa richiesta non esiste",
  "data": {
    "status": 404
  }
}
```

#### Parametri Mancanti o Non Validi

```json
{
  "code": "eto_api_invalid_params",
  "message": "Parametri mancanti o non validi",
  "data": {
    "status": 400,
    "params": {
      "name": "Il campo name è obbligatorio",
      "start_date": "Il campo start_date deve essere una data valida"
    }
  }
}
```

#### Errore del Server

```json
{
  "code": "eto_api_server_error",
  "message": "Si è verificato un errore interno del server",
  "data": {
    "status": 500
  }
}
```

### Codici di Errore

| Codice | Descrizione |
|--------|-------------|
| eto_api_authentication_failed | Autenticazione fallita |
| eto_api_insufficient_permissions | Permessi insufficienti |
| eto_api_resource_not_found | Risorsa non trovata |
| eto_api_invalid_params | Parametri non validi |
| eto_api_server_error | Errore del server |
| eto_api_rate_limit_exceeded | Limite di richieste superato |
| eto_api_method_not_allowed | Metodo non consentito |
| eto_api_conflict | Conflitto (es. nome già in uso) |

## Limitazioni e Best Practices

### Rate Limiting

L'API implementa un limite di richieste per prevenire abusi:

- 1000 richieste per ora per chiave API
- 10 richieste al secondo per chiave API

Se superi questi limiti, riceverai un errore `429 Too Many Requests`.

### Caching

Per migliorare le prestazioni, è consigliabile implementare il caching lato client. L'API supporta il caching HTTP standard:

- L'header `ETag` è incluso nelle risposte
- L'header `Last-Modified` è incluso nelle risposte
- Puoi utilizzare gli header `If-None-Match` e `If-Modified-Since` nelle richieste

### Ottimizzazione delle Richieste

- Utilizza il parametro `fields` per limitare i campi restituiti
- Utilizza il parametro `include` solo quando necessario
- Implementa la paginazione per le richieste che restituiscono molti elementi

## Appendice

### Changelog API

#### v1.0.0 (2025-01-01)

- Versione iniziale dell'API

#### v1.1.0 (2025-02-15)

- Aggiunto endpoint per i risultati dei match
- Aggiunto supporto per il filtraggio avanzato
- Migliorata la documentazione

#### v1.2.0 (2025-03-10)

- Aggiunto supporto per la gestione delle chiavi API
- Aggiunto supporto per il parametro `fields`
- Migliorati i messaggi di errore

---

Per ulteriori informazioni o assistenza, contatta il team di supporto tecnico all'indirizzo tech-support@eto-esports.com.

© 2025 Esports Tournament Organizer (ETO). Tutti i diritti riservati.
