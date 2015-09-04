<?php

/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\GW2Tools\Api;

class MenuList implements \IteratorAggregate {

    /**
     *
     * @var MenuList 
     */
    static protected $instance;

    /**
     *
     * @var array
     */
    protected $menus;

    /**
     * 
     */
    public function __construct(Gw2Account $account = null) {

        // menu 1
        $menu = Menu::create('General')
            ->addItem('account', 'Account')
            ->addItem('wallet', 'Wallet')
            ->addItem('golds', 'Golds')
        ;
        $this->addMenu($menu);

        // menu 2
        $menu = Menu::create('Characters')
            ->addItem('characters', 'Summary')
            ->addItem('equipments', 'Equipments')
            ->addItem('inventories', 'Inventories')
            ->addItem('attributes', 'Attributes (BETA)')
            ->addSeparator()
        ;
        if ($account) {
            foreach ($account->getCharacterNames() as $name) {
                $menu->addItem('character', $name, 'character/' . $name);
            }
        }
        $this->addMenu($menu);

        // menu 3
        $menu = Menu::create('Vaults')
            ->addItem('bank', 'Bank')
            ->addItem('collectibles', 'Collectibles')
        ;
        $this->addMenu($menu);

        // menu 4
        $menu = Menu::create('Unlocks')
            ->addItem('wardrobe_armors', 'Wardrobe Armors')
            ->addItem('wardrobe_weapons', 'Wardrobe Weapons')
            ->addItem('dyes', 'Dyes')
        ;
        $this->addMenu($menu);

        // menu 5
        $menu = Menu::create('Trading post')
            ->addItem('tp_buys', 'Buys')
            ->addItem('tp_sells', 'Sells')
        ;
        $this->addMenu($menu);
    }

    /**
     * 
     * @param string $page
     * @return boolean
     */
    public function pageExists($page) {
        foreach ($this->menus as /* @var $menu Menu */ $menu) {
            foreach ($menu->getItems() as $item) {
                if (isset($item['page']) && $item['page'] == $page) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 
     * @return array
     */
    public function getRights() {
        $list = [];
        foreach ($this->menus as /* @var $menu Menu */ $menu) {
            foreach ($menu->getItems() as $item) {
                if (isset($item['right'])) {
                    if ($item['right'] === 'character') {
                        $list[$item['right']] = $menu->getLabel() . ' / Character details';
                    }
                    else {
                        $list[$item['right']] = $menu->getLabel() . ' / ' . $item['label'];
                    }
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
