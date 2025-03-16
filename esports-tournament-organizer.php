<?php
/**
 * Plugin Name: Esports Tournament Organizer
 * Plugin URI: https://github.com/MichaelPain/ETO
 * Description: Un plugin per la gestione di tornei di esports.
 * Version: 2.5.4
 * Author: Michael Pain
 * Author URI: https://github.com/MichaelPain
 * Text Domain: eto
 * Domain Path: /languages
 */

// Previeni l'accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Definisci costanti
define('ETO_VERSION', '2.5.4');
define('ETO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ETO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ETO_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Includi i file necessari
require_once ETO_PLUGIN_DIR . 'eto-functions.php';
require_once ETO_PLUGIN_DIR . 'eto-shortcodes.php';
require_once ETO_PLUGIN_DIR . 'eto-widgets.php';

// Includi il gestore AJAX
require_once ETO_PLUGIN_DIR . 'eto-ajax-handler.php';

// Includi i file di amministrazione
if (is_admin()) {
    require_once ETO_PLUGIN_DIR . 'admin/eto-admin.php';
}

// Attivazione del plugin
register_activation_hook(__FILE__, 'eto_activate');

// Disattivazione del plugin
register_deactivation_hook(__FILE__, 'eto_deactivate');

// Funzione di attivazione
function eto_activate() {
    // Crea le tabelle del database
    eto_create_tables();
    
    // Aggiungi le opzioni predefinite
    add_option('eto_default_format', 'single_elimination');
    add_option('eto_default_game', 'lol');
    add_option('eto_max_teams_per_tournament', 32);
    add_option('eto_enable_third_place_match', 1);
    add_option('eto_riot_api_key', '');
    add_option('eto_enable_riot_api', 0);
    add_option('eto_tournament_page', 0);
    add_option('eto_team_page', 0);
    
    // Svuota i rewrite rules
    flush_rewrite_rules();
}

// Funzione di disattivazione
function eto_deactivate() {
    // Svuota i rewrite rules
    flush_rewrite_rules();
}

// Crea le tabelle del database
function eto_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Tabella dei tornei
    $table_name = $wpdb->prefix . 'eto_tournaments';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        description text,
        game varchar(50) NOT NULL,
        format varchar(50) NOT NULL,
        start_date datetime NOT NULL,
        end_date datetime NOT NULL,
        registration_start datetime,
        registration_end datetime,
        min_teams int DEFAULT 2,
        max_teams int DEFAULT 16,
        rules text,
        prizes text,
        featured_image varchar(255),
        status varchar(20) DEFAULT 'draft',
        created_by bigint(20),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime,
        PRIMARY KEY  (id),
        UNIQUE KEY slug (slug)
    ) $charset_collate;";
    
    // Tabella dei team
    $table_name = $wpdb->prefix . 'eto_teams';
    $sql .= "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        description text,
        game varchar(50) NOT NULL,
        logo_url varchar(255),
        captain_id bigint(20) NOT NULL,
        email varchar(100),
        website varchar(255),
        social_media text,
        created_by bigint(20),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime,
        PRIMARY KEY  (id),
        UNIQUE KEY slug (slug)
    ) $charset_collate;";
    
    // Tabella delle iscrizioni ai tornei
    $table_name = $wpdb->prefix . 'eto_tournament_registrations';
    $sql .= "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tournament_id mediumint(9) NOT NULL,
        team_id mediumint(9) NOT NULL,
        status varchar(20) DEFAULT 'pending',
        registered_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY tournament_team (tournament_id, team_id)
    ) $charset_collate;";
    
    // Tabella delle partite
    $table_name = $wpdb->prefix . 'eto_matches';
    $sql .= "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tournament_id mediumint(9) NOT NULL,
        round int NOT NULL,
        match_number int NOT NULL,
        team1_id mediumint(9),
        team2_id mediumint(9),
        team1_score int DEFAULT 0,
        team2_score int DEFAULT 0,
        winner_id mediumint(9),
        status varchar(20) DEFAULT 'pending',
        scheduled_date datetime,
        completed_date datetime,
        notes text,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Carica i file di traduzione
function eto_load_textdomain() {
    load_plugin_textdomain('eto', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'eto_load_textdomain');

// Aggiungi gli script e gli stili
function eto_enqueue_scripts() {
    // Stili
    wp_enqueue_style('eto-style', ETO_PLUGIN_URL . 'assets/css/eto.css', array(), ETO_VERSION);
    
    // Script
    wp_enqueue_script('eto-script', ETO_PLUGIN_URL . 'assets/js/eto.js', array('jquery'), ETO_VERSION, true);
    
    // Localizza lo script
    wp_localize_script('eto-script', 'eto_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eto-nonce')
    ));
}
add_action('wp_enqueue_scripts', 'eto_enqueue_scripts');

// Aggiungi gli script e gli stili di amministrazione
function eto_admin_enqueue_scripts() {
    // Stili
    wp_enqueue_style('eto-admin-style', ETO_PLUGIN_URL . 'admin/assets/css/eto-admin.css', array(), ETO_VERSION);
    
    // Script
    wp_enqueue_script('eto-admin-script', ETO_PLUGIN_URL . 'admin/assets/js/eto-admin.js', array('jquery'), ETO_VERSION, true);
    
    // Localizza lo script
    wp_localize_script('eto-admin-script', 'eto_admin_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eto-admin-nonce')
    ));
    
    // Media Uploader
    wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'eto_admin_enqueue_scripts');
