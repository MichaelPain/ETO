<?php
/**
 * Widget per il plugin ETO
 * 
 * @package ETO
 * @since 2.5.4
 */

// Previeni l'accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widget per visualizzare i tornei recenti
 */
class ETO_Recent_Tournaments_Widget extends WP_Widget {
    
    /**
     * Costruttore
     */
    public function __construct() {
        parent::__construct(
            'eto_recent_tournaments',
            __('ETO - Tornei Recenti', 'eto'),
            array(
                'description' => __('Visualizza un elenco dei tornei più recenti.', 'eto')
            )
        );
    }
    
    /**
     * Front-end del widget
     *
     * @param array $args Argomenti del widget
     * @param array $instance Istanza del widget
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $limit = !empty($instance['limit']) ? absint($instance['limit']) : 5;
        $status = !empty($instance['status']) ? $instance['status'] : 'active';
        
        // Ottieni i tornei
        global $wpdb;
        $table = $wpdb->prefix . 'eto_tournaments';
        
        $query = "SELECT * FROM $table WHERE status = %s ORDER BY created_at DESC LIMIT %d";
        $tournaments = $wpdb->get_results($wpdb->prepare($query, $status, $limit));
        
        if (!empty($tournaments)) {
            echo '<ul class="eto-recent-tournaments">';
            
            foreach ($tournaments as $tournament) {
                echo '<li>';
                echo '<a href="' . esc_url(eto_get_tournament_url($tournament->id)) . '">' . esc_html($tournament->name) . '</a>';
                echo '<span class="eto-tournament-date">' . esc_html(eto_format_date($tournament->start_date, 'date')) . '</span>';
                echo '</li>';
            }
            
            echo '</ul>';
        } else {
            echo '<p>' . __('Nessun torneo trovato.', 'eto') . '</p>';
        }
        
        echo $args['after_widget'];
    }
    
    /**
     * Back-end del widget
     *
     * @param array $instance Istanza del widget
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Tornei Recenti', 'eto');
        $limit = !empty($instance['limit']) ? absint($instance['limit']) : 5;
        $status = !empty($instance['status']) ? $instance['status'] : 'active';
        
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Titolo:', 'eto'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>"><?php _e('Numero di tornei da visualizzare:', 'eto'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('limit')); ?>" name="<?php echo esc_attr($this->get_field_name('limit')); ?>" type="number" step="1" min="1" value="<?php echo esc_attr($limit); ?>" size="3">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('status')); ?>"><?php _e('Stato dei tornei:', 'eto'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('status')); ?>" name="<?php echo esc_attr($this->get_field_name('status')); ?>">
                <option value="active" <?php selected($status, 'active'); ?>><?php _e('Attivi', 'eto'); ?></option>
                <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('In attesa', 'eto'); ?></option>
                <option value="completed" <?php selected($status, 'completed'); ?>><?php _e('Completati', 'eto'); ?></option>
                <option value="all" <?php selected($status, 'all'); ?>><?php _e('Tutti', 'eto'); ?></option>
            </select>
        </p>
        <?php
    }
    
    /**
     * Salvataggio delle opzioni del widget
     *
     * @param array $new_instance Nuove opzioni
     * @param array $old_instance Vecchie opzioni
     * @return array Opzioni aggiornate
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? absint($new_instance['limit']) : 5;
        $instance['status'] = (!empty($new_instance['status'])) ? sanitize_text_field($new_instance['status']) : 'active';
        
        return $instance;
    }
}

/**
 * Widget per visualizzare i team recenti
 */
class ETO_Recent_Teams_Widget extends WP_Widget {
    
    /**
     * Costruttore
     */
    public function __construct() {
        parent::__construct(
            'eto_recent_teams',
            __('ETO - Team Recenti', 'eto'),
            array(
                'description' => __('Visualizza un elenco dei team più recenti.', 'eto')
            )
        );
    }
    
    /**
     * Front-end del widget
     *
     * @param array $args Argomenti del widget
     * @param array $instance Istanza del widget
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $limit = !empty($instance['limit']) ? absint($instance['limit']) : 5;
        $game = !empty($instance['game']) ? $instance['game'] : '';
        
        // Ottieni i team
        global $wpdb;
        $table = $wpdb->prefix . 'eto_teams';
        
        $query = "SELECT * FROM $table WHERE 1=1";
        $query_args = array();
        
        if (!empty($game) && $game !== 'all') {
            $query .= " AND game = %s";
            $query_args[] = $game;
        }
        
        $query .= " ORDER BY created_at DESC LIMIT %d";
        $query_args[] = $limit;
        
        $teams = $wpdb->get_results($wpdb->prepare($query, $query_args));
        
        if (!empty($teams)) {
            echo '<ul class="eto-recent-teams">';
            
            foreach ($teams as $team) {
                echo '<li>';
                echo '<a href="' . esc_url(eto_get_team_url($team->id)) . '">' . esc_html($team->name) . '</a>';
                echo '<span class="eto-team-game">' . esc_html(eto_get_game_name($team->game)) . '</span>';
                echo '</li>';
            }
            
            echo '</ul>';
        } else {
            echo '<p>' . __('Nessun team trovato.', 'eto') . '</p>';
        }
        
        echo $args['after_widget'];
    }
    
    /**
     * Back-end del widget
     *
     * @param array $instance Istanza del widget
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Team Recenti', 'eto');
        $limit = !empty($instance['limit']) ? absint($instance['limit']) : 5;
        $game = !empty($instance['game']) ? $instance['game'] : 'all';
        
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Titolo:', 'eto'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>"><?php _e('Numero di team da visualizzare:', 'eto'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('limit')); ?>" name="<?php echo esc_attr($this->get_field_name('limit')); ?>" type="number" step="1" min="1" value="<?php echo esc_attr($limit); ?>" size="3">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('game')); ?>"><?php _e('Gioco:', 'eto'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('game')); ?>" name="<?php echo esc_attr($this->get_field_name('game')); ?>">
                <option value="all" <?php selected($game, 'all'); ?>><?php _e('Tutti i giochi', 'eto'); ?></option>
                <?php
                $games = eto_get_games();
                foreach ($games as $game_slug => $game_name) {
                    echo '<option value="' . esc_attr($game_slug) . '" ' . selected($game, $game_slug, false) . '>' . esc_html($game_name) . '</option>';
                }
                ?>
            </select>
        </p>
        <?php
    }
    
    /**
     * Salvataggio delle opzioni del widget
     *
     * @param array $new_instance Nuove opzioni
     * @param array $old_instance Vecchie opzioni
     * @return array Opzioni aggiornate
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? absint($new_instance['limit']) : 5;
        $instance['game'] = (!empty($new_instance['game'])) ? sanitize_text_field($new_instance['game']) : 'all';
        
        return $instance;
    }
}

/**
 * Registra i widget
 */
function eto_register_widgets() {
    register_widget('ETO_Recent_Tournaments_Widget');
    register_widget('ETO_Recent_Teams_Widget');
}
add_action('widgets_init', 'eto_register_widgets');
