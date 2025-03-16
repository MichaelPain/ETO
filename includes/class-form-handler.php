<?php
/**
 * Gestore dei form per ETO
 * 
 * @package ETO
 * @since 2.5.3
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Form_Handler {
    
    public function __construct() {
        // Aggiungi gli hook per gestire i form
        add_action('admin_post_eto_create_tournament', array($this, 'handle_create_tournament'));
        add_action('admin_post_eto_create_team', array($this, 'handle_create_team'));
        
        // Verifica e aggiorna la struttura delle tabelle
        add_action('admin_init', array($this, 'check_tables_structure'));
    }
    
    /**
     * Verifica e aggiorna la struttura delle tabelle
     */
public function check_tables_structure() {
    global $wpdb;
    
    // Verifica la tabella dei tornei
    $tournaments_table = $wpdb->prefix . 'eto_tournaments';
    if ($wpdb->get_var("SHOW TABLES LIKE '$tournaments_table'") == $tournaments_table) {
        // Verifica se le colonne necessarie esistono
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $tournaments_table");
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        
        // Aggiungi le colonne mancanti
        if (!in_array('rules', $column_names)) {
            $wpdb->query("ALTER TABLE $tournaments_table ADD COLUMN rules text AFTER max_teams");
        }
        if (!in_array('prizes', $column_names)) {
            $wpdb->query("ALTER TABLE $tournaments_table ADD COLUMN prizes text AFTER rules");
        }
        if (!in_array('featured_image', $column_names)) {
            $wpdb->query("ALTER TABLE $tournaments_table ADD COLUMN featured_image varchar(255) AFTER prizes");
        }
    }
    
    // Verifica la tabella dei team
    $teams_table = $wpdb->prefix . 'eto_teams';
    if ($wpdb->get_var("SHOW TABLES LIKE '$teams_table'") == $teams_table) {
        // Verifica se le colonne necessarie esistono
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $teams_table");
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        
        // Aggiungi le colonne mancanti
        if (!in_array('logo_url', $column_names)) {
            $wpdb->query("ALTER TABLE $teams_table ADD COLUMN logo_url varchar(255) AFTER game");
        }
        if (!in_array('email', $column_names)) {
            $wpdb->query("ALTER TABLE $teams_table ADD COLUMN email varchar(100) AFTER captain_id");
        }
        if (!in_array('website', $column_names)) {
            $wpdb->query("ALTER TABLE $teams_table ADD COLUMN website varchar(255) AFTER email");
        }
        if (!in_array('social_media', $column_names)) {
            $wpdb->query("ALTER TABLE $teams_table ADD COLUMN social_media text AFTER website");
        }
    }
}
    
    public function handle_create_tournament() {
        // Verifica il nonce
        if (!isset($_POST['eto_nonce']) || !wp_verify_nonce($_POST['eto_nonce'], 'eto_create_tournament')) {
            wp_die('Errore di sicurezza');
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_die('Permessi insufficienti');
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
            'status' => 'draft',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );
        
        // Aggiungi i campi opzionali solo se le colonne esistono
        global $wpdb;
        $tournaments_table = $wpdb->prefix . 'eto_tournaments';
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $tournaments_table");
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        
        if (in_array('rules', $column_names)) {
            $tournament_data['rules'] = isset($_POST['rules']) ? wp_kses_post($_POST['rules']) : '';
        }
        if (in_array('prizes', $column_names)) {
            $tournament_data['prizes'] = isset($_POST['prizes']) ? wp_kses_post($_POST['prizes']) : '';
        }
        if (in_array('featured_image', $column_names)) {
            $tournament_data['featured_image'] = isset($_POST['featured_image']) ? esc_url_raw($_POST['featured_image']) : '';
        }
        
// Inserisci il torneo
$result = $wpdb->insert($tournaments_table, $tournament_data);

if ($result) {
    $tournament_id = $wpdb->insert_id;
    
    // Verifica che il torneo sia stato effettivamente creato
    $check = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tournaments_table WHERE id = %d", $tournament_id));
    
    if ($check) {
        // Registra l'azione nell'audit log se disponibile
        if (function_exists('eto_log_action')) {
            eto_log_action('tournament_created', sprintf('Torneo "%s" creato con successo (ID: %d)', $tournament_data['name'], $tournament_id));
        }
        
        // Reindirizza alla pagina dei tornei con messaggio di successo
        wp_redirect(admin_url('admin.php?page=eto-tournaments&message=created'));
        exit;
    } else {
        wp_die('Errore: Il torneo è stato inserito ma non è stato possibile recuperarlo. ID: ' . $tournament_id);
    }
} else {
    wp_die('Errore durante la creazione del torneo: ' . $wpdb->last_error);
}
    }
    
    public function handle_create_team() {
        // Verifica il nonce
        if (!isset($_POST['eto_nonce']) || !wp_verify_nonce($_POST['eto_nonce'], 'eto_create_team')) {
            wp_die('Errore di sicurezza');
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_die('Permessi insufficienti');
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
            'captain_id' => intval($_POST['captain_id']),
            'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'website' => isset($_POST['website']) ? esc_url_raw($_POST['website']) : '',
            'social_media' => isset($_POST['social_media']) ? json_encode($_POST['social_media']) : '{}',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );
        
        // Aggiungi i campi opzionali solo se le colonne esistono
        global $wpdb;
        $teams_table = $wpdb->prefix . 'eto_teams';
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $teams_table");
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        
        if (in_array('logo_url', $column_names)) {
            $team_data['logo_url'] = isset($_POST['logo_url']) ? esc_url_raw($_POST['logo_url']) : '';
        }
        
        // Inserisci il team
        $result = $wpdb->insert($teams_table, $team_data);
        
        if ($result) {
            $team_id = $wpdb->insert_id;
            // Reindirizza alla pagina dei team con messaggio di successo
            wp_redirect(admin_url('admin.php?page=eto-teams&message=created'));
            exit;
        } else {
            wp_die('Errore durante la creazione del team: ' . $wpdb->last_error);
        }
    }
}

// Funzione di debug per visualizzare la struttura delle tabelle
function eto_debug_table_structure() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_GET['eto_debug_tables']) && $_GET['eto_debug_tables'] == 1) {
        global $wpdb;
        
        echo '<div class="wrap">';
        echo '<h1>Debug Struttura Tabelle ETO</h1>';
        
        // Tabella tornei
        $tournaments_table = $wpdb->prefix . 'eto_tournaments';
        echo '<h2>Tabella: ' . $tournaments_table . '</h2>';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$tournaments_table'") == $tournaments_table) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $tournaments_table");
            echo '<table class="widefat">';
            echo '<thead><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>';
            echo '<tbody>';
            foreach ($columns as $column) {
                echo '<tr>';
                echo '<td>' . $column->Field . '</td>';
                echo '<td>' . $column->Type . '</td>';
                echo '<td>' . $column->Null . '</td>';
                echo '<td>' . $column->Key . '</td>';
                echo '<td>' . $column->Default . '</td>';
                echo '<td>' . $column->Extra . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            
            // Mostra i primi 5 record
            $records = $wpdb->get_results("SELECT * FROM $tournaments_table LIMIT 5");
            if ($records) {
                echo '<h3>Ultimi 5 record:</h3>';
                echo '<table class="widefat">';
                echo '<thead><tr>';
                foreach ($columns as $column) {
                    echo '<th>' . $column->Field . '</th>';
                }
                echo '</tr></thead>';
                echo '<tbody>';
                foreach ($records as $record) {
                    echo '<tr>';
                    foreach ($columns as $column) {
                        $field = $column->Field;
                        echo '<td>' . (isset($record->$field) ? esc_html($record->$field) : '') . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>Nessun record trovato.</p>';
            }
        } else {
            echo '<p>Tabella non trovata.</p>';
        }
        
        // Tabella team
        $teams_table = $wpdb->prefix . 'eto_teams';
        echo '<h2>Tabella: ' . $teams_table . '</h2>';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$teams_table'") == $teams_table) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $teams_table");
            echo '<table class="widefat">';
            echo '<thead><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>';
            echo '<tbody>';
            foreach ($columns as $column) {
                echo '<tr>';
                echo '<td>' . $column->Field . '</td>';
                echo '<td>' . $column->Type . '</td>';
                echo '<td>' . $column->Null . '</td>';
                echo '<td>' . $column->Key . '</td>';
                echo '<td>' . $column->Default . '</td>';
                echo '<td>' . $column->Extra . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            
            // Mostra i primi 5 record
            $records = $wpdb->get_results("SELECT * FROM $teams_table LIMIT 5");
            if ($records) {
                echo '<h3>Ultimi 5 record:</h3>';
                echo '<table class="widefat">';
                echo '<thead><tr>';
                foreach ($columns as $column) {
                    echo '<th>' . $column->Field . '</th>';
                }
                echo '</tr></thead>';
                echo '<tbody>';
                foreach ($records as $record) {
                    echo '<tr>';
                    foreach ($columns as $column) {
                        $field = $column->Field;
                        echo '<td>' . (isset($record->$field) ? esc_html($record->$field) : '') . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>Nessun record trovato.</p>';
            }
        } else {
            echo '<p>Tabella non trovata.</p>';
        }
        
        echo '</div>';
        exit;
    }
}
add_action('admin_init', 'eto_debug_table_structure');

// Inizializza la classe
new ETO_Form_Handler();
