# Forumsbeitrag: Production Hierarchy

---

## English

### [Module] Production Hierarchy — Recursive multi-level MRP suggestions for BOM/MO/Stock

Hi everyone,

I'd like to share a module I built for our own production planning and figured others running manufacturing in Dolibarr might find useful too: **Production Hierarchy**.

**The problem it solves:**
Dolibarr's core BOM/MRP tools tell you what a single product needs, but if you have multi-level assemblies (a product made of sub-assemblies, made of sub-sub-assemblies, made of raw materials...), you end up manually walking down the tree yourself to figure out what's actually missing at *every* level, and what to order or manufacture next.

**What the module does:**
Pick a product and a target quantity, hit "Analyze Production Needs", and it recursively breaks down the full BOM hierarchy — as many levels deep as your BOMs go — while checking availability at each level against:

- Physical and virtual stock
- Active manufacturing orders (validated & in progress)
- Incoming supplier orders

From that, it computes a bottom-up production plan and gives you concrete, actionable suggestions:
- Which manufacturing orders to create, and for how much
- Which raw materials to purchase, and how much

You can create the suggested MOs directly from the results screen (batch creation supported), so you're not just staring at a report — you can act on it immediately.

**Key features:**
- Recursive multi-level BOM analysis
- Availability calc combining stock + MOs + supplier orders
- Color-coded shortage/surplus per component
- Configurable warehouse filtering (by prefix)
- Direct MO creation from suggestions
- Granular permissions (read / create / export)
- EN/DE translations included

**Requirements:** Dolibarr 21.0+, core modules BOM, MRP, Product, Stock enabled. No custom database tables — it reads directly from existing MRP/BOM/Stock data.

**Status:** In active use, considered production-ready. Roadmap includes CSV/Excel export, saved analysis history, PDF reports, and supplier-order creation directly from suggestions.

Happy to answer questions or hear feedback if anyone tries it out on a more complex BOM structure than mine.

— Kim (Wittkowski IT)

---

## Deutsch

### [Modul] Production Hierarchy — Rekursive mehrstufige MRP-Vorschläge für Stückliste/Fertigungsauftrag/Lager

Hallo zusammen,

ich möchte ein Modul vorstellen, das ich ursprünglich für unsere eigene Produktionsplanung gebaut habe und das vermutlich auch für andere mit mehrstufiger Fertigung in Dolibarr interessant ist: **Production Hierarchy**.

**Das gelöste Problem:**
Die Bordmittel von Dolibarr (Stückliste/MRP) zeigen den Bedarf für ein einzelnes Produkt, aber bei mehrstufigen Baugruppen (Produkt aus Baugruppen, aus Unterbaugruppen, aus Rohmaterial ...) muss man den Baum bisher von Hand durchgehen, um auf jeder Ebene herauszufinden, was wirklich fehlt und was als Nächstes bestellt oder gefertigt werden muss.

**Was das Modul macht:**
Produkt und Zielmenge auswählen, auf "Produktionsbedarf analysieren" klicken – das Modul zerlegt rekursiv die komplette Stücklistenhierarchie (beliebig viele Ebenen tief) und prüft auf jeder Ebene die Verfügbarkeit anhand von:

- physischem und virtuellem Lagerbestand
- aktiven Fertigungsaufträgen (validiert & in Bearbeitung)
- offenen Lieferantenbestellungen

Daraus wird ein Bottom-up-Produktionsplan berechnet mit konkreten, umsetzbaren Vorschlägen:
- welche Fertigungsaufträge in welcher Menge angelegt werden sollten
- welche Rohmaterialien in welcher Menge nachbestellt werden müssen

Die vorgeschlagenen Fertigungsaufträge lassen sich direkt aus der Ergebnisansicht heraus anlegen (auch im Batch) – man bleibt also nicht bei der Analyse stehen, sondern kann sofort handeln.

**Hauptfunktionen:**
- Rekursive, mehrstufige Stücklistenanalyse
- Verfügbarkeitsberechnung aus Lagerbestand + Fertigungsaufträgen + Lieferantenbestellungen
- Farblich markierte Fehlmengen/Überschüsse je Komponente
- Konfigurierbare Lagerfilterung (nach Präfix)
- Direkte Anlage von Fertigungsaufträgen aus den Vorschlägen
- Granulare Berechtigungen (Lesen / Erstellen / Export)
- Deutsche und englische Übersetzung enthalten

**Voraussetzungen:** Dolibarr 21.0+, Kernmodule Stückliste, MRP, Produkt und Lager aktiviert. Keine eigenen Datenbanktabellen – das Modul liest ausschließlich bestehende MRP-/Stücklisten-/Lagerdaten.

**Status:** Im produktiven Einsatz. Auf der Roadmap stehen u. a. CSV/Excel-Export, gespeicherte Analyseverläufe, PDF-Berichte und die Erstellung von Lieferantenbestellungen direkt aus den Vorschlägen.

Über Fragen und Rückmeldungen freue ich mich – besonders, wenn jemand es an einer komplexeren Stücklistenstruktur als meiner testet.

— Kim (Wittkowski IT)
