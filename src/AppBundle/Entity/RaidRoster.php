<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Token
 *
 * @ORM\Table(name="raidroster", indexes={
 *      @ORM\Index(name="name"          , columns={"name"}    ),
 *      @ORM\Index(name="creator"       , columns={"creator"} ),
 *      @ORM\Index(name="date_creation" , columns={"date_creation"} )
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RaidRosterRepository")
 */
class RaidRoster
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
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="creator", type="string", length=100)
     */
    private $creator = '';

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description = '';

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
     * @return string
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param string $creator
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * @param int $time
     */
    public function setDateCreation($time)
    {
        $this->dateCreation = $time;
    }

}
