<?php
/**
 * Classe per l'integrazione con Twitch
 *
 * Implementa l'integrazione con Twitch per lo streaming delle partite
 *
 * @package ETO
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Twitch {

    /**
     * Istanza del database query
     *
     * @var ETO_DB_Query
     */
    private $db_query;

    /**
     * Istanza della classe di sicurezza
     *
     * @var ETO_Security
     */
    private $security;

    /**
     * Istanza della classe di notifiche
     *
     * @var ETO_Notifications
     */
    private $notifications;

    /**
     * Client ID di Twitch
     *
     * @var string
     */
    private $client_id;

    /**
     * Client Secret di Twitch
     *
     * @var string
     */
    private $client_secret;

    /**
     * Token di accesso per l'API di Twitch
     *
     * @var string
     */
    private $access_token;

    /**
     * Costruttore
     */
    public function __construct() {
        $this->db_query = new ETO_DB_Query();
        $this->security = new ETO_Security();
        $this->notifications = new ETO_Notifications();
        
        // Ottieni le credenziali Twitch dalle opzioni
        $this->client_id = get_option('eto_twitch_client_id', '');
        $this->client_secret = get_option('eto_twitch_client_secret', '');
        $this->access_token = get_option('eto_twitch_access_token', '');
    }

    /**
     * Inizializza la classe
     */
    public function init() {
        // Aggiungi gli shortcode
        add_shortcode('eto_twitch_stream', [$this, 'stream_shortcode']);
        add_shortcode('eto_twitch_streams', [$this, 'streams_shortcode']);
        
        // Aggiungi le azioni AJAX
        add_action('wp_ajax_eto_twitch_search_channel', [$this, 'ajax_search_channel']);
        add_action('wp_ajax_eto_twitch_get_stream_info', [$this, 'ajax_get_stream_info']);
        
        // Aggiungi i filtri per le colonne admin
        add_filter('manage_eto_match_posts_columns', [$this, 'add_stream_column']);
        add_action('manage_eto_match_posts_custom_column', [$this, 'manage_stream_column'], 10, 2);
        
        // Aggiungi le meta box per i match
        add_action('add_meta_boxes', [$this, 'add_stream_meta_box']);
        add_action('save_post_eto_match', [$this, 'save_stream_meta_box'], 10, 3);
        
        // Aggiungi le azioni cron
        add_action('eto_twitch_refresh_token', [$this, 'refresh_access_token']);
        add_action('eto_twitch_update_streams', [$this, 'update_live_streams']);
        
        // Pianifica gli eventi cron
        $this->schedule_events();
    }

    /**
     * Pianifica gli eventi cron
     */
    private function schedule_events() {
        // Pianifica il refresh del token ogni 24 ore
        if (!wp_next_scheduled('eto_twitch_refresh_token')) {
            wp_schedule_event(time(), 'daily', 'eto_twitch_refresh_token');
        }
        
        // Pianifica l'aggiornamento degli stream ogni 5 minuti
        if (!wp_next_scheduled('eto_twitch_update_streams')) {
            wp_schedule_event(time(), 'eto_five_minutes', 'eto_twitch_update_streams');
        }
    }

    /**
     * Registra l'intervallo di 5 minuti per il cron
     *
     * @param array $schedules Intervalli esistenti
     * @return array Intervalli modificati
     */
    public function register_cron_interval($schedules) {
        $schedules['eto_five_minutes'] = [
            'interval' => 300,
            'display' => __('Ogni 5 minuti', 'eto')
        ];
        
        return $schedules;
    }

    /**
     * Shortcode per visualizzare un singolo stream
     *
     * @param array $atts Attributi dello shortcode
     * @return string HTML dello shortcode
     */
    public function stream_shortcode($atts) {
        $atts = shortcode_atts([
            'match_id' => 0,
            'channel' => '',
            'width' => '100%',
            'height' => '480',
            'autoplay' => 'no',
            'muted' => 'no',
            'show_chat' => 'yes',
            'chat_height' => '400',
            'theme' => 'dark'
        ], $atts);
        
        // Converti alcuni attributi in booleani
        $atts['autoplay'] = $atts['autoplay'] === 'yes';
        $atts['muted'] = $atts['muted'] === 'yes';
        $atts['show_chat'] = $atts['show_chat'] === 'yes';
        $atts['match_id'] = intval($atts['match_id']);
        
        // Se l'ID del match è specificato, ottieni il canale dal match
        if ($atts['match_id'] > 0) {
            $channel = get_post_meta($atts['match_id'], 'eto_twitch_channel', true);
            if ($channel) {
                $atts['channel'] = $channel;
            }
        }
        
        // Verifica che il canale sia specificato
        if (empty($atts['channel'])) {
            return '<p>' . __('Canale Twitch non specificato.', 'eto') . '</p>';
        }
        
        // Inizia il buffer di output
        ob_start();
        
        // Includi gli script e gli stili necessari
        wp_enqueue_script('twitch-embed', 'https://embed.twitch.tv/embed/v1.js', [], null, true);
        wp_enqueue_style('eto-twitch', ETO_PLUGIN_URL . 'public/css/twitch.css', [], ETO_VERSION);
        
        // Genera un ID univoco per l'embed
        $embed_id = 'eto-twitch-embed-' . uniqid();
        
        // Crea l'embed
        ?>
        <div class="eto-twitch-container">
            <div id="<?php echo esc_attr($embed_id); ?>" class="eto-twitch-embed"></div>
            
            <?php if ($atts['show_chat']): ?>
            <div id="<?php echo esc_attr($embed_id); ?>-chat" class="eto-twitch-chat"></div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var options = {
                width: '<?php echo esc_js($atts['width']); ?>',
                height: <?php echo esc_js($atts['height']); ?>,
                channel: '<?php echo esc_js($atts['channel']); ?>',
                parent: ['<?php echo esc_js($_SERVER['HTTP_HOST']); ?>'],
                autoplay: <?php echo $atts['autoplay'] ? 'true' : 'false'; ?>,
                muted: <?php echo $atts['muted'] ? 'true' : 'false'; ?>,
                theme: '<?php echo esc_js($atts['theme']); ?>'
            };
            
            var embed = new Twitch.Embed('<?php echo esc_js($embed_id); ?>', options);
            
            <?php if ($atts['show_chat']): ?>
            var chatOptions = {
                width: '100%',
                height: <?php echo esc_js($atts['chat_height']); ?>,
                channel: '<?php echo esc_js($atts['channel']); ?>',
                parent: ['<?php echo esc_js($_SERVER['HTTP_HOST']); ?>'],
                theme: '<?php echo esc_js($atts['theme']); ?>'
            };
            
            var chat = new Twitch.Embed('<?php echo esc_js($embed_id); ?>-chat', chatOptions);
            <?php endif; ?>
        });
        </script>
        
        <style>
        .eto-twitch-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .eto-twitch-embed {
            flex: 2;
            min-width: 300px;
        }
        
        .eto-twitch-chat {
            flex: 1;
            min-width: 300px;
            height: <?php echo esc_attr($atts['chat_height']); ?>px;
        }
        
        @media (max-width: 768px) {
            .eto-twitch-container {
                flex-direction: column;
            }
        }
        </style>
        <?php
        
        // Restituisci il contenuto
        return ob_get_clean();
    }

    /**
     * Shortcode per visualizzare una lista di stream
     *
     * @param array $atts Attributi dello shortcode
     * @return string HTML dello shortcode
     */
    public function streams_shortcode($atts) {
        $atts = shortcode_atts([
            'tournament_id' => 0,
            'limit' => 10,
            'show_offline' => 'no',
            'layout' => 'grid',
            'columns' => 3
        ], $atts);
        
        // Converti alcuni attributi
        $atts['tournament_id'] = intval($atts['tournament_id']);
        $atts['limit'] = intval($atts['limit']);
        $atts['show_offline'] = $atts['show_offline'] === 'yes';
        $atts['columns'] = intval($atts['columns']);
        
        // Se l'ID del torneo non è specificato, usa il post corrente
        if ($atts['tournament_id'] === 0) {
            global $post;
            if ($post && $post->post_type === 'eto_tournament') {
                $atts['tournament_id'] = $post->ID;
            }
        }
        
        // Ottieni i match del torneo
        $match_ids = [];
        
        if ($atts['tournament_id'] > 0) {
            // Ottieni i match dal torneo
            $matches = get_posts([
                'post_type' => 'eto_match',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'eto_tournament_id',
                        'value' => $atts['tournament_id']
                    ]
                ]
            ]);
            
            foreach ($matches as $match) {
                $match_ids[] = $match->ID;
            }
        }
        
        // Ottieni gli stream dai match
        $streams = [];
        
        foreach ($match_ids as $match_id) {
            $channel = get_post_meta($match_id, 'eto_twitch_channel', true);
            
            if ($channel) {
                $stream_info = get_post_meta($match_id, 'eto_twitch_stream_info', true);
                $is_live = !empty($stream_info) && isset($stream_info['is_live']) && $stream_info['is_live'];
                
                // Salta gli stream offline se richiesto
                if (!$atts['show_offline'] && !$is_live) {
                    continue;
                }
                
                $streams[] = [
                    'match_id' => $match_id,
                    'channel' => $channel,
                    'is_live' => $is_live,
                    'stream_info' => $stream_info,
                    'match_title' => get_the_title($match_id),
                    'match_date' => get_post_meta($match_id, 'eto_match_date', true)
                ];
            }
        }
        
        // Ordina gli stream: prima quelli live, poi per data del match
        usort($streams, function($a, $b) {
            if ($a['is_live'] && !$b['is_live']) {
                return -1;
            } elseif (!$a['is_live'] && $b['is_live']) {
                return 1;
            } else {
                $date_a = strtotime($a['match_date']);
                $date_b = strtotime($b['match_date']);
                
                return $date_b - $date_a;
            }
        });
        
        // Limita il numero di stream
        if ($atts['limit'] > 0) {
            $streams = array_slice($streams, 0, $atts['limit']);
        }
        
        // Inizia il buffer di output
        ob_start();
        
        // Includi gli script e gli stili necessari
        wp_enqueue_style('eto-twitch', ETO_PLUGIN_URL . 'public/css/twitch.css', [], ETO_VERSION);
        
        // Mostra gli stream
        if (!empty($streams)) {
            echo '<div class="eto-twitch-streams eto-twitch-layout-' . esc_attr($atts['layout']) . '">';
            
            if ($atts['layout'] === 'grid') {
                echo '<div class="eto-twitch-grid" style="grid-template-columns: repeat(' . esc_attr($atts['columns']) . ', 1fr);">';
            }
            
            foreach ($streams as $stream) {
                $thumbnail = '';
                $title = '';
                $game = '';
                $viewers = 0;
                
                if ($stream['is_live'] && !empty($stream['stream_info'])) {
                    $thumbnail = isset($stream['stream_info']['thumbnail_url']) ? str_replace('{width}x{height}', '320x180', $stream['stream_info']['thumbnail_url']) : '';
                    $title = isset($stream['stream_info']['title']) ? $stream['stream_info']['title'] : '';
                    $game = isset($stream['stream_info']['game_name']) ? $stream['stream_info']['game_name'] : '';
                    $viewers = isset($stream['stream_info']['viewer_count']) ? $stream['stream_info']['viewer_count'] : 0;
                }
                
                echo '<div class="eto-twitch-stream-item' . ($stream['is_live'] ? ' eto-twitch-live' : ' eto-twitch-offline') . '">';
                
                echo '<div class="eto-twitch-stream-thumbnail">';
                echo '<a href="' . esc_url(get_permalink($stream['match_id'])) . '">';
                
                if ($stream['is_live'] && $thumbnail) {
                    echo '<img src="' . esc_url($thumbnail) . '" alt="' . esc_attr($stream['channel']) . '">';
                    echo '<span class="eto-twitch-live-badge">' . __('LIVE', 'eto') . '</span>';
                } else {
                    echo '<div class="eto-twitch-offline-thumbnail">';
                    echo '<span class="eto-twitch-offline-text">' . __('Offline', 'eto') . '</span>';
                    echo '</div>';
                }
                
                echo '</a>';
                echo '</div>';
                
                echo '<div class="eto-twitch-stream-info">';
                echo '<h3 class="eto-twitch-stream-title"><a href="' . esc_url(get_permalink($stream['match_id'])) . '">' . esc_html($stream['match_title']) . '</a></h3>';
                
                echo '<div class="eto-twitch-channel-info">';
                echo '<span class="eto-twitch-channel-name">' . esc_html($stream['channel']) . '</span>';
                
                if ($stream['is_live']) {
                    echo '<span class="eto-twitch-viewers">' . sprintf(__('%s spettatori', 'eto'), number_format($viewers)) . '</span>';
                }
                
                echo '</div>';
                
                if ($stream['is_live'] && $title) {
                    echo '<p class="eto-twitch-stream-title">' . esc_html($title) . '</p>';
                }
                
                if ($stream['is_live'] && $game) {
                    echo '<p class="eto-twitch-game">' . esc_html($game) . '</p>';
                }
                
                echo '<div class="eto-twitch-stream-actions">';
                echo '<a href="' . esc_url(get_permalink($stream['match_id'])) . '" class="eto-twitch-watch-button">' . __('Guarda', 'eto') . '</a>';
                echo '<a href="https://twitch.tv/' . esc_attr($stream['channel']) . '" target="_blank" class="eto-twitch-channel-button">' . __('Canale Twitch', 'eto') . '</a>';
                echo '</div>';
                
                echo '</div>';
                
                echo '</div>';
            }
            
            if ($atts['layout'] === 'grid') {
                echo '</div>';
            }
            
            echo '</div>';
        } else {
            echo '<p>' . __('Nessuno stream disponibile.', 'eto') . '</p>';
        }
        
        // Restituisci il contenuto
        return ob_get_clean();
    }

    /**
     * Aggiunge una colonna per lo stream nella lista dei match in admin
     *
     * @param array $columns Colonne esistenti
     * @return array Colonne modificate
     */
    public function add_stream_column($columns) {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            // Aggiungi la colonna dopo lo stato
            if ($key === 'eto_status') {
                $new_columns['eto_twitch'] = __('Stream Twitch', 'eto');
            }
        }
        
        return $new_columns;
    }

    /**
     * Gestisce il contenuto della colonna stream
     *
     * @param string $column Nome della colonna
     * @param int $post_id ID del post
     */
    public function manage_stream_column($column, $post_id) {
        if ($column !== 'eto_twitch') {
            return;
        }
        
        $channel = get_post_meta($post_id, 'eto_twitch_channel', true);
        
        if (!$channel) {
            echo '<span class="eto-twitch-not-set">' . __('Non impostato', 'eto') . '</span>';
            return;
        }
        
        $stream_info = get_post_meta($post_id, 'eto_twitch_stream_info', true);
        $is_live = !empty($stream_info) && isset($stream_info['is_live']) && $stream_info['is_live'];
        
        if ($is_live) {
            echo '<span class="eto-twitch-live">' . __('LIVE', 'eto') . '</span> ';
        } else {
            echo '<span class="eto-twitch-offline">' . __('Offline', 'eto') . '</span> ';
        }
        
        echo '<a href="https://twitch.tv/' . esc_attr($channel) . '" target="_blank">' . esc_html($channel) . '</a>';
        
        if ($is_live && isset($stream_info['viewer_count'])) {
            echo '<br>';
            echo '<small>' . sprintf(__('%s spettatori', 'eto'), number_format($stream_info['viewer_count'])) . '</small>';
        }
    }

    /**
     * Aggiunge una meta box per lo stream nella pagina di modifica del match
     */
    public function add_stream_meta_box() {
        add_meta_box(
            'eto_twitch_meta_box',
            __('Stream Twitch', 'eto'),
            [$this, 'render_stream_meta_box'],
            'eto_match',
            'side',
            'default'
        );
    }

    /**
     * Renderizza la meta box per lo stream
     *
     * @param WP_Post $post Post corrente
     */
    public function render_stream_meta_box($post) {
        // Ottieni i dati dello stream
        $channel = get_post_meta($post->ID, 'eto_twitch_channel', true);
        $stream_info = get_post_meta($post->ID, 'eto_twitch_stream_info', true);
        $is_live = !empty($stream_info) && isset($stream_info['is_live']) && $stream_info['is_live'];
        
        // Aggiungi il nonce
        wp_nonce_field('eto_twitch_meta_box', 'eto_twitch_meta_box_nonce');
        
        // Includi gli script e gli stili necessari
        wp_enqueue_script('eto-twitch-admin', ETO_PLUGIN_URL . 'admin/js/twitch.js', ['jquery'], ETO_VERSION, true);
        wp_localize_script('eto-twitch-admin', 'eto_twitch', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eto_twitch_nonce'),
            'match_id' => $post->ID,
            'messages' => [
                'searching' => __('Ricerca in corso...', 'eto'),
                'no_results' => __('Nessun risultato trovato.', 'eto'),
                'error' => __('Si è verificato un errore durante la ricerca.', 'eto')
            ]
        ]);
        
        // Renderizza il form
        ?>
        <p>
            <label for="eto_twitch_channel"><?php _e('Canale Twitch:', 'eto'); ?></label>
            <div class="eto-twitch-channel-input">
                <input type="text" id="eto_twitch_channel" name="eto_twitch_channel" value="<?php echo esc_attr($channel); ?>" class="widefat" />
                <button type="button" id="eto_twitch_search" class="button"><?php _e('Cerca', 'eto'); ?></button>
            </div>
            <div id="eto_twitch_search_results" class="eto-twitch-search-results"></div>
        </p>
        
        <div id="eto_twitch_preview" class="eto-twitch-preview" <?php echo $channel ? '' : 'style="display: none;"'; ?>>
            <h4><?php _e('Anteprima:', 'eto'); ?></h4>
            
            <?php if ($is_live && isset($stream_info['thumbnail_url'])): ?>
            <div class="eto-twitch-preview-thumbnail">
                <img src="<?php echo esc_url(str_replace('{width}x{height}', '320x180', $stream_info['thumbnail_url'])); ?>" alt="<?php echo esc_attr($channel); ?>">
                <span class="eto-twitch-live-badge"><?php _e('LIVE', 'eto'); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="eto-twitch-preview-info">
                <p>
                    <strong><?php _e('Canale:', 'eto'); ?></strong>
                    <a href="https://twitch.tv/<?php echo esc_attr($channel); ?>" target="_blank"><?php echo esc_html($channel); ?></a>
                </p>
                
                <?php if ($is_live): ?>
                <p>
                    <strong><?php _e('Stato:', 'eto'); ?></strong>
                    <span class="eto-twitch-live"><?php _e('LIVE', 'eto'); ?></span>
                </p>
                
                <?php if (isset($stream_info['title'])): ?>
                <p>
                    <strong><?php _e('Titolo:', 'eto'); ?></strong>
                    <?php echo esc_html($stream_info['title']); ?>
                </p>
                <?php endif; ?>
                
                <?php if (isset($stream_info['game_name'])): ?>
                <p>
                    <strong><?php _e('Gioco:', 'eto'); ?></strong>
                    <?php echo esc_html($stream_info['game_name']); ?>
                </p>
                <?php endif; ?>
                
                <?php if (isset($stream_info['viewer_count'])): ?>
                <p>
                    <strong><?php _e('Spettatori:', 'eto'); ?></strong>
                    <?php echo number_format($stream_info['viewer_count']); ?>
                </p>
                <?php endif; ?>
                
                <?php else: ?>
                <p>
                    <strong><?php _e('Stato:', 'eto'); ?></strong>
                    <span class="eto-twitch-offline"><?php _e('Offline', 'eto'); ?></span>
                </p>
                <?php endif; ?>
                
                <p>
                    <button type="button" id="eto_twitch_refresh" class="button"><?php _e('Aggiorna', 'eto'); ?></button>
                </p>
            </div>
        </div>
        
        <p>
            <label for="eto_twitch_shortcode"><?php _e('Shortcode:', 'eto'); ?></label>
            <input type="text" id="eto_twitch_shortcode" value='[eto_twitch_stream match_id="<?php echo esc_attr($post->ID); ?>"]' readonly class="widefat" onclick="this.select();" />
        </p>
        
        <style>
        .eto-twitch-channel-input {
            display: flex;
            gap: 5px;
            margin-bottom: 10px;
        }
        
        .eto-twitch-search-results {
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            display: none;
        }
        
        .eto-twitch-search-result {
            padding: 5px;
            cursor: pointer;
        }
        
        .eto-twitch-search-result:hover {
            background-color: #f0f0f0;
        }
        
        .eto-twitch-preview-thumbnail {
            position: relative;
            margin-bottom: 10px;
        }
        
        .eto-twitch-preview-thumbnail img {
            width: 100%;
            height: auto;
        }
        
        .eto-twitch-live-badge {
            position: absolute;
            top: 5px;
            left: 5px;
            background-color: #e91916;
            color: white;
            padding: 2px 5px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 3px;
        }
        
        .eto-twitch-live {
            color: #e91916;
            font-weight: bold;
        }
        
        .eto-twitch-offline {
            color: #999;
        }
        
        .eto-twitch-not-set {
            color: #999;
            font-style: italic;
        }
        </style>
        <?php
    }

    /**
     * Salva i dati della meta box per lo stream
     *
     * @param int $post_id ID del post
     * @param WP_Post $post Post corrente
     * @param bool $update Se è un aggiornamento
     */
    public function save_stream_meta_box($post_id, $post, $update) {
        // Verifica il nonce
        if (!isset($_POST['eto_twitch_meta_box_nonce']) || !wp_verify_nonce($_POST['eto_twitch_meta_box_nonce'], 'eto_twitch_meta_box')) {
            return;
        }
        
        // Verifica se è un salvataggio automatico
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Verifica i permessi
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Salva i dati
        if (isset($_POST['eto_twitch_channel'])) {
            $channel = sanitize_text_field($_POST['eto_twitch_channel']);
            
            // Rimuovi eventuali URL completi
            if (strpos($channel, 'twitch.tv/') !== false) {
                $channel = preg_replace('/.*twitch\.tv\//', '', $channel);
            }
            
            update_post_meta($post_id, 'eto_twitch_channel', $channel);
            
            // Aggiorna le informazioni sullo stream
            if ($channel) {
                $this->update_stream_info($post_id, $channel);
            } else {
                delete_post_meta($post_id, 'eto_twitch_stream_info');
            }
        }
    }

    /**
     * Gestisce la ricerca di un canale Twitch tramite AJAX
     */
    public function ajax_search_channel() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto_twitch_nonce')) {
            wp_send_json_error(['message' => __('Errore di sicurezza. Ricarica la pagina e riprova.', 'eto')]);
        }
        
        // Verifica i parametri
        if (!isset($_POST['query'])) {
            wp_send_json_error(['message' => __('Parametri mancanti.', 'eto')]);
        }
        
        $query = sanitize_text_field($_POST['query']);
        
        // Verifica che la query non sia vuota
        if (empty($query)) {
            wp_send_json_error(['message' => __('Inserisci un termine di ricerca.', 'eto')]);
        }
        
        // Verifica che le credenziali siano impostate
        if (empty($this->client_id) || empty($this->client_secret)) {
            wp_send_json_error(['message' => __('Credenziali Twitch non configurate. Contatta l\'amministratore.', 'eto')]);
        }
        
        // Ottieni un token di accesso se necessario
        if (empty($this->access_token)) {
            $this->refresh_access_token();
        }
        
        // Cerca i canali
        $channels = $this->search_channels($query);
        
        if (is_wp_error($channels)) {
            wp_send_json_error(['message' => $channels->get_error_message()]);
        }
        
        wp_send_json_success(['channels' => $channels]);
    }

    /**
     * Gestisce l'ottenimento delle informazioni su uno stream tramite AJAX
     */
    public function ajax_get_stream_info() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto_twitch_nonce')) {
            wp_send_json_error(['message' => __('Errore di sicurezza. Ricarica la pagina e riprova.', 'eto')]);
        }
        
        // Verifica i parametri
        if (!isset($_POST['match_id']) || !isset($_POST['channel'])) {
            wp_send_json_error(['message' => __('Parametri mancanti.', 'eto')]);
        }
        
        $match_id = intval($_POST['match_id']);
        $channel = sanitize_text_field($_POST['channel']);
        
        // Verifica che il match esista
        $match = get_post($match_id);
        if (!$match || $match->post_type !== 'eto_match') {
            wp_send_json_error(['message' => __('Match non trovato.', 'eto')]);
        }
        
        // Verifica che il canale non sia vuoto
        if (empty($channel)) {
            wp_send_json_error(['message' => __('Canale non specificato.', 'eto')]);
        }
        
        // Aggiorna le informazioni sullo stream
        $stream_info = $this->update_stream_info($match_id, $channel);
        
        if (is_wp_error($stream_info)) {
            wp_send_json_error(['message' => $stream_info->get_error_message()]);
        }
        
        wp_send_json_success(['stream_info' => $stream_info]);
    }

    /**
     * Cerca canali su Twitch
     *
     * @param string $query Query di ricerca
     * @return array|WP_Error Risultati della ricerca o errore
     */
    private function search_channels($query) {
        // Verifica che le credenziali siano impostate
        if (empty($this->client_id) || empty($this->access_token)) {
            return new WP_Error('twitch_credentials', __('Credenziali Twitch non configurate.', 'eto'));
        }
        
        // Prepara la richiesta
        $url = 'https://api.twitch.tv/helix/search/channels?query=' . urlencode($query) . '&first=10';
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Client-ID' => $this->client_id,
                'Authorization' => 'Bearer ' . $this->access_token
            ]
        ]);
        
        // Verifica la risposta
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Verifica che la risposta sia valida
        if (!isset($data['data'])) {
            return new WP_Error('twitch_api', __('Risposta API non valida.', 'eto'));
        }
        
        return $data['data'];
    }

    /**
     * Aggiorna le informazioni su uno stream
     *
     * @param int $match_id ID del match
     * @param string $channel Nome del canale
     * @return array|WP_Error Informazioni sullo stream o errore
     */
    public function update_stream_info($match_id, $channel) {
        // Verifica che le credenziali siano impostate
        if (empty($this->client_id) || empty($this->access_token)) {
            return new WP_Error('twitch_credentials', __('Credenziali Twitch non configurate.', 'eto'));
        }
        
        // Prepara la richiesta
        $url = 'https://api.twitch.tv/helix/streams?user_login=' . urlencode($channel);
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Client-ID' => $this->client_id,
                'Authorization' => 'Bearer ' . $this->access_token
            ]
        ]);
        
        // Verifica la risposta
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Verifica che la risposta sia valida
        if (!isset($data['data'])) {
            return new WP_Error('twitch_api', __('Risposta API non valida.', 'eto'));
        }
        
        // Prepara le informazioni sullo stream
        $stream_info = [
            'is_live' => false,
            'updated_at' => current_time('mysql')
        ];
        
        // Se lo stream è live, ottieni le informazioni
        if (!empty($data['data'])) {
            $stream_data = $data['data'][0];
            
            $stream_info['is_live'] = true;
            $stream_info['id'] = $stream_data['id'];
            $stream_info['user_id'] = $stream_data['user_id'];
            $stream_info['user_login'] = $stream_data['user_login'];
            $stream_info['user_name'] = $stream_data['user_name'];
            $stream_info['game_id'] = $stream_data['game_id'];
            $stream_info['game_name'] = $stream_data['game_name'];
            $stream_info['type'] = $stream_data['type'];
            $stream_info['title'] = $stream_data['title'];
            $stream_info['viewer_count'] = $stream_data['viewer_count'];
            $stream_info['started_at'] = $stream_data['started_at'];
            $stream_info['language'] = $stream_data['language'];
            $stream_info['thumbnail_url'] = $stream_data['thumbnail_url'];
            $stream_info['tag_ids'] = $stream_data['tag_ids'];
            $stream_info['is_mature'] = $stream_data['is_mature'];
        }
        
        // Salva le informazioni
        update_post_meta($match_id, 'eto_twitch_stream_info', $stream_info);
        
        return $stream_info;
    }

    /**
     * Aggiorna le informazioni su tutti gli stream attivi
     */
    public function update_live_streams() {
        // Ottieni tutti i match con un canale Twitch impostato
        $matches = get_posts([
            'post_type' => 'eto_match',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'eto_twitch_channel',
                    'value' => '',
                    'compare' => '!='
                ]
            ]
        ]);
        
        foreach ($matches as $match) {
            $channel = get_post_meta($match->ID, 'eto_twitch_channel', true);
            
            if ($channel) {
                $this->update_stream_info($match->ID, $channel);
            }
        }
    }

    /**
     * Aggiorna il token di accesso per l'API di Twitch
     */
    public function refresh_access_token() {
        // Verifica che le credenziali siano impostate
        if (empty($this->client_id) || empty($this->client_secret)) {
            return;
        }
        
        // Prepara la richiesta
        $url = 'https://id.twitch.tv/oauth2/token';
        
        $response = wp_remote_post($url, [
            'body' => [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'client_credentials'
            ]
        ]);
        
        // Verifica la risposta
        if (is_wp_error($response)) {
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Verifica che la risposta sia valida
        if (!isset($data['access_token'])) {
            return;
        }
        
        // Salva il token
        $this->access_token = $data['access_token'];
        update_option('eto_twitch_access_token', $data['access_token']);
    }

    /**
     * Aggiunge una pagina di amministrazione per le impostazioni di Twitch
     */
    public function add_admin_page() {
        add_submenu_page(
            'eto-dashboard',
            __('Impostazioni Twitch', 'eto'),
            __('Twitch', 'eto'),
            'manage_options',
            'eto-twitch',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Renderizza la pagina di amministrazione per le impostazioni di Twitch
     */
    public function render_admin_page() {
        // Gestisci il salvataggio delle impostazioni
        if (isset($_POST['eto_twitch_save']) && isset($_POST['eto_twitch_nonce']) && wp_verify_nonce($_POST['eto_twitch_nonce'], 'eto_twitch_settings')) {
            // Salva le impostazioni
            if (isset($_POST['eto_twitch_client_id'])) {
                $client_id = sanitize_text_field($_POST['eto_twitch_client_id']);
                update_option('eto_twitch_client_id', $client_id);
                $this->client_id = $client_id;
            }
            
            if (isset($_POST['eto_twitch_client_secret'])) {
                $client_secret = sanitize_text_field($_POST['eto_twitch_client_secret']);
                update_option('eto_twitch_client_secret', $client_secret);
                $this->client_secret = $client_secret;
            }
            
            // Aggiorna il token di accesso
            $this->refresh_access_token();
            
            echo '<div class="notice notice-success"><p>' . __('Impostazioni salvate con successo.', 'eto') . '</p></div>';
        }
        
        // Renderizza la pagina
        ?>
        <div class="wrap">
            <h1><?php _e('Impostazioni Twitch', 'eto'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('eto_twitch_settings', 'eto_twitch_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Client ID', 'eto'); ?></th>
                        <td>
                            <input type="text" name="eto_twitch_client_id" value="<?php echo esc_attr($this->client_id); ?>" class="regular-text" />
                            <p class="description"><?php _e('Il Client ID della tua applicazione Twitch.', 'eto'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Client Secret', 'eto'); ?></th>
                        <td>
                            <input type="password" name="eto_twitch_client_secret" value="<?php echo esc_attr($this->client_secret); ?>" class="regular-text" />
                            <p class="description"><?php _e('Il Client Secret della tua applicazione Twitch.', 'eto'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Stato connessione', 'eto'); ?></th>
                        <td>
                            <?php if (!empty($this->client_id) && !empty($this->client_secret) && !empty($this->access_token)): ?>
                            <span class="eto-twitch-connected"><?php _e('Connesso', 'eto'); ?></span>
                            <?php else: ?>
                            <span class="eto-twitch-disconnected"><?php _e('Non connesso', 'eto'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Istruzioni', 'eto'); ?></h2>
                
                <p><?php _e('Per utilizzare l\'integrazione con Twitch, devi creare un\'applicazione su Twitch Developer Console:', 'eto'); ?></p>
                
                <ol>
                    <li><?php _e('Vai su <a href="https://dev.twitch.tv/console/apps" target="_blank">https://dev.twitch.tv/console/apps</a>', 'eto'); ?></li>
                    <li><?php _e('Clicca su "Register Your Application"', 'eto'); ?></li>
                    <li><?php _e('Inserisci un nome per la tua applicazione', 'eto'); ?></li>
                    <li><?php _e('Inserisci l\'URL di reindirizzamento (puoi usare l\'URL del tuo sito)', 'eto'); ?></li>
                    <li><?php _e('Seleziona "Website Integration" come categoria', 'eto'); ?></li>
                    <li><?php _e('Accetta i termini di servizio e clicca su "Create"', 'eto'); ?></li>
                    <li><?php _e('Copia il Client ID e il Client Secret e incollali nei campi sopra', 'eto'); ?></li>
                </ol>
                
                <h2><?php _e('Utilizzo', 'eto'); ?></h2>
                
                <p><?php _e('Puoi utilizzare i seguenti shortcode per visualizzare gli stream Twitch:', 'eto'); ?></p>
                
                <ul>
                    <li><code>[eto_twitch_stream match_id="123"]</code> - <?php _e('Visualizza lo stream di un match specifico', 'eto'); ?></li>
                    <li><code>[eto_twitch_stream channel="nome_canale"]</code> - <?php _e('Visualizza lo stream di un canale specifico', 'eto'); ?></li>
                    <li><code>[eto_twitch_streams tournament_id="123"]</code> - <?php _e('Visualizza tutti gli stream di un torneo', 'eto'); ?></li>
                </ul>
                
                <p class="submit">
                    <input type="submit" name="eto_twitch_save" id="submit" class="button button-primary" value="<?php _e('Salva impostazioni', 'eto'); ?>">
                </p>
            </form>
        </div>
        
        <style>
        .eto-twitch-connected {
            color: #46b450;
            font-weight: bold;
        }
        
        .eto-twitch-disconnected {
            color: #dc3232;
            font-weight: bold;
        }
        </style>
        <?php
    }
}