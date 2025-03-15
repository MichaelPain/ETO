<?php
/**
 * Classe per la gestione del database
 * 
 * Gestisce la creazione e l'aggiornamento delle tabelle del database
 * 
 * @package ETO
 * @since 2.5.0
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Database_Manager {
    
    /**
     * Versione corrente del database
     *
     * @var string
     */
    private $db_version = '2.6.0';
    
    /**
     * Costruttore
     */
    public function __construct() {
        // Nessuna operazione specifica nel costruttore
    }
    
    /**
     * Crea o aggiorna le tabelle del database
     *
     * @return void
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabella dei tornei
        $table_tournaments = $wpdb->prefix . 'eto_tournaments';
        $sql_tournaments = "CREATE TABLE $table_tournaments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            start_date datetime,
            end_date datetime,
            registration_start datetime,
            registration_end datetime,
            max_teams smallint(5) NOT NULL DEFAULT 8,
            min_teams smallint(5) NOT NULL DEFAULT 2,
            format varchar(50) NOT NULL DEFAULT 'single_elimination',
            game varchar(50) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'draft',
            third_place_match tinyint(1) NOT NULL DEFAULT 0,
            is_individual tinyint(1) NOT NULL DEFAULT 0,
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY format (format),
            KEY game (game),
            KEY status (status)
        ) $charset_collate;";
        
        // Tabella dei team
        $table_teams = $wpdb->prefix . 'eto_teams';
        $sql_teams = "CREATE TABLE $table_teams (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            logo varchar(255),
            captain_id bigint(20) NOT NULL,
            game varchar(50) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'active',
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY captain_id (captain_id),
            KEY game (game),
            KEY status (status)
        ) $charset_collate;";
        
        // Tabella dei membri del team
        $table_team_members = $wpdb->prefix . 'eto_team_members';
        $sql_team_members = "CREATE TABLE $table_team_members (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            team_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            role varchar(50) NOT NULL DEFAULT 'player',
            game_id varchar(255),
            joined_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY team_user (team_id,user_id),
            KEY team_id (team_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Tabella delle iscrizioni ai tornei
        $table_tournament_entries = $wpdb->prefix . 'eto_tournament_entries';
        $sql_tournament_entries = "CREATE TABLE $table_tournament_entries (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tournament_id bigint(20) NOT NULL,
            team_id bigint(20) NOT NULL,
            seed int(11) NOT NULL DEFAULT 0,
            checked_in tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY tournament_team (tournament_id,team_id),
            KEY tournament_id (tournament_id),
            KEY team_id (team_id)
        ) $charset_collate;";
        
        // Tabella dei match
        $table_matches = $wpdb->prefix . 'eto_matches';
        $sql_matches = "CREATE TABLE $table_matches (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tournament_id bigint(20) NOT NULL,
            team1_id bigint(20),
            team2_id bigint(20),
            round int(11) NOT NULL,
            match_number int(11) NOT NULL,
            scheduled_date datetime,
            status varchar(20) NOT NULL DEFAULT 'pending',
            winner_id bigint(20),
            loser_id bigint(20),
            is_third_place_match tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY tournament_id (tournament_id),
            KEY team1_id (team1_id),
            KEY team2_id (team2_id),
            KEY round (round),
            KEY status (status)
        ) $charset_collate;";
        
        // Tabella dei risultati dei match
        $table_match_results = $wpdb->prefix . 'eto_match_results';
        $sql_match_results = "CREATE TABLE $table_match_results (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            match_id bigint(20) NOT NULL,
            team1_score int(11) NOT NULL DEFAULT 0,
            team2_score int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY match_id (match_id),
            KEY team1_score (team1_score),
            KEY team2_score (team2_score)
        ) $charset_collate;";
        
        // Tabella dei metadati dei tornei
        $table_tournament_meta = $wpdb->prefix . 'eto_tournament_meta';
        $sql_tournament_meta = "CREATE TABLE $table_tournament_meta (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tournament_id bigint(20) NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext,
            PRIMARY KEY  (id),
            KEY tournament_id (tournament_id),
            KEY meta_key (meta_key)
        ) $charset_collate;";
        
        // Tabella dei metadati dei team
        $table_team_meta = $wpdb->prefix . 'eto_team_meta';
        $sql_team_meta = "CREATE TABLE $table_team_meta (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            team_id bigint(20) NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext,
            PRIMARY KEY  (id),
            KEY team_id (team_id),
            KEY meta_key (meta_key)
        ) $charset_collate;";
        
        // Tabella dei metadati dei match
        $table_match_meta = $wpdb->prefix . 'eto_match_meta';
        $sql_match_meta = "CREATE TABLE $table_match_meta (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            match_id bigint(20) NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext,
            PRIMARY KEY  (id),
            KEY match_id (match_id),
            KEY meta_key (meta_key)
        ) $charset_collate;";
        
        // Tabella degli screenshot dei match
        $table_match_screenshots = $wpdb->prefix . 'eto_match_screenshots';
        $sql_match_screenshots = "CREATE TABLE $table_match_screenshots (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            match_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            team_id bigint(20) NOT NULL,
            file_path varchar(255) NOT NULL,
            uploaded_at datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            validated_by_opponent tinyint(1) NOT NULL DEFAULT 0,
            validated_by_admin tinyint(1) NOT NULL DEFAULT 0,
            validation_notes text,
            PRIMARY KEY  (id),
            KEY match_id (match_id),
            KEY user_id (user_id),
            KEY team_id (team_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Tabella dei partecipanti individuali
        $table_individual_participants = $wpdb->prefix . 'eto_individual_participants';
        $sql_individual_participants = "CREATE TABLE $table_individual_participants (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tournament_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            seed int(11) NOT NULL DEFAULT 0,
            checked_in tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY tournament_user (tournament_id,user_id),
            KEY tournament_id (tournament_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Carica il file necessario per dbDelta
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Crea o aggiorna le tabelle
        dbDelta($sql_tournaments);
        dbDelta($sql_teams);
        dbDelta($sql_team_members);
        dbDelta($sql_tournament_entries);
        dbDelta($sql_matches);
        dbDelta($sql_match_results);
        dbDelta($sql_tournament_meta);
        dbDelta($sql_team_meta);
        dbDelta($sql_match_meta);
        dbDelta($sql_match_screenshots);
        dbDelta($sql_individual_participants);
        
        // Aggiorna la versione del database
        update_option('eto_db_version', $this->db_version);
    }
    
    /**
     * Verifica se è necessario un aggiornamento del database
     *
     * @return bool True se è necessario un aggiornamento, false altrimenti
     */
    public function needs_upgrade() {
        $current_version = get_option('eto_db_version', '0');
        return version_compare($current_version, $this->db_version, '<');
    }
}
