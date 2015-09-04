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
use Arnapou\GW2Api\Model\AbstractObject;
use Arnapou\GW2Api\SimpleClient;
use Arnapou\GW2Tools\Api\User;
use Arnapou\GW2Tools\Service;

/*
 * DELETE OLD USERS
 */
$conn = User::getConnection();

// clean old non accessed codes / users
$conn->executeDelete(User::table(), 'lastaccess < ' . (time() - 180 * 86400));

foreach ($conn->query("SELECT * FROM `" . User::table() . "`") as $row) {
    $user = new User($row);
    $user->checkAccount(); // automatic delete if error with token
}

/*
 * FORCE CACHE CHECKS
 */
foreach ([
// AbstractClient::LANG_DE,
AbstractClient::LANG_EN,
// AbstractClient::LANG_ES,
// AbstractClient::LANG_FR
] as $lang) {

    echo date('Y-m-d H:i:s') . ' ----- ' . $lang . " -----\n";

    $simpleClient = Service::newSimpleClient($lang, false);

    $simpleClient->getClientV2()
        ->getRequestManager()
        ->getEventListener()
        ->bind(RequestManager::onRequest, function($event) {
            echo date('Y-m-d H:i:s') . ' ' . round($event['time'], 3) . "s " . $event['uri'] . " \n";
        });

    foreach ([
    'v2_colors',
    'v2_currencies',
    'v2_files',
    'v2_items',
    'v2_maps',
    'v2_materials',
    'v2_quaggans',
    'v2_recipes',
    'v2_skins',
    'v2_specializations',
    'v2_traits',
    'v2_worlds',
    ] as $api) {
        $ids = $simpleClient->$api();
        $simpleClient->$api($ids);
    }
}
