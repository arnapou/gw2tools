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
use Arnapou\GW2Api\Exception\InvalidTokenException;
use Arnapou\GW2Tools\Service;
use Arnapou\Toolbox\Exception\Exception;

class User {

    /**
     *
     * @var string
     */
    protected $code;

    /**
     *
     * @var string
     */
    protected $token;

    /**
     *
     * @var int
     */
    protected $lastaccess;

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
     * @return \Arnapou\Toolbox\Connection\Mysql
     */
    public static function getConnection() {
        return Service::getInstance()->getConnection();
    }

    /**
     * 
     * @return string
     */
    public static function table() {
        return Service::getInstance()->getConfig()->get('table.tokens', 'tokens');
    }

    /**
     * 
     * @param array $row
     */
    public function __construct($row) {
        $this->code       = $row['code'];
        $this->token      = $row['token'];
        $this->lastaccess = $row['lastaccess'];
        $this->data       = empty($row['data']) ? [] : (is_array($row['data']) ? $row['data'] : unserialize($row['data']));
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
     * @return string
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * 
     * @return string
     */
    public function getToken() {
        return $this->token;
    }

    /**
     * 
     * @return string
     */
    public function getLastaccess() {
        return $this->lastaccess;
    }

    /**
     * 
     * @return User
     */
    public function setLastaccess() {
        $this->lastaccess = time();
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
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * 
     * @param string $token
     * @return mixed
     */
    public function setToken($token) {
        $this->token = $token;
        return $this;
    }

    /**
     * 
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function remove($key) {
        unset($this->data[$key]);
        return $this;
    }

    /**
     * 
     * @return User
     */
    public function save() {
        $conn = self::getConnection();
        $this->setLastaccess();
        $name = $this->getAccount()->getName();
        if (!empty($name)) {
            $conn->executeReplace(self::table(), [
                'name'       => $name,
                'code'       => $this->code,
                'token'      => $this->token,
                'lastaccess' => $this->lastaccess,
                'data'       => serialize($this->data),
            ]);
        }
        return $this;
    }

    /**
     * 
     */
    public function delete() {
        $conn = self::getConnection();
        $conn->executeDelete(self::table(), '`code` = ' . $conn->quote($this->code));
    }

    /**
     * 
     * @return Gw2Account
     */
    public function checkAccount() {
        try {
            $account = $this->getAccount();
            if (empty($account->getName())) {
                throw new EmptyAccountNameException();
            }
            return $account;
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
        $conn = self::getConnection();
        if (strlen($code) !== 10) {
            $chars   = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890';
            $nbchars = strlen($chars);
            $code    = '';
            do {
                $n = 10;
                while ($n--) {
                    $code .= $chars[mt_rand(0, $nbchars - 1)];
                }
            }
            while ($code == $conn->getValue("SELECT `code` FROM `" . self::table() . "` WHERE `code`=" . $conn->quote($code)));
        }

        $object = new self([
            'code'       => $code,
            'token'      => $token,
            'lastaccess' => time(),
            'data'       => [
                'rights' => [
                    'account',
                    'characters',
                ],
            ],
        ]);
        $object->save();
        return $object;
    }

    /**
     * 
     * @param string $token
     * @return User
     */
    public static function findByToken($token) {
        $conn = self::getConnection();
        $sql  = "SELECT * FROM `" . self::table() . "` WHERE `token`=" . $conn->quote($token);
        $data = $conn->getFirstRow($sql);
        if ($data) {
            return new self($data);
        }
        return null;
    }

    /**
     * 
     * @param string $code
     * @return User
     */
    public static function findByCode($code) {
        $conn = self::getConnection();
        $sql  = "SELECT * FROM `" . self::table() . "` WHERE `code`=" . $conn->quote($code);
        $data = $conn->getFirstRow($sql);
        if ($data) {
            return new self($data);
        }
        return null;
    }

}
