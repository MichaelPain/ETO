<?php
/**
 * Classe per la gestione degli shortcode
 * 
 * Registra e gestisce tutti gli shortcode disponibili nel plugin
 * 
 * @package ETO
 * @since 2.5.3
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Shortcodes {
    
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
     * Registra tutti gli shortcode
     */
    public function register() {
        // Profilo utente
        add_shortcode('eto_profile', [$this, 'profile_shortcode']);
        
        // Creazione team
        add_shortcode('eto_create_team', [$this, 'create_team_shortcode']);
        
        // Lista tornei
        add_shortcode('eto_tournament_list', [$this, 'tournament_list_shortcode']);
        
        // Visualizzazione torneo
        add_shortcode('eto_tournament_view', [$this, 'tournament_view_shortcode']);
        
        // Check-in torneo
        add_shortcode('eto_checkin', [$this, 'checkin_shortcode']);
        
        // Leaderboard
        add_shortcode('eto_leaderboard', [$this, 'leaderboard_shortcode']);
        
        // Membri team
        add_shortcode('eto_team_members', [$this, 'team_members_shortcode']);
        
        // Aggiungi script e stili necessari
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Registra script e stili
     */
    public function enqueue_scripts() {
        // Stili principali
        wp_enqueue_style(
            'eto-public-css',
            plugins_url('/public/css/eto-public.css', dirname(__FILE__)),
            [],
            ETO_VERSION
        );
        
        // Script principali
        wp_enqueue_script(
            'eto-public-js',
            plugins_url('/public/js/eto-public.js', dirname(__FILE__)),
            ['jquery'],
            ETO_VERSION,
            true
        );
        
        // Localizzazione script
        wp_localize_script('eto-public-js', 'etoData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eto_public_nonce'),
            'i18n' => [
                'confirmDelete' => __('Sei sicuro di voler eliminare questo elemento?', 'eto'),
                'loading' => __('Caricamento in corso...', 'eto'),
                'error' => __('Si è verificato un errore. Riprova più tardi.', 'eto')
            ]
        ]);
    }
    
    /**
     * Shortcode per il profilo utente
     *
     * @param array $atts Attributi dello shortcode
     * @return string Output HTML
     */
    public function profile_shortcode($atts) {
        // Verifica se l'utente è loggato
        if (!is_user_logged_in()) {
            return $this->get_login_message();
        }
        
        // Ottieni l'utente corrente
        $current_user = wp_get_current_user();
        
        // Ottieni i meta dell'utente
        $riot_id = get_user_meta($current_user->ID, 'eto_riot_id', true);
        $discord_tag = get_user_meta($current_user->ID, 'eto_discord_tag', true);
        $nationality = get_user_meta($current_user->ID, 'eto_nationality', true);
        
        // Ottieni i team dell'utente
        $teams = $this->get_user_teams($current_user->ID);
        
        // Ottieni lo storico dei tornei
        $tournaments = $this->get_user_tournaments($current_user->ID);
        
        // Inizia il buffer di output
        ob_start();
        
        // Includi il template aggiornato
        include(ETO_PLUGIN_DIR . '/templates/frontend/users/profile.php');
        
        // Restituisci il contenuto
        return ob_get_clean();
    }
    
    /**
     * Shortcode per la creazione di un team
     *
     * @param array $atts Attributi dello shortcode
     * @return string Output HTML
     */
    public function create_team_shortcode($atts) {
        // Verifica se l'utente è loggato
        if (!is_user_logged_in()) {
            return $this->get_login_message();
        }
        
        // Ottieni l'utente corrente
        $current_user = wp_get_current_user();
        
        // Verifica se l'utente è già membro di un team
        $user_teams = $this->get_user_teams($current_user->ID);
        
        // Se l'utente è già membro di un team, mostra un messaggio
        if (!empty($user_teams)) {
            ob_start();
            include(ETO_PLUGIN_DIR . '/templates/frontend/teams/team-exists.php');
            return ob_get_clean();
        }
        
        // Gestisci il form di creazione team
        $errors = [];
        $success = false;
        
        if (isset($_POST['eto_create_team_submit'])) {
            // Verifica il nonce
            if (!$this->security->verify_request_nonce('eto_create_team', 'eto_team_nonce', false)) {
                $errors['nonce'] = __('Errore di sicurezza. Ricarica la pagina e riprova.', 'eto');
            } else {
                // Definisci le regole di validazione
                $validation_rules = [
                    'team_name' => [
                        'required' => true,
                        'min_length' => 3,
                        'max_length' => 50
                    ],
                    'team_description' => [
                        'max_length' => 500
                    ]
                ];
                
                // Sanitizza i dati del form
                $form_data = $this->security->sanitize_form_data($_POST, [
                    'team_name' => ['type' => 'text'],
                    'team_description' => ['type' => 'textarea']
                ]);
                
                // Valida i dati
                $validation_errors = $this->security->validate_form_data($form_data, $validation_rules);
                
                if (!empty($validation_errors)) {
                    $errors = array_merge($errors, $validation_errors);
                } else {
                    // Crea il team
                    $team_id = $this->create_team($form_data, $current_user->ID);
                    
                    if ($team_id) {
                        $success = true;
                    } else {
                        $errors['general'] = __('Si è verificato un errore durante la creazione del team. Riprova più tardi.', 'eto');
                    }
                }
            }
        }
        
        // Inizia il buffer di output
        ob_start();
        
        // Includi il template aggiornato
        include(ETO_PLUGIN_DIR . '/templates/frontend/teams/create-team.php');
        
        // Restituisci il contenuto
        return ob_get_clean();
    }
    
    /**
     * Shortcode per la lista dei tornei
     *
     * @param array $atts Attributi dello shortcode
     * @return string Output HTML
     */
    public function tournament_list_shortcode($atts) {
        // Analizza gli attributi
        $atts = shortcode_atts([
            'status' => 'active',
            'limit' => 10
        ], $atts, 'eto_tournament_list');
        
        // Sanitizza gli attributi
        $status = sanitize_text_field($atts['status']);
        $limit = absint($atts['limit']);
        
        // Ottieni i tornei
        $tournaments = $this->db_query->get_tournaments([
            'status' => $status,
            'limit' => $limit
        ]);
        
        // Inizia il buffer di output
        ob_start();
        
        // Includi il template aggiornato
        include(ETO_PLUGIN_DIR . '/templates/frontend/tournaments/tournament-list.php');
        
        // Restituisci il contenuto
        return ob_get_clean();
    }
    
    /**
     * Shortcode per la visualizzazione di un torneo
     *
     * @param array $atts Attributi dello shortcode
     * @return string Output HTML
     */
    public function tournament_view_shortcode($atts) {
        // Analizza gli attributi
        $atts = shortcode_atts([
            'id' => 0
        ], $atts, 'eto_tournament_view');
        
        // Sanitizza gli attributi
        $tournament_id = absint($atts['id']);
        
        // Se non è specificato un ID, prova a ottenerlo dalla query
        if ($tournament_id === 0 && isset($_GET['tournament_id'])) {
            $tournament_id = absint($_GET['tournament_id']);
        }
        
        // Se ancora non abbiamo un ID, mostra un messaggio di errore
        if ($tournament_id === 0) {
            return '<div class="eto-error">' . __('Torneo non specificato', 'eto') . '</div>';
        }
        
        // Ottieni il torneo
        $tournament = $this->db_query->get_tournament($tournament_id);
        
        // Se il torneo non esiste, mostra un messaggio di errore
        if (!$tournament) {
            return '<div class="eto-error">' . __('Torneo non trovato', 'eto') . '</div>';
        }
        
        // Ottieni i team partecipanti
        $teams = $this->db_query->get_tournament_teams($tournament_id);
        
        // Ottieni le partite
        $matches = $this->db_query->get_tournament_matches($tournament_id);
        
        // Inizia il buffer di output
        ob_start();
        
        // Includi il template aggiornato
        include(ETO_PLUGIN_DIR . '/templates/frontend/tournaments/tournament-view.php');
        
        // Restituisci il contenuto
        return ob_get_clean();
    }
    
    /**
     * Shortcode per il check-in a un torneo
     *
     * @param array $atts Attributi dello shortcode
     * @return string Output HTML
     */
    public function checkin_shortcode($atts) {
        // Analizza gli attributi
        $atts = shortcode_atts([
            'id' => 0
        ], $atts, 'eto_checkin');
        
        // Sanitizza gli attributi
        $tournament_id = absint($atts['id']);
        
        // Se non è specificato un ID, prova a ottenerlo dalla query
        if ($tournament_id === 0 && isset($_GET['tournament_id'])) {
            $tournament_id = absint($_GET['tournament_id']);
        }
        
        // Se ancora non abbiamo un ID, mostra un messaggio di errore
        if ($tournament_id === 0) {
            return '<div class="eto-error">' . __('Torneo non specificato', 'eto') . '</div>';
        }
        
        // Ottieni il torneo
        $tournament = $this->db_query->get_tournament($tournament_id);
        
        // Se il torneo non esiste, mostra un messaggio di errore
        if (!$tournament) {
            return '<div class="eto-error">' . __('Torneo non trovato', 'eto') . '</div>';
        }
        
        // Inizia il buffer di output
        ob_start();
        
        // Includi il template aggiornato
        include(ETO_PLUGIN_DIR . '/templates/frontend/tournaments/checkin.php');
        
        // Restituisci il contenuto
        return ob_get_clean();
    }
    
    /**
     * Shortcode per la leaderboard
     *
     * @param array $atts Attributi dello shortcode
     * @return string Output HTML
     */
    public function leaderboard_shortcode($atts) {
        // Analizza gli attributi
        $atts = shortcode_atts([
            'game' => '',
            'limit' => 10
        ], $atts, 'eto_leaderboard');
        
        // Sanitizza gli attributi
        $game = sanitize_text_field($atts['game']);
        $limit = absint($atts['limit']);
        
        // Ottieni la leaderboard
        $leaderboard = $this->db_query->get_leaderboard([
            'game' => $game,
            'limit' => $limit
        ]);
        
        // Inizia il buffer di output
        ob_start();
        
        // Includi il template aggiornato
        include(ETO_PLUGIN_DIR . '/templates/frontend/tournaments/leaderboard.php');
        
        // Restituisci il contenuto
        return ob_get_clean();
    }
    
    /**
     * Shortcode per i membri di un team
     *
     * @param array $atts Attributi dello shortcode
     * @return string Output HTML
     */
    public function team_members_shortcode($atts) {
        // Analizza gli attributi
        $atts = shortcode_atts([
            'id' => 0
        ], $atts, 'eto_team_members');
        
        // Sanitizza gli attributi
        $team_id = absint($atts['id']);
        
        // Se non è specificato un ID, prova a ottenerlo dalla query
        if ($team_id === 0 && isset($_GET['team_id'])) {
            $team_id = absint($_GET['team_id']);
        }
        
        // Se ancora non abbiamo un ID, mostra un messaggio di errore
        if ($team_id === 0) {
            return '<div class="eto-error">' . __('Team non specificato', 'eto') . '</div>';
        }
        
        // Ottieni il team
        $team = $this->db_query->get_team($team_id);
        
        // Se il team non esiste, mostra un messaggio di errore
        if (!$team) {
            return '<div class="eto-error">' . __('Team non trovato', 'eto') . '</div>';
        }
        
        // Ottieni i membri del team
        $members = $this->db_query->get_team_members($team_id);
        
        // Verifica se l'utente corrente è il capitano
        $is_captain = false;
        
        if (is_user_logged_in()) {
            $current_user_id = get_current_user_id();
            $is_captain = ($team['captain_id'] == $current_user_id);
        }
        
        // Inizia il buffer di output
        ob_start();
        
        // Includi il template aggiornato
        include(ETO_PLUGIN_DIR . '/templates/frontend/teams/team-members.php');
        
        // Restituisci il contenuto
        return ob_get_clean();
    }
    
    /**
     * Ottiene i team di un utente
     *
     * @param int $user_id ID dell'utente
     * @return array Array di team
     */
    private function get_user_teams($user_id) {
        return $this->db_query->get_user_teams($user_id);
    }
    
    /**
     * Ottiene i tornei di un utente
     *
     * @param int $user_id ID dell'utente
     * @return array Array di tornei
     */
    private function get_user_tournaments($user_id) {
        return $this->db_query->get_user_tournaments($user_id);
    }
    
    /**
     * Crea un nuovo team
     *
     * @param array $data Dati del team
     * @param int $captain_id ID del capitano
     * @return int|false ID del team creato o false in caso di errore
     */
    private function create_team($data, $captain_id) {
        return $this->db_query->create_team([
            'name' => $data['team_name'],
            'description' => $data['team_description'],
            'captain_id' => $captain_id,
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Ottiene il messaggio di login
     *
     * @return string Messaggio HTML
     */
    private function get_login_message() {
        return sprintf(
            '<div class="eto-login-required"><p>%s</p><p><a href="%s" class="button">%s</a></p></div>',
            __('Devi effettuare il login per accedere a questa funzionalità.', 'eto'),
            wp_login_url(get_permalink()),
            __('Accedi', 'eto')
        );
    }
}
