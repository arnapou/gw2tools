<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Token
 *
 * @ORM\Table(name="tokens", indexes={
 *      @ORM\Index(name="name"       , columns={"name"}       ),
 *      @ORM\Index(name="lastaccess" , columns={"lastaccess"} )
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TokenRepository")
 */
class Token
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=200)
     */
    private $name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=10)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=100, unique=true)
     */
    private $token;

    /**
     * @var int
     *
     * @ORM\Column(name="lastaccess", type="integer")
     */
    private $lastaccess;

    /**
     * @var bool
     *
     * @ORM\Column(name="valid", type="boolean")
     */
    private $valid = false;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="blob")
     */
    private $data = '';

    /**
     *
     * @var array
     */
    private $_rights = null;

    public function __construct()
    {
        $this->lastaccess = time();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Token
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Token
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set token
     *
     * @param string $token
     *
     * @return Token
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set lastaccess
     *
     * @param int $lastaccess
     *
     * @return Token
     */
    public function setLastaccess($lastaccess)
    {
        $this->lastaccess = $lastaccess;
        return $this;
    }

    /**
     * Get lastaccess
     *
     * @return int
     */
    public function getLastaccess()
    {
        return $this->lastaccess;
    }

    /**
     * Update lastaccess
     *
     * @return Token
     */
    public function updateLastaccess()
    {
        $this->lastaccess = time();
        return $this;
    }

    /**
     * Set valid
     *
     * @param bool $bool
     *
     * @return Token
     */
    public function setIsValid($bool)
    {
        $this->valid = $bool;
        return $this;
    }

    /**
     * Get valid
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Set data
     *
     * @param string $data
     *
     * @return Token
     */
    public function setData($data)
    {
        $this->data    = serialize($data);
        $this->_rights = null;
        return $this;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (\is_resource($this->data)) {
            $data = stream_get_contents($this->data);
        } else {
            return [];
        }
        if (empty($data)) {
            return [];
        }
        return unserialize($data);
    }

    /**
     *
     * @return string
     */
    public function __toString()
    {
        return $this->token;
    }

    /**
     *
     * @param string $right
     * @return bool
     */
    public function hasRight($right)
    {
        return \in_array($right, $this->getRights());
    }

    /**
     *
     * @return array
     */
    public function getRights()
    {
        if ($this->_rights === null) {
            $data          = $this->getData();
            $this->_rights = $data['rights'] ?? [];
        }
        return $this->_rights;
    }

    /**
     *
     * @param array $rights
     * @return Token
     */
    public function setRights($rights)
    {
        $this->_rights  = $rights;
        $data           = $this->getData();
        $data['rights'] = $rights;
        $this->setData($data);
        return $this;
    }
}
