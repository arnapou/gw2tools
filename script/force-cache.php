<?php

/*
 * This file is part of the Arnapou FileStore package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include __DIR__ . '/../vendor/autoload.php';

Arnapou\GW2Tools\Service::getInstance();

use Arnapou\GW2Api\Core\AbstractClient;
use Arnapou\GW2Api\Core\RequestManager;
use Arnapou\GW2Api\SimpleClient;
use Arnapou\GW2Tools\Service;

$cache = new MemcachedCache();

foreach ([
// AbstractClient::LANG_DE,
AbstractClient::LANG_EN,
// AbstractClient::LANG_ES,
// AbstractClient::LANG_FR
] as $lang) {

    echo date('Y-m-d H:i:s') . ' ----- ' . $lang . " -----\n";

    $simpleClient = Service::newSimpleClient($lang, false);
    $clientV2 = $simpleClient->getClientV2();

    $clientV2
        ->getRequestManager()
        ->getEventListener()
        ->bind(RequestManager::onRequest, function($event) {
            echo date('Y-m-d H:i:s') . ' ' . round($event['time'], 3) . "s " . $event['uri'] . " \n";
        });


    $ids = $clientV2->apiSkins()->execute()->getAllData();
    $clientV2->smartRequest('apiSkins', $ids, 7 * 86400);

    $ids = $clientV2->apiItems()->execute()->getAllData();
    $clientV2->smartRequest('apiItems', $ids, 7 * 86400);
}
