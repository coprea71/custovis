Rollen bündeln Berechtigungen. Jeder Nutzer kann mehrere Rollen haben. Mitgeliefert werden `system_admin`, `agent` und `technician`. Eigene Rollen legen Sie für besondere Aufgaben an, z. B. `dispatcher` oder `cab`.

## Eigene Rolle anlegen

1. Öffnen Sie **Administration → Rollen**.
2. Tragen Sie im Feld **Neue Rolle** einen Namen ein, z. B. `dispatcher`.
3. Klicken Sie auf **Anlegen**. Die Rolle erscheint als neue Spalte in der Tabelle.

## Berechtigungen zuweisen

1. Suchen Sie in der Tabelle die Zeile der gewünschten **Berechtigung**, z. B. `dispatch.manage` für die Einsatzplanung.
2. Setzen Sie in der Spalte der Rolle das Häkchen. Die Änderung gilt sofort.
3. Entfernen Sie das Häkchen, um die Berechtigung wieder zu entziehen.
4. Weisen Sie die Rolle unter **Administration → Nutzer** den passenden Personen zu.

## Wichtige Berechtigungen

- `changes.approve`: CAB-Freigaben erteilen
- `dispatch.manage`: Einsatzplanung
- `appointments.view.own`: Techniker-App
- `kb.articles.manage` / `kb.categories.manage`: Wissensdatenbank pflegen
- `customers.manage`: Kunden anlegen und einladen
- `tickets.view.all`: Tickets aller Teams sehen
- `erp.customer.view`: ERP-Kundendaten im Ticket abrufen

## Rolle löschen

1. Klicken Sie im Spaltenkopf der Rolle auf **×** und bestätigen Sie.
2. Alle Nutzer mit dieser Rolle verlieren die zugehörigen Rechte.

## Gut zu wissen

- Die Rolle `system_admin` hat immer alle Berechtigungen und kann nicht geändert werden.
- Vergeben Sie nur die Rechte, die für die Aufgabe nötig sind (Prinzip der minimalen Rechte).
- Ganze Funktionsbereiche schalten Sie zusätzlich unter **Module** frei oder ab.
