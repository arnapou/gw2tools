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

use Arnapou\GW2Api\Core\AbstractClient;
use Arnapou\GW2Api\SimpleClient;
use Arnapou\Toolbox\Exception\Exception;
use Arnapou\Toolbox\Functions\Directory;

class User {

    /**
     *
     * @var array
     */
    protected $data;

    /**
     * 
     * @param array $data
     */
    private function __construct($data) {
        if (!isset($data['code'], $data['token'], $data['lastaccess'])) {
            throw new Exception('Corrupted data');
        }
        $this->data = $data;
    }

    /**
     * 
     * @return ApiClient
     */
    public function getApiClient() {
        $lang = AbstractClient::LANG_EN;
        
        $path = Service::getInstance()->getPathCache() . '/gw2api_' . $lang;
        Directory::createIfNotExists($path);

        return SimpleClient::create($lang, $path);
        
        $path = Service::getInstance()->getPathCache() . '/gw2api';
        Directory::createIfNotExists($path);

        $apiClient = ApiClient::EN($path);
        $apiClient->setAccessToken($this->getToken());
        return $apiClient;
    }

    /**
     * 
     * @return array
     */
    public function toArray() {
        return $this->data;
    }

    /**
     * 
     * @return string
     */
    public function getCode() {
        return $this->data['code'];
    }

    /**
     * 
     * @param string $code
     * @return User
     */
    public function setCode($code) {
        $this->data['code'] = $code;
        return $this;
    }

    /**
     * 
     * @return string
     */
    public function getToken() {
        return $this->data['token'];
    }

    /**
     * 
     * @param string $token
     * @return User
     */
    public function setToken($token) {
        $this->data['token'] = $token;
        return $this;
    }

    /**
     * 
     * @return string
     */
    public function getLastaccess() {
        return $this->data['lastaccess'];
    }

    /**
     * 
     * @return User
     */
    public function setLastaccess() {
        $this->data['lastaccess'] = time();
        return $this;
    }

    /**
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null) {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return $default;
    }

    /**
     * 
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set($key, $value) {
        if ($key === 'code') {
            $this->setCode($value);
        }
        elseif ($key === 'token') {
            $this->setToken($value);
        }
        elseif ($key === 'lastaccess') {
            $this->setLastaccess();
        }
        else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    /**
     * 
     * @return User
     */
    public function save() {
        $vault = TokenVault::getInstance();
        $vault->set('code.' . $this->data['code'], $this->data);
        $vault->set('token.' . $this->data['token'], $this->data['code']);
        return $this;
    }

    /**
     * 
     * @return User
     */
    public function delete() {
        $vault = TokenVault::getInstance();
        $vault->remove('code.' . $this->data['code']);
        $vault->remove('token.' . $this->data['token']);
        return $this;
    }

    /**
     * 
     * @param string $token
     * @return User
     */
    public static function create($token, $code = null) {
        $vault     = TokenVault::getInstance();
        $data      = [
            'code'       => ($code && strlen($code) == 10) ? $code : $vault->newKey(),
            'token'      => $token,
            'lastaccess' => time(),
        ];
        $object    = new self($data);
        $apiClient = $object->getApiClient();
        $object->save();
        return $object;
    }

    /**
     * 
     * @param string $token
     * @return User
     */
    public static function findByToken($token) {
        $vault = TokenVault::getInstance();
        $code  = $vault->get('token.' . $token);
        if (empty($code)) {
            return null;
        }
        return self::findByCode($code);
    }

    /**
     * 
     * @param string $code
     * @return User
     */
    public static function findByCode($code) {
        $vault = TokenVault::getInstance();
        $data  = $vault->get('code.' . $code);
        if (empty($data)) {
            return null;
        }
        return new self($data);
    }

}
