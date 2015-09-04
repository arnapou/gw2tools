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

use Arnapou\GW2Api\Cache\FileCache;
use Arnapou\GW2Api\Cache\MemcachedCache;
use Arnapou\GW2Api\Cache\MongoCache;
use Arnapou\GW2Api\Cache\MemoryCacheDecorator;
use Arnapou\GW2Api\Cache\MysqlCache;
use Arnapou\GW2Api\Core\AbstractClient;
use Arnapou\GW2Api\SimpleClient;
use Arnapou\Toolbox\Connection\Mysql;
use Arnapou\Toolbox\Functions\Directory;
use Arnapou\Toolbox\Http\Service\Config;
use Arnapou\Toolbox\Twig\TwigFactory;
use Arnapou\Toolbox\Twig\TwigEnvironment;

class Service extends \Arnapou\Toolbox\Http\Service\Service {

    /**
     *
     * @var Service
     */
    static protected $instance;

    /**
     *
     * @var TwigFactory 
     */
    protected $twigFactory;

    /**
     * 
     * @return Service
     */
    static public function getInstance() {
        if (!isset(self::$instance)) {
            $config         = new Config(__DIR__ . '/../../../config');
            $service        = new Service('GW2Tools', $config);
            $service->addModule('api', new Api\Module($service));
            $service->addModule('assets', new Assets\Module($service));
            self::$instance = $service;
        }
        return self::$instance;
    }

    /**
     * 
     * @return TwigEnvironment
     */
    public function getTwig() {
        if (!isset($this->twigFactory)) {
            $this->getConnection();
            $factory = TwigFactory::create($this);
            $factory->addPath(__DIR__ . '/twig');
            $factory->addFilter(new \Twig_SimpleFilter('image', function($url) {
                return image($url);
            }));
            $factory->addFilter(new \Twig_SimpleFilter('amount', function($value) {
                return amount($value);
            }));
            $factory->addFilter(new \Twig_SimpleFilter('columns', function($array, $n, $fill = true) {
                return chunk($array, ceil(count($array) / $n), $fill);
            }));
            $factory->addFilter(new \Twig_SimpleFilter('chunk', function($array, $n, $fill = true) {
                return chunk($array, $n, $fill);
            }));
            $factory->addFilter(new \Twig_SimpleFilter('datediff', function($date) {
                $diff = (new \DateTime('now'))->getTimestamp() - (new \DateTime($date))->getTimestamp();
                if ($diff < 60) {
                    return '< 1 min.';
                }
                elseif ($diff < 3600) {
                    return floor($diff / 60) . ' min.';
                }
                elseif ($diff < 86400) {
                    $h = floor($diff / 3600);
                    return $h . ' hour' . ($h > 1 ? 's' : '');
                }
                elseif ($diff < 2629728) {
                    $d = floor($diff / 86400);
                    return $d . ' day' . ($d > 1 ? 's' : '');
                }
                else {
                    $m = floor($diff / 2629728);
                    return $m . ' month' . ($m > 1 ? 's' : '');
                }
            }));
            $this->twigFactory = $factory;
        }
        return $this->twigFactory->getEnvironment();
    }

    /**
     * 
     * @param string $lang
     * @param boolean $withDecorator
     * @return SimpleClient
     */
    static public function newSimpleClient($lang = AbstractClient::LANG_EN, $withDecorator = true) {

        $config    = self::getInstance()->getConfig();
        $cacheType = $config->get('cache.type');

        if ('memcached' === $cacheType) {
            $cache = new MemcachedCache();
        }
        elseif ('mongo' === $cacheType) {
            $mongo      = new \MongoClient();
            $cache      = new MongoCache($mongo->selectDB('gw2tool'), 'cache');
        }
        elseif ('mysql' === $cacheType) {
            $pdo   = self::getInstance()->getConnection();
            $cache = new MysqlCache($pdo, $config->get('table.cache', 'cache'));
        }
        else {
            $path = self::getInstance()->getPathCache() . '/gw2api_' . $lang;
            Directory::createIfNotExists($path);

            $cache = new FileCache($path);
        }
        if ($withDecorator) {
            $cache = new MemoryCacheDecorator($cache);
        }

        $client = SimpleClient::create($lang, $cache);
        $client->getClientV2()->getRequestManager()->setDefautCacheRetention(1800);
        return $client;
    }

}
