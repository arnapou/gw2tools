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
use Arnapou\GW2Tools\Service;
use Arnapou\Toolbox\Functions\Directory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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
            $path           = Service::getInstance()->getPathData() . '/tokens';
            Directory::createIfNotExists($path);
            self::$instance = new self($path);
        }
        return self::$instance;
    }

    static public function cleanTokens() {
        $path  = Service::getInstance()->getPathData() . '/tokens';
        Directory::createIfNotExists($path);
        $vault = self::getInstance();
        foreach (Finder::create()->name('*.php')->in($path) as /* @var $file SplFileInfo */ $file) {
            try {
                $fh      = fopen($file->getPathname(), 'r');
                $content = fread($fh, 4096);
                fclose($fh);
                if (preg_match('!^<\?php\n/\* key = (.*?) \*/!si', $content, $m)) {
                    $key    = $m[1];
                    $skey   = str_replace('.', ' [', $key) . ']';
                    $length = strlen($key);
                    try {
                        if ($length == 10) { // simple code
                            $value = $vault->get($key);
                            $vault->remove($key);
                            $vault->remove($value);
                            if (!empty($value)) {
                                User::create($value, $key)->save();
                            }
                            echo "migrate $skey\n";
                        }
                        elseif ($length > 70 && $length < 75) { // simple token
                            $value = $vault->get($key);
                            $vault->remove($key);
                            $vault->remove($value);
                            if (!empty($value)) {
                                User::create($key, $value)->save();
                            }
                            echo "migrate $skey\n";
                        }
                        else {
                            if (strpos($key, 'token.') === 0) { // new token
                                $user = User::findByToken(substr($key, 6));
                            }
                            elseif (strpos($key, 'code.') === 0) { // new code
                                $user = User::findByCode(substr($key, 5));
                            }
                            if (empty($user)) {
                                $vault->remove($key);
                                echo "remove $key\n";
                            }
                            elseif (time() - $user->getLastaccess() > 86400 * 180) {
                                echo "expired $skey\n";
                                $user->delete();
                            }
                            else {
                                try {
                                    Service::getInstance()->newSimpleClient();
                                    $user->save();
                                    echo "user ok $skey\n";
                                }
                                catch (InvalidTokenException $e) {
                                    echo "delete  $skey\n";
                                    $user->delete();
                                }
                            }
                        }
                    }
                    catch (\Exception $e) {
                        echo $e->getMessage() . "\n";
                    }
                }
            }
            catch (\Exception $e) {
                
            }
        }
    }

    public function newKey() {
        $chars   = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890';
        $nbchars = strlen($chars);
        $key     = '';
        do {
            $n = 10;
            while ($n--) {
                $key .= $chars[mt_rand(0, $nbchars - 1)];
            }
        }
        while ($this->exists($key));
        return $key;
    }

}
