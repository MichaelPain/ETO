<?php
/**
 * Classe modello per i team
 * 
 * Gestisce l'accesso ai dati e la logica di business per i team
 * 
 * @package ETO
 * @subpackage Models
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Team_Model {
    
    /**
     * ID del team
     *
     * @var int
     */
    private $id;
    
    /**
     * Dati del team
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
     * Nome della tabella dei team
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Costruttore
     *
     * @param int $id ID del team (opzionale)
     */
    public function __construct($id = 0) {
        global $wpdb;
        
        $this->db_query = new ETO_DB_Query();
        $this->table_name = $wpdb->prefix . 'eto_teams';
        
        if ($id > 0) {
            $this->id = absint($id);
            $this->load();
        }
    }
    
    /**
     * Carica i dati del team dal database
     *
     * @return bool True se il caricamento è riuscito, false altrimenti
     */
    public function load() {
        if (empty($this->id)) {
            return false;
        }
        
        $team = $this->db_query->get_row(
            $this->table_name,
            ['id' => $this->id]
        );
        
        if (!$team) {
            return false;
        }
        
        $this->data = $team;
        return true;
    }
    
    /**
     * Salva i dati del team nel database
     *
     * @return bool|int ID del team se il salvataggio è riuscito, false altrimenti
     */
    public function save() {
        if (empty($this->data)) {
            return false;
        }
        
        // Prepara i dati per il salvataggio
        $data = $this->prepare_data_for_db($this->data);
        
        // Aggiorna o inserisce
        if (!empty($this->id)) {
            $result = $this->db_query->update(
                $this->table_name,
                $data,
                ['id' => $this->id]
            );
            
            return ($result !== false) ? $this->id : false;
        } else {
            $result = $this->db_query->insert(
                $this->table_name,
                $data
            );
            
            if ($result) {
                $this->id = $result;
                return $this->id;
            }
            
            return false;
        }
    }
    
    /**
     * Elimina il team dal database
     *
     * @return bool True se l'eliminazione è riuscita, false altrimenti
     */
    public function delete() {
        if (empty($this->id)) {
            return false;
        }
        
        // Prima elimina i dati correlati (membri, iscrizioni, ecc.)
        $this->delete_related_data();
        
        // Poi elimina il team
        return $this->db_query->delete(
            $this->table_name,
            ['id' => $this->id]
        );
    }
    
    /**
     * Elimina i dati correlati al team
     *
     * @return void
     */
    private function delete_related_data() {
        global $wpdb;
        
        // Elimina i membri del team
        $this->db_query->delete(
            $wpdb->prefix . 'eto_team_members',
            ['team_id' => $this->id]
        );
        
        // Elimina le iscrizioni ai tornei
        $this->db_query->delete(
            $wpdb->prefix . 'eto_tournament_entries',
            ['team_id' => $this->id]
        );
        
        // Elimina i metadati
        $this->db_query->delete(
            $wpdb->prefix . 'eto_team_meta',
            ['team_id' => $this->id]
        );
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'team_deleted',
                sprintf(__('Team ID %d eliminato', 'eto'), $this->id),
                ['team_id' => $this->id]
            );
        }
    }
    
    /**
     * Prepara i dati per il salvataggio nel database
     *
     * @param array $data Dati da preparare
     * @return array Dati preparati
     */
    private function prepare_data_for_db($data) {
        $prepared = [];
        
        // Campi consentiti
        $allowed_fields = [
            'name', 'slug', 'description', 'logo', 'captain_id',
            'game', 'status', 'created_by', 'created_at', 'updated_at'
        ];
        
        // Filtra i campi consentiti
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $prepared[$field] = $data[$field];
            }
        }
        
        // Aggiungi timestamp di aggiornamento
        $prepared['updated_at'] = current_time('mysql');
        
        // Se è un nuovo team, aggiungi timestamp di creazione
        if (empty($this->id)) {
            $prepared['created_at'] = current_time('mysql');
            $prepared['created_by'] = get_current_user_id();
            
            // Genera slug se non fornito
            if (empty($prepared['slug']) && !empty($prepared['name'])) {
                $prepared['slug'] = $this->generate_unique_slug($prepared['name']);
            }
        }
        
        return $prepared;
    }
    
    /**
     * Genera uno slug unico per il team
     *
     * @param string $name Nome del team
     * @return string Slug unico
     */
    private function generate_unique_slug($name) {
        global $wpdb;
        
        $slug = sanitize_title($name);
        $original_slug = $slug;
        $counter = 1;
        
        // Verifica se lo slug esiste già
        while ($this->slug_exists($slug)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Verifica se uno slug esiste già
     *
     * @param string $slug Slug da verificare
     * @return bool True se lo slug esiste, false altrimenti
     */
    private function slug_exists($slug) {
        global $wpdb;
        
        $query = $this->db_query->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE slug = %s",
            $slug
        );
        
        return (int) $this->db_query->get_var($query) > 0;
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
     * Ottiene l'ID del team
     *
     * @return int ID del team
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Ottiene tutti i dati del team
     *
     * @return array Dati del team
     */
    public function get_data() {
        return $this->data;
    }
    
    /**
     * Imposta tutti i dati del team
     *
     * @param array $data Dati del team
     * @return void
     */
    public function set_data($data) {
        $this->data = $data;
    }
    
    /**
     * Ottiene i membri del team
     *
     * @return array Array di oggetti membro
     */
    public function get_members() {
        global $wpdb;
        
        if (empty($this->id)) {
            return [];
        }
        
        $members_table = $wpdb->prefix . 'eto_team_members';
        
        $query = $this->db_query->prepare(
            "SELECT * FROM $members_table WHERE team_id = %d ORDER BY role ASC",
            $this->id
        );
        
        return $this->db_query->get_results($query);
    }
    
    /**
     * Aggiunge un membro al team
     *
     * @param int $user_id ID utente
     * @param string $role Ruolo nel team
     * @param array $data Dati aggiuntivi
     * @return bool|int ID del membro se l'aggiunta è riuscita, false altrimenti
     */
    public function add_member($user_id, $role = 'player', $data = []) {
        global $wpdb;
        
        if (empty($this->id) || empty($user_id)) {
            return false;
        }
        
        // Verifica se l'utente è già membro del team
        if ($this->is_member($user_id)) {
            return false;
        }
        
        // Verifica se il team ha raggiunto il limite di membri
        if ($this->is_full()) {
            return false;
        }
        
        $members_table = $wpdb->prefix . 'eto_team_members';
        
        $member_data = [
            'team_id' => $this->id,
            'user_id' => $user_id,
            'role' => $role,
            'joined_at' => current_time('mysql')
        ];
        
        // Aggiungi dati aggiuntivi
        if (!empty($data['nickname'])) {
            $member_data['nickname'] = sanitize_text_field($data['nickname']);
        }
        
        if (!empty($data['game_id'])) {
            $member_data['game_id'] = sanitize_text_field($data['game_id']);
        }
        
        return $this->db_query->insert($members_table, $member_data);
    }
    
    /**
     * Rimuove un membro dal team
     *
     * @param int $user_id ID utente
     * @return bool True se la rimozione è riuscita, false altrimenti
     */
    public function remove_member($user_id) {
        global $wpdb;
        
        if (empty($this->id) || empty($user_id)) {
            return false;
        }
        
        $members_table = $wpdb->prefix . 'eto_team_members';
        
        return $this->db_query->delete(
            $members_table,
            [
                'team_id' => $this->id,
                'user_id' => $user_id
            ]
        );
    }
    
    /**
     * Verifica se un utente è membro del team
     *
     * @param int $user_id ID utente
     * @return bool True se l'utente è membro, false altrimenti
     */
    public function is_member($user_id) {
        global $wpdb;
        
        if (empty($this->id) || empty($user_id)) {
            return false;
        }
        
        $members_table = $wpdb->prefix . 'eto_team_members';
        
        $query = $this->db_query->prepare(
            "SELECT COUNT(*) FROM $members_table WHERE team_id = %d AND user_id = %d",
            $this->id,
            $user_id
        );
        
        return (int) $this->db_query->get_var($query) > 0;
    }
    
    /**
     * Verifica se il team è pieno
     *
     * @return bool True se il team è pieno, false altrimenti
     */
    public function is_full() {
        if (empty($this->id)) {
            return false;
        }
        
        $max_members = $this->get_max_members();
        $current_members = $this->count_members();
        
        return ($max_members > 0 && $current_members >= $max_members);
    }
    
    /**
     * Ottiene il numero massimo di membri consentiti
     *
     * @return int Numero massimo di membri
     */
    public function get_max_members() {
        // Ottieni il limite dal gioco
        $game = $this->get('game', '');
        
        switch ($game) {
            case 'lol':
                return 6; // 5 giocatori + 1 riserva
            case 'valorant':
                return 6; // 5 giocatori + 1 riserva
            case 'csgo':
                return 7; // 5 giocatori + 2 riserve
            default:
                return 6; // Valore predefinito
        }
    }
    
    /**
     * Conta i membri del team
     *
     * @return int Numero di membri
     */
    public function count_members() {
        global $wpdb;
        
        if (empty($this->id)) {
            return 0;
        }
        
        $members_table = $wpdb->prefix . 'eto_team_members';
        
        $query = $this->db_query->prepare(
            "SELECT COUNT(*) FROM $members_table WHERE team_id = %d",
            $this->id
        );
        
        return (int) $this->db_query->get_var($query);
    }
    
    /**
     * Ottiene il capitano del team
     *
     * @return array|false Dati del capitano o false se non trovato
     */
    public function get_captain() {
        if (empty($this->id)) {
            return false;
        }
        
        $captain_id = $this->get('captain_id', 0);
        
        if (empty($captain_id)) {
            return false;
        }
        
        $user = get_userdata($captain_id);
        
        if (!$user) {
            return false;
        }
        
        return [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email
        ];
    }
    
    /**
     * Imposta il capitano del team
     *
     * @param int $user_id ID utente
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function set_captain($user_id) {
        if (empty($this->id) || empty($user_id)) {
            return false;
        }
        
        // Verifica se l'utente è membro del team
        if (!$this->is_member($user_id)) {
            return false;
        }
        
        $this->set('captain_id', $user_id);
        return $this->save();
    }
    
    /**
     * Ottiene i tornei a cui il team è iscritto
     *
     * @return array Array di oggetti torneo
     */
    public function get_tournaments() {
        global $wpdb;
        
        if (empty($this->id)) {
            return [];
        }
        
        $entries_table = $wpdb->prefix . 'eto_tournament_entries';
        $tournaments_table = $wpdb->prefix . 'eto_tournaments';
        
        $query = $this->db_query->prepare(
            "SELECT t.* FROM $tournaments_table AS t
            JOIN $entries_table AS e ON t.id = e.tournament_id
            WHERE e.team_id = %d
            ORDER BY t.start_date ASC",
            $this->id
        );
        
        return $this->db_query->get_results($query);
    }
    
    /**
     * Iscrive il team a un torneo
     *
     * @param int $tournament_id ID del torneo
     * @return bool True se l'iscrizione è riuscita, false altrimenti
     */
    public function register_to_tournament($tournament_id) {
        global $wpdb;
        
        if (empty($this->id) || empty($tournament_id)) {
            return false;
        }
        
        // Verifica se il team è già iscritto
        if ($this->is_registered_to_tournament($tournament_id)) {
            return false;
        }
        
        $entries_table = $wpdb->prefix . 'eto_tournament_entries';
        
        return $this->db_query->insert(
            $entries_table,
            [
                'tournament_id' => $tournament_id,
                'team_id' => $this->id,
                'created_at' => current_time('mysql'),
                'status' => 'pending'
            ]
        );
    }
    
    /**
     * Verifica se il team è iscritto a un torneo
     *
     * @param int $tournament_id ID del torneo
     * @return bool True se il team è iscritto, false altrimenti
     */
    public function is_registered_to_tournament($tournament_id) {
        global $wpdb;
        
        if (empty($this->id) || empty($tournament_id)) {
            return false;
        }
        
        $entries_table = $wpdb->prefix . 'eto_tournament_entries';
        
        $query = $this->db_query->prepare(
            "SELECT COUNT(*) FROM $entries_table WHERE tournament_id = %d AND team_id = %d",
            $tournament_id,
            $this->id
        );
        
        return (int) $this->db_query->get_var($query) > 0;
    }
    
    /**
     * Ottiene tutti i team
     *
     * @param array $args Argomenti per la query
     * @return array Array di team
     */
    public static function get_all($args = []) {
        global $wpdb;
        
        $db_query = new ETO_DB_Query();
        $table_name = $wpdb->prefix . 'eto_teams';
        
        // Imposta i valori predefiniti
        $defaults = [
            'status' => '',
            'game' => '',
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => 0,
            'offset' => 0
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Costruisci la clausola WHERE
        $where = [];
        
        if (!empty($args['status'])) {
            $where['status'] = $args['status'];
        }
        
        if (!empty($args['game'])) {
            $where['game'] = $args['game'];
        }
        
        // Costruisci la clausola ORDER BY
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        // Esegui la query
        $teams = $db_query->get_results(
            $table_name,
            $where,
            $orderby,
            $args['limit'],
            $args['offset']
        );
        
        return $teams;
    }
    
    /**
     * Conta tutti i team
     *
     * @param array $args Argomenti per la query
     * @return int Numero di team
     */
    public static function count_all($args = []) {
        global $wpdb;
        
        $db_query = new ETO_DB_Query();
        $table_name = $wpdb->prefix . 'eto_teams';
        
        // Imposta i valori predefiniti
        $defaults = [
            'status' => '',
            'game' => ''
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Costruisci la clausola WHERE
        $where = [];
        
        if (!empty($args['status'])) {
            $where['status'] = $args['status'];
        }
        
        if (!empty($args['game'])) {
            $where['game'] = $args['game'];
        }
        
        // Esegui la query
        return $db_query->count($table_name, $where);
    }
    
    /**
     * Ottiene un team per ID
     *
     * @param int $id ID del team
     * @return ETO_Team_Model|false Istanza del modello o false se non trovato
     */
    public static function get_by_id($id) {
        $team = new self($id);
        
        if ($team->get_id() > 0) {
            return $team;
        }
        
        return false;
    }
    
    /**
     * Ottiene un team per slug
     *
     * @param string $slug Slug del team
     * @return ETO_Team_Model|false Istanza del modello o false se non trovato
     */
    public static function get_by_slug($slug) {
        global $wpdb;
        
        $db_query = new ETO_DB_Query();
        $table_name = $wpdb->prefix . 'eto_teams';
        
        $team_data = $db_query->get_row(
            $table_name,
            ['slug' => $slug]
        );
        
        if (!$team_data) {
            return false;
        }
        
        $team = new self();
        $team->id = $team_data['id'];
        $team->data = $team_data;
        
        return $team;
    }
    
    /**
     * Ottiene i team di un utente
     *
     * @param int $user_id ID utente
     * @return array Array di team
     */
    public static function get_user_teams($user_id) {
        global $wpdb;
        
        if (empty($user_id)) {
            return [];
        }
        
        $db_query = new ETO_DB_Query();
        $teams_table = $wpdb->prefix . 'eto_teams';
        $members_table = $wpdb->prefix . 'eto_team_members';
        
        $query = $db_query->prepare(
            "SELECT t.* FROM $teams_table AS t
            JOIN $members_table AS m ON t.id = m.team_id
            WHERE m.user_id = %d
            ORDER BY t.name ASC",
            $user_id
        );
        
        return $db_query->get_results($query);
    }
    
    /**
     * Ottiene i team capitanati da un utente
     *
     * @param int $user_id ID utente
     * @return array Array di team
     */
    public static function get_user_captain_teams($user_id) {
        global $wpdb;
        
        if (empty($user_id)) {
            return [];
        }
        
        $db_query = new ETO_DB_Query();
        $table_name = $wpdb->prefix . 'eto_teams';
        
        return $db_query->get_results(
            $table_name,
            ['captain_id' => $user_id],
            'name ASC'
        );
    }
    
    /**
     * Ottiene i metadati del team
     *
     * @param string $key Chiave del metadato (opzionale)
     * @param mixed $default Valore predefinito
     * @return mixed Valore del metadato o array di metadati
     */
    public function get_meta($key = '', $default = null) {
        global $wpdb;
        
        if (empty($this->id)) {
            return ($key) ? $default : [];
        }
        
        $meta_table = $wpdb->prefix . 'eto_team_meta';
        
        if (!empty($key)) {
            $query = $this->db_query->prepare(
                "SELECT meta_value FROM $meta_table WHERE team_id = %d AND meta_key = %s",
                $this->id,
                $key
            );
            
            $value = $this->db_query->get_var($query);
            
            return ($value !== null) ? maybe_unserialize($value) : $default;
        }
        
        $query = $this->db_query->prepare(
            "SELECT meta_key, meta_value FROM $meta_table WHERE team_id = %d",
            $this->id
        );
        
        $results = $this->db_query->get_results($query);
        $meta = [];
        
        foreach ($results as $row) {
            $meta[$row['meta_key']] = maybe_unserialize($row['meta_value']);
        }
        
        return $meta;
    }
    
    /**
     * Imposta un metadato del team
     *
     * @param string $key Chiave del metadato
     * @param mixed $value Valore del metadato
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function update_meta($key, $value) {
        global $wpdb;
        
        if (empty($this->id) || empty($key)) {
            return false;
        }
        
        $meta_table = $wpdb->prefix . 'eto_team_meta';
        
        // Verifica se il metadato esiste già
        $query = $this->db_query->prepare(
            "SELECT COUNT(*) FROM $meta_table WHERE team_id = %d AND meta_key = %s",
            $this->id,
            $key
        );
        
        $exists = (int) $this->db_query->get_var($query);
        
        if ($exists) {
            // Aggiorna
            return $this->db_query->update(
                $meta_table,
                ['meta_value' => maybe_serialize($value)],
                [
                    'team_id' => $this->id,
                    'meta_key' => $key
                ]
            );
        } else {
            // Inserisci
            return $this->db_query->insert(
                $meta_table,
                [
                    'team_id' => $this->id,
                    'meta_key' => $key,
                    'meta_value' => maybe_serialize($value)
                ]
            );
        }
    }
    
    /**
     * Elimina un metadato del team
     *
     * @param string $key Chiave del metadato
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function delete_meta($key) {
        global $wpdb;
        
        if (empty($this->id) || empty($key)) {
            return false;
        }
        
        $meta_table = $wpdb->prefix . 'eto_team_meta';
        
        return $this->db_query->delete(
            $meta_table,
            [
                'team_id' => $this->id,
                'meta_key' => $key
            ]
        );
    }
}