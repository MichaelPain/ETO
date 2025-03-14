<?php
/**
 * Template per la visualizzazione dell'archivio dei match
 * 
 * @package ETO
 * @subpackage Frontend
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

get_header();
?>

<div class="eto-matches-archive">
    <div class="eto-container">
        <header class="eto-page-header">
            <h1 class="eto-page-title"><?php _e('Match', 'eto'); ?></h1>
            
            <div class="eto-matches-filters">
                <form method="get" class="eto-filter-form">
                    <div class="eto-filter-row">
                        <div class="eto-filter-field">
                            <label for="tournament-filter"><?php _e('Torneo', 'eto'); ?></label>
                            <select id="tournament-filter" name="tournament_id">
                                <option value=""><?php _e('Tutti i tornei', 'eto'); ?></option>
                                <?php foreach ($tournaments as $tournament) : ?>
                                    <option value="<?php echo esc_attr($tournament['id']); ?>" <?php selected(isset($_GET['tournament_id']) ? $_GET['tournament_id'] : '', $tournament['id']); ?>>
                                        <?php echo esc_html($tournament['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="eto-filter-field">
                            <label for="team-filter"><?php _e('Team', 'eto'); ?></label>
                            <select id="team-filter" name="team_id">
                                <option value=""><?php _e('Tutti i team', 'eto'); ?></option>
                                <?php foreach ($teams as $team) : ?>
                                    <option value="<?php echo esc_attr($team['id']); ?>" <?php selected(isset($_GET['team_id']) ? $_GET['team_id'] : '', $team['id']); ?>>
                                        <?php echo esc_html($team['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="eto-filter-field">
                            <label for="status-filter"><?php _e('Stato', 'eto'); ?></label>
                            <select id="status-filter" name="status">
                                <option value=""><?php _e('Tutti gli stati', 'eto'); ?></option>
                                <option value="pending" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'pending'); ?>><?php _e('In attesa', 'eto'); ?></option>
                                <option value="in_progress" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'in_progress'); ?>><?php _e('In corso', 'eto'); ?></option>
                                <option value="completed" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'completed'); ?>><?php _e('Completati', 'eto'); ?></option>
                                <option value="cancelled" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'cancelled'); ?>><?php _e('Annullati', 'eto'); ?></option>
                            </select>
                        </div>
                        
                        <div class="eto-filter-submit">
                            <button type="submit" class="eto-button"><?php _e('Filtra', 'eto'); ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </header>
        
        <?php if (empty($matches)) : ?>
            <div class="eto-no-results">
                <p><?php _e('Nessun match trovato.', 'eto'); ?></p>
            </div>
        <?php else : ?>
            <div class="eto-matches-list">
                <?php foreach ($matches as $match) : 
                    $match_obj = ETO_Match_Model::get_by_id($match['id']);
                    if (!$match_obj) continue;
                    
                    $tournament = ETO_Tournament_Model::get_by_id($match['tournament_id']);
                    $team1 = ETO_Team_Model::get_by_id($match['team1_id']);
                    $team2 = ETO_Team_Model::get_by_id($match['team2_id']);
                    $result = $match_obj->get_result();
                    
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
                    
                    $status = isset($match['status']) ? $match['status'] : 'pending';
                    $label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
                    $class = isset($status_class[$status]) ? $status_class[$status] : '';
                ?>
                    <div class="eto-match-item <?php echo esc_attr($class); ?>">
                        <div class="eto-match-tournament">
                            <?php if ($tournament) : ?>
                                <a href="<?php echo esc_url(get_permalink($tournament->get('id'))); ?>"><?php echo esc_html($tournament->get('name')); ?></a>
                            <?php else : ?>
                                <?php _e('Torneo sconosciuto', 'eto'); ?>
                            <?php endif; ?>
                            
                            <span class="eto-match-round">
                                <?php printf(__('Round %s - Match #%s', 'eto'), intval($match['round']), intval($match['match_number'])); ?>
                            </span>
                        </div>
                        
                        <div class="eto-match-details">
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
                            
                            <div class="eto-match-info">
                                <div class="eto-match-date">
                                    <span class="eto-date-label"><?php _e('Data', 'eto'); ?>:</span>
                                    <span class="eto-date-value"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($match['scheduled_date'])); ?></span>
                                </div>
                                
                                <div class="eto-match-status">
                                    <span class="eto-status-badge <?php echo esc_attr($class); ?>"><?php echo esc_html($label); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($match['stream_url'] && ($status === 'pending' || $status === 'in_progress')) : ?>
                            <div class="eto-match-stream">
                                <a href="<?php echo esc_url($match['stream_url']); ?>" target="_blank" class="eto-button eto-button-stream">
                                    <span class="eto-stream-icon"></span>
                                    <?php _e('Guarda lo Streaming', 'eto'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="eto-match-actions">
                            <a href="<?php echo esc_url(get_permalink($match['id'])); ?>" class="eto-button"><?php _e('Dettagli Match', 'eto'); ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($total_pages > 1) : ?>
                <div class="eto-pagination">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo; Precedente', 'eto'),
                        'next_text' => __('Successivo &raquo;', 'eto'),
                        'total' => $total_pages,
                        'current' => $page,
                        'type' => 'list'
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
