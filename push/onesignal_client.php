<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use onesignal\client\api\DefaultApi;
use onesignal\client\Configuration;
use onesignal\client\model\Notification;
use onesignal\client\model\LanguageStringMap;
use onesignal\client\model\Filter;
use GuzzleHttp\Client;

if (!function_exists('getApi')) {
    function getApi(): DefaultApi
    {
        $config = Configuration::getDefaultConfiguration()
            ->setRestApiKeyToken(APP_KEY_TOKEN);
        return new DefaultApi(new Client(), $config);
    }
}

if (!function_exists('sendPush')) {
    function sendPush(string $tag, string $message, string $title = 'ORI'): array
    {
        $heading = new LanguageStringMap();
        $heading->setEn($title);

        $content = new LanguageStringMap();
        $content->setEn($message);

        $notification = new Notification();
        $notification->setAppId(APP_ID);
        $notification->setHeadings($heading);
        $notification->setContents($content);
        $notification->setAndroidChannelId(CLIENT_ID);

        if ($tag === 'all') {
            $notification->setIncludedSegments(['All']);
        } elseif (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $tag)) {
            $notification->setIncludeSubscriptionIds([$tag]);
        } else {
            $isExact = (bool) preg_match('/^[ac]_[a-z]+_\\d+$/', $tag);
            $filter = new Filter();
            $filter->setField('tag');
            $filter->setKey('uid');
            $filter->setRelation($isExact ? '=' : 'starts_with');
            $filter->setValue($tag);
            $notification->setFilters([$filter]);
        }

        $result = getApi()->createNotification($notification);
        return ['id' => $result->getId(), 'errors' => $result->getErrors()];
    }
}

if (!function_exists('sendPushTag')) {
    function sendPushTag(string $tag, string $message, string $title = 'ORI'): array
    {
        $heading = new LanguageStringMap();
        $heading->setEn($title);

        $content = new LanguageStringMap();
        $content->setEn($message);

        $notification = new Notification();
        $notification->setAppId(APP_ID);
        $notification->setHeadings($heading);
        $notification->setContents($content);
        $notification->setAndroidChannelId('a442a0c5-6658-486a-956c-efb44ac76234');

        if ($tag === 'all') {
            $notification->setIncludedSegments(['All']);
        } else {
            $isExact = (bool) preg_match('/^[ac]_[a-z]+_\\d+$/', $tag);
            $filter = new Filter();
            $filter->setField('tag');
            $filter->setKey('uid');
            $filter->setRelation($isExact ? '=' : 'starts_with');
            $filter->setValue($tag);
            $notification->setFilters([$filter]);
        }

        $result = getApi()->createNotification($notification);
        return ['id' => $result->getId(), 'errors' => $result->getErrors()];
    }
}
