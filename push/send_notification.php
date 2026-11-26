<?php

/**
 * OneSignal Push Notification Sender (Official PHP SDK)
 * Requires: composer install
 *
 * Usage:
 *   php send_notification.php broadcast "Title" "Message"
 *   php send_notification.php segment "c_my" "Title" "Message"
 *   php send_notification.php segment "c_my_82" "Title" "Message"
 *   php send_notification.php segments "c_my,a_my" "Title" "Message"
 *   php send_notification.php user "82" "Title" "Message"
 *
 * Segment uid tag reference:
 *   c_my     = Malaysia customers
 *   a_my     = Malaysia admins
 *   c_bd     = Bangladesh customers
 *   a_bd     = Bangladesh admins
 *   c_my_82  = Exact user (MY customer uid 82)
 */

require_once __DIR__ . '/vendor/autoload.php';

use onesignal\client\api\DefaultApi;
use onesignal\client\Configuration;
use onesignal\client\model\Notification;
use onesignal\client\model\LanguageStringMap;
use onesignal\client\model\Filter;
use GuzzleHttp\Client;


// ─── SDK client ───────────────────────────────────────────────────────────────

function getApi(): DefaultApi {
    $config = Configuration::getDefaultConfiguration()
        ->setRestApiKeyToken(APP_KEY_TOKEN);
    return new DefaultApi(new Client(), $config);
}

function makeContents(string $title, string $message): array {
    $heading = new LanguageStringMap();
    $heading->setEn($title);

    $content = new LanguageStringMap();
    $content->setEn($message);

    return [$heading, $content];
}

// ─── Broadcast to all subscribers ─────────────────────────────────────────────

function broadcast(string $title, string $message): void {
    [$heading, $content] = makeContents($title, $message);

    $notification = new Notification();
    $notification->setAppId(APP_ID);
    $notification->setHeadings($heading);
    $notification->setContents($content);
    $notification->setIncludedSegments(['All']);

    $result = getApi()->createNotification($notification);
    print_r($result);
}

// ─── Send by uid tag (prefix or exact) ────────────────────────────────────────
// Exact:  c_my_82  → uid = c_my_82
// Prefix: c_my     → uid starts_with c_my

function sendToSegment(string $uidValue, string $title, string $message): void {
    [$heading, $content] = makeContents($title, $message);

    $isExact = (bool) preg_match('/^[ac]_[a-z]+_\d+$/', $uidValue);
    $relation = $isExact ? '=' : 'starts_with';

    $filter = new Filter();
    $filter->setField('tag');
    $filter->setKey('uid');
    $filter->setRelation($relation);
    $filter->setValue($uidValue);

    $notification = new Notification();
    $notification->setAppId(APP_ID);
    $notification->setHeadings($heading);
    $notification->setContents($content);
    $notification->setFilters([$filter]);

    $result = getApi()->createNotification($notification);
    print_r($result);
}

// ─── Send to multiple uid prefixes (OR logic) ─────────────────────────────────

function sendToSegments(array $uidPrefixes, string $title, string $message): void {
    [$heading, $content] = makeContents($title, $message);

    $filters = [];
    foreach ($uidPrefixes as $i => $prefix) {
        if ($i > 0) {
            // OR operator between filters
            $filters[] = ['operator' => 'OR'];
        }
        $f = new Filter();
        $f->setField('tag');
        $f->setKey('uid');
        $f->setRelation('starts_with');
        $f->setValue(trim($prefix));
        $filters[] = $f;
    }

    $notification = new Notification();
    $notification->setAppId(APP_ID);
    $notification->setHeadings($heading);
    $notification->setContents($content);
    $notification->setFilters($filters);

    $result = getApi()->createNotification($notification);
    print_r($result);
}

// ─── Send to specific user by External ID ────────────────────────────────────

function sendToUser(string $externalUserId, string $title, string $message): void {
    [$heading, $content] = makeContents($title, $message);

    $notification = new Notification();
    $notification->setAppId(APP_ID);
    $notification->setHeadings($heading);
    $notification->setContents($content);
    $notification->setIncludeAliases(['external_id' => [$externalUserId]]);
    $notification->setTargetChannel('push');

    $result = getApi()->createNotification($notification);
    print_r($result);
}

// ─── CLI entrypoint ───────────────────────────────────────────────────────────

if (php_sapi_name() === 'cli') {
    $mode = $argv[1] ?? '';
    $arg2 = $argv[2] ?? '';
    $arg3 = $argv[3] ?? 'Notification';
    $arg4 = $argv[4] ?? '';

    switch ($mode) {
        case 'broadcast':
            broadcast($arg2, $arg3);
            break;

        case 'segment':
            // single uid prefix or exact: "c_my" or "c_my_82"
            sendToSegment($arg2, $arg3, $arg4);
            break;

        case 'segments':
            // comma-separated prefixes: "c_my,a_my"
            $prefixes = array_filter(array_map('trim', explode(',', $arg2)));
            sendToSegments($prefixes, $arg3, $arg4);
            break;

        case 'user':
            // by external ID (OneSignal.login userId)
            sendToUser($arg2, $arg3, $arg4);
            break;

        default:
            echo "Usage:\n";
            echo "  php send_notification.php broadcast \"Title\" \"Message\"\n";
            echo "  php send_notification.php segment \"c_my\" \"Title\" \"Message\"\n";
            echo "  php send_notification.php segment \"c_my_82\" \"Title\" \"Message\"\n";
            echo "  php send_notification.php segments \"c_my,a_my\" \"Title\" \"Message\"\n";
            echo "  php send_notification.php user \"82\" \"Title\" \"Message\"\n";
    }
}
