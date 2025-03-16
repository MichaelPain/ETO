<?php
/**
 * Template per la pagina delle impostazioni
 * 
 * @package ETO
 * @since 2.5.3
 */

// Verifica che l'utente sia loggato e abbia i permessi necessari
if (!current_user_can('manage_options')) {
    wp_die(__('Non hai i permessi necessari per accedere a questa pagina.', 'eto'));
}

// Ottieni le opzioni salvate
$default_format = get_option('eto_default_format', 'single_elimination');
$default_game = get_option('eto_default_game', 'lol');
$max_teams = get_option('eto_max_teams_per_tournament', 32);
$enable_third_place = get_option('eto_enable_third_place_match', 1);
$riot_api_key = get_option('eto_riot_api_key', '');
$enable_riot_api = get_option('eto_enable_riot_api', 0);

// Ottieni le pagine per i tornei e i team
$tournament_page = get_option('eto_tournament_page', 0);
$team_page = get_option('eto_team_page', 0);

// Ottieni tutte le pagine
$pages = get_pages();

// Registra le opzioni
function eto_register_settings() {
    // Registra le opzioni eto_settings
    register_setting('eto_settings', 'eto_default_format');
    register_setting('eto_settings', 'eto_default_game');
    register_setting('eto_settings', 'eto_max_teams_per_tournament');
    register_setting('eto_settings', 'eto_enable_third_place_match');
    register_setting('eto_settings', 'eto_riot_api_key');
    register_setting('eto_settings', 'eto_enable_riot_api');
    register_setting('eto_settings', 'eto_tournament_page');
    register_setting('eto_settings', 'eto_team_page');
}
add_action('admin_init', 'eto_register_settings');
?>

<div class="wrap">
    <h1><?php _e('Impostazioni ETO', 'eto'); ?></h1>
    
    <?php settings_errors(); ?>
    
    <form method="post" action="options.php">
        <?php
        // Output dei campi nascosti necessari per WordPress
        settings_fields('eto_settings');
        do_settings_sections('eto-settings');
        ?>
        
        <div class="eto-settings-container">
            <div class="eto-settings-section">
                <h2><?php _e('Impostazioni Generali', 'eto'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="eto_default_format"><?php _e('Formato Predefinito', 'eto'); ?></label>
                        </th>
                        <td>
                            <select name="eto_default_format" id="eto_default_format">
                                <option value="single_elimination" <?php selected($default_format, 'single_elimination'); ?>><?php _e('Eliminazione diretta', 'eto'); ?></option>
                                <option value="double_elimination" <?php selected($default_format, 'double_elimination'); ?>><?php _e('Doppia eliminazione', 'eto'); ?></option>
                                <option value="round_robin" <?php selected($default_format, 'round_robin'); ?>><?php _e('Girone all\'italiana', 'eto'); ?></option>
                                <option value="swiss" <?php selected($default_format, 'swiss'); ?>><?php _e('Sistema svizzero', 'eto'); ?></option>
                                <option value="custom" <?php selected($default_format, 'custom'); ?>><?php _e('Personalizzato', 'eto'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="eto_default_game"><?php _e('Gioco Predefinito', 'eto'); ?></label>
                        </th>
                        <td>
                            <select name="eto_default_game" id="eto_default_game">
                                <option value="lol" <?php selected($default_game, 'lol'); ?>><?php _e('League of Legends', 'eto'); ?></option>
                                <option value="dota2" <?php selected($default_game, 'dota2'); ?>><?php _e('Dota 2', 'eto'); ?></option>
                                <option value="csgo" <?php selected($default_game, 'csgo'); ?>><?php _e('CS:GO', 'eto'); ?></option>
                                <option value="valorant" <?php selected($default_game, 'valorant'); ?>><?php _e('Valorant', 'eto'); ?></option>
                                <option value="fortnite" <?php selected($default_game, 'fortnite'); ?>><?php _e('Fortnite', 'eto'); ?></option>
                                <option value="pubg" <?php selected($default_game, 'pubg'); ?>><?php _e('PUBG', 'eto'); ?></option>
                                <option value="rocketleague" <?php selected($default_game, 'rocketleague'); ?>><?php _e('Rocket League', 'eto'); ?></option>
                                <option value="overwatch" <?php selected($default_game, 'overwatch'); ?>><?php _e('Overwatch', 'eto'); ?></option>
                                <option value="fifa" <?php selected($default_game, 'fifa'); ?>><?php _e('FIFA', 'eto'); ?></option>
                                <option value="other" <?php selected($default_game, 'other'); ?>><?php _e('Altro', 'eto'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="eto_max_teams_per_tournament"><?php _e('Numero Massimo di Team per Torneo', 'eto'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="eto_max_teams_per_tournament" id="eto_max_teams_per_tournament" value="<?php echo esc_attr($max_teams); ?>" min="2" max="128" step="1">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="eto_enable_third_place_match"><?php _e('Abilita Finale 3°/4° Posto', 'eto'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="eto_enable_third_place_match" id="eto_enable_third_place_match" value="1" <?php checked($enable_third_place, 1); ?>>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="eto-settings-section">
                <h2><?php _e('Pagine', 'eto'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="eto_tournament_page"><?php _e('Pagina Tornei', 'eto'); ?></label>
                        </th>
                        <td>
                            <select name="eto_tournament_page" id="eto_tournament_page">
                                <option value="0"><?php _e('Seleziona una pagina', 'eto'); ?></option>
                                <?php foreach ($pages as $page) : ?>
                                    <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($tournament_page, $page->ID); ?>><?php echo esc_html($page->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Seleziona la pagina che mostrerà l\'elenco dei tornei.', 'eto'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="eto_team_page"><?php _e('Pagina Team', 'eto'); ?></label>
                        </th>
                        <td>
                            <select name="eto_team_page" id="eto_team_page">
                                <option value="0"><?php _e('Seleziona una pagina', 'eto'); ?></option>
                                <?php foreach ($pages as $page) : ?>
                                    <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($team_page, $page->ID); ?>><?php echo esc_html($page->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Seleziona la pagina che mostrerà l\'elenco dei team.', 'eto'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="eto-settings-section">
                <h2><?php _e('Impostazioni API', 'eto'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="eto_riot_api_key"><?php _e('Chiave API Riot', 'eto'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="eto_riot_api_key" id="eto_riot_api_key" value="<?php echo esc_attr($riot_api_key); ?>" class="regular-text">
                            <p class="description"><?php _e('Inserisci la tua chiave API Riot. Puoi ottenerla da <a href="https://developer.riotgames.com/" target="_blank">developer.riotgames.com</a>', 'eto'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="eto_enable_riot_api"><?php _e('Abilita API Riot', 'eto'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="eto_enable_riot_api" id="eto_enable_riot_api" value="1" <?php checked($enable_riot_api, 1); ?>>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php submit_button(__('Salva Impostazioni', 'eto')); ?>
    </form>
</div>
