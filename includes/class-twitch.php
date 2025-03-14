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
     * @param int $post<response clipped><NOTE>To save on context only part of this file has been shown to you. You should retry this tool after you have searched inside the file with `grep -n` in order to find the line numbers of what you are looking for.</NOTE>