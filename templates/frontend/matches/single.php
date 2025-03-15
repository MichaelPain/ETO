<?php
/**
 * Template per la visualizzazione di un singolo match
 * 
 * @package ETO
 * @subpackage Frontend
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

get_header();
?>

<div class="eto-match-single">
    <div class="eto-container">
        <?php if (isset($match) && $match) : 
            $tournament = ETO_Tournament_Model::get_by_id($match->get('tournament_id'));
            $team1 = ETO_Team_Model::get_by_id($match->get('team1_id'));
            $team2 = ETO_Team_Model::get_by_id($match->get('team2_id'));
            $result = $match->get_result();
            
            $status_labels = [
                'pending' => __('In attesa', 'eto'),
                'in_progress' => __('In corso', 'eto'),
                'completed' => __('Completato', 'eto'),
                'cancelled' => __('Annullato', 'eto')
            ];
            
            $status_class = [
                'pending' => 'status-pending',
                'in_progress' => 'status-in-progress',
                'completed' => 'status-completed',
                'cancelled' => 'status-cancelled'
            ];
            
            $status = $match->get('status') ?: 'pending';
            $label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
            $class = isset($status_class[$status]) ? $status_class[$status] : '';
        ?>
            <article class="eto-match">
                <header class="eto-match-header">
                    <div class="eto-match-header-content">
                        <h1 class="eto-match-title">
                            <?php 
                            if ($team1 && $team2) {
                                printf(
                                    __('%s vs %s', 'eto'),
                                    esc_html($team1->get('name')),
                                    esc_html($team2->get('name'))
                                );
                            } else {
                                _e('Dettagli Match', 'eto');
                            }
                            ?>
                        </h1>
                        
                        <div class="eto-match-meta">
                            <span class="eto-match-status <?php echo esc_attr($class); ?>"><?php echo esc_html($label); ?></span>
                            
                            <?php if ($tournament) : ?>
                                <span class="eto-match-tournament">
                                    <a href="<?php echo esc_url(get_permalink($tournament->get('id'))); ?>"><?php echo esc_html($tournament->get('name')); ?></a>
                                </span>
                            <?php endif; ?>
                            
                            <span class="eto-match-round">
                                <?php printf(__('Round %s - Match #%s', 'eto'), intval($match->get('round')), intval($match->get('match_number'))); ?>
                            </span>
                        </div>
                    </div>
                </header>
                
                <div class="eto-match-content">
                    <div class="eto-match-details">
                        <div class="eto-match-date">
                            <h3><?php _e('Data e Ora', 'eto'); ?></h3>
                            <div class="eto-date-value">
                                <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($match->get('scheduled_date'))); ?>
                            </div>
                        </div>
                        
                        <?php if ($match->get('stream_url')) : ?>
                            <div class="eto-match-stream">
                                <h3><?php _e('Streaming', 'eto'); ?></h3>
                                <a href="<?php echo esc_url($match->get('stream_url')); ?>" target="_blank" class="eto-button eto-button-stream">
                                    <span class="eto-stream-icon"></span>
                                    <?php _e('Guarda lo Streaming', 'eto'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="eto-match-teams-container">
                        <div class="eto-match-teams">
                            <div class="eto-match-team team1">
                                <?php if ($team1) : ?>
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
                                <?php if ($result && $status === 'completed') : ?>
                                    <div class="eto-result-score">
                                        <span class="eto-team1-score"><?php echo intval($result['team1_score']); ?></span>
                                        <span class="eto-score-separator">:</span>
                                        <span class="eto-team2-score"><?php echo intval($result['team2_score']); ?></span>
                                    </div>
                                    
                                    <?php if ($result['team1_score'] !== $result['team2_score']) : ?>
                                        <div class="eto-winner-label">
                                            <?php
                                            if ($result['team1_score'] > $result['team2_score']) {
                                                $winner = $team1 ? $team1->get('name') : __('Team 1', 'eto');
                                            } else {
                                                $winner = $team2 ? $team2->get('name') : __('Team 2', 'eto');
                                            }
                                            
                                            printf(__('Vincitore: %s', 'eto'), esc_html($winner));
                                            ?>
                                        </div>
                                    <?php else : ?>
                                        <div class="eto-winner-label">
                                            <?php _e('Pareggio', 'eto'); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <div class="eto-result-vs">VS</div>
                                    
                                    <?php if ($status === 'pending' || $status === 'in_progress') : ?>
                                        <div class="eto-match-countdown" data-date="<?php echo esc_attr(date('Y-m-d H:i:s', strtotime($match->get('scheduled_date')))); ?>">
                                            <?php if (strtotime($match->get('scheduled_date')) > current_time('timestamp')) : ?>
                                                <div class="eto-countdown-label"><?php _e('Inizia tra', 'eto'); ?></div>
                                                <div class="eto-countdown-timer">
                                                    <span class="eto-countdown-days">00</span>
                                                    <span class="eto-countdown-separator">:</span>
                                                    <span class="eto-countdown-hours">00</span>
                                                    <span class="eto-countdown-separator">:</span>
                                                    <span class="eto-countdown-minutes">00</span>
                                                    <span class="eto-countdown-separator">:</span>
                                                    <span class="eto-countdown-seconds">00</span>
                                                </div>
                                            <?php elseif ($status === 'in_progress') : ?>
                                                <div class="eto-match-live"><?php _e('LIVE', 'eto'); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="eto-match-team team2">
                                <?php if ($team2) : ?>
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
                    </div>
                    
                    <?php if ($match->get('notes')) : ?>
                        <div class="eto-match-notes">
                            <h3><?php _e('Note', 'eto'); ?></h3>
                            <div class="eto-content">
                                <?php echo wpautop(wp_kses_post($match->get('notes'))); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($tournament) : ?>
                    <div class="eto-tournament-info">
                        <h2><?php _e('Informazioni Torneo', 'eto'); ?></h2>
                        
                        <div class="eto-tournament-details">
                            <div class="eto-tournament-image">
                                <?php if ($tournament->get('featured_image')) : ?>
                                    <img src="<?php echo esc_url($tournament->get('featured_image')); ?>" alt="<?php echo esc_attr($tournament->get('name')); ?>">
                                <?php else : ?>
                                    <div class="eto-tournament-image-placeholder"></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="eto-tournament-content">
                                <h3 class="eto-tournament-name">
                                    <a href="<?php echo esc_url(get_permalink($tournament->get('id'))); ?>"><?php echo esc_html($tournament->get('name')); ?></a>
                                </h3>
                                
                                <div class="eto-tournament-meta">
                                    <?php
                                    $t_status = $tournament->get('status') ?: 'pending';
                                    $t_label = isset($status_labels[$t_status]) ? $status_labels[$t_status] : $t_status;
                                    $t_class = isset($status_class[$t_status]) ? $status_class[$t_status] : '';
                                    ?>
                                    
                                    <span class="eto-tournament-status <?php echo esc_attr($t_class); ?>"><?php echo esc_html($t_label); ?></span>
                                    
                                    <?php
                                    $games = ETO_Tournament_Controller::get_available_games();
                                    $game = $tournament->get('game');
                                    $game_name = isset($games[$game]) ? $games[$game] : $game;
                                    ?>
                                    
                                    <span class="eto-tournament-game"><?php echo esc_html($game_name); ?></span>
                                </div>
                                
                                <div class="eto-tournament-dates">
                                    <div class="eto-date-item">
                                        <span class="eto-date-label"><?php _e('Inizio', 'eto'); ?>:</span>
                                        <span class="eto-date-value"><?php echo date_i18n(get_option('date_format'), strtotime($tournament->get('start_date'))); ?></span>
                                    </div>
                                    
                                    <div class="eto-date-item">
                                        <span class="eto-date-label"><?php _e('Fine', 'eto'); ?>:</span>
                                        <span class="eto-date-value"><?php echo date_i18n(get_option('date_format'), strtotime($tournament->get('end_date'))); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($tournament->get('description')) : ?>
                                    <div class="eto-tournament-excerpt">
                                        <?php echo wp_trim_words(wp_kses_post($tournament->get('description')), 30); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="eto-tournament-actions">
                                    <a href="<?php echo esc_url(get_permalink($tournament->get('id'))); ?>" class="eto-button"><?php _e('Visualizza Torneo', 'eto'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="eto-related-matches">
                    <h2><?php _e('Altri Match', 'eto'); ?></h2>
                    
                    <?php
                    $related_matches = [];
                    
                    if ($tournament) {
                        $all_matches = $tournament->get_matches();
                        
                        // Filtra per ottenere solo i match dello stesso round
                        $round_matches = array_filter($all_matches, function($m) use ($match) {
                            return $m['id'] != $match->get('id') && $m['round'] == $match->get('round');
                        });
                        
                        // Prendi i primi 3 match
                        $related_matches = array_slice($round_matches, 0, 3);
                    }
                    
                    if (empty($related_matches)) :
                    ?>
                        <div class="eto-no-results">
                            <p><?php _e('Nessun altro match trovato in questo round.', 'eto'); ?></p>
                        </div>
                    <?php else : ?>
                        <div class="eto-matches-grid">
                            <?php foreach ($related_matches as $rel_match) : 
                                $rel_match_obj = ETO_Match_Model::get_by_id($rel_match['id']);
                                if (!$rel_match_obj) continue;
                                
                                $rel_team1 = ETO_Team_Model::get_by_id($rel_match['team1_id']);
                                $rel_team2 = ETO_Team_Model::get_by_id($rel_match['team2_id']);
                                $rel_result = $rel_match_obj->get_result();
                                
                                $rel_status = isset($rel_match['status']) ? $rel_match['status'] : 'pending';
                                $rel_label = isset($status_labels[$rel_status]) ? $status_labels[$rel_status] : $rel_status;
                                $rel_class = isset($status_class[$rel_status]) ? $status_class[$rel_status] : '';
                            ?>
                                <div class="eto-match-card <?php echo esc_attr($rel_class); ?>">
                                    <div class="eto-match-card-header">
                                        <span class="eto-match-number">
                                            <?php printf(__('Match #%s', 'eto'), intval($rel_match['match_number'])); ?>
                                        </span>
                                        
                                        <span class="eto-match-status <?php echo esc_attr($rel_class); ?>"><?php echo esc_html($rel_label); ?></span>
                                    </div>
                                    
                                    <div class="eto-match-card-teams">
                                        <div class="eto-match-card-team team1">
                                            <?php if ($rel_team1) : ?>
                                                <div class="eto-team-logo">
                                                    <?php if ($rel_team1->get('logo_url')) : ?>
                                                        <img src="<?php echo esc_url($rel_team1->get('logo_url')); ?>" alt="<?php echo esc_attr($rel_team1->get('name')); ?>">
                                                    <?php else : ?>
                                                        <div class="eto-team-logo-placeholder"></div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="eto-team-name">
                                                    <?php echo esc_html($rel_team1->get('name')); ?>
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
                                        
                                        <div class="eto-match-card-result">
                                            <?php if ($rel_result && $rel_status === 'completed') : ?>
                                                <div class="eto-result-score">
                                                    <span class="eto-team1-score"><?php echo intval($rel_result['team1_score']); ?></span>
                                                    <span class="eto-score-separator">:</span>
                                                    <span class="eto-team2-score"><?php echo intval($rel_result['team2_score']); ?></span>
                                                </div>
                                            <?php else : ?>
                                                <div class="eto-result-vs">VS</div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="eto-match-card-team team2">
                                            <?php if ($rel_team2) : ?>
                                                <div class="eto-team-logo">
                                                    <?php if ($rel_team2->get('logo_url')) : ?>
                                                        <img src="<?php echo esc_url($rel_team2->get('logo_url')); ?>" alt="<?php echo esc_attr($rel_team2->get('name')); ?>">
                                                    <?php else : ?>
                                                        <div class="eto-team-logo-placeholder"></div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="eto-team-name">
                                                    <?php echo esc_html($rel_team2->get('name')); ?>
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
                                    
                                    <div class="eto-match-card-footer">
                                        <div class="eto-match-date">
                                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($rel_match['scheduled_date'])); ?>
                                        </div>
                                        
                                        <div class="eto-match-actions">
                                            <a href="<?php echo esc_url(get_permalink($rel_match['id'])); ?>" class="eto-button eto-button-sm"><?php _e('Dettagli', 'eto'); ?></a>
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
                <h1><?php _e('Match non trovato', 'eto'); ?></h1>
                <p><?php _e('Il match richiesto non esiste o è stato rimosso.', 'eto'); ?></p>
                <a href="<?php echo esc_url(get_post_type_archive_link('eto_match')); ?>" class="eto-button"><?php _e('Torna alla lista dei match', 'eto'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestione countdown
        const countdownElements = document.querySelectorAll('.eto-match-countdown');
        
        countdownElements.forEach(function(element) {
            const targetDate = new Date(element.getAttribute('data-date')).getTime();
            
            // Aggiorna il countdown ogni secondo
            const countdownTimer = setInterval(function() {
                const now = new Date().getTime();
                const distance = targetDate - now;
                
                // Calcola giorni, ore, minuti e secondi
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                // Aggiorna gli elementi del countdown
                const daysElement = element.querySelector('.eto-countdown-days');
                const hoursElement = element.querySelector('.eto-countdown-hours');
                const minutesElement = element.querySelector('.eto-countdown-minutes');
                const secondsElement = element.querySelector('.eto-countdown-seconds');
                
                if (daysElement) daysElement.textContent = days.toString().padStart(2, '0');
                if (hoursElement) hoursElement.textContent = hours.toString().padStart(2, '0');
                if (minutesElement) minutesElement.textContent = minutes.toString().padStart(2, '0');
                if (secondsElement) secondsElement.textContent = seconds.toString().padStart(2, '0');
                
                // Se il countdown è terminato
                if (distance < 0) {
                    clearInterval(countdownTimer);
                    element.innerHTML = '<div class="eto-match-live"><?php _e('LIVE', 'eto'); ?></div>';
                }
            }, 1000);
        });
    });
</script>

<?php get_footer(); ?>
