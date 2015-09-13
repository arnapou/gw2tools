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
            $config  = new Config(__DIR__ . '/../../../config');
            $service = new Service('GW2Tools', $config);
            foreach (Translator::getInstance()->getLangs() as $lang) {
                $service->addModule($lang, new ModuleApi($service, $lang));
            }
            $service->addModule('', new ModuleGeneric($service));
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
            $factory->addFilter(new \Twig_SimpleFilter('buffdescription', function($item) {
                return buffdescription($item);
            }));
            $factory->addFilter(new \Twig_SimpleFilter('consumableduration', function($item) {
                return consumableduration($item);
            }));
            $factory->addFilter(new \Twig_SimpleFilter('datediff', function($date) {
                return datediff($date);
            }));
            $factory->addFilter(new \Twig_SimpleFilter('dataitem', function($item) {
                if ($item instanceof \Arnapou\GW2Api\Model\InventorySlot) {
                    return data_inventory_item($item);
                }
                elseif ($item instanceof \Arnapou\GW2Api\Model\Item) {
                    return data_item($item);
                }
                return '';
            }));
            $factory->addSimpleFilter('trans', function (\Twig_Environment $env, $context, $string, $params = [], $prefix = '') {
                if (isset($context['module'])) {
                    return $context['module']->trans($prefix . $string, $params);
                }
                else {
                    return Translator::getInstance()->trans($prefix . $string, $params);
                }
            });
            $factory->addSimpleFilter('transarray', function (\Twig_Environment $env, $context, $array, $params = [], $prefix = '') {
                if (isset($context['module'])) {
                    return array_map(function ($string) use ($context, $prefix, $params) {
                        return $context['module']->trans($prefix . $string, $params);
                    }, $array);
                }
                else {
                    return array_map(function ($string) use ($prefix, $params) {
                        return Translator::getInstance()->trans($prefix . $string, $params);
                    }, $array);
                }
            });
            $this->twigFactory = $factory;
        }
        return $this->twigFactory->getEnvironment();
    }

}
