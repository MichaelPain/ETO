<?php
/**
 * Classe per la gestione sicura delle query al database
 * 
 * Fornisce metodi sicuri per interagire con il database
 * 
 * @package ETO
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_DB_Query_Secure {
    
    /**
     * Istanza di wpdb
     *
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * Costruttore
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Esegue una query preparata
     *
     * @param string $query Query SQL con placeholder
     * @param mixed ...$args Argomenti per la query
     * @return mixed Risultato della query
     */
    public function prepare($query, ...$args) {
        if (empty($args)) {
            return $query;
        }
        
        return $this->wpdb->prepare($query, ...$args);
    }
    
    /**
     * Esegue una query
     *
     * @param string $query Query SQL
     * @return mixed Risultato della query
     */
    public function query($query) {
        return $this->wpdb->query($query);
    }
    
    /**
     * Ottiene una singola variabile dal database
     *
     * @param string $query Query SQL
     * @return mixed Valore della variabile
     */
    public function get_var($query) {
        return $this->wpdb->get_var($query);
    }
    
    /**
     * Ottiene una singola riga dal database
     *
     * @param string|array $table Nome della tabella o query SQL
     * @param array $where Condizioni WHERE (opzionale)
     * @param string $output_type Tipo di output (ARRAY_A, ARRAY_N, OBJECT)
     * @return array|object|null Riga del database
     */
    public function get_row($table, $where = [], $output_type = ARRAY_A) {
        if (is_string($table) && !empty($where)) {
            // Costruisci la query
            $query = "SELECT * FROM {$this->wpdb->prefix}$table WHERE ";
            $conditions = [];
            $values = [];
            
            foreach ($where as $field => $value) {
                $conditions[] = "$field = %s";
                $values[] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
            $query .= " LIMIT 1";
            
            // Prepara la query
            $query = $this->prepare($query, ...$values);
        } else {
            // Usa la query fornita
            $query = $table;
        }
        
        return $this->wpdb->get_row($query, $output_type);
    }
    
    /**
     * Ottiene più righe dal database
     *
     * @param string|array $table Nome della tabella o query SQL
     * @param array $where Condizioni WHERE (opzionale)
     * @param string $orderby Clausola ORDER BY (opzionale)
     * @param int $limit Limite di risultati (opzionale)
     * @param int $offset Offset dei risultati (opzionale)
     * @param string $output_type Tipo di output (ARRAY_A, ARRAY_N, OBJECT)
     * @return array Array di righe
     */
    public function get_results($table, $where = [], $orderby = '', $limit = 0, $offset = 0, $output_type = ARRAY_A) {
        if (is_string($table) && (!is_array($where) || !empty($where))) {
            // Costruisci la query
            $query = "SELECT * FROM {$this->wpdb->prefix}$table";
            $values = [];
            
            if (!empty($where)) {
                $query .= " WHERE ";
                $conditions = [];
                
                foreach ($where as $field => $value) {
                    $conditions[] = "$field = %s";
                    $values[] = $value;
                }
                
                $query .= implode(' AND ', $conditions);
            }
            
            if (!empty($orderby)) {
                $query .= " ORDER BY $orderby";
            }
            
            if ($limit > 0) {
                $query .= " LIMIT %d";
                $values[] = $limit;
                
                if ($offset > 0) {
                    $query .= " OFFSET %d";
                    $values[] = $offset;
                }
            }
            
            // Prepara la query
            $query = $this->prepare($query, ...$values);
        } else {
            // Usa la query fornita
            $query = $table;
        }
        
        return $this->wpdb->get_results($query, $output_type);
    }
    
    /**
     * Inserisce una riga nel database
     *
     * @param string $table Nome della tabella
     * @param array $data Dati da inserire
     * @return int|false ID della riga inserita o false in caso di errore
     */
    public function insert($table, $data) {
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . $table,
            $data
        );
        
        if ($result === false) {
            $this->log_error("Errore nell'inserimento nella tabella $table: " . $this->wpdb->last_error);
            return false;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Aggiorna una riga nel database
     *
     * @param string $table Nome della tabella
     * @param array $data Dati da aggiornare
     * @param array $where Condizioni WHERE
     * @return int|false Numero di righe aggiornate o false in caso di errore
     */
    public function update($table, $data, $where) {
        $result = $this->wpdb->update(
            $this->wpdb->prefix . $table,
            $data,
            $where
        );
        
        if ($result === false) {
            $this->log_error("Errore nell'aggiornamento della tabella $table: " . $this->wpdb->last_error);
            return false;
        }
        
        return $result;
    }
    
    /**
     * Elimina una riga dal database
     *
     * @param string $table Nome della tabella
     * @param array $where Condizioni WHERE
     * @return int|false Numero di righe eliminate o false in caso di errore
     */
    public function delete($table, $where) {
        $result = $this->wpdb->delete(
            $this->wpdb->prefix . $table,
            $where
        );
        
        if ($result === false) {
            $this->log_error("Errore nell'eliminazione dalla tabella $table: " . $this->wpdb->last_error);
            return false;
        }
        
        return $result;
    }
    
    /**
     * Conta le righe nel database
     *
     * @param string $table Nome della tabella
     * @param array $where Condizioni WHERE (opzionale)
     * @return int Numero di righe
     */
    public function count($table, $where = []) {
        $query = "SELECT COUNT(*) FROM {$this->wpdb->prefix}$table";
        $values = [];
        
        if (!empty($where)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($where as $field => $value) {
                $conditions[] = "$field = %s";
                $values[] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        // Prepara la query
        $query = $this->prepare($query, ...$values);
        
        return (int) $this->wpdb->get_var($query);
    }
    
    /**
     * Esegue una transazione
     *
     * @param callable $callback Funzione da eseguire nella transazione
     * @return mixed Risultato della funzione o false in caso di errore
     */
    public function transaction($callback) {
        $this->wpdb->query('START TRANSACTION');
        
        try {
            $result = call_user_func($callback, $this);
            
            if ($result === false) {
                $this->wpdb->query('ROLLBACK');
                return false;
            }
            
            $this->wpdb->query('COMMIT');
            return $result;
        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            $this->log_error("Errore nella transazione: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Esegue una query di ricerca
     *
     * @param string $table Nome della tabella
     * @param array $fields Campi da cercare
     * @param string $search Termine di ricerca
     * @param string $orderby Clausola ORDER BY (opzionale)
     * @param int $limit Limite di risultati (opzionale)
     * @param int $offset Offset dei risultati (opzionale)
     * @return array Array di righe
     */
    public function search($table, $fields, $search, $orderby = '', $limit = 0, $offset = 0) {
        $query = "SELECT * FROM {$this->wpdb->prefix}$table WHERE ";
        $conditions = [];
        $values = [];
        
        foreach ($fields as $field) {
            $conditions[] = "$field LIKE %s";
            $values[] = '%' . $this->wpdb->esc_like($search) . '%';
        }
        
        $query .= '(' . implode(' OR ', $conditions) . ')';
        
        if (!empty($orderby)) {
            $query .= " ORDER BY $orderby";
        }
        
        if ($limit > 0) {
            $query .= " LIMIT %d";
            $values[] = $limit;
            
            if ($offset > 0) {
                $query .= " OFFSET %d";
                $values[] = $offset;
            }
        }
        
        // Prepara la query
        $query = $this->prepare($query, ...$values);
        
        return $this->wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Esegue una query di ricerca avanzata
     *
     * @param string $table Nome della tabella
     * @param array $conditions Condizioni di ricerca
     * @param string $operator Operatore logico (AND, OR)
     * @param string $orderby Clausola ORDER BY (opzionale)
     * @param int $limit Limite di risultati (opzionale)
     * @param int $offset Offset dei risultati (opzionale)
     * @return array Array di righe
     */
    public function advanced_search($table, $conditions, $operator = 'AND', $orderby = '', $limit = 0, $offset = 0) {
        $query = "SELECT * FROM {$this->wpdb->prefix}$table WHERE ";
        $where_conditions = [];
        $values = [];
        
        foreach ($conditions as $condition) {
            $field = $condition['field'];
            $value = $condition['value'];
            $compare = isset($condition['compare']) ? $condition['compare'] : '=';
            
            switch ($compare) {
                case 'LIKE':
                    $where_conditions[] = "$field LIKE %s";
                    $values[] = '%' . $this->wpdb->esc_like($value) . '%';
                    break;
                    
                case 'IN':
                    if (is_array($value) && !empty($value)) {
                        $placeholders = array_fill(0, count($value), '%s');
                        $where_conditions[] = "$field IN (" . implode(', ', $placeholders) . ")";
                        $values = array_merge($values, $value);
                    }
                    break;
                    
                case 'BETWEEN':
                    if (is_array($value) && count($value) === 2) {
                        $where_conditions[] = "$field BETWEEN %s AND %s";
                        $values[] = $value[0];
                        $values[] = $value[1];
                    }
                    break;
                    
                default:
                    $where_conditions[] = "$field $compare %s";
                    $values[] = $value;
                    break;
            }
        }
        
        $query .= implode(" $operator ", $where_conditions);
        
        if (!empty($orderby)) {
            $query .= " ORDER BY $orderby";
        }
        
        if ($limit > 0) {
            $query .= " LIMIT %d";
            $values[] = $limit;
            
            if ($offset > 0) {
                $query .= " OFFSET %d";
                $values[] = $offset;
            }
        }
        
        // Prepara la query
        $query = $this->prepare($query, ...$values);
        
        return $this->wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Ottiene il prefisso delle tabelle
     *
     * @return string Prefisso delle tabelle
     */
    public function get_prefix() {
        return $this->wpdb->prefix;
    }
    
    /**
     * Ottiene l'ultimo errore
     *
     * @return string Ultimo errore
     */
    public function last_error() {
        return $this->wpdb->last_error;
    }
    
    /**
     * Registra un errore nel log
     *
     * @param string $message Messaggio di errore
     * @return void
     */
    private function log_error($message) {
        if (defined('ETO_DEBUG') && ETO_DEBUG) {
            error_log("[ETO DB] $message");
        }
    }
    
    /**
     * Esegue una query di join
     *
     * @param array $tables Array di tabelle
     * @param array $joins Array di join
     * @param array $fields Array di campi da selezionare
     * @param array $where Condizioni WHERE (opzionale)
     * @param string $orderby Clausola ORDER BY (opzionale)
     * @param int $limit Limite di risultati (opzionale)
     * @param int $offset Offset dei risultati (opzionale)
     * @return array Array di righe
     */
    public function join($tables, $joins, $fields, $where = [], $orderby = '', $limit = 0, $offset = 0) {
        // Costruisci la clausola SELECT
        $select = [];
        
        foreach ($fields as $table_alias => $table_fields) {
            foreach ($table_fields as $field) {
                $select[] = "$table_alias.$field AS {$table_alias}_$field";
            }
        }
        
        $query = "SELECT " . implode(', ', $select) . " FROM ";
        
        // Aggiungi la prima tabella
        $main_table = key($tables);
        $main_alias = $tables[$main_table];
        $query .= "{$this->wpdb->prefix}$main_table AS $main_alias";
        
        // Aggiungi i join
        foreach ($joins as $join) {
            $type = isset($join['type']) ? $join['type'] : 'INNER';
            $table = $join['table'];
            $alias = $join['alias'];
            $on = $join['on'];
            
            $query .= " $type JOIN {$this->wpdb->prefix}$table AS $alias ON $on";
        }
        
        // Aggiungi le condizioni WHERE
        $values = [];
        
        if (!empty($where)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($where as $condition) {
                $field = $condition['field'];
                $value = $condition['value'];
                $compare = isset($condition['compare']) ? $condition['compare'] : '=';
                
                switch ($compare) {
                    case 'LIKE':
                        $conditions[] = "$field LIKE %s";
                        $values[] = '%' . $this->wpdb->esc_like($value) . '%';
                        break;
                        
                    case 'IN':
                        if (is_array($value) && !empty($value)) {
                            $placeholders = array_fill(0, count($value), '%s');
                            $conditions[] = "$field IN (" . implode(', ', $placeholders) . ")";
                            $values = array_merge($values, $value);
                        }
                        break;
                        
                    case 'BETWEEN':
                        if (is_array($value) && count($value) === 2) {
                            $conditions[] = "$field BETWEEN %s AND %s";
                            $values[] = $value[0];
                            $values[] = $value[1];
                        }
                        break;
                        
                    default:
                        $conditions[] = "$field $compare %s";
                        $values[] = $value;
                        break;
                }
            }
            
            $query .= implode(" AND ", $conditions);
        }
        
        // Aggiungi ORDER BY
        if (!empty($orderby)) {
            $query .= " ORDER BY $orderby";
        }
        
        // Aggiungi LIMIT e OFFSET
        if ($limit > 0) {
            $query .= " LIMIT %d";
            $values[] = $limit;
            
            if ($offset > 0) {
                $query .= " OFFSET %d";
                $values[] = $offset;
            }
        }
        
        // Prepara la query
        $query = $this->prepare($query, ...$values);
        
        return $this->wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Esegue una query di aggregazione
     *
     * @param string $table Nome della tabella
     * @param string $function Funzione di aggregazione (COUNT, SUM, AVG, MIN, MAX)
     * @param string $field Campo da aggregare
     * @param array $where Condizioni WHERE (opzionale)
     * @param string $group_by Campo per il raggruppamento (opzionale)
     * @return mixed Risultato dell'aggregazione
     */
    public function aggregate($table, $function, $field, $where = [], $group_by = '') {
        $function = strtoupper($function);
        $allowed_functions = ['COUNT', 'SUM', 'AVG', 'MIN', 'MAX'];
        
        if (!in_array($function, $allowed_functions)) {
            $this->log_error("Funzione di aggregazione non valida: $function");
            return false;
        }
        
        $query = "SELECT $function($field) FROM {$this->wpdb->prefix}$table";
        $values = [];
        
        if (!empty($where)) {
            $query .= " WHERE ";
            $conditions = [];
            
            foreach ($where as $field_name => $value) {
                $conditions[] = "$field_name = %s";
                $values[] = $value;
            }
            
            $query .= implode(' AND ', $conditions);
        }
        
        if (!empty($group_by)) {
            $query .= " GROUP BY $group_by";
        }
        
        // Prepara la query
        $query = $this->prepare($query, ...$values);
        
        return $this->wpdb->get_var($query);
    }
    
    /**
     * Esegue una query di inserimento multiplo
     *
     * @param string $table Nome della tabella
     * @param array $columns Array di colonne
     * @param array $rows Array di righe
     * @return int|false Numero di righe inserite o false in caso di errore
     */
    public function insert_batch($table, $columns, $rows) {
        if (empty($rows)) {
            return 0;
        }
        
        $query = "INSERT INTO {$this->wpdb->prefix}$table (" . implode(', ', $columns) . ") VALUES ";
        $values = [];
        $placeholders = [];
        
        foreach ($rows as $row) {
            $row_placeholders = [];
            
            foreach ($columns as $column) {
                $row_placeholders[] = '%s';
                $values[] = isset($row[$column]) ? $row[$column] : '';
            }
            
            $placeholders[] = '(' . implode(', ', $row_placeholders) . ')';
        }
        
        $query .= implode(', ', $placeholders);
        
        // Prepara la query
        $query = $this->prepare($query, ...$values);
        
        return $this->wpdb->query($query);
    }
    
    /**
     * Esegue una query di aggiornamento multiplo
     *
     * @param string $table Nome della tabella
     * @param array $data Array di dati da aggiornare
     * @param string $id_column Nome della colonna ID
     * @return int|false Numero di righe aggiornate o false in caso di errore
     */
    public function update_batch($table, $data, $id_column) {
        if (empty($data)) {
            return 0;
        }
        
        $ids = [];
        $case_statements = [];
        $values = [];
        
        // Ottieni tutte le colonne da aggiornare
        $columns = [];
        
        foreach ($data as $row) {
            foreach ($row as $column => $value) {
                if ($column !== $id_column && !in_array($column, $columns)) {
                    $columns[] = $column;
                }
            }
        }
        
        // Costruisci le clausole CASE per ogni colonna
        foreach ($columns as $column) {
            $case_statement = "$column = CASE ";
            
            foreach ($data as $row) {
                if (isset($row[$id_column]) && isset($row[$column])) {
                    $case_statement .= "WHEN $id_column = %s THEN %s ";
                    $values[] = $row[$id_column];
                    $values[] = $row[$column];
                    
                    if (!in_array($row[$id_column], $ids)) {
                        $ids[] = $row[$id_column];
                    }
                }
            }
            
            $case_statement .= "ELSE $column END";
            $case_statements[] = $case_statement;
        }
        
        if (empty($ids)) {
            return 0;
        }
        
        // Costruisci la query
        $query = "UPDATE {$this->wpdb->prefix}$table SET " . implode(', ', $case_statements) . " WHERE $id_column IN (";
        $query .= implode(', ', array_fill(0, count($ids), '%s')) . ")";
        
        // Aggiungi gli ID alla lista dei valori
        $values = array_merge($values, $ids);
        
        // Prepara la query
        $query = $this->prepare($query, ...$values);
        
        return $this->wpdb->query($query);
    }
    
    /**
     * Esegue una query di eliminazione multipla
     *
     * @param string $table Nome della tabella
     * @param string $id_column Nome della colonna ID
     * @param array $ids Array di ID da eliminare
     * @return int|false Numero di righe eliminate o false in caso di errore
     */
    public function delete_batch($table, $id_column, $ids) {
        if (empty($ids)) {
            return 0;
        }
        
        $query = "DELETE FROM {$this->wpdb->prefix}$table WHERE $id_column IN (";
        $query .= implode(', ', array_fill(0, count($ids), '%s')) . ")";
        
        // Prepara la query
        $query = $this->prepare($query, ...$ids);
        
        return $this->wpdb->query($query);
    }
    
    /**
     * Ottiene il nome completo di una tabella
     *
     * @param string $table Nome della tabella
     * @return string Nome completo della tabella
     */
    public function get_table_name($table) {
        return $this->wpdb->prefix . $table;
    }
    
    /**
     * Verifica se una tabella esiste
     *
     * @param string $table Nome della tabella
     * @return bool True se la tabella esiste, false altrimenti
     */
    public function table_exists($table) {
        $table_name = $this->wpdb->prefix . $table;
        $query = $this->prepare("SHOW TABLES LIKE %s", $table_name);
        
        return $this->wpdb->get_var($query) === $table_name;
    }
    
    /**
     * Ottiene la struttura di una tabella
     *
     * @param string $table Nome della tabella
     * @return array Array di colonne
     */
    public function get_table_structure($table) {
        $table_name = $this->wpdb->prefix . $table;
        $query = $this->prepare("DESCRIBE %s", $table_name);
        
        return $this->wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Ottiene le chiavi primarie di una tabella
     *
     * @param string $table Nome della tabella
     * @return array Array di chiavi primarie
     */
    public function get_primary_keys($table) {
        $table_name = $this->wpdb->prefix . $table;
        $query = $this->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND CONSTRAINT_NAME = 'PRIMARY'",
            $this->wpdb->dbname,
            $table_name
        );
        
        $results = $this->wpdb->get_results($query, ARRAY_A);
        $primary_keys = [];
        
        foreach ($results as $result) {
            $primary_keys[] = $result['COLUMN_NAME'];
        }
        
        return $primary_keys;
    }
}

/**
 * Funzione helper per ottenere un'istanza della classe di query
 *
 * @return ETO_DB_Query_Secure Istanza della classe di query
 */
function eto_db_query_secure() {
    return new ETO_DB_Query_Secure();
}
