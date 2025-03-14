# Guida per Sviluppatori - Esports Tournament Organizer (ETO)

## Introduzione

Benvenuti nella guida per sviluppatori di **Esports Tournament Organizer (ETO)**. Questo documento è destinato agli sviluppatori che desiderano estendere, personalizzare o contribuire al progetto ETO. Qui troverai informazioni dettagliate sull'architettura del sistema, sulle convenzioni di codice, sulle API disponibili e sulle procedure per contribuire al progetto.

## Indice dei Contenuti

1. [Panoramica del Progetto](#panoramica-del-progetto)
2. [Architettura del Sistema](#architettura-del-sistema)
3. [Ambiente di Sviluppo](#ambiente-di-sviluppo)
4. [Struttura del Codice](#struttura-del-codice)
5. [API REST](#api-rest)
6. [Estensione del Sistema](#estensione-del-sistema)
7. [Convenzioni di Codice](#convenzioni-di-codice)
8. [Test](#test)
9. [Procedure di Contribuzione](#procedure-di-contribuzione)
10. [Risorse Aggiuntive](#risorse-aggiuntive)

## Panoramica del Progetto

ETO è una piattaforma completa per la gestione di tornei di esports, sviluppata in PHP con WordPress come framework di base. Il progetto segue un'architettura MVC (Model-View-Controller) per garantire una separazione chiara tra dati, logica di business e interfaccia utente.

### Obiettivi del Progetto

- Fornire una soluzione completa per l'organizzazione e la gestione di tornei di esports
- Offrire un'interfaccia intuitiva per amministratori, organizzatori e partecipanti
- Garantire scalabilità e personalizzazione per diversi tipi di tornei e giochi
- Implementare standard moderni di sicurezza e performance

### Tecnologie Utilizzate

- **Backend**: PHP 7.4+, WordPress 5.8+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **API**: REST API con autenticazione tramite chiavi API
- **Librerie JS**: jQuery, Chart.js, Select2
- **Build Tools**: Webpack, Sass

## Architettura del Sistema

ETO segue un'architettura MVC (Model-View-Controller) personalizzata all'interno dell'ecosistema WordPress:

### Model

I modelli rappresentano le entità principali del sistema e gestiscono l'accesso ai dati:

- `Tournament_Model`: Gestisce i dati relativi ai tornei
- `Team_Model`: Gestisce i dati relativi ai team
- `Match_Model`: Gestisce i dati relativi ai match
- `User_Model`: Estende le funzionalità utente di WordPress

### View

Le viste sono responsabili della presentazione dei dati all'utente:

- **Admin Views**: Template per l'interfaccia amministrativa
- **Frontend Views**: Template per l'interfaccia pubblica
- **Partials**: Componenti riutilizzabili

### Controller

I controller gestiscono la logica di business e coordinano l'interazione tra modelli e viste:

- `Tournament_Controller`: Gestisce le operazioni sui tornei
- `Team_Controller`: Gestisce le operazioni sui team
- `Match_Controller`: Gestisce le operazioni sui match
- `Admin_Controller`: Gestisce l'interfaccia amministrativa
- `API_Controller`: Gestisce le richieste API

### Diagramma dell'Architettura

```
+----------------+     +----------------+     +----------------+
|     Models     |     |  Controllers   |     |     Views      |
+----------------+     +----------------+     +----------------+
| Tournament     | <-> | Tournament     | <-> | Admin Views    |
| Team           | <-> | Team           | <-> | Frontend Views |
| Match          | <-> | Match          | <-> | Partials       |
| User           | <-> | Admin          |     |                |
+----------------+     | API            |     +----------------+
                       +----------------+
                              ^
                              |
                              v
                       +----------------+
                       |   WordPress    |
                       |   Core API     |
                       +----------------+
                              ^
                              |
                              v
                       +----------------+
                       |   Database     |
                       +----------------+
```

## Ambiente di Sviluppo

### Requisiti

- PHP 7.4 o superiore
- MySQL 5.7 o superiore
- WordPress 5.8 o superiore
- Node.js 14 o superiore (per build frontend)
- Composer (per gestione dipendenze PHP)

### Installazione Locale

1. Clona il repository:
   ```bash
   git clone https://github.com/MichaelPain/ETO.git
   cd ETO
   ```

2. Installa le dipendenze PHP:
   ```bash
   composer install
   ```

3. Installa le dipendenze JavaScript:
   ```bash
   npm install
   ```

4. Configura WordPress:
   - Crea un database MySQL
   - Configura wp-config.php con i dettagli del database
   - Esegui l'installazione di WordPress

5. Attiva il plugin ETO:
   - Accedi all'area amministrativa di WordPress
   - Vai su "Plugin"
   - Attiva "Esports Tournament Organizer"

6. Avvia il server di sviluppo per il frontend:
   ```bash
   npm run dev
   ```

### Struttura delle Directory

```
ETO/
├── admin/                  # File per l'area amministrativa
│   ├── controllers/        # Controller dell'area admin
│   └── views/              # Template dell'area admin
├── includes/               # Core del plugin
│   ├── api/                # API REST
│   ├── models/             # Modelli dati
│   └── class-*.php         # Classi principali
├── public/                 # File accessibili pubblicamente
│   ├── css/                # Fogli di stile
│   ├── js/                 # Script JavaScript
│   └── images/             # Immagini
├── templates/              # Template frontend
│   ├── frontend/           # Template pubblici
│   └── partials/           # Componenti riutilizzabili
├── tests/                  # Test automatizzati
├── docs/                   # Documentazione
├── languages/              # File di traduzione
├── vendor/                 # Dipendenze PHP (Composer)
├── node_modules/           # Dipendenze JS (npm)
├── esports-tournament-organizer.php  # File principale del plugin
├── README.md               # Readme del progetto
├── composer.json           # Configurazione Composer
└── package.json            # Configurazione npm
```

## Struttura del Codice

### Modelli

I modelli estendono la classe base `ETO_Base_Model` e implementano metodi per l'accesso e la manipolazione dei dati:

```php
class ETO_Tournament_Model extends ETO_Base_Model {
    // Proprietà
    protected $table_name = 'eto_tournaments';
    protected $fields = [
        'id', 'name', 'description', 'game', 'format',
        'start_date', 'end_date', 'registration_start',
        'registration_end', 'status', 'min_teams',
        'max_teams', 'rules', 'prizes', 'featured_image',
        'created_at', 'updated_at'
    ];
    
    // Metodi
    public function get_teams() {
        // Implementazione
    }
    
    public function get_matches() {
        // Implementazione
    }
    
    // Altri metodi...
}
```

### Controller

I controller estendono la classe base `ETO_Base_Controller` e gestiscono la logica di business:

```php
class ETO_Tournament_Controller extends ETO_Base_Controller {
    // Proprietà
    protected $model;
    
    // Costruttore
    public function __construct() {
        $this->model = new ETO_Tournament_Model();
        $this->register_hooks();
    }
    
    // Registrazione hook WordPress
    protected function register_hooks() {
        add_action('init', [$this, 'register_post_type']);
        add_action('admin_menu', [$this, 'add_menu_pages']);
        // Altri hook...
    }
    
    // Metodi per gestire le richieste
    public function handle_create_tournament() {
        // Implementazione
    }
    
    public function handle_update_tournament() {
        // Implementazione
    }
    
    // Altri metodi...
}
```

### Viste

Le viste sono file PHP che generano l'output HTML:

```php
// admin/views/tournaments/edit.php
<?php
// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php echo esc_html($title); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('eto_update_tournament', 'eto_nonce'); ?>
        <input type="hidden" name="tournament_id" value="<?php echo esc_attr($tournament->get('id')); ?>">
        
        <table class="form-table">
            <tr>
                <th><label for="name"><?php _e('Nome', 'eto'); ?></label></th>
                <td>
                    <input type="text" id="name" name="name" value="<?php echo esc_attr($tournament->get('name')); ?>" class="regular-text" required>
                </td>
            </tr>
            <!-- Altri campi... -->
        </table>
        
        <?php submit_button(__('Aggiorna Torneo', 'eto')); ?>
    </form>
</div>
```

## API REST

ETO fornisce un'API REST completa per l'accesso programmatico ai dati. L'API è disponibile all'endpoint `/wp-json/eto/v1/`.

### Autenticazione

L'API utilizza chiavi API per l'autenticazione. Le richieste autenticate devono includere l'header `X-ETO-API-Key`.

### Endpoint Disponibili

#### Tornei

- `GET /wp-json/eto/v1/tournaments`: Ottiene l'elenco dei tornei
- `GET /wp-json/eto/v1/tournaments/{id}`: Ottiene un singolo torneo
- `POST /wp-json/eto/v1/tournaments`: Crea un nuovo torneo
- `PUT /wp-json/eto/v1/tournaments/{id}`: Aggiorna un torneo esistente
- `DELETE /wp-json/eto/v1/tournaments/{id}`: Elimina un torneo

#### Team

- `GET /wp-json/eto/v1/teams`: Ottiene l'elenco dei team
- `GET /wp-json/eto/v1/teams/{id}`: Ottiene un singolo team
- `POST /wp-json/eto/v1/teams`: Crea un nuovo team
- `PUT /wp-json/eto/v1/teams/{id}`: Aggiorna un team esistente
- `DELETE /wp-json/eto/v1/teams/{id}`: Elimina un team

#### Match

- `GET /wp-json/eto/v1/matches`: Ottiene l'elenco dei match
- `GET /wp-json/eto/v1/matches/{id}`: Ottiene un singolo match
- `POST /wp-json/eto/v1/matches`: Crea un nuovo match
- `PUT /wp-json/eto/v1/matches/{id}`: Aggiorna un match esistente
- `PUT /wp-json/eto/v1/matches/{id}/results`: Aggiorna i risultati di un match
- `DELETE /wp-json/eto/v1/matches/{id}`: Elimina un match

### Esempio di Utilizzo

```javascript
// Ottiene tutti i tornei attivi
fetch('/wp-json/eto/v1/tournaments?status=active', {
    headers: {
        'X-ETO-API-Key': 'your_api_key'
    }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));

// Crea un nuovo torneo
fetch('/wp-json/eto/v1/tournaments', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-ETO-API-Key': 'your_api_key'
    },
    body: JSON.stringify({
        name: 'Nuovo Torneo',
        description: 'Descrizione del torneo',
        game: 'League of Legends',
        format: 'single_elimination',
        start_date: '2025-04-01 10:00:00',
        end_date: '2025-04-03 18:00:00',
        status: 'pending',
        min_teams: 8,
        max_teams: 16
    })
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

Per ulteriori dettagli, consulta la [documentazione completa dell'API](../technical/api-reference.md).

## Estensione del Sistema

ETO è progettato per essere estensibile attraverso vari punti di integrazione.

### Hook e Filtri

ETO fornisce numerosi hook e filtri per personalizzare il comportamento del sistema:

```php
// Esempio: Aggiungere un campo personalizzato al modulo di creazione torneo
add_filter('eto_tournament_form_fields', function($fields) {
    $fields['custom_field'] = [
        'label' => __('Campo personalizzato', 'eto'),
        'type' => 'text',
        'default' => ''
    ];
    return $fields;
});

// Esempio: Eseguire un'azione quando viene creato un torneo
add_action('eto_after_tournament_create', function($tournament_id, $tournament_data) {
    // Codice da eseguire
}, 10, 2);
```

### Creazione di Estensioni

È possibile creare estensioni per ETO seguendo questi passaggi:

1. Crea una nuova classe che estende `ETO_Extension`:

```php
class ETO_My_Extension extends ETO_Extension {
    public function __construct() {
        $this->id = 'my_extension';
        $this->name = __('My Extension', 'eto');
        $this->description = __('Descrizione dell\'estensione', 'eto');
        $this->version = '1.0.0';
        $this->author = 'Your Name';
        
        parent::__construct();
    }
    
    public function init() {
        // Registra hook e filtri
        add_action('eto_after_tournament_create', [$this, 'handle_tournament_create']);
    }
    
    public function handle_tournament_create($tournament_id, $tournament_data) {
        // Implementazione
    }
}

// Registra l'estensione
add_action('eto_register_extensions', function($extension_manager) {
    $extension_manager->register(new ETO_My_Extension());
});
```

2. Crea i file necessari per l'estensione:
   - Modelli personalizzati
   - Controller personalizzati
   - Template personalizzati

3. Registra l'estensione con ETO

### Temi Personalizzati

È possibile personalizzare l'aspetto di ETO sovrascrivendo i template nel tuo tema:

1. Crea una directory `eto` nella directory del tuo tema
2. Copia i file template che desideri personalizzare dalla directory `templates` di ETO alla directory `eto` del tuo tema
3. Modifica i file template copiati secondo le tue esigenze

ETO cercherà prima i template nella directory del tema prima di utilizzare i template predefiniti.

## Convenzioni di Codice

ETO segue le convenzioni di codice di WordPress con alcune modifiche:

### PHP

- Indentazione: Tab (4 spazi)
- Nomi delle classi: CamelCase con prefisso `ETO_`
- Nomi dei metodi e delle variabili: snake_case
- Costanti: UPPERCASE con prefisso `ETO_`
- Commenti: PHPDoc per classi, metodi e proprietà

Esempio:

```php
/**
 * Classe per la gestione dei tornei
 *
 * @package ETO
 * @subpackage Models
 * @since 2.5.2
 */
class ETO_Tournament_Model extends ETO_Base_Model {
    /**
     * Nome della tabella nel database
     *
     * @var string
     */
    protected $table_name = 'eto_tournaments';
    
    /**
     * Ottiene i team associati al torneo
     *
     * @param array $args Argomenti opzionali
     * @return array Array di oggetti team
     */
    public function get_teams($args = []) {
        // Implementazione
    }
}
```

### JavaScript

- Indentazione: 2 spazi
- Sintassi: ES6+
- Nomi delle funzioni e delle variabili: camelCase
- Costanti: UPPERCASE
- Commenti: JSDoc per funzioni e classi

Esempio:

```javascript
/**
 * Classe per la gestione dell'interfaccia torneo
 */
class TournamentManager {
  /**
   * Costruttore
   * 
   * @param {Object} options Opzioni di configurazione
   */
  constructor(options) {
    this.options = Object.assign({
      container: '#tournament-container',
      apiUrl: '/wp-json/eto/v1'
    }, options);
    
    this.init();
  }
  
  /**
   * Inizializza il manager
   */
  init() {
    // Implementazione
  }
}
```

### CSS/SASS

- Indentazione: 2 spazi
- Nomi delle classi: kebab-case con prefisso `eto-`
- Organizzazione: BEM (Block Element Modifier)

Esempio:

```scss
.eto-tournament {
  &__header {
    margin-bottom: 20px;
    
    &--featured {
      background-color: #f5f5f5;
    }
  }
  
  &__title {
    font-size: 24px;
    font-weight: bold;
  }
}
```

## Test

ETO utilizza PHPUnit per i test unitari e di integrazione.

### Esecuzione dei Test

1. Installa le dipendenze di sviluppo:
   ```bash
   composer install --dev
   ```

2. Esegui i test:
   ```bash
   ./vendor/bin/phpunit
   ```

### Scrittura dei Test

I test sono organizzati nella directory `tests`:

```
tests/
├── bootstrap.php           # File di bootstrap per i test
├── unit-tests.php          # Runner per i test unitari
├── integration-tests.php   # Runner per i test di integrazione
├── unit/                   # Test unitari
└── integration/            # Test di integrazione
```

Esempio di test unitario:

```php
class ETO_Tournament_Model_Test extends WP_UnitTestCase {
    public function test_get_teams() {
        // Crea un torneo di test
        $tournament = new ETO_Tournament_Model();
        $tournament->set('name', 'Test Tournament');
        $tournament->set('game', 'Test Game');
        $tournament->set('format', 'single_elimination');
        $tournament->set('start_date', '2025-04-01 10:00:00');
        $tournament->set('end_date', '2025-04-03 18:00:00');
        $tournament->set('status', 'pending');
        $tournament->save();
        
        // Crea alcuni team di test e associali al torneo
        $team1 = new ETO_Team_Model();
        $team1->set('name', 'Team 1');
        $team1->set('game', 'Test Game');
        $team1->save();
        
        $team2 = new ETO_Team_Model();
        $team2->set('name', 'Team 2');
        $team2->set('game', 'Test Game');
        $team2->save();
        
        // Associa i team al torneo
        $tournament->add_team($team1->get('id'));
        $tournament->add_team($team2->get('id'));
        
        // Verifica che get_teams restituisca i team corretti
        $teams = $tournament->get_teams();
        $this->assertEquals(2, count($teams));
        $this->assertEquals('Team 1', $teams[0]['name']);
        $this->assertEquals('Team 2', $teams[1]['name']);
    }
}
```

## Procedure di Contribuzione

### Flusso di Lavoro Git

1. Forka il repository su GitHub
2. Clona il tuo fork:
   ```bash
   git clone https://github.com/YOUR_USERNAME/ETO.git
   ```
3. Crea un branch per la tua feature o bugfix:
   ```bash
   git checkout -b feature/nome-feature
   ```
   o
   ```bash
   git checkout -b fix/nome-bugfix
   ```
4. Sviluppa la tua feature o bugfix
5. Esegui i test:
   ```bash
   ./vendor/bin/phpunit
   ```
6. Commit delle modifiche:
   ```bash
   git commit -m "Descrizione delle modifiche"
   ```
7. Push al tuo fork:
   ```bash
   git push origin feature/nome-feature
   ```
8. Crea una Pull Request su GitHub

### Linee Guida per le Pull Request

- Assicurati che tutti i test passino
- Segui le convenzioni di codice
- Aggiungi test per le nuove funzionalità
- Aggiorna la documentazione se necessario
- Descrivi in dettaglio le modifiche nella descrizione della Pull Request

### Segnalazione dei Bug

Se trovi un bug, apri un issue su GitHub con le seguenti informazioni:

- Descrizione dettagliata del bug
- Passaggi per riprodurre il bug
- Comportamento atteso
- Comportamento effettivo
- Screenshot (se applicabile)
- Versione di ETO, PHP, WordPress e MySQL
- Eventuali errori nei log

## Risorse Aggiuntive

- [Documentazione API](../technical/api-reference.md)
- [Documentazione Database](../technical/database-schema.md)
- [Guida alla Sicurezza](../technical/security-guide.md)
- [Guida alla Performance](../technical/performance-guide.md)

---

Per ulteriori informazioni o assistenza, contatta il team di sviluppo all'indirizzo developers@eto-esports.com o apri un issue su GitHub.

© 2025 Esports Tournament Organizer (ETO). Tutti i diritti riservati.
