<?php
/**
 * Classe per la registrazione delle impostazioni del plugin
 * 
 * Gestisce la registrazione delle impostazioni e delle pagine di amministrazione
 * 
 * @package ETO
 * @since 2.5.0
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Settings_Register {
    
    /**
     * Costruttore
     */
    public function __construct() {
        // Inizializza le azioni e i filtri
        $this->init_hooks();
    }
    
    /**
     * Ottiene i giochi disponibili
     * 
     * @return array Array associativo di giochi disponibili
     */
    public function get_available_games() {
        return array(
            'lol' => __('League of Legends', 'eto'),
            'dota2' => __('Dota 2', 'eto'),
            'csgo' => __('CS:GO', 'eto'),
            'valorant' => __('Valorant', 'eto'),
            'fortnite' => __('Fortnite', 'eto'),
            'overwatch' => __('Overwatch', 'eto'),
            'rocketleague' => __('Rocket League', 'eto'),
            'fifa' => __('FIFA', 'eto'),
            'other' => __('Altro', 'eto')
        );
    }
    
    /**
     * Ottiene i formati disponibili
     * 
     * @return array Array associativo di formati disponibili
     */
    public function get_available_formats() {
        return array(
            'single_elimination' => __('Eliminazione Diretta', 'eto'),
            'double_elimination' => __('Doppia Eliminazione', 'eto'),
            'round_robin' => __('Round Robin', 'eto'),
            'swiss' => __('Sistema Svizzero', 'eto'),
            'group_stage' => __('Fase a Gironi', 'eto')
        );
    }
    
    /**
     * Inizializza le azioni e i filtri
     */
    private function init_hooks() {
        // Registra le impostazioni
        add_action('admin_init', array($this, 'register_settings'));
        
        // Aggiungi le pagine di amministrazione
        add_action('admin_menu', array($this, 'add_admin_pages'));
    }
    
    /**
     * Registra le impostazioni del plugin
     */
    public function register_settings() {
add_filter('allowed_options', function($allowed_options) {
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
});
// Assicurati che tutte le opzioni siano registrate
register_setting('eto_settings', 'eto_default_format');
register_setting('eto_settings', 'eto_default_game');
register_setting('eto_settings', 'eto_max_teams_per_tournament');
register_setting('eto_settings', 'eto_enable_third_place_match');
register_setting('eto_settings', 'eto_riot_api_key');
register_setting('eto_settings', 'eto_enable_riot_api');
register_setting('eto_settings', 'eto_tournament_page');
register_setting('eto_settings', 'eto_team_page');

        // Registra la sezione delle impostazioni generali
        add_settings_section(
            'eto_general_settings',
            __('Impostazioni Generali', 'eto'),
            array($this, 'render_general_settings_section'),
            'eto-settings'
        );
        
        // Registra la sezione delle impostazioni API
        add_settings_section(
            'eto_api_settings',
            __('Impostazioni API', 'eto'),
            array($this, 'render_api_settings_section'),
            'eto-settings'
        );
        
        // Registra i campi delle impostazioni generali
        register_setting('eto_general_settings', 'eto_default_format');
        register_setting('eto_general_settings', 'eto_default_game');
        register_setting('eto_general_settings', 'eto_max_teams_per_tournament');
        register_setting('eto_general_settings', 'eto_enable_third_place_match');
        
        // Registra i campi delle impostazioni API
        register_setting('eto_api_settings', 'eto_riot_api_key');
        register_setting('eto_api_settings', 'eto_enable_riot_api');
        
        // Aggiungi i campi delle impostazioni generali
        add_settings_field(
            'eto_default_format',
            __('Formato Predefinito', 'eto'),
            array($this, 'render_default_format_field'),
            'eto-settings',
            'eto_general_settings'
        );
        
        add_settings_field(
            'eto_default_game',
            __('Gioco Predefinito', 'eto'),
            array($this, 'render_default_game_field'),
            'eto-settings',
            'eto_general_settings'
        );
        
        add_settings_field(
            'eto_max_teams_per_tournament',
            __('Numero Massimo di Team per Torneo', 'eto'),
            array($this, 'render_max_teams_field'),
            'eto-settings',
            'eto_general_settings'
        );
        
        add_settings_field(
            'eto_enable_third_place_match',
            __('Abilita Finale 3°/4° Posto', 'eto'),
            array($this, 'render_enable_third_place_field'),
            'eto-settings',
            'eto_general_settings'
        );
        
        // Aggiungi i campi delle impostazioni API
        add_settings_field(
            'eto_riot_api_key',
            __('Chiave API Riot', 'eto'),
            array($this, 'render_riot_api_key_field'),
            'eto-settings',
            'eto_api_settings'
        );
        
        add_settings_field(
            'eto_enable_riot_api',
            __('Abilita API Riot', 'eto'),
            array($this, 'render_enable_riot_api_field'),
            'eto-settings',
            'eto_api_settings'
        );
    }
    
    /**
     * Aggiunge le pagine di amministrazione
     */
    public function add_admin_pages() {
        // Pagina principale
        add_menu_page(
            __('ETO - Esports Tournament Organizer', 'eto'),
            __('ETO', 'eto'),
            'manage_options',
            'eto-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-awards',
            30
        );
        
        // Sottopagine
        add_submenu_page(
            'eto-dashboard',
            __('Dashboard', 'eto'),
            __('Dashboard', 'eto'),
            'manage_options',
            'eto-dashboard',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'eto-dashboard',
            __('Tornei', 'eto'),
            __('Tornei', 'eto'),
            'manage_options',
            'eto-tournaments',
            array($this, 'render_tournaments_page')
        );
        
        add_submenu_page(
            'eto-dashboard',
            __('Team', 'eto'),
            __('Team', 'eto'),
            'manage_options',
            'eto-teams',
            array($this, 'render_teams_page')
        );
        
        add_submenu_page(
            'eto-dashboard',
            __('Match', 'eto'),
            __('Match', 'eto'),
            'manage_options',
            'eto-matches',
            array($this, 'render_matches_page')
        );
        
        add_submenu_page(
            'eto-dashboard',
            __('Impostazioni', 'eto'),
            __('Impostazioni', 'eto'),
            'manage_options',
            'eto-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Renderizza la sezione delle impostazioni generali
     */
    public function render_general_settings_section() {
        echo '<p>' . __('Configura le impostazioni generali del plugin.', 'eto') . '</p>';
    }
    
    /**
     * Renderizza la sezione delle impostazioni API
     */
    public function render_api_settings_section() {
        echo '<p>' . __('Configura le impostazioni per le API esterne.', 'eto') . '</p>';
    }
    
    /**
     * Renderizza il campo del formato predefinito
     */
    public function render_default_format_field() {
        $formats = eto_get_available_formats();
        $current = get_option('eto_default_format', 'single_elimination');
        
        echo '<select name="eto_default_format">';
        foreach ($formats as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($current, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }
    
    /**
     * Renderizza il campo del gioco predefinito
     */
    public function render_default_game_field() {
        $games = eto_get_available_games();
        $current = get_option('eto_default_game', 'lol');
        
        echo '<select name="eto_default_game">';
        foreach ($games as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($current, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }
    
    /**
     * Renderizza il campo del numero massimo di team
     */
    public function render_max_teams_field() {
        $current = get_option('eto_max_teams_per_tournament', 32);
        
        echo '<input type="number" name="eto_max_teams_per_tournament" value="' . esc_attr($current) . '" min="2" max="128" step="1">';
    }
    
    /**
     * Renderizza il campo per abilitare la finale 3°/4° posto
     */
    public function render_enable_third_place_field() {
        $current = get_option('eto_enable_third_place_match', 1);
        
        echo '<input type="checkbox" name="eto_enable_third_place_match" value="1" ' . checked($current, 1, false) . '>';
    }
    
    /**
     * Renderizza il campo della chiave API Riot
     */
    public function render_riot_api_key_field() {
        $current = get_option('eto_riot_api_key', '');
        
        echo '<input type="text" name="eto_riot_api_key" value="' . esc_attr($current) . '" class="regular-text">';
        echo '<p class="description">' . __('Inserisci la tua chiave API Riot. Puoi ottenerla da <a href="https://developer.riotgames.com/" target="_blank">developer.riotgames.com</a>', 'eto') . '</p>';
    }
    
    /**
     * Renderizza il campo per abilitare l'API Riot
     */
    public function render_enable_riot_api_field() {
        $current = get_option('eto_enable_riot_api', 0);
        
        echo '<input type="checkbox" name="eto_enable_riot_api" value="1" ' . checked($current, 1, false) . '>';
    }
    
    /**
     * Renderizza la pagina della dashboard
     */
    public function render_dashboard_page() {
        include ETO_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Renderizza la pagina dei tornei
     */
    public function render_tournaments_page() {
        include ETO_PLUGIN_DIR . 'admin/views/tournaments/list.php';
    }
    
    /**
     * Renderizza la pagina dei team
     */
    public function render_teams_page() {
        include ETO_PLUGIN_DIR . 'admin/views/teams/list.php';
    }
    
    /**
     * Renderizza la pagina dei match
     */
    public function render_matches_page() {
        include ETO_PLUGIN_DIR . 'admin/views/matches/list.php';
    }
    
    /**
     * Renderizza la pagina delle impostazioni
     */
    public function render_settings_page() {
        include ETO_PLUGIN_DIR . 'admin/views/settings.php';
    }
}

// Inizializza la classe
$eto_settings_register = new ETO_Settings_Register();
