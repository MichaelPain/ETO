<?php
/**
 * Classe Logger per ETO
 * 
 * Gestisce il logging di eventi, errori e attività degli utenti
 * 
 * @package ETO
 * @subpackage Core
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Logger {
    /**
     * Livelli di log disponibili
     */
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
    const CRITICAL = 'critical';
    
    /**
     * Categorie di log predefinite
     */
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_USER = 'user';
    const CATEGORY_TOURNAMENT = 'tournament';
    const CATEGORY_TEAM = 'team';
    const CATEGORY_MATCH = 'match';
    const CATEGORY_API = 'api';
    
    /**
     * Nome della tabella dei log
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Livello minimo di log da registrare
     *
     * @var string
     */
    private $min_level;
    
    /**
     * Indica se il logging è abilitato
     *
     * @var bool
     */
    private $enabled;
    
    /**
     * Opzioni di configurazione
     *
     * @var array
     */
    private $options;
    
    /**
     * Istanza singleton
     *
     * @var ETO_Logger
     */
    private static $instance = null;
    
    /**
     * Ottiene l'istanza singleton del logger
     *
     * @return ETO_Logger
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Costruttore
     */
    private function __construct() {
        global $wpdb;
        
        $this->table_name = $wpdb->prefix . 'eto_logs';
        $this->options = get_option('eto_logger_options', array(
            'enabled' => true,
            'min_level' => self::INFO,
            'log_to_file' => false,
            'log_file_path' => WP_CONTENT_DIR . '/eto-logs/eto-debug.log',
            'rotate_logs' => true,
            'max_log_size' => 10, // MB
            'max_log_age' => 30, // giorni
            'retention_count' => 10 // numero di file di log da conservare
        ));
        
        $this->enabled = $this->options['enabled'];
        $this->min_level = $this->options['min_level'];
        
        // Assicurati che la directory dei log esista se il logging su file è abilitato
        if ($this->options['log_to_file']) {
            $log_dir = dirname($this->options['log_file_path']);
            if (!file_exists($log_dir)) {
                wp_mkdir_p($log_dir);
            }
        }
        
        // Registra gli hook per la pulizia dei log
        add_action('eto_daily_maintenance', array($this, 'cleanup_logs'));
        
        // Registra gli hook per le azioni di logging automatico
        $this->register_automatic_logging_hooks();
    }
    
    /**
     * Registra gli hook per il logging automatico di eventi
     */
    private function register_automatic_logging_hooks() {
        // Hook per il login e logout
        add_action('wp_login', array($this, 'log_user_login'), 10, 2);
        add_action('wp_logout', array($this, 'log_user_logout'));
        
        // Hook per la creazione e modifica di tornei
        add_action('eto_after_tournament_create', array($this, 'log_tournament_create'), 10, 2);
        add_action('eto_after_tournament_update', array($this, 'log_tournament_update'), 10, 2);
        add_action('eto_after_tournament_delete', array($this, 'log_tournament_delete'), 10, 1);
        
        // Hook per la creazione e modifica di team
        add_action('eto_after_team_create', array($this, 'log_team_create'), 10, 2);
        add_action('eto_after_team_update', array($this, 'log_team_update'), 10, 2);
        add_action('eto_after_team_delete', array($this, 'log_team_delete'), 10, 1);
        
        // Hook per la creazione e modifica di match
        add_action('eto_after_match_create', array($this, 'log_match_create'), 10, 2);
        add_action('eto_after_match_update', array($this, 'log_match_update'), 10, 2);
        add_action('eto_after_match_delete', array($this, 'log_match_delete'), 10, 1);
        add_action('eto_after_match_result_update', array($this, 'log_match_result_update'), 10, 3);
        
        // Hook per le richieste API
        add_action('eto_api_request', array($this, 'log_api_request'), 10, 3);
        
        // Hook per gli errori
        add_action('eto_error', array($this, 'log_error'), 10, 3);
    }
    
    /**
     * Verifica se un livello di log è registrabile
     *
     * @param string $level Livello di log
     * @return bool True se il livello è registrabile, false altrimenti
     */
    private function is_level_loggable($level) {
        if (!$this->enabled) {
            return false;
        }
        
        $levels = array(
            self::DEBUG => 0,
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4
        );
        
        return isset($levels[$level]) && isset($levels[$this->min_level]) && $levels[$level] >= $levels[$this->min_level];
    }
    
    /**
     * Registra un messaggio di log
     *
     * @param string $level Livello di log (debug, info, warning, error, critical)
     * @param string $message Messaggio di log
     * @param array $context Contesto aggiuntivo
     * @param string $category Categoria del log
     * @return bool True se il log è stato registrato, false altrimenti
     */
    public function log($level, $message, $context = array(), $category = self::CATEGORY_SYSTEM) {
        if (!$this->is_level_loggable($level)) {
            return false;
        }
        
        // Ottieni informazioni sull'utente corrente
        $user_id = get_current_user_id();
        
        // Ottieni informazioni sulla richiesta
        $ip_address = $this->get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        // Aggiungi informazioni standard al contesto
        $context = array_merge($context, array(
            'user_id' => $user_id,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'request_uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            'request_method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '',
            'timestamp' => current_time('mysql')
        ));
        
        // Registra nel database
        $result = $this->log_to_database($level, $message, $context, $category, $user_id, $ip_address, $user_agent);
        
        // Registra nel file se abilitato
        if ($this->options['log_to_file']) {
            $this->log_to_file($level, $message, $context, $category);
        }
        
        return $result;
    }
    
    /**
     * Registra un messaggio di log nel database
     *
     * @param string $level Livello di log
     * @param string $message Messaggio di log
     * @param array $context Contesto aggiuntivo
     * @param string $category Categoria del log
     * @param int $user_id ID dell'utente
     * @param string $ip_address Indirizzo IP
     * @param string $user_agent User agent
     * @return bool True se il log è stato registrato, false altrimenti
     */
    private function log_to_database($level, $message, $context, $category, $user_id, $ip_address, $user_agent) {
        global $wpdb;
        
        $data = array(
            'level' => $level,
            'message' => $message,
            'context' => json_encode($context),
            'category' => $category,
            'user_id' => $user_id,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($this->table_name, $data);
        
        return $result !== false;
    }
    
    /**
     * Registra un messaggio di log in un file
     *
     * @param string $level Livello di log
     * @param string $message Messaggio di log
     * @param array $context Contesto aggiuntivo
     * @param string $category Categoria del log
     * @return bool True se il log è stato registrato, false altrimenti
     */
    private function log_to_file($level, $message, $context, $category) {
        $log_file = $this->options['log_file_path'];
        
        // Verifica se è necessario ruotare il log
        if ($this->options['rotate_logs'] && file_exists($log_file)) {
            $file_size = filesize($log_file) / (1024 * 1024); // Converti in MB
            
            if ($file_size >= $this->options['max_log_size']) {
                $this->rotate_log_file();
            }
        }
        
        // Formatta il messaggio di log
        $timestamp = current_time('mysql');
        $log_entry = sprintf(
            "[%s] [%s] [%s] %s - %s\n",
            $timestamp,
            strtoupper($level),
            $category,
            $message,
            json_encode($context)
        );
        
        // Scrivi nel file di log
        $result = file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        return $result !== false;
    }
    
    /**
     * Ruota il file di log
     */
    private function rotate_log_file() {
        $log_file = $this->options['log_file_path'];
        $log_dir = dirname($log_file);
        $log_basename = basename($log_file);
        $log_extension = pathinfo($log_file, PATHINFO_EXTENSION);
        $log_name = pathinfo($log_file, PATHINFO_FILENAME);
        
        // Crea il nuovo nome del file di log
        $timestamp = date('Y-m-d-H-i-s');
        $rotated_log = $log_dir . '/' . $log_name . '-' . $timestamp . '.' . $log_extension;
        
        // Rinomina il file di log corrente
        rename($log_file, $rotated_log);
        
        // Pulisci i vecchi file di log
        $this->cleanup_log_files();
    }
    
    /**
     * Pulisce i vecchi file di log
     */
    private function cleanup_log_files() {
        $log_file = $this->options['log_file_path'];
        $log_dir = dirname($log_file);
        $log_basename = basename($log_file);
        $log_extension = pathinfo($log_file, PATHINFO_EXTENSION);
        $log_name = pathinfo($log_file, PATHINFO_FILENAME);
        
        // Ottieni tutti i file di log ruotati
        $pattern = $log_dir . '/' . $log_name . '-*.' . $log_extension;
        $log_files = glob($pattern);
        
        // Ordina per data (più recenti prima)
        usort($log_files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Mantieni solo il numero specificato di file di log
        $retention_count = $this->options['retention_count'];
        
        if (count($log_files) > $retention_count) {
            $files_to_delete = array_slice($log_files, $retention_count);
            
            foreach ($files_to_delete as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
        
        // Elimina i file più vecchi di max_log_age giorni
        $max_age = $this->options['max_log_age'] * 86400; // Converti in secondi
        $now = time();
        
        foreach ($log_files as $file) {
            if (file_exists($file) && ($now - filemtime($file)) > $max_age) {
                unlink($file);
            }
        }
    }
    
    /**
     * Pulisce i vecchi log dal database
     */
    public function cleanup_logs() {
        global $wpdb;
        
        // Elimina i log più vecchi di max_log_age giorni
        $max_age = $this->options['max_log_age'];
        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$max_age} days"));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE created_at < %s",
            $date_threshold
        ));
        
        // Ottimizza la tabella
        $wpdb->query("OPTIMIZE TABLE {$this->table_name}");
    }
    
    /**
     * Ottiene l'indirizzo IP del client
     *
     * @return string Indirizzo IP
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }
        
        return $ip;
    }
    
    /**
     * Registra un messaggio di debug
     *
     * @param string $message Messaggio di log
     * @param array $context Contesto aggiuntivo
     * @param string $category Categoria del log
     * @return bool True se il log è stato registrato, false altrimenti
     */
    public function debug($message, $context = array(), $category = self::CATEGORY_SYSTEM) {
        return $this->log(self::DEBUG, $message, $context, $category);
    }
    
    /**
     * Registra un messaggio informativo
     *
     * @param string $message Messaggio di log
     * @param array $context Contesto aggiuntivo
     * @param string $category Categoria del log
     * @return bool True se il log è stato registrato, false altrimenti
     */
    public function info($message, $context = array(), $category = self::CATEGORY_SYSTEM) {
        return $this->log(self::INFO, $message, $context, $category);
    }
    
    /**
     * Registra un messaggio di avviso
     *
     * @param string $message Messaggio di log
     * @param array $context Contesto aggiuntivo
     * @param string $category Categoria del log
     * @return bool True se il log è stato registrato, false altrimenti
     */
    public function warning($message, $context = array(), $category = self::CATEGORY_SYSTEM) {
        return $this->log(self::WARNING, $message, $context, $category);
    }
    
    /**
     * Registra un messaggio di errore
     *
     * @param string $message Messaggio di log
     * @param array $context Contesto aggiuntivo
     * @param string $category Categoria del log
     * @return bool True se il log è stato registrato, false altrimenti
     */
    public function error($message, $context = array(), $category = self::CATEGORY_SYSTEM) {
        return $this->log(self::ERROR, $message, $context, $category);
    }
    
    /**
     * Registra un messaggio di errore critico
     *
     * @param string $message Messaggio di log
     * @param array $context Contesto aggiuntivo
     * @param string $category Categoria del log
     * @return bool True se il log è stato registrato, false altrimenti
     */
    public function critical($message, $context = array(), $category = self::CATEGORY_SYSTEM) {
        return $this->log(self::CRITICAL, $message, $context, $category);
    }
    
    /**
     * Registra un login utente
     *
     * @param string $username Nome utente
     * @param WP_User $user Oggetto utente
     */
    public function log_user_login($username, $user) {
        $this->info(
            sprintf('Utente "%s" ha effettuato il login', $username),
            array('user_id' => $user->ID),
            self::CATEGORY_SECURITY
        );
    }
    
    /**
     * Registra un logout utente
     */
    public function log_user_logout() {
        $user_id = get_current_user_id();
        
        if ($user_id) {
            $user = get_userdata($user_id);
            
            $this->info(
                sprintf('Utente "%s" ha effettuato il logout', $user->user_login),
                array('user_id' => $user_id),
                self::CATEGORY_SECURITY
            );
        }
    }
    
    /**
     * Registra la creazione di un torneo
     *
     * @param int $tournament_id ID del torneo
     * @param array $tournament_data Dati del torneo
     */
    public function log_tournament_create($tournament_id, $tournament_data) {
        $this->info(
            sprintf('Torneo "%s" creato', $tournament_data['name']),
            array(
                'tournament_id' => $tournament_id,
                'tournament_data' => $tournament_data
            ),
            self::CATEGORY_TOURNAMENT
        );
    }
    
    /**
     * Registra l'aggiornamento di un torneo
     *
     * @param int $tournament_id ID del torneo
     * @param array $tournament_data Dati del torneo
     */
    public function log_tournament_update($tournament_id, $tournament_data) {
        $this->info(
            sprintf('Torneo "%s" aggiornato', $tournament_data['name']),
            array(
                'tournament_id' => $tournament_id,
                'tournament_data' => $tournament_data
            ),
            self::CATEGORY_TOURNAMENT
        );
    }
    
    /**
     * Registra l'eliminazione di un torneo
     *
     * @param int $tournament_id ID del torneo
     */
    public function log_tournament_delete($tournament_id) {
        $this->info(
            sprintf('Torneo ID "%d" eliminato', $tournament_id),
            array('tournament_id' => $tournament_id),
            self::CATEGORY_TOURNAMENT
        );
    }
    
    /**
     * Registra la creazione di un team
     *
     * @param int $team_id ID del team
     * @param array $team_data Dati del team
     */
    public function log_team_create($team_id, $team_data) {
        $this->info(
            sprintf('Team "%s" creato', $team_data['name']),
            array(
                'team_id' => $team_id,
                'team_data' => $team_data
            ),
            self::CATEGORY_TEAM
        );
    }
    
    /**
     * Registra l'aggiornamento di un team
     *
     * @param int $team_id ID del team
     * @param array $team_data Dati del team
     */
    public function log_team_update($team_id, $team_data) {
        $this->info(
            sprintf('Team "%s" aggiornato', $team_data['name']),
            array(
                'team_id' => $team_id,
                'team_data' => $team_data
            ),
            self::CATEGORY_TEAM
        );
    }
    
    /**
     * Registra l'eliminazione di un team
     *
     * @param int $team_id ID del team
     */
    public function log_team_delete($team_id) {
        $this->info(
            sprintf('Team ID "%d" eliminato', $team_id),
            array('team_id' => $team_id),
            self::CATEGORY_TEAM
        );
    }
    
    /**
     * Registra la creazione di un match
     *
     * @param int $match_id ID del match
     * @param array $match_data Dati del match
     */
    public function log_match_create($match_id, $match_data) {
        $this->info(
            sprintf('Match ID "%d" creato', $match_id),
            array(
                'match_id' => $match_id,
                'match_data' => $match_data
            ),
            self::CATEGORY_MATCH
        );
    }
    
    /**
     * Registra l'aggiornamento di un match
     *
     * @param int $match_id ID del match
     * @param array $match_data Dati del match
     */
    public function log_match_update($match_id, $match_data) {
        $this->info(
            sprintf('Match ID "%d" aggiornato', $match_id),
            array(
                'match_id' => $match_id,
                'match_data' => $match_data
            ),
            self::CATEGORY_MATCH
        );
    }
    
    /**
     * Registra l'eliminazione di un match
     *
     * @param int $match_id ID del match
     */
    public function log_match_delete($match_id) {
        $this->info(
            sprintf('Match ID "%d" eliminato', $match_id),
            array('match_id' => $match_id),
            self::CATEGORY_MATCH
        );
    }
    
    /**
     * Registra l'aggiornamento dei risultati di un match
     *
     * @param int $match_id ID del match
     * @param int $team1_score Punteggio del team 1
     * @param int $team2_score Punteggio del team 2
     */
    public function log_match_result_update($match_id, $team1_score, $team2_score) {
        $this->info(
            sprintf('Risultati del match ID "%d" aggiornati: %d - %d', $match_id, $team1_score, $team2_score),
            array(
                'match_id' => $match_id,
                'team1_score' => $team1_score,
                'team2_score' => $team2_score
            ),
            self::CATEGORY_MATCH
        );
    }
    
    /**
     * Registra una richiesta API
     *
     * @param string $endpoint Endpoint API
     * @param array $params Parametri della richiesta
     * @param int $status_code Codice di stato HTTP
     */
    public function log_api_request($endpoint, $params, $status_code) {
        $level = ($status_code >= 400) ? self::WARNING : self::INFO;
        
        $this->log(
            $level,
            sprintf('Richiesta API a "%s" con codice di stato %d', $endpoint, $status_code),
            array(
                'endpoint' => $endpoint,
                'params' => $params,
                'status_code' => $status_code
            ),
            self::CATEGORY_API
        );
    }
    
    /**
     * Registra un errore
     *
     * @param string $message Messaggio di errore
     * @param array $context Contesto dell'errore
     * @param string $category Categoria dell'errore
     */
    public function log_error($message, $context = array(), $category = self::CATEGORY_SYSTEM) {
        $this->error($message, $context, $category);
    }
    
    /**
     * Ottiene i log dal database
     *
     * @param array $args Argomenti per la query
     * @return array Array di log
     */
    public function get_logs($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'page' => 1,
            'per_page' => 20,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'level' => array(),
            'category' => array(),
            'user_id' => 0,
            'search' => '',
            'date_from' => '',
            'date_to' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Costruisci la query
        $query = "SELECT * FROM {$this->table_name} WHERE 1=1";
        $query_args = array();
        
        // Filtra per livello
        if (!empty($args['level'])) {
            $levels = (array) $args['level'];
            $placeholders = implode(', ', array_fill(0, count($levels), '%s'));
            $query .= " AND level IN ($placeholders)";
            $query_args = array_merge($query_args, $levels);
        }
        
        // Filtra per categoria
        if (!empty($args['category'])) {
            $categories = (array) $args['category'];
            $placeholders = implode(', ', array_fill(0, count($categories), '%s'));
            $query .= " AND category IN ($placeholders)";
            $query_args = array_merge($query_args, $categories);
        }
        
        // Filtra per utente
        if (!empty($args['user_id'])) {
            $query .= " AND user_id = %d";
            $query_args[] = $args['user_id'];
        }
        
        // Filtra per ricerca
        if (!empty($args['search'])) {
            $query .= " AND (message LIKE %s OR context LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $query_args[] = $search_term;
            $query_args[] = $search_term;
        }
        
        // Filtra per data
        if (!empty($args['date_from'])) {
            $query .= " AND created_at >= %s";
            $query_args[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $query .= " AND created_at <= %s";
            $query_args[] = $args['date_to'];
        }
        
        // Ordina
        $valid_orderby = array('id', 'level', 'category', 'user_id', 'created_at');
        $orderby = in_array($args['orderby'], $valid_orderby) ? $args['orderby'] : 'created_at';
        
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        $query .= " ORDER BY {$orderby} {$order}";
        
        // Paginazione
        $page = max(1, intval($args['page']));
        $per_page = max(1, intval($args['per_page']));
        $offset = ($page - 1) * $per_page;
        
        $query .= " LIMIT %d, %d";
        $query_args[] = $offset;
        $query_args[] = $per_page;
        
        // Esegui la query
        $logs = $wpdb->get_results($wpdb->prepare($query, $query_args), ARRAY_A);
        
        // Decodifica il contesto JSON
        foreach ($logs as &$log) {
            $log['context'] = json_decode($log['context'], true);
        }
        
        return $logs;
    }
    
    /**
     * Conta il numero totale di log nel database
     *
     * @param array $args Argomenti per la query
     * @return int Numero totale di log
     */
    public function count_logs($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'level' => array(),
            'category' => array(),
            'user_id' => 0,
            'search' => '',
            'date_from' => '',
            'date_to' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Costruisci la query
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE 1=1";
        $query_args = array();
        
        // Filtra per livello
        if (!empty($args['level'])) {
            $levels = (array) $args['level'];
            $placeholders = implode(', ', array_fill(0, count($levels), '%s'));
            $query .= " AND level IN ($placeholders)";
            $query_args = array_merge($query_args, $levels);
        }
        
        // Filtra per categoria
        if (!empty($args['category'])) {
            $categories = (array) $args['category'];
            $placeholders = implode(', ', array_fill(0, count($categories), '%s'));
            $query .= " AND category IN ($placeholders)";
            $query_args = array_merge($query_args, $categories);
        }
        
        // Filtra per utente
        if (!empty($args['user_id'])) {
            $query .= " AND user_id = %d";
            $query_args[] = $args['user_id'];
        }
        
        // Filtra per ricerca
        if (!empty($args['search'])) {
            $query .= " AND (message LIKE %s OR context LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $query_args[] = $search_term;
            $query_args[] = $search_term;
        }
        
        // Filtra per data
        if (!empty($args['date_from'])) {
            $query .= " AND created_at >= %s";
            $query_args[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $query .= " AND created_at <= %s";
            $query_args[] = $args['date_to'];
        }
        
        // Esegui la query
        return (int) $wpdb->get_var($wpdb->prepare($query, $query_args));
    }
    
    /**
     * Ottiene le opzioni di configurazione del logger
     *
     * @return array Opzioni di configurazione
     */
    public function get_options() {
        return $this->options;
    }
    
    /**
     * Aggiorna le opzioni di configurazione del logger
     *
     * @param array $options Nuove opzioni
     * @return bool True se le opzioni sono state aggiornate, false altrimenti
     */
    public function update_options($options) {
        $this->options = array_merge($this->options, $options);
        $this->enabled = $this->options['enabled'];
        $this->min_level = $this->options['min_level'];
        
        return update_option('eto_logger_options', $this->options);
    }
    
    /**
     * Crea la tabella dei log nel database
     *
     * @return bool True se la tabella è stata creata, false altrimenti
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eto_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            level VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            context LONGTEXT NOT NULL,
            category VARCHAR(50) NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            ip_address VARCHAR(45) NOT NULL,
            user_agent VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_level (level),
            KEY idx_category (category),
            KEY idx_user_id (user_id),
            KEY idx_created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        return true;
    }
    
    /**
     * Elimina la tabella dei log dal database
     *
     * @return bool True se la tabella è stata eliminata, false altrimenti
     */
    public static function drop_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eto_logs';
        
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        return true;
    }
}

// Inizializza il logger
function eto_get_logger() {
    return ETO_Logger::get_instance();
}
