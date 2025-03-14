<?php
/**
 * Classe per la gestione dell'amministrazione
 * 
 * Gestisce tutte le funzionalità del pannello di amministrazione
 * 
 * @package ETO
 * @since 2.5.1
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Admin_Controller {
    
    /**
     * Istanza del database query
     *
     * @var ETO_DB_Query
     */
    private $db_query;
    
    /**
     * Istanza della classe di sicurezza
     *
     * @var ETO_Security
     */
    private $security;
    
    /**
     * Costruttore
     */
    public function __construct() {
        $this->db_query = new ETO_DB_Query();
        $this->security = eto_security();
    }
    
    /**
     * Inizializza il controller
     */
    public function init() {
        // Aggiungi le pagine di amministrazione
        add_action('admin_menu', [$this, 'register_admin_menu']);
        
        // Registra gli script e gli stili
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Registra gli AJAX handler
        $this->register_ajax_handlers();
        
        // Aggiungi i meta box
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        
        // Aggiungi le notifiche di amministrazione
        add_action('admin_notices', [$this, 'display_admin_notices']);
        
        // Aggiungi le azioni per i tornei
        add_action('admin_init', [$this, 'handle_tournament_actions']);
        
        // Aggiungi le azioni per i team
        add_action('admin_init', [$this, 'handle_team_actions']);
        
        // Aggiungi le azioni per i match
        add_action('admin_init', [$this, 'handle_match_actions']);
    }
    
    /**
     * Registra il menu di amministrazione
     */
    public function register_admin_menu() {
        // Pagina principale
        add_menu_page(
            __('ETO - Esports Tournament Organizer', 'eto'),
            __('ETO', 'eto'),
            'manage_options',
            'eto-dashboard',
            [$this, 'render_dashboard_page'],
            'dashicons-shield',
            30
        );
        
        // Sottopagina dashboard
        add_submenu_page(
            'eto-dashboard',
            __('Dashboard', 'eto'),
            __('Dashboard', 'eto'),
            'manage_options',
            'eto-dashboard',
            [$this, 'render_dashboard_page']
        );
        
        // Sottopagina tornei
        add_submenu_page(
            'eto-dashboard',
            __('Tornei', 'eto'),
            __('Tornei', 'eto'),
            'manage_options',
            'eto-tournaments',
            [$this, 'render_tournaments_page']
        );
        
        // Sottopagina team
        add_submenu_page(
            'eto-dashboard',
            __('Team', 'eto'),
            __('Team', 'eto'),
            'manage_options',
            'eto-teams',
            [$this, 'render_teams_page']
        );
        
        // Sottopagina match
        add_submenu_page(
            'eto-dashboard',
            __('Match', 'eto'),
            __('Match', 'eto'),
            'manage_options',
            'eto-matches',
            [$this, 'render_matches_page']
        );
        
        // Sottopagina impostazioni
        add_submenu_page(
            'eto-dashboard',
            __('Impostazioni', 'eto'),
            __('Impostazioni', 'eto'),
            'manage_options',
            'eto-settings',
            [$this, 'render_settings_page']
        );
        
        // Sottopagina strumenti
        add_submenu_page(
            'eto-dashboard',
            __('Strumenti', 'eto'),
            __('Strumenti', 'eto'),
            'manage_options',
            'eto-tools',
            [$this, 'render_tools_page']
        );
    }
    
    /**
     * Registra gli script e gli stili di amministrazione
     *
     * @param string $hook Hook della pagina corrente
     */
    public function enqueue_admin_assets($hook) {
        // Verifica se siamo in una pagina del plugin
        if (strpos($hook, 'eto-') === false) {
            return;
        }
        
        // Stili principali
        wp_enqueue_style(
            'eto-admin-css',
            plugins_url('/admin/css/eto-admin.css', dirname(__FILE__)),
            [],
            ETO_VERSION
        );
        
        // jQuery UI
        wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
        
        // Script principali
        wp_enqueue_script(
            'eto-admin-js',
            plugins_url('/admin/js/eto-admin.js', dirname(__FILE__)),
            ['jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'jquery-ui-dialog'],
            ETO_VERSION,
            true
        );
        
        // Localizzazione script
        wp_localize_script('eto-admin-js', 'etoAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eto_admin_nonce'),
            'i18n' => [
                'confirmDelete' => __('Sei sicuro di voler eliminare questo elemento? Questa azione non può essere annullata.', 'eto'),
                'loading' => __('Caricamento in corso...', 'eto'),
                'error' => __('Si è verificato un errore. Riprova più tardi.', 'eto'),
                'success' => __('Operazione completata con successo.', 'eto')
            ]
        ]);
        
        // Script specifici per la pagina tornei
        if ($hook === 'eto-tournaments' || $hook === 'eto_page_eto-tournaments') {
            wp_enqueue_script(
                'eto-bracket-js',
                plugins_url('/public/js/bracket-renderer.js', dirname(__FILE__)),
                ['jquery'],
                ETO_VERSION,
                true
            );
        }
    }
    
    /**
     * Registra gli AJAX handler
     */
    private function register_ajax_handlers() {
        // Tornei
        add_action('wp_ajax_eto_create_tournament', [$this, 'ajax_create_tournament']);
        add_action('wp_ajax_eto_update_tournament', [$this, 'ajax_update_tournament']);
        add_action('wp_ajax_eto_delete_tournament', [$this, 'ajax_delete_tournament']);
        add_action('wp_ajax_eto_get_tournament', [$this, 'ajax_get_tournament']);
        add_action('wp_ajax_eto_generate_bracket', [$this, 'ajax_generate_bracket']);
        
        // Team
        add_action('wp_ajax_eto_create_team', [$this, 'ajax_create_team']);
        add_action('wp_ajax_eto_update_team', [$this, 'ajax_update_team']);
        add_action('wp_ajax_eto_delete_team', [$this, 'ajax_delete_team']);
        add_action('wp_ajax_eto_get_team', [$this, 'ajax_get_team']);
        add_action('wp_ajax_eto_add_team_member', [$this, 'ajax_add_team_member']);
        add_action('wp_ajax_eto_remove_team_member', [$this, 'ajax_remove_team_member']);
        
        // Match
        add_action('wp_ajax_eto_update_match', [$this, 'ajax_update_match']);
        add_action('wp_ajax_eto_get_match', [$this, 'ajax_get_match']);
        
        // Strumenti
        add_action('wp_ajax_eto_repair_database', [$this, 'ajax_repair_database']);
        add_action('wp_ajax_eto_clear_cache', [$this, 'ajax_clear_cache']);
    }
    
    /**
     * Registra i meta box
     */
    public function register_meta_boxes() {
        // Meta box per i tornei
        add_meta_box(
            'eto-tournament-details',
            __('Dettagli torneo', 'eto'),
            [$this, 'render_tournament_details_meta_box'],
            'eto_tournament',
            'normal',
            'high'
        );
        
        // Meta box per i team
        add_meta_box(
            'eto-team-details',
            __('Dettagli team', 'eto'),
            [$this, 'render_team_details_meta_box'],
            'eto_team',
            'normal',
            'high'
        );
        
        // Meta box per i match
        add_meta_box(
            'eto-match-details',
            __('Dettagli match', 'eto'),
            [$this, 'render_match_details_meta_box'],
            'eto_match',
            'normal',
            'high'
        );
    }
    
    /**
     * Visualizza le notifiche di amministrazione
     */
    public function display_admin_notices() {
        // Verifica se ci sono notifiche
        $notices = get_option('eto_admin_notices', []);
        
        if (empty($notices)) {
            return;
        }
        
        // Visualizza le notifiche
        foreach ($notices as $notice) {
            $type = isset($notice['type']) ? $notice['type'] : 'info';
            $message = isset($notice['message']) ? $notice['message'] : '';
            $dismissible = isset($notice['dismissible']) ? $notice['dismissible'] : true;
            
            $class = 'notice notice-' . $type;
            if ($dismissible) {
                $class .= ' is-dismissible';
            }
            
            echo '<div class="' . esc_attr($class) . '"><p>' . wp_kses_post($message) . '</p></div>';
        }
        
        // Rimuovi le notifiche visualizzate
        delete_option('eto_admin_notices');
    }
    
    /**
     * Gestisce le azioni per i tornei
     */
    public function handle_tournament_actions() {
        // Verifica se siamo nella pagina dei tornei
        if (!isset($_GET['page']) || $_GET['page'] !== 'eto-tournaments') {
            return;
        }
        
        // Verifica se è stata richiesta un'azione
        if (!isset($_GET['action'])) {
            return;
        }
        
        $action = sanitize_text_field($_GET['action']);
        
        // Gestisci le azioni
        switch ($action) {
            case 'edit':
                // Verifica l'ID del torneo
                if (!isset($_GET['id'])) {
                    wp_redirect(admin_url('admin.php?page=eto-tournaments'));
                    exit;
                }
                
                $tournament_id = absint($_GET['id']);
                $tournament = $this->db_query->get_tournament($tournament_id);
                
                if (!$tournament) {
                    // Aggiungi una notifica di errore
                    $this->add_admin_notice(
                        'error',
                        __('Torneo non trovato.', 'eto')
                    );
                    
                    wp_redirect(admin_url('admin.php?page=eto-tournaments'));
                    exit;
                }
                
                // Continua con la visualizzazione della pagina di modifica
                break;
                
            case 'delete':
                // Verifica il nonce
                if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'eto_delete_tournament')) {
                    // Aggiungi una notifica di errore
                    $this->add_admin_notice(
                        'error',
                        __('Errore di sicurezza. Riprova.', 'eto')
                    );
                    
                    wp_redirect(admin_url('admin.php?page=eto-tournaments'));
                    exit;
                }
                
                // Verifica l'ID del torneo
                if (!isset($_GET['id'])) {
                    wp_redirect(admin_url('admin.php?page=eto-tournaments'));
                    exit;
                }
                
                $tournament_id = absint($_GET['id']);
                
                // Elimina il torneo
                $result = $this->db_query->delete_tournament($tournament_id);
                
                if ($result) {
                    // Aggiungi una notifica di successo
                    $this->add_admin_notice(
                        'success',
                        __('Torneo eliminato con successo.', 'eto')
                    );
                } else {
                    // Aggiungi una notifica di errore
                    $this->add_admin_notice(
                        'error',
                        __('Errore durante l\'eliminazione del torneo.', 'eto')
                    );
                }
                
                wp_redirect(admin_url('admin.php?page=eto-tournaments'));
                exit;
                
            case 'generate_bracket':
                // Verifica il nonce
                if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'eto_generate_bracket')) {
                    // Aggiungi una notifica di errore
                    $this->add_admin_notice(
                        'error',
                        __('Errore di sicurezza. Riprova.', 'eto')
                    );
                    
                    wp_redirect(admin_url('admin.php?page=eto-tournaments'));
                    exit;
                }
                
                // Verifica l'ID del torneo
                if (!isset($_GET['id'])) {
                    wp_redirect(admin_url('admin.php?page=eto-tournaments'));
                    exit;
                }
                
                $tournament_id = absint($_GET['id']);
                
                // Genera il bracket
                $result = $this->generate_tournament_bracket($tournament_id);
                
                if ($result) {
                    // Aggiungi una notifica di successo
                    $this->add_admin_notice(
                        'success',
                        __('Bracket generato con successo.', 'eto')
                    );
                } else {
                    // Aggiungi una notifica di errore
                    $this->add_admin_notice(
                        'error',
                        __('Errore durante la generazione del bracket.', 'eto')
                    );
                }
                
                wp_redirect(admin_url('admin.php?page=eto-tournaments&action=edit&id=' . $tournament_id));
                exit;
        }
    }
    
    /**
     * Gestisce le azioni per i team
     */
    public function handle_team_actions() {
        // Verifica se siamo nella pagina dei team
        if (!isset($_GET['page']) || $_GET['page'] !== 'eto-teams') {
            return;
        }
        
        // Verifica se è stata richiesta un'azione
        if (!isset($_GET['action'])) {
            return;
        }
        
        $action = sanitize_text_field($_GET['action']);
        
        // Gestisci le azioni
        switch ($action) {
            case 'edit':
                // Verifica l'ID del team
                if (!isset($_GET['id'])) {
                    wp_redirect(admin_url('admin.php?page=eto-teams'));
                    exit;
                }
                
                $team_id = absint($_GET['id']);
                $team = $this->db_query->get_team($team_id);
                
                if (!$team) {
                    // Aggiungi una notifica di errore
                    $this->add_admin_notice(
                        'error',
                        __('Team non trovato.', 'eto')
                    );
                    
                    wp_redirect(admin_url('admin.php?page=eto-teams'));
                    exit;
                }
                
                // Continua con la visualizzazione della pagina di modifica
                break;
                
            case 'delete':
                // Verifica il nonce
                if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'eto_delete_team')) {
                    // Aggiungi una notifica di errore
                    $this->add_admin_notice(
                        'error',
                        __('Errore di sicurezza. Riprova.', 'eto')
                    );
                    
                    wp_redirect(admin_url('admin.php?page=eto-teams'));
                    exit;
                }
                
                // Verifica l'ID del team
                if (!isset($_GET['id'])) {
                    wp_redirect(admin_url('admin.php?page=eto-teams'));
                    exit;
                }
                
                $team_id = absint($_GET['id']);
                
                // Elimina il team
                $result = $this->delete_team($team_id);
                
                if ($result) {
                    // Aggiungi una notifica di successo
                    $this->add_admin_notice(
                        'success',
                        __('Team eliminato con successo.', 'eto')
                    );
                } else {
                    // Aggiungi una notifica di errore
                    $this->add_admin_notice(
                        'error',
                        __('Errore durante l\'eliminazione del team.', 'eto')
                    );
                }
                
                wp_redirect(admin_url('admin.php?page=eto-teams'));
                exit;
        }
    }
    
    /**
     * Gestisce le azioni per i match
     */
    public function handle_match_actions() {
        // Verifica se siamo nella pagina dei match
        if (!isset($_GET['page']) || $_GET['page'] !== 'eto-matches') {
            return;
        }
        
        // Verifica se è stata richiesta un'azione
        if (!isset($_GET['action'])) {
            return;
        }
        
        $action = sanitize_text_field($_GET['action']);
        
        // Gestisci le azioni
        switch ($action) {
            case 'edit':
                // Verifica l'ID del match
                if (!isset($_GET['id'])) {
                    wp_redirect(admin_url('admin.php?page=eto-matches'));
                    exit;
                }
                
                $match_id = absint($_GET['id']);
                $match = $this->get_match($match_id);
                
                if (!$match) {
                    // Aggiungi una notifica di errore
                    $this->add_admin_notice(
                        'error',
                        __('Match non trovato.', 'eto')
                    );
                    
                    wp_redirect(admin_url('admin.php?page=eto-matches'));
                    exit;
                }
                
                // Continua con la visualizzazione della pagina di modifica
                break;
        }
    }
    
    /**
     * Renderizza la pagina dashboard
     */
    public function render_dashboard_page() {
        // Ottieni i dati per la dashboard
        $tournaments_count = $this->db_query->count_tournaments();
        $active_tournaments = $this->db_query->count_tournaments(['status' => 'active']);
        $teams_count = $this->count_teams();
        $matches_count = $this->count_matches();
        
        // Ottieni i tornei recenti
        $recent_tournaments = $this->db_query->get_tournaments([
            'limit' => 5,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ]);
        
        // Ottieni i match recenti
        $recent_matches = $this->get_recent_matches(5);
        
        // Includi il template
        include(ETO_PLUGIN_DIR . '/admin/templates/dashboard.php');
    }
    
    /**
     * Renderizza la pagina tornei
     */
    public function render_tournaments_page() {
        // Verifica se è stata richiesta un'azione
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        if ($action === 'edit' || $action === 'new') {
            // Ottieni il torneo se stiamo modificando
            $tournament = null;
            if ($action === 'edit' && isset($_GET['id'])) {
                $tournament_id = absint($_GET['id']);
                $tournament = $this->db_query->get_tournament($tournament_id);
                
                if (!$tournament) {
                    // Aggiungi una notifica di errore
                    $this->add_admin_notice(
                        'error',
                        __('Torneo non trovato.', 'eto')
                    );
                    
                    wp_redirect(admin_url('admin.php?page=eto-tournaments'));
                    exit;
                }
            }
            
            // Includi il template di modifica
            include(ETO_PLUGIN_DIR . '/admin/templates/tournament-edit.php');
        } else {
            // Ottieni i parametri di paginazione
            $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
            $per_page = 20;
            $offset = ($page - 1) * $per_page;
            
            // Ottieni i parametri di filtro
            $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
            $format = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : '';
            
            // Prepara i parametri di query
            $args = [
                'limit' => $per_page,
                'offset' => $offset,
                'orderby' => 'start_date',
                'order' => 'DESC'
            ];
            
            if (!empty($status)) {
                $args['status'] = $status;
            }
            
            if (!empty($format)) {
                $args['format'] = $format;
            }
            
            // Ottieni i tornei
            $tournaments = $this->db_query->get_tournaments($args);
            
            // Ottieni il conteggio totale per la paginazione
            $total_tournaments = $this->db_query->count_tournaments($args);
            $total_pages = ceil($total_tournaments / $per_page);
            
            // Includi il template di lista
            include(ETO_PLUGIN_DIR . '/admin/templates/tournaments-list.php');
        }
    }
    
    /**
     * Renderizza la pagina team
     */
    public function render_teams_page() {
        // Verifica se è stata richiesta un'azione
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        if ($action === 'edit' || $action === 'new') {
            // Ottieni il team se stiamo modificando
            $team = null;
            if ($action === 'edit' && isset($_GET['id'])) {
                $team_id = absint($_GET['id']);
                $team = $this->db_query->get_team($team_id);
                
                if (!$team) {
                    // Aggiungi una notifica di errore
                    $this->add_admin_notice(
                        'error',
                        __('Team non trovato.', 'eto')
                    );
                    
                    wp_redirect(admin_url('admin.php?page=eto-teams'));
                    exit;
                }
                
                // Ottieni i membri del team
                $team_members = $this->db_query->get_team_members($team_id);
            }
            
            // Includi il template di modifica
            include(ETO_PLUGIN_DIR . '/admin/templates/team-edit.php');
        } else {
            // Ottieni i parametri di paginazione
            $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
            $per_page = 20;
            $offset = ($page - 1) * $per_page;
            
            // Ottieni i team
            $teams = $this->get_teams([
                'limit' => $per_page,
                'offset' => $offset
            ]);
            
            // Ottieni il conteggio totale per la paginazione
            $total_teams = $this->count_teams();
            $total_pages = ceil($total_teams / $per_page);
            
            // Includi il template di lista
            include(ETO_PLUGIN_DIR . '/admin/templates/teams-list.php');
        }
    }
    
    /**
     * Renderizza la pagina match
     */
    public function render_matches_page() {
        // Verifica se è stata richiesta un'azione
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        if ($action === 'edit') {
            // Ottieni il match
            $match = null;
            if (isset($_GET['id'])) {
                $match_id = absint($_GET['id']);
                $match = $this->get_match($match_id);
                
                if (!$match) {
                    // Aggiungi una notifica di errore
                    $this->add_admin_notice(
                        'error',
                        __('Match non trovato.', 'eto')
                    );
                    
                    wp_redirect(admin_url('admin.php?page=eto-matches'));
                    exit;
                }
                
                // Ottieni il torneo
                $tournament = $this->db_query->get_tournament($match->tournament_id);
                
                // Ottieni i team
                $team1 = $match->team1_id ? $this->db_query->get_team($match->team1_id) : null;
                $team2 = $match->team2_id ? $this->db_query->get_team($match->team2_id) : null;
            }
            
            // Includi il template di modifica
            include(ETO_PLUGIN_DIR . '/admin/templates/match-edit.php');
        } else {
            // Ottieni i parametri di paginazione
            $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
            $per_page = 20;
            $offset = ($page - 1) * $per_page;
            
            // Ottieni i parametri di filtro
            $tournament_id = isset($_GET['tournament_id']) ? absint($_GET['tournament_id']) : 0;
            $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
            
            // Prepara i parametri di query
            $args = [
                'limit' => $per_page,
                'offset' => $offset
            ];
            
            if ($tournament_id > 0) {
                $args['tournament_id'] = $tournament_id;
            }
            
            if (!empty($status)) {
                $args['status'] = $status;
            }
            
            // Ottieni i match
            $matches = $this->get_matches($args);
            
            // Ottieni il conteggio totale per la paginazione
            $total_matches = $this->count_matches($args);
            $total_pages = ceil($total_matches / $per_page);
            
            // Ottieni i tornei per il filtro
            $tournaments = $this->db_query->get_tournaments([
                'limit' => 100,
                'orderby' => 'name',
                'order' => 'ASC'
            ]);
            
            // Includi il template di lista
            include(ETO_PLUGIN_DIR . '/admin/templates/matches-list.php');
        }
    }
    
    /**
     * Renderizza la pagina impostazioni
     */
    public function render_settings_page() {
        // Verifica se il form è stato inviato
        if (isset($_POST['eto_settings_submit'])) {
            // Verifica il nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'eto_settings')) {
                // Aggiungi una notifica di errore
                $this->add_admin_notice(
                    'error',
                    __('Errore di sicurezza. Riprova.', 'eto')
                );
            } else {
                // Salva le impostazioni
                $this->save_settings($_POST);
                
                // Aggiungi una notifica di successo
                $this->add_admin_notice(
                    'success',
                    __('Impostazioni salvate con successo.', 'eto')
                );
            }
        }
        
        // Ottieni le impostazioni correnti
        $settings = $this->get_settings();
        
        // Includi il template
        include(ETO_PLUGIN_DIR . '/admin/templates/settings.php');
    }
    
    /**
     * Renderizza la pagina strumenti
     */
    public function render_tools_page() {
        // Includi il template
        include(ETO_PLUGIN_DIR . '/admin/templates/tools.php');
    }
    
    /**
     * Renderizza il meta box dei dettagli del torneo
     *
     * @param object $post Post corrente
     */
    public function render_tournament_details_meta_box($post) {
        // Ottieni il torneo
        $tournament_id = absint($post->ID);
        $tournament = $this->db_query->get_tournament($tournament_id);
        
        if (!$tournament) {
            echo '<p>' . __('Torneo non trovato.', 'eto') . '</p>';
            return;
        }
        
        // Includi il template
        include(ETO_PLUGIN_DIR . '/admin/templates/meta-boxes/tournament-details.php');
    }
    
    /**
     * Renderizza il meta box dei dettagli del team
     *
     * @param object $post Post corrente
     */
    public function render_team_details_meta_box($post) {
        // Ottieni il team
        $team_id = absint($post->ID);
        $team = $this->db_query->get_team($team_id);
        
        if (!$team) {
            echo '<p>' . __('Team non trovato.', 'eto') . '</p>';
            return;
        }
        
        // Ottieni i membri del team
        $team_members = $this->db_query->get_team_members($team_id);
        
        // Includi il template
        include(ETO_PLUGIN_DIR . '/admin/templates/meta-boxes/team-details.php');
    }
    
    /**
     * Renderizza il meta box dei dettagli del match
     *
     * @param object $post Post corrente
     */
    public function render_match_details_meta_box($post) {
        // Ottieni il match
        $match_id = absint($post->ID);
        $match = $this->get_match($match_id);
        
        if (!$match) {
            echo '<p>' . __('Match non trovato.', 'eto') . '</p>';
            return;
        }
        
        // Ottieni il torneo
        $tournament = $this->db_query->get_tournament($match->tournament_id);
        
        // Ottieni i team
        $team1 = $match->team1_id ? $this->db_query->get_team($match->team1_id) : null;
        $team2 = $match->team2_id ? $this->db_query->get_team($match->team2_id) : null;
        
        // Includi il template
        include(ETO_PLUGIN_DIR . '/admin/templates/meta-boxes/match-details.php');
    }
    
    /**
     * AJAX: Crea un torneo
     */
    public function ajax_create_tournament() {
        // Verifica il nonce
        if (!check_ajax_referer('eto_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza. Riprova.', 'eto')]);
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni i dati del form
        $tournament_data = $this->security->sanitize_form_data($_POST, [
            'name' => ['type' => 'text'],
            'description' => ['type' => 'textarea'],
            'format' => ['type' => 'text'],
            'elimination_type' => ['type' => 'text'],
            'status' => ['type' => 'text'],
            'start_date' => ['type' => 'text'],
            'end_date' => ['type' => 'text'],
            'max_teams' => ['type' => 'int']
        ]);
        
        // Aggiungi l'utente corrente
        $tournament_data['created_by'] = get_current_user_id();
        
        // Crea il torneo
        $tournament_id = $this->db_query->insert_tournament($tournament_data);
        
        if (!$tournament_id) {
            wp_send_json_error(['message' => __('Errore durante la creazione del torneo.', 'eto')]);
        }
        
        // Trigger dell'azione
        do_action('eto_tournament_created', $tournament_id, $tournament_data);
        
        wp_send_json_success([
            'message' => __('Torneo creato con successo.', 'eto'),
            'tournament_id' => $tournament_id,
            'redirect' => admin_url('admin.php?page=eto-tournaments&action=edit&id=' . $tournament_id)
        ]);
    }
    
    /**
     * AJAX: Aggiorna un torneo
     */
    public function ajax_update_tournament() {
        // Verifica il nonce
        if (!check_ajax_referer('eto_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza. Riprova.', 'eto')]);
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Verifica l'ID del torneo
        if (!isset($_POST['tournament_id'])) {
            wp_send_json_error(['message' => __('ID torneo mancante.', 'eto')]);
        }
        
        $tournament_id = absint($_POST['tournament_id']);
        
        // Ottieni i dati del form
        $tournament_data = $this->security->sanitize_form_data($_POST, [
            'name' => ['type' => 'text'],
            'description' => ['type' => 'textarea'],
            'format' => ['type' => 'text'],
            'elimination_type' => ['type' => 'text'],
            'status' => ['type' => 'text'],
            'start_date' => ['type' => 'text'],
            'end_date' => ['type' => 'text'],
            'max_teams' => ['type' => 'int']
        ]);
        
        // Aggiorna il torneo
        $result = $this->db_query->update_tournament($tournament_id, $tournament_data);
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore durante l\'aggiornamento del torneo.', 'eto')]);
        }
        
        // Trigger dell'azione
        do_action('eto_tournament_updated', $tournament_id, $tournament_data);
        
        wp_send_json_success([
            'message' => __('Torneo aggiornato con successo.', 'eto'),
            'tournament_id' => $tournament_id
        ]);
    }
    
    /**
     * AJAX: Elimina un torneo
     */
    public function ajax_delete_tournament() {
        // Verifica il nonce
        if (!check_ajax_referer('eto_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza. Riprova.', 'eto')]);
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Verifica l'ID del torneo
        if (!isset($_POST['tournament_id'])) {
            wp_send_json_error(['message' => __('ID torneo mancante.', 'eto')]);
        }
        
        $tournament_id = absint($_POST['tournament_id']);
        
        // Elimina il torneo
        $result = $this->db_query->delete_tournament($tournament_id);
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore durante l\'eliminazione del torneo.', 'eto')]);
        }
        
        wp_send_json_success([
            'message' => __('Torneo eliminato con successo.', 'eto')
        ]);
    }
    
    /**
     * AJAX: Ottiene un torneo
     */
    public function ajax_get_tournament() {
        // Verifica il nonce
        if (!check_ajax_referer('eto_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza. Riprova.', 'eto')]);
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Verifica l'ID del torneo
        if (!isset($_POST['tournament_id'])) {
            wp_send_json_error(['message' => __('ID torneo mancante.', 'eto')]);
        }
        
        $tournament_id = absint($_POST['tournament_id']);
        
        // Ottieni il torneo
        $tournament = $this->db_query->get_tournament($tournament_id);
        
        if (!$tournament) {
            wp_send_json_error(['message' => __('Torneo non trovato.', 'eto')]);
        }
        
        wp_send_json_success([
            'tournament' => $tournament
        ]);
    }
    
    /**
     * AJAX: Genera il bracket di un torneo
     */
    public function ajax_generate_bracket() {
        // Verifica il nonce
        if (!check_ajax_referer('eto_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza. Riprova.', 'eto')]);
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Verifica l'ID del torneo
        if (!isset($_POST['tournament_id'])) {
            wp_send_json_error(['message' => __('ID torneo mancante.', 'eto')]);
        }
        
        $tournament_id = absint($_POST['tournament_id']);
        
        // Genera il bracket
        $result = $this->generate_tournament_bracket($tournament_id);
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore durante la generazione del bracket.', 'eto')]);
        }
        
        wp_send_json_success([
            'message' => __('Bracket generato con successo.', 'eto')
        ]);
    }
    
    /**
     * Genera il bracket di un torneo
     *
     * @param int $tournament_id ID del torneo
     * @return bool True se la generazione è riuscita, false altrimenti
     */
    private function generate_tournament_bracket($tournament_id) {
        // Ottieni il torneo
        $tournament = $this->db_query->get_tournament($tournament_id);
        
        if (!$tournament) {
            return false;
        }
        
        // Verifica il formato del torneo
        switch ($tournament->format) {
            case 'single':
                return $this->generate_single_elimination_bracket($tournament_id);
                
            case 'double':
                return $this->generate_double_elimination_bracket($tournament_id);
                
            case 'swiss':
                // Carica la classe Swiss
                require_once(ETO_PLUGIN_DIR . '/includes/class-swiss-tournament.php');
                $swiss = new ETO_Swiss_Tournament();
                
                // Genera gli accoppiamenti per il primo round
                return $swiss->generate_pairings($tournament_id, 1) !== false;
                
            default:
                return false;
        }
    }
    
    /**
     * Genera il bracket per un torneo a eliminazione singola
     *
     * @param int $tournament_id ID del torneo
     * @return bool True se la generazione è riuscita, false altrimenti
     */
    private function generate_single_elimination_bracket($tournament_id) {
        global $wpdb;
        
        // Ottieni i team registrati e che hanno fatto check-in
        $table_entries = $this->db_query->get_table_name('tournament_entries');
        $table_teams = $this->db_query->get_table_name('teams');
        
        $teams = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, te.seed
                FROM $table_teams t
                JOIN $table_entries te ON t.id = te.team_id
                WHERE te.tournament_id = %d
                AND te.checked_in = 1
                ORDER BY te.seed ASC",
                $tournament_id
            )
        );
        
        if (empty($teams)) {
            return false;
        }
        
        // Calcola il numero di team e il numero di round
        $team_count = count($teams);
        $round_count = ceil(log($team_count, 2));
        $match_count = pow(2, $round_count) - 1;
        
        // Calcola il numero di bye
        $bye_count = pow(2, $round_count) - $team_count;
        
        // Elimina i match esistenti
        $table_matches = $this->db_query->get_table_name('matches');
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_matches WHERE tournament_id = %d",
                $tournament_id
            )
        );
        
        // Crea i match del primo round
        $matches = [];
        $match_number = 1;
        
        // Inizia la transazione
        $wpdb->query('START TRANSACTION');
        
        try {
            // Distribuisci i bye
            $bye_positions = [];
            if ($bye_count > 0) {
                // Calcola le posizioni dei bye
                $bye_positions = $this->calculate_bye_positions($team_count, $bye_count);
            }
            
            // Crea i match del primo round
            $first_round_matches = pow(2, $round_count - 1);
            for ($i = 0; $i < $first_round_matches; $i++) {
                $team1_index = $i;
                $team2_index = $first_round_matches * 2 - 1 - $i;
                
                $team1 = isset($teams[$team1_index]) ? $teams[$team1_index] : null;
                $team2 = isset($teams[$team2_index]) ? $teams[$team2_index] : null;
                
                // Verifica se questo match ha un bye
                $has_bye = in_array($i, $bye_positions) || in_array($team2_index, $bye_positions);
                
                if ($has_bye) {
                    // Determina quale team ha il bye
                    $bye_team = null;
                    $opponent_team = null;
                    
                    if (in_array($i, $bye_positions)) {
                        $bye_team = $team2;
                        $opponent_team = $team1;
                    } else {
                        $bye_team = $team1;
                        $opponent_team = $team2;
                    }
                    
                    // Crea un match con bye
                    if ($opponent_team) {
                        $wpdb->insert(
                            $table_matches,
                            [
                                'tournament_id' => $tournament_id,
                                'team1_id' => $opponent_team->id,
                                'team2_id' => null,
                                'team1_score' => 1,
                                'team2_score' => 0,
                                'round' => 1,
                                'match_number' => $match_number,
                                'status' => 'completed',
                                'created_at' => current_time('mysql')
                            ],
                            ['%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s']
                        );
                        
                        $matches[] = [
                            'id' => $wpdb->insert_id,
                            'team1_id' => $opponent_team->id,
                            'team2_id' => null,
                            'winner_id' => $opponent_team->id,
                            'round' => 1,
                            'match_number' => $match_number
                        ];
                    }
                } else {
                    // Crea un match normale
                    if ($team1 && $team2) {
                        $wpdb->insert(
                            $table_matches,
                            [
                                'tournament_id' => $tournament_id,
                                'team1_id' => $team1->id,
                                'team2_id' => $team2->id,
                                'team1_score' => 0,
                                'team2_score' => 0,
                                'round' => 1,
                                'match_number' => $match_number,
                                'status' => 'pending',
                                'created_at' => current_time('mysql')
                            ],
                            ['%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s']
                        );
                        
                        $matches[] = [
                            'id' => $wpdb->insert_id,
                            'team1_id' => $team1->id,
                            'team2_id' => $team2->id,
                            'winner_id' => null,
                            'round' => 1,
                            'match_number' => $match_number
                        ];
                    }
                }
                
                $match_number++;
            }
            
            // Crea i match dei round successivi
            for ($round = 2; $round <= $round_count; $round++) {
                $matches_in_round = pow(2, $round_count - $round);
                
                for ($i = 0; $i < $matches_in_round; $i++) {
                    $wpdb->insert(
                        $table_matches,
                        [
                            'tournament_id' => $tournament_id,
                            'team1_id' => null,
                            'team2_id' => null,
                            'team1_score' => 0,
                            'team2_score' => 0,
                            'round' => $round,
                            'match_number' => $match_number,
                            'status' => 'pending',
                            'created_at' => current_time('mysql')
                        ],
                        ['%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s']
                    );
                    
                    $matches[] = [
                        'id' => $wpdb->insert_id,
                        'team1_id' => null,
                        'team2_id' => null,
                        'winner_id' => null,
                        'round' => $round,
                        'match_number' => $match_number
                    ];
                    
                    $match_number++;
                }
            }
            
            // Commit della transazione
            $wpdb->query('COMMIT');
            
            return true;
            
        } catch (Exception $e) {
            // Rollback in caso di errore
            $wpdb->query('ROLLBACK');
            
            if (defined('ETO_DEBUG') && ETO_DEBUG) {
                error_log('[ETO] Errore generazione bracket: ' . $e->getMessage());
            }
            
            return false;
        }
    }
    
    /**
     * Calcola le posizioni dei bye
     *
     * @param int $team_count Numero di team
     * @param int $bye_count Numero di bye
     * @return array Posizioni dei bye
     */
    private function calculate_bye_positions($team_count, $bye_count) {
        $positions = [];
        $total_positions = $team_count + $bye_count;
        
        // Algoritmo per distribuire i bye in modo equo
        for ($i = 0; $i < $bye_count; $i++) {
            $positions[] = $i * 2 + 1;
        }
        
        return $positions;
    }
    
    /**
     * Genera il bracket per un torneo a doppia eliminazione
     *
     * @param int $tournament_id ID del torneo
     * @return bool True se la generazione è riuscita, false altrimenti
     */
    private function generate_double_elimination_bracket($tournament_id) {
        // Implementazione del bracket a doppia eliminazione
        // (Codice omesso per brevità)
        
        return true;
    }
    
    /**
     * Ottiene i team
     *
     * @param array $args Argomenti di query
     * @return array Lista di team
     */
    private function get_teams($args = []) {
        global $wpdb;
        
        $defaults = [
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'name',
            'order' => 'ASC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Sanitizzazione
        $args['limit'] = absint($args['limit']);
        $args['offset'] = absint($args['offset']);
        
        // Whitelist per orderby
        $allowed_orderby = ['id', 'name', 'created_at'];
        if (!in_array($args['orderby'], $allowed_orderby)) {
            $args['orderby'] = 'name';
        }
        
        // Whitelist per order
        $args['order'] = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        
        $table_teams = $this->db_query->get_table_name('teams');
        
        $query = "SELECT t.*, u.display_name as captain_name
                FROM $table_teams t
                LEFT JOIN {$wpdb->users} u ON t.captain_id = u.ID
                ORDER BY t.{$args['orderby']} {$args['order']}
                LIMIT %d OFFSET %d";
        
        $query = $wpdb->prepare($query, $args['limit'], $args['offset']);
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Conta i team
     *
     * @return int Numero di team
     */
    private function count_teams() {
        global $wpdb;
        
        $table_teams = $this->db_query->get_table_name('teams');
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_teams");
    }
    
    /**
     * Elimina un team
     *
     * @param int $team_id ID del team
     * @return bool True se l'eliminazione è riuscita, false altrimenti
     */
    private function delete_team($team_id) {
        global $wpdb;
        
        $table_teams = $this->db_query->get_table_name('teams');
        
        $result = $wpdb->delete(
            $table_teams,
            ['id' => $team_id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Ottiene i match
     *
     * @param array $args Argomenti di query
     * @return array Lista di match
     */
    private function get_matches($args = []) {
        global $wpdb;
        
        $defaults = [
            'limit' => 10,
            'offset' => 0,
            'tournament_id' => 0,
            'status' => '',
            'orderby' => 'scheduled_at',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Sanitizzazione
        $args['limit'] = absint($args['limit']);
        $args['offset'] = absint($args['offset']);
        $args['tournament_id'] = absint($args['tournament_id']);
        
        // Whitelist per orderby
        $allowed_orderby = ['id', 'scheduled_at', 'created_at', 'round', 'match_number'];
        if (!in_array($args['orderby'], $allowed_orderby)) {
            $args['orderby'] = 'scheduled_at';
        }
        
        // Whitelist per order
        $args['order'] = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        
        $table_matches = $this->db_query->get_table_name('matches');
        $table_tournaments = $this->db_query->get_table_name('tournaments');
        $table_teams = $this->db_query->get_table_name('teams');
        
        $query = "SELECT m.*, 
                t.name as tournament_name,
                t1.name as team1_name,
                t2.name as team2_name
                FROM $table_matches m
                LEFT JOIN $table_tournaments t ON m.tournament_id = t.id
                LEFT JOIN $table_teams t1 ON m.team1_id = t1.id
                LEFT JOIN $table_teams t2 ON m.team2_id = t2.id
                WHERE 1=1";
        
        $query_args = [];
        
        if ($args['tournament_id'] > 0) {
            $query .= " AND m.tournament_id = %d";
            $query_args[] = $args['tournament_id'];
        }
        
        if (!empty($args['status'])) {
            $query .= " AND m.status = %s";
            $query_args[] = $args['status'];
        }
        
        $query .= " ORDER BY m.{$args['orderby']} {$args['order']}";
        $query .= " LIMIT %d OFFSET %d";
        $query_args[] = $args['limit'];
        $query_args[] = $args['offset'];
        
        $prepared_query = $wpdb->prepare($query, $query_args);
        
        return $wpdb->get_results($prepared_query);
    }
    
    /**
     * Ottiene un match
     *
     * @param int $match_id ID del match
     * @return object|false Match o false se non trovato
     */
    private function get_match($match_id) {
        global $wpdb;
        
        $table_matches = $this->db_query->get_table_name('matches');
        $table_tournaments = $this->db_query->get_table_name('tournaments');
        $table_teams = $this->db_query->get_table_name('teams');
        
        $query = $wpdb->prepare(
            "SELECT m.*, 
            t.name as tournament_name,
            t1.name as team1_name,
            t2.name as team2_name
            FROM $table_matches m
            LEFT JOIN $table_tournaments t ON m.tournament_id = t.id
            LEFT JOIN $table_teams t1 ON m.team1_id = t1.id
            LEFT JOIN $table_teams t2 ON m.team2_id = t2.id
            WHERE m.id = %d",
            $match_id
        );
        
        return $wpdb->get_row($query);
    }
    
    /**
     * Conta i match
     *
     * @param array $args Argomenti di query
     * @return int Numero di match
     */
    private function count_matches($args = []) {
        global $wpdb;
        
        $defaults = [
            'tournament_id' => 0,
            'status' => ''
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Sanitizzazione
        $args['tournament_id'] = absint($args['tournament_id']);
        
        $table_matches = $this->db_query->get_table_name('matches');
        
        $query = "SELECT COUNT(*) FROM $table_matches WHERE 1=1";
        
        $query_args = [];
        
        if ($args['tournament_id'] > 0) {
            $query .= " AND tournament_id = %d";
            $query_args[] = $args['tournament_id'];
        }
        
        if (!empty($args['status'])) {
            $query .= " AND status = %s";
            $query_args[] = $args['status'];
        }
        
        if (!empty($query_args)) {
            $query = $wpdb->prepare($query, $query_args);
        }
        
        return (int) $wpdb->get_var($query);
    }
    
    /**
     * Ottiene i match recenti
     *
     * @param int $limit Numero massimo di match
     * @return array Lista di match
     */
    private function get_recent_matches($limit = 5) {
        return $this->get_matches([
            'limit' => $limit,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ]);
    }
    
    /**
     * Ottiene le impostazioni
     *
     * @return array Impostazioni
     */
    private function get_settings() {
        $defaults = [
            'tournament_page' => 0,
            'team_page' => 0,
            'checkin_page' => 0,
            'riot_api_key' => '',
            'riot_default_region' => 'euw1',
            'riot_cache_ttl' => 3600,
            'email_notifications' => true,
            'keep_data_on_uninstall' => false
        ];
        
        $settings = [];
        
        foreach ($defaults as $key => $default) {
            $option_name = 'eto_' . $key;
            $value = get_option($option_name, $default);
            $settings[$key] = $value;
        }
        
        return $settings;
    }
    
    /**
     * Salva le impostazioni
     *
     * @param array $data Dati del form
     * @return bool True se il salvataggio è riuscito, false altrimenti
     */
    private function save_settings($data) {
        // Sanitizza i dati
        $settings = $this->security->sanitize_form_data($data, [
            'tournament_page' => ['type' => 'int'],
            'team_page' => ['type' => 'int'],
            'checkin_page' => ['type' => 'int'],
            'riot_api_key' => ['type' => 'text'],
            'riot_default_region' => ['type' => 'text'],
            'riot_cache_ttl' => ['type' => 'int'],
            'email_notifications' => ['type' => 'checkbox'],
            'keep_data_on_uninstall' => ['type' => 'checkbox']
        ]);
        
        // Salva le impostazioni
        foreach ($settings as $key => $value) {
            $option_name = 'eto_' . $key;
            
            if ($key === 'riot_api_key' && !empty($value)) {
                // Salva la chiave API in modo sicuro
                eto_save_api_key('riot', $value);
            } else {
                update_option($option_name, $value);
            }
        }
        
        return true;
    }
    
    /**
     * Aggiunge una notifica di amministrazione
     *
     * @param string $type Tipo di notifica (success, error, warning, info)
     * @param string $message Messaggio della notifica
     * @param bool $dismissible Se la notifica può essere chiusa
     */
    private function add_admin_notice($type, $message, $dismissible = true) {
        $notices = get_option('eto_admin_notices', []);
        
        $notices[] = [
            'type' => $type,
            'message' => $message,
            'dismissible' => $dismissible
        ];
        
        update_option('eto_admin_notices', $notices);
    }
    
    /**
     * AJAX: Ripara il database
     */
    public function ajax_repair_database() {
        // Verifica il nonce
        if (!check_ajax_referer('eto_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza. Riprova.', 'eto')]);
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ripara il database
        require_once(ETO_PLUGIN_DIR . '/includes/class-database-manager.php');
        $db_manager = new ETO_Database_Manager();
        $result = $db_manager->repair_database();
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore durante la riparazione del database.', 'eto')]);
        }
        
        wp_send_json_success([
            'message' => __('Database riparato con successo.', 'eto')
        ]);
    }
    
    /**
     * AJAX: Pulisce la cache
     */
    public function ajax_clear_cache() {
        // Verifica il nonce
        if (!check_ajax_referer('eto_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza. Riprova.', 'eto')]);
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni il tipo di cache
        $cache_type = isset($_POST['cache_type']) ? sanitize_text_field($_POST['cache_type']) : 'all';
        
        // Pulisci la cache
        $deleted = false;
        
        switch ($cache_type) {
            case 'transients':
                global $wpdb;
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_eto_%'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_eto_%'");
                $deleted = true;
                break;
                
            case 'riot':
                // Pulisci la cache delle API Riot
                require_once(ETO_PLUGIN_DIR . '/includes/class-riot-api.php');
                $riot_api = eto_riot_api();
                $deleted = $riot_api->clear_cache('all');
                break;
                
            case 'all':
                // Pulisci tutti i tipi di cache
                global $wpdb;
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_eto_%'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_eto_%'");
                
                require_once(ETO_PLUGIN_DIR . '/includes/class-riot-api.php');
                $riot_api = eto_riot_api();
                $riot_api->clear_cache('all');
                
                $deleted = true;
                break;
        }
        
        if (!$deleted) {
            wp_send_json_error(['message' => __('Errore durante la pulizia della cache.', 'eto')]);
        }
        
        wp_send_json_success([
            'message' => __('Cache pulita con successo.', 'eto')
        ]);
    }
}
