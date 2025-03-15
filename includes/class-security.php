<?php
/**
 * Classe per la gestione della sicurezza
 * 
 * Implementa funzionalità di sicurezza base per il plugin
 * 
 * @package ETO
 * @since 2.5.0
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Security {
    
    /**
     * Costruttore
     */
    public function __construct() {
        // Nessuna operazione specifica nel costruttore
    }
    
    /**
     * Verifica se un utente ha i permessi per un'azione
     * 
     * @param string $action Nome dell'azione
     * @param int $user_id ID dell'utente (opzionale, usa l'utente corrente se non specificato)
     * @return bool True se l'utente ha i permessi
     */
    public function check_permission($action, $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // Gli amministratori hanno sempre tutti i permessi
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Verifica i permessi specifici
        switch ($action) {
            case 'manage_tournaments':
                return user_can($user_id, 'edit_posts');
                
            case 'manage_teams':
                return user_can($user_id, 'edit_posts');
                
            case 'manage_matches':
                return user_can($user_id, 'edit_posts');
                
            default:
                return false;
        }
    }
    
    /**
     * Verifica un nonce
     * 
     * @param string $nonce Nonce da verificare
     * @param string $action Nome dell'azione
     * @return bool True se il nonce è valido
     */
    public function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, 'eto-' . $action);
    }
    
    /**
     * Sanitizza un input
     * 
     * @param mixed $input Input da sanitizzare
     * @param string $type Tipo di input (text, email, url, int, float)
     * @return mixed Input sanitizzato
     */
    public function sanitize_input($input, $type = 'text') {
        switch ($type) {
            case 'text':
                return sanitize_text_field($input);
                
            case 'email':
                return sanitize_email($input);
                
            case 'url':
                return esc_url_raw($input);
                
            case 'int':
                return intval($input);
                
            case 'float':
                return floatval($input);
                
            case 'html':
                return wp_kses_post($input);
                
            default:
                return sanitize_text_field($input);
        }
    }
    
    /**
     * Genera un token di sicurezza
     * 
     * @param string $action Nome dell'azione
     * @return string Token generato
     */
    public function generate_token($action) {
        return wp_create_nonce('eto-' . $action);
    }
}

/**
 * Classe per la gestione della sicurezza avanzata
 * 
 * Estende la classe di sicurezza base con funzionalità avanzate
 * 
 * @package ETO
 * @since 2.5.0
 */
class ETO_Security_Enhanced extends ETO_Security {
    
    /**
     * Costruttore
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Verifica se un utente ha i permessi per un'azione su un oggetto specifico
     * 
     * @param string $action Nome dell'azione
     * @param int $object_id ID dell'oggetto
     * @param string $object_type Tipo di oggetto (tournament, team, match)
     * @param int $user_id ID dell'utente (opzionale, usa l'utente corrente se non specificato)
     * @return bool True se l'utente ha i permessi
     */
    public function check_object_permission($action, $object_id, $object_type, $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // Gli amministratori hanno sempre tutti i permessi
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Verifica i permessi specifici per tipo di oggetto
        switch ($object_type) {
            case 'tournament':
                return $this->check_tournament_permission($action, $object_id, $user_id);
                
            case 'team':
                return $this->check_team_permission($action, $object_id, $user_id);
                
            case 'match':
                return $this->check_match_permission($action, $object_id, $user_id);
                
            default:
                return false;
        }
    }
    
    /**
     * Verifica se un utente ha i permessi per un'azione su un torneo
     * 
     * @param string $action Nome dell'azione
     * @param int $tournament_id ID del torneo
     * @param int $user_id ID dell'utente
     * @return bool True se l'utente ha i permessi
     */
    private function check_tournament_permission($action, $tournament_id, $user_id) {
        $tournament = new ETO_Tournament_Model($tournament_id);
        
        if (!$tournament) {
            return false;
        }
        
        // Il creatore del torneo ha tutti i permessi
        if ($tournament->get('created_by') == $user_id) {
            return true;
        }
        
        // Verifica i permessi specifici
        switch ($action) {
            case 'view':
                return true; // Tutti possono vedere i tornei
                
            case 'edit':
            case 'delete':
                return false; // Solo il creatore può modificare o eliminare
                
            default:
                return false;
        }
    }
    
    /**
     * Verifica se un utente ha i permessi per un'azione su un team
     * 
     * @param string $action Nome dell'azione
     * @param int $team_id ID del team
     * @param int $user_id ID dell'utente
     * @return bool True se l'utente ha i permessi
     */
    private function check_team_permission($action, $team_id, $user_id) {
        $team = new ETO_Team_Model($team_id);
        
        if (!$team) {
            return false;
        }
        
        // Il capitano del team ha tutti i permessi
        if ($team->get('captain_id') == $user_id) {
            return true;
        }
        
        // Verifica se l'utente è un membro del team
        $is_member = $team->is_member($user_id);
        
        // Verifica i permessi specifici
        switch ($action) {
            case 'view':
                return true; // Tutti possono vedere i team
                
            case 'edit':
            case 'delete':
                return false; // Solo il capitano può modificare o eliminare
                
            case 'leave':
                return $is_member; // Solo i membri possono lasciare il team
                
            default:
                return false;
        }
    }
    
    /**
     * Verifica se un utente ha i permessi per un'azione su un match
     * 
     * @param string $action Nome dell'azione
     * @param int $match_id ID del match
     * @param int $user_id ID dell'utente
     * @return bool True se l'utente ha i permessi
     */
    private function check_match_permission($action, $match_id, $user_id) {
        $match = new ETO_Match_Model($match_id);
        
        if (!$match) {
            return false;
        }
        
        $team1_id = $match->get('team1_id');
        $team2_id = $match->get('team2_id');
        
        $team1 = $team1_id ? new ETO_Team_Model($team1_id) : null;
        $team2 = $team2_id ? new ETO_Team_Model($team2_id) : null;
        
        $is_team1_captain = $team1 && $team1->get('captain_id') == $user_id;
        $is_team2_captain = $team2 && $team2->get('captain_id') == $user_id;
        
        // Verifica i permessi specifici
        switch ($action) {
            case 'view':
                return true; // Tutti possono vedere i match
                
            case 'report_result':
                return $is_team1_captain || $is_team2_captain; // Solo i capitani possono riportare i risultati
                
            case 'edit':
            case 'delete':
                return false; // Solo gli admin possono modificare o eliminare
                
            default:
                return false;
        }
    }
}

/**
 * Classe per la gestione delle query al database sicure
 * 
 * Implementa funzionalità per eseguire query al database in modo sicuro
 * 
 * @package ETO
 * @since 2.5.0
 */
class ETO_Security_DB_Query {
    
    /**
     * Costruttore
     */
    public function __construct() {
        // Nessuna operazione specifica nel costruttore
    }
    
    /**
     * Esegue una query SELECT sicura
     * 
     * @param string $table Nome della tabella
     * @param array $columns Colonne da selezionare
     * @param array $where Condizioni WHERE (opzionale)
     * @param string $order_by Ordinamento (opzionale)
     * @param int $limit Limite di risultati (opzionale)
     * @param int $offset Offset dei risultati (opzionale)
     * @return array Risultati della query
     */
    public function select($table, $columns, $where = [], $order_by = '', $limit = 0, $offset = 0) {
        global $wpdb;
        
        // Prepara la query
        $query = "SELECT " . implode(', ', $columns) . " FROM $table";
        
        // Aggiungi le condizioni WHERE
        if (!empty($where)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($where as $column => $value) {
                $conditions[] = "$column = %s";
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        // Aggiungi l'ordinamento
        if (!empty($order_by)) {
            $query .= " ORDER BY $order_by";
        }
        
        // Aggiungi il limite
        if ($limit > 0) {
            $query .= " LIMIT $limit";
            
            if ($offset > 0) {
                $query .= " OFFSET $offset";
            }
        }
        
        // Prepara i parametri
        $params = [];
        
        if (!empty($where)) {
            foreach ($where as $value) {
                $params[] = $value;
            }
        }
        
        // Esegui la query
        if (empty($params)) {
            return $wpdb->get_results($query);
        } else {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }
    }
    
    /**
     * Esegue una query INSERT sicura
     * 
     * @param string $table Nome della tabella
     * @param array $data Dati da inserire
     * @return int|false ID dell'elemento inserito o false in caso di errore
     */
    public function insert($table, $data) {
        global $wpdb;
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Esegue una query UPDATE sicura
     * 
     * @param string $table Nome della tabella
     * @param array $data Dati da aggiornare
     * @param array $where Condizioni WHERE
     * @return int|false Numero di righe aggiornate o false in caso di errore
     */
    public function update($table, $data, $where) {
        global $wpdb;
        
        return $wpdb->update($table, $data, $where);
    }
    
    /**
     * Esegue una query DELETE sicura
     * 
     * @param string $table Nome della tabella
     * @param array $where Condizioni WHERE
     * @return int|false Numero di righe eliminate o false in caso di errore
     */
    public function delete($table, $where) {
        global $wpdb;
        
        return $wpdb->delete($table, $where);
    }
}
