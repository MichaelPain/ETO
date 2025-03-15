<?php
/**
 * Template per la visualizzazione del profilo utente
 *
 * @package ETO
 * @since 2.5.3
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

// Variabili disponibili:
// $current_user - Oggetto utente corrente
// $riot_id - ID Riot Games dell'utente
// $discord_tag - Tag Discord dell'utente
// $nationality - Nazionalità dell'utente
// $teams - Array di team dell'utente
// $tournaments - Array di tornei a cui l'utente ha partecipato
?>

<div class="eto-profile-container">
    <div class="eto-profile-header">
        <h2><?php _e('Il tuo profilo', 'eto'); ?></h2>
        <div class="eto-profile-avatar">
            <?php echo get_avatar($current_user->ID, 96); ?>
        </div>
        <div class="eto-profile-info">
            <h3><?php echo esc_html($current_user->display_name); ?></h3>
            <p class="eto-profile-username"><?php echo esc_html($current_user->user_login); ?></p>
            <p class="eto-profile-email"><?php echo esc_html($current_user->user_email); ?></p>
        </div>
    </div>
    
    <div class="eto-profile-details">
        <h3><?php _e('Dettagli giocatore', 'eto'); ?></h3>
        
        <div class="eto-profile-fields">
            <?php if (!empty($riot_id)): ?>
                <div class="eto-profile-field">
                    <span class="eto-field-label"><?php _e('Riot ID:', 'eto'); ?></span>
                    <span class="eto-field-value"><?php echo esc_html($riot_id); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($discord_tag)): ?>
                <div class="eto-profile-field">
                    <span class="eto-field-label"><?php _e('Discord:', 'eto'); ?></span>
                    <span class="eto-field-value"><?php echo esc_html($discord_tag); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($nationality)): ?>
                <div class="eto-profile-field">
                    <span class="eto-field-label"><?php _e('Nazionalità:', 'eto'); ?></span>
                    <span class="eto-field-value"><?php echo esc_html($nationality); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="eto-profile-actions">
            <a href="<?php echo esc_url(add_query_arg('action', 'edit_profile')); ?>" class="button eto-button-secondary">
                <?php _e('Modifica profilo', 'eto'); ?>
            </a>
        </div>
    </div>
    
    <?php if (!empty($teams)): ?>
        <div class="eto-profile-teams">
            <h3><?php _e('I tuoi team', 'eto'); ?></h3>
            
            <div class="eto-team-list">
                <?php foreach ($teams as $team): ?>
                    <div class="eto-team-item">
                        <h4 class="eto-team-name"><?php echo esc_html($team->name); ?></h4>
                        
                        <?php if (!empty($team->description)): ?>
                            <p class="eto-team-description"><?php echo esc_html($team->description); ?></p>
                        <?php endif; ?>
                        
                        <div class="eto-team-meta">
                            <?php if ($team->captain_id == $current_user->ID): ?>
                                <span class="eto-team-role eto-role-captain"><?php _e('Capitano', 'eto'); ?></span>
                            <?php else: ?>
                                <span class="eto-team-role eto-role-member"><?php _e('Membro', 'eto'); ?></span>
                            <?php endif; ?>
                            
                            <span class="eto-team-members">
                                <?php printf(_n('%d membro', '%d membri', $team->member_count, 'eto'), $team->member_count); ?>
                            </span>
                        </div>
                        
                        <div class="eto-team-actions">
                            <a href="<?php echo esc_url(add_query_arg('team_id', $team->id, get_permalink(get_option('eto_team_page')))); ?>" class="button eto-button-secondary">
                                <?php _e('Visualizza team', 'eto'); ?>
                            </a>
                            
                            <?php if ($team->captain_id == $current_user->ID): ?>
                                <a href="<?php echo esc_url(add_query_arg(['team_id' => $team->id, 'action' => 'edit'], get_permalink(get_option('eto_team_page')))); ?>" class="button eto-button-secondary">
                                    <?php _e('Gestisci team', 'eto'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($teams) < get_option('eto_max_teams_per_user', 3)): ?>
                <div class="eto-create-team">
                    <a href="<?php echo esc_url(get_permalink(get_option('eto_create_team_page'))); ?>" class="button eto-button-primary">
                        <?php _e('Crea nuovo team', 'eto'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="eto-profile-no-teams">
            <h3><?php _e('I tuoi team', 'eto'); ?></h3>
            <p><?php _e('Non sei ancora membro di nessun team.', 'eto'); ?></p>
            <a href="<?php echo esc_url(get_permalink(get_option('eto_create_team_page'))); ?>" class="button eto-button-primary">
                <?php _e('Crea un team', 'eto'); ?>
            </a>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($tournaments)): ?>
        <div class="eto-profile-tournaments">
            <h3><?php _e('Storico tornei', 'eto'); ?></h3>
            
            <div class="eto-tournament-list">
                <?php foreach ($tournaments as $tournament): ?>
                    <div class="eto-tournament-item">
                        <h4 class="eto-tournament-name"><?php echo esc_html($tournament->name); ?></h4>
                        
                        <div class="eto-tournament-meta">
                            <span class="eto-tournament-date">
                                <?php echo date_i18n(get_option('date_format'), strtotime($tournament->date)); ?>
                            </span>
                            
                            <?php if (!empty($tournament->team_name)): ?>
                                <span class="eto-tournament-team">
                                    <?php printf(__('Team: %s', 'eto'), esc_html($tournament->team_name)); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($tournament->position)): ?>
                                <span class="eto-tournament-position">
                                    <?php printf(__('Posizione: %s', 'eto'), esc_html($tournament->position)); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="eto-tournament-actions">
                            <a href="<?php echo esc_url(get_permalink($tournament->id)); ?>" class="button eto-button-secondary">
                                <?php _e('Visualizza torneo', 'eto'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="eto-profile-no-tournaments">
            <h3><?php _e('Storico tornei', 'eto'); ?></h3>
            <p><?php _e('Non hai ancora partecipato a nessun torneo.', 'eto'); ?></p>
            <a href="<?php echo esc_url(get_permalink(get_option('eto_tournaments_page'))); ?>" class="button eto-button-primary">
                <?php _e('Esplora tornei', 'eto'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>
