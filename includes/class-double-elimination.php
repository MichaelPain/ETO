<?php
/**
 * Classe per la gestione dei tornei a doppia eliminazione
 *
 * Implementa l'algoritmo di doppia eliminazione per la generazione degli accoppiamenti nei tornei
 *
 * @package ETO
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Double_Elimination {

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
     * Genera il bracket iniziale per un torneo a doppia eliminazione
     *
     * @param int $tournament_id ID del torneo
     * @return array|WP_Error Bracket generato o errore
     */
    public function generate_bracket($tournament_id) {
        // Verifica che il torneo esista
        $tournament = get_post($tournament_id);
        if (!$tournament) {
            return new WP_Error('invalid_tournament', __('Torneo non valido', 'eto'));
        }

        // Ottieni i team iscritti al torneo
        $teams = get_post_meta($tournament_id, 'eto_teams', true);
        if (!is_array($teams) || empty($teams)) {
            return new WP_Error('no_teams', __('Nessun team iscritto al torneo', 'eto'));
        }

        // Mescola i team in modo casuale per il seeding iniziale
        shuffle($teams);

        // Calcola il numero di team e il numero di round necessari
        $num_teams = count($teams);
        $bracket = $this->create_bracket_structure($tournament_id, $teams);

        // Salva il bracket nel database
        $this->save_bracket($tournament_id, $bracket);

        return $bracket;
    }

    /**
     * Crea la struttura del bracket per un torneo a doppia eliminazione
     *
     * @param int $tournament_id ID del torneo
     * @param array $teams Array di ID dei team
     * @return array Struttura del bracket
     */
    private function create_bracket_structure($tournament_id, $teams) {
        $num_teams = count($teams);
        
        // Calcola il numero di round nel winner bracket
        // Per un bracket a potenza di 2, il numero di round è log2(num_teams)
        $num_rounds_winner = ceil(log($num_teams, 2));
        
        // Calcola il numero di match nel primo round
        $first_round_matches = pow(2, $num_rounds_winner - 1);
        
        // Se il numero di team non è una potenza di 2, alcuni team riceveranno un bye
        $num_byes = $first_round_matches * 2 - $num_teams;
        
        // Crea la struttura del bracket
        $bracket = [
            'tournament_id' => $tournament_id,
            'type' => 'double_elimination',
            'winner_bracket' => [],
            'loser_bracket' => [],
            'final_match' => null,
            'grand_final_match' => null
        ];
        
        // Genera il winner bracket
        $bracket['winner_bracket'] = $this->generate_winner_bracket($tournament_id, $teams, $num_rounds_winner, $num_byes);
        
        // Genera il loser bracket
        $bracket['loser_bracket'] = $this->generate_loser_bracket($tournament_id, $num_rounds_winner);
        
        // Genera la finale e la grand finale
        $bracket['final_match'] = $this->generate_final_match($tournament_id, $num_rounds_winner);
        $bracket['grand_final_match'] = $this->generate_grand_final_match($tournament_id);
        
        return $bracket;
    }

    /**
     * Genera il winner bracket
     *
     * @param int $tournament_id ID del torneo
     * @param array $teams Array di ID dei team
     * @param int $num_rounds Numero di round
     * @param int $num_byes Numero di bye
     * @return array Winner bracket
     */
    private function generate_winner_bracket($tournament_id, $teams, $num_rounds, $num_byes) {
        $winner_bracket = [];
        
        // Distribuisci i bye in modo uniforme
        $bye_positions = [];
        if ($num_byes > 0) {
            $bye_positions = $this->distribute_byes($teams, $num_byes);
        }
        
        // Genera i match del primo round
        $first_round = [];
        $match_number = 1;
        $team_index = 0;
        
        for ($i = 0; $i < pow(2, $num_rounds - 1); $i++) {
            $match = [
                'id' => 'W_R1_M' . $match_number,
                'round' => 1,
                'match_number' => $match_number,
                'team1_id' => in_array($i * 2, $bye_positions) ? 0 : $teams[$team_index++],
                'team2_id' => in_array($i * 2 + 1, $bye_positions) ? 0 : $teams[$team_index++],
                'winner_id' => null,
                'loser_id' => null,
                'next_match_id' => 'W_R2_M' . ceil($match_number / 2),
                'next_loser_match_id' => 'L_R1_M' . $match_number,
                'status' => 'pending'
            ];
            
            // Se c'è un bye, imposta automaticamente il vincitore
            if ($match['team1_id'] === 0 && $match['team2_id'] !== 0) {
                $match['winner_id'] = $match['team2_id'];
                $match['status'] = 'completed';
            } else if ($match['team1_id'] !== 0 && $match['team2_id'] === 0) {
                $match['winner_id'] = $match['team1_id'];
                $match['status'] = 'completed';
            }
            
            $first_round[] = $match;
            $match_number++;
        }
        
        $winner_bracket[] = $first_round;
        
        // Genera i match dei round successivi
        for ($round = 2; $round <= $num_rounds; $round++) {
            $current_round = [];
            $match_number = 1;
            $num_matches = pow(2, $num_rounds - $round);
            
            for ($i = 0; $i < $num_matches; $i++) {
                $match = [
                    'id' => 'W_R' . $round . '_M' . $match_number,
                    'round' => $round,
                    'match_number' => $match_number,
                    'team1_id' => null, // Sarà determinato dai vincitori del round precedente
                    'team2_id' => null, // Sarà determinato dai vincitori del round precedente
                    'winner_id' => null,
                    'loser_id' => null,
                    'next_match_id' => $round < $num_rounds ? 'W_R' . ($round + 1) . '_M' . ceil($match_number / 2) : 'F_M1',
                    'next_loser_match_id' => 'L_R' . ($round - 1) * 2 . '_M' . $match_number,
                    'status' => 'pending'
                ];
                
                $current_round[] = $match;
                $match_number++;
            }
            
            $winner_bracket[] = $current_round;
        }
        
        return $winner_bracket;
    }

    /**
     * Genera il loser bracket
     *
     * @param int $tournament_id ID del torneo
     * @param int $num_rounds_winner Numero di round nel winner bracket
     * @return array Loser bracket
     */
    private function generate_loser_bracket($tournament_id, $num_rounds_winner) {
        $loser_bracket = [];
        
        // Il loser bracket ha 2 * (num_rounds_winner - 1) round
        $num_rounds_loser = 2 * ($num_rounds_winner - 1);
        
        // Genera i match del loser bracket
        for ($round = 1; $round <= $num_rounds_loser; $round++) {
            $current_round = [];
            
            // Il numero di match in ogni round del loser bracket segue un pattern specifico
            $num_matches = $this->get_loser_bracket_matches_count($round, $num_rounds_winner);
            
            for ($match_number = 1; $match_number <= $num_matches; $match_number++) {
                $match = [
                    'id' => 'L_R' . $round . '_M' . $match_number,
                    'round' => $round,
                    'match_number' => $match_number,
                    'team1_id' => null, // Sarà determinato dai perdenti del winner bracket o dai vincitori del loser bracket
                    'team2_id' => null, // Sarà determinato dai perdenti del winner bracket o dai vincitori del loser bracket
                    'winner_id' => null,
                    'loser_id' => null,
                    'next_match_id' => $round < $num_rounds_loser ? $this->get_next_loser_match_id($round, $match_number, $num_rounds_winner) : 'F_M1',
                    'status' => 'pending'
                ];
                
                $current_round[] = $match;
            }
            
            $loser_bracket[] = $current_round;
        }
        
        return $loser_bracket;
    }

    /**
     * Genera la finale
     *
     * @param int $tournament_id ID del torneo
     * @param int $num_rounds_winner Numero di round nel winner bracket
     * @return array Match finale
     */
    private function generate_final_match($tournament_id, $num_rounds_winner) {
        return [
            'id' => 'F_M1',
            'round' => 'final',
            'match_number' => 1,
            'team1_id' => null, // Vincitore del winner bracket
            'team2_id' => null, // Vincitore del loser bracket
            'winner_id' => null,
            'loser_id' => null,
            'next_match_id' => 'GF_M1',
            'status' => 'pending'
        ];
    }

    /**
     * Genera la grand finale
     *
     * @param int $tournament_id ID del torneo
     * @return array Match grand finale
     */
    private function generate_grand_final_match($tournament_id) {
        return [
            'id' => 'GF_M1',
            'round' => 'grand_final',
            'match_number' => 1,
            'team1_id' => null, // Vincitore della finale
            'team2_id' => null, // Perdente della finale
            'winner_id' => null,
            'loser_id' => null,
            'next_match_id' => null,
            'status' => 'pending'
        ];
    }

    /**
     * Distribuisce i bye in modo uniforme
     *
     * @param array $teams Array di ID dei team
     * @param int $num_byes Numero di bye
     * @return array Posizioni dei bye
     */
    private function distribute_byes($teams, $num_byes) {
        $num_teams = count($teams);
        $total_positions = pow(2, ceil(log($num_teams, 2)));
        $bye_positions = [];
        
        // Algoritmo per distribuire i bye in modo uniforme
        // Basato su: https://en.wikipedia.org/wiki/Bye_(sports)
        for ($i = 0; $i < $num_byes; $i++) {
            $bye_positions[] = $total_positions - 1 - $i;
        }
        
        return $bye_positions;
    }

    /**
     * Ottiene il numero di match in un round del loser bracket
     *
     * @param int $round Numero del round
     * @param int $num_rounds_winner Numero di round nel winner bracket
     * @return int Numero di match
     */
    private function get_loser_bracket_matches_count($round, $num_rounds_winner) {
        // Il pattern del numero di match nel loser bracket è complesso
        // Per round dispari: numero di match = 2^(num_rounds_winner - ceil(round/2) - 1)
        // Per round pari: numero di match = 2^(num_rounds_winner - ceil(round/2))
        
        if ($round % 2 === 1) {
            return pow(2, $num_rounds_winner - ceil($round / 2) - 1);
        } else {
            return pow(2, $num_rounds_winner - ceil($round / 2));
        }
    }

    /**
     * Ottiene l'ID del prossimo match nel loser bracket
     *
     * @param int $round Numero del round corrente
     * @param int $match_number Numero del match corrente
     * @param int $num_rounds_winner Numero di round nel winner bracket
     * @return string ID del prossimo match
     */
    private function get_next_loser_match_id($round, $match_number, $num_rounds_winner) {
        $next_round = $round + 1;
        $next_match_number = ceil($match_number / 2);
        
        return 'L_R' . $next_round . '_M' . $next_match_number;
    }

    /**
     * Salva il bracket nel database
     *
     * @param int $tournament_id ID del torneo
     * @param array $bracket Struttura del bracket
     * @return bool True se il salvataggio è riuscito, false altrimenti
     */
    private function save_bracket($tournament_id, $bracket) {
        global $wpdb;
        
        $table_brackets = $wpdb->prefix . 'eto_brackets';
        
        // Verifica se esiste già un bracket per questo torneo
        $existing_bracket = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_brackets WHERE tournament_id = %d AND bracket_type = 'double_elimination'",
            $tournament_id
        ));
        
        $data = [
            'tournament_id' => $tournament_id,
            'bracket_type' => 'double_elimination',
            'data' => json_encode($bracket),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        if ($existing_bracket) {
            // Aggiorna il bracket esistente
            $result = $wpdb->update(
                $table_brackets,
                $data,
                ['id' => $existing_bracket]
            );
        } else {
            // Inserisci un nuovo bracket
            $result = $wpdb->insert(
                $table_brackets,
                $data
            );
        }
        
        return $result !== false;
    }

    /**
     * Aggiorna il bracket dopo un match
     *
     * @param int $tournament_id ID del torneo
     * @param string $match_id ID del match
     * @param int $winner_id ID del team vincitore
     * @param int $loser_id ID del team perdente
     * @return bool True se l'aggiornamento è riuscito, false altrimenti
     */
    public function update_bracket_after_match($tournament_id, $match_id, $winner_id, $loser_id) {
        global $wpdb;
        
        $table_brackets = $wpdb->prefix . 'eto_brackets';
        
        // Ottieni il bracket dal database
        $bracket_data = $wpdb->get_var($wpdb->prepare(
            "SELECT data FROM $table_brackets WHERE tournament_id = %d AND bracket_type = 'double_elimination'",
            $tournament_id
        ));
        
        if (!$bracket_data) {
            return false;
        }
        
        $bracket = json_decode($bracket_data, true);
        
        // Trova il match nel bracket
        $match_found = false;
        $next_match_id = null;
        $next_loser_match_id = null;
        
        // Cerca nel winner bracket
        foreach ($bracket['winner_bracket'] as $round_index => $round) {
            foreach ($round as $match_index => $match) {
                if ($match['id'] === $match_id) {
                    // Aggiorna il match
                    $bracket['winner_bracket'][$round_index][$match_index]['winner_id'] = $winner_id;
                    $bracket['winner_bracket'][$round_index][$match_index]['loser_id'] = $loser_id;
                    $bracket['winner_bracket'][$round_index][$match_index]['status'] = 'completed';
                    
                    $next_match_id = $match['next_match_id'];
                    $next_loser_match_id = $match['next_loser_match_id'];
                    $match_found = true;
                    break 2;
                }
            }
        }
        
        // Se non trovato nel winner bracket, cerca nel loser bracket
        if (!$match_found) {
            foreach ($bracket['loser_bracket'] as $round_index => $round) {
                foreach ($round as $match_index => $match) {
                    if ($match['id'] === $match_id) {
                        // Aggiorna il match
                        $bracket['loser_bracket'][$round_index][$match_index]['winner_id'] = $winner_id;
                        $bracket['loser_bracket'][$round_index][$match_index]['loser_id'] = $loser_id;
                        $bracket['loser_bracket'][$round_index][$match_index]['status'] = 'completed';
                        
                        $next_match_id = $match['next_match_id'];
                        $match_found = true;
                        break 2;
                    }
                }
            }
        }
        
        // Se non trovato nei bracket principali, controlla la finale
        if (!$match_found && $bracket['final_match']['id'] === $match_id) {
            $bracket['final_match']['winner_id'] = $winner_id;
            $bracket['final_match']['loser_id'] = $loser_id;
            $bracket['final_match']['status'] = 'completed';
            
            $next_match_id = $bracket['final_match']['next_match_id'];
            $match_found = true;
        }
        
        // Se non trovato nella finale, controlla la grand finale
        if (!$match_found && $bracket['grand_final_match']['id'] === $match_id) {
            $bracket['grand_final_match']['winner_id'] = $winner_id;
            $bracket['grand_final_match']['loser_id'] = $loser_id;
            $bracket['grand_final_match']['status'] = 'completed';
            
            $match_found = true;
        }
        
        if (!$match_found) {
            return false;
        }
        
        // Aggiorna il prossimo match nel winner bracket
        if ($next_match_id) {
            $this->update_next_match($bracket, $next_match_id, $winner_id);
        }
        
        // Aggiorna il prossimo match nel loser bracket
        if ($next_loser_match_id) {
            $this->update_next_loser_match($bracket, $next_loser_match_id, $loser_id);
        }
        
        // Salva il bracket aggiornato
        $result = $wpdb->update(
            $table_brackets,
            [
                'data' => json_encode($bracket),
                'updated_at' => current_time('mysql')
            ],
            [
                'tournament_id' => $tournament_id,
                'bracket_type' => 'double_elimination'
            ]
        );
        
        return $result !== false;
    }

    /**
     * Aggiorna il prossimo match con il vincitore
     *
     * @param array $bracket Struttura del bracket
     * @param string $next_match_id ID del prossimo match
     * @param int $winner_id ID del team vincitore
     */
    private function update_next_match(&$bracket, $next_match_id, $winner_id) {
        // Cerca nel winner bracket
        foreach ($bracket['winner_bracket'] as $round_index => $round) {
            foreach ($round as $match_index => $match) {
                if ($match['id'] === $next_match_id) {
                    // Aggiorna il team nel prossimo match
                    if ($match['team1_id'] === null) {
                        $bracket['winner_bracket'][$round_index][$match_index]['team1_id'] = $winner_id;
                    } else {
                        $bracket['winner_bracket'][$round_index][$match_index]['team2_id'] = $winner_id;
                    }
                    return;
                }
            }
        }
        
        // Cerca nel loser bracket
        foreach ($bracket['loser_bracket'] as $round_index => $round) {
            foreach ($round as $match_index => $match) {
                if ($match['id'] === $next_match_id) {
                    // Aggiorna il team nel prossimo match
                    if ($match['team1_id'] === null) {
                        $bracket['loser_bracket'][$round_index][$match_index]['team1_id'] = $winner_id;
                    } else {
                        $bracket['loser_bracket'][$round_index][$match_index]['team2_id'] = $winner_id;
                    }
                    return;
                }
            }
        }
        
        // Controlla se è la finale
        if ($next_match_id === 'F_M1') {
            if ($bracket['final_match']['team1_id'] === null) {
                $bracket['final_match']['team1_id'] = $winner_id;
            } else {
                $bracket['final_match']['team2_id'] = $winner_id;
            }
            return;
        }
        
        // Controlla se è la grand finale
        if ($next_match_id === 'GF_M1') {
            if ($bracket['grand_final_match']['team1_id'] === null) {
                $bracket['grand_final_match']['team1_id'] = $winner_id;
            } else {
                $bracket['grand_final_match']['team2_id'] = $winner_id;
            }
            return;
        }
    }

    /**
     * Aggiorna il prossimo match nel loser bracket con il perdente
     *
     * @param array $bracket Struttura del bracket
     * @param string $next_loser_match_id ID del prossimo match nel loser bracket
     * @param int $loser_id ID del team perdente
     */
    private function update_next_loser_match(&$bracket, $next_loser_match_id, $loser_id) {
        // Cerca nel loser bracket
        foreach ($bracket['loser_bracket'] as $round_index => $round) {
            foreach ($round as $match_index => $match) {
                if ($match['id'] === $next_loser_match_id) {
                    // Aggiorna il team nel prossimo match
                    if ($match['team1_id'] === null) {
                        $bracket['loser_bracket'][$round_index][$match_index]['team1_id'] = $loser_id;
                    } else {
                        $bracket['loser_bracket'][$round_index][$match_index]['team2_id'] = $loser_id;
                    }
                    return;
                }
            }
        }
    }

    /**
     * Ottiene il bracket di un torneo
     *
     * @param int $tournament_id ID del torneo
     * @return array|false Bracket o false se non trovato
     */
    public function get_bracket($tournament_id) {
        global $wpdb;
        
        $table_brackets = $wpdb->prefix . 'eto_brackets';
        
        $bracket_data = $wpdb->get_var($wpdb->prepare(
            "SELECT data FROM $table_brackets WHERE tournament_id = %d AND bracket_type = 'double_elimination'",
            $tournament_id
        ));
        
        if (!$bracket_data) {
            return false;
        }
        
        return json_decode($bracket_data, true);
    }

    /**
     * Ottiene i match di un torneo a doppia eliminazione
     *
     * @param int $tournament_id ID del torneo
     * @return array Array di match
     */
    public function get_matches($tournament_id) {
        $bracket = $this->get_bracket($tournament_id);
        
        if (!$bracket) {
            return [];
        }
        
        $matches = [];
        
        // Aggiungi i match del winner bracket
        foreach ($bracket['winner_bracket'] as $round) {
            foreach ($round as $match) {
                $matches[] = $match;
            }
        }
        
        // Aggiungi i match del loser bracket
        foreach ($bracket['loser_bracket'] as $round) {
            foreach ($round as $match) {
                $matches[] = $match;
            }
        }
        
        // Aggiungi la finale
        $matches[] = $bracket['final_match'];
        
        // Aggiungi la grand finale
        $matches[] = $bracket['grand_final_match'];
        
        return $matches;
    }

    /**
     * Ottiene i match pendenti di un torneo a doppia eliminazione
     *
     * @param int $tournament_id ID del torneo
     * @return array Array di match pendenti
     */
    public function get_pending_matches($tournament_id) {
        $matches = $this->get_matches($tournament_id);
        
        return array_filter($matches, function($match) {
            return $match['status'] === 'pending' && $match['team1_id'] !== null && $match['team2_id'] !== null;
        });
    }

    /**
     * Ottiene i match completati di un torneo a doppia eliminazione
     *
     * @param int $tournament_id ID del torneo
     * @return array Array di match completati
     */
    public function get_completed_matches($tournament_id) {
        $matches = $this->get_matches($tournament_id);
        
        return array_filter($matches, function($match) {
            return $match['status'] === 'completed';
        });
    }

    /**
     * Ottiene il vincitore di un torneo a doppia eliminazione
     *
     * @param int $tournament_id ID del torneo
     * @return int|false ID del team vincitore o false se il torneo non è completato
     */
    public function get_winner($tournament_id) {
        $bracket = $this->get_bracket($tournament_id);
        
        if (!$bracket) {
            return false;
        }
        
        // Se la grand finale è completata, il vincitore è il vincitore della grand finale
        if ($bracket['grand_final_match']['status'] === 'completed') {
            return $bracket['grand_final_match']['winner_id'];
        }
        
        return false;
    }

    /**
     * Verifica se un torneo a doppia eliminazione è completato
     *
     * @param int $tournament_id ID del torneo
     * @return bool True se il torneo è completato, false altrimenti
     */
    public function is_tournament_completed($tournament_id) {
        $bracket = $this->get_bracket($tournament_id);
        
        if (!$bracket) {
            return false;
        }
        
        // Il torneo è completato se la grand finale è completata
        return $bracket['grand_final_match']['status'] === 'completed';
    }

    /**
     * Ottiene il round corrente di un torneo a doppia eliminazione
     *
     * @param int $tournament_id ID del torneo
     * @return string|false Round corrente o false se il torneo non è trovato
     */
    public function get_current_round($tournament_id) {
        $bracket = $this->get_bracket($tournament_id);
        
        if (!$bracket) {
            return false;
        }
        
        // Controlla se ci sono match pendenti nel winner bracket
        foreach ($bracket['winner_bracket'] as $round_index => $round) {
            foreach ($round as $match) {
                if ($match['status'] === 'pending' && $match['team1_id'] !== null && $match['team2_id'] !== null) {
                    return 'W_R' . ($round_index + 1);
                }
            }
        }
        
        // Controlla se ci sono match pendenti nel loser bracket
        foreach ($bracket['loser_bracket'] as $round_index => $round) {
            foreach ($round as $match) {
                if ($match['status'] === 'pending' && $match['team1_id'] !== null && $match['team2_id'] !== null) {
                    return 'L_R' . ($round_index + 1);
                }
            }
        }
        
        // Controlla se la finale è pendente
        if ($bracket['final_match']['status'] === 'pending' && $bracket['final_match']['team1_id'] !== null && $bracket['final_match']['team2_id'] !== null) {
            return 'F';
        }
        
        // Controlla se la grand finale è pendente
        if ($bracket['grand_final_match']['status'] === 'pending' && $bracket['grand_final_match']['team1_id'] !== null && $bracket['grand_final_match']['team2_id'] !== null) {
            return 'GF';
        }
        
        // Se tutti i match sono completati, il torneo è completato
        return 'completed';
    }
}