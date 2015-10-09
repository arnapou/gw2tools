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

use Arnapou\GW2Api\Model\AbstractObject;
use Arnapou\GW2Api\Model\Character;
use Arnapou\GW2Api\Model\CraftingDiscipline;
use Arnapou\GW2Api\Model\Guild;
use Arnapou\GW2Api\Model\InventorySlot;
use Arnapou\GW2Api\Model\Item;
use Arnapou\GW2Api\Model\PvpGame;
use Arnapou\GW2Api\Model\PvpStatsProfession;
use Arnapou\GW2Api\Model\Skin;
use Arnapou\GW2Api\Model\Specialization;
use Arnapou\GW2Api\Model\SpecializationTrait;

/**
 * 
 * @param Item $item
 * @return string
 */
function consumableduration(Item $item) {
    $ms = $item->getConsumableDurationMs();
    if ($ms) {
        $h = floor($ms / 3600000);
        $m = round(($ms % 3600000) / 60000);
        if ($h && $m) {
            return $h . 'h' . $m . 'm';
        }
        elseif ($m) {
            return $m . 'm';
        }
        elseif ($h) {
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
function gameduration(PvpGame $item) {
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
function buffdescription(Item $item) {
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
function gwlink_trait(SpecializationTrait $item) {
    $url = 'trait/' . $item->getId() . '.html';
    return ' class="gwitemlink" data-url="' . $url . '"';
}

/**
 * 
 * @param Specialization $item
 * @return string
 */
function gwlink_specialization(Specialization $item) {
    $url = 'specialization/' . $item->getId() . '.html';
    return ' class="gwitemlink" data-url="' . $url . '"';
}

/**
 * 
 * @param Skin $item
 * @return string
 */
function gwlink_skin(Skin $item) {
    $url = 'skin/' . $item->getId() . '.html';
    return ' class="gwitemlink" data-url="' . $url . '"';
}

/**
 * 
 * @param Item $item
 * @return string
 */
function gwlink_item(Item $item) {
    $url = 'item/' . $item->getId() . '.html';
    return ' class="gwitemlink" data-url="' . $url . '"';
}

/**
 * 
 * @param InventorySlot $item
 * @return string
 */
function gwlink_inventoryslot(InventorySlot $item) {
    $url = 'item/' . $item->getId();
    if (count($item->getInfusions())) {
        $url .= '/inf';
        foreach ($item->getInfusions() as $infusion) {
            $url .= '-' . $infusion->getId();
        }
    }
    if (count($item->getUpgrades())) {
        $url .= '/upg';
        foreach ($item->getUpgrades() as $upgrade) {
            $url .= '-' . $upgrade->getId();
        }
    }
    if ($item->getSkin()) {
        $url .= '/ski-' . $item->getSkin()->getId();
    }
    if ($item->getCount() > 1) {
        $url .= '/cnt-' . $item->getCount();
    }
    $url.= '.html';
    return ' class="gwitemlink" data-url="' . $url . '"';
}

/**
 * 
 * @param string $date
 * @return string
 */
function datediff($date) {
    $utc  = new \DateTimeZone('UTC');
    $diff = (new \DateTime('now', $utc))->getTimestamp() - (new \DateTime($date, $utc))->getTimestamp();
    if ($diff < 60) {
        return '< 1 min.';
    }
    elseif ($diff < 3600) {
        return floor($diff / 60) . ' min.';
    }
    elseif ($diff < 86400) {
        $h = floor($diff / 3600);
        return $h . ' hour' . ($h > 1 ? 's' : '');
    }
    elseif ($diff < 2629728) {
        $d = floor($diff / 86400);
        return $d . ' day' . ($d > 1 ? 's' : '');
    }
    else {
        $m = floor($diff / 2629728);
        return $m . ' month' . ($m > 1 ? 's' : '');
    }
}

/**
 * 
 * @param string $url
 * @return string
 */
function image($url) {
    if (is_string($url) && !empty($url)) {
        return str_replace('https://render.guildwars2.com/file/', '/proxy/', $url);
    }
    elseif ($url instanceof InventorySlot) {
        return image($url->getSkin() ? $url->getSkin()->getIcon() : $url->getIcon());
    }
    elseif ($url instanceof Character || $url instanceof PvpStatsProfession || $url instanceof PvpGame) {
        return '/assets/images/professions_color/' . $url->getProfession() . '.png';
    }
    elseif ($url instanceof CraftingDiscipline) {
        return '/assets/images/disciplines/' . $url->getName() . '.png';
    }
    elseif ($url instanceof Guild) {
        return image($url->hasEmblem() ? '/guild/' . $url->getId() . '.png' : '');
    }
    elseif ($url instanceof AbstractObject && method_exists($url, 'getIcon')) {
        return image($url->getIcon());
    }
    return '/assets/images/nothing.png';
}

/**
 * 
 * @param array $array
 * @param integer $n
 * @param boolean $fill
 * @return array
 */
function chunk($array, $n, $fill = true) {
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
function amount($value) {
    if ($value === null || $value === '') {
        return '';
    }
    if (is_array($value)) {
        if (isset($value['buy_total']) && $value['buy_total'] != $value['buy']) {
            return amount($value['buy_total']) . ' - ' . amount($value['sell_total']) . ' / ' . amount($value['buy']) . ' - ' . amount($value['sell']);
        }
        elseif (isset($value['buy'])) {
            return amount($value['buy']) . ' - ' . amount($value['sell']);
        }
        return '';
    }
    else {
        $g = floor($value / 10000);
        $s = floor($value / 100) % 100;
        $c = $value % 100;
        if ($g) {
            return $g . 'g' . $s . 's';
        }
        elseif ($s) {
            return $s . 's' . $c . 'c';
        }
        else {
            return $c . 'c';
        }
    }
}
