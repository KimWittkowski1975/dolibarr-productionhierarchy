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
