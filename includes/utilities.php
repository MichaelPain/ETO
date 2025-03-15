<?php
/**
 * Funzioni di utilità per il plugin ETO
 * 
 * Contiene funzioni helper utilizzate in tutto il plugin
 * 
 * @package ETO
 * @since 2.5.0
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

/**
 * Restituisce un'istanza della classe di sicurezza
 * 
 * @return ETO_Security Istanza della classe di sicurezza
 */
function eto_security() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new ETO_Security();
    }
    
    return $instance;
}

/**
 * Restituisce un'istanza della classe di sicurezza avanzata
 * 
 * @return ETO_Security_Enhanced Istanza della classe di sicurezza avanzata
 */
function eto_security_enhanced() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new ETO_Security_Enhanced();
    }
    
    return $instance;
}

/**
 * Restituisce un'istanza della classe di query al database sicura
 * 
 * @return ETO_DB_Query_Secure Istanza della classe di query al database sicura
 */
function eto_db_query_secure() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new ETO_DB_Query_Secure();
    }
    
    return $instance;
}

/**
 * Ottiene una chiave API dal file di configurazione
 * 
 * @param string $service Nome del servizio (es. 'riot')
 * @return string Chiave API o stringa vuota se non trovata
 */
function eto_get_api_key($service) {
    $key_file = ETO_PLUGIN_DIR . '/keys/' . $service . '-api.key';
    
    if (file_exists($key_file)) {
        return trim(file_get_contents($key_file));
    }
    
    // Fallback alle opzioni di WordPress se il file non esiste
    return get_option('eto_' . $service . '_api_key', '');
}

/**
 * Registra un messaggio di log
 * 
 * @param string $message Messaggio da registrare
 * @param string $level Livello di log (info, warning, error)
 * @return void
 */
function eto_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[ETO] ' . $message);
    }
}

/**
 * Formatta una data nel formato locale
 * 
 * @param string $date Data in formato MySQL
 * @param bool $include_time Se includere anche l'ora
 * @return string Data formattata
 */
function eto_format_date($date, $include_time = true) {
    if (empty($date)) {
        return '';
    }
    
    $format = get_option('date_format');
    
    if ($include_time) {
        $format .= ' ' . get_option('time_format');
    }
    
    return date_i18n($format, strtotime($date));
}

/**
 * Verifica se un utente è un amministratore del torneo
 * 
 * @param int $user_id ID dell'utente
 * @param int $tournament_id ID del torneo
 * @return bool True se l'utente è un amministratore del torneo
 */
function eto_is_tournament_admin($user_id, $tournament_id) {
    // Gli amministratori di WordPress sono sempre amministratori dei tornei
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Verifica se l'utente è il creatore del torneo
    $tournament = new ETO_Tournament_Model($tournament_id);
    
    if ($tournament && $tournament->get('created_by') == $user_id) {
        return true;
    }
    
    // Verifica se l'utente è un amministratore del torneo
    $admins = get_post_meta($tournament_id, 'eto_tournament_admins', true);
    
    if (is_array($admins) && in_array($user_id, $admins)) {
        return true;
    }
    
    return false;
}

/**
 * Ottiene i giochi disponibili
 * 
 * @return array Array di giochi disponibili
 */
function eto_get_available_games() {
    $games = [
        'lol' => __('League of Legends', 'eto'),
        'valorant' => __('Valorant', 'eto'),
        'csgo' => __('Counter-Strike 2', 'eto'),
        'dota2' => __('Dota 2', 'eto'),
        'fortnite' => __('Fortnite', 'eto'),
        'rocketleague' => __('Rocket League', 'eto'),
        'overwatch' => __('Overwatch 2', 'eto'),
        'other' => __('Altro', 'eto')
    ];
    
    return apply_filters('eto_available_games', $games);
}

/**
 * Ottiene i formati di torneo disponibili
 * 
 * @return array Array di formati di torneo disponibili
 */
function eto_get_available_formats() {
    $formats = [
        'single_elimination' => __('Eliminazione Singola', 'eto'),
        'double_elimination' => __('Eliminazione Doppia', 'eto'),
        'swiss' => __('Sistema Svizzero', 'eto'),
        'round_robin' => __('Round Robin', 'eto')
    ];
    
    return apply_filters('eto_available_formats', $formats);
}

/**
 * Genera un URL sicuro per un'azione
 * 
 * @param string $action Nome dell'azione
 * @param array $args Argomenti aggiuntivi
 * @return string URL generato
 */
function eto_get_action_url($action, $args = []) {
    $args['action'] = $action;
    $args['nonce'] = wp_create_nonce('eto-' . $action);
    
    return add_query_arg($args, admin_url('admin-ajax.php'));
}

/**
 * Verifica un nonce per un'azione
 * 
 * @param string $action Nome dell'azione
 * @param string $nonce Nonce da verificare
 * @return bool True se il nonce è valido
 */
function eto_verify_nonce($action, $nonce) {
    return wp_verify_nonce($nonce, 'eto-' . $action);
}
