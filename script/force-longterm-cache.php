<?php

/*
 * This file is part of the Arnapou FileStore package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include_once __DIR__ . '/../vendor/autoload.php';

Arnapou\GW2Tools\Service::getInstance();

use Arnapou\GW2Api\Core\RequestManager;
use Arnapou\GW2Api\Model\Character;
use Arnapou\GW2Tools\User;
use Arnapou\GW2Tools\SimpleClient;
use Arnapou\GW2Tools\Translator;

/*
 * FORCE CACHE CHECKS
 */
foreach (Translator::getInstance()->getLangs() as $lang) {

    echo date('Y-m-d H:i:s') . ' ----- ' . $lang . " -----\n";

    $simpleClient = SimpleClient::getInstance($lang, false);

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
