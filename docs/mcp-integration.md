# MCP-Anbindung für KI-Telefonassistenten

Custovis stellt einen globalen [Model-Context-Protocol](https://modelcontextprotocol.io)-Endpunkt
bereit, über den KI-Telefonassistenten (z. B. telli, fonio) während eines Anrufs Tickets
anlegen, den Ticketstatus abfragen und freigegebene Hilfe-Artikel durchsuchen.

| | |
|---|---|
| Endpunkt | `POST https://<ihre-domain>/mcp` (Streamable HTTP, JSON-RPC 2.0) |
| Health-Check | `GET https://<ihre-domain>/mcp/health` (ohne Anmeldung) |
| Authentifizierung | `Authorization: Bearer <API-Key>` |
| Rate-Limit | 60 Aufrufe pro Minute und API-Key, über alle Tools gemeinsam |

## Einrichtung

1. Team-Admin öffnet **Team → Einstellungen → API-Keys** (`/agent/team/{team}/settings/api-keys`).
2. Neuen Key anlegen, **„MCP (KI-Telefonassistent)"** anhaken, optional „REST-API" abwählen.
3. Wissensdatenbank-Kategorien auswählen, die der Assistent durchsuchen darf
   (Unterkategorien sind eingeschlossen). Ohne Auswahl liefert die KB-Suche nichts.
4. Den einmalig angezeigten Key in der Konfiguration des Telefonassistenten als
   Bearer-Token hinterlegen.

Alle Tickets landen im Team des Keys. Das Team lässt sich nicht über Tool-Parameter
wählen, sondern ergibt sich immer aus dem Key.

## Tools

| Tool | Zweck | Pflichtparameter |
|---|---|---|
| `create_ticket` | Ticket für einen Anrufer anlegen (`source = phone`) | `caller_phone`, `summary`, `idempotency_key` |
| `search_tickets` | Offene Tickets zur Rufnummer, cursor-paginiert | `caller_phone` |
| `get_ticket_status` | Status eines Tickets des eigenen Teams | `ticket_id` |
| `add_call_note` | Anruf-Zusammenfassung als interne Notiz anhängen | `ticket_id`, `note` |
| `search_knowledge_base` | Suche in den freigegebenen öffentlichen Artikeln | `query` |

`idempotency_key` sollte die Call-ID des Telefonats sein: Wiederholte Aufrufe mit
derselben ID liefern das bereits angelegte Ticket (`"duplicate": true`) statt eines neuen.

## Fehlerklassen

Fehlgeschlagene Tool-Aufrufe liefern `isError: true` und einen Text mit Präfix:

- `client_error:` – Eingabe ungültig → beim Anrufer nachfragen
- `not_found:` – Ticket existiert nicht (oder gehört zu einem anderen Team)
- `server_error:` – interner Fehler → an einen Menschen übergeben

## Datenschutz

Jeder Tool-Aufruf wird im Audit-Log protokolliert (Team, Tool, Erfolg/Fehlerklasse,
keine Inhalte). Anrufer-Rufnummern sind personenbezogene Daten und werden wie
E-Mail-Adressen in den DSGVO-Funktionen berücksichtigt.
