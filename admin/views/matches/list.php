<?php
/**
 * Vista per la lista dei match
 * 
 * @package ETO
 * @subpackage Views
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

// Inizializza le variabili necessarie
$tournaments = array(); // Questo dovrebbe essere ottenuto dal database
$matches = array(); // Questo dovrebbe essere ottenuto dal database
$page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;
$total_matches = 0; // Questo dovrebbe essere ottenuto dal database
$total_pages = ceil($total_matches / $per_page);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Match', 'eto'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=eto-add-match'); ?>" class="page-title-action"><?php _e('Aggiungi Nuovo', 'eto'); ?></a>
    
    <hr class="wp-header-end">
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="eto-matches">
                
                <select name="tournament_id">
                    <option value=""><?php _e('Tutti i tornei', 'eto'); ?></option>
                    <?php foreach ($tournaments as $tournament) : ?>
                        <option value="<?php echo esc_attr($tournament['id']); ?>" <?php selected(isset($_GET['tournament_id']) ? $_GET['tournament_id'] : '', $tournament['id']); ?>>
                            <?php echo esc_html($tournament['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="status">
                    <option value=""><?php _e('Tutti gli stati', 'eto'); ?></option>
                    <option value="pending" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'pending'); ?>><?php _e('In attesa', 'eto'); ?></option>
                    <option value="in_progress" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'in_progress'); ?>><?php _e('In corso', 'eto'); ?></option>
                    <option value="completed" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'completed'); ?>><?php _e('Completato', 'eto'); ?></option>
                    <option value="cancelled" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'cancelled'); ?>><?php _e('Annullato', 'eto'); ?></option>
                </select>
                
                <input type="submit" class="button" value="<?php _e('Filtra', 'eto'); ?>">
            </form>
        </div>
        
        <div class="tablenav-pages">
            <?php if ($total_pages > 1) : ?>
                <span class="displaying-num"><?php printf(_n('%s elemento', '%s elementi', $total_matches, 'eto'), number_format_i18n($total_matches)); ?></span>
                
                <span class="pagination-links">
                    <?php if ($page > 1) : ?>
                        <a class="first-page button" href="<?php echo add_query_arg('paged', 1); ?>">
                            <span class="screen-reader-text"><?php _e('Prima pagina', 'eto'); ?></span>
                            <span aria-hidden="true">«</span>
                        </a>
                        <a class="prev-page button" href="<?php echo add_query_arg('paged', max(1, $page - 1)); ?>">
                            <span class="screen-reader-text"><?php _e('Pagina precedente', 'eto'); ?></span>
                            <span aria-hidden="true">‹</span>
                        </a>
                    <?php endif; ?>
                    
                    <span class="paging-input">
                        <label for="current-page-selector" class="screen-reader-text"><?php _e('Pagina corrente', 'eto'); ?></label>
                        <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo $page; ?>" size="1" aria-describedby="table-paging">
                        <span class="tablenav-paging-text"> <?php _e('di', 'eto'); ?> <span class="total-pages"><?php echo $total_pages; ?></span></span>
                    </span>
                    
                    <?php if ($page < $total_pages) : ?>
                        <a class="next-page button" href="<?php echo add_query_arg('paged', min($total_pages, $page + 1)); ?>">
                            <span class="screen-reader-text"><?php _e('Pagina successiva', 'eto'); ?></span>
                            <span aria-hidden="true">›</span>
                        </a>
                        <a class="last-page button" href="<?php echo add_query_arg('paged', $total_pages); ?>">
                            <span class="screen-reader-text"><?php _e('Ultima pagina', 'eto'); ?></span>
                            <span aria-hidden="true">»</span>
                        </a>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
        
        <br class="clear">
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-tournament"><?php _e('Torneo', 'eto'); ?></th>
                <th scope="col" class="manage-column column-round"><?php _e('Round', 'eto'); ?></th>
                <th scope="col" class="manage-column column-teams"><?php _e('Team', 'eto'); ?></th>
                <th scope="col" class="manage-column column-result"><?php _e('Risultato', 'eto'); ?></th>
                <th scope="col" class="manage-column column-date"><?php _e('Data', 'eto'); ?></th>
                <th scope="col" class="manage-column column-status"><?php _e('Stato', 'eto'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php _e('Azioni', 'eto'); ?></th>
            </tr>
        </thead>
        
        <tbody id="the-list">
            <?php if (empty($matches)) : ?>
                <tr>
                    <td colspan="7"><?php _e('Nessun match trovato.', 'eto'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($matches as $match) : ?>
                    <tr>
                        <td class="column-tournament">
                            <?php
                            $tournament = ETO_Tournament_Model::get_by_id($match['tournament_id']);
                            if ($tournament) {
                                echo '<a href="' . admin_url('admin.php?page=eto-edit-tournament&id=' . $match['tournament_id']) . '">' . esc_html($tournament->get('name')) . '</a>';
                            } else {
                                echo __('Torneo non trovato', 'eto');
                            }
                            ?>
                        </td>
                        <td class="column-round">
                            <?php echo sprintf(__('Round %d - Match %d', 'eto'), intval($match['round']), intval($match['match_number'])); ?>
                        </td>
                        <td class="column-teams">
                            <?php
                            $team1 = ETO_Team_Model::get_by_id($match['team1_id']);
                            $team2 = ETO_Team_Model::get_by_id($match['team2_id']);
                            
                            if ($team1) {
                                echo '<a href="' . admin_url('admin.php?page=eto-edit-team&id=' . $match['team1_id']) . '">' . esc_html($team1->get('name')) . '</a>';
                            } else {
                                echo __('TBD', 'eto');
                            }
                            
                            echo ' vs ';
                            
                            if ($team2) {
                                echo '<a href="' . admin_url('admin.php?page=eto-edit-team&id=' . $match['team2_id']) . '">' . esc_html($team2->get('name')) . '</a>';
                            } else {
                                echo __('TBD', 'eto');
                            }
                            ?>
                        </td>
                        <td class="column-result">
                            <?php
                            $match_obj = ETO_Match_Model::get_by_id($match['id']);
                            $result = $match_obj ? $match_obj->get_result() : false;
                            
                            if ($result) {
                                echo esc_html($result['team1_score'] . ' - ' . $result['team2_score']);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td class="column-date">
                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($match['scheduled_date'])); ?>
                        </td>
                        <td class="column-status">
                            <?php
                            $status_labels = [
                                'pending' => __('In attesa', 'eto'),
                                'in_progress' => __('In corso', 'eto'),
                                'completed' => __('Completato', 'eto'),
                                'cancelled' => __('Annullato', 'eto')
                            ];
                            
                            $status_class = [
                                'pending' => 'status-pending',
                                'in_progress' => 'status-in-progress',
                                'completed' => 'status-completed',
                                'cancelled' => 'status-cancelled'
                            ];
                            
                            $status = isset($match['status']) ? $match['status'] : 'pending';
                            $label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
                            $class = isset($status_class[$status]) ? $status_class[$status] : '';
                            
                            echo '<span class="status-badge ' . esc_attr($class) . '">' . esc_html($label) . '</span>';
                            ?>
                        </td>
                        <td class="column-actions">
                            <a href="<?php echo admin_url('admin.php?page=eto-edit-match&id=' . $match['id']); ?>" class="button button-small">
                                <?php _e('Modifica', 'eto'); ?>
                            </a>
                            <button type="button" class="button button-small delete-match" data-id="<?php echo $match['id']; ?>">
                                <?php _e('Elimina', 'eto'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-tournament"><?php _e('Torneo', 'eto'); ?></th>
                <th scope="col" class="manage-column column-round"><?php _e('Round', 'eto'); ?></th>
                <th scope="col" class="manage-column column-teams"><?php _e('Team', 'eto'); ?></th>
                <th scope="col" class="manage-column column-result"><?php _e('Risultato', 'eto'); ?></th>
                <th scope="col" class="manage-column column-date"><?php _e('Data', 'eto'); ?></th>
                <th scope="col" class="manage-column column-status"><?php _e('Stato', 'eto'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php _e('Azioni', 'eto'); ?></th>
            </tr>
        </tfoot>
    </table>
    
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php if ($total_pages > 1) : ?>
                <span class="displaying-num"><?php printf(_n('%s elemento', '%s elementi', $total_matches, 'eto'), number_format_i18n($total_matches)); ?></span>
                
                <span class="pagination-links">
                    <?php if ($page > 1) : ?>
                        <a class="first-page button" href="<?php echo add_query_arg('paged', 1); ?>">
                            <span class="screen-reader-text"><?php _e('Prima pagina', 'eto'); ?></span>
                            <span aria-hidden="true">«</span>
                        </a>
                        <a class="prev-page button" href="<?php echo add_query_arg('paged', max(1, $page - 1)); ?>">
                            <span class="screen-reader-text"><?php _e('Pagina precedente', 'eto'); ?></span>
                            <span aria-hidden="true">‹</span>
                        </a>
                    <?php endif; ?>
                    
                    <span class="paging-input">
                        <label for="current-page-selector-bottom" class="screen-reader-text"><?php _e('Pagina corrente', 'eto'); ?></label>
                        <input class="current-page" id="current-page-selector-bottom" type="text" name="paged" value="<?php echo $page; ?>" size="1" aria-describedby="table-paging">
                        <span class="tablenav-paging-text"> <?php _e('di', 'eto'); ?> <span class="total-pages"><?php echo $total_pages; ?></span></span>
                    </span>
                    
                    <?php if ($page < $total_pages) : ?>
                        <a class="next-page button" href="<?php echo add_query_arg('paged', min($total_pages, $page + 1)); ?>">
                            <span class="screen-reader-text"><?php _e('Pagina successiva', 'eto'); ?></span>
                            <span aria-hidden="true">›</span>
                        </a>
                        <a class="last-page button" href="<?php echo add_query_arg('paged', $total_pages); ?>">
                            <span class="screen-reader-text"><?php _e('Ultima pagina', 'eto'); ?></span>
                            <span aria-hidden="true">»</span>
                        </a>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
        
        <br class="clear">
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('.delete-match').on('click', function(e) {
        e.preventDefault();
        
        var matchId = $(this).data('id');
        
        if (confirm('<?php _e('Sei sicuro di voler eliminare questo match? Questa azione non può essere annullata.', 'eto'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eto_delete_match',
                    match_id: matchId,
                    _wpnonce: '<?php echo wp_create_nonce('eto_delete_match'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('<?php _e('Si è verificato un errore durante l\'elaborazione della richiesta.', 'eto'); ?>');
                }
            });
        }
    });
});
</script>

<style>
.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-pending {
    background-color: #f0f0f0;
    color: #666;
}

.status-in-progress {
    background-color: #e6f7ff;
    color: #0073aa;
}

.status-completed {
    background-color: #e6ffe6;
    color: #46b450;
}

.status-cancelled {
    background-color: #ffe6e6;
    color: #dc3232;
}
</style>
