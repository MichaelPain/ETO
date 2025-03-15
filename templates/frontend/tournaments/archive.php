<?php
/**
 * Template per la visualizzazione dell'archivio dei tornei
 * 
 * @package ETO
 * @subpackage Frontend
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

get_header();
?>

<div class="eto-tournaments-archive">
    <div class="eto-container">
        <header class="eto-page-header">
            <h1 class="eto-page-title"><?php _e('Tornei', 'eto'); ?></h1>
            
            <div class="eto-tournaments-filters">
                <form method="get" class="eto-filter-form">
                    <div class="eto-filter-row">
                        <div class="eto-filter-field">
                            <label for="game-filter"><?php _e('Gioco', 'eto'); ?></label>
                            <select id="game-filter" name="game">
                                <option value=""><?php _e('Tutti i giochi', 'eto'); ?></option>
                                <?php foreach ($games as $game_id => $game_name) : ?>
                                    <option value="<?php echo esc_attr($game_id); ?>" <?php selected(isset($_GET['game']) ? $_GET['game'] : '', $game_id); ?>><?php echo esc_html($game_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="eto-filter-field">
                            <label for="status-filter"><?php _e('Stato', 'eto'); ?></label>
                            <select id="status-filter" name="status">
                                <option value=""><?php _e('Tutti gli stati', 'eto'); ?></option>
                                <option value="active" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'active'); ?>><?php _e('Attivi', 'eto'); ?></option>
                                <option value="pending" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'pending'); ?>><?php _e('In attesa', 'eto'); ?></option>
                                <option value="completed" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'completed'); ?>><?php _e('Completati', 'eto'); ?></option>
                            </select>
                        </div>
                        
                        <div class="eto-filter-submit">
                            <button type="submit" class="eto-button"><?php _e('Filtra', 'eto'); ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </header>
        
        <?php if (empty($tournaments)) : ?>
            <div class="eto-no-results">
                <p><?php _e('Nessun torneo trovato.', 'eto'); ?></p>
            </div>
        <?php else : ?>
            <div class="eto-tournaments-grid">
                <?php foreach ($tournaments as $tournament) : ?>
                    <article class="eto-tournament-card">
                        <div class="eto-tournament-header">
                            <?php if (!empty($tournament['featured_image'])) : ?>
                                <div class="eto-tournament-image">
                                    <img src="<?php echo esc_url($tournament['featured_image']); ?>" alt="<?php echo esc_attr($tournament['name']); ?>">
                                </div>
                            <?php endif; ?>
                            
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
                                
                                $status = isset($tournament['status']) ? $tournament['status'] : 'pending';
                                $label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
                                $class = isset($status_class[$status]) ? $status_class[$status] : '';
                                ?>
                                
                                <span class="eto-tournament-status <?php echo esc_attr($class); ?>"><?php echo esc_html($label); ?></span>
                                
                                <?php
                                $games = ETO_Tournament_Controller::get_available_games();
                                $game_name = isset($games[$tournament['game']]) ? $games[$tournament['game']] : $tournament['game'];
                                ?>
                                
                                <span class="eto-tournament-game"><?php echo esc_html($game_name); ?></span>
                            </div>
                        </div>
                        
                        <div class="eto-tournament-content">
                            <h2 class="eto-tournament-title">
                                <a href="<?php echo esc_url(get_permalink($tournament['id'])); ?>"><?php echo esc_html($tournament['name']); ?></a>
                            </h2>
                            
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
                            
                            <?php if (!empty($tournament['description'])) : ?>
                                <div class="eto-tournament-excerpt">
                                    <?php echo wp_trim_words(wp_kses_post($tournament['description']), 20); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="eto-tournament-teams">
                                <?php
                                $tournament_obj = ETO_Tournament_Model::get_by_id($tournament['id']);
                                $teams = $tournament_obj ? $tournament_obj->get_teams() : [];
                                $team_count = count($teams);
                                $max_teams = intval($tournament['max_teams']) ?: '-';
                                ?>
                                
                                <span class="eto-teams-count">
                                    <?php printf(_n('%s team iscritto', '%s team iscritti', $team_count, 'eto'), number_format_i18n($team_count)); ?>
                                    <?php if ($max_teams !== '-') : ?>
                                        <?php printf(__('su %s', 'eto'), number_format_i18n($max_teams)); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="eto-tournament-actions">
                                <a href="<?php echo esc_url(get_permalink($tournament['id'])); ?>" class="eto-button eto-button-primary"><?php _e('Visualizza Dettagli', 'eto'); ?></a>
                                
                                <?php if ($status === 'active' && isset($tournament['registration_end']) && strtotime($tournament['registration_end']) > current_time('timestamp')) : ?>
                                    <a href="<?php echo esc_url(add_query_arg('register', 1, get_permalink($tournament['id']))); ?>" class="eto-button"><?php _e('Iscriviti', 'eto'); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
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
