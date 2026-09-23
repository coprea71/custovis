# Custovis - Service Suite

Ein ITIL-konformes, quelloffenes ITSM-/Ticketsystem, das Shared-Mailbox-
Ticketing (wie FreeScout), Self-Service & KI-Add-ons (wie Zammad) und
Incident-/Problem-/Change-/Request-Management inkl. CMDB (wie iTop/GLPI)
vereint.

## 100 % Open Source – keine Paywall

Anders als die meisten vergleichbaren Tools bietet Custovis **alle**
Funktionen von Anfang an frei und modular an: unbegrenzte Agenten, Tickets
und Mailboxen, KI-Funktionen über selbst konfigurierte API-Keys (keine
Nachkauf-Kosten beim Hersteller), Self-Service-Portal, WhatsApp-Anbindung,
externe Ticket-API inkl. GitHub-/GitLab-Issue-Import — keine Zusatzkosten,
keine künstlich beschränkten Editionen.

## Tech-Stack

- Laravel 12, PHP 8.2+, MySQL/MariaDB
- Auth: Laravel Fortify + Sanctum, getrennte Guards (`web`, `customer`, `sanctum`)
- Rechte: `spatie/laravel-permission` + eigenes Team-Modell
- Frontend: Livewire/Volt + Blade + Alpine.js (kein Filament)
- Echtzeit: Laravel Reverb
- Mailbox: `webklex/laravel-imap`

Details zur Architektur: [docs/planhub/0.md](docs/planhub/0.md).

## Installation

Zwei Wege, da auf Zielservern in der Regel kein SSH-Zugriff besteht:

1. **Composer-basiert** (Server mit Shell-Zugriff):
   ```bash
   composer install
   php artisan custovis:install
   ```
2. **Web-Installer** unter `/install` für reine FTP/SFTP-Ziele: geführter
   Browser-Wizard (DB-Test, Migrationen, Admin-Anlage), deaktiviert sich
   nach Abschluss selbst (`storage/installed.lock`).

Lokale Entwicklung:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

## Module

Custovis ist modular aufgebaut (`app/Modules/`). Jedes Modul kann
unabhängig aktiviert/deaktiviert werden, ohne den Systemstart zu
beeinflussen (`ModuleServiceProvider`, Tabelle `modules`).

## Lizenz

[AGPLv3](LICENSE) — schließt die SaaS-Lücke und schützt den
Open-Source-Charakter dauerhaft.

## Sicherheit

Siehe [SECURITY.md](SECURITY.md).

## Contributing

Siehe [CONTRIBUTING.md](CONTRIBUTING.md).
