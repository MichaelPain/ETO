<?php
/**
 * Vista per l'aggiunta di un nuovo torneo
 * 
 * @package ETO
 * @subpackage Views
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php _e('Aggiungi Nuovo Torneo', 'eto'); ?></h1>
    
    <div id="eto-messages"></div>
    
    <form id="eto-add-tournament-form" class="eto-form">
        <div class="eto-form-section">
            <h2><?php _e('Informazioni Generali', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="tournament-name"><?php _e('Nome del Torneo', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="tournament-name" name="name" class="regular-text" required>
                        <p class="description"><?php _e('Il nome del torneo che sarà visibile agli utenti.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-description"><?php _e('Descrizione', 'eto'); ?></label>
                    </th>
                    <td>
                        <textarea id="tournament-description" name="description" rows="5" cols="50" class="large-text"></textarea>
                        <p class="description"><?php _e('Una descrizione dettagliata del torneo, regole, premi, ecc.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-game"><?php _e('Gioco', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select id="tournament-game" name="game" required>
                            <option value=""><?php _e('Seleziona un gioco', 'eto'); ?></option>
                            <?php foreach ($games as $game_id => $game_name) : ?>
                                <option value="<?php echo esc_attr($game_id); ?>"><?php echo esc_html($game_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Il gioco per cui si svolge il torneo.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-format"><?php _e('Formato', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select id="tournament-format" name="format" required>
                            <option value=""><?php _e('Seleziona un formato', 'eto'); ?></option>
                            <?php foreach ($formats as $format_id => $format_name) : ?>
                                <option value="<?php echo esc_attr($format_id); ?>"><?php echo esc_html($format_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Il formato del torneo determina come saranno organizzati i match.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Date e Orari', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="tournament-start-date"><?php _e('Data di Inizio', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="datetime-local" id="tournament-start-date" name="start_date" class="regular-text" required>
                        <p class="description"><?php _e('La data e l\'ora di inizio del torneo.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-end-date"><?php _e('Data di Fine', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="datetime-local" id="tournament-end-date" name="end_date" class="regular-text" required>
                        <p class="description"><?php _e('La data e l\'ora di fine del torneo.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-registration-start"><?php _e('Inizio Registrazioni', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="datetime-local" id="tournament-registration-start" name="registration_start" class="regular-text">
                        <p class="description"><?php _e('La data e l\'ora di inizio delle registrazioni al torneo.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-registration-end"><?php _e('Fine Registrazioni', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="datetime-local" id="tournament-registration-end" name="registration_end" class="regular-text">
                        <p class="description"><?php _e('La data e l\'ora di fine delle registrazioni al torneo.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Impostazioni Team', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="tournament-min-teams"><?php _e('Numero Minimo di Team', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="tournament-min-teams" name="min_teams" class="small-text" min="2" value="2">
                        <p class="description"><?php _e('Il numero minimo di team necessari per avviare il torneo.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-max-teams"><?php _e('Numero Massimo di Team', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="tournament-max-teams" name="max_teams" class="small-text" min="2" value="16">
                        <p class="description"><?php _e('Il numero massimo di team che possono partecipare al torneo.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Impostazioni Avanzate', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="tournament-rules"><?php _e('Regolamento', 'eto'); ?></label>
                    </th>
                    <td>
                        <textarea id="tournament-rules" name="rules" rows="5" cols="50" class="large-text"></textarea>
                        <p class="description"><?php _e('Regole specifiche del torneo. Supporta il formato HTML.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-prizes"><?php _e('Premi', 'eto'); ?></label>
                    </th>
                    <td>
                        <textarea id="tournament-prizes" name="prizes" rows="5" cols="50" class="large-text"></textarea>
                        <p class="description"><?php _e('Descrizione dei premi del torneo. Supporta il formato HTML.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-featured-image"><?php _e('Immagine in Evidenza', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="tournament-featured-image" name="featured_image" class="regular-text">
                        <button type="button" class="button" id="upload-image-button"><?php _e('Carica Immagine', 'eto'); ?></button>
                        <div id="featured-image-preview"></div>
                        <p class="description"><?php _e('URL dell\'immagine in evidenza del torneo.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="hidden" name="action" value="eto_create_tournament">
            <input type="hidden" name="eto_nonce" value="<?php echo wp_create_nonce('eto_create_tournament'); ?>">
            <button type="submit" class="button button-primary"><?php _e('Crea Torneo', 'eto'); ?></button>
            <a href="<?php echo admin_url('admin.php?page=eto-tournaments'); ?>" class="button"><?php _e('Annulla', 'eto'); ?></a>
        </p>
    </form>
</div>

<script type="text/javascript">
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
    
    // Validazione date
    function validateDates() {
        var startDate = $('#tournament-start-date').val();
        var endDate = $('#tournament-end-date').val();
        var regStart = $('#tournament-registration-start').val();
        var regEnd = $('#tournament-registration-end').val();
        
        var errors = [];
        
        if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
            errors.push('<?php _e('La data di fine deve essere successiva alla data di inizio.', 'eto'); ?>');
        }
        
        if (regStart && regEnd && new Date(regStart) > new Date(regEnd)) {
            errors.push('<?php _e('La data di fine registrazione deve essere successiva alla data di inizio registrazione.', 'eto'); ?>');
        }
        
        if (regEnd && startDate && new Date(regEnd) > new Date(startDate)) {
            errors.push('<?php _e('La data di fine registrazione deve essere precedente alla data di inizio torneo.', 'eto'); ?>');
        }
        
        return errors;
    }
    
    // Validazione numeri team
    function validateTeams() {
        var minTeams = parseInt($('#tournament-min-teams').val(), 10);
        var maxTeams = parseInt($('#tournament-max-teams').val(), 10);
        
        var errors = [];
        
        if (minTeams < 2) {
            errors.push('<?php _e('Il numero minimo di team deve essere almeno 2.', 'eto'); ?>');
        }
        
        if (maxTeams < minTeams) {
            errors.push('<?php _e('Il numero massimo di team deve essere maggiore o uguale al numero minimo.', 'eto'); ?>');
        }
        
        return errors;
    }
    
    // Gestione invio form
    $('#eto-add-tournament-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validazione
        var dateErrors = validateDates();
        var teamErrors = validateTeams();
        var errors = dateErrors.concat(teamErrors);
        
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
                $('#eto-messages').html('<div class="notice notice-info"><p><?php _e('Creazione del torneo in corso...', 'eto'); ?></p></div>');
            },
            success: function(response) {
                if (response.success) {
                    $('#eto-messages').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    
                    // Reindirizza alla pagina di modifica
                    setTimeout(function() {
                        window.location.href = '<?php echo admin_url('admin.php?page=eto-edit-tournament&id='); ?>' + response.data.tournament_id;
                    }, 1000);
                } else {
                    var errorHtml = '<div class="notice notice-error"><p>' + response.data.message + '</p>';
                    
                    if (response.data.errors) {
                        errorHtml += '<ul>';
                        
                        $.each(response.data.errors, function(field, error) {
                            errorHtml += '<li>' + error + '</li>';
                            $('#tournament-' + field).addClass('eto-field-error');
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

#featured-image-preview {
    margin-top: 10px;
}

#featured-image-preview img {
    max-width: 300px;
    border: 1px solid #ddd;
    padding: 5px;
    background: #f9f9f9;
}
</style>
