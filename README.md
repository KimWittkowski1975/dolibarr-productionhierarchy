# Production Hierarchy Module

**Module Name:** productionhierarchy  
**Version:** 1.0.0  
**Author:** Kim Wittkowski <kim@wittkowski-it.de>  
**License:** GPL v3  
**Dolibarr Version:** 21.0+

## 📋 Description

Advanced Material Requirements Planning (MRP) module that provides intelligent production and procurement suggestions by analyzing:
- ✅ Hierarchical BOM structures (multi-level)
- ✅ Current stock levels (real or virtual)
- ✅ Active manufacturing orders (MOs)
- ✅ Incoming supplier orders

## 🎯 Features

### Core Features (v1.0)
- **Hierarchical BOM Analysis:** Complete multi-level component breakdown
- **Availability Calculation:** Stock + MOs + Supplier Orders
- **Intelligent Suggestions:** Automated MO and procurement proposals
- **Bottom-Up Planning:** Optimal production sequence
- **Direct MO Creation:** Batch create MOs from suggestions
- **Configurable Warehouses:** Filter by warehouse prefix

### Integrations
- 🏭 **Manufacturing Orders:** Validated & In Progress status
- 📦 **Supplier Orders:** Ordered & Partially Received status
- 📊 **BOMs:** Recursive multi-level structure support
- 🏢 **Warehouses:** Configurable prefix filtering (e.g., 10001*)

## 📥 Installation

1. Extract module to `/custom/productionhierarchy/`
2. Go to: **Home → Setup → Modules**
3. Find **"Production Hierarchy"** and activate it
4. Configure settings in **Setup** page

## ⚙️ Configuration

**Access:** Home → MRP → Production Hierarchy → ⚙️ Setup

### Available Settings:

| Setting | Description | Default |
|---------|-------------|---------|
| **Use Virtual Stock** | Calculate with virtual stock (includes planned orders) | No (Real Stock) |
| **Warehouse Prefix** | Filter warehouses by prefix (e.g., 10001) | 10001 |
| **Consider MOs** | Include active manufacturing orders in calculation | Yes |
| **Consider Supplier Orders** | Include incoming supplier orders in calculation | Yes |

## 🚀 Usage

### Basic Workflow

1. Navigate to: **MRP → Production Hierarchy**
2. Select product from dropdown
3. Enter desired production quantity
4. Click **"Analyze Production Needs"**
5. Review hierarchical component breakdown
6. Check suggested MOs and procurement
7. Create MOs directly from suggestions

### Understanding the Results

**Availability Summary:**
- **Stock Available:** Current warehouse stock
- **MOs Planned:** Quantity in active manufacturing orders
- **Supplier Orders:** Quantity in incoming orders
- **Total Available:** Sum of all sources
- **Shortage/Surplus:** Difference vs. desired quantity

**Component Hierarchy:**
- **Level 0:** Main product
- **Level 1+:** Components and sub-components
- Shows needed quantities and availability for each

**Suggestions:**
- **MOs to Create:** Products that can be manufactured
- **Orders to Create:** Materials that must be purchased

## 📊 Example Scenario

**Goal:** Produce 10x Product A

```
Product A (10 needed)
├─ 5x shortage → Suggest: Create MO for 5x Product A
   ├─ Requires: 5x Component B
   │  └─ 2x in stock, 3x shortage → Suggest: Create MO for 3x Component B
   │     └─ Requires: 36x Raw Material C (12 per unit)
   │        └─ 20x in stock, 16x shortage → Suggest: Order 16x Raw Material C
   └─ Requires: 5x Component D
      └─ 10x in stock ✓ (sufficient)
```

## 🛠️ Technical Details

### Dependencies
- **Required Modules:**
  - BOM (Bill of Materials)
  - MRP (Manufacturing Resource Planning)
  - Product
  - Stock (Warehouse Management)

### Database Tables Used
- `llx_bom` - Bill of Materials
- `llx_bom_bomline` - BOM components
- `llx_mrp_mo` - Manufacturing Orders
- `llx_product_stock` - Stock levels
- `llx_commande_fournisseur` - Supplier Orders
- `llx_commande_fournisseurdet` - Supplier Order lines

### Permissions
- **Read:** View production hierarchy analysis
- **Create:** Create manufacturing orders from suggestions
- **Export:** Export results to CSV/Excel

## 📈 Roadmap

### Planned Features (v1.1+)
- [ ] CSV/Excel Export
- [ ] Suggestion History (save analyses)
- [ ] PDF Reports
- [ ] Supplier Order creation from suggestions
- [ ] Advanced warehouse selection
- [ ] Calendar view for MO planning
- [ ] Email notifications for critical shortages

## 🐛 Troubleshooting

**Module not visible after activation:**
- Ensure all required modules are enabled (BOM, MRP, Product, Stock)
- Check user permissions: Setup → Users → Permissions → Production Hierarchy

**No suggestions shown:**
- Verify BOMs exist for products
- Check warehouse configuration in Setup
- Ensure MO/Supplier Order statuses are correct

**"No BOM found" error:**
- Product must have a validated BOM (status = 1)
- Go to: Manufacturing → BOMs → Create BOM for product

## 📝 Support

**Author:** Kim Wittkowski  
**Email:** kim@wittkowski-it.de  
**Company:** Wittkowski IT  
**Website:** https://www.wittkowski-it.de

## 📄 License

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

## 🙏 Acknowledgments

Based on concepts from:
- **bomcapacity** module (capacity analysis)
- Dolibarr MRP/BOM core functionality

---

**Version:** 1.0.0  
**Last Updated:** May 21, 2026  
**Status:** ✅ Production Ready
