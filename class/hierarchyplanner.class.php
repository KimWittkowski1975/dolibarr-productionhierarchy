<?php
/* Copyright (C) 2025 Kim Wittkowski <kim@wittkowski-it.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/custom/productionhierarchy/class/hierarchyplanner.class.php
 * \ingroup    productionhierarchy
 * \brief      Production Hierarchy planning and analysis class
 */

require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';

/**
 * HierarchyPlanner class for production needs analysis
 */
class HierarchyPlanner
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var array Analysis results
	 */
	public $results = array();

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var array Error messages
	 */
	public $errors = array();

	/**
	 * @var array Cache for products
	 */
	private $cache_products = array();

	/**
	 * @var array Cache for BOMs
	 */
	private $cache_boms = array();

	/**
	 * @var array Cache for availability data
	 */
	private $cache_availability = array();

	/**
	 * @var array Processed BOMs (to avoid circular references)
	 */
	private $processed_boms = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Analyze production needs for a product
	 *
	 * @param  int     $product_id              Product ID
	 * @param  float   $desired_qty             Desired production quantity
	 * @param  array   $options                 Analysis options
	 * @return array|false                      Analysis results or false on error
	 */
	public function analyzeProductionNeeds($product_id, $desired_qty, $options = array())
	{
		global $conf;

		// Reset processed BOMs
		$this->processed_boms = array();

		// Load product
		$product = $this->getProduct($product_id);
		if (!$product || $product->id <= 0) {
			$this->error = 'Product not found';
			return false;
		}

		// Get BOM for product
		$bom = $this->getBOMForProduct($product_id);
		if (!$bom || $bom->id <= 0) {
			$this->error = 'No BOM found for product '.$product->ref;
			return false;
		}

		// Parse options
		$use_virtual_stock = !empty($options['use_virtual_stock']) ? 1 : getDolGlobalInt('PRODUCTIONHIERARCHY_USE_VIRTUAL_STOCK', 0);
		$consider_mos = !empty($options['consider_mos']) ? 1 : getDolGlobalInt('PRODUCTIONHIERARCHY_CONSIDER_MOS', 1);
		$consider_supplier_orders = !empty($options['consider_supplier_orders']) ? 1 : getDolGlobalInt('PRODUCTIONHIERARCHY_CONSIDER_SUPPLIER_ORDERS', 1);

		// Get availability for main product (Level 0)
		$availability = $this->getAvailability($product_id, array(
			'use_virtual_stock' => $use_virtual_stock,
			'consider_mos' => $consider_mos,
			'consider_supplier_orders' => $consider_supplier_orders
		));

		$shortage = $desired_qty - $availability['total_available'];

		// Resolve BOM hierarchy
		$hierarchy = array();
		$result = $this->resolveHierarchy($bom, $desired_qty, $hierarchy, 0, $options);
		
		if ($result === false) {
			return false;
		}

		// Calculate availability for each component
		foreach ($hierarchy as $key => &$component) {
			$component['availability'] = $this->getAvailability($component['product_id'], array(
				'use_virtual_stock' => $use_virtual_stock,
				'consider_mos' => $consider_mos,
				'consider_supplier_orders' => $consider_supplier_orders
			));
			
			$component['current_stock'] = $component['availability']['stock_available'];
			$component['mos_qty'] = $component['availability']['mos_planned'];
			$component['supplier_orders_qty'] = $component['availability']['supplier_orders_incoming'];
			$component['total_available'] = $component['availability']['total_available'];
			$component['shortage'] = $component['needed_qty'] - $component['total_available'];

			// Determine status
			if ($component['shortage'] > 0) {
				$component['status'] = 'missing';
			} elseif ($component['shortage'] == 0) {
				$component['status'] = 'exact';
			} else {
				$component['status'] = 'sufficient';
			}
		}

		// Generate suggestions (if main product has shortage)
		$suggestions = array();
		if ($shortage > 0) {
			$suggestions = $this->generateSuggestions($product_id, $shortage, $hierarchy, $options);
		}

		// Store results
		$this->results = array(
			'product' => $product,
			'desired_qty' => $desired_qty,
			'bom' => $bom,
			'availability' => $availability,
			'shortage' => $shortage,
			'hierarchy' => $hierarchy,
			'suggestions' => $suggestions,
			'calculation_date' => dol_now(),
			'options' => array(
				'use_virtual_stock' => $use_virtual_stock,
				'consider_mos' => $consider_mos,
				'consider_supplier_orders' => $consider_supplier_orders
			)
		);

		return $this->results;
	}

	/**
	 * Get availability for a product (Stock + MOs + Supplier Orders)
	 *
	 * @param  int     $product_id Product ID
	 * @param  array   $options    Options
	 * @return array               Availability data
	 */
	private function getAvailability($product_id, $options = array())
	{
		global $conf;

		// Check cache
		$cache_key = $product_id.'_'.serialize($options);
		if (isset($this->cache_availability[$cache_key])) {
			return $this->cache_availability[$cache_key];
		}

		$product = $this->getProduct($product_id);
		if (!$product || $product->id <= 0) {
			return array(
				'stock_available' => 0,
				'mos_planned' => 0,
				'mos_list' => array(),
				'supplier_orders_incoming' => 0,
				'supplier_orders_list' => array(),
				'total_available' => 0
			);
		}

		// Stock - Load both physical and virtual
		$product->load_stock(''); // Load both real and virtual
		$stock_physical = $product->stock_reel; // Physical stock for display
		$stock_virtual = $product->stock_theorique; // Virtual stock for calculation
		
		if (empty($stock_physical)) {
			$stock_physical = 0;
		}
		if (empty($stock_virtual)) {
			$stock_virtual = 0;
		}

		// Filter by warehouse prefix if configured
		$warehouse_prefix = getDolGlobalString('PRODUCTIONHIERARCHY_WAREHOUSE_PREFIX', '');
		if (!empty($warehouse_prefix) && is_array($product->stock_warehouse) && count($product->stock_warehouse) > 0) {
			$filtered_stock_physical = 0;
			$found_match = false;
			foreach ($product->stock_warehouse as $warehouse_id => $warehouse_data) {
				// Check if warehouse ID starts with prefix
				if (strpos((string)$warehouse_id, $warehouse_prefix) === 0) {
					$filtered_stock_physical += $warehouse_data->real;
					$found_match = true;
				}
			}
			// Only apply filter if we found matching warehouses
			if ($found_match) {
				$stock_physical = $filtered_stock_physical;
				// Note: When filtering by warehouse, we can't accurately recalculate virtual stock
				// Virtual stock from load_stock() is global and includes MOs/orders across all warehouses
				// We keep the global virtual stock as it's the best available value
			}
		}

		// MOs
		$mos_qty = 0;
		$mos_list = array();
		if (!empty($options['consider_mos'])) {
			$mos_list = $this->getActiveMOsForProduct($product_id);
			foreach ($mos_list as $mo) {
				$mos_qty += $mo['qty'];
			}
		}

		// Supplier Orders
		$supplier_orders_qty = 0;
		$supplier_orders_list = array();
		if (!empty($options['consider_supplier_orders'])) {
			$supplier_orders_list = $this->getIncomingSupplierOrders($product_id);
			foreach ($supplier_orders_list as $order) {
				$supplier_orders_qty += $order['qty_remaining'];
			}
		}

		$result = array(
			'stock_physical' => $stock_physical,
			'stock_virtual' => $stock_virtual,
			'stock_available' => $stock_physical, // For display (backward compatibility)
			'mos_planned' => $mos_qty,
			'mos_list' => $mos_list,
			'supplier_orders_incoming' => $supplier_orders_qty,
			'supplier_orders_list' => $supplier_orders_list,
			'total_available' => $stock_virtual // MOs and supplier orders already included in virtual stock
		);

		// Cache result
		$this->cache_availability[$cache_key] = $result;

		return $result;
	}

	/**
	 * Get active manufacturing orders for a product
	 *
	 * @param  int     $product_id Product ID
	 * @return array               List of active MOs
	 */
	private function getActiveMOsForProduct($product_id)
	{
		global $conf;

		$mos = array();

		$sql = "SELECT m.rowid, m.ref, m.fk_product, m.qty, m.status,";
		$sql .= " m.date_start_planned, m.date_end_planned";
		$sql .= " FROM ".MAIN_DB_PREFIX."mrp_mo as m";
		$sql .= " WHERE m.fk_product = ".((int) $product_id);
		$sql .= " AND m.status IN (1, 2)"; // Validated + InProgress
		$sql .= " AND m.entity = ".((int) $conf->entity);
		$sql .= " ORDER BY m.date_start_planned ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			for ($i = 0; $i < $num; $i++) {
				$obj = $this->db->fetch_object($resql);
				$mos[] = array(
					'id' => $obj->rowid,
					'ref' => $obj->ref,
					'qty' => $obj->qty,
					'status' => $obj->status,
					'date_start' => $obj->date_start_planned,
					'date_end' => $obj->date_end_planned
				);
			}
			$this->db->free($resql);
		}

		return $mos;
	}

	/**
	 * Get incoming supplier orders for a product
	 *
	 * @param  int     $product_id Product ID
	 * @return array               List of supplier orders
	 */
	private function getIncomingSupplierOrders($product_id)
	{
		global $conf;

		$orders = array();

		// Query supplier orders with received quantities from dispatch table
		$sql = "SELECT cf.rowid as order_id, cf.ref as order_ref, cf.ref_supplier,";
		$sql .= " cf.date_livraison, cfd.rowid as line_id, cfd.fk_product,";
		$sql .= " cfd.qty as ordered_qty,";
		$sql .= " COALESCE(SUM(cfd_dispatch.qty), 0) as qty_received,";
		$sql .= " (cfd.qty - COALESCE(SUM(cfd_dispatch.qty), 0)) as qty_remaining";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."commande_fournisseurdet as cfd ON cf.rowid = cfd.fk_commande";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as cfd_dispatch";
		$sql .= " ON cfd.rowid = cfd_dispatch.fk_commandefourndet";
		$sql .= " WHERE cfd.fk_product = ".((int) $product_id);
		$sql .= " AND cf.fk_statut IN (3, 4)"; // Ordered + Partially Received
		$sql .= " AND cf.entity = ".((int) $conf->entity);
		$sql .= " GROUP BY cf.rowid, cfd.rowid";
		$sql .= " HAVING qty_remaining > 0"; // Only lines with remaining quantity
		$sql .= " ORDER BY cf.date_livraison ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			for ($i = 0; $i < $num; $i++) {
				$obj = $this->db->fetch_object($resql);
				$orders[] = array(
					'order_id' => $obj->order_id,
					'order_ref' => $obj->order_ref,
					'ref_supplier' => $obj->ref_supplier,
					'ordered_qty' => $obj->ordered_qty,
					'qty_received' => $obj->qty_received,
					'qty_remaining' => $obj->qty_remaining,
					'delivery_date' => $obj->date_livraison
				);
			}
			$this->db->free($resql);
		}

		return $orders;
	}

	/**
	 * Resolve BOM hierarchy recursively
	 *
	 * @param  BOM     $bom                BOM object
	 * @param  float   $qty_factor         Quantity multiplier
	 * @param  array   &$hierarchy         Array to store hierarchy
	 * @param  int     $level              Recursion level
	 * @param  array   $options            Options
	 * @return bool                        True on success, false on error
	 */
	private function resolveHierarchy($bom, $qty_factor, &$hierarchy, $level = 0, $options = array())
	{
		// Check for circular references
		if (in_array($bom->id, $this->processed_boms)) {
			$this->error = 'Circular BOM reference detected: '.$bom->ref;
			return false;
		}
		$this->processed_boms[] = $bom->id;

		// Load BOM lines
		$bom->fetchLines();

		if (!is_array($bom->lines) || empty($bom->lines)) {
			return true; // Empty BOM is OK
		}

		foreach ($bom->lines as $line) {
			// Check if line is a sub-BOM
			if (!empty($line->fk_bom_child)) {
				// Load sub-BOM
				$subbom = $this->getBOM($line->fk_bom_child);
				if (!$subbom || $subbom->id <= 0) {
					continue; // Skip invalid sub-BOM
				}

				$subbom_product_id = $subbom->fk_product;

				// Check if sub-BOM product is available in stock
				$availability = $this->getAvailability($subbom_product_id, $options);

				// ALWAYS add sub-BOM as component to hierarchy (for MO suggestions)
				$this->addComponentToHierarchy($hierarchy, $subbom_product_id, $line->qty * $qty_factor, $level + 1, 'subbom', $line->fk_bom_child);

				// If not enough available, ALSO resolve into sub-components
				if ($availability['total_available'] < ($line->qty * $qty_factor)) {
					// Mark that this sub-BOM will be resolved into components
					$this->markComponentAsResolved($hierarchy, $subbom_product_id, $level + 1);

					// Recurse into sub-BOM components
					$result = $this->resolveHierarchy($subbom, $line->qty * $qty_factor, $hierarchy, $level + 1, $options);
					if ($result === false) {
						return false;
					}
				}
			} else {
				// Regular component (raw material or product)
				$product_id = $line->fk_product;

				// Skip services (they don't have stock)
				$product = $this->getProduct($product_id);
				if ($product && $product->type == Product::TYPE_SERVICE) {
					continue;
				}

				$this->addComponentToHierarchy($hierarchy, $product_id, $line->qty * $qty_factor, $level + 1, 'raw', null);
			}
		}

		return true;
	}

	/**
	 * Add or update component in hierarchy
	 *
	 * @param  array   &$hierarchy      Hierarchy array
	 * @param  int     $product_id      Product ID
	 * @param  float   $qty             Quantity
	 * @param  int     $level           Level
	 * @param  string  $type            Type (raw, subbom)
	 * @param  int     $bom_id          BOM ID if sub-BOM
	 * @return void
	 */
	private function addComponentToHierarchy(&$hierarchy, $product_id, $qty, $level, $type, $bom_id = null)
	{
		// Check if component already exists at this level
		$existing_key = null;
		foreach ($hierarchy as $key => $comp) {
			if ($comp['product_id'] == $product_id && $comp['level'] == $level) {
				$existing_key = $key;
				break;
			}
		}

		if ($existing_key !== null) {
			// Component exists - add quantity
			$hierarchy[$existing_key]['needed_qty'] += $qty;
		} else {
			// New component
			$product = $this->getProduct($product_id);
			$hierarchy[] = array(
				'product_id' => $product_id,
				'product' => $product,
				'level' => $level,
				'needed_qty' => $qty,
				'type' => $type,
				'bom_id' => $bom_id,
				'resolved_into_components' => false // Flag to track if resolved into sub-components
			);
		}
	}

	/**
	 * Mark a component as resolved into sub-components
	 *
	 * @param  array   &$hierarchy      Hierarchy array
	 * @param  int     $product_id      Product ID
	 * @param  int     $level           Level
	 * @return void
	 */
	private function markComponentAsResolved(&$hierarchy, $product_id, $level)
	{
		foreach ($hierarchy as $key => &$comp) {
			if ($comp['product_id'] == $product_id && $comp['level'] == $level) {
				$comp['resolved_into_components'] = true;
				break;
			}
		}
	}

	/**
	 * Generate production and procurement suggestions
	 *
	 * @param  int     $main_product_id    Main product ID
	 * @param  float   $main_shortage      Main product shortage
	 * @param  array   $hierarchy          Component hierarchy
	 * @param  array   $options            Options
	 * @return array                       Suggestions
	 */
	private function generateSuggestions($main_product_id, $main_shortage, $hierarchy, $options = array())
	{
		$mos_to_create = array();
		$orders_to_create = array();

		// Sort hierarchy by level (highest first = bottom-up)
		usort($hierarchy, function ($a, $b) {
			return $b['level'] - $a['level'];
		});

		// Process each component
		foreach ($hierarchy as $component) {
			if ($component['shortage'] <= 0) {
				continue; // No shortage for this component
			}

			// Check if product has a BOM (can be manufactured)
			$bom_id = $this->getBOMIdForProduct($component['product_id']);

			if ($bom_id > 0) {
				// Product can be manufactured
				$mos_to_create[] = array(
					'product_id' => $component['product_id'],
					'product_ref' => $component['product']->ref,
					'product_label' => $component['product']->label,
					'qty' => ceil($component['shortage']),
					'bom_id' => $bom_id,
					'priority' => $component['level'],
					'type' => 'mo'
				);
			} else {
				// Product cannot be manufactured - must be ordered
				$orders_to_create[] = array(
					'product_id' => $component['product_id'],
					'product_ref' => $component['product']->ref,
					'product_label' => $component['product']->label,
					'qty' => ceil($component['shortage']),
					'type' => 'purchase'
				);
			}
		}

		// Add main product MO if needed
		if ($main_shortage > 0) {
			$main_bom_id = $this->getBOMIdForProduct($main_product_id);
			if ($main_bom_id > 0) {
				$main_product = $this->getProduct($main_product_id);
				array_unshift($mos_to_create, array(
					'product_id' => $main_product_id,
					'product_ref' => $main_product->ref,
					'product_label' => $main_product->label,
					'qty' => ceil($main_shortage),
					'bom_id' => $main_bom_id,
					'priority' => 0,
					'type' => 'mo'
				));
			}
		}

		// Sort MOs by priority (highest level first = create sub-assemblies before main product)
		usort($mos_to_create, function ($a, $b) {
			return $b['priority'] - $a['priority']; // Higher priority number = create first
		});

		return array(
			'mos_to_create' => $mos_to_create,
			'orders_to_create' => $orders_to_create
		);
	}

	/**
	 * Get product from cache or load it
	 *
	 * @param  int     $product_id Product ID
	 * @return Product|false       Product object or false
	 */
	private function getProduct($product_id)
	{
		if (!isset($this->cache_products[$product_id])) {
			$product = new Product($this->db);
			$result = $product->fetch($product_id);
			if ($result <= 0) {
				return false;
			}
			$this->cache_products[$product_id] = $product;
		}
		return $this->cache_products[$product_id];
	}

	/**
	 * Get BOM from cache or load it
	 *
	 * @param  int     $bom_id BOM ID
	 * @return BOM|false       BOM object or false
	 */
	private function getBOM($bom_id)
	{
		if (!isset($this->cache_boms[$bom_id])) {
			$bom = new BOM($this->db);
			$result = $bom->fetch($bom_id);
			if ($result <= 0) {
				return false;
			}
			$this->cache_boms[$bom_id] = $bom;
		}
		return $this->cache_boms[$bom_id];
	}

	/**
	 * Get BOM for a product (first validated BOM)
	 *
	 * @param  int     $product_id Product ID
	 * @return BOM|false           BOM object or false
	 */
	private function getBOMForProduct($product_id)
	{
		global $conf;

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."bom_bom";
		$sql .= " WHERE fk_product = ".((int) $product_id);
		$sql .= " AND status = 1"; // Validated
		$sql .= " AND entity = ".((int) $conf->entity);
		$sql .= " LIMIT 1";

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				return $this->getBOM($obj->rowid);
			}
		}

		return false;
	}

	/**
	 * Get BOM ID for a product
	 *
	 * @param  int     $product_id Product ID
	 * @return int                 BOM ID or 0
	 */
	private function getBOMIdForProduct($product_id)
	{
		$bom = $this->getBOMForProduct($product_id);
		return ($bom && $bom->id > 0) ? $bom->id : 0;
	}
}
