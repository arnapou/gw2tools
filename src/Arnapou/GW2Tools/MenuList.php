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
        $trans = Translator::getInstance();

        // menu 1
        $menu = Menu::create($trans['menu.general']);
        foreach (['account', 'wallet', 'golds', 'search', 'pvp'] as $name) {
            $menu->addItem($name, $trans['menu.general.' . $name]);
        }
        $this->addMenu($menu);

        // menu 2
        $menu = Menu::create($trans['menu.characters']);
        foreach (['characters', 'equipments', 'inventories', 'attributes'] as $name) {
            $menu->addItem($name, $trans['menu.characters.' . $name]);
        }
        if ($account) {
            $menu->addSeparator();
            foreach ($account->getCharacterNames() as $name) {
                $menu->addItem('character', $name, 'character/' . $name);
            }
        }
        $this->addMenu($menu);

        // menu 3
        $menu = Menu::create($trans['menu.vaults']);
        foreach (['bank', 'collectibles'] as $name) {
            $menu->addItem($name, $trans['menu.vaults.' . $name]);
        }
        $this->addMenu($menu);

        // menu 4
        $menu = Menu::create($trans['menu.unlocks']);
        foreach (['wardrobe_armors', 'wardrobe_weapons', 'dyes'] as $name) {
            $menu->addItem($name, $trans['menu.unlocks.' . $name]);
        }
        $this->addMenu($menu);

        // menu 5
        $menu = Menu::create($trans['menu.tp']);
        foreach (['tp_buys', 'tp_sells'] as $name) {
            $menu->addItem($name, $trans['menu.tp.' . $name]);
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
            foreach ($menu->getItems() as $item) {
                if (isset($item['page']) && $item['page'] === $page) {
                    return $item['label'];
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
            foreach ($menu->getItems() as $item) {
                if (isset($item['right'])) {
                    if ($item['right'] === 'character') {
                        $list[$item['right']] = $menu->getLabel() . ' / ' . Translator::getInstance()['menu.characters.character'];
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
