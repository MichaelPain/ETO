<?php
/**
 * Template per la visualizzazione dell'archivio dei team
 * 
 * @package ETO
 * @subpackage Frontend
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

get_header();
?>

<div class="eto-teams-archive">
    <div class="eto-container">
        <header class="eto-page-header">
            <h1 class="eto-page-title"><?php _e('Team', 'eto'); ?></h1>
            
            <div class="eto-teams-filters">
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
                        
                        <div class="eto-filter-submit">
                            <button type="submit" class="eto-button"><?php _e('Filtra', 'eto'); ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </header>
        
        <?php if (empty($teams)) : ?>
            <div class="eto-no-results">
                <p><?php _e('Nessun team trovato.', 'eto'); ?></p>
            </div>
        <?php else : ?>
            <div class="eto-teams-grid">
                <?php foreach ($teams as $team) : ?>
                    <article class="eto-team-card">
                        <div class="eto-team-header">
                            <?php if (!empty($team['logo_url'])) : ?>
                                <div class="eto-team-logo">
                                    <img src="<?php echo esc_url($team['logo_url']); ?>" alt="<?php echo esc_attr($team['name']); ?>">
                                </div>
                            <?php else : ?>
                                <div class="eto-team-logo">
                                    <div class="eto-team-logo-placeholder"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="eto-team-content">
                            <h2 class="eto-team-title">
                                <a href="<?php echo esc_url(get_permalink($team['id'])); ?>"><?php echo esc_html($team['name']); ?></a>
                            </h2>
                            
                            <?php
                            $games = ETO_Tournament_Controller::get_available_games();
                            $game_name = isset($games[$team['game']]) ? $games[$team['game']] : $team['game'];
                            ?>
                            
                            <div class="eto-team-game">
                                <span class="eto-game-label"><?php _e('Gioco', 'eto'); ?>:</span>
                                <span class="eto-game-value"><?php echo esc_html($game_name); ?></span>
                            </div>
                            
                            <?php
                            $captain = ETO_User_Model::get_by_id($team['captain_id']);
                            if ($captain) :
                            ?>
                                <div class="eto-team-captain">
                                    <span class="eto-captain-label"><?php _e('Capitano', 'eto'); ?>:</span>
                                    <span class="eto-captain-value"><?php echo esc_html($captain->get('display_name')); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($team['description'])) : ?>
                                <div class="eto-team-excerpt">
                                    <?php echo wp_trim_words(wp_kses_post($team['description']), 20); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="eto-team-stats">
                                <?php
                                $team_obj = ETO_Team_Model::get_by_id($team['id']);
                                $members = $team_obj ? $team_obj->get_members() : [];
                                $tournaments = $team_obj ? $team_obj->get_tournaments() : [];
                                ?>
                                
                                <div class="eto-stat-item">
                                    <span class="eto-stat-value"><?php echo count($members); ?></span>
                                    <span class="eto-stat-label"><?php _e('Membri', 'eto'); ?></span>
                                </div>
                                
                                <div class="eto-stat-item">
                                    <span class="eto-stat-value"><?php echo count($tournaments); ?></span>
                                    <span class="eto-stat-label"><?php _e('Tornei', 'eto'); ?></span>
                                </div>
                            </div>
                            
                            <div class="eto-team-actions">
                                <a href="<?php echo esc_url(get_permalink($team['id'])); ?>" class="eto-button eto-button-primary"><?php _e('Visualizza Profilo', 'eto'); ?></a>
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
