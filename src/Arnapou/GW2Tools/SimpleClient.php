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
use Arnapou\GW2Api\Model\Item;

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

            $cache = MongoCache::getInstance($withDecorator);

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

    /**
     * 
     * @param int $colorid
     * @return Item
     */
    public function getDyeItem($colorid) {
        $cache = MongoCache::getInstance();
        $lang  = Translator::getInstance()->getLang();
        $key   = 'dyeitem/' . $lang . '/' . $colorid;
        $obj   = $cache->get($key);
        if (empty($obj)) {
            $collection = $cache->getCache()->getMongoCollection($lang . '_items');
            $obj        = $collection->findOne(['value.details.color_id' => (int) $colorid]);
            $cache->set($key, $obj, 86400);
        }
        if ($obj && isset($obj['value'], $obj['value']['id'])) {
            return new Item(self::getInstance($lang), $obj['value']['id']);
        }
        return null;
    }

}
