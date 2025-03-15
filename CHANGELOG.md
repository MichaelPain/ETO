# Changelog delle correzioni al plugin ETO

## Versione 2.5.4 (Patch di correzione bug)

### Problemi risolti

#### 1. Funzione duplicata `eto_db_query_secure()`
- **Problema**: La funzione `eto_db_query_secure()` era dichiarata due volte:
  - In `includes/utilities.php` (linea 48)
  - In `includes/class-db-query-secure.php` (linea 756)
  - Errore: `PHP Fatal error: Cannot redeclare eto_db_query_secure() (previously declared in /web/htdocs/www.furygaming.net/home/wp-content/plugins/ETO/includes/utilities.php:49) in /web/htdocs/www.furygaming.net/home/wp-content/plugins/ETO/includes/class-db-query-secure.php on line 756`
- **Soluzione**: Rimossa la seconda definizione della funzione in `class-db-query-secure.php` e aggiunto un commento esplicativo che indica che la funzione è già definita in `utilities.php`.

#### 2. Permessi file e directory errati
- **Problema**: I permessi di alcune directory e file non erano corretti:
  - Directory `/logs`: Attuale 755, Richiesto 750
  - Directory `/uploads`: Attuale 755, Richiesto 750
  - File `config.php`: Attuale 755, Richiesto 600
  - Errore: `[ETO] Errore: Permessi directory errati: /web/htdocs/www.furygaming.net/home/wp-content/plugins/ETO/logs/. Attuale: 755, Richiesto: 750`
- **Soluzione**:
  - Create le directory `/logs` e `/uploads` con i permessi corretti (750)
  - Impostati i permessi del file `config.php` a 600
  - **Aggiornamento**: Implementata l'impostazione automatica dei permessi durante l'attivazione del plugin

#### 3. Caricamento anticipato delle traduzioni
- **Problema**: Il caricamento del dominio di traduzione "eto" avveniva troppo presto nel ciclo di WordPress:
  - Errore: `PHP Notice: Function _load_textdomain_just_in_time was called incorrectly. Translation loading for the eto domain was triggered too early. This is usually an indicator for some code in the plugin or theme running too early. Translations should be loaded at the init action or later.`
- **Soluzione**: 
  - Spostato il caricamento delle traduzioni dall'hook `plugins_loaded` all'hook `init`
  - Creata una funzione dedicata `eto_load_textdomain()` per gestire il caricamento delle traduzioni

#### 4. Classe ETO_DB_Query non trovata
- **Problema**: La classe `ETO_DB_Query` non veniva trovata nonostante il file esistesse:
  - Errore: `PHP Fatal error: Uncaught Error: Class "ETO_DB_Query" not found in /web/htdocs/www.furygaming.net/home/wp-content/plugins/ETO/public/class-public-controller.php:34`
- **Soluzione**:
  - Aggiunto il file `includes/class-db-query.php` all'array dei file core da caricare
  - Assicurato che venga caricato prima di `class-db-query-secure.php`

#### 5. Classe ETO_DB_Query_Secure dichiarata più volte
- **Problema**: La classe `ETO_DB_Query_Secure` era dichiarata in più file:
  - In `includes/class-db-query-secure.php`
  - In `includes/class-security.php` (linea 286)
  - Errore: `PHP Fatal error: Cannot declare class ETO_DB_Query_Secure, because the name is already in use in /web/htdocs/www.furygaming.net/home/wp-content/plugins/ETO/includes/class-security.php on line 286`
- **Soluzione**:
  - Rinominata la classe in `class-security.php` da `ETO_DB_Query_Secure` a `ETO_Security_DB_Query`
  - Mantenuta la classe originale in `class-db-query-secure.php`

### Dettagli tecnici delle modifiche

#### 1. Modifica in `includes/class-db-query-secure.php`
```php
// Prima:
/**
 * Funzione helper per ottenere un'istanza della classe di query
 *
 * @return ETO_DB_Query_Secure Istanza della classe di query
 */
function eto_db_query_secure() {
    return new ETO_DB_Query_Secure();
}

// Dopo:
/**
 * Funzione helper per ottenere un'istanza della classe di query
 * Questa funzione è già definita in utilities.php, quindi è stata rimossa da qui
 * per evitare errori di duplicazione.
 *
 * @see utilities.php
 */
// function eto_db_query_secure() rimossa per evitare duplicazione
```

#### 2. Impostazione automatica dei permessi
- Modificata la funzione `eto_check_permissions()` per accettare un parametro `$force_set`
- Quando `$force_set` è `true`, la funzione imposta forzatamente i permessi corretti utilizzando metodi multipli:
  - Utilizzo di `chmod()` di PHP
  - Utilizzo di `exec()` per eseguire comandi shell (se disponibile)
- Aggiornata la funzione di attivazione per utilizzare `eto_check_permissions(true)`

#### 3. Modifica in `esports-tournament-organizer.php` per il caricamento traduzioni
```php
// Prima:
// Inizializzazione
add_action('plugins_loaded', 'eto_init');
function eto_init() {
    // Carica il dominio di traduzione
    load_plugin_textdomain('eto', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Verifica permessi
    eto_check_permissions();
    
    // Includi i file core

// Dopo:
// Caricamento traduzioni (spostato da plugins_loaded a init per risolvere il problema di caricamento anticipato)
add_action('init', 'eto_load_textdomain');
function eto_load_textdomain() {
    load_plugin_textdomain('eto', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Inizializzazione
add_action('plugins_loaded', 'eto_init');
function eto_init() {
    // Verifica permessi
    eto_check_permissions();
    
    // Includi i file core
```

#### 4. Modifica in `esports-tournament-organizer.php` per il caricamento della classe ETO_DB_Query
```php
// Prima:
$core_files = array(
    'includes/config.php',
    'includes/utilities.php',  // Carica utilities.php prima di class-db-query-secure.php
    'includes/class-db-query-secure.php'
);

// Dopo:
$core_files = array(
    'includes/config.php',
    'includes/utilities.php',  // Carica utilities.php prima di class-db-query-secure.php
    'includes/class-db-query.php',  // Carica class-db-query.php prima di class-db-query-secure.php
    'includes/class-db-query-secure.php'
);
```

#### 5. Modifica in `includes/class-security.php` per risolvere la duplicazione della classe
```php
// Prima:
class ETO_DB_Query_Secure {
    
    /**
     * Costruttore
     */
    public function __construct() {
        // Nessuna operazione specifica nel costruttore
    }

// Dopo:
class ETO_Security_DB_Query {
    
    /**
     * Costruttore
     */
    public function __construct() {
        // Nessuna operazione specifica nel costruttore
    }
```

### Note di implementazione
- Le modifiche sono state progettate per essere minimamente invasive, mantenendo la struttura e la funzionalità originale del plugin.
- È stato creato un file di patch (`eto_fixes.patch`) che contiene tutte le modifiche apportate.
- È stato creato uno script di test (`test/test_fixes.php`) per verificare che tutte le correzioni siano state implementate correttamente.
- **Aggiornamento**: Implementata l'impostazione automatica dei permessi durante l'attivazione del plugin per garantire il corretto funzionamento anche in ambienti con restrizioni sui permessi.
- **Aggiornamento**: Corretto il caricamento della classe ETO_DB_Query per risolvere l'errore "Class not found".
- **Aggiornamento**: Risolto il problema di duplicazione della classe ETO_DB_Query_Secure rinominando la seconda istanza.
