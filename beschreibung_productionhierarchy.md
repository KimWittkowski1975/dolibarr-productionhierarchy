# Beschreibung des Moduls productionhierarchy

**Modulname:** Production Hierarchy
**Version:** 1.1.0
**Autor:** Kim Wittkowski <kim@wittkowski-it.de>
**Lizenz:** GPL v3
**Dolibarr-Version:** 21.0+
**Modul-Numero:** 8000100

## Worum geht es?

Production Hierarchy ist ein erweitertes MRP-Modul (Material Requirements Planning) für Dolibarr. Es beantwortet die Frage "Was brauche ich, um Produkt X in Menge Y zu produzieren – und was davon habe ich schon?" nicht nur für das Endprodukt, sondern rekursiv über alle Stücklistenebenen (Baugruppen, Unterbaugruppen, Rohmaterialien) hinweg.

Statt jede Komponente einzeln manuell zu prüfen, liefert das Modul auf Knopfdruck eine vollständige, hierarchische Bedarfsanalyse inklusive konkreter Handlungsvorschläge: welche Fertigungsaufträge angelegt und welche Materialien nachbestellt werden müssen.

## Kernfunktionen

- **Hierarchische Stücklistenanalyse:** Vollständige mehrstufige Zerlegung der BOM (Bill of Materials), beliebig viele Ebenen tief.
- **Verfügbarkeitsberechnung:** Berücksichtigt physischen Lagerbestand, virtuellen Lagerbestand, laufende Fertigungsaufträge (validiert & in Bearbeitung) sowie offene Lieferantenbestellungen.
- **Intelligente Vorschläge:** Automatische Vorschläge, welche Fertigungsaufträge erstellt und welche Rohmaterialien bestellt werden sollten – bottom-up berechnet für eine sinnvolle Produktionsreihenfolge.
- **Direkte FA-Erstellung:** Fertigungsaufträge lassen sich direkt aus den Vorschlägen heraus (auch im Batch) anlegen.
- **Konfigurierbare Lagerfilterung:** Einschränkung der Berechnung auf Lager mit bestimmtem Präfix (z. B. `10001*`).
- **Mengendifferenz-Anzeige:** Farbliche Kennzeichnung von Fehlmengen (rot) und Überschüssen (grün) je Komponente.

## Funktionsweise (Kurzbeispiel)

```
Produkt A (10 Stück benötigt)
├─ 5 Stück fehlen → Vorschlag: FA für 5x Produkt A anlegen
   ├─ benötigt: 5x Komponente B
   │  └─ 2 auf Lager, 3 fehlen → Vorschlag: FA für 3x Komponente B
   │     └─ benötigt: 36x Rohmaterial C (12 pro Einheit)
   │        └─ 20 auf Lager, 16 fehlen → Vorschlag: 16x Rohmaterial C bestellen
   └─ benötigt: 5x Komponente D
      └─ 10 auf Lager ✓ (ausreichend)
```

## Abhängigkeiten

Benötigt die Dolibarr-Kernmodule **BOM**, **MRP**, **Produkt** und **Lager**. Kein eigenes Datenbankschema – das Modul liest ausschließlich bestehende Dolibarr-Tabellen (`llx_bom`, `llx_bom_bomline`, `llx_mrp_mo`, `llx_product_stock`, `llx_commande_fournisseur` u. a.) und erzeugt keine eigenen Tabellen.

## Berechtigungen

Drei granulare Rechte: Lesen (Analyse einsehen), Erstellen (Fertigungsaufträge aus Vorschlägen anlegen), Export (Ergebnisse exportieren).

## Status

Produktiv einsetzbar (Status: Production Ready). Geplante Erweiterungen (v1.1+/v1.2): CSV/Excel-Export, Speicherung von Analysen, PDF-Berichte, Bestellungserstellung aus Vorschlägen, Kalenderansicht für die FA-Planung, E-Mail-Benachrichtigungen bei kritischen Fehlmengen.
