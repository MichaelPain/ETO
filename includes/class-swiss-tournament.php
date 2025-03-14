<?php
/**
 * Classe per la gestione dei tornei con sistema Swiss
 * 
 * Implementa l'algoritmo Swiss per la generazione degli accoppiamenti
 * 
 * @package ETO
 * @since 2.5.1
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Swiss_Tournament {
    
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
    }
    
    /**
     * Genera gli accoppiamenti per un round di un torneo Swiss
     *
     * @param int $tournament_id ID del torneo
     * @param int $round Numero del round
     * @return array|false Accoppiamenti generati o false in caso di errore
     */
    public function generate_pairings($tournament_id, $round) {
        global $wpdb;
        
        // Verifica che il torneo esista e sia di tipo Swiss
        $tournament = $this->db_query->get_tournament($tournament_id);
        if (!$tournament || $tournament->format !== 'swiss') {
            return false;
        }
        
        // Ottieni i team registrati e che hanno fatto check-in
        $table_teams = $this->db_query->get_table_name('teams');
        $table_entries = $this->db_query->get_table_name('tournament_entries');
        
        $teams = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, te.seed
                FROM $table_teams t
                JOIN $table_entries te ON t.id = te.team_id
                WHERE te.tournament_id = %d
                AND te.checked_in = 1
                ORDER BY te.seed ASC",
                $tournament_id
            )
        );
        
        if (empty($teams)) {
            return false;
        }
        
        // Se è il primo round, usa il seeding
        if ($round === 1) {
            return $this->generate_first_round_pairings($teams, $tournament_id);
        }
        
        // Per i round successivi, usa i punti
        return $this->generate_subsequent_round_pairings($teams, $tournament_id, $round);
    }
    
    /**
     * Genera gli accoppiamenti per il primo round
     *
     * @param array $teams Lista dei team
     * @param int $tournament_id ID del torneo
     * @return array Accoppiamenti generati
     */
    private function generate_first_round_pairings($teams, $tournament_id) {
        // Ordina i team per seed
        usort($teams, function($a, $b) {
            return $a->seed - $b->seed;
        });
        
        return $this->create_pairings($teams, $tournament_id, 1);
    }
    
    /**
     * Genera gli accoppiamenti per i round successivi al primo
     *
     * @param array $teams Lista dei team
     * @param int $tournament_id ID del torneo
     * @param int $round Numero del round
     * @return array Accoppiamenti generati
     */
    private function generate_subsequent_round_pairings($teams, $tournament_id, $round) {
        global $wpdb;
        
        $table_matches = $this->db_query->get_table_name('matches');
        
        // Calcola i punti per ogni team
        $team_points = [];
        $previous_opponents = [];
        
        foreach ($teams as $team) {
            $team_id = $team->id;
            $team_points[$team_id] = 0;
            $previous_opponents[$team_id] = [];
            
            // Ottieni i match precedenti del team
            $matches = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table_matches
                    WHERE tournament_id = %d
                    AND (team1_id = %d OR team2_id = %d)
                    AND round < %d",
                    $tournament_id,
                    $team_id,
                    $team_id,
                    $round
                )
            );
            
            foreach ($matches as $match) {
                // Calcola i punti
                if ($match->team1_id == $team_id) {
                    if ($match->team1_score > $match->team2_score) {
                        $team_points[$team_id] += 3; // Vittoria
                    } elseif ($match->team1_score == $match->team2_score) {
                        $team_points[$team_id] += 1; // Pareggio
                    }
                    
                    // Registra l'avversario
                    if ($match->team2_id) {
                        $previous_opponents[$team_id][] = $match->team2_id;
                    }
                } else {
                    if ($match->team2_score > $match->team1_score) {
                        $team_points[$team_id] += 3; // Vittoria
                    } elseif ($match->team2_score == $match->team1_score) {
                        $team_points[$team_id] += 1; // Pareggio
                    }
                    
                    // Registra l'avversario
                    if ($match->team1_id) {
                        $previous_opponents[$team_id][] = $match->team1_id;
                    }
                }
            }
        }
        
        // Ordina i team per punti (gruppi di punti)
        $point_groups = [];
        foreach ($teams as $team) {
            $points = isset($team_points[$team->id]) ? $team_points[$team->id] : 0;
            if (!isset($point_groups[$points])) {
                $point_groups[$points] = [];
            }
            $point_groups[$points][] = $team;
        }
        
        // Ordina i gruppi per punti (decrescente)
        krsort($point_groups);
        
        // Crea una lista piatta di team ordinati per punti
        $sorted_teams = [];
        foreach ($point_groups as $points => $group) {
            // Ordina casualmente i team all'interno dello stesso gruppo di punti
            shuffle($group);
            foreach ($group as $team) {
                $sorted_teams[] = $team;
            }
        }
        
        // Genera gli accoppiamenti evitando incontri ripetuti
        return $this->create_pairings_with_constraints($sorted_teams, $previous_opponents, $tournament_id, $round);
    }
    
    /**
     * Crea gli accoppiamenti per il primo round
     *
     * @param array $teams Lista dei team ordinati
     * @param int $tournament_id ID del torneo
     * @param int $round Numero del round
     * @return array Accoppiamenti generati
     */
    private function create_pairings($teams, $tournament_id, $round) {
        global $wpdb;
        
        $table_matches = $this->db_query->get_table_name('matches');
        $pairings = [];
        $match_number = 1;
        
        // Gestisci il caso di numero dispari di team
        $bye_team = null;
        if (count($teams) % 2 !== 0) {
            // Assegna un bye all'ultimo team
            $bye_team = array_pop($teams);
            
            // Crea un match con bye
            $wpdb->insert(
                $table_matches,
                [
                    'tournament_id' => $tournament_id,
                    'team1_id' => $bye_team->id,
                    'team2_id' => null,
                    'team1_score' => 1,
                    'team2_score' => 0,
                    'round' => $round,
                    'match_number' => $match_number,
                    'status' => 'completed',
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s']
            );
            
            $pairings[] = [
                'team1' => $bye_team,
                'team2' => null,
                'match_number' => $match_number
            ];
            
            $match_number++;
        }
        
        // Crea gli accoppiamenti per i team rimanenti
        $count = count($teams);
        for ($i = 0; $i < $count; $i += 2) {
            if ($i + 1 < $count) {
                $team1 = $teams[$i];
                $team2 = $teams[$i + 1];
                
                // Inserisci il match nel database
                $wpdb->insert(
                    $table_matches,
                    [
                        'tournament_id' => $tournament_id,
                        'team1_id' => $team1->id,
                        'team2_id' => $team2->id,
                        'team1_score' => 0,
                        'team2_score' => 0,
                        'round' => $round,
                        'match_number' => $match_number,
                        'status' => 'pending',
                        'created_at' => current_time('mysql')
                    ],
                    ['%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s']
                );
                
                $pairings[] = [
                    'team1' => $team1,
                    'team2' => $team2,
                    'match_number' => $match_number
                ];
                
                $match_number++;
            }
        }
        
        return $pairings;
    }
    
    /**
     * Crea gli accoppiamenti evitando incontri ripetuti
     *
     * @param array $teams Lista dei team ordinati per punti
     * @param array $previous_opponents Avversari precedenti per ogni team
     * @param int $tournament_id ID del torneo
     * @param int $round Numero del round
     * @return array Accoppiamenti generati
     */
    private function create_pairings_with_constraints($teams, $previous_opponents, $tournament_id, $round) {
        global $wpdb;
        
        $table_matches = $this->db_query->get_table_name('matches');
        $pairings = [];
        $match_number = 1;
        $paired_teams = [];
        
        // Gestisci il caso di numero dispari di team
        if (count($teams) % 2 !== 0) {
            // Trova il team con il punteggio più basso che non ha ancora avuto un bye
            $bye_team = null;
            
            for ($i = count($teams) - 1; $i >= 0; $i--) {
                $team = $teams[$i];
                
                // Verifica se il team ha già avuto un bye
                $has_bye = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_matches
                        WHERE tournament_id = %d
                        AND team1_id = %d
                        AND team2_id IS NULL",
                        $tournament_id,
                        $team->id
                    )
                );
                
                if ($has_bye == 0) {
                    $bye_team = $team;
                    array_splice($teams, $i, 1);
                    break;
                }
            }
            
            // Se tutti i team hanno già avuto un bye, assegna al team con il punteggio più basso
            if ($bye_team === null) {
                $bye_team = array_pop($teams);
            }
            
            // Crea un match con bye
            $wpdb->insert(
                $table_matches,
                [
                    'tournament_id' => $tournament_id,
                    'team1_id' => $bye_team->id,
                    'team2_id' => null,
                    'team1_score' => 1,
                    'team2_score' => 0,
                    'round' => $round,
                    'match_number' => $match_number,
                    'status' => 'completed',
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s']
            );
            
            $pairings[] = [
                'team1' => $bye_team,
                'team2' => null,
                'match_number' => $match_number
            ];
            
            $paired_teams[] = $bye_team->id;
            $match_number++;
        }
        
        // Crea gli accoppiamenti per i team rimanenti
        while (count($teams) > 1) {
            $team1 = array_shift($teams);
            $best_opponent_index = null;
            
            // Trova il miglior avversario possibile
            for ($i = 0; $i < count($teams); $i++) {
                $team2 = $teams[$i];
                
                // Verifica se i team si sono già affrontati
                if (!in_array($team2->id, $previous_opponents[$team1->id])) {
                    $best_opponent_index = $i;
                    break;
                }
            }
            
            // Se non è stato trovato un avversario ideale, prendi il primo disponibile
            if ($best_opponent_index === null) {
                $best_opponent_index = 0;
            }
            
            $team2 = $teams[$best_opponent_index];
            array_splice($teams, $best_opponent_index, 1);
            
            // Inserisci il match nel database
            $wpdb->insert(
                $table_matches,
                [
                    'tournament_id' => $tournament_id,
                    'team1_id' => $team1->id,
                    'team2_id' => $team2->id,
                    'team1_score' => 0,
                    'team2_score' => 0,
                    'round' => $round,
                    'match_number' => $match_number,
                    'status' => 'pending',
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s']
            );
            
            $pairings[] = [
                'team1' => $team1,
                'team2' => $team2,
                'match_number' => $match_number
            ];
            
            $paired_teams[] = $team1->id;
            $paired_teams[] = $team2->id;
            $match_number++;
        }
        
        // Gestisci eventuali team rimanenti (dovrebbe accadere solo in casi eccezionali)
        if (count($teams) == 1) {
            $team = $teams[0];
            
            // Crea un match con bye
            $wpdb->insert(
                $table_matches,
                [
                    'tournament_id' => $tournament_id,
                    'team1_id' => $team->id,
                    'team2_id' => null,
                    'team1_score' => 1,
                    'team2_score' => 0,
                    'round' => $round,
                    'match_number' => $match_number,
                    'status' => 'completed',
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s']
            );
            
            $pairings[] = [
                'team1' => $team,
                'team2' => null,
                'match_number' => $match_number
            ];
        }
        
        return $pairings;
    }
    
    /**
     * Calcola la classifica di un torneo Swiss
     *
     * @param int $tournament_id ID del torneo
     * @return array Classifica dei team
     */
    public function calculate_standings($tournament_id) {
        global $wpdb;
        
        $table_teams = $this->db_query->get_table_name('teams');
        $table_entries = $this->db_query->get_table_name('tournament_entries');
        $table_matches = $this->db_query->get_table_name('matches');
        
        // Ottieni tutti i team registrati al torneo
        $teams = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.id, t.name
                FROM $table_teams t
                JOIN $table_entries te ON t.id = te.team_id
                WHERE te.tournament_id = %d
                AND te.checked_in = 1",
                $tournament_id
            )
        );
        
        if (empty($teams)) {
            return [];
        }
        
        // Calcola i punti e i tiebreaker per ogni team
        $standings = [];
        
        foreach ($teams as $team) {
            $team_id = $team->id;
            
            // Inizializza i dati del team
            $team_data = [
                'id' => $team_id,
                'name' => $team->name,
                'matches_played' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'points' => 0,
                'buchholz' => 0, // Somma dei punti degli avversari
                'sonneborn_berger' => 0, // Somma dei punti degli avversari battuti + metà dei punti degli avversari pareggiati
                'opponents' => []
            ];
            
            // Ottieni tutti i match del team
            $matches = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table_matches
                    WHERE tournament_id = %d
                    AND (team1_id = %d OR team2_id = %d)
                    AND status = 'completed'",
                    $tournament_id,
                    $team_id,
                    $team_id
                )
            );
            
            foreach ($matches as $match) {
                // Salta i bye
                if ($match->team1_id == $team_id && $match->team2_id === null) {
                    $team_data['wins']++;
                    $team_data['points'] += 3;
                    continue;
                }
                
                if ($match->team2_id == $team_id && $match->team1_id === null) {
                    $team_data['wins']++;
                    $team_data['points'] += 3;
                    continue;
                }
                
                $team_data['matches_played']++;
                
                // Calcola risultato
                if ($match->team1_id == $team_id) {
                    $opponent_id = $match->team2_id;
                    
                    if ($match->team1_score > $match->team2_score) {
                        $team_data['wins']++;
                        $team_data['points'] += 3;
                    } elseif ($match->team1_score == $match->team2_score) {
                        $team_data['draws']++;
                        $team_data['points'] += 1;
                    } else {
                        $team_data['losses']++;
                    }
                } else {
                    $opponent_id = $match->team1_id;
                    
                    if ($match->team2_score > $match->team1_score) {
                        $team_data['wins']++;
                        $team_data['points'] += 3;
                    } elseif ($match->team2_score == $match->team1_score) {
                        $team_data['draws']++;
                        $team_data['points'] += 1;
                    } else {
                        $team_data['losses']++;
                    }
                }
                
                // Registra l'avversario per i tiebreaker
                $team_data['opponents'][] = $opponent_id;
            }
            
            $standings[$team_id] = $team_data;
        }
        
        // Calcola i tiebreaker
        foreach ($standings as $team_id => &$team_data) {
            foreach ($team_data['opponents'] as $opponent_id) {
                if (isset($standings[$opponent_id])) {
                    $team_data['buchholz'] += $standings[$opponent_id]['points'];
                }
            }
            
            // Rimuovi la lista degli avversari per pulizia
            unset($team_data['opponents']);
        }
        
        // Ordina la classifica
        usort($standings, function($a, $b) {
            // Prima per punti
            if ($a['points'] != $b['points']) {
                return $b['points'] - $a['points'];
            }
            
            // Poi per Buchholz
            if ($a['buchholz'] != $b['buchholz']) {
                return $b['buchholz'] - $a['buchholz'];
            }
            
            // Poi per vittorie
            if ($a['wins'] != $b['wins']) {
                return $b['wins'] - $a['wins'];
            }
            
            // Infine per scontro diretto (non implementato qui)
            return 0;
        });
        
        return $standings;
    }
    
    /**
     * Ottiene il numero massimo di round per un torneo Swiss
     *
     * @param int $num_players Numero di giocatori
     * @return int Numero massimo di round
     */
    public function get_max_rounds($num_players) {
        // Formula standard per tornei Swiss: log2(n) + 1
        return ceil(log($num_players, 2)) + 1;
    }
    
    /**
     * Verifica se un torneo Swiss è completato
     *
     * @param int $tournament_id ID del torneo
     * @return bool True se il torneo è completato, false altrimenti
     */
    public function is_tournament_completed($tournament_id) {
        global $wpdb;
        
        $table_matches = $this->db_query->get_table_name('matches');
        $table_entries = $this->db_query->get_table_name('tournament_entries');
        
        // Conta i team registrati
        $team_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_entries
                WHERE tournament_id = %d
                AND checked_in = 1",
                $tournament_id
            )
        );
        
        if ($team_count <= 0) {
            return false;
        }
        
        // Calcola il numero massimo di round
        $max_rounds = $this->get_max_rounds($team_count);
        
        // Ottieni il round corrente
        $current_round = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(round) FROM $table_matches
                WHERE tournament_id = %d",
                $tournament_id
            )
        );
        
        if ($current_round < $max_rounds) {
            return false;
        }
        
        // Verifica se tutti i match dell'ultimo round sono completati
        $pending_matches = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_matches
                WHERE tournament_id = %d
                AND round = %d
                AND status != 'completed'",
                $tournament_id,
                $current_round
            )
        );
        
        return $pending_matches == 0;
    }
}
