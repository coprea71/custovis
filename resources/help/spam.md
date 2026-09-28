Im Spam-Ordner landen Tickets, die Agenten als Spam markiert haben, und neue Mails von gesperrten Absendern. Sie tauchen nicht in Ticketlisten, Dashboards oder dem Kundenportal auf und lösen keine Benachrichtigungen aus.

## Spam prüfen und freigeben

1. Öffnen Sie **Administration → Spam**.
2. Mit **Vorschau** lesen Sie den Text der Mail. Aus Sicherheitsgründen wird nur reiner Text angezeigt, keine Bilder oder Links.
3. War die Mail doch kein Spam, klicken Sie auf **Freigeben**. Das Ticket erscheint wieder in der Ticketliste seines Teams.
4. Mit **Löschen** entfernen Sie ein Ticket sofort endgültig, samt Anhängen.

## Automatische Löschung

Spam wird 30 Tage nach dem Markieren bzw. Eingang automatisch gelöscht. Das Datum steht bei jedem Eintrag unter **wird gelöscht am**. Voraussetzung ist ein eingerichteter Web-Cron (siehe Hilfethema „System-Updates & Web-Cron“).

## Gesperrte Absender verwalten

Unter **Gesperrte Absender** sehen Sie alle Sperren mit Team, Urheber und Datum. Eine Domain-Sperre gilt auch für ihre Subdomains.

1. Klicken Sie bei einem Eintrag auf **Sperre aufheben** und bestätigen Sie.
2. Neue Mails dieses Absenders landen wieder normal in der Ticketliste. Bereits archivierter Spam bleibt im Ordner, bis Sie ihn freigeben oder er gelöscht wird.

## Gut zu wissen

- Gesperrt wird über die Schaltfläche **Spam** in einem E-Mail-Ticket (siehe Hilfethema „Tickets bearbeiten“).
- Eine Sperre gilt nur für das Team, in dem das Ticket markiert wurde.
- Freigegebene Tickets erhalten keine SLA-Fristen nachträglich.
