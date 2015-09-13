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

use Arnapou\GW2Api\Cache\MemoryCacheDecorator;

class MongoCache extends \Arnapou\GW2Api\Cache\MongoCache {

    /**
     *
     * @var MongoCache
     */
    static protected $instance;

    /**
     *
     * @var MemoryCacheDecorator
     */
    static protected $instanceWithDecorator;

    /**
     * 
     * @param boolean $withDecorator
     * @return MongoCache
     */
    public static function getInstance($withDecorator = true) {
        if (!isset(self::$instance)) {

            $mongo = new \MongoClient();
            $cache = new self($mongo->selectDB('gw2tool'), 'cache');

            self::$instance              = $cache;
            self::$instanceWithDecorator = new MemoryCacheDecorator($cache);
        }
        return $withDecorator ? self::$instanceWithDecorator : self::$instance;
    }

}
