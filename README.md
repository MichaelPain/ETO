# Istruzioni per l'applicazione della patch

Questa patch risolve i seguenti problemi nel plugin ETO:

1. Funzione duplicata `eto_db_query_secure()`
2. Permessi errati per directory e file
3. Caricamento anticipato delle traduzioni

## Come applicare la patch

### Metodo 1: Applicazione manuale delle modifiche

1. Modifica il file `includes/class-db-query-secure.php`:
   - Trova la funzione `eto_db_query_secure()` alla fine del file (circa linea 756)
   - Commenta o rimuovi la funzione e aggiungi un commento esplicativo

2. Modifica il file `esports-tournament-organizer.php`:
   - Sposta il caricamento delle traduzioni dall'hook `plugins_loaded` all'hook `init`
   - Crea una funzione dedicata `eto_load_textdomain()`

3. Correggi i permessi:
   - Assicurati che la directory `/logs` esista con permessi 750
   - Assicurati che la directory `/uploads` esista con permessi 750
   - Imposta i permessi del file `config.php` a 600

### Metodo 2: Applicazione automatica tramite patch

1. Salva il file `eto_fixes.patch` nella directory principale del plugin
2. Esegui il comando: `git apply eto_fixes.patch`
3. Verifica che le modifiche siano state applicate correttamente
4. Correggi manualmente i permessi delle directory e dei file:
   ```
   chmod 750 logs uploads
   chmod 600 includes/config.php
   ```

## Verifica delle correzioni

Dopo aver applicato le modifiche, verifica che:

1. Il plugin si carichi senza errori
2. Non ci siano più messaggi di errore relativi alla funzione duplicata
3. Non ci siano più avvisi relativi ai permessi dei file
4. Non ci siano più avvisi relativi al caricamento anticipato delle traduzioni

Per maggiori dettagli sulle modifiche apportate, consulta il file CHANGELOG.md.
