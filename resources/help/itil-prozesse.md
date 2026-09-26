Tickets vom Typ **Incident**, **Problem**, **Change** oder **Service-Request** haben in der Seitenleiste einen zusätzlichen Abschnitt **ITIL**. Dort sehen Sie den aktuellen Zustand, wechseln in den nächsten Zustand und pflegen typspezifische Details.

## Zustand weiterschalten

1. Öffnen Sie das Ticket und die Seitenleiste (auf kleinen Bildschirmen über **Details**).
2. Unter **Zustand** sehen Sie, wo der Vorgang gerade steht.
3. Klicken Sie auf eine der Schaltflächen mit Pfeil, z. B. **→ In Bearbeitung**. Angeboten werden nur Übergänge, die im Prozess erlaubt sind.

Die Abläufe im Überblick:

- **Incident:** Neu → In Bearbeitung → Gelöst → Geschlossen. Ein gelöster Incident kann wieder in Bearbeitung gehen.
- **Problem:** Neu → Wird untersucht → Bekannter Fehler (optional) → Gelöst → Geschlossen.
- **Change:** Entwurf → CAB-Prüfung → Freigegeben oder Abgelehnt → In Umsetzung → Geschlossen.
- **Service-Request:** Neu → Freigegeben → In Bearbeitung → Erfüllt → Geschlossen.

## Details pflegen

1. **Incident:** Wählen Sie **Auswirkung** und **Dringlichkeit** (niedrig, mittel, hoch).
2. **Problem:** Beschreiben Sie unter **Ursache (Root Cause)** die gefundene Ursache.
3. **Change:** Wählen Sie **Change-Typ** (Standard, Normal, Notfall) und **Risiko**, und tragen Sie **Geplanter Beginn** und **Geplantes Ende** ein.
4. Klicken Sie auf **Details speichern**.

## CAB-Freigabe für einen Change anfordern

1. Legen Sie den Change an bzw. öffnen Sie ihn. Er muss sich im Zustand **Entwurf** befinden.
2. Pflegen Sie Typ, Risiko und Zeitraum und speichern Sie die Details.
3. Markieren Sie unter **CAB-Freigabe anfordern bei:** eine oder mehrere genehmigende Personen (Mehrfachauswahl mit Strg bzw. Cmd).
4. Klicken Sie auf **Freigabe anfordern**. Der Change wechselt in **CAB-Prüfung**.
5. Die Entscheidungen erscheinen in der Seitenleiste. Genehmigen alle, ist der Change **Freigegeben**. Lehnt eine Person ab, ist er **Abgelehnt**.
6. Nach der Freigabe schalten Sie den Change auf **In Umsetzung** und zum Schluss auf **Geschlossen**.

## Configuration Items (CIs) zuordnen

1. Wählen Sie im Abschnitt **Configuration Items** unter **CI zuordnen...** das betroffene System aus der CMDB.
2. Klicken Sie auf **+**.
3. Mit **×** entfernen Sie eine Zuordnung wieder.

## Gut zu wissen

- Service-Requests entstehen meist aus dem Kundenportal über den Service-Katalog.
- Welche CIs zur Auswahl stehen, pflegt die Administration unter **CMDB**.
