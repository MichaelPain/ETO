<?php
/**
 * Vista per la modifica di un torneo esistente
 * 
 * @package ETO
 * @subpackage Views
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php _e('Modifica Torneo', 'eto'); ?></h1>
    
    <div id="eto-messages"></div>
    
    <form id="eto-edit-tournament-form" class="eto-form">
        <div class="eto-form-section">
            <h2><?php _e('Informazioni Generali', 'eto'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="tournament-name"><?php _e('Nome del Torneo', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="tournament-name" name="name" class="regular-text" value="<?php echo esc_attr($tournament->get('name')); ?>" required>
                        <p class="description"><?php _e('Il nome del torneo che sarà visibile agli utenti.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-description"><?php _e('Descrizione', 'eto'); ?></label>
                    </th>
                    <td>
                        <textarea id="tournament-description" name="description" rows="5" cols="50" class="large-text"><?php echo esc_textarea($tournament->get('description')); ?></textarea>
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
                                <option value="<?php echo esc_attr($game_id); ?>" <?php selected($tournament->get('game'), $game_id); ?>><?php echo esc_html($game_name); ?></option>
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
                                <option value="<?php echo esc_attr($format_id); ?>" <?php selected($tournament->get('format'), $format_id); ?>><?php echo esc_html($format_name); ?></option>
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
                        <input type="datetime-local" id="tournament-start-date" name="start_date" class="regular-text" value="<?php echo date('Y-m-d\TH:i', strtotime($tournament->get('start_date'))); ?>" required>
                        <p class="description"><?php _e('La data e l\'ora di inizio del torneo.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-end-date"><?php _e('Data di Fine', 'eto'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="datetime-local" id="tournament-end-date" name="end_date" class="regular-text" value="<?php echo date('Y-m-d\TH:i', strtotime($tournament->get('end_date'))); ?>" required>
                        <p class="description"><?php _e('La data e l\'ora di fine del torneo.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-registration-start"><?php _e('Inizio Registrazioni', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="datetime-local" id="tournament-registration-start" name="registration_start" class="regular-text" value="<?php echo $tournament->get('registration_start') ? date('Y-m-d\TH:i', strtotime($tournament->get('registration_start'))) : ''; ?>">
                        <p class="description"><?php _e('La data e l\'ora di inizio delle registrazioni al torneo.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-registration-end"><?php _e('Fine Registrazioni', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="datetime-local" id="tournament-registration-end" name="registration_end" class="regular-text" value="<?php echo $tournament->get('registration_end') ? date('Y-m-d\TH:i', strtotime($tournament->get('registration_end'))) : ''; ?>">
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
                        <input type="number" id="tournament-min-teams" name="min_teams" class="small-text" min="2" value="<?php echo intval($tournament->get('min_teams')); ?>">
                        <p class="description"><?php _e('Il numero minimo di team necessari per avviare il torneo.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-max-teams"><?php _e('Numero Massimo di Team', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="tournament-max-teams" name="max_teams" class="small-text" min="2" value="<?php echo intval($tournament->get('max_teams')); ?>">
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
                        <textarea id="tournament-rules" name="rules" rows="5" cols="50" class="large-text"><?php echo esc_textarea($tournament->get('rules')); ?></textarea>
                        <p class="description"><?php _e('Regole specifiche del torneo. Supporta il formato HTML.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-prizes"><?php _e('Premi', 'eto'); ?></label>
                    </th>
                    <td>
                        <textarea id="tournament-prizes" name="prizes" rows="5" cols="50" class="large-text"><?php echo esc_textarea($tournament->get('prizes')); ?></textarea>
                        <p class="description"><?php _e('Descrizione dei premi del torneo. Supporta il formato HTML.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-featured-image"><?php _e('Immagine in Evidenza', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="tournament-featured-image" name="featured_image" class="regular-text" value="<?php echo esc_url($tournament->get('featured_image')); ?>">
                        <button type="button" class="button" id="upload-image-button"><?php _e('Carica Immagine', 'eto'); ?></button>
                        <div id="featured-image-preview">
                            <?php if ($tournament->get('featured_image')) : ?>
                                <img src="<?php echo esc_url($tournament->get('featured_image')); ?>" style="max-width: 300px; margin-top: 10px;">
                            <?php endif; ?>
                        </div>
                        <p class="description"><?php _e('URL dell\'immagine in evidenza del torneo.', 'eto'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tournament-status"><?php _e('Stato', 'eto'); ?></label>
                    </th>
                    <td>
                        <select id="tournament-status" name="status">
                            <option value="pending" <?php selected($tournament->get('status'), 'pending'); ?>><?php _e('In attesa', 'eto'); ?></option>
                            <option value="active" <?php selected($tournament->get('status'), 'active'); ?>><?php _e('Attivo', 'eto'); ?></option>
                            <option value="completed" <?php selected($tournament->get('status'), 'completed'); ?>><?php _e('Completato', 'eto'); ?></option>
                            <option value="cancelled" <?php selected($tournament->get('status'), 'cancelled'); ?>><?php _e('Annullato', 'eto'); ?></option>
                        </select>
                        <p class="description"><?php _e('Lo stato corrente del torneo.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Team Partecipanti', 'eto'); ?></h2>
            
            <div id="tournament-teams">
                <?php
                $teams = $tournament->get_teams();
                if (empty($teams)) :
                ?>
                    <p><?php _e('Nessun team partecipante.', 'eto'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php _e('Nome', 'eto'); ?></th>
                                <th scope="col"><?php _e('Capitano', 'eto'); ?></th>
                                <th scope="col"><?php _e('Membri', 'eto'); ?></th>
                                <th scope="col"><?php _e('Data Iscrizione', 'eto'); ?></th>
                                <th scope="col"><?php _e('Azioni', 'eto'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teams as $team) : ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=eto-edit-team&id=' . $team['id']); ?>">
                                            <?php echo esc_html($team['name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $captain = ETO_User_Model::get_by_id($team['captain_id']);
                                        echo $captain ? esc_html($captain->get('display_name')) : __('N/A', 'eto');
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $team_obj = ETO_Team_Model::get_by_id($team['id']);
                                        $members = $team_obj ? $team_obj->get_members() : [];
                                        echo count($members);
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($team['registration_date'])); ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small remove-team" data-team-id="<?php echo $team['id']; ?>">
                                            <?php _e('Rimuovi', 'eto'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="add-team-section" style="margin-top: 15px;">
                <h3><?php _e('Aggiungi Team', 'eto'); ?></h3>
                
                <select id="team-select" style="width: 300px;">
                    <option value=""><?php _e('Seleziona un team', 'eto'); ?></option>
                    <?php
                    // Ottieni tutti i team disponibili che non sono già iscritti
                    $all_teams = ETO_Team_Model::get_all(['game' => $tournament->get('game')]);
                    $tournament_team_ids = array_column($teams, 'id');
                    
                    foreach ($all_teams as $available_team) {
                        if (!in_array($available_team['id'], $tournament_team_ids)) {
                            echo '<option value="' . $available_team['id'] . '">' . esc_html($available_team['name']) . '</option>';
                        }
                    }
                    ?>
                </select>
                
                <button type="button" class="button" id="add-team-button"><?php _e('Aggiungi Team', 'eto'); ?></button>
            </div>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e('Match', 'eto'); ?></h2>
            
            <div id="tournament-matches">
                <?php
                $matches = $tournament->get_matches();
                if (empty($matches)) :
                ?>
                    <p><?php _e('Nessun match creato.', 'eto'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php _e('Round', 'eto'); ?></th>
                                <th scope="col"><?php _e('Match #', 'eto'); ?></th>
                                <th scope="col"><?php _e('Team 1', 'eto'); ?></th>
                                <th scope="col"><?php _e('Team 2', 'eto'); ?></th>
                                <th scope="col"><?php _e('Risultato', 'eto'); ?></th>
                                <th scope="col"><?php _e('Data', 'eto'); ?></th>
                                <th scope="col"><?php _e('Stato', 'eto'); ?></th>
                                <th scope="col"><?php _e('Azioni', 'eto'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matches as $match) : ?>
                                <tr>
                                    <td><?php echo intval($match['round']); ?></td>
                                    <td><?php echo intval($match['match_number']); ?></td>
                                    <td>
                                        <?php
                                        $team1 = ETO_Team_Model::get_by_id($match['team1_id']);
                                        echo $team1 ? esc_html($team1->get('name')) : __('TBD', 'eto');
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $team2 = ETO_Team_Model::get_by_id($match['team2_id']);
                                        echo $team2 ? esc_html($team2->get('name')) : __('TBD', 'eto');
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $match_obj = ETO_Match_Model::get_by_id($match['id']);
                                        $result = $match_obj ? $match_obj->get_result() : false;
                                        
                                        if ($result) {
                                            echo esc_html($result['team1_score'] . ' - ' . $result['team2_score']);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($match['scheduled_date'])); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_labels = [
                                            'pending' => __('In attesa', 'eto'),
                                            'in_progress' => __('In corso', 'eto'),
                                            'completed' => __('Completato', 'eto'),
                                            'cancelled' => __('Annullato', 'eto')
                                        ];
                                        
                                        $status = isset($match['status']) ? $match['status'] : 'pending';
                                        $label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
                                        
                                        echo esc_html($label);
                                        ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small edit-match" data-match-id="<?php echo $match['id']; ?>">
                                            <?php _e('Modifica', 'eto'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="generate-matches-section" style="margin-top: 15px;">
                <h3><?php _e('Generazione Match', 'eto'); ?></h3>
                
                <p><?php _e('Genera automaticamente i match per il torneo in base al formato selezionato.', 'eto'); ?></p>
                
                <button type="button" class="button" id="generate-matches-button" <?php echo empty($teams) || count($teams) < 2 ? 'disabled' : ''; ?>>
                    <?php _e('Genera Match', 'eto'); ?>
                </button>
                
                <p class="description">
                    <?php _e('Nota: La generazione dei match eliminerà tutti i match esistenti e ne creerà di nuovi.', 'eto'); ?>
                    <?php if (empty($teams) || count($teams) < 2) : ?>
                        <br>
                        <strong><?php _e('Devi avere almeno 2 team iscritti per generare i match.', 'eto'); ?></strong>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <p class="submit">
            <input type="hidden" name="action" value="eto_update_tournament">
            <input type="hidden" name="tournament_id" value="<?php echo $tournament->get('id'); ?>">
            <input type="hidden" name="eto_nonce" value="<?php echo wp_create_nonce('eto_update_tournament'); ?>">
            <button type="submit" class="button button-primary"><?php _e('Aggiorna Torneo', 'eto'); ?></button>
            <a href="<?php echo admin_url('admin.php?page=eto-tournaments'); ?>" class="button"><?php _e('Torna alla Lista', 'eto'); ?></a>
        </p>
    </form>
</div>

<!-- Modal per la modifica dei match -->
<div id="match-modal" class="eto-modal" style="display: none;">
    <div class="eto-modal-content">
        <span class="eto-modal-close">&times;</span>
        <h2><?php _e('Modifica Match', 'eto'); ?></h2>
        
        <form id="edit-match-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="match-team1"><?php _e('Team 1', 'eto'); ?></label>
                    </th>
                    <td>
                        <select id="match-team1" name="team1_id">
                            <option value=""><?php _e('Seleziona un team', 'eto'); ?></option>
                            <?php foreach ($teams as $team) : ?>
                                <option value="<?php echo $team['id']; ?>"><?php echo esc_html($team['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="match-team2"><?php _e('Team 2', 'eto'); ?></label>
                    </th>
                    <td>
                        <select id="match-team2" name="team2_id">
                            <option value=""><?php _e('Seleziona un team', 'eto'); ?></option>
                            <?php foreach ($teams as $team) : ?>
                                <option value="<?php echo $team['id']; ?>"><?php echo esc_html($team['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="match-date"><?php _e('Data e Ora', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="datetime-local" id="match-date" name="scheduled_date" class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="match-round"><?php _e('Round', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="match-round" name="round" class="small-text" min="1">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="match-number"><?php _e('Numero Match', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="match-number" name="match_number" class="small-text" min="1">
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
                    </td>
                </tr>
                
                <tr class="match-result-row" style="display: none;">
                    <th scope="row">
                        <label for="match-team1-score"><?php _e('Risultato', 'eto'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="match-team1-score" name="team1_score" class="small-text" min="0" style="width: 60px;"> -
                        <input type="number" id="match-team2-score" name="team2_score" class="small-text" min="0" style="width: 60px;">
                    </td>
                </tr>
            </table>
            
            <div class="submit">
                <input type="hidden" name="action" value="eto_update_match">
                <input type="hidden" name="match_id" id="match-id" value="">
                <input type="hidden" name="tournament_id" value="<?php echo $tournament->get('id'); ?>">
                <input type="hidden" name="eto_nonce" value="<?php echo wp_create_nonce('eto_update_match'); ?>">
                <button type="submit" class="button button-primary"><?php _e('Salva Match', 'eto'); ?></button>
                <button type="button" class="button eto-modal-cancel"><?php _e('Annulla', 'eto'); ?></button>
            </div>
        </form>
    </div>
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
    $('#eto-edit-tournament-form').on('submit', function(e) {
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
                $('#eto-messages').html('<div class="notice notice-info"><p><?php _e('Aggiornamento del torneo in corso...', 'eto'); ?></p></div>');
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
    
    // Gestione aggiunta team
    $('#add-team-button').on('click', function() {
        var teamId = $('#team-select').val();
        
        if (!teamId) {
            alert('<?php _e('Seleziona un team da aggiungere.', 'eto'); ?>');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eto_add_tournament_team',
                tournament_id: <?php echo $tournament->get('id'); ?>,
                team_id: teamId,
                eto_nonce: '<?php echo wp_create_nonce('eto_add_tournament_team'); ?>'
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
    
    // Gestione rimozione team
    $('.remove-team').on('click', function() {
        var teamId = $(this).data('team-id');
        
        if (confirm('<?php _e('Sei sicuro di voler rimuovere questo team dal torneo?', 'eto'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eto_remove_tournament_team',
                    tournament_id: <?php echo $tournament->get('id'); ?>,
                    team_id: teamId,
                    eto_nonce: '<?php echo wp_create_nonce('eto_remove_tournament_team'); ?>'
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
    
    // Gestione generazione match
    $('#generate-matches-button').on('click', function() {
        if (confirm('<?php _e('Sei sicuro di voler generare i match? Tutti i match esistenti saranno eliminati.', 'eto'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eto_generate_matches',
                    tournament_id: <?php echo $tournament->get('id'); ?>,
                    eto_nonce: '<?php echo wp_create_nonce('eto_generate_matches'); ?>'
                },
                beforeSend: function() {
                    $('#eto-messages').html('<div class="notice notice-info"><p><?php _e('Generazione dei match in corso...', 'eto'); ?></p></div>');
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                },
                success: function(response) {
                    if (response.success) {
                        $('#eto-messages').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        $('#eto-messages').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                },
                error: function() {
                    $('#eto-messages').html('<div class="notice notice-error"><p><?php _e('Si è verificato un errore durante l\'elaborazione della richiesta.', 'eto'); ?></p></div>');
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                }
            });
        }
    });
    
    // Gestione modal match
    $('.edit-match').on('click', function() {
        var matchId = $(this).data('match-id');
        
        // Carica i dati del match
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eto_load_match',
                match_id: matchId,
                eto_nonce: '<?php echo wp_create_nonce('eto_load_match'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var match = response.data.match;
                    
                    $('#match-id').val(match.id);
                    $('#match-team1').val(match.team1_id);
                    $('#match-team2').val(match.team2_id);
                    $('#match-date').val(match.scheduled_date.replace(' ', 'T'));
                    $('#match-round').val(match.round);
                    $('#match-number').val(match.match_number);
                    $('#match-status').val(match.status);
                    
                    if (match.status === 'completed') {
                        $('.match-result-row').show();
                        $('#match-team1-score').val(match.team1_score);
                        $('#match-team2-score').val(match.team2_score);
                    } else {
                        $('.match-result-row').hide();
                    }
                    
                    $('#match-modal').show();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Si è verificato un errore durante l\'elaborazione della richiesta.', 'eto'); ?>');
            }
        });
    });
    
    // Gestione chiusura modal
    $('.eto-modal-close, .eto-modal-cancel').on('click', function() {
        $('#match-modal').hide();
    });
    
    // Gestione stato match
    $('#match-status').on('change', function() {
        if ($(this).val() === 'completed') {
            $('.match-result-row').show();
        } else {
            $('.match-result-row').hide();
        }
    });
    
    // Gestione invio form match
    $('#edit-match-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#match-modal').hide();
                    $('#eto-messages').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Si è verificato un errore durante l\'elaborazione della richiesta.', 'eto'); ?>');
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

/* Modal */
.eto-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.eto-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 60%;
    max-width: 800px;
    box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
}

.eto-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.eto-modal-close:hover,
.eto-modal-close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}
</style>
