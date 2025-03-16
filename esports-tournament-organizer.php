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

// Includi i file delle classi principali (in ordine di dipendenza)
require_once ETO_PLUGIN_DIR . 'includes/class-database-manager.php';
require_once ETO_PLUGIN_DIR . 'includes/class-db-query.php';

// Includi i file di amministrazione
if (is_admin()) {
    require_once ETO_PLUGIN_DIR . 'admin/eto-admin.php';
}

// Attivazione del plugin
register_activation_hook(__FILE__, 'eto_activate');

// Disattivazione del plugin
register_deactivation_hook(__FILE__, 'eto_deactivate');

/**
 * Crea le tabelle del database utilizzando la classe ETO_Database_Manager
 * 
 * @since 2.5.4
 * @return void
 */
function eto_create_tables() {
    // Istanzia la classe Database Manager
    $db_manager = new ETO_Database_Manager();
    
    // Chiama il metodo per creare le tabelle
    $db_manager->create_tables();
    
    // Crea anche la tabella dei log se necessario
    if (class_exists('ETO_Logger')) {
        ETO_Logger::create_table();
    }
}

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

// Carica i file di traduzione
function eto_load_textdomain() {
    load_plugin_textdomain('eto', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'eto_load_textdomain');

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
