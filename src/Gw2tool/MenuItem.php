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
     * @var boolean
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
     * @return boolean
     */
    function getSeparator()
    {
        return $this->separator;
    }

    /**
     * 
     * @return string
     */
    function getIcon()
    {
        return $this->icon;
    }

    /**
     * 
     * @return string
     */
    function getPage()
    {
        return $this->page;
    }

    /**
     * 
     * @return string
     */
    function getUri()
    {
        return $this->uri;
    }

    /**
     * 
     * @return string
     */
    function getLabel()
    {
        return $this->label;
    }

    /**
     * 
     * @return string
     */
    function getRight()
    {
        return $this->right;
    }

    /**
     * 
     * @return string
     */
    function getPermission()
    {
        return $this->permission;
    }

    /**
     * 
     * @param boolean $separator
     * @return MenuItem
     */
    function setSeparator($separator)
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * 
     * @param string $page
     * @return MenuItem
     */
    function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * 
     * @param string $uri
     * @return MenuItem
     */
    function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * 
     * @param string $icon
     * @return MenuItem
     */
    function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * 
     * @param string $label
     * @return MenuItem
     */
    function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * 
     * @param string $right
     * @return MenuItem
     */
    function setRight($right)
    {
        $this->right = $right;
        return $this;
    }

    /**
     * 
     * @param string $permission
     * @return MenuItem
     */
    function setPermission($permission)
    {
        $this->permission = $permission;
        return $this;
    }
}
