<?php
/**
 * Pagine di amministrazione del plugin
 * 
 * Contiene funzioni per la gestione delle pagine di amministrazione
 * 
 * @package ETO
 * @since 2.5.0
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

/**
 * Gestisce la pagina di aggiunta di un torneo
 */
function eto_admin_add_tournament_page() {
    // Verifica i permessi
    if (!current_user_can('manage_options')) {
        wp_die(__('Non hai i permessi per accedere a questa pagina', 'eto'));
    }
    
    // Gestisci il salvataggio del form
    if (isset($_POST['eto_add_tournament']) && isset($_POST['eto_tournament_nonce']) && wp_verify_nonce($_POST['eto_tournament_nonce'], 'eto_add_tournament')) {
        // Sanitizza e valida i dati
        $name = sanitize_text_field($_POST['name']);
        $description = wp_kses_post($_POST['description']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $registration_start = sanitize_text_field($_POST['registration_start']);
        $registration_end = sanitize_text_field($_POST['registration_end']);
        $max_teams = intval($_POST['max_teams']);
        $min_teams = intval($_POST['min_teams']);
        $format = sanitize_text_field($_POST['format']);
        $game = sanitize_text_field($_POST['game']);
        $third_place_match = isset($_POST['third_place_match']) ? 1 : 0;
        $is_individual = isset($_POST['is_individual']) ? 1 : 0;
        
        // Crea il torneo
        $tournament_data = array(
            'name' => $name,
            'description' => $description,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'registration_start' => $registration_start,
            'registration_end' => $registration_end,
            'max_teams' => $max_teams,
            'min_teams' => $min_teams,
            'format' => $format,
            'game' => $game,
            'status' => 'draft',
            'third_place_match' => $third_place_match,
            'is_individual' => $is_individual,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $tournament = new ETO_Tournament_Model();
        $tournament_id = $tournament->create($tournament_data);
        
        if ($tournament_id) {
            // Salva i metadati aggiuntivi
            if (isset($_POST['meta']) && is_array($_POST['meta'])) {
                foreach ($_POST['meta'] as $key => $value) {
                    $tournament->set_meta($key, $value);
                }
            }
            
            // Redirect alla pagina di modifica
            wp_redirect(admin_url('admin.php?page=eto-tournaments&action=edit&id=' . $tournament_id . '&message=1'));
            exit;
        } else {
            // Errore durante la creazione
            $error_message = __('Errore durante la creazione del torneo', 'eto');
        }
    }
    
    // Carica il template
    include ETO_PLUGIN_DIR . 'admin/views/tournaments/add.php';
}

/**
 * Gestisce la pagina di modifica di un torneo
 */
function eto_admin_edit_tournament_page() {
    // Verifica i permessi
    if (!current_user_can('manage_options')) {
        wp_die(__('Non hai i permessi per accedere a questa pagina', 'eto'));
    }
    
    // Verifica l'ID del torneo
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        wp_die(__('ID torneo non specificato', 'eto'));
    }
    
    $tournament_id = intval($_GET['id']);
    $tournament = new ETO_Tournament_Model($tournament_id);
    
    if (!$tournament) {
        wp_die(__('Torneo non trovato', 'eto'));
    }
    
    // Gestisci il salvataggio del form
    if (isset($_POST['eto_edit_tournament']) && isset($_POST['eto_tournament_nonce']) && wp_verify_nonce($_POST['eto_tournament_nonce'], 'eto_edit_tournament')) {
        // Sanitizza e valida i dati
        $name = sanitize_text_field($_POST['name']);
        $description = wp_kses_post($_POST['description']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $registration_start = sanitize_text_field($_POST['registration_start']);
        $registration_end = sanitize_text_field($_POST['registration_end']);
        $max_teams = intval($_POST['max_teams']);
        $min_teams = intval($_POST['min_teams']);
        $format = sanitize_text_field($_POST['format']);
        $game = sanitize_text_field($_POST['game']);
        $status = sanitize_text_field($_POST['status']);
        $third_place_match = isset($_POST['third_place_match']) ? 1 : 0;
        $is_individual = isset($_POST['is_individual']) ? 1 : 0;
        
        // Aggiorna il torneo
        $tournament_data = array(
            'name' => $name,
            'description' => $description,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'registration_start' => $registration_start,
            'registration_end' => $registration_end,
            'max_teams' => $max_teams,
            'min_teams' => $min_teams,
            'format' => $format,
            'game' => $game,
            'status' => $status,
            'third_place_match' => $third_place_match,
            'is_individual' => $is_individual,
            'updated_at' => current_time('mysql')
        );
        
        $result = $tournament->update($tournament_data);
        
        if ($result) {
            // Salva i metadati aggiuntivi
            if (isset($_POST['meta']) && is_array($_POST['meta'])) {
                foreach ($_POST['meta'] as $key => $value) {
                    $tournament->set_meta($key, $value);
                }
            }
            
            // Messaggio di successo
            $success_message = __('Torneo aggiornato con successo', 'eto');
        } else {
            // Errore durante l'aggiornamento
            $error_message = __('Errore durante l\'aggiornamento del torneo', 'eto');
        }
    }
    
    // Carica il template
    include ETO_PLUGIN_DIR . 'admin/views/tournaments/edit.php';
}

/**
 * Gestisce la pagina di aggiunta di un team
 */
function eto_admin_add_team_page() {
    // Verifica i permessi
    if (!current_user_can('manage_options')) {
        wp_die(__('Non hai i permessi per accedere a questa pagina', 'eto'));
    }
    
    // Gestisci il salvataggio del form
    if (isset($_POST['eto_add_team']) && isset($_POST['eto_team_nonce']) && wp_verify_nonce($_POST['eto_team_nonce'], 'eto_add_team')) {
        // Sanitizza e valida i dati
        $name = sanitize_text_field($_POST['name']);
        $slug = sanitize_title($_POST['name']);
        $description = wp_kses_post($_POST['description']);
        $logo = sanitize_text_field($_POST['logo']);
        $captain_id = intval($_POST['captain_id']);
        $game = sanitize_text_field($_POST['game']);
        
        // Crea il team
        $team_data = array(
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'logo' => $logo,
            'captain_id' => $captain_id,
            'game' => $game,
            'status' => 'active',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $team = new ETO_Team_Model();
        $team_id = $team->create($team_data);
        
        if ($team_id) {
            // Aggiungi il capitano come membro
            $team->add_member($captain_id, 'captain');
            
            // Salva i metadati aggiuntivi
            if (isset($_POST['meta']) && is_array($_POST['meta'])) {
                foreach ($_POST['meta'] as $key => $value) {
                    $team->set_meta($key, $value);
                }
            }
            
            // Redirect alla pagina di modifica
            wp_redirect(admin_url('admin.php?page=eto-teams&action=edit&id=' . $team_id . '&message=1'));
            exit;
        } else {
            // Errore durante la creazione
            $error_message = __('Errore durante la creazione del team', 'eto');
        }
    }
    
    // Carica il template
    include ETO_PLUGIN_DIR . 'admin/views/teams/add.php';
}

/**
 * Gestisce la pagina di modifica di un team
 */
function eto_admin_edit_team_page() {
    // Verifica i permessi
    if (!current_user_can('manage_options')) {
        wp_die(__('Non hai i permessi per accedere a questa pagina', 'eto'));
    }
    
    // Verifica l'ID del team
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        wp_die(__('ID team non specificato', 'eto'));
    }
    
    $team_id = intval($_GET['id']);
    $team = new ETO_Team_Model($team_id);
    
    if (!$team) {
        wp_die(__('Team non trovato', 'eto'));
    }
    
    // Gestisci il salvataggio del form
    if (isset($_POST['eto_edit_team']) && isset($_POST['eto_team_nonce']) && wp_verify_nonce($_POST['eto_team_nonce'], 'eto_edit_team')) {
        // Sanitizza e valida i dati
        $name = sanitize_text_field($_POST['name']);
        $description = wp_kses_post($_POST['description']);
        $logo = sanitize_text_field($_POST['logo']);
        $captain_id = intval($_POST['captain_id']);
        $game = sanitize_text_field($_POST['game']);
        $status = sanitize_text_field($_POST['status']);
        
        // Aggiorna il team
        $team_data = array(
            'name' => $name,
            'description' => $description,
            'logo' => $logo,
            'captain_id' => $captain_id,
            'game' => $game,
            'status' => $status,
            'updated_at' => current_time('mysql')
        );
        
        $result = $team->update($team_data);
        
        if ($result) {
            // Salva i metadati aggiuntivi
            if (isset($_POST['meta']) && is_array($_POST['meta'])) {
                foreach ($_POST['meta'] as $key => $value) {
                    $team->set_meta($key, $value);
                }
            }
            
            // Messaggio di successo
            $success_message = __('Team aggiornato con successo', 'eto');
        } else {
            // Errore durante l'aggiornamento
            $error_message = __('Errore durante l\'aggiornamento del team', 'eto');
        }
    }
    
    // Carica il template
    include ETO_PLUGIN_DIR . 'admin/views/teams/edit.php';
}

/**
 * Gestisce la pagina di aggiunta di un match
 */
function eto_admin_add_match_page() {
    // Verifica i permessi
    if (!current_user_can('manage_options')) {
        wp_die(__('Non hai i permessi per accedere a questa pagina', 'eto'));
    }
    
    // Gestisci il salvataggio del form
    if (isset($_POST['eto_add_match']) && isset($_POST['eto_match_nonce']) && wp_verify_nonce($_POST['eto_match_nonce'], 'eto_add_match')) {
        // Sanitizza e valida i dati
        $tournament_id = intval($_POST['tournament_id']);
        $team1_id = !empty($_POST['team1_id']) ? intval($_POST['team1_id']) : null;
        $team2_id = !empty($_POST['team2_id']) ? intval($_POST['team2_id']) : null;
        $round = intval($_POST['round']);
        $match_number = intval($_POST['match_number']);
        $scheduled_date = sanitize_text_field($_POST['scheduled_date']);
        $status = sanitize_text_field($_POST['status']);
        $is_third_place_match = isset($_POST['is_third_place_match']) ? 1 : 0;
        
        // Crea il match
        $match_data = array(
            'tournament_id' => $tournament_id,
            'team1_id' => $team1_id,
            'team2_id' => $team2_id,
            'round' => $round,
            'match_number' => $match_number,
            'scheduled_date' => $scheduled_date,
            'status' => $status,
            'is_third_place_match' => $is_third_place_match,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $match = new ETO_Match_Model();
        $match_id = $match->create($match_data);
        
        if ($match_id) {
            // Salva i metadati aggiuntivi
            if (isset($_POST['meta']) && is_array($_POST['meta'])) {
                foreach ($_POST['meta'] as $key => $value) {
                    $match->set_meta($key, $value);
                }
            }
            
            // Redirect alla pagina di modifica
            wp_redirect(admin_url('admin.php?page=eto-matches&action=edit&id=' . $match_id . '&message=1'));
            exit;
        } else {
            // Errore durante la creazione
            $error_message = __('Errore durante la creazione del match', 'eto');
        }
    }
    
    // Carica il template
    include ETO_PLUGIN_DIR . 'admin/views/matches/add.php';
}

/**
 * Gestisce la pagina di modifica di un match
 */
function eto_admin_edit_match_page() {
    // Verifica i permessi
    if (!current_user_can('manage_options')) {
        wp_die(__('Non hai i permessi per accedere a questa pagina', 'eto'));
    }
    
    // Verifica l'ID del match
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        wp_die(__('ID match non specificato', 'eto'));
    }
    
    $match_id = intval($_GET['id']);
    $match = new ETO_Match_Model($match_id);
    
    if (!$match) {
        wp_die(__('Match non trovato', 'eto'));
    }
    
    // Gestisci il salvataggio del form
    if (isset($_POST['eto_edit_match']) && isset($_POST['eto_match_nonce']) && wp_verify_nonce($_POST['eto_match_nonce'], 'eto_edit_match')) {
        // Sanitizza e valida i dati
        $team1_id = !empty($_POST['team1_id']) ? intval($_POST['team1_id']) : null;
        $team2_id = !empty($_POST['team2_id']) ? intval($_POST['team2_id']) : null;
        $round = intval($_POST['round']);
        $match_number = intval($_POST['match_number']);
        $scheduled_date = sanitize_text_field($_POST['scheduled_date']);
        $status = sanitize_text_field($_POST['status']);
        $winner_id = !empty($_POST['winner_id']) ? intval($_POST['winner_id']) : null;
        $loser_id = !empty($_POST['loser_id']) ? intval($_POST['loser_id']) : null;
        $is_third_place_match = isset($_POST['is_third_place_match']) ? 1 : 0;
        
        // Aggiorna il match
        $match_data = array(
            'team1_id' => $team1_id,
            'team2_id' => $team2_id,
            'round' => $round,
            'match_number' => $match_number,
            'scheduled_date' => $scheduled_date,
            'status' => $status,
            'winner_id' => $winner_id,
            'loser_id' => $loser_id,
            'is_third_place_match' => $is_third_place_match,
            'updated_at' => current_time('mysql')
        );
        
        $result = $match->update($match_data);
        
        if ($result) {
            // Aggiorna il risultato se fornito
            if (isset($_POST['team1_score']) && isset($_POST['team2_score'])) {
                $team1_score = intval($_POST['team1_score']);
                $team2_score = intval($_POST['team2_score']);
                $match->set_result($team1_score, $team2_score);
            }
            
            // Salva i metadati aggiuntivi
            if (isset($_POST['meta']) && is_array($_POST['meta'])) {
                foreach ($_POST['meta'] as $key => $value) {
                    $match->set_meta($key, $value);
                }
            }
            
            // Messaggio di successo
            $success_message = __('Match aggiornato con successo', 'eto');
        } else {
            // Errore durante l'aggiornamento
            $error_message = __('Errore durante l\'aggiornamento del match', 'eto');
        }
    }
    
    // Carica il template
    include ETO_PLUGIN_DIR . 'admin/views/matches/edit.php';
}

/**
 * Gestisce la pagina delle impostazioni
 */
function eto_admin_settings_page() {
    // Verifica i permessi
    if (!current_user_can('manage_options')) {
        wp_die(__('Non hai i permessi per accedere a questa pagina', 'eto'));
    }
    
    // Carica il template
    include ETO_PLUGIN_DIR . 'admin/views/settings.php';
}
