<?php
/**
 * Template per la visualizzazione di un singolo torneo
 * 
 * @package ETO
 * @subpackage Frontend
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

get_header();
?>

<div class="eto-tournament-single">
    <div class="eto-container">
        <?php if (isset($tournament) && $tournament) : ?>
            <article class="eto-tournament">
                <header class="eto-tournament-header">
                    <div class="eto-tournament-header-content">
                        <h1 class="eto-tournament-title"><?php echo esc_html($tournament->get('name')); ?></h1>
                        
                        <div class="eto-tournament-meta">
                            <?php
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
                            
                            $status = $tournament->get('status') ?: 'pending';
                            $label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
                            $class = isset($status_class[$status]) ? $status_class[$status] : '';
                            ?>
                            
                            <span class="eto-tournament-status <?php echo esc_attr($class); ?>"><?php echo esc_html($label); ?></span>
                            
                            <?php
                            $games = ETO_Tournament_Controller::get_available_games();
                            $game = $tournament->get('game');
                            $game_name = isset($games[$game]) ? $games[$game] : $game;
                            ?>
                            
                            <span class="eto-tournament-game"><?php echo esc_html($game_name); ?></span>
                            
                            <?php
                            $formats = ETO_Tournament_Controller::get_available_formats();
                            $format = $tournament->get('format');
                            $format_name = isset($formats[$format]) ? $formats[$format] : $format;
                            ?>
                            
                            <span class="eto-tournament-format"><?php echo esc_html($format_name); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($tournament->get('featured_image')) : ?>
                        <div class="eto-tournament-image">
                            <img src="<?php echo esc_url($tournament->get('featured_image')); ?>" alt="<?php echo esc_attr($tournament->get('name')); ?>">
                        </div>
                    <?php endif; ?>
                </header>
                
                <div class="eto-tournament-content">
                    <div class="eto-tournament-details">
                        <div class="eto-tournament-dates">
                            <h3><?php _e('Date', 'eto'); ?></h3>
                            
                            <div class="eto-date-item">
                                <span class="eto-date-label"><?php _e('Inizio', 'eto'); ?>:</span>
                                <span class="eto-date-value"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($tournament->get('start_date'))); ?></span>
                            </div>
                            
                            <div class="eto-date-item">
                                <span class="eto-date-label"><?php _e('Fine', 'eto'); ?>:</span>
                                <span class="eto-date-value"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($tournament->get('end_date'))); ?></span>
                            </div>
                            
                            <?php if ($tournament->get('registration_start') && $tournament->get('registration_end')) : ?>
                                <div class="eto-date-item">
                                    <span class="eto-date-label"><?php _e('Registrazioni', 'eto'); ?>:</span>
                                    <span class="eto-date-value">
                                        <?php
                                        printf(
                                            __('Dal %s al %s', 'eto'),
                                            date_i18n(get_option('date_format'), strtotime($tournament->get('registration_start'))),
                                            date_i18n(get_option('date_format'), strtotime($tournament->get('registration_end')))
                                        );
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="eto-tournament-teams-info">
                            <h3><?php _e('Team', 'eto'); ?></h3>
                            
                            <?php
                            $teams = $tournament->get_teams();
                            $team_count = count($teams);
                            $min_teams = intval($tournament->get('min_teams')) ?: 2;
                            $max_teams = intval($tournament->get('max_teams')) ?: '-';
                            ?>
                            
                            <div class="eto-teams-count">
                                <?php printf(_n('%s team iscritto', '%s team iscritti', $team_count, 'eto'), number_format_i18n($team_count)); ?>
                                <?php if ($max_teams !== '-') : ?>
                                    <?php printf(__('su %s', 'eto'), number_format_i18n($max_teams)); ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($status === 'active' && $tournament->get('registration_end') && strtotime($tournament->get('registration_end')) > current_time('timestamp')) : ?>
                                <div class="eto-registration-status">
                                    <?php if ($team_count >= $max_teams && $max_teams !== '-') : ?>
                                        <p class="eto-registration-closed"><?php _e('Registrazioni chiuse: numero massimo di team raggiunto', 'eto'); ?></p>
                                    <?php else : ?>
                                        <p class="eto-registration-open"><?php _e('Registrazioni aperte', 'eto'); ?></p>
                                        <a href="<?php echo esc_url(add_query_arg('register', 1, get_permalink())); ?>" class="eto-button eto-button-primary"><?php _e('Iscriviti', 'eto'); ?></a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($tournament->get('description')) : ?>
                        <div class="eto-tournament-description">
                            <h3><?php _e('Descrizione', 'eto'); ?></h3>
                            <div class="eto-content">
                                <?php echo wpautop(wp_kses_post($tournament->get('description'))); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($tournament->get('rules')) : ?>
                        <div class="eto-tournament-rules">
                            <h3><?php _e('Regolamento', 'eto'); ?></h3>
                            <div class="eto-content">
                                <?php echo wpautop(wp_kses_post($tournament->get('rules'))); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($tournament->get('prizes')) : ?>
                        <div class="eto-tournament-prizes">
                            <h3><?php _e('Premi', 'eto'); ?></h3>
                            <div class="eto-content">
                                <?php echo wpautop(wp_kses_post($tournament->get('prizes'))); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="eto-tournament-teams">
                    <h2><?php _e('Team Partecipanti', 'eto'); ?></h2>
                    
                    <?php if (empty($teams)) : ?>
                        <div class="eto-no-results">
                            <p><?php _e('Nessun team iscritto a questo torneo.', 'eto'); ?></p>
                        </div>
                    <?php else : ?>
                        <div class="eto-teams-grid">
                            <?php foreach ($teams as $team_data) : 
                                $team = ETO_Team_Model::get_by_id($team_data['id']);
                                if (!$team) continue;
                            ?>
                                <div class="eto-team-card">
                                    <?php if ($team->get('logo_url')) : ?>
                                        <div class="eto-team-logo">
                                            <img src="<?php echo esc_url($team->get('logo_url')); ?>" alt="<?php echo esc_attr($team->get('name')); ?>">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="eto-team-info">
                                        <h3 class="eto-team-name">
                                            <a href="<?php echo esc_url(get_permalink($team->get('id'))); ?>"><?php echo esc_html($team->get('name')); ?></a>
                                        </h3>
                                        
                                        <?php
                                        $captain = ETO_User_Model::get_by_id($team->get('captain_id'));
                                        if ($captain) :
                                        ?>
                                            <div class="eto-team-captain">
                                                <span class="eto-captain-label"><?php _e('Capitano', 'eto'); ?>:</span>
                                                <span class="eto-captain-name"><?php echo esc_html($captain->get('display_name')); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $members = $team->get_members();
                                        $member_count = count($members);
                                        ?>
                                        <div class="eto-team-members">
                                            <span class="eto-members-count"><?php printf(_n('%s membro', '%s membri', $member_count, 'eto'), number_format_i18n($member_count)); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="eto-tournament-matches">
                    <h2><?php _e('Match', 'eto'); ?></h2>
                    
                    <?php
                    $matches = $tournament->get_matches();
                    
                    if (empty($matches)) :
                    ?>
                        <div class="eto-no-results">
                            <p><?php _e('Nessun match programmato per questo torneo.', 'eto'); ?></p>
                        </div>
                    <?php else : ?>
                        <div class="eto-matches-tabs">
                            <div class="eto-tabs-nav">
                                <?php
                                $rounds = [];
                                foreach ($matches as $match) {
                                    $round = intval($match['round']);
                                    if (!in_array($round, $rounds)) {
                                        $rounds[] = $round;
                                    }
                                }
                                sort($rounds);
                                
                                foreach ($rounds as $index => $round) :
                                ?>
                                    <button class="eto-tab-button <?php echo $index === 0 ? 'active' : ''; ?>" data-tab="round-<?php echo $round; ?>">
                                        <?php printf(__('Round %s', 'eto'), $round); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="eto-tabs-content">
                                <?php foreach ($rounds as $index => $round) : ?>
                                    <div class="eto-tab-pane <?php echo $index === 0 ? 'active' : ''; ?>" id="round-<?php echo $round; ?>">
                                        <div class="eto-matches-list">
                                            <?php
                                            $round_matches = array_filter($matches, function($match) use ($round) {
                                                return intval($match['round']) === $round;
                                            });
                                            
                                            usort($round_matches, function($a, $b) {
                                                return strtotime($a['scheduled_date']) - strtotime($b['scheduled_date']);
                                            });
                                            
                                            foreach ($round_matches as $match) :
                                                $match_obj = ETO_Match_Model::get_by_id($match['id']);
                                                if (!$match_obj) continue;
                                                
                                                $team1 = ETO_Team_Model::get_by_id($match['team1_id']);
                                                $team2 = ETO_Team_Model::get_by_id($match['team2_id']);
                                                $result = $match_obj->get_result();
                                            ?>
                                                <div class="eto-match-item">
                                                    <div class="eto-match-header">
                                                        <div class="eto-match-number">
                                                            <?php printf(__('Match #%s', 'eto'), $match['match_number']); ?>
                                                        </div>
                                                        
                                                        <div class="eto-match-date">
                                                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($match['scheduled_date'])); ?>
                                                        </div>
                                                        
                                                        <div class="eto-match-status">
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
                                                        </div>
                                                    </div>
                                                    
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
                                                            <?php else : ?>
                                                                <div class="eto-result-vs">VS</div>
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
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php else : ?>
            <div class="eto-not-found">
                <h1><?php _e('Torneo non trovato', 'eto'); ?></h1>
                <p><?php _e('Il torneo richiesto non esiste o è stato rimosso.', 'eto'); ?></p>
                <a href="<?php echo esc_url(get_post_type_archive_link('eto_tournament')); ?>" class="eto-button"><?php _e('Torna alla lista dei tornei', 'eto'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestione tabs dei match
        const tabButtons = document.querySelectorAll('.eto-tab-button');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Rimuovi la classe active da tutti i pulsanti e pannelli
                document.querySelectorAll('.eto-tab-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                document.querySelectorAll('.eto-tab-pane').forEach(pane => {
                    pane.classList.remove('active');
                });
                
                // Aggiungi la classe active al pulsante cliccato e al pannello corrispondente
                this.classList.add('active');
                
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
    });
</script>

<?php get_footer(); ?>
