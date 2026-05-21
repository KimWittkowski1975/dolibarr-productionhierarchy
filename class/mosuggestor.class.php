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
 * \file       htdocs/custom/productionhierarchy/class/mosuggestor.class.php
 * \ingroup    productionhierarchy
 * \brief      Manufacturing Order Suggestor and Creator class
 */

require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

/**
 * MOSuggestor class for creating MOs from suggestions
 */
class MOSuggestor
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var array Error messages
	 */
	public $errors = array();

	/**
	 * @var array Successfully created MOs
	 */
	public $created_mos = array();

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
	 * Create MO from suggestion data
	 *
	 * @param  array   $suggestion      Suggestion data
	 * @param  int     $warehouse_id    Warehouse ID
	 * @param  int     $date_start      Start date timestamp
	 * @param  int     $date_end        End date timestamp
	 * @param  User    $user            User object
	 * @return int|false                MO ID or false on error
	 */
	public function createMOFromSuggestion($suggestion, $warehouse_id, $date_start, $date_end, $user)
	{
		global $conf;

		// Validate suggestion
		if (empty($suggestion['product_id']) || empty($suggestion['qty']) || empty($suggestion['bom_id'])) {
			$this->error = 'Invalid suggestion data';
			return false;
		}

		// Load product
		$product = new Product($this->db);
		$result = $product->fetch($suggestion['product_id']);
		if ($result <= 0) {
			$this->error = 'Product not found';
			return false;
		}

		// Load BOM
		$bom = new BOM($this->db);
		$result = $bom->fetch($suggestion['bom_id']);
		if ($result <= 0) {
			$this->error = 'BOM not found';
			return false;
		}

		// Create MO
		$mo = new Mo($this->db);
		$mo->ref = '(PROV)';
		$mo->fk_bom = $bom->id;
		$mo->fk_product = $product->id;
		$mo->qty = $suggestion['qty'];
		$mo->label = 'Auto-generated from Production Hierarchy';
		$mo->fk_warehouse = $warehouse_id;
		$mo->date_start_planned = $date_start;
		$mo->date_end_planned = $date_end;
		$mo->status = Mo::STATUS_DRAFT;

		$mo_id = $mo->create($user);

		if ($mo_id <= 0) {
			$this->error = $mo->error;
			$this->errors = $mo->errors;
			return false;
		}

		// Store created MO info
		$this->created_mos[] = array(
			'id' => $mo_id,
			'ref' => $mo->ref,
			'product_ref' => $product->ref,
			'qty' => $suggestion['qty']
		);

		return $mo_id;
	}

	/**
	 * Create multiple MOs from suggestions (batch mode)
	 *
	 * @param  array   $suggestions     Array of suggestions
	 * @param  int     $warehouse_id    Warehouse ID
	 * @param  int     $date_start      Start date timestamp
	 * @param  int     $date_end        End date timestamp
	 * @param  User    $user            User object
	 * @return array                    Results: ['success' => count, 'errors' => array]
	 */
	public function createMOBatch($suggestions, $warehouse_id, $date_start, $date_end, $user)
	{
		$success_count = 0;
		$error_details = array();

		// Sort by priority (highest level first)
		usort($suggestions, function ($a, $b) {
			return $b['priority'] - $a['priority'];
		});

		foreach ($suggestions as $suggestion) {
			$mo_id = $this->createMOFromSuggestion($suggestion, $warehouse_id, $date_start, $date_end, $user);
			
			if ($mo_id > 0) {
				$success_count++;
			} else {
				$error_details[] = array(
					'product_ref' => $suggestion['product_ref'],
					'error' => $this->error
				);
			}
		}

		return array(
			'success' => $success_count,
			'errors' => $error_details,
			'created_mos' => $this->created_mos
		);
	}

	/**
	 * Validate MO from suggestion data
	 *
	 * @param  int     $mo_id           MO ID
	 * @param  User    $user            User object
	 * @return int|false                Result >0 on success, false on error
	 */
	public function validateMO($mo_id, $user)
	{
		$mo = new Mo($this->db);
		$result = $mo->fetch($mo_id);
		
		if ($result <= 0) {
			$this->error = 'MO not found';
			return false;
		}

		$result = $mo->validate($user);

		if ($result <= 0) {
			$this->error = $mo->error;
			$this->errors = $mo->errors;
			return false;
		}

		return $result;
	}

	/**
	 * Get created MOs
	 *
	 * @return array List of created MOs
	 */
	public function getCreatedMOs()
	{
		return $this->created_mos;
	}
}
