/**
 * Script per l'amministrazione del plugin ETO
 * 
 * @package ETO
 * @since 2.5.3
 */

jQuery(document).ready(function($) {
    // Definizione esplicita di ajaxurl se non è già definito
    if (typeof ajaxurl === 'undefined') {
        var ajaxurl = '/wp-admin/admin-ajax.php';
    }
    
    console.log('ETO Admin JS caricato');
    console.log('ajaxurl:', ajaxurl);
    
    // Gestione eliminazione torneo
    $('.delete-tournament').on('click', function(e) {
        e.preventDefault();
        
        var tournamentId = $(this).data('id');
        
        if (confirm(etoAdmin.i18n.confirmDelete)) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eto_delete_tournament',
                    id: tournamentId,
                    nonce: etoAdmin.nonce
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
                    alert('Si è verificato un errore durante l\'eliminazione del torneo.');
                }
            });
        }
    });
    
    // Gestione eliminazione team
    $('.delete-team').on('click', function(e) {
        e.preventDefault();
        
        var teamId = $(this).data('id');
        
        if (confirm(etoAdmin.i18n.confirmDelete)) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eto_delete_team',
                    id: teamId,
                    nonce: etoAdmin.nonce
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
                    alert('Si è verificato un errore durante l\'eliminazione del team.');
                }
            });
        }
    });
    
    // Gestione eliminazione partecipante
    $('.eto-delete-participant').on('click', function(e) {
        e.preventDefault();
        
        var participantId = $(this).data('id');
        
        if (confirm(etoAdmin.i18n.confirmDelete)) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eto_remove_participant',
                    id: participantId,
                    nonce: etoAdmin.nonce
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
                    alert('Si è verificato un errore durante l\'eliminazione del partecipante.');
                }
            });
        }
    });
    
    // Gestione form di aggiunta torneo
    if ($('#eto-add-tournament-form').length) {
        console.log('Form di aggiunta torneo trovato');
        
        // Gestione caricamento immagine
        $('#upload-image-button').on('click', function(e) {
            e.preventDefault();
            
            var custom_uploader = wp.media({
                title: 'Seleziona o carica un\'immagine',
                button: {
                    text: 'Usa questa immagine'
                },
                multiple: false
            });
            
            custom_uploader.on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                $('#tournament-featured-image').val(attachment.url);
                $('#featured-image-preview').html('<img src="' + attachment.url + '" style="max-width: 300px; margin-top: 10px;">');
            });
            
            custom_uploader.open();
        });
        
        // Validazione date
        function validateDates() {
            var startDate = $('#tournament-start-date').val();
            var endDate = $('#tournament-end-date').val();
            var regStart = $('#tournament-registration-start').val();
            var regEnd = $('#tournament-registration-end').val();
            
            var errors = [];
            
            if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                errors.push('La data di fine deve essere successiva alla data di inizio.');
            }
            
            if (regStart && regEnd && new Date(regStart) > new Date(regEnd)) {
                errors.push('La data di fine registrazione deve essere successiva alla data di inizio registrazione.');
            }
            
            if (regEnd && startDate && new Date(regEnd) > new Date(startDate)) {
                errors.push('La data di fine registrazione deve essere precedente alla data di inizio torneo.');
            }
            
            return errors;
        }
        
        // Validazione team
        function validateTeams() {
            var minTeams = parseInt($('#tournament-min-teams').val());
            var maxTeams = parseInt($('#tournament-max-teams').val());
            
            var errors = [];
            
            if (minTeams < 2) {
                errors.push('Il numero minimo di team deve essere almeno 2.');
            }
            
            if (maxTeams < minTeams) {
                errors.push('Il numero massimo di team deve essere maggiore o uguale al numero minimo.');
            }
            
            return errors;
        }
        
        // Gestione invio form
        $('#eto-add-tournament-form').on('submit', function(e) {
            e.preventDefault();
            
            console.log('Form di aggiunta torneo inviato');
            
            // Validazione
            var dateErrors = validateDates();
            var teamErrors = validateTeams();
            var errors = dateErrors.concat(teamErrors);
            
            if (errors.length > 0) {
                var errorHtml = '<div class="notice notice-error"><p><strong>Errori:</strong></p><ul>';
                
                $.each(errors, function(index, error) {
                    errorHtml += '<li>' + error + '</li>';
                });
                
                errorHtml += '</ul></div>';
                
                $('#eto-messages').html(errorHtml);
                $('html, body').animate({ scrollTop: 0 }, 'slow');
                return;
            }
            
            // Invio dati
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: $(this).serialize(),
                beforeSend: function() {
                    $('#eto-messages').html('<div class="notice notice-info"><p>Creazione del torneo in corso...</p></div>');
                },
                success: function(response) {
                    console.log('Risposta ricevuta:', response);
                    
                    if (response.success) {
                        $('#eto-messages').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        
                        // Reindirizza alla pagina dei tornei
                        setTimeout(function() {
                            window.location.href = '/wp-admin/admin.php?page=eto-tournaments';
                        }, 1000);
                    } else {
                        var errorHtml = '<div class="notice notice-error"><p>' + response.data.message + '</p>';
                        
                        if (response.data.errors) {
                            errorHtml += '<ul>';
                            
                            $.each(response.data.errors, function(field, error) {
                                errorHtml += '<li>' + error + '</li>';
                                $('#tournament-' + field).addClass('eto-field-error');
                            });
                            
                            errorHtml += '</ul>';
                        }
                        
                        errorHtml += '</div>';
                        
                        $('#eto-messages').html(errorHtml);
                        $('html, body').animate({ scrollTop: 0 }, 'slow');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Errore AJAX:', xhr, status, error);
                    $('#eto-messages').html('<div class="notice notice-error"><p>Si è verificato un errore durante l\'elaborazione della richiesta.</p></div>');
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                }
            });
        });
    }
    
    // Gestione form di aggiunta team
    if ($('#eto-add-team-form').length) {
        console.log('Form di aggiunta team trovato');
        
        // Gestione caricamento logo
        $('#upload-logo-button').on('click', function(e) {
            e.preventDefault();
            
            var custom_uploader = wp.media({
                title: 'Seleziona o carica un\'immagine',
                button: {
                    text: 'Usa questa immagine'
                },
                multiple: false
            });
            
            custom_uploader.on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                $('#team-logo-url').val(attachment.url);
                $('#logo-preview').html('<img src="' + attachment.url + '" style="max-width: 150px; margin-top: 10px;">');
            });
            
            custom_uploader.open();
        });
        
        // Gestione invio form
        $('#eto-add-team-form').on('submit', function(e) {
            e.preventDefault();
            
            console.log('Form di aggiunta team inviato');
            
            // Validazione
            var name = $('#team-name').val();
            var game = $('#team-game').val();
            var captain = $('#team-captain').val();
            
            var errors = [];
            
            if (!name) {
                errors.push('Il nome del team è obbligatorio.');
                $('#team-name').addClass('eto-field-error');
            } else {
                $('#team-name').removeClass('eto-field-error');
            }
            
            if (!game) {
                errors.push('Il gioco è obbligatorio.');
                $('#team-game').addClass('eto-field-error');
            } else {
                $('#team-game').removeClass('eto-field-error');
            }
            
            if (!captain) {
                errors.push('Il capitano è obbligatorio.');
                $('#team-captain').addClass('eto-field-error');
            } else {
                $('#team-captain').removeClass('eto-field-error');
            }
            
            if (errors.length > 0) {
                var errorHtml = '<div class="notice notice-error"><p><strong>Errori:</strong></p><ul>';
                
                $.each(errors, function(index, error) {
                    errorHtml += '<li>' + error + '</li>';
                });
                
                errorHtml += '</ul></div>';
                
                $('#eto-messages').html(errorHtml);
                $('html, body').animate({ scrollTop: 0 }, 'slow');
                return;
            }
            
            // Invio dati
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: $(this).serialize(),
                beforeSend: function() {
                    $('#eto-messages').html('<div class="notice notice-info"><p>Creazione del team in corso...</p></div>');
                },
                success: function(response) {
                    console.log('Risposta ricevuta:', response);
                    
                    if (response.success) {
                        $('#eto-messages').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        
                        // Reindirizza alla pagina dei team
                        setTimeout(function() {
                            window.location.href = '/wp-admin/admin.php?page=eto-teams';
                        }, 1000);
                    } else {
                        var errorHtml = '<div class="notice notice-error"><p>' + response.data.message + '</p>';
                        
                        if (response.data.errors) {
                            errorHtml += '<ul>';
                            
                            $.each(response.data.errors, function(field, error) {
                                errorHtml += '<li>' + error + '</li>';
                                $('#team-' + field).addClass('eto-field-error');
                            });
                            
                            errorHtml += '</ul>';
                        }
                        
                        errorHtml += '</div>';
                        
                        $('#eto-messages').html(errorHtml);
                        $('html, body').animate({ scrollTop: 0 }, 'slow');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Errore AJAX:', xhr, status, error);
                    $('#eto-messages').html('<div class="notice notice-error"><p>Si è verificato un errore durante l\'elaborazione della richiesta.</p></div>');
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                }
            });
        });
    }
    
    // Gestione form di aggiunta partecipante
    if ($('#eto-add-participant-form').length) {
        console.log('Form di aggiunta partecipante trovato');
        
        // Gestione invio form
        $('#eto-add-participant-form').on('submit', function(e) {
            e.preventDefault();
            
            console.log('Form di aggiunta partecipante inviato');
            
            // Validazione
            var name = $('#participant-name').val();
            var email = $('#participant-email').val();
            var team = $('#participant-team').val();
            
            var errors = [];
            
            if (!name) {
                errors.push('Il nome è obbligatorio.');
                $('#participant-name').addClass('eto-field-error');
            } else {
                $('#participant-name').removeClass('eto-field-error');
            }
            
            if (!email) {
                errors.push('L\'email è obbligatoria.');
                $('#participant-email').addClass('eto-field-error');
            } else {
                $('#participant-email').removeClass('eto-field-error');
            }
            
            if (!team) {
                errors.push('Il team è obbligatorio.');
                $('#participant-team').addClass('eto-field-error');
            } else {
                $('#participant-team').removeClass('eto-field-error');
            }
            
            if (errors.length > 0) {
                var errorHtml = '<div class="notice notice-error"><p><strong>Errori:</strong></p><ul>';
                
                $.each(errors, function(index, error) {
                    errorHtml += '<li>' + error + '</li>';
                });
                
                errorHtml += '</ul></div>';
                
                $('#eto-messages').html(errorHtml);
                $('html, body').animate({ scrollTop: 0 }, 'slow');
                return;
            }
            
            // Invio dati
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: $(this).serialize(),
                beforeSend: function() {
                    $('#eto-messages').html('<div class="notice notice-info"><p>Invio in corso...</p></div>');
                },
                success: function(response) {
                    console.log('Risposta ricevuta:', response);
                    
                    if (response.success) {
                        $('#eto-messages').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        
                        // Reindirizza alla lista dei partecipanti
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1000);
                        }
                    } else {
                        $('#eto-messages').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                    
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                },
                error: function(xhr, status, error) {
                    console.error('Errore AJAX:', xhr, status, error);
                    $('#eto-messages').html('<div class="notice notice-error"><p>Si è verificato un errore durante l\'invio dei dati.</p></div>');
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                }
            });
        });
    }
});
