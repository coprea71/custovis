Die CMDB (Configuration Management Database) enthält die IT-Komponenten, die Sie betreuen: Server, Anwendungen, Netzwerkgeräte, Arbeitsplätze und Dienste. Diese **Configuration Items (CIs)** ordnen Sie Incidents und Changes zu und sehen so, was betroffen ist.

## CI anlegen

1. Öffnen Sie **Administration → CMDB** und klicken Sie auf **Neues CI**.
2. Tragen Sie einen eindeutigen **Namen** ein, z. B. `srv-mail-01`.
3. Wählen Sie das zuständige **Team**.
4. Wählen Sie den **Typ**: Server, Anwendung, Netzwerkgerät, Arbeitsplatz oder Dienst.
5. Wählen Sie den **Status**: **in Betrieb** oder **außer Betrieb**.
6. Klicken Sie auf **Speichern**.

## CI bearbeiten

1. Klicken Sie das CI in der Liste links an. Das Formular **CI bearbeiten** öffnet sich.
2. Ändern Sie Name, Team, Typ oder Status.
3. Klicken Sie auf **Speichern**.

## Beziehungen erfassen

1. Wählen Sie das CI in der Liste links.
2. Wählen Sie unter **Beziehungen** die Art: **hängt ab von**, **hostet** oder **verbunden mit**.
3. Wählen Sie unter **CI wählen...** das Ziel-CI.
4. Klicken Sie auf **Hinzufügen**. Mit **Entfernen** löschen Sie eine Beziehung wieder.

Beispiel: „Webshop“ *hängt ab von* „Datenbank-Server“. Fällt der Server aus, sehen Sie sofort, dass auch der Webshop betroffen ist.

## Gut zu wissen

- Ausgemusterte Komponenten setzen Sie auf **außer Betrieb**, statt sie zu löschen. So bleiben alte Tickets nachvollziehbar.
- Wie Sie CIs einem Ticket zuordnen, steht im Hilfethema „Incidents, Problems und Changes“.
