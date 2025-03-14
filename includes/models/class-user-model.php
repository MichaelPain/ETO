<?php
/**
 * Classe modello per gli utenti
 * 
 * Gestisce l'accesso ai dati e la logica di business per gli utenti
 * 
 * @package ETO
 * @subpackage Models
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_User_Model {
    
    /**
     * ID dell'utente
     *
     * @var int
     */
    private $id;
    
    /**
     * Dati dell'utente
     *
     * @var array
     */
    private $data;
    
    /**
     * Istanza del database query
     *
     * @var ETO_DB_Query
     */
    private $db_query;
    
    /**
     * Costruttore
     *
     * @param int $id ID dell'utente (opzionale)
     */
    public function __construct($id = 0) {
        $this->db_query = new ETO_DB_Query();
        
        if ($id > 0) {
            $this->id = absint($id);
            $this->load();
        } elseif ($id === 0) {
            // Utente corrente
            $current_user = wp_get_current_user();
            if ($current_user->ID > 0) {
                $this->id = $current_user->ID;
                $this->load();
            }
        }
    }
    
    /**
     * Carica i dati dell'utente
     *
     * @return bool True se il caricamento è riuscito, false altrimenti
     */
    public function load() {
        if (empty($this->id)) {
            return false;
        }
        
        $user = get_userdata($this->id);
        
        if (!$user) {
            return false;
        }
        
        $this->data = [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name,
            'roles' => $user->roles,
            'registered' => $user->user_registered
        ];
        
        // Carica i metadati specifici del plugin
        $this->load_meta();
        
        return true;
    }
    
    /**
     * Carica i metadati dell'utente
     *
     * @return void
     */
    private function load_meta() {
        if (empty($this->id)) {
            return;
        }
        
        // Metadati specifici del plugin
        $meta_keys = [
            'eto_game_id',
            'eto_discord_id',
            'eto_twitch_id',
            'eto_preferred_game',
            'eto_notifications'
        ];
        
        foreach ($meta_keys as $key) {
            $value = get_user_meta($this->id, $key, true);
            if (!empty($value)) {
                $this->data[$key] = $value;
            }
        }
    }
    
    /**
     * Salva i dati dell'utente
     *
     * @return bool True se il salvataggio è riuscito, false altrimenti
     */
    public function save() {
        if (empty($this->id) || empty($this->data)) {
            return false;
        }
        
        $user_data = [];
        
        // Campi standard di WordPress
        if (isset($this->data['email'])) {
            $user_data['user_email'] = $this->data['email'];
        }
        
        if (isset($this->data['first_name'])) {
            $user_data['first_name'] = $this->data['first_name'];
        }
        
        if (isset($this->data['last_name'])) {
            $user_data['last_name'] = $this->data['last_name'];
        }
        
        if (isset($this->data['display_name'])) {
            $user_data['display_name'] = $this->data['display_name'];
        }
        
        // Aggiorna i dati dell'utente
        if (!empty($user_data)) {
            $user_data['ID'] = $this->id;
            $result = wp_update_user($user_data);
            
            if (is_wp_error($result)) {
                return false;
            }
        }
        
        // Aggiorna i metadati
        $meta_keys = [
            'eto_game_id',
            'eto_discord_id',
            'eto_twitch_id',
            'eto_preferred_game',
            'eto_notifications'
        ];
        
        foreach ($meta_keys as $key) {
            if (isset($this->data[$key])) {
                update_user_meta($this->id, $key, $this->data[$key]);
            }
        }
        
        return true;
    }
    
    /**
     * Imposta un valore nel modello
     *
     * @param string $key Chiave
     * @param mixed $value Valore
     * @return void
     */
    public function set($key, $value) {
        if (!is_array($this->data)) {
            $this->data = [];
        }
        
        $this->data[$key] = $value;
    }
    
    /**
     * Ottiene un valore dal modello
     *
     * @param string $key Chiave
     * @param mixed $default Valore predefinito
     * @return mixed Valore
     */
    public function get($key, $default = null) {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        
        return $default;
    }
    
    /**
     * Ottiene l'ID dell'utente
     *
     * @return int ID dell'utente
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Ottiene tutti i dati dell'utente
     *
     * @return array Dati dell'utente
     */
    public function get_data() {
        return $this->data;
    }
    
    /**
     * Imposta tutti i dati dell'utente
     *
     * @param array $data Dati dell'utente
     * @return void
     */
    public function set_data($data) {
        $this->data = $data;
    }
    
    /**
     * Ottiene il nome completo dell'utente
     *
     * @return string Nome completo
     */
    public function get_full_name() {
        if (empty($this->data)) {
            return '';
        }
        
        $first_name = $this->get('first_name', '');
        $last_name = $this->get('last_name', '');
        
        if (!empty($first_name) && !empty($last_name)) {
            return $first_name . ' ' . $last_name;
        } elseif (!empty($first_name)) {
            return $first_name;
        } elseif (!empty($last_name)) {
            return $last_name;
        }
        
        return $this->get('display_name', '');
    }
    
    /**
     * Verifica se l'utente ha un ruolo specifico
     *
     * @param string $role Ruolo da verificare
     * @return bool True se l'utente ha il ruolo, false altrimenti
     */
    public function has_role($role) {
        if (empty($this->data) || empty($this->data['roles'])) {
            return false;
        }
        
        return in_array($role, $this->data['roles']);
    }
    
    /**
     * Aggiunge un ruolo all'utente
     *
     * @param string $role Ruolo da aggiungere
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function add_role($role) {
        if (empty($this->id)) {
            return false;
        }
        
        $user = get_user_by('id', $this->id);
        
        if (!$user) {
            return false;
        }
        
        $user->add_role($role);
        
        // Aggiorna i dati locali
        $this->data['roles'] = $user->roles;
        
        return true;
    }
    
    /**
     * Rimuove un ruolo dall'utente
     *
     * @param string $role Ruolo da rimuovere
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function remove_role($role) {
        if (empty($this->id)) {
            return false;
        }
        
        $user = get_user_by('id', $this->id);
        
        if (!$user) {
            return false;
        }
        
        $user->remove_role($role);
        
        // Aggiorna i dati locali
        $this->data['roles'] = $user->roles;
        
        return true;
    }
    
    /**
     * Ottiene i team dell'utente
     *
     * @return array Array di team
     */
    public function get_teams() {
        if (empty($this->id)) {
            return [];
        }
        
        return ETO_Team_Model::get_user_teams($this->id);
    }
    
    /**
     * Ottiene i team capitanati dall'utente
     *
     * @return array Array di team
     */
    public function get_captain_teams() {
        if (empty($this->id)) {
            return [];
        }
        
        return ETO_Team_Model::get_user_captain_teams($this->id);
    }
    
    /**
     * Ottiene i tornei a cui l'utente è iscritto
     *
     * @return array Array di tornei
     */
    public function get_tournaments() {
        if (empty($this->id)) {
            return [];
        }
        
        global $wpdb;
        
        $teams_table = $wpdb->prefix . 'eto_teams';
        $members_table = $wpdb->prefix . 'eto_team_members';
        $entries_table = $wpdb->prefix . 'eto_tournament_entries';
        $tournaments_table = $wpdb->prefix . 'eto_tournaments';
        
        $query = $this->db_query->prepare(
            "SELECT DISTINCT t.* FROM $tournaments_table AS t
            JOIN $entries_table AS e ON t.id = e.tournament_id
            JOIN $teams_table AS tm ON e.team_id = tm.id
            JOIN $members_table AS m ON tm.id = m.team_id
            WHERE m.user_id = %d
            ORDER BY t.start_date ASC",
            $this->id
        );
        
        return $this->db_query->get_results($query);
    }
    
    /**
     * Ottiene i match dell'utente
     *
     * @param string $status Stato dei match (opzionale)
     * @return array Array di match
     */
    public function get_matches($status = '') {
        if (empty($this->id)) {
            return [];
        }
        
        global $wpdb;
        
        $teams_table = $wpdb->prefix . 'eto_teams';
        $members_table = $wpdb->prefix . 'eto_team_members';
        $matches_table = $wpdb->prefix . 'eto_matches';
        
        $query = "SELECT DISTINCT m.* FROM $matches_table AS m
                 JOIN $teams_table AS t1 ON m.team1_id = t1.id
                 JOIN $members_table AS mm1 ON t1.id = mm1.team_id
                 WHERE mm1.user_id = %d
                 UNION
                 SELECT DISTINCT m.* FROM $matches_table AS m
                 JOIN $teams_table AS t2 ON m.team2_id = t2.id
                 JOIN $members_table AS mm2 ON t2.id = mm2.team_id
                 WHERE mm2.user_id = %d";
        
        $query_args = [$this->id, $this->id];
        
        if (!empty($status)) {
            $query .= " AND m.status = %s";
            $query_args[] = $status;
        }
        
        $query .= " ORDER BY m.scheduled_date DESC";
        
        // Prepara la query
        $query = $this->db_query->prepare($query, ...$query_args);
        
        // Esegui la query
        return $this->db_query->get_results($query);
    }
    
    /**
     * Verifica se l'utente può modificare un team
     *
     * @param int $team_id ID del team
     * @return bool True se l'utente può modificare il team, false altrimenti
     */
    public function can_edit_team($team_id) {
        if (empty($this->id) || empty($team_id)) {
            return false;
        }
        
        // Gli amministratori possono modificare tutti i team
        if ($this->has_role('administrator')) {
            return true;
        }
        
        $team = ETO_Team_Model::get_by_id($team_id);
        
        if (!$team) {
            return false;
        }
        
        // Il capitano può modificare il team
        return $team->get('captain_id') == $this->id;
    }
    
    /**
     * Verifica se l'utente può modificare un torneo
     *
     * @param int $tournament_id ID del torneo
     * @return bool True se l'utente può modificare il torneo, false altrimenti
     */
    public function can_edit_tournament($tournament_id) {
        if (empty($this->id) || empty($tournament_id)) {
            return false;
        }
        
        // Gli amministratori possono modificare tutti i tornei
        if ($this->has_role('administrator')) {
            return true;
        }
        
        // Gli organizzatori possono modificare tutti i tornei
        if ($this->has_role('tournament_organizer')) {
            return true;
        }
        
        $tournament = ETO_Tournament_Model::get_by_id($tournament_id);
        
        if (!$tournament) {
            return false;
        }
        
        // Il creatore può modificare il torneo
        return $tournament->get('created_by') == $this->id;
    }
    
    /**
     * Ottiene un utente per ID
     *
     * @param int $id ID dell'utente
     * @return ETO_User_Model|false Istanza del modello o false se non trovato
     */
    public static function get_by_id($id) {
        $user = new self($id);
        
        if ($user->get_id() > 0) {
            return $user;
        }
        
        return false;
    }
    
    /**
     * Ottiene un utente per email
     *
     * @param string $email Email dell'utente
     * @return ETO_User_Model|false Istanza del modello o false se non trovato
     */
    public static function get_by_email($email) {
        $user = get_user_by('email', $email);
        
        if (!$user) {
            return false;
        }
        
        return self::get_by_id($user->ID);
    }
    
    /**
     * Ottiene un utente per username
     *
     * @param string $username Username dell'utente
     * @return ETO_User_Model|false Istanza del modello o false se non trovato
     */
    public static function get_by_username($username) {
        $user = get_user_by('login', $username);
        
        if (!$user) {
            return false;
        }
        
        return self::get_by_id($user->ID);
    }
    
    /**
     * Ottiene l'utente corrente
     *
     * @return ETO_User_Model|false Istanza del modello o false se non loggato
     */
    public static function get_current() {
        $current_user = wp_get_current_user();
        
        if ($current_user->ID === 0) {
            return false;
        }
        
        return self::get_by_id($current_user->ID);
    }
    
    /**
     * Cerca utenti
     *
     * @param string $search Termine di ricerca
     * @param int $limit Limite di risultati
     * @return array Array di utenti
     */
    public static function search($search, $limit = 10) {
        $args = [
            'search' => '*' . $search . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'number' => $limit
        ];
        
        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        
        $result = [];
        
        foreach ($users as $user) {
            $user_model = self::get_by_id($user->ID);
            $result[] = $user_model->get_data();
        }
        
        return $result;
    }
    
    /**
     * Ottiene gli utenti con un ruolo specifico
     *
     * @param string $role Ruolo
     * @param int $limit Limite di risultati
     * @return array Array di utenti
     */
    public static function get_by_role($role, $limit = 0) {
        $args = [
            'role' => $role
        ];
        
        if ($limit > 0) {
            $args['number'] = $limit;
        }
        
        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        
        $result = [];
        
        foreach ($users as $user) {
            $user_model = self::get_by_id($user->ID);
            $result[] = $user_model->get_data();
        }
        
        return $result;
    }
    
    /**
     * Ottiene gli utenti con un metadato specifico
     *
     * @param string $meta_key Chiave del metadato
     * @param mixed $meta_value Valore del metadato
     * @param int $limit Limite di risultati
     * @return array Array di utenti
     */
    public static function get_by_meta($meta_key, $meta_value, $limit = 0) {
        $args = [
            'meta_key' => $meta_key,
            'meta_value' => $meta_value
        ];
        
        if ($limit > 0) {
            $args['number'] = $limit;
        }
        
        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        
        $result = [];
        
        foreach ($users as $user) {
            $user_model = self::get_by_id($user->ID);
            $result[] = $user_model->get_data();
        }
        
        return $result;
    }
}
