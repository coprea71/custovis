# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden in dieser Datei
dokumentiert.

Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

## [Unreleased]

### Hinzugefügt

- ERP-Kundendaten-Anbindung (read-only): Agenten mit `erp.customer.view`
  laden in der Ticket-Sidebar per Klick Kundendaten aus Odoo (JSON-RPC)
  oder Shopware 6 (Admin-API, OAuth2) – nur für Mitglieder des
  Ticket-Teams, nur die je Verbindung freigegebenen Felder, kein Import
  (5 Minuten Cache). Team-Admins verwalten Verbindungen unter
  `/agent/team/{team}/settings/erp` (verschlüsselte Zugangsdaten,
  Rotation, nur HTTPS). 5 s Timeout und Circuit-Breaker (nach 3 Fehlern
  5 Minuten Pause), Audit-Log je Abruf ohne Kundendaten, Permissions
  `erp.customer.view`/`team.erp.manage`, AVV-/TOM-Hinweise unter
  `docs/compliance/erp-integration.md`.
- Techniker-Einsatzplanung: Technikerverwaltung unter `/admin/technicians`
  (Profile mit Heimatstandort, Skills mit Level, Schichten, Abwesenheiten,
  Einwilligung zur Standortübermittlung), Checklisten-Vorlagen unter
  `/admin/checklists`, Einsatztermine (`service_appointments`, State-Machine
  Vorgeschlagen → Geplant → Unterwegs → Vor Ort → Abgeschlossen/Storniert),
  Dispatcher-Board `/agent/dispatch` mit Drag & Drop, Karte (Leaflet, über
  Vite gebündelt) und regelbasierter Zuweisungs-Engine (Skill, Team,
  Distanz, Verfügbarkeit, SLA-Risiko-Hinweis), `RoutingService` für
  selbst gehostetes OSRM/Nominatim mit plausibilisierten Antworten und
  Luftlinien-Fallback, Offline-fähige Techniker-PWA `/field` (Service
  Worker, IndexedDB-Warteschlange, Sync über `field-sync`-Queue,
  idempotent, Server gewinnt bei Statuskonflikten) mit Checkliste,
  Unterschrift, Materialerfassung und Warenauslieferung inkl.
  Empfangsbestätigung und Lieferschein-PDF am Ticket, opt-in GPS-Log mit
  Löschfrist (`field:prune-locations`). Neue Rolle `technician`.
- Globale MCP-API für KI-Telefonassistenten (`POST /mcp`, offizielles
  `laravel/mcp`, Streamable HTTP): Tools `create_ticket` (Idempotenz über
  Call-ID), `search_tickets` (Cursor-Paginierung), `get_ticket_status`,
  `add_call_note`, `search_knowledge_base` (nur freigegebene Kategorien
  inkl. Unterkategorien, fail-closed über `api_client_kb_categories`).
  Team immer aus dem API-Key, Autorisierung bei jedem Tool-Aufruf,
  strukturierte Fehlerklassen, Audit-Log je Aufruf, 60 Aufrufe/Minute je
  Key über alle Tools, Health-Check `GET /mcp/health`. API-Keys erhalten
  getrennte Abilities für REST (`tickets.create`) und MCP
  (`mcp.tools.use`). Doku: `docs/mcp-integration.md`.
- Self-Service-Portal unter `/portal` (Guard `customer`, eigener
  rate-limitierter Login): `CustomerOwnedScope` (per Middleware, auch für
  Livewire-Updates, vor dem Route-Model-Binding) zeigt nur eigene Tickets,
  `TicketPolicy` als zweite Absicherung, Status-Timeline ohne interne
  Notizen, Antworten öffnen geschlossene Tickets wieder, neue Anfragen aus
  dem Service-Katalog (nur aktive Einträge), Suche in öffentlichen
  KB-Artikeln mit Feedback-Widget.
- Interner Team-Chat: Kanaltypen Team/Global/Ticket (`chat_channels`)
  plus 1:1-Direktnachrichten (`chat_direct_threads`), Zugriff zentral
  über `ChatAccess` (abgeleitet aus Teamzugehörigkeit/Ticket-Team, kein
  kopierter Mitglieder-Stand), Echtzeit über Reverb (Private Channels,
  nur IDs im Payload) mit Polling-Fallback, Ungelesen-Zähler
  (`chat_read_states`) mit Badge in der Navigation, Dateianhänge über den
  `AttachmentService`, Ticket-Chat getrennt von der Ticket-Historie,
  `chat.global.post` für Schreibrecht im Firmen-Chat, opt-in
  Aufbewahrungsfrist (`chat:prune`, Setting `chat.retention_days`).
- Knowledge Base: Kategorien-Baum (`knowledge_base_categories`),
  Markdown-Artikel mit Sichtbarkeit intern/öffentlich, Versionierung
  (`knowledge_base_article_versions`, jede Veröffentlichung = neue
  Version, Diff-Ansicht und Rollback als neue Version), MySQL-Volltextsuche
  (LIKE-Fallback auf SQLite), anonymes Feedback-Widget
  (`knowledge_base_article_feedback`, Aggregat im Management-Dashboard),
  Ticket-Verknüpfung (Artikel in Antwort einfügen, „gelöst mit Artikel"
  über `tickets.resolved_with_article_id`), Admin-UI unter
  `/admin/knowledge-base`, Agenten-Ansicht unter `/agent/kb`,
  Permissions `kb.articles.view`/`kb.articles.manage`/`kb.categories.manage`.
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
