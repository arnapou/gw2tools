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

use Arnapou\GW2Api\Model\Account;
use Arnapou\GW2Api\Model\Character;
use Arnapou\GW2Api\Model\Guild;
use Symfony\Component\Translation\TranslatorInterface;

class MenuList implements \IteratorAggregate {

    /**
     *
     * @var array
     */
    protected $menus;

    /**
     * 
     */
    public function __construct(TranslatorInterface $tr, $characters, $guilds) {

        // GENERAL
        $menu = Menu::create($tr->trans('menu.general'));
        $menu->addItem('account', $tr->trans('menu.general.account'), null, 'ic-ui-key');
        $menu->addItem('masteries', $tr->trans('menu.general.masteries'), null, 'ic-ui-masteries')->setPermission(Account::PERMISSION_PROGRESSION);
        $menu->addItem('wallet', $tr->trans('menu.general.wallet'), null, 'ic-ui-wallet')->setPermission(Account::PERMISSION_WALLET);
        $menu->addItem('golds', $tr->trans('menu.general.golds'), null, 'ic-ui-golds');
        $menu->addItem('search', $tr->trans('menu.general.search'), null, 'ic-ui-magnifier');
        $menu->addItem('pvp', $tr->trans('menu.general.pvp'), null, 'ic-mode-pvp')->setPermission(Account::PERMISSION_PVP);
        $menu->addItem('statistics', $tr->trans('menu.general.statistics'), null, 'glyphicon glyphicon-stats');
        $this->addMenu($menu);

        // CHARACTERS
        $menu = Menu::create($tr->trans('menu.characters'));
        $menu->addItem('characters', $tr->trans('menu.characters.characters'), null, 'ic-ui-list');
        $menu->addItem('equipments', $tr->trans('menu.characters.equipments'), null, 'ic-armor-coat');
        $menu->addItem('inventories', $tr->trans('menu.characters.inventories'), null, 'ic-ui-bag')->setPermission(Account::PERMISSION_INVENTORIES);
        $menu->addItem('attributes', $tr->trans('menu.characters.attributes'), null, 'ic-attribute-power');
        $menu->addItem('builds', $tr->trans('menu.characters.builds'), null, 'ic-ui-archetype')->setPermission(Account::PERMISSION_BUILDS);
        if (!empty($characters) && is_array($characters)) {
            $menu->addSeparator();
            foreach ($characters as $name => /* @var $character Character */ $character) {
                $menu->addItem('character/' . $name, $name, 'character/' . $name, 'ic-profession-' . strtolower($character->getProfession()));
            }
        }
        $this->addMenu($menu);

        // VAULTS
        $menu = Menu::create($tr->trans('menu.vaults'));
        $menu->addItem('bank', $tr->trans('menu.vaults.bank'), null, 'ic-ui-bank')->setPermission(Account::PERMISSION_INVENTORIES);
        $menu->addItem('collectibles', $tr->trans('menu.vaults.collectibles'), null, 'ic-ui-collections')->setPermission(Account::PERMISSION_INVENTORIES);
        if (!empty($guilds) && is_array($guilds)) {
            $menu->addSeparator();
            foreach ($guilds as $id => /* @var $guild Guild */ $guild) {
                $icon = $guild->hasEmblem() ? 'guild-icon-' . $id : 'guild-icon-nothing';
                $menu->addItem('guild_stash/' . $id, (string) $guild, 'guild_stash/' . $id, $icon);
            }
        }
        $this->addMenu($menu);

        // UNLOCKS
        $menu = Menu::create($tr->trans('menu.unlocks'));
        $menu->addItem('wardrobe_armors', $tr->trans('menu.unlocks.wardrobe_armors'), null, 'ic-armor-coat')->setPermission(Account::PERMISSION_UNLOCKS);
        $menu->addItem('wardrobe_weapons', $tr->trans('menu.unlocks.wardrobe_weapons'), null, 'ic-weapon-hammer')->setPermission(Account::PERMISSION_UNLOCKS);
        $menu->addItem('dyes', $tr->trans('menu.unlocks.dyes'), null, 'ic-ui-dye')->setPermission(Account::PERMISSION_UNLOCKS);
        $menu->addItem('minis', $tr->trans('menu.unlocks.minis'), null, 'ic-ui-minipets')->setPermission(Account::PERMISSION_UNLOCKS);
        $menu->addItem('titles', $tr->trans('menu.unlocks.titles'), null, 'ic-ui-title')->setPermission(Account::PERMISSION_UNLOCKS);
        $menu->addItem('finishers', $tr->trans('menu.unlocks.finishers'), null, 'ic-ui-finisher')->setPermission(Account::PERMISSION_UNLOCKS);
        $menu->addItem('outfits', $tr->trans('menu.unlocks.outfits'), null, 'ic-ui-hero')->setPermission(Account::PERMISSION_UNLOCKS);
        $menu->addItem('gliders', $tr->trans('menu.unlocks.gliders'), null, 'ic-ui-glider')->setPermission(Account::PERMISSION_UNLOCKS);
        $menu->addItem('home', $tr->trans('menu.unlocks.home'), null, 'ic-ui-home')->setPermission(Account::PERMISSION_PROGRESSION);
        $this->addMenu($menu);

        // OTHERS 
        $menu = Menu::create($tr->trans('menu.others'));
        $menu->addItem('tp_buys', $tr->trans('menu.tp.tp_buys'), null, 'ic-ui-blc-balance')->setPermission(Account::PERMISSION_TRADINGPOST);
        $menu->addItem('tp_sells', $tr->trans('menu.tp.tp_sells'), null, 'ic-ui-blc-balance')->setPermission(Account::PERMISSION_TRADINGPOST);
        $menu->addSeparator();
        $menu->addItem('achievements_daily', $tr->trans('menu.achievements.daily'), null, 'ic-ui-arenanet');
        $menu->addItem('achievements', $tr->trans('menu.achievements'), null, 'ic-ui-arenanet');
        $menu->addItem('dungeons_raids', $tr->trans('menu.dungeons_raids'), null, 'ic-ui-dungeon')->setPermission(Account::PERMISSION_PROGRESSION);
        if (!empty($guilds) && is_array($guilds)) {
            $menu->addSeparator();
            foreach ($guilds as $id => /* @var $guild Guild */ $guild) {
                $icon = $guild->hasEmblem() ? 'guild-icon-' . $id : 'guild-icon-nothing';
                $menu->addItem('guild/' . $id, (string) $guild, 'guild/' . $id, $icon);
            }
        }
        $this->addMenu($menu);
    }

    /**
     * 
     * @param string $page
     * @return boolean
     */
    public function pageExists($page) {
        return $this->pageName($page) !== null;
    }

    /**
     * 
     * @param string $page
     * @return string
     */
    public function pageName($page) {
        foreach ($this->menus as /* @var $menu Menu */ $menu) {
            foreach ($menu->getItems() as /* @var $item MenuItem */ $item) {
                if ($item->getPage() && $item->getPage() === $page) {
                    return $item->getLabel();
                }
            }
        }
        return null;
    }

    /**
     * 
     * @return array
     */
    public function getRights() {
        $list = [];
        foreach ($this->menus as /* @var $menu Menu */ $menu) {
            foreach ($menu->getItems() as /* @var $item MenuItem */ $item) {
                if ($item->getRight()) {
                    $list[$item->getRight()] = $menu->getLabel() . ' / ' . $item->getLabel();
                }
            }
        }
        return $list;
    }

    /**
     * 
     * @param Menu $menu
     * @return MenuList
     */
    public function addMenu(Menu $menu) {
        $this->menus[] = $menu;
        return $this;
    }

    /**
     * 
     * @param string $label
     * @return Menu
     */
    static public function create($label) {
        return new self($label);
    }

    public function getIterator() {
        return new \ArrayIterator($this->menus);
    }

}
