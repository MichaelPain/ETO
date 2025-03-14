# Schema del Database - Esports Tournament Organizer (ETO)

## Introduzione

Questo documento descrive lo schema del database utilizzato da Esports Tournament Organizer (ETO). Il database è progettato per memorizzare tutte le informazioni relative a tornei, team, match, utenti e altre entità del sistema.

## Tabelle Principali

### eto_tournaments

Questa tabella memorizza le informazioni sui tornei.

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | BIGINT(20) UNSIGNED | Chiave primaria, auto-incremento |
| name | VARCHAR(255) | Nome del torneo |
| description | LONGTEXT | Descrizione del torneo |
| game | VARCHAR(100) | Nome del gioco |
| format | VARCHAR(50) | Formato del torneo (single_elimination, double_elimination, round_robin, etc.) |
| start_date | DATETIME | Data e ora di inizio del torneo |
| end_date | DATETIME | Data e ora di fine del torneo |
| registration_start | DATETIME | Data e ora di inizio delle registrazioni |
| registration_end | DATETIME | Data e ora di fine delle registrazioni |
| status | VARCHAR(20) | Stato del torneo (pending, active, completed, cancelled) |
| min_teams | INT(11) | Numero minimo di team richiesti |
| max_teams | INT(11) | Numero massimo di team ammessi |
| rules | LONGTEXT | Regolamento del torneo |
| prizes | LONGTEXT | Descrizione dei premi |
| featured_image | VARCHAR(255) | URL dell'immagine in evidenza |
| created_at | DATETIME | Data e ora di creazione del record |
| updated_at | DATETIME | Data e ora dell'ultimo aggiornamento |

**Indici:**
- PRIMARY KEY (id)
- KEY idx_status (status)
- KEY idx_game (game)
- KEY idx_dates (start_date, end_date)

### eto_teams

Questa tabella memorizza le informazioni sui team.

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | BIGINT(20) UNSIGNED | Chiave primaria, auto-incremento |
| name | VARCHAR(255) | Nome del team |
| description | LONGTEXT | Descrizione del team |
| game | VARCHAR(100) | Gioco principale del team |
| captain_id | BIGINT(20) UNSIGNED | ID dell'utente capitano |
| logo_url | VARCHAR(255) | URL del logo del team |
| email | VARCHAR(100) | Email di contatto |
| website | VARCHAR(255) | Sito web del team |
| social_media | LONGTEXT | JSON con i link ai social media |
| created_at | DATETIME | Data e ora di creazione del record |
| updated_at | DATETIME | Data e ora dell'ultimo aggiornamento |

**Indici:**
- PRIMARY KEY (id)
- KEY idx_game (game)
- KEY idx_captain (captain_id)
- UNIQUE KEY idx_name (name)

### eto_matches

Questa tabella memorizza le informazioni sui match.

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | BIGINT(20) UNSIGNED | Chiave primaria, auto-incremento |
| tournament_id | BIGINT(20) UNSIGNED | ID del torneo associato |
| team1_id | BIGINT(20) UNSIGNED | ID del primo team |
| team2_id | BIGINT(20) UNSIGNED | ID del secondo team |
| round | INT(11) | Numero del round nel torneo |
| match_number | INT(11) | Numero progressivo del match nel round |
| scheduled_date | DATETIME | Data e ora programmata |
| status | VARCHAR(20) | Stato del match (pending, in_progress, completed, cancelled) |
| result | LONGTEXT | JSON con i risultati del match |
| stream_url | VARCHAR(255) | URL dello streaming |
| notes | LONGTEXT | Note aggiuntive |
| created_at | DATETIME | Data e ora di creazione del record |
| updated_at | DATETIME | Data e ora dell'ultimo aggiornamento |

**Indici:**
- PRIMARY KEY (id)
- KEY idx_tournament (tournament_id)
- KEY idx_teams (team1_id, team2_id)
- KEY idx_status (status)
- KEY idx_scheduled (scheduled_date)

### eto_tournament_teams

Questa tabella gestisce la relazione molti-a-molti tra tornei e team.

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | BIGINT(20) UNSIGNED | Chiave primaria, auto-incremento |
| tournament_id | BIGINT(20) UNSIGNED | ID del torneo |
| team_id | BIGINT(20) UNSIGNED | ID del team |
| registration_date | DATETIME | Data e ora di registrazione |
| status | VARCHAR(20) | Stato della registrazione (pending, confirmed, rejected) |
| seed | INT(11) | Posizione di seeding nel torneo |
| created_at | DATETIME | Data e ora di creazione del record |
| updated_at | DATETIME | Data e ora dell'ultimo aggiornamento |

**Indici:**
- PRIMARY KEY (id)
- UNIQUE KEY idx_tournament_team (tournament_id, team_id)
- KEY idx_tournament (tournament_id)
- KEY idx_team (team_id)

### eto_team_members

Questa tabella gestisce la relazione tra team e utenti (membri).

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | BIGINT(20) UNSIGNED | Chiave primaria, auto-incremento |
| team_id | BIGINT(20) UNSIGNED | ID del team |
| user_id | BIGINT(20) UNSIGNED | ID dell'utente |
| role | VARCHAR(50) | Ruolo nel team (captain, manager, coach, member, substitute) |
| joined_date | DATETIME | Data e ora di ingresso nel team |
| status | VARCHAR(20) | Stato del membro (active, inactive) |
| created_at | DATETIME | Data e ora di creazione del record |
| updated_at | DATETIME | Data e ora dell'ultimo aggiornamento |

**Indici:**
- PRIMARY KEY (id)
- UNIQUE KEY idx_team_user (team_id, user_id)
- KEY idx_team (team_id)
- KEY idx_user (user_id)
- KEY idx_role (role)

### eto_api_keys

Questa tabella memorizza le chiavi API per l'autenticazione alle API REST.

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | BIGINT(20) UNSIGNED | Chiave primaria, auto-incremento |
| user_id | BIGINT(20) UNSIGNED | ID dell'utente associato |
| api_key | VARCHAR(64) | Chiave API (hash) |
| description | VARCHAR(255) | Descrizione della chiave |
| access_level | VARCHAR(20) | Livello di accesso (read, write, admin) |
| last_used | DATETIME | Data e ora dell'ultimo utilizzo |
| expires_at | DATETIME | Data e ora di scadenza |
| created_at | DATETIME | Data e ora di creazione del record |
| updated_at | DATETIME | Data e ora dell'ultimo aggiornamento |

**Indici:**
- PRIMARY KEY (id)
- UNIQUE KEY idx_api_key (api_key)
- KEY idx_user (user_id)

### eto_logs

Questa tabella memorizza i log del sistema.

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | BIGINT(20) UNSIGNED | Chiave primaria, auto-incremento |
| level | VARCHAR(20) | Livello del log (info, warning, error, debug) |
| message | TEXT | Messaggio del log |
| context | LONGTEXT | JSON con il contesto del log |
| user_id | BIGINT(20) UNSIGNED | ID dell'utente associato (se applicabile) |
| ip_address | VARCHAR(45) | Indirizzo IP |
| user_agent | VARCHAR(255) | User agent del browser |
| created_at | DATETIME | Data e ora di creazione del record |

**Indici:**
- PRIMARY KEY (id)
- KEY idx_level (level)
- KEY idx_user (user_id)
- KEY idx_created (created_at)

## Tabelle Aggiuntive

### eto_notifications

Questa tabella memorizza le notifiche per gli utenti.

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | BIGINT(20) UNSIGNED | Chiave primaria, auto-incremento |
| user_id | BIGINT(20) UNSIGNED | ID dell'utente destinatario |
| type | VARCHAR(50) | Tipo di notifica |
| title | VARCHAR(255) | Titolo della notifica |
| message | TEXT | Messaggio della notifica |
| link | VARCHAR(255) | Link associato alla notifica |
| is_read | TINYINT(1) | Flag che indica se la notifica è stata letta |
| created_at | DATETIME | Data e ora di creazione del record |
| updated_at | DATETIME | Data e ora dell'ultimo aggiornamento |

**Indici:**
- PRIMARY KEY (id)
- KEY idx_user (user_id)
- KEY idx_type (type)
- KEY idx_is_read (is_read)

### eto_settings

Questa tabella memorizza le impostazioni globali del sistema.

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| id | BIGINT(20) UNSIGNED | Chiave primaria, auto-incremento |
| option_name | VARCHAR(191) | Nome dell'opzione |
| option_value | LONGTEXT | Valore dell'opzione |
| autoload | VARCHAR(20) | Flag per il caricamento automatico |
| created_at | DATETIME | Data e ora di creazione del record |
| updated_at | DATETIME | Data e ora dell'ultimo aggiornamento |

**Indici:**
- PRIMARY KEY (id)
- UNIQUE KEY idx_option_name (option_name)

## Relazioni tra le Tabelle

### Diagramma ER

```
eto_tournaments <---> eto_tournament_teams <---> eto_teams
       ^                                             ^
       |                                             |
       v                                             v
eto_matches                                   eto_team_members
                                                     ^
                                                     |
                                                     v
                                                 wp_users
                                                     ^
                                                     |
                                                     v
                                               eto_api_keys
                                                     ^
                                                     |
                                                     v
                                               eto_notifications
```

### Descrizione delle Relazioni

- **Tornei e Team**: Relazione molti-a-molti attraverso la tabella `eto_tournament_teams`
- **Tornei e Match**: Relazione uno-a-molti (un torneo può avere molti match)
- **Team e Utenti**: Relazione molti-a-molti attraverso la tabella `eto_team_members`
- **Team e Match**: Relazione molti-a-molti (un team può partecipare a molti match, un match coinvolge due team)
- **Utenti e API Keys**: Relazione uno-a-molti (un utente può avere molte chiavi API)
- **Utenti e Notifiche**: Relazione uno-a-molti (un utente può avere molte notifiche)

## Trigger e Procedure

### Trigger per l'Aggiornamento dei Timestamp

```sql
DELIMITER //

CREATE TRIGGER eto_tournaments_before_update
BEFORE UPDATE ON eto_tournaments
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //

-- Trigger simili per le altre tabelle

DELIMITER ;
```

### Procedure per la Generazione dei Match

```sql
DELIMITER //

CREATE PROCEDURE eto_generate_tournament_matches(IN tournament_id BIGINT)
BEGIN
    DECLARE format VARCHAR(50);
    
    -- Ottieni il formato del torneo
    SELECT format INTO format FROM eto_tournaments WHERE id = tournament_id;
    
    -- Genera i match in base al formato
    IF format = 'single_elimination' THEN
        CALL eto_generate_single_elimination_matches(tournament_id);
    ELSEIF format = 'double_elimination' THEN
        CALL eto_generate_double_elimination_matches(tournament_id);
    ELSEIF format = 'round_robin' THEN
        CALL eto_generate_round_robin_matches(tournament_id);
    END IF;
END //

DELIMITER ;
```

## Indici e Performance

### Indici Principali

Ogni tabella ha indici appropriati per ottimizzare le query più comuni:

- Chiavi primarie su tutte le tabelle
- Indici sulle chiavi esterne per le join
- Indici sui campi utilizzati frequentemente nelle clausole WHERE
- Indici sui campi utilizzati per l'ordinamento

### Ottimizzazione delle Query

Le query più pesanti sono ottimizzate attraverso:

- Utilizzo di join appropriati
- Limitazione dei risultati con LIMIT
- Utilizzo di subquery solo quando necessario
- Caching dei risultati quando appropriato

## Migrazione e Versionamento

### Struttura delle Migrazioni

Le migrazioni del database sono gestite attraverso file PHP nella directory `includes/migrations`:

```
includes/migrations/
├── class-eto-migration-manager.php
├── 1.0.0/
│   ├── create_tournaments_table.php
│   ├── create_teams_table.php
│   └── ...
├── 1.1.0/
│   ├── add_featured_image_to_tournaments.php
│   └── ...
└── ...
```

### Esempio di Migrazione

```php
class ETO_Migration_Create_Tournaments_Table extends ETO_Migration {
    public function up() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eto_tournaments';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description LONGTEXT,
            game VARCHAR(100) NOT NULL,
            format VARCHAR(50) NOT NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            registration_start DATETIME,
            registration_end DATETIME,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            min_teams INT(11),
            max_teams INT(11),
            rules LONGTEXT,
            prizes LONGTEXT,
            featured_image VARCHAR(255),
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_game (game),
            KEY idx_dates (start_date, end_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function down() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eto_tournaments';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}
```

## Backup e Ripristino

### Strategia di Backup

ETO implementa una strategia di backup completa:

1. **Backup Automatici**: Eseguiti quotidianamente
2. **Backup Prima delle Migrazioni**: Eseguiti automaticamente prima di ogni migrazione
3. **Backup Manuali**: Disponibili nell'interfaccia amministrativa

### Procedura di Ripristino

Per ripristinare un database da un backup:

1. Accedi all'interfaccia amministrativa
2. Vai su "Strumenti" > "Backup e Ripristino"
3. Seleziona il backup da ripristinare
4. Clicca su "Ripristina"

Oppure, da riga di comando:

```bash
wp eto restore-backup --file=backup_file.sql
```

## Considerazioni sulla Sicurezza

### Protezione dei Dati

- Tutte le password sono hashate con algoritmi sicuri
- I dati sensibili sono criptati nel database
- Le chiavi API sono generate con entropia sufficiente e hashate

### Prevenzione SQL Injection

- Tutte le query utilizzano prepared statements
- I parametri utente sono sempre sanitizzati
- Le query dinamiche sono evitate quando possibile

### Accesso al Database

- L'accesso al database è limitato all'utente del database
- Le credenziali del database sono memorizzate in modo sicuro
- I privilegi del database sono limitati al minimo necessario

## Considerazioni sulla Scalabilità

### Sharding

Per installazioni di grandi dimensioni, è possibile implementare lo sharding:

- Sharding orizzontale per tornei (basato sull'ID del torneo)
- Sharding verticale per separare dati frequentemente e raramente acceduti

### Caching

ETO implementa una strategia di caching a più livelli:

- Cache di oggetti in memoria
- Cache di query nel database
- Cache di pagine a livello di applicazione

### Indici e Partizioni

Per tabelle di grandi dimensioni:

- Partizioni temporali per i log e le notifiche
- Indici compositi per query complesse
- Indici parziali per sottoinsiemi di dati frequentemente acceduti

## Appendice

### Script di Creazione del Database

Lo script completo per la creazione del database è disponibile nel file `includes/migrations/schema.sql`.

### Strumenti di Gestione

ETO fornisce strumenti da riga di comando per la gestione del database:

```bash
# Esegui le migrazioni
wp eto migrate

# Visualizza lo stato delle migrazioni
wp eto migrate-status

# Genera una nuova migrazione
wp eto generate-migration add_new_field_to_tournaments

# Esegui una query SQL
wp eto db-query "SELECT * FROM wp_eto_tournaments LIMIT 10"
```

---

Per ulteriori informazioni o assistenza, contatta il team di supporto tecnico all'indirizzo tech-support@eto-esports.com.

© 2025 Esports Tournament Organizer (ETO). Tutti i diritti riservati.
