# Correzioni Plugin WordPress ETO

## Problemi risolti

### 1. Problema di reindirizzamento dopo la creazione di tornei e team

**Descrizione del problema:**
Quando si tentava di creare un nuovo torneo o un nuovo team, dopo aver premuto sul tasto "crea torneo" o "crea team", l'utente veniva reindirizzato a una pagina inesistente sul sito ("ops, questa pagina non esiste").

**Causa:**
Il problema era causato da due fattori principali:
1. Il percorso relativo utilizzato nei form per puntare al file `eto-process.php`
2. L'utilizzo di `admin_url()` nel file `eto-process.php` per il reindirizzamento dopo la creazione

**Soluzione implementata:**
1. Creato un nuovo file `eto-process-fixed.php` con le seguenti modifiche:
   - Sostituito `admin_url()` con `site_url()` per garantire percorsi assoluti corretti nei reindirizzamenti
   - Migliorata la gestione degli errori

2. Aggiornati i form di creazione nei file:
   - `/admin/views/tournaments/add.php`
   - `/admin/views/teams/add.php`
   - Modificato l'attributo `action` dei form per utilizzare percorsi assoluti con `site_url()`

### 2. Errore nelle impostazioni

**Descrizione del problema:**
Quando si tentava di salvare le impostazioni, veniva visualizzato il messaggio di errore: "Error: The eto_settings options page is not in the allowed options list."

**Causa:**
Il file delle impostazioni era mancante o non registrava correttamente le opzioni nella whitelist di WordPress.

**Soluzione implementata:**
1. Creato il file `/admin/views/settings.php` mancante
2. Implementata la corretta registrazione delle opzioni con:
   - `register_setting('eto_settings', ...)` per ogni opzione
   - `add_option('eto_settings')` per aggiungere le opzioni alla whitelist
   - Aggiunta la gestione delle pagine per tornei e team nelle impostazioni

## Istruzioni per l'implementazione

Per implementare le correzioni, seguire questi passaggi:

1. **Sostituire il file di processo:**
   - Rinominare il file `eto-process-fixed.php` in `eto-process.php` (o mantenere entrambi e aggiornare i riferimenti nei form)

2. **Aggiornare i file dei form:**
   - Utilizzare le versioni aggiornate di:
     - `/admin/views/tournaments/add.php`
     - `/admin/views/teams/add.php`

3. **Aggiungere il file delle impostazioni:**
   - Utilizzare il nuovo file `/admin/views/settings.php`

4. **Verificare le impostazioni in WordPress:**
   - Accedere alla pagina delle impostazioni del plugin
   - Configurare le pagine per tornei e team
   - Salvare le impostazioni

## Note aggiuntive

- Le modifiche sono state progettate per essere minimamente invasive e mantenere la compatibilità con il resto del plugin
- È consigliabile testare le modifiche in un ambiente di sviluppo prima di applicarle in produzione
- Se si riscontrano ulteriori problemi, verificare i permessi dei file e assicurarsi che WordPress possa accedere e modificare i file del plugin
