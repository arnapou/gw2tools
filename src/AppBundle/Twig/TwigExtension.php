<?php
namespace AppBundle\Twig;

class TwigExtension extends \Twig_Extension
{

    public function getName()
    {
        return 'gw2tool';
    }

    public function getFilters()
    {
        return array(
//            new \Twig_SimpleFilter('striptags', 'striptags'),
            new \Twig_SimpleFilter('br2nl', [$this, 'br2nl']),
            new \Twig_SimpleFilter('image', [$this, 'image']),
            new \Twig_SimpleFilter('imagestat', [$this, 'imagestat']),
            new \Twig_SimpleFilter('amount', [$this, 'amount']),
            new \Twig_SimpleFilter('columns', [$this, 'columns']),
            new \Twig_SimpleFilter('chunk', [$this, 'chunk']),
            new \Twig_SimpleFilter('buffdescription', [$this, 'buffdescription']),
            new \Twig_SimpleFilter('consumableduration', [$this, 'consumableduration']),
            new \Twig_SimpleFilter('gameduration', [$this, 'gameduration']),
            new \Twig_SimpleFilter('datediff', [$this, 'datediff']),
            new \Twig_SimpleFilter('gwlink', [$this, 'gwlink']),
            new \Twig_SimpleFilter('idtoname', [$this, 'idtoname']),
        );
    }

    public function idtoname($item)
    {
        return \Arnapou\GW2Api\id_to_name($item);
    }

    public function gwlink($item)
    {
        if ($item instanceof \Arnapou\GW2Api\Model\InventorySlot) {
            return \Gw2tool\gwlink_inventoryslot($item);
        } elseif ($item instanceof \Arnapou\GW2Api\Model\Item) {
            return \Gw2tool\gwlink_item($item);
        } elseif ($item instanceof \Arnapou\GW2Api\Model\Specialization) {
            return \Gw2tool\gwlink_specialization($item);
        } elseif ($item instanceof \Arnapou\GW2Api\Model\SpecializationTrait) {
            return \Gw2tool\gwlink_trait($item);
        } elseif ($item instanceof \Arnapou\GW2Api\Model\Skin) {
            return \Gw2tool\gwlink_skin($item);
        } elseif ($item instanceof \Arnapou\GW2Api\Model\Skill) {
            return \Gw2tool\gwlink_skill($item);
        } elseif ($item instanceof \Arnapou\GW2Api\Model\PvpAmulet) {
            return \Gw2tool\gwlink_pvpamulet($item);
        } elseif ($item instanceof \Arnapou\GW2Api\Model\Pet) {
            return \Gw2tool\gwlink_pet($item);
        }
        return '';
    }

    public function br2nl($string)
    {
        return preg_replace('!<br(\s*/)?>!si', "\n", $string);
    }

    public function datediff($date)
    {
        return \Gw2tool\datediff($date);
    }

    public function image($url)
    {
        return \Gw2tool\image($url);
    }

    public function imagestat($url)
    {
        return \Gw2tool\imagestat($url);
    }

    public function amount($value)
    {
        return \Gw2tool\amount($value);
    }

    public function gameduration($item)
    {
        return \Gw2tool\gameduration($item);
    }

    public function consumableduration($item)
    {
        return \Gw2tool\consumableduration($item);
    }

    public function buffdescription($item)
    {
        return \Gw2tool\buffdescription($item);
    }

    public function columns($array, $n, $fill = true)
    {
        return \Gw2tool\chunk($array, ceil(count($array) / $n), $fill);
    }

    public function chunk($array, $n, $fill = true)
    {
        return \Gw2tool\chunk($array, $n, $fill);
    }
}
