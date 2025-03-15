<?php
/**
 * Classe unificata per la gestione del check-in dei partecipanti
 * 
 * Implementa un sistema completo di check-in online per i tornei,
 * combinando funzionalità frontend e backend
 * 
 * @package ETO
 * @since 2.5.3
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Checkin {
    
    /**
     * Istanza della classe di query al database
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
        
        // Inizializza le classi di supporto se disponibili
        if (class_exists('ETO_Security')) {
            $this->security = new ETO_Security();
        }
        
        if (class_exists('ETO_Notifications')) {
            $this->notifications = ETO_Notifications::get_instance();
        }
    }
    
    /**
     * Inizializza la classe
     */
    public function init() {
        // Registra gli shortcode
        add_shortcode('eto_checkin', [$this, 'checkin_shortcode']);
        add_shortcode('eto_checkin_form', [$this, 'shortcode_checkin_form']);
        
        // Registra gli endpoint AJAX
        add_action('wp_ajax_eto_checkin_team', [$this, 'ajax_checkin_team']);
        add_action('wp_ajax_nopriv_eto_checkin_team', [$this, 'ajax_checkin_team']);
        add_action('wp_ajax_eto_checkin_individual', [$this, 'ajax_checkin_individual']);
        
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
        
        // Carica il template
        ob_start();
        include(ETO_PLUGIN_DIR . '/templates/checkin.php');
        return ob_get_clean();
    }
    
    /**
     * Shortcode per il form di check-in
     * 
     * @param array $atts Attributi dello shortcode
     * @return string HTML generato
     */
    public function shortcode_checkin_form($atts) {
        $atts = shortcode_atts(array(
            'tournament_id' => 0
        ), $atts, 'eto_checkin_form');
        
        if (empty($atts['tournament_id'])) {
            return '<p>' . __('ID torneo non specificato', 'eto') . '</p>';
        }
        
        // Ottieni il torneo
        $tournament_id = intval($atts['tournament_id']);
        $tournament = class_exists('ETO_Tournament_Model') ? 
            new ETO_Tournament_Model($tournament_id) : 
            get_post($tournament_id);
        
        if (!$tournament) {
            return '<p>' . __('Torneo non trovato', 'eto') . '</p>';
        }
        
        // Verifica se il check-in è aperto
        $checkin_open = $this->is_checkin_open($tournament_id);
        
        if (!$checkin_open) {
            return '<p>' . __('Il check-in non è aperto', 'eto') . '</p>';
        }
        
        // Verifica se è un torneo individuale
        $is_individual = class_exists('ETO_Tournament_Model') ? 
            $tournament->get_meta('is_individual', false) : 
            get_post_meta($tournament_id, 'eto_is_individual', true);
        
        // Carica il template appropriato
        ob_start();
        if ($is_individual) {
            include ETO_PLUGIN_DIR . '/templates/frontend/tournaments/checkin-individual.php';
        } else {
            include ETO_PLUGIN_DIR . '/templates/frontend/tournaments/checkin-team.php';
        }
        return ob_get_clean();
    }
    
    /**
     * Gestisce il check-in di un team via AJAX
     */
    public function ajax_checkin_team() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-checkin-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i parametri
        if (!isset($_POST['tournament_id']) || !isset($_POST['team_id'])) {
            wp_send_json_error(array('message' => __('Parametri mancanti', 'eto')));
        }
        
        $tournament_id = intval($_POST['tournament_id']);
        $team_id = intval($_POST['team_id']);
        $user_id = get_current_user_id();
        
        // Verifica che l'utente sia il capitano del team
        if (class_exists('ETO_Team_Model')) {
            $team = new ETO_Team_Model($team_id);
            if (!$team || $team->get('captain_id') != $user_id) {
                wp_send_json_error(array('message' => __('Non sei il capitano di questo team', 'eto')));
            }
            
            // Verifica che il team sia registrato al torneo
            $tournament = new ETO_Tournament_Model($tournament_id);
            if (!$tournament || !$tournament->is_team_registered($team_id)) {
                wp_send_json_error(array('message' => __('Il team non è registrato a questo torneo', 'eto')));
            }
        } else {
            // Fallback per la verifica del capitano
            global $wpdb;
            $table = $wpdb->prefix . 'eto_teams';
            $captain_id = $wpdb->get_var($wpdb->prepare(
                "SELECT captain_id FROM $table WHERE id = %d",
                $team_id
            ));
            
            if ($captain_id != $user_id) {
                wp_send_json_error(array('message' => __('Non sei il capitano di questo team', 'eto')));
            }
            
            // Fallback per la verifica della registrazione
            $table = $wpdb->prefix . 'eto_tournament_entries';
            $is_registered = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE tournament_id = %d AND team_id = %d",
                $tournament_id,
                $team_id
            ));
            
            if (!$is_registered) {
                wp_send_json_error(array('message' => __('Il team non è registrato a questo torneo', 'eto')));
            }
        }
        
        // Verifica che il check-in sia aperto
        if (!$this->is_checkin_open($tournament_id)) {
            wp_send_json_error(array('message' => __('Il check-in non è aperto', 'eto')));
        }
        
        // Esegui il check-in
        $result = $this->checkin_team($tournament_id, $team_id);
        
        if ($result) {
            // Invia notifica se la classe è disponibile
            if (isset($this->notifications)) {
                $this->notifications->send_team_checkin_notification($tournament_id, $team_id);
            }
            
            wp_send_json_success(array('message' => __('Check-in completato con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante il check-in', 'eto')));
        }
    }
    
    /**
     * Gestisce il check-in di un partecipante individuale via AJAX
     */
    public function ajax_checkin_individual() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-checkin-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i parametri
        if (!isset($_POST['tournament_id'])) {
            wp_send_json_error(array('message' => __('Parametri mancanti', 'eto')));
        }
        
        $tournament_id = intval($_POST['tournament_id']);
        $user_id = get_current_user_id();
        
        // Verifica che l'utente sia registrato al torneo
        if (class_exists('ETO_Tournament_Model')) {
            $tournament = new ETO_Tournament_Model($tournament_id);
            if (!$tournament || !$this->is_user_registered($tournament_id, $user_id)) {
                wp_send_json_error(array('message' => __('Non sei registrato a questo torneo', 'eto')));
            }
        } else {
            // Fallback per la verifica della registrazione
            if (!$this->is_user_registered($tournament_id, $user_id)) {
                wp_send_json_error(array('message' => __('Non sei registrato a questo torneo', 'eto')));
            }
        }
        
        // Verifica che il check-in sia aperto
        if (!$this->is_checkin_open($tournament_id)) {
            wp_send_json_error(array('message' => __('Il check-in non è aperto', 'eto')));
        }
        
        // Esegui il check-in
        $result = $this->checkin_individual($tournament_id, $user_id);
        
        if ($result) {
            // Invia notifica se la classe è disponibile
            if (isset($this->notifications)) {
                $this->notifications->send_individual_checkin_notification($tournament_id, $user_id);
            }
            
            wp_send_json_success(array('message' => __('Check-in completato con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante il check-in', 'eto')));
        }
    }
    
    /**
     * Verifica se il check-in è aperto per un torneo
     * 
     * @param int $tournament_id ID del torneo
     * @return bool True se il check-in è aperto
     */
    public function is_checkin_open($tournament_id) {
        if (class_exists('ETO_Tournament_Model')) {
            $tournament = new ETO_Tournament_Model($tournament_id);
            
            if (!$tournament) {
                return false;
            }
            
            $checkin_start = $tournament->get_meta('checkin_start', '');
            $checkin_end = $tournament->get_meta('checkin_end', '');
        } else {
            // Fallback per ottenere i dati del check-in
            $checkin_start = get_post_meta($tournament_id, 'eto_checkin_start', true);
            $checkin_end = get_post_meta($tournament_id, 'eto_checkin_end', true);
        }
        
        $now = current_time('mysql');
        
        if (empty($checkin_start) || empty($checkin_end)) {
            return false;
        }
        
        return ($now >= $checkin_start && $now <= $checkin_end);
    }
    
    /**
     * Esegue il check-in di un team
     * 
     * @param int $tournament_id ID del torneo
     * @param int $team_id ID del team
     * @return bool True se il check-in è riuscito
     */
    public function checkin_team($tournament_id, $team_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'eto_tournament_entries';
        
        return $wpdb->update(
            $table,
            array('checked_in' => 1),
            array('tournament_id' => $tournament_id, 'team_id' => $team_id),
            array('%d'),
            array('%d', '%d')
        ) !== false;
    }
    
    /**
     * Esegue il check-in di un partecipante individuale
     * 
     * @param int $tournament_id ID del torneo
     * @param int $user_id ID dell'utente
     * @return bool True se il check-in è riuscito
     */
    public function checkin_individual($tournament_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'eto_individual_participants';
        
        return $wpdb->update(
            $table,
            array('checked_in' => 1),
            array('tournament_id' => $tournament_id, 'user_id' => $user_id),
            array('%d'),
            array('%d', '%d')
        ) !== false;
    }
    
    /**
     * Verifica se un utente è registrato a un torneo individuale
     * 
     * @param int $tournament_id ID del torneo
     * @param int $user_id ID dell'utente
     * @return bool True se l'utente è registrato
     */
    public function is_user_registered($tournament_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'eto_individual_participants';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE tournament_id = %d AND user_id = %d",
            $tournament_id,
            $user_id
        ));
        
        return $result > 0;
    }
    
    /**
     * Aggiunge una colonna per il check-in nella lista dei tornei in admin
     *
     * @param array $columns Colonne esistenti
     * @return array Colonne aggiornate
     */
    public function add_checkin_column($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            // Aggiungi la colonna dopo la data
            if ($key === 'date') {
                $new_columns['checkin'] = __('Check-in', 'eto');
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
        if ($column !== 'checkin') {
            return;
        }
        
        $checkin_enabled = get_post_meta($post_id, 'eto_checkin_enabled', true);
        
        if (!$checkin_enabled) {
            echo '<span class="eto-status eto-status-disabled">' . __('Disabilitato', 'eto') . '</span>';
            return;
        }
        
        $checkin_start = get_post_meta($post_id, 'eto_checkin_start', true);
        $checkin_end = get_post_meta($post_id, 'eto_checkin_end', true);
        
        if (empty($checkin_start) || empty($checkin_end)) {
            echo '<span class="eto-status eto-status-error">' . __('Configurazione incompleta', 'eto') . '</span>';
            return;
        }
        
        $now = current_time('timestamp');
        $checkin_start_timestamp = strtotime($checkin_start);
        $checkin_end_timestamp = strtotime($checkin_end);
        
        if ($now < $checkin_start_timestamp) {
            echo '<span class="eto-status eto-status-pending">' . __('Non iniziato', 'eto') . '</span>';
            echo '<br><small>' . sprintf(__('Inizia: %s', 'eto'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $checkin_start_timestamp)) . '</small>';
        } elseif ($now >= $checkin_start_timestamp && $now <= $checkin_end_timestamp) {
            echo '<span class="eto-status eto-status-active">' . __('Aperto', 'eto') . '</span>';
            echo '<br><small>' . sprintf(__('Chiude: %s', 'eto'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $checkin_end_timestamp)) . '</small>';
        } else {
            echo '<span class="eto-status eto-status-completed">' . __('Chiuso', 'eto') . '</span>';
        }
    }
    
    /**
     * Aggiunge la meta box per il check-in nei tornei
     */
    public function add_checkin_meta_box() {
        add_meta_box(
            'eto_checkin_meta_box',
            __('Impostazioni Check-in', 'eto'),
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
        // Ottieni i dati salvati
        $checkin_enabled = get_post_meta($post->ID, 'eto_checkin_enabled', true);
        $checkin_start = get_post_meta($post->ID, 'eto_checkin_start', true);
        $checkin_end = get_post_meta($post->ID, 'eto_checkin_end', true);
        
        // Genera il nonce
        wp_nonce_field('eto_checkin_meta_box', 'eto_checkin_meta_box_nonce');
        
        // Renderizza il form
        ?>
        <p>
            <label>
                <input type="checkbox" name="eto_checkin_enabled" value="1" <?php checked($checkin_enabled, '1'); ?>>
                <?php _e('Abilita check-in online', 'eto'); ?>
            </label>
        </p>
        
        <p>
            <label for="eto_checkin_start"><?php _e('Data inizio check-in:', 'eto'); ?></label><br>
            <input type="datetime-local" id="eto_checkin_start" name="eto_checkin_start" value="<?php echo esc_attr(str_replace(' ', 'T', $checkin_start)); ?>" class="widefat">
        </p>
        
        <p>
            <label for="eto_checkin_end"><?php _e('Data fine check-in:', 'eto'); ?></label><br>
            <input type="datetime-local" id="eto_checkin_end" name="eto_checkin_end" value="<?php echo esc_attr(str_replace(' ', 'T', $checkin_end)); ?>" class="widefat">
        </p>
        
        <p>
            <button type="button" class="button" id="eto_send_checkin_reminders" data-tournament-id="<?php echo $post->ID; ?>" data-nonce="<?php echo wp_create_nonce('eto_admin_checkin_action'); ?>">
                <?php _e('Invia promemoria check-in', 'eto'); ?>
            </button>
        </p>
        
        <p>
            <button type="button" class="button" id="eto_close_checkin" data-tournament-id="<?php echo $post->ID; ?>" data-nonce="<?php echo wp_create_nonce('eto_admin_checkin_action'); ?>">
                <?php _e('Chiudi check-in', 'eto'); ?>
            </button>
        </p>
        <?php
    }
    
    /**
     * Invia promemoria per il check-in
     *
     * @param int $tournament_id ID del torneo
     * @return bool True se i promemoria sono stati inviati
     */
    public function send_checkin_reminders($tournament_id) {
        // Verifica che il torneo esista
        $tournament = get_post($tournament_id);
        if (!$tournament || $tournament->post_type !== 'eto_tournament') {
            return false;
        }
        
        // Verifica che il check-in sia abilitato
        $checkin_enabled = get_post_meta($tournament_id, 'eto_checkin_enabled', true);
        if (!$checkin_enabled) {
            return false;
        }
        
        // Verifica che il check-in sia aperto o stia per aprirsi
        $checkin_start = get_post_meta($tournament_id, 'eto_checkin_start', true);
        $checkin_end = get_post_meta($tournament_id, 'eto_checkin_end', true);
        
        $now = current_time('timestamp');
        $checkin_start_timestamp = strtotime($checkin_start);
        $checkin_end_timestamp = strtotime($checkin_end);
        
        if ($now > $checkin_end_timestamp) {
            return false;
        }
        
        // Invia notifiche se la classe è disponibile
        if (isset($this->notifications)) {
            return $this->notifications->send_checkin_reminders($tournament_id);
        }
        
        return false;
    }
    
    /**
     * Chiude il check-in per un torneo
     *
     * @param int $tournament_id ID del torneo
     * @return bool True se il check-in è stato chiuso
     */
    public function close_checkin($tournament_id) {
        // Verifica che il torneo esista
        $tournament = get_post($tournament_id);
        if (!$tournament || $tournament->post_type !== 'eto_tournament') {
            return false;
        }
        
        // Verifica che il check-in sia abilitato
        $checkin_enabled = get_post_meta($tournament_id, 'eto_checkin_enabled', true);
        if (!$checkin_enabled) {
            return false;
        }
        
        // Imposta la data di fine check-in a ora
        $now = current_time('mysql');
        update_post_meta($tournament_id, 'eto_checkin_end', $now);
        
        // Invia notifiche se la classe è disponibile
        if (isset($this->notifications)) {
            $this->notifications->send_checkin_closed_notification($tournament_id);
        }
        
        return true;
    }
}

// Non inizializzare automaticamente la classe
// L'inizializzazione avverrà nel file principale del plugin
