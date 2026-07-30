<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* RESET & BASE */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        /* MAIN CONTAINER */
        .fm-container {
            display: flex;
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* LEFT CONTENT AREA */
        .fm-content {
            flex: 1;
        }

        /* HEADER */
        .fm-header {
            margin-bottom: 15px;
        }

        .fm-header h1 {
            color: #444;
            font-size: 28px;
            margin-bottom: 5px;
            display: inline-block;
        }

        .fm-header .storage-badge {
            display: inline-block;
            background: #444;
            color: #ccc;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 10px;
            vertical-align: middle;
        }

        .fm-header .breadcrumb {
            color: #888;
            font-size: 13px;
            margin-top: 5px;
        }

        .fm-header .breadcrumb i {
            color: #F0AD4E;
            margin-right: 5px;
        }

        /* SEARCH BAR */
        .fm-search-box {
            margin-bottom: 15px;
            display: flex;
            justify-content: flex-end;
        }

        .fm-search-box input {
            padding: 8px 12px;
            border: 1px solid #ccc;
            /* background: #333; */
            /* color: #fff; */
            border-radius: 4px;
            width: 250px;
            font-size: 13px;
        }

        .fm-search-box input::placeholder {
            color: #777;
        }

        /* ITEM GRID */
        .fm-grid {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 8px !important;
            margin-bottom: 20px !important;
            width: 100% !important;
        }

        /* ITEM ROW - Common */
        .fm-item {
            display: flex !important;
            align-items: center !important;
            height: 36px !important;
            border-radius: 3px !important;
            overflow: hidden !important;
            cursor: pointer !important;
            transition: opacity 0.15s !important;
            width: 100% !important;
        }

        .fm-item:hover {
            opacity: 0.9;
        }

        .fm-item-name {
            flex: 1;
            padding: 0 10px;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: uppercase;
        }

        .fm-item-name i {
            margin-right: 6px;
            font-size: 11px;
        }

        /* FOLDER */
        .fm-folder {
            background-color: #F0AD4E;
            cursor: pointer;
        }

        /* FOLDER HEADER (when inside a folder or root section) */
        .fm-section-header {
            display: flex !important;
            align-items: center !important;
            background-color: #ccc !important;
            color: #333 !important;
            padding: 0 10px !important;
            height: 42px !important;
            border-radius: 6px !important;
            margin-bottom: 12px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
        }

        .fm-section-header .fm-item-name {
            color: #333 !important;
            cursor: default !important;
        }

        .fm-folder-header {
            display: flex !important;
            align-items: center !important;
            background-color: #F0AD4E !important;
            color: #fff !important;
            padding: 0 10px !important;
            height: 42px !important;
            border-radius: 6px !important;
            margin-bottom: 12px !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            cursor: default !important;
        }

        .fm-folder-header .fm-item-name {
            color: #fff !important;
            cursor: pointer !important;
        }

        /* FILE */
        .fm-file {
            background-color: #5CB85C;
        }

        /* DELETE BUTTON */
        .fm-delete {
            width: 36px !important;
            height: 100% !important;
            background-color: #FF4500 !important;
            color: #fff !important;
            border: none !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 14px !important;
            transition: background-color 0.15s !important;
            flex-shrink: 0 !important;
        }

        .fm-delete:hover {
            background-color: #e03e00;
        }

        /* SIDEBAR */
        .fm-sidebar {
            width: 300px;
            flex-shrink: 0;
        }

        .fm-box {
            /* background: #2a2a2a; */
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .fm-box input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            /* background: #333; */
            /* color: #fff; */
            border-radius: 3px;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .fm-box input[type="text"]::placeholder {
            color: #888;
        }

        .fm-box input[type="file"] {
            width: 100%;
            margin-bottom: 8px;
            color: #ccc;
            font-size: 12px;
        }

        .fm-box .file-status {
            color: #888;
            font-size: 11px;
            margin-bottom: 8px;
        }

        .fm-btn {
            width: 100%;
            padding: 8px;
            border: none;
            border-radius: 3px;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s;
        }

        .fm-btn:hover {
            opacity: 0.9;
        }

        .fm-btn-amber {
            background-color: #F0AD4E;
        }

        .fm-btn-green {
            background-color: #5CB85C;
        }

        /* STATUS MESSAGE */
        .fm-msg {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .fm-msg-success {
            background: #1a472a;
            color: #4ade80;
            border-left: 4px solid #22c55e;
        }

        .fm-msg-error {
            background: #4a1818;
            color: #fca5a5;
            border-left: 4px solid #ef4444;
        }

        .fm-msg button {
            background: none;
            border: none;
            color: inherit;
            font-size: 18px;
            cursor: pointer;
            margin-left: 10px;
        }

        /* EMPTY STATE */
        .fm-empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 14px;
        }

        /* MODALS */
        .fm-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 100000;
            align-items: center;
            justify-content: center;
        }

        .fm-modal-overlay.active {
            display: flex;
        }

        .fm-modal {
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 6px;
            padding: 25px;
            width: 90%;
            max-width: 400px;
        }

        .fm-modal h3 {
            color: #fff;
            margin-bottom: 5px;
            font-size: 18px;
        }

        .fm-modal p {
            color: #aaa;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .fm-modal label {
            display: block;
            color: #888;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .fm-modal input {
            width: 100%;
            padding: 10px;
            border: 1px solid #555;
            background: #333;
            color: #fff;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .fm-modal .fm-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .fm-modal button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }

        .fm-modal button.cancel {
            background: transparent;
            color: #888;
        }

        .fm-modal button.cancel:hover {
            color: #fff;
        }

        .fm-modal button.confirm-blue {
            background: #3b82f6;
            color: #fff;
        }

        .fm-modal button.confirm-red {
            background: #ef4444;
            color: #fff;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .fm-container {
                flex-direction: column;
            }

            .fm-sidebar {
                width: 100%;
            }

            .fm-grid {
                grid-template-columns: 1fr;
            }

            body {
                padding: 10px;
            }
        }
    </style>
</head>

<body>

    <?php
    /**
     * ARCHIVE SYSTEM LOGIC - FRAMEWORK COMPATIBLE (FOR INDEX.PHP ROUTER)
     */

    // Start session for status messages if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. FRAMEWORK ADAPTER
    $req_get = isset($get) ? (array) $get : (isset($_GET) ? $_GET : []);
    $req_post = isset($post) ? (array) $post : (isset($_POST) ? $_POST : []);

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
    function deleteRecursive($path)
    {
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
    function getDirectorySize($path)
    {
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
    function formatSizeUnits($bytes)
    {
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
    function get_db_connection()
    {
        foreach ($GLOBALS as $val) {
            if ($val instanceof mysqli)
                return $val;
        }
        global $db, $conn, $link, $mysqli;
        if (isset($db))
            return $db;
        if (isset($conn))
            return $conn;
        if (isset($link))
            return $link;
        if (isset($mysqli))
            return $mysqli;
        return null;
    }

    // 4. HANDLE ACTIONS
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $redirect_url = "/factory/" . (defined('PAGE') ? PAGE : 'company') . "?dir=" . urlencode($clean_dir);

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

    // 5. SCAN DIRECTORY - FOLDERS FIRST, THEN FILES
    $folders = [];
    $files = [];
    if (is_dir($full_server_path)) {
        $scanned = array_diff(scandir($full_server_path), array('..', '.'));
        foreach ($scanned as $item) {
            $abs_path = $full_server_path . DIRECTORY_SEPARATOR . $item;
            $is_dir = is_dir($abs_path);
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));

            $type = $is_dir ? 'folder' : 'file';
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                $type = 'image';

            $itemData = [
                'name' => $item,
                'type' => $type,
                'rel_link' => ($clean_dir ? $clean_dir . '/' : '') . $item,
                'web_path' => '/factory/app/pages/archive/' . ($clean_dir ? $clean_dir . '/' : '') . $item
            ];

            if ($is_dir) {
                $folders[] = $itemData;
            } else {
                $files[] = $itemData;
            }
        }
    }
    // Combine: folders first, then files
    $items = array_merge($folders, $files);

    // Calculate total used space
    $total_used_bytes = getDirectorySize($base_path);
    $total_used_formatted = formatSizeUnits($total_used_bytes);
    ?>

    <div id="fm-wrapper" style="position:relative;z-index:1;">
        <!-- Main Content -->
        <div class="fm-content">
            <!-- Header -->
            <!-- <div class="fm-header">
                <h1>File Manager <span class="storage-badge"><i class="fa fa-hdd"
                            style="margin-right:4px;font-size:10px;"></i><?php echo $total_used_formatted; ?></span>
                </h1>
                <div class="breadcrumb">
                    <i class="fa fa-folder-open"></i>
                    <a href="company.php" style="color:#337ab7;text-decoration:none;">/</a>
                    <?php if ($clean_dir): ?>
                        <?php echo str_replace('/', ' / ', $clean_dir); ?>
                    <?php endif; ?>
                </div>
            </div> -->

            <!-- Status Message -->
            <?php if (isset($_SESSION['archive_msg'])): ?>
                <div class="fm-msg fm-msg-<?php echo $_SESSION['archive_msg']['type']; ?>">
                    <div>
                        <i class="fa <?php echo $_SESSION['archive_msg']['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"
                            style="margin-right:8px;"></i>
                        <?php echo $_SESSION['archive_msg']['text']; ?>
                    </div>
                    <button onclick="this.parentElement.remove()">&times;</button>
                </div>
                <?php unset($_SESSION['archive_msg']); ?>
            <?php endif; ?>

            <!-- <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <div class="flex items-center">
                    <span class="text-sm font-medium text-gray-600">Items: <span
                            id="itemCountDisplay"><?php echo count($items); ?></span></span>
                </div>
                <div id="noResultsMsg" class="hidden text-xs text-red-500 font-medium">
                    No matches found
                </div>
            </div> -->

            <div class="row" style="margin-top: 15em;">
                <!-- <div class="col-2"></div> -->
                <div class="col">
                    <?php if ($clean_dir): ?>
                        <div class="fm-folder-header">
                            <button class="fm-back-btn" onclick="goBack()">
                                <i class="fa fa-arrow-left"></i> Back
                            </button>
                            <div class="fm-item-name">
                                <i class="fa fa-folder"></i><?php echo htmlspecialchars($clean_dir); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (count($items) > 0): ?>
                        <div class="fm-grid">
                            <?php
                            // Pre-sort items into two categories
                            $folders = array_filter($items, function ($i) {
                                return $i['type'] == 'folder';
                            });
                            $files = array_filter($items, function ($i) {
                                return $i['type'] == 'file';
                            });
                            ?>

                            <!-- 1. Render Folders First -->
                            <?php foreach ($folders as $item): ?>
                                <div class="fm-item fm-folder" data-name="<?php echo strtolower($item['name']); ?>"
                                    onclick="navigateTo('<?php echo addslashes($item['rel_link']); ?>')">
                                    <div class="fm-item-name">
                                        <i class="fa fa-folder"></i><?php echo htmlspecialchars($item['name']); ?>
                                    </div>
                                    <button class="fm-delete"
                                        onclick="openDeleteModal('<?php echo addslashes($item['name']); ?>', event)">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>

                            <!-- 2. Spacer: This creates the "total single row space" -->
                            <?php if (count($folders) > 0 && count($files) > 0): ?>
                                <div class="fm-row-spacer"
                                    style="grid-column: 1 / -1; height: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); margin: 10px 0;">
                                </div>
                            <?php endif; ?>

                            <!-- 3. Render Files Second -->
                            <?php foreach ($files as $item): ?>
                                <div class="fm-item fm-file" data-name="<?php echo strtolower($item['name']); ?>"
                                    onclick="viewFile('<?php echo addslashes($item['web_path']); ?>')">
                                    <div class="fm-item-name">
                                        <i class="fa fa-file"></i><?php echo htmlspecialchars($item['name']); ?>
                                    </div>
                                    <button class="fm-delete"
                                        onclick="openDeleteModal('<?php echo addslashes($item['name']); ?>', event)">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="fm-empty">
                            <i class="fa fa-folder-open" style="font-size:48px;"></i>
                            <p>This folder is currently empty.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-2">
                    <!-- Sidebar -->
                    <div class="fm-sidebar center-content">
                        <!-- Create Folder -->
                        <div class="fm-box">
                            <form method="POST">
                                <input type="hidden" name="create_folder" value="1">
                                <input type="text" name="folder_name" placeholder="Create Folder" required>
                                <button type="submit" class="fm-btn fm-btn-amber">Create</button>
                            </form>
                        </div>

                        <!-- Upload File -->
                        <div class="fm-box">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="upload_file" value="1">
                                <input type="file" name="file" id="fileInput"
                                    onchange="document.getElementById('fileLabel').innerText = this.files[0] ? this.files[0].name : 'No file chosen'">
                                <div class="file-status" id="fileLabel">No file chosen</div>
                                <button type="submit" class="fm-btn fm-btn-green">Upload</button>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- <div class="col-2"></div> -->
            </div>

        </div>


    </div>

    <!-- Rename Modal -->
    <div id="renameModal" class="fm-modal-overlay">
        <div class="fm-modal">
            <h3>Rename Item</h3>
            <p>Enter a new name for the item below.</p>
            <form method="POST">
                <input type="hidden" name="rename_item" value="1">
                <input type="hidden" name="old_name" id="renameOldName">
                <label>New Name</label>
                <input type="text" name="new_name" id="renameNewName" required>
                <div class="fm-modal-actions">
                    <button type="button" class="cancel" onclick="toggleModal('renameModal')">Cancel</button>
                    <button type="submit" class="confirm-blue">Apply Change</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="fm-modal-overlay">
        <div class="fm-modal">
            <h3>Security Check</h3>
            <p>You are about to delete <span id="deleteItemNameDisplay" style="color:#fff;font-weight:bold;"></span>.
                Please enter your Security PIN to continue.</p>
            <form method="POST">
                <input type="hidden" name="delete_item" value="1">
                <input type="hidden" name="item_name" id="deleteItemNameValue">
                <label>Admin PIN</label>
                <input type="password" name="user_pin" maxlength="6" required placeholder="••••••" autocomplete="off">
                <div class="fm-modal-actions">
                    <button type="button" class="cancel" onclick="toggleModal('deleteModal')">Cancel</button>
                    <button type="submit" class="confirm-red">Delete Permanently</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const BASE_ROUTE = window.location.origin + window.location.pathname;

        function filterItems() {
            const query = document.getElementById('archiveSearch').value.toLowerCase();
            const items = document.querySelectorAll('.fm-item');
            let visibleCount = 0;

            items.forEach(item => {
                const name = item.getAttribute('data-name');
                if (name.includes(query)) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            document.getElementById('itemCountDisplay').innerText = visibleCount;
            const noResults = document.getElementById('noResultsMsg');
            if (visibleCount === 0 && query !== '') {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }

        function toggleModal(id) {
            document.getElementById(id).classList.toggle('active');
        }

        function goBack() {
            window.location.href = BASE_ROUTE;
        }

        function navigateTo(path) {
            window.location.href = BASE_ROUTE + "?dir=" + encodeURIComponent(path);
        }

        function viewFile(path) {
            window.open(path, '_blank');
        }

        function openDeleteModal(name, event) {
            event.stopPropagation();
            document.getElementById('deleteItemNameDisplay').innerText = name;
            document.getElementById('deleteItemNameValue').value = name;
            toggleModal('deleteModal');
        }
    </script>

</body>

</html>