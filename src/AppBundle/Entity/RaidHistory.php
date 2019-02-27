<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Token
 *
 * @ORM\Table(name="raidhistory", indexes={
 *      @ORM\Index(name="type"          , columns={"type"}    ),
 *      @ORM\Index(name="date_creation" , columns={"date_creation"} )
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RaidHistoryRepository")
 */
class RaidHistory
{
    const ROSTER_CREATION    = 'roster_creation';
    const OFFICER_PROMOTE    = 'officer_promote';
    const OFFICER_RETROGRADE = 'officer_retrograde';
    const MEMBER_NEW         = 'member_new';
    const MEMBER_LEAVE       = 'member_leave';
    const MEMBER_REMOVE      = 'member_remove';
    const STATUS_CHANGE      = 'status_change';
    const MEMBER_CHANGE_NAME = 'member_change_name';

    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="member_name", type="string", length=100)
     */
    private $memberName = '';

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=100)
     */
    private $type = '';

    /**
     * @var RaidRoster
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\RaidRoster")
     * @ORM\JoinColumn(name="roster_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $roster;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text")
     */
    private $data = '';

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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
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
    public function setRoster(RaidRoster $roster)
    {
        $this->roster = $roster;
    }

    /**
     * @return array
     */
    public function getDataTrans()
    {
        $trans = [
            '%member%' => $this->getMemberName(),
        ];
        foreach ($this->getData() as $key => $value) {
            $trans['%' . $key . '%'] = $value;
        }
        return $trans;
    }

    /**
     * @return array
     */
    public function getData()
    {
        if ($this->data) {
            return unserialize($this->data);
        }
        return [];
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = !empty($data) && \is_array($data) ? serialize($data) : '';
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

    /**
     * @return string
     */
    public function getMemberName()
    {
        return $this->memberName;
    }

    /**
     * @param string $name
     */
    public function setMemberName($name)
    {
        $this->memberName = $name;
    }
}
