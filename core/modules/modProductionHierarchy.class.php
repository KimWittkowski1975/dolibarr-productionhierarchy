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
 * \defgroup   productionhierarchy     Module ProductionHierarchy
 * \brief      ProductionHierarchy module descriptor.
 *
 * \file       htdocs/custom/productionhierarchy/core/modules/modProductionHierarchy.class.php
 * \ingroup    productionhierarchy
 * \brief      Description and activation file for module ProductionHierarchy
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module ProductionHierarchy
 */
class modProductionHierarchy extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;
		$this->db = $db;

		// Id for module (must be unique).
		$this->numero = 8000100;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'productionhierarchy';

		// Family can be 'base','crm','financial','hr','projects','products','ecm','technic','interface','other'
		$this->family = "products";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '96';

		// Module label (no space allowed), used if translation string 'ModuleProductionHierarchyName' not found
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleProductionHierarchyDesc' not found
		$this->description = "Extended Material Requirements Planning (MRP) with intelligent production and procurement suggestions";
		$this->descriptionlong = "Production Hierarchy module provides advanced material requirements planning by analyzing BOM structures, stock levels, active manufacturing orders, and supplier orders. Features: Hierarchical BOM analysis with multi-level components, Integration with MOs (Validated & In Progress), Integration with Supplier Orders (incoming), Intelligent production suggestions (MO creation), Procurement suggestions for missing materials, Bottom-up calculation for optimal planning, Configurable warehouse filtering, Direct MO creation from suggestions";

		// Author
		$this->editor_name = 'Wittkowski IT';
		$this->editor_url = 'https://www.wittkowski-it.de';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0.0';

		// Key used in llx_const table to save module status enabled/disabled
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		$this->picto = 'mrp';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			'triggers' => 0,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => array('/productionhierarchy/css/productionhierarchy.css'),
			'js' => array(),
			'hooks' => array(),
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		$this->dirs = array();

		// Config pages.
		$this->config_page_url = array('setup.php@productionhierarchy');

		// Dependencies
		$this->hidden = false;
		$this->depends = array('modBom', 'modMrp', 'modProduct', 'modStock'); // Required modules
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("productionhierarchy@productionhierarchy");
		$this->phpmin = array(7, 0);
		$this->need_dolibarr_version = array(21, 0);

		// Constants
		$this->const = array(
			0 => array(
				'PRODUCTIONHIERARCHY_USE_VIRTUAL_STOCK',
				'chaine',
				'0',
				'Use virtual stock instead of real stock (0=real, 1=virtual)',
				0,
				'current',
				1
			),
			1 => array(
				'PRODUCTIONHIERARCHY_WAREHOUSE_PREFIX',
				'chaine',
				'10001',
				'Warehouse prefix to filter warehouses (e.g., 10001)',
				0,
				'current',
				1
			),
			2 => array(
				'PRODUCTIONHIERARCHY_CONSIDER_MOS',
				'chaine',
				'1',
				'Consider manufacturing orders in availability calculation (0=no, 1=yes)',
				0,
				'current',
				1
			),
			3 => array(
				'PRODUCTIONHIERARCHY_CONSIDER_SUPPLIER_ORDERS',
				'chaine',
				'1',
				'Consider supplier orders in availability calculation (0=no, 1=yes)',
				0,
				'current',
				1
			),
		);

		// Array to add new pages in new tabs
		$this->tabs = array();

		// Dictionaries
		$this->dictionaries = array();

		// Boxes/Widgets
		$this->boxes = array();

		// Cronjobs
		$this->cronjobs = array();

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;

		// Read permission
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Read production hierarchy analysis';
		$this->rights[$r][4] = 'read';
		$r++;

		// Write permission (create MOs)
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Create manufacturing orders from suggestions';
		$this->rights[$r][4] = 'create';
		$r++;

		// Export permission
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Export production hierarchy results';
		$this->rights[$r][4] = 'export';
		$r++;

		// Main menu entries to add
		$this->menu = array();
		$r = 0;

		// Add menu entry under MRP
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=mrp',
			'type' => 'left',
			'titre' => 'ProductionHierarchy',
			'prefix' => '<i class="fas fa-project-diagram fa-fw pictofixedwidth"></i>',
			'mainmenu' => 'mrp',
			'leftmenu' => 'productionhierarchy',
			'url' => '/productionhierarchy/production_needs.php',
			'langs' => 'productionhierarchy@productionhierarchy',
			'position' => 1000,
			'enabled' => 'isModEnabled("productionhierarchy") && isModEnabled("mrp") && isModEnabled("bom")',
			'perms' => '$user->hasRight("productionhierarchy", "read")',
			'target' => '',
			'user' => 2
		);
	}

	/**
	 * Function called when module is enabled.
	 * The init function adds constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return int >= 0 if OK, <0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		// Remove permissions and default values if reinit
		$this->remove($options);

		$sql = array();

		// Load sql files (if any exist)
		// Uncomment when SQL table is needed:
		// $sql = array_merge($sql, $this->loadSqlFromFile('sql/llx_productionhierarchy_suggestion.sql'));

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param string $options Options when disabling module ('', 'noboxes')
	 * @return int >= 0 if OK, <0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
