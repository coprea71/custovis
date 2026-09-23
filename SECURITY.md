# Security Policy

## Unterstützte Versionen

Solange sich das Projekt in aktiver Entwicklung vor dem ersten stabilen
Release befindet, wird ausschließlich der `main`-Branch mit
Sicherheitsupdates versorgt.

## Sicherheitslücke melden

Bitte melde Sicherheitslücken **nicht** über öffentliche GitHub-Issues.
Kontaktiere stattdessen die Projektbetreuer direkt (Kontaktadresse folgt
mit der Veröffentlichung, siehe [12.md](docs/planhub/12.md)).

## Grundsätze

- Keine sensitiven Daten (Passwörter, Tokens, personenbezogene Daten) in
  Logs oder API-Responses.
- Mutierende Endpunkte erfordern Authentifizierung/Autorisierung.
- Nutzer-/Auth-Identität wird nie aus dem Request-Body übernommen.
- Entwicklung erfolgt ISO-27001-konform, siehe interne Projektregeln.
