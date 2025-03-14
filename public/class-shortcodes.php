<?php
/**
 * Classe per la gestione degli shortcode
 * 
 * Registra e gestisce tutti gli shortcode disponibili nel plugin
 * 
 * @package ETO
 * @since 2.5.1
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
        
        // Includi il template
        include(ETO_PLUGIN_DIR . '/templates/profile.php');
        
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
            include(ETO_PLUGIN_DIR . '/templates/team-exists.php');
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
        
        // Includi il template
        include(ETO_PLUGIN_DIR . '/templates/create-team.php');
        
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
        
        // Includi il template
        include(ETO_PLUGIN_DIR . '/templates/tournament-list.php');
        
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
        
        if ($tournament_id <= 0) {
            return '<p class="eto-error">' . __('ID torneo non valido.', 'eto') . '</p>';
        }
        
        // Ottieni il torneo
        $tournament = $this->db_query->get_tournament($tournament_id);
        
        if (!$tournament) {
            return '<p class="eto-error">' . __('Torneo non trovato.', 'eto') . '</p>';
        }
        
        // Ottieni i team registrati
        $teams = $this->get_tournament_teams($tournament_id);
        
        // Ottieni il bracket
        $bracket = $this->get_tournament_bracket($tournament_id);
        
        // Inizia il buffer di output
        ob_start();
        
        // Includi il template
        include(ETO_PLUGIN_DIR . '/templates/tournament-view.php');
        
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
        // Verifica se l'utente è loggato
        if (!is_user_logged_in()) {
            return $this->get_login_message();
        }
        
        // Analizza gli attributi
        $atts = shortcode_atts([
            'tournament_id' => 0
        ], $atts, 'eto_checkin');
        
        // Sanitizza gli attributi
        $tournament_id = absint($atts['tournament_id']);
        
        if ($tournament_id <= 0) {
            return '<p class="eto-error">' . __('ID torneo non valido.', 'eto') . '</p>';
        }
        
        // Ottieni l'utente corrente
        $current_user = wp_get_current_user();
        
        // Ottieni il torneo
        $tournament = $this->db_query->get_tournament($tournament_id);
        
        if (!$tournament) {
            return '<p class="eto-error">' . __('Torneo non trovato.', 'eto') . '</p>';
        }
        
        // Verifica se l'utente appartiene a un team registrato al torneo
        $user_team = $this->get_user_tournament_team($current_user->ID, $tournament_id);
        
        if (!$user_team) {
            return '<p class="eto-error">' . __('Non sei registrato a questo torneo con nessun team.', 'eto') . '</p>';
        }
        
        // Verifica se il check-in è già stato effettuato
        $checked_in = $this->is_team_checked_in($user_team->team_id, $tournament_id);
        
        // Gestisci il form di check-in
        $errors = [];
        $success = false;
        
        if (isset($_POST['eto_checkin_submit']) && !$checked_in) {
            // Verifica il nonce
            if (!$this->security->verify_request_nonce('eto_checkin', 'eto_checkin_nonce', false)) {
                $errors['nonce'] = __('Errore di sicurezza. Ricarica la pagina e riprova.', 'eto');
            } else {
                // Effettua il check-in
                $result = $this->perform_checkin($user_team->team_id, $tournament_id);
                
                if ($result) {
                    $success = true;
                    $checked_in = true;
                } else {
                    $errors['general'] = __('Si è verificato un errore durante il check-in. Riprova più tardi.', 'eto');
                }
            }
        }
        
        // Inizia il buffer di output
        ob_start();
        
        // Includi il template
        include(ETO_PLUGIN_DIR . '/templates/checkin.php');
        
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
            'limit' => 10
        ], $atts, 'eto_leaderboard');
        
        // Sanitizza gli attributi
        $limit = absint($atts['limit']);
        
        // Ottieni la classifica
        $leaderboard = $this->get_leaderboard($limit);
        
        // Inizia il buffer di output
        ob_start();
        
        // Includi il template
        include(ETO_PLUGIN_DIR . '/templates/leaderboard.php');
        
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
            'team_id' => 0
        ], $atts, 'eto_team_members');
        
        // Sanitizza gli attributi
        $team_id = absint($atts['team_id']);
        
        if ($team_id <= 0) {
            return '<p class="eto-error">' . __('ID team non valido.', 'eto') . '</p>';
        }
        
        // Ottieni il team
        $team = $this->db_query->get_team($team_id);
        
        if (!$team) {
            return '<p class="eto-error">' . __('Team non trovato.', 'eto') . '</p>';
        }
        
        // Ottieni i membri del team
        $members = $this->db_query->get_team_members($team_id);
        
        // Inizia il buffer di output
        ob_start();
        
        // Includi il template
        include(ETO_PLUGIN_DIR . '/templates/team-members.php');
        
        // Restituisci il contenuto
        return ob_get_clean();
    }
    
    /**
     * Ottiene i team di un utente
     *
     * @param int $user_id ID dell'utente
     * @return array Lista di team
     */
    private function get_user_teams($user_id) {
        global $wpdb;
        
        $table_teams = $this->db_query->get_table_name('teams');
        $table_members = $this->db_query->get_table_name('team_members');
        
        $teams = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, tm.role 
                FROM $table_teams t
                JOIN $table_members tm ON t.id = tm.team_id
                WHERE tm.user_id = %d",
                $user_id
            )
        );
        
        return $teams;
    }
    
    /**
     * Ottiene i tornei di un utente
     *
     * @param int $user_id ID dell'utente
     * @return array Lista di tornei
     */
    private function get_user_tournaments($user_id) {
        global $wpdb;
        
        $table_tournaments = $this->db_query->get_table_name('tournaments');
        $table_entries = $this->db_query->get_table_name('tournament_entries');
        $table_teams = $this->db_query->get_table_name('teams');
        $table_members = $this->db_query->get_table_name('team_members');
        
        $tournaments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, te.status as entry_status, tm.name as team_name
                FROM $table_tournaments t
                JOIN $table_entries te ON t.id = te.tournament_id
                JOIN $table_teams tm ON te.team_id = tm.id
                JOIN $table_members tmm ON tm.id = tmm.team_id
                WHERE tmm.user_id = %d
                ORDER BY t.start_date DESC",
                $user_id
            )
        );
        
        return $tournaments;
    }
    
    /**
     * Crea un nuovo team
     *
     * @param array $data Dati del team
     * @param int $user_id ID dell'utente
     * @return int|false ID del team creato o false in caso di errore
     */
    private function create_team($data, $user_id) {
        global $wpdb;
        
        $table_teams = $this->db_query->get_table_name('teams');
        $table_members = $this->db_query->get_table_name('team_members');
        
        // Inizia la transazione
        $wpdb->query('START TRANSACTION');
        
        try {
            // Inserisci il team
            $result = $wpdb->insert(
                $table_teams,
                [
                    'name' => $data['team_name'],
                    'captain_id' => $user_id,
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%d', '%s']
            );
            
            if ($result === false) {
                throw new Exception($wpdb->last_error);
            }
            
            $team_id = $wpdb->insert_id;
            
            // Aggiungi l'utente come capitano
            $result = $wpdb->insert(
                $table_members,
                [
                    'team_id' => $team_id,
                    'user_id' => $user_id,
                    'role' => 'captain',
                    'joined_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%s']
            );
            
            if ($result === false) {
                throw new Exception($wpdb->last_error);
            }
            
            // Commit della transazione
            $wpdb->query('COMMIT');
            
            return $team_id;
            
        } catch (Exception $e) {
            // Rollback in caso di errore
            $wpdb->query('ROLLBACK');
            
            if (defined('ETO_DEBUG') && ETO_DEBUG) {
                error_log('[ETO] Errore creazione team: ' . $e->getMessage());
            }
            
            return false;
        }
    }
    
    /**
     * Ottiene i team registrati a un torneo
     *
     * @param int $tournament_id ID del torneo
     * @return array Lista di team
     */
    private function get_tournament_teams($tournament_id) {
        global $wpdb;
        
        $table_teams = $this->db_query->get_table_name('teams');
        $table_entries = $this->db_query->get_table_name('tournament_entries');
        
        $teams = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, te.status as entry_status, te.checked_in
                FROM $table_teams t
                JOIN $table_entries te ON t.id = te.team_id
                WHERE te.tournament_id = %d
                ORDER BY te.seed ASC, t.name ASC",
                $tournament_id
            )
        );
        
        return $teams;
    }
    
    /**
     * Ottiene il bracket di un torneo
     *
     * @param int $tournament_id ID del torneo
     * @return array Dati del bracket
     */
    private function get_tournament_bracket($tournament_id) {
        global $wpdb;
        
        $table_matches = $this->db_query->get_table_name('matches');
        $table_teams = $this->db_query->get_table_name('teams');
        
        $matches = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.*, 
                t1.name as team1_name, 
                t2.name as team2_name
                FROM $table_matches m
                LEFT JOIN $table_teams t1 ON m.team1_id = t1.id
                LEFT JOIN $table_teams t2 ON m.team2_id = t2.id
                WHERE m.tournament_id = %d
                ORDER BY m.round ASC, m.match_number ASC",
                $tournament_id
            )
        );
        
        // Organizza i match per round
        $bracket = [];
        foreach ($matches as $match) {
            if (!isset($bracket[$match->round])) {
                $bracket[$match->round] = [];
            }
            $bracket[$match->round][] = $match;
        }
        
        return $bracket;
    }
    
    /**
     * Ottiene il team di un utente per un torneo
     *
     * @param int $user_id ID dell'utente
     * @param int $tournament_id ID del torneo
     * @return object|false Team o false se non trovato
     */
    private function get_user_tournament_team($user_id, $tournament_id) {
        global $wpdb;
        
        $table_teams = $this->db_query->get_table_name('teams');
        $table_members = $this->db_query->get_table_name('team_members');
        $table_entries = $this->db_query->get_table_name('tournament_entries');
        
        $team = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT t.*, tm.role, te.status as entry_status
                FROM $table_teams t
                JOIN $table_members tm ON t.id = tm.team_id
                JOIN $table_entries te ON t.id = te.team_id
                WHERE tm.user_id = %d
                AND te.tournament_id = %d",
                $user_id,
                $tournament_id
            )
        );
        
        return $team;
    }
    
    /**
     * Verifica se un team ha effettuato il check-in per un torneo
     *
     * @param int $team_id ID del team
     * @param int $tournament_id ID del torneo
     * @return bool True se il check-in è stato effettuato, false altrimenti
     */
    private function is_team_checked_in($team_id, $tournament_id) {
        global $wpdb;
        
        $table_entries = $this->db_query->get_table_name('tournament_entries');
        
        $checked_in = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT checked_in
                FROM $table_entries
                WHERE team_id = %d
                AND tournament_id = %d",
                $team_id,
                $tournament_id
            )
        );
        
        return (bool) $checked_in;
    }
    
    /**
     * Effettua il check-in di un team per un torneo
     *
     * @param int $team_id ID del team
     * @param int $tournament_id ID del torneo
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    private function perform_checkin($team_id, $tournament_id) {
        global $wpdb;
        
        $table_entries = $this->db_query->get_table_name('tournament_entries');
        
        $result = $wpdb->update(
            $table_entries,
            [
                'checked_in' => 1,
                'checked_in_at' => current_time('mysql')
            ],
            [
                'team_id' => $team_id,
                'tournament_id' => $tournament_id
            ],
            ['%d', '%s'],
            ['%d', '%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Ottiene la leaderboard
     *
     * @param int $limit Numero massimo di team
     * @return array Dati della leaderboard
     */
    private function get_leaderboard($limit) {
        global $wpdb;
        
        $table_teams = $this->db_query->get_table_name('teams');
        $table_matches = $this->db_query->get_table_name('matches');
        
        // Questa query calcola vittorie e differenziale punti per ogni team
        $leaderboard = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    t.id, 
                    t.name,
                    COUNT(CASE 
                        WHEN (m.team1_id = t.id AND m.team1_score > m.team2_score) OR 
                             (m.team2_id = t.id AND m.team2_score > m.team1_score) 
                        THEN 1 
                        ELSE NULL 
                    END) as wins,
                    COUNT(CASE 
                        WHEN (m.team1_id = t.id AND m.team1_score < m.team2_score) OR 
                             (m.team2_id = t.id AND m.team2_score < m.team1_score) 
                        THEN 1 
                        ELSE NULL 
                    END) as losses,
                    SUM(CASE 
                        WHEN m.team1_id = t.id THEN m.team1_score - m.team2_score
                        WHEN m.team2_id = t.id THEN m.team2_score - m.team1_score
                        ELSE 0 
                    END) as score_diff
                FROM $table_teams t
                LEFT JOIN $table_matches m ON (m.team1_id = t.id OR m.team2_id = t.id) AND m.status = 'completed'
                GROUP BY t.id
                ORDER BY wins DESC, score_diff DESC
                LIMIT %d",
                $limit
            )
        );
        
        return $leaderboard;
    }
    
    /**
     * Ottiene il messaggio di login
     *
     * @return string Messaggio HTML
     */
    private function get_login_message() {
        $login_url = wp_login_url(get_permalink());
        
        return sprintf(
            '<div class="eto-login-required"><p>%s</p><p><a href="%s" class="button">%s</a></p></div>',
            __('Devi effettuare il login per accedere a questa funzionalità.', 'eto'),
            esc_url($login_url),
            __('Accedi', 'eto')
        );
    }
}
