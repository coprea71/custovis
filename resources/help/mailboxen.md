Jede Mailbox ist ein E-Mail-Postfach, das Custovis per IMAP abruft. Neue E-Mails werden zu Tickets des zugeordneten Teams, Antworten gehen per SMTP über dasselbe Postfach hinaus.

## Mailbox anlegen

1. Öffnen Sie **Administration → Mailboxen**.
2. Wählen Sie im Formular **Neue Mailbox** das **Team**.
3. Tragen Sie **Name** und **E-Mail-Adresse** ein.
4. Tragen Sie die IMAP-Daten Ihres Mail-Anbieters ein: **IMAP-Host**, **Port** (meist 993), **Verschlüsselung** (meist SSL), **IMAP-Benutzername** und **IMAP-Passwort**.
5. Tragen Sie die SMTP-Daten ein: **SMTP-Host**, **Port** (meist 465 mit SSL oder 587 mit TLS), **Verschlüsselung**, **SMTP-Benutzername** und **SMTP-Passwort**.
6. Klicken Sie auf **Speichern**.

## Abruf testen

1. Klicken Sie bei der Mailbox auf **Abruf testen**.
2. Das Ergebnis erscheint direkt unter der Mailbox. Bei einem Fehler prüfen Sie Host, Port, Verschlüsselung und Zugangsdaten.
3. Senden Sie eine Test-E-Mail an das Postfach. Nach dem nächsten Abruf erscheint sie als Ticket.

## Mailbox ändern oder pausieren

1. Klicken Sie bei der Mailbox auf **Bearbeiten**. Lassen Sie die Passwortfelder leer, um die gespeicherten Passwörter zu behalten.
2. Mit **Aktiv** / **Inaktiv** schalten Sie den Abruf ein oder aus, ohne die Mailbox zu löschen.

## Gut zu wissen

- Die Liste zeigt, wann zuletzt abgerufen wurde. Ein Warnhinweis mit **Abruffehler** bedeutet, dass derzeit keine E-Mails ankommen.
- Der Abruf läuft über den Cronjob oder den Web-Cron. Ohne einen der beiden werden keine E-Mails abgeholt (siehe Hilfethema „System-Updates & Web-Cron“).
- Zugangsdaten werden verschlüsselt gespeichert. Verwenden Sie bei Anbietern mit Zwei-Faktor-Anmeldung ein App-Passwort.
