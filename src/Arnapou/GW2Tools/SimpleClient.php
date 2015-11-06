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
use Arnapou\GW2Api\Model\Color;
use Arnapou\GW2Api\Model\Dyes;
use Arnapou\GW2Api\Model\Mini;
use Arnapou\GW2Api\Model\Minis;
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
            $manager->addCacheRetentionPolicy('/v1/guild_details', 86400);
            $manager->addCacheRetentionPolicy('/v2/colors', 604000);
            $manager->addCacheRetentionPolicy('/v2/commerce/listings', 600);
            $manager->addCacheRetentionPolicy('/v2/commerce/prices', 600);
            $manager->addCacheRetentionPolicy('/v2/characters', 600);
            $manager->addCacheRetentionPolicy('/v2/currencies', 86400);
            $manager->addCacheRetentionPolicy('/v2/files', 604000);
            $manager->addCacheRetentionPolicy('/v2/items', 604000);
            $manager->addCacheRetentionPolicy('/v2/maps', 604000);
            $manager->addCacheRetentionPolicy('/v2/materials', 604000);
            $manager->addCacheRetentionPolicy('/v2/quaggans', 604000);
            $manager->addCacheRetentionPolicy('/v2/recipes', 604000);
            $manager->addCacheRetentionPolicy('/v2/skins', 604000);
            $manager->addCacheRetentionPolicy('/v2/specializations', 604000);
            $manager->addCacheRetentionPolicy('/v2/traits', 604000);
            $manager->addCacheRetentionPolicy('/v2/worlds', 604000);

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
     * @return array
     */
    public function getAchievementsDaily() {
        $daily = $this->v2_achievements_daily();
        $ids   = [];
        foreach ($daily as $type => $items) {
            foreach ($items as $item) {
                $ids[] = $item['id'];
            }
        }
        $objects = $this->v2_achievements($ids);
        foreach ($daily as $type => &$items) {
            foreach ($items as &$item) {
                $item['object'] = isset($objects[$item['id']]) ? $objects[$item['id']] : null;
            }
        }
        return $daily;
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
     * @param integer $item_id
     * @return integer
     */
    public function getMiniId($item_id) {
        $cache      = MongoCache::getInstance();
        $lang       = Translator::getInstance()->getLang();
        $collection = $cache->getCache()->getMongoCollection($lang . '_minis');
        $object     = $collection->findOne(['value.item_id' => $item_id]);
        if ($object && isset($object['value'], $object['value']['id'])) {
            return $object['value']['id'];
        }
        return null;
    }

    /**
     * 
     * @param integer $item_id
     * @return Mini
     */
    public function getMini($item_id) {
        $id = $this->getMiniId($item_id);
        if($id){
            return new Mini($this, $id);
        }
        return null;
    }

    /**
     * 
     * @param Minis $minis
     * @return array
     */
    public function getMinisByRarity(Minis $minis = null) {
        $minis   = $minis->getMinis();
        $grouped = [];
        foreach ($minis as /* @var $mini Mini */ $mini) {
            $item = $mini->getItem();
            if ($item) {
                $rarity = $item->getRarity();
            }
            else {
                $item   = null;
                $rarity = '';
            }
            if (empty($grouped[$rarity])) {
                $grouped[$rarity] = [
                    'count' => 0,
                    'total' => 0,
                    'items' => [],
                ];
            }
            $grouped[$rarity]['items'][] = [$mini, $item];
            $grouped[$rarity]['count'] += $mini->isUnlocked() ? 1 : 0;
            $grouped[$rarity]['total'] ++;
        }
        return $grouped;
    }

    /**
     * 
     * @param Dyes $dyes
     * @return array
     */
    public function getDyesByRarity(Dyes $dyes = null) {
        $colors  = $dyes->getColors();
        $map     = $this->getDyeItems();
        $grouped = [];
        foreach ($colors as /* @var $color Color */ $color) {
            if (isset($map[$color->getId()])) {
                $item   = $map[$color->getId()];
                $rarity = $item->getRarity();
            }
            else {
                $item   = null;
                $rarity = '';
            }
            if (empty($grouped[$rarity])) {
                $grouped[$rarity] = [
                    'count' => 0,
                    'total' => 0,
                    'items' => [],
                ];
            }
            $grouped[$rarity]['items'][] = [$color, $item];
            $grouped[$rarity]['count'] += $color->isUnlocked() ? 1 : 0;
            $grouped[$rarity]['total'] ++;
        }
        return $grouped;
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
