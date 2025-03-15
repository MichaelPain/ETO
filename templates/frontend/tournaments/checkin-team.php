<?php
/**
 * Template per la visualizzazione del form di check-in per tornei a squadre
 *
 * @package ETO
 * @since 2.5.3
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

// Variabili disponibili:
// $tournament_id - ID del torneo
// $tournament - Oggetto torneo (ETO_Tournament_Model o WP_Post)
?>

<div class="eto-checkin-form eto-checkin-team">
    <h3><?php _e('Check-in Team', 'eto'); ?></h3>
    
    <p><?php _e('Conferma la partecipazione del tuo team al torneo effettuando il check-in.', 'eto'); ?></p>
    
    <?php if (!is_user_logged_in()): ?>
        <div class="eto-login-required">
            <p><?php _e('Devi effettuare il login per completare il check-in.', 'eto'); ?></p>
            <p><a href="<?php echo wp_login_url(get_permalink()); ?>" class="button"><?php _e('Accedi', 'eto'); ?></a></p>
        </div>
    <?php else: ?>
        <?php
        // Ottieni i team dell'utente
        global $wpdb;
        $user_id = get_current_user_id();
        $teams_table = $wpdb->prefix . 'eto_teams';
        $members_table = $wpdb->prefix . 'eto_team_members';
        $entries_table = $wpdb->prefix . 'eto_tournament_entries';
        
        // Ottieni i team di cui l'utente è capitano
        $captain_teams = $wpdb->get_results($wpdb->prepare(
            "SELECT t.* FROM $teams_table t
            INNER JOIN $entries_table e ON t.id = e.team_id
            WHERE t.captain_id = %d AND e.tournament_id = %d AND e.checked_in = 0",
            $user_id,
            $tournament_id
        ));
        
        // Ottieni i team di cui l'utente è membro
        $member_teams = $wpdb->get_results($wpdb->prepare(
            "SELECT t.* FROM $teams_table t
            INNER JOIN $members_table m ON t.id = m.team_id
            INNER JOIN $entries_table e ON t.id = e.team_id
            WHERE m.user_id = %d AND t.captain_id != %d AND e.tournament_id = %d AND e.checked_in = 0",
            $user_id,
            $user_id,
            $tournament_id
        ));
        
        if (empty($captain_teams) && empty($member_teams)):
        ?>
            <div class="eto-message eto-message-info">
                <p><?php _e('Non sei registrato a questo torneo con nessun team, o il check-in è già stato effettuato.', 'eto'); ?></p>
            </div>
        <?php else: ?>
            <?php if (!empty($captain_teams)): ?>
                <div class="eto-team-list eto-captain-teams">
                    <h4><?php _e('I tuoi team (capitano)', 'eto'); ?></h4>
                    
                    <?php foreach ($captain_teams as $team): ?>
                        <div class="eto-team-item">
                            <form class="eto-team-checkin-form" method="post">
                                <?php wp_nonce_field('eto-checkin-nonce', 'nonce'); ?>
                                <input type="hidden" name="tournament_id" value="<?php echo esc_attr($tournament_id); ?>">
                                <input type="hidden" name="team_id" value="<?php echo esc_attr($team->id); ?>">
                                
                                <div class="eto-team-info">
                                    <h5><?php echo esc_html($team->name); ?></h5>
                                    <?php if (!empty($team->description)): ?>
                                        <p class="eto-team-description"><?php echo esc_html($team->description); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="eto-form-row">
                                    <button type="submit" class="button eto-button-primary eto-checkin-submit">
                                        <?php _e('Conferma Check-in', 'eto'); ?>
                                    </button>
                                </div>
                                
                                <div class="eto-form-message"></div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($member_teams)): ?>
                <div class="eto-team-list eto-member-teams">
                    <h4><?php _e('Team di cui sei membro', 'eto'); ?></h4>
                    
                    <?php foreach ($member_teams as $team): ?>
                        <div class="eto-team-item">
                            <div class="eto-team-info">
                                <h5><?php echo esc_html($team->name); ?></h5>
                                <?php if (!empty($team->description)): ?>
                                    <p class="eto-team-description"><?php echo esc_html($team->description); ?></p>
                                <?php endif; ?>
                                <p class="eto-team-captain-note">
                                    <?php _e('Solo il capitano può effettuare il check-in per questo team.', 'eto'); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <script>
            jQuery(document).ready(function($) {
                $('.eto-team-checkin-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    var $form = $(this);
                    var $message = $form.find('.eto-form-message');
                    var $submit = $form.find('.eto-checkin-submit');
                    
                    $submit.prop('disabled', true).text('<?php _e('Elaborazione...', 'eto'); ?>');
                    $message.html('').removeClass('eto-message-error eto-message-success');
                    
                    $.ajax({
                        url: eto_data.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'eto_checkin_team',
                            nonce: $form.find('[name="nonce"]').val(),
                            tournament_id: $form.find('[name="tournament_id"]').val(),
                            team_id: $form.find('[name="team_id"]').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                $message.html('<p>' + response.data.message + '</p>').addClass('eto-message-success');
                                $form.find('button[type="submit"]').hide();
                            } else {
                                $message.html('<p>' + response.data.message + '</p>').addClass('eto-message-error');
                                $submit.prop('disabled', false).text('<?php _e('Conferma Check-in', 'eto'); ?>');
                            }
                        },
                        error: function() {
                            $message.html('<p><?php _e('Si è verificato un errore. Riprova più tardi.', 'eto'); ?></p>').addClass('eto-message-error');
                            $submit.prop('disabled', false).text('<?php _e('Conferma Check-in', 'eto'); ?>');
                        }
                    });
                });
            });
            </script>
        <?php endif; ?>
    <?php endif; ?>
</div>
