<?php
/**
 * Vista per la dashboard del plugin
 * 
 * @package ETO
 * @subpackage Views
 * @since 2.5.4
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Dashboard ETO', 'eto'); ?></h1>
    
    <hr class="wp-header-end">
    
    <div class="eto-dashboard-wrapper">
        <div class="eto-dashboard-header">
            <div class="eto-dashboard-welcome">
                <h2><?php _e('Benvenuto in Esports Tournament Organizer', 'eto'); ?></h2>
                <p><?php _e('Gestisci facilmente i tuoi tornei di esports con ETO.', 'eto'); ?></p>
            </div>
        </div>
        
        <div class="eto-dashboard-main">
            <div class="eto-dashboard-column">
                <div class="eto-dashboard-card">
                    <h3><?php _e('Tornei Recenti', 'eto'); ?></h3>
                    <?php
                    // Ottieni i tornei recenti
                    $recent_tournaments = array(); // Qui dovresti ottenere i tornei recenti dal database
                    
                    if (empty($recent_tournaments)) {
                        echo '<p>' . __('Nessun torneo trovato.', 'eto') . '</p>';
                        echo '<a href="' . admin_url('admin.php?page=eto-tournaments') . '" class="button button-primary">' . __('Crea il tuo primo torneo', 'eto') . '</a>';
                    } else {
                        echo '<ul class="eto-dashboard-list">';
                        foreach ($recent_tournaments as $tournament) {
                            echo '<li>';
                            echo '<a href="' . admin_url('admin.php?page=eto-edit-tournament&id=' . $tournament['id']) . '">' . esc_html($tournament['name']) . '</a>';
                            echo '<span class="eto-dashboard-meta">' . esc_html($tournament['status']) . '</span>';
                            echo '</li>';
                        }
                        echo '</ul>';
                        echo '<a href="' . admin_url('admin.php?page=eto-tournaments') . '" class="button">' . __('Visualizza tutti i tornei', 'eto') . '</a>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="eto-dashboard-column">
                <div class="eto-dashboard-card">
                    <h3><?php _e('Team Recenti', 'eto'); ?></h3>
                    <?php
                    // Ottieni i team recenti
                    $recent_teams = array(); // Qui dovresti ottenere i team recenti dal database
                    
                    if (empty($recent_teams)) {
                        echo '<p>' . __('Nessun team trovato.', 'eto') . '</p>';
                        echo '<a href="' . admin_url('admin.php?page=eto-teams') . '" class="button button-primary">' . __('Crea il tuo primo team', 'eto') . '</a>';
                    } else {
                        echo '<ul class="eto-dashboard-list">';
                        foreach ($recent_teams as $team) {
                            echo '<li>';
                            echo '<a href="' . admin_url('admin.php?page=eto-edit-team&id=' . $team['id']) . '">' . esc_html($team['name']) . '</a>';
                            echo '<span class="eto-dashboard-meta">' . esc_html($team['game']) . '</span>';
                            echo '</li>';
                        }
                        echo '</ul>';
                        echo '<a href="' . admin_url('admin.php?page=eto-teams') . '" class="button">' . __('Visualizza tutti i team', 'eto') . '</a>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="eto-dashboard-footer">
            <div class="eto-dashboard-card">
                <h3><?php _e('Guida Rapida', 'eto'); ?></h3>
                <ul class="eto-dashboard-steps">
                    <li>
                        <span class="eto-dashboard-step-number">1</span>
                        <div class="eto-dashboard-step-content">
                            <h4><?php _e('Crea Team', 'eto'); ?></h4>
                            <p><?php _e('Crea i team che parteciperanno ai tuoi tornei.', 'eto'); ?></p>
                            <a href="<?php echo admin_url('admin.php?page=eto-teams'); ?>" class="button"><?php _e('Gestisci Team', 'eto'); ?></a>
                        </div>
                    </li>
                    <li>
                        <span class="eto-dashboard-step-number">2</span>
                        <div class="eto-dashboard-step-content">
                            <h4><?php _e('Crea Tornei', 'eto'); ?></h4>
                            <p><?php _e('Configura i tuoi tornei con formato, date e regole.', 'eto'); ?></p>
                            <a href="<?php echo admin_url('admin.php?page=eto-tournaments'); ?>" class="button"><?php _e('Gestisci Tornei', 'eto'); ?></a>
                        </div>
                    </li>
                    <li>
                        <span class="eto-dashboard-step-number">3</span>
                        <div class="eto-dashboard-step-content">
                            <h4><?php _e('Gestisci Match', 'eto'); ?></h4>
                            <p><?php _e('Registra i risultati dei match e aggiorna i bracket.', 'eto'); ?></p>
                            <a href="<?php echo admin_url('admin.php?page=eto-matches'); ?>" class="button"><?php _e('Gestisci Match', 'eto'); ?></a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.eto-dashboard-wrapper {
    margin-top: 20px;
}

.eto-dashboard-header {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 20px;
    border: 1px solid #e5e5e5;
}

.eto-dashboard-welcome h2 {
    margin-top: 0;
}

.eto-dashboard-main {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px;
}

.eto-dashboard-column {
    flex: 1;
    min-width: 300px;
    padding: 0 10px;
    margin-bottom: 20px;
}

.eto-dashboard-card {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    border: 1px solid #e5e5e5;
    height: 100%;
}

.eto-dashboard-card h3 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.eto-dashboard-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.eto-dashboard-list li {
    padding: 10px 0;
    border-bottom: 1px solid #f5f5f5;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.eto-dashboard-list li:last-child {
    border-bottom: none;
}

.eto-dashboard-meta {
    color: #777;
    font-size: 12px;
}

.eto-dashboard-footer {
    margin-top: 20px;
}

.eto-dashboard-steps {
    margin: 0;
    padding: 0;
    list-style: none;
}

.eto-dashboard-steps li {
    display: flex;
    margin-bottom: 20px;
}

.eto-dashboard-step-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background-color: #2271b1;
    color: #fff;
    border-radius: 50%;
    margin-right: 15px;
    font-weight: bold;
}

.eto-dashboard-step-content {
    flex: 1;
}

.eto-dashboard-step-content h4 {
    margin-top: 0;
    margin-bottom: 5px;
}
</style>
