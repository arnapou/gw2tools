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
//            $manager->addCacheRetentionPolicy('/v2/commerce/listings', 1800);
//            $manager->addCacheRetentionPolicy('/v2/commerce/prices', 1800);

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
     * @param integer $item_id
     * @return integer
     */
    public function getRecipeId($item_id) {
        $cache      = MongoCache::getInstance();
        $lang       = Translator::getInstance()->getLang();
        $collection = $cache->getCache()->getMongoCollection($lang . '_recipes');
        $object     = $collection->findOne(['value.output_item_id' => $item_id]);
        if ($object && isset($object['value'], $object['value']['id'])) {
            return $object['value']['id'];
        }
        return null;
    }

    /**
     * 
     * @return array
     */
    public function getDyeItems() {
        $cache   = MongoCache::getInstance();
        $lang    = Translator::getInstance()->getLang();
        $key     = 'map/dye-item/' . $lang;
        $objects = $cache->get($key);
        if (empty($objects)) {
            $collection = $cache->getCache()->getMongoCollection($lang . '_items');
            $objects    = [];
            foreach ($collection->find(['value.details.color_id' => ['$gt' => 0]]) as $doc) {
                $objects[$doc['value']['details']['color_id']] = $doc['value']['id'];
            }
            $cache->set($key, $objects, 86400);
        }
        if ($objects) {
            $client = self::getInstance($lang);
            $client->v2_items(array_values($objects));
            foreach ($objects as $color_id => $item_id) {
                $objects[$color_id] = new Item($client, $item_id);
            }
            return $objects;
        }
        return null;
    }

    /**
     * 
     * @return array
     */
    public function getSkinItems($skin_ids) {
        if (empty($skin_ids)) {
            return [];
        }

        $cache      = MongoCache::getInstance();
        $lang       = Translator::getInstance()->getLang();
        $collection = $cache->getCache()->getMongoCollection($lang . '_items');
        $objects    = [];
        foreach ($collection->find(['value.default_skin' => ['$in' => $skin_ids]]) as $doc) {
            if (!isset($objects[$doc['value']['default_skin']])) {
                $objects[$doc['value']['default_skin']] = $doc['value']['id'];
            }
        }

        $client = self::getInstance($lang);
        $client->v2_items(array_values($objects));
        foreach ($objects as $skin_id => $item_id) {
            $objects[$skin_id] = new Item($client, $item_id);
        }
        return $objects;
    }

}
