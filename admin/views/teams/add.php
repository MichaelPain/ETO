<?php
/**
 * Vista per l'aggiunta di un nuovo team
 * 
 * @package ETO
 * @subpackage Views
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php _e('Aggiungi Nuovo Team', 'eto'); ?></h1>
    
    <div id="eto-messages"></div>
    
    <form id="eto-add-team-form" class="eto-form">
        <div class="eto-form-section">
            <h2><?php _e('Informazioni Generali', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="team-name"><?php _e('Nome del Team', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="team-name" name="name" class="regular-text" required>
                        <p class="description"><?php _e('Il nome del team che sarà visibile agli utenti.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="team-description"><?php _e('Descrizione', 'eto'); ?></label>
                    </th>
                    <td>
                        <textarea id="team-description" name="description" rows="5" cols="50" class="large-text"></textarea>
                        <p class="description"><?php _e('Una descrizione del team, la sua storia, obiettivi, ecc.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="team-game"><?php _e('Gioco', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select id="team-game" name="game" required>
                            <option value=""><?php _e('Seleziona un gioco', 'eto'); ?></option>
                            <?php foreach ($games as $game_id => $game_name) : ?>
                                <option value="<?php echo esc_attr($game_id); ?>"><?php echo esc_html($game_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Il gioco principale per cui compete il team.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="team-logo-url"><?php _e('Logo URL', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="team-logo-url" name="logo_url" class="regular-text">
                        <button type="button" class="button" id="upload-logo-button"><?php _e('Carica Logo', 'eto'); ?></button>
                        <div id="logo-preview"></div>
                        <p class="description"><?php _e('URL del logo del team.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Capitano del Team', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="team-captain"><?php _e('Capitano', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select id="team-captain" name="captain_id" required>
                            <option value=""><?php _e('Seleziona un utente', 'eto'); ?></option>
                            <?php
                            // Ottieni tutti gli utenti
                            $users = get_users();
                            foreach ($users as $user) {
                                echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</option>';
                            }
                            ?>
                        </select>
                        <p class="description"><?php _e('Il capitano del team. Questa persona avrà permessi speciali per gestire il team.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Informazioni di Contatto', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="team-email"><?php _e('Email', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="team-email" name="email" class="regular-text">
                        <p class="description"><?php _e('Indirizzo email di contatto del team.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="team-website"><?php _e('Sito Web', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="team-website" name="website" class="regular-text">
                        <p class="description"><?php _e('Sito web del team.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="team-social-media"><?php _e('Social Media', 'eto'); ?></label>
                    </th>
                    <td>
                        <div class="social-media-fields">
                            <div class="social-media-field">
                                <label for="team-twitter"><?php _e('Twitter', 'eto'); ?></label>
                                <input type="url" id="team-twitter" name="social_media[twitter]" class="regular-text" placeholder="https://twitter.com/username">
                            </div>
                            
                            <div class="social-media-field">
                                <label for="team-facebook"><?php _e('Facebook', 'eto'); ?></label>
                                <input type="url" id="team-facebook" name="social_media[facebook]" class="regular-text" placeholder="https://facebook.com/pagename">
                            </div>
                            
                            <div class="social-media-field">
                                <label for="team-instagram"><?php _e('Instagram', 'eto'); ?></label>
                                <input type="url" id="team-instagram" name="social_media[instagram]" class="regular-text" placeholder="https://instagram.com/username">
                            </div>
                            
                            <div class="social-media-field">
                                <label for="team-twitch"><?php _e('Twitch', 'eto'); ?></label>
                                <input type="url" id="team-twitch" name="social_media[twitch]" class="regular-text" placeholder="https://twitch.tv/username">
                            </div>
                            
                            <div class="social-media-field">
                                <label for="team-youtube"><?php _e('YouTube', 'eto'); ?></label>
                                <input type="url" id="team-youtube" name="social_media[youtube]" class="regular-text" placeholder="https://youtube.com/channel/channelid">
                            </div>
                            
                            <div class="social-media-field">
                                <label for="team-discord"><?php _e('Discord', 'eto'); ?></label>
                                <input type="url" id="team-discord" name="social_media[discord]" class="regular-text" placeholder="https://discord.gg/invitecode">
                            </div>
                        </div>
                        <p class="description"><?php _e('Collegamenti ai profili social media del team.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="hidden" name="action" value="eto_create_team">
            <input type="hidden" name="eto_nonce" value="<?php echo wp_create_nonce('eto_create_team'); ?>">
            <button type="submit" class="button button-primary"><?php _e('Crea Team', 'eto'); ?></button>
            <a href="<?php echo admin_url('admin.php?page=eto-teams'); ?>" class="button"><?php _e('Annulla', 'eto'); ?></a>
        </p>
    </form>
</div>

<script type="text/javascript">
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
    
    // Gestione invio form
    $('#eto-add-team-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validazione
        var name = $('#team-name').val();
        var game = $('#team-game').val();
        var captain = $('#team-captain').val();
        
        var errors = [];
        
        if (!name) {
            errors.push('<?php _e('Il nome del team è obbligatorio.', 'eto'); ?>');
            $('#team-name').addClass('eto-field-error');
        } else {
            $('#team-name').removeClass('eto-field-error');
        }
        
        if (!game) {
            errors.push('<?php _e('Il gioco è obbligatorio.', 'eto'); ?>');
            $('#team-game').addClass('eto-field-error');
        } else {
            $('#team-game').removeClass('eto-field-error');
        }
        
        if (!captain) {
            errors.push('<?php _e('Il capitano è obbligatorio.', 'eto'); ?>');
            $('#team-captain').addClass('eto-field-error');
        } else {
            $('#team-captain').removeClass('eto-field-error');
        }
        
        if (errors.length > 0) {
            var errorHtml = '<div class="notice notice-error"><p><strong><?php _e('Errori:', 'eto'); ?></strong></p><ul>';
            
            $.each(errors, function(index, error) {
                errorHtml += '<li>' + error + '</li>';
            });
            
            errorHtml += '</ul></div>';
            
            $('#eto-messages').html(errorHtml);
            $('html, body').animate({ scrollTop: 0 }, 'slow');
            return;
        }
        
        // Invio dati
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $(this).serialize(),
            beforeSend: function() {
                $('#eto-messages').html('<div class="notice notice-info"><p><?php _e('Creazione del team in corso...', 'eto'); ?></p></div>');
            },
            success: function(response) {
                if (response.success) {
                    $('#eto-messages').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    
                    // Reindirizza alla pagina di modifica
                    setTimeout(function() {
                        window.location.href = '<?php echo admin_url('admin.php?page=eto-edit-team&id='); ?>' + response.data.team_id;
                    }, 1000);
                } else {
                    var errorHtml = '<div class="notice notice-error"><p>' + response.data.message + '</p>';
                    
                    if (response.data.errors) {
                        errorHtml += '<ul>';
                        
                        $.each(response.data.errors, function(field, error) {
                            errorHtml += '<li>' + error + '</li>';
                            $('#team-' + field).addClass('eto-field-error');
                        });
                        
                        errorHtml += '</ul>';
                    }
                    
                    errorHtml += '</div>';
                    
                    $('#eto-messages').html(errorHtml);
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                }
            },
            error: function() {
                $('#eto-messages').html('<div class="notice notice-error"><p><?php _e('Si è verificato un errore durante l\'elaborazione della richiesta.', 'eto'); ?></p></div>');
                $('html, body').animate({ scrollTop: 0 }, 'slow');
            }
        });
    });
});
</script>

<style>
.eto-form-section {
    margin-bottom: 30px;
    padding: 20px;
    background-color: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.eto-form-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.required {
    color: #dc3232;
}

.eto-field-error {
    border-color: #dc3232 !important;
}

#logo-preview {
    margin-top: 10px;
}

#logo-preview img {
    max-width: 150px;
    border: 1px solid #ddd;
    padding: 5px;
    background: #f9f9f9;
}

.social-media-fields {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    grid-gap: 15px;
    margin-bottom: 10px;
}

.social-media-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}
</style>
