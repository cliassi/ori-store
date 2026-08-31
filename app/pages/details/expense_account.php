<style type="text/css">
	.dragged {
		position: absolute;
		opacity: 0.4;
		z-index: 2000;
	}

	ol {
		background: #efefef;
		border-radius: 4px;
		padding: 4px;
		margin: 0 20px;
		min-height: 100px;
		min-width: 40px;
	}

	ol.example li.placeholder {
		position: relative;
	}

	ol.example li.placeholder:before {
		position: absolute;
	}

	ol {
		display: inline-block;
		vertical-align: top;
	}

	.editable {
		background: rgba(232, 244, 232, .4);
	}

	input[type=checkbox] {
		display: none;
	}

	tr:hover {
		background: #89CFF0CC !important;
	}

	tr td a {
		color: #000;
	}

	tr.depth-1 td.padded {
		padding-left: 18px !important;
		font-size: 1.05rem;
		font-weight: 700;
		text-decoration: underline;
	}

	tr.depth-2 td.padded {
		padding-left: 38px !important;
		font-size: 1rem;
		font-weight: 600;
	}

	tr.depth-3 td.padded {
		padding-left: 58px !important;
		font-size: 0.95rem;
		font-weight: 500;
	}

	tr.depth-4 td.padded {
		padding-left: 77px !important;
		font-size: 0.9rem;
		font-weight: 400;
	}

	tr.depth-5 td.padded {
		padding-left: 98px !important;
		font-size: 0.85rem
	}

	tr.depth-6 td.padded {
		padding-left: 118px !important;
		font-size: 0.8rem
	}

	tr.Credit td {
		background-color: #5cb85c55 !important;
	}

	tr.dragging {
		opacity: 0.4;
	}

	tr.drop-above td {
		border-top: 3px solid #0d6efd;
	}

	tr.drop-below td {
		border-bottom: 3px solid #0d6efd;
	}

	tr.drop-invalid td {
		border: 3px solid #dc3545;
		background-color: #dc354520 !important;
	}

	.branc {
		position: absolute;
		height: 49px;
		border-left: solid 2px #999;
		border-top: solid 2px #999;
		width: 20px;
		margin-top: 9px;
		border-bottom: solid 2px #999;
	}

	tr.depth-1 .branch {
		left: 100px;
	}

	tr.depth-2 .branch {
		left: 120px;
	}

	tr.depth-3 .branch {
		left: 140px;
	}

	tr.depth-4 .branch {
		left: 160px;
	}

	tr.depth-5 .branch {
		left: 180px;
	}

	tr.depth-6 .branch {
		left: 200px;
	}
</style>
<script src='<?php print $appurl; ?>/assets/jquery-sortable.js'></script>
<?php
if (isset($get->up)) {
	$item1 = R::load("expense_account", $get->up);
	if (nn($item1->parent)) {
		$prev = getMaxA("expense_account", "sortorder", "sortorder<'$item1->sortorder' AND parent=$item1->parent");
	} else {
		$prev = getMaxA("expense_account", "sortorder", "sortorder<'$item1->sortorder' AND parent IS NULL");
	}
	// vd($prev);
	if (nn($prev)) {
		$item2 = R::findOne("expense_account", "sortorder=?", [$prev]);
		$item1_so = $item1->sortorder;
		$item2_so = $item2->sortorder;
		$item1->sortorder = $item2_so;
		$item2->sortorder = $item1_so;
		// vd($item1);
		// vd($item2);
		R::store($item1);
		R::store($item2);
	}
	// dd(0);
	redir("?");
}
if (isset($get->down)) {
	$item1 = R::load("expense_account", $get->down);
	if (nn($item1->parent)) {
		$prev = getMinA("expense_account", "sortorder", "sortorder>'$item1->sortorder' AND parent=$item1->parent");
	} else {
		$prev = getMinA("expense_account", "sortorder", "sortorder>'$item1->sortorder' AND parent IS NULL");
	}
	// dd($prev);
	if (nn($prev)) {
		$item2 = R::findOne("expense_account", "sortorder=?", [$prev]);
		$item1_so = $item1->sortorder;
		$item2_so = $item2->sortorder;
		$item1->sortorder = $item2_so;
		$item2->sortorder = $item1_so;
		R::store($item1);
		R::store($item2);
	}
	redir("?");
}

if (isset($post->ajax_reorder)) {
	$order = isset($post->order) && is_array($post->order) ? $post->order : [];
	$i = 1;
	foreach ($order as $rid) {
		// Check if this is a top-level item (parent IS NULL)
		$account = R::load("expense_account", (int) $rid);
		if ($account && !$account->parent) {
			// Top-level items get sortorder starting from 0
			R::exec(
				"UPDATE expense_account SET sortorder = ? WHERE id = ?",
				[$i - 1, (int) $rid]
			);
		} else {
			// Child items get sequential sortorder starting from 1
			R::exec(
				"UPDATE expense_account SET sortorder = ? WHERE id = ?",
				[$i, (int) $rid]
			);
		}
		$i++;
	}

	header("Content-Type: application/json");
	echo json_encode(["status" => "ok"]);
	exit;
}
//$page = is("page", 1, "", FALSE);
$page = is("page", 1);
$offset = 1000;

$month = isset($get->month) ? $get->month : date("Y-m", time());

// Set History button URL and text based on t parameter using correct mapping from navigation
$historyUrl = '/store/expense_account_entry/view';
$historyText = 'History';
if (isset($get->t)) {
	if ($get->t == 'capex') {
		$historyUrl = '/store/expense_account_entry/view?page=1&accountid=1&pm=';
		$historyText = 'Capex History';
	} elseif ($get->t == 'opex') {
		$historyUrl = '/store/expense_account_entry/view?page=1&accountid=3&pm=';
		$historyText = 'Opex History';
	}
}
print "<div><a class='frht btn btn-danger' href='$historyUrl'>$historyText</a></div>";

$filter = "";
openFilterForm("get");
print "<input type='hidden' name='page' value='$page' class='form-control-fluid' />";
if (isset($get->name) && nn($get->name)) {
	joinFilter($filter, "name like '%$get->name%' AND ");
} else {
	$get->name = '';
}
print str("Name") . " <input type='text' name='name' id='filterkey' autofocus value='$get->name' class='form-control form-control-fluid w150' /> ";
// Set parent based on t parameter for proper OPEX/CAPEX filtering
if (isset($get->t)) {
	if ($get->t == 'opex') {
		$parent = '/3'; // OPEX accounts (company=3)
	} elseif ($get->t == 'capex') {
		$parent = '/1'; // CAPEX accounts (company=1)
	} else {
		$parent = isset($get->parent) ? $get->parent : '';
	}
} else {
	// Default to CAPEX when no t parameter
	$parent = '/1'; // CAPEX accounts (company=1)
}
$get->parent = $parent;
print "<input type='hidden' name='parent' value='$parent'>";
print "<input type='hidden' name='t' value='" . (isset($get->t) ? $get->t : '') . "'>";
print "Month " . monthSelector("month", "$month-01");
// print str("Parent")." ".sop2('parent', $parent, ['optional'=>true, 'filter'=>'id<3'], 'expense_account');		
// if($parent == 1){
// 	print "<a class='btn btn-primary' href='?page=2&name=&d=$d&t=$t&month=$month&parent=2'>Outsource</a>";
// }	else{
// 	print "<a class='btn btn-warning' href='?page=2&name=&d=$d&t=$t&month=$month&parent=1'>Hotel</a>";
print space(5);
closeFilterForm();
$userlist = userList();

// $nor = num_rows("a.*", "expense_account a", "$filter");
// $nop = ceil($nor/$offset);


$parentList = toA("expense_account");

print "<hr>";
print "<table align='center' class='table table-responsive table-striped'>
	<thead><tr><th></th><th>#</th><th>Name</th><th class='text-right'>Income</th><th class='text-right'>Expense</th><th class='text-right'>Balance</th><th></th></tr></thead>
	    <tbody class='sortable'>";

$i = 1; //$start + 1;
// <td class='padded'>{$parentList[$expense_account->parent]}</td>

$entryFilter = "(`month`='$month' OR expense_date LIKE '$month-%') AND ";
printExpenseAccount($filter, $i);

print "</tbody>
	<tfoot>";
// print paging(5, $nop, $nor, $page);
print "</tfoot>
	</table>";

function printExpenseAccount($_filter, $i, $parent = '')
{
	global $entryFilter;
	global $get;
	$month = isset($get->month) ? $get->month : date("Y-m", time());

	$filter = $_filter;
	if ($parent) {
		$filter .= " path LIKE '$get->parent/%' AND parent=$parent ";
	} else {
		$filter .= " path LIKE '$get->parent/%' AND parent IS NULL ";
	}

	$month_start = $month . '-01';
	$month_end = date('Y-m-t', strtotime($month_start));

	$expense_accounts = select("a.*,
			(SELECT IFNULL(SUM(IF(tran_type='Credit', amount, 0)),0) FROM `expense_account_entry` WHERE entry_time LIKE '$month-%' AND accountpath LIKE CONCAT(a.path,'%')) +
			IF(a.path = '/1/', IFNULL((SELECT SUM(ii.quantity * (ii.price - ii.cost)) FROM invoice i INNER JOIN invoice_item ii ON i.id = ii.invoice_id WHERE i.invoice_date BETWEEN '$month_start' AND '$month_end'), 0), 0) income,
			(SELECT IFNULL(SUM(IF(tran_type='Debit', amount, 0)),0) FROM `expense_account_entry` WHERE $entryFilter accountpath LIKE CONCAT(a.path,'%')) expense", "expense_account a", "$filter", "order by sortorder");

	while ($expense_account = mysqli_fetch_object($expense_accounts)) {
		// $depth = substr_count($expense_account->path, "/") - 2;
		print "<tr class='$expense_account->type depth-$expense_account->depth' data-breadcrumbs='$expense_account->breadcrumbs' draggable='true' data-id='$expense_account->id'>
			<td style='line-height: 1; width:50px;'>
				<i class='fa fa-grip-vertical' style='color: #999; font-size: 12px; cursor: move;'></i>
				<a href='?up=$expense_account->id' style='margin-left: 5px;'><i class='fa fa-chevron-up'></i></a>
				<a href='?down=$expense_account->id' style='margin-left: 2px;'><i class='fa fa-chevron-down'></i></a>
			</td>
			<td><a href='view/$expense_account->id'>$i</a></td>
			<td class='padded'>
				<div class='branch'></div>
				<a href='/store/expense_account_entry/view?accountid=$expense_account->id'>$expense_account->name</a><span class='hidden'>$expense_account->breadcrumbs</span>";
		if (strpos($expense_account->path, "/2/") === FALSE || strpos($expense_account->path, '/2/358/') !== FALSE) {
			print "<a href='/store/expense_account_entry/add?t=" . (isset($get->t) ? $get->t : '') . "&a=$expense_account->id' class='frht btn btn-warning btn-sm' style='padding: 5px 20px; margin-left:15px'><i class='fas fa-money-bill'></i></a>";
		}
		print "<a href='add?t=" . (isset($get->t) ? $get->t : '') . "&parent=$expense_account->id' class='frht btn btn-info btn-sm' style='padding: 5px 20px;'><i class='fa fa-plus-circle'></i></a>";
		print "</td>";
		// <td class='padded'>$expense_account->breadcrumbs";
		// print "";
		// print "</td>";"
		print "<td class='rht'>" . nfz($expense_account->income) . "</td>
	    <td class='rht'>" . nfz($expense_account->expense) . "</td>
	    <td class='rht'>" . nfz($expense_account->income - $expense_account->expense) . "</td>";
		// Check if expense account has associated entries
		$hasEntriesQuery = "SELECT COUNT(*) as count FROM expense_account_entry WHERE accountpath LIKE CONCAT(?, '%') OR accountid = ?";
		$hasEntriesResult = R::getRow($hasEntriesQuery, [$expense_account->path, $expense_account->id]);
		$hasEntries = $hasEntriesResult['count'] > 0;

		print "<td>";
		// Show delete button only if no associated entries
		if (!$hasEntries) {
			print "<a href='remove/$expense_account->id?company=" . (isset($get->company) ? $get->company : '') . "&t=" . (isset($get->t) ? $get->t : '') . "'><i class='fas fa-trash'></i></a> ";
		} else {
			// Show clickable delete button with JavaScript alert
			print "<a href='javascript:void(0)' onclick='alert(\"Cannot delete expense account as it has previous data\")' style='color: #999;' title='Expense Account Entry has previous month data therefore you cannot delete it'><i class='fas fa-trash'></i></a> ";
		}
		if (isUserIn(['superadmin'])) {
			$eyePath = urlencode($expense_account->path);
			$eyeCompany = isset($get->company) ? urlencode($get->company) : '';
			$eyeT = isset($get->t) ? urlencode($get->t) : '';
			print "<a href='/store/report/cash?cw=$eyeCompany&account_path=$eyePath' target='_blank' title='View in Cash Report'><i class='fa fa-eye' style='color: #5cb85c; margin-left: 5px;'></i></a> ";
		}
		print "<a href='edit/$expense_account->id?company=" . (isset($get->company) ? $get->company : '') . "&t=" . (isset($get->t) ? $get->t : '') . "'><i class='fas fa-edit'></i></a></td>";
		// <td>".options2("", $expense_account->id, array("edit", "remove","erase"))."</td>
		print "</tr>";
		$i++;
		$i = printExpenseAccount($_filter, $i, $expense_account->id);
	}
	return $i;
}

?>

<script>
	(function () {
		const tbody = document.querySelector('tbody.sortable');
		if (!tbody) {
			return;
		}

		function getRowDepth(row) {
			const classList = row.className.split(' ');
			const depthClass = classList.find(cls => cls.startsWith('depth-'));
			return depthClass ? parseInt(depthClass.replace('depth-', '')) : 0;
		}

		function getRowId(row) {
			return parseInt(row.dataset.id);
		}

		function getRowPath(row) {
			return row.dataset.breadcrumbs || '';
		}

		function isChildOf(childRow, parentRow) {
			const childPath = getRowPath(childRow);
			const parentPath = getRowPath(parentRow);
			return childPath.startsWith(parentPath + ' > ') || childPath === parentPath;
		}

		function getAllDescendants(parentRow) {
			const parentId = getRowId(parentRow);
			const parentPath = getRowPath(parentRow);
			const descendants = [];
			const allRows = [...tbody.querySelectorAll('tr[data-id]')];
			const parentIndex = allRows.indexOf(parentRow);

			// Get all rows that come after parent and have greater depth
			for (let i = parentIndex + 1; i < allRows.length; i++) {
				const row = allRows[i];
				const rowPath = getRowPath(row);
				if (rowPath.startsWith(parentPath + ' > ')) {
					descendants.push(row);
				} else {
					break; // Stop when we reach a row at same or higher level
				}
			}
			return descendants;
		}

		function canDrop(draggingRow, targetRow, position) {
			const draggingDepth = getRowDepth(draggingRow);
			const targetDepth = getRowDepth(targetRow);
			const draggingId = getRowId(draggingRow);
			const targetId = getRowId(targetRow);

			// Can't drop on itself
			if (draggingId === targetId) {
				return false;
			}

			// Can't drop parent on its own child
			if (isChildOf(targetRow, draggingRow)) {
				return false;
			}

			// Child can only be dragged to same level or within same parent
			if (draggingDepth > 1) {
				const draggingParent = findParentRow(draggingRow);
				if (draggingParent) {
					// If dropping at top level (no target), check if allowed
					if (!targetRow) {
						return false; // Children can't be dragged to top level
					}

					// Can only drop within same parent or at same level
					const targetParent = findParentRow(targetRow);
					if (targetParent && getRowId(targetParent) !== getRowId(draggingParent)) {
						return false; // Different parents
					}
				}
			}

			return true;
		}

		function findParentRow(row) {
			const rowDepth = getRowDepth(row);
			if (rowDepth <= 1) return null;

			const allRows = [...tbody.querySelectorAll('tr[data-id]')];
			const rowIndex = allRows.indexOf(row);

			// Look backwards for a row with depth = current depth - 1
			for (let i = rowIndex - 1; i >= 0; i--) {
				const candidateRow = allRows[i];
				if (getRowDepth(candidateRow) === rowDepth - 1) {
					return candidateRow;
				}
			}
			return null;
		}

		function getDragAfterElement(container, y) {
			const rows = [...container.querySelectorAll('tr:not(.dragging)')];
			let closest = { offset: Number.NEGATIVE_INFINITY, element: null };

			for (const row of rows) {
				const rect = row.getBoundingClientRect();
				const offset = y - (rect.top + rect.height / 2);
				if (offset < 0 && offset > closest.offset) {
					closest = { offset, element: row };
				}
			}
			return closest.element;
		}

		function clearDropIndicators() {
			tbody.querySelectorAll('.drop-above, .drop-below, .drop-invalid').forEach((row) => {
				row.classList.remove('drop-above', 'drop-below', 'drop-invalid');
			});
		}

		function reorderNumbering() {
			let n = 1;
			tbody.querySelectorAll('tr').forEach((tr) => {
				const secondCell = tr.querySelector('td:nth-child(2)');
				if (secondCell) {
					const link = secondCell.querySelector('a');
					if (link) {
						link.textContent = n++;
					}
				}
			});
		}

		function saveOrder() {
			const ids = [...tbody.querySelectorAll('tr[data-id]')].map((row) => row.dataset.id);
			if (!ids.length) {
				return;
			}

			const params = new URLSearchParams();
			params.append('ajax_reorder', '1');
			ids.forEach((id) => params.append('order[]', id));

			fetch(window.location.href, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params.toString()
			})
				.then((response) => response.json())
				.then((data) => {
					if (data.status !== 'ok') {
						console.warn('Reorder save failed', data);
					} else {
						// Show success message
						const toast = document.createElement('div');
						toast.className = 'alert alert-success position-fixed top-0 end-0 m-3';
						toast.style.zIndex = '9999';
						toast.textContent = 'Order updated successfully!';
						document.body.appendChild(toast);
						setTimeout(() => toast.remove(), 3000);
					}
				})
				.catch((err) => {
					console.error('Reorder AJAX error', err);
					// Show error message
					const toast = document.createElement('div');
					toast.className = 'alert alert-danger position-fixed top-0 end-0 m-3';
					toast.style.zIndex = '9999';
					toast.textContent = 'Error updating order!';
					document.body.appendChild(toast);
					setTimeout(() => toast.remove(), 3000);
				});
		}

		let draggingRow = null;
		let draggingDescendants = [];

		tbody.addEventListener('dragstart', (e) => {
			if (['A', 'INPUT', 'BUTTON', 'IMG', 'I'].includes(e.target.tagName)) {
				e.preventDefault();
				return;
			}

			const tr = e.target.closest('tr[draggable="true"]');
			if (!tr) {
				return;
			}

			draggingRow = tr;
			draggingDescendants = getAllDescendants(tr);

			// Add dragging class to parent and all descendants
			tr.classList.add('dragging');
			draggingDescendants.forEach(desc => desc.classList.add('dragging'));

			e.dataTransfer.effectAllowed = 'move';
			e.dataTransfer.setData('text/plain', tr.dataset.id);
		});

		tbody.addEventListener('dragend', () => {
			if (!draggingRow) {
				return;
			}

			// Remove dragging class from parent and descendants
			draggingRow.classList.remove('dragging');
			draggingDescendants.forEach(desc => desc.classList.remove('dragging'));

			clearDropIndicators();
			reorderNumbering();
			saveOrder();
			draggingRow = null;
			draggingDescendants = [];
		});

		tbody.addEventListener('dragover', (e) => {
			e.preventDefault();
			if (!draggingRow) {
				return;
			}

			const after = getDragAfterElement(tbody, e.clientY);
			clearDropIndicators();

			// Check if drop is allowed
			if (after && !canDrop(draggingRow, after, 'above')) {
				after.classList.add('drop-invalid');
				return;
			}

			if (after == null) {
				// Check if can drop at top level
				if (canDrop(draggingRow, null, 'bottom')) {
					// Move parent and all its children to bottom
					tbody.appendChild(draggingRow);
					draggingDescendants.forEach(desc => tbody.appendChild(desc));

					const last = tbody.querySelector('tr:last-child');
					if (last && last !== draggingRow) {
						last.classList.add('drop-below');
					}
				} else {
					// Show invalid drop indicator
					const firstRow = tbody.querySelector('tr:first-child');
					if (firstRow) {
						firstRow.classList.add('drop-invalid');
					}
				}
			} else {
				// Insert parent and all its children before target
				tbody.insertBefore(draggingRow, after);
				draggingDescendants.forEach(desc => tbody.insertBefore(desc, after));
				after.classList.add('drop-above');
			}
		});
	})();

	$("#filterkey").keyup(function () {
		var key = $(this).val();
		$('tr').hide();
		$('tr').filter(function () {
			return this.innerHTML.toLowerCase().includes(key.toLowerCase());
		}).show()
		console.log(key);
	});
</script>