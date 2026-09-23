# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden in dieser Datei
dokumentiert.

Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

## [Unreleased]

### Hinzugefügt

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
