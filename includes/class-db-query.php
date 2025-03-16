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
     // Commentiamo temporaneamente la creazione dell'istanza di ETO_Database_Manager
    // $this->db_manager = new ETO_Database_Manager();
    
    // Utilizziamo direttamente l'oggetto wpdb globale
    global $wpdb;
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
            $query_args[] = $args['captain_id'];
        }
        
        // Prepara la query con tutti i parametri
        $prepared_query = $wpdb->prepare($query, $query_args);
        
        // Esegui la query
        return (int) $wpdb->get_var($prepared_query);
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
            'game' => '',
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
            $query_args[] = $args['captain_id'];
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
     * Ottiene un team dal suo slug
     *
     * @param string $slug Slug del team
     * @return object|false Oggetto team o false se non trovato
     */
    public function get_team_by_slug($slug) {
        global $wpdb;
        
        $table = $this->get_table_name('teams');
        
        $team = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE slug = %s",
                $slug
            )
        );
        
        return $team;
    }
    
    /**
     * Ottiene i membri di un team
     *
     * @param int $team_id ID del team
     * @return array Lista di membri del team
     */
    public function get_team_members($team_id) {
        global $wpdb;
        
        $table = $this->get_table_name('team_members');
        
        $members = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE team_id = %d ORDER BY role ASC",
                $team_id
            )
        );
        
        return $members;
    }
    
    /**
     * Ottiene le partite di un torneo
     *
     * @param int $tournament_id ID del torneo
     * @param array $args Argomenti di query
     * @return array Lista di partite
     */
    public function get_tournament_matches($tournament_id, $args = []) {
        global $wpdb;
        
        $defaults = [
            'round' => 0,
            'status' => '',
            'orderby' => 'match_number',
            'order' => 'ASC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Whitelist per orderby
        $allowed_orderby = ['id', 'round', 'match_number', 'scheduled_date'];
        if (!in_array($args['orderby'], $allowed_orderby)) {
            $args['orderby'] = 'match_number';
        }
        
        // Whitelist per order
        $args['order'] = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        
        $table = $this->get_table_name('matches');
        
        $query = "SELECT * FROM $table WHERE tournament_id = %d";
        $query_args = [$tournament_id];
        
        // Filtro per round
        if (!empty($args['round'])) {
            $query .= " AND round = %d";
            $query_args[] = $args['round'];
        }
        
        // Filtro per status
        if (!empty($args['status'])) {
            $query .= " AND status = %s";
            $query_args[] = $args['status'];
        }
        
        // Ordinamento
        $query .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        // Prepara la query con tutti i parametri
        $prepared_query = $wpdb->prepare($query, $query_args);
        
        // Esegui la query
        $results = $wpdb->get_results($prepared_query);
        
        return $results;
    }
    
    /**
     * Ottiene una singola partita dal database
     *
     * @param int $match_id ID della partita
     * @return object|false Oggetto partita o false se non trovato
     */
    public function get_match($match_id) {
        global $wpdb;
        
        $table = $this->get_table_name('matches');
        
        $match = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $match_id
            )
        );
        
        return $match;
    }
    
    /**
     * Ottiene le iscrizioni a un torneo
     *
     * @param int $tournament_id ID del torneo
     * @param string $status Status dell'iscrizione (opzionale)
     * @return array Lista di iscrizioni
     */
    public function get_tournament_registrations($tournament_id, $status = '') {
        global $wpdb;
        
        $table = $this->get_table_name('tournament_registrations');
        
        $query = "SELECT * FROM $table WHERE tournament_id = %d";
        $query_args = [$tournament_id];
        
        if (!empty($status)) {
            $query .= " AND status = %s";
            $query_args[] = $status;
        }
        
        $query .= " ORDER BY registered_at ASC";
        
        // Prepara la query con tutti i parametri
        $prepared_query = $wpdb->prepare($query, $query_args);
        
        // Esegui la query
        $results = $wpdb->get_results($prepared_query);
        
        return $results;
    }
    
    /**
     * Verifica se un team è iscritto a un torneo
     *
     * @param int $tournament_id ID del torneo
     * @param int $team_id ID del team
     * @return bool True se il team è iscritto, false altrimenti
     */
    public function is_team_registered($tournament_id, $team_id) {
        global $wpdb;
        
        $table = $this->get_table_name('tournament_registrations');
        
        $registration = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE tournament_id = %d AND team_id = %d",
                $tournament_id,
                $team_id
            )
        );
        
        return !empty($registration);
    }
    
    /**
     * Inserisce un nuovo torneo nel database
     *
     * @param array $data Dati del torneo
     * @return int|false ID del torneo inserito o false in caso di errore
     */
    public function insert_tournament($data) {
        global $wpdb;
        
        $table = $this->get_table_name('tournaments');
        
        // Assicura che i campi obbligatori siano presenti
        if (empty($data['name']) || empty($data['slug']) || empty($data['game']) || empty($data['format'])) {
            return false;
        }
        
        // Verifica se lo slug esiste già
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE slug = %s",
                $data['slug']
            )
        );
        
        if ($exists) {
            // Genera uno slug univoco
            $original_slug = $data['slug'];
            $counter = 1;
            
            do {
                $data['slug'] = $original_slug . '-' . $counter;
                $counter++;
                
                $exists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM $table WHERE slug = %s",
                        $data['slug']
                    )
                );
            } while ($exists);
        }
        
        // Imposta i valori predefiniti
        $data = wp_parse_args($data, [
            'description' => '',
            'start_date' => current_time('mysql'),
            'end_date' => current_time('mysql'),
            'registration_start' => null,
            'registration_end' => null,
            'min_teams' => 2,
            'max_teams' => 16,
            'rules' => '',
            'prizes' => '',
            'featured_image' => '',
            'status' => 'draft',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]);
        
        // Inserisci il torneo
        $wpdb->insert(
            $table,
            $data,
            [
                '%s', // name
                '%s', // slug
                '%s', // description
                '%s', // game
                '%s', // format
                '%s', // start_date
                '%s', // end_date
                '%s', // registration_start
                '%s', // registration_end
                '%d', // min_teams
                '%d', // max_teams
                '%s', // rules
                '%s', // prizes
                '%s', // featured_image
                '%s', // status
                '%d', // created_by
                '%s', // created_at
                '%s'  // updated_at
            ]
        );
        
        if ($wpdb->last_error) {
            return false;
        }
        
        return $wpdb->insert_id;
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
        
        $table = $this->get_table_name('tournaments');
        
        // Verifica se il torneo esiste
        $tournament = $this->get_tournament($tournament_id);
        if (!$tournament) {
            return false;
        }
        
        // Verifica se lo slug è cambiato e se esiste già
        if (!empty($data['slug']) && $data['slug'] !== $tournament->slug) {
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE slug = %s AND id != %d",
                    $data['slug'],
                    $tournament_id
                )
            );
            
            if ($exists) {
                // Genera uno slug univoco
                $original_slug = $data['slug'];
                $counter = 1;
                
                do {
                    $data['slug'] = $original_slug . '-' . $counter;
                    $counter++;
                    
                    $exists = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT COUNT(*) FROM $table WHERE slug = %s AND id != %d",
                            $data['slug'],
                            $tournament_id
                        )
                    );
                } while ($exists);
            }
        }
        
        // Imposta la data di aggiornamento
        $data['updated_at'] = current_time('mysql');
        
        // Aggiorna il torneo
        $result = $wpdb->update(
            $table,
            $data,
            ['id' => $tournament_id],
            '%s',
            '%d'
        );
        
        return $result !== false;
    }
    
    /**
     * Elimina un torneo
     *
     * @param int $tournament_id ID del torneo
     * @return bool True se l'eliminazione è riuscita, false altrimenti
     */
    public function delete_tournament($tournament_id) {
        global $wpdb;
        
        $table = $this->get_table_name('tournaments');
        
        // Verifica se il torneo esiste
        $tournament = $this->get_tournament($tournament_id);
        if (!$tournament) {
            return false;
        }
        
        // Elimina le iscrizioni al torneo
        $registrations_table = $this->get_table_name('tournament_registrations');
        $wpdb->delete(
            $registrations_table,
            ['tournament_id' => $tournament_id],
            '%d'
        );
        
        // Elimina le partite del torneo
        $matches_table = $this->get_table_name('matches');
        $wpdb->delete(
            $matches_table,
            ['tournament_id' => $tournament_id],
            '%d'
        );
        
        // Elimina il torneo
        $result = $wpdb->delete(
            $table,
            ['id' => $tournament_id],
            '%d'
        );
        
        return $result !== false;
    }
    
    /**
     * Inserisce un nuovo team nel database
     *
     * @param array $data Dati del team
     * @return int|false ID del team inserito o false in caso di errore
     */
    public function insert_team($data) {
        global $wpdb;
        
        $table = $this->get_table_name('teams');
        
        // Assicura che i campi obbligatori siano presenti
        if (empty($data['name']) || empty($data['slug']) || empty($data['game']) || empty($data['captain_id'])) {
            return false;
        }
        
        // Verifica se lo slug esiste già
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE slug = %s",
                $data['slug']
            )
        );
        
        if ($exists) {
            // Genera uno slug univoco
            $original_slug = $data['slug'];
            $counter = 1;
            
            do {
                $data['slug'] = $original_slug . '-' . $counter;
                $counter++;
                
                $exists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM $table WHERE slug = %s",
                        $data['slug']
                    )
                );
            } while ($exists);
        }
        
        // Imposta i valori predefiniti
        $data = wp_parse_args($data, [
            'description' => '',
            'logo_url' => '',
            'email' => '',
            'website' => '',
            'social_media' => '',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]);
        
        // Inserisci il team
        $wpdb->insert(
            $table,
            $data,
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
                '%s', // created_at
                '%s'  // updated_at
            ]
        );
        
        if ($wpdb->last_error) {
            return false;
        }
        
        return $wpdb->insert_id;
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
        
        $table = $this->get_table_name('teams');
        
        // Verifica se il team esiste
        $team = $this->get_team($team_id);
        if (!$team) {
            return false;
        }
        
        // Verifica se lo slug è cambiato e se esiste già
        if (!empty($data['slug']) && $data['slug'] !== $team->slug) {
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE slug = %s AND id != %d",
                    $data['slug'],
                    $team_id
                )
            );
            
            if ($exists) {
                // Genera uno slug univoco
                $original_slug = $data['slug'];
                $counter = 1;
                
                do {
                    $data['slug'] = $original_slug . '-' . $counter;
                    $counter++;
                    
                    $exists = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT COUNT(*) FROM $table WHERE slug = %s AND id != %d",
                            $data['slug'],
                            $team_id
                        )
                    );
                } while ($exists);
            }
        }
        
        // Imposta la data di aggiornamento
        $data['updated_at'] = current_time('mysql');
        
        // Aggiorna il team
        $result = $wpdb->update(
            $table,
            $data,
            ['id' => $team_id],
            '%s',
            '%d'
        );
        
        return $result !== false;
    }
    
    /**
     * Elimina un team
     *
     * @param int $team_id ID del team
     * @return bool True se l'eliminazione è riuscita, false altrimenti
     */
    public function delete_team($team_id) {
        global $wpdb;
        
        $table = $this->get_table_name('teams');
        
        // Verifica se il team esiste
        $team = $this->get_team($team_id);
        if (!$team) {
            return false;
        }
        
        // Elimina le iscrizioni del team ai tornei
        $registrations_table = $this->get_table_name('tournament_registrations');
        $wpdb->delete(
            $registrations_table,
            ['team_id' => $team_id],
            '%d'
        );
        
        // Elimina i membri del team
        $members_table = $this->get_table_name('team_members');
        $wpdb->delete(
            $members_table,
            ['team_id' => $team_id],
            '%d'
        );
        
        // Elimina il team
        $result = $wpdb->delete(
            $table,
            ['id' => $team_id],
            '%d'
        );
        
        return $result !== false;
    }
    
    /**
     * Iscrive un team a un torneo
     *
     * @param int $tournament_id ID del torneo
     * @param int $team_id ID del team
     * @param string $status Status dell'iscrizione
     * @return int|false ID dell'iscrizione o false in caso di errore
     */
    public function register_team_to_tournament($tournament_id, $team_id, $status = 'pending') {
        global $wpdb;
        
        $table = $this->get_table_name('tournament_registrations');
        
        // Verifica se il torneo e il team esistono
        $tournament = $this->get_tournament($tournament_id);
        $team = $this->get_team($team_id);
        
        if (!$tournament || !$team) {
            return false;
        }
        
        // Verifica se il team è già iscritto
        if ($this->is_team_registered($tournament_id, $team_id)) {
            return false;
        }
        
        // Inserisci l'iscrizione
        $wpdb->insert(
            $table,
            [
                'tournament_id' => $tournament_id,
                'team_id' => $team_id,
                'status' => $status,
                'registered_at' => current_time('mysql')
            ],
            [
                '%d', // tournament_id
                '%d', // team_id
                '%s', // status
                '%s'  // registered_at
            ]
        );
        
        if ($wpdb->last_error) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Aggiorna lo status di un'iscrizione
     *
     * @param int $registration_id ID dell'iscrizione
     * @param string $status Nuovo status
     * @return bool True se l'aggiornamento è riuscito, false altrimenti
     */
    public function update_registration_status($registration_id, $status) {
        global $wpdb;
        
        $table = $this->get_table_name('tournament_registrations');
        
        // Aggiorna lo status
        $result = $wpdb->update(
            $table,
            ['status' => $status],
            ['id' => $registration_id],
            '%s',
            '%d'
        );
        
        return $result !== false;
    }
    
    /**
     * Inserisce una nuova partita nel database
     *
     * @param array $data Dati della partita
     * @return int|false ID della partita inserita o false in caso di errore
     */
    public function insert_match($data) {
        global $wpdb;
        
        $table = $this->get_table_name('matches');
        
        // Assicura che i campi obbligatori siano presenti
        if (empty($data['tournament_id']) || !isset($data['round']) || !isset($data['match_number'])) {
            return false;
        }
        
        // Imposta i valori predefiniti
        $data = wp_parse_args($data, [
            'team1_id' => null,
            'team2_id' => null,
            'team1_score' => 0,
            'team2_score' => 0,
            'winner_id' => null,
            'status' => 'pending',
            'scheduled_date' => null,
            'completed_date' => null,
            'notes' => ''
        ]);
        
        // Inserisci la partita
        $wpdb->insert(
            $table,
            $data,
            [
                '%d', // tournament_id
                '%d', // round
                '%d', // match_number
                '%d', // team1_id
                '%d', // team2_id
                '%d', // team1_score
                '%d', // team2_score
                '%d', // winner_id
                '%s', // status
                '%s', // scheduled_date
                '%s', // completed_date
                '%s'  // notes
            ]
        );
        
        if ($wpdb->last_error) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Aggiorna una partita esistente
     *
     * @param int $match_id ID della partita
     * @param array $data Dati da aggiornare
     * @return bool True se l'aggiornamento è riuscito, false altrimenti
     */
    public function update_match($match_id, $data) {
        global $wpdb;
        
        $table = $this->get_table_name('matches');
        
        // Verifica se la partita esiste
        $match = $this->get_match($match_id);
        if (!$match) {
            return false;
        }
        
        // Aggiorna la partita
        $result = $wpdb->update(
            $table,
            $data,
            ['id' => $match_id],
            '%s',
            '%d'
        );
        
        return $result !== false;
    }
    
    /**
     * Elimina una partita
     *
     * @param int $match_id ID della partita
     * @return bool True se l'eliminazione è riuscita, false altrimenti
     */
    public function delete_match($match_id) {
        global $wpdb;
        
        $table = $this->get_table_name('matches');
        
        // Verifica se la partita esiste
        $match = $this->get_match($match_id);
        if (!$match) {
            return false;
        }
        
        // Elimina la partita
        $result = $wpdb->delete(
            $table,
            ['id' => $match_id],
            '%d'
        );
        
        return $result !== false;
    }
}
