# Custovis – Service Suite

**Custovis ist eine quelloffene ITSM- und Kundenservice-Suite (AGPLv3) auf Basis von
Laravel 12.** Sie vereint Shared-Mailbox-Ticketing (wie FreeScout), Self-Service und
KI-Funktionen (wie Zammad) sowie Incident-, Problem-, Change- und Request-Management
mit CMDB (wie iTop/GLPI) in einer Anwendung. Alle Funktionen sind kostenlos, es gibt
keine Paywall und keine Editionen.

## 100 % Open Source, keine Paywall

Die meisten vergleichbaren Tools verkaufen Kanäle, KI, Kundenportal oder
Außendienst als Zusatzmodule. Custovis enthält all das von Anfang an:

- unbegrenzt viele Agenten, Tickets und Mailboxen
- KI-Funktionen über selbst konfigurierte API-Keys, ohne Nachkauf-Kosten beim Hersteller
- Kundenportal, WhatsApp-Anbindung, externe Ticket-API, GitHub-/GitLab-Issue-Import
- Techniker-Einsatzplanung mit Offline-App und MCP-Schnittstelle für KI-Telefonassistenten

## Funktionen

| Bereich | Funktionen |
|---|---|
| **Ticket-Kern** | IMAP-Import je Mailbox, Antworten per SMTP, interne Notizen, Textbausteine, Tags, Collision Detection in Echtzeit |
| **Kanäle** | E-Mail, WhatsApp Business (Meta Cloud API), REST-API mit teamverwalteten API-Keys, GitHub-/GitLab-Issues, Telefon über MCP |
| **ITIL** | Incident, Problem, Change mit CAB-Freigabe, Service Requests aus dem Service-Katalog, SLA-Engine, minimale CMDB |
| **KI-Layer** | Austauschbare Provider (OpenAI, Anthropic, Ollama, eigener Endpunkt), Zusammenfassung, Antwortvorschlag, Triage, ähnliche Tickets, Budgets je Team, optionale PII-Schwärzung |
| **Dashboards** | Management-Dashboard über alle Teams, Team-Dashboards mit Auslastung und SLA-Verletzungen, stündliche Snapshots |
| **Wissensdatenbank** | Kategorien-Baum, versionierte Markdown-Artikel mit Diff und Rollback, Volltextsuche, Feedback, Verknüpfung mit Tickets |
| **Team-Chat** | Team-, Firmen- und Ticket-Kanäle, Direktnachrichten, Ungelesen-Zähler, Echtzeit über Laravel Reverb |
| **Kundenportal** | Eigene Tickets mit Status-Timeline, Anfragen aus dem Service-Katalog, öffentliche Hilfe-Artikel |
| **Außendienst** | Technikerverwaltung (Skills, Schichten, Abwesenheiten), Dispatcher-Board mit Drag & Drop und Zuweisungsvorschlag, Routing über selbst gehostetes OSRM/Nominatim, Offline-PWA mit Checklisten, Unterschrift und Lieferschein-PDF |
| **ERP-Anbindung** | Kundendaten aus Odoo oder Shopware 6 live in der Ticket-Sidebar, ohne Datenimport |
| **Themes** | Wählbare, nachträglich erweiterbare Farb-Themes für alle Bereiche |
| **Compliance** | 2FA-Pflicht, Audit-Log, verschlüsselte Zugangsdaten, DSGVO-Auskunft und -Anonymisierung, Aufbewahrungsfristen ([Details](docs/compliance/README.md)) |

## Tech-Stack

- Laravel 12, PHP 8.2+, MySQL/MariaDB
- Auth: Laravel Fortify und Sanctum mit getrennten Guards (`web`, `customer`, `sanctum`), TOTP-2FA
- Rechte: `spatie/laravel-permission` und ein eigenes Team-Modell
- Frontend: Livewire, Blade und Alpine.js, Tailwind über Vite (kein CDN, kein Filament)
- Echtzeit: Laravel Reverb; Queues: Datenbank-Treiber (cron-tauglich), Redis optional
- MCP: offizielles `laravel/mcp` (Streamable HTTP)

## Installation

Auf den Zielservern besteht in der Regel kein SSH-Zugriff. Deshalb gibt es zwei Wege:

### 1. Web-Installer (FTP/SFTP)

1. Das Release-Archiv `custovis-vX.Y.Z.zip` von der
   [Releases-Seite](../../releases) laden. Es enthält `vendor/` und die gebauten
   Frontend-Assets.
2. Das Archiv per FTP/SFTP hochladen und den Webserver auf das Verzeichnis `public/`
   zeigen lassen.
3. `https://ihre-domain/install` aufrufen, Datenbank-Zugang und ersten Administrator
   eintragen, optional Demo-Daten anlegen.
4. Danach ist der Installer dauerhaft gesperrt (`storage/installed.lock`). Er öffnet
   sich auch nie auf einem System, das bereits Benutzer hat.

### 2. Kommandozeile

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env        # Datenbank-Zugang eintragen
php artisan key:generate
php artisan custovis:install  # optional: --demo
```

### Nach der Installation

- **Cronjob:** einmal pro Minute `php artisan schedule:run` (Mailabruf, SLA-Prüfung,
  Dashboards, Aufbewahrungsfristen).
- **Queue-Worker:** `php artisan queue:work --queue=default,mail-fetch,mail-send,git-sync,ai-processing,whatsapp,notifications,field-sync`.
  Ohne dauerhaften Prozess geht auch ein Cronjob mit `queue:work --stop-when-empty`.
- **Zwei-Faktor-Authentifizierung** ist für Agenten und Admins in Produktion Pflicht.
  Sie wird beim ersten Login eingerichtet.
- **Updates:** neue Version per FTP hochladen, dann unter **Administration → System**
  die ausstehenden Migrationen ausführen.

### Lokale Entwicklung

```bash
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan db:seed --class=DemoSeeder   # Demo-Konten, Passwort: demo-passwort
php artisan test
```

## Weitere Dokumentation

- [MCP-Anbindung für KI-Telefonassistenten](docs/mcp-integration.md)
- [Compliance-Übersicht (DSGVO/ISO 27001)](docs/compliance/README.md)
- [ERP-Anbindung und Auftragsverarbeitung](docs/compliance/erp-integration.md)
- [Architektur und Planhistorie](docs/planhub/0.md)
- [Änderungsprotokoll](CHANGELOG.md)

## Lizenz

[AGPLv3](LICENSE): schließt die SaaS-Lücke und schützt den Open-Source-Charakter
dauerhaft.

## Sicherheit

Siehe [SECURITY.md](SECURITY.md).

## Contributing

Siehe [CONTRIBUTING.md](CONTRIBUTING.md).
