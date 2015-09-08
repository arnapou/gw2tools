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
use Arnapou\GW2Api\Model\Guild;
use Arnapou\GW2Api\Model\InventorySlot;

/**
 * 
 * @param string $date
 * @return string
 */
function datediff($date) {
    $diff = (new \DateTime('now'))->getTimestamp() - (new \DateTime($date))->getTimestamp();
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
        $s = '';
        if (isset($value['buy_total']) && $value['buy_total'] != $value['buy']) {
            return amount($value['buy_total']) . ' - ' . amount($value['sell_total']) . ' / ' . amount($value['buy']) . ' - ' . amount($value['sell']);
        }
        elseif (isset($value['buy'])) {
            return amount($value['buy']) . ' - ' . amount($value['sell']);
        }
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
