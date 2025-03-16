<?php
/**
 * Shortcodes per il plugin ETO
 * 
 * @package ETO
 * @since 2.5.4
 */

// Previeni l'accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode per visualizzare un torneo
 *
 * @param array $atts Attributi dello shortcode
 * @return string HTML del torneo
 */
function eto_tournament_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
        'show_teams' => true,
        'show_matches' => true,
        'show_bracket' => true
    ), $atts, 'eto_tournament');
    
    $tournament_id = absint($atts['id']);
    
    if (empty($tournament_id)) {
        // Prova a ottenere l'ID dal parametro GET
        $tournament_id = isset($_GET['tournament_id']) ? absint($_GET['tournament_id']) : 0;
    }
    
    if (empty($tournament_id)) {
        return '<p>' . __('Nessun torneo specificato.', 'eto') . '</p>';
    }
    
    // Ottieni il torneo
    global $wpdb;
    $table = $wpdb->prefix . 'eto_tournaments';
    
    $tournament = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $tournament_id
        )
    );
    
    if (!$tournament) {
        return '<p>' . __('Torneo non trovato.', 'eto') . '</p>';
    }
    
    // Inizia l'output
    ob_start();
    
    // Includi il template del torneo
    include(ETO_PLUGIN_DIR . 'templates/tournament.php');
    
    return ob_get_clean();
}
add_shortcode('eto_tournament', 'eto_tournament_shortcode');

/**
 * Shortcode per visualizzare un elenco di tornei
 *
 * @param array $atts Attributi dello shortcode
 * @return string HTML dell'elenco di tornei
 */
function eto_tournaments_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 10,
        'status' => 'active',
        'format' => '',
        'game' => '',
        'orderby' => 'start_date',
        'order' => 'ASC'
    ), $atts, 'eto_tournaments');
    
    // Prepara gli argomenti per la query
    $args = array(
        'limit' => absint($atts['limit']),
        'status' => $atts['status'],
        'orderby' => $atts['orderby'],
        'order' => $atts['order']
    );
    
    if (!empty($atts['format'])) {
        $args['format'] = $atts['format'];
    }
    
    if (!empty($atts['game'])) {
        $args['game'] = $atts['game'];
    }
    
    // Ottieni i tornei
    global $wpdb;
    $table = $wpdb->prefix . 'eto_tournaments';
    
    $query = "SELECT * FROM $table WHERE 1=1";
    $query_args = array();
    
    // Filtro per status
    if (!empty($args['status'])) {
        $query .= " AND status = %s";
        $query_args[] = $args['status'];
    }
    
    // Filtro per format
    if (!empty($args['format'])) {
        $query .= " AND format = %s";
        $query_args[] = $args['format'];
    }
    
    // Filtro per game
    if (!empty($args['game'])) {
        $query .= " AND game = %s";
        $query_args[] = $args['game'];
    }
    
    // Ordinamento
    $query .= " ORDER BY {$args['orderby']} {$args['order']}";
    
    // Limite
    $query .= " LIMIT %d";
    $query_args[] = $args['limit'];
    
    // Prepara la query
    $prepared_query = $wpdb->prepare($query, $query_args);
    
    // Esegui la query
    $tournaments = $wpdb->get_results($prepared_query);
    
    if (empty($tournaments)) {
        return '<p>' . __('Nessun torneo trovato.', 'eto') . '</p>';
    }
    
    // Inizia l'output
    ob_start();
    
    // Includi il template dell'elenco di tornei
    include(ETO_PLUGIN_DIR . 'templates/tournaments-list.php');
    
    return ob_get_clean();
}
add_shortcode('eto_tournaments', 'eto_tournaments_shortcode');

/**
 * Shortcode per visualizzare un team
 *
 * @param array $atts Attributi dello shortcode
 * @return string HTML del team
 */
function eto_team_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
        'show_members' => true,
        'show_tournaments' => true
    ), $atts, 'eto_team');
    
    $team_id = absint($atts['id']);
    
    if (empty($team_id)) {
        // Prova a ottenere l'ID dal parametro GET
        $team_id = isset($_GET['team_id']) ? absint($_GET['team_id']) : 0;
    }
    
    if (empty($team_id)) {
        return '<p>' . __('Nessun team specificato.', 'eto') . '</p>';
    }
    
    // Ottieni il team
    global $wpdb;
    $table = $wpdb->prefix . 'eto_teams';
    
    $team = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $team_id
        )
    );
    
    if (!$team) {
        return '<p>' . __('Team non trovato.', 'eto') . '</p>';
    }
    
    // Inizia l'output
    ob_start();
    
    // Includi il template del team
    include(ETO_PLUGIN_DIR . 'templates/team.php');
    
    return ob_get_clean();
}
add_shortcode('eto_team', 'eto_team_shortcode');

/**
 * Shortcode per visualizzare un elenco di team
 *
 * @param array $atts Attributi dello shortcode
 * @return string HTML dell'elenco di team
 */
function eto_teams_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 10,
        'game' => '',
        'orderby' => 'name',
        'order' => 'ASC'
    ), $atts, 'eto_teams');
    
    // Prepara gli argomenti per la query
    $args = array(
        'limit' => absint($atts['limit']),
        'orderby' => $atts['orderby'],
        'order' => $atts['order']
    );
    
    if (!empty($atts['game'])) {
        $args['game'] = $atts['game'];
    }
    
    // Ottieni i team
    global $wpdb;
    $table = $wpdb->prefix . 'eto_teams';
    
    $query = "SELECT * FROM $table WHERE 1=1";
    $query_args = array();
    
    // Filtro per game
    if (!empty($args['game'])) {
        $query .= " AND game = %s";
        $query_args[] = $args['game'];
    }
    
    // Ordinamento
    $query .= " ORDER BY {$args['orderby']} {$args['order']}";
    
    // Limite
    $query .= " LIMIT %d";
    $query_args[] = $args['limit'];
    
    // Prepara la query
    $prepared_query = $wpdb->prepare($query, $query_args);
    
    // Esegui la query
    $teams = $wpdb->get_results($prepared_query);
    
    if (empty($teams)) {
        return '<p>' . __('Nessun team trovato.', 'eto') . '</p>';
    }
    
    // Inizia l'output
    ob_start();
    
    // Includi il template dell'elenco di team
    include(ETO_PLUGIN_DIR . 'templates/teams-list.php');
    
    return ob_get_clean();
}
add_shortcode('eto_teams', 'eto_teams_shortcode');

/**
 * Shortcode per visualizzare il modulo di registrazione a un torneo
 *
 * @param array $atts Attributi dello shortcode
 * @return string HTML del modulo di registrazione
 */
function eto_tournament_registration_shortcode($atts) {
    $atts = shortcode_atts(array(
        'tournament_id' => 0
    ), $atts, 'eto_tournament_registration');
    
    $tournament_id = absint($atts['tournament_id']);
    
    if (empty($tournament_id)) {
        // Prova a ottenere l'ID dal parametro GET
        $tournament_id = isset($_GET['tournament_id']) ? absint($_GET['tournament_id']) : 0;
    }
    
    if (empty($tournament_id)) {
        return '<p>' . __('Nessun torneo specificato.', 'eto') . '</p>';
    }
    
    // Verifica se l'utente è loggato
    if (!is_user_logged_in()) {
        return '<p>' . __('Devi essere loggato per registrarti a un torneo.', 'eto') . '</p>';
    }
    
    // Ottieni il torneo
    global $wpdb;
    $table = $wpdb->prefix . 'eto_tournaments';
    
    $tournament = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $tournament_id
        )
    );
    
    if (!$tournament) {
        return '<p>' . __('Torneo non trovato.', 'eto') . '</p>';
    }
    
    // Verifica se il torneo è aperto alle registrazioni
    if ($tournament->status !== 'active') {
        return '<p>' . __('Il torneo non è attualmente aperto alle registrazioni.', 'eto') . '</p>';
    }
    
    // Inizia l'output
    ob_start();
    
    // Includi il template del modulo di registrazione
    include(ETO_PLUGIN_DIR . 'templates/tournament-registration.php');
    
    return ob_get_clean();
}
add_shortcode('eto_tournament_registration', 'eto_tournament_registration_shortcode');
