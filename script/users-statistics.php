<?php

/*
 * This file is part of the Arnapou FileStore package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include_once __DIR__ . '/../vendor/autoload.php';

Arnapou\GW2Tools\Service::getInstance();

use Arnapou\GW2Api\Model\Account;
use Arnapou\GW2Api\Model\Character;
use Arnapou\GW2Api\Model\Currency;
use Arnapou\GW2Tools\MongoCache;
use Arnapou\GW2Tools\User;

$conn       = User::getConnection();
$collection = MongoCache::getInstance(false)->getMongoDB()->selectCollection('statistics');
$collection->ensureIndex('account');

$incrementData = function(&$data, $key, $subkey, $value = 1) {
    if (!isset($data[$key])) {
        $data[$key] = [];
    }
    if (!isset($data[$key][$subkey])) {
        $data[$key][$subkey] = $value;
    }
    else {
        $data[$key][$subkey] += $value;
    }
};

/*
 * STATISTICS
 */
foreach ($conn->query("SELECT * FROM `" . User::table() . "`") as $row) {
    try {
        $user    = new User($row);
        $account = $user->getAccount();
        $data    = [
            'account'      => $account->getName(),
            'time_updated' => time(),
        ];
        if ($account->hasPermission(Account::PERMISSION_WALLET)) {
            foreach ($account->getWallet() as /* @var $currency Currency */ $currency) {
                $data['wallet'][$currency->getId()] = $currency->getQuantity();
            }
        }
        if ($account->hasPermission(Account::PERMISSION_UNLOCKS)) {
            $incrementData($data, 'unlocks', 'dyes', $account->getDyes()->getCount());
            $incrementData($data, 'unlocks', 'skins', $account->getWardrobe()->getCount());
        }
        if ($account->hasPermission(Account::PERMISSION_PVP)) {
            $incrementData($data, 'pvp', 'rank', $account->getPvp()->getRank());
            $incrementData($data, 'pvp', 'total', $account->getPvp()->getAggregateStats()->getTotal());
            $incrementData($data, 'pvp', 'wins', $account->getPvp()->getAggregateStats()->getWins());
            $incrementData($data, 'pvp', 'winrate', $account->getPvp()->getAggregateStats()->getWinRate());
        }
        foreach ($account->getCharacters() as /* @var $character Character */ $character) {
            $incrementData($data, 'race', $character->getRace());
            $incrementData($data, 'gender', $character->getGender());
            $incrementData($data, 'profession', $character->getProfession());
            $incrementData($data, 'generic', 'characters');
            $incrementData($data, 'generic', 'age', $character->getAge());
            $incrementData($data, 'generic', 'deaths', $character->getDeaths());
            $incrementData($data, 'generic', 'level80', $character->getLevel() == 80 ? 1 : 0);
            $collection->update([ 'account' => $account->getName()], $data, [ 'upsert' => true]);
        }
    }
    catch (Exception $ex) {
        echo $user->getAccount()->getName() . " " . $ex->getMessage() . "\n";
    }
}