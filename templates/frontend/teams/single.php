<?php
/**
 * Template per la visualizzazione di un singolo team
 * 
 * @package ETO
 * @subpackage Frontend
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

get_header();
?>

<div class="eto-team-single">
    <div class="eto-container">
        <?php if (isset($team) && $team) : ?>
            <article class="eto-team">
                <header class="eto-team-header">
                    <div class="eto-team-header-content">
                        <h1 class="eto-team-title"><?php echo esc_html($team->get('name')); ?></h1>
                        
                        <div class="eto-team-meta">
                            <?php
                            $games = ETO_Tournament_Controller::get_available_games();
                            $game = $team->get('game');
                            $game_name = isset($games[$game]) ? $games[$game] : $game;
                            ?>
                            
                            <span class="eto-team-game"><?php echo esc_html($game_name); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($team->get('logo_url')) : ?>
                        <div class="eto-team-logo">
                            <img src="<?php echo esc_url($team->get('logo_url')); ?>" alt="<?php echo esc_attr($team->get('name')); ?>">
                        </div>
                    <?php endif; ?>
                </header>
                
                <div class="eto-team-content">
                    <?php if ($team->get('description')) : ?>
                        <div class="eto-team-description">
                            <h3><?php _e('Descrizione', 'eto'); ?></h3>
                            <div class="eto-content">
                                <?php echo wpautop(wp_kses_post($team->get('description'))); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="eto-team-details">
                        <div class="eto-team-contact">
                            <h3><?php _e('Contatti', 'eto'); ?></h3>
                            
                            <?php if ($team->get('email')) : ?>
                                <div class="eto-contact-item">
                                    <span class="eto-contact-label"><?php _e('Email', 'eto'); ?>:</span>
                                    <span class="eto-contact-value">
                                        <a href="mailto:<?php echo esc_attr($team->get('email')); ?>"><?php echo esc_html($team->get('email')); ?></a>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($team->get('website')) : ?>
                                <div class="eto-contact-item">
                                    <span class="eto-contact-label"><?php _e('Sito Web', 'eto'); ?>:</span>
                                    <span class="eto-contact-value">
                                        <a href="<?php echo esc_url($team->get('website')); ?>" target="_blank"><?php echo esc_html($team->get('website')); ?></a>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php
                            $social_media = $team->get('social_media');
                            if (is_array($social_media) && !empty($social_media)) :
                            ?>
                                <div class="eto-social-media">
                                    <h4><?php _e('Social Media', 'eto'); ?></h4>
                                    <ul class="eto-social-links">
                                        <?php foreach ($social_media as $platform => $url) : 
                                            if (empty($url)) continue;
                                        ?>
                                            <li class="eto-social-item eto-social-<?php echo esc_attr($platform); ?>">
                                                <a href="<?php echo esc_url($url); ?>" target="_blank">
                                                    <span class="eto-social-icon eto-icon-<?php echo esc_attr($platform); ?>"></span>
                                                    <span class="eto-social-label"><?php echo esc_html(ucfirst($platform)); ?></span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="eto-team-members">
                    <h2><?php _e('Membri del Team', 'eto'); ?></h2>
                    
                    <?php
                    $members = $team->get_members();
                    
                    if (empty($members)) :
                    ?>
                        <div class="eto-no-results">
                            <p><?php _e('Nessun membro in questo team.', 'eto'); ?></p>
                        </div>
                    <?php else : ?>
                        <div class="eto-members-grid">
                            <?php
                            // Ordina i membri per ruolo (capitano prima)
                            usort($members, function($a, $b) {
                                if ($a['role'] === 'captain') return -1;
                                if ($b['role'] === 'captain') return 1;
                                return 0;
                            });
                            
                            foreach ($members as $member) :
                                $user = get_userdata($member['user_id']);
                                if (!$user) continue;
                                
                                $roles = [
                                    'captain' => __('Capitano', 'eto'),
                                    'manager' => __('Manager', 'eto'),
                                    'coach' => __('Coach', 'eto'),
                                    'member' => __('Membro', 'eto'),
                                    'substitute' => __('Sostituto', 'eto')
                                ];
                                
                                $role = isset($member['role']) ? $member['role'] : 'member';
                                $role_label = isset($roles[$role]) ? $roles[$role] : $role;
                            ?>
                                <div class="eto-member-card <?php echo 'eto-role-' . esc_attr($role); ?>">
                                    <div class="eto-member-avatar">
                                        <?php echo get_avatar($member['user_id'], 96); ?>
                                    </div>
                                    
                                    <div class="eto-member-info">
                                        <h3 class="eto-member-name"><?php echo esc_html($user->display_name); ?></h3>
                                        <div class="eto-member-role"><?php echo esc_html($role_label); ?></div>
                                        
                                        <?php if (isset($member['joined_date'])) : ?>
                                            <div class="eto-member-joined">
                                                <span class="eto-joined-label"><?php _e('Membro dal', 'eto'); ?>:</span>
                                                <span class="eto-joined-date"><?php echo date_i18n(get_option('date_format'), strtotime($member['joined_date'])); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="eto-team-tournaments">
                    <h2><?php _e('Tornei', 'eto'); ?></h2>
                    
                    <?php
                    $tournaments = $team->get_tournaments();
                    
                    if (empty($tournaments)) :
                    ?>
                        <div class="eto-no-results">
                            <p><?php _e('Questo team non partecipa a nessun torneo.', 'eto'); ?></p>
                        </div>
                    <?php else : ?>
                        <div class="eto-tournaments-list">
                            <?php
                            // Ordina i tornei per data di inizio (più recenti prima)
                            usort($tournaments, function($a, $b) {
                                return strtotime($b['start_date']) - strtotime($a['start_date']);
                            });
                            
                            foreach ($tournaments as $tournament) :
                                $tournament_obj = ETO_Tournament_Model::get_by_id($tournament['id']);
                                if (!$tournament_obj) continue;
                                
                                $status_labels = [
                                    'pending' => __('In attesa', 'eto'),
                                    'active' => __('Attivo', 'eto'),
                                    'completed' => __('Completato', 'eto'),
                                    'cancelled' => __('Annullato', 'eto')
                                ];
                                
                                $status_class = [
                                    'pending' => 'status-pending',
                                    'active' => 'status-active',
                                    'completed' => 'status-completed',
                                    'cancelled' => 'status-cancelled'
                                ];
                                
                                $status = isset($tournament['status']) ? $tournament['status'] : 'pending';
                                $label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
                                $class = isset($status_class[$status]) ? $status_class[$status] : '';
                            ?>
                                <div class="eto-tournament-item">
                                    <div class="eto-tournament-info">
                                        <h3 class="eto-tournament-name">
                                            <a href="<?php echo esc_url(get_permalink($tournament['id'])); ?>"><?php echo esc_html($tournament['name']); ?></a>
                                        </h3>
                                        
                                        <div class="eto-tournament-meta">
                                            <span class="eto-tournament-status <?php echo esc_attr($class); ?>"><?php echo esc_html($label); ?></span>
                                            
                                            <?php
                                            $games = ETO_Tournament_Controller::get_available_games();
                                            $game_name = isset($games[$tournament['game']]) ? $games[$tournament['game']] : $tournament['game'];
                                            ?>
                                            
                                            <span class="eto-tournament-game"><?php echo esc_html($game_name); ?></span>
                                        </div>
                                        
                                        <div class="eto-tournament-dates">
                                            <div class="eto-date-item">
                                                <span class="eto-date-label"><?php _e('Inizio', 'eto'); ?>:</span>
                                                <span class="eto-date-value"><?php echo date_i18n(get_option('date_format'), strtotime($tournament['start_date'])); ?></span>
                                            </div>
                                            
                                            <div class="eto-date-item">
                                                <span class="eto-date-label"><?php _e('Fine', 'eto'); ?>:</span>
                                                <span class="eto-date-value"><?php echo date_i18n(get_option('date_format'), strtotime($tournament['end_date'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="eto-tournament-actions">
                                        <a href="<?php echo esc_url(get_permalink($tournament['id'])); ?>" class="eto-button"><?php _e('Visualizza Torneo', 'eto'); ?></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="eto-team-matches">
                    <h2><?php _e('Prossimi Match', 'eto'); ?></h2>
                    
                    <?php
                    $upcoming_matches = $team->get_upcoming_matches();
                    
                    if (empty($upcoming_matches)) :
                    ?>
                        <div class="eto-no-results">
                            <p><?php _e('Nessun match programmato per questo team.', 'eto'); ?></p>
                        </div>
                    <?php else : ?>
                        <div class="eto-matches-list">
                            <?php
                            foreach ($upcoming_matches as $match) :
                                $match_obj = ETO_Match_Model::get_by_id($match['id']);
                                if (!$match_obj) continue;
                                
                                $tournament = ETO_Tournament_Model::get_by_id($match['tournament_id']);
                                $opponent_id = ($match['team1_id'] == $team->get('id')) ? $match['team2_id'] : $match['team1_id'];
                                $opponent = ETO_Team_Model::get_by_id($opponent_id);
                            ?>
                                <div class="eto-match-item">
                                    <div class="eto-match-tournament">
                                        <?php if ($tournament) : ?>
                                            <a href="<?php echo esc_url(get_permalink($tournament->get('id'))); ?>"><?php echo esc_html($tournament->get('name')); ?></a>
                                        <?php else : ?>
                                            <?php _e('Torneo sconosciuto', 'eto'); ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="eto-match-details">
                                        <div class="eto-match-teams">
                                            <div class="eto-match-team team1 <?php echo ($match['team1_id'] == $team->get('id')) ? 'eto-current-team' : ''; ?>">
                                                <?php
                                                $team1 = ($match['team1_id'] == $team->get('id')) ? $team : ETO_Team_Model::get_by_id($match['team1_id']);
                                                if ($team1) :
                                                ?>
                                                    <div class="eto-team-logo">
                                                        <?php if ($team1->get('logo_url')) : ?>
                                                            <img src="<?php echo esc_url($team1->get('logo_url')); ?>" alt="<?php echo esc_attr($team1->get('name')); ?>">
                                                        <?php else : ?>
                                                            <div class="eto-team-logo-placeholder"></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="eto-team-name">
                                                        <a href="<?php echo esc_url(get_permalink($team1->get('id'))); ?>"><?php echo esc_html($team1->get('name')); ?></a>
                                                    </div>
                                                <?php else : ?>
                                                    <div class="eto-team-logo">
                                                        <div class="eto-team-logo-placeholder"></div>
                                                    </div>
                                                    
                                                    <div class="eto-team-name">
                                                        <?php _e('TBD', 'eto'); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="eto-match-vs">VS</div>
                                            
                                            <div class="eto-match-team team2 <?php echo ($match['team2_id'] == $team->get('id')) ? 'eto-current-team' : ''; ?>">
                                                <?php
                                                $team2 = ($match['team2_id'] == $team->get('id')) ? $team : ETO_Team_Model::get_by_id($match['team2_id']);
                                                if ($team2) :
                                                ?>
                                                    <div class="eto-team-logo">
                                                        <?php if ($team2->get('logo_url')) : ?>
                                                            <img src="<?php echo esc_url($team2->get('logo_url')); ?>" alt="<?php echo esc_attr($team2->get('name')); ?>">
                                                        <?php else : ?>
                                                            <div class="eto-team-logo-placeholder"></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="eto-team-name">
                                                        <a href="<?php echo esc_url(get_permalink($team2->get('id'))); ?>"><?php echo esc_html($team2->get('name')); ?></a>
                                                    </div>
                                                <?php else : ?>
                                                    <div class="eto-team-logo">
                                                        <div class="eto-team-logo-placeholder"></div>
                                                    </div>
                                                    
                                                    <div class="eto-team-name">
                                                        <?php _e('TBD', 'eto'); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="eto-match-info">
                                            <div class="eto-match-date">
                                                <span class="eto-date-label"><?php _e('Data', 'eto'); ?>:</span>
                                                <span class="eto-date-value"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($match['scheduled_date'])); ?></span>
                                            </div>
                                            
                                            <div class="eto-match-round">
                                                <span class="eto-round-label"><?php _e('Round', 'eto'); ?>:</span>
                                                <span class="eto-round-value"><?php echo intval($match['round']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($match['stream_url']) : ?>
                                        <div class="eto-match-stream">
                                            <a href="<?php echo esc_url($match['stream_url']); ?>" target="_blank" class="eto-button eto-button-stream">
                                                <span class="eto-stream-icon"></span>
                                                <?php _e('Guarda lo Streaming', 'eto'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="eto-team-results">
                    <h2><?php _e('Risultati Recenti', 'eto'); ?></h2>
                    
                    <?php
                    $recent_matches = $team->get_recent_matches();
                    
                    if (empty($recent_matches)) :
                    ?>
                        <div class="eto-no-results">
                            <p><?php _e('Nessun match completato per questo team.', 'eto'); ?></p>
                        </div>
                    <?php else : ?>
                        <div class="eto-matches-list">
                            <?php
                            foreach ($recent_matches as $match) :
                                $match_obj = ETO_Match_Model::get_by_id($match['id']);
                                if (!$match_obj) continue;
                                
                                $tournament = ETO_Tournament_Model::get_by_id($match['tournament_id']);
                                $result = $match_obj->get_result();
                                $is_winner = false;
                                
                                if ($result) {
                                    if ($match['team1_id'] == $team->get('id')) {
                                        $is_winner = $result['team1_score'] > $result['team2_score'];
                                    } else {
                                        $is_winner = $result['team2_score'] > $result['team1_score'];
                                    }
                                }
                            ?>
                                <div class="eto-match-item <?php echo $is_winner ? 'eto-match-win' : 'eto-match-loss'; ?>">
                                    <div class="eto-match-tournament">
                                        <?php if ($tournament) : ?>
                                            <a href="<?php echo esc_url(get_permalink($tournament->get('id'))); ?>"><?php echo esc_html($tournament->get('name')); ?></a>
                                        <?php else : ?>
                                            <?php _e('Torneo sconosciuto', 'eto'); ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="eto-match-details">
                                        <div class="eto-match-teams">
                                            <div class="eto-match-team team1 <?php echo ($match['team1_id'] == $team->get('id')) ? 'eto-current-team' : ''; ?>">
                                                <?php
                                                $team1 = ($match['team1_id'] == $team->get('id')) ? $team : ETO_Team_Model::get_by_id($match['team1_id']);
                                                if ($team1) :
                                                ?>
                                                    <div class="eto-team-logo">
                                                        <?php if ($team1->get('logo_url')) : ?>
                                                            <img src="<?php echo esc_url($team1->get('logo_url')); ?>" alt="<?php echo esc_attr($team1->get('name')); ?>">
                                                        <?php else : ?>
                                                            <div class="eto-team-logo-placeholder"></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="eto-team-name">
                                                        <a href="<?php echo esc_url(get_permalink($team1->get('id'))); ?>"><?php echo esc_html($team1->get('name')); ?></a>
                                                    </div>
                                                <?php else : ?>
                                                    <div class="eto-team-logo">
                                                        <div class="eto-team-logo-placeholder"></div>
                                                    </div>
                                                    
                                                    <div class="eto-team-name">
                                                        <?php _e('TBD', 'eto'); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="eto-match-result">
                                                <?php if ($result) : ?>
                                                    <div class="eto-result-score">
                                                        <span class="eto-team1-score"><?php echo intval($result['team1_score']); ?></span>
                                                        <span class="eto-score-separator">:</span>
                                                        <span class="eto-team2-score"><?php echo intval($result['team2_score']); ?></span>
                                                    </div>
                                                <?php else : ?>
                                                    <div class="eto-result-vs">VS</div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="eto-match-team team2 <?php echo ($match['team2_id'] == $team->get('id')) ? 'eto-current-team' : ''; ?>">
                                                <?php
                                                $team2 = ($match['team2_id'] == $team->get('id')) ? $team : ETO_Team_Model::get_by_id($match['team2_id']);
                                                if ($team2) :
                                                ?>
                                                    <div class="eto-team-logo">
                                                        <?php if ($team2->get('logo_url')) : ?>
                                                            <img src="<?php echo esc_url($team2->get('logo_url')); ?>" alt="<?php echo esc_attr($team2->get('name')); ?>">
                                                        <?php else : ?>
                                                            <div class="eto-team-logo-placeholder"></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="eto-team-name">
                                                        <a href="<?php echo esc_url(get_permalink($team2->get('id'))); ?>"><?php echo esc_html($team2->get('name')); ?></a>
                                                    </div>
                                                <?php else : ?>
                                                    <div class="eto-team-logo">
                                                        <div class="eto-team-logo-placeholder"></div>
                                                    </div>
                                                    
                                                    <div class="eto-team-name">
                                                        <?php _e('TBD', 'eto'); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="eto-match-info">
                                            <div class="eto-match-date">
                                                <span class="eto-date-label"><?php _e('Data', 'eto'); ?>:</span>
                                                <span class="eto-date-value"><?php echo date_i18n(get_option('date_format'), strtotime($match['scheduled_date'])); ?></span>
                                            </div>
                                            
                                            <div class="eto-match-result-label">
                                                <?php if ($is_winner) : ?>
                                                    <span class="eto-result-win"><?php _e('Vittoria', 'eto'); ?></span>
                                                <?php else : ?>
                                                    <span class="eto-result-loss"><?php _e('Sconfitta', 'eto'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php else : ?>
            <div class="eto-not-found">
                <h1><?php _e('Team non trovato', 'eto'); ?></h1>
                <p><?php _e('Il team richiesto non esiste o è stato rimosso.', 'eto'); ?></p>
                <a href="<?php echo esc_url(get_post_type_archive_link('eto_team')); ?>" class="eto-button"><?php _e('Torna alla lista dei team', 'eto'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
