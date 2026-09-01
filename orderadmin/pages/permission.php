<?php
if (!orderadminIsAdministrator()) {
    http_response_code(403);
    echo '<div class="max-w-sm mx-auto px-4 py-8 text-center"><h1 class="text-xl font-semibold">Access denied</h1><p class="mt-2">You do not have permission to manage dashboard permissions.</p></div>';
    return;
}

orderadminSeedAdminDashboardPermissions();
$items = orderadminDashboardItems();
$users = R::find('sys_user', 'u_status = 1 ORDER BY u_fullname');
$selectedUserId = isset($get->user_id) ? (int) $get->user_id : (isset($post->user_id) ? (int) $post->user_id : 1);
$selectedUser = R::load('sys_user', $selectedUserId);
$message = '';
$error = '';

if (!$selectedUser || !(int) $selectedUser->id) {
    $selectedUserId = 1;
    $selectedUser = R::load('sys_user', $selectedUserId);
    $error = 'Please select a valid user.';
}

if (isset($post->save_permissions)) {
    $selectedUserId = (int) $post->user_id;
    $selectedUser = R::load('sys_user', $selectedUserId);
    if (!$selectedUser || !(int) $selectedUser->id) {
        $error = 'Please select a valid user.';
    } else {
        $allowedPages = [];
        foreach ($items as $item) {
            $allowedPages[$item['page']] = true;
        }
        $submittedPermissions = isset($post->permissions) && is_array($post->permissions) ? $post->permissions : [];
        $permissionValues = [];
        foreach ($allowedPages as $page => $allowed) {
            $permissionValues[$page] = isset($submittedPermissions[$page]) ? 1 : 0;
        }

        global $c;
        $userId = (int) $selectedUserId;
        if ($c->query("DELETE FROM `sys_user_dashboard_permission` WHERE user_id=$userId")) {
            foreach ($permissionValues as $page => $enabled) {
                $page = mysqli_real_escape_string($c, $page);
                $c->query("INSERT INTO `sys_user_dashboard_permission` (`user_id`, `dashboard_page`, `enabled`) VALUES ($userId, '$page', $enabled)");
            }
            $message = 'Permissions saved successfully.';
        } else {
            $error = 'Unable to save permissions.';
        }
    }
}

$permissions = orderadminDashboardPermissions($selectedUserId);
$hasConfiguration = count($permissions) > 0;
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<style>
    .permission-page { max-width: 42rem; margin: 0 auto; padding: 1.5rem .75rem; }
    .permission-table { width: 100%; border-collapse: collapse; background: #e7f2e3; border: 1px solid #2f4f2f33; border-radius: 18px; overflow: hidden; }
    .permission-table th, .permission-table td { padding: .75rem; border-bottom: 1px solid #2f4f2f33; text-align: left; }
    .permission-table tr:last-child td { border-bottom: 0; }
    .permission-icon { color: #47773f !important; font-size: 42px; line-height: 1; }
</style>
<section class="permission-page">
    <h1 class="text-2xl font-semibold mb-4">Dashboard Permissions</h1>

    <?php if ($message): ?>
        <div class="mb-4 rounded bg-green-100 px-3 py-2 text-green-800"><?php echo $escape($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mb-4 rounded bg-red-100 px-3 py-2 text-red-800"><?php echo $escape($error); ?></div>
    <?php endif; ?>

    <form method="get" class="mb-5">
        <input type="hidden" name="page" value="permission">
        <label for="permission-user" class="block mb-1 font-semibold">User</label>
        <select id="permission-user" name="user_id" class="form-control" onchange="this.form.submit()">
            <?php foreach ($users as $user): ?>
                <option value="<?php echo $escape($user->id); ?>"<?php echo (int) $user->id === $selectedUserId ? ' selected' : ''; ?>>
                    <?php echo $escape($user->u_fullname . ' (' . $user->u_username . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <form method="post">
        <input type="hidden" name="user_id" value="<?php echo $escape($selectedUserId); ?>">
        <table class="permission-table">
            <thead>
                <tr><th>Dashboard Item</th><th class="text-center">Enabled</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <?php $enabled = !$hasConfiguration || !empty($permissions[$item['page']]); ?>
                    <tr>
                        <td>
                            <span class="material-symbols-outlined permission-icon align-middle mr-2"><?php echo $escape(orderadminDashboardIcon($item['icon'])); ?></span>
                            <span class="align-middle"><?php echo $escape($item['label']); ?></span>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="permissions[<?php echo $escape($item['page']); ?>]" value="1"<?php echo $enabled ? ' checked' : ''; ?>>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" name="save_permissions" class="mt-4 form-control btn btn-success">Save Permissions</button>
    </form>
</section>
