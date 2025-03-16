<?php
/**
 * Funzioni di utilità per il plugin ETO
 * 
 * @package ETO
 * @since 2.5.4
 */

// Previeni l'accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Genera uno slug univoco basato su un titolo
 *
 * @param string $title Titolo da cui generare lo slug
 * @param string $table_name Nome della tabella per verificare l'unicità
 * @param int $existing_id ID dell'elemento esistente (per escluderlo dalla verifica)
 * @return string Slug univoco
 */
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

/**
 * Formatta una data nel formato locale
 *
 * @param string $date Data in formato MySQL
 * @param string $format Formato della data (default: data e ora)
 * @return string Data formattata
 */
function eto_format_date($date, $format = 'datetime') {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    
    if ($format === 'date') {
        return date_i18n(get_option('date_format'), $timestamp);
    } elseif ($format === 'time') {
        return date_i18n(get_option('time_format'), $timestamp);
    } else {
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
    }
}

/**
 * Ottiene il nome di un gioco
 *
 * @param string $game_slug Slug del gioco
 * @return string Nome del gioco
 */
function eto_get_game_name($game_slug) {
    $games = eto_get_games();
    
    return isset($games[$game_slug]) ? $games[$game_slug] : $game_slug;
}

/**
 * Ottiene la lista dei giochi disponibili
 *
 * @return array Lista dei giochi
 */
function eto_get_games() {
    return apply_filters('eto_games', array(
        'lol' => __('League of Legends', 'eto'),
        'dota2' => __('Dota 2', 'eto'),
        'csgo' => __('CS:GO', 'eto'),
        'valorant' => __('Valorant', 'eto'),
        'fortnite' => __('Fortnite', 'eto'),
        'pubg' => __('PUBG', 'eto'),
        'rocketleague' => __('Rocket League', 'eto'),
        'overwatch' => __('Overwatch', 'eto'),
        'fifa' => __('FIFA', 'eto'),
        'other' => __('Altro', 'eto')
    ));
}

/**
 * Ottiene il nome di un formato di torneo
 *
 * @param string $format_slug Slug del formato
 * @return string Nome del formato
 */
function eto_get_format_name($format_slug) {
    $formats = eto_get_formats();
    
    return isset($formats[$format_slug]) ? $formats[$format_slug] : $format_slug;
}

/**
 * Ottiene la lista dei formati di torneo disponibili
 *
 * @return array Lista dei formati
 */
function eto_get_formats() {
    return apply_filters('eto_formats', array(
        'single_elimination' => __('Eliminazione diretta', 'eto'),
        'double_elimination' => __('Doppia eliminazione', 'eto'),
        'round_robin' => __('Girone all\'italiana', 'eto'),
        'swiss' => __('Sistema svizzero', 'eto'),
        'custom' => __('Personalizzato', 'eto')
    ));
}

/**
 * Ottiene lo stato di un torneo
 *
 * @param string $status_slug Slug dello stato
 * @return string Nome dello stato
 */
function eto_get_status_name($status_slug) {
    $statuses = eto_get_statuses();
    
    return isset($statuses[$status_slug]) ? $statuses[$status_slug] : $status_slug;
}

/**
 * Ottiene la lista degli stati disponibili
 *
 * @return array Lista degli stati
 */
function eto_get_statuses() {
    return apply_filters('eto_statuses', array(
        'draft' => __('Bozza', 'eto'),
        'pending' => __('In attesa', 'eto'),
        'active' => __('Attivo', 'eto'),
        'completed' => __('Completato', 'eto'),
        'cancelled' => __('Annullato', 'eto')
    ));
}

/**
 * Verifica se un utente è il capitano di un team
 *
 * @param int $user_id ID dell'utente
 * @param int $team_id ID del team
 * @return bool True se l'utente è il capitano, false altrimenti
 */
function eto_is_team_captain($user_id, $team_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'eto_teams';
    
    $captain_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT captain_id FROM $table WHERE id = %d",
            $team_id
        )
    );
    
    return $captain_id == $user_id;
}

/**
 * Ottiene l'URL di un torneo
 *
 * @param int $tournament_id ID del torneo
 * @return string URL del torneo
 */
function eto_get_tournament_url($tournament_id) {
    $tournament_page = get_option('eto_tournament_page', 0);
    
    if (empty($tournament_page)) {
        return '';
    }
    
    return add_query_arg('tournament_id', $tournament_id, get_permalink($tournament_page));
}

/**
 * Ottiene l'URL di un team
 *
 * @param int $team_id ID del team
 * @return string URL del team
 */
function eto_get_team_url($team_id) {
    $team_page = get_option('eto_team_page', 0);
    
    if (empty($team_page)) {
        return '';
    }
    
    return add_query_arg('team_id', $team_id, get_permalink($team_page));
}

/**
 * Registra un'azione nel log
 *
 * @param string $action Azione eseguita
 * @param string $object_type Tipo di oggetto
 * @param int $object_id ID dell'oggetto
 * @param array $data Dati aggiuntivi
 * @param int $user_id ID dell'utente
 * @return bool True se il log è stato registrato, false altrimenti
 */
function eto_log_action($action, $object_type, $object_id, $data = array(), $user_id = 0) {
    global $wpdb;
    
    if (empty($user_id)) {
        $user_id = get_current_user_id();
    }
    
    $table = $wpdb->prefix . 'eto_logs';
    
    $result = $wpdb->insert(
        $table,
        array(
            'action' => $action,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'data' => json_encode($data),
            'user_id' => $user_id,
            'created_at' => current_time('mysql')
        ),
        array(
            '%s', // action
            '%s', // object_type
            '%d', // object_id
            '%s', // data
            '%d', // user_id
            '%s'  // created_at
        )
    );
    
    return $result !== false;
}

/**
 * Ottiene il nome di un utente
 *
 * @param int $user_id ID dell'utente
 * @return string Nome dell'utente
 */
function eto_get_user_name($user_id) {
    $user = get_userdata($user_id);
    
    if (!$user) {
        return __('Utente sconosciuto', 'eto');
    }
    
    return $user->display_name;
}

/**
 * Verifica se un utente può gestire un torneo
 *
 * @param int $user_id ID dell'utente
 * @param int $tournament_id ID del torneo
 * @return bool True se l'utente può gestire il torneo, false altrimenti
 */
function eto_can_manage_tournament($user_id, $tournament_id) {
    // Gli amministratori possono gestire tutti i tornei
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    global $wpdb;
    
    $table = $wpdb->prefix . 'eto_tournaments';
    
    $created_by = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT created_by FROM $table WHERE id = %d",
            $tournament_id
        )
    );
    
    return $created_by == $user_id;
}

/**
 * Verifica se un utente può gestire un team
 *
 * @param int $user_id ID dell'utente
 * @param int $team_id ID del team
 * @return bool True se l'utente può gestire il team, false altrimenti
 */
function eto_can_manage_team($user_id, $team_id) {
    // Gli amministratori possono gestire tutti i team
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Il capitano può gestire il team
    if (eto_is_team_captain($user_id, $team_id)) {
        return true;
    }
    
    global $wpdb;
    
    $table = $wpdb->prefix . 'eto_teams';
    
    $created_by = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT created_by FROM $table WHERE id = %d",
            $team_id
        )
    );
    
    return $created_by == $user_id;
}
