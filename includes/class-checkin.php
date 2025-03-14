<?php
/**
 * Classe per la gestione del check-in online
 *
 * Implementa un sistema di check-in online per i tornei
 *
 * @package ETO
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Checkin {

    /**
     * Istanza del database query
     *
     * @var ETO_DB_Query
     */
    private $db_query;

    /**
     * Istanza della classe di sicurezza
     *
     * @var ETO_Security
     */
    private $security;

    /**
     * Istanza della classe di notifiche
     *
     * @var ETO_Notifications
     */
    private $notifications;

    /**
     * Costruttore
     */
    public function __construct() {
        $this->db_query = new ETO_DB_Query();
        $this->security = new ETO_Security();
        $this->notifications = new ETO_Notifications();
    }

    /**
     * Inizializza la classe
     */
    public function init() {
        // Aggiungi gli shortcode
        add_shortcode('eto_checkin', [$this, 'checkin_shortcode']);
        
        // Aggiungi le azioni AJAX
        add_action('wp_ajax_eto_checkin_team', [$this, 'ajax_checkin_team']);
        add_action('wp_ajax_nopriv_eto_checkin_team', [$this, 'ajax_checkin_team']);
        
        // Aggiungi i filtri per le colonne admin
        add_filter('manage_eto_tournament_posts_columns', [$this, 'add_checkin_column']);
        add_action('manage_eto_tournament_posts_custom_column', [$this, 'manage_checkin_column'], 10, 2);
        
        // Aggiungi le meta box per i tornei
        add_action('add_meta_boxes', [$this, 'add_checkin_meta_box']);
        
        // Aggiungi le azioni cron
        add_action('eto_checkin_reminder', [$this, 'send_checkin_reminders']);
        add_action('eto_checkin_close', [$this, 'close_checkin']);
    }

    /**
     * Shortcode per il check-in
     *
     * @param array $atts Attributi dello shortcode
     * @return string HTML dello shortcode
     */
    public function checkin_shortcode($atts) {
        $atts = shortcode_atts([
            'tournament_id' => 0,
            'show_title' => 'yes',
            'show_description' => 'yes',
            'show_teams' => 'yes',
            'show_countdown' => 'yes'
        ], $atts);
        
        // Converti alcuni attributi in booleani
        $atts['show_title'] = $atts['show_title'] === 'yes';
        $atts['show_description'] = $atts['show_description'] === 'yes';
        $atts['show_teams'] = $atts['show_teams'] === 'yes';
        $atts['show_countdown'] = $atts['show_countdown'] === 'yes';
        $atts['tournament_id'] = intval($atts['tournament_id']);
        
        // Se l'ID non è specificato, usa il post corrente
        if ($atts['tournament_id'] === 0) {
            global $post;
            if ($post && $post->post_type === 'eto_tournament') {
                $atts['tournament_id'] = $post->ID;
            }
        }
        
        // Verifica che il torneo esista
        $tournament = get_post($atts['tournament_id']);
        if (!$tournament || $tournament->post_type !== 'eto_tournament') {
            return '<p>' . __('Torneo non trovato.', 'eto') . '</p>';
        }
        
        // Verifica che il check-in sia abilitato per questo torneo
        $checkin_enabled = get_post_meta($atts['tournament_id'], 'eto_checkin_enabled', true);
        if (!$checkin_enabled) {
            return '<p>' . __('Il check-in non è abilitato per questo torneo.', 'eto') . '</p>';
        }
        
        // Ottieni le informazioni sul check-in
        $checkin_start = get_post_meta($atts['tournament_id'], 'eto_checkin_start', true);
        $checkin_end = get_post_meta($atts['tournament_id'], 'eto_checkin_end', true);
        $checkin_teams = get_post_meta($atts['tournament_id'], 'eto_checkin_teams', true);
        
        if (!is_array($checkin_teams)) {
            $checkin_teams = [];
        }
        
        // Verifica lo stato del check-in
        $now = current_time('timestamp');
        $checkin_start_timestamp = strtotime($checkin_start);
        $checkin_end_timestamp = strtotime($checkin_end);
        
        $checkin_status = 'not_started';
        if ($now >= $checkin_start_timestamp && $now <= $checkin_end_timestamp) {
            $checkin_status = 'open';
        } elseif ($now > $checkin_end_timestamp) {
            $checkin_status = 'closed';
        }
        
        // Ottieni i team iscritti al torneo
        $teams = get_post_meta($atts['tournament_id'], 'eto_teams', true);
        if (!is_array($teams)) {
            $teams = [];
        }
        
        // Inizia il buffer di output
        ob_start();
        
        // Includi gli script e gli stili necessari
        wp_enqueue_script('eto-checkin', ETO_PLUGIN_URL . 'public/js/checkin.js', ['jquery'], ETO_VERSION, true);
        wp_localize_script('eto-checkin', 'eto_checkin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eto_checkin_nonce'),
            'tournament_id' => $atts['tournament_id'],
            'checkin_end' => $checkin_end_timestamp * 1000, // Converti in millisecondi per JavaScript
            'messages' => [
                'confirm' => __('Sei sicuro di voler effettuare il check-in per questo team?', 'eto'),
                'success' => __('Check-in effettuato con successo!', 'eto'),
                'error' => __('Si è verificato un errore durante il check-in. Riprova.', 'eto')
            ]
        ]);
        
        wp_enqueue_style('eto-checkin', ETO_PLUGIN_URL . 'public/css/checkin.css', [], ETO_VERSION);
        
        // Mostra il titolo
        if ($atts['show_title']) {
            echo '<h2 class="eto-checkin-title">' . esc_html($tournament->post_title) . ' - ' . __('Check-in', 'eto') . '</h2>';
        }
        
        // Mostra la descrizione
        if ($atts['show_description']) {
            echo '<div class="eto-checkin-description">';
            echo '<p>' . __('Il check-in è necessario per confermare la partecipazione al torneo.', 'eto') . '</p>';
            
            if ($checkin_status === 'not_started') {
                echo '<p>' . sprintf(__('Il check-in aprirà il %s.', 'eto'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $checkin_start_timestamp)) . '</p>';
            } elseif ($checkin_status === 'open') {
                echo '<p>' . sprintf(__('Il check-in è aperto e si chiuderà il %s.', 'eto'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $checkin_end_timestamp)) . '</p>';
            } elseif ($checkin_status === 'closed') {
                echo '<p>' . __('Il check-in è chiuso.', 'eto') . '</p>';
            }
            
            echo '</div>';
        }
        
        // Mostra il countdown
        if ($atts['show_countdown'] && $checkin_status === 'open') {
            echo '<div class="eto-checkin-countdown" data-end="' . esc_attr($checkin_end_timestamp * 1000) . '">';
            echo '<p>' . __('Tempo rimanente per il check-in:', 'eto') . ' <span class="eto-countdown-timer">00:00:00</span></p>';
            echo '</div>';
        }
        
        // Mostra i team
        if ($atts['show_teams'] && !empty($teams)) {
            echo '<div class="eto-checkin-teams">';
            echo '<h3>' . __('Team iscritti', 'eto') . '</h3>';
            
            echo '<div class="eto-team-list">';
            foreach ($teams as $team_id) {
                $team = get_post($team_id);
                if (!$team) continue;
                
                $team_checked_in = in_array($team_id, $checkin_teams);
                $team_class = $team_checked_in ? 'eto-team-checked-in' : '';
                $team_status = $team_checked_in ? __('Check-in effettuato', 'eto') : __('In attesa di check-in', 'eto');
                $team_captain_id = get_post_meta($team_id, 'eto_captain_id', true);
                $current_user_id = get_current_user_id();
                $can_checkin = ($team_captain_id == $current_user_id && $checkin_status === 'open' && !$team_checked_in);
                
                echo '<div class="eto-team-item ' . esc_attr($team_class) . '">';
                echo '<div class="eto-team-info">';
                echo '<h4>' . esc_html($team->post_title) . '</h4>';
                echo '<span class="eto-team-status">' . esc_html($team_status) . '</span>';
                echo '</div>';
                
                if ($can_checkin) {
                    echo '<div class="eto-team-actions">';
                    echo '<button class="eto-checkin-button" data-team-id="' . esc_attr($team_id) . '">' . __('Effettua check-in', 'eto') . '</button>';
                    echo '</div>';
                }
                
                echo '</div>';
            }
            echo '</div>';
            
            echo '</div>';
        }
        
        // Restituisci il contenuto
        return ob_get_clean();
    }

    /**
     * Gestisce il check-in di un team tramite AJAX
     */
    public function ajax_checkin_team() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto_checkin_nonce')) {
            wp_send_json_error(['message' => __('Errore di sicurezza. Ricarica la pagina e riprova.', 'eto')]);
        }
        
        // Verifica i parametri
        if (!isset($_POST['tournament_id']) || !isset($_POST['team_id'])) {
            wp_send_json_error(['message' => __('Parametri mancanti.', 'eto')]);
        }
        
        $tournament_id = intval($_POST['tournament_id']);
        $team_id = intval($_POST['team_id']);
        
        // Verifica che il torneo esista
        $tournament = get_post($tournament_id);
        if (!$tournament || $tournament->post_type !== 'eto_tournament') {
            wp_send_json_error(['message' => __('Torneo non trovato.', 'eto')]);
        }
        
        // Verifica che il team esista
        $team = get_post($team_id);
        if (!$team || $team->post_type !== 'eto_team') {
            wp_send_json_error(['message' => __('Team non trovato.', 'eto')]);
        }
        
        // Verifica che il check-in sia abilitato per questo torneo
        $checkin_enabled = get_post_meta($tournament_id, 'eto_checkin_enabled', true);
        if (!$checkin_enabled) {
            wp_send_json_error(['message' => __('Il check-in non è abilitato per questo torneo.', 'eto')]);
        }
        
        // Verifica che il check-in sia aperto
        $checkin_start = get_post_meta($tournament_id, 'eto_checkin_start', true);
        $checkin_end = get_post_meta($tournament_id, 'eto_checkin_end', true);
        
        $now = current_time('timestamp');
        $checkin_start_timestamp = strtotime($checkin_start);
        $checkin_end_timestamp = strtotime($checkin_end);
        
        if ($now < $checkin_start_timestamp || $now > $checkin_end_timestamp) {
            wp_send_json_error(['message' => __('Il check-in non è attualmente aperto.', 'eto')]);
        }
        
        // Verifica che il team sia iscritto al torneo
        $teams = get_post_meta($tournament_id, 'eto_teams', true);
        if (!is_array($teams) || !in_array($team_id, $teams)) {
            wp_send_json_error(['message' => __('Il team non è iscritto a questo torneo.', 'eto')]);
        }
        
        // Verifica che il team non abbia già effettuato il check-in
        $checkin_teams = get_post_meta($tournament_id, 'eto_checkin_teams', true);
        if (!is_array($checkin_teams)) {
            $checkin_teams = [];
        }
        
        if (in_array($team_id, $checkin_teams)) {
            wp_send_json_error(['message' => __('Il team ha già effettuato il check-in.', 'eto')]);
        }
        
        // Verifica che l'utente sia il capitano del team
        $team_captain_id = get_post_meta($team_id, 'eto_captain_id', true);
        $current_user_id = get_current_user_id();
        
        if ($team_captain_id != $current_user_id) {
            wp_send_json_error(['message' => __('Solo il capitano del team può effettuare il check-in.', 'eto')]);
        }
        
        // Effettua il check-in
        $checkin_teams[] = $team_id;
        update_post_meta($tournament_id, 'eto_checkin_teams', $checkin_teams);
        
        // Registra il check-in nel log
        $this->log_checkin($tournament_id, $team_id, $current_user_id);
        
        // Invia notifica
        $this->send_checkin_notification($tournament_id, $team_id);
        
        wp_send_json_success(['message' => __('Check-in effettuato con successo!', 'eto')]);
    }

    /**
     * Registra il check-in nel log
     *
     * @param int $tournament_id ID del torneo
     * @param int $team_id ID del team
     * @param int $user_id ID dell'utente
     */
    private function log_checkin($tournament_id, $team_id, $user_id) {
        global $wpdb;
        
        $table_logs = $wpdb->prefix . 'eto_logs';
        
        $wpdb->insert(
            $table_logs,
            [
                'level' => 'info',
                'message' => sprintf(__('Check-in effettuato per il team %s nel torneo %s', 'eto'), get_the_title($team_id), get_the_title($tournament_id)),
                'context' => json_encode([
                    'tournament_id' => $tournament_id,
                    'team_id' => $team_id
                ]),
                'user_id' => $user_id,
                'ip_address' => $this->security->get_ip_address(),
                'created_at' => current_time('mysql')
            ]
        );
    }

    /**
     * Invia una notifica di check-in
     *
     * @param int $tournament_id ID del torneo
     * @param int $team_id ID del team
     */
    private function send_checkin_notification($tournament_id, $team_id) {
        // Notifica all'amministratore
        $admin_email = get_option('admin_email');
        $tournament_title = get_the_title($tournament_id);
        $team_title = get_the_title($team_id);
        
        $subject = sprintf(__('[%s] Check-in effettuato per il team %s', 'eto'), $tournament_title, $team_title);
        
        $message = sprintf(__('Il team %s ha effettuato il check-in per il torneo %s.', 'eto'), $team_title, $tournament_title);
        $message .= "\n\n";
        $message .= sprintf(__('Per visualizzare i dettagli, visita: %s', 'eto'), admin_url('post.php?post=' . $tournament_id . '&action=edit'));
        
        $this->notifications->send_email($admin_email, $subject, $message);
        
        // Notifica al team
        $team_email = get_post_meta($team_id, 'eto_email', true);
        if ($team_email) {
            $subject = sprintf(__('[%s] Conferma di check-in', 'eto'), $tournament_title);
            
            $message = sprintf(__('Ciao %s,', 'eto'), $team_title);
            $message .= "\n\n";
            $message .= sprintf(__('Il tuo check-in per il torneo %s è stato registrato con successo.', 'eto'), $tournament_title);
            $message .= "\n\n";
            $message .= __('Ti preghiamo di essere online e pronto a giocare all\'orario di inizio del torneo.', 'eto');
            $message .= "\n\n";
            $message .= __('Buona fortuna!', 'eto');
            
            $this->notifications->send_email($team_email, $subject, $message);
        }
        
        // Notifica Discord se configurata
        $discord_webhook = get_post_meta($team_id, 'eto_discord_webhook', true);
        if ($discord_webhook) {
            $message = sprintf(__('Il team %s ha effettuato il check-in per il torneo %s.', 'eto'), $team_title, $tournament_title);
            
            $this->notifications->send_discord_notification($discord_webhook, $message);
        }
    }

    /**
     * Aggiunge una colonna per il check-in nella lista dei tornei in admin
     *
     * @param array $columns Colonne esistenti
     * @return array Colonne modificate
     */
    public function add_checkin_column($columns) {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            // Aggiungi la colonna dopo lo stato
            if ($key === 'eto_status') {
                $new_columns['eto_checkin'] = __('Check-in', 'eto');
            }
        }
        
        return $new_columns;
    }

    /**
     * Gestisce il contenuto della colonna check-in
     *
     * @param string $column Nome della colonna
     * @param int $post_id ID del post
     */
    public function manage_checkin_column($column, $post_id) {
        if ($column !== 'eto_checkin') {
            return;
        }
        
        $checkin_enabled = get_post_meta($post_id, 'eto_checkin_enabled', true);
        
        if (!$checkin_enabled) {
            echo '<span class="eto-checkin-disabled">' . __('Disabilitato', 'eto') . '</span>';
            return;
        }
        
        $checkin_start = get_post_meta($post_id, 'eto_checkin_start', true);
        $checkin_end = get_post_meta($post_id, 'eto_checkin_end', true);
        $checkin_teams = get_post_meta($post_id, 'eto_checkin_teams', true);
        
        if (!is_array($checkin_teams)) {
            $checkin_teams = [];
        }
        
        $teams = get_post_meta($post_id, 'eto_teams', true);
        if (!is_array($teams)) {
            $teams = [];
        }
        
        $now = current_time('timestamp');
        $checkin_start_timestamp = strtotime($checkin_start);
        $checkin_end_timestamp = strtotime($checkin_end);
        
        if ($now < $checkin_start_timestamp) {
            echo '<span class="eto-checkin-not-started">' . __('Non iniziato', 'eto') . '</span>';
        } elseif ($now >= $checkin_start_timestamp && $now <= $checkin_end_timestamp) {
            echo '<span class="eto-checkin-open">' . __('Aperto', 'eto') . '</span>';
        } else {
            echo '<span class="eto-checkin-closed">' . __('Chiuso', 'eto') . '</span>';
        }
        
        echo '<br>';
        echo sprintf(__('%d/%d team', 'eto'), count($checkin_teams), count($teams));
    }

    /**
     * Aggiunge una meta box per il check-in nella pagina di modifica del torneo
     */
    public function add_checkin_meta_box() {
        add_meta_box(
            'eto_checkin_meta_box',
            __('Check-in', 'eto'),
            [$this, 'render_checkin_meta_box'],
            'eto_tournament',
            'side',
            'default'
        );
    }

    /**
     * Renderizza la meta box per il check-in
     *
     * @param WP_Post $post Post corrente
     */
    public function render_checkin_meta_box($post) {
        // Ottieni i dati del check-in
        $checkin_enabled = get_post_meta($post->ID, 'eto_checkin_enabled', true);
        $checkin_start = get_post_meta($post->ID, 'eto_checkin_start', true);
        $checkin_end = get_post_meta($post->ID, 'eto_checkin_end', true);
        $checkin_teams = get_post_meta($post->ID, 'eto_checkin_teams', true);
        
        if (!is_array($checkin_teams)) {
            $checkin_teams = [];
        }
        
        // Ottieni i team iscritti al torneo
        $teams = get_post_meta($post->ID, 'eto_teams', true);
        if (!is_array($teams)) {
            $teams = [];
        }
        
        // Aggiungi il nonce
        wp_nonce_field('eto_checkin_meta_box', 'eto_checkin_meta_box_nonce');
        
        // Renderizza il form
        ?>
        <p>
            <label for="eto_checkin_enabled">
                <input type="checkbox" id="eto_checkin_enabled" name="eto_checkin_enabled" value="1" <?php checked($checkin_enabled, true); ?> />
                <?php _e('Abilita check-in online', 'eto'); ?>
            </label>
        </p>
        
        <p>
            <label for="eto_checkin_start"><?php _e('Inizio check-in:', 'eto'); ?></label><br>
            <input type="datetime-local" id="eto_checkin_start" name="eto_checkin_start" value="<?php echo esc_attr(str_replace(' ', 'T', $checkin_start)); ?>" class="widefat" />
        </p>
        
        <p>
            <label for="eto_checkin_end"><?php _e('Fine check-in:', 'eto'); ?></label><br>
            <input type="datetime-local" id="eto_checkin_end" name="eto_checkin_end" value="<?php echo esc_attr(str_replace(' ', 'T', $checkin_end)); ?>" class="widefat" />
        </p>
        
        <p>
            <strong><?php _e('Stato check-in:', 'eto'); ?></strong><br>
            <?php
            $now = current_time('timestamp');
            $checkin_start_timestamp = strtotime($checkin_start);
            $checkin_end_timestamp = strtotime($checkin_end);
            
            if (!$checkin_enabled) {
                echo '<span class="eto-checkin-disabled">' . __('Disabilitato', 'eto') . '</span>';
            } elseif ($now < $checkin_start_timestamp) {
                echo '<span class="eto-checkin-not-started">' . __('Non iniziato', 'eto') . '</span>';
            } elseif ($now >= $checkin_start_timestamp && $now <= $checkin_end_timestamp) {
                echo '<span class="eto-checkin-open">' . __('Aperto', 'eto') . '</span>';
            } else {
                echo '<span class="eto-checkin-closed">' . __('Chiuso', 'eto') . '</span>';
            }
            ?>
        </p>
        
        <p>
            <strong><?php _e('Team con check-in:', 'eto'); ?></strong><br>
            <?php echo sprintf(__('%d/%d team', 'eto'), count($checkin_teams), count($teams)); ?>
        </p>
        
        <?php if (!empty($teams)): ?>
        <div class="eto-checkin-team-list" style="max-height: 200px; overflow-y: auto; margin-top: 10px;">
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Team', 'eto'); ?></th>
                        <th><?php _e('Check-in', 'eto'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teams as $team_id): ?>
                    <?php $team = get_post($team_id); ?>
                    <?php if (!$team) continue; ?>
                    <tr>
                        <td><?php echo esc_html($team->post_title); ?></td>
                        <td>
                            <input type="checkbox" name="eto_checkin_teams[]" value="<?php echo esc_attr($team_id); ?>" <?php checked(in_array($team_id, $checkin_teams), true); ?> />
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <p>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'eto-checkin', 'tournament_id' => $post->ID], admin_url('admin.php'))); ?>" class="button"><?php _e('Gestisci check-in', 'eto'); ?></a>
        </p>
        <?php
    }

    /**
     * Salva i dati della meta box per il check-in
     *
     * @param int $post_id ID del post
     * @param WP_Post $post Post corrente
     * @param bool $update Se è un aggiornamento
     */
    public function save_checkin_meta_box($post_id, $post, $update) {
        // Verifica il nonce
        if (!isset($_POST['eto_checkin_meta_box_nonce']) || !wp_verify_nonce($_POST['eto_checkin_meta_box_nonce'], 'eto_checkin_meta_box')) {
            return;
        }
        
        // Verifica se è un salvataggio automatico
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Verifica i permessi
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Salva i dati
        $checkin_enabled = isset($_POST['eto_checkin_enabled']) ? true : false;
        update_post_meta($post_id, 'eto_checkin_enabled', $checkin_enabled);
        
        if (isset($_POST['eto_checkin_start'])) {
            $checkin_start = sanitize_text_field($_POST['eto_checkin_start']);
            $checkin_start = str_replace('T', ' ', $checkin_start);
            update_post_meta($post_id, 'eto_checkin_start', $checkin_start);
        }
        
        if (isset($_POST['eto_checkin_end'])) {
            $checkin_end = sanitize_text_field($_POST['eto_checkin_end']);
            $checkin_end = str_replace('T', ' ', $checkin_end);
            update_post_meta($post_id, 'eto_checkin_end', $checkin_end);
        }
        
        if (isset($_POST['eto_checkin_teams'])) {
            $checkin_teams = array_map('intval', $_POST['eto_checkin_teams']);
            update_post_meta($post_id, 'eto_checkin_teams', $checkin_teams);
        } else {
            update_post_meta($post_id, 'eto_checkin_teams', []);
        }
        
        // Pianifica i cron job per i promemoria e la chiusura del check-in
        $this->schedule_checkin_events($post_id);
    }

    /**
     * Pianifica gli eventi cron per il check-in
     *
     * @param int $post_id ID del post
     */
    private function schedule_checkin_events($post_id) {
        $checkin_enabled = get_post_meta($post_id, 'eto_checkin_enabled', true);
        
        // Rimuovi gli eventi esistenti
        wp_clear_scheduled_hook('eto_checkin_reminder', [$post_id]);
        wp_clear_scheduled_hook('eto_checkin_close', [$post_id]);
        
        if (!$checkin_enabled) {
            return;
        }
        
        $checkin_start = get_post_meta($post_id, 'eto_checkin_start', true);
        $checkin_end = get_post_meta($post_id, 'eto_checkin_end', true);
        
        $checkin_start_timestamp = strtotime($checkin_start);
        $checkin_end_timestamp = strtotime($checkin_end);
        
        // Pianifica il promemoria 1 ora prima della chiusura del check-in
        $reminder_timestamp = $checkin_end_timestamp - 3600;
        if ($reminder_timestamp > time()) {
            wp_schedule_single_event($reminder_timestamp, 'eto_checkin_reminder', [$post_id]);
        }
        
        // Pianifica la chiusura del check-in
        if ($checkin_end_timestamp > time()) {
            wp_schedule_single_event($checkin_end_timestamp, 'eto_checkin_close', [$post_id]);
        }
    }

    /**
     * Invia i promemoria per il check-in
     *
     * @param int $tournament_id ID del torneo
     */
    public function send_checkin_reminders($tournament_id) {
        $checkin_enabled = get_post_meta($tournament_id, 'eto_checkin_enabled', true);
        
        if (!$checkin_enabled) {
            return;
        }
        
        $checkin_end = get_post_meta($tournament_id, 'eto_checkin_end', true);
        $checkin_teams = get_post_meta($tournament_id, 'eto_checkin_teams', true);
        $teams = get_post_meta($tournament_id, 'eto_teams', true);
        
        if (!is_array($checkin_teams)) {
            $checkin_teams = [];
        }
        
        if (!is_array($teams)) {
            $teams = [];
        }
        
        $tournament_title = get_the_title($tournament_id);
        
        // Invia promemoria ai team che non hanno ancora effettuato il check-in
        $teams_without_checkin = array_diff($teams, $checkin_teams);
        
        foreach ($teams_without_checkin as $team_id) {
            $team = get_post($team_id);
            if (!$team) continue;
            
            $team_email = get_post_meta($team_id, 'eto_email', true);
            if (!$team_email) continue;
            
            $subject = sprintf(__('[%s] Promemoria: Check-in richiesto', 'eto'), $tournament_title);
            
            $message = sprintf(__('Ciao %s,', 'eto'), $team->post_title);
            $message .= "\n\n";
            $message .= sprintf(__('Ti ricordiamo che devi effettuare il check-in per il torneo %s entro %s.', 'eto'), $tournament_title, date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($checkin_end)));
            $message .= "\n\n";
            $message .= __('Se non effettui il check-in, non potrai partecipare al torneo.', 'eto');
            $message .= "\n\n";
            $message .= sprintf(__('Per effettuare il check-in, visita: %s', 'eto'), get_permalink($tournament_id));
            
            $this->notifications->send_email($team_email, $subject, $message);
            
            // Notifica Discord se configurata
            $discord_webhook = get_post_meta($team_id, 'eto_discord_webhook', true);
            if ($discord_webhook) {
                $message = sprintf(__('PROMEMORIA: Il team %s deve effettuare il check-in per il torneo %s entro %s.', 'eto'), $team->post_title, $tournament_title, date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($checkin_end)));
                
                $this->notifications->send_discord_notification($discord_webhook, $message);
            }
        }
    }

    /**
     * Chiude il check-in e gestisce i team che non hanno effettuato il check-in
     *
     * @param int $tournament_id ID del torneo
     */
    public function close_checkin($tournament_id) {
        $checkin_enabled = get_post_meta($tournament_id, 'eto_checkin_enabled', true);
        
        if (!$checkin_enabled) {
            return;
        }
        
        $checkin_teams = get_post_meta($tournament_id, 'eto_checkin_teams', true);
        $teams = get_post_meta($tournament_id, 'eto_teams', true);
        
        if (!is_array($checkin_teams)) {
            $checkin_teams = [];
        }
        
        if (!is_array($teams)) {
            $teams = [];
        }
        
        $tournament_title = get_the_title($tournament_id);
        
        // Identifica i team che non hanno effettuato il check-in
        $teams_without_checkin = array_diff($teams, $checkin_teams);
        
        // Rimuovi i team che non hanno effettuato il check-in dal torneo
        if (!empty($teams_without_checkin)) {
            $teams = array_diff($teams, $teams_without_checkin);
            update_post_meta($tournament_id, 'eto_teams', $teams);
            
            // Registra nel log
            global $wpdb;
            $table_logs = $wpdb->prefix . 'eto_logs';
            
            foreach ($teams_without_checkin as $team_id) {
                $team = get_post($team_id);
                if (!$team) continue;
                
                $wpdb->insert(
                    $table_logs,
                    [
                        'level' => 'warning',
                        'message' => sprintf(__('Il team %s è stato rimosso dal torneo %s per mancato check-in', 'eto'), $team->post_title, $tournament_title),
                        'context' => json_encode([
                            'tournament_id' => $tournament_id,
                            'team_id' => $team_id
                        ]),
                        'user_id' => 0,
                        'ip_address' => '',
                        'created_at' => current_time('mysql')
                    ]
                );
                
                // Notifica il team
                $team_email = get_post_meta($team_id, 'eto_email', true);
                if ($team_email) {
                    $subject = sprintf(__('[%s] Rimozione dal torneo per mancato check-in', 'eto'), $tournament_title);
                    
                    $message = sprintf(__('Ciao %s,', 'eto'), $team->post_title);
                    $message .= "\n\n";
                    $message .= sprintf(__('Ci dispiace informarti che sei stato rimosso dal torneo %s per non aver effettuato il check-in entro il termine stabilito.', 'eto'), $tournament_title);
                    $message .= "\n\n";
                    $message .= __('Se ritieni che ci sia stato un errore, contatta gli organizzatori del torneo.', 'eto');
                    
                    $this->notifications->send_email($team_email, $subject, $message);
                }
                
                // Notifica Discord se configurata
                $discord_webhook = get_post_meta($team_id, 'eto_discord_webhook', true);
                if ($discord_webhook) {
                    $message = sprintf(__('Il team %s è stato rimosso dal torneo %s per mancato check-in.', 'eto'), $team->post_title, $tournament_title);
                    
                    $this->notifications->send_discord_notification($discord_webhook, $message);
                }
            }
            
            // Notifica all'amministratore
            $admin_email = get_option('admin_email');
            $subject = sprintf(__('[%s] Check-in chiuso - Team rimossi', 'eto'), $tournament_title);
            
            $message = sprintf(__('Il check-in per il torneo %s è stato chiuso.', 'eto'), $tournament_title);
            $message .= "\n\n";
            $message .= sprintf(__('%d team sono stati rimossi per mancato check-in:', 'eto'), count($teams_without_checkin));
            $message .= "\n";
            
            foreach ($teams_without_checkin as $team_id) {
                $team = get_post($team_id);
                if (!$team) continue;
                
                $message .= '- ' . $team->post_title . "\n";
            }
            
            $message .= "\n";
            $message .= sprintf(__('Per visualizzare i dettagli, visita: %s', 'eto'), admin_url('post.php?post=' . $tournament_id . '&action=edit'));
            
            $this->notifications->send_email($admin_email, $subject, $message);
        } else {
            // Notifica all'amministratore
            $admin_email = get_option('admin_email');
            $subject = sprintf(__('[%s] Check-in chiuso - Tutti i team hanno effettuato il check-in', 'eto'), $tournament_title);
            
            $message = sprintf(__('Il check-in per il torneo %s è stato chiuso.', 'eto'), $tournament_title);
            $message .= "\n\n";
            $message .= __('Tutti i team hanno effettuato il check-in con successo.', 'eto');
            $message .= "\n\n";
            $message .= sprintf(__('Per visualizzare i dettagli, visita: %s', 'eto'), admin_url('post.php?post=' . $tournament_id . '&action=edit'));
            
            $this->notifications->send_email($admin_email, $subject, $message);
        }
    }

    /**
     * Aggiunge una pagina di amministrazione per la gestione del check-in
     */
    public function add_admin_page() {
        add_submenu_page(
            'eto-dashboard',
            __('Gestione Check-in', 'eto'),
            __('Check-in', 'eto'),
            'manage_tournaments',
            'eto-checkin',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Renderizza la pagina di amministrazione per la gestione del check-in
     */
    public function render_admin_page() {
        // Verifica se è stato specificato un torneo
        $tournament_id = isset($_GET['tournament_id']) ? intval($_GET['tournament_id']) : 0;
        
        if ($tournament_id) {
            $this->render_tournament_checkin_page($tournament_id);
        } else {
            $this->render_tournaments_list_page();
        }
    }

    /**
     * Renderizza la pagina di amministrazione per la gestione del check-in di un torneo specifico
     *
     * @param int $tournament_id ID del torneo
     */
    private function render_tournament_checkin_page($tournament_id) {
        $tournament = get_post($tournament_id);
        
        if (!$tournament || $tournament->post_type !== 'eto_tournament') {
            echo '<div class="wrap"><h1>' . __('Gestione Check-in', 'eto') . '</h1>';
            echo '<div class="notice notice-error"><p>' . __('Torneo non trovato.', 'eto') . '</p></div>';
            echo '</div>';
            return;
        }
        
        $checkin_enabled = get_post_meta($tournament_id, 'eto_checkin_enabled', true);
        $checkin_start = get_post_meta($tournament_id, 'eto_checkin_start', true);
        $checkin_end = get_post_meta($tournament_id, 'eto_checkin_end', true);
        $checkin_teams = get_post_meta($tournament_id, 'eto_checkin_teams', true);
        
        if (!is_array($checkin_teams)) {
            $checkin_teams = [];
        }
        
        $teams = get_post_meta($tournament_id, 'eto_teams', true);
        if (!is_array($teams)) {
            $teams = [];
        }
        
        $now = current_time('timestamp');
        $checkin_start_timestamp = strtotime($checkin_start);
        $checkin_end_timestamp = strtotime($checkin_end);
        
        $checkin_status = 'not_started';
        if ($now >= $checkin_start_timestamp && $now <= $checkin_end_timestamp) {
            $checkin_status = 'open';
        } elseif ($now > $checkin_end_timestamp) {
            $checkin_status = 'closed';
        }
        
        // Gestisci le azioni
        if (isset($_POST['eto_checkin_action']) && isset($_POST['eto_checkin_nonce']) && wp_verify_nonce($_POST['eto_checkin_nonce'], 'eto_checkin_action')) {
            $action = $_POST['eto_checkin_action'];
            
            if ($action === 'update_settings') {
                // Aggiorna le impostazioni del check-in
                $checkin_enabled = isset($_POST['eto_checkin_enabled']) ? true : false;
                update_post_meta($tournament_id, 'eto_checkin_enabled', $checkin_enabled);
                
                if (isset($_POST['eto_checkin_start'])) {
                    $checkin_start = sanitize_text_field($_POST['eto_checkin_start']);
                    $checkin_start = str_replace('T', ' ', $checkin_start);
                    update_post_meta($tournament_id, 'eto_checkin_start', $checkin_start);
                }
                
                if (isset($_POST['eto_checkin_end'])) {
                    $checkin_end = sanitize_text_field($_POST['eto_checkin_end']);
                    $checkin_end = str_replace('T', ' ', $checkin_end);
                    update_post_meta($tournament_id, 'eto_checkin_end', $checkin_end);
                }
                
                // Pianifica i cron job per i promemoria e la chiusura del check-in
                $this->schedule_checkin_events($tournament_id);
                
                echo '<div class="notice notice-success"><p>' . __('Impostazioni aggiornate con successo.', 'eto') . '</p></div>';
            } elseif ($action === 'update_teams') {
                // Aggiorna i team con check-in
                if (isset($_POST['eto_checkin_teams'])) {
                    $checkin_teams = array_map('intval', $_POST['eto_checkin_teams']);
                    update_post_meta($tournament_id, 'eto_checkin_teams', $checkin_teams);
                } else {
                    update_post_meta($tournament_id, 'eto_checkin_teams', []);
                }
                
                echo '<div class="notice notice-success"><p>' . __('Team aggiornati con successo.', 'eto') . '</p></div>';
            } elseif ($action === 'send_reminders') {
                // Invia promemoria ai team
                $this->send_checkin_reminders($tournament_id);
                
                echo '<div class="notice notice-success"><p>' . __('Promemoria inviati con successo.', 'eto') . '</p></div>';
            } elseif ($action === 'close_checkin') {
                // Chiudi il check-in
                $this->close_checkin($tournament_id);
                
                echo '<div class="notice notice-success"><p>' . __('Check-in chiuso con successo.', 'eto') . '</p></div>';
            }
            
            // Ricarica i dati
            $checkin_enabled = get_post_meta($tournament_id, 'eto_checkin_enabled', true);
            $checkin_start = get_post_meta($tournament_id, 'eto_checkin_start', true);
            $checkin_end = get_post_meta($tournament_id, 'eto_checkin_end', true);
            $checkin_teams = get_post_meta($tournament_id, 'eto_checkin_teams', true);
            
            if (!is_array($checkin_teams)) {
                $checkin_teams = [];
            }
        }
        
        // Renderizza la pagina
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($tournament->post_title); ?> - <?php _e('Gestione Check-in', 'eto'); ?></h1>
            
            <div class="eto-admin-tabs">
                <a href="#settings" class="eto-tab active"><?php _e('Impostazioni', 'eto'); ?></a>
                <a href="#teams" class="eto-tab"><?php _e('Team', 'eto'); ?></a>
                <a href="#actions" class="eto-tab"><?php _e('Azioni', 'eto'); ?></a>
            </div>
            
            <div class="eto-admin-tab-content" id="settings">
                <h2><?php _e('Impostazioni Check-in', 'eto'); ?></h2>
                
                <form method="post" action="">
                    <?php wp_nonce_field('eto_checkin_action', 'eto_checkin_nonce'); ?>
                    <input type="hidden" name="eto_checkin_action" value="update_settings">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Abilita check-in', 'eto'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="eto_checkin_enabled" value="1" <?php checked($checkin_enabled, true); ?> />
                                    <?php _e('Abilita check-in online per questo torneo', 'eto'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Inizio check-in', 'eto'); ?></th>
                            <td>
                                <input type="datetime-local" name="eto_checkin_start" value="<?php echo esc_attr(str_replace(' ', 'T', $checkin_start)); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Fine check-in', 'eto'); ?></th>
                            <td>
                                <input type="datetime-local" name="eto_checkin_end" value="<?php echo esc_attr(str_replace(' ', 'T', $checkin_end)); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Stato check-in', 'eto'); ?></th>
                            <td>
                                <?php
                                if (!$checkin_enabled) {
                                    echo '<span class="eto-checkin-disabled">' . __('Disabilitato', 'eto') . '</span>';
                                } elseif ($checkin_status === 'not_started') {
                                    echo '<span class="eto-checkin-not-started">' . __('Non iniziato', 'eto') . '</span>';
                                } elseif ($checkin_status === 'open') {
                                    echo '<span class="eto-checkin-open">' . __('Aperto', 'eto') . '</span>';
                                } else {
                                    echo '<span class="eto-checkin-closed">' . __('Chiuso', 'eto') . '</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Shortcode', 'eto'); ?></th>
                            <td>
                                <code>[eto_checkin tournament_id="<?php echo esc_attr($tournament_id); ?>"]</code>
                                <p class="description"><?php _e('Usa questo shortcode per mostrare il form di check-in in una pagina.', 'eto'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Salva impostazioni', 'eto'); ?>">
                    </p>
                </form>
            </div>
            
            <div class="eto-admin-tab-content" id="teams" style="display: none;">
                <h2><?php _e('Team Check-in', 'eto'); ?></h2>
                
                <p>
                    <?php echo sprintf(__('Team con check-in: %d/%d', 'eto'), count($checkin_teams), count($teams)); ?>
                </p>
                
                <?php if (!empty($teams)): ?>
                <form method="post" action="">
                    <?php wp_nonce_field('eto_checkin_action', 'eto_checkin_nonce'); ?>
                    <input type="hidden" name="eto_checkin_action" value="update_teams">
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col" class="check-column">
                                    <input type="checkbox" id="eto-checkin-select-all" />
                                </th>
                                <th scope="col"><?php _e('Team', 'eto'); ?></th>
                                <th scope="col"><?php _e('Capitano', 'eto'); ?></th>
                                <th scope="col"><?php _e('Email', 'eto'); ?></th>
                                <th scope="col"><?php _e('Check-in', 'eto'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teams as $team_id): ?>
                            <?php
                            $team = get_post($team_id);
                            if (!$team) continue;
                            
                            $team_captain_id = get_post_meta($team_id, 'eto_captain_id', true);
                            $team_captain = get_userdata($team_captain_id);
                            $team_email = get_post_meta($team_id, 'eto_email', true);
                            $team_checked_in = in_array($team_id, $checkin_teams);
                            ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="eto_checkin_teams[]" value="<?php echo esc_attr($team_id); ?>" <?php checked($team_checked_in, true); ?> />
                                </th>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($team_id)); ?>"><?php echo esc_html($team->post_title); ?></a>
                                </td>
                                <td>
                                    <?php echo $team_captain ? esc_html($team_captain->display_name) : __('Nessun capitano', 'eto'); ?>
                                </td>
                                <td>
                                    <?php echo esc_html($team_email); ?>
                                </td>
                                <td>
                                    <?php echo $team_checked_in ? __('Effettuato', 'eto') : __('Non effettuato', 'eto'); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Aggiorna team', 'eto'); ?>">
                    </p>
                </form>
                <?php else: ?>
                <p><?php _e('Nessun team iscritto a questo torneo.', 'eto'); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="eto-admin-tab-content" id="actions" style="display: none;">
                <h2><?php _e('Azioni Check-in', 'eto'); ?></h2>
                
                <div class="eto-admin-action-card">
                    <h3><?php _e('Invia promemoria', 'eto'); ?></h3>
                    <p><?php _e('Invia un promemoria a tutti i team che non hanno ancora effettuato il check-in.', 'eto'); ?></p>
                    <form method="post" action="">
                        <?php wp_nonce_field('eto_checkin_action', 'eto_checkin_nonce'); ?>
                        <input type="hidden" name="eto_checkin_action" value="send_reminders">
                        <p>
                            <input type="submit" name="submit" class="button" value="<?php _e('Invia promemoria', 'eto'); ?>" <?php disabled($checkin_status !== 'open'); ?>>
                        </p>
                    </form>
                </div>
                
                <div class="eto-admin-action-card">
                    <h3><?php _e('Chiudi check-in', 'eto'); ?></h3>
                    <p><?php _e('Chiudi il check-in e rimuovi i team che non hanno effettuato il check-in.', 'eto'); ?></p>
                    <form method="post" action="">
                        <?php wp_nonce_field('eto_checkin_action', 'eto_checkin_nonce'); ?>
                        <input type="hidden" name="eto_checkin_action" value="close_checkin">
                        <p>
                            <input type="submit" name="submit" class="button" value="<?php _e('Chiudi check-in', 'eto'); ?>" <?php disabled($checkin_status !== 'open'); ?> onclick="return confirm('<?php _e('Sei sicuro di voler chiudere il check-in? I team che non hanno effettuato il check-in saranno rimossi dal torneo.', 'eto'); ?>')">
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Gestisci le tab
            $('.eto-tab').on('click', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                
                $('.eto-tab').removeClass('active');
                $(this).addClass('active');
                
                $('.eto-admin-tab-content').hide();
                $(target).show();
            });
            
            // Gestisci il select all
            $('#eto-checkin-select-all').on('change', function() {
                $('input[name="eto_checkin_teams[]"]').prop('checked', $(this).prop('checked'));
            });
        });
        </script>
        
        <style>
        .eto-admin-tabs {
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
        }
        
        .eto-tab {
            display: inline-block;
            padding: 10px 15px;
            margin-bottom: -1px;
            text-decoration: none;
            color: #555;
            border: 1px solid transparent;
        }
        
        .eto-tab.active {
            border: 1px solid #ccc;
            border-bottom-color: #f1f1f1;
            background: #f1f1f1;
        }
        
        .eto-admin-action-card {
            background: #fff;
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
            max-width: 500px;
        }
        
        .eto-checkin-disabled {
            color: #999;
        }
        
        .eto-checkin-not-started {
            color: #0073aa;
        }
        
        .eto-checkin-open {
            color: #46b450;
        }
        
        .eto-checkin-closed {
            color: #dc3232;
        }
        </style>
        <?php
    }

    /**
     * Renderizza la pagina di amministrazione con la lista dei tornei
     */
    private function render_tournaments_list_page() {
        // Ottieni i tornei
        $args = [
            'post_type' => 'eto_tournament',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ];
        
        $tournaments = get_posts($args);
        
        // Renderizza la pagina
        ?>
        <div class="wrap">
            <h1><?php _e('Gestione Check-in', 'eto'); ?></h1>
            
            <?php if (!empty($tournaments)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php _e('Torneo', 'eto'); ?></th>
                        <th scope="col"><?php _e('Stato check-in', 'eto'); ?></th>
                        <th scope="col"><?php _e('Team', 'eto'); ?></th>
                        <th scope="col"><?php _e('Azioni', 'eto'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tournaments as $tournament): ?>
                    <?php
                    $checkin_enabled = get_post_meta($tournament->ID, 'eto_checkin_enabled', true);
                    $checkin_start = get_post_meta($tournament->ID, 'eto_checkin_start', true);
                    $checkin_end = get_post_meta($tournament->ID, 'eto_checkin_end', true);
                    $checkin_teams = get_post_meta($tournament->ID, 'eto_checkin_teams', true);
                    $teams = get_post_meta($tournament->ID, 'eto_teams', true);
                    
                    if (!is_array($checkin_teams)) {
                        $checkin_teams = [];
                    }
                    
                    if (!is_array($teams)) {
                        $teams = [];
                    }
                    
                    $now = current_time('timestamp');
                    $checkin_start_timestamp = strtotime($checkin_start);
                    $checkin_end_timestamp = strtotime($checkin_end);
                    
                    $checkin_status = 'not_started';
                    if (!$checkin_enabled) {
                        $checkin_status = 'disabled';
                    } elseif ($now >= $checkin_start_timestamp && $now <= $checkin_end_timestamp) {
                        $checkin_status = 'open';
                    } elseif ($now > $checkin_end_timestamp) {
                        $checkin_status = 'closed';
                    }
                    
                    $checkin_status_text = '';
                    switch ($checkin_status) {
                        case 'disabled':
                            $checkin_status_text = '<span class="eto-checkin-disabled">' . __('Disabilitato', 'eto') . '</span>';
                            break;
                        case 'not_started':
                            $checkin_status_text = '<span class="eto-checkin-not-started">' . __('Non iniziato', 'eto') . '</span>';
                            break;
                        case 'open':
                            $checkin_status_text = '<span class="eto-checkin-open">' . __('Aperto', 'eto') . '</span>';
                            break;
                        case 'closed':
                            $checkin_status_text = '<span class="eto-checkin-closed">' . __('Chiuso', 'eto') . '</span>';
                            break;
                    }
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(get_edit_post_link($tournament->ID)); ?>"><?php echo esc_html($tournament->post_title); ?></a>
                        </td>
                        <td>
                            <?php echo $checkin_status_text; ?>
                            <?php if ($checkin_status !== 'disabled'): ?>
                            <br>
                            <small>
                                <?php echo sprintf(__('Inizio: %s', 'eto'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $checkin_start_timestamp)); ?>
                                <br>
                                <?php echo sprintf(__('Fine: %s', 'eto'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $checkin_end_timestamp)); ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo sprintf(__('%d/%d team', 'eto'), count($checkin_teams), count($teams)); ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg(['page' => 'eto-checkin', 'tournament_id' => $tournament->ID], admin_url('admin.php'))); ?>" class="button"><?php _e('Gestisci', 'eto'); ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p><?php _e('Nessun torneo trovato.', 'eto'); ?></p>
            <?php endif; ?>
        </div>
        
        <style>
        .eto-checkin-disabled {
            color: #999;
        }
        
        .eto-checkin-not-started {
            color: #0073aa;
        }
        
        .eto-checkin-open {
            color: #46b450;
        }
        
        .eto-checkin-closed {
            color: #dc3232;
        }
        </style>
        <?php
    }
}