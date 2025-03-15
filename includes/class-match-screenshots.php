<?php
/**
 * Classe per la gestione degli screenshot dei match
 *
 * Implementa la logica per caricare, visualizzare e validare gli screenshot dei risultati dei match
 *
 * @package ETO
 * @since 2.6.0
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Match_Screenshots {
    
    /**
     * Istanza del database query
     *
     * @var ETO_DB_Query
     */
    private $db_query;
    
    /**
     * Costruttore
     */
    public function __construct() {
        $this->db_query = new ETO_DB_Query();
        
        // Aggiungi i filtri e le azioni
        add_action('wp_ajax_eto_upload_match_screenshot', array($this, 'ajax_upload_screenshot'));
        add_action('wp_ajax_eto_validate_match_screenshot', array($this, 'ajax_validate_screenshot'));
        add_action('wp_ajax_eto_reject_match_screenshot', array($this, 'ajax_reject_screenshot'));
        
        // Aggiungi shortcode per il frontend
        add_shortcode('eto_match_screenshots', array($this, 'shortcode_match_screenshots'));
        
        // Aggiungi metabox per l'admin
        add_action('add_meta_boxes', array($this, 'add_screenshots_meta_box'));
        
        // Aggiungi script e stili
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Registra gli script e gli stili per il frontend
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_media();
        wp_enqueue_script('eto-screenshots', ETO_PLUGIN_URL . 'public/js/screenshots.js', array('jquery'), ETO_VERSION, true);
        wp_localize_script('eto-screenshots', 'etoScreenshots', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eto-screenshots-nonce'),
            'i18n' => array(
                'upload' => __('Carica Screenshot', 'eto'),
                'validate' => __('Convalida', 'eto'),
                'reject' => __('Rifiuta', 'eto'),
                'confirmValidate' => __('Sei sicuro di voler convalidare questo screenshot?', 'eto'),
                'confirmReject' => __('Sei sicuro di voler rifiutare questo screenshot? Inserisci un motivo:', 'eto')
            )
        ));
    }
    
    /**
     * Registra gli script e gli stili per l'admin
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook == 'post.php' || $hook == 'post-new.php') {
            wp_enqueue_media();
            wp_enqueue_script('eto-admin-screenshots', ETO_PLUGIN_URL . 'admin/js/screenshots.js', array('jquery'), ETO_VERSION, true);
            wp_localize_script('eto-admin-screenshots', 'etoScreenshots', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('eto-screenshots-nonce'),
                'i18n' => array(
                    'validate' => __('Convalida', 'eto'),
                    'reject' => __('Rifiuta', 'eto'),
                    'confirmValidate' => __('Sei sicuro di voler convalidare questo screenshot?', 'eto'),
                    'confirmReject' => __('Sei sicuro di voler rifiutare questo screenshot? Inserisci un motivo:', 'eto')
                )
            ));
        }
    }
    
    /**
     * Aggiunge il metabox per gli screenshot nella pagina di modifica del match
     */
    public function add_screenshots_meta_box() {
        add_meta_box(
            'eto-match-screenshots',
            __('Screenshot dei Risultati', 'eto'),
            array($this, 'render_screenshots_meta_box'),
            'eto-match',
            'normal',
            'default'
        );
    }
    
    /**
     * Renderizza il metabox per gli screenshot
     *
     * @param WP_Post $post Post corrente
     */
    public function render_screenshots_meta_box($post) {
        $match_id = $post->ID;
        $screenshots = $this->get_match_screenshots($match_id);
        
        include ETO_PLUGIN_DIR . '/admin/views/matches/screenshots-meta-box.php';
    }
    
    /**
     * Gestisce l'upload degli screenshot via AJAX
     */
    public function ajax_upload_screenshot() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-screenshots-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i permessi
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Non hai i permessi per caricare file', 'eto')));
        }
        
        // Verifica i parametri
        if (!isset($_POST['match_id']) || !isset($_POST['team_id']) || !isset($_FILES['screenshot'])) {
            wp_send_json_error(array('message' => __('Parametri mancanti', 'eto')));
        }
        
        $match_id = intval($_POST['match_id']);
        $team_id = intval($_POST['team_id']);
        $user_id = get_current_user_id();
        
        // Verifica che l'utente sia capitano del team
        $team = new ETO_Team_Model($team_id);
        if ($team->get('captain_id') != $user_id) {
            wp_send_json_error(array('message' => __('Solo il capitano può caricare screenshot', 'eto')));
        }
        
        // Verifica che il team partecipi al match
        $match = new ETO_Match_Model($match_id);
        if ($match->get('team1_id') != $team_id && $match->get('team2_id') != $team_id) {
            wp_send_json_error(array('message' => __('Il tuo team non partecipa a questo match', 'eto')));
        }
        
        // Gestisci l'upload del file
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/eto/screenshots/';
        
        // Crea la directory se non esiste
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        
        // Genera un nome file unico
        $file_name = 'match_' . $match_id . '_team_' . $team_id . '_' . time() . '_' . sanitize_file_name($_FILES['screenshot']['name']);
        $target_file = $target_dir . $file_name;
        
        // Verifica il tipo di file
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        if (!in_array($_FILES['screenshot']['type'], $allowed_types)) {
            wp_send_json_error(array('message' => __('Tipo di file non supportato. Usa JPG, PNG o GIF', 'eto')));
        }
        
        // Sposta il file caricato
        if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $target_file)) {
            // Salva i dati nel database
            global $wpdb;
            $table_screenshots = $wpdb->prefix . 'eto_match_screenshots';
            
            $result = $wpdb->insert(
                $table_screenshots,
                array(
                    'match_id' => $match_id,
                    'user_id' => $user_id,
                    'team_id' => $team_id,
                    'file_path' => 'screenshots/' . $file_name,
                    'uploaded_at' => current_time('mysql'),
                    'status' => 'pending'
                ),
                array('%d', '%d', '%d', '%s', '%s', '%s')
            );
            
            if ($result) {
                $screenshot_id = $wpdb->insert_id;
                
                // Notifica l'altro team
                $this->notify_opponent_team($match_id, $team_id, $screenshot_id);
                
                // Notifica l'amministratore
                $this->notify_admin($match_id, $team_id, $screenshot_id);
                
                wp_send_json_success(array(
                    'message' => __('Screenshot caricato con successo', 'eto'),
                    'screenshot_id' => $screenshot_id,
                    'file_url' => $upload_dir['baseurl'] . '/eto/screenshots/' . $file_name
                ));
            } else {
                // Elimina il file se non è stato possibile salvare nel database
                @unlink($target_file);
                wp_send_json_error(array('message' => __('Errore nel salvataggio dello screenshot', 'eto')));
            }
        } else {
            wp_send_json_error(array('message' => __('Errore nel caricamento del file', 'eto')));
        }
    }
    
    /**
     * Gestisce la validazione degli screenshot via AJAX
     */
    public function ajax_validate_screenshot() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-screenshots-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i parametri
        if (!isset($_POST['screenshot_id'])) {
            wp_send_json_error(array('message' => __('Parametri mancanti', 'eto')));
        }
        
        $screenshot_id = intval($_POST['screenshot_id']);
        $user_id = get_current_user_id();
        
        // Ottieni i dati dello screenshot
        global $wpdb;
        $table_screenshots = $wpdb->prefix . 'eto_match_screenshots';
        
        $screenshot = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_screenshots WHERE id = %d",
            $screenshot_id
        ));
        
        if (!$screenshot) {
            wp_send_json_error(array('message' => __('Screenshot non trovato', 'eto')));
        }
        
        // Ottieni i dati del match
        $match = new ETO_Match_Model($screenshot->match_id);
        if (!$match) {
            wp_send_json_error(array('message' => __('Match non trovato', 'eto')));
        }
        
        // Verifica se l'utente è un amministratore
        $is_admin = current_user_can('manage_options');
        
        // Verifica se l'utente è il capitano del team avversario
        $is_opponent_captain = false;
        $opponent_team_id = ($match->get('team1_id') == $screenshot->team_id) ? $match->get('team2_id') : $match->get('team1_id');
        
        if ($opponent_team_id) {
            $opponent_team = new ETO_Team_Model($opponent_team_id);
            if ($opponent_team && $opponent_team->get('captain_id') == $user_id) {
                $is_opponent_captain = true;
            }
        }
        
        // Aggiorna lo stato dello screenshot
        $update_data = array();
        $update_format = array();
        
        if ($is_admin) {
            $update_data['validated_by_admin'] = 1;
            $update_format[] = '%d';
        } elseif ($is_opponent_captain) {
            $update_data['validated_by_opponent'] = 1;
            $update_format[] = '%d';
        } else {
            wp_send_json_error(array('message' => __('Non hai i permessi per convalidare questo screenshot', 'eto')));
        }
        
        // Se entrambe le validazioni sono complete, aggiorna lo stato
        if (($is_admin && $screenshot->validated_by_opponent == 1) || 
            ($is_opponent_captain && $screenshot->validated_by_admin == 1) ||
            ($is_admin && $is_opponent_captain)) {
            $update_data['status'] = 'validated';
            $update_format[] = '%s';
            
            // Aggiorna il risultato del match se necessario
            $this->update_match_result($screenshot->match_id);
        }
        
        $result = $wpdb->update(
            $table_screenshots,
            $update_data,
            array('id' => $screenshot_id),
            $update_format,
            array('%d')
        );
        
        if ($result) {
            wp_send_json_success(array('message' => __('Screenshot convalidato con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore nella convalida dello screenshot', 'eto')));
        }
    }
    
    /**
     * Gestisce il rifiuto degli screenshot via AJAX
     */
    public function ajax_reject_screenshot() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-screenshots-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i parametri
        if (!isset($_POST['screenshot_id']) || !isset($_POST['reason'])) {
            wp_send_json_error(array('message' => __('Parametri mancanti', 'eto')));
        }
        
        $screenshot_id = intval($_POST['screenshot_id']);
        $reason = sanitize_textarea_field($_POST['reason']);
        $user_id = get_current_user_id();
        
        // Ottieni i dati dello screenshot
        global $wpdb;
        $table_screenshots = $wpdb->prefix . 'eto_match_screenshots';
        
        $screenshot = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_screenshots WHERE id = %d",
            $screenshot_id
        ));
        
        if (!$screenshot) {
            wp_send_json_error(array('message' => __('Screenshot non trovato', 'eto')));
        }
        
        // Ottieni i dati del match
        $match = new ETO_Match_Model($screenshot->match_id);
        if (!$match) {
            wp_send_json_error(array('message' => __('Match non trovato', 'eto')));
        }
        
        // Verifica se l'utente è un amministratore
        $is_admin = current_user_can('manage_options');
        
        // Verifica se l'utente è il capitano del team avversario
        $is_opponent_captain = false;
        $opponent_team_id = ($match->get('team1_id') == $screenshot->team_id) ? $match->get('team2_id') : $match->get('team1_id');
        
        if ($opponent_team_id) {
            $opponent_team = new ETO_Team_Model($opponent_team_id);
            if ($opponent_team && $opponent_team->get('captain_id') == $user_id) {
                $is_opponent_captain = true;
            }
        }
        
        // Verifica i permessi
        if (!$is_admin && !$is_opponent_captain) {
            wp_send_json_error(array('message' => __('Non hai i permessi per rifiutare questo screenshot', 'eto')));
        }
        
        // Aggiorna lo stato dello screenshot
        $result = $wpdb->update(
            $table_screenshots,
            array(
                'status' => 'rejected',
                'validation_notes' => $reason
            ),
            array('id' => $screenshot_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result) {
            // Notifica il team che ha caricato lo screenshot
            $this->notify_team_rejection($screenshot->match_id, $screenshot->team_id, $screenshot_id, $reason);
            
            wp_send_json_success(array('message' => __('Screenshot rifiutato con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore nel rifiuto dello screenshot', 'eto')));
        }
    }
    
    /**
     * Ottiene gli screenshot di un match
     *
     * @param int $match_id ID del match
     * @return array Array di oggetti screenshot
     */
    public function get_match_screenshots($match_id) {
        global $wpdb;
        $table_screenshots = $wpdb->prefix . 'eto_match_screenshots';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_screenshots WHERE match_id = %d ORDER BY uploaded_at DESC",
            $match_id
        ));
    }
    
    /**
     * Notifica il team avversario del caricamento di uno screenshot
     *
     * @param int $match_id ID del match
     * @param int $team_id ID del team che ha caricato lo screenshot
     * @param int $screenshot_id ID dello screenshot
     * @return void
     */
    private function notify_opponent_team($match_id, $team_id, $screenshot_id) {
        $match = new ETO_Match_Model($match_id);
        if (!$match) {
            return;
        }
        
        $opponent_team_id = ($match->get('team1_id') == $team_id) ? $match->get('team2_id') : $match->get('team1_id');
        
        if (!$opponent_team_id) {
            return;
        }
        
        $opponent_team = new ETO_Team_Model($opponent_team_id);
        if (!$opponent_team) {
            return;
        }
        
        $captain_id = $opponent_team->get('captain_id');
        if (!$captain_id) {
            return;
        }
        
        $captain = get_userdata($captain_id);
        if (!$captain) {
            return;
        }
        
        $team = new ETO_Team_Model($team_id);
        $team_name = $team ? $team->get('name') : __('Team sconosciuto', 'eto');
        
        $tournament = $match->get_tournament();
        $tournament_name = $tournament ? $tournament->get('name') : __('Torneo sconosciuto', 'eto');
        
        $subject = sprintf(__('[%s] Nuovo screenshot caricato per il match', 'eto'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Ciao %s,

Il team %s ha caricato uno screenshot per il match nel torneo %s.

Per convalidare o rifiutare lo screenshot, visita la pagina del match:
%s

Grazie,
%s', 'eto'),
            $captain->display_name,
            $team_name,
            $tournament_name,
            get_permalink($match_id),
            get_bloginfo('name')
        );
        
        wp_mail($captain->user_email, $subject, $message);
    }
    
    /**
     * Notifica l'amministratore del caricamento di uno screenshot
     *
     * @param int $match_id ID del match
     * @param int $team_id ID del team che ha caricato lo screenshot
     * @param int $screenshot_id ID dello screenshot
     * @return void
     */
    private function notify_admin($match_id, $team_id, $screenshot_id) {
        $admin_email = get_option('admin_email');
        if (!$admin_email) {
            return;
        }
        
        $match = new ETO_Match_Model($match_id);
        if (!$match) {
            return;
        }
        
        $team = new ETO_Team_Model($team_id);
        $team_name = $team ? $team->get('name') : __('Team sconosciuto', 'eto');
        
        $tournament = $match->get_tournament();
        $tournament_name = $tournament ? $tournament->get('name') : __('Torneo sconosciuto', 'eto');
        
        $subject = sprintf(__('[%s] Nuovo screenshot caricato per il match', 'eto'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Ciao Admin,

Il team %s ha caricato uno screenshot per un match nel torneo %s.

Per convalidare o rifiutare lo screenshot, visita la pagina di amministrazione:
%s

Grazie,
%s', 'eto'),
            $team_name,
            $tournament_name,
            admin_url('post.php?post=' . $match_id . '&action=edit'),
            get_bloginfo('name')
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Notifica il team del rifiuto di uno screenshot
     *
     * @param int $match_id ID del match
     * @param int $team_id ID del team che ha caricato lo screenshot
     * @param int $screenshot_id ID dello screenshot
     * @param string $reason Motivo del rifiuto
     * @return void
     */
    private function notify_team_rejection($match_id, $team_id, $screenshot_id, $reason) {
        $team = new ETO_Team_Model($team_id);
        if (!$team) {
            return;
        }
        
        $captain_id = $team->get('captain_id');
        if (!$captain_id) {
            return;
        }
        
        $captain = get_userdata($captain_id);
        if (!$captain) {
            return;
        }
        
        $match = new ETO_Match_Model($match_id);
        if (!$match) {
            return;
        }
        
        $tournament = $match->get_tournament();
        $tournament_name = $tournament ? $tournament->get('name') : __('Torneo sconosciuto', 'eto');
        
        $subject = sprintf(__('[%s] Screenshot rifiutato per il match', 'eto'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Ciao %s,

Lo screenshot che hai caricato per il match nel torneo %s è stato rifiutato.

Motivo: %s

Per caricare un nuovo screenshot, visita la pagina del match:
%s

Grazie,
%s', 'eto'),
            $captain->display_name,
            $tournament_name,
            $reason,
            get_permalink($match_id),
            get_bloginfo('name')
        );
        
        wp_mail($captain->user_email, $subject, $message);
    }
    
    /**
     * Aggiorna il risultato del match in base allo screenshot convalidato
     *
     * @param int $match_id ID del match
     * @return void
     */
    private function update_match_result($match_id) {
        // Questa funzione può essere implementata per aggiornare automaticamente
        // il risultato del match quando uno screenshot viene convalidato.
        // Per ora, lasciamo che l'amministratore aggiorni manualmente il risultato.
    }
    
    /**
     * Shortcode per visualizzare gli screenshot di un match
     *
     * @param array $atts Attributi dello shortcode
     * @return string HTML generato
     */
    public function shortcode_match_screenshots($atts) {
        $atts = shortcode_atts(array(
            'match_id' => 0
        ), $atts, 'eto_match_screenshots');
        
        $match_id = intval($atts['match_id']);
        
        if (empty($match_id)) {
            return '';
        }
        
        $match = new ETO_Match_Model($match_id);
        if (!$match) {
            return '';
        }
        
        $screenshots = $this->get_match_screenshots($match_id);
        $user_id = get_current_user_id();
        
        // Verifica se l'utente è un amministratore
        $is_admin = current_user_can('manage_options');
        
        // Verifica se l'utente è il capitano di uno dei team
        $is_team1_captain = false;
        $is_team2_captain = false;
        
        $team1_id = $match->get('team1_id');
        $team2_id = $match->get('team2_id');
        
        if ($team1_id) {
            $team1 = new ETO_Team_Model($team1_id);
            if ($team1 && $team1->get('captain_id') == $user_id) {
                $is_team1_captain = true;
            }
        }
        
        if ($team2_id) {
            $team2 = new ETO_Team_Model($team2_id);
            if ($team2 && $team2->get('captain_id') == $user_id) {
                $is_team2_captain = true;
            }
        }
        
        // Carica il template
        ob_start();
        include ETO_PLUGIN_DIR . '/templates/frontend/matches/screenshots.php';
        return ob_get_clean();
    }
}

// Inizializza la classe
$match_screenshots = new ETO_Match_Screenshots();
