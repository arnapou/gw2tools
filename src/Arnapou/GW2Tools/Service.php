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
			$service->addModule('api', new Module\Api($service));
			$service->addModule('assets', new Module\Assets($service));
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
			$factory->addFilter(new \Twig_SimpleFilter('sort_array', function($array, $field) {
				usort($array, function($a, $b) use($field) {
					if (!isset($a[$field]) || !isset($b[$field]) || $a[$field] == $b[$field]) {
						return 0;
					}
					return $a[$field] < $b[$field] ? -1 : 1;
				});
				return $array;
			}));
			$factory->addFilter(new \Twig_SimpleFilter('chunk', function($array, $n) {
				$return = [];
				$current = [];
				$i = 0;
				foreach ($array as $key => $value) {
					$current[$key] = $value;
					$i++;
					if ($i == $n) {
						$return[] = $current;
						$current = [];
						$i = 0;
					}
				}
				if ($i > 0) {
					while ($i < $n) {
						$current[] = null;
						$i++;
					}
					$return[] = $current;
				}
				return $return;
			}));
			$this->twigFactory = $factory;
		}
		return $this->twigFactory->getEnvironment();
	}

}
