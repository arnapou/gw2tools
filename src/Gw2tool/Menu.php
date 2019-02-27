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

class Menu implements \IteratorAggregate
{
    /**
     *
     * @var string
     */
    protected $label;

    /**
     *
     * @var array
     */
    protected $items = [];

    /**
     *
     * @param string $label
     */
    public function __construct($label)
    {
        $this->label = $label;
    }

    /**
     *
     * @param string $page
     * @param string $label
     * @param string $uri
     * @param string $icon
     * @return MenuItem
     */
    public function addItem($page, $label, $uri = null, $icon = null)
    {
        $item          = new MenuItem($page, $label, $uri, $icon);
        $this->items[] = $item;
        return $item;
    }

    /**
     *
     * @return MenuItem
     */
    public function addSeparator()
    {
        return $this->addItem(null, null)->setSeparator(true);
    }

    /**
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     *
     * @param Token $token
     * @return array
     */
    public function getItems(Token $token = null)
    {
        if ($token === null) {
            return $this->items;
        }
        $items = [];
        foreach ($this->items as $item) {
            /* @var $item MenuItem */
            if ($item->getRight() == '' || $token->hasRight($item->getRight())) {
                $items[] = $item;
            }
        }
        return $this->trimSeparators($items);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->getItems());
    }

    /**
     *
     * @param array $items
     * @return array
     */
    protected function trimSeparators($items)
    {
        if (empty($items)) {
            return [];
        }
        $n = \count($items);

        $offsetStart = 0;
        for ($i = 0; $i < $n; $i++) {
            if (!$items[$i]->getSeparator()) {
                break;
            }
            $offsetStart++;
        }

        $offsetEnd = $n - 1;
        for ($i = $n - 1; $i >= 0; $i--) {
            if (!$items[$i]->getSeparator()) {
                break;
            }
            $offsetEnd--;
        }

        $nbItems = $offsetEnd - $offsetStart + 1;
        if ($nbItems <= 0) {
            return [];
        }
        return \array_slice($items, $offsetStart, $nbItems);
    }

    /**
     *
     * @param string $label
     * @return Menu
     */
    public static function create($label)
    {
        return new self($label);
    }
}
