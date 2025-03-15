<?php
/**
 * Classe per la gestione dell'amministrazione
 * 
 * Gestisce tutte le funzionalità del pannello di amministrazione
 * 
 * @package ETO
 * @since 2.5.3
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
     * Costruttore
     */
    public function __construct() {
        $this->db_query = new ETO_DB_Query();
        // Rimuoviamo il riferimento a eto_security() che non esiste
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
    }
    
    /**
     * Registra le pagine di amministrazione
     */
    public function register_admin_menu() {
        // Pagina principale
        add_menu_page(
            __('ETO - Gestione Tornei', 'eto'),
            __('ETO Tornei', 'eto'),
            'manage_options',
            'eto-tournaments',
            [$this, 'render_tournaments_page'],
            'dashicons-awards',
            30
        );
        
        // Sottopagine
        add_submenu_page(
            'eto-tournaments',
            __('Tornei', 'eto'),
            __('Tornei', 'eto'),
            'manage_options',
            'eto-tournaments',
            [$this, 'render_tournaments_page']
        );
        
        add_submenu_page(
            'eto-tournaments',
            __('Team', 'eto'),
            __('Team', 'eto'),
            'manage_options',
            'eto-teams',
            [$this, 'render_teams_page']
        );
        
        add_submenu_page(
            'eto-tournaments',
            __('Partecipanti', 'eto'),
            __('Partecipanti', 'eto'),
            'manage_options',
            'eto-participants',
            [$this, 'render_participants_page']
        );
        
        add_submenu_page(
            'eto-tournaments',
            __('Impostazioni', 'eto'),
            __('Impostazioni', 'eto'),
            'manage_options',
            'eto-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Registra gli script e gli stili per l'amministrazione
     */
    public function enqueue_admin_assets($hook) {
        // Verifica se siamo in una pagina del plugin
        if (strpos($hook, 'eto-') === false) {
            return;
        }
        
        // Stili
        wp_enqueue_style('eto-admin', plugin_dir_url(dirname(__FILE__)) . 'admin/css/admin.css', [], ETO_VERSION);
        
        // Script
        wp_enqueue_script('eto-admin', plugin_dir_url(dirname(__FILE__)) . 'admin/js/admin.js', ['jquery'], ETO_VERSION, true);
        
        // Localizzazione
        wp_localize_script('eto-admin', 'etoAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eto-admin-nonce'),
            'i18n' => [
                'confirmDelete' => __('Sei sicuro di voler eliminare questo elemento?', 'eto'),
                'confirmReset' => __('Sei sicuro di voler resettare questo torneo? Tutti i dati saranno persi.', 'eto'),
                'confirmStart' => __('Sei sicuro di voler avviare questo torneo? Non sarà più possibile aggiungere partecipanti.', 'eto')
            ]
        ]);
    }
    
    /**
     * Registra gli handler AJAX
     */
    public function register_ajax_handlers() {
        // Tornei
        add_action('wp_ajax_eto_create_tournament', [$this, 'ajax_create_tournament']);
        add_action('wp_ajax_eto_update_tournament', [$this, 'ajax_update_tournament']);
        add_action('wp_ajax_eto_delete_tournament', [$this, 'ajax_delete_tournament']);
        add_action('wp_ajax_eto_start_tournament', [$this, 'ajax_start_tournament']);
        add_action('wp_ajax_eto_reset_tournament', [$this, 'ajax_reset_tournament']);
        
        // Team
        add_action('wp_ajax_eto_create_team', [$this, 'ajax_create_team']);
        add_action('wp_ajax_eto_update_team', [$this, 'ajax_update_team']);
        add_action('wp_ajax_eto_delete_team', [$this, 'ajax_delete_team']);
        
        // Partecipanti
        add_action('wp_ajax_eto_add_participant', [$this, 'ajax_add_participant']);
        add_action('wp_ajax_eto_remove_participant', [$this, 'ajax_remove_participant']);
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
            'eto-tournament',
            'normal',
            'high'
        );
        
        add_meta_box(
            'eto-tournament-participants',
            __('Partecipanti', 'eto'),
            [$this, 'render_tournament_participants_meta_box'],
            'eto-tournament',
            'normal',
            'default'
        );
        
        add_meta_box(
            'eto-tournament-matches',
            __('Partite', 'eto'),
            [$this, 'render_tournament_matches_meta_box'],
            'eto-tournament',
            'normal',
            'default'
        );
    }
    
    /**
     * Mostra le notifiche di amministrazione
     */
    public function display_admin_notices() {
        // Implementazione delle notifiche
    }
    
    /**
     * Gestisce le azioni per i tornei
     */
    public function handle_tournament_actions() {
        // Implementazione delle azioni
    }
    
    /**
     * Renderizza la pagina dei tornei
     */
    public function render_tournaments_page() {
        include(plugin_dir_path(dirname(__FILE__)) . 'admin/views/tournaments/list.php');
    }
    
    /**
     * Renderizza la pagina dei team
     */
    public function render_teams_page() {
        include(plugin_dir_path(dirname(__FILE__)) . 'admin/views/teams/list.php');
    }
    
    /**
     * Renderizza la pagina dei partecipanti
     */
    public function render_participants_page() {
        // Verifica se esiste il file dei partecipanti, altrimenti usa un template vuoto
        $participants_file = plugin_dir_path(dirname(__FILE__)) . 'admin/views/participants/list.php';
        if (file_exists($participants_file)) {
            include($participants_file);
        } else {
            echo '<div class="wrap"><h1>' . __('Partecipanti', 'eto') . '</h1>';
            echo '<div class="notice notice-info"><p>' . __('Funzionalità in fase di sviluppo.', 'eto') . '</p></div></div>';
        }
    }
    
    /**
     * Renderizza la pagina delle impostazioni
     */
    public function render_settings_page() {
        // Verifica se esiste il file delle impostazioni, altrimenti usa un template vuoto
        $settings_file = plugin_dir_path(dirname(__FILE__)) . 'admin/views/settings/list.php';
        if (file_exists($settings_file)) {
            include($settings_file);
        } else {
            echo '<div class="wrap"><h1>' . __('Impostazioni', 'eto') . '</h1>';
            echo '<div class="notice notice-info"><p>' . __('Funzionalità in fase di sviluppo.', 'eto') . '</p></div></div>';
        }
    }
    
    /**
     * Renderizza il meta box dei dettagli del torneo
     */
    public function render_tournament_details_meta_box($post) {
        // Implementazione del meta box
    }
    
    /**
     * Renderizza il meta box dei partecipanti del torneo
     */
    public function render_tournament_participants_meta_box($post) {
        // Implementazione del meta box
    }
    
    /**
     * Renderizza il meta box delle partite del torneo
     */
    public function render_tournament_matches_meta_box($post) {
        // Implementazione del meta box
    }
    
    /**
     * Handler AJAX per la creazione di un torneo
     */
    public function ajax_create_tournament() {
        // Implementazione dell'handler
    }
    
    /**
     * Handler AJAX per l'aggiornamento di un torneo
     */
    public function ajax_update_tournament() {
        // Implementazione dell'handler
    }
    
    /**
     * Handler AJAX per l'eliminazione di un torneo
     */
    public function ajax_delete_tournament() {
        // Implementazione dell'handler
    }
    
    /**
     * Handler AJAX per l'avvio di un torneo
     */
    public function ajax_start_tournament() {
        // Implementazione dell'handler
    }
    
    /**
     * Handler AJAX per il reset di un torneo
     */
    public function ajax_reset_tournament() {
        // Implementazione dell'handler
    }
    
    /**
     * Handler AJAX per la creazione di un team
     */
    public function ajax_create_team() {
        // Implementazione dell'handler
    }
    
    /**
     * Handler AJAX per l'aggiornamento di un team
     */
    public function ajax_update_team() {
        // Implementazione dell'handler
    }
    
    /**
     * Handler AJAX per l'eliminazione di un team
     */
    public function ajax_delete_team() {
        // Implementazione dell'handler
    }
    
    /**
     * Handler AJAX per l'aggiunta di un partecipante
     */
    public function ajax_add_participant() {
        // Implementazione dell'handler
    }
    
    /**
     * Handler AJAX per la rimozione di un partecipante
     */
    public function ajax_remove_participant() {
        // Implementazione dell'handler
    }
}
