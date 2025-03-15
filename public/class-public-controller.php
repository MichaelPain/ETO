<?php
/**
 * Controller pubblico del plugin
 * 
 * Gestisce le funzionalità frontend del plugin
 * 
 * @package ETO
 * @since 2.5.0
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Public_Controller {
    
    /**
     * Istanza della classe di sicurezza
     *
     * @var ETO_Security
     */
    private $security;
    
    /**
     * Istanza della classe di query al database
     *
     * @var ETO_DB_Query
     */
    private $db_query;
    
    /**
     * Costruttore
     */
    public function __construct() {
        $this->db_query = new ETO_DB_Query();
        
        // Inizializza le azioni e i filtri
        $this->init_hooks();
    }
    
    /**
     * Inizializza le azioni e i filtri
     */
    private function init_hooks() {
        // Registra gli script e gli stili
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        
        // Registra gli endpoint AJAX
        add_action('wp_ajax_eto_register_team', array($this, 'ajax_register_team'));
        add_action('wp_ajax_eto_join_team', array($this, 'ajax_join_team'));
        add_action('wp_ajax_eto_leave_team', array($this, 'ajax_leave_team'));
        add_action('wp_ajax_eto_report_match_result', array($this, 'ajax_report_match_result'));
        
        // Aggiungi i filtri per i template
        add_filter('template_include', array($this, 'template_loader'));
    }
    
    /**
     * Registra gli script e gli stili
     */
    public function register_scripts() {
        // Stili
        wp_enqueue_style('eto-public', ETO_PLUGIN_URL . 'public/css/public.css', array(), ETO_VERSION);
        
        // Script
        wp_enqueue_script('eto-public', ETO_PLUGIN_URL . 'public/js/public.js', array('jquery'), ETO_VERSION, true);
        
        // Localizzazione
        wp_localize_script('eto-public', 'etoPublic', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eto-public-nonce'),
            'i18n' => array(
                'confirmDelete' => __('Sei sicuro di voler eliminare questo elemento?', 'eto'),
                'confirmLeave' => __('Sei sicuro di voler lasciare questo team?', 'eto'),
                'confirmJoin' => __('Sei sicuro di voler entrare in questo team?', 'eto'),
                'confirmRegister' => __('Sei sicuro di voler registrare questo team al torneo?', 'eto')
            )
        ));
    }
    
    /**
     * Carica i template personalizzati
     *
     * @param string $template Percorso del template
     * @return string Percorso del template modificato
     */
    public function template_loader($template) {
        global $wp_query;
        
        // Verifica se è una pagina di torneo
        if (isset($wp_query->query_vars['post_type']) && $wp_query->query_vars['post_type'] === 'eto-tournament') {
            if (is_single()) {
                $template = ETO_PLUGIN_DIR . 'templates/single-tournament.php';
            } elseif (is_archive()) {
                $template = ETO_PLUGIN_DIR . 'templates/archive-tournament.php';
            }
        }
        
        // Verifica se è una pagina di team
        if (isset($wp_query->query_vars['post_type']) && $wp_query->query_vars['post_type'] === 'eto-team') {
            if (is_single()) {
                $template = ETO_PLUGIN_DIR . 'templates/single-team.php';
            } elseif (is_archive()) {
                $template = ETO_PLUGIN_DIR . 'templates/archive-team.php';
            }
        }
        
        // Verifica se è una pagina di match
        if (isset($wp_query->query_vars['post_type']) && $wp_query->query_vars['post_type'] === 'eto-match') {
            if (is_single()) {
                $template = ETO_PLUGIN_DIR . 'templates/single-match.php';
            } elseif (is_archive()) {
                $template = ETO_PLUGIN_DIR . 'templates/archive-match.php';
            }
        }
        
        return $template;
    }
    
    /**
     * Gestisce la registrazione di un team via AJAX
     */
    public function ajax_register_team() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-public-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i parametri
        if (!isset($_POST['tournament_id']) || !isset($_POST['team_id'])) {
            wp_send_json_error(array('message' => __('Parametri mancanti', 'eto')));
        }
        
        $tournament_id = intval($_POST['tournament_id']);
        $team_id = intval($_POST['team_id']);
        $user_id = get_current_user_id();
        
        // Verifica che l'utente sia il capitano del team
        $team = new ETO_Team_Model($team_id);
        if (!$team || $team->get('captain_id') != $user_id) {
            wp_send_json_error(array('message' => __('Non sei il capitano di questo team', 'eto')));
        }
        
        // Verifica che il torneo esista e sia aperto alle registrazioni
        $tournament = new ETO_Tournament_Model($tournament_id);
        if (!$tournament) {
            wp_send_json_error(array('message' => __('Torneo non trovato', 'eto')));
        }
        
        // Verifica che il team non sia già registrato
        if ($tournament->is_team_registered($team_id)) {
            wp_send_json_error(array('message' => __('Il team è già registrato a questo torneo', 'eto')));
        }
        
        // Registra il team al torneo
        $result = $tournament->register_team($team_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Team registrato con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante la registrazione del team', 'eto')));
        }
    }
    
    /**
     * Gestisce l'entrata in un team via AJAX
     */
    public function ajax_join_team() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-public-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i parametri
        if (!isset($_POST['team_id'])) {
            wp_send_json_error(array('message' => __('Parametri mancanti', 'eto')));
        }
        
        $team_id = intval($_POST['team_id']);
        $user_id = get_current_user_id();
        
        // Verifica che il team esista
        $team = new ETO_Team_Model($team_id);
        if (!$team) {
            wp_send_json_error(array('message' => __('Team non trovato', 'eto')));
        }
        
        // Verifica che l'utente non sia già nel team
        if ($team->is_member($user_id)) {
            wp_send_json_error(array('message' => __('Sei già un membro di questo team', 'eto')));
        }
        
        // Aggiungi l'utente al team
        $result = $team->add_member($user_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Sei entrato nel team con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante l\'entrata nel team', 'eto')));
        }
    }
    
    /**
     * Gestisce l'uscita da un team via AJAX
     */
    public function ajax_leave_team() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-public-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i parametri
        if (!isset($_POST['team_id'])) {
            wp_send_json_error(array('message' => __('Parametri mancanti', 'eto')));
        }
        
        $team_id = intval($_POST['team_id']);
        $user_id = get_current_user_id();
        
        // Verifica che il team esista
        $team = new ETO_Team_Model($team_id);
        if (!$team) {
            wp_send_json_error(array('message' => __('Team non trovato', 'eto')));
        }
        
        // Verifica che l'utente sia nel team
        if (!$team->is_member($user_id)) {
            wp_send_json_error(array('message' => __('Non sei un membro di questo team', 'eto')));
        }
        
        // Verifica che l'utente non sia il capitano
        if ($team->get('captain_id') == $user_id) {
            wp_send_json_error(array('message' => __('Il capitano non può lasciare il team', 'eto')));
        }
        
        // Verifica che il team non stia partecipando a un torneo attivo
        if ($team->is_participating_in_tournament()) {
            wp_send_json_error(array('message' => __('Non puoi lasciare il team mentre partecipa a un torneo', 'eto')));
        }
        
        // Rimuovi l'utente dal team
        $result = $team->remove_member($user_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Hai lasciato il team con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante l\'uscita dal team', 'eto')));
        }
    }
    
    /**
     * Gestisce il report di un risultato di match via AJAX
     */
    public function ajax_report_match_result() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-public-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i parametri
        if (!isset($_POST['match_id']) || !isset($_POST['team1_score']) || !isset($_POST['team2_score'])) {
            wp_send_json_error(array('message' => __('Parametri mancanti', 'eto')));
        }
        
        $match_id = intval($_POST['match_id']);
        $team1_score = intval($_POST['team1_score']);
        $team2_score = intval($_POST['team2_score']);
        $user_id = get_current_user_id();
        
        // Verifica che il match esista
        $match = new ETO_Match_Model($match_id);
        if (!$match) {
            wp_send_json_error(array('message' => __('Match non trovato', 'eto')));
        }
        
        // Verifica che l'utente sia il capitano di uno dei team
        $team1_id = $match->get('team1_id');
        $team2_id = $match->get('team2_id');
        
        $team1 = $team1_id ? new ETO_Team_Model($team1_id) : null;
        $team2 = $team2_id ? new ETO_Team_Model($team2_id) : null;
        
        $is_team1_captain = $team1 && $team1->get('captain_id') == $user_id;
        $is_team2_captain = $team2 && $team2->get('captain_id') == $user_id;
        
        if (!$is_team1_captain && !$is_team2_captain && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Non hai i permessi per riportare questo risultato', 'eto')));
        }
        
        // Aggiorna il risultato del match
        $result = $match->set_result($team1_score, $team2_score);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Risultato riportato con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante il report del risultato', 'eto')));
        }
    }
}
