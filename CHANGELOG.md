# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden in dieser Datei
dokumentiert.

Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

## [Unreleased]

### Hinzugefügt

- Dashboards: `dashboard_snapshots` (stündlich per
  `dashboards:refresh-snapshots` aktualisiert), Management-Dashboard
  unter `/admin/dashboard` (Permission `dashboard.management.view`,
  KPIs über alle Teams), Team-Dashboard unter
  `/agent/team/{team}/dashboard` (`TeamPolicy::viewDashboard`, nur
  eigenes Team) mit gemeinsamen Kachel-Komponenten.
- Theme-System: `themes`-Registry (`ThemeServiceProvider` scannt
  `resources/themes/<slug>/theme.json`, Whitelist-Validierung der
  CSS-Variablen, fehlerhafte Themes werden geloggt und übersprungen),
  Default-Theme „Musterlayout", gemeinsames Basis-Layout
  (`<x-layouts.app>`) mit `data-theme` für `/agent`, `/admin` und
  `/portal`, Admin-UI unter `/admin/settings/theme` (Permission
  `system.settings.manage`, Audit-Log je Wechsel), Key-Value-Tabelle
  `settings`.
- KI-Layer: austauschbare Provider-Architektur (`AiProviderInterface`,
  OpenAI/Anthropic/Ollama/Custom-Endpoint-Adapter), `ai_settings`
  (Provider je Team+Anwendungsfall, `.env`-Fallback, PII-Redaction-
  Option), Queue-Jobs in `ai-processing` (`AutoTriageJob`,
  `SummarizeTicketJob`, `SuggestReplyJob`, `FindSimilarTicketsJob` mit
  Kosinus-Ähnlichkeit über `ticket_embeddings`, keine Vektor-DB nötig),
  Kostenkontrolle (`ai_usage_logs`, `ai_budgets` je Team), Team-Admin-UI
  unter `/agent/team/{team}/settings/ai`.
- ITIL-Erweiterung: Typ-Erweiterungstabellen (`ticket_incidents`,
  `ticket_problems`, `ticket_changes`, `ticket_service_requests`) mit
  State-Machines (`spatie/laravel-model-states`), CAB-Workflow
  (`cab_approvals`, `ChangeApprovalService`, Regel: Einstimmigkeit),
  SLA-Engine (`sla_policies`, `business_hours`, `TicketObserver`
  berechnet Deadlines bei Ticket-Erstellung, `sla:check-breaches`
  alle 5 Minuten), Service-Katalog (`service_catalog_items`,
  Admin-UI, `ServiceCatalogService` erzeugt typisierte Tickets),
  minimale CMDB (`cmdb_configuration_items`, `cmdb_ci_relations`,
  `ticket_configuration_items`-Zuordnung).
- WhatsApp-Business-Anbindung (Meta Cloud API direkt, kein BSP):
  `whatsapp_accounts`/`whatsapp_templates`, Webhook mit Verify-Handshake
  und `X-Hub-Signature-256`-Prüfung, „ein Chat = ein Ticket"
  (Zuordnung über `whatsapp_account_id`+Telefonnummer, automatisches
  Reopen statt neuem Ticket), 24-Stunden-Regel (Freitext nur innerhalb
  des Fensters, danach Pflicht zu genehmigten Templates), Team-Admin-UI
  unter `/agent/team/{team}/settings/whatsapp` inkl. Verbindungstest.
- Externe Ticket-Anlage-API (`POST /api/v1/tickets`, Sanctum-Guard,
  Ability `tickets.create`, 60 Anfragen/Minute je Token), teamverwaltete
  API-Keys (`api_clients`) mit Team-Admin-UI unter
  `/agent/team/{team}/settings/api-keys` (System-Admin sieht nur lesend).
- GitHub-/GitLab-Issue-Import (`git_issue_connections`): Webhook-Endpunkte
  mit Signaturprüfung (HMAC-SHA256 bzw. Shared-Token) und
  Polling-Fallback (`SyncGitIssuesJob`, Queue `git-sync`, alle 5 Minuten),
  Dedup über `tickets.external_ref` bzw. `ticket_messages.message_id`,
  Team-Admin-UI unter `/agent/team/{team}/settings/git-issues`.
- `audit_logs` (polymorph) für API-Key- und Git-Issue-Connection-
  Erstellung/-Widerruf.
- Ticket-Kern: `tickets`/`ticket_messages`/`ticket_attachments`, Mailbox-
  Verwaltung (`mailboxes`, Admin-UI unter `/admin/mailboxes`), IMAP-Abruf
  (`FetchMailboxJob`, `webklex/laravel-imap`, minütlicher Scheduler je
  aktiver Mailbox), Mail-Versand (`MailSenderService`,
  `SendTicketReplyJob`), gemeinsamer `AttachmentService`.
- Agenten-Arbeitsbereich unter `/agent` (Livewire, Master-Detail-Split,
  öffentliche Antwort vs. interne Notiz, Textbausteine (`canned_responses`),
  Kunden-/Metadaten-Sidebar mit Tags, Farbpalette/Font aus der
  UI-Layoutvorlage übernommen), Login-View für den `web`-Guard.
- Collision Detection über Laravel Reverb (Presence-Channel je Ticket).
- Laravel-12-Projekt-Grundgerüst.
- Auth-Grundgerüst: Fortify + Sanctum, Guards `web`/`customer`/`sanctum`.
- Team-/Rechte-Grundgerüst (`teams`, `team_user`, `spatie/laravel-permission`).
- Modul-Registry (`modules`, `module_user`, `module_role`, `ModuleServiceProvider`)
  mit Platzhaltern für alle geplanten Module.
- Repo-Grundgerüst (README, CHANGELOG, LICENSE, `.env.example`).
