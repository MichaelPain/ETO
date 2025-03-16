<?php
/**
 * Vista per la lista dei team
 * 
 * @package ETO
 * @subpackage Views
 * @since 2.5.2
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

// Inizializza le variabili necessarie
$games = $this->get_available_games();
$page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;
$total_teams = 0; // Questo dovrebbe essere ottenuto dal database
$total_pages = ceil($total_teams / $per_page);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Team', 'eto'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=eto-add-team'); ?>" class="page-title-action"><?php _e('Aggiungi Nuovo', 'eto'); ?></a>
    
    <hr class="wp-header-end">
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="eto-teams">
                
                <select name="game">
                    <option value=""><?php _e('Tutti i giochi', 'eto'); ?></option>
                    <?php foreach ($games as $game_id => $game_name) : ?>
                        <option value="<?php echo esc_attr($game_id); ?>" <?php selected(isset($_GET['game']) ? $_GET['game'] : '', $game_id); ?>><?php echo esc_html($game_name); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <input type="submit" class="button" value="<?php _e('Filtra', 'eto'); ?>">
            </form>
        </div>
        
        <div class="tablenav-pages">
            <?php if ($total_pages > 1) : ?>
                <span class="displaying-num"><?php printf(_n('%s elemento', '%s elementi', $total_teams, 'eto'), number_format_i18n($total_teams)); ?></span>
                
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
                <th scope="col" class="manage-column column-logo"><?php _e('Logo', 'eto'); ?></th>
                <th scope="col" class="manage-column column-game"><?php _e('Gioco', 'eto'); ?></th>
                <th scope="col" class="manage-column column-captain"><?php _e('Capitano', 'eto'); ?></th>
                <th scope="col" class="manage-column column-members"><?php _e('Membri', 'eto'); ?></th>
                <th scope="col" class="manage-column column-tournaments"><?php _e('Tornei', 'eto'); ?></th>
            </tr>
        </thead>
        
        <tbody id="the-list">
            <?php if (empty($teams)) : ?>
                <tr>
                    <td colspan="6"><?php _e('Nessun team trovato.', 'eto'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($teams as $team) : ?>
                    <tr>
                        <td class="column-name column-primary">
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=eto-edit-team&id=' . $team['id']); ?>"><?php echo esc_html($team['name']); ?></a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=eto-edit-team&id=' . $team['id']); ?>"><?php _e('Modifica', 'eto'); ?></a> |
                                </span>
                                <span class="view">
                                    <a href="<?php echo home_url('team/' . $team['id']); ?>"><?php _e('Visualizza', 'eto'); ?></a> |
                                </span>
                                <span class="delete">
                                    <a href="#" class="delete-team" data-id="<?php echo $team['id']; ?>"><?php _e('Elimina', 'eto'); ?></a>
                                </span>
                            </div>
                            <button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e('Mostra più dettagli', 'eto'); ?></span></button>
                        </td>
                        <td class="column-logo">
                            <?php if (!empty($team['logo_url'])) : ?>
                                <img src="<?php echo esc_url($team['logo_url']); ?>" alt="<?php echo esc_attr($team['name']); ?>" style="max-width: 50px; max-height: 50px;">
                            <?php else : ?>
                                <span class="no-logo"><?php _e('Nessun logo', 'eto'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-game">
                            <?php 
                            $games = $this->get_available_games();
                            echo isset($games[$team['game']]) ? esc_html($games[$team['game']]) : esc_html($team['game']);
                            ?>
                        </td>
                        <td class="column-captain">
                            <?php 
                            $captain = ETO_User_Model::get_by_id($team['captain_id']);
                            if ($captain) {
                                echo '<a href="' . admin_url('user-edit.php?user_id=' . $team['captain_id']) . '">' . esc_html($captain->get('display_name')) . '</a>';
                            } else {
                                echo __('Non assegnato', 'eto');
                            }
                            ?>
                        </td>
                        <td class="column-members">
                            <?php 
                            $team_obj = ETO_Team_Model::get_by_id($team['id']);
                            $members = $team_obj ? $team_obj->get_members() : [];
                            echo count($members);
                            ?>
                        </td>
                        <td class="column-tournaments">
                            <?php 
                            $team_obj = ETO_Team_Model::get_by_id($team['id']);
                            $tournaments = $team_obj ? $team_obj->get_tournaments() : [];
                            echo count($tournaments);
                            
                            if (!empty($tournaments)) {
                                echo '<div class="row-actions">';
                                foreach ($tournaments as $index => $tournament) {
                                    echo '<span class="view">';
                                    echo '<a href="' . admin_url('admin.php?page=eto-edit-tournament&id=' . $tournament['id']) . '">' . esc_html($tournament['name']) . '</a>';
                                    if ($index < count($tournaments) - 1) {
                                        echo ', ';
                                    }
                                    echo '</span>';
                                }
                                echo '</div>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-name column-primary"><?php _e('Nome', 'eto'); ?></th>
                <th scope="col" class="manage-column column-logo"><?php _e('Logo', 'eto'); ?></th>
                <th scope="col" class="manage-column column-game"><?php _e('Gioco', 'eto'); ?></th>
                <th scope="col" class="manage-column column-captain"><?php _e('Capitano', 'eto'); ?></th>
                <th scope="col" class="manage-column column-members"><?php _e('Membri', 'eto'); ?></th>
                <th scope="col" class="manage-column column-tournaments"><?php _e('Tornei', 'eto'); ?></th>
            </tr>
        </tfoot>
    </table>
    
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php if ($total_pages > 1) : ?>
                <span class="displaying-num"><?php printf(_n('%s elemento', '%s elementi', $total_teams, 'eto'), number_format_i18n($total_teams)); ?></span>
                
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
    $('.delete-team').on('click', function(e) {
        e.preventDefault();
        
        var teamId = $(this).data('id');
        
        if (confirm('<?php _e('Sei sicuro di voler eliminare questo team? Questa azione non può essere annullata.', 'eto'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eto_delete_team',
                    team_id: teamId,
                    _wpnonce: '<?php echo wp_create_nonce('eto_delete_team'); ?>'
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
.no-logo {
    color: #999;
    font-style: italic;
}
</style>
