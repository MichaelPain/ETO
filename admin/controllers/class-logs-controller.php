<?php
/**
 * Controller per la gestione dei log
 * 
 * @package ETO
 * @subpackage Admin
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

class ETO_Logs_Controller {
    /**
     * Costruttore
     */
    public function __construct() {
        // Registra gli hook
        add_action('admin_menu', array($this, 'add_logs_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Registra gli hook per l'esportazione dei log
        add_action('admin_post_eto_export_logs', array($this, 'handle_export_logs'));
    }
    
    /**
     * Aggiunge la voce di menu per i log
     */
    public function add_logs_menu() {
        add_submenu_page(
            'eto-dashboard',
            __('Log di Sistema', 'eto'),
            __('Log', 'eto'),
            'manage_options',
            'eto-logs',
            array($this, 'render_logs_page')
        );
    }
    
    /**
     * Carica gli script e gli stili necessari
     *
     * @param string $hook Hook della pagina corrente
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'eto_page_eto-logs') {
            return;
        }
        
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        
        wp_enqueue_style('eto-logs', plugins_url('assets/css/logs.css', ETO_PLUGIN_FILE), array(), ETO_VERSION);
        wp_enqueue_script('eto-logs', plugins_url('assets/js/logs.js', ETO_PLUGIN_FILE), array('jquery', 'jquery-ui-datepicker'), ETO_VERSION, true);
    }
    
    /**
     * Renderizza la pagina dei log
     */
    public function render_logs_page() {
        // Carica il template
        require_once(ETO_PLUGIN_DIR . 'admin/views/logs/logs-list.php');
    }
    
    /**
     * Gestisce l'esportazione dei log in formato CSV
     */
    public function handle_export_logs() {
        // Verifica il nonce
        if (!isset($_POST['eto_logs_nonce']) || !wp_verify_nonce($_POST['eto_logs_nonce'], 'eto_logs_actions')) {
            wp_die(__('Verifica di sicurezza fallita.', 'eto'));
        }
        
        // Verifica i permessi
        if (!current_user_can('manage_options')) {
            wp_die(__('Non hai i permessi per eseguire questa operazione.', 'eto'));
        }
        
        // Verifica che ci siano log da esportare
        if (empty($_POST['log_ids'])) {
            wp_redirect(admin_url('admin.php?page=eto-logs&error=no_logs_selected'));
            exit;
        }
        
        $log_ids = array_map('intval', $_POST['log_ids']);
        
        // Ottieni i log selezionati
        global $wpdb;
        $table_name = $wpdb->prefix . 'eto_logs';
        
        $placeholders = implode(', ', array_fill(0, count($log_ids), '%d'));
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE id IN ($placeholders) ORDER BY created_at DESC", $log_ids);
        
        $logs = $wpdb->get_results($query, ARRAY_A);
        
        if (empty($logs)) {
            wp_redirect(admin_url('admin.php?page=eto-logs&error=no_logs_found'));
            exit;
        }
        
        // Prepara l'output CSV
        $filename = 'eto-logs-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // Intestazioni CSV
        fputcsv($output, array(
            'ID',
            __('Livello', 'eto'),
            __('Categoria', 'eto'),
            __('Messaggio', 'eto'),
            __('Contesto', 'eto'),
            __('Utente ID', 'eto'),
            __('IP', 'eto'),
            __('User Agent', 'eto'),
            __('Data', 'eto')
        ));
        
        // Dati CSV
        foreach ($logs as $log) {
            fputcsv($output, array(
                $log['id'],
                $log['level'],
                $log['category'],
                $log['message'],
                $log['context'],
                $log['user_id'],
                $log['ip_address'],
                $log['user_agent'],
                $log['created_at']
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Ottiene il numero di log per ogni livello
     *
     * @return array Array con il conteggio dei log per livello
     */
    public function get_log_counts_by_level() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eto_logs';
        
        $results = $wpdb->get_results("
            SELECT level, COUNT(*) as count
            FROM $table_name
            GROUP BY level
        ", ARRAY_A);
        
        $counts = array(
            ETO_Logger::DEBUG => 0,
            ETO_Logger::INFO => 0,
            ETO_Logger::WARNING => 0,
            ETO_Logger::ERROR => 0,
            ETO_Logger::CRITICAL => 0
        );
        
        foreach ($results as $result) {
            $counts[$result['level']] = (int) $result['count'];
        }
        
        return $counts;
    }
    
    /**
     * Ottiene il numero di log per ogni categoria
     *
     * @return array Array con il conteggio dei log per categoria
     */
    public function get_log_counts_by_category() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eto_logs';
        
        $results = $wpdb->get_results("
            SELECT category, COUNT(*) as count
            FROM $table_name
            GROUP BY category
        ", ARRAY_A);
        
        $counts = array(
            ETO_Logger::CATEGORY_SYSTEM => 0,
            ETO_Logger::CATEGORY_SECURITY => 0,
            ETO_Logger::CATEGORY_USER => 0,
            ETO_Logger::CATEGORY_TOURNAMENT => 0,
            ETO_Logger::CATEGORY_TEAM => 0,
            ETO_Logger::CATEGORY_MATCH => 0,
            ETO_Logger::CATEGORY_API => 0
        );
        
        foreach ($results as $result) {
            $counts[$result['category']] = (int) $result['count'];
        }
        
        return $counts;
    }
    
    /**
     * Ottiene il numero di log per ogni giorno negli ultimi 30 giorni
     *
     * @return array Array con il conteggio dei log per giorno
     */
    public function get_log_counts_by_day() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eto_logs';
        
        $results = $wpdb->get_results("
            SELECT DATE(created_at) as log_date, COUNT(*) as count
            FROM $table_name
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY log_date ASC
        ", ARRAY_A);
        
        $counts = array();
        
        // Inizializza l'array con tutti i giorni degli ultimi 30 giorni
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $counts[$date] = 0;
        }
        
        // Popola l'array con i conteggi effettivi
        foreach ($results as $result) {
            $counts[$result['log_date']] = (int) $result['count'];
        }
        
        return $counts;
    }
}

// Inizializza il controller
$eto_logs_controller = new ETO_Logs_Controller();
