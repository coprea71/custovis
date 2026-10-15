Unter **Administration → Datensicherung** sichern Sie die komplette Datenbank als verschlüsselte ZIP-Datei – ohne Shell-Zugriff. Hochgeladene Dateianhänge (Ordner `storage`) sind nicht enthalten; sichern Sie diese zusätzlich über Ihren Hoster.

## Sicherung erstellen und herunterladen

1. Klicken Sie auf **Sicherung erstellen**.
2. Laden Sie **ZIP-Datei** und **Passwort-Datei** herunter. Die ZIP-Datei ist mit AES-256 verschlüsselt, das Passwort steht nur in der Textdatei.
3. Bewahren Sie beide Dateien **getrennt** auf (z. B. ZIP im Archiv, Passwort im Passwort-Manager).
4. Klicken Sie auf **Vom Server löschen** – die Sicherung enthält personenbezogene Daten.

Außerhalb von Custovis öffnen Sie die ZIP-Datei z. B. mit 7-Zip; der Windows-Explorer kann AES-verschlüsselte ZIP-Dateien nicht entpacken.

## Erinnerung

Liegt die letzte heruntergeladene oder automatische Sicherung mehr als 7 Tage zurück, sehen Administratoren oben auf jeder Seite den Hinweis **Datensicherung fällig**.

## Automatische Sicherung

Custovis kann einmal täglich per Cronjob oder Web-Cron eine Sicherung in ein Verzeichnis auf dem Server schreiben.

1. Setzen Sie den Haken **Täglich automatisch sichern**.
2. Tragen Sie ein **Verzeichnis** als absoluten Pfad ein, z. B. `/home/www/backups/custovis`. Leer bedeutet das Standardverzeichnis unter `storage`. Verzeichnisse im öffentlichen Ordner `public` sind nicht erlaubt.
3. Legen Sie fest, wie viele Sicherungen aufbewahrt werden. Ältere werden automatisch gelöscht.
4. Klicken Sie auf **Speichern**.

ZIP- und Passwort-Datei liegen dabei im selben Verzeichnis. Kopieren Sie die Sicherungen regelmäßig vom Server weg, sonst gehen sie bei einem Serverausfall mit verloren. Schlägt eine Sicherung fehl, zeigt die Seite die Fehlermeldung; der nächste Versuch folgt nach 24 Stunden.

## Sicherung zurückspielen

1. Erstellen Sie vorher eine aktuelle Sicherung.
2. Wählen Sie unter **Sicherung zurückspielen** die ZIP-Datei und die passende Passwort-Datei aus.
3. Bestätigen Sie, dass die aktuelle Datenbank überschrieben wird, und klicken Sie auf **Sicherung zurückspielen**.
4. Melden Sie sich danach neu an. Stammt die Sicherung von einer älteren Version, führen Sie anschließend unter **Administration → System** die ausstehenden Migrationen aus.
