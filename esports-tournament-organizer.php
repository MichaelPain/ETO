<?php
/**
 * Plugin Name: ETO - Esports Tournament Organizer
 * Description: Organizza tornei e competizioni gaming con vari formati
 * Version: 2.5.1
 * Author: Fury Gaming Team
 * Author URI: https://www.furygaming.net
 * Text Domain: eto
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH') ) exit;

// 1. DEFINIZIONE COSTANTI
if (!defined('ETO_DEBUG')) define('ETO_DEBUG', true);
if (!defined('ETO_PLUGIN_DIR')) {
    define('ETO_PLUGIN_DIR', untrailingslashit(WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__))));
}
if (!defined('ETO_DEBUG_LOG')) {
    define('ETO_DEBUG_LOG', ETO_PLUGIN_DIR . '/logs/debug.log');
}

// 2. VERIFICA PERMESSI FILE SYSTEM
$required_perms = [
    'directories' => [
        ETO_PLUGIN_DIR . '/logs/' => 0750,    // -rwxr-x---
        ETO_PLUGIN_DIR . '/uploads/' => 0750  // -rwxr-x---
    ],
    'files' => [
        ETO_PLUGIN_DIR . '/includes/config.php' => 0600, // -rw-------
        ETO_PLUGIN_DIR . '/keys/riot-api.key' => 0600    // -rw-------
    ]
];

// Funzione migliorata per la verifica dei permessi
function eto_check_permissions() {
    global $required_perms;
    $issues_found = false;
    
    // Verifica directory
    foreach ($required_perms['directories'] as $path => $expected_perm) {
        if (!is_dir($path)) {
            // Crea la directory se non esiste
            if (!wp_mkdir_p($path)) {
                eto_add_admin_notice(
                    'error',
                    sprintf(__('Impossibile creare la directory: %s', 'eto'), esc_html($path))
                );
                $issues_found = true;
                continue;
            }
        }
        
        $current_perm = fileperms($path) & 0777;
        if ($current_perm != $expected_perm) {
            // Tenta di correggere i permessi
            @chmod($path, $expected_perm);
            
            // Verifica se la correzione ha avuto successo
            $current_perm = fileperms($path) & 0777;
            if ($current_perm != $expected_perm) {
                eto_add_admin_notice(
                    'error',
                    sprintf(
                        __('Permessi directory errati: %s. Attuale: %o, Richiesto: %o', 'eto'),
                        esc_html($path),
                        $current_perm,
                        $expected_perm
                    )
                );
                $issues_found = true;
            }
        }
    }
    
    // Verifica file
    foreach ($required_perms['files'] as $file => $expected_perm) {
        if (!file_exists($file)) {
            // Per i file di configurazione, crea file vuoti se non esistono
            if (strpos($file, 'config.php') !== false) {
                $default_content = "<?php\n// Configurazione generata automaticamente\nif (!defined('ABSPATH')) exit;\n";
                if (!file_put_contents($file, $default_content)) {
                    eto_add_admin_notice(
                        'error',
                        sprintf(__('Impossibile creare il file: %s', 'eto'), esc_html($file))
                    );
                    $issues_found = true;
                    continue;
                }
            } else {
                eto_add_admin_notice(
                    'error',
                    sprintf(__('File mancante: %s', 'eto'), esc_html($file))
                );
                $issues_found = true;
                continue;
            }
        }
        
        $current_perm = fileperms($file) & 0777;
        if ($current_perm != $expected_perm) {
            // Tenta di correggere i permessi
            @chmod($file, $expected_perm);
            
            // Verifica se la correzione ha avuto successo
            $current_perm = fileperms($file) & 0777;
            if ($current_perm != $expected_perm) {
                eto_add_admin_notice(
                    'error',
                    sprintf(
                        __('Permessi file errati: %s. Attuale: %o, Richiesto: %o', 'eto'),
                        esc_html($file),
                        $current_perm,
                        $expected_perm
                    )
                );
                $issues_found = true;
            }
        }
    }
    
    return !$issues_found;
}

// 3. INCLUSIONI CORE CON VERIFICA
$core_files = [
    'includes/config.php',
    'includes/utilities.php',
    'admin/class-settings-register.php',
    'admin/admin-pages.php',
    'public/shortcodes.php',
    'public/class-checkin.php',
    'public/displays.php'
];

// Funzione migliorata per includere i file core
function eto_include_core_files() {
    global $core_files;
    
    foreach ($core_files as $file) {
        $full_path = ETO_PLUGIN_DIR . '/' . $file;
        
        if (!file_exists($full_path)) {
            eto_add_admin_notice(
                'error',
                sprintf(__('File core mancante: %s', 'eto'), esc_html($file))
            );
            continue;
        }
        
        require_once $full_path;
    }
}

// 4. SISTEMA DI AUTOLOAD MIGLIORATO
spl_autoload_register(function($class) {
    $prefix = 'ETO_';
    
    // Verifica se la classe inizia con il nostro prefisso
    if (strpos($class, $prefix) === 0) {
        // Rimuovi il prefisso
        $class_name = str_replace($prefix, '', $class);
        
        // Converti in lowercase e sostituisci underscore con trattini
        $file_name = 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
        
        // Determina il percorso del file
        $file_path = ETO_PLUGIN_DIR . '/includes/' . $file_name;
        
        // Debugging avanzato
        if (defined('ETO_DEBUG') && ETO_DEBUG) {
            error_log("[ETO] Tentativo di caricare: {$file_path}");
        }
        
        // Verifica se il file esiste
        if (file_exists($file_path)) {
            require_once $file_path;
            return;
        }
        
        // Prova nella cartella admin
        $file_path = ETO_PLUGIN_DIR . '/admin/' . $file_name;
        if (file_exists($file_path)) {
            require_once $file_path;
            return;
        }
        
        // Prova nella cartella public
        $file_path = ETO_PLUGIN_DIR . '/public/' . $file_name;
        if (file_exists($file_path)) {
            require_once $file_path;
            return;
        }
        
        if (defined('ETO_DEBUG') && ETO_DEBUG) {
            error_log("[ETO] Classe non trovata: {$class}");
        }
    }
});

// 5. SISTEMA DI GESTIONE ERRORI CENTRALIZZATO
function eto_add_admin_notice($type, $message, $dismissible = true) {
    // Inizializza l'array di notifiche se non esiste
    if (!isset($GLOBALS['eto_admin_notices'])) {
        $GLOBALS['eto_admin_notices'] = [];
    }
    
    // Aggiungi la notifica
    $GLOBALS['eto_admin_notices'][] = [
        'type' => $type,
        'message' => $message,
        'dismissible' => $dismissible
    ];
    
    // Assicurati che l'hook per mostrare le notifiche sia registrato
    if (!has_action('admin_notices', 'eto_display_admin_notices')) {
        add_action('admin_notices', 'eto_display_admin_notices');
    }
    
    // Log degli errori se in debug mode
    if ($type === 'error' && defined('ETO_DEBUG') && ETO_DEBUG) {
        error_log("[ETO] Errore: {$message}");
    }
}

function eto_display_admin_notices() {
    if (!isset($GLOBALS['eto_admin_notices']) || empty($GLOBALS['eto_admin_notices'])) {
        return;
    }
    
    foreach ($GLOBALS['eto_admin_notices'] as $notice) {
        $dismissible_class = $notice['dismissible'] ? 'is-dismissible' : '';
        echo '<div class="notice notice-' . esc_attr($notice['type']) . ' ' . esc_attr($dismissible_class) . '">';
        echo '<p>' . wp_kses_post($notice['message']) . '</p>';
        echo '</div>';
    }
    
    // Resetta le notifiche dopo averle mostrate
    $GLOBALS['eto_admin_notices'] = [];
}

// 6. FUNZIONI DI SICUREZZA

/**
 * Genera e verifica nonce per la sicurezza CSRF
 */
function eto_verify_nonce($nonce_name, $action) {
    if (!isset($_REQUEST[$nonce_name]) || !wp_verify_nonce($_REQUEST[$nonce_name], $action)) {
        eto_add_admin_notice('error', __('Errore di sicurezza: token di verifica non valido.', 'eto'));
        return false;
    }
    return true;
}

/**
 * Gestione sicura delle chiavi API
 */
function eto_save_api_key($key_name, $key_value) {
    if (empty($key_value)) {
        return false;
    }
    
    // Usa l'API Options di WordPress invece di file
    $encrypted_key = wp_salt('auth') . $key_value;
    update_option('eto_api_key_' . sanitize_key($key_name), $encrypted_key);
    return true;
}

function eto_get_api_key($key_name) {
    $encrypted_key = get_option('eto_api_key_' . sanitize_key($key_name));
    if (empty($encrypted_key)) {
        // Retrocompatibilità: prova a leggere dal file
        $key_file = ETO_PLUGIN_DIR . '/keys/' . sanitize_file_name($key_name) . '.key';
        if (file_exists($key_file)) {
            $key_value = file_get_contents($key_file);
            if (!empty($key_value)) {
                // Migra la chiave al database
                eto_save_api_key($key_name, trim($key_value));
                return trim($key_value);
            }
        }
        return false;
    }
    
    return str_replace(wp_salt('auth'), '', $encrypted_key);
}

// 7. HOOKS DI INIZIALIZZAZIONE

// Attivazione
register_activation_hook(__FILE__, 'eto_activate');
function eto_activate() {
    // Crea le directory necessarie
    wp_mkdir_p(ETO_PLUGIN_DIR . '/logs');
    wp_mkdir_p(ETO_PLUGIN_DIR . '/uploads');
    
    // Imposta i permessi
    eto_check_permissions();
    
    // Crea/aggiorna le tabelle del database
    require_once(ETO_PLUGIN_DIR . '/includes/class-database-manager.php');
    $db_manager = new ETO_Database_Manager();
    $db_manager->create_tables();
    
    // Imposta la versione del plugin
    update_option('eto_version', '2.5.1');
}

// Disattivazione
register_deactivation_hook(__FILE__, 'eto_deactivate');
function eto_deactivate() {
    // Pulizia temporanea
    wp_clear_scheduled_hook('eto_daily_maintenance');
}

// Disinstallazione
// La logica di disinstallazione è in uninstall.php

// Inizializzazione
add_action('plugins_loaded', 'eto_init');
function eto_init() {
    // Carica il dominio di traduzione
    load_plugin_textdomain('eto', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Verifica permessi
    eto_check_permissions();
    
    // Includi i file core
    eto_include_core_files();
    
    // Inizializza le classi principali
    if (is_admin()) {
        // Admin
        require_once(ETO_PLUGIN_DIR . '/admin/class-admin-controller.php');
        $admin_controller = new ETO_Admin_Controller();
        $admin_controller->init();
    } else {
        // Public
        require_once(ETO_PLUGIN_DIR . '/public/class-public-controller.php');
        $public_controller = new ETO_Public_Controller();
        $public_controller->init();
    }
    
    // Registra gli shortcode
    require_once(ETO_PLUGIN_DIR . '/public/class-shortcodes.php');
    $shortcodes = new ETO_Shortcodes();
    $shortcodes->register();
    
    // Pianifica attività di manutenzione
    if (!wp_next_scheduled('eto_daily_maintenance')) {
        wp_schedule_event(time(), 'daily', 'eto_daily_maintenance');
    }
}

// Aggiungi azione per la manutenzione giornaliera
add_action('eto_daily_maintenance', 'eto_perform_maintenance');
function eto_perform_maintenance() {
    // Pulizia log vecchi
    $log_dir = ETO_PLUGIN_DIR . '/logs';
    if (is_dir($log_dir)) {
        $files = glob($log_dir . '/*.log');
        $now = time();
        
        foreach ($files as $file) {
            // Elimina log più vecchi di 30 giorni
            if ($now - filemtime($file) >= 30 * 24 * 60 * 60) {
                @unlink($file);
            }
        }
    }
    
    // Altre operazioni di manutenzione...
}
