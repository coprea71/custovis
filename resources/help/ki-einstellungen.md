Custovis nutzt KI über Ihre eigenen API-Keys. Es gibt keine versteckten Kosten beim Hersteller. Für jeden Anwendungsfall legen Sie fest, welcher Anbieter und welches Modell verwendet wird, ob personenbezogene Daten vorher geschwärzt werden und wie viel das Team im Monat ausgeben darf.

Die Anwendungsfälle:

- **summarize**: Zusammenfassung von Tickets
- **suggest_reply**: Antwortvorschläge
- **classify**: automatische Einordnung (Triage)
- **embed**: Vektoren für die Suche nach ähnlichen Tickets

## Anbieter für einen Anwendungsfall einrichten

1. Klicken Sie unter Ihrem Team auf **Einstellungen** und dann auf **KI**.
2. Oben sehen Sie je Anwendungsfall den aktuellen Stand. „nicht konfiguriert (.env-Fallback)“ bedeutet, dass die installationsweite Voreinstellung gilt.
3. Wählen Sie den **Anwendungsfall**.
4. Wählen Sie den **Provider**: OpenAI, Anthropic, Ollama (selbst gehostet) oder einen eigenen Endpunkt (custom).
5. Tragen Sie den **API-Key** ein. Bei Ollama oder einem eigenen Endpunkt zusätzlich die Adresse unter **Endpoint (Ollama/Custom)**.
6. Tragen Sie das **Modell** ein, z. B. den Modellnamen laut Dokumentation Ihres Anbieters.
7. Aktivieren Sie **PII vor Versand redigieren**, damit E-Mail-Adressen, Telefonnummern und IBANs vor dem Versand an den Anbieter geschwärzt werden. Namen im Freitext erkennt die Schwärzung nicht.
8. Klicken Sie auf **Speichern**.

## Monatsbudget festlegen

1. Tragen Sie unter **Monatliches Budget (€)** den Höchstbetrag für das Team ein.
2. Klicken Sie auf **Speichern**.
3. Ist das Budget eines Monats aufgebraucht, werden keine weiteren KI-Anfragen für das Team ausgeführt.

## Wann die KI arbeitet und wo die Ergebnisse erscheinen

Die KI arbeitet nur, wenn das Modul **AiAgent** (KI-Funktionen) aktiv ist, für den Anwendungsfall ein Anbieter eingerichtet ist (oder die installationsweite Voreinstellung greift) und das Monatsbudget nicht aufgebraucht ist. Die Anfragen laufen im Hintergrund. Das Ergebnis erscheint, sobald der Hintergrunddienst (Queue-Worker oder Web-Cron) gelaufen ist, meist nach ein bis zwei Minuten.

- **classify** (Triage): läuft automatisch bei jedem neuen Ticket. Die KI kann die Priorität nur anheben, nie senken. Einen **Notfall** vergibt sie nie.
- **embed** (ähnliche Tickets): läuft automatisch bei jedem neuen Ticket. Findet die KI ähnliche Tickets Ihres Teams, erscheint im Ticket die interne Notiz **KI: Ähnliche Tickets** mit „Ähnliche Tickets: #…“.
- **summarize**: auf Knopfdruck im Ticket über **Zusammenfassen**. Das Ergebnis erscheint als interne Notiz **KI-Zusammenfassung**.
- **suggest_reply**: auf Knopfdruck im Ticket über **Antwort vorschlagen**. Das Ergebnis erscheint als interne Notiz **KI-Antwortvorschlag**, die Sie in Ihre Antwort übernehmen können.

Spam-Tickets werden nie an die KI geschickt.

## Gut zu wissen

- Ein leeres API-Key-Feld behält beim Speichern den bisher hinterlegten Key. Keys werden verschlüsselt gespeichert und nie wieder angezeigt.
- Den Verbrauch des laufenden Monats sehen Sie im **Team-Dashboard** unter **KI-Nutzung (Monat)**.
- Für maximale Datensparsamkeit eignet sich ein selbst gehostetes Ollama.
