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
        // Commentiamo questa riga per evitare menu duplicati
        // add_action('admin_menu', [$this, 'register_admin_menu']);
        
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
        $categories = ETO_Tournament_Model::get_categories();
        
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
            wp_send_json_error(__('Errore di sicurezza: token di verifica non valido.', 'eto'));
        }
        
        // Verifica i permessi - Modificato per consentire l'accesso a più ruoli
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permessi insufficienti.', 'eto'));
        }
        
        // Ottieni i dati del form
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        $game = isset($_POST['game']) ? sanitize_text_field($_POST['game']) : '';
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : '';
        $featured_image = isset($_POST['featured_image']) ? esc_url_raw($_POST['featured_image']) : '';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $registration_start = isset($_POST['registration_start']) ? sanitize_text_field($_POST['registration_start']) : '';
        $registration_end = isset($_POST['registration_end']) ? sanitize_text_field($_POST['registration_end']) : '';
        $min_teams = isset($_POST['min_teams']) ? intval($_POST['min_teams']) : 2;
        $max_teams = isset($_POST['max_teams']) ? intval($_POST['max_teams']) : 16;
        $rules = isset($_POST['rules']) ? wp_kses_post($_POST['rules']) : '';
        $prizes = isset($_POST['prizes']) ? wp_kses_post($_POST['prizes']) : '';
        
        // Valida i dati
        if (empty($name)) {
            wp_send_json_error(__('Il nome del torneo è obbligatorio.', 'eto'));
        }
        
        if (empty($game)) {
            wp_send_json_error(__('Il gioco è obbligatorio.', 'eto'));
        }
        
        if (empty($format)) {
            wp_send_json_error(__('Il formato è obbligatorio.', 'eto'));
        }
        
        if (empty($start_date)) {
            wp_send_json_error(__('La data di inizio è obbligatoria.', 'eto'));
        }
        
        if (empty($end_date)) {
            wp_send_json_error(__('La data di fine è obbligatoria.', 'eto'));
        }
        
        // Crea il torneo
        $tournament_data = [
            'name' => $name,
            'description' => $description,
            'game' => $game,
            'format' => $format,
            'featured_image' => $featured_image,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'registration_start' => $registration_start,
            'registration_end' => $registration_end,
            'min_teams' => $min_teams,
            'max_teams' => $max_teams,
            'rules' => $rules,
            'prizes' => $prizes,
            'status' => 'draft',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ];
        
        $tournament_id = ETO_Tournament_Model::create($tournament_data);
        
        if (!$tournament_id) {
            wp_send_json_error(__('Errore durante la creazione del torneo.', 'eto'));
        }
        
        // Invia la risposta
        wp_send_json_success([
            'message' => __('Torneo creato con successo.', 'eto'),
            'tournament_id' => $tournament_id,
            'redirect' => admin_url('admin.php?page=eto-tournaments')
        ]);
    }
    
    /**
     * AJAX: Aggiorna un torneo
     */
    public function ajax_update_tournament() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_update_tournament', 'eto_nonce', false)) {
            wp_send_json_error(__('Errore di sicurezza: token di verifica non valido.', 'eto'));
        }
        
        // Verifica i permessi - Modificato per consentire l'accesso a più ruoli
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permessi insufficienti.', 'eto'));
        }
        
        // Ottieni l'ID del torneo
        $tournament_id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
        
        if (empty($tournament_id)) {
            wp_send_json_error(__('ID torneo non valido.', 'eto'));
        }
        
        // Ottieni il torneo
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            wp_send_json_error(__('Torneo non trovato.', 'eto'));
        }
        
        // Verifica i permessi specifici
        $user_id = get_current_user_id();
        if ($tournament->get('created_by') != $user_id && !current_user_can('manage_options')) {
            wp_send_json_error(__('Non hai i permessi per modificare questo torneo.', 'eto'));
        }
        
        // Ottieni i dati del form
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        $game = isset($_POST['game']) ? sanitize_text_field($_POST['game']) : '';
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : '';
        $featured_image = isset($_POST['featured_image']) ? esc_url_raw($_POST['featured_image']) : '';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $registration_start = isset($_POST['registration_start']) ? sanitize_text_field($_POST['registration_start']) : '';
        $registration_end = isset($_POST['registration_end']) ? sanitize_text_field($_POST['registration_end']) : '';
        $min_teams = isset($_POST['min_teams']) ? intval($_POST['min_teams']) : 2;
        $max_teams = isset($_POST['max_teams']) ? intval($_POST['max_teams']) : 16;
        $rules = isset($_POST['rules']) ? wp_kses_post($_POST['rules']) : '';
        $prizes = isset($_POST['prizes']) ? wp_kses_post($_POST['prizes']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'draft';
        
        // Valida i dati
        if (empty($name)) {
            wp_send_json_error(__('Il nome del torneo è obbligatorio.', 'eto'));
        }
        
        if (empty($game)) {
            wp_send_json_error(__('Il gioco è obbligatorio.', 'eto'));
        }
        
        if (empty($format)) {
            wp_send_json_error(__('Il formato è obbligatorio.', 'eto'));
        }
        
        if (empty($start_date)) {
            wp_send_json_error(__('La data di inizio è obbligatoria.', 'eto'));
        }
        
        if (empty($end_date)) {
            wp_send_json_error(__('La data di fine è obbligatoria.', 'eto'));
        }
        
        // Aggiorna il torneo
        $tournament_data = [
            'name' => $name,
            'description' => $description,
            'game' => $game,
            'format' => $format,
            'featured_image' => $featured_image,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'registration_start' => $registration_start,
            'registration_end' => $registration_end,
            'min_teams' => $min_teams,
            'max_teams' => $max_teams,
            'rules' => $rules,
            'prizes' => $prizes,
            'status' => $status,
            'updated_at' => current_time('mysql')
        ];
        
        $result = ETO_Tournament_Model::update($tournament_id, $tournament_data);
        
        if (!$result) {
            wp_send_json_error(__('Errore durante l\'aggiornamento del torneo.', 'eto'));
        }
        
        // Invia la risposta
        wp_send_json_success([
            'message' => __('Torneo aggiornato con successo.', 'eto'),
            'tournament_id' => $tournament_id,
            'redirect' => admin_url('admin.php?page=eto-tournaments')
        ]);
    }
    
    /**
     * AJAX: Elimina un torneo
     */
    public function ajax_delete_tournament() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_delete_tournament', 'eto_nonce', false)) {
            wp_send_json_error(__('Errore di sicurezza: token di verifica non valido.', 'eto'));
        }
        
        // Verifica i permessi - Modificato per consentire l'accesso a più ruoli
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permessi insufficienti.', 'eto'));
        }
        
        // Ottieni l'ID del torneo
        $tournament_id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
        
        if (empty($tournament_id)) {
            wp_send_json_error(__('ID torneo non valido.', 'eto'));
        }
        
        // Ottieni il torneo
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            wp_send_json_error(__('Torneo non trovato.', 'eto'));
        }
        
        // Verifica i permessi specifici
        $user_id = get_current_user_id();
        if ($tournament->get('created_by') != $user_id && !current_user_can('manage_options')) {
            wp_send_json_error(__('Non hai i permessi per eliminare questo torneo.', 'eto'));
        }
        
        // Elimina il torneo
        $result = ETO_Tournament_Model::delete($tournament_id);
        
        if (!$result) {
            wp_send_json_error(__('Errore durante l\'eliminazione del torneo.', 'eto'));
        }
        
        // Invia la risposta
        wp_send_json_success([
            'message' => __('Torneo eliminato con successo.', 'eto'),
            'redirect' => admin_url('admin.php?page=eto-tournaments')
        ]);
    }
    
    /**
     * AJAX: Carica i dati di un torneo
     */
    public function ajax_load_tournament() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_load_tournament', 'eto_nonce', false)) {
            wp_send_json_error(__('Errore di sicurezza: token di verifica non valido.', 'eto'));
        }
        
        // Verifica i permessi - Modificato per consentire l'accesso a più ruoli
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permessi insufficienti.', 'eto'));
        }
        
        // Ottieni l'ID del torneo
        $tournament_id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
        
        if (empty($tournament_id)) {
            wp_send_json_error(__('ID torneo non valido.', 'eto'));
        }
        
        // Ottieni il torneo
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            wp_send_json_error(__('Torneo non trovato.', 'eto'));
        }
        
        // Invia la risposta
        wp_send_json_success([
            'tournament' => $tournament->to_array()
        ]);
    }
    
    /**
     * AJAX: Genera i match di un torneo
     */
    public function ajax_generate_matches() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_generate_matches', 'eto_nonce', false)) {
            wp_send_json_error(__('Errore di sicurezza: token di verifica non valido.', 'eto'));
        }
        
        // Verifica i permessi - Modificato per consentire l'accesso a più ruoli
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permessi insufficienti.', 'eto'));
        }
        
        // Ottieni l'ID del torneo
        $tournament_id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
        
        if (empty($tournament_id)) {
            wp_send_json_error(__('ID torneo non valido.', 'eto'));
        }
        
        // Ottieni il torneo
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            wp_send_json_error(__('Torneo non trovato.', 'eto'));
        }
        
        // Verifica i permessi specifici
        $user_id = get_current_user_id();
        if ($tournament->get('created_by') != $user_id && !current_user_can('manage_options')) {
            wp_send_json_error(__('Non hai i permessi per generare i match di questo torneo.', 'eto'));
        }
        
        // Verifica se il torneo ha abbastanza team
        $teams = $tournament->get_teams();
        
        if (count($teams) < $tournament->get('min_teams')) {
            wp_send_json_error(__('Il torneo non ha abbastanza team iscritti.', 'eto'));
        }
        
        // Genera i match
        $result = $tournament->generate_matches();
        
        if (!$result) {
            wp_send_json_error(__('Errore durante la generazione dei match.', 'eto'));
        }
        
        // Aggiorna lo stato del torneo
        ETO_Tournament_Model::update($tournament_id, ['status' => 'active']);
        
        // Invia la risposta
        wp_send_json_success([
            'message' => __('Match generati con successo.', 'eto'),
            'redirect' => admin_url('admin.php?page=eto-tournaments&action=view&id=' . $tournament_id)
        ]);
    }
    
    /**
     * AJAX: Aggiorna lo stato di un torneo
     */
    public function ajax_update_tournament_status() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_update_tournament_status', 'eto_nonce', false)) {
            wp_send_json_error(__('Errore di sicurezza: token di verifica non valido.', 'eto'));
        }
        
        // Verifica i permessi - Modificato per consentire l'accesso a più ruoli
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permessi insufficienti.', 'eto'));
        }
        
        // Ottieni i dati
        $tournament_id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (empty($tournament_id)) {
            wp_send_json_error(__('ID torneo non valido.', 'eto'));
        }
        
        if (empty($status)) {
            wp_send_json_error(__('Stato non valido.', 'eto'));
        }
        
        // Ottieni il torneo
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            wp_send_json_error(__('Torneo non trovato.', 'eto'));
        }
        
        // Verifica i permessi specifici
        $user_id = get_current_user_id();
        if ($tournament->get('created_by') != $user_id && !current_user_can('manage_options')) {
            wp_send_json_error(__('Non hai i permessi per aggiornare lo stato di questo torneo.', 'eto'));
        }
        
        // Aggiorna lo stato
        $result = ETO_Tournament_Model::update($tournament_id, ['status' => $status]);
        
        if (!$result) {
            wp_send_json_error(__('Errore durante l\'aggiornamento dello stato.', 'eto'));
        }
        
        // Invia la risposta
        wp_send_json_success([
            'message' => __('Stato aggiornato con successo.', 'eto')
        ]);
    }
    
    /**
     * Ottiene i giochi disponibili
     * 
     * @return array Array associativo di giochi disponibili
     */
    private function get_available_games() {
        return [
            'lol' => __('League of Legends', 'eto'),
            'dota2' => __('Dota 2', 'eto'),
            'csgo' => __('CS:GO', 'eto'),
            'valorant' => __('Valorant', 'eto'),
            'fortnite' => __('Fortnite', 'eto'),
            'pubg' => __('PUBG', 'eto'),
            'rocketleague' => __('Rocket League', 'eto'),
            'overwatch' => __('Overwatch', 'eto'),
            'fifa' => __('FIFA', 'eto'),
            'other' => __('Altro', 'eto')
        ];
    }
    
    /**
     * Ottiene i formati di torneo disponibili
     * 
     * @return array Array associativo di formati disponibili
     */
    private function get_available_formats() {
        return [
            'single_elimination' => __('Eliminazione diretta', 'eto'),
            'double_elimination' => __('Doppia eliminazione', 'eto'),
            'round_robin' => __('Girone all\'italiana', 'eto'),
            'swiss' => __('Sistema svizzero', 'eto'),
            'custom' => __('Personalizzato', 'eto')
        ];
    }
}
