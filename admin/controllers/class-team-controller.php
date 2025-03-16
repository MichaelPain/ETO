<?php
/**
 * Controller per la gestione dell'amministrazione dei team
 * 
 * Gestisce tutte le funzionalità relative ai team nel pannello di amministrazione
 * 
 * @package ETO
 * @subpackage Controllers
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Team_Controller {
    
    /**
     * Istanza del modello team
     *
     * @var ETO_Team_Model
     */
    private $team_model;
    
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
        $this->team_model = new ETO_Team_Model();
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
            __('Team', 'eto'),
            __('Team', 'eto'),
            'manage_options',
            'eto-teams',
            [$this, 'render_teams_page'],
            'dashicons-groups',
            31
        );
        
        add_submenu_page(
            'eto-teams',
            __('Tutti i Team', 'eto'),
            __('Tutti i Team', 'eto'),
            'manage_options',
            'eto-teams',
            [$this, 'render_teams_page']
        );
        
        add_submenu_page(
            'eto-teams',
            __('Aggiungi Nuovo', 'eto'),
            __('Aggiungi Nuovo', 'eto'),
            'manage_options',
            'eto-add-team',
            [$this, 'render_add_team_page']
        );
    }
    
    /**
     * Registra gli AJAX handler
     */
    private function register_ajax_handlers() {
        // AJAX per la creazione di un team
        add_action('wp_ajax_eto_create_team', [$this, 'ajax_create_team']);
        
        // AJAX per l'aggiornamento di un team
        add_action('wp_ajax_eto_update_team', [$this, 'ajax_update_team']);
        
        // AJAX per l'eliminazione di un team
        add_action('wp_ajax_eto_delete_team', [$this, 'ajax_delete_team']);
        
        // AJAX per il caricamento dei dati di un team
        add_action('wp_ajax_eto_load_team', [$this, 'ajax_load_team']);
        
        // AJAX per l'aggiunta di un membro al team
        add_action('wp_ajax_eto_add_team_member', [$this, 'ajax_add_team_member']);
        
        // AJAX per la rimozione di un membro dal team
        add_action('wp_ajax_eto_remove_team_member', [$this, 'ajax_remove_team_member']);
        
        // AJAX per la promozione di un membro a capitano
        add_action('wp_ajax_eto_promote_team_member', [$this, 'ajax_promote_team_member']);
    }
    
    /**
     * Registra i meta box
     */
    public function register_meta_boxes() {
        add_meta_box(
            'eto-team-details',
            __('Dettagli Team', 'eto'),
            [$this, 'render_team_details_meta_box'],
            'eto-team',
            'normal',
            'high'
        );
        
        add_meta_box(
            'eto-team-members',
            __('Membri', 'eto'),
            [$this, 'render_team_members_meta_box'],
            'eto-team',
            'normal',
            'default'
        );
        
        add_meta_box(
            'eto-team-tournaments',
            __('Tornei', 'eto'),
            [$this, 'render_team_tournaments_meta_box'],
            'eto-team',
            'normal',
            'default'
        );
    }
    
    /**
     * Renderizza la pagina dei team
     */
    public function render_teams_page() {
        // Ottieni i parametri di paginazione
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        // Ottieni i parametri di filtro
        $game = isset($_GET['game']) ? sanitize_text_field($_GET['game']) : '';
        
        // Ottieni i team
        $args = [
            'limit' => $per_page,
            'offset' => $offset
        ];
        
        if (!empty($game)) {
            $args['game'] = $game;
        }
        
        $teams = ETO_Team_Model::get_all($args);
        $total_teams = ETO_Team_Model::count_all($args);
        
        // Calcola la paginazione
        $total_pages = ceil($total_teams / $per_page);
        
        // Renderizza la vista
        include ETO_PLUGIN_DIR . '/admin/views/teams/list.php';
    }
    
    /**
     * Renderizza la pagina di aggiunta di un team
     */
    public function render_add_team_page() {
        // Ottieni i giochi disponibili
        $games = $this->get_available_games();
        
        // Renderizza la vista
        include ETO_PLUGIN_DIR . '/admin/views/teams/add.php';
    }
    
    /**
     * Renderizza la pagina di modifica di un team
     */
    public function render_edit_team_page() {
        // Ottieni l'ID del team
        $team_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (empty($team_id)) {
            wp_die(__('ID team non valido', 'eto'));
        }
        
        // Ottieni il team
        $team = ETO_Team_Model::get_by_id($team_id);
        
        if (!$team) {
            wp_die(__('Team non trovato', 'eto'));
        }
        
        // Ottieni i giochi disponibili
        $games = $this->get_available_games();
        
        // Renderizza la vista
        include ETO_PLUGIN_DIR . '/admin/views/teams/edit.php';
    }
    
    /**
     * Renderizza il meta box dei dettagli del team
     *
     * @param WP_Post $post Post corrente
     */
    public function render_team_details_meta_box($post) {
        // Ottieni il team
        $team_id = $post->ID;
        $team = ETO_Team_Model::get_by_id($team_id);
        
        if (!$team) {
            echo '<p>' . __('Team non trovato', 'eto') . '</p>';
            return;
        }
        
        // Ottieni i giochi disponibili
        $games = $this->get_available_games();
        
        // Renderizza la vista
        include ETO_PLUGIN_DIR . '/admin/views/teams/meta-box-details.php';
    }
    
    /**
     * Renderizza il meta box dei membri del team
     *
     * @param WP_Post $post Post corrente
     */
    public function render_team_members_meta_box($post) {
        // Ottieni il team
        $team_id = $post->ID;
        $team = ETO_Team_Model::get_by_id($team_id);
        
        if (!$team) {
            echo '<p>' . __('Team non trovato', 'eto') . '</p>';
            return;
        }
        
        // Ottieni i membri
        $members = $team->get_members();
        
        // Renderizza la vista
        include ETO_PLUGIN_DIR . '/admin/views/teams/meta-box-members.php';
    }
    
    /**
     * Renderizza il meta box dei tornei del team
     *
     * @param WP_Post $post Post corrente
     */
    public function render_team_tournaments_meta_box($post) {
        // Ottieni il team
        $team_id = $post->ID;
        $team = ETO_Team_Model::get_by_id($team_id);
        
        if (!$team) {
            echo '<p>' . __('Team non trovato', 'eto') . '</p>';
            return;
        }
        
        // Ottieni i tornei
        $tournaments = $team->get_tournaments();
        
        // Renderizza la vista
        include ETO_PLUGIN_DIR . '/admin/views/teams/meta-box-tournaments.php';
    }
    
    /**
     * AJAX: Crea un nuovo team
     */
    public function ajax_create_team() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_create_team', 'eto_nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza: token di verifica non valido.', 'eto')]);
        }
        
        // Verifica i permessi - Modificato per consentire l'accesso a più ruoli
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni i dati del form
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        $game = isset($_POST['game']) ? sanitize_text_field($_POST['game']) : '';
        $captain_id = isset($_POST['captain_id']) ? intval($_POST['captain_id']) : 0;
        $logo_url = isset($_POST['logo_url']) ? esc_url_raw($_POST['logo_url']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $website = isset($_POST['website']) ? esc_url_raw($_POST['website']) : '';
        $social_media = isset($_POST['social_media']) ? $_POST['social_media'] : [];
        
        // Sanitizza i social media
        $sanitized_social_media = [];
        foreach ($social_media as $key => $value) {
            $sanitized_social_media[$key] = sanitize_text_field($value);
        }
        
        // Valida i dati
        if (empty($name)) {
            wp_send_json_error(['message' => __('Il nome del team è obbligatorio.', 'eto')]);
        }
        
        if (empty($game)) {
            wp_send_json_error(['message' => __('Il gioco è obbligatorio.', 'eto')]);
        }
        
        if (empty($captain_id)) {
            wp_send_json_error(['message' => __('Il capitano è obbligatorio.', 'eto')]);
        }
        
        // Crea il team
        $team_data = [
            'name' => $name,
            'description' => $description,
            'game' => $game,
            'captain_id' => $captain_id,
            'logo_url' => $logo_url,
            'email' => $email,
            'website' => $website,
            'social_media' => maybe_serialize($sanitized_social_media),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ];
        
        $team_id = ETO_Team_Model::create($team_data);
        
        if (!$team_id) {
            wp_send_json_error(['message' => __('Errore durante la creazione del team.', 'eto')]);
        }
        
        // Aggiungi il capitano come membro
        ETO_Team_Model::add_member($team_id, $captain_id, 'captain');
        
        // Invia la risposta
        wp_send_json_success([
            'message' => __('Team creato con successo.', 'eto'),
            'team_id' => $team_id,
            'redirect' => admin_url('admin.php?page=eto-teams')
        ]);
    }
    
    /**
     * AJAX: Aggiorna un team
     */
    public function ajax_update_team() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_update_team', 'eto_nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza: token di verifica non valido.', 'eto')]);
        }
        
        // Verifica i permessi - Modificato per consentire l'accesso a più ruoli
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni l'ID del team
        $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
        
        if (empty($team_id)) {
            wp_send_json_error(['message' => __('ID team non valido.', 'eto')]);
        }
        
        // Ottieni il team
        $team = ETO_Team_Model::get_by_id($team_id);
        
        if (!$team) {
            wp_send_json_error(['message' => __('Team non trovato.', 'eto')]);
        }
        
        // Verifica i permessi specifici
        $user_id = get_current_user_id();
        if ($team->get('captain_id') != $user_id && $team->get('created_by') != $user_id && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Non hai i permessi per modificare questo team.', 'eto')]);
        }
        
        // Ottieni i dati del form
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        $game = isset($_POST['game']) ? sanitize_text_field($_POST['game']) : '';
        $captain_id = isset($_POST['captain_id']) ? intval($_POST['captain_id']) : 0;
        $logo_url = isset($_POST['logo_url']) ? esc_url_raw($_POST['logo_url']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $website = isset($_POST['website']) ? esc_url_raw($_POST['website']) : '';
        $social_media = isset($_POST['social_media']) ? $_POST['social_media'] : [];
        
        // Sanitizza i social media
        $sanitized_social_media = [];
        foreach ($social_media as $key => $value) {
            $sanitized_social_media[$key] = sanitize_text_field($value);
        }
        
        // Valida i dati
        if (empty($name)) {
            wp_send_json_error(['message' => __('Il nome del team è obbligatorio.', 'eto')]);
        }
        
        if (empty($game)) {
            wp_send_json_error(['message' => __('Il gioco è obbligatorio.', 'eto')]);
        }
        
        if (empty($captain_id)) {
            wp_send_json_error(['message' => __('Il capitano è obbligatorio.', 'eto')]);
        }
        
        // Aggiorna il team
        $team_data = [
            'name' => $name,
            'description' => $description,
            'game' => $game,
            'captain_id' => $captain_id,
            'logo_url' => $logo_url,
            'email' => $email,
            'website' => $website,
            'social_media' => maybe_serialize($sanitized_social_media),
            'updated_at' => current_time('mysql')
        ];
        
        $result = ETO_Team_Model::update($team_id, $team_data);
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore durante l\'aggiornamento del team.', 'eto')]);
        }
        
        // Invia la risposta
        wp_send_json_success([
            'message' => __('Team aggiornato con successo.', 'eto'),
            'team_id' => $team_id,
            'redirect' => admin_url('admin.php?page=eto-teams')
        ]);
    }
    
    /**
     * AJAX: Elimina un team
     */
    public function ajax_delete_team() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_delete_team', 'eto_nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza: token di verifica non valido.', 'eto')]);
        }
        
        // Verifica i permessi - Modificato per consentire l'accesso a più ruoli
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni l'ID del team
        $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
        
        if (empty($team_id)) {
            wp_send_json_error(['message' => __('ID team non valido.', 'eto')]);
        }
        
        // Ottieni il team
        $team = ETO_Team_Model::get_by_id($team_id);
        
        if (!$team) {
            wp_send_json_error(['message' => __('Team non trovato.', 'eto')]);
        }
        
        // Verifica i permessi specifici
        $user_id = get_current_user_id();
        if ($team->get('captain_id') != $user_id && $team->get('created_by') != $user_id && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Non hai i permessi per eliminare questo team.', 'eto')]);
        }
        
        // Elimina il team
        $result = ETO_Team_Model::delete($team_id);
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore durante l\'eliminazione del team.', 'eto')]);
        }
        
        // Invia la risposta
        wp_send_json_success([
            'message' => __('Team eliminato con successo.', 'eto'),
            'redirect' => admin_url('admin.php?page=eto-teams')
        ]);
    }
    
    /**
     * AJAX: Carica i dati di un team
     */
    public function ajax_load_team() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_load_team', 'eto_nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza: token di verifica non valido.', 'eto')]);
        }
        
        // Verifica i permessi - Modificato per consentire l'accesso a più ruoli
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni l'ID del team
        $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
        
        if (empty($team_id)) {
            wp_send_json_error(['message' => __('ID team non valido.', 'eto')]);
        }
        
        // Ottieni il team
        $team = ETO_Team_Model::get_by_id($team_id);
        
        if (!$team) {
            wp_send_json_error(['message' => __('Team non trovato.', 'eto')]);
        }
        
        // Invia la risposta
        wp_send_json_success([
            'team' => $team->to_array()
        ]);
    }
    
    /**
     * AJAX: Aggiungi un membro al team
     */
    public function ajax_add_team_member() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_add_team_member', 'eto_nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza: token di verifica non valido.', 'eto')]);
        }
        
        // Verifica i permessi - Modificato per consentire l'accesso a più ruoli
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni i dati
        $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : 'member';
        
        if (empty($team_id)) {
            wp_send_json_error(['message' => __('ID team non valido.', 'eto')]);
        }
        
        if (empty($user_id)) {
            wp_send_json_error(['message' => __('ID utente non valido.', 'eto')]);
        }
        
        // Ottieni il team
        $team = ETO_Team_Model::get_by_id($team_id);
        
        if (!$team) {
            wp_send_json_error(['message' => __('Team non trovato.', 'eto')]);
        }
        
        // Verifica i permessi specifici
        $current_user_id = get_current_user_id();
        if ($team->get('captain_id') != $current_user_id && $team->get('created_by') != $current_user_id && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Non hai i permessi per aggiungere membri a questo team.', 'eto')]);
        }
        
        // Verifica se l'utente è già membro
        if ($team->is_member($user_id)) {
            wp_send_json_error(['message' => __('L\'utente è già membro di questo team.', 'eto')]);
        }
        
        // Aggiungi il membro
        $result = ETO_Team_Model::add_member($team_id, $user_id, $role);
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore durante l\'aggiunta del membro.', 'eto')]);
        }
        
        // Invia la risposta
        wp_send_json_success([
            'message' => __('Membro aggiunto con successo.', 'eto')
        ]);
    }
    
    /**
     * AJAX: Rimuovi un membro dal team
     */
    public function ajax_remove_team_member() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_remove_team_member', 'eto_nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza: token di verifica non valido.', 'eto')]);
        }
        
        // Verifica i permessi - Modificato per consentire l'accesso a più ruoli
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni i dati
        $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (empty($team_id)) {
            wp_send_json_error(['message' => __('ID team non valido.', 'eto')]);
        }
        
        if (empty($user_id)) {
            wp_send_json_error(['message' => __('ID utente non valido.', 'eto')]);
        }
        
        // Ottieni il team
        $team = ETO_Team_Model::get_by_id($team_id);
        
        if (!$team) {
            wp_send_json_error(['message' => __('Team non trovato.', 'eto')]);
        }
        
        // Verifica i permessi specifici
        $current_user_id = get_current_user_id();
        if ($team->get('captain_id') != $current_user_id && $team->get('created_by') != $current_user_id && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Non hai i permessi per rimuovere membri da questo team.', 'eto')]);
        }
        
        // Verifica se l'utente è il capitano
        if ($team->get('captain_id') == $user_id) {
            wp_send_json_error(['message' => __('Non puoi rimuovere il capitano dal team.', 'eto')]);
        }
        
        // Rimuovi il membro
        $result = ETO_Team_Model::remove_member($team_id, $user_id);
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore durante la rimozione del membro.', 'eto')]);
        }
        
        // Invia la risposta
        wp_send_json_success([
            'message' => __('Membro rimosso con successo.', 'eto')
        ]);
    }
    
    /**
     * AJAX: Promuovi un membro a capitano
     */
    public function ajax_promote_team_member() {
        // Verifica il nonce
        if (!$this->security->verify_request_nonce('eto_promote_team_member', 'eto_nonce', false)) {
            wp_send_json_error(['message' => __('Errore di sicurezza: token di verifica non valido.', 'eto')]);
        }
        
        // Verifica i permessi - Modificato per consentire l'accesso a più ruoli
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni i dati
        $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (empty($team_id)) {
            wp_send_json_error(['message' => __('ID team non valido.', 'eto')]);
        }
        
        if (empty($user_id)) {
            wp_send_json_error(['message' => __('ID utente non valido.', 'eto')]);
        }
        
        // Ottieni il team
        $team = ETO_Team_Model::get_by_id($team_id);
        
        if (!$team) {
            wp_send_json_error(['message' => __('Team non trovato.', 'eto')]);
        }
        
        // Verifica i permessi specifici
        $current_user_id = get_current_user_id();
        if ($team->get('captain_id') != $current_user_id && $team->get('created_by') != $current_user_id && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Non hai i permessi per promuovere membri in questo team.', 'eto')]);
        }
        
        // Verifica se l'utente è già il capitano
        if ($team->get('captain_id') == $user_id) {
            wp_send_json_error(['message' => __('L\'utente è già il capitano di questo team.', 'eto')]);
        }
        
        // Verifica se l'utente è membro
        if (!$team->is_member($user_id)) {
            wp_send_json_error(['message' => __('L\'utente non è membro di questo team.', 'eto')]);
        }
        
        // Aggiorna il capitano
        $result = ETO_Team_Model::update($team_id, ['captain_id' => $user_id]);
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore durante la promozione del membro.', 'eto')]);
        }
        
        // Aggiorna il ruolo del membro
        ETO_Team_Model::update_member_role($team_id, $user_id, 'captain');
        
        // Aggiorna il ruolo del vecchio capitano
        ETO_Team_Model::update_member_role($team_id, $team->get('captain_id'), 'member');
        
        // Invia la risposta
        wp_send_json_success([
            'message' => __('Membro promosso a capitano con successo.', 'eto')
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
}
