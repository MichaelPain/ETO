<?php
/**
 * Vista per la modifica di un team esistente
 * 
 * @package ETO
 * @subpackage Views
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php _e('Modifica Team', 'eto'); ?></h1>
    
    <div id="eto-messages"></div>
    
    <form id="eto-edit-team-form" class="eto-form">
        <div class="eto-form-section">
            <h2><?php _e('Informazioni Generali', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="team-name"><?php _e('Nome del Team', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="team-name" name="name" class="regular-text" value="<?php echo esc_attr($team->get('name')); ?>" required>
                        <p class="description"><?php _e('Il nome del team che sarà visibile agli utenti.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="team-description"><?php _e('Descrizione', 'eto'); ?></label>
                    </th>
                    <td>
                        <textarea id="team-description" name="description" rows="5" cols="50" class="large-text"><?php echo esc_textarea($team->get('description')); ?></textarea>
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
                                <option value="<?php echo esc_attr($game_id); ?>" <?php selected($team->get('game'), $game_id); ?>><?php echo esc_html($game_name); ?></option>
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
                        <input type="url" id="team-logo-url" name="logo_url" class="regular-text" value="<?php echo esc_url($team->get('logo_url')); ?>">
                        <button type="button" class="button" id="upload-logo-button"><?php _e('Carica Logo', 'eto'); ?></button>
                        <div id="logo-preview">
                            <?php if ($team->get('logo_url')) : ?>
                                <img src="<?php echo esc_url($team->get('logo_url')); ?>" style="max-width: 150px; margin-top: 10px;">
                            <?php endif; ?>
                        </div>
                        <p class="description"><?php _e('URL del logo del team.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Membri del Team', 'eto'); ?></h2>
            
            <div id="team-members">
                <?php
                $members = $team->get_members();
                if (empty($members)) :
                ?>
                    <p><?php _e('Nessun membro nel team.', 'eto'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php _e('Nome', 'eto'); ?></th>
                                <th scope="col"><?php _e('Email', 'eto'); ?></th>
                                <th scope="col"><?php _e('Ruolo', 'eto'); ?></th>
                                <th scope="col"><?php _e('Data Iscrizione', 'eto'); ?></th>
                                <th scope="col"><?php _e('Azioni', 'eto'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member) : ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $member['user_id']); ?>">
                                            <?php echo esc_html($member['display_name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo esc_html($member['user_email']); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $roles = [
                                            'captain' => __('Capitano', 'eto'),
                                            'manager' => __('Manager', 'eto'),
                                            'coach' => __('Coach', 'eto'),
                                            'member' => __('Membro', 'eto'),
                                            'substitute' => __('Sostituto', 'eto')
                                        ];
                                        
                                        $role = isset($member['role']) ? $member['role'] : 'member';
                                        $role_label = isset($roles[$role]) ? $roles[$role] : $role;
                                        
                                        echo esc_html($role_label);
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo isset($member['joined_date']) ? date_i18n(get_option('date_format'), strtotime($member['joined_date'])) : '-'; ?>
                                    </td>
                                    <td>
                                        <?php if ($member['role'] !== 'captain') : ?>
                                            <button type="button" class="button button-small promote-member" data-user-id="<?php echo $member['user_id']; ?>">
                                                <?php _e('Promuovi a Capitano', 'eto'); ?>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="button button-small remove-member" data-user-id="<?php echo $member['user_id']; ?>" <?php echo $member['role'] === 'captain' ? 'disabled' : ''; ?>>
                                            <?php _e('Rimuovi', 'eto'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="add-member-section" style="margin-top: 15px;">
                <h3><?php _e('Aggiungi Membro', 'eto'); ?></h3>
                
                <div class="add-member-form">
                    <select id="user-select" style="width: 300px;">
                        <option value=""><?php _e('Seleziona un utente', 'eto'); ?></option>
                        <?php
                        // Ottieni tutti gli utenti che non sono già membri
                        $users = get_users();
                        $member_ids = array_column($members, 'user_id');
                        
                        foreach ($users as $user) {
                            if (!in_array($user->ID, $member_ids)) {
                                echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</option>';
                            }
                        }
                        ?>
                    </select>
                    
                    <select id="role-select">
                        <option value="member"><?php _e('Membro', 'eto'); ?></option>
                        <option value="manager"><?php _e('Manager', 'eto'); ?></option>
                        <option value="coach"><?php _e('Coach', 'eto'); ?></option>
                        <option value="substitute"><?php _e('Sostituto', 'eto'); ?></option>
                    </select>
                    
                    <button type="button" class="button" id="add-member-button"><?php _e('Aggiungi Membro', 'eto'); ?></button>
                </div>
            </div>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Tornei', 'eto'); ?></h2>
            
            <div id="team-tournaments">
                <?php
                $tournaments = $team->get_tournaments();
                if (empty($tournaments)) :
                ?>
                    <p><?php _e('Nessun torneo a cui il team partecipa.', 'eto'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php _e('Nome', 'eto'); ?></th>
                                <th scope="col"><?php _e('Gioco', 'eto'); ?></th>
                                <th scope="col"><?php _e('Date', 'eto'); ?></th>
                                <th scope="col"><?php _e('Stato', 'eto'); ?></th>
                                <th scope="col"><?php _e('Azioni', 'eto'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tournaments as $tournament) : ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=eto-edit-tournament&id=' . $tournament['id']); ?>">
                                            <?php echo esc_html($tournament['name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $games = $this->get_available_games();
                                        echo isset($games[$tournament['game']]) ? esc_html($games[$tournament['game']]) : esc_html($tournament['game']);
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        echo sprintf(
                                            __('Inizio: %s<br>Fine: %s', 'eto'),
                                            date_i18n(get_option('date_format'), strtotime($tournament['start_date'])),
                                            date_i18n(get_option('date_format'), strtotime($tournament['end_date']))
                                        );
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_labels = [
                                            'pending' => __('In attesa', 'eto'),
                                            'active' => __('Attivo', 'eto'),
                                            'completed' => __('Completato', 'eto'),
                                            'cancelled' => __('Annullato', 'eto')
                                        ];
                                        
                                        $status = isset($tournament['status']) ? $tournament['status'] : 'pending';
                                        $label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
                                        
                                        echo esc_html($label);
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=eto-edit-tournament&id=' . $tournament['id']); ?>" class="button button-small">
                                            <?php _e('Visualizza', 'eto'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Informazioni di Contatto', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="team-email"><?php _e('Email', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="team-email" name="email" class="regular-text" value="<?php echo esc_attr($team->get('email')); ?>">
                        <p class="description"><?php _e('Indirizzo email di contatto del team.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="team-website"><?php _e('Sito Web', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="team-website" name="website" class="regular-text" value="<?php echo esc_url($team->get('website')); ?>">
                        <p class="description"><?php _e('Sito web del team.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="team-social-media"><?php _e('Social Media', 'eto'); ?></label>
                    </th>
                    <td>
                        <?php
                        $social_media = $team->get('social_media');
                        if (!is_array($social_media)) {
                            $social_media = [];
                        }
                        ?>
                        
                        <div class="social-media-fields">
                            <div class="social-media-field">
                                <label for="team-twitter"><?php _e('Twitter', 'eto'); ?></label>
                                <input type="url" id="team-twitter" name="social_media[twitter]" class="regular-text" placeholder="https://twitter.com/username" value="<?php echo isset($social_media['twitter']) ? esc_url($social_media['twitter']) : ''; ?>">
                            </div>
                            
                            <div class="social-media-field">
                                <label for="team-facebook"><?php _e('Facebook', 'eto'); ?></label>
                                <input type="url" id="team-facebook" name="social_media[facebook]" class="regular-text" placeholder="https://facebook.com/pagename" value="<?php echo isset($social_media['facebook']) ? esc_url($social_media['facebook']) : ''; ?>">
                            </div>
                            
                            <div class="social-media-field">
                                <label for="team-instagram"><?php _e('Instagram', 'eto'); ?></label>
                                <input type="url" id="team-instagram" name="social_media[instagram]" class="regular-text" placeholder="https://instagram.com/username" value="<?php echo isset($social_media['instagram']) ? esc_url($social_media['instagram']) : ''; ?>">
                            </div>
                            
                            <div class="social-media-field">
                                <label for="team-twitch"><?php _e('Twitch', 'eto'); ?></label>
                                <input type="url" id="team-twitch" name="social_media[twitch]" class="regular-text" placeholder="https://twitch.tv/username" value="<?php echo isset($social_media['twitch']) ? esc_url($social_media['twitch']) : ''; ?>">
                            </div>
                            
                            <div class="social-media-field">
                                <label for="team-youtube"><?php _e('YouTube', 'eto'); ?></label>
                                <input type="url" id="team-youtube" name="social_media[youtube]" class="regular-text" placeholder="https://youtube.com/channel/channelid" value="<?php echo isset($social_media['youtube']) ? esc_url($social_media['youtube']) : ''; ?>">
                            </div>
                            
                            <div class="social-media-field">
                                <label for="team-discord"><?php _e('Discord', 'eto'); ?></label>
                                <input type="url" id="team-discord" name="social_media[discord]" class="regular-text" placeholder="https://discord.gg/invitecode" value="<?php echo isset($social_media['discord']) ? esc_url($social_media['discord']) : ''; ?>">
                            </div>
                        </div>
                        <p class="description"><?php _e('Collegamenti ai profili social media del team.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="hidden" name="action" value="eto_update_team">
            <input type="hidden" name="team_id" value="<?php echo $team->get('id'); ?>">
            <input type="hidden" name="eto_nonce" value="<?php echo wp_create_nonce('eto_update_team'); ?>">
            <button type="submit" class="button button-primary"><?php _e('Aggiorna Team', 'eto'); ?></button>
            <a href="<?php echo admin_url('admin.php?page=eto-teams'); ?>" class="button"><?php _e('Torna alla Lista', 'eto'); ?></a>
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
    $('#eto-edit-team-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validazione
        var name = $('#team-name').val();
        var game = $('#team-game').val();
        
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
                $('#eto-messages').html('<div class="notice notice-info"><p><?php _e('Aggiornamento del team in corso...', 'eto'); ?></p></div>');
            },
            success: function(response) {
                if (response.success) {
                    $('#eto-messages').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
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
    
    // Gestione aggiunta membro
    $('#add-member-button').on('click', function() {
        var userId = $('#user-select').val();
        var role = $('#role-select').val();
        
        if (!userId) {
            alert('<?php _e('Seleziona un utente da aggiungere.', 'eto'); ?>');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eto_add_team_member',
                team_id: <?php echo $team->get('id'); ?>,
                user_id: userId,
                role: role,
                eto_nonce: '<?php echo wp_create_nonce('eto_add_team_member'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Si è verificato un errore durante l\'elaborazione della richiesta.', 'eto'); ?>');
            }
        });
    });
    
    // Gestione rimozione membro
    $('.remove-member').on('click', function() {
        var userId = $(this).data('user-id');
        
        if (confirm('<?php _e('Sei sicuro di voler rimuovere questo membro dal team?', 'eto'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eto_remove_team_member',
                    team_id: <?php echo $team->get('id'); ?>,
                    user_id: userId,
                    eto_nonce: '<?php echo wp_create_nonce('eto_remove_team_member'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('<?php _e('Si è verificato un errore durante l\'elaborazione della richiesta.', 'eto'); ?>');
                }
            });
        }
    });
    
    // Gestione promozione a capitano
    $('.promote-member').on('click', function() {
        var userId = $(this).data('user-id');
        
        if (confirm('<?php _e('Sei sicuro di voler promuovere questo membro a capitano? Il capitano attuale diventerà un membro normale.', 'eto'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eto_promote_team_member',
                    team_id: <?php echo $team->get('id'); ?>,
                    user_id: userId,
                    eto_nonce: '<?php echo wp_create_nonce('eto_promote_team_member'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('<?php _e('Si è verificato un errore durante l\'elaborazione della richiesta.', 'eto'); ?>');
                }
            });
        }
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

.add-member-form {
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

#user-select, #role-select {
    margin-right: 10px;
}
</style>
