# Custovis – Open-Source Service Suite for Helpdesk, ITSM and Field Service

[![CI](https://github.com/coprea71/custovis/actions/workflows/ci.yml/badge.svg)](https://github.com/coprea71/custovis/actions/workflows/ci.yml)
[![Release](https://img.shields.io/github/v/release/coprea71/custovis)](https://github.com/coprea71/custovis/releases)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](LICENSE)

**English** | [Deutsch](README.md)

**Custovis is an open-source ITSM and customer service suite (AGPLv3) built on
Laravel 12.** It combines shared-mailbox ticketing (like FreeScout), self-service and
AI features (like Zammad), incident, problem, change and request management with a
CMDB (like iTop/GLPI) and technician dispatching in a single application. Every
feature is free: no paywall, no editions. Custovis also runs on plain shared hosting
without SSH access: installation and updates work via FTP/SFTP and a web installer.

![Custovis in action: replying to a ticket and assigning a job to a technician via drag & drop](.github/screenshots/demo.gif)

> **Note:** The user interface is currently available in German only.

## Who is Custovis for?

- **Customer service teams** handling emails, WhatsApp messages and phone calls as
  tickets in shared mailboxes.
- **IT departments and service providers** working along ITIL with incidents,
  problems, changes, service requests, SLAs and a CMDB.
- **Service companies with field technicians** who schedule on-site jobs and
  document them offline.
- **Organisations with data protection requirements** that need a self-hosted
  solution with 2FA, audit log and GDPR tooling.

## 100 % open source, no paywall

Most comparable tools sell channels, AI, customer portal or field service as add-on
modules. Custovis includes all of it from day one:

- unlimited agents, tickets and mailboxes
- AI features using your own API keys, with no extra charges from the vendor
- customer portal, WhatsApp integration, external ticket API, GitHub/GitLab issue import
- technician dispatching with an offline app and an MCP interface for AI phone assistants

## Features at a glance

| Area | Features |
|---|---|
| **Ticket core** | IMAP import per mailbox, replies via SMTP through the team's mailbox, email layout and signature per team, internal notes, canned responses, tags, spam filter per team, real-time collision detection |
| **Channels** | Email, WhatsApp Business (Meta Cloud API), REST API with team-managed API keys, GitHub/GitLab issues, phone via MCP |
| **ITIL** | Incident, problem, change with CAB approval, service requests from the service catalogue, SLA engine with business hours and customer SLAs, CMDB |
| **AI layer** | Swappable providers (OpenAI, Anthropic, Ollama, custom endpoint), summaries, reply suggestions, triage, similar tickets, budgets per team, optional PII redaction |
| **Dashboards** | Management dashboard across all teams, team dashboards with workload and SLA breaches, hourly snapshots |
| **Knowledge base** | Category tree, versioned Markdown articles with diff and rollback, full-text search, feedback, links to tickets |
| **Team chat** | Team, company and ticket channels, direct messages, unread counters, real time via Laravel Reverb |
| **Customer portal** | Own tickets with status timeline, requests from the service catalogue, public help articles, password reset |
| **Field service** | Technician management (skills, shifts, absences), dispatcher board with drag & drop and assignment suggestions, routing via self-hosted OSRM/Nominatim, offline PWA with checklists, signature and delivery note PDF |
| **ERP integration** | Customer data from Odoo or Shopware 6 live in the ticket sidebar, without importing data |
| **Time tracking & invoicing** | Book time on tickets (timer), single and collective invoices, e-invoices as ZUGFeRD PDF and XRechnung XML, GoBD-compliant numbering and cancellation |
| **Administration** | Users, teams, roles and permissions, customer management, module management per role/user, themes, schema updates without a shell |
| **Compliance** | Mandatory 2FA, passkeys, audit log, encrypted credentials, GDPR data export and anonymisation, retention periods |

## Screenshots

<table>
  <tr>
    <td width="50%"><img src=".github/screenshots/dispatch.webp" alt="Dispatcher board with map and drag & drop"><br><sub>Dispatcher board with map and drag & drop</sub></td>
    <td width="50%"><img src=".github/screenshots/management-dashboard.webp" alt="Management dashboard across all teams"><br><sub>Management dashboard across all teams</sub></td>
  </tr>
  <tr>
    <td width="50%"><img src=".github/screenshots/knowledge-base.webp" alt="Knowledge base"><br><sub>Knowledge base</sub></td>
    <td width="50%"><img src=".github/screenshots/portal.webp" alt="Customer portal"><br><sub>Customer portal</sub></td>
  </tr>
  <tr>
    <td colspan="2" align="center"><img src=".github/screenshots/field-app.webp" alt="Offline-capable technician app" width="260"><br><sub>Offline-capable technician app</sub></td>
  </tr>
</table>

## Feature details

### Ticketing and agent workspace

Custovis polls any number of mailboxes via IMAP and turns every new email into a
ticket. Replies are sent via SMTP through the ticket's mailbox and appear in the same
thread. The workspace at `/agent` shows the ticket list and the ticket side by side:

- public reply or internal note, canned responses per team, tags
- email layout per team: team admins set colour, font, logo, a signature with
  placeholders and a footer, with live preview; emails include a plain-text version
- customer contact details and internal notes via an info button in the sidebar;
  missing details can be added right there
- tickets from the same email address are automatically assigned to the same
  customer – across all channels, case-insensitive, and retroactively when the
  customer is created or their address changes
- change status, priority, team and assignee directly in the sidebar
- optional per team (switched on by the team admin): unassigned tickets are assigned
  automatically to the team member who opens them
- emails flagged as important or urgent (X-Priority, Importance) set the ticket
  priority automatically; a colour bar shows it in the list, emergencies are framed
  in red and marked with a warning icon
- create tickets manually for phone or walk-in requests
- spam filter: mark email tickets as spam with one click and block the sender address
  or the whole domain for the team; future emails go to the spam folder in the
  administration area, can be released from there and are deleted after 30 days
- filters such as "Assigned to me"; filter and sort settings are saved per user;
  agents only see tickets of their own teams
- collision detection: see in real time who is currently working on a ticket

### Channels and interfaces

- **WhatsApp Business** via the Meta Cloud API: one chat equals one ticket,
  including the 24-hour rule and approved templates.
- **REST API** (`POST /api/v1/tickets`) with API keys managed by each team.
- **GitHub/GitLab issues** are imported as tickets via webhook or periodic polling,
  including self-hosted GitLab instances.
- **MCP interface** (`POST /mcp`) for AI phone assistants: create and search tickets,
  query status, add call notes, search approved help articles.

### ITIL processes

Besides simple tickets, Custovis supports the ITIL types incident (impact and
urgency), problem (root cause), change (type, risk, time window) and service request.
Each type has a fixed lifecycle. Changes go through CAB approval with a dedicated
approval inbox. The SLA engine calculates deadlines within the team's business hours
and reports breaches; contractual SLAs of individual customers take precedence over
the team SLA. Service requests are created from a service catalogue, and
configuration items from the CMDB can be linked to tickets.

### AI assistance

AI features run with your own API keys at OpenAI, Anthropic, a local Ollama instance
or a custom endpoint. Custovis summarises tickets, suggests replies, routes new
tickets automatically and finds similar tickets. Provider and budget can be set per
team, and personal data can be redacted before it is sent.

### Knowledge base and customer portal

Help articles are written in Markdown, versioned (with diff and rollback) and marked
as internal or public. Agents insert articles directly into replies. Customers sign in
to the portal at `/portal`, see only their own tickets with status history, submit new
requests from the service catalogue and search the public help articles.

### Time tracking and invoicing

Agents book their time directly on the ticket, manually or with a start/stop timer,
and mark it as billable or internal. Team admins invoice the open time entries: as a
single invoice per customer or as a collective invoice for a period, with one line
item per ticket. Every invoice is an e-invoice according to EN 16931 and is emailed
to the customer as a ZUGFeRD PDF (PDF/A-3, EN 16931 profile) and as XRechnung 3.0 XML.

- consecutive invoice numbers per year; issued invoices are immutable, corrections
  are made via a cancellation invoice (GoBD)
- German small business regulation (§ 19 UStG), Leitweg-ID for public-sector clients
- keep track of open items, incoming payments and overdue invoices

### Team chat and dashboards

The internal chat offers team, company and ticket channels as well as direct messages
in real time. The management dashboard shows key figures across all teams; team
dashboards show workload, open tickets and SLA breaches of the own team.

### Field service and dispatching

Dispatchers plan jobs on a board with a map using drag & drop. An assignment engine
suggests suitable technicians by skill, team, distance and availability. Technicians
use the offline-capable web app at `/field`: tick off checklists, record materials
and deliveries, capture the customer's signature and attach a delivery note PDF to
the ticket. Data syncs automatically as soon as a connection is available again.

### Administration and operations

- Users, teams and roles with fine-grained permissions; new users set their own
  password via an invitation link.
- Customer management with phone, mobile, address, internal notes, portal access
  and invitation link.
- Module management: disable feature areas globally or enable them only for specific
  roles or users.
- Selectable and extensible colour themes.
- One installation can run under several domains (`APP_HOSTS`).
- Database updates with one click under **Administration → System**, without shell
  access.
- Built-in help at `/agent/help` with step-by-step guides for all features, filtered
  by permissions and enabled modules; customers find their own help at `/portal/help`.

### Security and privacy

Two-factor authentication is mandatory for agents and admins, passkeys are supported.
Security-relevant changes are recorded in the audit log, credentials for external
systems are stored encrypted. Server URLs of external systems must not point to
internal addresses (SSRF protection). For the GDPR there is data export and
anonymisation per customer as well as configurable retention periods.

## Tech stack

- Laravel 12, PHP 8.2+, MySQL/MariaDB
- Auth: Laravel Fortify and Sanctum with separate guards (`web`, `customer`, `sanctum`), TOTP 2FA
- Permissions: `spatie/laravel-permission` and a custom team model
- Frontend: Livewire, Blade and Alpine.js, Tailwind via Vite (no CDN, no Filament)
- Real time: Laravel Reverb, optional – without a Reverb server (e.g. shared hosting
  without a shell) team chat and collision detection update via polling; queues: database driver (cron-friendly), Redis optional
- MCP: official `laravel/mcp` (Streamable HTTP)
- E-invoicing: `horstoeko/zugferd` (XRechnung/ZUGFeRD), PDF via `barryvdh/laravel-dompdf`

## Installation

Target servers usually have no SSH access, so there are two ways to install:

### 1. Web installer (FTP/SFTP)

1. Download the release archive `custovis-vX.Y.Z.zip` from the
   [releases page](https://github.com/coprea71/custovis/releases). It contains `vendor/`
   and the built frontend assets.
2. Upload the archive via FTP/SFTP and point the web server to the `public/`
   directory. If your host does not allow this, the domain may also point to the
   project folder – the `.htaccess` in the project folder then forwards everything
   to `public/`.
3. Open `https://your-domain/install`, enter the database credentials and the first
   administrator, optionally create demo data.
4. Afterwards the installer is locked permanently (`storage/installed.lock`). It also
   never opens on a system that already has users.
5. To make the same installation available under additional domains, add them to
   `.env` as `APP_HOSTS=support.example.org,support.example.net` (`APP_URL` stays the
   main domain for canonical links, emails and the console). Other hosts are
   rejected. Passkeys technically only work on the domain they were created on.

### 2. Command line

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env        # enter database credentials
php artisan key:generate
php artisan custovis:install  # optional: --demo
```

### After installation

- **Cron job:** run `php artisan schedule:run` once per minute (mail polling, SLA
  checks, dashboards, retention periods).
- **Queue worker:** `php artisan queue:work --queue=default,mail-fetch,mail-send,git-sync,ai-processing,whatsapp,notifications,field-sync`.
  Without a long-running process, a cron job with `queue:work --stop-when-empty` works too.
- **Web cron only (no shell cron job):** under **Administration → System → Web-Cron**
  generate a secret URL and have it called every minute. It replaces both cron job
  and queue worker.
- **Timezone:** `APP_TIMEZONE` in `.env` (default `Europe/Berlin`). Business hours,
  SLA deadlines and appointments use this timezone.
- **Two-factor authentication** is mandatory for agents and admins in production.
  It is set up on first login.
- **Updates:** upload the new version via FTP, then run the pending migrations under
  **Administration → System**.

### Local development

```bash
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan db:seed --class=DemoSeeder   # demo accounts, password: demo-passwort
php artisan test
```

## Further documentation

- [Changelog](CHANGELOG.md) (German)

## License

Copyright (C) 2026 Dirk Eichner

Custovis is licensed under the [GNU Affero General Public License v3.0](LICENSE)
(`AGPL-3.0-only`). The AGPLv3 closes the SaaS loophole and permanently protects the
open-source nature of the project:

- You may use, modify and distribute Custovis free of charge – including commercially.
- Anyone who distributes a modified version **or makes it available to users over a
  network** (e.g. as a hosted service) must also make its complete source code
  available under the AGPLv3.
- The software is provided without any warranty.

Only the license text in the [LICENSE](LICENSE) file is legally binding.

## Contact

- Bugs and feature requests: [GitHub issues](https://github.com/coprea71/custovis/issues)
- Other enquiries: [support@fahrklar.net](mailto:support@fahrklar.net)

## Security

See [SECURITY.md](SECURITY.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).
