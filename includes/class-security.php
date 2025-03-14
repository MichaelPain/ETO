<?php
/**
 * Classe per la gestione della sicurezza
 * 
 * Fornisce metodi per la protezione contro attacchi comuni
 * 
 * @package ETO
 * @since 2.5.1
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Security {
    
    /**
     * Istanza singleton
     *
     * @var ETO_Security
     */
    private static $instance = null;
    
    /**
     * Ottiene l'istanza singleton
     *
     * @return ETO_Security
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
        // Inizializza i controlli di sicurezza
        $this->init();
    }
    
    /**
     * Inizializza i controlli di sicurezza
     */
    public function init() {
        // Aggiungi filtri e azioni per la sicurezza
        add_filter('eto_form_fields', [$this, 'add_nonce_field']);
        add_action('init', [$this, 'register_security_headers']);
        
        // Proteggi contro attacchi XSS nei parametri GET
        $this->sanitize_get_params();
    }
    
    /**
     * Aggiunge un campo nonce ai form
     *
     * @param array $fields Campi del form
     * @return array Campi aggiornati
     */
    public function add_nonce_field($fields) {
        $action = isset($fields['form_id']) ? 'eto_' . $fields['form_id'] : 'eto_default_action';
        
        $fields['_wpnonce'] = wp_nonce_field($action, '_wpnonce', true, false);
        $fields['_wp_http_referer'] = wp_referer_field(false) ;
        
        return $fields;
    }
    
    /**
     * Registra header di sicurezza
     */
    public function register_security_headers() {
        // Aggiungi header di sicurezza solo se non è una richiesta AJAX
        if (!wp_doing_ajax()) {
            // Content Security Policy
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://ajax.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:;") ;
            
            // X-XSS-Protection
            header("X-XSS-Protection: 1; mode=block");
            
            // X-Content-Type-Options
            header("X-Content-Type-Options: nosniff");
            
            // Referrer-Policy
            header("Referrer-Policy: strict-origin-when-cross-origin");
            
            // Permissions-Policy
            header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
        }
    }
    
    /**
     * Sanitizza i parametri GET
     */
    private function sanitize_get_params() {
        foreach ($_GET as $key => $value) {
            if (is_string($value)) {
                $_GET[$key] = sanitize_text_field($value);
            }
        }
    }
    
    /**
     * Verifica un nonce
     *
     * @param string $nonce Valore del nonce
     * @param string $action Azione associata al nonce
     * @return bool True se il nonce è valido, false altrimenti
     */
    public function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }
    
    /**
     * Verifica un nonce da una richiesta
     *
     * @param string $action Azione associata al nonce
     * @param string $nonce_name Nome del campo nonce (default: _wpnonce)
     * @param bool $die Se terminare l'esecuzione in caso di nonce non valido
     * @return bool True se il nonce è valido, false altrimenti
     */
    public function verify_request_nonce($action, $nonce_name = '_wpnonce', $die = true) {
        $nonce = isset($_REQUEST[$nonce_name]) ? $_REQUEST[$nonce_name] : '';
        
        if (!$this->verify_nonce($nonce, $action)) {
            if ($die) {
                wp_die(
                    __('Errore di sicurezza: token di verifica non valido.', 'eto'),
                    __('Errore di sicurezza', 'eto'),
                    [
                        'response' => 403,
                        'back_link' => true,
                    ]
                );
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitizza un array ricorsivamente
     *
     * @param array $array Array da sanitizzare
     * @return array Array sanitizzato
     */
    public function sanitize_array($array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->sanitize_array($value);
            } else {
                $array[$key] = sanitize_text_field($value);
            }
        }
        
        return $array;
    }
    
    /**
     * Sanitizza i dati di un form in base al tipo
     *
     * @param array $data Dati del form
     * @param array $fields_config Configurazione dei campi
     * @return array Dati sanitizzati
     */
    public function sanitize_form_data($data, $fields_config) {
        $sanitized = [];
        
        foreach ($fields_config as $field_name => $config) {
            if (!isset($data[$field_name])) {
                continue;
            }
            
            $value = $data[$field_name];
            
            switch ($config['type']) {
                case 'text':
                case 'select':
                case 'radio':
                    $sanitized[$field_name] = sanitize_text_field($value);
                    break;
                    
                case 'email':
                    $sanitized[$field_name] = sanitize_email($value);
                    break;
                    
                case 'url':
                    $sanitized[$field_name] = esc_url_raw($value);
                    break;
                    
                case 'textarea':
                    $sanitized[$field_name] = sanitize_textarea_field($value);
                    break;
                    
                case 'html':
                    $allowed_html = isset($config['allowed_html']) ? $config['allowed_html'] : 'post';
                    $sanitized[$field_name] = wp_kses($value, $allowed_html);
                    break;
                    
                case 'int':
                case 'number':
                    $sanitized[$field_name] = intval($value);
                    break;
                    
                case 'float':
                    $sanitized[$field_name] = floatval($value);
                    break;
                    
                case 'checkbox':
                    $sanitized[$field_name] = isset($value) ? 1 : 0;
                    break;
                    
                case 'array':
                    if (is_array($value)) {
                        $sanitized[$field_name] = $this->sanitize_array($value);
                    } else {
                        $sanitized[$field_name] = [];
                    }
                    break;
                    
                default:
                    $sanitized[$field_name] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Valida i dati di un form in base alle regole
     *
     * @param array $data Dati del form
     * @param array $rules Regole di validazione
     * @return array Array di errori (vuoto se nessun errore)
     */
    public function validate_form_data($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field_name => $field_rules) {
            $value = isset($data[$field_name]) ? $data[$field_name] : '';
            
            foreach ($field_rules as $rule => $rule_value) {
                switch ($rule) {
                    case 'required':
                        if ($rule_value && empty($value)) {
                            $errors[$field_name] = __('Questo campo è obbligatorio.', 'eto');
                        }
                        break;
                        
                    case 'email':
                        if ($rule_value && !empty($value) && !is_email($value)) {
                            $errors[$field_name] = __('Inserisci un indirizzo email valido.', 'eto');
                        }
                        break;
                        
                    case 'url':
                        if ($rule_value && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[$field_name] = __('Inserisci un URL valido.', 'eto');
                        }
                        break;
                        
                    case 'min_length':
                        if (!empty($value) && strlen($value) < $rule_value) {
                            $errors[$field_name] = sprintf(__('Questo campo deve contenere almeno %d caratteri.', 'eto'), $rule_value);
                        }
                        break;
                        
                    case 'max_length':
                        if (!empty($value) && strlen($value) > $rule_value) {
                            $errors[$field_name] = sprintf(__('Questo campo non può contenere più di %d caratteri.', 'eto'), $rule_value);
                        }
                        break;
                        
                    case 'min':
                        if (!empty($value) && floatval($value) < $rule_value) {
                            $errors[$field_name] = sprintf(__('Il valore minimo è %s.', 'eto'), $rule_value);
                        }
                        break;
                        
                    case 'max':
                        if (!empty($value) && floatval($value) > $rule_value) {
                            $errors[$field_name] = sprintf(__('Il valore massimo è %s.', 'eto'), $rule_value);
                        }
                        break;
                        
                    case 'pattern':
                        if (!empty($value) && !preg_match($rule_value, $value)) {
                            $errors[$field_name] = __('Il formato non è valido.', 'eto');
                        }
                        break;
                        
                    case 'in':
                        if (!empty($value) && !in_array($value, $rule_value)) {
                            $errors[$field_name] = __('Il valore selezionato non è valido.', 'eto');
                        }
                        break;
                }
                
                // Se c'è già un errore per questo campo, passa al campo successivo
                if (isset($errors[$field_name])) {
                    break;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Genera un token CSRF
     *
     * @param string $action Azione associata al token
     * @return string Token CSRF
     */
    public function generate_csrf_token($action) {
        return wp_create_nonce('eto_csrf_' . $action);
    }
    
    /**
     * Verifica un token CSRF
     *
     * @param string $token Token da verificare
     * @param string $action Azione associata al token
     * @return bool True se il token è valido, false altrimenti
     */
    public function verify_csrf_token($token, $action) {
        return wp_verify_nonce($token, 'eto_csrf_' . $action);
    }
    
    /**
     * Protegge contro attacchi CSRF
     *
     * @param string $action Azione associata al token
     * @return bool True se la richiesta è sicura, false altrimenti
     */
    public function protect_against_csrf($action) {
        // Verifica il metodo della richiesta
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }
        
        // Verifica il referer
        $referer = wp_get_referer();
        if (!$referer) {
            return false;
        }
        
        $site_url = site_url();
        if (strpos($referer, $site_url) !== 0) {
            return false;
        }
        
        // Verifica il token CSRF
        $token = isset($_POST['_csrf_token']) ? $_POST['_csrf_token'] : '';
        if (!$this->verify_csrf_token($token, $action)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Limita le richieste per prevenire attacchi di forza bruta
     *
     * @param string $action Identificatore dell'azione
     * @param int $max_attempts Numero massimo di tentativi
     * @param int $timeframe Intervallo di tempo in secondi
     * @return bool True se la richiesta è consentita, false altrimenti
     */
    public function rate_limit($action, $max_attempts = 5, $timeframe = 300) {
        $ip = $this->get_client_ip();
        $key = 'eto_rate_limit_' . md5($action . '_' . $ip);
        
        // Ottieni i tentativi correnti
        $attempts = get_transient($key);
        
        if ($attempts === false) {
            // Prima richiesta, inizializza il contatore
            set_transient($key, 1, $timeframe);
            return true;
        }
        
        if ($attempts >= $max_attempts) {
            // Limite superato
            return false;
        }
        
        // Incrementa il contatore
        set_transient($key, $attempts + 1, $timeframe);
        return true;
    }
    
    /**
     * Ottiene l'indirizzo IP del client
     *
     * @return string Indirizzo IP
     */
    private function get_client_ip() {
        $ip = '';
        
        // Proxy trusted
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        
        // Validazione IP
        $ip = filter_var($ip, FILTER_VALIDATE_IP);
        
        return $ip ?: '';
    }
}

// Inizializza la classe di sicurezza
function eto_security() {
    return ETO_Security::get_instance();
}
