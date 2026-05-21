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
 * \file       htdocs/custom/productionhierarchy/admin/about.php
 * \ingroup    productionhierarchy
 * \brief      About page for Production Hierarchy module
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

global $langs, $user;

// Load translation files
$langs->loadLangs(array("admin", "productionhierarchy@productionhierarchy"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$backtopage = GETPOST('backtopage', 'alpha');

/*
 * View
 */

$page_name = "ProductionHierarchyAbout";

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

print dol_get_fiche_head($head, 'about', '', -1, '');

// Module information
print '<div class="underbanner clearboth"></div>';
print '<br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th class="titlefield">'.$langs->trans("Parameter").'</th><th>'.$langs->trans("Value").'</th></tr>';

// Module name
print '<tr class="oddeven"><td>'.$langs->trans("ModuleName").'</td>';
print '<td>Production Hierarchy</td></tr>';

// Version
print '<tr class="oddeven"><td>'.$langs->trans("Version").'</td>';
print '<td>1.0.0</td></tr>';

// Author
print '<tr class="oddeven"><td>'.$langs->trans("Author").'</td>';
print '<td>Kim Wittkowski &lt;kim@wittkowski-it.de&gt;</td></tr>';

// Company
print '<tr class="oddeven"><td>'.$langs->trans("Company").'</td>';
print '<td>Wittkowski IT</td></tr>';

// Website
print '<tr class="oddeven"><td>'.$langs->trans("Website").'</td>';
print '<td><a href="https://www.wittkowski-it.de" target="_blank">https://www.wittkowski-it.de</a></td></tr>';

// License
print '<tr class="oddeven"><td>'.$langs->trans("License").'</td>';
print '<td>GPL v3</td></tr>';

// Dolibarr version
print '<tr class="oddeven"><td>'.$langs->trans("DolibarrMinVersion").'</td>';
print '<td>21.0</td></tr>';

// PHP version
print '<tr class="oddeven"><td>'.$langs->trans("PHPMinVersion").'</td>';
print '<td>7.0</td></tr>';

print '</table>';

print '<br>';

// Description
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th>'.$langs->trans("Description").'</th></tr>';
print '<tr><td class="wordbreak">';
print $langs->trans("ProductionHierarchyLongDesc");
print '</td></tr>';
print '</table>';

print '<br>';

// Features
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th>'.$langs->trans("Features").'</th></tr>';
print '<tr><td>';
print '<ul>';
print '<li>'.$langs->trans("Feature1").'</li>';
print '<li>'.$langs->trans("Feature2").'</li>';
print '<li>'.$langs->trans("Feature3").'</li>';
print '<li>'.$langs->trans("Feature4").'</li>';
print '<li>'.$langs->trans("Feature5").'</li>';
print '<li>'.$langs->trans("Feature6").'</li>';
print '</ul>';
print '</td></tr>';
print '</table>';

print dol_get_fiche_end();

llxFooter();
$db->close();
