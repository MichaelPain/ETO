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
    
    // NON creiamo una nuova istanza di ETO_Settings_Register qui
    // perché viene già istanziata nel suo file di classe
    // $settings = new ETO_Settings_Register();
}

// Aggiungi il menu di amministrazione
function eto_admin_menu() {
    // Menu principale rimosso per evitare duplicazione con quello in class-settings-register.php
    // Il menu principale è ora gestito solo dalla classe ETO_Settings_Register
}

// Inizializza l'amministrazione
eto_admin_init();

// Rimuoviamo la registrazione del menu per evitare duplicazioni
// add_action('admin_menu', 'eto_admin_menu');
