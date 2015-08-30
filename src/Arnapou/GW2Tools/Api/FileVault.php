<?php

/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\GW2Tools\Api;

use Arnapou\GW2Api\Core\Curl;
use Arnapou\GW2Tools\Service;
use Arnapou\Toolbox\Functions\Directory;

class FileVault extends \Arnapou\Toolbox\Vault\FileVault {

    /**
     *
     * @var array
     */
    static protected $instances = [];

    /**
     * 
     * @return FileVault
     */
    static private function getInstance($key) {
        if (!isset(self::$instances[$key])) {
            $path                  = Service::getInstance()->getPathCache() . '/' . $key;
            Directory::createIfNotExists($path);
            self::$instances[$key] = new self($path);
        }
        return self::$instances[$key];
    }

    /**
     * 
     * @return FileVault
     */
    static public function getVaultEmblems() {
        return self::getInstance('emblems');
    }

    /**
     * 
     * @return FileVault
     */
    static public function getVaultProxy() {
        return self::getInstance('proxy');
    }

    public function getResponse($url) {
        if (empty($url)) {
            return null;
        }
        if ($this->exists($url)) {
            $filepath = $this->getVaultFilename($url);
            if (time() - filemtime($filepath) < 86400 * 15) {
                return parent::getResponse($url);
            }
        }
        $curl     = new Curl();
        $curl->setUrl($url);
        $curl->setTimeout(10);
        $response = $curl->execute();
        if ($response->getInfoHttpCode() == 200) {
            $content = $response->getContent();
            $this->set($url, $content);
            return parent::getResponse($url);
        }
        return null;
    }

}
