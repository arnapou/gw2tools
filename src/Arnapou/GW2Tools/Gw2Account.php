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
use Arnapou\GW2Api\Exception\MissingPermissionException;
use Arnapou\GW2Tools\Service;

class Gw2Account extends \Arnapou\GW2Api\Model\Account {

    /**
     * 
     * @param string $accesToken
     * @param string $lang
     * @return GwAccount
     */
    public static function getInstance($accesToken, $lang = AbstractClient::LANG_EN) {
        $client  = Service::getInstance()->newSimpleClient($lang);
        $account = new self($client, $accesToken);
        if (!$account->hasPermission(self::PERMISSION_ACCOUNT)) {
            throw new MissingPermissionException(self::PERMISSION_ACCOUNT);
        }
        if (!$account->hasPermission(self::PERMISSION_CHARACTERS)) {
            throw new MissingPermissionException(self::PERMISSION_CHARACTERS);
        }
        return $account;
    }

}
