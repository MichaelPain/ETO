<?php
/**
 * Classe per la gestione della sicurezza avanzata
 * 
 * Fornisce metodi avanzati per la protezione contro attacchi comuni
 * 
 * @package ETO
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Security_Enhanced {
    
    /**
     * Istanza singleton
     *
     * @var ETO_Security_Enhanced
     */
    private static $instance = null;
    
    /**
     * Ottiene l'istanza singleton
     *
     * @return ETO_Security_Enhanced
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
        add_action('init', [$this, 'prevent_direct_file_access']);
        add_action('admin_init', [$this, 'enforce_strong_passwords']);
        
        // Proteggi contro attacchi XSS nei parametri GET e POST
        $this->sanitize_request_params();
        
        // Proteggi contro attacchi CSRF
        $this->protect_against_csrf();
        
        // Proteggi contro attacchi di brute force
        $this->protect_against_brute_force();
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
        $fields['_wp_http_referer'] = wp_referer_field(false);
        
        return $fields;
    }
    
    /**
     * Registra header di sicurezza
     */
    public function register_security_headers() {
        // Aggiungi header di sicurezza solo se non è una richiesta AJAX
        if (!wp_doing_ajax()) {
            // Content Security Policy
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://ajax.googleapis.com https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https://secure.gravatar.com; connect-src 'self' https://api.twitch.tv https://api.riotgames.com;");
            
            // X-XSS-Protection
            header("X-XSS-Protection: 1; mode=block");
            
            // X-Content-Type-Options
            header("X-Content-Type-Options: nosniff");
            
            // Referrer-Policy
            header("Referrer-Policy: strict-origin-when-cross-origin");
            
            // Permissions-Policy
            header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
            
            // Feature-Policy
            header("Feature-Policy: geolocation 'none'; microphone 'none'; camera 'none'");
            
            // Strict-Transport-Security (HSTS)
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
            
            // X-Frame-Options
            header("X-Frame-Options: SAMEORIGIN");
        }
    }
    
    /**
     * Previene l'accesso diretto ai file PHP
     */
    public function prevent_direct_file_access() {
        // Verifica se è una richiesta diretta a un file PHP nel plugin
        if (isset($_SERVER['SCRIPT_FILENAME']) && strpos($_SERVER['SCRIPT_FILENAME'], 'ETO') !== false) {
            $script_path = $_SERVER['SCRIPT_FILENAME'];
            
            // Verifica se il file è all'interno della directory del plugin
            if (strpos($script_path, ETO_PLUGIN_DIR) === 0) {
                // Verifica se è un file PHP
                if (pathinfo($script_path, PATHINFO_EXTENSION) === 'php') {
                    // Verifica se è il file principale del plugin
                    if (basename($script_path) !== 'esports-tournament-organizer.php') {
                        // Blocca l'accesso diretto
                        wp_die(__('Accesso diretto ai file del plugin non consentito.', 'eto'), __('Errore di sicurezza', 'eto'), ['response' => 403]);
                    }
                }
            }
        }
    }
    
    /**
     * Impone password forti per gli utenti
     */
    public function enforce_strong_passwords() {
        // Aggiungi filtro per la validazione delle password
        add_filter('user_profile_update_errors', [$this, 'validate_strong_password'], 10, 3);
    }
    
    /**
     * Valida la forza della password
     *
     * @param WP_Error $errors Oggetto errori
     * @param bool $update Flag di aggiornamento
     * @param WP_User $user Oggetto utente
     * @return WP_Error Oggetto errori aggiornato
     */
    public function validate_strong_password($errors, $update, $user) {
        if (isset($_POST['pass1']) && !empty($_POST['pass1'])) {
            $password = $_POST['pass1'];
            
            // Verifica la lunghezza minima
            if (strlen($password) < 12) {
                $errors->add('password_too_short', __('<strong>ERRORE</strong>: La password deve contenere almeno 12 caratteri.', 'eto'));
            }
            
            // Verifica la presenza di lettere maiuscole
            if (!preg_match('/[A-Z]/', $password)) {
                $errors->add('password_no_uppercase', __('<strong>ERRORE</strong>: La password deve contenere almeno una lettera maiuscola.', 'eto'));
            }
            
            // Verifica la presenza di lettere minuscole
            if (!preg_match('/[a-z]/', $password)) {
                $errors->add('password_no_lowercase', __('<strong>ERRORE</strong>: La password deve contenere almeno una lettera minuscola.', 'eto'));
            }
            
            // Verifica la presenza di numeri
            if (!preg_match('/[0-9]/', $password)) {
                $errors->add('password_no_number', __('<strong>ERRORE</strong>: La password deve contenere almeno un numero.', 'eto'));
            }
            
            // Verifica la presenza di caratteri speciali
            if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
                $errors->add('password_no_special_char', __('<strong>ERRORE</strong>: La password deve contenere almeno un carattere speciale.', 'eto'));
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitizza i parametri della richiesta
     */
    private function sanitize_request_params() {
        // Sanitizza i parametri GET
        foreach ($_GET as $key => $value) {
            if (is_string($value)) {
                $_GET[$key] = sanitize_text_field($value);
            } elseif (is_array($value)) {
                $_GET[$key] = $this->sanitize_array($value);
            }
        }
        
        // Sanitizza i parametri POST
        foreach ($_POST as $key => $value) {
            if (is_string($value)) {
                $_POST[$key] = sanitize_text_field($value);
            } elseif (is_array($value)) {
                $_POST[$key] = $this->sanitize_array($value);
            }
        }
    }
    
    /**
     * Protegge contro attacchi CSRF
     */
    private function protect_against_csrf() {
        // Verifica se è una richiesta POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verifica se è una richiesta admin
            if (is_admin() && !wp_doing_ajax()) {
                // Verifica se è una richiesta a una pagina del plugin
                if (isset($_GET['page']) && strpos($_GET['page'], 'eto') === 0) {
                    // Verifica il nonce
                    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'eto_' . $_GET['page'])) {
                        wp_die(__('Errore di sicurezza: token di verifica non valido.', 'eto'), __('Errore di sicurezza', 'eto'), ['response' => 403]);
                    }
                }
            }
        }
    }
    
    /**
     * Protegge contro attacchi di brute force
     */
    private function protect_against_brute_force() {
        // Verifica se è una richiesta di login
        if (isset($_POST['log']) && isset($_POST['pwd'])) {
            $ip = $this->get_client_ip();
            $username = sanitize_user($_POST['log']);
            
            // Ottieni i tentativi falliti
            $failed_attempts = get_transient('eto_failed_login_' . md5($ip));
            
            if ($failed_attempts === false) {
                $failed_attempts = 0;
            }
            
            // Verifica se l'IP è bloccato
            if ($failed_attempts >= 5) {
                // Blocca l'IP per 30 minuti
                wp_die(__('Troppi tentativi di accesso falliti. Riprova tra 30 minuti.', 'eto'), __('Accesso bloccato', 'eto'), ['response' => 403]);
            }
            
            // Aggiungi filtro per il login fallito
            add_action('wp_login_failed', function($username) use ($ip) {
                $failed_attempts = get_transient('eto_failed_login_' . md5($ip));
                
                if ($failed_attempts === false) {
                    $failed_attempts = 1;
                } else {
                    $failed_attempts++;
                }
                
                // Salva il numero di tentativi falliti
                set_transient('eto_failed_login_' . md5($ip), $failed_attempts, 1800); // 30 minuti
                
                // Registra il tentativo fallito
                error_log(sprintf('[ETO] Tentativo di login fallito: %s (IP: %s)', $username, $ip));
            });
            
            // Aggiungi filtro per il login riuscito
            add_action('wp_login', function($username) use ($ip) {
                // Resetta i tentativi falliti
                delete_transient('eto_failed_login_' . md5($ip));
            });
        }
    }
    
    /**
     * Ottiene l'indirizzo IP del client
     *
     * @return string Indirizzo IP
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
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
                    
                case 'date':
                    // Verifica se la data è nel formato corretto (YYYY-MM-DD)
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        $sanitized[$field_name] = $value;
                    } else {
                        $sanitized[$field_name] = '';
                    }
                    break;
                    
                case 'datetime':
                    // Verifica se la data e ora è nel formato corretto (YYYY-MM-DD HH:MM:SS)
                    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
                        $sanitized[$field_name] = $value;
                    } else {
                        $sanitized[$field_name] = '';
                    }
                    break;
                    
                case 'time':
                    // Verifica se l'ora è nel formato corretto (HH:MM:SS)
                    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                        $sanitized[$field_name] = $value;
                    } else {
                        $sanitized[$field_name] = '';
                    }
                    break;
                    
                case 'color':
                    // Verifica se il colore è nel formato corretto (#RRGGBB)
                    if (preg_match('/^#[a-f0-9]{6}$/i', $value)) {
                        $sanitized[$field_name] = $value;
                    } else {
                        $sanitized[$field_name] = '';
                    }
                    break;
                    
                case 'file':
                    // Non sanitizzare i file, verranno gestiti separatamente
                    $sanitized[$field_name] = $value;
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
                        
                    case 'not_in':
                        if (!empty($value) && in_array($value, $rule_value)) {
                            $errors[$field_name] = __('Il valore selezionato non è consentito.', 'eto');
                        }
                        break;
                        
                    case 'date':
                        if ($rule_value && !empty($value)) {
                            $date = date_create_from_format('Y-m-d', $value);
                            if (!$date || date_format($date, 'Y-m-d') !== $value) {
                                $errors[$field_name] = __('Inserisci una data valida nel formato YYYY-MM-DD.', 'eto');
                            }
                        }
                        break;
                        
                    case 'datetime':
                        if ($rule_value && !empty($value)) {
                            $date = date_create_from_format('Y-m-d H:i:s', $value);
                            if (!$date || date_format($date, 'Y-m-d H:i:s') !== $value) {
                                $errors[$field_name] = __('Inserisci una data e ora valida nel formato YYYY-MM-DD HH:MM:SS.', 'eto');
                            }
                        }
                        break;
                        
                    case 'time':
                        if ($rule_value && !empty($value)) {
                            $date = date_create_from_format('H:i:s', $value);
                            if (!$date || date_format($date, 'H:i:s') !== $value) {
                                $errors[$field_name] = __('Inserisci un\'ora valida nel formato HH:MM:SS.', 'eto');
                            }
                        }
                        break;
                        
                    case 'color':
                        if ($rule_value && !empty($value) && !preg_match('/^#[a-f0-9]{6}$/i', $value)) {
                            $errors[$field_name] = __('Inserisci un colore valido nel formato #RRGGBB.', 'eto');
                        }
                        break;
                        
                    case 'file_type':
                        if ($rule_value && !empty($value['name'])) {
                            $file_type = wp_check_filetype($value['name']);
                            if (!in_array($file_type['ext'], $rule_value)) {
                                $errors[$field_name] = sprintf(__('Il tipo di file non è consentito. Tipi consentiti: %s.', 'eto'), implode(', ', $rule_value));
                            }
                        }
                        break;
                        
                    case 'file_size':
                        if ($rule_value && !empty($value['size'])) {
                            $max_size = $rule_value * 1024 * 1024; // Converti in byte
                            if ($value['size'] > $max_size) {
                                $errors[$field_name] = sprintf(__('La dimensione del file non può superare %d MB.', 'eto'), $rule_value);
                            }
                        }
                        break;
                        
                    case 'custom':
                        if (is_callable($rule_value)) {
                            $custom_error = call_user_func($rule_value, $value, $data);
                            if (!empty($custom_error)) {
                                $errors[$field_name] = $custom_error;
                            }
                        }
                        break;
                }
                
                // Interrompi la validazione se è già stato trovato un errore per questo campo
                if (isset($errors[$field_name])) {
                    break;
                }
            }
        }
        
        return $errors;
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
     * @param string $token Token CSRF
     * @param string $action Azione associata al token
     * @return bool True se il token è valido, false altrimenti
     */
    public function verify_csrf_token($token, $action) {
        return wp_verify_nonce($token, 'eto_csrf_' . $action);
    }
    
    /**
     * Cripta un valore
     *
     * @param string $value Valore da criptare
     * @return string Valore criptato
     */
    public function encrypt($value) {
        if (empty($value)) {
            return '';
        }
        
        $key = wp_salt('auth');
        $method = 'aes-256-cbc';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        
        $encrypted = openssl_encrypt($value, $method, $key, 0, $iv);
        
        if ($encrypted === false) {
            return '';
        }
        
        // Combina IV e valore criptato
        $result = base64_encode($iv . $encrypted);
        
        return $result;
    }
    
    /**
     * Decripta un valore
     *
     * @param string $value Valore criptato
     * @return string Valore decriptato
     */
    public function decrypt($value) {
        if (empty($value)) {
            return '';
        }
        
        $key = wp_salt('auth');
        $method = 'aes-256-cbc';
        
        $decoded = base64_decode($value);
        
        if ($decoded === false) {
            return '';
        }
        
        $iv_length = openssl_cipher_iv_length($method);
        
        if (strlen($decoded) <= $iv_length) {
            return '';
        }
        
        $iv = substr($decoded, 0, $iv_length);
        $encrypted = substr($decoded, $iv_length);
        
        $decrypted = openssl_decrypt($encrypted, $method, $key, 0, $iv);
        
        if ($decrypted === false) {
            return '';
        }
        
        return $decrypted;
    }
    
    /**
     * Genera un hash sicuro
     *
     * @param string $value Valore da hashare
     * @return string Hash
     */
    public function hash($value) {
        return wp_hash($value);
    }
    
    /**
     * Verifica un hash
     *
     * @param string $value Valore da verificare
     * @param string $hash Hash da confrontare
     * @return bool True se il hash corrisponde, false altrimenti
     */
    public function verify_hash($value, $hash) {
        return wp_hash($value) === $hash;
    }
    
    /**
     * Genera una password casuale
     *
     * @param int $length Lunghezza della password
     * @return string Password casuale
     */
    public function generate_random_password($length = 12) {
        return wp_generate_password($length, true, true);
    }
    
    /**
     * Genera un token di autenticazione
     *
     * @param int $user_id ID utente
     * @param string $action Azione associata al token
     * @param int $expiration Scadenza in secondi
     * @return string Token di autenticazione
     */
    public function generate_auth_token($user_id, $action, $expiration = 3600) {
        $token = wp_generate_password(32, false);
        $expiration_time = time() + $expiration;
        
        // Salva il token nel database
        update_user_meta($user_id, 'eto_auth_token_' . $action, [
            'token' => $this->hash($token),
            'expiration' => $expiration_time
        ]);
        
        return $token;
    }
    
    /**
     * Verifica un token di autenticazione
     *
     * @param int $user_id ID utente
     * @param string $token Token di autenticazione
     * @param string $action Azione associata al token
     * @return bool True se il token è valido, false altrimenti
     */
    public function verify_auth_token($user_id, $token, $action) {
        $stored_token = get_user_meta($user_id, 'eto_auth_token_' . $action, true);
        
        if (empty($stored_token) || !is_array($stored_token)) {
            return false;
        }
        
        // Verifica la scadenza
        if ($stored_token['expiration'] < time()) {
            delete_user_meta($user_id, 'eto_auth_token_' . $action);
            return false;
        }
        
        // Verifica il token
        if (!$this->verify_hash($token, $stored_token['token'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Invalida un token di autenticazione
     *
     * @param int $user_id ID utente
     * @param string $action Azione associata al token
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function invalidate_auth_token($user_id, $action) {
        return delete_user_meta($user_id, 'eto_auth_token_' . $action);
    }
}

/**
 * Funzione helper per ottenere l'istanza della classe di sicurezza
 *
 * @return ETO_Security_Enhanced Istanza della classe di sicurezza
 */
function eto_security_enhanced() {
    return ETO_Security_Enhanced::get_instance();
}
