<?php
/**
 * Classe per la gestione delle finali per il terzo e quarto posto
 *
 * Implementa la logica per generare e gestire le finali per il terzo e quarto posto nei tornei
 *
 * @package ETO
 * @since 2.6.0
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Third_Place_Match {
    
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
        
        // Aggiungi i filtri e le azioni
        add_filter('eto_tournament_options', array($this, 'add_third_place_option'));
        add_action('eto_after_tournament_bracket_generation', array($this, 'generate_third_place_match'), 10, 2);
        add_action('eto_after_match_update', array($this, 'update_third_place_match'), 10, 3);
    }
    
    /**
     * Aggiunge l'opzione per la finale del terzo posto alle opzioni del torneo
     *
     * @param array $options Opzioni del torneo
     * @return array Opzioni aggiornate
     */
    public function add_third_place_option($options) {
        $options['third_place_match'] = array(
            'label' => __('Finale 3°/4° posto', 'eto'),
            'type' => 'checkbox',
            'default' => false,
            'description' => __('Abilita la finale per il terzo e quarto posto', 'eto')
        );
        
        return $options;
    }
    
    /**
     * Genera la finale per il terzo e quarto posto dopo la generazione del bracket
     *
     * @param int $tournament_id ID del torneo
     * @param string $format Formato del torneo
     * @return void
     */
    public function generate_third_place_match($tournament_id, $format) {
        // Verifica se la finale per il terzo posto è abilitata
        $tournament = new ETO_Tournament_Model($tournament_id);
        $third_place_enabled = $tournament->get_meta('third_place_match', false);
        
        if (!$third_place_enabled) {
            return;
        }
        
        // Genera la finale per il terzo posto solo per i formati supportati
        if ($format === 'single_elimination' || $format === 'double_elimination') {
            $this->create_third_place_match($tournament_id, $format);
        }
    }
    
    /**
     * Crea la finale per il terzo e quarto posto
     *
     * @param int $tournament_id ID del torneo
     * @param string $format Formato del torneo
     * @return int|false ID del match creato o false in caso di errore
     */
    private function create_third_place_match($tournament_id, $format) {
        global $wpdb;
        
        // Ottieni il numero di round del torneo
        $matches_table = $wpdb->prefix . 'eto_matches';
        $max_round = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(round) FROM $matches_table WHERE tournament_id = %d",
            $tournament_id
        ));
        
        if (!$max_round) {
            return false;
        }
        
        // Per single elimination, i perdenti delle semifinali giocano per il terzo posto
        if ($format === 'single_elimination') {
            // Trova le semifinali (penultimo round)
            $semifinal_round = $max_round - 1;
            $semifinal_matches = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $matches_table WHERE tournament_id = %d AND round = %d ORDER BY match_number ASC",
                $tournament_id,
                $semifinal_round
            ));
            
            if (count($semifinal_matches) !== 2) {
                return false;
            }
            
            // Crea il match per il terzo posto
            $match_data = array(
                'tournament_id' => $tournament_id,
                'round' => $max_round, // Stesso round della finale
                'match_number' => 2, // La finale è match_number 1
                'status' => 'pending',
                'is_third_place_match' => 1,
                'created_at' => current_time('mysql')
            );
            
            $result = $wpdb->insert($matches_table, $match_data);
            
            if ($result) {
                $match_id = $wpdb->insert_id;
                
                // Aggiungi metadati per indicare che è una finale per il terzo posto
                $meta_table = $wpdb->prefix . 'eto_match_meta';
                $wpdb->insert(
                    $meta_table,
                    array(
                        'match_id' => $match_id,
                        'meta_key' => 'is_third_place_match',
                        'meta_value' => '1'
                    )
                );
                
                return $match_id;
            }
        }
        
        // Per double elimination, il perdente della finale del winner bracket e il perdente della finale del loser bracket
        // giocano per il terzo posto
        if ($format === 'double_elimination') {
            // La logica è simile ma adattata al formato double elimination
            // Implementazione specifica per double elimination
            // ...
            
            // Esempio semplificato
            $match_data = array(
                'tournament_id' => $tournament_id,
                'round' => $max_round,
                'match_number' => 2, // La finale è match_number 1
                'status' => 'pending',
                'is_third_place_match' => 1,
                'created_at' => current_time('mysql')
            );
            
            $result = $wpdb->insert($matches_table, $match_data);
            
            if ($result) {
                $match_id = $wpdb->insert_id;
                
                // Aggiungi metadati
                $meta_table = $wpdb->prefix . 'eto_match_meta';
                $wpdb->insert(
                    $meta_table,
                    array(
                        'match_id' => $match_id,
                        'meta_key' => 'is_third_place_match',
                        'meta_value' => '1'
                    )
                );
                
                return $match_id;
            }
        }
        
        return false;
    }
    
    /**
     * Aggiorna la finale per il terzo e quarto posto quando i match delle semifinali vengono aggiornati
     *
     * @param int $match_id ID del match aggiornato
     * @param int $winner_id ID del team vincitore
     * @param int $loser_id ID del team perdente
     * @return void
     */
    public function update_third_place_match($match_id, $winner_id, $loser_id) {
        global $wpdb;
        
        if (empty($match_id) || empty($loser_id)) {
            return;
        }
        
        $match = new ETO_Match_Model($match_id);
        $tournament_id = $match->get('tournament_id');
        $round = $match->get('round');
        
        if (empty($tournament_id)) {
            return;
        }
        
        $tournament = new ETO_Tournament_Model($tournament_id);
        $format = $tournament->get('format');
        $third_place_enabled = $tournament->get_meta('third_place_match', false);
        
        if (!$third_place_enabled) {
            return;
        }
        
        $matches_table = $wpdb->prefix . 'eto_matches';
        $meta_table = $wpdb->prefix . 'eto_match_meta';
        
        // Per single elimination
        if ($format === 'single_elimination') {
            // Verifica se è una semifinale
            $max_round = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(round) FROM $matches_table WHERE tournament_id = %d",
                $tournament_id
            ));
            
            $semifinal_round = $max_round - 1;
            
            if ($round == $semifinal_round) {
                // Trova il match per il terzo posto
                $third_place_match_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT m.id FROM $matches_table m
                    JOIN $meta_table mm ON m.id = mm.match_id
                    WHERE m.tournament_id = %d AND mm.meta_key = 'is_third_place_match' AND mm.meta_value = '1'",
                    $tournament_id
                ));
                
                if ($third_place_match_id) {
                    $third_place_match = new ETO_Match_Model($third_place_match_id);
                    
                    // Aggiorna i team per il match del terzo posto
                    if ($third_place_match->get('team1_id') === null) {
                        $third_place_match->set('team1_id', $loser_id);
                    } else {
                        $third_place_match->set('team2_id', $loser_id);
                    }
                    
                    $third_place_match->save();
                }
            }
        }
        
        // Per double elimination
        // Logica simile ma adattata al formato double elimination
        // ...
    }
}

// Inizializza la classe
$third_place_match = new ETO_Third_Place_Match();
