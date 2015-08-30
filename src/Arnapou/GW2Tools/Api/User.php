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

use Arnapou\GW2Api\Exception\InvalidTokenException;
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
     * @var Gw2Account 
     */
    protected $account;

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
     * @param type $right
     * @return boolean
     */
    public function hasRight($right) {
        return in_array($right, $this->getRights());
    }

    /**
     * 
     * @return array
     */
    public function getRights() {
        return isset($this->data['rights']) ? $this->data['rights'] : [];
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
     * @return Gw2Account
     */
    public function checkAccount() {
        try {
            return $this->getAccount();
        }
        catch (InvalidTokenException $e) {
            $this->delete();
        }
    }

    /**
     * 
     * @return Gw2Account
     */
    public function getAccount() {
        if (!isset($this->account)) {
            $this->account = Gw2Account::getInstance($this->getToken());
        }
        return $this->account;
    }

    /**
     * 
     * @param string $token
     * @return User
     */
    public static function create($token, $code = null) {
        $vault  = TokenVault::getInstance();
        $data   = [
            'code'       => ($code && strlen($code) == 10) ? $code : $vault->newKey(),
            'token'      => $token,
            'lastaccess' => time(),
            'rights'     => [
                self::RIGHT_ACCOUNT,
                self::RIGHT_CHARACTERS,
            ],
        ];
        $object = new self($data);
        $object->getAccount(); // makes token controls
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
