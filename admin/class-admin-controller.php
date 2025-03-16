<?php
/**
 * Classe per la gestione dell'amministrazione
 * 
 * Gestisce tutte le funzionalità del pannello di amministrazione
 * 
 * @package ETO
 * @since 2.5.3
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Admin_Controller {
    
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
        // Rimuoviamo il riferimento a eto_security() che non esiste
    }
    
    /**
     * Inizializza il controller
     */
    public function init() {
        // Commentiamo questa riga per evitare menu duplicati con class-settings-register.php
        // add_action('admin_menu', [$this, 'register_admin_menu']);
        
        // Registra gli script e gli stili
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Registra gli AJAX handler
        $this->register_ajax_handlers();
        
        // Aggiungi i meta box
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        
        // Aggiungi le notifiche di amministrazione
        add_action('admin_notices', [$this, 'display_admin_notices']);
        
        // Aggiungi le azioni per i tornei
        add_action('admin_init', [$this, 'handle_tournament_actions']);
    }
    
    /**
     * Registra le pagine di amministrazione
     */
    public function register_admin_menu() {
        // Pagina principale
        add_menu_page(
            __('ETO - Gestione Tornei', 'eto'),
            __('ETO Tornei', 'eto'),
            'edit_posts',
            'eto-tournaments',
            [$this, 'render_tournaments_page'],
            'dashicons-awards',
            30
        );
        
        // Sottopagine
        add_submenu_page(
            'eto-tournaments',
            __('Tornei', 'eto'),
            __('Tornei', 'eto'),
            'edit_posts',
            'eto-tournaments',
            [$this, 'render_tournaments_page']
        );
        
        add_submenu_page(
            'eto-tournaments',
            __('Team', 'eto'),
            __('Team', 'eto'),
            'edit_posts',
            'eto-teams',
            [$this, 'render_teams_page']
        );
        
        add_submenu_page(
            'eto-tournaments',
            __('Partecipanti', 'eto'),
            __('Partecipanti', 'eto'),
            'edit_posts',
            'eto-participants',
            [$this, 'render_participants_page']
        );
        
        add_submenu_page(
            'eto-tournaments',
            __('Impostazioni', 'eto'),
            __('Impostazioni', 'eto'),
            'edit_posts',
            'eto-settings',
            [$this, 'render_settings_page']
        );
        
        // Pagine nascoste per aggiunta/modifica
        add_submenu_page(
            null,
            __('Aggiungi Torneo', 'eto'),
            __('Aggiungi Torneo', 'eto'),
            'edit_posts',
            'eto-add-tournament',
            [$this, 'render_add_tournament_page']
        );
        
        add_submenu_page(
            null,
            __('Modifica Torneo', 'eto'),
            __('Modifica Torneo', 'eto'),
            'edit_posts',
            'eto-edit-tournament',
            [$this, 'render_edit_tournament_page']
        );
        
        add_submenu_page(
            null,
            __('Aggiungi Team', 'eto'),
            __('Aggiungi Team', 'eto'),
            'edit_posts',
            'eto-add-team',
            [$this, 'render_add_team_page']
        );
        
        add_submenu_page(
            null,
            __('Modifica Team', 'eto'),
            __('Modifica Team', 'eto'),
            'edit_posts',
            'eto-edit-team',
            [$this, 'render_edit_team_page']
        );
        
        add_submenu_page(
            null,
            __('Aggiungi Partecipante', 'eto'),
            __('Aggiungi Partecipante', 'eto'),
            'edit_posts',
            'eto-add-participant',
            [$this, 'render_add_participant_page']
        );
    }
    
    /**
     * Registra gli script e gli stili per l'amministrazione
     */
    public function enqueue_admin_assets($hook) {
        // Verifica se siamo in una pagina del plugin
        if (strpos($hook, 'eto-') === false) {
            return;
        }
        
        // Stili
        wp_enqueue_style('eto-admin', plugin_dir_url(dirname(__FILE__)) . 'admin/css/admin.css', [], ETO_VERSION);
        
        // Media Uploader
        wp_enqueue_media();
        
        // Script
// Rimuoviamo il riferimento al file JavaScript esterno
// wp_enqueue_script('eto-admin', plugin_dir_url(dirname(__FILE__)) . 'admin/js/admin-fixed.js', ['jquery'], ETO_VERSION, true);

// Localizzazione
wp_localize_script('jquery', 'etoAdmin', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('eto-admin-nonce'),
    'i18n' => [
        'confirmDelete' => __('Sei sicuro di voler eliminare questo elemento?', 'eto'),
        'confirmReset' => __('Sei sicuro di voler resettare questo torneo? Tutti i dati saranno persi.', 'eto'),
        'confirmStart' => __('Sei sicuro di voler avviare questo torneo? Non sarà più possibile aggiungere partecipanti.', 'eto')
    ]
]);        

        // Localizzazione
        wp_localize_script('eto-admin', 'etoAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eto-admin-nonce'),
            'i18n' => [
                'confirmDelete' => __('Sei sicuro di voler eliminare questo elemento?', 'eto'),
                'confirmReset' => __('Sei sicuro di voler resettare questo torneo? Tutti i dati saranno persi.', 'eto'),
                'confirmStart' => __('Sei sicuro di voler avviare questo torneo? Non sarà più possibile aggiungere partecipanti.', 'eto')
            ]
        ]);
    }
    
    /**
     * Registra gli handler AJAX
     */
    public function register_ajax_handlers() {
        // Tornei
        add_action('wp_ajax_eto_create_tournament', [$this, 'ajax_create_tournament']);
        add_action('wp_ajax_eto_update_tournament', [$this, 'ajax_update_tournament']);
        add_action('wp_ajax_eto_delete_tournament', [$this, 'ajax_delete_tournament']);
        add_action('wp_ajax_eto_start_tournament', [$this, 'ajax_start_tournament']);
        add_action('wp_ajax_eto_reset_tournament', [$this, 'ajax_reset_tournament']);
        
        // Team
        add_action('wp_ajax_eto_create_team', [$this, 'ajax_create_team']);
        add_action('wp_ajax_eto_update_team', [$this, 'ajax_update_team']);
        add_action('wp_ajax_eto_delete_team', [$this, 'ajax_delete_team']);
        
        // Partecipanti
        add_action('wp_ajax_eto_add_participant', [$this, 'ajax_add_participant']);
        add_action('wp_ajax_eto_remove_participant', [$this, 'ajax_remove_participant']);
    }
    
    /**
     * Registra i meta box
     */
    public function register_meta_boxes() {
        // Meta box per i tornei
        add_meta_box(
            'eto-tournament-details',
            __('Dettagli torneo', 'eto'),
            [$this, 'render_tournament_details_meta_box'],
            'eto-tournament',
            'normal',
            'high'
        );
        
        add_meta_box(
            'eto-tournament-participants',
            __('Partecipanti', 'eto'),
            [$this, 'render_tournament_participants_meta_box'],
            'eto-tournament',
            'normal',
            'default'
        );
        
        add_meta_box(
            'eto-tournament-matches',
            __('Partite', 'eto'),
            [$this, 'render_tournament_matches_meta_box'],
            'eto-tournament',
            'normal',
            'default'
        );
    }
    
    /**
     * Mostra le notifiche di amministrazione
     */
    public function display_admin_notices() {
        // Implementazione delle notifiche
    }
    
    /**
     * Gestisce le azioni per i tornei
     */
    public function handle_tournament_actions() {
        // Non è più necessario gestire qui le azioni, poiché ora utilizziamo pagine dedicate
    }
    
    /**
     * Renderizza la pagina dei tornei
     */
    public function render_tournaments_page() {
        // Inizializza le variabili necessarie per la vista
        $total_pages = 1;
        $total_tournaments = 0;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $tournaments = array(); // Array vuoto predefinito
        $formats = $this->get_available_formats();
        $games = $this->get_available_games();
        
        include(plugin_dir_path(dirname(__FILE__)) . 'admin/views/tournaments/list.php');
    }
    
    /**
     * Renderizza la pagina di aggiunta di un nuovo torneo
     */
    public function render_add_tournament_page() {
        // Inizializza le variabili necessarie per la vista
        $games = $this->get_available_games();
        $formats = $this->get_available_formats();
        
        include(plugin_dir_path(dirname(__FILE__)) . 'admin/views/tournaments/add.php');
    }
    
    /**
     * Renderizza la pagina di modifica di un torneo
     */
    public function render_edit_tournament_page() {
        // Verifica l'ID del torneo
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            wp_die(__('ID torneo mancante', 'eto'));
        }
        
        $tournament_id = intval($_GET['id']);
        
        // Ottieni i dati del torneo
        $tournament = $this->db_query->get_tournament($tournament_id);
        
        if (!$tournament) {
            wp_die(__('Torneo non trovato', 'eto'));
        }
        
        // Inizializza le variabili necessarie per la vista
        $games = $this->get_available_games();
        $formats = $this->get_available_formats();
        
        include(plugin_dir_path(dirname(__FILE__)) . 'admin/views/tournaments/edit.php');
    }
    
    /**
     * Renderizza la pagina dei team
     */
    public function render_teams_page() {
        // Inizializza le variabili necessarie per la vista
        $games = $this->get_available_games();
        
        // Imposta un valore predefinito per total_pages
        $total_pages = 1;
        
        include(plugin_dir_path(dirname(__FILE__)) . 'admin/views/teams/list.php');
    }
    
    /**
     * Renderizza la pagina di aggiunta di un nuovo team
     */
    public function render_add_team_page() {
        // Inizializza le variabili necessarie per la vista
        $games = $this->get_available_games();
        
        include(plugin_dir_path(dirname(__FILE__)) . 'admin/views/teams/add.php');
    }
    
    /**
     * Renderizza la pagina di modifica di un team
     */
    public function render_edit_team_page() {
        // Verifica l'ID del team
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            wp_die(__('ID team mancante', 'eto'));
        }
        
        $team_id = intval($_GET['id']);
        
        // Ottieni i dati del team
        $team = $this->db_query->get_team($team_id);
        
        if (!$team) {
            wp_die(__('Team non trovato', 'eto'));
        }
        
        // Inizializza le variabili necessarie per la vista
        $games = $this->get_available_games();
        
        include(plugin_dir_path(dirname(__FILE__)) . 'admin/views/teams/edit.php');
    }
    
    /**
     * Renderizza la pagina dei partecipanti
     */
    public function render_participants_page() {
        // Crea la directory per i partecipanti se non esiste
        $participants_dir = plugin_dir_path(dirname(__FILE__)) . 'admin/views/participants';
        if (!file_exists($participants_dir)) {
            mkdir($participants_dir, 0755, true);
        }
        
        // Crea il file list.php se non esiste
        $participants_file = $participants_dir . '/list.php';
        if (!file_exists($participants_file)) {
            $content = '<?php
/**
 * Vista per la lista dei partecipanti
 * 
 * @package ETO
 * @subpackage Views
 * @since 2.5.3
 */

// Impedisci l\'accesso diretto
if (!defined(\'ABSPATH\')) exit;
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e(\'Partecipanti\', \'eto\'); ?></h1>
    <a href="<?php echo admin_url(\'admin.php?page=eto-add-participant\'); ?>" class="page-title-action"><?php _e(\'Aggiungi Nuovo\', \'eto\'); ?></a>
    
    <hr class="wp-header-end">
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="eto-participants">
                
                <select name="tournament">
                    <option value=""><?php _e(\'Tutti i tornei\', \'eto\'); ?></option>
                    <?php 
                    $tournaments = $this->db_query->get_tournaments();
                    foreach ($tournaments as $tournament) : 
                    ?>
                        <option value="<?php echo esc_attr($tournament[\'id\']); ?>" <?php selected(isset($_GET[\'tournament\']) ? $_GET[\'tournament\'] : \'\', $tournament[\'id\']); ?>><?php echo esc_html($tournament[\'name\']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select name="team">
                    <option value=""><?php _e(\'Tutti i team\', \'eto\'); ?></option>
                    <?php 
                    $teams = $this->db_query->get_teams();
                    foreach ($teams as $team) : 
                    ?>
                        <option value="<?php echo esc_attr($team[\'id\']); ?>" <?php selected(isset($_GET[\'team\']) ? $_GET[\'team\'] : \'\', $team[\'id\']); ?>><?php echo esc_html($team[\'name\']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <input type="submit" class="button" value="<?php _e(\'Filtra\', \'eto\'); ?>">
            </form>
        </div>
        
        <br class="clear">
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-name column-primary"><?php _e(\'Nome\', \'eto\'); ?></th>
                <th scope="col" class="manage-column column-email"><?php _e(\'Email\', \'eto\'); ?></th>
                <th scope="col" class="manage-column column-team"><?php _e(\'Team\', \'eto\'); ?></th>
                <th scope="col" class="manage-column column-tournaments"><?php _e(\'Tornei\', \'eto\'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php _e(\'Azioni\', \'eto\'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($participants)) : ?>
                <tr>
                    <td colspan="5"><?php _e(\'Nessun partecipante trovato.\', \'eto\'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($participants as $participant) : ?>
                    <tr>
                        <td class="column-name column-primary">
                            <strong><?php echo esc_html($participant->name); ?></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="<?php echo admin_url(\'admin.php?page=eto-edit-participant&id=\' . $participant->id); ?>"><?php _e(\'Modifica\', \'eto\'); ?></a> | </span>
                                <span class="delete"><a href="#" class="eto-delete-participant" data-id="<?php echo $participant->id; ?>"><?php _e(\'Elimina\', \'eto\'); ?></a></span>
                            </div>
                        </td>
                        <td class="column-email"><?php echo esc_html($participant->email); ?></td>
                        <td class="column-team"><?php echo esc_html($participant->team_name); ?></td>
                        <td class="column-tournaments"><?php echo count($participant->tournaments); ?></td>
                        <td class="column-actions">
                            <a href="<?php echo admin_url(\'admin.php?page=eto-view-participant&id=\' . $participant->id); ?>" class="button button-small"><?php _e(\'Visualizza\', \'eto\'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-name column-primary"><?php _e(\'Nome\', \'eto\'); ?></th>
                <th scope="col" class="manage-column column-email"><?php _e(\'Email\', \'eto\'); ?></th>
                <th scope="col" class="manage-column column-team"><?php _e(\'Team\', \'eto\'); ?></th>
                <th scope="col" class="manage-column column-tournaments"><?php _e(\'Tornei\', \'eto\'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php _e(\'Azioni\', \'eto\'); ?></th>
            </tr>
        </tfoot>
    </table>
</div>';
            file_put_contents($participants_file, $content);
        }
        
        // Inizializza le variabili necessarie per la vista
        $participants = array(); // Array vuoto predefinito
        
        include($participants_file);
    }
    
    /**
     * Renderizza la pagina di aggiunta di un nuovo partecipante
     */
    public function render_add_participant_page() {
        // Crea la directory per i partecipanti se non esiste
        $participants_dir = plugin_dir_path(dirname(__FILE__)) . 'admin/views/participants';
        if (!file_exists($participants_dir)) {
            mkdir($participants_dir, 0755, true);
        }
        
        // Crea il file add.php se non esiste
        $add_participant_file = $participants_dir . '/add.php';
        if (!file_exists($add_participant_file)) {
            $content = '<?php
/**
 * Vista per l\'aggiunta di un nuovo partecipante
 * 
 * @package ETO
 * @subpackage Views
 * @since 2.5.3
 */

// Impedisci l\'accesso diretto
if (!defined(\'ABSPATH\')) exit;
?>

<div class="wrap">
    <h1><?php _e(\'Aggiungi Nuovo Partecipante\', \'eto\'); ?></h1>
    
    <div id="eto-messages"></div>
    
    <form id="eto-add-participant-form" class="eto-form">
        <div class="eto-form-section">
            <h2><?php _e(\'Informazioni Generali\', \'eto\'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="participant-name"><?php _e(\'Nome\', \'eto\'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="participant-name" name="name" class="regular-text" required>
                        <p class="description"><?php _e(\'Il nome del partecipante.\', \'eto\'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="participant-email"><?php _e(\'Email\', \'eto\'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="email" id="participant-email" name="email" class="regular-text" required>
                        <p class="description"><?php _e(\'L\\\'indirizzo email del partecipante.\', \'eto\'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="participant-team"><?php _e(\'Team\', \'eto\'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select id="participant-team" name="team_id" required>
                            <option value=""><?php _e(\'Seleziona un team\', \'eto\'); ?></option>
                            <?php 
                            $teams = $this->db_query->get_teams();
                            foreach ($teams as $team) : 
                            ?>
                                <option value="<?php echo esc_attr($team[\'id\']); ?>"><?php echo esc_html($team[\'name\']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e(\'Il team a cui appartiene il partecipante.\', \'eto\'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e(\'Tornei\', \'eto\'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="participant-tournaments"><?php _e(\'Tornei\', \'eto\'); ?></label>
                    </th>
                    <td>
                        <div class="tournaments-list">
                            <?php 
                            $tournaments = $this->db_query->get_tournaments();
                            foreach ($tournaments as $tournament) : 
                            ?>
                                <label>
                                    <input type="checkbox" name="tournaments[]" value="<?php echo esc_attr($tournament[\'id\']); ?>">
                                    <?php echo esc_html($tournament[\'name\']); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </div>
                        <p class="description"><?php _e(\'Seleziona i tornei a cui il partecipante è iscritto.\', \'eto\'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-form-section">
            <h2><?php _e(\'Informazioni Aggiuntive\', \'eto\'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="participant-role"><?php _e(\'Ruolo\', \'eto\'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="participant-role" name="role" class="regular-text">
                        <p class="description"><?php _e(\'Il ruolo del partecipante nel team.\', \'eto\'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="participant-notes"><?php _e(\'Note\', \'eto\'); ?></label>
                    </th>
                    <td>
                        <textarea id="participant-notes" name="notes" rows="5" cols="50" class="large-text"></textarea>
                        <p class="description"><?php _e(\'Note aggiuntive sul partecipante.\', \'eto\'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="hidden" name="action" value="eto_add_participant">
            <input type="hidden" name="eto_nonce" value="<?php echo wp_create_nonce(\'eto_add_participant\'); ?>">
            <button type="submit" class="button button-primary"><?php _e(\'Aggiungi Partecipante\', \'eto\'); ?></button>
            <a href="<?php echo admin_url(\'admin.php?page=eto-participants\'); ?>" class="button"><?php _e(\'Annulla\', \'eto\'); ?></a>
        </p>
    </form>
</div>';
            file_put_contents($add_participant_file, $content);
        }
        
        // Inizializza le variabili necessarie per la vista
        $teams = $this->db_query->get_teams();
        $tournaments = $this->db_query->get_tournaments();
        
        include($add_participant_file);
    }
    
    /**
     * Renderizza la pagina delle impostazioni
     */
    public function render_settings_page() {
        // Crea la directory per le impostazioni se non esiste
        $settings_dir = plugin_dir_path(dirname(__FILE__)) . 'admin/views/settings';
        if (!file_exists($settings_dir)) {
            mkdir($settings_dir, 0755, true);
        }
        
        // Crea il file list.php se non esiste
        $settings_file = $settings_dir . '/list.php';
        if (!file_exists($settings_file)) {
            $content = '<?php
/**
 * Vista per le impostazioni
 * 
 * @package ETO
 * @subpackage Views
 * @since 2.5.3
 */

// Impedisci l\'accesso diretto
if (!defined(\'ABSPATH\')) exit;
?>

<div class="wrap">
    <h1><?php _e(\'Impostazioni\', \'eto\'); ?></h1>
    
    <form method="post" action="options.php" id="eto-settings-form">
        <?php settings_fields(\'eto_settings\'); ?>
        <?php do_settings_sections(\'eto_settings\'); ?>
        
        <div class="eto-settings-section">
            <h2><?php _e(\'Impostazioni Generali\', \'eto\'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="eto_page_tournaments"><?php _e(\'Pagina Tornei\', \'eto\'); ?></label>
                    </th>
                    <td>
                        <?php
                        wp_dropdown_pages(array(
                            \'name\' => \'eto_page_tournaments\',
                            \'id\' => \'eto_page_tournaments\',
                            \'echo\' => 1,
                            \'show_option_none\' => __(\' — Seleziona — \', \'eto\'),
                            \'option_none_value\' => \'0\',
                            \'selected\' => get_option(\'eto_page_tournaments\', 0)
                        ));
                        ?>
                        <p class="description"><?php _e(\'Seleziona la pagina che mostrerà l\\\'elenco dei tornei.\', \'eto\'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eto_page_teams"><?php _e(\'Pagina Team\', \'eto\'); ?></label>
                    </th>
                    <td>
                        <?php
                        wp_dropdown_pages(array(
                            \'name\' => \'eto_page_teams\',
                            \'id\' => \'eto_page_teams\',
                            \'echo\' => 1,
                            \'show_option_none\' => __(\' — Seleziona — \', \'eto\'),
                            \'option_none_value\' => \'0\',
                            \'selected\' => get_option(\'eto_page_teams\', 0)
                        ));
                        ?>
                        <p class="description"><?php _e(\'Seleziona la pagina che mostrerà l\\\'elenco dei team.\', \'eto\'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eto_registration_enabled"><?php _e(\'Abilita Registrazione\', \'eto\'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="eto_registration_enabled" name="eto_registration_enabled" value="1" <?php checked(get_option(\'eto_registration_enabled\', 1), 1); ?>>
                        <p class="description"><?php _e(\'Abilita la registrazione ai tornei per gli utenti.\', \'eto\'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-settings-section">
            <h2><?php _e(\'Impostazioni Email\', \'eto\'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="eto_email_sender"><?php _e(\'Email Mittente\', \'eto\'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="eto_email_sender" name="eto_email_sender" value="<?php echo esc_attr(get_option(\'eto_email_sender\', get_option(\'admin_email\'))); ?>" class="regular-text">
                        <p class="description"><?php _e(\'L\\\'indirizzo email utilizzato come mittente per le notifiche.\', \'eto\'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eto_email_notifications"><?php _e(\'Notifiche Email\', \'eto\'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="eto_email_notifications" name="eto_email_notifications" value="1" <?php checked(get_option(\'eto_email_notifications\', 1), 1); ?>>
                        <p class="description"><?php _e(\'Abilita l\\\'invio di notifiche email per eventi importanti.\', \'eto\'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="eto-settings-section">
            <h2><?php _e(\'Impostazioni Avanzate\', \'eto\'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="eto_debug_mode"><?php _e(\'Modalità Debug\', \'eto\'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="eto_debug_mode" name="eto_debug_mode" value="1" <?php checked(get_option(\'eto_debug_mode\', 0), 1); ?>>
                        <p class="description"><?php _e(\'Abilita la modalità debug per la registrazione di informazioni aggiuntive.\', \'eto\'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eto_cache_enabled"><?php _e(\'Abilita Cache\', \'eto\'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="eto_cache_enabled" name="eto_cache_enabled" value="1" <?php checked(get_option(\'eto_cache_enabled\', 1), 1); ?>>
                        <p class="description"><?php _e(\'Abilita la cache per migliorare le prestazioni.\', \'eto\'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button(__(\'Salva Impostazioni\', \'eto\')); ?>
    </form>
</div>';
            file_put_contents($settings_file, $content);
        }
        
        include($settings_file);
    }
    
    /**
     * Renderizza il meta box dei dettagli del torneo
     */
    public function render_tournament_details_meta_box($post) {
        // Implementazione del meta box
    }
    
    /**
     * Renderizza il meta box dei partecipanti del torneo
     */
    public function render_tournament_participants_meta_box($post) {
        // Implementazione del meta box
    }
    
    /**
     * Renderizza il meta box delle partite del torneo
     */
    public function render_tournament_matches_meta_box($post) {
        // Implementazione del meta box
    }
    
    /**
     * Handler AJAX per la creazione di un torneo
     */
    public function ajax_create_tournament() {
        // Verifica il nonce
        if (!isset($_POST['eto_nonce']) || !wp_verify_nonce($_POST['eto_nonce'], 'eto_create_tournament')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i permessi
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Non hai i permessi necessari', 'eto')));
        }
        
        // Verifica i campi obbligatori
        if (empty($_POST['name']) || empty($_POST['game']) || empty($_POST['format']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
            wp_send_json_error(array('message' => __('Tutti i campi obbligatori devono essere compilati', 'eto')));
        }
        
        // Sanitizza i dati
        $tournament_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
            'game' => sanitize_text_field($_POST['game']),
            'format' => sanitize_text_field($_POST['format']),
            'start_date' => sanitize_text_field($_POST['start_date']),
            'end_date' => sanitize_text_field($_POST['end_date']),
            'registration_start' => isset($_POST['registration_start']) ? sanitize_text_field($_POST['registration_start']) : '',
            'registration_end' => isset($_POST['registration_end']) ? sanitize_text_field($_POST['registration_end']) : '',
            'min_teams' => isset($_POST['min_teams']) ? intval($_POST['min_teams']) : 2,
            'max_teams' => isset($_POST['max_teams']) ? intval($_POST['max_teams']) : 16,
            'rules' => isset($_POST['rules']) ? wp_kses_post($_POST['rules']) : '',
            'prizes' => isset($_POST['prizes']) ? wp_kses_post($_POST['prizes']) : '',
            'featured_image' => isset($_POST['featured_image']) ? esc_url_raw($_POST['featured_image']) : '',
            'status' => 'draft',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );
        
        // Inserisci il torneo nel database
        $result = $this->db_query->insert_tournament($tournament_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Torneo creato con successo', 'eto'),
                'tournament_id' => $result,
                'redirect' => admin_url('admin.php?page=eto-tournaments')
            ));
        } else {
            wp_send_json_error(array('message' => __('Errore durante la creazione del torneo', 'eto')));
        }
    }
    
    /**
     * Handler AJAX per l'aggiornamento di un torneo
     */
    public function ajax_update_tournament() {
        // Verifica il nonce
        if (!isset($_POST['eto_nonce']) || !wp_verify_nonce($_POST['eto_nonce'], 'eto_update_tournament')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i permessi
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Non hai i permessi necessari', 'eto')));
        }
        
        // Verifica l'ID del torneo
        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('ID torneo mancante', 'eto')));
        }
        
        // Verifica i campi obbligatori
        if (empty($_POST['name']) || empty($_POST['game']) || empty($_POST['format']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
            wp_send_json_error(array('message' => __('Tutti i campi obbligatori devono essere compilati', 'eto')));
        }
        
        // Sanitizza i dati
        $tournament_data = array(
            'id' => intval($_POST['id']),
            'name' => sanitize_text_field($_POST['name']),
            'description' => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
            'game' => sanitize_text_field($_POST['game']),
            'format' => sanitize_text_field($_POST['format']),
            'start_date' => sanitize_text_field($_POST['start_date']),
            'end_date' => sanitize_text_field($_POST['end_date']),
            'registration_start' => isset($_POST['registration_start']) ? sanitize_text_field($_POST['registration_start']) : '',
            'registration_end' => isset($_POST['registration_end']) ? sanitize_text_field($_POST['registration_end']) : '',
            'min_teams' => isset($_POST['min_teams']) ? intval($_POST['min_teams']) : 2,
            'max_teams' => isset($_POST['max_teams']) ? intval($_POST['max_teams']) : 16,
            'rules' => isset($_POST['rules']) ? wp_kses_post($_POST['rules']) : '',
            'prizes' => isset($_POST['prizes']) ? wp_kses_post($_POST['prizes']) : '',
            'featured_image' => isset($_POST['featured_image']) ? esc_url_raw($_POST['featured_image']) : '',
            'updated_at' => current_time('mysql')
        );
        
        // Aggiorna il torneo nel database
        $result = $this->db_query->update_tournament($tournament_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Torneo aggiornato con successo', 'eto'),
                'redirect' => admin_url('admin.php?page=eto-tournaments')
            ));
        } else {
            wp_send_json_error(array('message' => __('Errore durante l\'aggiornamento del torneo', 'eto')));
        }
    }
    
    /**
     * Handler AJAX per l'eliminazione di un torneo
     */
    public function ajax_delete_tournament() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-admin-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i permessi
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Non hai i permessi necessari', 'eto')));
        }
        
        // Verifica l'ID del torneo
        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('ID torneo mancante', 'eto')));
        }
        
        // Elimina il torneo dal database
        $result = $this->db_query->delete_tournament(intval($_POST['id']));
        
        if ($result) {
            wp_send_json_success(array('message' => __('Torneo eliminato con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante l\'eliminazione del torneo', 'eto')));
        }
    }
    
    /**
     * Handler AJAX per l'avvio di un torneo
     */
    public function ajax_start_tournament() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-admin-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i permessi
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Non hai i permessi necessari', 'eto')));
        }
        
        // Verifica l'ID del torneo
        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('ID torneo mancante', 'eto')));
        }
        
        // Aggiorna lo stato del torneo a 'in_progress'
        $result = $this->db_query->update_tournament_status(intval($_POST['id']), 'in_progress');
        
        if ($result) {
            wp_send_json_success(array('message' => __('Torneo avviato con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante l\'avvio del torneo', 'eto')));
        }
    }
    
    /**
     * Handler AJAX per il reset di un torneo
     */
    public function ajax_reset_tournament() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-admin-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i permessi
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Non hai i permessi necessari', 'eto')));
        }
        
        // Verifica l'ID del torneo
        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('ID torneo mancante', 'eto')));
        }
        
        // Resetta il torneo (elimina partite e risultati, imposta lo stato a 'draft')
        $result = $this->db_query->reset_tournament(intval($_POST['id']));
        
        if ($result) {
            wp_send_json_success(array('message' => __('Torneo resettato con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante il reset del torneo', 'eto')));
        }
    }
    
    /**
     * Handler AJAX per la creazione di un team
     */
    public function ajax_create_team() {
        // Verifica il nonce
        if (!isset($_POST['eto_nonce']) || !wp_verify_nonce($_POST['eto_nonce'], 'eto_create_team')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i permessi
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Non hai i permessi necessari', 'eto')));
        }
        
        // Verifica i campi obbligatori
        if (empty($_POST['name']) || empty($_POST['game']) || empty($_POST['captain_id'])) {
            wp_send_json_error(array('message' => __('Tutti i campi obbligatori devono essere compilati', 'eto')));
        }
        
        // Sanitizza i dati
        $team_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
            'game' => sanitize_text_field($_POST['game']),
            'logo_url' => isset($_POST['logo_url']) ? esc_url_raw($_POST['logo_url']) : '',
            'captain_id' => intval($_POST['captain_id']),
            'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'website' => isset($_POST['website']) ? esc_url_raw($_POST['website']) : '',
            'social_media' => isset($_POST['social_media']) ? array_map('esc_url_raw', $_POST['social_media']) : array(),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );
        
        // Inserisci il team nel database
        $result = $this->db_query->insert_team($team_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Team creato con successo', 'eto'),
                'team_id' => $result,
                'redirect' => admin_url('admin.php?page=eto-teams')
            ));
        } else {
            wp_send_json_error(array('message' => __('Errore durante la creazione del team', 'eto')));
        }
    }
    
    /**
     * Handler AJAX per l'aggiornamento di un team
     */
    public function ajax_update_team() {
        // Verifica il nonce
        if (!isset($_POST['eto_nonce']) || !wp_verify_nonce($_POST['eto_nonce'], 'eto_update_team')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i permessi
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Non hai i permessi necessari', 'eto')));
        }
        
        // Verifica l'ID del team
        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('ID team mancante', 'eto')));
        }
        
        // Verifica i campi obbligatori
        if (empty($_POST['name']) || empty($_POST['game']) || empty($_POST['captain_id'])) {
            wp_send_json_error(array('message' => __('Tutti i campi obbligatori devono essere compilati', 'eto')));
        }
        
        // Sanitizza i dati
        $team_data = array(
            'id' => intval($_POST['id']),
            'name' => sanitize_text_field($_POST['name']),
            'description' => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
            'game' => sanitize_text_field($_POST['game']),
            'logo_url' => isset($_POST['logo_url']) ? esc_url_raw($_POST['logo_url']) : '',
            'captain_id' => intval($_POST['captain_id']),
            'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'website' => isset($_POST['website']) ? esc_url_raw($_POST['website']) : '',
            'social_media' => isset($_POST['social_media']) ? array_map('esc_url_raw', $_POST['social_media']) : array(),
            'updated_at' => current_time('mysql')
        );
        
        // Aggiorna il team nel database
        $result = $this->db_query->update_team($team_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Team aggiornato con successo', 'eto'),
                'redirect' => admin_url('admin.php?page=eto-teams')
            ));
        } else {
            wp_send_json_error(array('message' => __('Errore durante l\'aggiornamento del team', 'eto')));
        }
    }
    
    /**
     * Handler AJAX per l'eliminazione di un team
     */
    public function ajax_delete_team() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-admin-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i permessi
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Non hai i permessi necessari', 'eto')));
        }
        
        // Verifica l'ID del team
        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('ID team mancante', 'eto')));
        }
        
        // Elimina il team dal database
        $result = $this->db_query->delete_team(intval($_POST['id']));
        
        if ($result) {
            wp_send_json_success(array('message' => __('Team eliminato con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante l\'eliminazione del team', 'eto')));
        }
    }
    
    /**
     * Handler AJAX per l'aggiunta di un partecipante
     */
    public function ajax_add_participant() {
        // Verifica il nonce
        if (!isset($_POST['eto_nonce']) || !wp_verify_nonce($_POST['eto_nonce'], 'eto_add_participant')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i permessi
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Non hai i permessi necessari', 'eto')));
        }
        
        // Verifica i campi obbligatori
        if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['team_id'])) {
            wp_send_json_error(array('message' => __('Tutti i campi obbligatori devono essere compilati', 'eto')));
        }
        
        // Sanitizza i dati
        $participant_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'team_id' => intval($_POST['team_id']),
            'role' => isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '',
            'notes' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );
        
        // Inserisci il partecipante nel database
        $result = $this->db_query->insert_participant($participant_data);
        
        if ($result) {
            // Aggiungi il partecipante ai tornei selezionati
            if (isset($_POST['tournaments']) && is_array($_POST['tournaments'])) {
                foreach ($_POST['tournaments'] as $tournament_id) {
                    $this->db_query->add_participant_to_tournament($result, intval($tournament_id));
                }
            }
            
            wp_send_json_success(array(
                'message' => __('Partecipante aggiunto con successo', 'eto'),
                'redirect' => admin_url('admin.php?page=eto-participants')
            ));
        } else {
            wp_send_json_error(array('message' => __('Errore durante l\'aggiunta del partecipante', 'eto')));
        }
    }
    
    /**
     * Handler AJAX per la rimozione di un partecipante
     */
    public function ajax_remove_participant() {
        // Verifica il nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eto-admin-nonce')) {
            wp_send_json_error(array('message' => __('Errore di sicurezza', 'eto')));
        }
        
        // Verifica i permessi
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Non hai i permessi necessari', 'eto')));
        }
        
        // Verifica l'ID del partecipante
        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('ID partecipante mancante', 'eto')));
        }
        
        // Elimina il partecipante dal database
        $result = $this->db_query->delete_participant(intval($_POST['id']));
        
        if ($result) {
            wp_send_json_success(array('message' => __('Partecipante rimosso con successo', 'eto')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante la rimozione del partecipante', 'eto')));
        }
    }
    
    /**
     * Ottiene i giochi disponibili per i tornei
     * @return array Lista dei giochi disponibili
     */
    public function get_available_games() {
        // Array predefinito di giochi supportati
        $games = array(
            'lol' => 'League of Legends',
            'dota2' => 'Dota 2',
            'csgo' => 'CS:GO',
            'valorant' => 'Valorant',
            'fortnite' => 'Fortnite',
            'pubg' => 'PUBG',
            'rocketleague' => 'Rocket League',
            'overwatch' => 'Overwatch',
            'fifa' => 'FIFA',
            'other' => 'Altro'
        );
        
        // Filtro per permettere l'aggiunta di giochi personalizzati
        return apply_filters('eto_available_games', $games);
    }
    
    /**
     * Ottiene i formati di torneo disponibili
     * @return array Lista dei formati disponibili
     */
    public function get_available_formats() {
        // Array predefinito di formati supportati
        $formats = array(
            'single_elimination' => 'Eliminazione diretta',
            'double_elimination' => 'Doppia eliminazione',
            'round_robin' => 'Girone all\'italiana',
            'swiss' => 'Sistema svizzero',
            'custom' => 'Personalizzato'
        );
        
        // Filtro per permettere l'aggiunta di formati personalizzati
        return apply_filters('eto_available_formats', $formats);
    }
}
