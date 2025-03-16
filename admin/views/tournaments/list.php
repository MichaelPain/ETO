<?php
/**
 * Vista per la lista dei tornei
 * 
 * @package ETO
 * @subpackage Views
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

// Inizializza le variabili necessarie
$tournaments = array(); // Questo dovrebbe essere ottenuto dal database
$page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;
$total_tournaments = 0; // Questo dovrebbe essere ottenuto dal database
$total_pages = ceil($total_tournaments / $per_page);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Tornei', 'eto'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=eto-add-tournament'); ?>" class="page-title-action"><?php _e('Aggiungi Nuovo', 'eto'); ?></a>
    
    <hr class="wp-header-end">
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="eto-tournaments">
                
                <select name="status">
                    <option value=""><?php _e('Tutti gli stati', 'eto'); ?></option>
                    <option value="pending" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'pending'); ?>><?php _e('In attesa', 'eto'); ?></option>
                    <option value="active" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'active'); ?>><?php _e('Attivo', 'eto'); ?></option>
                    <option value="completed" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'completed'); ?>><?php _e('Completato', 'eto'); ?></option>
                    <option value="cancelled" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'cancelled'); ?>><?php _e('Annullato', 'eto'); ?></option>
                </select>
                
                <select name="game">
                    <option value=""><?php _e('Tutti i giochi', 'eto'); ?></option>
                    <?php foreach ($this->get_available_games() as $game_id => $game_name) : ?>
                        <option value="<?php echo esc_attr($game_id); ?>" <?php selected(isset($_GET['game']) ? $_GET['game'] : '', $game_id); ?>><?php echo esc_html($game_name); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <input type="submit" class="button" value="<?php _e('Filtra', 'eto'); ?>">
            </form>
        </div>
        
        <div class="tablenav-pages">
            <?php if ($total_pages > 1) : ?>
                <span class="displaying-num"><?php printf(_n('%s elemento', '%s elementi', $total_tournaments, 'eto'), number_format_i18n($total_tournaments)); ?></span>
                
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
                <th scope="col" class="manage-column column-name column-primary"><?php _e('Nome', 'eto'); ?></th>
                <th scope="col" class="manage-column column-game"><?php _e('Gioco', 'eto'); ?></th>
                <th scope="col" class="manage-column column-format"><?php _e('Formato', 'eto'); ?></th>
                <th scope="col" class="manage-column column-teams"><?php _e('Team', 'eto'); ?></th>
                <th scope="col" class="manage-column column-dates"><?php _e('Date', 'eto'); ?></th>
                <th scope="col" class="manage-column column-status"><?php _e('Stato', 'eto'); ?></th>
            </tr>
        </thead>
        
        <tbody id="the-list">
            <?php if (empty($tournaments)) : ?>
                <tr>
                    <td colspan="6"><?php _e('Nessun torneo trovato.', 'eto'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($tournaments as $tournament) : ?>
                    <tr>
                        <td class="column-name column-primary">
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=eto-edit-tournament&id=' . $tournament['id']); ?>"><?php echo esc_html($tournament['name']); ?></a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=eto-edit-tournament&id=' . $tournament['id']); ?>"><?php _e('Modifica', 'eto'); ?></a> |
                                </span>
                                <span class="view">
                                    <a href="<?php echo home_url('tournament/' . $tournament['id']); ?>"><?php _e('Visualizza', 'eto'); ?></a> |
                                </span>
                                <span class="delete">
                                    <a href="#" class="delete-tournament" data-id="<?php echo $tournament['id']; ?>"><?php _e('Elimina', 'eto'); ?></a>
                                </span>
                            </div>
                            <button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e('Mostra più dettagli', 'eto'); ?></span></button>
                        </td>
                        <td class="column-game">
                            <?php 
                            $games = $this->get_available_games();
                            echo isset($games[$tournament['game']]) ? esc_html($games[$tournament['game']]) : esc_html($tournament['game']);
                            ?>
                        </td>
                        <td class="column-format">
                            <?php 
                            $formats = $this->get_available_formats();
                            echo isset($formats[$tournament['format']]) ? esc_html($formats[$tournament['format']]) : esc_html($tournament['format']);
                            ?>
                        </td>
                        <td class="column-teams">
                            <?php 
                            $tournament_obj = ETO_Tournament_Model::get_by_id($tournament['id']);
                            $teams = $tournament_obj ? $tournament_obj->get_teams() : [];
                            echo count($teams) . '/' . $tournament['max_teams'];
                            ?>
                        </td>
                        <td class="column-dates">
                            <?php 
                            echo sprintf(
                                __('Inizio: %s<br>Fine: %s', 'eto'),
                                date_i18n(get_option('date_format'), strtotime($tournament['start_date'])),
                                date_i18n(get_option('date_format'), strtotime($tournament['end_date']))
                            );
                            ?>
                        </td>
                        <td class="column-status">
                            <?php 
                            $status_labels = [
                                'pending' => __('In attesa', 'eto'),
                                'active' => __('Attivo', 'eto'),
                                'completed' => __('Completato', 'eto'),
                                'cancelled' => __('Annullato', 'eto')
                            ];
                            
                            $status_class = [
                                'pending' => 'status-pending',
                                'active' => 'status-active',
                                'completed' => 'status-completed',
                                'cancelled' => 'status-cancelled'
                            ];
                            
                            $status = isset($tournament['status']) ? $tournament['status'] : 'pending';
                            $label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
                            $class = isset($status_class[$status]) ? $status_class[$status] : '';
                            
                            echo '<span class="status-badge ' . esc_attr($class) . '">' . esc_html($label) . '</span>';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-name column-primary"><?php _e('Nome', 'eto'); ?></th>
                <th scope="col" class="manage-column column-game"><?php _e('Gioco', 'eto'); ?></th>
                <th scope="col" class="manage-column column-format"><?php _e('Formato', 'eto'); ?></th>
                <th scope="col" class="manage-column column-teams"><?php _e('Team', 'eto'); ?></th>
                <th scope="col" class="manage-column column-dates"><?php _e('Date', 'eto'); ?></th>
                <th scope="col" class="manage-column column-status"><?php _e('Stato', 'eto'); ?></th>
            </tr>
        </tfoot>
    </table>
    
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php if ($total_pages > 1) : ?>
                <span class="displaying-num"><?php printf(_n('%s elemento', '%s elementi', $total_tournaments, 'eto'), number_format_i18n($total_tournaments)); ?></span>
                
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
    $('.delete-tournament').on('click', function(e) {
        e.preventDefault();
        
        var tournamentId = $(this).data('id');
        
        if (confirm('<?php _e('Sei sicuro di voler eliminare questo torneo? Questa azione non può essere annullata.', 'eto'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eto_delete_tournament',
                    tournament_id: tournamentId,
                    _wpnonce: '<?php echo wp_create_nonce('eto_delete_tournament'); ?>'
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

.status-active {
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
