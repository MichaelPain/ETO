<?php
/**
 * Template per la pagina di aggiunta torneo
 * Versione modificata per utilizzare AJAX
 * 
 * @package ETO
 * @since 2.5.4
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

// Ottieni i formati disponibili
$formats = array(
    'single_elimination' => 'Eliminazione diretta',
    'double_elimination' => 'Doppia eliminazione',
    'round_robin' => 'Girone all\'italiana',
    'swiss' => 'Sistema svizzero',
    'custom' => 'Personalizzato'
);

// Ottieni il percorso del plugin
$plugin_url = plugin_dir_url(dirname(dirname(__FILE__)));
?>

<div class="wrap">
    <h1><?php _e('Aggiungi nuovo torneo', 'eto'); ?></h1>
    
    <div id="eto-messages"></div>
    
    <form id="eto-add-tournament-form" class="eto-form" method="post">
        <input type="hidden" name="action" value="eto_create_tournament">
        <input type="hidden" name="eto_nonce" value="<?php echo wp_create_nonce('eto_create_tournament'); ?>">
        
        <div class="eto-form-section">
            <h2><?php _e('Informazioni generali', 'eto'); ?></h2>
            
            <div class="eto-form-field">
                <label for="tournament-name"><?php _e('Nome del torneo', 'eto'); ?> <span class="required">*</span></label>
                <input type="text" id="tournament-name" name="name" required>
            </div>
            
            <div class="eto-form-field">
                <label for="tournament-description"><?php _e('Descrizione', 'eto'); ?></label>
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
                <label for="tournament-game"><?php _e('Gioco', 'eto'); ?> <span class="required">*</span></label>
                <select id="tournament-game" name="game" required>
                    <option value=""><?php _e('Seleziona un gioco', 'eto'); ?></option>
                    <?php foreach ($games as $key => $value) : ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="eto-form-field">
                <label for="tournament-format"><?php _e('Formato', 'eto'); ?> <span class="required">*</span></label>
                <select id="tournament-format" name="format" required>
                    <option value=""><?php _e('Seleziona un formato', 'eto'); ?></option>
                    <?php foreach ($formats as $key => $value) : ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="eto-form-field">
                <label for="tournament-featured-image"><?php _e('Immagine in evidenza', 'eto'); ?></label>
                <input type="text" id="tournament-featured-image" name="featured_image" readonly>
                <button id="upload-image-button" class="button"><?php _e('Carica immagine', 'eto'); ?></button>
                <div id="featured-image-preview"></div>
            </div>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Date', 'eto'); ?></h2>
            
            <div class="eto-form-field">
                <label for="tournament-start-date"><?php _e('Data di inizio', 'eto'); ?> <span class="required">*</span></label>
                <input type="datetime-local" id="tournament-start-date" name="start_date" required>
            </div>
            
            <div class="eto-form-field">
                <label for="tournament-end-date"><?php _e('Data di fine', 'eto'); ?> <span class="required">*</span></label>
                <input type="datetime-local" id="tournament-end-date" name="end_date" required>
            </div>
            
            <div class="eto-form-field">
                <label for="tournament-registration-start"><?php _e('Inizio registrazione', 'eto'); ?></label>
                <input type="datetime-local" id="tournament-registration-start" name="registration_start">
            </div>
            
            <div class="eto-form-field">
                <label for="tournament-registration-end"><?php _e('Fine registrazione', 'eto'); ?></label>
                <input type="datetime-local" id="tournament-registration-end" name="registration_end">
            </div>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Partecipanti', 'eto'); ?></h2>
            
            <div class="eto-form-field">
                <label for="tournament-min-teams"><?php _e('Numero minimo di team', 'eto'); ?></label>
                <input type="number" id="tournament-min-teams" name="min_teams" min="2" value="2">
            </div>
            
            <div class="eto-form-field">
                <label for="tournament-max-teams"><?php _e('Numero massimo di team', 'eto'); ?></label>
                <input type="number" id="tournament-max-teams" name="max_teams" min="2" value="16">
            </div>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Regolamento e premi', 'eto'); ?></h2>
            
            <div class="eto-form-field">
                <label for="tournament-rules"><?php _e('Regolamento', 'eto'); ?></label>
                <?php
                wp_editor('', 'rules', array(
                    'textarea_name' => 'rules',
                    'textarea_rows' => 10,
                    'media_buttons' => false,
                    'teeny' => true,
                    'quicktags' => true
                ));
                ?>
            </div>
            
            <div class="eto-form-field">
                <label for="tournament-prizes"><?php _e('Premi', 'eto'); ?></label>
                <?php
                wp_editor('', 'prizes', array(
                    'textarea_name' => 'prizes',
                    'textarea_rows' => 10,
                    'media_buttons' => false,
                    'teeny' => true,
                    'quicktags' => true
                ));
                ?>
            </div>
        </div>
        
        <div class="eto-form-actions">
            <button type="submit" class="button button-primary"><?php _e('Crea torneo', 'eto'); ?></button>
            <a href="<?php echo admin_url('admin.php?page=eto-tournaments'); ?>" class="button"><?php _e('Annulla', 'eto'); ?></a>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Gestione caricamento immagine
    $('#upload-image-button').on('click', function(e) {
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
            $('#tournament-featured-image').val(attachment.url);
            $('#featured-image-preview').html('<img src="' + attachment.url + '" style="max-width: 300px; margin-top: 10px;">');
        });
        
        custom_uploader.open();
    });
    
    // Gestione invio form tramite AJAX
    $('#eto-add-tournament-form').on('submit', function(e) {
        e.preventDefault();
        
        // Mostra messaggio di caricamento
        $('#eto-messages').html('<div class="notice notice-info"><p>Creazione torneo in corso...</p></div>');
        
        // Ottieni i dati del form
        var formData = new FormData(this);
        
        // Aggiungi il contenuto degli editor
        if (typeof tinyMCE !== 'undefined') {
            if (tinyMCE.get('description') !== null) {
                formData.set('description', tinyMCE.get('description').getContent());
            }
            if (tinyMCE.get('rules') !== null) {
                formData.set('rules', tinyMCE.get('rules').getContent());
            }
            if (tinyMCE.get('prizes') !== null) {
                formData.set('prizes', tinyMCE.get('prizes').getContent());
            }
        }
        
        // Invia la richiesta AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Mostra messaggio di successo
                    $('#eto-messages').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    
                    // Reindirizza dopo un breve ritardo
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1000);
                } else {
                    // Mostra messaggio di errore
                    $('#eto-messages').html('<div class="notice notice-error"><p>Errore: ' + response.data + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                // Mostra messaggio di errore
                $('#eto-messages').html('<div class="notice notice-error"><p>Errore durante la richiesta: ' + error + '</p></div>');
            }
        });
    });
});
</script>
