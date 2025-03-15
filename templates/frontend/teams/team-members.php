<?php
/**
 * Template per la visualizzazione dei membri di un team
 *
 * @package ETO
 * @since 2.5.3
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

// Variabili disponibili:
// $team - Oggetto team
// $members - Array di membri del team
// $is_captain - Booleano che indica se l'utente corrente è il capitano
// $current_user - Oggetto utente corrente
?>

<div class="eto-team-members-container">
    <h2><?php printf(__('Membri del team %s', 'eto'), esc_html($team->name)); ?></h2>
    
    <?php if (!empty($members)): ?>
        <div class="eto-team-members-list">
            <?php foreach ($members as $member): ?>
                <div class="eto-member-item <?php echo ($member->ID == $team->captain_id) ? 'eto-member-captain' : ''; ?>">
                    <div class="eto-member-avatar">
                        <?php echo get_avatar($member->ID, 64); ?>
                    </div>
                    
                    <div class="eto-member-info">
                        <h4 class="eto-member-name">
                            <?php echo esc_html($member->display_name); ?>
                            <?php if ($member->ID == $team->captain_id): ?>
                                <span class="eto-captain-badge"><?php _e('Capitano', 'eto'); ?></span>
                            <?php endif; ?>
                        </h4>
                        
                        <?php
                        // Ottieni informazioni aggiuntive sul giocatore
                        $riot_id = get_user_meta($member->ID, 'eto_riot_id', true);
                        $discord_tag = get_user_meta($member->ID, 'eto_discord_tag', true);
                        ?>
                        
                        <div class="eto-member-details">
                            <?php if (!empty($riot_id)): ?>
                                <div class="eto-member-detail">
                                    <span class="eto-detail-label"><?php _e('Riot ID:', 'eto'); ?></span>
                                    <span class="eto-detail-value"><?php echo esc_html($riot_id); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($discord_tag)): ?>
                                <div class="eto-member-detail">
                                    <span class="eto-detail-label"><?php _e('Discord:', 'eto'); ?></span>
                                    <span class="eto-detail-value"><?php echo esc_html($discord_tag); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="eto-member-detail">
                                <span class="eto-detail-label"><?php _e('Membro dal:', 'eto'); ?></span>
                                <span class="eto-detail-value">
                                    <?php 
                                    $join_date = get_user_meta($member->ID, 'eto_team_' . $team->id . '_join_date', true);
                                    echo !empty($join_date) ? date_i18n(get_option('date_format'), strtotime($join_date)) : __('Data sconosciuta', 'eto');
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($is_captain && $member->ID != $current_user->ID): ?>
                        <div class="eto-member-actions">
                            <?php if ($member->ID != $team->captain_id): ?>
                                <form method="post" class="eto-promote-member-form">
                                    <?php wp_nonce_field('eto_team_action', 'eto_team_nonce'); ?>
                                    <input type="hidden" name="action" value="promote_captain">
                                    <input type="hidden" name="member_id" value="<?php echo $member->ID; ?>">
                                    <input type="hidden" name="team_id" value="<?php echo $team->id; ?>">
                                    <button type="submit" class="button eto-button-secondary eto-promote-button" onclick="return confirm('<?php esc_attr_e('Sei sicuro di voler nominare questo membro come nuovo capitano? Perderai i privilegi da capitano.', 'eto'); ?>')">
                                        <?php _e('Nomina capitano', 'eto'); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="post" class="eto-remove-member-form">
                                <?php wp_nonce_field('eto_team_action', 'eto_team_nonce'); ?>
                                <input type="hidden" name="action" value="remove_member">
                                <input type="hidden" name="member_id" value="<?php echo $member->ID; ?>">
                                <input type="hidden" name="team_id" value="<?php echo $team->id; ?>">
                                <button type="submit" class="button eto-button-danger eto-remove-button" onclick="return confirm('<?php esc_attr_e('Sei sicuro di voler rimuovere questo membro dal team?', 'eto'); ?>')">
                                    <?php _e('Rimuovi', 'eto'); ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($is_captain): ?>
            <div class="eto-invite-member">
                <h3><?php _e('Invita nuovi membri', 'eto'); ?></h3>
                
                <form method="post" class="eto-invite-form">
                    <?php wp_nonce_field('eto_team_action', 'eto_team_nonce'); ?>
                    <input type="hidden" name="action" value="invite_member">
                    <input type="hidden" name="team_id" value="<?php echo $team->id; ?>">
                    
                    <div class="eto-form-row">
                        <label for="invite_username"><?php _e('Nome utente o email:', 'eto'); ?></label>
                        <input type="text" id="invite_username" name="invite_username" required>
                    </div>
                    
                    <div class="eto-form-row">
                        <button type="submit" class="button eto-button-primary"><?php _e('Invia invito', 'eto'); ?></button>
                    </div>
                </form>
                
                <?php
                // Mostra gli inviti pendenti
                global $wpdb;
                $invites_table = $wpdb->prefix . 'eto_team_invites';
                $pending_invites = $wpdb->get_results($wpdb->prepare(
                    "SELECT i.*, u.display_name, u.user_email 
                    FROM $invites_table i 
                    LEFT JOIN {$wpdb->users} u ON i.user_id = u.ID 
                    WHERE i.team_id = %d AND i.status = 'pending'",
                    $team->id
                ));
                
                if (!empty($pending_invites)):
                ?>
                    <div class="eto-pending-invites">
                        <h4><?php _e('Inviti pendenti', 'eto'); ?></h4>
                        
                        <ul class="eto-invites-list">
                            <?php foreach ($pending_invites as $invite): ?>
                                <li class="eto-invite-item">
                                    <?php echo esc_html($invite->display_name); ?> (<?php echo esc_html($invite->user_email); ?>)
                                    <span class="eto-invite-date">
                                        <?php printf(__('Inviato il: %s', 'eto'), date_i18n(get_option('date_format'), strtotime($invite->created_at))); ?>
                                    </span>
                                    
                                    <form method="post" class="eto-cancel-invite-form">
                                        <?php wp_nonce_field('eto_team_action', 'eto_team_nonce'); ?>
                                        <input type="hidden" name="action" value="cancel_invite">
                                        <input type="hidden" name="invite_id" value="<?php echo $invite->id; ?>">
                                        <input type="hidden" name="team_id" value="<?php echo $team->id; ?>">
                                        <button type="submit" class="button eto-button-small eto-button-danger">
                                            <?php _e('Annulla', 'eto'); ?>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="eto-no-members">
            <p><?php _e('Questo team non ha ancora membri.', 'eto'); ?></p>
            
            <?php if ($is_captain): ?>
                <p>
                    <a href="<?php echo esc_url(add_query_arg('action', 'invite', get_permalink())); ?>" class="button eto-button-primary">
                        <?php _e('Invita membri', 'eto'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($is_captain): ?>
        <div class="eto-team-management">
            <h3><?php _e('Gestione team', 'eto'); ?></h3>
            
            <div class="eto-team-actions">
                <a href="<?php echo esc_url(add_query_arg(['team_id' => $team->id, 'action' => 'edit'], get_permalink())); ?>" class="button eto-button-secondary">
                    <?php _e('Modifica informazioni team', 'eto'); ?>
                </a>
                
                <form method="post" class="eto-leave-team-form">
                    <?php wp_nonce_field('eto_team_action', 'eto_team_nonce'); ?>
                    <input type="hidden" name="action" value="leave_team">
                    <input type="hidden" name="team_id" value="<?php echo $team->id; ?>">
                    <button type="submit" class="button eto-button-danger" onclick="return confirm('<?php esc_attr_e('Sei sicuro di voler lasciare il team? Dovrai nominare un nuovo capitano prima di poter procedere.', 'eto'); ?>')">
                        <?php _e('Lascia team', 'eto'); ?>
                    </button>
                </form>
                
                <?php
                // Verifica se il team è iscritto a tornei attivi
                global $wpdb;
                $entries_table = $wpdb->prefix . 'eto_tournament_entries';
                $tournaments_table = $wpdb->prefix . 'eto_tournaments';
                
                $active_tournaments = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $entries_table e
                    JOIN $tournaments_table t ON e.tournament_id = t.id
                    WHERE e.team_id = %d AND t.status IN ('registration', 'checkin', 'active')",
                    $team->id
                ));
                
                if ($active_tournaments == 0):
                ?>
                    <form method="post" class="eto-delete-team-form">
                        <?php wp_nonce_field('eto_team_action', 'eto_team_nonce'); ?>
                        <input type="hidden" name="action" value="delete_team">
                        <input type="hidden" name="team_id" value="<?php echo $team->id; ?>">
                        <button type="submit" class="button eto-button-danger" onclick="return confirm('<?php esc_attr_e('Sei sicuro di voler eliminare definitivamente questo team? Questa azione non può essere annullata.', 'eto'); ?>')">
                            <?php _e('Elimina team', 'eto'); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif (in_array($current_user->ID, array_column($members, 'ID'))): ?>
        <div class="eto-member-actions">
            <form method="post" class="eto-leave-team-form">
                <?php wp_nonce_field('eto_team_action', 'eto_team_nonce'); ?>
                <input type="hidden" name="action" value="leave_team">
                <input type="hidden" name="team_id" value="<?php echo $team->id; ?>">
                <button type="submit" class="button eto-button-danger" onclick="return confirm('<?php esc_attr_e('Sei sicuro di voler lasciare il team?', 'eto'); ?>')">
                    <?php _e('Lascia team', 'eto'); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
