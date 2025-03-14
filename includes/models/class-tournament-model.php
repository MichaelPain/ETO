<?php
/**
 * Classe modello per i tornei
 * 
 * Gestisce l'accesso ai dati e la logica di business per i tornei
 * 
 * @package ETO
 * @subpackage Models
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Tournament_Model {
    
    /**
     * ID del torneo
     *
     * @var int
     */
    private $id;
    
    /**
     * Dati del torneo
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
     * Nome della tabella dei tornei
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Costruttore
     *
     * @param int $id ID del torneo (opzionale)
     */
    public function __construct($id = 0) {
        global $wpdb;
        
        $this->db_query = new ETO_DB_Query();
        $this->table_name = $wpdb->prefix . 'eto_tournaments';
        
        if ($id > 0) {
            $this->id = absint($id);
            $this->load();
        }
    }
    
    /**
     * Carica i dati del torneo dal database
     *
     * @return bool True se il caricamento è riuscito, false altrimenti
     */
    public function load() {
        if (empty($this->id)) {
            return false;
        }
        
        $tournament = $this->db_query->get_row(
            $this->table_name,
            ['id' => $this->id]
        );
        
        if (!$tournament) {
            return false;
        }
        
        $this->data = $tournament;
        return true;
    }
    
    /**
     * Salva i dati del torneo nel database
     *
     * @return bool|int ID del torneo se il salvataggio è riuscito, false altrimenti
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
     * Elimina il torneo dal database
     *
     * @return bool True se l'eliminazione è riuscita, false altrimenti
     */
    public function delete() {
        if (empty($this->id)) {
            return false;
        }
        
        // Prima elimina i dati correlati (match, iscrizioni, ecc.)
        $this->delete_related_data();
        
        // Poi elimina il torneo
        return $this->db_query->delete(
            $this->table_name,
            ['id' => $this->id]
        );
    }
    
    /**
     * Elimina i dati correlati al torneo
     *
     * @return void
     */
    private function delete_related_data() {
        global $wpdb;
        
        // Elimina i match
        $this->db_query->delete(
            $wpdb->prefix . 'eto_matches',
            ['tournament_id' => $this->id]
        );
        
        // Elimina le iscrizioni dei team
        $this->db_query->delete(
            $wpdb->prefix . 'eto_tournament_entries',
            ['tournament_id' => $this->id]
        );
        
        // Elimina i metadati
        $this->db_query->delete(
            $wpdb->prefix . 'eto_tournament_meta',
            ['tournament_id' => $this->id]
        );
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'tournament_deleted',
                sprintf(__('Torneo ID %d eliminato', 'eto'), $this->id),
                ['tournament_id' => $this->id]
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
            'name', 'description', 'start_date', 'end_date', 
            'registration_start', 'registration_end', 'max_teams',
            'min_teams', 'format', 'game', 'status', 'created_by',
            'created_at', 'updated_at'
        ];
        
        // Filtra i campi consentiti
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $prepared[$field] = $data[$field];
            }
        }
        
        // Aggiungi timestamp di aggiornamento
        $prepared['updated_at'] = current_time('mysql');
        
        // Se è un nuovo torneo, aggiungi timestamp di creazione
        if (empty($this->id)) {
            $prepared['created_at'] = current_time('mysql');
            $prepared['created_by'] = get_current_user_id();
        }
        
        return $prepared;
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
     * Ottiene l'ID del torneo
     *
     * @return int ID del torneo
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Ottiene tutti i dati del torneo
     *
     * @return array Dati del torneo
     */
    public function get_data() {
        return $this->data;
    }
    
    /**
     * Imposta tutti i dati del torneo
     *
     * @param array $data Dati del torneo
     * @return void
     */
    public function set_data($data) {
        $this->data = $data;
    }
    
    /**
     * Ottiene i team iscritti al torneo
     *
     * @return array Array di oggetti team
     */
    public function get_teams() {
        global $wpdb;
        
        if (empty($this->id)) {
            return [];
        }
        
        $entries_table = $wpdb->prefix . 'eto_tournament_entries';
        $teams_table = $wpdb->prefix . 'eto_teams';
        
        $query = $this->db_query->prepare(
            "SELECT t.* FROM $teams_table AS t
            JOIN $entries_table AS e ON t.id = e.team_id
            WHERE e.tournament_id = %d
            ORDER BY e.created_at ASC",
            $this->id
        );
        
        $teams = $this->db_query->get_results($query);
        
        return $teams;
    }
    
    /**
     * Ottiene i match del torneo
     *
     * @param string $status Stato dei match (opzionale)
     * @return array Array di oggetti match
     */
    public function get_matches($status = '') {
        global $wpdb;
        
        if (empty($this->id)) {
            return [];
        }
        
        $matches_table = $wpdb->prefix . 'eto_matches';
        
        $where = ['tournament_id' => $this->id];
        
        if (!empty($status)) {
            $where['status'] = $status;
        }
        
        $matches = $this->db_query->get_results(
            $matches_table,
            $where,
            'round ASC, match_number ASC'
        );
        
        return $matches;
    }
    
    /**
     * Verifica se il torneo è pieno
     *
     * @return bool True se il torneo è pieno, false altrimenti
     */
    public function is_full() {
        if (empty($this->id) || empty($this->data)) {
            return false;
        }
        
        $max_teams = $this->get('max_teams', 0);
        $current_teams = $this->count_teams();
        
        return ($max_teams > 0 && $current_teams >= $max_teams);
    }
    
    /**
     * Conta i team iscritti al torneo
     *
     * @return int Numero di team
     */
    public function count_teams() {
        global $wpdb;
        
        if (empty($this->id)) {
            return 0;
        }
        
        $entries_table = $wpdb->prefix . 'eto_tournament_entries';
        
        $query = $this->db_query->prepare(
            "SELECT COUNT(*) FROM $entries_table WHERE tournament_id = %d",
            $this->id
        );
        
        return (int) $this->db_query->get_var($query);
    }
    
    /**
     * Ottiene tutti i tornei
     *
     * @param array $args Argomenti per la query
     * @return array Array di tornei
     */
    public static function get_all($args = []) {
        global $wpdb;
        
        $db_query = new ETO_DB_Query();
        $table_name = $wpdb->prefix . 'eto_tournaments';
        
        // Imposta i valori predefiniti
        $defaults = [
            'status' => '',
            'game' => '',
            'format' => '',
            'orderby' => 'start_date',
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
        
        if (!empty($args['format'])) {
            $where['format'] = $args['format'];
        }
        
        // Costruisci la clausola ORDER BY
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        // Esegui la query
        $tournaments = $db_query->get_results(
            $table_name,
            $where,
            $orderby,
            $args['limit'],
            $args['offset']
        );
        
        return $tournaments;
    }
    
    /**
     * Conta tutti i tornei
     *
     * @param array $args Argomenti per la query
     * @return int Numero di tornei
     */
    public static function count_all($args = []) {
        global $wpdb;
        
        $db_query = new ETO_DB_Query();
        $table_name = $wpdb->prefix . 'eto_tournaments';
        
        // Imposta i valori predefiniti
        $defaults = [
            'status' => '',
            'game' => '',
            'format' => ''
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
        
        if (!empty($args['format'])) {
            $where['format'] = $args['format'];
        }
        
        // Esegui la query
        return $db_query->count($table_name, $where);
    }
    
    /**
     * Ottiene un torneo per ID
     *
     * @param int $id ID del torneo
     * @return ETO_Tournament_Model|false Istanza del modello o false se non trovato
     */
    public static function get_by_id($id) {
        $tournament = new self($id);
        
        if ($tournament->get_id() > 0) {
            return $tournament;
        }
        
        return false;
    }
    
    /**
     * Ottiene un torneo per slug
     *
     * @param string $slug Slug del torneo
     * @return ETO_Tournament_Model|false Istanza del modello o false se non trovato
     */
    public static function get_by_slug($slug) {
        global $wpdb;
        
        $db_query = new ETO_DB_Query();
        $table_name = $wpdb->prefix . 'eto_tournaments';
        
        $tournament_data = $db_query->get_row(
            $table_name,
            ['slug' => $slug]
        );
        
        if (!$tournament_data) {
            return false;
        }
        
        $tournament = new self();
        $tournament->id = $tournament_data['id'];
        $tournament->data = $tournament_data;
        
        return $tournament;
    }
    
    /**
     * Ottiene i tornei attivi
     *
     * @param int $limit Limite di risultati
     * @return array Array di tornei
     */
    public static function get_active($limit = 0) {
        return self::get_all([
            'status' => 'active',
            'orderby' => 'start_date',
            'order' => 'ASC',
            'limit' => $limit
        ]);
    }
    
    /**
     * Ottiene i tornei imminenti
     *
     * @param int $limit Limite di risultati
     * @return array Array di tornei
     */
    public static function get_upcoming($limit = 0) {
        global $wpdb;
        
        $db_query = new ETO_DB_Query();
        $table_name = $wpdb->prefix . 'eto_tournaments';
        
        $now = current_time('mysql');
        
        $query = "SELECT * FROM $table_name 
                 WHERE start_date > %s 
                 ORDER BY start_date ASC";
                 
        if ($limit > 0) {
            $query .= " LIMIT %d";
            $query = $db_query->prepare($query, $now, $limit);
        } else {
            $query = $db_query->prepare($query, $now);
        }
        
        return $db_query->get_results($query);
    }
    
    /**
     * Ottiene i tornei passati
     *
     * @param int $limit Limite di risultati
     * @return array Array di tornei
     */
    public static function get_past($limit = 0) {
        global $wpdb;
        
        $db_query = new ETO_DB_Query();
        $table_name = $wpdb->prefix . 'eto_tournaments';
        
        $now = current_time('mysql');
        
        $query = "SELECT * FROM $table_name 
                 WHERE end_date < %s 
                 ORDER BY end_date DESC";
                 
        if ($limit > 0) {
            $query .= " LIMIT %d";
            $query = $db_query->prepare($query, $now, $limit);
        } else {
            $query = $db_query->prepare($query, $now);
        }
        
        return $db_query->get_results($query);
    }
    
    /**
     * Ottiene i metadati del torneo
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
        
        $meta_table = $wpdb->prefix . 'eto_tournament_meta';
        
        if (!empty($key)) {
            $query = $this->db_query->prepare(
                "SELECT meta_value FROM $meta_table WHERE tournament_id = %d AND meta_key = %s",
                $this->id,
                $key
            );
            
            $value = $this->db_query->get_var($query);
            
            return ($value !== null) ? maybe_unserialize($value) : $default;
        }
        
        $query = $this->db_query->prepare(
            "SELECT meta_key, meta_value FROM $meta_table WHERE tournament_id = %d",
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
     * Imposta un metadato del torneo
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
        
        $meta_table = $wpdb->prefix . 'eto_tournament_meta';
        
        // Verifica se il metadato esiste già
        $query = $this->db_query->prepare(
            "SELECT COUNT(*) FROM $meta_table WHERE tournament_id = %d AND meta_key = %s",
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
                    'tournament_id' => $this->id,
                    'meta_key' => $key
                ]
            );
        } else {
            // Inserisci
            return $this->db_query->insert(
                $meta_table,
                [
                    'tournament_id' => $this->id,
                    'meta_key' => $key,
                    'meta_value' => maybe_serialize($value)
                ]
            );
        }
    }
    
    /**
     * Elimina un metadato del torneo
     *
     * @param string $key Chiave del metadato
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function delete_meta($key) {
        global $wpdb;
        
        if (empty($this->id) || empty($key)) {
            return false;
        }
        
        $meta_table = $wpdb->prefix . 'eto_tournament_meta';
        
        return $this->db_query->delete(
            $meta_table,
            [
                'tournament_id' => $this->id,
                'meta_key' => $key
            ]
        );
    }
}
