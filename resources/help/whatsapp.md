Custovis bindet WhatsApp über die offizielle **Meta WhatsApp Business Cloud API** an. Jeder WhatsApp-Chat wird zu einem Ticket, Antworten aus dem Ticket gehen per WhatsApp an den Kunden zurück.

## Voraussetzungen bei Meta schaffen

1. Legen Sie im Meta-Entwicklerportal eine App mit dem Produkt **WhatsApp** an und verbinden Sie Ihr WhatsApp-Business-Konto.
2. Notieren Sie **Phone Number ID**, **Business Account ID**, einen dauerhaften **Access Token** und das **App Secret** der App.
3. Denken Sie sich einen **Webhook Verify Token** aus, eine beliebige geheime Zeichenfolge.

## Verbindung in Custovis anlegen

1. Klicken Sie unter Ihrem Team auf **Einstellungen** und dann auf **WhatsApp**.
2. Tragen Sie **Anzeigename**, **Phone Number ID**, **Business Account ID**, **Access Token**, **Webhook Verify Token** und **App Secret** ein.
3. Klicken Sie auf **Verbindung anlegen**.
4. Klicken Sie beim neuen Konto auf **Verbindung testen**. Das Ergebnis erscheint oben auf der Seite.

## Webhook bei Meta eintragen

1. Öffnen Sie in Custovis unter Ihrem Team **Einstellungen → WhatsApp**.
2. Unter dem angelegten Konto steht die **Callback-URL** (endet auf `/webhooks/whatsapp/` und eine Nummer). Ein Klick darauf markiert sie vollständig. Kopieren Sie sie mit Strg+C bzw. Cmd+C.
3. Öffnen Sie in der Meta-App **WhatsApp → Konfiguration** und fügen Sie die URL als **Callback-URL** ein.
4. Tragen Sie denselben **Webhook Verify Token** wie in Custovis ein und klicken Sie auf **Bestätigen und speichern**. Meta prüft die Verbindung sofort; eine Fehlermeldung deutet auf eine falsch kopierte URL, einen abweichenden Verify Token oder ein ausgeschaltetes WhatsApp-Modul hin.
5. Abonnieren Sie das Webhook-Feld **messages**.
6. Schicken Sie eine Test-Nachricht an Ihre WhatsApp-Nummer. Nach kurzer Zeit erscheint sie als Ticket.

## Auf WhatsApp-Tickets antworten

1. Öffnen Sie das Ticket. Oben steht, ob das **24-Std.-Fenster offen** oder **abgelaufen** ist.
2. **Fenster offen:** Antworten Sie ganz normal über **Öffentliche Antwort** und **Senden**.
3. **Fenster abgelaufen:** WhatsApp erlaubt dann nur von Meta genehmigte Vorlagen. Wählen Sie unter **Genehmigte Vorlage wählen...** eine Vorlage und klicken Sie auf **Vorlage senden**.
4. Sobald der Kunde antwortet, öffnet sich das 24-Stunden-Fenster erneut.

## Gut zu wissen

- Mit der Schaltfläche **Aktiv** / **Inaktiv** pausieren Sie ein Konto, ohne es zu löschen.
- Zugangsdaten werden verschlüsselt gespeichert und nicht wieder angezeigt.
