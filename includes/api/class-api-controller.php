<?php
/**
 * Controller principale per l'API REST
 * 
 * @package ETO
 * @subpackage API
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_API_Controller {
    /**
     * Versione dell'API
     *
     * @var string
     */
    private $api_version = 'v1';

    /**
     * Namespace dell'API
     *
     * @var string
     */
    private $api_namespace;

    /**
     * Istanza del gestore delle chiavi API
     *
     * @var ETO_API_Key_Manager
     */
    private $api_key_manager;

    /**
     * Costruttore
     */
    public function __construct() {
        $this->api_namespace = 'eto/'. $this->api_version;
        $this->api_key_manager = new ETO_API_Key_Manager();
        
        // Registra gli hook
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Registra tutte le rotte dell'API
     */
    public function register_routes() {
        // Rotte per i tornei
        register_rest_route($this->api_namespace, '/tournaments', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_tournaments'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_tournament'),
                'permission_callback' => array($this, 'create_item_permissions_check'),
            ),
        ));

        register_rest_route($this->api_namespace, '/tournaments/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_tournament'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_tournament'),
                'permission_callback' => array($this, 'update_item_permissions_check'),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_tournament'),
                'permission_callback' => array($this, 'delete_item_permissions_check'),
            ),
        ));

        // Rotte per i team
        register_rest_route($this->api_namespace, '/teams', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_teams'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_team'),
                'permission_callback' => array($this, 'create_item_permissions_check'),
            ),
        ));

        register_rest_route($this->api_namespace, '/teams/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_team'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_team'),
                'permission_callback' => array($this, 'update_item_permissions_check'),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_team'),
                'permission_callback' => array($this, 'delete_item_permissions_check'),
            ),
        ));

        // Rotte per i match
        register_rest_route($this->api_namespace, '/matches', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_matches'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_match'),
                'permission_callback' => array($this, 'create_item_permissions_check'),
            ),
        ));

        register_rest_route($this->api_namespace, '/matches/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_match'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_match'),
                'permission_callback' => array($this, 'update_item_permissions_check'),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_match'),
                'permission_callback' => array($this, 'delete_item_permissions_check'),
            ),
        ));

        // Rotta per aggiornare i risultati di un match
        register_rest_route($this->api_namespace, '/matches/(?P<id>\d+)/results', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_match_results'),
                'permission_callback' => array($this, 'update_item_permissions_check'),
            ),
        ));
    }

    /**
     * Verifica i permessi per le operazioni di lettura
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return bool|WP_Error True se l'utente ha i permessi, WP_Error in caso contrario
     */
    public function get_items_permissions_check($request) {
        // Verifica se è richiesta l'autenticazione per le operazioni di lettura
        $require_auth = get_option('eto_api_require_auth_for_read', false);
        
        if (!$require_auth) {
            return true;
        }
        
        return $this->check_api_key($request);
    }

    /**
     * Verifica i permessi per le operazioni di creazione
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return bool|WP_Error True se l'utente ha i permessi, WP_Error in caso contrario
     */
    public function create_item_permissions_check($request) {
        return $this->check_api_key($request, 'write');
    }

    /**
     * Verifica i permessi per le operazioni di aggiornamento
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return bool|WP_Error True se l'utente ha i permessi, WP_Error in caso contrario
     */
    public function update_item_permissions_check($request) {
        return $this->check_api_key($request, 'write');
    }

    /**
     * Verifica i permessi per le operazioni di eliminazione
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return bool|WP_Error True se l'utente ha i permessi, WP_Error in caso contrario
     */
    public function delete_item_permissions_check($request) {
        return $this->check_api_key($request, 'admin');
    }

    /**
     * Verifica la validità della chiave API
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @param string $required_level Livello di accesso richiesto (read, write, admin)
     * @return bool|WP_Error True se la chiave è valida, WP_Error in caso contrario
     */
    private function check_api_key($request, $required_level = 'read') {
        $api_key = $request->get_header('X-ETO-API-Key');
        
        if (empty($api_key)) {
            return new WP_Error(
                'rest_forbidden',
                __('API key mancante.', 'eto'),
                array('status' => 401)
            );
        }
        
        $key_data = $this->api_key_manager->validate_key($api_key);
        
        if (!$key_data) {
            return new WP_Error(
                'rest_forbidden',
                __('API key non valida.', 'eto'),
                array('status' => 401)
            );
        }
        
        // Verifica il livello di accesso
        $access_levels = array(
            'read'  => 1,
            'write' => 2,
            'admin' => 3
        );
        
        $key_level = $access_levels[$key_data['access_level']] ?? 0;
        $required_level_value = $access_levels[$required_level] ?? 0;
        
        if ($key_level < $required_level_value) {
            return new WP_Error(
                'rest_forbidden',
                __('Permessi insufficienti per questa operazione.', 'eto'),
                array('status' => 403)
            );
        }
        
        return true;
    }

    /**
     * Ottiene l'elenco dei tornei
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function get_tournaments($request) {
        $page = isset($request['page']) ? (int) $request['page'] : 1;
        $per_page = isset($request['per_page']) ? (int) $request['per_page'] : 10;
        $game = isset($request['game']) ? sanitize_text_field($request['game']) : '';
        $status = isset($request['status']) ? sanitize_text_field($request['status']) : '';
        
        $args = array(
            'page' => $page,
            'per_page' => $per_page
        );
        
        if (!empty($game)) {
            $args['game'] = $game;
        }
        
        if (!empty($status)) {
            $args['status'] = $status;
        }
        
        $tournaments = ETO_Tournament_Model::get_all($args);
        $total = ETO_Tournament_Model::count($args);
        
        $data = array();
        foreach ($tournaments as $tournament) {
            $data[] = $this->prepare_tournament_for_response($tournament);
        }
        
        $response = new WP_REST_Response($data, 200);
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $per_page));
        
        return $response;
    }

    /**
     * Ottiene un singolo torneo
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function get_tournament($request) {
        $id = (int) $request['id'];
        $tournament = ETO_Tournament_Model::get_by_id($id);
        
        if (!$tournament) {
            return new WP_Error(
                'rest_tournament_not_found',
                __('Torneo non trovato.', 'eto'),
                array('status' => 404)
            );
        }
        
        $data = $this->prepare_tournament_for_response($tournament);
        
        return new WP_REST_Response($data, 200);
    }

    /**
     * Crea un nuovo torneo
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function create_tournament($request) {
        $tournament_data = $this->prepare_tournament_for_database($request);
        
        // Validazione
        $validation = $this->validate_tournament_data($tournament_data);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Crea il torneo
        $tournament = new ETO_Tournament_Model();
        foreach ($tournament_data as $key => $value) {
            $tournament->set($key, $value);
        }
        
        $result = $tournament->save();
        
        if (!$result) {
            return new WP_Error(
                'rest_tournament_creation_failed',
                __('Impossibile creare il torneo.', 'eto'),
                array('status' => 500)
            );
        }
        
        $data = $this->prepare_tournament_for_response($tournament);
        
        return new WP_REST_Response($data, 201);
    }

    /**
     * Aggiorna un torneo esistente
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function update_tournament($request) {
        $id = (int) $request['id'];
        $tournament = ETO_Tournament_Model::get_by_id($id);
        
        if (!$tournament) {
            return new WP_Error(
                'rest_tournament_not_found',
                __('Torneo non trovato.', 'eto'),
                array('status' => 404)
            );
        }
        
        $tournament_data = $this->prepare_tournament_for_database($request, false);
        
        // Aggiorna il torneo
        foreach ($tournament_data as $key => $value) {
            $tournament->set($key, $value);
        }
        
        $result = $tournament->save();
        
        if (!$result) {
            return new WP_Error(
                'rest_tournament_update_failed',
                __('Impossibile aggiornare il torneo.', 'eto'),
                array('status' => 500)
            );
        }
        
        $data = $this->prepare_tournament_for_response($tournament);
        
        return new WP_REST_Response($data, 200);
    }

    /**
     * Elimina un torneo
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function delete_tournament($request) {
        $id = (int) $request['id'];
        $tournament = ETO_Tournament_Model::get_by_id($id);
        
        if (!$tournament) {
            return new WP_Error(
                'rest_tournament_not_found',
                __('Torneo non trovato.', 'eto'),
                array('status' => 404)
            );
        }
        
        $result = $tournament->delete();
        
        if (!$result) {
            return new WP_Error(
                'rest_tournament_deletion_failed',
                __('Impossibile eliminare il torneo.', 'eto'),
                array('status' => 500)
            );
        }
        
        return new WP_REST_Response(null, 204);
    }

    /**
     * Ottiene l'elenco dei team
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function get_teams($request) {
        $page = isset($request['page']) ? (int) $request['page'] : 1;
        $per_page = isset($request['per_page']) ? (int) $request['per_page'] : 10;
        $game = isset($request['game']) ? sanitize_text_field($request['game']) : '';
        $tournament_id = isset($request['tournament_id']) ? (int) $request['tournament_id'] : 0;
        
        $args = array(
            'page' => $page,
            'per_page' => $per_page
        );
        
        if (!empty($game)) {
            $args['game'] = $game;
        }
        
        if ($tournament_id > 0) {
            $args['tournament_id'] = $tournament_id;
        }
        
        $teams = ETO_Team_Model::get_all($args);
        $total = ETO_Team_Model::count($args);
        
        $data = array();
        foreach ($teams as $team) {
            $data[] = $this->prepare_team_for_response($team);
        }
        
        $response = new WP_REST_Response($data, 200);
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $per_page));
        
        return $response;
    }

    /**
     * Ottiene un singolo team
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function get_team($request) {
        $id = (int) $request['id'];
        $team = ETO_Team_Model::get_by_id($id);
        
        if (!$team) {
            return new WP_Error(
                'rest_team_not_found',
                __('Team non trovato.', 'eto'),
                array('status' => 404)
            );
        }
        
        $data = $this->prepare_team_for_response($team);
        
        return new WP_REST_Response($data, 200);
    }

    /**
     * Crea un nuovo team
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function create_team($request) {
        $team_data = $this->prepare_team_for_database($request);
        
        // Validazione
        $validation = $this->validate_team_data($team_data);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Crea il team
        $team = new ETO_Team_Model();
        foreach ($team_data as $key => $value) {
            $team->set($key, $value);
        }
        
        $result = $team->save();
        
        if (!$result) {
            return new WP_Error(
                'rest_team_creation_failed',
                __('Impossibile creare il team.', 'eto'),
                array('status' => 500)
            );
        }
        
        $data = $this->prepare_team_for_response($team);
        
        return new WP_REST_Response($data, 201);
    }

    /**
     * Aggiorna un team esistente
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function update_team($request) {
        $id = (int) $request['id'];
        $team = ETO_Team_Model::get_by_id($id);
        
        if (!$team) {
            return new WP_Error(
                'rest_team_not_found',
                __('Team non trovato.', 'eto'),
                array('status' => 404)
            );
        }
        
        $team_data = $this->prepare_team_for_database($request, false);
        
        // Aggiorna il team
        foreach ($team_data as $key => $value) {
            $team->set($key, $value);
        }
        
        $result = $team->save();
        
        if (!$result) {
            return new WP_Error(
                'rest_team_update_failed',
                __('Impossibile aggiornare il team.', 'eto'),
                array('status' => 500)
            );
        }
        
        $data = $this->prepare_team_for_response($team);
        
        return new WP_REST_Response($data, 200);
    }

    /**
     * Elimina un team
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function delete_team($request) {
        $id = (int) $request['id'];
        $team = ETO_Team_Model::get_by_id($id);
        
        if (!$team) {
            return new WP_Error(
                'rest_team_not_found',
                __('Team non trovato.', 'eto'),
                array('status' => 404)
            );
        }
        
        $result = $team->delete();
        
        if (!$result) {
            return new WP_Error(
                'rest_team_deletion_failed',
                __('Impossibile eliminare il team.', 'eto'),
                array('status' => 500)
            );
        }
        
        return new WP_REST_Response(null, 204);
    }

    /**
     * Ottiene l'elenco dei match
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function get_matches($request) {
        $page = isset($request['page']) ? (int) $request['page'] : 1;
        $per_page = isset($request['per_page']) ? (int) $request['per_page'] : 10;
        $tournament_id = isset($request['tournament_id']) ? (int) $request['tournament_id'] : 0;
        $team_id = isset($request['team_id']) ? (int) $request['team_id'] : 0;
        $status = isset($request['status']) ? sanitize_text_field($request['status']) : '';
        
        $args = array(
            'page' => $page,
            'per_page' => $per_page
        );
        
        if ($tournament_id > 0) {
            $args['tournament_id'] = $tournament_id;
        }
        
        if ($team_id > 0) {
            $args['team_id'] = $team_id;
        }
        
        if (!empty($status)) {
            $args['status'] = $status;
        }
        
        $matches = ETO_Match_Model::get_all($args);
        $total = ETO_Match_Model::count($args);
        
        $data = array();
        foreach ($matches as $match) {
            $data[] = $this->prepare_match_for_response($match);
        }
        
        $response = new WP_REST_Response($data, 200);
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $per_page));
        
        return $response;
    }

    /**
     * Ottiene un singolo match
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function get_match($request) {
        $id = (int) $request['id'];
        $match = ETO_Match_Model::get_by_id($id);
        
        if (!$match) {
            return new WP_Error(
                'rest_match_not_found',
                __('Match non trovato.', 'eto'),
                array('status' => 404)
            );
        }
        
        $data = $this->prepare_match_for_response($match);
        
        return new WP_REST_Response($data, 200);
    }

    /**
     * Crea un nuovo match
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function create_match($request) {
        $match_data = $this->prepare_match_for_database($request);
        
        // Validazione
        $validation = $this->validate_match_data($match_data);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Crea il match
        $match = new ETO_Match_Model();
        foreach ($match_data as $key => $value) {
            $match->set($key, $value);
        }
        
        $result = $match->save();
        
        if (!$result) {
            return new WP_Error(
                'rest_match_creation_failed',
                __('Impossibile creare il match.', 'eto'),
                array('status' => 500)
            );
        }
        
        $data = $this->prepare_match_for_response($match);
        
        return new WP_REST_Response($data, 201);
    }

    /**
     * Aggiorna un match esistente
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function update_match($request) {
        $id = (int) $request['id'];
        $match = ETO_Match_Model::get_by_id($id);
        
        if (!$match) {
            return new WP_Error(
                'rest_match_not_found',
                __('Match non trovato.', 'eto'),
                array('status' => 404)
            );
        }
        
        $match_data = $this->prepare_match_for_database($request, false);
        
        // Aggiorna il match
        foreach ($match_data as $key => $value) {
            $match->set($key, $value);
        }
        
        $result = $match->save();
        
        if (!$result) {
            return new WP_Error(
                'rest_match_update_failed',
                __('Impossibile aggiornare il match.', 'eto'),
                array('status' => 500)
            );
        }
        
        $data = $this->prepare_match_for_response($match);
        
        return new WP_REST_Response($data, 200);
    }

    /**
     * Elimina un match
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function delete_match($request) {
        $id = (int) $request['id'];
        $match = ETO_Match_Model::get_by_id($id);
        
        if (!$match) {
            return new WP_Error(
                'rest_match_not_found',
                __('Match non trovato.', 'eto'),
                array('status' => 404)
            );
        }
        
        $result = $match->delete();
        
        if (!$result) {
            return new WP_Error(
                'rest_match_deletion_failed',
                __('Impossibile eliminare il match.', 'eto'),
                array('status' => 500)
            );
        }
        
        return new WP_REST_Response(null, 204);
    }

    /**
     * Aggiorna i risultati di un match
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @return WP_REST_Response|WP_Error Risposta REST o errore
     */
    public function update_match_results($request) {
        $id = (int) $request['id'];
        $match = ETO_Match_Model::get_by_id($id);
        
        if (!$match) {
            return new WP_Error(
                'rest_match_not_found',
                __('Match non trovato.', 'eto'),
                array('status' => 404)
            );
        }
        
        $team1_score = isset($request['team1_score']) ? (int) $request['team1_score'] : null;
        $team2_score = isset($request['team2_score']) ? (int) $request['team2_score'] : null;
        
        if ($team1_score === null || $team2_score === null) {
            return new WP_Error(
                'rest_missing_param',
                __('Risultati mancanti. Specificare team1_score e team2_score.', 'eto'),
                array('status' => 400)
            );
        }
        
        $result = array(
            'team1_score' => $team1_score,
            'team2_score' => $team2_score,
            'updated_at' => current_time('mysql')
        );
        
        $match->set('result', $result);
        $match->set('status', 'completed');
        
        $save_result = $match->save();
        
        if (!$save_result) {
            return new WP_Error(
                'rest_match_update_failed',
                __('Impossibile aggiornare i risultati del match.', 'eto'),
                array('status' => 500)
            );
        }
        
        $data = $this->prepare_match_for_response($match);
        
        return new WP_REST_Response($data, 200);
    }

    /**
     * Prepara i dati del torneo per la risposta API
     *
     * @param ETO_Tournament_Model $tournament Oggetto torneo
     * @return array Dati formattati per la risposta
     */
    private function prepare_tournament_for_response($tournament) {
        $data = array(
            'id' => (int) $tournament->get('id'),
            'name' => $tournament->get('name'),
            'description' => $tournament->get('description'),
            'game' => $tournament->get('game'),
            'format' => $tournament->get('format'),
            'start_date' => $tournament->get('start_date'),
            'end_date' => $tournament->get('end_date'),
            'registration_start' => $tournament->get('registration_start'),
            'registration_end' => $tournament->get('registration_end'),
            'status' => $tournament->get('status'),
            'min_teams' => (int) $tournament->get('min_teams'),
            'max_teams' => (int) $tournament->get('max_teams'),
            'rules' => $tournament->get('rules'),
            'prizes' => $tournament->get('prizes'),
            'featured_image' => $tournament->get('featured_image'),
            'created_at' => $tournament->get('created_at'),
            'updated_at' => $tournament->get('updated_at')
        );
        
        // Includi team e match solo se richiesto
        if (isset($_GET['include']) && strpos($_GET['include'], 'teams') !== false) {
            $data['teams'] = $tournament->get_teams();
        }
        
        if (isset($_GET['include']) && strpos($_GET['include'], 'matches') !== false) {
            $data['matches'] = $tournament->get_matches();
        }
        
        return $data;
    }

    /**
     * Prepara i dati del team per la risposta API
     *
     * @param ETO_Team_Model $team Oggetto team
     * @return array Dati formattati per la risposta
     */
    private function prepare_team_for_response($team) {
        $data = array(
            'id' => (int) $team->get('id'),
            'name' => $team->get('name'),
            'description' => $team->get('description'),
            'game' => $team->get('game'),
            'captain_id' => (int) $team->get('captain_id'),
            'logo_url' => $team->get('logo_url'),
            'email' => $team->get('email'),
            'website' => $team->get('website'),
            'social_media' => $team->get('social_media'),
            'created_at' => $team->get('created_at'),
            'updated_at' => $team->get('updated_at')
        );
        
        // Includi membri e tornei solo se richiesto
        if (isset($_GET['include']) && strpos($_GET['include'], 'members') !== false) {
            $data['members'] = $team->get_members();
        }
        
        if (isset($_GET['include']) && strpos($_GET['include'], 'tournaments') !== false) {
            $data['tournaments'] = $team->get_tournaments();
        }
        
        return $data;
    }

    /**
     * Prepara i dati del match per la risposta API
     *
     * @param ETO_Match_Model $match Oggetto match
     * @return array Dati formattati per la risposta
     */
    private function prepare_match_for_response($match) {
        $data = array(
            'id' => (int) $match->get('id'),
            'tournament_id' => (int) $match->get('tournament_id'),
            'team1_id' => (int) $match->get('team1_id'),
            'team2_id' => (int) $match->get('team2_id'),
            'round' => (int) $match->get('round'),
            'match_number' => (int) $match->get('match_number'),
            'scheduled_date' => $match->get('scheduled_date'),
            'status' => $match->get('status'),
            'stream_url' => $match->get('stream_url'),
            'notes' => $match->get('notes'),
            'created_at' => $match->get('created_at'),
            'updated_at' => $match->get('updated_at')
        );
        
        // Aggiungi i risultati se disponibili
        $result = $match->get_result();
        if ($result) {
            $data['result'] = $result;
        }
        
        return $data;
    }

    /**
     * Prepara i dati del torneo per il database
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @param bool $is_new Se true, stiamo creando un nuovo torneo
     * @return array Dati formattati per il database
     */
    private function prepare_tournament_for_database($request, $is_new = true) {
        $data = array();
        
        // Campi obbligatori per un nuovo torneo
        if ($is_new) {
            $data['name'] = isset($request['name']) ? sanitize_text_field($request['name']) : '';
            $data['game'] = isset($request['game']) ? sanitize_text_field($request['game']) : '';
            $data['format'] = isset($request['format']) ? sanitize_text_field($request['format']) : '';
            $data['start_date'] = isset($request['start_date']) ? sanitize_text_field($request['start_date']) : '';
            $data['end_date'] = isset($request['end_date']) ? sanitize_text_field($request['end_date']) : '';
            $data['status'] = isset($request['status']) ? sanitize_text_field($request['status']) : 'pending';
            $data['created_at'] = current_time('mysql');
        } else {
            // Campi che possono essere aggiornati
            if (isset($request['name'])) {
                $data['name'] = sanitize_text_field($request['name']);
            }
            
            if (isset($request['game'])) {
                $data['game'] = sanitize_text_field($request['game']);
            }
            
            if (isset($request['format'])) {
                $data['format'] = sanitize_text_field($request['format']);
            }
            
            if (isset($request['start_date'])) {
                $data['start_date'] = sanitize_text_field($request['start_date']);
            }
            
            if (isset($request['end_date'])) {
                $data['end_date'] = sanitize_text_field($request['end_date']);
            }
            
            if (isset($request['status'])) {
                $data['status'] = sanitize_text_field($request['status']);
            }
        }
        
        // Campi opzionali
        if (isset($request['description'])) {
            $data['description'] = wp_kses_post($request['description']);
        }
        
        if (isset($request['registration_start'])) {
            $data['registration_start'] = sanitize_text_field($request['registration_start']);
        }
        
        if (isset($request['registration_end'])) {
            $data['registration_end'] = sanitize_text_field($request['registration_end']);
        }
        
        if (isset($request['min_teams'])) {
            $data['min_teams'] = (int) $request['min_teams'];
        }
        
        if (isset($request['max_teams'])) {
            $data['max_teams'] = (int) $request['max_teams'];
        }
        
        if (isset($request['rules'])) {
            $data['rules'] = wp_kses_post($request['rules']);
        }
        
        if (isset($request['prizes'])) {
            $data['prizes'] = wp_kses_post($request['prizes']);
        }
        
        if (isset($request['featured_image'])) {
            $data['featured_image'] = esc_url_raw($request['featured_image']);
        }
        
        $data['updated_at'] = current_time('mysql');
        
        return $data;
    }

    /**
     * Prepara i dati del team per il database
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @param bool $is_new Se true, stiamo creando un nuovo team
     * @return array Dati formattati per il database
     */
    private function prepare_team_for_database($request, $is_new = true) {
        $data = array();
        
        // Campi obbligatori per un nuovo team
        if ($is_new) {
            $data['name'] = isset($request['name']) ? sanitize_text_field($request['name']) : '';
            $data['game'] = isset($request['game']) ? sanitize_text_field($request['game']) : '';
            $data['captain_id'] = isset($request['captain_id']) ? (int) $request['captain_id'] : 0;
            $data['created_at'] = current_time('mysql');
        } else {
            // Campi che possono essere aggiornati
            if (isset($request['name'])) {
                $data['name'] = sanitize_text_field($request['name']);
            }
            
            if (isset($request['game'])) {
                $data['game'] = sanitize_text_field($request['game']);
            }
            
            if (isset($request['captain_id'])) {
                $data['captain_id'] = (int) $request['captain_id'];
            }
        }
        
        // Campi opzionali
        if (isset($request['description'])) {
            $data['description'] = wp_kses_post($request['description']);
        }
        
        if (isset($request['logo_url'])) {
            $data['logo_url'] = esc_url_raw($request['logo_url']);
        }
        
        if (isset($request['email'])) {
            $data['email'] = sanitize_email($request['email']);
        }
        
        if (isset($request['website'])) {
            $data['website'] = esc_url_raw($request['website']);
        }
        
        if (isset($request['social_media']) && is_array($request['social_media'])) {
            $social_media = array();
            foreach ($request['social_media'] as $platform => $url) {
                $social_media[sanitize_text_field($platform)] = esc_url_raw($url);
            }
            $data['social_media'] = $social_media;
        }
        
        $data['updated_at'] = current_time('mysql');
        
        return $data;
    }

    /**
     * Prepara i dati del match per il database
     *
     * @param WP_REST_Request $request Richiesta REST completa
     * @param bool $is_new Se true, stiamo creando un nuovo match
     * @return array Dati formattati per il database
     */
    private function prepare_match_for_database($request, $is_new = true) {
        $data = array();
        
        // Campi obbligatori per un nuovo match
        if ($is_new) {
            $data['tournament_id'] = isset($request['tournament_id']) ? (int) $request['tournament_id'] : 0;
            $data['team1_id'] = isset($request['team1_id']) ? (int) $request['team1_id'] : 0;
            $data['team2_id'] = isset($request['team2_id']) ? (int) $request['team2_id'] : 0;
            $data['round'] = isset($request['round']) ? (int) $request['round'] : 1;
            $data['match_number'] = isset($request['match_number']) ? (int) $request['match_number'] : 1;
            $data['scheduled_date'] = isset($request['scheduled_date']) ? sanitize_text_field($request['scheduled_date']) : '';
            $data['status'] = isset($request['status']) ? sanitize_text_field($request['status']) : 'pending';
            $data['created_at'] = current_time('mysql');
        } else {
            // Campi che possono essere aggiornati
            if (isset($request['tournament_id'])) {
                $data['tournament_id'] = (int) $request['tournament_id'];
            }
            
            if (isset($request['team1_id'])) {
                $data['team1_id'] = (int) $request['team1_id'];
            }
            
            if (isset($request['team2_id'])) {
                $data['team2_id'] = (int) $request['team2_id'];
            }
            
            if (isset($request['round'])) {
                $data['round'] = (int) $request['round'];
            }
            
            if (isset($request['match_number'])) {
                $data['match_number'] = (int) $request['match_number'];
            }
            
            if (isset($request['scheduled_date'])) {
                $data['scheduled_date'] = sanitize_text_field($request['scheduled_date']);
            }
            
            if (isset($request['status'])) {
                $data['status'] = sanitize_text_field($request['status']);
            }
        }
        
        // Campi opzionali
        if (isset($request['stream_url'])) {
            $data['stream_url'] = esc_url_raw($request['stream_url']);
        }
        
        if (isset($request['notes'])) {
            $data['notes'] = wp_kses_post($request['notes']);
        }
        
        // Risultati
        if (isset($request['team1_score']) && isset($request['team2_score'])) {
            $data['result'] = array(
                'team1_score' => (int) $request['team1_score'],
                'team2_score' => (int) $request['team2_score'],
                'updated_at' => current_time('mysql')
            );
            
            // Se vengono forniti i risultati, imposta lo stato su completato
            $data['status'] = 'completed';
        }
        
        $data['updated_at'] = current_time('mysql');
        
        return $data;
    }

    /**
     * Valida i dati del torneo
     *
     * @param array $data Dati del torneo
     * @return true|WP_Error True se i dati sono validi, WP_Error in caso contrario
     */
    private function validate_tournament_data($data) {
        if (empty($data['name'])) {
            return new WP_Error(
                'rest_missing_param',
                __('Il nome del torneo è obbligatorio.', 'eto'),
                array('status' => 400)
            );
        }
        
        if (empty($data['game'])) {
            return new WP_Error(
                'rest_missing_param',
                __('Il gioco è obbligatorio.', 'eto'),
                array('status' => 400)
            );
        }
        
        if (empty($data['format'])) {
            return new WP_Error(
                'rest_missing_param',
                __('Il formato del torneo è obbligatorio.', 'eto'),
                array('status' => 400)
            );
        }
        
        if (empty($data['start_date'])) {
            return new WP_Error(
                'rest_missing_param',
                __('La data di inizio è obbligatoria.', 'eto'),
                array('status' => 400)
            );
        }
        
        if (empty($data['end_date'])) {
            return new WP_Error(
                'rest_missing_param',
                __('La data di fine è obbligatoria.', 'eto'),
                array('status' => 400)
            );
        }
        
        // Verifica che la data di fine sia successiva alla data di inizio
        if (strtotime($data['end_date']) < strtotime($data['start_date'])) {
            return new WP_Error(
                'rest_invalid_param',
                __('La data di fine deve essere successiva alla data di inizio.', 'eto'),
                array('status' => 400)
            );
        }
        
        return true;
    }

    /**
     * Valida i dati del team
     *
     * @param array $data Dati del team
     * @return true|WP_Error True se i dati sono validi, WP_Error in caso contrario
     */
    private function validate_team_data($data) {
        if (empty($data['name'])) {
            return new WP_Error(
                'rest_missing_param',
                __('Il nome del team è obbligatorio.', 'eto'),
                array('status' => 400)
            );
        }
        
        if (empty($data['game'])) {
            return new WP_Error(
                'rest_missing_param',
                __('Il gioco è obbligatorio.', 'eto'),
                array('status' => 400)
            );
        }
        
        if (empty($data['captain_id']) || $data['captain_id'] <= 0) {
            return new WP_Error(
                'rest_missing_param',
                __('L\'ID del capitano è obbligatorio.', 'eto'),
                array('status' => 400)
            );
        }
        
        // Verifica che l'utente esista
        $user = get_userdata($data['captain_id']);
        if (!$user) {
            return new WP_Error(
                'rest_invalid_param',
                __('L\'utente specificato come capitano non esiste.', 'eto'),
                array('status' => 400)
            );
        }
        
        return true;
    }

    /**
     * Valida i dati del match
     *
     * @param array $data Dati del match
     * @return true|WP_Error True se i dati sono validi, WP_Error in caso contrario
     */
    private function validate_match_data($data) {
        if (empty($data['tournament_id']) || $data['tournament_id'] <= 0) {
            return new WP_Error(
                'rest_missing_param',
                __('L\'ID del torneo è obbligatorio.', 'eto'),
                array('status' => 400)
            );
        }
        
        // Verifica che il torneo esista
        $tournament = ETO_Tournament_Model::get_by_id($data['tournament_id']);
        if (!$tournament) {
            return new WP_Error(
                'rest_invalid_param',
                __('Il torneo specificato non esiste.', 'eto'),
                array('status' => 400)
            );
        }
        
        // Verifica che i team esistano se specificati
        if (!empty($data['team1_id']) && $data['team1_id'] > 0) {
            $team1 = ETO_Team_Model::get_by_id($data['team1_id']);
            if (!$team1) {
                return new WP_Error(
                    'rest_invalid_param',
                    __('Il team 1 specificato non esiste.', 'eto'),
                    array('status' => 400)
                );
            }
        }
        
        if (!empty($data['team2_id']) && $data['team2_id'] > 0) {
            $team2 = ETO_Team_Model::get_by_id($data['team2_id']);
            if (!$team2) {
                return new WP_Error(
                    'rest_invalid_param',
                    __('Il team 2 specificato non esiste.', 'eto'),
                    array('status' => 400)
                );
            }
        }
        
        if (empty($data['scheduled_date'])) {
            return new WP_Error(
                'rest_missing_param',
                __('La data programmata è obbligatoria.', 'eto'),
                array('status' => 400)
            );
        }
        
        return true;
    }
}

// Inizializza il controller dell'API
$eto_api_controller = new ETO_API_Controller();
