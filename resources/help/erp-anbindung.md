Mit einer ERP-Anbindung sehen Agenten im Ticket die Stammdaten des Kunden aus **Odoo** oder **Shopware 6**, z. B. Kundennummer oder Adresse. Die Daten werden nur gelesen und live abgefragt, nicht in Custovis gespeichert. Zugeordnet wird über die E-Mail-Adresse des Ticket-Absenders.

## Zugangsdaten im ERP anlegen

1. **Odoo:** Legen Sie einen eigenen Integrationsnutzer mit Leserechten auf Kontakte an und erzeugen Sie für ihn unter **Einstellungen → Kontosicherheit** einen API-Key.
2. **Shopware 6:** Legen Sie in der Administration unter **Einstellungen → System → Integrationen** eine Integration an und notieren Sie Access-Key-ID und Secret-Access-Key.

## Verbindung in Custovis anlegen

1. Klicken Sie unter Ihrem Team auf **Einstellungen** und dann auf **ERP**.
2. Vergeben Sie eine **Bezeichnung** und wählen Sie das **System**.
3. Tragen Sie die **Basis-URL** ein (muss mit `https://` beginnen).
4. **Odoo:** Tragen Sie **Datenbank**, **Login des Integrationsnutzers** und **API-Key** ein. **Shopware:** Tragen Sie **Access-Key-ID (Client-ID)** und **Secret-Access-Key** ein.
5. Legen Sie unter **Angezeigte Felder** fest, welche Felder abgefragt werden: je Zeile `feldname=Bezeichnung`, z. B. `phone=Telefon`. Nur diese Felder werden gelesen (Datenminimierung).
6. Speichern Sie die Verbindung. Sie ist danach **Aktiv**.

## Kundendaten im Ticket abrufen

1. Öffnen Sie ein Ticket des Teams und die Seitenleiste.
2. Klicken Sie im Abschnitt **ERP-Kundendaten** auf **Kundendaten laden**.
3. Die festgelegten Felder erscheinen. Mit **Erneut laden** aktualisieren Sie sie.
4. „Kein passender Kunde gefunden“ bedeutet, dass im ERP kein Kunde mit der E-Mail-Adresse des Tickets existiert.

## Gut zu wissen

- Zum Abrufen im Ticket brauchen Agenten die Berechtigung zum Anzeigen von ERP-Kundendaten.
- **Bearbeiten**: Lassen Sie das Secret leer, um die gespeicherten Zugangsdaten zu behalten. Nach einer URL-Änderung müssen Sie es neu eingeben.
- Mit **Deaktivieren** schalten Sie die Anzeige ab, ohne die Verbindung zu löschen.
