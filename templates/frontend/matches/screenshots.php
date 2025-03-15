<?php
/**
 * Template per la visualizzazione degli screenshot dei match nel frontend
 *
 * @package ETO
 * @since 2.6.0
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

// Variabili disponibili:
// $match_id - ID del match
// $match - Oggetto match
// $screenshots - Array di oggetti screenshot
// $user_id - ID dell'utente corrente
// $is_admin - True se l'utente è un amministratore
// $is_team1_captain - True se l'utente è il capitano del team 1
// $is_team2_captain - True se l'utente è il capitano del team 2
// $team1_id - ID del team 1
// $team2_id - ID del team 2

$can_upload = $is_team1_captain || $is_team2_captain;
$user_team_id = $is_team1_captain ? $team1_id : ($is_team2_captain ? $team2_id : 0);
$upload_dir = wp_upload_dir();
?>

<div class="eto-match-screenshots">
    <h3><?php _e('Screenshot dei Risultati', 'eto'); ?></h3>
    
    <?php if ($can_upload): ?>
    <div class="eto-screenshot-upload">
        <h4><?php _e('Carica Screenshot', 'eto'); ?></h4>
        <p><?php _e('Carica uno screenshot del risultato della partita. Formati supportati: JPG, PNG, GIF.', 'eto'); ?></p>
        
        <form class="eto-screenshot-form" enctype="multipart/form-data">
            <input type="hidden" name="match_id" value="<?php echo esc_attr($match_id); ?>">
            <input type="hidden" name="team_id" value="<?php echo esc_attr($user_team_id); ?>">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('eto-screenshots-nonce'); ?>">
            
            <div class="eto-form-row">
                <label for="screenshot"><?php _e('Seleziona un\'immagine:', 'eto'); ?></label>
                <input type="file" name="screenshot" id="screenshot" accept="image/jpeg,image/png,image/gif" required>
            </div>
            
            <div class="eto-form-row">
                <button type="submit" class="button eto-upload-button"><?php _e('Carica Screenshot', 'eto'); ?></button>
                <span class="eto-upload-status"></span>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="eto-screenshots-list">
        <h4><?php _e('Screenshot Caricati', 'eto'); ?></h4>
        
        <?php if (empty($screenshots)): ?>
            <p><?php _e('Nessuno screenshot caricato.', 'eto'); ?></p>
        <?php else: ?>
            <div class="eto-screenshots-grid">
                <?php foreach ($screenshots as $screenshot): 
                    $file_url = $upload_dir['baseurl'] . '/eto/' . $screenshot->file_path;
                    $team = new ETO_Team_Model($screenshot->team_id);
                    $team_name = $team ? $team->get('name') : __('Team sconosciuto', 'eto');
                    $user = get_userdata($screenshot->user_id);
                    $user_name = $user ? $user->display_name : __('Utente sconosciuto', 'eto');
                    
                    // Determina se l'utente può validare questo screenshot
                    $can_validate = false;
                    $is_opponent = false;
                    
                    if ($is_admin && $screenshot->validated_by_admin == 0) {
                        $can_validate = true;
                    } elseif ($screenshot->team_id == $team1_id && $is_team2_captain && $screenshot->validated_by_opponent == 0) {
                        $can_validate = true;
                        $is_opponent = true;
                    } elseif ($screenshot->team_id == $team2_id && $is_team1_captain && $screenshot->validated_by_opponent == 0) {
                        $can_validate = true;
                        $is_opponent = true;
                    }
                ?>
                <div class="eto-screenshot-item" data-id="<?php echo esc_attr($screenshot->id); ?>">
                    <div class="eto-screenshot-image">
                        <a href="<?php echo esc_url($file_url); ?>" target="_blank">
                            <img src="<?php echo esc_url($file_url); ?>" alt="<?php echo esc_attr(sprintf(__('Screenshot caricato da %s', 'eto'), $team_name)); ?>">
                        </a>
                    </div>
                    
                    <div class="eto-screenshot-info">
                        <p><strong><?php _e('Team:', 'eto'); ?></strong> <?php echo esc_html($team_name); ?></p>
                        <p><strong><?php _e('Caricato da:', 'eto'); ?></strong> <?php echo esc_html($user_name); ?></p>
                        <p><strong><?php _e('Data:', 'eto'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($screenshot->uploaded_at)); ?></p>
                        <p><strong><?php _e('Stato:', 'eto'); ?></strong> 
                            <?php 
                            switch ($screenshot->status) {
                                case 'pending':
                                    echo '<span class="eto-status-pending">' . __('In attesa di convalida', 'eto') . '</span>';
                                    break;
                                case 'validated':
                                    echo '<span class="eto-status-validated">' . __('Convalidato', 'eto') . '</span>';
                                    break;
                                case 'rejected':
                                    echo '<span class="eto-status-rejected">' . __('Rifiutato', 'eto') . '</span>';
                                    break;
                                default:
                                    echo esc_html($screenshot->status);
                            }
                            ?>
                        </p>
                        
                        <?php if ($screenshot->status == 'rejected' && !empty($screenshot->validation_notes)): ?>
                        <p><strong><?php _e('Motivo del rifiuto:', 'eto'); ?></strong> <?php echo esc_html($screenshot->validation_notes); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($can_validate && $screenshot->status == 'pending'): ?>
                        <div class="eto-screenshot-actions">
                            <button class="button eto-validate-button" data-id="<?php echo esc_attr($screenshot->id); ?>" data-nonce="<?php echo wp_create_nonce('eto-screenshots-nonce'); ?>">
                                <?php _e('Convalida', 'eto'); ?>
                            </button>
                            
                            <button class="button eto-reject-button" data-id="<?php echo esc_attr($screenshot->id); ?>" data-nonce="<?php echo wp_create_nonce('eto-screenshots-nonce'); ?>">
                                <?php _e('Rifiuta', 'eto'); ?>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Gestione del form di upload
    $('.eto-screenshot-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'eto_upload_match_screenshot');
        
        var statusElement = $(this).find('.eto-upload-status');
        statusElement.text('<?php _e('Caricamento in corso...', 'eto'); ?>');
        
        $.ajax({
            url: etoScreenshots.ajaxurl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    statusElement.text(response.data.message);
                    // Ricarica la pagina per mostrare il nuovo screenshot
                    location.reload();
                } else {
                    statusElement.text(response.data.message);
                }
            },
            error: function() {
                statusElement.text('<?php _e('Errore durante il caricamento', 'eto'); ?>');
            }
        });
    });
    
    // Gestione della convalida degli screenshot
    $('.eto-validate-button').on('click', function() {
        var button = $(this);
        var screenshotId = button.data('id');
        var nonce = button.data('nonce');
        
        if (confirm(etoScreenshots.i18n.confirmValidate)) {
            $.ajax({
                url: etoScreenshots.ajaxurl,
                type: 'POST',
                data: {
                    action: 'eto_validate_match_screenshot',
                    screenshot_id: screenshotId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('<?php _e('Errore durante la convalida', 'eto'); ?>');
                }
            });
        }
    });
    
    // Gestione del rifiuto degli screenshot
    $('.eto-reject-button').on('click', function() {
        var button = $(this);
        var screenshotId = button.data('id');
        var nonce = button.data('nonce');
        
        var reason = prompt(etoScreenshots.i18n.confirmReject);
        if (reason !== null) {
            $.ajax({
                url: etoScreenshots.ajaxurl,
                type: 'POST',
                data: {
                    action: 'eto_reject_match_screenshot',
                    screenshot_id: screenshotId,
                    reason: reason,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('<?php _e('Errore durante il rifiuto', 'eto'); ?>');
                }
            });
        }
    });
});
</script>

<style>
.eto-match-screenshots {
    margin: 20px 0;
}

.eto-screenshot-upload {
    margin-bottom: 30px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.eto-form-row {
    margin-bottom: 15px;
}

.eto-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.eto-upload-status {
    margin-left: 10px;
    font-style: italic;
}

.eto-screenshots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.eto-screenshot-item {
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.eto-screenshot-image {
    height: 200px;
    overflow: hidden;
}

.eto-screenshot-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.eto-screenshot-info {
    padding: 15px;
}

.eto-screenshot-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.eto-status-pending {
    color: #856404;
    background-color: #fff3cd;
    padding: 2px 6px;
    border-radius: 3px;
}

.eto-status-validated {
    color: #155724;
    background-color: #d4edda;
    padding: 2px 6px;
    border-radius: 3px;
}

.eto-status-rejected {
    color: #721c24;
    background-color: #f8d7da;
    padding: 2px 6px;
    border-radius: 3px;
}
</style>
