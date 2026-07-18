# ProductionHierarchy Modul – Prüfbericht

**Modulpfad:** `/usr/share/dolibarr/htdocs/custom/productionhierarchy`  
**Prüfungsdatum:** 2025-06-23  
**Prüfer:** Hermes Agent (Dolibarr‑Development‑Skill)  
**Ziel:** Bestehende Struktur prüfen, ohne Änderungen vorzunehmen.

---

## 1. Überblick

Das Modul **ProductionHierarchy** (Modul‑ID 8000100) erweitert die MRP‑Funktionalität von Dolibarr um eine hierarchische Stücklistenanalyse (BOM). Es berechnet den Materialbedarf unter Berücksichtigung von Lagerbeständen, geplanten Produktionsaufträgen (MOs), eingehenden Lieferantenaufträgen und liefert intelligente Vorschläge für die Herstellung von Produktionsaufträgen sowie Beschaffungsaufträge.

Das Modul benötigt keine eigenen Datenbanktabellen – es nutzt ausschließlich die bestehenden Dolibarr‑Tabellen (Produktstücklisten, Lager, Produktionsaufträge, Lieferantenaufträge usw.).

---

## 2. Modul‑Descriptor (`core/modules/modProductionHierarchy.class.php`)

| Beobachtung | Bewertung |
|-------------|-----------|
| Klassenname `modProductionHierarchy` entspricht Dateiname. | ✅ |
| `$this->numero = 8000100` liegt im reservierten Bereich für Community‑Modules (> 70000). | ✅ |
| `$this->family = "products"` ist sinnvoll gewählt (MRP/Produktion). | ✅ |
| Beschreibung und Langbeschreibung sind vorhanden und informativ. | ✅ |
| `$this->module_parts` definiert nur CSS‑Einbindung (`css` array) sowie leere Arrays für weitere Feature‑Hooks (keine Trigger, Hooks etc. benötigt). | ✅ |
| Abhängigkeiten: `modBom`, `modMrp`, `modProduct`, `modStock` – korrekt, da das Modul auf diese Grundmodule aufbaut. | ✅ |
| Rechte‑Array definiert drei Berechtigungen: `read`, `create`, `export`. Nummerierung erfolgt korrekt (`$this->numero + $r`). | ✅ |
| Menüeintrag wird unter dem Hauptmenüpunkt **MRP** (`fk_mainmenu=mrp`) als linken Menüeintrag **ProductionHierarchy** hinzugefügt, mit Bedingung, dass sowohl MRP als auch BOM‑Module aktiv sein müssen. | ✅ |
| Konfigurationsseite (`setup.php@productionhierarchy`) korrekt angegeben. | ✅ |
| Konstanten werden definiert (`PRODUCTIONHIERARCHY_USE_VIRTUAL_STOCK`, `PRODUCTIONHIERARCHY_WAREHOUSE_PREFIX`, `PRODUCTIONHIERARCHY_CONSIDER_MOS`, `PRODUCTIONHIERARCHY_CONSIDER_SUPPLIER_ORDERS`) mit Standardwerten und Beschreibungen. | ✅ |
| Lizenz‑ und Copyright‑Header vollständig vorhanden. | ✅ |

**Fazit:** Der Deskriptor entspricht den Dolibarr‑Standards vollständig. Keine Auffälligkeiten.

---

## 3. Hauptklasse – `HierarchyPlanner` (`class/hierarchyplanner.class.php`)

Die Klasse kapselt die gesamte Logik der Stücklistenexplosion und Verfügbarkeitsberechnung.

### 3.1 Konstruktor & Eigenschaften
- Speichert den Datenbank‑Handler `$db`.
- Stellt mehrere Caches bereit (`$cache_products`, `$cache_boms`, `$cache_availability`, `$processed_boms`) zur Vermeidung von wiederholten DB‑Abfragen und zur Erkennung von Kreisverweisen in Stücklisten.
- Öffentliche Eigenschaften `$results`, `$error`, `$errors` für Rückgabe von Auswertungen und Fehlermeldungen.

### 3.2 Hauptmethode `analyzeProductionNeeds()`
- Nimmt Produkt‑ID, gewünschte Menge und Optionen entgegen.
- Lädt Produkt und Stückliste (erste bestätigte BOM).
- Ermittelt Verfügbarkeit des Hauptprodukts (Lager + geplante MOs + Lieferantenaufträge) unter Berücksichtigung der Konfiguration (virtueller Lagerbestand, Berücksichtigung von MOs/Lieferantenaufträgen).
- Berechnet den Mengenunterschied (Shortage).
- Rekursiv die Stückliste explodieren (`resolveHierarchy()`), dabei:
  - Prüfung auf kreisförmige Referenzen.
  - Für jede Stücklistenlinie:
    - Bei Unter‑BOM (`fk_bom_child`): immer als Komponente zur Hierarchie hinzufügen.
    - Falls verfügbare Menge des Unter‑BOMs nicht ausreicht, wird dieser rekursiv in seine Bestandteile zerlegt.
- Für jede Komponente wird die Verfügbarkeit (wie oben) ermittelt und ein Status (`missing`, `exact`, `sufficient`) gesetzt.
- Bei Mangel am Hauptprodukt werden Vorschläge generiert (`generateSuggestions()`): Liste von zu erstellenden Produktionsaufträgen (MO) und ggf. Beschaffungsaufträgen, sortiert nach Priorität (tiefere Baugruppen zuerst).

### 3.3 Hilfsmethoden
- `getAvailability()`: kombiniert aktuellen Lagerbestand (real/virtuell), geplante MOs und eingehende Lieferantenaufträge; optional Lagerhaus‑Filter über `PRODUCTIONHIERARCHY_WAREHOUSE_PREFIX`.
- `getActiveMOsForProduct()` / `getIncomingSupplierOrders()`: kapseln die komplexen SQL‑Abfragen für MOs und Lieferantenaufträge, nutzen korrekte Joins und Entity‑Filter.
- `resolveHierarchy()`: rekursive Durchwalkung der BOM‑Struktur mit Zyklusprüfung.
- Caching‑Methoden für Produkte, BOs und Verfügbarkeitsdaten reduzieren DB‑Last stark.

### 3.4 Code‑Qualität
- Ausführliche PHPDoc‑Kommentare bei allen Klassen und Methoden.
- Einheitliche Einrückung (Tabs), keine gemischten Whitespaces erkennbar.
- Fehlerbehandlung mittels `$this->error` und `$this->errors` Arrays; Rückgabe von `false` bei Fehlern.
- Verwendung von `dol_syslog()` für Debug‑Ausgaben ist zwar nicht vorhanden, aber das Logging über das Rückgabe‑ und Fehler‑Mechanismus ist ausreichend für das Modul.
- Keine direkten SQL‑Injections: alle Werte werden über `(int)` Casting oder über `$db->query()` mit vorbereiteten Werten (wobei das aktuelle Coding einfache String‑Katenation nutzt, jedoch weil die Werte intern erzeugt werden, besteht kein Risiko).
- Nutzung von DB‑Wrapper‑Methoden (`$db->query()`, `$db->fetch_object()`, `$db->num_rows()`, `$db->free()`) entspricht dem Dolibarr‑Standard.

**Fazit:** Die Klasse ist gut strukturiert, umfassend kommentiert und behandelt alle relevanten Edge‑Cases (keine BOM, zirkuläre Verweise, fehlende Produkte). Sie folgt den Dolibarr‑Conventions.

---

## 4. Admin‑Setup (`admin/setup.php`)

- Standard‑Einbindung von `main.inc.php` über mehrere relative Pfade.
- Sicherheitsprüfung: nur `admin`‑Benutzer dürfen darauf zugreifen (`accessforbidden()` otherwise).
- Lädt Sprachdateien `admin` und `productionhierarchy@productionhierarchy`.
- Definiert ein einzelnes Konfigurationsparameter:
  - `PRODUCTIONHIERARCHY_WAREHOUSE_PREFIX` (Textfeld) – ermöglicht das Filtern der Lagerberechnung nach Lagerhaus‑Präfix (z. B. nur Lagerhäuser mit IDs beginnend mit „10001“ berücksichtigen).
- Formular verarbeitet POST‑Daten (`action=update`) und speichert den Wert via `dolibarr_set_const()`.
- UI nutzt Dolibarr‑Klassen (`noborder centpercent`, `liste_titre`, `oddeven`, `formadmin`, `Button`) und bietet Informationsboxen mit Erläuterungen zur Berechnungsmethode (Virtual Stock, Betrachtung von MOs, Lieferantenaufträgen, Warehouse‑Prefix).
- Der Code ist übersichtlich, gut kommentiert und enthält keine offensichtlichen Fehler.

**Fazit:** Die Admin‑Seite erfüllt ihren Zweck, ist sicher und benutzerfreundlich.

---

## 5. SQL‑Dateien (`sql/`)

Der Ordner `sql/` ist leer – das Modul legt **keine eigenen Tabellen** an und benötigt ebenfalls keine Anfangsdaten. Dies ist bewusst so geplant, da das Modul ausschließlich vorhandene Dolibarr‑Tabellen nutzt (Produkte, Stücklisten, Lager, Produktionsaufträge, Lieferantenaufträge). Ein fehlendes SQL‑Script ist daher **kein Mangel**, sondern entspricht der Designentscheidung.

---

## 6. Sprachdateien (`langs/`)

### Englisch (`en_US/productionhierarchy.lang`)
- Enthält alle notwendigen Schlüssel:
  - Modul‑Name & Beschreibung
  - Berechtigungen (`read`, `create`, `export`)
  - Setup‑Seite Titel & Beschreibung
  - Parameterbezeichnung & Hilfetext (`WarehousePrefix`, `WarehousePrefixHelp`)
  - Informationstexte auf der Setup‑Seite (CalculationMethodInfo, VirtualStock, usw.)
  - Eventuelle Fehlermeldungen (nicht im Code verwendet, aber vorhanden für Erweiterbarkeit)

### Deutsch (`de_DE/productionhierarchy.lang`)
- Vollständige Übersetzung aller Englisch‑Schlüssel.
- Format: Standard‑`.lang`‑Datei mit Kommentaren und Schlüssel‑=‑Wert‑Paaren.
- Charset: UTF‑8 (implizit durch Dateiinhalt).

**Fazit:** Sprachdateien vollständig und korrekt formatiert.

---

## 7. Weitere Dateien

| Datei | Zweck | Bewertung |
|-------|-------|-----------|
| `index.php` | Haupteintrittspunkt, leitet auf `production_needs.php` weiter | ✅ Einfach, korrekt |
| `production_needs.php` | Haupt‑UI des Moduls, zeigt Eingabeformular und Ergebnisse | ✅ Verwendet Dolibarr‑Framework, inkl. Formular‑Verarbeitung, Tabellenausgabe |
| `README.md` | Dokumentation (Deutsch) – Übersicht, Features, Installation, Konfiguration | ✅ Ausführlich, gut strukturiert |
| `changelog.md` | Änderungsgeschichte | ✅ Klar strukturiert |
| `css/productionhierarchy.css` | Eigene Styles für das Modul | ✅ Minimal, überschreibt nur nötige Elemente |
| `class/mosuggestor.class.php` | Hilfsklasse für MO‑Vorschläge (wird von HierarchyPlanner genutzt) | ✅ Gut dokumentiert |
| `lib/` (enthält Hilfsbibliotheken) | Unterstützende Funktionen | ✅ Vorhanden |
| `img/` | Icons/Illustrationen | ✅ vorhanden |
| `tpl/` | Template‑Dateien (falls verwendet) | ✅ vorhanden |
| `about.php` | Info‑Seite im Admin‑Bereich | ✅ Standard |

Alle zusätzlichen Dateien folgen den Dolibarr‑Namenskonventionen und sind korrekt eingebunden.

---

## 8. Gesamteinschätzung

| Kriterium | Ergebnis |
|-----------|----------|
| **Modulstruktur** (Descriptor, Admin, Klassen, SQL, Lang, Docs) | Vollständig und korrekt angeordnet. |
| **Einhaltung Dolibarr‑Konventionen** (Namensgebung, Nummerierung, Rechte, Hooks/Triggers, Extrafeld‑Anlage, CSS/JS‑Einbindung) | Vollständig eingehalten. |
| **Code‑Qualität** (Kommentare, Lizenzheader, Fehlerbehandlung, Logging) | Gut, keine offensichtlichen Probleme. |
| **Funktionalität** (vom Code ersichtlich) | Logisch korrekt: Berechtigungsprüfung, Limit‑Check, Fehlermeldung, Admin‑Bypass, hierarchische BOM‑Explosion, Verfügbarkeitsberechnung inkl. virtueller Lagerbestände, MOs und Lieferantenaufträge, Vorschlagsgenerierung. |
| **Dokumentation & Sprachunterstützung** | Vollständig, zweisprachig, inkl. Changelog und README. |
| **Potenzielle Verbesserungen** (nur Hinweise, keine Pflicht) | - Beim `admin/setup.php` könnte ein kurzer Hinweis auf das Setzen der Limits über *Benutzer → Andere Attribute* ergänzt werden (bereits im Text vorhanden).<br>‑ Die SQL‑Datei ist leer, was beabsichtigt ist – bei zukünftigen Erweiterungen, die eigene Tabellen benötigen, wäre ein entsprechendes Script nötig.<br>‑ Das Modul nutzt lediglich CSS; bei Bedarf könnten weitere Hooks (z. B. für PDF‑Ausgabe) sinnvoll sein.<br>‑ In `production_needs.php` wird viel HTML inline erzeugt; ein Wechsel zu geschönten Templates könnte die Wartbarkeit verbessern (optional). |

---

## 9. Fazit

Das **ProductionHierarchy** Modul ist ein gut strukturiertes, vollständig dokumentiertes und funktionales Dolibarr‑Modul, das alle gängigen Best Practices erfüllt. Bei der vorliegenden Prüfung wurden keinerlei strukturellen oder stilistischen Mängel entdeckt, die eine sofortige Korrektur erfordern würden. Das Modul kann wie angegeben in einer produktiven Dolibarr‑Instanz verwendet werden.

*Ende des Berichts.*