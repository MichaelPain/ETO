<?php
/**
 * Template per la visualizzazione di un singolo torneo
 *
 * @package ETO
 * @since 2.5.3
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

// Variabili disponibili:
// $tournament - Oggetto torneo (WP_Post)
// $tournament_id - ID del torneo
?>

<div class="eto-tournament-view-container">
    <div class="eto-tournament-header">
        <h2 class="eto-tournament-title"><?php echo get_the_title($tournament_id); ?></h2>
        
        <?php
        // Ottieni i meta del torneo
        $start_date = get_post_meta($tournament_id, 'eto_tournament_start_date', true);
        $end_date = get_post_meta($tournament_id, 'eto_tournament_end_date', true);
        $game = get_post_meta($tournament_id, 'eto_tournament_game', true);
        $format = get_post_meta($tournament_id, 'eto_tournament_format', true);
        $max_teams = get_post_meta($tournament_id, 'eto_tournament_max_teams', true);
        $registered_teams = get_post_meta($tournament_id, 'eto_tournament_registered_teams', true);
        $is_individual = get_post_meta($tournament_id, 'eto_is_individual', true);
        $prize_pool = get_post_meta($tournament_id, 'eto_tournament_prize_pool', true);
        $rules = get_post_meta($tournament_id, 'eto_tournament_rules', true);
        
        // Determina lo stato del torneo
        $tournament_status = get_post_meta($tournament_id, 'eto_tournament_status', true);
        if (empty($tournament_status)) {
            $tournament_status = 'upcoming';
            if (!empty($start_date) && strtotime($start_date) < current_time('timestamp')) {
                $tournament_status = 'active';
                
                // Verifica se il torneo è terminato
                if (!empty($end_date) && strtotime($end_date) < current_time('timestamp')) {
                    $tournament_status = 'completed';
                }
            }
        }
        
        // Verifica se il check-in è abilitato
        $checkin_enabled = get_post_meta($tournament_id, 'eto_checkin_enabled', true);
        $checkin_start = get_post_meta($tournament_id, 'eto_checkin_start', true);
        $checkin_end = get_post_meta($tournament_id, 'eto_checkin_end', true);
        
        // Verifica se l'utente è registrato al torneo
        $user_registered = false;
        $user_team_id = 0;
        
        if (is_user_logged_in()) {
            $current_user_id = get_current_user_id();
            
            if ($is_individual) {
                global $wpdb;
                $participants_table = $wpdb->prefix . 'eto_individual_participants';
                
                $user_registered = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $participants_table WHERE tournament_id = %d AND user_id = %d",
                    $tournament_id,
                    $current_user_id
                )) > 0;
            } else {
                global $wpdb;
                $teams_table = $wpdb->prefix . 'eto_teams';
                $members_table = $wpdb->prefix . 'eto_team_members';
                $entries_table = $wpdb->prefix . 'eto_tournament_entries';
                
                // Ottieni i team dell'utente
                $user_teams = $wpdb->get_results($wpdb->prepare(
                    "SELECT t.id, t.name, t.captain_id 
                    FROM $teams_table t
                    JOIN $members_table m ON t.id = m.team_id
                    WHERE m.user_id = %d",
                    $current_user_id
                ));
                
                // Verifica se uno dei team dell'utente è registrato al torneo
                foreach ($user_teams as $team) {
                    $is_registered = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $entries_table WHERE tournament_id = %d AND team_id = %d",
                        $tournament_id,
                        $team->id
                    )) > 0;
                    
                    if ($is_registered) {
                        $user_registered = true;
                        $user_team_id = $team->id;
                        break;
                    }
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
                    <span class="eto-meta-label"><?php _e('Data inizio:', 'eto'); ?></span>
                    <span class="eto-meta-value"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($start_date)); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($end_date)): ?>
                <div class="eto-meta-item eto-meta-date">
                    <span class="eto-meta-label"><?php _e('Data fine:', 'eto'); ?></span>
                    <span class="eto-meta-value"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($end_date)); ?></span>
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
            
            <?php if (!empty($prize_pool)): ?>
                <div class="eto-meta-item eto-meta-prize">
                    <span class="eto-meta-label"><?php _e('Montepremi:', 'eto'); ?></span>
                    <span class="eto-meta-value"><?php echo esc_html($prize_pool); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="eto-tournament-content">
            <?php echo wpautop(get_the_content(null, false, $tournament_id)); ?>
        </div>
        
        <?php if (!empty($rules)): ?>
            <div class="eto-tournament-rules">
                <h3><?php _e('Regolamento', 'eto'); ?></h3>
                <?php echo wpautop($rules); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="eto-tournament-actions">
        <?php if ($tournament_status == 'registration' && !$user_registered): ?>
            <?php if (is_user_logged_in()): ?>
                <?php if ($is_individual): ?>
                    <form method="post" class="eto-register-form">
                        <?php wp_nonce_field('eto_tournament_action', 'eto_tournament_nonce'); ?>
                        <input type="hidden" name="action" value="register_individual">
                        <input type="hidden" name="tournament_id" value="<?php echo $tournament_id; ?>">
                        <button type="submit" class="button eto-button-primary">
                            <?php _e('Registrati al torneo', 'eto'); ?>
                        </button>
                    </form>
                <?php else: ?>
                    <?php
                    // Ottieni i team dell'utente che possono essere registrati
                    global $wpdb;
                    $teams_table = $wpdb->prefix . 'eto_teams';
                    $members_table = $wpdb->prefix . 'eto_team_members';
                    $entries_table = $wpdb->prefix . 'eto_tournament_entries';
                    
                    $user_teams = $wpdb->get_results($wpdb->prepare(
                        "SELECT t.id, t.name, t.captain_id 
                        FROM $teams_table t
                        JOIN $members_table m ON t.id = m.team_id
                        WHERE m.user_id = %d AND t.captain_id = %d",
                        get_current_user_id(),
                        get_current_user_id()
                    ));
                    
                    // Filtra i team già registrati ad altri tornei attivi
                    $available_teams = [];
                    foreach ($user_teams as $team) {
                        $is_registered_elsewhere = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $entries_table e
                            JOIN $wpdb->posts p ON e.tournament_id = p.ID
                            WHERE e.team_id = %d 
                            AND p.ID != %d
                            AND p.post_status = 'publish'
                            AND (
                                p.meta_value = 'registration' 
                                OR p.meta_value = 'checkin' 
                                OR p.meta_value = 'active'
                            )",
                            $team->id,
                            $tournament_id
                        )) > 0;
                        
                        if (!$is_registered_elsewhere) {
                            $available_teams[] = $team;
                        }
                    }
                    
                    if (!empty($available_teams)):
                    ?>
                        <form method="post" class="eto-register-form">
                            <?php wp_nonce_field('eto_tournament_action', 'eto_tournament_nonce'); ?>
                            <input type="hidden" name="action" value="register_team">
                            <input type="hidden" name="tournament_id" value="<?php echo $tournament_id; ?>">
                            
                            <div class="eto-form-row">
                                <label for="team_id"><?php _e('Seleziona team:', 'eto'); ?></label>
                                <select id="team_id" name="team_id" required>
                                    <option value=""><?php _e('-- Seleziona --', 'eto'); ?></option>
                                    <?php foreach ($available_teams as $team): ?>
                                        <option value="<?php echo $team->id; ?>"><?php echo esc_html($team->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="eto-form-row">
                                <button type="submit" class="button eto-button-primary">
                                    <?php _e('Registra team al torneo', 'eto'); ?>
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="eto-no-teams">
                            <p><?php _e('Non hai team disponibili per la registrazione.', 'eto'); ?></p>
                            <a href="<?php echo esc_url(get_permalink(get_option('eto_create_team_page'))); ?>" class="button eto-button-secondary">
                                <?php _e('Crea un team', 'eto'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="eto-login-required">
                    <p><?php _e('Devi effettuare il login per registrarti al torneo.', 'eto'); ?></p>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button eto-button-primary">
                        <?php _e('Accedi', 'eto'); ?>
                    </a>
                </div>
            <?php endif; ?>
        <?php elseif ($user_registered): ?>
            <div class="eto-registered-info">
                <p>
                    <?php 
                    if ($is_individual) {
                        _e('Sei registrato a questo torneo.', 'eto');
                    } else {
                        printf(__('Il tuo team "%s" è registrato a questo torneo.', 'eto'), 
                            esc_html(get_the_title($user_team_id)));
                    }
                    ?>
                </p>
                
                <?php if ($checkin_enabled && $tournament_status == 'checkin'): ?>
                    <a href="<?php echo esc_url(add_query_arg('action', 'checkin', get_permalink())); ?>" class="button eto-button-primary">
                        <?php _e('Vai al check-in', 'eto'); ?>
                    </a>
                <?php endif; ?>
                
                <?php if ($tournament_status == 'registration'): ?>
                    <form method="post" class="eto-unregister-form">
                        <?php wp_nonce_field('eto_tournament_action', 'eto_tournament_nonce'); ?>
                        <input type="hidden" name="action" value="<?php echo $is_individual ? 'unregister_individual' : 'unregister_team'; ?>">
                        <input type="hidden" name="tournament_id" value="<?php echo $tournament_id; ?>">
                        <?php if (!$is_individual): ?>
                            <input type="hidden" name="team_id" value="<?php echo $user_team_id; ?>">
                        <?php endif; ?>
                        <button type="submit" class="button eto-button-danger" onclick="return confirm('<?php esc_attr_e('Sei sicuro di voler annullare la registrazione?', 'eto'); ?>')">
                            <?php _e('Annulla registrazione', 'eto'); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($tournament_status == 'active' || $tournament_status == 'completed'): ?>
        <div class="eto-tournament-bracket">
            <h3><?php _e('Bracket', 'eto'); ?></h3>
            
            <?php
            // Carica il bracket se disponibile
            $bracket_data = get_post_meta($tournament_id, 'eto_tournament_bracket', true);
            if (!empty($bracket_data)):
            ?>
                <div class="eto-bracket-container" id="eto-bracket-<?php echo $tournament_id; ?>"></div>
                
                <script>
                jQuery(document).ready(function($) {
                    var bracketData = <?php echo json_encode($bracket_data); ?>;
                    
                    // Inizializza il bracket renderer
                    if (typeof ETO_BracketRenderer !== 'undefined') {
                        var renderer = new ETO_BracketRenderer('#eto-bracket-<?php echo $tournament_id; ?>');
                        renderer.render(bracketData);
                    } else {
                        $('#eto-bracket-<?php echo $tournament_id; ?>').html('<p><?php _e('Errore nel caricamento del bracket.', 'eto'); ?></p>');
                    }
                });
                </script>
            <?php else: ?>
                <p><?php _e('Il bracket non è ancora disponibile.', 'eto'); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if ($tournament_status == 'completed'): ?>
            <div class="eto-tournament-results">
                <h3><?php _e('Risultati finali', 'eto'); ?></h3>
                
                <?php
                // Carica i risultati se disponibili
                $results = get_post_meta($tournament_id, 'eto_tournament_results', true);
                if (!empty($results) && is_array($results)):
                ?>
                    <div class="eto-results-table">
                        <table>
                            <thead>
                                <tr>
                                    <th><?php _e('Posizione', 'eto'); ?></th>
                                    <th><?php _e('Nome', 'eto'); ?></th>
                                    <?php if (!empty($prize_pool)): ?>
                                        <th><?php _e('Premio', 'eto'); ?></th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $position => $result): ?>
                                    <tr>
                                        <td class="eto-result-position"><?php echo $position + 1; ?></td>
                                        <td class="eto-result-name">
                                            <?php 
                                            if ($is_individual) {
                                                echo esc_html(get_the_author_meta('display_name', $result['id']));
                                            } else {
                                                echo esc_html(get_the_title($result['id']));
                                            }
                                            ?>
                                        </td>
                                        <?php if (!empty($prize_pool) && !empty($result['prize'])): ?>
                                            <td class="eto-result-prize"><?php echo esc_html($result['prize']); ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p><?php _e('I risultati non sono ancora disponibili.', 'eto'); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php if ($checkin_enabled): ?>
        <div class="eto-tournament-checkin">
            <h3><?php _e('Check-in', 'eto'); ?></h3>
            
            <?php
            // Mostra lo stato del check-in
            $now = current_time('timestamp');
            $checkin_start_timestamp = strtotime($checkin_start);
            $checkin_end_timestamp = strtotime($checkin_end);
            
            if ($now < $checkin_start_timestamp):
            ?>
                <p><?php _e('Il check-in non è ancora iniziato.', 'eto'); ?></p>
                <p>
                    <?php printf(__('Il check-in inizierà il %s alle %s.', 'eto'), 
                        date_i18n(get_option('date_format'), $checkin_start_timestamp),
                        date_i18n(get_option('time_format'), $checkin_start_timestamp)); ?>
                </p>
            <?php elseif ($now >= $checkin_start_timestamp && $now <= $checkin_end_timestamp): ?>
                <p><?php _e('Il check-in è attualmente aperto.', 'eto'); ?></p>
                <p>
                    <?php printf(__('Il check-in terminerà il %s alle %s.', 'eto'), 
                        date_i18n(get_option('date_format'), $checkin_end_timestamp),
                        date_i18n(get_option('time_format'), $checkin_end_timestamp)); ?>
                </p>
                
                <?php if ($user_registered): ?>
                    <a href="<?php echo esc_url(add_query_arg('action', 'checkin', get_permalink())); ?>" class="button eto-button-primary">
                        <?php _e('Vai al check-in', 'eto'); ?>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <p><?php _e('Il check-in è terminato.', 'eto'); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
