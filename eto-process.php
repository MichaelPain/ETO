<?php
/**
 * Processore di form per ETO
 * 
 * Questo file gestisce tutte le operazioni di form senza utilizzare AJAX
 * 
 * @package ETO
 * @since 2.5.3
 */

// Carica WordPress
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

// Verifica che l'utente sia loggato e abbia i permessi necessari
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Accesso non autorizzato');
}

// Inizializza il database
global $wpdb;

// Funzione per inserire un torneo
function insert_tournament($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'eto_tournaments';
    
    // Verifica se la tabella esiste
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // Crea la tabella se non esiste
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
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
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // Inserisci il torneo
    $result = $wpdb->insert(
        $table_name,
        array(
            'name' => $data['name'],
            'description' => $data['description'],
            'game' => $data['game'],
            'format' => $data['format'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'registration_start' => $data['registration_start'],
            'registration_end' => $data['registration_end'],
            'min_teams' => $data['min_teams'],
            'max_teams' => $data['max_teams'],
            'rules' => $data['rules'],
            'prizes' => $data['prizes'],
            'featured_image' => $data['featured_image'],
            'status' => 'draft',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        )
    );
    
    if ($result) {
        return $wpdb->insert_id;
    }
    
    return false;
}

// Funzione per inserire un team
function insert_team($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'eto_teams';
    
    // Verifica se la tabella esiste
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // Crea la tabella se non esiste
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
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
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // Inserisci il team
    $result = $wpdb->insert(
        $table_name,
        array(
            'name' => $data['name'],
            'description' => $data['description'],
            'game' => $data['game'],
            'logo_url' => $data['logo_url'],
            'captain_id' => $data['captain_id'],
            'email' => $data['email'],
            'website' => $data['website'],
            'social_media' => json_encode($data['social_media']),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        )
    );
    
    if ($result) {
        return $wpdb->insert_id;
    }
    
    return false;
}

// Gestisci la richiesta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica l'azione
    if (isset($_POST['action'])) {
        // Crea torneo
        if ($_POST['action'] === 'eto_create_tournament') {
            // Verifica il nonce
            if (!isset($_POST['eto_nonce']) || !wp_verify_nonce($_POST['eto_nonce'], 'eto_create_tournament')) {
                wp_die('Errore di sicurezza');
            }
            
            // Verifica i campi obbligatori
            if (empty($_POST['name']) || empty($_POST['game']) || empty($_POST['format']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
                wp_die('Tutti i campi obbligatori devono essere compilati');
            }
            
            // Sanitizza i dati
            $tournament_data = array(
                'name' => sanitize_text_field($_POST['name']),
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
                'featured_image' => isset($_POST['featured_image']) ? esc_url_raw($_POST['featured_image']) : ''
            );
            
            // Inserisci il torneo
            $result = insert_tournament($tournament_data);
            
            if ($result) {
                // Reindirizza alla pagina dei tornei con messaggio di successo
                // Modifica: Utilizzo di site_url() invece di admin_url() per garantire un percorso assoluto
                wp_redirect(site_url('wp-admin/admin.php?page=eto-tournaments&message=created'));
                exit;
            } else {
                wp_die('Errore durante la creazione del torneo');
            }
        }
        
        // Crea team
        if ($_POST['action'] === 'eto_create_team') {
            // Verifica il nonce
            if (!isset($_POST['eto_nonce']) || !wp_verify_nonce($_POST['eto_nonce'], 'eto_create_team')) {
                wp_die('Errore di sicurezza');
            }
            
            // Verifica i campi obbligatori
            if (empty($_POST['name']) || empty($_POST['game']) || empty($_POST['captain_id'])) {
                wp_die('Tutti i campi obbligatori devono essere compilati');
            }
            
            // Sanitizza i dati
            $team_data = array(
                'name' => sanitize_text_field($_POST['name']),
                'description' => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
                'game' => sanitize_text_field($_POST['game']),
                'logo_url' => isset($_POST['logo_url']) ? esc_url_raw($_POST['logo_url']) : '',
                'captain_id' => intval($_POST['captain_id']),
                'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
                'website' => isset($_POST['website']) ? esc_url_raw($_POST['website']) : '',
                'social_media' => isset($_POST['social_media']) ? $_POST['social_media'] : array()
            );
            
            // Inserisci il team
            $result = insert_team($team_data);
            
            if ($result) {
                // Reindirizza alla pagina dei team con messaggio di successo
                // Modifica: Utilizzo di site_url() invece di admin_url() per garantire un percorso assoluto
                wp_redirect(site_url('wp-admin/admin.php?page=eto-teams&message=created'));
                exit;
            } else {
                wp_die('Errore durante la creazione del team');
            }
        }
    }
}

// Se arriviamo qui, c'è stato un errore
wp_die('Richiesta non valida');
