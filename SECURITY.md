# Security Policy

## Unterstützte Versionen

Solange sich das Projekt in aktiver Entwicklung vor dem ersten stabilen
Release befindet, wird ausschließlich der `main`-Branch mit
Sicherheitsupdates versorgt.

## Sicherheitslücke melden

Bitte melde Sicherheitslücken **nicht** über öffentliche GitHub-Issues.
Nutze stattdessen das vertrauliche Meldeformular von GitHub:
[Sicherheitslücke melden](https://github.com/coprea71/custovis/security/advisories/new)
(Reiter *Security* → *Report a vulnerability*).

Die Meldung ist nur für dich und den Projektbetreuer sichtbar. Bitte beschreibe
betroffene Version, Schritte zum Nachstellen und mögliche Auswirkungen. Nach
Behebung wird die Lücke als GitHub Security Advisory veröffentlicht.

## Grundsätze

- Keine sensitiven Daten (Passwörter, Tokens, personenbezogene Daten) in
  Logs oder API-Responses.
- Mutierende Endpunkte erfordern Authentifizierung/Autorisierung.
- Nutzer-/Auth-Identität wird nie aus dem Request-Body übernommen.
- Entwicklung erfolgt ISO-27001-konform, siehe interne Projektregeln.
