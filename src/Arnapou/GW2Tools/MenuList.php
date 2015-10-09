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

class MenuList implements \IteratorAggregate {

    /**
     *
     * @var array
     */
    protected $menus;

    /**
     * 
     */
    public function __construct(Gw2Account $account = null) {
        $trans = Translator::getInstance();

        // menu 1
        $menu = Menu::create($trans['menu.general']);
        $menu->addItem('account', $trans['menu.general.account']);
        $menu->addItem('wallet', $trans['menu.general.wallet'])->setPermission(Gw2Account::PERMISSION_WALLET);
        $menu->addItem('golds', $trans['menu.general.golds']);
        $menu->addItem('search', $trans['menu.general.search']);
        $menu->addItem('pvp', $trans['menu.general.pvp'])->setPermission(Gw2Account::PERMISSION_PVP);
        $menu->addItem('statistics', $trans['menu.general.statistics']);
        $this->addMenu($menu);

        // menu 2
        $menu = Menu::create($trans['menu.characters']);
        $menu->addItem('characters', $trans['menu.characters.characters']);
        $menu->addItem('equipments', $trans['menu.characters.equipments']);
        $menu->addItem('inventories', $trans['menu.characters.inventories'])->setPermission(Gw2Account::PERMISSION_INVENTORIES);
        $menu->addItem('attributes', $trans['menu.characters.attributes']);
        $menu->addItem('builds', $trans['menu.characters.builds'])->setPermission(Gw2Account::PERMISSION_BUILDS);
        if ($account) {
            $menu->addSeparator();
            foreach ($account->getCharacterNames() as $name) {
                $menu->addItem('character/' . $name, $name, 'character/' . $name);
            }
        }
        $this->addMenu($menu);

        // menu 3
        $menu = Menu::create($trans['menu.vaults']);
        $menu->addItem('bank', $trans['menu.vaults.bank'])->setPermission(Gw2Account::PERMISSION_INVENTORIES);
        $menu->addItem('collectibles', $trans['menu.vaults.collectibles'])->setPermission(Gw2Account::PERMISSION_INVENTORIES);
        $this->addMenu($menu);

        // menu 4
        $menu = Menu::create($trans['menu.unlocks']);
        $menu->addItem('wardrobe_armors', $trans['menu.unlocks.wardrobe_armors'])->setPermission(Gw2Account::PERMISSION_UNLOCKS);
        $menu->addItem('wardrobe_weapons', $trans['menu.unlocks.wardrobe_weapons'])->setPermission(Gw2Account::PERMISSION_UNLOCKS);
        $menu->addItem('dyes', $trans['menu.unlocks.dyes'])->setPermission(Gw2Account::PERMISSION_UNLOCKS);
        $this->addMenu($menu);

        // menu 5
        $menu = Menu::create($trans['menu.tp']);
        $menu->addItem('tp_buys', $trans['menu.tp.tp_buys'])->setPermission(Gw2Account::PERMISSION_TRADINGPOST);
        $menu->addItem('tp_sells', $trans['menu.tp.tp_sells'])->setPermission(Gw2Account::PERMISSION_TRADINGPOST);
        $this->addMenu($menu);

        // menu 5
        $menu = Menu::create($trans['menu.achievements']);
        $menu->addItem('achievements_daily', $trans['menu.achievements.daily']);
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
