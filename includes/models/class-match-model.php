<?php
/**
 * Classe modello per i match
 * 
 * Gestisce l'accesso ai dati e la logica di business per i match
 * 
 * @package ETO
 * @subpackage Models
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Match_Model {
    
    /**
     * ID del match
     *
     * @var int
     */
    private $id;
    
    /**
     * Dati del match
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
     * Nome della tabella dei match
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Costruttore
     *
     * @param int $id ID del match (opzionale)
     */
    public function __construct($id = 0) {
        global $wpdb;
        
        $this->db_query = new ETO_DB_Query();
        $this->table_name = $wpdb->prefix . 'eto_matches';
        
        if ($id > 0) {
            $this->id = absint($id);
            $this->load();
        }
    }
    
    /**
     * Carica i dati del match dal database
     *
     * @return bool True se il caricamento è riuscito, false altrimenti
     */
    public function load() {
        if (empty($this->id)) {
            return false;
        }
        
        $match = $this->db_query->get_row(
            $this->table_name,
            ['id' => $this->id]
        );
        
        if (!$match) {
            return false;
        }
        
        $this->data = $match;
        return true;
    }
    
    /**
     * Salva i dati del match nel database
     *
     * @return bool|int ID del match se il salvataggio è riuscito, false altrimenti
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
     * Elimina il match dal database
     *
     * @return bool True se l'eliminazione è riuscita, false altrimenti
     */
    public function delete() {
        if (empty($this->id)) {
            return false;
        }
        
        // Prima elimina i dati correlati (risultati, ecc.)
        $this->delete_related_data();
        
        // Poi elimina il match
        return $this->db_query->delete(
            $this->table_name,
            ['id' => $this->id]
        );
    }
    
    /**
     * Elimina i dati correlati al match
     *
     * @return void
     */
    private function delete_related_data() {
        global $wpdb;
        
        // Elimina i risultati
        $this->db_query->delete(
            $wpdb->prefix . 'eto_match_results',
            ['match_id' => $this->id]
        );
        
        // Elimina i metadati
        $this->db_query->delete(
            $wpdb->prefix . 'eto_match_meta',
            ['match_id' => $this->id]
        );
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'match_deleted',
                sprintf(__('Match ID %d eliminato', 'eto'), $this->id),
                ['match_id' => $this->id]
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
            'tournament_id', 'team1_id', 'team2_id', 'round', 
            'match_number', 'scheduled_date', 'status', 'winner_id',
            'loser_id', 'created_at', 'updated_at'
        ];
        
        // Filtra i campi consentiti
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $prepared[$field] = $data[$field];
            }
        }
        
        // Aggiungi timestamp di aggiornamento
        $prepared['updated_at'] = current_time('mysql');
        
        // Se è un nuovo match, aggiungi timestamp di creazione
        if (empty($this->id)) {
            $prepared['created_at'] = current_time('mysql');
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
     * Ottiene l'ID del match
     *
     * @return int ID del match
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Ottiene tutti i dati del match
     *
     * @return array Dati del match
     */
    public function get_data() {
        return $this->data;
    }
    
    /**
     * Imposta tutti i dati del match
     *
     * @param array $data Dati del match
     * @return void
     */
    public function set_data($data) {
        $this->data = $data;
    }
    
    /**
     * Ottiene il torneo associato al match
     *
     * @return ETO_Tournament_Model|false Istanza del modello torneo o false se non trovato
     */
    public function get_tournament() {
        if (empty($this->id) || empty($this->data)) {
            return false;
        }
        
        $tournament_id = $this->get('tournament_id', 0);
        
        if (empty($tournament_id)) {
            return false;
        }
        
        return ETO_Tournament_Model::get_by_id($tournament_id);
    }
    
    /**
     * Ottiene il team 1
     *
     * @return ETO_Team_Model|false Istanza del modello team o false se non trovato
     */
    public function get_team1() {
        if (empty($this->id) || empty($this->data)) {
            return false;
        }
        
        $team_id = $this->get('team1_id', 0);
        
        if (empty($team_id)) {
            return false;
        }
        
        return ETO_Team_Model::get_by_id($team_id);
    }
    
    /**
     * Ottiene il team 2
     *
     * @return ETO_Team_Model|false Istanza del modello team o false se non trovato
     */
    public function get_team2() {
        if (empty($this->id) || empty($this->data)) {
            return false;
        }
        
        $team_id = $this->get('team2_id', 0);
        
        if (empty($team_id)) {
            return false;
        }
        
        return ETO_Team_Model::get_by_id($team_id);
    }
    
    /**
     * Ottiene il team vincitore
     *
     * @return ETO_Team_Model|false Istanza del modello team o false se non trovato
     */
    public function get_winner() {
        if (empty($this->id) || empty($this->data)) {
            return false;
        }
        
        $winner_id = $this->get('winner_id', 0);
        
        if (empty($winner_id)) {
            return false;
        }
        
        return ETO_Team_Model::get_by_id($winner_id);
    }
    
    /**
     * Ottiene il team perdente
     *
     * @return ETO_Team_Model|false Istanza del modello team o false se non trovato
     */
    public function get_loser() {
        if (empty($this->id) || empty($this->data)) {
            return false;
        }
        
        $loser_id = $this->get('loser_id', 0);
        
        if (empty($loser_id)) {
            return false;
        }
        
        return ETO_Team_Model::get_by_id($loser_id);
    }
    
    /**
     * Imposta il risultato del match
     *
     * @param int $team1_score Punteggio team 1
     * @param int $team2_score Punteggio team 2
     * @param int $reported_by ID utente che ha riportato il risultato
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function set_result($team1_score, $team2_score, $reported_by = 0) {
        if (empty($this->id) || empty($this->data)) {
            return false;
        }
        
        $team1_id = $this->get('team1_id', 0);
        $team2_id = $this->get('team2_id', 0);
        
        if (empty($team1_id) || empty($team2_id)) {
            return false;
        }
        
        // Determina il vincitore
        if ($team1_score > $team2_score) {
            $winner_id = $team1_id;
            $loser_id = $team2_id;
        } elseif ($team2_score > $team1_score) {
            $winner_id = $team2_id;
            $loser_id = $team1_id;
        } else {
            // Pareggio
            $winner_id = 0;
            $loser_id = 0;
        }
        
        // Aggiorna il match
        $this->set('winner_id', $winner_id);
        $this->set('loser_id', $loser_id);
        $this->set('status', 'completed');
        
        // Salva il risultato
        global $wpdb;
        $results_table = $wpdb->prefix . 'eto_match_results';
        
        // Verifica se esiste già un risultato
        $existing_result = $this->db_query->get_row(
            $results_table,
            ['match_id' => $this->id]
        );
        
        $result_data = [
            'team1_score' => $team1_score,
            'team2_score' => $team2_score,
            'reported_by' => ($reported_by > 0) ? $reported_by : get_current_user_id(),
            'reported_at' => current_time('mysql')
        ];
        
        if ($existing_result) {
            // Aggiorna
            $this->db_query->update(
                $results_table,
                $result_data,
                ['match_id' => $this->id]
            );
        } else {
            // Inserisci
            $result_data['match_id'] = $this->id;
            $this->db_query->insert($results_table, $result_data);
        }
        
        // Registra l'azione nell'audit log
        if (class_exists('ETO_Audit_Log')) {
            ETO_Audit_Log::log(
                'match_result_set',
                sprintf(__('Risultato impostato per il match ID %d: %d-%d', 'eto'), $this->id, $team1_score, $team2_score),
                [
                    'match_id' => $this->id,
                    'team1_score' => $team1_score,
                    'team2_score' => $team2_score,
                    'reported_by' => $result_data['reported_by']
                ]
            );
        }
        
        return $this->save();
    }
    
    /**
     * Ottiene il risultato del match
     *
     * @return array|false Array con i dati del risultato o false se non trovato
     */
    public function get_result() {
        if (empty($this->id)) {
            return false;
        }
        
        global $wpdb;
        $results_table = $wpdb->prefix . 'eto_match_results';
        
        return $this->db_query->get_row(
            $results_table,
            ['match_id' => $this->id]
        );
    }
    
    /**
     * Verifica se il match è completato
     *
     * @return bool True se il match è completato, false altrimenti
     */
    public function is_completed() {
        if (empty($this->id) || empty($this->data)) {
            return false;
        }
        
        return $this->get('status', '') === 'completed';
    }
    
    /**
     * Verifica se il match è in attesa
     *
     * @return bool True se il match è in attesa, false altrimenti
     */
    public function is_pending() {
        if (empty($this->id) || empty($this->data)) {
            return false;
        }
        
        return $this->get('status', '') === 'pending';
    }
    
    /**
     * Verifica se il match è in corso
     *
     * @return bool True se il match è in corso, false altrimenti
     */
    public function is_in_progress() {
        if (empty($this->id) || empty($this->data)) {
            return false;
        }
        
        return $this->get('status', '') === 'in_progress';
    }
    
    /**
     * Ottiene tutti i match
     *
     * @param array $args Argomenti per la query
     * @return array Array di match
     */
    public static function get_all($args = []) {
        global $wpdb;
        
        $db_query = new ETO_DB_Query();
        $table_name = $wpdb->prefix . 'eto_matches';
        
        // Imposta i valori predefiniti
        $defaults = [
            'tournament_id' => 0,
            'team_id' => 0,
            'status' => '',
            'round' => 0,
            'orderby' => 'scheduled_date',
            'order' => 'ASC',
            'limit' => 0,
            'offset' => 0
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Costruisci la clausola WHERE
        $where = [];
        
        if (!empty($args['tournament_id'])) {
            $where['tournament_id'] = $args['tournament_id'];
        }
        
        if (!empty($args['status'])) {
            $where['status'] = $args['status'];
        }
        
        if (!empty($args['round'])) {
            $where['round'] = $args['round'];
        }
        
        // Gestisci la ricerca per team_id
        if (!empty($args['team_id'])) {
            $team_id = $args['team_id'];
            
            // Rimuovi gli altri where e usa una query personalizzata
            $where = [];
            
            $query = "SELECT * FROM $table_name WHERE team1_id = %d OR team2_id = %d";
            $query_args = [$team_id, $team_id];
            
            if (!empty($args['tournament_id'])) {
                $query .= " AND tournament_id = %d";
                $query_args[] = $args['tournament_id'];
            }
            
            if (!empty($args['status'])) {
                $query .= " AND status = %s";
                $query_args[] = $args['status'];
            }
            
            if (!empty($args['round'])) {
                $query .= " AND round = %d";
                $query_args[] = $args['round'];
            }
            
            // Aggiungi ORDER BY
            $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
            $query .= " ORDER BY $orderby";
            
            // Aggiungi LIMIT
            if ($args['limit'] > 0) {
                $query .= " LIMIT %d";
                $query_args[] = $args['limit'];
                
                if ($args['offset'] > 0) {
                    $query .= " OFFSET %d";
                    $query_args[] = $args['offset'];
                }
            }
            
            // Prepara la query
            $query = $db_query->prepare($query, ...$query_args);
            
            // Esegui la query
            return $db_query->get_results($query);
        }
        
        // Costruisci la clausola ORDER BY
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        // Esegui la query
        $matches = $db_query->get_results(
            $table_name,
            $where,
            $orderby,
            $args['limit'],
            $args['offset']
        );
        
        return $matches;
    }
    
    /**
     * Conta tutti i match
     *
     * @param array $args Argomenti per la query
     * @return int Numero di match
     */
    public static function count_all($args = []) {
        global $wpdb;
        
        $db_query = new ETO_DB_Query();
        $table_name = $wpdb->prefix . 'eto_matches';
        
        // Imposta i valori predefiniti
        $defaults = [
            'tournament_id' => 0,
            'team_id' => 0,
            'status' => '',
            'round' => 0
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Costruisci la clausola WHERE
        $where = [];
        
        if (!empty($args['tournament_id'])) {
            $where['tournament_id'] = $args['tournament_id'];
        }
        
        if (!empty($args['status'])) {
            $where['status'] = $args['status'];
        }
        
        if (!empty($args['round'])) {
            $where['round'] = $args['round'];
        }
        
        // Gestisci la ricerca per team_id
        if (!empty($args['team_id'])) {
            $team_id = $args['team_id'];
            
            // Rimuovi gli altri where e usa una query personalizzata
            $where = [];
            
            $query = "SELECT COUNT(*) FROM $table_name WHERE team1_id = %d OR team2_id = %d";
            $query_args = [$team_id, $team_id];
            
            if (!empty($args['tournament_id'])) {
                $query .= " AND tournament_id = %d";
                $query_args[] = $args['tournament_id'];
            }
            
            if (!empty($args['status'])) {
                $query .= " AND status = %s";
                $query_args[] = $args['status'];
            }
            
            if (!empty($args['round'])) {
                $query .= " AND round = %d";
                $query_args[] = $args['round'];
            }
            
            // Prepara la query
            $query = $db_query->prepare($query, ...$query_args);
            
            // Esegui la query
            return (int) $db_query->get_var($query);
        }
        
        // Esegui la query
        return $db_query->count($table_name, $where);
    }
    
    /**
     * Ottiene un match per ID
     *
     * @param int $id ID del match
     * @return ETO_Match_Model|false Istanza del modello o false se non trovato
     */
    public static function get_by_id($id) {
        $match = new self($id);
        
        if ($match->get_id() > 0) {
            return $match;
        }
        
        return false;
    }
    
    /**
     * Ottiene i match di un torneo
     *
     * @param int $tournament_id ID del torneo
     * @param string $status Stato dei match (opzionale)
     * @param int $round Round dei match (opzionale)
     * @return array Array di match
     */
    public static function get_tournament_matches($tournament_id, $status = '', $round = 0) {
        $args = [
            'tournament_id' => $tournament_id,
            'orderby' => 'round, match_number',
            'order' => 'ASC'
        ];
        
        if (!empty($status)) {
            $args['status'] = $status;
        }
        
        if ($round > 0) {
            $args['round'] = $round;
        }
        
        return self::get_all($args);
    }
    
    /**
     * Ottiene i match di un team
     *
     * @param int $team_id ID del team
     * @param string $status Stato dei match (opzionale)
     * @return array Array di match
     */
    public static function get_team_matches($team_id, $status = '') {
        $args = [
            'team_id' => $team_id,
            'orderby' => 'scheduled_date',
            'order' => 'DESC'
        ];
        
        if (!empty($status)) {
            $args['status'] = $status;
        }
        
        return self::get_all($args);
    }
    
    /**
     * Ottiene i match imminenti
     *
     * @param int $limit Limite di risultati
     * @return array Array di match
     */
    public static function get_upcoming($limit = 0) {
        global $wpdb;
        
        $db_query = new ETO_DB_Query();
        $table_name = $wpdb->prefix . 'eto_matches';
        
        $now = current_time('mysql');
        
        $query = "SELECT * FROM $table_name 
                 WHERE scheduled_date > %s AND status = 'pending'
                 ORDER BY scheduled_date ASC";
                 
        if ($limit > 0) {
            $query .= " LIMIT %d";
            $query = $db_query->prepare($query, $now, $limit);
        } else {
            $query = $db_query->prepare($query, $now);
        }
        
        return $db_query->get_results($query);
    }
    
    /**
     * Ottiene i metadati del match
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
        
        $meta_table = $wpdb->prefix . 'eto_match_meta';
        
        if (!empty($key)) {
            $query = $this->db_query->prepare(
                "SELECT meta_value FROM $meta_table WHERE match_id = %d AND meta_key = %s",
                $this->id,
                $key
            );
            
            $value = $this->db_query->get_var($query);
            
            return ($value !== null) ? maybe_unserialize($value) : $default;
        }
        
        $query = $this->db_query->prepare(
            "SELECT meta_key, meta_value FROM $meta_table WHERE match_id = %d",
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
     * Imposta un metadato del match
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
        
        $meta_table = $wpdb->prefix . 'eto_match_meta';
        
        // Verifica se il metadato esiste già
        $query = $this->db_query->prepare(
            "SELECT COUNT(*) FROM $meta_table WHERE match_id = %d AND meta_key = %s",
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
                    'match_id' => $this->id,
                    'meta_key' => $key
                ]
            );
        } else {
            // Inserisci
            return $this->db_query->insert(
                $meta_table,
                [
                    'match_id' => $this->id,
                    'meta_key' => $key,
                    'meta_value' => maybe_serialize($value)
                ]
            );
        }
    }
    
    /**
     * Elimina un metadato del match
     *
     * @param string $key Chiave del metadato
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function delete_meta($key) {
        global $wpdb;
        
        if (empty($this->id) || empty($key)) {
            return false;
        }
        
        $meta_table = $wpdb->prefix . 'eto_match_meta';
        
        return $this->db_query->delete(
            $meta_table,
            [
                'match_id' => $this->id,
                'meta_key' => $key
            ]
        );
    }
}
