<?php
/**
 * Classe per l'integrazione con le API di Riot Games
 * 
 * Gestisce le chiamate alle API di Riot Games per ottenere dati sui giocatori e sulle partite
 * 
 * @package ETO
 * @since 2.5.1
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Riot_API {
    
    /**
     * Istanza singleton
     *
     * @var ETO_Riot_API
     */
    private static $instance = null;
    
    /**
     * Chiave API di Riot Games
     *
     * @var string
     */
    private $api_key;
    
    /**
     * URL base delle API
     *
     * @var array
     */
    private $base_urls = [
        'euw1' => 'https://euw1.api.riotgames.com',
        'eun1' => 'https://eun1.api.riotgames.com',
        'na1' => 'https://na1.api.riotgames.com',
        'kr' => 'https://kr.api.riotgames.com',
        'jp1' => 'https://jp1.api.riotgames.com',
        'br1' => 'https://br1.api.riotgames.com',
        'la1' => 'https://la1.api.riotgames.com',
        'la2' => 'https://la2.api.riotgames.com',
        'oc1' => 'https://oc1.api.riotgames.com',
        'tr1' => 'https://tr1.api.riotgames.com',
        'ru' => 'https://ru.api.riotgames.com'
    ];
    
    /**
     * URL base per le API globali
     *
     * @var string
     */
    private $global_url = 'https://europe.api.riotgames.com';
    
    /**
     * Regione predefinita
     *
     * @var string
     */
    private $default_region = 'euw1';
    
    /**
     * Cache TTL in secondi
     *
     * @var int
     */
    private $cache_ttl = 3600; // 1 ora
    
    /**
     * Ottiene l'istanza singleton
     *
     * @return ETO_Riot_API
     */
    public static function get_instance()  {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Costruttore
     */
    private function __construct() {
        // Ottieni la chiave API
        $this->api_key = eto_get_api_key('riot');
        
        // Imposta la regione predefinita dalle opzioni
        $region = get_option('eto_riot_default_region', 'euw1');
        if (array_key_exists($region, $this->base_urls)) {
            $this->default_region = $region;
        }
        
        // Imposta il TTL della cache dalle opzioni
        $cache_ttl = get_option('eto_riot_cache_ttl', 3600);
        if (is_numeric($cache_ttl)) {
            $this->cache_ttl = intval($cache_ttl);
        }
    }
    
    /**
     * Verifica se la chiave API è valida
     *
     * @return bool True se la chiave API è valida, false altrimenti
     */
    public function is_api_key_valid() {
        if (empty($this->api_key)) {
            return false;
        }
        
        // Prova a fare una richiesta di test
        $response = $this->make_request('/lol/status/v4/platform-data', $this->default_region);
        
        return !is_wp_error($response);
    }
    
    /**
     * Ottiene i dati di un summoner dal nome
     *
     * @param string $summoner_name Nome del summoner
     * @param string $region Regione del summoner
     * @return array|WP_Error Dati del summoner o errore
     */
    public function get_summoner_by_name($summoner_name, $region = '') {
        if (empty($summoner_name)) {
            return new WP_Error('invalid_summoner', __('Nome summoner non valido', 'eto'));
        }
        
        $region = $this->validate_region($region);
        
        // Sanitizza il nome del summoner
        $summoner_name = rawurlencode($summoner_name);
        
        // Controlla la cache
        $cache_key = 'eto_riot_summoner_' . md5($summoner_name . $region);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Effettua la richiesta
        $response = $this->make_request("/lol/summoner/v4/summoners/by-name/{$summoner_name}", $region);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Salva nella cache
        set_transient($cache_key, $response, $this->cache_ttl);
        
        return $response;
    }
    
    /**
     * Ottiene i dati di un summoner dall'ID
     *
     * @param string $summoner_id ID del summoner
     * @param string $region Regione del summoner
     * @return array|WP_Error Dati del summoner o errore
     */
    public function get_summoner_by_id($summoner_id, $region = '') {
        if (empty($summoner_id)) {
            return new WP_Error('invalid_summoner', __('ID summoner non valido', 'eto'));
        }
        
        $region = $this->validate_region($region);
        
        // Controlla la cache
        $cache_key = 'eto_riot_summoner_id_' . md5($summoner_id . $region);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Effettua la richiesta
        $response = $this->make_request("/lol/summoner/v4/summoners/{$summoner_id}", $region);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Salva nella cache
        set_transient($cache_key, $response, $this->cache_ttl);
        
        return $response;
    }
    
    /**
     * Ottiene i dati di un summoner dal PUUID
     *
     * @param string $puuid PUUID del summoner
     * @param string $region Regione del summoner
     * @return array|WP_Error Dati del summoner o errore
     */
    public function get_summoner_by_puuid($puuid, $region = '') {
        if (empty($puuid)) {
            return new WP_Error('invalid_puuid', __('PUUID non valido', 'eto'));
        }
        
        $region = $this->validate_region($region);
        
        // Controlla la cache
        $cache_key = 'eto_riot_summoner_puuid_' . md5($puuid . $region);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Effettua la richiesta
        $response = $this->make_request("/lol/summoner/v4/summoners/by-puuid/{$puuid}", $region);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Salva nella cache
        set_transient($cache_key, $response, $this->cache_ttl);
        
        return $response;
    }
    
    /**
     * Ottiene il rank di un summoner
     *
     * @param string $summoner_id ID del summoner
     * @param string $region Regione del summoner
     * @return array|WP_Error Dati del rank o errore
     */
    public function get_summoner_rank($summoner_id, $region = '') {
        if (empty($summoner_id)) {
            return new WP_Error('invalid_summoner', __('ID summoner non valido', 'eto'));
        }
        
        $region = $this->validate_region($region);
        
        // Controlla la cache
        $cache_key = 'eto_riot_rank_' . md5($summoner_id . $region);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Effettua la richiesta
        $response = $this->make_request("/lol/league/v4/entries/by-summoner/{$summoner_id}", $region);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Salva nella cache
        set_transient($cache_key, $response, $this->cache_ttl);
        
        return $response;
    }
    
    /**
     * Ottiene i match recenti di un summoner
     *
     * @param string $puuid PUUID del summoner
     * @param int $count Numero di match da ottenere
     * @param string $region Regione del summoner
     * @return array|WP_Error Lista di ID match o errore
     */
    public function get_recent_matches($puuid, $count = 10, $region = '') {
        if (empty($puuid)) {
            return new WP_Error('invalid_puuid', __('PUUID non valido', 'eto'));
        }
        
        $region = $this->validate_region($region);
        $count = min(100, max(1, intval($count)));
        
        // Converti la regione nel formato corretto per le API di match
        $region_cluster = $this->get_region_cluster($region);
        
        // Controlla la cache
        $cache_key = 'eto_riot_matches_' . md5($puuid . $count . $region);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Effettua la richiesta
        $response = $this->make_request(
            "/lol/match/v5/matches/by-puuid/{$puuid}/ids",
            $region_cluster,
            ['count' => $count]
        );
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Salva nella cache
        set_transient($cache_key, $response, $this->cache_ttl);
        
        return $response;
    }
    
    /**
     * Ottiene i dettagli di un match
     *
     * @param string $match_id ID del match
     * @param string $region Regione del match
     * @return array|WP_Error Dati del match o errore
     */
    public function get_match_details($match_id, $region = '') {
        if (empty($match_id)) {
            return new WP_Error('invalid_match', __('ID match non valido', 'eto'));
        }
        
        $region = $this->validate_region($region);
        
        // Converti la regione nel formato corretto per le API di match
        $region_cluster = $this->get_region_cluster($region);
        
        // Controlla la cache
        $cache_key = 'eto_riot_match_' . md5($match_id . $region);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Effettua la richiesta
        $response = $this->make_request("/lol/match/v5/matches/{$match_id}", $region_cluster);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Salva nella cache
        set_transient($cache_key, $response, $this->cache_ttl);
        
        return $response;
    }
    
    /**
     * Ottiene la timeline di un match
     *
     * @param string $match_id ID del match
     * @param string $region Regione del match
     * @return array|WP_Error Dati della timeline o errore
     */
    public function get_match_timeline($match_id, $region = '') {
        if (empty($match_id)) {
            return new WP_Error('invalid_match', __('ID match non valido', 'eto'));
        }
        
        $region = $this->validate_region($region);
        
        // Converti la regione nel formato corretto per le API di match
        $region_cluster = $this->get_region_cluster($region);
        
        // Controlla la cache
        $cache_key = 'eto_riot_timeline_' . md5($match_id . $region);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Effettua la richiesta
        $response = $this->make_request("/lol/match/v5/matches/{$match_id}/timeline", $region_cluster);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Salva nella cache
        set_transient($cache_key, $response, $this->cache_ttl);
        
        return $response;
    }
    
    /**
     * Ottiene i dati di un campione
     *
     * @param int $champion_id ID del campione
     * @return array|WP_Error Dati del campione o errore
     */
    public function get_champion_data($champion_id) {
        // Ottieni tutti i campioni
        $champions = $this->get_all_champions();
        
        if (is_wp_error($champions)) {
            return $champions;
        }
        
        // Cerca il campione specifico
        foreach ($champions['data'] as $champion) {
            if ($champion['key'] == $champion_id) {
                return $champion;
            }
        }
        
        return new WP_Error('champion_not_found', __('Campione non trovato', 'eto'));
    }
    
    /**
     * Ottiene tutti i campioni
     *
     * @return array|WP_Error Dati di tutti i campioni o errore
     */
    public function get_all_champions() {
        // Controlla la cache
        $cache_key = 'eto_riot_champions';
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Ottieni la versione più recente
        $versions = $this->get_versions();
        
        if (is_wp_error($versions)) {
            return $versions;
        }
        
        $latest_version = $versions[0];
        
        // Effettua la richiesta
        $url = "https://ddragon.leagueoflegends.com/cdn/{$latest_version}/data/en_US/champion.json";
        $response = wp_remote_get($url) ;
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data)) {
            return new WP_Error('invalid_response', __('Risposta non valida dalle API', 'eto'));
        }
        
        // Salva nella cache
        set_transient($cache_key, $data, $this->cache_ttl * 24); // Cache più lunga per i dati statici
        
        return $data;
    }
    
    /**
     * Ottiene le versioni disponibili
     *
     * @return array|WP_Error Lista di versioni o errore
     */
    public function get_versions() {
        // Controlla la cache
        $cache_key = 'eto_riot_versions';
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Effettua la richiesta
        $url = "https://ddragon.leagueoflegends.com/api/versions.json";
        $response = wp_remote_get($url) ;
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data)) {
            return new WP_Error('invalid_response', __('Risposta non valida dalle API', 'eto'));
        }
        
        // Salva nella cache
        set_transient($cache_key, $data, $this->cache_ttl * 24); // Cache più lunga per i dati statici
        
        return $data;
    }
    
    /**
     * Ottiene l'URL dell'icona di un campione
     *
     * @param string $champion_id ID del campione
     * @return string URL dell'icona
     */
    public function get_champion_icon_url($champion_id) {
        $champion = $this->get_champion_data($champion_id);
        
        if (is_wp_error($champion)) {
            return '';
        }
        
        $versions = $this->get_versions();
        $latest_version = $versions[0];
        
        return "https://ddragon.leagueoflegends.com/cdn/{$latest_version}/img/champion/{$champion['image']['full']}";
    }
    
    /**
     * Ottiene l'URL dell'icona di un summoner
     *
     * @param int $icon_id ID dell'icona
     * @return string URL dell'icona
     */
    public function get_summoner_icon_url($icon_id)  {
        $versions = $this->get_versions();
        
        if (is_wp_error($versions)) {
            return '';
        }
        
        $latest_version = $versions[0];
        
        return "https://ddragon.leagueoflegends.com/cdn/{$latest_version}/img/profileicon/{$icon_id}.png";
    }
    
    /**
     * Effettua una richiesta alle API di Riot Games
     *
     * @param string $endpoint Endpoint dell'API
     * @param string $region Regione
     * @param array $params Parametri della query
     * @return array|WP_Error Risposta o errore
     */
    private function make_request($endpoint, $region, $params = [])  {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Chiave API non configurata', 'eto'));
        }
        
        // Determina l'URL base
        $base_url = $this->get_base_url($region);
        
        if (empty($base_url)) {
            return new WP_Error('invalid_region', __('Regione non valida', 'eto'));
        }
        
        // Costruisci l'URL
        $url = $base_url . $endpoint;
        
        // Aggiungi i parametri della query
        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }
        
        // Effettua la richiesta
        $response = wp_remote_get($url, [
            'headers' => [
                'X-Riot-Token' => $this->api_key
            ],
            'timeout' => 15
        ]);
        
        // Gestisci gli errori
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Log degli errori
        if ($status_code !== 200) {
            $error_message = sprintf(
                __('Errore API Riot (HTTP %d): %s', 'eto'),
                $status_code,
                $body
            );
            
            if (defined('ETO_DEBUG') && ETO_DEBUG) {
                error_log('[ETO] ' . $error_message);
            }
            
            switch ($status_code) {
                case 400:
                    return new WP_Error('bad_request', __('Richiesta non valida', 'eto'));
                case 401:
                    return new WP_Error('unauthorized', __('Chiave API non valida', 'eto'));
                case 403:
                    return new WP_Error('forbidden', __('Accesso negato', 'eto'));
                case 404:
                    return new WP_Error('not_found', __('Risorsa non trovata', 'eto'));
                case 429:
                    return new WP_Error('rate_limit', __('Limite di richieste superato', 'eto'));
                case 500:
                case 502:
                case 503:
                case 504:
                    return new WP_Error('server_error', __('Errore del server Riot', 'eto'));
                default:
                    return new WP_Error('unknown_error', $error_message);
            }
        }
        
        // Decodifica la risposta
        $data = json_decode($body, true);
        
        if (empty($data) && $body !== '[]') {
            return new WP_Error('invalid_response', __('Risposta non valida dalle API', 'eto'));
        }
        
        return $data;
    }
    
    /**
     * Ottiene l'URL base per una regione
     *
     * @param string $region Regione
     * @return string URL base
     */
    private function get_base_url($region) {
        // Per le API globali
        if ($region === 'europe' || $region === 'americas' || $region === 'asia' || $region === 'sea') {
            return "https://{$region}.api.riotgames.com";
        }
        
        // Per le API regionali
        if (isset($this->base_urls[$region]) ) {
            return $this->base_urls[$region];
        }
        
        return $this->base_urls[$this->default_region];
    }
    
    /**
     * Valida una regione
     *
     * @param string $region Regione
     * @return string Regione validata
     */
    private function validate_region($region) {
        if (empty($region) || !isset($this->base_urls[$region])) {
            return $this->default_region;
        }
        
        return $region;
    }
    
    /**
     * Ottiene il cluster di regione per le API di match
     *
     * @param string $region Regione
     * @return string Cluster di regione
     */
    private function get_region_cluster($region) {
        $europe_regions = ['euw1', 'eun1', 'tr1', 'ru'];
        $americas_regions = ['na1', 'br1', 'la1', 'la2'];
        $asia_regions = ['kr', 'jp1'];
        $sea_regions = ['oc1'];
        
        if (in_array($region, $europe_regions)) {
            return 'europe';
        } elseif (in_array($region, $americas_regions)) {
            return 'americas';
        } elseif (in_array($region, $asia_regions)) {
            return 'asia';
        } elseif (in_array($region, $sea_regions)) {
            return 'sea';
        }
        
        return 'europe'; // Default
    }
    
    /**
     * Pulisce la cache
     *
     * @param string $type Tipo di cache da pulire (all, summoner, match, static)
     * @return bool True se la pulizia è riuscita, false altrimenti
     */
    public function clear_cache($type = 'all') {
        global $wpdb;
        
        $deleted = false;
        
        switch ($type) {
            case 'summoner':
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_eto_riot_summoner_%'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_eto_riot_summoner_%'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_eto_riot_rank_%'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_eto_riot_rank_%'");
                $deleted = true;
                break;
                
            case 'match':
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_eto_riot_match_%'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_eto_riot_match_%'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_eto_riot_matches_%'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_eto_riot_matches_%'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_eto_riot_timeline_%'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_eto_riot_timeline_%'");
                $deleted = true;
                break;
                
            case 'static':
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_eto_riot_champions'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_eto_riot_champions'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_eto_riot_versions'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_eto_riot_versions'");
                $deleted = true;
                break;
                
            case 'all':
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_eto_riot_%'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_eto_riot_%'");
                $deleted = true;
                break;
        }
        
        return $deleted;
    }
}

// Inizializza la classe Riot API
function eto_riot_api() {
    return ETO_Riot_API::get_instance();
}
