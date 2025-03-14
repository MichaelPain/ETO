<?php
/**
 * Script di disinstallazione del plugin
 * 
 * Questo file viene eseguito quando il plugin viene disinstallato.
 * Rimuove tutte le tabelle e le opzioni create dal plugin.
 * 
 * @package ETO
 * @since 2.5.1
 */

// Se non è una richiesta di disinstallazione, esci
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Definisci il nome del plugin
define('ETO_PLUGIN_NAME', 'esports-tournament-organizer');

// Carica le funzioni di utilità
require_once(dirname(__FILE__) . '/includes/utilities.php');

/**
 * Classe per la disinstallazione del plugin
 */
class ETO_Uninstaller {
    
    /**
     * Prefisso delle tabelle del plugin
     *
     * @var string
     */
    private $table_prefix;
    
    /**
     * Opzione di conservazione dei dati
     *
     * @var bool
     */
    private $keep_data;
    
    /**
     * Costruttore
     */
    public function __construct() {
        global $wpdb;
        
        $this->table_prefix = $wpdb->prefix . 'eto_';
        $this->keep_data = get_option('eto_keep_data_on_uninstall', false);
    }
    
    /**
     * Esegue la disinstallazione
     */
    public function uninstall() {
        // Se l'opzione di conservazione dei dati è attiva, esci
        if ($this->keep_data) {
            $this->log_uninstall('Disinstallazione completata con conservazione dei dati.');
            return;
        }
        
        // Rimuovi le tabelle del database
        $this->drop_tables();
        
        // Rimuovi le opzioni
        $this->delete_options();
        
        // Rimuovi i file temporanei
        $this->delete_temp_files();
        
        // Rimuovi i transient
        $this->delete_transients();
        
        // Rimuovi i meta degli utenti
        $this->delete_user_meta();
        
        // Rimuovi i cron
        $this->delete_cron_jobs();
        
        // Log della disinstallazione
        $this->log_uninstall('Disinstallazione completata con rimozione di tutti i dati.');
    }
    
    /**
     * Rimuove le tabelle del database
     */
    private function drop_tables() {
        global $wpdb;
        
        // Lista delle tabelle da rimuovere
        $tables = [
            'tournaments',
            'teams',
            'team_members',
            'matches',
            'tournament_entries',
            'audit_logs'
        ];
        
        // Rimuovi le tabelle
        foreach ($tables as $table) {
            $table_name = $this->table_prefix . $table;
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
        }
    }
    
    /**
     * Rimuove le opzioni
     */
    private function delete_options() {
        global $wpdb;
        
        // Rimuovi tutte le opzioni con prefisso eto_
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'eto_%'");
        
        // Rimuovi anche l'opzione di versione
        delete_option('eto_version');
        delete_option('eto_db_version');
    }
    
    /**
     * Rimuove i file temporanei
     */
    private function delete_temp_files() {
        // Percorso della directory uploads
        $upload_dir = wp_upload_dir();
        $eto_dir = $upload_dir['basedir'] . '/eto';
        
        // Se la directory esiste, rimuovila
        if (is_dir($eto_dir)) {
            $this->delete_directory($eto_dir);
        }
        
        // Rimuovi anche la directory logs
        $logs_dir = dirname(__FILE__) . '/logs';
        if (is_dir($logs_dir)) {
            $this->delete_directory($logs_dir);
        }
    }
    
    /**
     * Rimuove una directory e il suo contenuto
     *
     * @param string $dir Percorso della directory
     * @return bool True se la rimozione è riuscita, false altrimenti
     */
    private function delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->delete_directory($path);
            } else {
                @unlink($path);
            }
        }
        
        return @rmdir($dir);
    }
    
    /**
     * Rimuove i transient
     */
    private function delete_transients() {
        global $wpdb;
        
        // Rimuovi tutti i transient con prefisso eto_
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_eto_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_eto_%'");
    }
    
    /**
     * Rimuove i meta degli utenti
     */
    private function delete_user_meta() {
        global $wpdb;
        
        // Rimuovi tutti i meta degli utenti con prefisso eto_
        $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'eto_%'");
    }
    
    /**
     * Rimuove i cron jobs
     */
    private function delete_cron_jobs() {
        // Rimuovi tutti i cron jobs del plugin
        wp_clear_scheduled_hook('eto_daily_maintenance');
        wp_clear_scheduled_hook('eto_tournament_check');
        wp_clear_scheduled_hook('eto_checkin_reminder');
    }
    
    /**
     * Registra un log della disinstallazione
     *
     * @param string $message Messaggio da registrare
     */
    private function log_uninstall($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[ETO] ' . $message);
        }
    }
}

// Esegui la disinstallazione
$uninstaller = new ETO_Uninstaller();
$uninstaller->uninstall();
