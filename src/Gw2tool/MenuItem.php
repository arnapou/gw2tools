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

class MenuItem
{
    /**
     *
     * @var bool
     */
    protected $separator = false;

    /**
     *
     * @var string
     */
    protected $page;

    /**
     *
     * @var string
     */
    protected $uri;

    /**
     *
     * @var string
     */
    protected $icon;

    /**
     *
     * @var string
     */
    protected $label;

    /**
     *
     * @var string
     */
    protected $right;

    /**
     *
     * @var string
     */
    protected $permission;

    /**
     *
     * @param string $label
     */
    public function __construct($page, $label, $uri = null, $icon = null)
    {
        $this->page  = $page;
        $this->icon  = $icon;
        $this->label = $label;
        $this->uri   = empty($uri) ? $page . '/' : $uri;
        $this->right = $page;
    }

    /**
     *
     * @return bool
     */
    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     *
     * @return string
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
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
     * @return string
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     *
     * @return string
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     *
     * @param bool $separator
     * @return MenuItem
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     *
     * @param string $page
     * @return MenuItem
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     *
     * @param string $uri
     * @return MenuItem
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     *
     * @param string $icon
     * @return MenuItem
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     *
     * @param string $label
     * @return MenuItem
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     *
     * @param string $right
     * @return MenuItem
     */
    public function setRight($right)
    {
        $this->right = $right;
        return $this;
    }

    /**
     *
     * @param string $permission
     * @return MenuItem
     */
    public function setPermission($permission)
    {
        $this->permission = $permission;
        return $this;
    }
}
