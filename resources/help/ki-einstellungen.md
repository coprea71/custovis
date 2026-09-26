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

## Gut zu wissen

- Ein leeres API-Key-Feld behält beim Speichern den bisher hinterlegten Key. Keys werden verschlüsselt gespeichert und nie wieder angezeigt.
- Den Verbrauch des laufenden Monats sehen Sie im **Team-Dashboard** unter **KI-Nutzung (Monat)**.
- Für maximale Datensparsamkeit eignet sich ein selbst gehostetes Ollama.
