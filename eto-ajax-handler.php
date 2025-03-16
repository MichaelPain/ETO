<?php
/**
 * ETO AJAX Handler
 * 
 * Gestisce tutte le richieste AJAX per il plugin ETO
 * 
 * @package ETO
 * @since 2.5.4
 */

// Previeni l'accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Funzione per generare uno slug univoco
function eto_generate_unique_slug($title, $table_name, $existing_id = 0) {
    global $wpdb;
    
    // Crea uno slug base dal titolo
    $slug = sanitize_title($title);
    
    // Se lo slug è vuoto (potrebbe accadere con caratteri speciali), usa un prefisso con un timestamp
    if (empty($slug)) {
        $slug = 'item-' . time();
    }
    
    $original_slug = $slug;
    $i = 1;
    
    // Verifica se lo slug esiste già
    while ($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE slug = %s AND id != %d",
        $slug, $existing_id
    ))) {
        // Se esiste, aggiungi un numero incrementale
        $slug = $original_slug . '-' . $i++;
    }
    
    return $slug;
}

// Registra le funzioni AJAX
function eto_register_ajax_handlers() {
    // Azione per creare un torneo
    add_action('wp_ajax_eto_create_tournament', 'eto_ajax_create_tournament');
    
    // Azione per creare un team
    add_action('wp_ajax_eto_create_team', 'eto_ajax_create_team');
    
    // Azione per salvare le impostazioni
    add_action('wp_ajax_eto_save_settings', 'eto_ajax_save_settings');
}
add_action('init', 'eto_register_ajax_handlers');

// Handler AJAX per la creazione di un torneo
function eto_ajax_create_tournament() {
    // Verifica che l'utente sia loggato e abbia i permessi necessari
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        wp_send_json_error('Accesso non autorizzato');
    }
    
    // Verifica il nonce
    if (!isset($_POST['eto_nonce']) || !wp_verify_nonce($_POST['eto_nonce'], 'eto_create_tournament')) {
        wp_send_json_error('Errore di sicurezza');
    }
    
    // Verifica i campi obbligatori
    if (empty($_POST['name']) || empty($_POST['game']) || empty($_POST['format']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
        wp_send_json_error('Tutti i campi obbligatori devono essere compilati');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'eto_tournaments';
    
    // Verifica se la tabella esiste
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // Crea la tabella se non esiste
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            game varchar(50) NOT NULL,
            format varchar(50) NOT NULL,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            registration_start datetime,
            registration_end datetime,
            min_teams int DEFAULT 2,
            max_teams int DEFAULT 16,
            rules text,
            prizes text,
            featured_image varchar(255),
            status varchar(20) DEFAULT 'draft',
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // Genera uno slug univoco
    $slug = eto_generate_unique_slug($_POST['name'], $table_name);
    
    // Sanitizza i dati
    $tournament_data = array(
        'name' => sanitize_text_field($_POST['name']),
        'slug' => $slug,
        'description' => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
        'game' => sanitize_text_field($_POST['game']),
        'format' => sanitize_text_field($_POST['format']),
        'start_date' => sanitize_text_field($_POST['start_date']),
        'end_date' => sanitize_text_field($_POST['end_date']),
        'registration_start' => isset($_POST['registration_start']) ? sanitize_text_field($_POST['registration_start']) : '',
        'registration_end' => isset($_POST['registration_end']) ? sanitize_text_field($_POST['registration_end']) : '',
        'min_teams' => isset($_POST['min_teams']) ? intval($_POST['min_teams']) : 2,
        'max_teams' => isset($_POST['max_teams']) ? intval($_POST['max_teams']) : 16,
        'rules' => isset($_POST['rules']) ? wp_kses_post($_POST['rules']) : '',
        'prizes' => isset($_POST['prizes']) ? wp_kses_post($_POST['prizes']) : '',
        'featured_image' => isset($_POST['featured_image']) ? esc_url_raw($_POST['featured_image']) : '',
        'status' => 'draft',
        'created_by' => get_current_user_id(),
        'created_at' => current_time('mysql')
    );
    
    // Inserisci il torneo
    $result = $wpdb->insert($table_name, $tournament_data);
    
    if ($result) {
        $tournament_id = $wpdb->insert_id;
        wp_send_json_success(array(
            'message' => 'Torneo creato con successo',
            'redirect' => admin_url('admin.php?page=eto-tournaments&message=created'),
            'tournament_id' => $tournament_id
        ));
    } else {
        wp_send_json_error('Errore durante la creazione del torneo: ' . $wpdb->last_error);
    }
    
    exit;
}

// Handler AJAX per la creazione di un team
function eto_ajax_create_team() {
    // Verifica che l'utente sia loggato e abbia i permessi necessari
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        wp_send_json_error('Accesso non autorizzato');
    }
    
    // Verifica il nonce
    if (!isset($_POST['eto_nonce']) || !wp_verify_nonce($_POST['eto_nonce'], 'eto_create_team')) {
        wp_send_json_error('Errore di sicurezza');
    }
    
    // Verifica i campi obbligatori
    if (empty($_POST['name']) || empty($_POST['game']) || empty($_POST['captain_id'])) {
        wp_send_json_error('Tutti i campi obbligatori devono essere compilati');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'eto_teams';
    
    // Verifica se la tabella esiste
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // Crea la tabella se non esiste
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            game varchar(50) NOT NULL,
            logo_url varchar(255),
            captain_id bigint(20) NOT NULL,
            email varchar(100),
            website varchar(255),
            social_media text,
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // Genera uno slug univoco
    $slug = eto_generate_unique_slug($_POST['name'], $table_name);
    
    // Sanitizza i dati
    $team_data = array(
        'name' => sanitize_text_field($_POST['name']),
        'slug' => $slug,
        'description' => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
        'game' => sanitize_text_field($_POST['game']),
        'logo_url' => isset($_POST['logo_url']) ? esc_url_raw($_POST['logo_url']) : '',
        'captain_id' => intval($_POST['captain_id']),
        'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
        'website' => isset($_POST['website']) ? esc_url_raw($_POST['website']) : '',
        'social_media' => isset($_POST['social_media']) ? json_encode($_POST['social_media']) : '{}',
        'created_by' => get_current_user_id(),
        'created_at' => current_time('mysql')
    );
    
    // Inserisci il team
    $result = $wpdb->insert($table_name, $team_data);
    
    if ($result) {
        $team_id = $wpdb->insert_id;
        wp_send_json_success(array(
            'message' => 'Team creato con successo',
            'redirect' => admin_url('admin.php?page=eto-teams&message=created'),
            'team_id' => $team_id
        ));
    } else {
        wp_send_json_error('Errore durante la creazione del team: ' . $wpdb->last_error);
    }
    
    exit;
}

// Handler AJAX per il salvataggio delle impostazioni
function eto_ajax_save_settings() {
    // Verifica il nonce
    if (!isset($_POST['eto_settings_nonce']) || !wp_verify_nonce($_POST['eto_settings_nonce'], 'eto_save_settings')) {
        wp_send_json_error(array('message' => 'Errore di sicurezza'));
        exit;
    }
    
    // Verifica i permessi
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permessi insufficienti'));
        exit;
    }
    
    // Salva le impostazioni
    if (isset($_POST['eto_default_format'])) {
        update_option('eto_default_format', sanitize_text_field($_POST['eto_default_format']));
    }
    
    if (isset($_POST['eto_default_game'])) {
        update_option('eto_default_game', sanitize_text_field($_POST['eto_default_game']));
    }
    
    if (isset($_POST['eto_max_teams_per_tournament'])) {
        update_option('eto_max_teams_per_tournament', intval($_POST['eto_max_teams_per_tournament']));
    }
    
    if (isset($_POST['eto_enable_third_place_match'])) {
        update_option('eto_enable_third_place_match', 1);
    } else {
        update_option('eto_enable_third_place_match', 0);
    }
    
    if (isset($_POST['eto_riot_api_key'])) {
        update_option('eto_riot_api_key', sanitize_text_field($_POST['eto_riot_api_key']));
    }
    
    if (isset($_POST['eto_enable_riot_api'])) {
        update_option('eto_enable_riot_api', 1);
    } else {
        update_option('eto_enable_riot_api', 0);
    }
    
    if (isset($_POST['eto_tournament_page'])) {
        update_option('eto_tournament_page', intval($_POST['eto_tournament_page']));
    }
    
    if (isset($_POST['eto_team_page'])) {
        update_option('eto_team_page', intval($_POST['eto_team_page']));
    }
    
    // Restituisci successo
    wp_send_json_success(array('message' => 'Impostazioni salvate con successo'));
    exit;
}
