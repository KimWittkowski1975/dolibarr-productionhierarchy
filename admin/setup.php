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
 * \file       htdocs/custom/productionhierarchy/admin/setup.php
 * \ingroup    productionhierarchy
 * \brief      Production Hierarchy module setup page
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
	$res = @include "../../../../main.inc.php";
}
if (!$res) {
	die("Main include failed");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

global $langs, $user, $conf, $db;

// Load translation files
$langs->loadLangs(array("admin", "productionhierarchy@productionhierarchy"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$form = new Form($db);

$arrayofparameters = array(
	'PRODUCTIONHIERARCHY_USE_VIRTUAL_STOCK' => array(
		'type' => 'yesno',
		'label' => 'UseVirtualStock',
		'help' => 'UseVirtualStockHelp'
	),
	'PRODUCTIONHIERARCHY_WAREHOUSE_PREFIX' => array(
		'type' => 'text',
		'label' => 'WarehousePrefix',
		'help' => 'WarehousePrefixHelp',
		'css' => 'minwidth200'
	),
	'PRODUCTIONHIERARCHY_CONSIDER_MOS' => array(
		'type' => 'yesno',
		'label' => 'ConsiderMOs',
		'help' => 'ConsiderMOsHelp'
	),
	'PRODUCTIONHIERARCHY_CONSIDER_SUPPLIER_ORDERS' => array(
		'type' => 'yesno',
		'label' => 'ConsiderSupplierOrders',
		'help' => 'ConsiderSupplierOrdersHelp'
	),
);

/*
 * Actions
 */

if ($action == 'update') {
	$error = 0;
	
	foreach ($arrayofparameters as $key => $val) {
		$value = GETPOST($key, 'alpha');
		
		if ($val['type'] == 'yesno') {
			$value = GETPOSTINT($key);
		}
		
		$result = dolibarr_set_const($db, $key, $value, 'chaine', 0, '', $conf->entity);
		if (!$result > 0) {
			$error++;
		}
	}
	
	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

/*
 * View
 */

$page_name = "ProductionHierarchySetup";

llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = array();
$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/custom/productionhierarchy/admin/setup.php';
$head[$h][1] = $langs->trans('Settings');
$head[$h][2] = 'settings';
$h++;

$head[$h][0] = DOL_URL_ROOT.'/custom/productionhierarchy/admin/about.php';
$head[$h][1] = $langs->trans('About');
$head[$h][2] = 'about';
$h++;

print dol_get_fiche_head($head, 'settings', '', -1, '');

// Info message
print info_admin($langs->trans("ProductionHierarchySetupInfo"));

// Setup form
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefield">'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

foreach ($arrayofparameters as $key => $val) {
	print '<tr class="oddeven"><td>';
	
	$help = '';
	if (!empty($val['help'])) {
		$help = $langs->trans($val['help']);
	}
	
	print $form->textwithpicto($langs->trans($val['label']), $help);
	print '</td><td>';

	if ($val['type'] == 'yesno') {
		print ajax_constantonoff($key);
	} elseif ($val['type'] == 'text') {
		$value = getDolGlobalString($key);
		print '<input name="'.$key.'" class="flat '.(empty($val['css']) ? 'minwidth200' : $val['css']).'" value="'.dol_escape_htmltag($value).'">';
	}
	
	print '</td></tr>';
}

print '</table>';

print dol_get_fiche_end();

print '<div class="tabsAction">';
print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

// Information section
print '<br>';
print '<div class="info hideonsmartphone">';
print '<b>'.$langs->trans("ConfigurationHelp").':</b><br><br>';
print '• <b>'.$langs->trans("UseVirtualStock").'</b>: '.$langs->trans("UseVirtualStockHelp").'<br>';
print '• <b>'.$langs->trans("WarehousePrefix").'</b>: '.$langs->trans("WarehousePrefixHelp").'<br>';
print '• <b>'.$langs->trans("ConsiderMOs").'</b>: '.$langs->trans("ConsiderMOsHelp").'<br>';
print '• <b>'.$langs->trans("ConsiderSupplierOrders").'</b>: '.$langs->trans("ConsiderSupplierOrdersHelp").'<br>';
print '</div>';

llxFooter();
$db->close();
