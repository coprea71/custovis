Mit API-Keys binden Sie externe Systeme an Ihr Team an: Die **REST-API** legt Tickets an (z. B. aus einem Webshop oder Kontaktformular), **MCP** erlaubt KI-Telefonassistenten, Tickets anzulegen und zu suchen, den Status abzufragen und freigegebene Wissensdatenbank-Artikel zu durchsuchen.

## API-Key anlegen

1. Klicken Sie unter Ihrem Team auf **Einstellungen** und dann auf **API-Keys**.
2. Geben Sie unter **Name des Clients** eine Bezeichnung ein, z. B. „Webshop“.
3. Wählen Sie, wofür der Key gelten soll: **REST-API (Ticket-Anlage)**, **MCP (KI-Telefonassistent)** oder beides.
4. Bei MCP: Markieren Sie die **freigegebenen Wissensdatenbank-Kategorien**. Unterkategorien sind eingeschlossen. Ohne Auswahl hat der Assistent keinen Zugriff auf die Wissensdatenbank.
5. Klicken Sie auf **Anlegen**.
6. **Kopieren Sie den angezeigten Key sofort** und hinterlegen Sie ihn im externen System. Er wird nur dieses eine Mal im Klartext angezeigt.
7. Klicken Sie auf **Schließen**.

## Key im externen System verwenden

1. **REST-API:** Tickets werden per `POST /api/v1/tickets` angelegt. Der Key wird als Bearer-Token im Header `Authorization` mitgeschickt.
2. **MCP:** Tragen Sie im Telefonassistenten die angezeigte MCP-Adresse (endet auf `/mcp`) und den Key als Bearer-Token ein.

## KB-Freigabe ändern oder Key widerrufen

1. Klicken Sie bei einem MCP-Key auf **KB-Freigabe**, passen Sie die Kategorien an und klicken Sie auf **Speichern**.
2. Klicken Sie auf **Widerrufen**, wenn ein Key nicht mehr gebraucht wird oder in falsche Hände geraten sein könnte. Der Key funktioniert danach sofort nicht mehr.

## Gut zu wissen

- Die Liste zeigt je Key, wer ihn angelegt hat, ob er aktiv ist und wann er zuletzt genutzt wurde. Wird ein Key lange nicht genutzt, widerrufen Sie ihn.
- Legen Sie für jedes externe System einen eigenen Key an. So können Sie einzelne Zugänge gezielt sperren.
