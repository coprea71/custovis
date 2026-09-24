# Compliance-Übersicht (DSGVO / ISO 27001)

Diese Übersicht fasst die technischen und organisatorischen Maßnahmen zusammen, die
Custovis – Service Suite mitbringt. Sie dient als Grundlage für das Verzeichnis von
Verarbeitungstätigkeiten und die TOM-Dokumentation des Betreibers. Stand: 2026-09-24.

## Zugriffskontrolle

| Maßnahme | Umsetzung |
|---|---|
| Getrennte Identitäten | Guards `web` (Agenten/Admins), `customer` (Kundenportal), `sanctum` (API-Keys). Ein Login gilt nie für einen anderen Bereich. |
| Keine Selbstregistrierung | Fortify-Registrierung ist deaktiviert. Agenten-Konten legt nur ein Admin an. |
| Zwei-Faktor-Authentifizierung | In Produktion **Pflicht** für alle Agenten/Admins (`EnsureTwoFactorIsConfirmed` auf `/agent`, `/admin`, `/field`). Ohne bestätigtes TOTP erfolgt eine Weiterleitung zur Einrichtung unter `/account/security`. Abschalten ist in Produktion gesperrt. Optional nur bei `APP_ENV=local/testing` oder `CUSTOVIS_DEV_MODE=true` (nie produktiv setzen). |
| Team-Trennung | Agenten sehen nur Tickets ihrer Teams bzw. ihnen zugewiesene Tickets (`TicketPolicy::view`, `Ticket::visibleTo`). Vollzugriff nur mit `tickets.view.all`. |
| Kunden-Trennung | `CustomerOwnedScope` auf allen Portal-Requests, inklusive Livewire-Updates. Er greift vor dem Route-Model-Binding, fremde IDs liefern 404. |
| Least Privilege | `spatie/laravel-permission`. Neue Rechte werden Rollen nur beim ersten Anlegen zugewiesen, spätere Entzüge bleiben bei Updates erhalten. |
| Brute-Force-Schutz | Login 5 Versuche/Minute, Portal-Login 5 Versuche/Minute, API/MCP 60 Aufrufe/Minute je Key. |

## Protokollierung (Audit-Log)

- Tabelle `audit_logs`, polymorph, `meta` nur mit Whitelist-Feldern.
- Trait `App\Models\Concerns\Auditable` auf `Ticket`, `ApiClient`, `Mailbox`,
  `WhatsappAccount`, `GitIssueConnection`, `AiSetting`, `KnowledgeBaseArticle` und
  `TechnicianProfile`. Geloggt werden nur explizit gelistete Felder mit Alt- und
  Neuwert. Geheimnisse erscheinen nur als Feldname unter `secrets_changed`, nie mit Wert.
- Zusätzliche fachliche Einträge: API-Key-Anlage/-Widerruf, MCP-Tool-Aufrufe,
  ERP-Abrufe (nur Kundenreferenz), Theme-Wechsel, KB-Veröffentlichungen,
  Einsatz-Zuweisungen, DSGVO-Export/-Anonymisierung (ohne Kennung der Person).

## Verschlüsselung gespeicherter Zugangsdaten

Alle Felder nutzen den `encrypted`-Cast (AES-256 mit `APP_KEY`) und sind zusätzlich in
`$hidden`. Dadurch landen sie nie in Livewire- oder API-Payloads.

| Modell | Verschlüsselte Felder |
|---|---|
| `Mailbox` | `imap_password`, `smtp_password` |
| `WhatsappAccount` | `access_token`, `webhook_verify_token`, `app_secret` |
| `GitIssueConnection` | `access_token`, `webhook_secret` |
| `AiSetting` | `api_key` |
| `ErpConnection` | `auth_payload` (`encrypted:array`) |
| `User` | `two_factor_secret`, `two_factor_recovery_codes` (Fortify) |

API-Keys werden von Sanctum nur als SHA-256-Hash gespeichert und nur einmal im Klartext
angezeigt.

## Betroffenenrechte (DSGVO)

Die Funktionen liegen unter `/admin/compliance` (Permission `compliance.manage`). Die
Befehle laufen per `Artisan::call` aus der Oberfläche, weil auf den Zielservern kein
Shell-Zugriff besteht.

- **Auskunft (Art. 15):** `gdpr:export-customer-data {Kunden-ID|E-Mail|Telefon}`. Die
  JSON-Datei enthält Kundenkonto, Tickets und öffentliche Nachrichten. Sie wird nach
  dem Download sofort gelöscht.
- **Löschung (Art. 17):** `gdpr:anonymize-customer`. Ersetzt Name, E-Mail und
  Telefonnummer, überschreibt eingehende Nachrichten, löscht deren Anhänge und
  anonymisiert Empfangsbestätigungen. Tickets bleiben für Statistik und Audit anonym
  erhalten. Die Oberfläche verlangt eine doppelte Eingabe der Kennung.
- Anrufer-Rufnummern aus der MCP-Telefonanbindung sind über die Telefonnummer abgedeckt.

## Aufbewahrungsfristen

Konfigurierbar unter `/admin/compliance`, täglich angewendet, 0 = unbegrenzt:

| Daten | Umsetzung |
|---|---|
| Team-Chat-Nachrichten inkl. Anhänge | `chat:prune` |
| Audit-Log-Einträge | `retention:apply` |
| Geschlossene Tickets | `retention:apply`, Anonymisierung nach X Tagen |
| Techniker-GPS-Positionen | `field:prune-locations`, `CUSTOVIS_LOCATION_RETENTION_DAYS` (Standard 30) |
| ERP-Kundendaten | nur 5 Minuten im Cache, nie persistiert |

## Externe Dienste und Auftragsverarbeitung

- KI-Provider: eigene API-Keys je Team, optionale PII-Redaction, Budgets. Für
  Cloud-Provider ist ein AVV nötig. Ollama bzw. ein selbst gehosteter Endpunkt ist die
  datensparsame Alternative.
- ERP-Anbindung: siehe [erp-integration.md](erp-integration.md).
- MCP/Telefonassistenten: siehe [../mcp-integration.md](../mcp-integration.md). Mit dem
  Anbieter des Telefonassistenten ist ein AVV nötig.
- Routing/Karten: OSRM/Nominatim/Kacheln selbst hosten, per `.env` konfigurierbar.

## Stichprobe mutierender Endpunkte (Review 2026-09-24)

| Endpunkt | Absicherung | Ergebnis |
|---|---|---|
| `POST /api/v1/tickets` | Sanctum + Ability `tickets.create`, `StoreExternalTicketRequest`, Team aus Token, `in:`-Whitelist für Priorität | in Ordnung |
| `POST /mcp` (`tools/call`) | Sanctum + Ability `mcp.tools.use`, Prüfung bei jedem Aufruf, Validierung je Tool, Team aus Token | in Ordnung |
| Portal-Antwort (`Portal\TicketDetail::sendReply`) | Guard `customer`, `TicketPolicy::replyAsCustomer`, Regeln aus `ReplyToTicketRequest` | in Ordnung |
| Agenten-Antwort (`TicketWorkspace::sendReply`) | Vorher nur Login, keine Team-Prüfung | **behoben:** `Ticket::visibleTo` + `TicketPolicy::view` |
| `POST /register` (Fortify) | Offene Selbstregistrierung von Agenten-Konten | **behoben:** Feature deaktiviert |
| `ServiceCatalogManager::create/toggleActive` | Berechtigung nur beim Laden der Seite geprüft | **behoben:** Prüfung zusätzlich in jeder Aktion |

Die Feature-Tests (`php artisan test`) decken Policies, Guards, DSGVO-Befehle,
2FA-Pflicht, Aufbewahrungsfristen sowie die Sichtbarkeitsregeln von Chat und KB ab.
