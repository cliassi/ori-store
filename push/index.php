<?php
require_once __DIR__ . '/onesignal_client.php';

$result  = null;
$error   = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tag     = trim($_POST['tag'] ?? '');
    $title   = trim($_POST['title'] ?? 'ORI');
    $message = trim($_POST['message'] ?? '');

    if ($tag === '' || $message === '') {
        $error = 'Tag and message are required.';
    } else {
        try {
            $result  = sendPush($tag, $message, $title);
            $success = empty($result['errors']);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
    }
}
exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORI Push Notification</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 36px 40px;
            width: 100%;
            max-width: 480px;
        }
        h1 {
            font-size: 22px;
            font-weight: 700;
            color: #111;
            margin-bottom: 6px;
        }
        .subtitle {
            font-size: 13px;
            color: #888;
            margin-bottom: 28px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #444;
            margin-bottom: 6px;
        }
        .hint {
            font-size: 11px;
            color: #aaa;
            margin-top: -4px;
            margin-bottom: 12px;
        }
        input[type="text"], textarea {
            width: 100%;
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 14px;
            color: #222;
            outline: none;
            transition: border-color 0.2s;
            margin-bottom: 4px;
        }
        input[type="text"]:focus, textarea:focus {
            border-color: #0a84ff;
        }
        textarea {
            resize: vertical;
            min-height: 110px;
            font-family: inherit;
        }
        .tag-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 20px;
        }
        .pill {
            cursor: pointer;
            background: #f0f2f5;
            border: 1.5px solid #e0e0e0;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            color: #555;
            transition: all 0.15s;
        }
        .pill:hover {
            background: #0a84ff;
            border-color: #0a84ff;
            color: #fff;
        }
        button[type="submit"] {
            width: 100%;
            background: #0a84ff;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: background 0.2s;
        }
        button[type="submit"]:hover { background: #0070e0; }
        .alert {
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .alert-success { background: #e6f9f0; color: #1a7a4a; border: 1px solid #b2e8ce; }
        .alert-error   { background: #fff0f0; color: #c0392b; border: 1px solid #f5c6c6; }
        .notif-id { font-family: monospace; font-size: 11px; color: #888; margin-top: 4px; }
    </style>
</head>
<body>
<div class="card">
    <h1>🔔 ORI Push Notification</h1>
    <p class="subtitle">Send push to segment, country, or specific user</p>

    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ Notification sent successfully!
            <div class="notif-id">ID: <?= htmlspecialchars($result['id'] ?? '') ?></div>
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <label for="tag">Target Tag (uid)</label>
        <input type="text" id="tag" name="tag"
               value="<?= htmlspecialchars($_POST['tag'] ?? '') ?>"
               placeholder="e.g. c_my or c_my_82 or all"
               required>
        <p class="hint">Use <b>all</b> to broadcast • prefix e.g. <b>c_my</b> = all MY customers • exact e.g. <b>c_my_82</b></p>

        <div class="tag-pills">
            <span class="pill" onclick="setTag('all')">📢 All</span>
            <span class="pill" onclick="setTag('c_my')">🇲🇾 MY Customers</span>
            <span class="pill" onclick="setTag('a_my')">🇲🇾 MY Admins</span>
            <span class="pill" onclick="setTag('c_bd')">🇧🇩 BD Customers</span>
            <span class="pill" onclick="setTag('a_bd')">🇧🇩 BD Admins</span>
        </div>

        <label for="title">Title</label>
        <input type="text" id="title" name="title"
               value="<?= htmlspecialchars($_POST['title'] ?? 'ORI') ?>"
               placeholder="Notification title">
        <p class="hint" style="margin-bottom:16px;"></p>

        <label for="message">Message</label>
        <textarea id="message" name="message"
                  placeholder="Enter your message here..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        <p class="hint">Keep it short and clear</p>

        <button type="submit">Send Push Notification</button>
    </form>
</div>

<script>
function setTag(value) {
    document.getElementById('tag').value = value;
    document.getElementById('tag').focus();
}
</script>
</body>
</html>
