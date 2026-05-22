# Changelog

All notable changes to the Production Hierarchy module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-01-20

### Added
- ✨ Initial release of Production Hierarchy module
- 📊 Hierarchical BOM analysis with multi-level component breakdown
- 📈 Availability calculation (Stock + MOs + Supplier Orders)
- 💡 Intelligent production suggestions (MO creation proposals)
- 🛒 Procurement suggestions for missing materials
- 🏭 Direct MO creation from suggestions (batch mode)
- ⚙️ Configuration page with warehouse filtering
- 🗄️ Integration with Dolibarr MRP, BOM, Stock modules
- 🌍 Multi-language support (English, German)
- 🔐 Permission system (read, create, export)
- 📋 Menu integration under MRP section

### Configuration Options
- Use virtual stock vs. real stock
- Warehouse prefix filtering (e.g., 10001*)
- Toggle MO consideration
- Toggle Supplier Order consideration

### Technical Details
- Module numero: 8000100
- Depends on: BOM, MRP, Product, Stock modules
- Compatible with: Dolibarr 21.0+
- PHP requirement: 7.0+

---

## [1.1.0] - 2026-05-22

### Added
- ✨ New "Qty Difference" column showing virtual available - needed quantity
- 🎨 Color-coded difference display (red for shortage, green for surplus)
- 📊 Separated physical stock display from virtual stock calculation

### Changed
- 🔄 Column rename: "FAs" → "Planned MOs" (clearer terminology)
- 🔄 Column rename: "Total Available" → "Virtual Available" (more accurate)
- 📈 "Current Stock" now shows physical stock only (not virtual)
- 🧮 "Virtual Available" calculation: Virtual Stock + Planned MOs (supplier orders already included in virtual stock)
- 📝 Updated calculation formula display to reflect new logic

### Fixed
- 🐛 Fixed double-counting of supplier orders in availability calculation
- 🐛 Supplier orders are now correctly included in virtual stock, not added separately
- ✅ Sub-assembly MO suggestions now work correctly (Level 2+ components)
- 🔧 Warehouse prefix filtering now applies to both physical and virtual stock

### Removed
- ❌ Non-functional checkboxes for calculation options (now fixed: always use virtual stock + MOs + orders)
- ❌ Setup page checkbox settings (replaced with informational text about calculation method)

### Technical
- Refactored `getAvailability()` to return both `stock_physical` and `stock_virtual`
- Updated language files (de_DE, en_US) with new translations
- Improved code documentation and calculation transparency

---

## [Unreleased]

### Planned for v1.1
- [ ] CSV/Excel export functionality
- [ ] Suggestion history (save & load past analyses)
- [ ] PDF report generation
- [ ] Supplier order creation from suggestions
- [ ] Advanced warehouse selection (multiple selection)

### Planned for v1.2
- [ ] Calendar view for MO planning
- [ ] Drag & drop MO scheduling
- [ ] Email notifications for critical shortages
- [ ] Dashboard widget with production alerts

---

## Version History

| Version | Date | Description |
|---------|------|-------------|
| 1.0.0 | 2026-05-21 | Initial release with core MRP features |

---

**Maintained by:** Kim Wittkowski <kim@wittkowski-it.de>  
**License:** GPL v3
