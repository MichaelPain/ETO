<?php
/**
 * Plugin Name: ETO - Esports Tournament Organizer
 * Description: Organizza tornei e competizioni gaming con vari formati
 * Version: 2.5.3
 * Author: Fury Gaming Team
 * Author URI: https://www.furygaming.net
 * Text Domain: eto
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH') ) exit;

// 1. DEFINIZIONE COSTANTI
if (!defined('ETO_VERSION')) define('ETO_VERSION', '2.5.3');
if (!defined('ETO_DEBUG')) define('ETO_DEBUG', true);
if (!defined('ETO_PLUGIN_DIR')) {
    define('ETO_PLUGIN_DIR', untrailingslashit(WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__))));
}
if (!defined('ETO_PLUGIN_URL')) {
    define('ETO_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('ETO_DEBUG_LOG')) {
    define('ETO_DEBUG_LOG', ETO_PLUGIN_DIR . '/logs/debug.log');
}

// Aggiungi le opzioni alla whitelist
function eto_add_allowed_options($allowed_options) {
    $allowed_options['eto_settings'] = [
        'eto_default_format',
        'eto_default_game',
        'eto_max_teams_per_tournament',
        'eto_enable_third_place_match',
        'eto_riot_api_key',
        'eto_enable_riot_api',
        'eto_tournament_page',
        'eto_team_page'
    ];
    return $allowed_options;
}
add_filter('allowed_options', 'eto_add_allowed_options');

// 2. VERIFICA PERMESSI FILE SYSTEM
// Inizializzazione sicura dell'array dei permessi
global $required_perms;
$required_perms = array(
    'directories' => array(
        ETO_PLUGIN_DIR . '/logs/' => 0750,    // -rwxr-x---
        ETO_PLUGIN_DIR . '/uploads/' => 0750  // -rwxr-x---
    ),
    'files' => array(
        ETO_PLUGIN_DIR . '/includes/config.php' => 0600  // -rw-------
    )
);

// Funzione migliorata per la verifica e l'impostazione forzata dei permessi
function eto_check_permissions($force_set = false) {
    global $required_perms;
    $issues_found = false;
    
    // Verifica che l'array sia inizializzato correttamente
    if (!is_array($required_perms) || empty($required_perms)) {
        error_log("[ETO] Errore: Array dei permessi non inizializzato correttamente");
        return false;
    }
    
    // Verifica directory
    if (isset($required_perms['directories']) && is_array($required_perms['directories'])) {
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
            
            // Imposta i permessi in modo forzato durante l'attivazione
            if ($force_set) {
                // Usa metodi alternativi per impostare i permessi
                @chmod($path, $expected_perm);
                
                // Prova con il comando shell se disponibile e se siamo in ambiente Linux/Unix
                if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions'))) && (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')) {
                    @exec('chmod ' . decoct($expected_perm) . ' ' . escapeshellarg($path) . ' 2>&1', $output, $return_var);
                }
                
                // Registra l'operazione
                error_log("[ETO] Impostati permessi directory: " . $path . " a " . decoct($expected_perm));
            } else {
                // Comportamento normale di verifica
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
        }
    }
    
    // Verifica file
    if (isset($required_perms['files']) && is_array($required_perms['files'])) {
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
            
            // Imposta i permessi in modo forzato durante l'attivazione
            if ($force_set) {
                // Usa metodi alternativi per impostare i permessi
                @chmod($file, $expected_perm);
                
                // Prova con il comando shell se disponibile e se siamo in ambiente Linux/Unix
                if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions'))) && (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')) {
                    @exec('chmod ' . decoct($expected_perm) . ' ' . escapeshellarg($file) . ' 2>&1', $output, $return_var);
                }
                
                // Registra l'operazione
                error_log("[ETO] Impostati permessi file: " . $file . " a " . decoct($expected_perm));
            } else {
                // Comportamento normale di verifica
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
        }
    }
    
    return !$issues_found;
}

// 3. INCLUSIONI CORE CON VERIFICA
global $core_files;
$core_files = array(
    'includes/config.php',
    'includes/utilities.php',  // Carica utilities.php prima di class-db-query-secure.php
    'includes/class-db-query.php',  // Carica class-db-query.php prima di class-db-query-secure.php
    'includes/class-db-query-secure.php'
);

// Funzione migliorata per includere i file core
function eto_include_core_files() {
    global $core_files;
    
    // Verifica che l'array sia inizializzato correttamente
    if (!is_array($core_files) || empty($core_files)) {
        error_log("[ETO] Errore: Array dei file core non inizializzato correttamente");
        return false;
    }
    
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
    
    return true;
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
        
        // Gestione speciale per la classe Checkin unificata
        if ($class_name === 'Checkin') {
            $file_path = ETO_PLUGIN_DIR . '/includes/class-checkin-unified.php';
        }
        
        // Gestione speciale per la classe DB_Query
        if ($class_name === 'DB_Query') {
            $file_path = ETO_PLUGIN_DIR . '/includes/class-db-query-secure.php';
        }
        
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
        $GLOBALS['eto_admin_notices'] = array();
    }
    
    // Aggiungi la notifica
    $GLOBALS['eto_admin_notices'][] = array(
        'type' => $type,
        'message' => $message,
        'dismissible' => $dismissible
    );
    
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
    $GLOBALS['eto_admin_notices'] = array();
}

// 6. FUNZIONI DI SICUREZZA - Implementazione di fallback
// Queste funzioni verranno sostituite da quelle in utilities.php quando il file sarà caricato

/**
 * Implementazione di fallback della funzione di sicurezza
 * Questa funzione viene sovrascritta dalla versione in utilities.php quando caricata
 * 
 * Nota: La funzione è stata rimossa da qui per evitare duplicazioni con utilities.php
 * La definizione principale si trova in includes/utilities.php
 */

/**
 * Genera e verifica nonce per la sicurezza CSRF
 * Questa funzione viene sovrascritta dalla versione in utilities.php quando caricata
 */
// La funzione eto_verify_nonce() è stata rimossa da qui per evitare duplicazioni
// La definizione principale si trova in includes/utilities.php

/**
 * Gestione sicura delle chiavi API
 * Questa funzione viene sovrascritta dalla versione in utilities.php quando caricata
 * 
 * Nota: La funzione è stata rimossa da qui per evitare duplicazioni con utilities.php
 * La definizione principale si trova in includes/utilities.php
 */

/**
 * Salva una chiave API in modo sicuro
 * Questa funzione viene sovrascritta dalla versione in utilities.php quando caricata
 */
if (!function_exists('eto_save_api_key')) {
    function eto_save_api_key($key_name, $key_value) {
        if (empty($key_value)) {
            return false;
        }
        
        // Usa l'API Options di WordPress invece di file
        $encrypted_key = wp_salt('auth') . $key_value;
        update_option('eto_api_key_' . sanitize_key($key_name), $encrypted_key);
        return true;
    }
}

// 7. HOOKS DI INIZIALIZZAZIONE

// Attivazione
register_activation_hook(__FILE__, 'eto_activate');
function eto_activate() {
    // Crea le directory necessarie
    wp_mkdir_p(ETO_PLUGIN_DIR . '/logs');
    wp_mkdir_p(ETO_PLUGIN_DIR . '/uploads');
    wp_mkdir_p(ETO_PLUGIN_DIR . '/templates');
    wp_mkdir_p(ETO_PLUGIN_DIR . '/templates/frontend/tournaments');
    wp_mkdir_p(ETO_PLUGIN_DIR . '/templates/frontend/teams');
    wp_mkdir_p(ETO_PLUGIN_DIR . '/templates/frontend/matches');
    wp_mkdir_p(ETO_PLUGIN_DIR . '/templates/frontend/users');
    
    // Imposta i permessi in modo forzato durante l'attivazione
    eto_check_permissions(true);
    
    // Crea/aggiorna le tabelle del database
    if (file_exists(ETO_PLUGIN_DIR . '/includes/class-database-manager.php')) {
        require_once(ETO_PLUGIN_DIR . '/includes/class-database-manager.php');
        $db_manager = new ETO_Database_Manager();
        $db_manager->create_tables();
    }
    
    // Imposta la versione del plugin
    update_option('eto_version', ETO_VERSION);
}

// Disattivazione
register_deactivation_hook(__FILE__, 'eto_deactivate');
function eto_deactivate() {
    // Pulizia temporanea
    wp_clear_scheduled_hook('eto_daily_maintenance');
}

// Disinstallazione
// La logica di disinstallazione è in uninstall.php

// Caricamento traduzioni (spostato da plugins_loaded a init per risolvere il problema di caricamento anticipato)
add_action('init', 'eto_load_textdomain');
function eto_load_textdomain() {
    load_plugin_textdomain('eto', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Inizializzazione
add_action('plugins_loaded', 'eto_init');
function eto_init() {
    // Verifica permessi
    eto_check_permissions();
    
    // Includi i file core
    eto_include_core_files();
    
    // Inizializza le classi principali
    if (is_admin()) {
        // Admin
        if (file_exists(ETO_PLUGIN_DIR . '/admin/class-admin-controller.php')) {
            require_once(ETO_PLUGIN_DIR . '/admin/class-admin-controller.php');
            if (class_exists('ETO_Admin_Controller')) {
                $admin_controller = new ETO_Admin_Controller();
                $admin_controller->init();
            }
        }
    } else {
        // Public
        if (file_exists(ETO_PLUGIN_DIR . '/public/class-public-controller.php')) {
            require_once(ETO_PLUGIN_DIR . '/public/class-public-controller.php');
            if (class_exists('ETO_Public_Controller')) {
                $public_controller = new ETO_Public_Controller();
                $public_controller->init();
            }
        }
    }
    
    // Inizializza la classe Checkin unificata
    if (class_exists('ETO_Checkin')) {
        $checkin = new ETO_Checkin();
        $checkin->init();
    }
    
    // Registra gli shortcode
    if (file_exists(ETO_PLUGIN_DIR . '/public/class-shortcodes.php')) {
        require_once(ETO_PLUGIN_DIR . '/public/class-shortcodes.php');
        if (class_exists('ETO_Shortcodes')) {
            $shortcodes = new ETO_Shortcodes();
            $shortcodes->register();
        }
    }
// Includi il gestore dei form
require_once ETO_PLUGIN_DIR . '/includes/class-form-handler.php';
    
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
