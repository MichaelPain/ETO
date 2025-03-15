<?php
/**
 * Template per la visualizzazione dei log
 * 
 * @package ETO
 * @subpackage Admin
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

// Ottieni i parametri di filtro dalla query
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;

$level = isset($_GET['level']) ? sanitize_text_field($_GET['level']) : '';
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

// Costruisci gli argomenti per la query
$args = array(
    'page' => $current_page,
    'per_page' => $per_page,
    'orderby' => isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at',
    'order' => isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC'
);

if (!empty($level)) {
    $args['level'] = $level;
}

if (!empty($category)) {
    $args['category'] = $category;
}

if (!empty($user_id)) {
    $args['user_id'] = $user_id;
}

if (!empty($search)) {
    $args['search'] = $search;
}

if (!empty($date_from)) {
    $args['date_from'] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $args['date_to'] = $date_to . ' 23:59:59';
}

// Ottieni i log
$logger = eto_get_logger();
$logs = $logger->get_logs($args);
$total_logs = $logger->count_logs($args);
$total_pages = ceil($total_logs / $per_page);

// Ottieni le opzioni di configurazione del logger
$logger_options = $logger->get_options();

// Livelli di log disponibili
$log_levels = array(
    ETO_Logger::DEBUG => __('Debug', 'eto'),
    ETO_Logger::INFO => __('Info', 'eto'),
    ETO_Logger::WARNING => __('Warning', 'eto'),
    ETO_Logger::ERROR => __('Error', 'eto'),
    ETO_Logger::CRITICAL => __('Critical', 'eto')
);

// Categorie di log disponibili
$log_categories = array(
    ETO_Logger::CATEGORY_SYSTEM => __('Sistema', 'eto'),
    ETO_Logger::CATEGORY_SECURITY => __('Sicurezza', 'eto'),
    ETO_Logger::CATEGORY_USER => __('Utente', 'eto'),
    ETO_Logger::CATEGORY_TOURNAMENT => __('Torneo', 'eto'),
    ETO_Logger::CATEGORY_TEAM => __('Team', 'eto'),
    ETO_Logger::CATEGORY_MATCH => __('Match', 'eto'),
    ETO_Logger::CATEGORY_API => __('API', 'eto')
);

// Colori per i livelli di log
$log_level_colors = array(
    ETO_Logger::DEBUG => '#999999',
    ETO_Logger::INFO => '#2196F3',
    ETO_Logger::WARNING => '#FF9800',
    ETO_Logger::ERROR => '#F44336',
    ETO_Logger::CRITICAL => '#9C27B0'
);

// Costruisci l'URL base per i filtri
$base_url = add_query_arg(array(
    'page' => 'eto-logs'
), admin_url('admin.php'));

// Gestisci le azioni di massa
if (isset($_POST['eto_logs_action']) && isset($_POST['eto_logs_nonce']) && wp_verify_nonce($_POST['eto_logs_nonce'], 'eto_logs_actions')) {
    $action = sanitize_text_field($_POST['eto_logs_action']);
    
    if ($action === 'delete_all') {
        ETO_Logger::drop_table();
        ETO_Logger::create_table();
        
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Tutti i log sono stati eliminati con successo.', 'eto') . '</p></div>';
    } elseif ($action === 'export_csv' && !empty($_POST['log_ids'])) {
        $log_ids = array_map('intval', $_POST['log_ids']);
        
        // Implementazione dell'esportazione CSV
        // ...
    }
}

// Gestisci le impostazioni del logger
if (isset($_POST['eto_logger_options']) && isset($_POST['eto_logger_nonce']) && wp_verify_nonce($_POST['eto_logger_nonce'], 'eto_logger_options')) {
    $new_options = array(
        'enabled' => isset($_POST['enabled']) ? true : false,
        'min_level' => sanitize_text_field($_POST['min_level']),
        'log_to_file' => isset($_POST['log_to_file']) ? true : false,
        'log_file_path' => sanitize_text_field($_POST['log_file_path']),
        'rotate_logs' => isset($_POST['rotate_logs']) ? true : false,
        'max_log_size' => intval($_POST['max_log_size']),
        'max_log_age' => intval($_POST['max_log_age']),
        'retention_count' => intval($_POST['retention_count'])
    );
    
    $logger->update_options($new_options);
    $logger_options = $logger->get_options();
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Le impostazioni del logger sono state aggiornate con successo.', 'eto') . '</p></div>';
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Log di Sistema', 'eto'); ?></h1>
    
    <a href="#" class="page-title-action" onclick="jQuery('#eto-logger-options').toggle(); return false;"><?php _e('Impostazioni', 'eto'); ?></a>
    
    <div id="eto-logger-options" style="display: none; margin-top: 20px; background: #fff; padding: 15px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
        <h2><?php _e('Impostazioni Logger', 'eto'); ?></h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('eto_logger_options', 'eto_logger_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Logging abilitato', 'eto'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enabled" value="1" <?php checked($logger_options['enabled']); ?>>
                            <?php _e('Abilita il logging di sistema', 'eto'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Livello minimo', 'eto'); ?></th>
                    <td>
                        <select name="min_level">
                            <?php foreach ($log_levels as $level_key => $level_name) : ?>
                                <option value="<?php echo esc_attr($level_key); ?>" <?php selected($logger_options['min_level'], $level_key); ?>>
                                    <?php echo esc_html($level_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Solo i log con questo livello o superiore saranno registrati.', 'eto'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Log su file', 'eto'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="log_to_file" value="1" <?php checked($logger_options['log_to_file']); ?>>
                            <?php _e('Abilita il logging su file', 'eto'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Percorso file di log', 'eto'); ?></th>
                    <td>
                        <input type="text" name="log_file_path" value="<?php echo esc_attr($logger_options['log_file_path']); ?>" class="regular-text">
                        <p class="description"><?php _e('Percorso completo del file di log.', 'eto'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Rotazione log', 'eto'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="rotate_logs" value="1" <?php checked($logger_options['rotate_logs']); ?>>
                            <?php _e('Abilita la rotazione automatica dei file di log', 'eto'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Dimensione massima log', 'eto'); ?></th>
                    <td>
                        <input type="number" name="max_log_size" value="<?php echo esc_attr($logger_options['max_log_size']); ?>" class="small-text">
                        <span><?php _e('MB', 'eto'); ?></span>
                        <p class="description"><?php _e('Dimensione massima del file di log prima della rotazione.', 'eto'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Età massima log', 'eto'); ?></th>
                    <td>
                        <input type="number" name="max_log_age" value="<?php echo esc_attr($logger_options['max_log_age']); ?>" class="small-text">
                        <span><?php _e('giorni', 'eto'); ?></span>
                        <p class="description"><?php _e('I log più vecchi di questo numero di giorni saranno eliminati automaticamente.', 'eto'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Numero di file da conservare', 'eto'); ?></th>
                    <td>
                        <input type="number" name="retention_count" value="<?php echo esc_attr($logger_options['retention_count']); ?>" class="small-text">
                        <p class="description"><?php _e('Numero di file di log ruotati da conservare.', 'eto'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="eto_logger_options" class="button button-primary" value="<?php _e('Salva impostazioni', 'eto'); ?>">
            </p>
        </form>
    </div>
    
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
        <input type="hidden" name="page" value="eto-logs">
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="level">
                    <option value=""><?php _e('Tutti i livelli', 'eto'); ?></option>
                    <?php foreach ($log_levels as $level_key => $level_name) : ?>
                        <option value="<?php echo esc_attr($level_key); ?>" <?php selected($level, $level_key); ?>>
                            <?php echo esc_html($level_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="category">
                    <option value=""><?php _e('Tutte le categorie', 'eto'); ?></option>
                    <?php foreach ($log_categories as $category_key => $category_name) : ?>
                        <option value="<?php echo esc_attr($category_key); ?>" <?php selected($category, $category_key); ?>>
                            <?php echo esc_html($category_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" name="date_from" value="<?php echo esc_attr($date_from); ?>" placeholder="<?php _e('Data inizio (YYYY-MM-DD)', 'eto'); ?>" class="date-picker">
                <input type="text" name="date_to" value="<?php echo esc_attr($date_to); ?>" placeholder="<?php _e('Data fine (YYYY-MM-DD)', 'eto'); ?>" class="date-picker">
                
                <input type="submit" class="button" value="<?php _e('Filtra', 'eto'); ?>">
                <?php if (!empty($level) || !empty($category) || !empty($user_id) || !empty($search) || !empty($date_from) || !empty($date_to)) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=eto-logs')); ?>" class="button"><?php _e('Reimposta', 'eto'); ?></a>
                <?php endif; ?>
            </div>
            
            <div class="alignright">
                <p class="search-box">
                    <label class="screen-reader-text" for="log-search-input"><?php _e('Cerca log:', 'eto'); ?></label>
                    <input type="search" id="log-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Cerca nei log...', 'eto'); ?>">
                    <input type="submit" id="search-submit" class="button" value="<?php _e('Cerca', 'eto'); ?>">
                </p>
            </div>
            
            <br class="clear">
        </div>
    </form>
    
    <form method="post" action="">
        <?php wp_nonce_field('eto_logs_actions', 'eto_logs_nonce'); ?>
        
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="eto_logs_action">
                    <option value="-1"><?php _e('Azioni di massa', 'eto'); ?></option>
                    <option value="delete_all"><?php _e('Elimina tutti i log', 'eto'); ?></option>
                    <option value="export_csv"><?php _e('Esporta selezionati (CSV)', 'eto'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php _e('Applica', 'eto'); ?>">
            </div>
            
            <div class="tablenav-pages">
                <?php if ($total_pages > 1) : ?>
                    <span class="displaying-num">
                        <?php printf(_n('%s elemento', '%s elementi', $total_logs, 'eto'), number_format_i18n($total_logs)); ?>
                    </span>
                    
                    <span class="pagination-links">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $current_page
                        ));
                        ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <br class="clear">
        </div>
        
        <table class="wp-list-table widefat fixed striped eto-logs-table">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1">
                    </td>
                    <th scope="col" class="manage-column column-level">
                        <?php _e('Livello', 'eto'); ?>
                    </th>
                    <th scope="col" class="manage-column column-category">
                        <?php _e('Categoria', 'eto'); ?>
                    </th>
                    <th scope="col" class="manage-column column-message">
                        <?php _e('Messaggio', 'eto'); ?>
                    </th>
                    <th scope="col" class="manage-column column-user">
                        <?php _e('Utente', 'eto'); ?>
                    </th>
                    <th scope="col" class="manage-column column-ip">
                        <?php _e('IP', 'eto'); ?>
                    </th>
                    <th scope="col" class="manage-column column-date">
                        <?php _e('Data', 'eto'); ?>
                    </th>
                </tr>
            </thead>
            
            <tbody>
                <?php if (empty($logs)) : ?>
                    <tr>
                        <td colspan="7"><?php _e('Nessun log trovato.', 'eto'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($logs as $log) : ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="log_ids[]" value="<?php echo esc_attr($log['id']); ?>">
                            </th>
                            <td class="column-level">
                                <span class="log-level log-level-<?php echo esc_attr($log['level']); ?>" style="background-color: <?php echo esc_attr($log_level_colors[$log['level']]); ?>;">
                                    <?php echo esc_html($log_levels[$log['level']]); ?>
                                </span>
                            </td>
                            <td class="column-category">
                                <?php echo esc_html($log_categories[$log['category']]); ?>
                            </td>
                            <td class="column-message">
                                <strong><?php echo esc_html($log['message']); ?></strong>
                                <?php if (!empty($log['context'])) : ?>
                                    <a href="#" class="toggle-context" data-log-id="<?php echo esc_attr($log['id']); ?>"><?php _e('Mostra dettagli', 'eto'); ?></a>
                                    <div id="log-context-<?php echo esc_attr($log['id']); ?>" class="log-context" style="display: none;">
                                        <pre><?php echo esc_html(json_encode($log['context'], JSON_PRETTY_PRINT)); ?></pre>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="column-user">
                                <?php
                                if (!empty($log['user_id'])) {
                                    $user = get_userdata($log['user_id']);
                                    if ($user) {
                                        echo '<a href="' . esc_url(add_query_arg('user_id', $log['user_id'], $base_url)) . '">' . esc_html($user->user_login) . '</a>';
                                    } else {
                                        echo esc_html($log['user_id']);
                                    }
                                } else {
                                    _e('Non autenticato', 'eto');
                                }
                                ?>
                            </td>
                            <td class="column-ip">
                                <?php echo esc_html($log['ip_address']); ?>
                            </td>
                            <td class="column-date">
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log['created_at']))); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            
            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-2">
                    </td>
                    <th scope="col" class="manage-column column-level">
                        <?php _e('Livello', 'eto'); ?>
                    </th>
                    <th scope="col" class="manage-column column-category">
                        <?php _e('Categoria', 'eto'); ?>
                    </th>
                    <th scope="col" class="manage-column column-message">
                        <?php _e('Messaggio', 'eto'); ?>
                    </th>
                    <th scope="col" class="manage-column column-user">
                        <?php _e('Utente', 'eto'); ?>
                    </th>
                    <th scope="col" class="manage-column column-ip">
                        <?php _e('IP', 'eto'); ?>
                    </th>
                    <th scope="col" class="manage-column column-date">
                        <?php _e('Data', 'eto'); ?>
                    </th>
                </tr>
            </tfoot>
        </table>
        
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="eto_logs_action2">
                    <option value="-1"><?php _e('Azioni di massa', 'eto'); ?></option>
                    <option value="delete_all"><?php _e('Elimina tutti i log', 'eto'); ?></option>
                    <option value="export_csv"><?php _e('Esporta selezionati (CSV)', 'eto'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php _e('Applica', 'eto'); ?>">
            </div>
            
            <div class="tablenav-pages">
                <?php if ($total_pages > 1) : ?>
                    <span class="displaying-num">
                        <?php printf(_n('%s elemento', '%s elementi', $total_logs, 'eto'), number_format_i18n($total_logs)); ?>
                    </span>
                    
                    <span class="pagination-links">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $current_page
                        ));
                        ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <br class="clear">
        </div>
    </form>
</div>

<style>
.log-level {
    display: inline-block;
    padding: 3px 6px;
    border-radius: 3px;
    color: #fff;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.log-context {
    margin-top: 5px;
    padding: 10px;
    background-color: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 3px;
    max-height: 200px;
    overflow: auto;
}

.eto-logs-table .column-level {
    width: 80px;
}

.eto-logs-table .column-category {
    width: 100px;
}

.eto-logs-table .column-user {
    width: 120px;
}

.eto-logs-table .column-ip {
    width: 120px;
}

.eto-logs-table .column-date {
    width: 150px;
}

.toggle-context {
    display: inline-block;
    margin-left: 10px;
    font-size: 12px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Inizializza i date picker
    $('.date-picker').datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true
    });
    
    // Toggle dei dettagli del contesto
    $('.toggle-context').on('click', function(e) {
        e.preventDefault();
        var logId = $(this).data('log-id');
        var $context = $('#log-context-' + logId);
        
        $context.toggle();
        
        if ($context.is(':visible')) {
            $(this).text('<?php _e('Nascondi dettagli', 'eto'); ?>');
        } else {
            $(this).text('<?php _e('Mostra dettagli', 'eto'); ?>');
        }
    });
});
</script>
