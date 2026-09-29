Custovis nutzt KI über Ihre eigenen API-Keys. Es gibt keine versteckten Kosten beim Hersteller. Für jeden Anwendungsfall legen Sie fest, welcher Anbieter und welches Modell verwendet wird, ob personenbezogene Daten vorher geschwärzt werden und wie viel das Team im Monat ausgeben darf.

## Was die KI für Sie erledigt

| Anwendungsfall | Was passiert | Wann | Ergebnis im Ticket |
|---|---|---|---|
| **classify** | Einordnung (Triage) der Dringlichkeit | automatisch bei jedem neuen Ticket | höhere **Priorität**, falls nötig |
| **embed** | Suche nach ähnlichen Tickets Ihres Teams | automatisch bei jedem neuen Ticket | interne Notiz **KI: Ähnliche Tickets** mit „Ähnliche Tickets: #…“ |
| **summarize** | Zusammenfassung des Verlaufs | auf Knopfdruck über **Zusammenfassen** | interne Notiz **KI-Zusammenfassung** |
| **suggest_reply** | Antwortvorschlag an den Kunden | auf Knopfdruck über **Antwort vorschlagen** | interne Notiz **KI-Antwortvorschlag** |

Sie müssen nicht alle Anwendungsfälle einrichten. Was nicht eingerichtet ist, bleibt einfach aus.

## Schritt 1: Modul einschalten (Administration)

Dieser Schritt ist nur einmal nötig und braucht die Berechtigung, Module zu verwalten.

1. Öffnen Sie **Administration → Module**.
2. Suchen Sie das Modul **AiAgent** (Beschreibung „KI-Funktionen mit eigenen API-Keys“).
3. Steht dort **Inaktiv**, klicken Sie darauf. Die Schaltfläche wechselt auf **Aktiv**.
4. Optional: Über **Zugang** geben Sie die KI nur bestimmten Rollen oder Personen frei (siehe Hilfethema „Module“).

## Schritt 2: Hintergrunddienst prüfen

Alle KI-Anfragen laufen im Hintergrund. Ohne laufenden Hintergrunddienst erscheinen keine Ergebnisse.

1. Öffnen Sie **Administration → System**.
2. Prüfen Sie unter **Web-Cron** bei **Letzter Aufruf**, ob dort eine aktuelle Zeit steht.
3. Fehlt sie, richten Sie den Web-Cron ein (siehe Hilfethema „System“). Er sollte **jede Minute** aufgerufen werden. Bei einem selteneren Aufruf erscheinen die KI-Ergebnisse entsprechend später.

Läuft auf Ihrem Server ein eigener Queue-Worker oder Cronjob, ist nichts weiter zu tun.

## Schritt 3: Anbieter für einen Anwendungsfall einrichten

1. Klicken Sie unter Ihrem Team auf **Einstellungen** und dann auf **KI**.
2. Oben sehen Sie je Anwendungsfall den aktuellen Stand. „nicht konfiguriert (.env-Fallback)“ bedeutet, dass die installationsweite Voreinstellung gilt. Ist dort kein Key hinterlegt, ist dieser Anwendungsfall aus.
3. Wählen Sie den **Anwendungsfall**, z. B. **summarize**.
4. Wählen Sie den **Provider**: OpenAI, Anthropic, Ollama (selbst gehostet) oder einen eigenen Endpunkt (custom).
5. Tragen Sie den **API-Key** ein. Bei Ollama oder einem eigenen Endpunkt zusätzlich die Adresse unter **Endpoint (Ollama/Custom)**.
6. Tragen Sie das **Modell** ein, z. B. den Modellnamen laut Dokumentation Ihres Anbieters.
7. Aktivieren Sie **PII vor Versand redigieren**, damit E-Mail-Adressen, Telefonnummern und IBANs vor dem Versand an den Anbieter geschwärzt werden. Namen im Freitext erkennt die Schwärzung nicht.
8. Klicken Sie auf **Speichern**. Oben erscheint jetzt beim Anwendungsfall der gewählte Anbieter.
9. Wiederholen Sie die Schritte 3 bis 8 für jeden weiteren Anwendungsfall, den Sie nutzen möchten. Denselben Anbieter und Key können Sie für alle Anwendungsfälle verwenden.

Hinweise zu **embed** (ähnliche Tickets):

- **Anthropic** bietet diese Funktion nicht an. Wählen Sie für **embed** einen anderen Anbieter.
- **OpenAI** nutzt dafür automatisch das Modell `text-embedding-3-small`, unabhängig vom eingetragenen Modell.
- **Ollama** nutzt dafür automatisch `nomic-embed-text`. Laden Sie es auf Ihrem Ollama-Server vorher mit `ollama pull nomic-embed-text`.
- Bei einem **eigenen Endpunkt** tragen Sie unter **Modell** ein Embedding-Modell ein.

## Schritt 4: Monatsbudget festlegen (empfohlen)

1. Tragen Sie im Abschnitt **Monatliches Budget (€)** den Höchstbetrag für das Team ein.
2. Klicken Sie auf das **Speichern** direkt darunter.
3. Ist das Budget eines Monats aufgebraucht, werden keine weiteren KI-Anfragen für das Team ausgeführt. Im Ticket verschwinden dann die KI-Schaltflächen. Am Monatsersten geht es automatisch weiter.

Ohne Budget ist die Nutzung nicht begrenzt.

## Schritt 5: Einrichtung testen

1. Öffnen Sie ein Ticket mit mindestens einer Kundennachricht.
2. In der Seitenleiste erscheint der Abschnitt **KI-Assistent** mit **Zusammenfassen** und/oder **Antwort vorschlagen**.
3. Klicken Sie auf **Zusammenfassen**. Darunter erscheint „In Arbeit – das Ergebnis erscheint in Kürze als interne Notiz.“
4. Warten Sie ein bis zwei Minuten. Der Ticketverlauf aktualisiert sich alle 30 Sekunden von selbst. Dann erscheint die interne Notiz **KI-Zusammenfassung**.
5. Für die automatischen Funktionen schicken Sie eine Test-Mail an eine Ihrer Mailboxen. Beim neuen Ticket erscheint nach kurzer Zeit gegebenenfalls eine höhere Priorität und, falls es ähnliche Tickets gibt, die Notiz **KI: Ähnliche Tickets**.
6. Den Verbrauch sehen Sie im **Team-Dashboard** unter **KI-Nutzung (Monat)**.

## Wenn nichts passiert

- **Kein Abschnitt KI-Assistent im Ticket:** Das Modul **AiAgent** ist aus oder für Sie nicht freigegeben (Schritt 1).
- **Hinweis „Kein KI-Provider eingerichtet oder Monatsbudget aufgebraucht“:** Richten Sie **summarize** bzw. **suggest_reply** ein (Schritt 3) oder erhöhen Sie das Budget (Schritt 4).
- **„In Arbeit“ erscheint, aber nie eine Notiz:** Der Hintergrunddienst läuft nicht (Schritt 2), oder API-Key bzw. Modell sind falsch. Prüfen Sie Key und Modellname beim Anbieter.
- **Keine Notiz „Ähnliche Tickets“:** Das ist normal, solange es noch keine ähnlichen älteren Tickets in Ihrem Team gibt. Die KI vergleicht nur Tickets, die nach der Einrichtung von **embed** eingegangen sind.

## Gut zu wissen

- Die Triage hebt die Priorität nur an, sie senkt sie nie. Einen **Notfall** vergibt die KI nie, den legt immer ein Mensch fest.
- Die KI schickt selbst nie etwas an Kunden. Alle Ergebnisse sind interne Notizen, die Kunden nicht sehen.
- Spam-Tickets werden nie an die KI geschickt.
- Ähnliche Tickets werden nur innerhalb Ihres Teams gesucht.
- Ein leeres API-Key-Feld behält beim Speichern den bisher hinterlegten Key. Keys werden verschlüsselt gespeichert und nie wieder angezeigt.
- Für maximale Datensparsamkeit eignet sich ein selbst gehostetes Ollama.
