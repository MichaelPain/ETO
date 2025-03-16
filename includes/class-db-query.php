<?php
/**
 * Classe per la gestione delle query al database
 * 
 * Fornisce metodi sicuri per interagire con il database
 * 
 * @package ETO
 * @since 2.5.1
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_DB_Query {
    
    /**
     * Istanza del database manager
     *
     * @var ETO_Database_Manager
     */
    private $db_manager;
    
    /**
     * Costruttore
     */
    public function __construct() {
        $this->db_manager = new ETO_Database_Manager();
    }
    
    /**
     * Ottiene il nome completo di una tabella del database
     *
     * @param string $table Nome della tabella senza prefisso
     * @return string Nome completo della tabella con prefisso
     */
    public function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'eto_' . $table;
    }
    
    /**
     * Ottiene un singolo torneo dal database
     *
     * @param int $tournament_id ID del torneo
     * @return object|false Oggetto torneo o false se non trovato
     */
    public function get_tournament($tournament_id) {
        global $wpdb;
        
        $table = $this->get_table_name('tournaments');
        
        $tournament = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $tournament_id
            )
        );
        
        return $tournament;
    }
    
    /**
     * Ottiene una lista di tornei dal database
     *
     * @param array $args Argomenti di query
     * @return array Lista di tornei
     */
    public function get_tournaments($args = []) {
        global $wpdb;
        
        $defaults = [
            'status' => 'active',
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'start_date',
            'order' => 'ASC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Sanitizzazione
        $args['limit'] = absint($args['limit']);
        $args['offset'] = absint($args['offset']);
        
        // Whitelist per orderby
        $allowed_orderby = ['id', 'name', 'start_date', 'end_date', 'created_at'];
        if (!in_array($args['orderby'], $allowed_orderby)) {
            $args['orderby'] = 'start_date';
        }
        
        // Whitelist per order
        $args['order'] = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        
        $table = $this->get_table_name('tournaments');
        
        $query = "SELECT * FROM $table WHERE 1=1";
        $query_args = [];
        
        // Filtro per status
        if (!empty($args['status'])) {
            // Supporto per array di status
            if (is_array($args['status'])) {
                $placeholders = array_fill(0, count($args['status']), '%s');
                $placeholders_str = implode(', ', $placeholders);
                $query .= " AND status IN ($placeholders_str)";
                $query_args = array_merge($query_args, $args['status']);
            } else {
                $query .= " AND status = %s";
                $query_args[] = $args['status'];
            }
        }
        
        // Filtro per format
        if (!empty($args['format'])) {
            $query .= " AND format = %s";
            $query_args[] = $args['format'];
        }
        
        // Filtro per elimination_type
        if (!empty($args['elimination_type'])) {
            $query .= " AND elimination_type = %s";
            $query_args[] = $args['elimination_type'];
        }
        
        // Filtro per date
        if (!empty($args['start_date_after'])) {
            $query .= " AND start_date >= %s";
            $query_args[] = $args['start_date_after'];
        }
        
        if (!empty($args['start_date_before'])) {
            $query .= " AND start_date <= %s";
            $query_args[] = $args['start_date_before'];
        }
        
        // Ordinamento
        $query .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        // Limite
        $query .= " LIMIT %d OFFSET %d";
        $query_args[] = $args['limit'];
        $query_args[] = $args['offset'];
        
        // Prepara la query con tutti i parametri
        $prepared_query = $wpdb->prepare($query, $query_args);
        
        // Esegui la query
        $results = $wpdb->get_results($prepared_query);
        
        return $results;
    }
    
    /**
     * Conta i tornei nel database
     *
     * @param array $args Argomenti di query
     * @return int Numero di tornei
     */
    public function count_tournaments($args = []) {
        global $wpdb;
        
        $defaults = [
            'status' => 'active'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $this->get_table_name('tournaments');
        
        $query = "SELECT COUNT(*) FROM $table WHERE 1=1";
        $query_args = [];
        
        // Filtro per status
        if (!empty($args['status'])) {
            // Supporto per array di status
            if (is_array($args['status'])) {
                $placeholders = array_fill(0, count($args['status']), '%s');
                $placeholders_str = implode(', ', $placeholders);
                $query .= " AND status IN ($placeholders_str)";
                $query_args = array_merge($query_args, $args['status']);
            } else {
                $query .= " AND status = %s";
                $query_args[] = $args['status'];
            }
        }
        
        // Filtro per format
        if (!empty($args['format'])) {
            $query .= " AND format = %s";
            $query_args[] = $args['format'];
        }
        
        // Filtro per elimination_type
        if (!empty($args['elimination_type'])) {
            $query .= " AND elimination_type = %s";
            $query_args[] = $args['elimination_type'];
        }
        
        // Prepara la query con tutti i parametri
        $prepared_query = $wpdb->prepare($query, $query_args);
        
        // Esegui la query
        return (int) $wpdb->get_var($prepared_query);
    }
    
    /**
     * Conta i team nel database
     *
     * @param array $args Argomenti di query
     * @return int Numero di team
     */
    public function count_teams($args = []) {
        global $wpdb;
        
        $defaults = [
            'game' => ''
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $this->get_table_name('teams');
        
        $query = "SELECT COUNT(*) FROM $table WHERE 1=1";
        $query_args = [];
        
        // Filtro per game
        if (!empty($args['game'])) {
            $query .= " AND game = %s";
            $query_args[] = $args['game'];
        }
        
        // Filtro per captain_id
        if (!empty($args['captain_id'])) {
            $query .= " AND captain_id = %d";
            $query_args[] = absint($args['captain_id']);
        }
        
        // Prepara la query con tutti i parametri
        if (!empty($query_args)) {
            $prepared_query = $wpdb->prepare($query, $query_args);
        } else {
            $prepared_query = $query;
        }
        
        // Esegui la query
        return (int) $wpdb->get_var($prepared_query);
    }
    
    /**
     * Inserisce un nuovo torneo nel database
     *
     * @param array $data Dati del torneo
     * @return int|false ID del torneo inserito o false in caso di errore
     */
    public function insert_tournament($data) {
        global $wpdb;
        
        // Verifica i dati obbligatori
        if (empty($data['name']) || empty($data['format']) || empty($data['created_by'])) {
            return false;
        }
        
        // Sanitizzazione
        $tournament_data = [
            'name' => sanitize_text_field($data['name']),
            'description' => isset($data['description']) ? wp_kses_post($data['description']) : '',
            'format' => sanitize_text_field($data['format']),
            'elimination_type' => isset($data['elimination_type']) ? sanitize_text_field($data['elimination_type']) : 'single',
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'pending',
            'start_date' => isset($data['start_date']) ? sanitize_text_field($data['start_date']) : null,
            'end_date' => isset($data['end_date']) ? sanitize_text_field($data['end_date']) : null,
            'max_teams' => isset($data['max_teams']) ? absint($data['max_teams']) : 8,
            'created_by' => absint($data['created_by']),
            'created_at' => current_time('mysql')
        ];
        
        $table = $this->get_table_name('tournaments');
        
        // Inserisci il torneo
        $result = $wpdb->insert(
            $table,
            $tournament_data,
            [
                '%s', // name
                '%s', // description
                '%s', // format
                '%s', // elimination_type
                '%s', // status
                '%s', // start_date
                '%s', // end_date
                '%d', // max_teams
                '%d', // created_by
                '%s'  // created_at
            ]
        );
        
        if ($result === false) {
            if (defined('ETO_DEBUG') && ETO_DEBUG) {
                error_log('[ETO] Errore inserimento torneo: ' . $wpdb->last_error);
            }
            return false;
        }
        
        $tournament_id = $wpdb->insert_id;
        
        // Registra l'azione nel log audit
        $this->log_action(
            'create',
            'tournament',
            $tournament_id,
            [
                'name' => $tournament_data['name'],
                'format' => $tournament_data['format']
            ],
            $tournament_data['created_by']
        );
        
        return $tournament_id;
    }
    
    /**
     * Aggiorna un torneo esistente
     *
     * @param int $tournament_id ID del torneo
     * @param array $data Dati da aggiornare
     * @return bool True se l'aggiornamento è riuscito, false altrimenti
     */
    public function update_tournament($tournament_id, $data) {
        global $wpdb;
        
        $tournament_id = absint($tournament_id);
        if ($tournament_id <= 0 || empty($data)) {
            return false;
        }
        
        $table = $this->get_table_name('tournaments');
        
        // Campi aggiornabili e relativi formati
        $allowed_fields = [
            'name' => '%s',
            'description' => '%s',
            'format' => '%s',
            'elimination_type' => '%s',
            'status' => '%s',
            'start_date' => '%s',
            'end_date' => '%s',
            'registration_start' => '%s',
            'registration_end' => '%s',
            'min_teams' => '%d',
            'max_teams' => '%d',
            'rules' => '%s',
            'prizes' => '%s',
            'featured_image' => '%s',
            'updated_at' => '%s'
        ];
        
        $update_data = [];
        $formats = [];
        
        // Prepara i dati da aggiornare
        foreach ($allowed_fields as $field => $format) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
                $formats[] = $format;
            }
        }
        
        // Aggiungi la data di aggiornamento
        if (!isset($update_data['updated_at'])) {
            $update_data['updated_at'] = current_time('mysql');
            $formats[] = '%s';
        }
        
        // Aggiorna il torneo
        $result = $wpdb->update(
            $table,
            $update_data,
            ['id' => $tournament_id],
            $formats,
            ['%d']
        );
        
        if ($result === false) {
            if (defined('ETO_DEBUG') && ETO_DEBUG) {
                error_log('[ETO] Errore aggiornamento torneo: ' . $wpdb->last_error);
            }
            return false;
        }
        
        // Registra l'azione nel log audit
        $this->log_action(
            'update',
            'tournament',
            $tournament_id,
            $update_data,
            get_current_user_id()
        );
        
        return true;
    }
    
    /**
     * Elimina un torneo
     *
     * @param int $tournament_id ID del torneo
     * @return bool True se l'eliminazione è riuscita, false altrimenti
     */
    public function delete_tournament($tournament_id) {
        global $wpdb;
        
        $tournament_id = absint($tournament_id);
        if ($tournament_id <= 0) {
            return false;
        }
        
        $table = $this->get_table_name('tournaments');
        
        // Elimina il torneo
        $result = $wpdb->delete(
            $table,
            ['id' => $tournament_id],
            ['%d']
        );
        
        if ($result === false) {
            if (defined('ETO_DEBUG') && ETO_DEBUG) {
                error_log('[ETO] Errore eliminazione torneo: ' . $wpdb->last_error);
            }
            return false;
        }
        
        // Registra l'azione nel log audit
        $this->log_action(
            'delete',
            'tournament',
            $tournament_id,
            [],
            get_current_user_id()
        );
        
        return true;
    }
    
    /**
     * Ottiene un singolo team dal database
     *
     * @param int $team_id ID del team
     * @return object|false Oggetto team o false se non trovato
     */
    public function get_team($team_id) {
        global $wpdb;
        
        $table = $this->get_table_name('teams');
        
        $team = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $team_id
            )
        );
        
        return $team;
    }
    
    /**
     * Ottiene una lista di team dal database
     *
     * @param array $args Argomenti di query
     * @return array Lista di team
     */
    public function get_teams($args = []) {
        global $wpdb;
        
        $defaults = [
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'name',
            'order' => 'ASC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Sanitizzazione
        $args['limit'] = absint($args['limit']);
        $args['offset'] = absint($args['offset']);
        
        // Whitelist per orderby
        $allowed_orderby = ['id', 'name', 'game', 'created_at'];
        if (!in_array($args['orderby'], $allowed_orderby)) {
            $args['orderby'] = 'name';
        }
        
        // Whitelist per order
        $args['order'] = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        
        $table = $this->get_table_name('teams');
        
        $query = "SELECT * FROM $table WHERE 1=1";
        $query_args = [];
        
        // Filtro per game
        if (!empty($args['game'])) {
            $query .= " AND game = %s";
            $query_args[] = $args['game'];
        }
        
        // Filtro per captain_id
        if (!empty($args['captain_id'])) {
            $query .= " AND captain_id = %d";
            $query_args[] = absint($args['captain_id']);
        }
        
        // Ordinamento
        $query .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        // Limite
        $query .= " LIMIT %d OFFSET %d";
        $query_args[] = $args['limit'];
        $query_args[] = $args['offset'];
        
        // Prepara la query con tutti i parametri
        $prepared_query = $wpdb->prepare($query, $query_args);
        
        // Esegui la query
        $results = $wpdb->get_results($prepared_query);
        
        return $results;
    }
    
    /**
     * Inserisce un nuovo team nel database
     *
     * @param array $data Dati del team
     * @return int|false ID del team inserito o false in caso di errore
     */
    public function insert_team($data) {
        global $wpdb;
        
        // Verifica i dati obbligatori
        if (empty($data['name']) || empty($data['game']) || empty($data['captain_id'])) {
            return false;
        }
        
        // Sanitizzazione
        $team_data = [
            'name' => sanitize_text_field($data['name']),
            'description' => isset($data['description']) ? wp_kses_post($data['description']) : '',
            'game' => sanitize_text_field($data['game']),
            'logo_url' => isset($data['logo_url']) ? esc_url_raw($data['logo_url']) : '',
            'captain_id' => absint($data['captain_id']),
            'email' => isset($data['email']) ? sanitize_email($data['email']) : '',
            'website' => isset($data['website']) ? esc_url_raw($data['website']) : '',
            'social_media' => isset($data['social_media']) ? json_encode($data['social_media']) : '{}',
            'created_by' => isset($data['created_by']) ? absint($data['created_by']) : get_current_user_id(),
            'created_at' => current_time('mysql')
        ];
        
        // Genera uno slug univoco
        $table = $this->get_table_name('teams');
        $team_data['slug'] = eto_generate_unique_slug($team_data['name'], $table);
        
        // Inserisci il team
        $result = $wpdb->insert(
            $table,
            $team_data,
            [
                '%s', // name
                '%s', // slug
                '%s', // description
                '%s', // game
                '%s', // logo_url
                '%d', // captain_id
                '%s', // email
                '%s', // website
                '%s', // social_media
                '%d', // created_by
                '%s'  // created_at
            ]
        );
        
        if ($result === false) {
            if (defined('ETO_DEBUG') && ETO_DEBUG) {
                error_log('[ETO] Errore inserimento team: ' . $wpdb->last_error);
            }
            return false;
        }
        
        $team_id = $wpdb->insert_id;
        
        // Registra l'azione nel log audit
        $this->log_action(
            'create',
            'team',
            $team_id,
            [
                'name' => $team_data['name'],
                'game' => $team_data['game']
            ],
            $team_data['created_by']
        );
        
        return $team_id;
    }
    
    /**
     * Aggiorna un team esistente
     *
     * @param int $team_id ID del team
     * @param array $data Dati da aggiornare
     * @return bool True se l'aggiornamento è riuscito, false altrimenti
     */
    public function update_team($team_id, $data) {
        global $wpdb;
        
        $team_id = absint($team_id);
        if ($team_id <= 0 || empty($data)) {
            return false;
        }
        
        $table = $this->get_table_name('teams');
        
        // Campi aggiornabili e relativi formati
        $allowed_fields = [
            'name' => '%s',
            'description' => '%s',
            'game' => '%s',
            'logo_url' => '%s',
            'captain_id' => '%d',
            'email' => '%s',
            'website' => '%s',
            'social_media' => '%s',
            'updated_at' => '%s'
        ];
        
        $update_data = [];
        $formats = [];
        
        // Prepara i dati da aggiornare
        foreach ($allowed_fields as $field => $format) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
                $formats[] = $format;
            }
        }
        
        // Aggiorna lo slug se il nome è cambiato
        if (isset($update_data['name'])) {
            $update_data['slug'] = eto_generate_unique_slug($update_data['name'], $table, $team_id);
            $formats[] = '%s';
        }
        
        // Aggiungi la data di aggiornamento
        if (!isset($update_data['updated_at'])) {
            $update_data['updated_at'] = current_time('mysql');
            $formats[] = '%s';
        }
        
        // Aggiorna il team
        $result = $wpdb->update(
            $table,
            $update_data,
            ['id' => $team_id],
            $formats,
            ['%d']
        );
        
        if ($result === false) {
            if (defined('ETO_DEBUG') && ETO_DEBUG) {
                error_log('[ETO] Errore aggiornamento team: ' . $wpdb->last_error);
            }
            return false;
        }
        
        // Registra l'azione nel log audit
        $this->log_action(
            'update',
            'team',
            $team_id,
            $update_data,
            get_current_user_id()
        );
        
        return true;
    }
    
    /**
     * Elimina un team
     *
     * @param int $team_id ID del team
     * @return bool True se l'eliminazione è riuscita, false altrimenti
     */
    public function delete_team($team_id) {
        global $wpdb;
        
        $team_id = absint($team_id);
        if ($team_id <= 0) {
            return false;
        }
        
        $table = $this->get_table_name('teams');
        
        // Elimina il team
        $result = $wpdb->delete(
            $table,
            ['id' => $team_id],
            ['%d']
        );
        
        if ($result === false) {
            if (defined('ETO_DEBUG') && ETO_DEBUG) {
                error_log('[ETO] Errore eliminazione team: ' . $wpdb->last_error);
            }
            return false;
        }
        
        // Registra l'azione nel log audit
        $this->log_action(
            'delete',
            'team',
            $team_id,
            [],
            get_current_user_id()
        );
        
        return true;
    }
    
    /**
     * Registra un'azione nel log audit
     *
     * @param string $action Azione eseguita
     * @param string $object_type Tipo di oggetto
     * @param int $object_id ID dell'oggetto
     * @param array $data Dati aggiuntivi
     * @param int $user_id ID dell'utente
     * @return bool True se il log è stato registrato, false altrimenti
     */
    public function log_action($action, $object_type, $object_id, $data = [], $user_id = 0) {
        global $wpdb;
        
        if (empty($user_id)) {
            $user_id = get_current_user_id();
        }
        
        $table = $this->get_table_name('logs');
        
        $result = $wpdb->insert(
            $table,
            [
                'action' => $action,
                'object_type' => $object_type,
                'object_id' => $object_id,
                'data' => json_encode($data),
                'user_id' => $user_id,
                'created_at' => current_time('mysql')
            ],
            [
                '%s', // action
                '%s', // object_type
                '%d', // object_id
                '%s', // data
                '%d', // user_id
                '%s'  // created_at
            ]
        );
        
        return $result !== false;
    }
}
