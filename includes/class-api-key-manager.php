<?php
/**
 * Classe per la gestione sicura delle chiavi API
 * 
 * Fornisce metodi per la gestione sicura delle chiavi API
 * 
 * @package ETO
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_API_Key_Manager {
    
    /**
     * Istanza singleton
     *
     * @var ETO_API_Key_Manager
     */
    private static $instance = null;
    
    /**
     * Istanza della classe di sicurezza
     *
     * @var ETO_Security_Enhanced
     */
    private $security;
    
    /**
     * Ottiene l'istanza singleton
     *
     * @return ETO_API_Key_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Costruttore
     */
    private function __construct() {
        $this->security = eto_security_enhanced();
    }
    
    /**
     * Salva una chiave API
     *
     * @param string $key_name Nome della chiave
     * @param string $key_value Valore della chiave
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function save_api_key($key_name, $key_value) {
        if (empty($key_value)) {
            return false;
        }
        
        // Cripta la chiave
        $encrypted_key = $this->security->encrypt($key_value);
        
        // Salva la chiave criptata
        update_option('eto_api_key_' . sanitize_key($key_name), $encrypted_key);
        
        return true;
    }
    
    /**
     * Ottiene una chiave API
     *
     * @param string $key_name Nome della chiave
     * @return string|false Valore della chiave o false se non trovata
     */
    public function get_api_key($key_name) {
        $encrypted_key = get_option('eto_api_key_' . sanitize_key($key_name));
        
        if (empty($encrypted_key)) {
            // Retrocompatibilità: prova a leggere dal file
            $key_file = ETO_PLUGIN_DIR . '/keys/' . sanitize_file_name($key_name) . '.key';
            
            if (file_exists($key_file)) {
                $key_value = file_get_contents($key_file);
                
                if (!empty($key_value)) {
                    // Migra la chiave al database
                    $this->save_api_key($key_name, trim($key_value));
                    return trim($key_value);
                }
            }
            
            return false;
        }
        
        // Decripta la chiave
        return $this->security->decrypt($encrypted_key);
    }
    
    /**
     * Elimina una chiave API
     *
     * @param string $key_name Nome della chiave
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function delete_api_key($key_name) {
        return delete_option('eto_api_key_' . sanitize_key($key_name));
    }
    
    /**
     * Verifica se una chiave API esiste
     *
     * @param string $key_name Nome della chiave
     * @return bool True se la chiave esiste, false altrimenti
     */
    public function api_key_exists($key_name) {
        return get_option('eto_api_key_' . sanitize_key($key_name)) !== false;
    }
    
    /**
     * Ottiene tutte le chiavi API
     *
     * @return array Array di nomi di chiavi
     */
    public function get_all_api_keys() {
        global $wpdb;
        
        $keys = [];
        $options = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'eto_api_key_%'"
        );
        
        foreach ($options as $option) {
            $key_name = str_replace('eto_api_key_', '', $option->option_name);
            $keys[] = $key_name;
        }
        
        return $keys;
    }
    
    /**
     * Genera una chiave API casuale
     *
     * @param int $length Lunghezza della chiave
     * @return string Chiave API casuale
     */
    public function generate_api_key($length = 32) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $key = '';
        
        for ($i = 0; $i < $length; $i++) {
            $key .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        return $key;
    }
    
    /**
     * Verifica una chiave API
     *
     * @param string $key_name Nome della chiave
     * @param string $key_value Valore della chiave da verificare
     * @return bool True se la chiave è valida, false altrimenti
     */
    public function verify_api_key($key_name, $key_value) {
        $stored_key = $this->get_api_key($key_name);
        
        if ($stored_key === false) {
            return false;
        }
        
        return $stored_key === $key_value;
    }
    
    /**
     * Migra le chiavi API dai file al database
     *
     * @return int Numero di chiavi migrate
     */
    public function migrate_api_keys_from_files() {
        $keys_dir = ETO_PLUGIN_DIR . '/keys/';
        
        if (!is_dir($keys_dir)) {
            return 0;
        }
        
        $key_files = glob($keys_dir . '*.key');
        $migrated = 0;
        
        foreach ($key_files as $key_file) {
            $key_name = basename($key_file, '.key');
            $key_value = file_get_contents($key_file);
            
            if (!empty($key_value) && !$this->api_key_exists($key_name)) {
                if ($this->save_api_key($key_name, trim($key_value))) {
                    $migrated++;
                }
            }
        }
        
        return $migrated;
    }
    
    /**
     * Esporta le chiavi API in un file JSON criptato
     *
     * @param string $password Password per la crittografia
     * @return string|false Contenuto del file JSON criptato o false in caso di errore
     */
    public function export_api_keys($password) {
        $keys = [];
        
        foreach ($this->get_all_api_keys() as $key_name) {
            $keys[$key_name] = $this->get_api_key($key_name);
        }
        
        if (empty($keys)) {
            return false;
        }
        
        $json = json_encode($keys);
        
        // Cripta il JSON con la password
        $method = 'aes-256-cbc';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        $encrypted = openssl_encrypt($json, $method, $password, 0, $iv);
        
        if ($encrypted === false) {
            return false;
        }
        
        // Combina IV e contenuto criptato
        $result = base64_encode($iv . $encrypted);
        
        return $result;
    }
    
    /**
     * Importa le chiavi API da un file JSON criptato
     *
     * @param string $encrypted_json Contenuto del file JSON criptato
     * @param string $password Password per la decrittografia
     * @return int|false Numero di chiavi importate o false in caso di errore
     */
    public function import_api_keys($encrypted_json, $password) {
        $decoded = base64_decode($encrypted_json);
        
        if ($decoded === false) {
            return false;
        }
        
        $method = 'aes-256-cbc';
        $iv_length = openssl_cipher_iv_length($method);
        
        if (strlen($decoded) <= $iv_length) {
            return false;
        }
        
        $iv = substr($decoded, 0, $iv_length);
        $encrypted = substr($decoded, $iv_length);
        
        $json = openssl_decrypt($encrypted, $method, $password, 0, $iv);
        
        if ($json === false) {
            return false;
        }
        
        $keys = json_decode($json, true);
        
        if (!is_array($keys)) {
            return false;
        }
        
        $imported = 0;
        
        foreach ($keys as $key_name => $key_value) {
            if ($this->save_api_key($key_name, $key_value)) {
                $imported++;
            }
        }
        
        return $imported;
    }
    
    /**
     * Ruota una chiave API
     *
     * @param string $key_name Nome della chiave
     * @param int $length Lunghezza della nuova chiave
     * @return string|false Nuova chiave o false in caso di errore
     */
    public function rotate_api_key($key_name, $length = 32) {
        if (!$this->api_key_exists($key_name)) {
            return false;
        }
        
        $new_key = $this->generate_api_key($length);
        
        if ($this->save_api_key($key_name, $new_key)) {
            return $new_key;
        }
        
        return false;
    }
    
    /**
     * Registra l'utilizzo di una chiave API
     *
     * @param string $key_name Nome della chiave
     * @param string $endpoint Endpoint utilizzato
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function log_api_key_usage($key_name, $endpoint) {
        $usage = get_option('eto_api_key_usage_' . sanitize_key($key_name), []);
        
        if (!is_array($usage)) {
            $usage = [];
        }
        
        $today = date('Y-m-d');
        
        if (!isset($usage[$today])) {
            $usage[$today] = [];
        }
        
        if (!isset($usage[$today][$endpoint])) {
            $usage[$today][$endpoint] = 0;
        }
        
        $usage[$today][$endpoint]++;
        
        return update_option('eto_api_key_usage_' . sanitize_key($key_name), $usage);
    }
    
    /**
     * Ottiene le statistiche di utilizzo di una chiave API
     *
     * @param string $key_name Nome della chiave
     * @param string $date Data (formato Y-m-d) o 'all' per tutte le date
     * @return array Statistiche di utilizzo
     */
    public function get_api_key_usage($key_name, $date = 'all') {
        $usage = get_option('eto_api_key_usage_' . sanitize_key($key_name), []);
        
        if (!is_array($usage)) {
            return [];
        }
        
        if ($date === 'all') {
            return $usage;
        }
        
        return isset($usage[$date]) ? $usage[$date] : [];
    }
    
    /**
     * Imposta un limite di utilizzo per una chiave API
     *
     * @param string $key_name Nome della chiave
     * @param int $limit Limite di utilizzo giornaliero
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function set_api_key_limit($key_name, $limit) {
        return update_option('eto_api_key_limit_' . sanitize_key($key_name), $limit);
    }
    
    /**
     * Ottiene il limite di utilizzo di una chiave API
     *
     * @param string $key_name Nome della chiave
     * @return int Limite di utilizzo giornaliero
     */
    public function get_api_key_limit($key_name) {
        return (int) get_option('eto_api_key_limit_' . sanitize_key($key_name), 0);
    }
    
    /**
     * Verifica se una chiave API ha superato il limite di utilizzo
     *
     * @param string $key_name Nome della chiave
     * @return bool True se il limite è stato superato, false altrimenti
     */
    public function is_api_key_limit_exceeded($key_name) {
        $limit = $this->get_api_key_limit($key_name);
        
        if ($limit === 0) {
            return false;
        }
        
        $usage = $this->get_api_key_usage($key_name, date('Y-m-d'));
        $total = 0;
        
        foreach ($usage as $endpoint => $count) {
            $total += $count;
        }
        
        return $total >= $limit;
    }
}

/**
 * Funzione helper per ottenere l'istanza del gestore delle chiavi API
 *
 * @return ETO_API_Key_Manager Istanza del gestore delle chiavi API
 */
function eto_api_key_manager() {
    return ETO_API_Key_Manager::get_instance();
}
