<?php

/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\GW2Tools;

use Arnapou\GW2Api\Core\AbstractClient;
use Arnapou\GW2Api\Cache\MemoryCacheDecorator;
use Arnapou\GW2Api\Cache\MongoCache;

class SimpleClient extends \Arnapou\GW2Api\SimpleClient {

    /**
     *
     * @var array
     */
    static protected $instances = [];

    /**
     * 
     * @param string $lang
     * @param boolean $withDecorator
     * @return SimpleClient
     */
    public static function getInstance($lang = AbstractClient::LANG_EN, $withDecorator = true) {
        $key = $lang . '-' . ($withDecorator ? 'Y' : 'N');
        if (!isset(self::$instances[$key])) {

            $mongo = new \MongoClient();
            $cache = new MongoCache($mongo->selectDB('gw2tool'), 'cache');

            if ($withDecorator) {
                $cache = new MemoryCacheDecorator($cache);
            }

            $client = self::create($lang, $cache);

            $manager = $client->getClientV2()->getRequestManager();
            $manager->setDefautCacheRetention(1800);
            $manager->addCacheRetentionPolicy('/v2/commerce/listings', 3600);
            $manager->addCacheRetentionPolicy('/v2/commerce/prices', 3600);

//            $manager
//                ->getEventListener()
//                ->bind('onRequest', function($event) {
//                    $line = date('Y-m-d H:i:s') . ' ' . round($event['time'], 3) . "s " . $event['uri'] . " \n";
//                    file_put_contents(__DIR__ . '/../../../requests.log', $line, FILE_APPEND);
//                });

            self::$instances[$key] = $client;
        }
        return self::$instances[$key];
    }

}
