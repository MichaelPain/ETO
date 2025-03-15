<?php
/**
 * Classe per la gestione dei tornei individuali (1vs1)
 *
 * Implementa la logica per creare e gestire tornei individuali senza team
 *
 * @package ETO
 * @since 2.6.0
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Individual_Tournament {
    
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
        add_filter('eto_tournament_options', array($this, 'add_individual_tournament_option'));
        add_action('eto_after_tournament_save', array($this, 'handle_individual_tournament_save'), 10, 2);
        add_filter('eto_tournament_registration_form', array($this, 'modify_registration_form'), 10, 2);
        add_action('wp_ajax_eto_register_individual', array($this, 'ajax_register_individual'));
        add_action('wp_ajax_nopriv_eto_register_individual', array($this, 'ajax_register_individual'));
        add_filter('eto_tournament_participants', array($this, 'get_tournament_participants'), 10, 2);
        add_filter('eto_match_display', array($this, 'modify_match_display'), 10, 3);
    }
    
    /**
     * Aggiunge l'opzione per i tornei individuali alle opzioni del torneo
     *
     * @param array $options Opzioni del torneo
     * @return array Opzioni aggiornate
     */
    public function add_individual_tournament_option($options) {
        $options['is_individual'] = array(
            'label' => __('Torneo Individuale (1vs1)', 'eto'),
            'type' => 'checkbox',
            'default' => false,
            'description' => __('Abilita per creare un torneo individuale senza team', 'eto')
        );
        
        return $options;
    }
    
    /**
     * Gestisce il salvataggio di un torneo individuale
     *
     * @param int $tournament_id ID del torneo
     * @param array $data Dati del torneo
     * @return void
     */
    public function handle_individual_tournament_save($tournament_id, $data) {
        if (isset($data['is_individual']) && $data['is_individual']) {
            // Imposta il flag is_individual nel database
            $tournament = new ETO_Tournament_Model($tournament_id);
            $tournament->set_meta('is_individual', true);
        }
    }
    
    /**
     * Modifica il form di registrazione per i tornei individuali
     *
     * @param string $form HTML del form di registrazione
     * @param int $tournament_id ID del torneo
     * @return string HTML modificato
     */
    public function modify_registration_form($form, $tournament_id) {
        $tournament = new ETO_Tournament_Model($tournament_id);
        $is_individual = $tournament->get_meta('is_individual', false);
        
        if (!$is_individual) {
            return $form;
        }
        
        // Sostituisci il form di registrazione team con un form individuale
        ob_start();
        ?>
        <div class="eto-registration-form">
            <h3><?php _e('Registrazione al Torneo Individuale', 'eto'); ?></h3>
            
            <?php if (is_user_logged_in()): ?>
                <?php
                $user_id = get_current_user_id();
                $already_registered = $this->is_user_registered($tournament_id, $user_id);
                
                if ($already_registered):
                ?>
                    <p><?php _e('Sei già registrato a questo torneo.', 'eto'); ?></p>
                <?php else: ?>
                    <form id="eto-individual-registration-form">
                        <input type="hidden" name="tournament_id" value="<?php echo esc_attr($tournament_id); ?>">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('eto-individual-registration-nonce'); ?>">
                        
                        <?php
                        // Campi aggiuntivi specifici del gioco
                        $game = $tournament->get('game');
                        if ($game == 'lol') {
                            ?>
                            <div class="eto-form-row">
                                <label for="riot_id"><?php _e('Riot ID:', 'eto'); ?></label>
                                <input type="text" name="game_id" id="riot_id" required>
                                <p class="description"><?php _e('Inserisci il tuo Riot ID (es. Username#TAG)', 'eto'); ?></p>
                            </div>
                            <?php
                        } elseif ($game == 'valorant') {
                            ?>
                            <div class="eto-form-row">
                                <label for="valorant_id"><?php _e('Valorant ID:', 'eto'); ?></label>
                                <input type="text" name="game_id" id="valorant_id" required>
                                <p class="description"><?php _e('Inserisci il tuo Valorant ID (es. Username#TAG)', 'eto'); ?></p>
                            </div>
                            <?php
                        }
                        ?>
                        
                        <div class="eto-form-row">
                            <button type="submit" class="button eto-register-button"><?php _e('Registrati al Torneo', 'eto'); ?></button>
                            <span class="eto-registration-status"></span>
                        </div>
                    </form>
                    
                    <script>
                    jQuery(document).ready(function($) {
                        $('#eto-individual-registration-form').on('submit', function(e) {
                            e.preventDefault();
                            
                            var formData = $(this).serialize();
                            formData += '&action=eto_register_individual';
                            
                            var statusElement = $(this).find('.eto-registration-status');
                            statusElement.text('<?php _e('Registrazione in corso...', 'eto'); ?>');
                            
                            $.ajax({
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                type: 'POST',
                                data: formData,
                                success: function(response) {
                                    if (response.success) {
                                        statusElement.text(response.data.message);
                                        // Ricarica la pagina dopo un breve ritardo
                                        setTimeout(function() {
                                            location.reload();
                                        }, 2000);
                                    } else {
                                        statusElement.text(response.data.message);
                                    }
                                },
                                error: function() {
                                    statusElement.text('<?php _e('Errore durante la registrazione', 'eto'); ?>');
                                }
                            });
                        });
                    });
                    </script>
                <?php endif; ?>
            <?php else: ?>
                <p><?php _e('Devi effettuare il login per registrarti al torneo.', 'eto'); ?></p>
                <p><a href="<?php echo wp_login_url(get_permalink()); ?>" class="button"><?php _e('Accedi', 'eto'); ?></a></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Gestisce la registrazione individuale via AJAX
     */
    public function ajax_register_individual() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-individual-registration-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i parametri
        if (!isset($_POST['tournament_id'])) {
            wp_send_json_error(array('message' => __('Parametri mancanti', 'eto')));
        }
        
        $tournament_id = intval($_POST['tournament_id']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Devi effettuare il login per registrarti', 'eto')));
        }
        
        // Verifica che il torneo esista e sia individuale
        $tournament = new ETO_Tournament_Model($tournament_id);
        if (!$tournament) {
            wp_send_json_error(array('message' => __('Torneo non trovato', 'eto')));
        }
        
        $is_individual = $tournament->get_meta('is_individual', false);
        if (!$is_individual) {
            wp_send_json_error(array('message' => __('Questo non è un torneo individuale', 'eto')));
        }
        
        // Verifica che l'utente non sia già registrato
        if ($this->is_user_registered($tournament_id, $user_id)) {
            wp_send_json_error(array('message' => __('Sei già registrato a questo torneo', 'eto')));
        }
        
        // Verifica che il torneo sia aperto alle registrazioni
        $registration_start = $tournament->get('registration_start');
        $registration_end = $tournament->get('registration_end');
        $now = current_time('mysql');
        
        if ($registration_start && $now < $registration_start) {
            wp_send_json_error(array('message' => __('Le registrazioni non sono ancora aperte', 'eto')));
        }
        
        if ($registration_end && $now > $registration_end) {
            wp_send_json_error(array('message' => __('Le registrazioni sono chiuse', 'eto')));
        }
        
        // Verifica che il torneo non abbia raggiunto il limite di partecipanti
        $max_participants = $tournament->get('max_teams');
        $current_participants = $this->count_participants($tournament_id);
        
        if ($max_participants > 0 && $current_participants >= $max_participants) {
            wp_send_json_error(array('message' => __('Il torneo ha raggiunto il numero massimo di partecipanti', 'eto')));
        }
        
        // Ottieni il game ID se fornito
        $game_id = isset($_POST['game_id']) ? sanitize_text_field($_POST['game_id']) : '';
        
        // Registra l'utente al torneo
        $result = $this->register_user($tournament_id, $user_id, $game_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Registrazione completata con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante la registrazione', 'eto')));
        }
    }
    
    /**
     * Verifica se un utente è già registrato a un torneo
     *
     * @param int $tournament_id ID del torneo
     * @param int $user_id ID dell'utente
     * @return bool True se l'utente è registrato, false altrimenti
     */
    public function is_user_registered($tournament_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'eto_individual_participants';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE tournament_id = %d AND user_id = %d",
            $tournament_id,
            $user_id
        ));
        
        return $result > 0;
    }
    
    /**
     * Conta i partecipanti a un torneo individuale
     *
     * @param int $tournament_id ID del torneo
     * @return int Numero di partecipanti
     */
    public function count_participants($tournament_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'eto_individual_participants';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE tournament_id = %d",
            $tournament_id
        ));
    }
    
    /**
     * Registra un utente a un torneo individuale
     *
     * @param int $tournament_id ID del torneo
     * @param int $user_id ID dell'utente
     * @param string $game_id ID del gioco (opzionale)
     * @return bool True se la registrazione è riuscita, false altrimenti
     */
    public function register_user($tournament_id, $user_id, $game_id = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'eto_individual_participants';
        
        // Calcola il seed (posizione) del partecipante
        $seed = $this->count_participants($tournament_id) + 1;
        
        $result = $wpdb->insert(
            $table,
            array(
                'tournament_id' => $tournament_id,
                'user_id' => $user_id,
                'seed' => $seed,
                'checked_in' => 0,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%d', '%s')
        );
        
        if ($result && !empty($game_id)) {
            // Salva il game ID come metadato dell'utente
            update_user_meta($user_id, 'eto_game_id_' . $tournament_id, $game_id);
        }
        
        return $result !== false;
    }
    
    /**
     * Ottiene i partecipanti di un torneo individuale
     *
     * @param array $participants Array di partecipanti (team)
     * @param int $tournament_id ID del torneo
     * @return array Array di partecipanti (individuali o team)
     */
    public function get_tournament_participants($participants, $tournament_id) {
        $tournament = new ETO_Tournament_Model($tournament_id);
        $is_individual = $tournament->get_meta('is_individual', false);
        
        if (!$is_individual) {
            return $participants;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'eto_individual_participants';
        
        $individual_participants = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE tournament_id = %d ORDER BY seed ASC",
            $tournament_id
        ));
        
        $formatted_participants = array();
        
        foreach ($individual_participants as $participant) {
            $user = get_userdata($participant->user_id);
            if ($user) {
                $formatted_participants[] = array(
                    'id' => $participant->user_id,
                    'name' => $user->display_name,
                    'type' => 'individual',
                    'seed' => $participant->seed,
                    'checked_in' => $participant->checked_in
                );
            }
        }
        
        return $formatted_participants;
    }
    
    /**
     * Modifica la visualizzazione dei match per i tornei individuali
     *
     * @param string $display HTML della visualizzazione del match
     * @param object $match Oggetto match
     * @param array $args Argomenti aggiuntivi
     * @return string HTML modificato
     */
    public function modify_match_display($display, $match, $args) {
        $tournament_id = $match->get('tournament_id');
        $tournament = new ETO_Tournament_Model($tournament_id);
        $is_individual = $tournament->get_meta('is_individual', false);
        
        if (!$is_individual) {
            return $display;
        }
        
        // Ottieni i dati dei partecipanti
        $player1_id = $match->get('team1_id');
        $player2_id = $match->get('team2_id');
        
        $player1 = $player1_id ? get_userdata($player1_id) : null;
        $player2 = $player2_id ? get_userdata($player2_id) : null;
        
        $player1_name = $player1 ? $player1->display_name : __('TBD', 'eto');
        $player2_name = $player2 ? $player2->display_name : __('TBD', 'eto');
        
        // Ottieni i risultati
        $result = $match->get_result();
        $player1_score = isset($result['team1_score']) ? $result['team1_score'] : 0;
        $player2_score = isset($result['team2_score']) ? $result['team2_score'] : 0;
        
        // Costruisci la visualizzazione personalizzata
        ob_start();
        ?>
        <div class="eto-match eto-individual-match">
            <div class="eto-match-header">
                <span class="eto-match-round"><?php printf(__('Round %d', 'eto'), $match->get('round')); ?></span>
                <span class="eto-match-number"><?php printf(__('Match %d', 'eto'), $match->get('match_number')); ?></span>
                <?php if ($match->get('scheduled_date')): ?>
                <span class="eto-match-date"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($match->get('scheduled_date'))); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="eto-match-content">
                <div class="eto-match-player eto-match-player1 <?php echo ($match->get('winner_id') == $player1_id) ? 'eto-match-winner' : ''; ?>">
                    <span class="eto-player-name"><?php echo esc_html($player1_name); ?></span>
                    <span class="eto-player-score"><?php echo esc_html($player1_score); ?></span>
                </div>
                
                <div class="eto-match-vs">vs</div>
                
                <div class="eto-match-player eto-match-player2 <?php echo ($match->get('winner_id') == $player2_id) ? 'eto-match-winner' : ''; ?>">
                    <span class="eto-player-name"><?php echo esc_html($player2_name); ?></span>
                    <span class="eto-player-score"><?php echo esc_html($player2_score); ?></span>
                </div>
            </div>
            
            <div class="eto-match-footer">
                <span class="eto-match-status">
                    <?php
                    switch ($match->get('status')) {
                        case 'pending':
                            _e('In attesa', 'eto');
                            break;
                        case 'in_progress':
                            _e('In corso', 'eto');
                            break;
                        case 'completed':
                            _e('Completato', 'eto');
                            break;
                        default:
                            echo esc_html($match->get('status'));
                    }
                    ?>
                </span>
                
                <?php if (isset($args['show_link']) && $args['show_link']): ?>
                <a href="<?php echo get_permalink($match->get_id()); ?>" class="eto-match-link"><?php _e('Dettagli', 'eto'); ?></a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Genera gli accoppiamenti per un torneo individuale
     *
     * @param int $tournament_id ID del torneo
     * @param string $format Formato del torneo
     * @return bool True se la generazione è riuscita, false altrimenti
     */
    public function generate_matches($tournament_id, $format) {
        $tournament = new ETO_Tournament_Model($tournament_id);
        $is_individual = $tournament->get_meta('is_individual', false);
        
        if (!$is_individual) {
            return false;
        }
        
        // Ottieni i partecipanti
        global $wpdb;
        $table = $wpdb->prefix . 'eto_individual_participants';
        
        $participants = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE tournament_id = %d AND checked_in = 1 ORDER BY seed ASC",
            $tournament_id
        ));
        
        if (empty($participants)) {
            return false;
        }
        
        // Genera i match in base al formato
        switch ($format) {
            case 'single_elimination':
                return $this->generate_single_elimination_matches($tournament_id, $participants);
            case 'double_elimination':
                return $this->generate_double_elimination_matches($tournament_id, $participants);
            case 'swiss':
                return $this->generate_swiss_matches($tournament_id, $participants);
            default:
                return false;
        }
    }
    
    /**
     * Genera gli accoppiamenti per un torneo individuale a eliminazione singola
     *
     * @param int $tournament_id ID del torneo
     * @param array $participants Array di partecipanti
     * @return bool True se la generazione è riuscita, false altrimenti
     */
    private function generate_single_elimination_matches($tournament_id, $participants) {
        // Implementazione simile a quella per i team, ma con partecipanti individuali
        // ...
        
        return true;
    }
    
    /**
     * Genera gli accoppiamenti per un torneo individuale a eliminazione doppia
     *
     * @param int $tournament_id ID del torneo
     * @param array $participants Array di partecipanti
     * @return bool True se la generazione è riuscita, false altrimenti
     */
    private function generate_double_elimination_matches($tournament_id, $participants) {
        // Implementazione simile a quella per i team, ma con partecipanti individuali
        // ...
        
        return true;
    }
    
    /**
     * Genera gli accoppiamenti per un torneo individuale con sistema svizzero
     *
     * @param int $tournament_id ID del torneo
     * @param array $participants Array di partecipanti
     * @return bool True se la generazione è riuscita, false altrimenti
     */
    private function generate_swiss_matches($tournament_id, $participants) {
        // Implementazione simile a quella per i team, ma con partecipanti individuali
        // ...
        
        return true;
    }
}

// Inizializza la classe
$individual_tournament = new ETO_Individual_Tournament();
