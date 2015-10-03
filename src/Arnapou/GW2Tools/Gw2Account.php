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
use Arnapou\GW2Api\Model\BankVault;
use Arnapou\GW2Api\Model\Character;
use Arnapou\GW2Api\Model\Item;

class Gw2Account extends \Arnapou\GW2Api\Model\Account {

    /**
     * 
     * @param string $accesToken
     * @return GwAccount
     */
    public static function getInstance($accesToken) {
        $lang    = Translator::getInstance()->getLang();
        $client  = SimpleClient::getInstance($lang);
        $account = new self($client, $accesToken);
        if (!$account->hasPermission(self::PERMISSION_ACCOUNT)) {
            throw new MissingPermissionException(self::PERMISSION_ACCOUNT);
        }
        if (!$account->hasPermission(self::PERMISSION_CHARACTERS)) {
            throw new MissingPermissionException(self::PERMISSION_CHARACTERS);
        }
        return $account;
    }

    public function removeStatistics() {
        try {
            $collection = Statistics::getInstance()->getCollection();
            $collection->remove(['account' => $this->getName()]);
        }
        catch (\Exception $ex) {
            
        }
    }

    public function calculateStatistics() {
        try {
            $collection = Statistics::getInstance()->getCollection();

            $data = $collection->findOne(['account' => $this->getName()]);
            if (!empty($data) && time() - $data['last_update'] < 86400) {
                return;
            }

            $data = [
                'account'              => $this->getName(),
                'last_update'          => time(),
                'wallet'               => $this->getStatsWallet(),
                'unlocks'              => $this->getStatsUnlocks(),
                'pvp'                  => $this->getStatsPvp(),
                'race'                 => $this->getRaceCount(),
                'gender'               => $this->getGenderCount(),
                'profession'           => $this->getProfessionCount(),
                'generic'              => [
                    'characters' => $this->getCharactersCount(),
                    'age'        => $this->getTotalAge(),
                    'deaths'     => $this->getTotalDeaths(),
                    'level80'    => $this->getCharactersLevel80Count(),
                ],
                Item::RARITY_ASCENDED  => $this->getAscendedCount(),
                Item::RARITY_LEGENDARY => $this->getLegendariesCount(),
            ];
            $collection->update([ 'account' => $this->getName()], $data, [ 'upsert' => true]);
        }
        catch (\Exception $ex) {
            
        }
    }

    /**
     * 
     * @return array
     */
    public function getCurrencies() {
        $currencies = $this->client->v2_currencies($this->client->v2_currencies());
        usort($currencies, function($a, $b) {
            if ($a['order'] == $b['order']) {
                return 0;
            }
            return $a['order'] > $b['order'] ? 1 : -1;
        });
        return $currencies;
    }

    /**
     * 
     * @return int
     */
    public function getCharactersCount() {
        return count($this->getCharacters());
    }

    /**
     * 
     * @return int
     */
    public function getCharactersLevel80Count() {
        $total = 0;
        foreach ($this->getCharacters() as /* @var $character Character */ $character) {
            if ($character->getLevel() == 80) {
                $total++;
            }
        }
        return $total;
    }

    /**
     * 
     * @return array
     */
    public function getStatsWallet() {
        $ids  = $this->client->v2_currencies();
        $data = array_combine($ids, array_fill(0, count($ids), null));
        if ($this->hasPermission(self::PERMISSION_WALLET)) {
            foreach ($this->getWallet() as /* @var $currency Currency */ $currency) {
                $data[$currency->getId()] = $currency->getQuantity();
            }
        }
        return $data;
    }

    /**
     * 
     * @return array
     */
    public function getStatsPvp() {
        $data = [
            'rank'    => null,
            'total'   => null,
            'wins'    => null,
            'winrate' => null,
        ];
        if ($this->hasPermission(self::PERMISSION_PVP)) {
            $pvp             = $this->getPvp();
            $data['rank']    = $pvp->getRank();
            $data['total']   = $pvp->getAggregateStats()->getTotal();
            $data['wins']    = $pvp->getAggregateStats()->getWins();
            $data['winrate'] = $pvp->getAggregateStats()->getWinRate();
        }
        return $data;
    }

    /**
     * 
     * @return array
     */
    public function getStatsUnlocks() {
        $data = [
            'dyes'  => null,
            'skins' => null,
        ];
        if ($this->hasPermission(self::PERMISSION_UNLOCKS)) {
            $data['dyes']  = $this->getDyes()->getCount();
            $data['skins'] = $this->getWardrobe()->getCount();
        }
        return $data;
    }

    /**
     * 
     * @return array
     */
    public function getTotalDeaths() {
        $total = 0;
        foreach ($this->getCharacters() as /* @var $character Character */ $character) {
            $total += $character->getDeaths();
        }
        return $total;
    }

    /**
     * 
     * @return array
     */
    public function getTotalAge() {
        $total = 0;
        foreach ($this->getCharacters() as /* @var $character Character */ $character) {
            $total += $character->getAge();
        }
        return $total;
    }

    /**
     * 
     * @return array
     */
    protected function getItemsRarityCount($rarity) {
        $data = [
            Item::TYPE_ARMOR   => 0,
            Item::TYPE_WEAPON  => 0,
            Item::TYPE_TRINKET => 0,
            Item::TYPE_BACK    => 0,
            'Total'            => 0,
        ];
        foreach ($this->getCharacters() as /* @var $character Character */ $character) {
            foreach ($character->getInventoryStuff() as $items) {
                foreach ($items as /* @var $item Item */ $item) {
                    if ($item->getRarity() == $rarity) {
                        $data['Total'] ++;
                        if (in_array($item->getType(), [Item::TYPE_ARMOR, Item::TYPE_WEAPON, Item::TYPE_TRINKET, Item::TYPE_BACK])) {
                            $data[$item->getType()] ++;
                        }
                    }
                }
            }
        }
        if ($this->hasPermission(self::PERMISSION_INVENTORIES)) {
            foreach ($this->getBankVaults() as /* @var $vault BankVault */ $vault) {
                foreach ($vault->getItems() as /* @var $item Item */ $item) {
                    if (!empty($item) && $item->getRarity() == $rarity) {
                        $data['Total'] ++;
                        if (in_array($item->getType(), [Item::TYPE_ARMOR, Item::TYPE_WEAPON, Item::TYPE_TRINKET, Item::TYPE_BACK])) {
                            $data[$item->getType()] ++;
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 
     * @return array
     */
    public function getAscendedCount() {
        return $this->getItemsRarityCount(Item::RARITY_ASCENDED);
    }

    /**
     * 
     * @return array
     */
    public function getLegendariesCount() {
        return $this->getItemsRarityCount(Item::RARITY_LEGENDARY);
    }

    /**
     * 
     * @return array
     */
    public function getGenderCount() {
        $data = [
            Character::GENDER_MALE   => 0,
            Character::GENDER_FEMALE => 0,
        ];
        foreach ($this->getCharacters() as /* @var $character Character */ $character) {
            $data[$character->getGender()] ++;
        }
        return $data;
    }

    /**
     * 
     * @return array
     */
    public function getRaceCount() {
        $data = [
            Character::RACE_ASURA   => 0,
            Character::RACE_CHARR   => 0,
            Character::RACE_HUMAN   => 0,
            Character::RACE_NORN    => 0,
            Character::RACE_SYLVARI => 0,
        ];
        foreach ($this->getCharacters() as /* @var $character Character */ $character) {
            $data[$character->getRace()] ++;
        }
        return $data;
    }

    /**
     * 
     * @return array
     */
    public function getProfessionCount() {
        $data = [
            Character::PROFESSION_ELEMENTALIST => 0,
            Character::PROFESSION_ENGINEER     => 0,
            Character::PROFESSION_GUARDIAN     => 0,
            Character::PROFESSION_MESMER       => 0,
            Character::PROFESSION_NECROMANCER  => 0,
            Character::PROFESSION_RANGER       => 0,
            Character::PROFESSION_REVENANT     => 0,
            Character::PROFESSION_THIEF        => 0,
            Character::PROFESSION_WARRIOR      => 0,
        ];
        foreach ($this->getCharacters() as /* @var $character Character */ $character) {
            $data[$character->getProfession()] ++;
        }
        return $data;
    }

}
