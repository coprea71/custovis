# ERP-Kundendaten-Anbindung (Odoo/Shopware) – Datenschutz & Sicherheit

Stand: 2026-09-24 · Bezug: Planhub #15

## Funktionsweise

- Agenten mit der Berechtigung `erp.customer.view` können im Ticket über
  „Kundendaten laden" Stammdaten aus einem angebundenen ERP-/Shopsystem
  abrufen (Odoo per JSON-RPC, Shopware 6 per Admin-API).
- Abgerufen wird nur für Mitglieder des Ticket-Teams und nur über aktive
  Verbindungen dieses Teams. Kundenreferenz ist die E-Mail-Adresse des
  Ticket-Anfragenden (im ERP wird zusätzlich die Kundennummer geprüft).
- **Kein Import:** Ergebnisse werden ausschließlich im Cache gehalten
  (TTL 5 Minuten, Schlüssel `erp_customer:{connection_id}:{referenz}`) und
  nie in einer Datenbanktabelle gespeichert (Art. 5 Abs. 1 lit. c DSGVO).
  Custovis wird dadurch nicht zur „Source of Truth" für diese Daten.
- **Datenminimierung:** Der Team-Admin legt je Verbindung fest, welche
  Remote-Felder abgefragt und angezeigt werden (`field_mapping`). Nur diese
  Felder werden beim ERP angefragt. Bestellungen werden nicht geladen.

## Auftragsverarbeitung (Art. 28 DSGVO)

- Wird das ERP-/Shopsystem von einem **Drittanbieter** betrieben (z. B.
  Odoo Online, Shopware Cloud, externer Hoster), ist mit diesem Anbieter ein
  **Auftragsverarbeitungsvertrag (AVV)** abzuschließen bzw. dessen
  Vorhandensein zu prüfen, bevor die Anbindung produktiv geschaltet wird.
- Der AVV sowie der Anbieter (inkl. Serverstandort, vorzugsweise EU) sind im
  Verzeichnis der Verarbeitungstätigkeiten (Art. 30 DSGVO) zu ergänzen.
- Bei selbst betriebenem ERP entfällt der AVV; die Verarbeitung ist dennoch
  im Verarbeitungsverzeichnis zu dokumentieren.

## Technische und organisatorische Maßnahmen (ISO 27001)

- Zugangsdaten (`auth_payload`) liegen verschlüsselt in der Datenbank
  (Laravel `encrypted`-Cast), werden nie ausgegeben, geloggt oder in
  Audit-Einträge übernommen. Rotation ist über die Team-Einstellungen
  (`/agent/team/{team}/settings/erp`) ohne Neuanlage möglich; eine geänderte
  Basis-URL erzwingt die erneute Eingabe der Zugangsdaten.
- Basis-URLs: nur HTTPS (HTTP ausschließlich in `local`/`testing`), keine
  Zugangsdaten, Parameter oder Anker in der URL.
- **Least Privilege im Zielsystem:** Integrationsnutzer in Odoo nur mit
  Leserecht auf `res.partner`; in Shopware eine Integration ohne
  Schreibrechte, beschränkt auf die Kundenentität.
- Jeder Abruf (auch aus dem Cache) erzeugt einen `audit_logs`-Eintrag
  `erp.customer.lookup` mit Team, Agent, Verbindung und Kundenreferenz –
  ohne Kundendaten. Anlage, Änderung und (De-)Aktivierung von Verbindungen
  werden ebenfalls protokolliert.
- Verfügbarkeit: 5 s Timeout pro Aufruf; nach 3 Fehlern in Folge wird die
  Verbindung für 5 Minuten als „down" markiert (Circuit-Breaker). Das
  Ticket bleibt nutzbar, die Sidebar zeigt einen Fehlerhinweis.
