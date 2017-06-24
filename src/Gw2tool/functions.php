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

use Arnapou\GW2Api\Model\AbstractObject;
use Arnapou\GW2Api\Model\Character;
use Arnapou\GW2Api\Model\CraftingDiscipline;
use Arnapou\GW2Api\Model\Guild;
use Arnapou\GW2Api\Model\InventorySlot;
use Arnapou\GW2Api\Model\Item;
use Arnapou\GW2Api\Model\Pet;
use Arnapou\GW2Api\Model\PvpAmulet;
use Arnapou\GW2Api\Model\PvpGame;
use Arnapou\GW2Api\Model\PvpStatsProfession;
use Arnapou\GW2Api\Model\Skill;
use Arnapou\GW2Api\Model\Skin;
use Arnapou\GW2Api\Model\Specialization;
use Arnapou\GW2Api\Model\SpecializationTrait;

/**
 * 
 * @param Item $item
 * @return string
 */
function consumableduration(AbstractObject $item)
{
    $ms = $item->getConsumableDurationMs();
    if ($ms) {
        $h = floor($ms / 3600000);
        $m = round(($ms % 3600000) / 60000);
        if ($h && $m) {
            return $h . 'h' . $m . 'm';
        } elseif ($m) {
            return $m . 'm';
        } elseif ($h) {
            return $h . 'h';
        }
    }
    return '';
}

/**
 * 
 * @param PvpGame $item
 * @return string
 */
function gameduration(PvpGame $item)
{
    $s = $item->getDuration();
    if ($s) {
        return sprintf('%0d:%02d', floor($s / 60), $s % 60);
    }
    return '';
}

/**
 * 
 * @param Item $item
 * @return string
 */
function buffdescription(AbstractObject $item)
{
    $desc = $item->getBuffDescription();
    if ($desc) {
        if ($item->getSubType() == Item::SUBTYPE_UPGRADE_COMPONENT_RUNE) {
            $lines = explode("\n", $desc);
            $s     = '';
            foreach ($lines as $i => $line) {
                $s .= '(' . ($i + 1) . '): ' . $line . "\n";
            }
            return strip_tags($s);
        }
    }
    return strip_tags($desc);
}

/**
 * 
 * @param SpecializationTrait $item
 * @return string
 */
function gwlink_trait(SpecializationTrait $item)
{
    $url = 'trait-' . $item->getId() . '.html';
    return ' class="gwitemlink" data-url="' . $url . '"';
}

/**
 * 
 * @param Specialization $item
 * @return string
 */
function gwlink_specialization(Specialization $item)
{
    $url = 'specialization-' . $item->getId() . '.html';
    return ' class="gwitemlink" data-url="' . $url . '"';
}

/**
 * 
 * @param Skin $item
 * @return string
 */
function gwlink_skin(Skin $item)
{
    $url = 'skin-' . $item->getId() . '.html';
    return ' class="gwitemlink" data-url="' . $url . '"';
}

/**
 * 
 * @param Skill $item
 * @return string
 */
function gwlink_skill(Skill $item)
{
    $url = 'skill-' . $item->getId() . '.html';
    return ' class="gwitemlink" data-url="' . $url . '"';
}

/**
 * 
 * @param Pet $item
 * @return string
 */
function gwlink_pet(Pet $item)
{
    $url = 'pet-' . $item->getId() . '.html';
    return ' class="gwitemlink" data-url="' . $url . '"';
}

/**
 * 
 * @param PvpAmulet $item
 * @return string
 */
function gwlink_pvpamulet(PvpAmulet $item)
{
    $url = 'pvpamulet-' . $item->getId() . '.html';
    return ' class="gwitemlink" data-url="' . $url . '"';
}

/**
 * 
 * @param Item $item
 * @return string
 */
function gwlink_item(Item $item)
{
    $url = 'item-' . $item->getId() . '.html';
    return ' class="gwitemlink" data-url="' . $url . '"';
}

/**
 * 
 * @param InventorySlot $item
 * @return string
 */
function gwlink_inventoryslot(InventorySlot $item)
{
    $url = 'slot-' . $item->getId();
    foreach ($item->getUpgrades() as $upgrade) {
        $url .= '-up' . $upgrade->getId();
    }
    foreach ($item->getInfusions() as $infusion) {
        $url .= '-in' . $infusion->getId();
    }
    if ($item->getSkin()) {
        $url .= '-sk' . $item->getSkin()->getId();
    }
    if ($item->getCount() > 1) {
        $url .= '-cn' . $item->getCount();
    }
    if ($item->getCharges()) {
        $url .= '-ch' . $item->getCharges();
    }
    if ($item->getBinding()) {
        $url .= '-bn' . $item->getBinding();
    }
    if ($item->getBoundTo()) {
        $url .= '-bt' . $item->getBoundTo();
    }
    $stats = $item->getData('stats');
    if (!empty($stats) && is_array($stats)) {
        if (isset($stats['id'])) {
            $url .= '-st' . $item->getItemStat()->getId();
        }
        if (isset($stats['attributes'])) {
            $map = [
                'AgonyResistance'   => 'za',
                'BoonDuration'      => 'zb',
                'ConditionDamage'   => 'zc',
                'ConditionDuration' => 'zd',
                'CritDamage'        => 'ze',
                'Healing'           => 'zf',
                'Power'             => 'zg',
                'Precision'         => 'zh',
                'Toughness'         => 'zi',
                'Vitality'          => 'zj',
            ];
            foreach ($stats['attributes'] as $key => $value) {
                if (isset($map[$key])) {
                    $url .= '-' . $map[$key] . $value;
                }
            }
        }
    }
    $url .= '.html';
    return ' class="gwitemlink" data-url="' . $url . '"';
}

/**
 * 
 * @param string $date
 * @return string
 */
function datediff($date)
{
    if (empty($date)) {
        return '';
    }
    $utc  = new \DateTimeZone('UTC');
    $diff = (new \DateTime('now', $utc))->getTimestamp() - (new \DateTime($date, $utc))->getTimestamp();
    if ($diff < 60) {
        return '< 1 min.';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' min.';
    } elseif ($diff < 86400) {
        $h = floor($diff / 3600);
        return $h . ' hour' . ($h > 1 ? 's' : '');
    } elseif ($diff < 2629728) {
        $d = floor($diff / 86400);
        return $d . ' day' . ($d > 1 ? 's' : '');
    } elseif ($diff < 31536000) {
        $m = floor($diff / 2629728);
        return $m . ' month' . ($m > 1 ? 's' : '');
    } else {
        $m = floor($diff / 2629728);
        $y = floor($m / 12);
        $m -= $y * 12;
        return $y . ' year' . ($y > 1 ? 's' : '') . ($m > 1 ? ', ' . $m . ' month' . ($m > 1 ? 's' : '') : '');
    }
}

/**
 * 
 * @param string $url
 * @return string
 */
function image($url)
{
    if (is_string($url) && !empty($url)) {
        if ($url == 'empty') {
            return '/assets/images/empty.png';
        }
        return str_replace('https://render.guildwars2.com/file/', '/proxy/file/', $url);
    } elseif ($url instanceof InventorySlot) {
        return image($url->getSkin() ? $url->getSkin()->getIcon() : $url->getIcon());
    } elseif ($url instanceof Character || $url instanceof PvpStatsProfession || $url instanceof PvpGame) {
        return '/assets/images/icons/profession-' . strtolower($url->getProfession()) . '.svg';
    } elseif ($url instanceof CraftingDiscipline) {
        return '/assets/images/icons/crafting-' . strtolower($url->getName()) . '.svg';
    } elseif ($url instanceof Guild) {
        return image($url->hasEmblem() ? '/proxy/guild/' . $url->getId() . '.svg' : '');
    } elseif ($url instanceof AbstractObject) {
        $icon = $url->getIcon();
        if ($icon) {
            return image($icon);
        }
    }
    return '/assets/images/nothing.svg';
}

/**
 * 
 * @param string $stat
 * @return string
 */
function imagestat($stat)
{
    if (empty($stat)) {
        return '/assets/images/nothing.svg';
    }
    $stat = strtolower(str_replace("'s", "", $stat));
    $stat = preg_replace('![^a-z]+!', '-', $stat);
    return '/assets/images/stats/' . $stat . '.svg';
}

/**
 * 
 * @param array $array
 * @param integer $n
 * @param boolean $fill
 * @return array
 */
function chunk($array, $n, $fill = true)
{
    $return  = [];
    $current = [];
    $i       = 0;
    foreach ($array as $key => $value) {
        $current[$key] = $value;
        $i++;
        if ($i == $n) {
            $return[] = $current;
            $current  = [];
            $i        = 0;
        }
    }
    if ($i > 0) {
        if ($fill) {
            while ($i < $n) {
                $current[] = null;
                $i++;
            }
        }
        $return[] = $current;
    }
    return $return;
}

/**
 * 
 * @param integer $value
 * @return string
 */
function amount($value)
{
    if ($value === null || $value === '') {
        return '';
    }
    if (is_array($value)) {
        if (isset($value['buy_total']) && $value['buy_total'] != $value['buy']) {
            return amount($value['buy_total']) . ' - ' . amount($value['sell_total']) . ' / ' . amount($value['buy']) . ' - ' . amount($value['sell']);
        } elseif (isset($value['buy'])) {
            return amount($value['buy']) . ' - ' . amount($value['sell']);
        }
        return '';
    } else {
        $g = floor($value / 10000);
        $s = floor($value / 100) % 100;
        $c = $value % 100;
        if ($g) {
            return $g . 'g' . $s . 's';
        } elseif ($s) {
            return $s . 's' . $c . 'c';
        } else {
            return $c . 'c';
        }
    }
}
