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

use Arnapou\Toolbox\Functions\Directory;

class TokenVault extends \Arnapou\Toolbox\Vault\PhpDataVault {

	/**
	 *
	 * @var TokenVault
	 */
	static protected $instance;

	/**
	 * 
	 * @return TokenVault
	 */
	static public function getInstance() {
		if (!isset(self::$instance)) {
			$path = Service::getInstance()->getPathData() . '/tokens';
			Directory::createIfNotExists($path);
			self::$instance = new self($path);
		}
		return self::$instance;
	}

	public function set($key, $value) {
		parent::set($key, $value);
		parent::set($value, $key);
	}

	public function remove($key) {
		if ($this->exists($key)) {
			$value = $this->get($key);
			parent::remove($key);
			parent::remove($value);
		}
	}

	public function newKey() {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890';
		$nbchars = strlen($chars);
		$key = '';
		do {
			$n = 10;
			while ($n--) {
				$key .= $chars[mt_rand(0, $nbchars - 1)];
			}
		} while ($this->exists($key));
		return $key;
	}

}
