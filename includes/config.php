<?php
/**
 * Configurazione del plugin
 * 
 * Contiene le impostazioni di configurazione del plugin
 * 
 * @package ETO
 * @since 2.5.0
 */

// Impedisci l'accesso diretto
if (!defined('ABSPATH')) exit;

// Configurazione del database
define('ETO_DB_VERSION', '2.6.0');

// Configurazione dei percorsi
define('ETO_LOGS_DIR', ETO_PLUGIN_DIR . 'logs/');
define('ETO_UPLOADS_DIR', ETO_PLUGIN_DIR . 'uploads/');
define('ETO_KEYS_DIR', ETO_PLUGIN_DIR . 'keys/');

// Configurazione delle API
define('ETO_USE_RIOT_API', false);

// Configurazione dei formati di torneo
define('ETO_DEFAULT_FORMAT', 'single_elimination');
define('ETO_DEFAULT_GAME', 'lol');
define('ETO_MAX_TEAMS', 32);
define('ETO_MIN_TEAMS', 2);

// Configurazione dei permessi
define('ETO_ENABLE_THIRD_PLACE_MATCH', true);
define('ETO_ENABLE_INDIVIDUAL_TOURNAMENTS', true);
define('ETO_ENABLE_MATCH_SCREENSHOTS', true);

// Configurazione dei ruoli
define('ETO_ROLE_ADMIN', 'administrator');
define('ETO_ROLE_MANAGER', 'editor');
define('ETO_ROLE_PLAYER', 'subscriber');

// Configurazione dei messaggi di errore
define('ETO_ERROR_PERMISSION', __('Non hai i permessi per eseguire questa azione', 'eto'));
define('ETO_ERROR_NONCE', __('Errore di sicurezza', 'eto'));
define('ETO_ERROR_MISSING_PARAM', __('Parametri mancanti', 'eto'));
define('ETO_ERROR_NOT_FOUND', __('Elemento non trovato', 'eto'));
define('ETO_ERROR_ALREADY_EXISTS', __('Elemento già esistente', 'eto'));
define('ETO_ERROR_INVALID_DATA', __('Dati non validi', 'eto'));
define('ETO_ERROR_DATABASE', __('Errore del database', 'eto'));
define('ETO_ERROR_API', __('Errore API', 'eto'));
define('ETO_ERROR_FILE', __('Errore file', 'eto'));
define('ETO_ERROR_UPLOAD', __('Errore durante il caricamento del file', 'eto'));
define('ETO_ERROR_GENERIC', __('Si è verificato un errore', 'eto'));

// Configurazione dei messaggi di successo
define('ETO_SUCCESS_CREATE', __('Elemento creato con successo', 'eto'));
define('ETO_SUCCESS_UPDATE', __('Elemento aggiornato con successo', 'eto'));
define('ETO_SUCCESS_DELETE', __('Elemento eliminato con successo', 'eto'));
define('ETO_SUCCESS_REGISTER', __('Registrazione completata con successo', 'eto'));
define('ETO_SUCCESS_CHECKIN', __('Check-in completato con successo', 'eto'));
define('ETO_SUCCESS_UPLOAD', __('File caricato con successo', 'eto'));
define('ETO_SUCCESS_GENERIC', __('Operazione completata con successo', 'eto'));
