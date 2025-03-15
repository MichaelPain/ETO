<?php
/**
 * Classe per la gestione delle notifiche
 * 
 * Gestisce l'invio di email e notifiche agli utenti
 * 
 * @package ETO
 * @since 2.5.1
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Notifications {
    
    /**
     * Istanza singleton
     *
     * @var ETO_Notifications
     */
    private static $instance = null;
    
    /**
     * Istanza del database query
     *
     * @var ETO_DB_Query
     */
    private $db_query;
    
    /**
     * Ottiene l'istanza singleton
     *
     * @return ETO_Notifications
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Costruttore
     */
    private function __construct() {
        $this->db_query = new ETO_DB_Query();
        
        // Registra gli hook per le notifiche
        $this->register_hooks();
    }
    
    /**
     * Registra gli hook per le notifiche
     */
    private function register_hooks() {
        // Notifiche per tornei
        add_action('eto_tournament_created', [$this, 'notify_tournament_created'], 10, 2);
        add_action('eto_tournament_updated', [$this, 'notify_tournament_updated'], 10, 2);
        add_action('eto_tournament_starting_soon', [$this, 'notify_tournament_starting_soon'], 10, 1);
        
        // Notifiche per team
        add_action('eto_team_created', [$this, 'notify_team_created'], 10, 2);
        add_action('eto_team_member_added', [$this, 'notify_team_member_added'], 10, 3);
        add_action('eto_team_member_removed', [$this, 'notify_team_member_removed'], 10, 3);
        
        // Notifiche per match
        add_action('eto_match_scheduled', [$this, 'notify_match_scheduled'], 10, 1);
        add_action('eto_match_completed', [$this, 'notify_match_completed'], 10, 1);
        
        // Notifiche per check-in
        add_action('eto_checkin_open', [$this, 'notify_checkin_open'], 10, 1);
        add_action('eto_checkin_reminder', [$this, 'notify_checkin_reminder'], 10, 1);
        add_action('eto_checkin_closed', [$this, 'notify_checkin_closed'], 10, 1);
        
        // Pianificazione delle notifiche
        add_action('eto_daily_maintenance', [$this, 'schedule_notifications']);
    }
    
    /**
     * Pianifica le notifiche giornaliere
     */
    public function schedule_notifications() {
        $this->schedule_tournament_starting_notifications();
        $this->schedule_checkin_notifications();
    }
    
    /**
     * Pianifica le notifiche per i tornei in partenza
     */
    private function schedule_tournament_starting_notifications() {
        global $wpdb;
        
        $table_tournaments = $this->db_query->get_table_name('tournaments');
        
        // Trova i tornei che iniziano tra 24 ore
        $tomorrow = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $today = date('Y-m-d H:i:s');
        
        $tournaments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_tournaments
                WHERE status = 'active'
                AND start_date BETWEEN %s AND %s",
                $today,
                $tomorrow
            )
        );
        
        foreach ($tournaments as $tournament) {
            do_action('eto_tournament_starting_soon', $tournament->id);
        }
    }
    
    /**
     * Pianifica le notifiche per il check-in
     */
    private function schedule_checkin_notifications() {
        global $wpdb;
        
        $table_tournaments = $this->db_query->get_table_name('tournaments');
        
        // Trova i tornei con check-in aperto
        $now = date('Y-m-d H:i:s');
        $in_two_hours = date('Y-m-d H:i:s', strtotime('+2 hours'));
        
        $tournaments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_tournaments
                WHERE status = 'active'
                AND start_date BETWEEN %s AND %s",
                $now,
                $in_two_hours
            )
        );
        
        foreach ($tournaments as $tournament) {
            // Invia promemoria check-in
            do_action('eto_checkin_reminder', $tournament->id);
        }
    }
    
    /**
     * Notifica la creazione di un torneo
     *
     * @param int $tournament_id ID del torneo
     * @param array $tournament_data Dati del torneo
     */
    public function notify_tournament_created($tournament_id, $tournament_data) {
        // Notifica agli admin
        $this->notify_admins_tournament_created($tournament_id, $tournament_data);
    }
    
    /**
     * Notifica agli admin la creazione di un torneo
     *
     * @param int $tournament_id ID del torneo
     * @param array $tournament_data Dati del torneo
     */
    private function notify_admins_tournament_created($tournament_id, $tournament_data) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(__('[%s] Nuovo torneo creato: %s', 'eto'), $site_name, $tournament_data['name']);
        
        $message = sprintf(
            __('Un nuovo torneo è stato creato sul sito %s.

Dettagli del torneo:
Nome: %s
Formato: %s
Data di inizio: %s
Numero massimo di team: %d

Per gestire il torneo, accedi al pannello di amministrazione:
%s

Cordiali saluti,
%s', 'eto'),
            $site_name,
            $tournament_data['name'],
            $tournament_data['format'],
            $tournament_data['start_date'],
            $tournament_data['max_teams'],
            admin_url('admin.php?page=eto-tournaments&action=edit&id=' . $tournament_id),
            $site_name
        );
        
        $this->send_email($admin_email, $subject, $message);
    }
    
    /**
     * Notifica l'aggiornamento di un torneo
     *
     * @param int $tournament_id ID del torneo
     * @param array $tournament_data Dati del torneo
     */
    public function notify_tournament_updated($tournament_id, $tournament_data) {
        // Notifica ai team registrati
        $this->notify_teams_tournament_updated($tournament_id, $tournament_data);
    }
    
    /**
     * Notifica ai team l'aggiornamento di un torneo
     *
     * @param int $tournament_id ID del torneo
     * @param array $tournament_data Dati del torneo
     */
    private function notify_teams_tournament_updated($tournament_id, $tournament_data) {
        global $wpdb;
        
        $table_entries = $this->db_query->get_table_name('tournament_entries');
        $table_teams = $this->db_query->get_table_name('teams');
        
        // Ottieni i team registrati al torneo
        $teams = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, u.user_email, u.display_name
                FROM $table_entries te
                JOIN $table_teams t ON te.team_id = t.id
                JOIN {$wpdb->users} u ON t.captain_id = u.ID
                WHERE te.tournament_id = %d",
                $tournament_id
            )
        );
        
        if (empty($teams)) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        $tournament_url = get_permalink(get_option('eto_tournament_page')) . '?id=' . $tournament_id;
        
        $subject = sprintf(__('[%s] Aggiornamento torneo: %s', 'eto'), $site_name, $tournament_data['name']);
        
        foreach ($teams as $team) {
            $message = sprintf(
                __('Ciao %s,

Il torneo "%s" a cui il tuo team "%s" è registrato è stato aggiornato.

Dettagli aggiornati:
Data di inizio: %s
Stato: %s

Per visualizzare i dettagli completi del torneo, visita:
%s

Cordiali saluti,
%s', 'eto'),
                $team->display_name,
                $tournament_data['name'],
                $team->name,
                $tournament_data['start_date'],
                $this->get_status_label($tournament_data['status']),
                $tournament_url,
                $site_name
            );
            
            $this->send_email($team->user_email, $subject, $message);
        }
    }
    
    /**
     * Notifica che un torneo sta per iniziare
     *
     * @param int $tournament_id ID del torneo
     */
    public function notify_tournament_starting_soon($tournament_id) {
        global $wpdb;
        
        $tournament = $this->db_query->get_tournament($tournament_id);
        if (!$tournament) {
            return;
        }
        
        $table_entries = $this->db_query->get_table_name('tournament_entries');
        $table_teams = $this->db_query->get_table_name('teams');
        
        // Ottieni i team registrati al torneo
        $teams = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, u.user_email, u.display_name
                FROM $table_entries te
                JOIN $table_teams t ON te.team_id = t.id
                JOIN {$wpdb->users} u ON t.captain_id = u.ID
                WHERE te.tournament_id = %d",
                $tournament_id
            )
        );
        
        if (empty($teams)) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        $tournament_url = get_permalink(get_option('eto_tournament_page')) . '?id=' . $tournament_id;
        $checkin_url = get_permalink(get_option('eto_checkin_page')) . '?tournament_id=' . $tournament_id;
        
        $subject = sprintf(__('[%s] Il torneo %s inizia tra 24 ore', 'eto'), $site_name, $tournament->name);
        
        foreach ($teams as $team) {
            $message = sprintf(
                __('Ciao %s,

Il torneo "%s" a cui il tuo team "%s" è registrato inizierà tra 24 ore.

Ricordati di effettuare il check-in prima dell\'inizio del torneo:
%s

Per visualizzare i dettagli completi del torneo, visita:
%s

Buona fortuna!

Cordiali saluti,
%s', 'eto'),
                $team->display_name,
                $tournament->name,
                $team->name,
                $checkin_url,
                $tournament_url,
                $site_name
            );
            
            $this->send_email($team->user_email, $subject, $message);
        }
    }
    
    /**
     * Notifica la creazione di un team
     *
     * @param int $team_id ID del team
     * @param array $team_data Dati del team
     */
    public function notify_team_created($team_id, $team_data) {
        // Notifica al capitano
        $captain = get_userdata($team_data['captain_id']);
        if (!$captain) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        $team_url = get_permalink(get_option('eto_team_page')) . '?id=' . $team_id;
        
        $subject = sprintf(__('[%s] Team creato: %s', 'eto'), $site_name, $team_data['name']);
        
        $message = sprintf(
            __('Ciao %s,

Congratulazioni! Il tuo team "%s" è stato creato con successo.

Sei stato designato come capitano del team. Puoi invitare altri giocatori a unirsi al tuo team.

Per gestire il tuo team, visita:
%s

Cordiali saluti,
%s', 'eto'),
            $captain->display_name,
            $team_data['name'],
            $team_url,
            $site_name
        );
        
        $this->send_email($captain->user_email, $subject, $message);
    }
    
    /**
     * Notifica l'aggiunta di un membro a un team
     *
     * @param int $team_id ID del team
     * @param int $user_id ID dell'utente aggiunto
     * @param string $role Ruolo dell'utente nel team
     */
    public function notify_team_member_added($team_id, $user_id, $role) {
        $team = $this->db_query->get_team($team_id);
        $user = get_userdata($user_id);
        $captain = get_userdata($team->captain_id);
        
        if (!$team || !$user) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        $team_url = get_permalink(get_option('eto_team_page')) . '?id=' . $team_id;
        
        // Notifica al nuovo membro
        $subject = sprintf(__('[%s] Sei stato aggiunto al team %s', 'eto'), $site_name, $team->name);
        
        $message = sprintf(
            __('Ciao %s,

Sei stato aggiunto al team "%s" con il ruolo di %s.

Il capitano del team è %s.

Per visualizzare i dettagli del team, visita:
%s

Cordiali saluti,
%s', 'eto'),
            $user->display_name,
            $team->name,
            $this->get_role_label($role),
            $captain->display_name,
            $team_url,
            $site_name
        );
        
        $this->send_email($user->user_email, $subject, $message);
        
        // Notifica al capitano
        if ($user_id != $team->captain_id) {
            $subject = sprintf(__('[%s] Nuovo membro aggiunto al team %s', 'eto'), $site_name, $team->name);
            
            $message = sprintf(
                __('Ciao %s,

Un nuovo membro è stato aggiunto al tuo team "%s":

Nome: %s
Email: %s
Ruolo: %s

Per gestire il tuo team, visita:
%s

Cordiali saluti,
%s', 'eto'),
                $captain->display_name,
                $team->name,
                $user->display_name,
                $user->user_email,
                $this->get_role_label($role),
                $team_url,
                $site_name
            );
            
            $this->send_email($captain->user_email, $subject, $message);
        }
    }
    
    /**
     * Notifica la rimozione di un membro da un team
     *
     * @param int $team_id ID del team
     * @param int $user_id ID dell'utente rimosso
     * @param string $role Ruolo dell'utente nel team
     */
    public function notify_team_member_removed($team_id, $user_id, $role) {
        $team = $this->db_query->get_team($team_id);
        $user = get_userdata($user_id);
        $captain = get_userdata($team->captain_id);
        
        if (!$team || !$user) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        
        // Notifica al membro rimosso
        $subject = sprintf(__('[%s] Sei stato rimosso dal team %s', 'eto'), $site_name, $team->name);
        
        $message = sprintf(
            __('Ciao %s,

Sei stato rimosso dal team "%s".

Per qualsiasi domanda, contatta il capitano del team: %s (%s).

Cordiali saluti,
%s', 'eto'),
            $user->display_name,
            $team->name,
            $captain->display_name,
            $captain->user_email,
            $site_name
        );
        
        $this->send_email($user->user_email, $subject, $message);
        
        // Notifica al capitano
        if ($user_id != $team->captain_id) {
            $subject = sprintf(__('[%s] Membro rimosso dal team %s', 'eto'), $site_name, $team->name);
            
            $message = sprintf(
                __('Ciao %s,

Un membro è stato rimosso dal tuo team "%s":

Nome: %s
Email: %s
Ruolo: %s

Cordiali saluti,
%s', 'eto'),
                $captain->display_name,
                $team->name,
                $user->display_name,
                $user->user_email,
                $this->get_role_label($role),
                $site_name
            );
            
            $this->send_email($captain->user_email, $subject, $message);
        }
    }
    
    /**
     * Notifica la pianificazione di un match
     *
     * @param int $match_id ID del match
     */
    public function notify_match_scheduled($match_id) {
        global $wpdb;
        
        $table_matches = $this->db_query->get_table_name('matches');
        $table_teams = $this->db_query->get_table_name('teams');
        $table_tournaments = $this->db_query->get_table_name('tournaments');
        
        // Ottieni i dati del match
        $match = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT m.*, t.name as tournament_name, 
                t1.name as team1_name, t1.captain_id as team1_captain_id,
                t2.name as team2_name, t2.captain_id as team2_captain_id
                FROM $table_matches m
                JOIN $table_tournaments t ON m.tournament_id = t.id
                LEFT JOIN $table_teams t1 ON m.team1_id = t1.id
                LEFT JOIN $table_teams t2 ON m.team2_id = t2.id
                WHERE m.id = %d",
                $match_id
            )
        );
        
        if (!$match || !$match->team1_id || !$match->team2_id) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        $tournament_url = get_permalink(get_option('eto_tournament_page')) . '?id=' . $match->tournament_id;
        
        // Notifica ai capitani dei team
        $captains = [
            get_userdata($match->team1_captain_id),
            get_userdata($match->team2_captain_id)
        ];
        
        $subject = sprintf(__('[%s] Match pianificato: %s vs %s', 'eto'), $site_name, $match->team1_name, $match->team2_name);
        
        foreach ($captains as $index => $captain) {
            if (!$captain) {
                continue;
            }
            
            $opponent_name = $index === 0 ? $match->team2_name : $match->team1_name;
            
            $message = sprintf(
                __('Ciao %s,

Un nuovo match è stato pianificato per il tuo team nel torneo "%s":

%s vs %s
Round: %d
Data: %s

Per visualizzare i dettagli del torneo e del match, visita:
%s

Buona fortuna!

Cordiali saluti,
%s', 'eto'),
                $captain->display_name,
                $match->tournament_name,
                $match->team1_name,
                $match->team2_name,
                $match->round,
                $match->scheduled_at ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($match->scheduled_at)) : __('Non pianificato', 'eto'),
                $tournament_url,
                $site_name
            );
            
            $this->send_email($captain->user_email, $subject, $message);
        }
    }
    
    /**
     * Notifica il completamento di un match
     *
     * @param int $match_id ID del match
     */
    public function notify_match_completed($match_id) {
        global $wpdb;
        
        $table_matches = $this->db_query->get_table_name('matches');
        $table_teams = $this->db_query->get_table_name('teams');
        $table_tournaments = $this->db_query->get_table_name('tournaments');
        
        // Ottieni i dati del match
        $match = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT m.*, t.name as tournament_name, 
                t1.name as team1_name, t1.captain_id as team1_captain_id,
                t2.name as team2_name, t2.captain_id as team2_captain_id
                FROM $table_matches m
                JOIN $table_tournaments t ON m.tournament_id = t.id
                LEFT JOIN $table_teams t1 ON m.team1_id = t1.id
                LEFT JOIN $table_teams t2 ON m.team2_id = t2.id
                WHERE m.id = %d",
                $match_id
            )
        );
        
        if (!$match || !$match->team1_id || !$match->team2_id) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        $tournament_url = get_permalink(get_option('eto_tournament_page')) . '?id=' . $match->tournament_id;
        
        // Determina il vincitore
        $winner_name = '';
        if ($match->team1_score > $match->team2_score) {
            $winner_name = $match->team1_name;
        } elseif ($match->team2_score > $match->team1_score) {
            $winner_name = $match->team2_name;
        } else {
            $winner_name = __('Pareggio', 'eto');
        }
        
        // Notifica ai capitani dei team
        $captains = [
            get_userdata($match->team1_captain_id),
            get_userdata($match->team2_captain_id)
        ];
        
        $subject = sprintf(__('[%s] Risultato match: %s vs %s', 'eto'), $site_name, $match->team1_name, $match->team2_name);
        
        foreach ($captains as $captain) {
            if (!$captain) {
                continue;
            }
            
            $message = sprintf(
                __('Ciao %s,

Il match tra %s e %s nel torneo "%s" è stato completato:

Risultato: %s %d - %d %s
Vincitore: %s

Per visualizzare i dettagli del torneo e i prossimi match, visita:
%s

Cordiali saluti,
%s', 'eto'),
                $captain->display_name,
                $match->team1_name,
                $match->team2_name,
                $match->tournament_name,
                $match->team1_name,
                $match->team1_score,
                $match->team2_score,
                $match->team2_name,
                $winner_name,
                $tournament_url,
                $site_name
            );
            
            $this->send_email($captain->user_email, $subject, $message);
        }
    }
    
    /**
     * Notifica l'apertura del check-in per un torneo
     *
     * @param int $tournament_id ID del torneo
     */
    public function notify_checkin_open($tournament_id) {
        global $wpdb;
        
        $tournament = $this->db_query->get_tournament($tournament_id);
        if (!$tournament) {
            return;
        }
        
        $table_entries = $this->db_query->get_table_name('tournament_entries');
        $table_teams = $this->db_query->get_table_name('teams');
        
        // Ottieni i team registrati al torneo
        $teams = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, u.user_email, u.display_name
                FROM $table_entries te
                JOIN $table_teams t ON te.team_id = t.id
                JOIN {$wpdb->users} u ON t.captain_id = u.ID
                WHERE te.tournament_id = %d",
                $tournament_id
            )
        );
        
        if (empty($teams)) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        $checkin_url = get_permalink(get_option('eto_checkin_page')) . '?tournament_id=' . $tournament_id;
        
        $subject = sprintf(__('[%s] Check-in aperto per il torneo %s', 'eto'), $site_name, $tournament->name);
        
        foreach ($teams as $team) {
            $message = sprintf(
                __('Ciao %s,

Il check-in per il torneo "%s" è ora aperto.

Per partecipare al torneo, è necessario effettuare il check-in prima dell'inizio:
%s

Il check-in si chiuderà 15 minuti prima dell'inizio del torneo.

Cordiali saluti,
%s', 'eto'),
                $team->display_name,
                $tournament->name,
                $checkin_url,
                $site_name
            );
            
            $this->send_email($team->user_email, $subject, $message);
        }
    }
    
    /**
     * Notifica un promemoria per il check-in
     *
     * @param int $tournament_id ID del torneo
     */
    public function notify_checkin_reminder($tournament_id) {
        global $wpdb;
        
        $tournament = $this->db_query->get_tournament($tournament_id);
        if (!$tournament) {
            return;
        }
        
        $table_entries = $this->db_query->get_table_name('tournament_entries');
        $table_teams = $this->db_query->get_table_name('teams');
        
        // Ottieni i team registrati al torneo che non hanno ancora fatto check-in
        $teams = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, u.user_email, u.display_name
                FROM $table_entries te
                JOIN $table_teams t ON te.team_id = t.id
                JOIN {$wpdb->users} u ON t.captain_id = u.ID
                WHERE te.tournament_id = %d
                AND te.checked_in = 0",
                $tournament_id
            )
        );
        
        if (empty($teams)) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        $checkin_url = get_permalink(get_option('eto_checkin_page')) . '?tournament_id=' . $tournament_id;
        
        $subject = sprintf(__('[%s] PROMEMORIA: Check-in per il torneo %s', 'eto'), $site_name, $tournament->name);
        
        foreach ($teams as $team) {
            $message = sprintf(
                __('Ciao %s,

PROMEMORIA: Non hai ancora effettuato il check-in per il torneo "%s" che inizierà a breve.

Per partecipare al torneo, è necessario effettuare il check-in prima dell'inizio:
%s

Il check-in si chiuderà 15 minuti prima dell'inizio del torneo.

Cordiali saluti,
%s', 'eto'),
                $team->display_name,
                $tournament->name,
                $checkin_url,
                $site_name
            );
            
            $this->send_email($team->user_email, $subject, $message);
        }
    }
    
    /**
     * Notifica la chiusura del check-in per un torneo
     *
     * @param int $tournament_id ID del torneo
     */
    public function notify_checkin_closed($tournament_id) {
        global $wpdb;
        
        $tournament = $this->db_query->get_tournament($tournament_id);
        if (!$tournament) {
            return;
        }
        
        $table_entries = $this->db_query->get_table_name('tournament_entries');
        $table_teams = $this->db_query->get_table_name('teams');
        
        // Ottieni i team che hanno fatto check-in
        $teams = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, u.user_email, u.display_name
                FROM $table_entries te
                JOIN $table_teams t ON te.team_id = t.id
                JOIN {$wpdb->users} u ON t.captain_id = u.ID
                WHERE te.tournament_id = %d
                AND te.checked_in = 1",
                $tournament_id
            )
        );
        
        if (empty($teams)) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        $tournament_url = get_permalink(get_option('eto_tournament_page')) . '?id=' . $tournament_id;
        
        $subject = sprintf(__('[%s] Il torneo %s sta per iniziare', 'eto'), $site_name, $tournament->name);
        
        foreach ($teams as $team) {
            $message = sprintf(
                __('Ciao %s,

Il check-in per il torneo "%s" è ora chiuso e il torneo sta per iniziare.

Il tuo team "%s" è confermato per la partecipazione.

Per visualizzare il bracket e i match, visita:
%s

Buona fortuna!

Cordiali saluti,
%s', 'eto'),
                $team->display_name,
                $tournament->name,
                $team->name,
                $tournament_url,
                $site_name
            );
            
            $this->send_email($team->user_email, $subject, $message);
        }
    }
    
    /**
     * Invia un'email
     *
     * @param string $to Destinatario
     * @param string $subject Oggetto
     * @param string $message Messaggio
     * @return bool True se l'email è stata inviata, false altrimenti
     */
    private function send_email($to, $subject, $message) {
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
        
        $result = wp_mail($to, $subject, $message, $headers);
        
        if (defined('ETO_DEBUG') && ETO_DEBUG && !$result) {
            error_log('[ETO] Errore invio email a ' . $to . ': ' . $subject);
        }
        
        return $result;
    }
    
    /**
     * Ottiene l'etichetta per uno stato
     *
     * @param string $status Codice dello stato
     * @return string Etichetta dello stato
     */
    private function get_status_label($status) {
        $labels = [
            'pending' => __('In attesa', 'eto'),
            'active' => __('Attivo', 'eto'),
            'completed' => __('Completato', 'eto'),
            'cancelled' => __('Annullato', 'eto')
        ];
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
    
    /**
     * Ottiene l'etichetta per un ruolo
     *
     * @param string $role Codice del ruolo
     * @return string Etichetta del ruolo
     */
    private function get_role_label($role) {
        $labels = [
            'captain' => __('Capitano', 'eto'),
            'member' => __('Membro', 'eto'),
            'substitute' => __('Sostituto', 'eto')
        ];
        
        return isset($labels[$role]) ? $labels[$role] : $role;
    }
}

// Inizializza la classe di notifiche
function eto_notifications() {
    return ETO_Notifications::get_instance();
}
