<?php

namespace AppBundle\Entity;

use Arnapou\GW2Api\Model\Character;
use Arnapou\GW2Api\Model\InventorySlot;
use Arnapou\GW2Api\Model\Item;
use Doctrine\ORM\Mapping as ORM;
use Gw2tool\Account;

/**
 * Token
 *
 * @ORM\Table(name="raidmember", indexes={
 *      @ORM\Index(name="name"          , columns={"name"}    ),
 *      @ORM\Index(name="date_creation" , columns={"date_creation"} )
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RaidMemberRepository")
 */
class RaidMember
{

    const CHECK_DELAY = 900;

    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    private $name = '';

    /**
     * @var RaidRoster
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\RaidRoster")
     * @ORM\JoinColumn(name="roster_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $roster;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="string", length=50)
     */
    private $text = '';

    /**
     * @var bool
     *
     * @ORM\Column(name="is_officer", type="boolean")
     */
    private $isOfficer = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive = true;

    /**
     * @var int
     *
     * @ORM\Column(name="date_creation", type="integer")
     */
    private $dateCreation;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text")
     */
    private $data = '';

    public function __construct()
    {
        $this->dateCreation = time();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getNameParts()
    {
        if (\preg_match('!^(.+)\.([0-9]+)$!', $this->name, $m)) {
            return [$m[1], $m[2]];
        }
        return [$this->name, ''];
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function hasData()
    {
        return !empty($this->data);
    }

    /**
     * @return array
     */
    public function getData()
    {
        if ($this->data) {
            return unserialize($this->data);
        }
        return [];
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = !empty($data) && is_array($data) ? serialize($data) : '';
    }

    /**
     * @return RaidRoster
     */
    public function getRoster()
    {
        return $this->roster;
    }

    /**
     * @param RaidRoster $roster
     */
    public function setRoster($roster)
    {
        $this->roster = $roster;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return bool
     */
    public function isCreator()
    {
        return $this->name === $this->getRoster()->getCreator();
    }

    /**
     * @return bool
     */
    public function isOfficer()
    {
        return $this->isOfficer;
    }

    /**
     * @param bool $bool
     */
    public function setIsOfficer($bool)
    {
        $this->isOfficer = $bool;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @param bool $bool
     */
    public function setIsActive($bool)
    {
        $this->isActive = $bool;
    }

    /**
     * @return int
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * @param int $dateCreation
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;
    }

    /**
     * @return bool
     */
    public function canModifyRoster()
    {
        return $this->isOfficer || $this->isCreator();
    }

    /**
     * @return bool
     */
    public function canAddMemberRoster()
    {
        return $this->isOfficer || $this->isCreator();
    }

    /**
     * @return bool
     */
    public function canDeleteRoster()
    {
        return $this->isCreator();
    }

    /**
     * @return bool
     */
    public function canLeaveRoster()
    {
        return $this->isCreator() ? false : true;
    }

    /**
     * @param Account $account
     * @return bool
     */
    public function checkData(Account $account)
    {
        $data      = $this->getData();
        $lastCheck = isset($data['last_check']) ? $data['last_check'] : 0;
        if (time() - $lastCheck > self::CHECK_DELAY) {
            try {

                $data['characters'] = [];
                if ($account->hasPermission(Account::PERMISSION_CHARACTERS)) {
                    foreach ($account->getCharacters() as $character) {
                        if ($character->getLevel() >= 80) {
                            $char   = [
                                'profession' => $character->getData('profession'),
                                'age'        => $character->getAge(),
                            ];
                            $blocks = [];
                            foreach ($character->getEquipmentsBySubtype() as $subtype => $items) {
                                foreach ($items as $item) {
                                    /** @var $item InventorySlot */
                                    $statName = $item->getStatName();
                                    $rarity   = $item->getRarity();
                                    if (empty($statName) || $subtype === Item::SUBTYPE_ARMOR_HELM_AQUATIC) {
                                        continue;
                                    }
                                    $key = $statName . ':' . $rarity;
                                    $cat = Item::TYPE_ARMOR;
                                    if ($item->getType() == Item::TYPE_ARMOR) {
                                        $key  .= ':' . Item::TYPE_ARMOR;
                                        $type = Item::TYPE_ARMOR;
                                    } elseif ($item->getType() == Item::TYPE_WEAPON) {
                                        $key  .= ':' . $subtype;
                                        $type = $subtype;
                                        $cat  = Item::TYPE_WEAPON;
                                    } elseif ($item->getType() == Item::TYPE_TRINKET) {
                                        $key  .= ':' . Item::TYPE_TRINKET;
                                        $type = Item::TYPE_TRINKET;
                                        $cat  = Item::TYPE_TRINKET;
                                    } elseif ($subtype === Character::SLOT_BACKPACK) {
                                        $key  .= ':' . Character::SLOT_BACKPACK;
                                        $type = Character::SLOT_BACKPACK;
                                    } else {
                                        continue;
                                    }
                                    if (!isset($blocks[$key])) {
                                        $blocks[$key] = [
                                            'count'    => 0,
                                            'cat'      => $cat,
                                            'type'     => $type,
                                            'stat'     => $statName,
                                            'rarity'   => $rarity,
                                            'upgrades' => [],
                                        ];
                                    }
                                    $blocks[$key]['count']++;
                                    foreach ($item->getUpgrades() as $upgrade) {
                                        if (!isset($blocks[$key]['upgrades'][$upgrade->getId()])) {
                                            $blocks[$key]['upgrades'][$upgrade->getId()] = 1;
                                        } else {
                                            $blocks[$key]['upgrades'][$upgrade->getId()]++;
                                        }
                                    }
                                }

                            }
                            usort($blocks, function ($a, $b) {
                                return $a['type'] <=> $b['type']
                                    ?: $a['rarity'] <=> $b['rarity']
                                        ?: $a['stat'] <=> $b['stat'];
                            });

                            $char['blocks'] = $blocks;

                            $data['characters'][$character->getName()] = $char;
                        }
                    }
                }


            } catch (\Exception $e) {

            }
            $data['last_check'] = time();
            $this->setData($data);
            return true;
        }
        return false;
    }

    /**
     * @param RaidWeek $week
     * @return bool
     */
    public function canModifyDay(RaidWeek $week)
    {
        return $week->getMember()->getRoster()->getId() == $this->getRoster()->getId() // only for the same roster
            && $week->getMember()->getName() === $this->getRoster()->getCreator()
            && (
                $this->isCreator() || $this->isOfficer()
            );
    }

    /**
     * @param RaidMember $member
     * @return bool
     */
    public function canRemoveMember(RaidMember $member)
    {
        return $member->getRoster()->getId() == $this->getRoster()->getId() // only for the same roster
            && $this->canAddMemberRoster()                                  // should have the right to add
            && $member->getId() != $this->getId()                           // cannot remove itself
            && !$member->isCreator()                                        // cannot remove the creator
            ;
    }

    /**
     * @param RaidMember $member
     * @return bool
     */
    public function canEditMember(RaidMember $member)
    {
        return $member->getRoster()->getId() == $this->getRoster()->getId() // only for the same roster
            && (
                $this->isCreator()
                || $this->isOfficer()
                || $member->getId() === $this->getId()
            );
    }

    /**
     * @param RaidWeek $week
     * @return bool
     */
    public function canModifyWeek(RaidWeek $week)
    {
        return $week->getMember()->getRoster()->getId() == $this->getRoster()->getId() // only for the same roster
            && (
                $this->isCreator()
                || $this->isOfficer()
                || $week->getMember()->getId() === $this->getId()
            );
    }

}
