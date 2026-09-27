Mit dem Rechnungsmodul rechnen Team-Admins die Leistungen ihres Teams ab: erfasste Ticket-Zeiten und freie Positionen. Jede Rechnung wird als **E-Rechnung** erzeugt und per E-Mail in zwei Formaten verschickt: als **ZUGFeRD-PDF** (PDF/A-3 mit eingebetteten Rechnungsdaten, Profil EN 16931) und als **XRechnung-XML** (Version 3.0). Beide Formate erfüllen die E-Rechnungspflicht im B2B-Bereich.

## Vorbereitung (Administration)

1. Öffnen Sie **Administration → Rechnungen** und tragen Sie Firmendaten, Ansprechpartner, USt-IdNr. oder Steuernummer und Bankverbindung ein.
2. Legen Sie Steuersatz, Stundensatz, Zahlungsziel und das Präfix der Rechnungsnummer fest. Als Kleinunternehmer wählen Sie **Ja, keine Umsatzsteuer**.
3. Wählen Sie unter **Versand über Mailbox**, über welches Postfach die Rechnungen verschickt werden.
4. Hinterlegen Sie bei den Kunden unter **Administration → Kunden** die vollständige Anschrift. Bei Behörden gehört die **Leitweg-ID** in das Feld **Käuferreferenz**.

## Einzelne Rechnung erstellen

1. Klicken Sie in der Navigation unter Ihrem Team auf **Einstellungen** und dann auf **Rechnungen**.
2. Wählen Sie unter **Einzelne Rechnung** den Kunden und klicken Sie auf **Entwurf anlegen**. Offene abrechenbare Zeiten aus den Tickets des Teams werden als Positionen übernommen, eine Position je Ticket.
3. Ergänzen Sie bei Bedarf weitere Positionen, z. B. Anfahrt oder Material, und tragen Sie einen **Leistungszeitraum** ein.
4. Klicken Sie auf **Ausstellen**. Die Rechnung erhält eine fortlaufende Nummer und ist ab jetzt nicht mehr änderbar.
5. Klicken Sie auf **Per E-Mail senden**.

## Sammelrechnungen für einen Zeitraum

1. Wählen Sie unter **Sammelrechnungen für einen Zeitraum** den Zeitraum, z. B. den Vormonat (voreingestellt).
2. Klicken Sie auf **Sammelrechnungen anlegen**. Für jeden Kunden mit offenen Zeiten im Zeitraum entsteht ein Entwurf mit allen Tickets dieses Zeitraums. Der Zeitraum wird als Leistungszeitraum übernommen.
3. Prüfen Sie die Entwürfe unter dem Filter **Entwürfe** und stellen Sie sie einzeln aus.

## Rechnung korrigieren oder stornieren

1. Öffnen Sie die ausgestellte Rechnung und klicken Sie auf **Stornieren**.
2. Es entsteht eine Stornorechnung mit eigener Nummer, die sich auf die ursprüngliche Rechnung bezieht. Beide bleiben erhalten.
3. Die abgerechneten Zeiten werden wieder frei und lassen sich neu in Rechnung stellen.

## Gut zu wissen

- Rechnungen erstellen, ausstellen, versenden und stornieren nur **Team-Admins** des jeweiligen Teams. Nutzer mit dem Recht zur Rechnungsverwaltung sehen die Rechnungen zur Ansicht.
- Entwürfe können gelöscht werden, ausgestellte Rechnungen nicht (GoBD). Rechnungen werden unveränderbar gespeichert und bleiben auch bei einer DSGVO-Anonymisierung des Kunden erhalten, weil sie 8 Jahre aufbewahrt werden müssen.
- Mit **Als bezahlt markieren** halten Sie den Zahlungseingang fest. Der Filter **Unbezahlt** zeigt offene Posten, überfällige Rechnungen sind gekennzeichnet.
- Das Rechnungsmodul und die Zeiterfassung sind Module und müssen unter **Administration → Module** eingeschaltet sein.
