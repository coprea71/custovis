Custovis läuft auch auf einfachem Webhosting ohne SSH. Updates spielen Sie per FTP/SFTP ein und führen die Datenbank-Anpassungen im Browser aus. Wo kein Cronjob möglich ist, übernimmt der Web-Cron die Hintergrundaufgaben.

## Update einspielen

1. Legen Sie ein **Backup** der Datenbank und der Dateien an, z. B. über das Kundenmenü Ihres Hosters.
2. Laden Sie die neue Version per FTP/SFTP hoch. Überschreiben Sie dabei **nicht** die Datei `.env` und den Ordner `storage`.
3. Öffnen Sie **Administration → System**. Unter **Datenbank-Updates** sehen Sie die installierte Version und die ausstehenden Migrationen.
4. Klicken Sie auf **… Migration(en) ausführen** und bestätigen Sie.
5. Prüfen Sie die Ausgabe. Danach steht dort „Die Datenbank ist auf dem aktuellen Stand.“

## Web-Cron einrichten

Ohne Cronjob ruft Custovis keine E-Mails ab und erledigt keine Hintergrundaufgaben (SLA-Prüfung, Dashboards, Aufbewahrungsfristen …).

1. Klicken Sie unter **Web-Cron** auf **URL erzeugen**.
2. Kopieren Sie die angezeigte URL sofort. Sie wird nur dieses eine Mal angezeigt.
3. Richten Sie beim Hoster oder bei einem Web-Cron-Dienst einen Aufruf dieser URL **jede Minute** ein.
4. Laden Sie die Seite nach ein bis zwei Minuten neu. Unter **Letzter Aufruf** sollte jetzt eine aktuelle Zeit stehen.

## URL erneuern

1. Klicken Sie auf **Neue URL erzeugen** und bestätigen Sie. Die bisherige URL funktioniert danach nicht mehr.
2. Tragen Sie die neue URL beim Web-Cron-Dienst ein.

## Gut zu wissen

- Behandeln Sie die Web-Cron-URL wie ein Passwort. Erneuern Sie sie, falls sie in falsche Hände geraten ist.
- Steht dort „noch nie aufgerufen“, erreicht der Web-Cron-Dienst die URL nicht. Prüfen Sie die Einrichtung beim Dienst.
