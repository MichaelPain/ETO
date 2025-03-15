<?php
/**
 * Template per la visualizzazione del form di check-in per tornei individuali
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

<div class="eto-checkin-form eto-checkin-individual">
    <h3><?php _e('Check-in Torneo', 'eto'); ?></h3>
    
    <p><?php _e('Conferma la tua partecipazione al torneo effettuando il check-in.', 'eto'); ?></p>
    
    <?php if (!is_user_logged_in()): ?>
        <div class="eto-login-required">
            <p><?php _e('Devi effettuare il login per completare il check-in.', 'eto'); ?></p>
            <p><a href="<?php echo wp_login_url(get_permalink()); ?>" class="button"><?php _e('Accedi', 'eto'); ?></a></p>
        </div>
    <?php else: ?>
        <form id="eto-individual-checkin-form" method="post">
            <?php wp_nonce_field('eto-checkin-nonce', 'nonce'); ?>
            <input type="hidden" name="tournament_id" value="<?php echo esc_attr($tournament_id); ?>">
            
            <div class="eto-form-row">
                <button type="submit" class="button eto-button-primary" id="eto-checkin-submit">
                    <?php _e('Conferma Check-in', 'eto'); ?>
                </button>
            </div>
            
            <div class="eto-form-message"></div>
        </form>
        
        <script>
        jQuery(document).ready(function($) {
            $('#eto-individual-checkin-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $message = $form.find('.eto-form-message');
                var $submit = $form.find('#eto-checkin-submit');
                
                $submit.prop('disabled', true).text('<?php _e('Elaborazione...', 'eto'); ?>');
                $message.html('').removeClass('eto-message-error eto-message-success');
                
                $.ajax({
                    url: eto_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'eto_checkin_individual',
                        nonce: $form.find('[name="nonce"]').val(),
                        tournament_id: $form.find('[name="tournament_id"]').val()
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
</div>
