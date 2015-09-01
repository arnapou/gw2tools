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
use Arnapou\GW2Api\Cache\MemoryCacheDecorator;
use Arnapou\GW2Api\Core\AbstractClient;
use Arnapou\GW2Api\SimpleClient;
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
            $config = new Config(__DIR__ . '/../../../config');
            $service = new Service('GW2Tools', $config);
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

        if (self::getInstance()->getConfig()->get('cache.memcached')) {
            $cache = new MemcachedCache();
        }
        else {
            $path = self::getInstance()->getPathCache() . '/gw2api_' . $lang;
            Directory::createIfNotExists($path);

            $cache = new FileCache($path);
        }
        if ($withDecorator) {
            $cache = new MemoryCacheDecorator($cache);
        }

        return SimpleClient::create($lang, $cache);
    }

}
