# Contributing to Custovis - Service Suite

Danke für dein Interesse an Custovis! Dieses Dokument beschreibt, wie du
Beiträge leisten kannst.

## Entwicklungsumgebung

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

## Modul-Struktur

Neue Features werden nach Möglichkeit als eigenständiges Modul unter
`app/Modules/<Name>/` umgesetzt (siehe bestehende Platzhalter). Core
(`app/Core/`) darf nie von Modulen abhängen.

## Pull Requests

- Ein PR = eine logische Änderung.
- Commit-Präfixe: `feat:`, `fix:`, `refactor:`, `test:`, `docs:`.
- Tests für neue Funktionalität ergänzen.
- Keine `.env`, Secrets oder API-Keys committen.

## Code-Stil

- PSR-12, Laravel-Konventionen.
- Max. ~30 Zeilen pro Methode, max. 600 Zeilen pro Datei — sonst aufteilen.
- Kommentare nur für nicht-offensichtliches WHY, nie für das WHAT.
