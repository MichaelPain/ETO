<?php
/**
 * Template per la pagina di aggiunta team
 * Versione modificata per utilizzare il processore di form diretto
 * 
 * @package ETO
 * @since 2.5.3
 */

// Verifica che l'utente sia loggato e abbia i permessi necessari
if (!current_user_can('manage_options')) {
    wp_die(__('Non hai i permessi necessari per accedere a questa pagina.', 'eto'));
}

// Ottieni i giochi disponibili
$games = array(
    'lol' => 'League of Legends',
    'dota2' => 'Dota 2',
    'csgo' => 'CS:GO',
    'valorant' => 'Valorant',
    'fortnite' => 'Fortnite',
    'pubg' => 'PUBG',
    'rocketleague' => 'Rocket League',
    'overwatch' => 'Overwatch',
    'fifa' => 'FIFA',
    'other' => 'Altro'
);

// Ottieni gli utenti per il capitano
$users = get_users(array('role__in' => array('administrator', 'editor', 'author', 'subscriber')));

// Ottieni il percorso del plugin
$plugin_url = plugin_dir_url(dirname(dirname(__FILE__)));
?>

<div class="wrap">
    <h1><?php _e('Aggiungi nuovo team', 'eto'); ?></h1>
    
    <div id="eto-messages"></div>
    
    <form id="eto-add-team-form" class="eto-form" method="post" action="<?php echo $plugin_url . 'eto-process.php'; ?>">
        <input type="hidden" name="action" value="eto_create_team">
        <input type="hidden" name="eto_nonce" value="<?php echo wp_create_nonce('eto_create_team'); ?>">
        
        <div class="eto-form-section">
            <h2><?php _e('Informazioni generali', 'eto'); ?></h2>
            
            <div class="eto-form-field">
                <label for="team-name"><?php _e('Nome del team', 'eto'); ?> <span class="required">*</span></label>
                <input type="text" id="team-name" name="name" required>
            </div>
            
            <div class="eto-form-field">
                <label for="team-description"><?php _e('Descrizione', 'eto'); ?></label>
                <?php
                wp_editor('', 'description', array(
                    'textarea_name' => 'description',
                    'textarea_rows' => 10,
                    'media_buttons' => true,
                    'teeny' => true,
                    'quicktags' => true
                ));
                ?>
            </div>
            
            <div class="eto-form-field">
                <label for="team-game"><?php _e('Gioco principale', 'eto'); ?> <span class="required">*</span></label>
                <select id="team-game" name="game" required>
                    <option value=""><?php _e('Seleziona un gioco', 'eto'); ?></option>
                    <?php foreach ($games as $key => $value) : ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="eto-form-field">
                <label for="team-captain"><?php _e('Capitano', 'eto'); ?> <span class="required">*</span></label>
                <select id="team-captain" name="captain_id" required>
                    <option value=""><?php _e('Seleziona un utente', 'eto'); ?></option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="eto-form-field">
                <label for="team-logo-url"><?php _e('Logo', 'eto'); ?></label>
                <input type="text" id="team-logo-url" name="logo_url" readonly>
                <button id="upload-logo-button" class="button"><?php _e('Carica logo', 'eto'); ?></button>
                <div id="logo-preview"></div>
            </div>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Contatti', 'eto'); ?></h2>
            
            <div class="eto-form-field">
                <label for="team-email"><?php _e('Email', 'eto'); ?></label>
                <input type="email" id="team-email" name="email">
            </div>
            
            <div class="eto-form-field">
                <label for="team-website"><?php _e('Sito web', 'eto'); ?></label>
                <input type="url" id="team-website" name="website">
            </div>
            
            <div class="eto-form-field">
                <label for="team-social-twitter"><?php _e('Twitter', 'eto'); ?></label>
                <input type="text" id="team-social-twitter" name="social_media[twitter]">
            </div>
            
            <div class="eto-form-field">
                <label for="team-social-facebook"><?php _e('Facebook', 'eto'); ?></label>
                <input type="text" id="team-social-facebook" name="social_media[facebook]">
            </div>
            
            <div class="eto-form-field">
                <label for="team-social-instagram"><?php _e('Instagram', 'eto'); ?></label>
                <input type="text" id="team-social-instagram" name="social_media[instagram]">
            </div>
            
            <div class="eto-form-field">
                <label for="team-social-twitch"><?php _e('Twitch', 'eto'); ?></label>
                <input type="text" id="team-social-twitch" name="social_media[twitch]">
            </div>
        </div>
        
        <div class="eto-form-actions">
            <button type="submit" class="button button-primary"><?php _e('Crea team', 'eto'); ?></button>
            <a href="<?php echo admin_url('admin.php?page=eto-teams'); ?>" class="button"><?php _e('Annulla', 'eto'); ?></a>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Gestione caricamento logo
    $('#upload-logo-button').on('click', function(e) {
        e.preventDefault();
        
        var custom_uploader = wp.media({
            title: '<?php _e('Seleziona o carica un\'immagine', 'eto'); ?>',
            button: {
                text: '<?php _e('Usa questa immagine', 'eto'); ?>'
            },
            multiple: false
        });
        
        custom_uploader.on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#team-logo-url').val(attachment.url);
            $('#logo-preview').html('<img src="' + attachment.url + '" style="max-width: 150px; margin-top: 10px;">');
        });
        
        custom_uploader.open();
    });
});
</script>
