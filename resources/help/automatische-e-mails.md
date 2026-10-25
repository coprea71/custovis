Unter **Automatische E-Mails** legen Administratoren und – sofern freigegeben – Team-Admins fest, welche Mails Kunden ohne Zutun eines Agenten erhalten und wie sie formuliert sind. Die Mails gelten für Tickets, die per E-Mail eingehen, und für Tickets aus der REST-API, sofern dem API-Key eine Mailbox zugeordnet ist. Sie nutzen das E-Mail-Layout des Teams und erscheinen im Ticketverlauf mit dem Hinweis „Automatische E-Mail“.

## Die drei Mails

- **Eingangsbestätigung:** geht an den Absender, sobald aus seiner E-Mail ein neues Ticket entsteht. Antworten auf ein bestehendes Ticket lösen keine weitere Bestätigung aus. Standardmäßig aktiv.
- **Problem behoben:** geht an den Kunden, sobald das Ticket auf „Geschlossen“ gesetzt wird. Standardmäßig aktiv.
- **Statuswechsel:** geht bei jedem Statuswechsel, aber nur an Kunden, bei denen in der Kundenverwaltung **Mail bei Statuswechsel** angehakt ist. Standardmäßig aus, sowohl im Team als auch beim Kunden.

## Texte anpassen

1. Klicken Sie in der Navigation unter Ihrem Team auf **Einstellungen** und dann auf **Automatische E-Mails**.
2. Schalten Sie jede Mail über das Häkchen ein oder aus.
3. Passen Sie den Text an. Diese Platzhalter werden beim Versand ersetzt:
   - `{requester_name}`: Name des Kunden
   - `{ticket_id}`: Ticketnummer
   - `{ticket_subject}`: Betreff des Tickets
   - `{ticket_status}`: aktueller Status des Tickets
   - `{team_name}`, `{mailbox_email}`, `{agent_name}`: wie im E-Mail-Layout
4. Klicken Sie auf **Speichern**. Mit **Standardtext** holen Sie den mitgelieferten Text zurück.

## Für einzelne Kunden oder Tickets abschalten

- **Je Kunde:** In der Kundenverwaltung lassen sich Eingangsbestätigung, „Problem behoben“ und Statuswechsel-Mail einzeln an- oder abhaken. Eingangsbestätigung und „Problem behoben“ sind dort standardmäßig angehakt, die Statuswechsel-Mail nicht.
- **Je Ticket:** In der Ticket-Seitenleiste unter **Bearbeitung** schaltet **Keine automatischen Mails zu diesem Ticket** die Mails „Problem behoben“ und Statuswechsel für dieses Ticket ab. Die Eingangsbestätigung ist dann bereits verschickt.

## HTTP-Fehlermails ohne automatische Antwort

Webanwendungen oder Monitoring-Dienste können ihre Fehlermeldungen direkt an eine Team-Mailbox schicken, damit daraus Tickets werden. Damit der Absender keine automatische Mail und keine Agenten-Antwort erhält, gibt es je Team den **Filter für HTTP-Fehlermails (4xx/5xx)**:

1. Öffnen Sie **Einstellungen → Automatische E-Mails** und haken Sie **Filter für HTTP-Fehlermails (4xx/5xx)** an.
2. Tragen Sie optional die Absender ein, von denen Fehlermails kommen – eine E-Mail-Adresse oder Domain pro Zeile. Eine Domain gilt auch für ihre Subdomains. Leer lassen heißt: alle Absender.
3. Klicken Sie auf **Speichern**.

Erkannt wird eine Mail am Betreff: Er muss einen HTTP-Statuscode 400–451 oder 500–511 mit HTTP-Bezug enthalten, z. B. „Error 500“, „HTTP 503“, „Fehler 404“ oder „502 Bad Gateway“. Eine bloße Zahl wie „Bestellung 404“ zählt nicht. Der Filter wirkt auf neu eingehende Mails, bereits bestehende Tickets bleiben unverändert.

Aus einer erkannten Mail entsteht ein normales Ticket mit dem Hinweis **HTTP-Fehlermail** und dem Statuscode. Zu diesem Ticket geht keine E-Mail an den Absender, weder automatisch noch als Antwort eines Agenten. Agenten können nur interne Notizen schreiben. Im Team- und Management-Dashboard zeigt die Kachel **HTTP-Fehlermails (30 Tage)** die Anzahl, aufgeteilt in 4xx und 5xx und offene Tickets, dazu eine Auswertung nach Statuscode. Ein Klick auf die Kachel öffnet die Ticketliste gefiltert auf diese Tickets.

## Gut zu wissen

- Nutzer mit dem Recht `team.auto_mails.manage` (z. B. Systemadministratoren) bearbeiten die automatischen E-Mails jedes Teams.
- Team-Admins sehen und bearbeiten die Seite ihres Teams nur, solange dort **Team-Admins dürfen diese Seite sehen und bearbeiten** eingeschaltet ist (Standard). Diesen Schalter sehen und ändern nur Nutzer mit dem Recht `team.auto_mails.manage`. Ausgeschaltet verschwindet die Seite für Team-Admins auch aus den Team-Einstellungen.
- Ist **Problem behoben** abgeschaltet, erhalten Kunden mit aktivierter Statusmail beim Schließen stattdessen die Statuswechsel-Mail.
- Absender wie `noreply@…` oder `mailer-daemon@…` erhalten keine Eingangsbestätigung. Jede automatische Mail ist außerdem als automatisch gekennzeichnet, damit Abwesenheitsnotizen des Kunden nicht darauf antworten.
