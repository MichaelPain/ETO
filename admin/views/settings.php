<?php
/**
 * Template per la pagina delle impostazioni
 * 
 * @package ETO
 * @since 2.5.0
 */

// Verifica che l'utente sia loggato e abbia i permessi necessari
if (!current_user_can('manage_options')) {
    wp_die(__('Non hai i permessi necessari per accedere a questa pagina.', 'eto'));
}

// Ottieni le opzioni
$default_format = get_option('eto_default_format', 'single_elimination');
$default_game = get_option('eto_default_game', 'lol');
$max_teams = get_option('eto_max_teams_per_tournament', 32);
$enable_third_place = get_option('eto_enable_third_place_match', 1);
$riot_api_key = get_option('eto_riot_api_key', '');
$enable_riot_api = get_option('eto_enable_riot_api', 0);
?>

<div class="wrap">
    <h1><?php _e('Impostazioni ETO', 'eto'); ?></h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('eto_settings'); ?>
        
        <div class="eto-settings-section">
            <h2><?php _e('Impostazioni Generali', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Formato Predefinito', 'eto'); ?></th>
                    <td>
                        <select name="eto_default_format">
                            <?php
                            $formats = eto_get_available_formats();
                            foreach ($formats as $key => $label) {
                                echo '<option value="' . esc_attr($key) . '" ' . selected($default_format, $key, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Gioco Predefinito', 'eto'); ?></th>
                    <td>
                        <select name="eto_default_game">
                            <?php
                            $games = eto_get_available_games();
                            foreach ($games as $key => $label) {
                                echo '<option value="' . esc_attr($key) . '" ' . selected($default_game, $key, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Numero Massimo di Team per Torneo', 'eto'); ?></th>
                    <td>
                        <input type="number" name="eto_max_teams_per_tournament" value="<?php echo esc_attr($max_teams); ?>" min="2" max="128" step="1">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Abilita Finale 3°/4° Posto', 'eto'); ?></th>
                    <td>
                        <input type="checkbox" name="eto_enable_third_place_match" value="1" <?php checked($enable_third_place, 1); ?>>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-settings-section">
            <h2><?php _e('Impostazioni API', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Chiave API Riot', 'eto'); ?></th>
                    <td>
                        <input type="text" name="eto_riot_api_key" value="<?php echo esc_attr($riot_api_key); ?>" class="regular-text">
                        <p class="description"><?php _e('Inserisci la tua chiave API Riot. Puoi ottenerla da <a href="https://developer.riotgames.com/" target="_blank">developer.riotgames.com</a>', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Abilita API Riot', 'eto'); ?></th>
                    <td>
                        <input type="checkbox" name="eto_enable_riot_api" value="1" <?php checked($enable_riot_api, 1); ?>>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button(); ?>
    </form>
</div>
