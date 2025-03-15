<?php
/**
 * Template per la creazione di un nuovo team
 *
 * @package ETO
 * @since 2.5.3
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

// Variabili disponibili:
// $errors - Array di errori di validazione
// $success - Booleano che indica se la creazione è avvenuta con successo
// $form_data - Dati del form (in caso di errore)
?>

<div class="eto-create-team-container">
    <h2><?php _e('Crea un nuovo team', 'eto'); ?></h2>
    
    <?php if ($success): ?>
        <div class="eto-message eto-message-success">
            <p><?php _e('Team creato con successo!', 'eto'); ?></p>
            <p>
                <a href="<?php echo esc_url(get_permalink(get_option('eto_profile_page'))); ?>" class="button eto-button-primary">
                    <?php _e('Torna al profilo', 'eto'); ?>
                </a>
            </p>
        </div>
    <?php else: ?>
        <?php if (!empty($errors['general'])): ?>
            <div class="eto-message eto-message-error">
                <p><?php echo esc_html($errors['general']); ?></p>
            </div>
        <?php endif; ?>
        
        <form id="eto-create-team-form" method="post" action="">
            <?php wp_nonce_field('eto_create_team', 'eto_team_nonce'); ?>
            <input type="hidden" name="eto_create_team_submit" value="1">
            
            <div class="eto-form-row">
                <label for="team_name"><?php _e('Nome del team', 'eto'); ?> <span class="required">*</span></label>
                <input type="text" id="team_name" name="team_name" value="<?php echo isset($form_data['team_name']) ? esc_attr($form_data['team_name']) : ''; ?>" required>
                <?php if (!empty($errors['team_name'])): ?>
                    <span class="eto-form-error"><?php echo esc_html($errors['team_name']); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="eto-form-row">
                <label for="team_description"><?php _e('Descrizione', 'eto'); ?></label>
                <textarea id="team_description" name="team_description" rows="4"><?php echo isset($form_data['team_description']) ? esc_textarea($form_data['team_description']) : ''; ?></textarea>
                <?php if (!empty($errors['team_description'])): ?>
                    <span class="eto-form-error"><?php echo esc_html($errors['team_description']); ?></span>
                <?php endif; ?>
                <span class="eto-form-help"><?php _e('Breve descrizione del team (max 500 caratteri)', 'eto'); ?></span>
            </div>
            
            <div class="eto-form-row">
                <label for="team_tag"><?php _e('Tag del team', 'eto'); ?></label>
                <input type="text" id="team_tag" name="team_tag" value="<?php echo isset($form_data['team_tag']) ? esc_attr($form_data['team_tag']) : ''; ?>" maxlength="5">
                <?php if (!empty($errors['team_tag'])): ?>
                    <span class="eto-form-error"><?php echo esc_html($errors['team_tag']); ?></span>
                <?php endif; ?>
                <span class="eto-form-help"><?php _e('Tag breve per il team (max 5 caratteri)', 'eto'); ?></span>
            </div>
            
            <div class="eto-form-row">
                <label for="team_game"><?php _e('Gioco principale', 'eto'); ?></label>
                <select id="team_game" name="team_game">
                    <option value=""><?php _e('Seleziona un gioco', 'eto'); ?></option>
                    <option value="lol" <?php selected(isset($form_data['team_game']) && $form_data['team_game'] == 'lol'); ?>><?php _e('League of Legends', 'eto'); ?></option>
                    <option value="valorant" <?php selected(isset($form_data['team_game']) && $form_data['team_game'] == 'valorant'); ?>><?php _e('Valorant', 'eto'); ?></option>
                    <option value="csgo" <?php selected(isset($form_data['team_game']) && $form_data['team_game'] == 'csgo'); ?>><?php _e('CS:GO', 'eto'); ?></option>
                    <option value="dota2" <?php selected(isset($form_data['team_game']) && $form_data['team_game'] == 'dota2'); ?>><?php _e('Dota 2', 'eto'); ?></option>
                    <option value="other" <?php selected(isset($form_data['team_game']) && $form_data['team_game'] == 'other'); ?>><?php _e('Altro', 'eto'); ?></option>
                </select>
                <?php if (!empty($errors['team_game'])): ?>
                    <span class="eto-form-error"><?php echo esc_html($errors['team_game']); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="eto-form-row eto-form-submit">
                <button type="submit" class="button eto-button-primary"><?php _e('Crea team', 'eto'); ?></button>
                <a href="<?php echo esc_url(get_permalink(get_option('eto_profile_page'))); ?>" class="button eto-button-secondary"><?php _e('Annulla', 'eto'); ?></a>
            </div>
        </form>
        
        <div class="eto-create-team-info">
            <h3><?php _e('Informazioni importanti', 'eto'); ?></h3>
            <ul>
                <li><?php _e('Creando un team, diventerai automaticamente il capitano.', 'eto'); ?></li>
                <li><?php _e('Il capitano può invitare nuovi membri, rimuovere membri esistenti e iscrivere il team ai tornei.', 'eto'); ?></li>
                <li><?php _e('Puoi essere membro di più team contemporaneamente, ma ogni team può partecipare a un solo torneo alla volta.', 'eto'); ?></li>
                <li><?php _e('Il nome del team deve essere unico e non può essere modificato dopo la creazione.', 'eto'); ?></li>
            </ul>
        </div>
    <?php endif; ?>
</div>
