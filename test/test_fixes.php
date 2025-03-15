<?php
/**
 * Script di verifica per le correzioni apportate al plugin ETO
 * 
 * Questo script verifica che tutte le correzioni siano state implementate correttamente
 * Non è un test funzionale completo, ma una verifica del codice
 */

echo "=== Test di verifica delle correzioni al plugin ETO ===\n\n";

// Verifica 1: Controllo della funzione duplicata
echo "1. Verifica della rimozione della funzione duplicata eto_db_query_secure()\n";
$utilities_file = file_get_contents('../includes/utilities.php');
$db_query_file = file_get_contents('../includes/class-db-query-secure.php');

$utilities_has_function = strpos($utilities_file, 'function eto_db_query_secure()') !== false;
$db_query_has_active_function = strpos($db_query_file, 'function eto_db_query_secure()') !== false && 
                               strpos($db_query_file, '// function eto_db_query_secure() rimossa') === false;

if ($utilities_has_function && !$db_query_has_active_function) {
    echo "   ✅ SUCCESSO: La funzione eto_db_query_secure() è presente solo in utilities.php\n";
} else {
    echo "   ❌ ERRORE: La funzione eto_db_query_secure() potrebbe ancora essere duplicata\n";
}

// Verifica 2: Controllo dei permessi delle directory
echo "\n2. Verifica dei permessi delle directory e dei file\n";

// Verifica directory logs
if (is_dir('../logs')) {
    $logs_perms = fileperms('../logs') & 0777;
    if ($logs_perms == 0750) {
        echo "   ✅ SUCCESSO: Directory logs esiste con permessi corretti (750)\n";
    } else {
        echo "   ❌ ERRORE: Directory logs ha permessi errati: " . decoct($logs_perms) . " invece di 750\n";
    }
} else {
    echo "   ❌ ERRORE: Directory logs non esiste\n";
}

// Verifica directory uploads
if (is_dir('../uploads')) {
    $uploads_perms = fileperms('../uploads') & 0777;
    if ($uploads_perms == 0750) {
        echo "   ✅ SUCCESSO: Directory uploads esiste con permessi corretti (750)\n";
    } else {
        echo "   ❌ ERRORE: Directory uploads ha permessi errati: " . decoct($uploads_perms) . " invece di 750\n";
    }
} else {
    echo "   ❌ ERRORE: Directory uploads non esiste\n";
}

// Verifica file config.php
if (file_exists('../includes/config.php')) {
    $config_perms = fileperms('../includes/config.php') & 0777;
    if ($config_perms == 0600) {
        echo "   ✅ SUCCESSO: File config.php esiste con permessi corretti (600)\n";
    } else {
        echo "   ❌ ERRORE: File config.php ha permessi errati: " . decoct($config_perms) . " invece di 600\n";
    }
} else {
    echo "   ❌ ERRORE: File config.php non esiste\n";
}

// Verifica 3: Controllo del caricamento delle traduzioni
echo "\n3. Verifica del caricamento delle traduzioni\n";
$main_file = file_get_contents('../esports-tournament-organizer.php');

$has_init_hook = strpos($main_file, "add_action('init', 'eto_load_textdomain')") !== false;
$has_textdomain_function = strpos($main_file, 'function eto_load_textdomain()') !== false;
$loads_textdomain = strpos($main_file, "load_plugin_textdomain('eto'") !== false;

if ($has_init_hook && $has_textdomain_function && $loads_textdomain) {
    echo "   ✅ SUCCESSO: Il caricamento delle traduzioni è stato spostato all'hook 'init'\n";
} else {
    echo "   ❌ ERRORE: Il caricamento delle traduzioni non è configurato correttamente\n";
}

// Riepilogo
echo "\n=== Riepilogo delle correzioni ===\n";
echo "1. Funzione duplicata: " . ($utilities_has_function && !$db_query_has_active_function ? "Corretto ✅" : "Non corretto ❌") . "\n";
echo "2. Permessi directory logs: " . ((is_dir('../logs') && (fileperms('../logs') & 0777) == 0750) ? "Corretto ✅" : "Non corretto ❌") . "\n";
echo "3. Permessi directory uploads: " . ((is_dir('../uploads') && (fileperms('../uploads') & 0777) == 0750) ? "Corretto ✅" : "Non corretto ❌") . "\n";
echo "4. Permessi file config.php: " . ((file_exists('../includes/config.php') && (fileperms('../includes/config.php') & 0777) == 0600) ? "Corretto ✅" : "Non corretto ❌") . "\n";
echo "5. Caricamento traduzioni: " . (($has_init_hook && $has_textdomain_function && $loads_textdomain) ? "Corretto ✅" : "Non corretto ❌") . "\n";
