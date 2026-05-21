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
 * \file       htdocs/custom/productionhierarchy/production_needs.php
 * \ingroup    productionhierarchy
 * \brief      Production Needs Analysis - Main Page
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Main include failed");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
dol_include_once('/productionhierarchy/class/hierarchyplanner.class.php');
dol_include_once('/productionhierarchy/class/mosuggestor.class.php');

// Load translation files
$langs->loadLangs(array("productionhierarchy@productionhierarchy", "mrp", "stocks", "products"));

// Get parameters
$product_id = GETPOSTINT('product_id');
$desired_qty = GETPOSTFLOAT('desired_qty');
$action = GETPOST('action', 'aZ09');
$use_virtual_stock = GETPOSTINT('use_virtual_stock');
$consider_mos = GETPOSTINT('consider_mos');
$consider_supplier_orders = GETPOSTINT('consider_supplier_orders');
$warehouse_id = GETPOSTINT('warehouse_id');

// Initialize objects
$form = new Form($db);
$planner = new HierarchyPlanner($db);
$mo_suggestor = new MOSuggestor($db);

// Security check
$result = restrictedArea($user, 'productionhierarchy');
if (!$user->hasRight('productionhierarchy', 'read')) {
	accessforbidden();
}

// Permissions
$permissiontoread = $user->hasRight('productionhierarchy', 'read');
$permissiontocreate = $user->hasRight('productionhierarchy', 'create');

/*
 * Actions
 */

$analysis_results = null;
$created_mos_info = array();

// Analyze production needs
if ($action == 'analyze' && $product_id > 0 && $desired_qty > 0) {
	$options = array(
		'use_virtual_stock' => $use_virtual_stock,
		'consider_mos' => $consider_mos,
		'consider_supplier_orders' => $consider_supplier_orders
	);

	$analysis_results = $planner->analyzeProductionNeeds($product_id, $desired_qty, $options);

	if ($analysis_results === false) {
		setEventMessages($planner->error, null, 'errors');
	} else {
		setEventMessages($langs->trans('AnalysisComplete'), null, 'mesgs');
	}
}

// Create MOs from suggestions
if ($action == 'create_mos' && $permissiontocreate) {
	if (empty($warehouse_id)) {
		setEventMessages($langs->trans('ErrorNoWarehouse'), null, 'errors');
	} else {
		// Get selected MO suggestions
		$selected_mos = GETPOST('selected_mos', 'array');
		
		if (empty($selected_mos)) {
			setEventMessages($langs->trans('SelectAtLeastOneMO'), null, 'warnings');
		} else {
			// Reconstruct suggestions from POST data
			$suggestions_to_create = array();
			foreach ($selected_mos as $index) {
				$suggestions_to_create[] = array(
					'product_id' => GETPOSTINT('mo_product_id_'.$index),
					'qty' => GETPOSTFLOAT('mo_qty_'.$index),
					'bom_id' => GETPOSTINT('mo_bom_id_'.$index),
					'product_ref' => GETPOST('mo_product_ref_'.$index, 'alpha'),
					'priority' => GETPOSTINT('mo_priority_'.$index)
				);
			}

			$date_start = dol_now();
			$date_end = dol_now() + (7 * 24 * 3600); // 7 days from now

			$result = $mo_suggestor->createMOBatch($suggestions_to_create, $warehouse_id, $date_start, $date_end, $user);

			if ($result['success'] > 0) {
				$created_mos_info = $result['created_mos'];
				setEventMessages(sprintf($langs->trans('MOsCreatedSuccess'), $result['success']), null, 'mesgs');
			}

			if (!empty($result['errors'])) {
				foreach ($result['errors'] as $error_detail) {
					setEventMessages($error_detail['product_ref'].': '.$error_detail['error'], null, 'errors');
				}
			}
		}
	}
}

/*
 * View
 */

$title = $langs->trans('ProductionHierarchy');
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-productionhierarchy');

// Page header
print load_fiche_titre($langs->trans('ProductionNeeds'), '', 'mrp');

print '<div class="fichecenter">';

// Analysis Form
print '<div class="div-table-responsive-no-min">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="analyze">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('AnalyzeProductionNeeds').'</th></tr>';

// Product selection
print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('Product').'</td><td>';
$form->select_produits($product_id, 'product_id', '', 0, 0, -1, 2, '', 1, array(), 0, '1', 0, 'minwidth200', 0, '', null, 1);
print '</td></tr>';

// Desired quantity
print '<tr><td class="fieldrequired">'.$form->textwithpicto($langs->trans('DesiredQuantity'), $langs->trans('DesiredQuantityHelp')).'</td><td>';
print '<input type="number" step="0.01" min="0" name="desired_qty" value="'.($desired_qty > 0 ? $desired_qty : '').'" required class="flat minwidth200">';
print '</td></tr>';

// Options
print '<tr><td>'.$langs->trans('UseVirtualStock').'</td><td>';
print '<input type="checkbox" name="use_virtual_stock" value="1"'.($use_virtual_stock ? ' checked' : '').'> ';
print $langs->trans('UseVirtualStockOption');
print '</td></tr>';

print '<tr><td>'.$langs->trans('ConsiderMOs').'</td><td>';
print '<input type="checkbox" name="consider_mos" value="1"'.(isset($_POST['consider_mos']) ? ($consider_mos ? ' checked' : '') : ' checked').'> ';
print $langs->trans('ConsiderMOsOption');
print '</td></tr>';

print '<tr><td>'.$langs->trans('ConsiderSupplierOrders').'</td><td>';
print '<input type="checkbox" name="consider_supplier_orders" value="1"'.(isset($_POST['consider_supplier_orders']) ? ($consider_supplier_orders ? ' checked' : '') : ' checked').'> ';
print $langs->trans('ConsiderSupplierOrdersOption');
print '</td></tr>';

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button button-add" value="'.$langs->trans('AnalyzeButton').'">';
print ' &nbsp; <input type="reset" class="button button-cancel" value="'.$langs->trans('ResetButton').'">';
print '</div>';

print '</form>';
print '</div>';
print '<br>';

// Display analysis results
if ($analysis_results !== null && is_array($analysis_results)) {
	$product = $analysis_results['product'];
	$availability = $analysis_results['availability'];
	$shortage = $analysis_results['shortage'];
	$hierarchy = $analysis_results['hierarchy'];
	$suggestions = $analysis_results['suggestions'];

	// Availability Summary
	print '<div class="ph-availability-summary">';
	print '<h3>'.$langs->trans('AvailabilitySummary').'</h3>';
	
	print '<div class="ph-availability-item">';
	print '<span class="ph-availability-label">'.$product->getNomUrl(1).' - '.$langs->trans('DesiredQuantity').':</span>';
	print '<span class="ph-availability-value">'.price($analysis_results['desired_qty'], 0, '', 0, 0).'</span>';
	print '</div>';

	print '<div class="ph-availability-item">';
	print '<span class="ph-availability-label">'.img_picto('', 'stock', 'class="pictofixedwidth"').$langs->trans('StockAvailable').':</span>';
	print '<span class="ph-availability-value">'.price($availability['stock_available'], 0, '', 0, 0).'</span>';
	print '</div>';

	if (!empty($availability['mos_planned'])) {
		print '<div class="ph-availability-item">';
		print '<span class="ph-availability-label">'.img_picto('', 'mrp', 'class="pictofixedwidth"').$langs->trans('MOsPlanned').' ('.count($availability['mos_list']).'):</span>';
		print '<span class="ph-availability-value">'.price($availability['mos_planned'], 0, '', 0, 0).'</span>';
		print '</div>';
	}

	if (!empty($availability['supplier_orders_incoming'])) {
		print '<div class="ph-availability-item">';
		print '<span class="ph-availability-label">'.img_picto('', 'supplier_order', 'class="pictofixedwidth"').$langs->trans('SupplierOrdersIncoming').' ('.count($availability['supplier_orders_list']).'):</span>';
		print '<span class="ph-availability-value">'.price($availability['supplier_orders_incoming'], 0, '', 0, 0).'</span>';
		print '</div>';
	}

	print '<div class="ph-availability-item">';
	print '<span class="ph-availability-label"><strong>'.$langs->trans('TotalAvailable').':</strong></span>';
	print '<span class="ph-availability-value"><strong>'.price($availability['total_available'], 0, '', 0, 0).'</strong></span>';
	print '</div>';

	print '<div class="ph-availability-item">';
	print '<span class="ph-availability-label"><strong>';
	if ($shortage > 0) {
		print $langs->trans('Shortage').':';
	} elseif ($shortage < 0) {
		print $langs->trans('Surplus').':';
	} else {
		print $langs->trans('ExactMatch');
	}
	print '</strong></span>';
	print '<span class="ph-availability-value '.($shortage > 0 ? 'ph-shortage' : 'ph-surplus').'"><strong>';
	if ($shortage != 0) {
		print price(abs($shortage), 0, '', 0, 0);
	} else {
		print dolGetBadge('✓', '', 'status4');
	}
	print '</strong></span>';
	print '</div>';

	print '</div>';
	print '<br>';

	// Component Hierarchy
	if (!empty($hierarchy)) {
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent ph-hierarchy-table">';
		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans('Level').'</th>';
		print '<th>'.$langs->trans('Component').'</th>';
		print '<th class="right">'.$langs->trans('NeededQty').'</th>';
		print '<th class="right">'.$langs->trans('CurrentStock').'</th>';
		print '<th class="right">'.$langs->trans('MOs').'</th>';
		print '<th class="right">'.$langs->trans('SupplierOrders').'</th>';
		print '<th class="right">'.$langs->trans('TotalAvailable').'</th>';
		print '<th class="center">'.$langs->trans('Status').'</th>';
		print '</tr>';

		foreach ($hierarchy as $component) {
			$css_class = 'oddeven';
			if ($component['status'] == 'missing') {
				$css_class .= ' ph-component-missing';
			} elseif ($component['status'] == 'exact') {
				$css_class .= ' ph-component-ok';
			}

			print '<tr class="'.$css_class.'">';
			
			// Level
			print '<td>'.$component['level'].'</td>';

			// Component with indent
			print '<td class="ph-hierarchy-level-'.$component['level'].'">';
			if ($component['type'] == 'subbom') {
				print img_picto('', 'mrp', 'class="pictofixedwidth"');
			}
			print $component['product']->getNomUrl(1);
			print '</td>';

			// Needed Qty
			print '<td class="right">'.price($component['needed_qty'], 0, '', 0, 0).'</td>';

			// Current Stock
			print '<td class="right">'.price($component['current_stock'], 0, '', 0, 0).'</td>';

			// MOs
			print '<td class="right">'.price($component['mos_qty'], 0, '', 0, 0).'</td>';

			// Supplier Orders
			print '<td class="right">'.price($component['supplier_orders_qty'], 0, '', 0, 0).'</td>';

			// Total Available
			print '<td class="right">'.price($component['total_available'], 0, '', 0, 0).'</td>';

			// Status
			print '<td class="center">';
			if ($component['status'] == 'missing') {
				print dolGetBadge($langs->trans('Missing'), '', 'status5', 'dot');
			} elseif ($component['status'] == 'exact') {
				print dolGetBadge($langs->trans('ExactMatch'), '', 'status4', 'dot');
			} else {
				print dolGetBadge($langs->trans('Sufficient'), '', 'status6', 'dot');
			}
			print '</td>';

			print '</tr>';
		}

		print '</table>';
		print '</div>';
		print '<br>';
	}

	// Suggestions
	if (!empty($suggestions['mos_to_create']) || !empty($suggestions['orders_to_create'])) {
		print '<div class="ph-suggestions">';
		
		// MOs to create
		if (!empty($suggestions['mos_to_create']) && $permissiontocreate) {
			print '<div class="ph-suggestions-section">';
			print '<h3>'.img_picto('', 'mrp', 'class="pictofixedwidth"').$langs->trans('MOsToCreate').'</h3>';

			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="create_mos">';

			// Warehouse selection
			print '<div style="margin-bottom: 10px;">';
			print '<label><strong>'.$langs->trans('WarehouseForProduction').':</strong></label> ';
			$form->select_companywarehouse($warehouse_id, 'warehouse_id', '', 1, 0, 0, '', 0, 0, array(), 'minwidth200');
			print '</div>';

			foreach ($suggestions['mos_to_create'] as $index => $mo_suggestion) {
				print '<div class="ph-suggestion-item">';
				print '<input type="checkbox" name="selected_mos[]" value="'.$index.'" class="ph-suggestion-checkbox" checked>';
				print '<span class="ph-priority-badge ph-priority-'.($mo_suggestion['priority'] + 1).'">'.$langs->trans('Priority').' '.($mo_suggestion['priority'] + 1).'</span>';
				print '<span class="ph-suggestion-info">';
				print '<strong>'.price($mo_suggestion['qty'], 0, '', 0, 0).'x</strong> '.$mo_suggestion['product_ref'].' - '.$mo_suggestion['product_label'];
				print ' <span class="opacitymedium">(BOM #'.$mo_suggestion['bom_id'].')</span>';
				print '</span>';

				// Hidden fields
				print '<input type="hidden" name="mo_product_id_'.$index.'" value="'.$mo_suggestion['product_id'].'">';
				print '<input type="hidden" name="mo_qty_'.$index.'" value="'.$mo_suggestion['qty'].'">';
				print '<input type="hidden" name="mo_bom_id_'.$index.'" value="'.$mo_suggestion['bom_id'].'">';
				print '<input type="hidden" name="mo_product_ref_'.$index.'" value="'.$mo_suggestion['product_ref'].'">';
				print '<input type="hidden" name="mo_priority_'.$index.'" value="'.$mo_suggestion['priority'].'">';
				print '</div>';
			}

			print '<div class="center" style="margin-top: 15px;">';
			print '<input type="submit" class="button button-add" value="'.$langs->trans('CreateSelectedMOs').'">';
			print '</div>';

			print '</form>';
			print '</div>';
		}

		// Orders to create
		if (!empty($suggestions['orders_to_create'])) {
			print '<div class="ph-suggestions-section">';
			print '<h3>'.img_picto('', 'supplier_order', 'class="pictofixedwidth"').$langs->trans('OrdersToCreate').'</h3>';

			foreach ($suggestions['orders_to_create'] as $order_suggestion) {
				print '<div class="ph-suggestion-item">';
				print '<span class="ph-suggestion-info">';
				print '<strong>'.price($order_suggestion['qty'], 0, '', 0, 0).'x</strong> '.$order_suggestion['product_ref'].' - '.$order_suggestion['product_label'];
				print '</span>';
				print '<span class="opacitymedium">'.$langs->trans('ManualOrderRequired').'</span>';
				print '</div>';
			}

			print '</div>';
		}

		print '</div>';
	} else {
		// No suggestions needed
		if ($shortage <= 0) {
			print info_admin($langs->trans('NoSuggestionsNeeded'));
		}
	}

	// Created MOs info
	if (!empty($created_mos_info)) {
		print '<div class="ph-suggestions-section">';
		print '<h3>'.dolGetBadge($langs->trans('Success'), '', 'status4').' '.$langs->trans('MOsCreated').':</h3>';
		foreach ($created_mos_info as $mo_info) {
			print '<div class="ph-suggestion-item">';
			print '<span class="ph-suggestion-info">';
			print '<strong>'.$mo_info['ref'].'</strong> - '.price($mo_info['qty'], 0, '', 0, 0).'x '.$mo_info['product_ref'];
			print '</span>';
			print '</div>';
		}
		print '</div>';
	}
}

print '</div>'; // fichecenter

llxFooter();
$db->close();
