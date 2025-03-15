<?php
/**
 * Struttura di directory per il plugin ETO
 * 
 * Questo file contiene la struttura di directory raccomandata per il plugin ETO
 * e le istruzioni per la migrazione dei file esistenti.
 * 
 * @package ETO
 * @since 2.5.3
 */

/**
 * Struttura di directory raccomandata:
 * 
 * /ETO
 * ├── admin/                     # File relativi all'area amministrativa
 * │   ├── controllers/           # Controller per l'area admin
 * │   ├── views/                 # Template per l'area admin
 * │   ├── class-admin-controller.php
 * │   └── ...
 * ├── includes/                  # File core del plugin
 * │   ├── api/                   # API REST
 * │   ├── models/                # Modelli dati
 * │   ├── class-database-manager.php
 * │   ├── class-checkin-unified.php  # Classe unificata per il check-in
 * │   └── ...
 * ├── public/                    # File relativi al frontend
 * │   ├── css/                   # Fogli di stile
 * │   ├── js/                    # Script JavaScript
 * │   ├── class-public-controller.php
 * │   ├── class-shortcodes.php
 * │   └── ...
 * ├── templates/                 # Template per il frontend
 * │   ├── frontend/              # Template organizzati per categoria
 * │   │   ├── tournaments/       # Template per i tornei
 * │   │   ├── teams/             # Template per i team
 * │   │   ├── matches/           # Template per le partite
 * │   │   └── users/             # Template per gli utenti
 * │   └── ...                    # Template legacy (da migrare)
 * ├── languages/                 # File di traduzione
 * ├── logs/                      # Directory per i log
 * ├── uploads/                   # Directory per gli upload
 * ├── keys/                      # Directory per le chiavi API (da migrare al database)
 * ├── esports-tournament-organizer.php  # File principale del plugin
 * └── uninstall.php              # Script di disinstallazione
 * 
 * Migrazioni da effettuare:
 * 
 * 1. Rimuovere i file duplicati:
 *    - Rimuovere includes/class-checkin.php (sostituito da includes/class-checkin-unified.php)
 *    - Rimuovere public/class-checkin.php (sostituito da includes/class-checkin-unified.php)
 * 
 * 2. Aggiornare i riferimenti nel file principale:
 *    - Aggiornare esports-tournament-organizer.php per utilizzare la classe unificata
 * 
 * 3. Migrare i template:
 *    - Spostare i template esistenti nella nuova struttura standardizzata
 * 
 * 4. Migrare le chiavi API:
 *    - Implementare la migrazione delle chiavi API dal filesystem al database
 */
