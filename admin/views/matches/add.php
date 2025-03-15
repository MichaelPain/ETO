<?php
/**
 * Vista per l'aggiunta di un nuovo match
 * 
 * @package ETO
 * @subpackage Views
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php _e('Aggiungi Nuovo Match', 'eto'); ?></h1>
    
    <div id="eto-messages"></div>
    
    <form id="eto-add-match-form" class="eto-form">
        <div class="eto-form-section">
            <h2><?php _e('Informazioni Generali', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="match-tournament"><?php _e('Torneo', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select id="match-tournament" name="tournament_id" required>
                            <option value=""><?php _e('Seleziona un torneo', 'eto'); ?></option>
                            <?php foreach ($tournaments as $tournament) : ?>
                                <option value="<?php echo esc_attr($tournament['id']); ?>"><?php echo esc_html($tournament['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Il torneo a cui appartiene il match.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="match-round"><?php _e('Round', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="match-round" name="round" class="small-text" min="1" value="1" required>
                        <p class="description"><?php _e('Il round del torneo a cui appartiene il match.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="match-number"><?php _e('Numero Match', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="match-number" name="match_number" class="small-text" min="1" value="1" required>
                        <p class="description"><?php _e('Il numero progressivo del match all\'interno del round.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Team', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="match-team1"><?php _e('Team 1', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select id="match-team1" name="team1_id" required>
                            <option value=""><?php _e('Seleziona un team', 'eto'); ?></option>
                            <!-- I team verranno caricati dinamicamente in base al torneo selezionato -->
                        </select>
                        <p class="description"><?php _e('Il primo team che partecipa al match.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="match-team2"><?php _e('Team 2', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select id="match-team2" name="team2_id" required>
                            <option value=""><?php _e('Seleziona un team', 'eto'); ?></option>
                            <!-- I team verranno caricati dinamicamente in base al torneo selezionato -->
                        </select>
                        <p class="description"><?php _e('Il secondo team che partecipa al match.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Pianificazione', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="match-date"><?php _e('Data e Ora', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="datetime-local" id="match-date" name="scheduled_date" class="regular-text" required>
                        <p class="description"><?php _e('La data e l\'ora in cui è pianificato il match.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="match-status"><?php _e('Stato', 'eto'); ?></label>
                    </th>
                    <td>
                        <select id="match-status" name="status">
                            <option value="pending"><?php _e('In attesa', 'eto'); ?></option>
                            <option value="in_progress"><?php _e('In corso', 'eto'); ?></option>
                            <option value="completed"><?php _e('Completato', 'eto'); ?></option>
                            <option value="cancelled"><?php _e('Annullato', 'eto'); ?></option>
                        </select>
                        <p class="description"><?php _e('Lo stato corrente del match.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-form-section match-result-section" style="display: none;">
            <h2><?php _e('Risultato', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="match-team1-score"><?php _e('Punteggio Team 1', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="match-team1-score" name="team1_score" class="small-text" min="0" value="0">
                        <p class="description"><?php _e('Il punteggio ottenuto dal primo team.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="match-team2-score"><?php _e('Punteggio Team 2', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="match-team2-score" name="team2_score" class="small-text" min="0" value="0">
                        <p class="description"><?php _e('Il punteggio ottenuto dal secondo team.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Informazioni Aggiuntive', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="match-notes"><?php _e('Note', 'eto'); ?></label>
                    </th>
                    <td>
                        <textarea id="match-notes" name="notes" rows="5" cols="50" class="large-text"></textarea>
                        <p class="description"><?php _e('Note aggiuntive sul match, come regole speciali o informazioni per gli spettatori.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="match-stream-url"><?php _e('URL Streaming', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="match-stream-url" name="stream_url" class="regular-text">
                        <p class="description"><?php _e('URL dello streaming del match, se disponibile.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="hidden" name="action" value="eto_create_match">
            <input type="hidden" name="eto_nonce" value="<?php echo wp_create_nonce('eto_create_match'); ?>">
            <button type="submit" class="button button-primary"><?php _e('Crea Match', 'eto'); ?></button>
            <a href="<?php echo admin_url('admin.php?page=eto-matches'); ?>" class="button"><?php _e('Annulla', 'eto'); ?></a>
        </p>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Gestione caricamento team in base al torneo selezionato
    $('#match-tournament').on('change', function() {
        var tournamentId = $(this).val();
        
        if (!tournamentId) {
            $('#match-team1, #match-team2').html('<option value=""><?php _e('Seleziona un team', 'eto'); ?></option>');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eto_get_tournament_teams',
                tournament_id: tournamentId,
                eto_nonce: '<?php echo wp_create_nonce('eto_get_tournament_teams'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var teams = response.data.teams;
                    var options = '<option value=""><?php _e('Seleziona un team', 'eto'); ?></option>';
                    
                    $.each(teams, function(index, team) {
                        options += '<option value="' + team.id + '">' + team.name + '</option>';
                    });
                    
                    $('#match-team1, #match-team2').html(options);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Si è verificato un errore durante l\'elaborazione della richiesta.', 'eto'); ?>');
            }
        });
    });
    
    // Gestione visualizzazione sezione risultato in base allo stato
    $('#match-status').on('change', function() {
        if ($(this).val() === 'completed') {
            $('.match-result-section').show();
        } else {
            $('.match-result-section').hide();
        }
    });
    
    // Validazione form
    function validateForm() {
        var tournamentId = $('#match-tournament').val();
        var team1Id = $('#match-team1').val();
        var team2Id = $('#match-team2').val();
        var scheduledDate = $('#match-date').val();
        var round = $('#match-round').val();
        var matchNumber = $('#match-number').val();
        
        var errors = [];
        
        if (!tournamentId) {
            errors.push('<?php _e('Il torneo è obbligatorio.', 'eto'); ?>');
            $('#match-tournament').addClass('eto-field-error');
        } else {
            $('#match-tournament').removeClass('eto-field-error');
        }
        
        if (!team1Id) {
            errors.push('<?php _e('Il team 1 è obbligatorio.', 'eto'); ?>');
            $('#match-team1').addClass('eto-field-error');
        } else {
            $('#match-team1').removeClass('eto-field-error');
        }
        
        if (!team2Id) {
            errors.push('<?php _e('Il team 2 è obbligatorio.', 'eto'); ?>');
            $('#match-team2').addClass('eto-field-error');
        } else {
            $('#match-team2').removeClass('eto-field-error');
        }
        
        if (team1Id && team2Id && team1Id === team2Id) {
            errors.push('<?php _e('I due team devono essere diversi.', 'eto'); ?>');
            $('#match-team1, #match-team2').addClass('eto-field-error');
        }
        
        if (!scheduledDate) {
            errors.push('<?php _e('La data e l\'ora sono obbligatorie.', 'eto'); ?>');
            $('#match-date').addClass('eto-field-error');
        } else {
            $('#match-date').removeClass('eto-field-error');
        }
        
        if (!round || round < 1) {
            errors.push('<?php _e('Il round deve essere un numero positivo.', 'eto'); ?>');
            $('#match-round').addClass('eto-field-error');
        } else {
            $('#match-round').removeClass('eto-field-error');
        }
        
        if (!matchNumber || matchNumber < 1) {
            errors.push('<?php _e('Il numero del match deve essere un numero positivo.', 'eto'); ?>');
            $('#match-number').addClass('eto-field-error');
        } else {
            $('#match-number').removeClass('eto-field-error');
        }
        
        return errors;
    }
    
    // Gestione invio form
    $('#eto-add-match-form').on('submit', function(e) {
        e.preventDefault();
        
        var errors = validateForm();
        
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
                $('#eto-messages').html('<div class="notice notice-info"><p><?php _e('Creazione del match in corso...', 'eto'); ?></p></div>');
            },
            success: function(response) {
                if (response.success) {
                    $('#eto-messages').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    
                    // Reindirizza alla pagina di modifica
                    setTimeout(function() {
                        window.location.href = '<?php echo admin_url('admin.php?page=eto-edit-match&id='); ?>' + response.data.match_id;
                    }, 1000);
                } else {
                    var errorHtml = '<div class="notice notice-error"><p>' + response.data.message + '</p>';
                    
                    if (response.data.errors) {
                        errorHtml += '<ul>';
                        
                        $.each(response.data.errors, function(field, error) {
                            errorHtml += '<li>' + error + '</li>';
                            $('#match-' + field.replace('_', '-')).addClass('eto-field-error');
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
</style>
