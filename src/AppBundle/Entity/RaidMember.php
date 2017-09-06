<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Token
 *
 * @ORM\Table(name="raidmember", indexes={
 *      @ORM\Index(name="name"         , columns={"name"}    ),
 *      @ORM\Index(name="creator"      , columns={"creator"} ),
 *      @ORM\Index(name="datecreation" , columns={"datecreation"} )
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RaidMemberRepository")
 */
class RaidMember
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=200)
     */
    private $name = '';

    /**
     * @var RaidRoster
     *
     * @ORM\ManyToOne(targetEntity="RaidRoster")
     * @ORM\JoinColumn(name="roster_id", referencedColumnName="id")
     */
    private $roster;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=200)
     */
    private $role = '';

    /**
     * @var bool
     *
     * @ORM\Column(name="is_admin", type="boolean")
     */
    private $isAdmin = false;

    /**
     * @var int
     *
     * @ORM\Column(name="date_creation", type="integer")
     */
    private $dateCreation;

    public function __construct()
    {
        $this->dateCreation = time();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return RaidRoster
     */
    public function getRoster()
    {
        return $this->roster;
    }

    /**
     * @param RaidRoster $roster
     */
    public function setRoster($roster)
    {
        $this->roster = $roster;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * @return bool
     */
    public function isCreator()
    {
        return $this->name === $this->getRoster()->getCreator();
    }

    /**
     * @param bool $isAdmin
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;
    }

    /**
     * @return int
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * @param int $dateCreation
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;
    }

}
