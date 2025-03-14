<?php
/**
 * Classe per la gestione del database
 * 
 * Gestisce la creazione, l'aggiornamento e la manutenzione delle tabelle del database
 * 
 * @package ETO
 * @since 2.5.1
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Database_Manager {
    
    /**
     * Prefisso delle tabelle del plugin
     *
     * @var string
     */
    private $table_prefix;
    
    /**
     * Versione corrente del database
     *
     * @var string
     */
    private $db_version;
    
    /**
     * Costruttore
     */
    public function __construct() {
        global $wpdb;
        
        $this->table_prefix = $wpdb->prefix . 'eto_';
        $this->db_version = get_option('eto_db_version', '0');
    }
    
    /**
     * Crea o aggiorna le tabelle del database
     *
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $success = true;
        
        // Inizia la transazione
        $wpdb->query('START TRANSACTION');
        
        try {
            // Tabella tornei
            $table_tournaments = $this->table_prefix . 'tournaments';
            $sql_tournaments = "CREATE TABLE $table_tournaments (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                description text,
                format varchar(50) NOT NULL,
                elimination_type varchar(20) DEFAULT 'single',
                status varchar(20) NOT NULL DEFAULT 'pending',
                start_date datetime DEFAULT NULL,
                end_date datetime DEFAULT NULL,
                max_teams smallint(5) NOT NULL DEFAULT 8,
                created_by bigint(20) NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY idx_status (status),
                KEY idx_dates (start_date, end_date),
                KEY idx_format (format)
            ) $charset_collate ENGINE=InnoDB;";
            
            // Tabella team
            $table_teams = $this->table_prefix . 'teams';
            $sql_teams = "CREATE TABLE $table_teams (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                logo varchar(255),
                captain_id bigint(20) NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY idx_captain (captain_id)
            ) $charset_collate ENGINE=InnoDB;";
            
            // Tabella membri team
            $table_team_members = $this->table_prefix . 'team_members';
            $sql_team_members = "CREATE TABLE $table_team_members (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                team_id mediumint(9) NOT NULL,
                user_id bigint(20) NOT NULL,
                role varchar(50) DEFAULT 'member',
                joined_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY idx_team_user (team_id, user_id),
                KEY idx_user (user_id),
                FOREIGN KEY (team_id) REFERENCES {$this->table_prefix}teams(id) ON DELETE CASCADE
            ) $charset_collate ENGINE=InnoDB;";
            
            // Tabella partite
            $table_matches = $this->table_prefix . 'matches';
            $sql_matches = "CREATE TABLE $table_matches (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                tournament_id mediumint(9) NOT NULL,
                team1_id mediumint(9),
                team2_id mediumint(9),
                team1_score tinyint(3) DEFAULT 0,
                team2_score tinyint(3) DEFAULT 0,
                round tinyint(3) NOT NULL,
                match_number smallint(5) NOT NULL,
                status varchar(20) DEFAULT 'pending',
                scheduled_at datetime DEFAULT NULL,
                completed_at datetime DEFAULT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY idx_tournament (tournament_id),
                KEY idx_teams (team1_id, team2_id),
                KEY idx_status (status),
                FOREIGN KEY (tournament_id) REFERENCES {$this->table_prefix}tournaments(id) ON DELETE CASCADE,
                FOREIGN KEY (team1_id) REFERENCES {$this->table_prefix}teams(id) ON DELETE SET NULL,
                FOREIGN KEY (team2_id) REFERENCES {$this->table_prefix}teams(id) ON DELETE SET NULL
            ) $charset_collate ENGINE=InnoDB;";
            
            // Tabella iscrizioni torneo
            $table_tournament_entries = $this->table_prefix . 'tournament_entries';
            $sql_tournament_entries = "CREATE TABLE $table_tournament_entries (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                tournament_id mediumint(9) NOT NULL,
                team_id mediumint(9) NOT NULL,
                status varchar(20) DEFAULT 'registered',
                seed smallint(5) DEFAULT NULL,
                checked_in tinyint(1) DEFAULT 0,
                checked_in_at datetime DEFAULT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY idx_tournament_team (tournament_id, team_id),
                KEY idx_tournament (tournament_id),
                KEY idx_team (team_id),
                KEY idx_status (status),
                FOREIGN KEY (tournament_id) REFERENCES {$this->table_prefix}tournaments(id) ON DELETE CASCADE,
                FOREIGN KEY (team_id) REFERENCES {$this->table_prefix}teams(id) ON DELETE CASCADE
            ) $charset_collate ENGINE=InnoDB;";
            
            // Tabella log audit
            $table_audit_logs = $this->table_prefix . 'audit_logs';
            $sql_audit_logs = "CREATE TABLE $table_audit_logs (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20),
                action varchar(100) NOT NULL,
                object_type varchar(50) NOT NULL,
                object_id bigint(20),
                details text,
                ip_address varchar(45),
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY idx_user (user_id),
                KEY idx_action (action),
                KEY idx_object (object_type, object_id),
                KEY idx_created_at (created_at)
            ) $charset_collate ENGINE=InnoDB;";
            
            // Esegui le query di creazione tabelle
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            dbDelta($sql_tournaments);
            dbDelta($sql_teams);
            dbDelta($sql_team_members);
            dbDelta($sql_matches);
            dbDelta($sql_tournament_entries);
            dbDelta($sql_audit_logs);
            
            // Aggiorna la versione del database
            update_option('eto_db_version', '2.5.1');
            
            // Commit della transazione
            $wpdb->query('COMMIT');
            
        } catch (Exception $e) {
            // Rollback in caso di errore
            $wpdb->query('ROLLBACK');
            
            if (defined('ETO_DEBUG') && ETO_DEBUG) {
                error_log('[ETO] Errore creazione tabelle: ' . $e->getMessage());
            }
            
            eto_add_admin_notice('error', __('Errore durante la creazione delle tabelle del database.', 'eto'));
            $success = false;
        }
        
        return $success;
    }
    
    /**
     * Esegue la migrazione dei dati tra versioni
     *
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function migrate_data() {
        global $wpdb;
        
        $current_version = $this->db_version;
        $target_version = '2.5.1';
        $success = true;
        
        // Se siamo già alla versione corrente, non fare nulla
        if (version_compare($current_version, $target_version, '>=')) {
            return true;
        }
        
        // Inizia la transazione
        $wpdb->query('START TRANSACTION');
        
        try {
            // Migrazione dalla versione 1.0 alla 2.0
            if (version_compare($current_version, '2.0', '<')) {
                // Aggiungi campo elimination_type alla tabella tournaments
                $table_tournaments = $this->table_prefix . 'tournaments';
                $column_exists = $wpdb->get_results($wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME, $table_tournaments, 'elimination_type'
                ));
                
                if (empty($column_exists)) {
                    $wpdb->query("ALTER TABLE $table_tournaments ADD COLUMN elimination_type VARCHAR(20) DEFAULT 'single' AFTER format");
                }
            }
            
            // Migrazione dalla versione 2.0 alla 2.5
            if (version_compare($current_version, '2.5', '<')) {
                // Aggiungi indici per migliorare le performance
                $table_matches = $this->table_prefix . 'matches';
                $wpdb->query("ALTER TABLE $table_matches ADD INDEX idx_status (status)");
                
                // Converti tutte le tabelle a InnoDB
                $tables = [
                    $this->table_prefix . 'tournaments',
                    $this->table_prefix . 'teams',
                    $this->table_prefix . 'team_members',
                    $this->table_prefix . 'matches',
                    $this->table_prefix . 'tournament_entries',
                    $this->table_prefix . 'audit_logs'
                ];
                
                foreach ($tables as $table) {
                    $wpdb->query("ALTER TABLE $table ENGINE = InnoDB");
                }
            }
            
            // Aggiorna la versione del database
            update_option('eto_db_version', $target_version);
            
            // Commit della transazione
            $wpdb->query('COMMIT');
            
        } catch (Exception $e) {
            // Rollback in caso di errore
            $wpdb->query('ROLLBACK');
            
            if (defined('ETO_DEBUG') && ETO_DEBUG) {
                error_log('[ETO] Errore migrazione dati: ' . $e->getMessage());
            }
            
            eto_add_admin_notice('error', __('Errore durante la migrazione dei dati del database.', 'eto'));
            $success = false;
        }
        
        return $success;
    }
    
    /**
     * Ottiene il nome completo di una tabella
     *
     * @param string $table Nome della tabella senza prefisso
     * @return string Nome completo della tabella
     */
    public function get_table_name($table) {
        return $this->table_prefix . $table;
    }
    
    /**
     * Verifica l'integrità del database
     *
     * @return array Array di problemi riscontrati
     */
    public function check_integrity() {
        global $wpdb;
        
        $issues = [];
        $tables = [
            'tournaments',
            'teams',
            'team_members',
            'matches',
            'tournament_entries',
            'audit_logs'
        ];
        
        foreach ($tables as $table) {
            $table_name = $this->table_prefix . $table;
            
            // Verifica se la tabella esiste
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables 
                WHERE table_schema = %s AND table_name = %s",
                DB_NAME, $table_name
            ));
            
            if (!$table_exists) {
                $issues[] = sprintf(__('Tabella mancante: %s', 'eto'), $table_name);
                continue;
            }
            
            // Verifica l'engine della tabella
            $engine = $wpdb->get_var($wpdb->prepare(
                "SELECT engine FROM information_schema.tables 
                WHERE table_schema = %s AND table_name = %s",
                DB_NAME, $table_name
            ));
            
            if ($engine !== 'InnoDB') {
                $issues[] = sprintf(__('Tabella %s: engine non ottimale (%s invece di InnoDB)', 'eto'), $table_name, $engine);
            }
        }
        
        return $issues;
    }
    
    /**
     * Ripara il database
     *
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function repair_database() {
        $issues = $this->check_integrity();
        
        if (empty($issues)) {
            return true;
        }
        
        // Se ci sono problemi, ricrea le tabelle
        return $this->create_tables();
    }
}
