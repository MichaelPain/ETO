<?php
/**
 * Documentazione API per ETO
 * 
 * @package ETO
 * @subpackage API
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_API_Documentation {
    /**
     * Versione dell'API
     *
     * @var string
     */
    private $api_version = 'v1';

    /**
     * Namespace dell'API
     *
     * @var string
     */
    private $api_namespace;

    /**
     * Costruttore
     */
    public function __construct() {
        $this->api_namespace = 'eto/'. $this->api_version;
        
        // Registra gli hook
        add_action('admin_menu', array($this, 'add_api_documentation_page'));
        add_action('rest_api_init', array($this, 'register_documentation_endpoint'));
    }

    /**
     * Aggiunge la pagina di documentazione API al menu admin
     */
    public function add_api_documentation_page() {
        add_submenu_page(
            'eto-dashboard',
            __('Documentazione API', 'eto'),
            __('API', 'eto'),
            'manage_options',
            'eto-api-documentation',
            array($this, 'render_documentation_page')
        );
    }

    /**
     * Registra un endpoint per ottenere la documentazione API in formato JSON
     */
    public function register_documentation_endpoint() {
        register_rest_route($this->api_namespace, '/docs', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_api_documentation'),
                'permission_callback' => '__return_true',
            ),
        ));
    }

    /**
     * Renderizza la pagina di documentazione API
     */
    public function render_documentation_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Documentazione API ETO', 'eto'); ?></h1>
            
            <div class="eto-api-docs">
                <div class="eto-api-intro">
                    <p><?php _e('Benvenuto nella documentazione dell\'API REST di ETO (Esports Tournament Organizer). Questa API consente di accedere e gestire tornei, team e match in modo programmatico.', 'eto'); ?></p>
                    
                    <h2><?php _e('Autenticazione', 'eto'); ?></h2>
                    <p><?php _e('Per utilizzare l\'API è necessaria una chiave API. Le richieste autenticate devono includere l\'header <code>X-ETO-API-Key</code> con una chiave API valida.', 'eto'); ?></p>
                    <p><?php _e('Le chiavi API possono essere generate e gestite nella sezione Impostazioni > API Keys.', 'eto'); ?></p>
                    
                    <h2><?php _e('Formato delle risposte', 'eto'); ?></h2>
                    <p><?php _e('Tutte le risposte sono in formato JSON. Le risposte di successo hanno un codice di stato HTTP 2xx e contengono i dati richiesti. Le risposte di errore hanno un codice di stato HTTP 4xx o 5xx e contengono un oggetto error con un messaggio descrittivo.', 'eto'); ?></p>
                    
                    <h2><?php _e('Paginazione', 'eto'); ?></h2>
                    <p><?php _e('Le richieste che restituiscono più elementi supportano la paginazione. Utilizza i parametri <code>page</code> e <code>per_page</code> per controllare la paginazione.', 'eto'); ?></p>
                    <p><?php _e('Le informazioni sulla paginazione sono incluse negli header della risposta:', 'eto'); ?></p>
                    <ul>
                        <li><code>X-WP-Total</code>: <?php _e('numero totale di elementi', 'eto'); ?></li>
                        <li><code>X-WP-TotalPages</code>: <?php _e('numero totale di pagine', 'eto'); ?></li>
                    </ul>
                </div>
                
                <div class="eto-api-endpoints">
                    <h2><?php _e('Endpoint disponibili', 'eto'); ?></h2>
                    
                    <div class="eto-api-section">
                        <h3><?php _e('Tornei', 'eto'); ?></h3>
                        
                        <div class="eto-api-endpoint">
                            <h4>GET /<?php echo esc_html($this->api_namespace); ?>/tournaments</h4>
                            <p><?php _e('Ottiene l\'elenco dei tornei.', 'eto'); ?></p>
                            <p><strong><?php _e('Parametri:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>page</code>: <?php _e('numero di pagina (default: 1)', 'eto'); ?></li>
                                <li><code>per_page</code>: <?php _e('elementi per pagina (default: 10)', 'eto'); ?></li>
                                <li><code>game</code>: <?php _e('filtra per gioco', 'eto'); ?></li>
                                <li><code>status</code>: <?php _e('filtra per stato (pending, active, completed, cancelled)', 'eto'); ?></li>
                                <li><code>include</code>: <?php _e('include relazioni (teams, matches)', 'eto'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="eto-api-endpoint">
                            <h4>GET /<?php echo esc_html($this->api_namespace); ?>/tournaments/{id}</h4>
                            <p><?php _e('Ottiene un singolo torneo.', 'eto'); ?></p>
                            <p><strong><?php _e('Parametri URL:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>id</code>: <?php _e('ID del torneo', 'eto'); ?></li>
                            </ul>
                            <p><strong><?php _e('Parametri query:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>include</code>: <?php _e('include relazioni (teams, matches)', 'eto'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="eto-api-endpoint">
                            <h4>POST /<?php echo esc_html($this->api_namespace); ?>/tournaments</h4>
                            <p><?php _e('Crea un nuovo torneo.', 'eto'); ?></p>
                            <p><strong><?php _e('Richiede autenticazione con livello di accesso:', 'eto'); ?></strong> write</p>
                            <p><strong><?php _e('Parametri corpo:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>name</code>: <?php _e('nome del torneo (obbligatorio)', 'eto'); ?></li>
                                <li><code>description</code>: <?php _e('descrizione del torneo', 'eto'); ?></li>
                                <li><code>game</code>: <?php _e('gioco (obbligatorio)', 'eto'); ?></li>
                                <li><code>format</code>: <?php _e('formato del torneo (obbligatorio)', 'eto'); ?></li>
                                <li><code>start_date</code>: <?php _e('data di inizio (obbligatorio, formato YYYY-MM-DD HH:MM:SS)', 'eto'); ?></li>
                                <li><code>end_date</code>: <?php _e('data di fine (obbligatorio, formato YYYY-MM-DD HH:MM:SS)', 'eto'); ?></li>
                                <li><code>registration_start</code>: <?php _e('data di inizio registrazioni (formato YYYY-MM-DD HH:MM:SS)', 'eto'); ?></li>
                                <li><code>registration_end</code>: <?php _e('data di fine registrazioni (formato YYYY-MM-DD HH:MM:SS)', 'eto'); ?></li>
                                <li><code>status</code>: <?php _e('stato (pending, active, completed, cancelled, default: pending)', 'eto'); ?></li>
                                <li><code>min_teams</code>: <?php _e('numero minimo di team', 'eto'); ?></li>
                                <li><code>max_teams</code>: <?php _e('numero massimo di team', 'eto'); ?></li>
                                <li><code>rules</code>: <?php _e('regolamento del torneo', 'eto'); ?></li>
                                <li><code>prizes</code>: <?php _e('premi del torneo', 'eto'); ?></li>
                                <li><code>featured_image</code>: <?php _e('URL dell\'immagine in evidenza', 'eto'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="eto-api-endpoint">
                            <h4>PUT /<?php echo esc_html($this->api_namespace); ?>/tournaments/{id}</h4>
                            <p><?php _e('Aggiorna un torneo esistente.', 'eto'); ?></p>
                            <p><strong><?php _e('Richiede autenticazione con livello di accesso:', 'eto'); ?></strong> write</p>
                            <p><strong><?php _e('Parametri URL:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>id</code>: <?php _e('ID del torneo', 'eto'); ?></li>
                            </ul>
                            <p><strong><?php _e('Parametri corpo:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>name</code>: <?php _e('nome del torneo', 'eto'); ?></li>
                                <li><code>description</code>: <?php _e('descrizione del torneo', 'eto'); ?></li>
                                <li><code>game</code>: <?php _e('gioco', 'eto'); ?></li>
                                <li><code>format</code>: <?php _e('formato del torneo', 'eto'); ?></li>
                                <li><code>start_date</code>: <?php _e('data di inizio (formato YYYY-MM-DD HH:MM:SS)', 'eto'); ?></li>
                                <li><code>end_date</code>: <?php _e('data di fine (formato YYYY-MM-DD HH:MM:SS)', 'eto'); ?></li>
                                <li><code>registration_start</code>: <?php _e('data di inizio registrazioni (formato YYYY-MM-DD HH:MM:SS)', 'eto'); ?></li>
                                <li><code>registration_end</code>: <?php _e('data di fine registrazioni (formato YYYY-MM-DD HH:MM:SS)', 'eto'); ?></li>
                                <li><code>status</code>: <?php _e('stato (pending, active, completed, cancelled)', 'eto'); ?></li>
                                <li><code>min_teams</code>: <?php _e('numero minimo di team', 'eto'); ?></li>
                                <li><code>max_teams</code>: <?php _e('numero massimo di team', 'eto'); ?></li>
                                <li><code>rules</code>: <?php _e('regolamento del torneo', 'eto'); ?></li>
                                <li><code>prizes</code>: <?php _e('premi del torneo', 'eto'); ?></li>
                                <li><code>featured_image</code>: <?php _e('URL dell\'immagine in evidenza', 'eto'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="eto-api-endpoint">
                            <h4>DELETE /<?php echo esc_html($this->api_namespace); ?>/tournaments/{id}</h4>
                            <p><?php _e('Elimina un torneo.', 'eto'); ?></p>
                            <p><strong><?php _e('Richiede autenticazione con livello di accesso:', 'eto'); ?></strong> admin</p>
                            <p><strong><?php _e('Parametri URL:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>id</code>: <?php _e('ID del torneo', 'eto'); ?></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="eto-api-section">
                        <h3><?php _e('Team', 'eto'); ?></h3>
                        
                        <div class="eto-api-endpoint">
                            <h4>GET /<?php echo esc_html($this->api_namespace); ?>/teams</h4>
                            <p><?php _e('Ottiene l\'elenco dei team.', 'eto'); ?></p>
                            <p><strong><?php _e('Parametri:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>page</code>: <?php _e('numero di pagina (default: 1)', 'eto'); ?></li>
                                <li><code>per_page</code>: <?php _e('elementi per pagina (default: 10)', 'eto'); ?></li>
                                <li><code>game</code>: <?php _e('filtra per gioco', 'eto'); ?></li>
                                <li><code>tournament_id</code>: <?php _e('filtra per torneo', 'eto'); ?></li>
                                <li><code>include</code>: <?php _e('include relazioni (members, tournaments)', 'eto'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="eto-api-endpoint">
                            <h4>GET /<?php echo esc_html($this->api_namespace); ?>/teams/{id}</h4>
                            <p><?php _e('Ottiene un singolo team.', 'eto'); ?></p>
                            <p><strong><?php _e('Parametri URL:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>id</code>: <?php _e('ID del team', 'eto'); ?></li>
                            </ul>
                            <p><strong><?php _e('Parametri query:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>include</code>: <?php _e('include relazioni (members, tournaments)', 'eto'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="eto-api-endpoint">
                            <h4>POST /<?php echo esc_html($this->api_namespace); ?>/teams</h4>
                            <p><?php _e('Crea un nuovo team.', 'eto'); ?></p>
                            <p><strong><?php _e('Richiede autenticazione con livello di accesso:', 'eto'); ?></strong> write</p>
                            <p><strong><?php _e('Parametri corpo:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>name</code>: <?php _e('nome del team (obbligatorio)', 'eto'); ?></li>
                                <li><code>description</code>: <?php _e('descrizione del team', 'eto'); ?></li>
                                <li><code>game</code>: <?php _e('gioco (obbligatorio)', 'eto'); ?></li>
                                <li><code>captain_id</code>: <?php _e('ID dell\'utente capitano (obbligatorio)', 'eto'); ?></li>
                                <li><code>logo_url</code>: <?php _e('URL del logo', 'eto'); ?></li>
                                <li><code>email</code>: <?php _e('email di contatto', 'eto'); ?></li>
                                <li><code>website</code>: <?php _e('sito web', 'eto'); ?></li>
                                <li><code>social_media</code>: <?php _e('oggetto con i social media (es. {"twitter": "url", "facebook": "url"})', 'eto'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="eto-api-endpoint">
                            <h4>PUT /<?php echo esc_html($this->api_namespace); ?>/teams/{id}</h4>
                            <p><?php _e('Aggiorna un team esistente.', 'eto'); ?></p>
                            <p><strong><?php _e('Richiede autenticazione con livello di accesso:', 'eto'); ?></strong> write</p>
                            <p><strong><?php _e('Parametri URL:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>id</code>: <?php _e('ID del team', 'eto'); ?></li>
                            </ul>
                            <p><strong><?php _e('Parametri corpo:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>name</code>: <?php _e('nome del team', 'eto'); ?></li>
                                <li><code>description</code>: <?php _e('descrizione del team', 'eto'); ?></li>
                                <li><code>game</code>: <?php _e('gioco', 'eto'); ?></li>
                                <li><code>captain_id</code>: <?php _e('ID dell\'utente capitano', 'eto'); ?></li>
                                <li><code>logo_url</code>: <?php _e('URL del logo', 'eto'); ?></li>
                                <li><code>email</code>: <?php _e('email di contatto', 'eto'); ?></li>
                                <li><code>website</code>: <?php _e('sito web', 'eto'); ?></li>
                                <li><code>social_media</code>: <?php _e('oggetto con i social media (es. {"twitter": "url", "facebook": "url"})', 'eto'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="eto-api-endpoint">
                            <h4>DELETE /<?php echo esc_html($this->api_namespace); ?>/teams/{id}</h4>
                            <p><?php _e('Elimina un team.', 'eto'); ?></p>
                            <p><strong><?php _e('Richiede autenticazione con livello di accesso:', 'eto'); ?></strong> admin</p>
                            <p><strong><?php _e('Parametri URL:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>id</code>: <?php _e('ID del team', 'eto'); ?></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="eto-api-section">
                        <h3><?php _e('Match', 'eto'); ?></h3>
                        
                        <div class="eto-api-endpoint">
                            <h4>GET /<?php echo esc_html($this->api_namespace); ?>/matches</h4>
                            <p><?php _e('Ottiene l\'elenco dei match.', 'eto'); ?></p>
                            <p><strong><?php _e('Parametri:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>page</code>: <?php _e('numero di pagina (default: 1)', 'eto'); ?></li>
                                <li><code>per_page</code>: <?php _e('elementi per pagina (default: 10)', 'eto'); ?></li>
                                <li><code>tournament_id</code>: <?php _e('filtra per torneo', 'eto'); ?></li>
                                <li><code>team_id</code>: <?php _e('filtra per team', 'eto'); ?></li>
                                <li><code>status</code>: <?php _e('filtra per stato (pending, in_progress, completed, cancelled)', 'eto'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="eto-api-endpoint">
                            <h4>GET /<?php echo esc_html($this->api_namespace); ?>/matches/{id}</h4>
                            <p><?php _e('Ottiene un singolo match.', 'eto'); ?></p>
                            <p><strong><?php _e('Parametri URL:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>id</code>: <?php _e('ID del match', 'eto'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="eto-api-endpoint">
                            <h4>POST /<?php echo esc_html($this->api_namespace); ?>/matches</h4>
                            <p><?php _e('Crea un nuovo match.', 'eto'); ?></p>
                            <p><strong><?php _e('Richiede autenticazione con livello di accesso:', 'eto'); ?></strong> write</p>
                            <p><strong><?php _e('Parametri corpo:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>tournament_id</code>: <?php _e('ID del torneo (obbligatorio)', 'eto'); ?></li>
                                <li><code>team1_id</code>: <?php _e('ID del team 1 (obbligatorio)', 'eto'); ?></li>
                                <li><code>team2_id</code>: <?php _e('ID del team 2 (obbligatorio)', 'eto'); ?></li>
                                <li><code>round</code>: <?php _e('numero del round (default: 1)', 'eto'); ?></li>
                                <li><code>match_number</code>: <?php _e('numero del match (default: 1)', 'eto'); ?></li>
                                <li><code>scheduled_date</code>: <?php _e('data programmata (obbligatorio, formato YYYY-MM-DD HH:MM:SS)', 'eto'); ?></li>
                                <li><code>status</code>: <?php _e('stato (pending, in_progress, completed, cancelled, default: pending)', 'eto'); ?></li>
                                <li><code>stream_url</code>: <?php _e('URL dello streaming', 'eto'); ?></li>
                                <li><code>notes</code>: <?php _e('note sul match', 'eto'); ?></li>
                                <li><code>team1_score</code>: <?php _e('punteggio team 1', 'eto'); ?></li>
                                <li><code>team2_score</code>: <?php _e('punteggio team 2', 'eto'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="eto-api-endpoint">
                            <h4>PUT /<?php echo esc_html($this->api_namespace); ?>/matches/{id}</h4>
                            <p><?php _e('Aggiorna un match esistente.', 'eto'); ?></p>
                            <p><strong><?php _e('Richiede autenticazione con livello di accesso:', 'eto'); ?></strong> write</p>
                            <p><strong><?php _e('Parametri URL:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>id</code>: <?php _e('ID del match', 'eto'); ?></li>
                            </ul>
                            <p><strong><?php _e('Parametri corpo:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>tournament_id</code>: <?php _e('ID del torneo', 'eto'); ?></li>
                                <li><code>team1_id</code>: <?php _e('ID del team 1', 'eto'); ?></li>
                                <li><code>team2_id</code>: <?php _e('ID del team 2', 'eto'); ?></li>
                                <li><code>round</code>: <?php _e('numero del round', 'eto'); ?></li>
                                <li><code>match_number</code>: <?php _e('numero del match', 'eto'); ?></li>
                                <li><code>scheduled_date</code>: <?php _e('data programmata (formato YYYY-MM-DD HH:MM:SS)', 'eto'); ?></li>
                                <li><code>status</code>: <?php _e('stato (pending, in_progress, completed, cancelled)', 'eto'); ?></li>
                                <li><code>stream_url</code>: <?php _e('URL dello streaming', 'eto'); ?></li>
                                <li><code>notes</code>: <?php _e('note sul match', 'eto'); ?></li>
                                <li><code>team1_score</code>: <?php _e('punteggio team 1', 'eto'); ?></li>
                                <li><code>team2_score</code>: <?php _e('punteggio team 2', 'eto'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="eto-api-endpoint">
                            <h4>PUT /<?php echo esc_html($this->api_namespace); ?>/matches/{id}/results</h4>
                            <p><?php _e('Aggiorna i risultati di un match.', 'eto'); ?></p>
                            <p><strong><?php _e('Richiede autenticazione con livello di accesso:', 'eto'); ?></strong> write</p>
                            <p><strong><?php _e('Parametri URL:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>id</code>: <?php _e('ID del match', 'eto'); ?></li>
                            </ul>
                            <p><strong><?php _e('Parametri corpo:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>team1_score</code>: <?php _e('punteggio team 1 (obbligatorio)', 'eto'); ?></li>
                                <li><code>team2_score</code>: <?php _e('punteggio team 2 (obbligatorio)', 'eto'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="eto-api-endpoint">
                            <h4>DELETE /<?php echo esc_html($this->api_namespace); ?>/matches/{id}</h4>
                            <p><?php _e('Elimina un match.', 'eto'); ?></p>
                            <p><strong><?php _e('Richiede autenticazione con livello di accesso:', 'eto'); ?></strong> admin</p>
                            <p><strong><?php _e('Parametri URL:', 'eto'); ?></strong></p>
                            <ul>
                                <li><code>id</code>: <?php _e('ID del match', 'eto'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="eto-api-examples">
                    <h2><?php _e('Esempi di utilizzo', 'eto'); ?></h2>
                    
                    <div class="eto-api-example">
                        <h3><?php _e('Esempio: Ottenere tutti i tornei attivi', 'eto'); ?></h3>
                        <pre><code>curl -X GET "<?php echo esc_url(rest_url($this->api_namespace . '/tournaments?status=active')); ?>" \
-H "X-ETO-API-Key: your_api_key"</code></pre>
                    </div>
                    
                    <div class="eto-api-example">
                        <h3><?php _e('Esempio: Creare un nuovo torneo', 'eto'); ?></h3>
                        <pre><code>curl -X POST "<?php echo esc_url(rest_url($this->api_namespace . '/tournaments')); ?>" \
-H "X-ETO-API-Key: your_api_key" \
-H "Content-Type: application/json" \
-d '{
    "name": "Torneo di esempio",
    "description": "Descrizione del torneo",
    "game": "League of Legends",
    "format": "single_elimination",
    "start_date": "2025-04-01 10:00:00",
    "end_date": "2025-04-03 18:00:00",
    "status": "pending",
    "min_teams": 8,
    "max_teams": 16
}'</code></pre>
                    </div>
                    
                    <div class="eto-api-example">
                        <h3><?php _e('Esempio: Aggiornare i risultati di un match', 'eto'); ?></h3>
                        <pre><code>curl -X PUT "<?php echo esc_url(rest_url($this->api_namespace . '/matches/123/results')); ?>" \
-H "X-ETO-API-Key: your_api_key" \
-H "Content-Type: application/json" \
-d '{
    "team1_score": 3,
    "team2_score": 2
}'</code></pre>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .eto-api-docs {
                margin-top: 20px;
                max-width: 1200px;
            }
            
            .eto-api-section {
                margin-bottom: 30px;
                border: 1px solid #e5e5e5;
                background: #fff;
                padding: 20px;
                border-radius: 3px;
            }
            
            .eto-api-endpoint {
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 1px solid #eee;
            }
            
            .eto-api-endpoint:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }
            
            .eto-api-endpoint h4 {
                background: #f9f9f9;
                padding: 10px;
                border-left: 4px solid #0073aa;
                font-family: monospace;
                margin-bottom: 15px;
            }
            
            .eto-api-examples pre {
                background: #f5f5f5;
                padding: 15px;
                overflow-x: auto;
                border-radius: 3px;
                border: 1px solid #ddd;
            }
            
            .eto-api-examples code {
                white-space: pre-wrap;
                word-break: break-word;
            }
        </style>
        <?php
    }

    /**
     * Restituisce la documentazione API in formato JSON
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response Risposta REST
     */
    public function get_api_documentation($request) {
        $docs = array(
            'info' => array(
                'title' => 'ETO API',
                'version' => $this->api_version,
                'description' => 'API REST per ETO (Esports Tournament Organizer)'
            ),
            'authentication' => array(
                'type' => 'apiKey',
                'name' => 'X-ETO-API-Key',
                'in' => 'header'
            ),
            'endpoints' => array(
                'tournaments' => array(
                    'get' => array(
                        'url' => '/' . $this->api_namespace . '/tournaments',
                        'description' => 'Ottiene l\'elenco dei tornei',
                        'parameters' => array(
                            'page' => array(
                                'type' => 'integer',
                                'description' => 'Numero di pagina',
                                'default' => 1
                            ),
                            'per_page' => array(
                                'type' => 'integer',
                                'description' => 'Elementi per pagina',
                                'default' => 10
                            ),
                            'game' => array(
                                'type' => 'string',
                                'description' => 'Filtra per gioco'
                            ),
                            'status' => array(
                                'type' => 'string',
                                'description' => 'Filtra per stato',
                                'enum' => array('pending', 'active', 'completed', 'cancelled')
                            ),
                            'include' => array(
                                'type' => 'string',
                                'description' => 'Include relazioni',
                                'enum' => array('teams', 'matches')
                            )
                        )
                    ),
                    'post' => array(
                        'url' => '/' . $this->api_namespace . '/tournaments',
                        'description' => 'Crea un nuovo torneo',
                        'authentication' => array(
                            'required' => true,
                            'level' => 'write'
                        ),
                        'parameters' => array(
                            'name' => array(
                                'type' => 'string',
                                'description' => 'Nome del torneo',
                                'required' => true
                            ),
                            'description' => array(
                                'type' => 'string',
                                'description' => 'Descrizione del torneo'
                            ),
                            'game' => array(
                                'type' => 'string',
                                'description' => 'Gioco',
                                'required' => true
                            ),
                            'format' => array(
                                'type' => 'string',
                                'description' => 'Formato del torneo',
                                'required' => true
                            ),
                            'start_date' => array(
                                'type' => 'string',
                                'description' => 'Data di inizio (formato YYYY-MM-DD HH:MM:SS)',
                                'required' => true
                            ),
                            'end_date' => array(
                                'type' => 'string',
                                'description' => 'Data di fine (formato YYYY-MM-DD HH:MM:SS)',
                                'required' => true
                            ),
                            'registration_start' => array(
                                'type' => 'string',
                                'description' => 'Data di inizio registrazioni (formato YYYY-MM-DD HH:MM:SS)'
                            ),
                            'registration_end' => array(
                                'type' => 'string',
                                'description' => 'Data di fine registrazioni (formato YYYY-MM-DD HH:MM:SS)'
                            ),
                            'status' => array(
                                'type' => 'string',
                                'description' => 'Stato',
                                'enum' => array('pending', 'active', 'completed', 'cancelled'),
                                'default' => 'pending'
                            ),
                            'min_teams' => array(
                                'type' => 'integer',
                                'description' => 'Numero minimo di team'
                            ),
                            'max_teams' => array(
                                'type' => 'integer',
                                'description' => 'Numero massimo di team'
                            ),
                            'rules' => array(
                                'type' => 'string',
                                'description' => 'Regolamento del torneo'
                            ),
                            'prizes' => array(
                                'type' => 'string',
                                'description' => 'Premi del torneo'
                            ),
                            'featured_image' => array(
                                'type' => 'string',
                                'description' => 'URL dell\'immagine in evidenza'
                            )
                        )
                    ),
                    'get_single' => array(
                        'url' => '/' . $this->api_namespace . '/tournaments/{id}',
                        'description' => 'Ottiene un singolo torneo',
                        'parameters' => array(
                            'id' => array(
                                'type' => 'integer',
                                'description' => 'ID del torneo',
                                'required' => true
                            ),
                            'include' => array(
                                'type' => 'string',
                                'description' => 'Include relazioni',
                                'enum' => array('teams', 'matches')
                            )
                        )
                    ),
                    'put' => array(
                        'url' => '/' . $this->api_namespace . '/tournaments/{id}',
                        'description' => 'Aggiorna un torneo esistente',
                        'authentication' => array(
                            'required' => true,
                            'level' => 'write'
                        ),
                        'parameters' => array(
                            'id' => array(
                                'type' => 'integer',
                                'description' => 'ID del torneo',
                                'required' => true
                            ),
                            // Altri parametri come in POST
                        )
                    ),
                    'delete' => array(
                        'url' => '/' . $this->api_namespace . '/tournaments/{id}',
                        'description' => 'Elimina un torneo',
                        'authentication' => array(
                            'required' => true,
                            'level' => 'admin'
                        ),
                        'parameters' => array(
                            'id' => array(
                                'type' => 'integer',
                                'description' => 'ID del torneo',
                                'required' => true
                            )
                        )
                    )
                ),
                'teams' => array(
                    // Simile a tournaments
                ),
                'matches' => array(
                    // Simile a tournaments
                )
            )
        );
        
        return new WP_REST_Response($docs, 200);
    }
}

// Inizializza la documentazione dell'API
$eto_api_documentation = new ETO_API_Documentation();
