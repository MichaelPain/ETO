<?php
/**
 * Classe per la gestione del check-in dei partecipanti
 * 
 * Implementa la logica per il check-in dei team ai tornei
 * 
 * @package ETO
 * @since 2.5.0
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Checkin {
    
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
        // Registra gli endpoint AJAX
        add_action('wp_ajax_eto_checkin_team', array($this, 'ajax_checkin_team'));
        add_action('wp_ajax_eto_checkin_individual', array($this, 'ajax_checkin_individual'));
        
        // Aggiungi shortcode per il form di check-in
        add_shortcode('eto_checkin_form', array($this, 'shortcode_checkin_form'));
    }
    
    /**
     * Gestisce il check-in di un team via AJAX
     */
    public function ajax_checkin_team() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-checkin-nonce')) {
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
        
        // Verifica che il team sia registrato al torneo
        $tournament = new ETO_Tournament_Model($tournament_id);
        if (!$tournament || !$tournament->is_team_registered($team_id)) {
            wp_send_json_error(array('message' => __('Il team non è registrato a questo torneo', 'eto')));
        }
        
        // Verifica che il check-in sia aperto
        if (!$this->is_checkin_open($tournament_id)) {
            wp_send_json_error(array('message' => __('Il check-in non è aperto', 'eto')));
        }
        
        // Esegui il check-in
        $result = $this->checkin_team($tournament_id, $team_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Check-in completato con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante il check-in', 'eto')));
        }
    }
    
    /**
     * Gestisce il check-in di un partecipante individuale via AJAX
     */
    public function ajax_checkin_individual() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-checkin-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i parametri
        if (!isset($_POST['tournament_id'])) {
            wp_send_json_error(array('message' => __('Parametri mancanti', 'eto')));
        }
        
        $tournament_id = intval($_POST['tournament_id']);
        $user_id = get_current_user_id();
        
        // Verifica che l'utente sia registrato al torneo
        $tournament = new ETO_Tournament_Model($tournament_id);
        if (!$tournament || !$this->is_user_registered($tournament_id, $user_id)) {
            wp_send_json_error(array('message' => __('Non sei registrato a questo torneo', 'eto')));
        }
        
        // Verifica che il check-in sia aperto
        if (!$this->is_checkin_open($tournament_id)) {
            wp_send_json_error(array('message' => __('Il check-in non è aperto', 'eto')));
        }
        
        // Esegui il check-in
        $result = $this->checkin_individual($tournament_id, $user_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Check-in completato con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante il check-in', 'eto')));
        }
    }
    
    /**
     * Shortcode per il form di check-in
     * 
     * @param array $atts Attributi dello shortcode
     * @return string HTML generato
     */
    public function shortcode_checkin_form($atts) {
        $atts = shortcode_atts(array(
            'tournament_id' => 0
        ), $atts, 'eto_checkin_form');
        
        if (empty($atts['tournament_id'])) {
            return '<p>' . __('ID torneo non specificato', 'eto') . '</p>';
        }
        
        // Ottieni il torneo
        $tournament = new ETO_Tournament_Model($atts['tournament_id']);
        
        if (!$tournament) {
            return '<p>' . __('Torneo non trovato', 'eto') . '</p>';
        }
        
        // Verifica se il check-in è aperto
        $checkin_open = $this->is_checkin_open($atts['tournament_id']);
        
        if (!$checkin_open) {
            return '<p>' . __('Il check-in non è aperto', 'eto') . '</p>';
        }
        
        // Verifica se è un torneo individuale
        $is_individual = $tournament->get_meta('is_individual', false);
        
        // Carica il template appropriato
        ob_start();
        if ($is_individual) {
            include ETO_PLUGIN_DIR . 'templates/frontend/tournaments/checkin-individual.php';
        } else {
            include ETO_PLUGIN_DIR . 'templates/frontend/tournaments/checkin-team.php';
        }
        return ob_get_clean();
    }
    
    /**
     * Verifica se il check-in è aperto per un torneo
     * 
     * @param int $tournament_id ID del torneo
     * @return bool True se il check-in è aperto
     */
    public function is_checkin_open($tournament_id) {
        $tournament = new ETO_Tournament_Model($tournament_id);
        
        if (!$tournament) {
            return false;
        }
        
        $checkin_start = $tournament->get_meta('checkin_start', '');
        $checkin_end = $tournament->get_meta('checkin_end', '');
        $now = current_time('mysql');
        
        if (empty($checkin_start) || empty($checkin_end)) {
            return false;
        }
        
        return ($now >= $checkin_start && $now <= $checkin_end);
    }
    
    /**
     * Esegue il check-in di un team
     * 
     * @param int $tournament_id ID del torneo
     * @param int $team_id ID del team
     * @return bool True se il check-in è riuscito
     */
    public function checkin_team($tournament_id, $team_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'eto_tournament_entries';
        
        return $wpdb->update(
            $table,
            array('checked_in' => 1),
            array('tournament_id' => $tournament_id, 'team_id' => $team_id),
            array('%d'),
            array('%d', '%d')
        ) !== false;
    }
    
    /**
     * Esegue il check-in di un partecipante individuale
     * 
     * @param int $tournament_id ID del torneo
     * @param int $user_id ID dell'utente
     * @return bool True se il check-in è riuscito
     */
    public function checkin_individual($tournament_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'eto_individual_participants';
        
        return $wpdb->update(
            $table,
            array('checked_in' => 1),
            array('tournament_id' => $tournament_id, 'user_id' => $user_id),
            array('%d'),
            array('%d', '%d')
        ) !== false;
    }
    
    /**
     * Verifica se un utente è registrato a un torneo individuale
     * 
     * @param int $tournament_id ID del torneo
     * @param int $user_id ID dell'utente
     * @return bool True se l'utente è registrato
     */
    private function is_user_registered($tournament_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'eto_individual_participants';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE tournament_id = %d AND user_id = %d",
            $tournament_id,
            $user_id
        ));
        
        return $result > 0;
    }
}

// Inizializza la classe
$eto_checkin = new ETO_Checkin();
