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
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni i dati del form
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $game = isset($_POST['game']) ? sanitize_text_field($_POST['game']) : '';
        $logo_url = isset($_POST['logo_url']) ? esc_url_raw($_POST['logo_url']) : '';
        $captain_id = isset($_POST['captain_id']) ? intval($_POST['captain_id']) : 0;
        
        // Valida i dati
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = __('Il nome del team è obbligatorio.', 'eto');
        }
        
        if (empty($game)) {
            $errors['game'] = __('Il gioco è obbligatorio.', 'eto');
        }
        
        if (empty($captain_id)) {
            $errors['captain_id'] = __('Il capitano è obbligatorio.', 'eto');
        }
        
        if (!empty($errors)) {
            wp_send_json_error(['message' => __('Errore nella validazione dei dati.', 'eto'), 'errors' => $errors]);
        }
        
        // Crea il team
        $team = new ETO_Team_Model();
        $team->set('name', $name);
        $team->set('description', $description);
        $team->set('game', $game);
        $team->set('logo_url', $logo_url);
        $team->set('captain_id', $captain_id);
        $team->set('created_by', get_current_user_id());
        
        $team_id = $team->save();
        
        if (!$team_id) {
            wp_send_json_error(['message' => __('Errore nella creazione del team.', 'eto')]);
        }
        
        // Aggiungi il capitano come membro
        $team->add_member($captain_id, 'captain');
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'team_created',
                sprintf(__('Team "%s" creato', 'eto'), $name),
                ['team_id' => $team_id]
            );
        }
        
        wp_send_json_success([
            'message' => __('Team creato con successo.', 'eto'),
            'team_id' => $team_id
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
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
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
        
        // Ottieni i dati del form
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $game = isset($_POST['game']) ? sanitize_text_field($_POST['game']) : '';
        $logo_url = isset($_POST['logo_url']) ? esc_url_raw($_POST['logo_url']) : '';
        
        // Valida i dati
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = __('Il nome del team è obbligatorio.', 'eto');
        }
        
        if (empty($game)) {
            $errors['game'] = __('Il gioco è obbligatorio.', 'eto');
        }
        
        if (!empty($errors)) {
            wp_send_json_error(['message' => __('Errore nella validazione dei dati.', 'eto'), 'errors' => $errors]);
        }
        
        // Aggiorna il team
        $team->set('name', $name);
        $team->set('description', $description);
        $team->set('game', $game);
        $team->set('logo_url', $logo_url);
        
        $result = $team->save();
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore nell\'aggiornamento del team.', 'eto')]);
        }
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'team_updated',
                sprintf(__('Team "%s" aggiornato', 'eto'), $name),
                ['team_id' => $team_id]
            );
        }
        
        wp_send_json_success([
            'message' => __('Team aggiornato con successo.', 'eto'),
            'team_id' => $team_id
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
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
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
        
        // Elimina il team
        $result = $team->delete();
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore nell\'eliminazione del team.', 'eto')]);
        }
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'team_deleted',
                sprintf(__('Team ID %d eliminato', 'eto'), $team_id),
                ['team_id' => $team_id]
            );
        }
        
        wp_send_json_success([
            'message' => __('Team eliminato con successo.', 'eto')
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
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
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
        
        // Ottieni i dati del team
        $team_data = $team->get_data();
        
        // Ottieni i membri
        $members = $team->get_members();
        
        // Ottieni i tornei
        $tournaments = $team->get_tournaments();
        
        wp_send_json_success([
            'team' => $team_data,
            'members' => $members,
            'tournaments' => $tournaments
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
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni l'ID del team
        $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
        
        if (empty($team_id)) {
            wp_send_json_error(['message' => __('ID team non valido.', 'eto')]);
        }
        
        // Ottieni l'ID dell'utente
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (empty($user_id)) {
            wp_send_json_error(['message' => __('ID utente non valido.', 'eto')]);
        }
        
        // Ottieni il ruolo
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : 'member';
        
        // Ottieni il team
        $team = ETO_Team_Model::get_by_id($team_id);
        
        if (!$team) {
            wp_send_json_error(['message' => __('Team non trovato.', 'eto')]);
        }
        
        // Verifica se l'utente è già membro
        if ($team->is_member($user_id)) {
            wp_send_json_error(['message' => __('L\'utente è già membro del team.', 'eto')]);
        }
        
        // Aggiungi il membro
        $result = $team->add_member($user_id, $role);
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore nell\'aggiunta del membro.', 'eto')]);
        }
        
        // Ottieni i dati dell'utente
        $user = ETO_User_Model::get_by_id($user_id);
        
        if (!$user) {
            wp_send_json_error(['message' => __('Utente non trovato.', 'eto')]);
        }
        
        $user_data = $user->get_data();
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'team_member_added',
                sprintf(__('Utente "%s" aggiunto al team ID %d con ruolo "%s"', 'eto'), $user_data['display_name'], $team_id, $role),
                [
                    'team_id' => $team_id,
                    'user_id' => $user_id,
                    'role' => $role
                ]
            );
        }
        
        wp_send_json_success([
            'message' => __('Membro aggiunto con successo.', 'eto'),
            'member' => [
                'user_id' => $user_id,
                'display_name' => $user_data['display_name'],
                'role' => $role
            ]
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
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni l'ID del team
        $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
        
        if (empty($team_id)) {
            wp_send_json_error(['message' => __('ID team non valido.', 'eto')]);
        }
        
        // Ottieni l'ID dell'utente
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (empty($user_id)) {
            wp_send_json_error(['message' => __('ID utente non valido.', 'eto')]);
        }
        
        // Ottieni il team
        $team = ETO_Team_Model::get_by_id($team_id);
        
        if (!$team) {
            wp_send_json_error(['message' => __('Team non trovato.', 'eto')]);
        }
        
        // Verifica se l'utente è membro
        if (!$team->is_member($user_id)) {
            wp_send_json_error(['message' => __('L\'utente non è membro del team.', 'eto')]);
        }
        
        // Verifica se l'utente è il capitano
        if ($team->get('captain_id') == $user_id) {
            wp_send_json_error(['message' => __('Non puoi rimuovere il capitano. Promuovi prima un altro membro a capitano.', 'eto')]);
        }
        
        // Rimuovi il membro
        $result = $team->remove_member($user_id);
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore nella rimozione del membro.', 'eto')]);
        }
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'team_member_removed',
                sprintf(__('Utente ID %d rimosso dal team ID %d', 'eto'), $user_id, $team_id),
                [
                    'team_id' => $team_id,
                    'user_id' => $user_id
                ]
            );
        }
        
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
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'eto')]);
        }
        
        // Ottieni l'ID del team
        $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
        
        if (empty($team_id)) {
            wp_send_json_error(['message' => __('ID team non valido.', 'eto')]);
        }
        
        // Ottieni l'ID dell'utente
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (empty($user_id)) {
            wp_send_json_error(['message' => __('ID utente non valido.', 'eto')]);
        }
        
        // Ottieni il team
        $team = ETO_Team_Model::get_by_id($team_id);
        
        if (!$team) {
            wp_send_json_error(['message' => __('Team non trovato.', 'eto')]);
        }
        
        // Verifica se l'utente è membro
        if (!$team->is_member($user_id)) {
            wp_send_json_error(['message' => __('L\'utente non è membro del team.', 'eto')]);
        }
        
        // Ottieni il capitano attuale
        $current_captain_id = $team->get('captain_id');
        
        // Verifica se l'utente è già il capitano
        if ($current_captain_id == $user_id) {
            wp_send_json_error(['message' => __('L\'utente è già il capitano del team.', 'eto')]);
        }
        
        // Aggiorna il ruolo del membro attuale
        $team->update_member_role($user_id, 'captain');
        
        // Aggiorna il ruolo del capitano precedente
        if ($current_captain_id > 0) {
            $team->update_member_role($current_captain_id, 'member');
        }
        
        // Aggiorna il capitano del team
        $team->set('captain_id', $user_id);
        $result = $team->save();
        
        if (!$result) {
            wp_send_json_error(['message' => __('Errore nella promozione del membro.', 'eto')]);
        }
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'team_captain_changed',
                sprintf(__('Utente ID %d promosso a capitano del team ID %d', 'eto'), $user_id, $team_id),
                [
                    'team_id' => $team_id,
                    'user_id' => $user_id,
                    'previous_captain_id' => $current_captain_id
                ]
            );
        }
        
        wp_send_json_success([
            'message' => __('Membro promosso a capitano con successo.', 'eto')
        ]);
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
}

// Inizializza il controller
$team_controller = new ETO_Team_Controller();