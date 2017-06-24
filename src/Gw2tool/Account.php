<?php
/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Gw2tool;

use AppBundle\Entity\Token;
use Arnapou\GW2Api\Model\Achievement;
use Arnapou\GW2Api\Model\AchievementCategory;
use Arnapou\GW2Api\Model\AchievementGroup;
use Arnapou\GW2Api\Model\Bag;
use Arnapou\GW2Api\Model\BankVault;
use Arnapou\GW2Api\Model\Character;
use Arnapou\GW2Api\Model\Currency;
use Arnapou\GW2Api\Model\Dyes;
use Arnapou\GW2Api\Model\Equipment;
use Arnapou\GW2Api\Model\InventorySlot;
use Arnapou\GW2Api\Model\Item;
use Arnapou\GW2Api\Model\Minis;
use Arnapou\GW2Api\Model\Title;
use MongoDB\Collection as MongoCollection;

class Account extends \Arnapou\GW2Api\Model\Account
{

    const STATISTIC_RETENTION_SECONDS = 14400; // 4 hours

    /**
     *
     * @var type 
     */

    private $characterEquipments = [];

    public function getCharacterNames()
    {
        $names = parent::getCharacterNames();
        sort($names);
        return $names;
    }

    public function getAchievementsCategory($id)
    {
        return new AchievementCategory($this->getEnvironment(), $id);
    }

    /**
     * 
     * @return array
     */
    public function getAchievementsCalculation()
    {
        $data = [];
        $aps  = $this->getAccountAchievements();
        foreach ($this->getAchievementsGroups() as /* @var $group AchievementGroup */ $group) {
            $groupCalc = [
                'count'     => 0,
                'total'     => 0,
                'completed' => 0,
            ];
            foreach ($group->getCategories() as /* @var $category AchievementCategory */ $category) {
                $catCalc = [
                    'count'     => 0,
                    'total'     => 0,
                    'completed' => 0,
                ];
                foreach ($category->getAchievements() as /* @var $item Achievement */ $item) {
                    $done = false;
                    if (isset($aps[$item->getId()]) && $aps[$item->getId()]->isDone()) {
                        $done             = true;
                        $catCalc['count'] += 1;
                    }
                    $catCalc['total'] ++;
                }
                $catCalc['completed'] = $catCalc['count'] == $catCalc['total'];
                $groupCalc['count']   += $catCalc['count'];
                $groupCalc['total']   += $catCalc['total'];

                $groupCalc[$category->getId()] = $catCalc;
            }
            $groupCalc['completed'] = $groupCalc['count'] == $groupCalc['total'];

            $data[$group->getId()] = $groupCalc;
        }
        return $data;
    }

    /**
     * 
     * @param MongoCollection $collection
     */
    public function removeStatistics(MongoCollection $collection, $quiet = true)
    {
        try {
            $collection->remove(['account' => $this->getName()]);
        } catch (\Exception $ex) {
            if (!$quiet) {
                throw $ex;
            }
        }
    }

    /**
     * 
     * @param MongoCollection $collection
     */
    public function calculateStatistics(MongoCollection $collection, $quiet = true)
    {
        try {
            $data = $collection->findOne(['account' => $this->getName()]);
            if (!empty($data) && $data['last_update'] > time() - self::STATISTIC_RETENTION_SECONDS) {
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
            $collection->updateOne(['account' => $this->getName()], ['$set' => $data], ['upsert' => true]);

            return true;
        } catch (\Exception $ex) {
            if (!$quiet) {
                throw $ex;
            }
        }
    }

    /**
     * 
     * @param Character $char
     * @return array
     */
    public function getCharacterEquipment(Character $char)
    {
        if (!isset($this->characterEquipments[$char->getName()])) {
            $equipment = [];
            foreach ($char->getEquipments() as $slot => /* @var $object Equipment */ $object) {
                if (in_array($slot, [Character::SLOT_SICKLE, Character::SLOT_AXE, Character::SLOT_PICK])) {
                    continue;
                }
                $slot               = strtr($slot, ['1' => '', '2' => '']);
                $equipment[$slot][] = $object;
            }
            foreach ($char->getBags() as /* @var $bag Bag */ $bag) {
                foreach ($bag->getInventorySlots() as /* @var $object InventorySlot */ $object) {
                    if (
                        empty($object) ||
                        $object->getId() == 0 ||
                        !in_array($object->getRarity(), [Item::RARITY_LEGENDARY, Item::RARITY_ASCENDED, Item::RARITY_EXOTIC]) ||
                        !in_array($object->getType(), [Item::TYPE_ARMOR, Item::TYPE_WEAPON, Item::TYPE_BACK, Item::TYPE_TRINKET])
                    ) {
                        continue;
                    }
                    if ($object->getType() == Item::TYPE_BACK) {
                        $equipment[Character::SLOT_BACKPACK][] = $object;
                    } else {
                        $equipment[$object->getSubType()][] = $object;
                    }
                }
            }
            $this->characterEquipments[$char->getName()] = $equipment;
        }
        return $this->characterEquipments[$char->getName()];
    }

    /**
     * 
     * @return array
     */
    public function getSkinItems($skin_ids)
    {
        if (empty($skin_ids)) {
            return [];
        }

        $env        = $this->getEnvironment();
        $storage    = $env->getStorage();
        $lang       = $env->getLang();
        $collection = $storage->getCollection($lang, 'items');
        $objects    = [];
        foreach ($collection->find(['data.default_skin' => ['$in' => $skin_ids]]) as $doc) {
            if (!isset($objects[$doc['data']['default_skin']])) {
                $objects[$doc['data']['default_skin']] = $doc['data']['id'];
            }
        }

        foreach ($objects as $skin_id => $item_id) {
            $objects[$skin_id] = new Item($env, $item_id);
        }
        return $objects;
    }

    /**
     * 
     * @param Dyes $dyes
     * @return array
     */
    public function getDyesByRarity(Dyes $dyes = null)
    {
        $colors  = $dyes->getColors();
        $map     = $this->getDyeItems();
        $grouped = [];
        foreach ($colors as /* @var $color Color */ $color) {
            if (isset($map[$color->getId()])) {
                $item   = $map[$color->getId()];
                $rarity = $item->getRarity();
            } else {
                $item   = null;
                $rarity = '';
            }
            if (empty($grouped[$rarity])) {
                $grouped[$rarity] = [
                    'count' => 0,
                    'total' => 0,
                    'items' => [],
                ];
            }
            $grouped[$rarity]['items'][] = [$color, $item];
            $grouped[$rarity]['count']   += $color->isUnlocked() ? 1 : 0;
            $grouped[$rarity]['total'] ++;
        }
        return $grouped;
    }

    /**
     * 
     * @return array
     */
    public function getTitlesAchievementCategories()
    {

        $env   = $this->getEnvironment();
        $lang  = $env->getLang();
        $cache = $env->getCache();

        $cachekey = 'map-titles:' . $lang;
        $objects  = $cache->get($cachekey);
        if (empty($objects) or true) {
            $objects = [];
            $map     = [];
            foreach ($this->getTitles()->getTitles() as /* @var $title Title */ $title) {
                $map[$title->getAchievementId()] = $title->getId();
            }
            foreach ($this->getAchievementsGroups() as /* @var $group AchievementGroup */ $group) {
                foreach ($group->getCategories() as /* @var $category AchievementCategory */ $category) {
                    foreach ($category->getAchievements() as /* @var $achievement  */ $achievement) {
                        if (isset($map[$achievement->getId()])) {
                            $objects[$group->getId()][$category->getId()][] = $map[$achievement->getId()];
                        }
                    }
                }
            }

            $cache->set($cachekey, $objects, time() + 3600);
        }
        if (!empty($objects)) {
            $output = [];
            $titles = $this->getTitles()->getTitles();
            foreach ($objects as $groupId => $categories) {
                $output[$groupId] = [
                    'obj'   => $groupId ? new AchievementGroup($env, $groupId) : null,
                    'items' => [],
                    'count' => 0,
                    'total' => 0,
                ];
                foreach ($categories as $categoryId => $titleIds) {
                    $output[$groupId]['items'][$categoryId] = [
                        'obj'   => $categoryId ? new AchievementCategory($env, $categoryId) : null,
                        'items' => $titleIds,
                    ];
                    foreach ($titleIds as $titleId) {
                        if (isset($titles[$titleId]) && $titles[$titleId]->isUnlocked()) {
                            $output[$groupId]['count'] ++;
                        }
                        $output[$groupId]['total'] ++;
                    }
                }
            }
            return $output;
        }
        return null;
    }

    /**
     * 
     * @return array
     */
    public function getDyeItems()
    {

        $env     = $this->getEnvironment();
        $storage = $env->getStorage();
        $lang    = $env->getLang();
        $cache   = $env->getCache();

        $cachekey = 'map-dyes:' . $lang;
        $objects  = $cache->get($cachekey);
        if (empty($objects)) {
            $collection = $storage->getCollection($lang, 'items');
            $objects    = [];
            foreach ($collection->find(['data.details.color_id' => ['$gt' => 0]]) as $doc) {
                $objects[$doc['data']['details']['color_id']] = $doc['data']['id'];
            }
            $cache->set($cachekey, $objects, time() + 3600);
        }
        if ($objects) {
            foreach ($objects as $colorId => $itemId) {
                $objects[$colorId] = new Item($env, $itemId);
            }
            return $objects;
        }
        return null;
    }

    /**
     * 
     * @param Minis $minis
     * @return array
     */
    public function getMinisByRarity(Minis $minis = null)
    {
        $minis   = $minis->getMinis();
        $grouped = [];
        foreach ($minis as /* @var $mini Mini */ $mini) {
            $item = $mini->getItem();
            if ($item) {
                $rarity = $item->getRarity();
            } else {
                $item   = null;
                $rarity = '';
            }
            if (empty($grouped[$rarity])) {
                $grouped[$rarity] = [
                    'count' => 0,
                    'total' => 0,
                    'items' => [],
                ];
            }
            $grouped[$rarity]['items'][] = [$mini, $item];
            $grouped[$rarity]['count']   += $mini->isUnlocked() ? 1 : 0;
            $grouped[$rarity]['total'] ++;
        }
        return $grouped;
    }

    /**
     * 
     * @return int
     */
    public function getCharactersCount()
    {
        return count($this->getCharacters());
    }

    /**
     * 
     * @return int
     */
    public function getCharactersLevel80Count()
    {
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
    public function getStatsWallet()
    {
        $ids  = $this->getEnvironment()->getClientVersion2()->apiCurrencies();
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
    public function getStatsPvp()
    {
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
    public function getStatsUnlocks()
    {
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
    public function getTotalDeaths()
    {
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
    public function getTotalAge()
    {
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
    protected function getItemsRarityCount($rarity)
    {
        $data = [
            Item::TYPE_ARMOR   => 0,
            Item::TYPE_WEAPON  => 0,
            Item::TYPE_TRINKET => 0,
            Item::TYPE_BACK    => 0,
            'Total'            => 0,
        ];
        foreach ($this->getCharacters() as /* @var $character Character */ $character) {
            foreach ($this->getCharacterEquipment($character) as $items) {
                foreach ($items as /* @var $item Item */ $item) {
                    if ($item->getRarity() == $rarity) {
                        if (in_array($item->getType(), [Item::TYPE_ARMOR, Item::TYPE_WEAPON, Item::TYPE_TRINKET, Item::TYPE_BACK])) {
                            $data[$item->getType()] ++;
                            $data['Total'] ++;
                        }
                    }
                }
            }
        }
        if ($this->hasPermission(self::PERMISSION_INVENTORIES)) {
            foreach ($this->getBankVaults() as /* @var $vault BankVault */ $vault) {
                foreach ($vault->getInventorySlots() as /* @var $item Item */ $item) {
                    if (!empty($item) && $item->getRarity() == $rarity) {
                        if (in_array($item->getType(), [Item::TYPE_ARMOR, Item::TYPE_WEAPON, Item::TYPE_TRINKET, Item::TYPE_BACK])) {
                            $data[$item->getType()] ++;
                            $data['Total'] ++;
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
    public function getAscendedCount()
    {
        return $this->getItemsRarityCount(Item::RARITY_ASCENDED);
    }

    /**
     * 
     * @return array
     */
    public function getLegendariesCount()
    {
        return $this->getItemsRarityCount(Item::RARITY_LEGENDARY);
    }

    /**
     * 
     * @return array
     */
    public function getGenderCount()
    {
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
    public function getRaceCount()
    {
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
    public function getProfessionCount()
    {
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
            $data[$character->getData('profession')] ++;
        }
        return $data;
    }

    /**
     * 
     * @return array
     */
    public function getCurrencies()
    {
        $client     = $this->getEnvironment()->getClientVersion2();
        $currencies = $client->apiCurrencies($client->apiCurrencies());
        usort($currencies, function($a, $b) {
            if ($a['order'] == $b['order']) {
                return 0;
            }
            return $a['order'] > $b['order'] ? 1 : -1;
        });
        return $currencies;
    }
}
