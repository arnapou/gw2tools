<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Token
 *
 * @ORM\Table(name="raidweek",
 *     indexes={
 *        @ORM\Index(name="date", columns={"date"})
 *     },
 *     uniqueConstraints={
 *        @ORM\UniqueConstraint(name="UNIQPK", columns={"member_id", "date"})
 *     },
 * )
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RaidWeekRepository")
 */
class RaidWeek
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var RaidMember
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\RaidMember")
     * @ORM\JoinColumn(name="member_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $member;

    /**
     * @var string
     *
     * @ORM\Column(name="date", type="string", length=10)
     */
    private $date = '';

    /**
     * @var string
     *
     * @ORM\Column(name="status1", type="string", length=10)
     */
    private $status1 = '';

    /**
     * @var string
     *
     * @ORM\Column(name="status2", type="string", length=10)
     */
    private $status2 = '';

    /**
     * @var string
     *
     * @ORM\Column(name="status3", type="string", length=10)
     */
    private $status3 = '';

    /**
     * @var string
     *
     * @ORM\Column(name="status4", type="string", length=10)
     */
    private $status4 = '';

    /**
     * @var string
     *
     * @ORM\Column(name="status5", type="string", length=10)
     */
    private $status5 = '';

    /**
     * @var string
     *
     * @ORM\Column(name="status6", type="string", length=10)
     */
    private $status6 = '';

    /**
     * @var string
     *
     * @ORM\Column(name="status7", type="string", length=10)
     */
    private $status7 = '';

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return RaidMember
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @param RaidMember $member
     */
    public function setMember($member)
    {
        $this->member = $member;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param string $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @param integer $index
     * @param string  $status
     */
    public function setStatus($index, $status)
    {
        if (!\in_array($index, [1, 2, 3, 4, 5, 6, 7])) {
            throw new \InvalidArgumentException("The status index is not valid.");
        }
        $property        = "status$index";
        $this->$property = $status;
    }

    /**
     * @param integer $index
     */
    public function getStatus($index)
    {
        if (!\in_array($index, [1, 2, 3, 4, 5, 6, 7])) {
            throw new \InvalidArgumentException("The status index is not valid.");
        }
        $property = "status$index";
        return $this->$property;
    }

    /**
     * @param array $statuses
     */
    public function setStatuses(array $statuses)
    {
        foreach ($statuses as $index => $status) {
            $this->setStatus($index, $status);
        }
    }

    /**
     * @return array
     */
    public function getStatuses()
    {
        return [
            1 => $this->status1,
            2 => $this->status2,
            3 => $this->status3,
            4 => $this->status4,
            5 => $this->status5,
            6 => $this->status6,
            7 => $this->status7,
        ];
    }


}
