Issues aus einem GitHub- oder GitLab-Repository lassen sich automatisch als Tickets in Ihr Team übernehmen, z. B. Fehlermeldungen aus einem öffentlichen Projekt.

## Access Token besorgen

1. **GitHub:** Erstellen Sie unter **Settings → Developer settings → Personal access tokens** einen Token mit Lesezugriff auf die Issues des Repositorys.
2. **GitLab:** Erstellen Sie im Projekt unter **Einstellungen → Zugriffstoken** einen Token mit dem Bereich `read_api`.
3. Denken Sie sich ein **Webhook Secret** aus, eine lange zufällige Zeichenfolge.

## Verbindung anlegen

1. Klicken Sie unter Ihrem Team auf **Einstellungen** und dann auf **Git-Issues**.
2. Wählen Sie den **Anbieter**: GitHub oder GitLab.
3. Tragen Sie das **Repository** ein: bei GitHub `besitzer/repository`, bei GitLab den Projektpfad.
4. Tragen Sie **Access Token** und **Webhook Secret** ein.
5. Wählen Sie den **Sync-Modus**: **Webhook (empfohlen)** überträgt neue Issues sofort, **Polling** fragt alle 5 Minuten nach.
6. Klicken Sie auf **Verbindung anlegen**.

## Webhook im Repository einrichten (nur Modus „Webhook“)

1. Kopieren Sie die angezeigte **Webhook-URL** der neuen Verbindung.
2. **GitHub:** Öffnen Sie im Repository **Settings → Webhooks → Add webhook**. Tragen Sie die URL ein, wählen Sie Content type `application/json`, tragen Sie das Webhook Secret ein und wählen Sie die Ereignisse **Issues** und **Issue comments**.
3. **GitLab:** Öffnen Sie im Projekt **Einstellungen → Webhooks**. Tragen Sie die URL und das Secret als Token ein und aktivieren Sie **Issue-Ereignisse** und **Kommentare**.
4. Legen Sie ein Test-Issue an. Es erscheint kurz darauf als Ticket.

## Gut zu wissen

- Die Liste zeigt je Verbindung den Zeitpunkt des **letzten Syncs**. Steht dort lange „nie“, prüfen Sie Token, URL und Secret.
- **Widerrufen** beendet den Import dauerhaft. Bereits importierte Tickets bleiben erhalten.
