<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .file-card:hover .actions { opacity: 1; }
        .actions { opacity: 0; transition: opacity 0.2s; }
        body { background-color: #f3f4f6; }
        /* Ensure modals appear above framework UI */
        .modal-z { z-index: 99999 !important; }
        /* Smooth hiding for filtered items */
        .item-hidden { display: none !important; }
    </style>
</head>
<body class="min-h-screen text-gray-800">

<?php
/**
 * ARCHIVE SYSTEM LOGIC - FRAMEWORK COMPATIBLE (FOR INDEX.PHP ROUTER)
 */

// Start session for status messages if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. FRAMEWORK ADAPTER
$req_get = isset($get) ? (array)$get : (isset($_GET) ? $_GET : []);
$req_post = isset($post) ? (array)$post : (isset($_POST) ? $_POST : []);

// 2. SETUP PATHS
$base_path = __DIR__ . DIRECTORY_SEPARATOR . 'archive';

if (!file_exists($base_path)) {
    mkdir($base_path, 0777, true);
}

// 3. IDENTIFY CURRENT DIRECTORY FROM $get->dir
$current_dir_param = isset($req_get['dir']) ? $req_get['dir'] : '';
$clean_dir = str_replace(['..', './', '\\'], '', $current_dir_param);
$clean_dir = trim($clean_dir, '/');

// Final absolute path for PHP file operations
$full_server_path = $clean_dir ? $base_path . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean_dir) : $base_path;

/**
 * Recursive delete function for folders
 */
function deleteRecursive($path) {
    if (is_dir($path)) {
        $files = array_diff(scandir($path), array('.', '..'));
        foreach ($files as $file) {
            deleteRecursive($path . DIRECTORY_SEPARATOR . $file);
        }
        return rmdir($path);
    } else if (file_exists($path)) {
        return unlink($path);
    }
    return false;
}

/**
 * Calculate total directory size recursively
 */
function getDirectorySize($path) {
    $size = 0;
    if (is_dir($path)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
            $size += $object->getSize();
        }
    }
    return $size;
}

/**
 * Format bytes to human readable format
 */
function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}

/**
 * Utility to find DB connection
 */
function get_db_connection() {
    foreach ($GLOBALS as $val) {
        if ($val instanceof mysqli) return $val;
    }
    global $db, $conn, $link, $mysqli;
    if (isset($db)) return $db;
    if (isset($conn)) return $conn;
    if (isset($link)) return $link;
    if (isset($mysqli)) return $mysqli;
    return null;
}

// 4. HANDLE ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect_url = "/store/" . (defined('PAGE') ? PAGE : 'company') . "?dir=" . urlencode($clean_dir);
    
    // Action: Create Folder
    if (isset($req_post['create_folder'])) {
        $folder_name = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $req_post['folder_name']);
        $new_path = $full_server_path . DIRECTORY_SEPARATOR . $folder_name;
        
        if (!file_exists($new_path)) {
            if (mkdir($new_path, 0777, true)) {
                $_SESSION['archive_msg'] = ['type' => 'success', 'text' => "Folder created."];
            }
        }
        echo "<script>window.location.href = '$redirect_url';</script>";
        exit;
    }

    // Action: Upload File
    if (isset($req_post['upload_file']) && isset($_FILES['file'])) {
        $file_name = basename($_FILES["file"]["name"]);
        $target_path = $full_server_path . DIRECTORY_SEPARATOR . $file_name;
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_path)) {
            chmod($target_path, 0666);
            $_SESSION['archive_msg'] = ['type' => 'success', 'text' => "File uploaded."];
        }
        echo "<script>window.location.href = '$redirect_url';</script>";
        exit;
    }

    // Action: Rename Item
    if (isset($req_post['rename_item']) && !empty($req_post['old_name']) && !empty($req_post['new_name'])) {
        $old_name = basename($req_post['old_name']);
        $new_name = preg_replace('/[^a-zA-Z0-9_\- \.]/', '', $req_post['new_name']);
        $old_path = $full_server_path . DIRECTORY_SEPARATOR . $old_name;
        $new_path = $full_server_path . DIRECTORY_SEPARATOR . $new_name;

        if (file_exists($old_path) && !file_exists($new_path)) {
            if (rename($old_path, $new_path)) {
                $_SESSION['archive_msg'] = ['type' => 'success', 'text' => "Renamed successfully."];
            }
        } else {
            $_SESSION['archive_msg'] = ['type' => 'error', 'text' => "Rename failed: File exists or not found."];
        }
        echo "<script>window.location.href = '$redirect_url';</script>";
        exit;
    }

    // Action: Delete Item (PIN required)
    if (isset($req_post['delete_item']) && !empty($req_post['item_name']) && !empty($req_post['user_pin'])) {
        $target_item = basename($req_post['item_name']);
        $delete_path = $full_server_path . DIRECTORY_SEPARATOR . $target_item;
        $input_pin = trim($req_post['user_pin']);

        $db_conn = get_db_connection();
        $is_valid_pin = false;
        
        if ($db_conn) {
            $check_pin = $db_conn->query("SELECT u_pin FROM sys_user WHERE id = 1 LIMIT 1");
            if ($check_pin && $check_pin->num_rows > 0) {
                $user_data = $check_pin->fetch_assoc();
                if ($user_data['u_pin'] == $input_pin) {
                    $is_valid_pin = true;
                }
            }
        } else {
            $_SESSION['archive_msg'] = ['type' => 'error', 'text' => "Database connection error."];
            echo "<script>window.location.href = '$redirect_url';</script>";
            exit;
        }

        if ($is_valid_pin) {
            if (file_exists($delete_path)) {
                if (deleteRecursive($delete_path)) {
                    $_SESSION['archive_msg'] = ['type' => 'success', 'text' => "Deleted successfully."];
                }
            }
        } else {
            $_SESSION['archive_msg'] = ['type' => 'error', 'text' => "Invalid Administrator PIN."];
        }

        echo "<script>window.location.href = '$redirect_url';</script>";
        exit;
    }
}

// 5. SCAN DIRECTORY
$items = [];
if (is_dir($full_server_path)) {
    $scanned = array_diff(scandir($full_server_path), array('..', '.'));
    foreach ($scanned as $item) {
        $abs_path = $full_server_path . DIRECTORY_SEPARATOR . $item;
        $is_dir = is_dir($abs_path);
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        
        $type = $is_dir ? 'folder' : 'file';
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) $type = 'image';

        $items[] = [
            'name' => $item,
            'type' => $type,
            'rel_link' => ($clean_dir ? $clean_dir . '/' : '') . $item,
            'web_path' => '/store/app/pages/archive/' . ($clean_dir ? $clean_dir . '/' : '') . $item
        ];
    }
}

// Calculate total used space
$total_used_bytes = getDirectorySize($base_path);
$total_used_formatted = formatSizeUnits($total_used_bytes);
?>

<div class="max-w-6xl mx-auto p-4 md:p-8">
    <!-- Status Message -->
    <?php if (isset($_SESSION['archive_msg'])): ?>
        <div class="mb-6 p-4 rounded-lg flex items-center justify-between <?php echo $_SESSION['archive_msg']['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> border-l-4 <?php echo $_SESSION['archive_msg']['type'] === 'success' ? 'border-green-500' : 'border-red-500'; ?>">
            <div class="flex items-center gap-3">
                <i class="fa <?php echo $_SESSION['archive_msg']['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <span class="font-medium"><?php echo $_SESSION['archive_msg']['text']; ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-lg opacity-50 hover:opacity-100">&times;</button>
        </div>
        <?php unset($_SESSION['archive_msg']); ?>
    <?php endif; ?>

    <!-- Header -->
    <header class="mb-8 flex flex-col md:flex-row md:items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h1 class="text-3xl font-bold text-gray-900">File Manager</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-200 text-gray-700 text-xs font-bold border border-gray-300">
                    <i class="fa fa-hdd text-[10px]"></i>
                    <?php echo $total_used_formatted; ?>
                </span>
            </div>
            <p class="text-gray-500 flex items-center gap-2">
                <i class="fa fa-folder-open text-amber-500"></i>
                <span class="font-mono text-sm">/ <?php echo $clean_dir ? str_replace('/', ' / ', $clean_dir) : 'root'; ?></span>
            </p>
        </div>
        
        <div class="flex flex-col md:flex-row items-center gap-3">
            <!-- Search / Filter Input -->
            <div class="relative w-full md:w-64">
                <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" id="archiveSearch" onkeyup="filterItems()" placeholder="Search in this folder..." 
                       class="w-full pl-9 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm">
            </div>

            <div class="flex items-center gap-2 w-full md:w-auto">
                <button onclick="toggleModal('folderModal')" class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm font-medium">
                    <i class="fa fa-folder-plus text-amber-500"></i> <span class="md:hidden lg:inline">Folder</span>
                </button>
                <button onclick="toggleModal('uploadModal')" class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-sm font-medium">
                    <i class="fa fa-cloud-upload"></i> <span class="md:hidden lg:inline">Upload</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Explorer Grid -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <div class="flex items-center">
                <?php if ($clean_dir): ?>
                <button onclick="goBack()" class="p-2 hover:bg-gray-200 rounded-full transition text-gray-600 mr-4">
                    <i class="fa fa-arrow-left"></i>
                </button>
                <?php endif; ?>
                <span class="text-sm font-medium text-gray-600">Items: <span id="itemCountDisplay"><?php echo count($items); ?></span></span>
            </div>
            <div id="noResultsMsg" class="hidden text-xs text-red-500 font-medium">
                No matches found
            </div>
        </div>

        <div id="explorerGrid" class="p-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
            <?php if (count($items) > 0): ?>
                <?php foreach ($items as $item): ?>
                    <div class="file-card group flex flex-col items-center p-4 rounded-xl hover:bg-blue-50 transition cursor-pointer relative" 
                         data-name="<?php echo strtolower($item['name']); ?>"
                         onclick="<?php echo $item['type'] == 'folder' ? "navigateTo('".$item['rel_link']."')" : "viewFile('".$item['web_path']."')"; ?>">
                        
                        <div class="w-16 h-16 mb-2 flex items-center justify-center">
                            <?php if($item['type'] == 'folder'): ?>
                                <i class="fa fa-folder text-amber-400 text-5xl"></i>
                            <?php elseif($item['type'] == 'image'): ?>
                                <i class="fa fa-file-image text-blue-400 text-5xl"></i>
                            <?php else: ?>
                                <i class="fa fa-file text-gray-400 text-5xl"></i>
                            <?php endif; ?>
                        </div>
                        
                        <span class="text-xs font-semibold text-center truncate w-full px-2" title="<?php echo $item['name']; ?>">
                            <?php echo $item['name']; ?>
                        </span>

                        <!-- Action Buttons -->
                        <div class="actions absolute top-1 right-1 flex flex-col gap-1">
                            <button onclick="openRenameModal('<?php echo addslashes($item['name']); ?>', event)" class="p-1.5 bg-white text-blue-500 rounded-lg shadow-sm hover:bg-blue-50 border border-gray-100 transition">
                                <i class="fa fa-edit text-xs"></i>
                            </button>
                            <button onclick="openDeleteModal('<?php echo addslashes($item['name']); ?>', event)" class="p-1.5 bg-white text-red-500 rounded-lg shadow-sm hover:bg-red-50 border border-gray-100 transition">
                                <i class="fa fa-trash-alt text-xs"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full py-20 text-center text-gray-400">
                    <p>This folder is currently empty.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: New Folder -->
<div id="folderModal" class="hidden fixed inset-0 modal-z flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-xl font-bold mb-4 text-gray-900">Create Folder</h3>
        <form method="POST">
            <input type="hidden" name="create_folder" value="1">
            <input type="text" name="folder_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg mb-6 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Folder Name" required autofocus>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="toggleModal('folderModal')" class="px-4 py-2 text-gray-500 hover:text-gray-700">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Upload -->
<div id="uploadModal" class="hidden fixed inset-0 modal-z flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-xl font-bold mb-4 text-gray-900">Upload File</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="upload_file" value="1">
            <div class="border-2 border-dashed border-gray-200 rounded-lg p-8 mb-6 text-center hover:border-blue-400 transition">
                <input type="file" name="file" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="toggleModal('uploadModal')" class="px-4 py-2 text-gray-500 hover:text-gray-700">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium">Upload Now</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Rename -->
<div id="renameModal" class="hidden fixed inset-0 modal-z flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-xl font-bold mb-2 text-gray-900">Rename Item</h3>
        <p class="text-xs text-gray-500 mb-4">Enter a new name for the item below.</p>
        <form method="POST">
            <input type="hidden" name="rename_item" value="1">
            <input type="hidden" name="old_name" id="renameOldName">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">New Name</label>
                    <input type="text" name="new_name" id="renameNewName" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" required>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="toggleModal('renameModal')" class="px-4 py-2 text-gray-500 hover:text-gray-700">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium">Apply Change</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Delete Confirmation -->
<div id="deleteModal" class="hidden fixed inset-0 modal-z flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 border-t-4 border-red-500">
        <h3 class="text-xl font-bold mb-2 text-gray-900">Security Check</h3>
        <p class="text-sm text-gray-500 mb-6">You are about to delete <span id="deleteItemNameDisplay" class="font-bold text-gray-800"></span>. Please enter your Security PIN to continue.</p>
        <form method="POST">
            <input type="hidden" name="delete_item" value="1">
            <input type="hidden" name="item_name" id="deleteItemNameValue">
            <div class="mb-6">
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2 tracking-wider">Admin PIN</label>
                <input type="password" name="user_pin" maxlength="6" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-center text-2xl tracking-[1em] focus:ring-2 focus:ring-red-500 outline-none" required placeholder="••••••" autocomplete="off">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="toggleModal('deleteModal')" class="px-4 py-2 text-gray-500 hover:text-gray-700">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg font-medium shadow-sm hover:bg-red-700">Delete Permanently</button>
            </div>
        </form>
    </div>
</div>

<script>
    const BASE_ROUTE = window.location.origin + window.location.pathname;

    /**
     * Filters items in the grid based on search input
     */
    function filterItems() {
        const query = document.getElementById('archiveSearch').value.toLowerCase();
        const cards = document.querySelectorAll('#explorerGrid .file-card');
        const noResults = document.getElementById('noResultsMsg');
        const countDisplay = document.getElementById('itemCountDisplay');
        
        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.getAttribute('data-name');
            if (name.includes(query)) {
                card.classList.remove('item-hidden');
                visibleCount++;
            } else {
                card.classList.add('item-hidden');
            }
        });

        // Update UI states
        countDisplay.innerText = visibleCount;
        if (visibleCount === 0 && query !== "") {
            noResults.classList.remove('hidden');
        } else {
            noResults.classList.add('hidden');
        }
    }

    function toggleModal(id) {
        document.getElementById(id).classList.toggle('hidden');
    }

    function navigateTo(path) {
        window.location.href = BASE_ROUTE + "?dir=" + encodeURIComponent(path);
    }

    function viewFile(path) {
        window.open(path, '_blank');
    }

    function openRenameModal(name, event) {
        event.stopPropagation();
        document.getElementById('renameOldName').value = name;
        document.getElementById('renameNewName').value = name;
        toggleModal('renameModal');
        setTimeout(() => document.getElementById('renameNewName').focus(), 100);
    }

    function openDeleteModal(name, event) {
        event.stopPropagation();
        document.getElementById('deleteItemNameDisplay').innerText = name;
        document.getElementById('deleteItemNameValue').value = name;
        toggleModal('deleteModal');
        setTimeout(() => {
            const pinInput = document.querySelector('#deleteModal input[name="user_pin"]');
            if (pinInput) pinInput.focus();
        }, 100);
    }

    function goBack() {
        const urlParams = new URLSearchParams(window.location.search);
        const dir = urlParams.get('dir') || '';
        if (dir) {
            const parts = dir.split('/').filter(p => p.length > 0);
            parts.pop();
            const parent = parts.join('/');
            window.location.href = BASE_ROUTE + (parent ? "?dir=" + encodeURIComponent(parent) : "");
        } else {
            window.history.back();
        }
    }
</script>
</body>
</html>