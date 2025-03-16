<?php
/**
 * File principale per l'amministrazione del plugin ETO
 *
 * Gestisce tutte le funzionalità di amministrazione del plugin
 *
 * @package ETO
 * @subpackage Admin
 * @since 2.5.3
 */
// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

// Includi i file necessari per l'amministrazione
require_once plugin_dir_path(__FILE__) . 'class-admin-controller.php';
require_once plugin_dir_path(__FILE__) . 'class-settings-register.php';
require_once plugin_dir_path(__FILE__) . 'admin-pages.php';

/**
 * Inizializza l'amministrazione del plugin
 */
function eto_admin_init() {
    // Inizializza il controller di amministrazione
    $admin_controller = new ETO_Admin_Controller();
    $admin_controller->init();
    
    // Inizializza le impostazioni
    $settings = new ETO_Settings_Register();
    // Non chiamiamo il metodo init() perché non esiste nella classe ETO_Settings_Register
    // La classe ETO_Settings_Register chiama già init_hooks() nel costruttore
}

// Aggiungi il menu di amministrazione
function eto_admin_menu() {
    // Menu principale
    add_menu_page(
        __('ETO - Gestione Tornei', 'eto'),
        __('ETO Tornei', 'eto'),
        'manage_options',
        'eto-tournaments',
        array('ETO_Admin_Controller', 'render_tournaments_page'),
        'dashicons-awards',
        30
    );
    
    // Sottopagine
    add_submenu_page(
        'eto-tournaments',
        __('Tornei', 'eto'),
        __('Tornei', 'eto'),
        'manage_options',
        'eto-tournaments',
        array('ETO_Admin_Controller', 'render_tournaments_page')
    );
    
    add_submenu_page(
        'eto-tournaments',
        __('Team', 'eto'),
        __('Team', 'eto'),
        'manage_options',
        'eto-teams',
        array('ETO_Admin_Controller', 'render_teams_page')
    );
    
    add_submenu_page(
        'eto-tournaments',
        __('Partecipanti', 'eto'),
        __('Partecipanti', 'eto'),
        'manage_options',
        'eto-participants',
        array('ETO_Admin_Controller', 'render_participants_page')
    );
    
    add_submenu_page(
        'eto-tournaments',
        __('Impostazioni', 'eto'),
        __('Impostazioni', 'eto'),
        'manage_options',
        'eto-settings',
        array('ETO_Admin_Controller', 'render_settings_page')
    );
}

// Inizializza l'amministrazione
eto_admin_init();

// Aggiungi il menu di amministrazione
add_action('admin_menu', 'eto_admin_menu');
