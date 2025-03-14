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
        
        foreach <response clipped><NOTE>To save on context only part of this file has been shown to you. You should retry this tool after you have searched inside the file with `grep -n` in order to find the line numbers of what you are looking for.</NOTE>