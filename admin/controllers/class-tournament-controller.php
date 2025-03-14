<?php
/**
 * Controller per la gestione dell'amministrazione dei tornei
 * 
 * Gestisce tutte le funzionalità relative ai tornei nel pannello di amministrazione
 * 
 * @package ETO
 * @subpackage Controllers
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Tournament_Controller {
    
    /**
     * Istanza del modello torneo
     *
     * @var ETO_Tournament_Model
     */
    private $tournament_model;
    
    /**
     * Istanza della classe di sicurezza
     *
     * @var ETO_Security_Enhanced
     */
    private $security;
    
    /**
     * Istanza della classe di query al database
     *
     * @var ETO_DB_Query_Secure
     */
    private $db_query;
    
    /**
     * Costruttore
     */
    public function __construct() {
        $this->tournament_model = new ETO_Tournament_Model();
        $this->security = eto_security_enhanced();
        $this->db_query = eto_db_query_secure();
        
        // Inizializza il controller
        $this->init();
    }
    
    /**
     * Inizializza il controller
     */
    public function init() {
        // Aggiungi le azioni per le pagine di amministrazione
        add_action('admin_menu', [$this, 'register_admin_menu']);
        
        // Registra gli AJAX handler
        $this->register_ajax_handlers();
        
        // Aggiungi i meta box
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
    }
    
    /**
     * Registra le voci di menu
     */
    public function register_admin_menu() {
        add_menu_page(
            __('Tornei', 'eto'),
            __('Tornei', 'eto'),
            'manage_options',
            'eto-tournaments',
            [$this, 'render_tournaments_page'],
            'dashicons-awards',
            30
        );
        
        add_submenu_page(
            'eto-tournaments',
            __('Tutti i Tornei', 'eto'),
            __('Tutti i Tornei', 'eto'),
            'manage_options',
            'eto-tournaments',
            [$this, 'render_tournaments_page']
        );
        
        add_submenu_page(
            'eto-tournaments',
            __('Aggiungi Nuovo', 'eto'),
            __('Aggiungi Nuovo', 'eto'),
            'manage_options',
            'eto-add-tournament',
            [$this, 'render_add_tournament_page']
        );
        
        add_submenu_page(
            'eto-tournaments',
            __('Categorie', 'eto'),
            __('Categorie', 'eto'),
            'manage_options',
            'eto-tournament-categories',
            [$this, 'render_tournament_categories_page']
        );
    }
    
    /**
     * Registra gli AJAX handler
     */
    private function register_ajax_handlers() {
        // AJAX per la creazione di un torneo
        add_action('wp_ajax_eto_create_tournament', [$this, 'ajax_create_tournament']);
        
        // AJAX per l'aggiornamento di un torneo
        add_action('wp_ajax_eto_update_tournament', [$this, 'ajax_update_tournament']);
        
        // AJAX per l'eliminazione di un torneo
        add_action('wp_ajax_eto_delete_tournament', [$this, 'ajax_delete_tournament']);
        
        // AJAX per il caricamento dei dati di un torneo
        add_action('wp_ajax_eto_load_tournament', [$this, 'ajax_load_tournament']);
        
        // AJAX per la generazione dei match
        add_action('wp_ajax_eto_generate_matches', [$this, 'ajax_generate_matches']);
        
        // AJAX per l'aggiornamento dello stato di un torneo
        add_action('wp_ajax_eto_update_tournament_status', [$this, 'ajax_update_tournament_status']);
    }
    
    /**
     * Registra i meta box
     */
    public function register_meta_boxes() {
        add_meta_box(
            'eto-tournament-details',
            __('Dettagli Torneo', 'eto'),
            [$this, 'render_tournament_details_meta_box'],
            'eto-tournament',
            'normal',
            'high'
        );
        
        add_meta_box(
            'eto-tournament-teams',
            __('Team Iscritti', 'eto'),
            [$this, 'render_tournament_teams_meta_box'],
            'eto-tournament',
            'normal',
            'default'
        );
        
        add_meta_box(
            'eto-tournament-matches',
            __('Match', 'eto'),
            [$this, 'render_tournament_matches_meta_box'],
            'eto-tournament',
            'normal',
            'default'
        );
    }
    
    /**
     * Renderizza la pagina dei tornei
     */
    public function render_tournaments_page() {
        // Ottieni i parametri di paginazione
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        // Ottieni i parametri di filtro
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $game = isset($_GET['game']) ? sanitize_text_field($_GET['game']) : '';
        
        // Ottieni i tornei
        $args = [
            'limit' => $per_page,
            'offset' => $offset
        ];
        
        if (!empty($status)) {
            $args['status'] = $status;
        }
        
        if (!empty($game)) {
            $args['game'] = $game;
        }
        
        $tournaments = ETO_Tournament_Model::get_all($args);
        $total_tournaments = ETO_Tournament_Model::count_all($args);
        
        // Calcola la paginazione
        $total_pages = ceil($total_tournaments / $per_page);
        
        // Renderizza la vista
        include ETO_PLUGIN_DIR . '/admin/views/tournaments/list.php';
    }
    
    /**
     * Renderizza la pagina di aggiunta di un torneo
     */
    public function render_add_tournament_page() {
        // Ottieni i giochi disponibili
        $games = $this->get_available_games();
        
        // Ottieni i formati di torneo disponibili
        $formats = $this->get_available_formats();
        
        // Renderizza la vista
        include ETO_PLUGIN_DIR . '/admin/views/tournaments/add.php';
    }
    
    /**
     * Renderizza la pagina di modifica di un torneo
     */
    public function render_edit_tournament_page() {
        // Ottieni l'ID del torneo
        $tournament_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (empty($tournament_id)) {
            wp_die(__('ID torneo non valido', 'eto'));
        }
        
        // Ottieni il torneo
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            wp_die(__('Torneo non trovato', 'eto'));
        }
        
        // Ottieni i giochi disponibili
        $games = $this->get_available_games();
        
        // Ottieni i formati di torneo disponibili
        $formats = $this->get_available_formats();
        
        // Renderizza la vista
        include ETO_PLUGIN_DIR . '/admin/views/tournaments/edit.php';
    }
    
    /**
     * Renderizza la pagina delle categorie di torneo
     */
    public function render_tournament_categories_page() {
        // Ottieni le categorie
        $categories = get_terms([
            'taxonomy' => 'eto_tournament_category',
            'hide_empty' => false
        ]);
        
        // Renderizza la vista
        include ETO_PLUGIN_DIR . '/admin/views/tournaments/categories.php';
    }
    
    /**
     * Renderizza il meta box dei dettagli del torneo
     *
     * @param WP_Post $post Post corrente
     */
    public function render_tournament_details_meta_box($post) {
        // Ottieni il torneo
        $tournament_id = $post->ID;
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            echo '<p>' . __('Torneo non trovato', 'eto') . '</p>';
            return;
        }
        
        // Ottieni i giochi disponibili
        $games = $this->get_available_games();
        
        // Ottieni i formati di torneo disponibili
        $formats = $this->get_available_formats();
        
        // Renderizza la vista
        include ETO_PLUGIN_DIR . '/admin/views/tournaments/meta-box-details.php';
    }
    
    /**
     * Renderizza il meta box dei team iscritti al torneo
     *
     * @param WP_Post $post Post corrente
     */
    public function render_tournament_teams_meta_box($post) {
        // Ottieni il torneo
        $tournament_id = $post->ID;
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            echo '<p>' . __('Torneo non trovato', 'eto') . '</p>';
            return;
        }
        
        // Ottieni i team iscritti
        $teams = $tournament->get_teams();
        
        // Renderizza la vista
        include ETO_PLUGIN_DIR . '/admin/views/tournaments/meta-box-teams.php';
    }
    
    /**
     * Renderizza il meta box dei match del torneo
     *
     * @param WP_Post $post Post corrente
     */
    public function render_tournament_matches_meta_box($post) {
        // Ottieni il torneo
        $tournament_id = $post->ID;
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            echo '<p>' . __('Torneo non trovato', 'eto') . '</p>';
            return;
        }
        
        // Ottieni i match
        $matches = $tournament->get_matches();
        
        // Renderizza la vista
        include ETO_PLUGIN_DIR . '/admin/views/tournaments/meta-box-matches.php';
    }
    
    /**
     * AJAX: Crea un nuovo torneo
     */
    public function ajax_create_tournament() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_create_tournament', 'eto_nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza: token di verifica non valido.', 'eto')]);
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni i dati del form
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $game = isset($_POST['game']) ? sanitize_text_field($_POST['game']) : '';
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : '';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $registration_start = isset($_POST['registration_start']) ? sanitize_text_field($_POST['registration_start']) : '';
        $registration_end = isset($_POST['registration_end']) ? sanitize_text_field($_POST['registration_end']) : '';
        $max_teams = isset($_POST['max_teams']) ? intval($_POST['max_teams']) : 0;
        $min_teams = isset($_POST['min_teams']) ? intval($_POST['min_teams']) : 0;
        
        // Valida i dati
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = __('Il nome del torneo è obbligatorio.', 'eto');
        }
        
        if (empty($game)) {
            $errors['game'] = __('Il gioco è obbligatorio.', 'eto');
        }
        
        if (empty($format)) {
            $errors['format'] = __('Il formato è obbligatorio.', 'eto');
        }
        
        if (empty($start_date)) {
            $errors['start_date'] = __('La data di inizio è obbligatoria.', 'eto');
        }
        
        if (empty($end_date)) {
            $errors['end_date'] = __('La data di fine è obbligatoria.', 'eto');
        }
        
        if (!empty($start_date) && !empty($end_date) && strtotime($start_date) > strtotime($end_date)) {
            $errors['end_date'] = __('La data di fine deve essere successiva alla data di inizio.', 'eto');
        }
        
        if (!empty($registration_start) && !empty($registration_end) && strtotime($registration_start) > strtotime($registration_end)) {
            $errors['registration_end'] = __('La data di fine registrazione deve essere successiva alla data di inizio registrazione.', 'eto');
        }
        
        if (!empty($registration_end) && !empty($start_date) && strtotime($registration_end) > strtotime($start_date)) {
            $errors['registration_end'] = __('La data di fine registrazione deve essere precedente alla data di inizio torneo.', 'eto');
        }
        
        if ($max_teams < $min_teams) {
            $errors['max_teams'] = __('Il numero massimo di team deve essere maggiore o uguale al numero minimo.', 'eto');
        }
        
        if (!empty($errors)) {
            wp_send_json_error(['message' => __('Errore nella validazione dei dati.', 'eto'), 'errors' => $errors]);
        }
        
        // Crea il torneo
        $tournament = new ETO_Tournament_Model();
        $tournament->set('name', $name);
        $tournament->set('description', $description);
        $tournament->set('game', $game);
        $tournament->set('format', $format);
        $tournament->set('start_date', $start_date);
        $tournament->set('end_date', $end_date);
        $tournament->set('registration_start', $registration_start);
        $tournament->set('registration_end', $registration_end);
        $tournament->set('max_teams', $max_teams);
        $tournament->set('min_teams', $min_teams);
        $tournament->set('status', 'pending');
        $tournament->set('created_by', get_current_user_id());
        
        $tournament_id = $tournament->save();
        
        if (!$tournament_id) {
            wp_send_json_error(['message' => __('Errore nella creazione del torneo.', 'eto')]);
        }
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'tournament_created',
                sprintf(__('Torneo "%s" creato', 'eto'), $name),
                ['tournament_id' => $tournament_id]
            );
        }
        
        wp_send_json_success([
            'message' => __('Torneo creato con successo.', 'eto'),
            'tournament_id' => $tournament_id
        ]);
    }
    
    /**
     * AJAX: Aggiorna un torneo
     */
    public function ajax_update_tournament() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_update_tournament', 'eto_nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza: token di verifica non valido.', 'eto')]);
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni l'ID del torneo
        $tournament_id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
        
        if (empty($tournament_id)) {
            wp_send_json_error(['message' => __('ID torneo non valido.', 'eto')]);
        }
        
        // Ottieni il torneo
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            wp_send_json_error(['message' => __('Torneo non trovato.', 'eto')]);
        }
        
        // Ottieni i dati del form
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $game = isset($_POST['game']) ? sanitize_text_field($_POST['game']) : '';
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : '';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $registration_start = isset($_POST['registration_start']) ? sanitize_text_field($_POST['registration_start']) : '';
        $registration_end = isset($_POST['registration_end']) ? sanitize_text_field($_POST['registration_end']) : '';
        $max_teams = isset($_POST['max_teams']) ? intval($_POST['max_teams']) : 0;
        $min_teams = isset($_POST['min_teams']) ? intval($_POST['min_teams']) : 0;
        
        // Valida i dati
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = __('Il nome del torneo è obbligatorio.', 'eto');
        }
        
        if (empty($game)) {
            $errors['game'] = __('Il gioco è obbligatorio.', 'eto');
        }
        
        if (empty($format)) {
            $errors['format'] = __('Il formato è obbligatorio.', 'eto');
        }
        
        if (empty($start_date)) {
            $errors['start_date'] = __('La data di inizio è obbligatoria.', 'eto');
        }
        
        if (empty($end_date)) {
            $errors['end_date'] = __('La data di fine è obbligatoria.', 'eto');
        }
        
        if (!empty($start_date) && !empty($end_date) && strtotime($start_date) > strtotime($end_date)) {
            $errors['end_date'] = __('La data di fine deve essere successiva alla data di inizio.', 'eto');
        }
        
        if (!empty($registration_start) && !empty($registration_end) && strtotime($registration_start) > strtotime($registration_end)) {
            $errors['registration_end'] = __('La data di fine registrazione deve essere successiva alla data di inizio registrazione.', 'eto');
        }
        
        if (!empty($registration_end) && !empty($start_date) && strtotime($registration_end) > strtotime($start_date)) {
            $errors['registration_end'] = __('La data di fine registrazione deve essere precedente alla data di inizio torneo.', 'eto');
        }
        
        if ($max_teams < $min_teams) {
            $errors['max_teams'] = __('Il numero massimo di team deve essere maggiore o uguale al numero minimo.', 'eto');
        }
        
        if (!empty($errors)) {
            wp_send_json_error(['message' => __('Errore nella validazione dei dati.', 'eto'), 'errors' => $errors]);
        }
        
        // Aggiorna il torneo
        $tournament->set('name', $name);
        $tournament->set('description', $description);
        $tournament->set('game', $game);
        $tournament->set('format', $format);
        $tournament->set('start_date', $start_date);
        $tournament->set('end_date', $end_date);
        $tournament->set('registration_start', $registration_start);
        $tournament->set('registration_end', $registration_end);
        $tournament->set('max_teams', $max_teams);
        $tournament->set('min_teams', $min_teams);
        
        $result = $tournament->save();
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore nell\'aggiornamento del torneo.', 'eto')]);
        }
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'tournament_updated',
                sprintf(__('Torneo "%s" aggiornato', 'eto'), $name),
                ['tournament_id' => $tournament_id]
            );
        }
        
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
        if (!$this->security->verify_request_nonce('eto_delete_tournament', 'eto_nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza: token di verifica non valido.', 'eto')]);
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni l'ID del torneo
        $tournament_id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
        
        if (empty($tournament_id)) {
            wp_send_json_error(['message' => __('ID torneo non valido.', 'eto')]);
        }
        
        // Ottieni il torneo
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            wp_send_json_error(['message' => __('Torneo non trovato.', 'eto')]);
        }
        
        // Elimina il torneo
        $result = $tournament->delete();
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore nell\'eliminazione del torneo.', 'eto')]);
        }
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'tournament_deleted',
                sprintf(__('Torneo ID %d eliminato', 'eto'), $tournament_id),
                ['tournament_id' => $tournament_id]
            );
        }
        
        wp_send_json_success([
            'message' => __('Torneo eliminato con successo.', 'eto')
        ]);
    }
    
    /**
     * AJAX: Carica i dati di un torneo
     */
    public function ajax_load_tournament() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_load_tournament', 'eto_nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza: token di verifica non valido.', 'eto')]);
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni l'ID del torneo
        $tournament_id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
        
        if (empty($tournament_id)) {
            wp_send_json_error(['message' => __('ID torneo non valido.', 'eto')]);
        }
        
        // Ottieni il torneo
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            wp_send_json_error(['message' => __('Torneo non trovato.', 'eto')]);
        }
        
        // Ottieni i dati del torneo
        $tournament_data = $tournament->get_data();
        
        // Ottieni i team iscritti
        $teams = $tournament->get_teams();
        
        // Ottieni i match
        $matches = $tournament->get_matches();
        
        wp_send_json_success([
            'tournament' => $tournament_data,
            'teams' => $teams,
            'matches' => $matches
        ]);
    }
    
    /**
     * AJAX: Genera i match di un torneo
     */
    public function ajax_generate_matches() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_generate_matches', 'eto_nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza: token di verifica non valido.', 'eto')]);
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni l'ID del torneo
        $tournament_id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
        
        if (empty($tournament_id)) {
            wp_send_json_error(['message' => __('ID torneo non valido.', 'eto')]);
        }
        
        // Ottieni il torneo
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            wp_send_json_error(['message' => __('Torneo non trovato.', 'eto')]);
        }
        
        // Verifica se il torneo ha già dei match
        $existing_matches = $tournament->get_matches();
        
        if (!empty($existing_matches)) {
            wp_send_json_error(['message' => __('Il torneo ha già dei match generati.', 'eto')]);
        }
        
        // Ottieni i team iscritti
        $teams = $tournament->get_teams();
        
        if (count($teams) < 2) {
            wp_send_json_error(['message' => __('Il torneo deve avere almeno 2 team iscritti.', 'eto')]);
        }
        
        // Genera i match in base al formato del torneo
        $format = $tournament->get('format');
        $matches = [];
        
        switch ($format) {
            case 'single_elimination':
                $matches = $this->generate_single_elimination_matches($tournament_id, $teams);
                break;
                
            case 'double_elimination':
                $matches = $this->generate_double_elimination_matches($tournament_id, $teams);
                break;
                
            case 'round_robin':
                $matches = $this->generate_round_robin_matches($tournament_id, $teams);
                break;
                
            case 'swiss':
                $matches = $this->generate_swiss_matches($tournament_id, $teams);
                break;
                
            default:
                wp_send_json_error(['message' => __('Formato torneo non supportato.', 'eto')]);
                break;
        }
        
        if (empty($matches)) {
            wp_send_json_error(['message' => __('Errore nella generazione dei match.', 'eto')]);
        }
        
        // Aggiorna lo stato del torneo
        $tournament->set('status', 'active');
        $tournament->save();
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'tournament_matches_generated',
                sprintf(__('Match generati per il torneo ID %d', 'eto'), $tournament_id),
                ['tournament_id' => $tournament_id, 'matches_count' => count($matches)]
            );
        }
        
        wp_send_json_success([
            'message' => __('Match generati con successo.', 'eto'),
            'matches' => $matches
        ]);
    }
    
    /**
     * AJAX: Aggiorna lo stato di un torneo
     */
    public function ajax_update_tournament_status() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_update_tournament_status', 'eto_nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza: token di verifica non valido.', 'eto')]);
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni l'ID del torneo
        $tournament_id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
        
        if (empty($tournament_id)) {
            wp_send_json_error(['message' => __('ID torneo non valido.', 'eto')]);
        }
        
        // Ottieni il nuovo stato
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (empty($status)) {
            wp_send_json_error(['message' => __('Stato non valido.', 'eto')]);
        }
        
        // Verifica se lo stato è valido
        $valid_statuses = ['pending', 'active', 'completed', 'cancelled'];
        
        if (!in_array($status, $valid_statuses)) {
            wp_send_json_error(['message' => __('Stato non valido.', 'eto')]);
        }
        
        // Ottieni il torneo
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            wp_send_json_error(['message' => __('Torneo non trovato.', 'eto')]);
        }
        
        // Aggiorna lo stato del torneo
        $tournament->set('status', $status);
        $result = $tournament->save();
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore nell\'aggiornamento dello stato del torneo.', 'eto')]);
        }
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'tournament_status_updated',
                sprintf(__('Stato del torneo ID %d aggiornato a "%s"', 'eto'), $tournament_id, $status),
                ['tournament_id' => $tournament_id, 'status' => $status]
            );
        }
        
        wp_send_json_success([
            'message' => __('Stato del torneo aggiornato con successo.', 'eto')
        ]);
    }
    
    /**
     * Genera i match per un torneo a eliminazione singola
     *
     * @param int $tournament_id ID del torneo
     * @param array $teams Array di team
     * @return array Array di match
     */
    private function generate_single_elimination_matches($tournament_id, $teams) {
        $matches = [];
        $num_teams = count($teams);
        
        // Calcola il numero di round necessari
        $num_rounds = ceil(log($num_teams, 2));
        
        // Calcola il numero di match nel primo round
        $num_first_round_matches = pow(2, $num_rounds - 1);
        
        // Calcola il numero di bye
        $num_byes = $num_first_round_matches * 2 - $num_teams;
        
        // Mescola i team
        shuffle($teams);
        
        // Crea i match del primo round
        $match_number = 1;
        $team_index = 0;
        
        for ($i = 0; $i < $num_first_round_matches; $i++) {
            $team1_id = ($team_index < $num_teams) ? $teams[$team_index]['id'] : 0;
            $team_index++;
            
            $team2_id = ($team_index < $num_teams) ? $teams[$team_index]['id'] : 0;
            $team_index++;
            
            // Se entrambi i team sono validi, crea il match
            if ($team1_id > 0 && $team2_id > 0) {
                $match = new ETO_Match_Model();
                $match->set('tournament_id', $tournament_id);
                $match->set('team1_id', $team1_id);
                $match->set('team2_id', $team2_id);
                $match->set('round', 1);
                $match->set('match_number', $match_number);
                $match->set('status', 'pending');
                $match->set('scheduled_date', date('Y-m-d H:i:s', strtotime('+1 week')));
                
                $match_id = $match->save();
                
                if ($match_id) {
                    $matches[] = $match->get_data();
                }
                
                $match_number++;
            }
            // Se uno dei team è bye, l'altro avanza automaticamente
            elseif ($team1_id > 0 || $team2_id > 0) {
                $winner_id = ($team1_id > 0) ? $team1_id : $team2_id;
                
                // Crea un match vuoto per il bye
                $match = new ETO_Match_Model();
                $match->set('tournament_id', $tournament_id);
                $match->set('team1_id', ($team1_id > 0) ? $team1_id : 0);
                $match->set('team2_id', ($team2_id > 0) ? $team2_id : 0);
                $match->set('round', 1);
                $match->set('match_number', $match_number);
                $match->set('status', 'completed');
                $match->set('winner_id', $winner_id);
                $match->set('scheduled_date', date('Y-m-d H:i:s'));
                
                $match_id = $match->save();
                
                if ($match_id) {
                    $matches[] = $match->get_data();
                }
                
                $match_number++;
            }
        }
        
        return $matches;
    }
    
    /**
     * Genera i match per un torneo a eliminazione doppia
     *
     * @param int $tournament_id ID del torneo
     * @param array $teams Array di team
     * @return array Array di match
     */
    private function generate_double_elimination_matches($tournament_id, $teams) {
        // Implementazione semplificata: genera solo i match del primo round
        // La logica completa richiederebbe la gestione del bracket dei perdenti
        return $this->generate_single_elimination_matches($tournament_id, $teams);
    }
    
    /**
     * Genera i match per un torneo round robin
     *
     * @param int $tournament_id ID del torneo
     * @param array $teams Array di team
     * @return array Array di match
     */
    private function generate_round_robin_matches($tournament_id, $teams) {
        $matches = [];
        $num_teams = count($teams);
        
        // Algoritmo round robin
        for ($i = 0; $i < $num_teams; $i++) {
            for ($j = $i + 1; $j < $num_teams; $j++) {
                $match = new ETO_Match_Model();
                $match->set('tournament_id', $tournament_id);
                $match->set('team1_id', $teams[$i]['id']);
                $match->set('team2_id', $teams[$j]['id']);
                $match->set('round', 1);
                $match->set('match_number', count($matches) + 1);
                $match->set('status', 'pending');
                $match->set('scheduled_date', date('Y-m-d H:i:s', strtotime('+1 week')));
                
                $match_id = $match->save();
                
                if ($match_id) {
                    $matches[] = $match->get_data();
                }
            }
        }
        
        return $matches;
    }
    
    /**
     * Genera i match per un torneo Swiss
     *
     * @param int $tournament_id ID del torneo
     * @param array $teams Array di team
     * @return array Array di match
     */
    private function generate_swiss_matches($tournament_id, $teams) {
        $matches = [];
        $num_teams = count($teams);
        
        // Mescola i team
        shuffle($teams);
        
        // Crea i match del primo round
        for ($i = 0; $i < $num_teams; $i += 2) {
            if ($i + 1 < $num_teams) {
                $match = new ETO_Match_Model();
                $match->set('tournament_id', $tournament_id);
                $match->set('team1_id', $teams[$i]['id']);
                $match->set('team2_id', $teams[$i + 1]['id']);
                $match->set('round', 1);
                $match->set('match_number', ($i / 2) + 1);
                $match->set('status', 'pending');
                $match->set('scheduled_date', date('Y-m-d H:i:s', strtotime('+1 week')));
                
                $match_id = $match->save();
                
                if ($match_id) {
                    $matches[] = $match->get_data();
                }
            }
        }
        
        return $matches;
    }
    
    /**
     * Ottiene i giochi disponibili
     *
     * @return array Array di giochi
     */
    private function get_available_games() {
        return [
            'lol' => __('League of Legends', 'eto'),
            'valorant' => __('Valorant', 'eto'),
            'csgo' => __('Counter-Strike: Global Offensive', 'eto'),
            'dota2' => __('Dota 2', 'eto'),
            'overwatch' => __('Overwatch', 'eto'),
            'fortnite' => __('Fortnite', 'eto'),
            'pubg' => __('PUBG', 'eto'),
            'rocketleague' => __('Rocket League', 'eto'),
            'hearthstone' => __('Hearthstone', 'eto'),
            'other' => __('Altro', 'eto')
        ];
    }
    
    /**
     * Ottiene i formati di torneo disponibili
     *
     * @return array Array di formati
     */
    private function get_available_formats() {
        return [
            'single_elimination' => __('Eliminazione Singola', 'eto'),
            'double_elimination' => __('Eliminazione Doppia', 'eto'),
            'round_robin' => __('Round Robin', 'eto'),
            'swiss' => __('Sistema Svizzero', 'eto')
        ];
    }
}

// Inizializza il controller
$tournament_controller = new ETO_Tournament_Controller();
