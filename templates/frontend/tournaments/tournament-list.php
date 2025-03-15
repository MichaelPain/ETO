<?php
/**
 * Template per la visualizzazione della lista dei tornei
 *
 * @package ETO
 * @since 2.5.3
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

// Variabili disponibili:
// $tournaments - Array di tornei
// $status - Stato dei tornei da visualizzare (active, upcoming, past)
// $limit - Numero massimo di tornei da visualizzare
?>

<div class="eto-tournament-list-container">
    <h2>
        <?php 
        if ($status == 'active') {
            _e('Tornei in corso', 'eto');
        } elseif ($status == 'upcoming') {
            _e('Prossimi tornei', 'eto');
        } elseif ($status == 'past') {
            _e('Tornei passati', 'eto');
        } else {
            _e('Tutti i tornei', 'eto');
        }
        ?>
    </h2>
    
    <?php if (!empty($tournaments)): ?>
        <div class="eto-tournament-list">
            <?php foreach ($tournaments as $tournament): ?>
                <div class="eto-tournament-item">
                    <div class="eto-tournament-header">
                        <h3 class="eto-tournament-title">
                            <a href="<?php echo esc_url(get_permalink($tournament->ID)); ?>">
                                <?php echo esc_html($tournament->post_title); ?>
                            </a>
                        </h3>
                        
                        <?php
                        // Ottieni i meta del torneo
                        $start_date = get_post_meta($tournament->ID, 'eto_tournament_start_date', true);
                        $game = get_post_meta($tournament->ID, 'eto_tournament_game', true);
                        $format = get_post_meta($tournament->ID, 'eto_tournament_format', true);
                        $max_teams = get_post_meta($tournament->ID, 'eto_tournament_max_teams', true);
                        $registered_teams = get_post_meta($tournament->ID, 'eto_tournament_registered_teams', true);
                        $is_individual = get_post_meta($tournament->ID, 'eto_is_individual', true);
                        
                        // Determina lo stato del torneo
                        $tournament_status = get_post_meta($tournament->ID, 'eto_tournament_status', true);
                        if (empty($tournament_status)) {
                            $tournament_status = 'upcoming';
                            if (!empty($start_date) && strtotime($start_date) < current_time('timestamp')) {
                                $tournament_status = 'active';
                                
                                // Verifica se il torneo è terminato
                                $end_date = get_post_meta($tournament->ID, 'eto_tournament_end_date', true);
                                if (!empty($end_date) && strtotime($end_date) < current_time('timestamp')) {
                                    $tournament_status = 'completed';
                                }
                            }
                        }
                        ?>
                        
                        <div class="eto-tournament-status">
                            <span class="eto-status-badge eto-status-<?php echo esc_attr($tournament_status); ?>">
                                <?php
                                if ($tournament_status == 'registration') {
                                    _e('Registrazione aperta', 'eto');
                                } elseif ($tournament_status == 'checkin') {
                                    _e('Check-in aperto', 'eto');
                                } elseif ($tournament_status == 'active') {
                                    _e('In corso', 'eto');
                                } elseif ($tournament_status == 'completed') {
                                    _e('Completato', 'eto');
                                } else {
                                    _e('In arrivo', 'eto');
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="eto-tournament-details">
                        <div class="eto-tournament-meta">
                            <?php if (!empty($start_date)): ?>
                                <div class="eto-meta-item eto-meta-date">
                                    <span class="eto-meta-label"><?php _e('Data:', 'eto'); ?></span>
                                    <span class="eto-meta-value"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($start_date)); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($game)): ?>
                                <div class="eto-meta-item eto-meta-game">
                                    <span class="eto-meta-label"><?php _e('Gioco:', 'eto'); ?></span>
                                    <span class="eto-meta-value"><?php echo esc_html($game); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($format)): ?>
                                <div class="eto-meta-item eto-meta-format">
                                    <span class="eto-meta-label"><?php _e('Formato:', 'eto'); ?></span>
                                    <span class="eto-meta-value"><?php echo esc_html($format); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="eto-meta-item eto-meta-type">
                                <span class="eto-meta-label"><?php _e('Tipo:', 'eto'); ?></span>
                                <span class="eto-meta-value">
                                    <?php echo $is_individual ? __('Individuale', 'eto') : __('Squadre', 'eto'); ?>
                                </span>
                            </div>
                            
                            <?php if (!$is_individual && !empty($max_teams)): ?>
                                <div class="eto-meta-item eto-meta-teams">
                                    <span class="eto-meta-label"><?php _e('Team:', 'eto'); ?></span>
                                    <span class="eto-meta-value">
                                        <?php 
                                        if (empty($registered_teams)) {
                                            $registered_teams = 0;
                                        }
                                        printf(__('%d/%d', 'eto'), $registered_teams, $max_teams); 
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="eto-tournament-excerpt">
                            <?php
                            if (!empty($tournament->post_excerpt)) {
                                echo wpautop(esc_html($tournament->post_excerpt));
                            } else {
                                $content = wp_trim_words($tournament->post_content, 30, '...');
                                echo wpautop(esc_html($content));
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="eto-tournament-actions">
                        <a href="<?php echo esc_url(get_permalink($tournament->ID)); ?>" class="button eto-button-primary">
                            <?php _e('Visualizza dettagli', 'eto'); ?>
                        </a>
                        
                        <?php if ($tournament_status == 'registration'): ?>
                            <a href="<?php echo esc_url(add_query_arg('action', 'register', get_permalink($tournament->ID))); ?>" class="button eto-button-secondary">
                                <?php _e('Registrati', 'eto'); ?>
                            </a>
                        <?php elseif ($tournament_status == 'checkin'): ?>
                            <a href="<?php echo esc_url(add_query_arg('action', 'checkin', get_permalink($tournament->ID))); ?>" class="button eto-button-secondary">
                                <?php _e('Check-in', 'eto'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php
        // Paginazione se necessaria
        $total_tournaments = count($tournaments);
        if ($total_tournaments > $limit) {
            $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
            $total_pages = ceil($total_tournaments / $limit);
            
            if ($total_pages > 1) {
                echo '<div class="eto-pagination">';
                
                // Link alla pagina precedente
                if ($current_page > 1) {
                    echo '<a href="' . esc_url(add_query_arg('paged', $current_page - 1)) . '" class="eto-prev-page">&laquo; ' . __('Precedente', 'eto') . '</a>';
                }
                
                // Numeri di pagina
                for ($i = 1; $i <= $total_pages; $i++) {
                    if ($i == $current_page) {
                        echo '<span class="eto-current-page">' . $i . '</span>';
                    } else {
                        echo '<a href="' . esc_url(add_query_arg('paged', $i)) . '" class="eto-page-number">' . $i . '</a>';
                    }
                }
                
                // Link alla pagina successiva
                if ($current_page < $total_pages) {
                    echo '<a href="' . esc_url(add_query_arg('paged', $current_page + 1)) . '" class="eto-next-page">' . __('Successiva', 'eto') . ' &raquo;</a>';
                }
                
                echo '</div>';
            }
        }
        ?>
        
    <?php else: ?>
        <div class="eto-no-tournaments">
            <p>
                <?php 
                if ($status == 'active') {
                    _e('Non ci sono tornei attualmente in corso.', 'eto');
                } elseif ($status == 'upcoming') {
                    _e('Non ci sono tornei in programma.', 'eto');
                } elseif ($status == 'past') {
                    _e('Non ci sono tornei passati.', 'eto');
                } else {
                    _e('Non ci sono tornei disponibili.', 'eto');
                }
                ?>
            </p>
        </div>
    <?php endif; ?>
</div>
