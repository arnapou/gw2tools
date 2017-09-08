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

    const NONE = 'none';
    const PRESENT = 'present';
    const MAYBE = 'maybe';
    const BACKUP = 'backup';

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
     * @var string
     *
     * @ORM\Column(name="text1", type="string", length=50)
     */
    private $text1 = '';

    /**
     * @var string
     *
     * @ORM\Column(name="text2", type="string", length=50)
     */
    private $text2 = '';

    /**
     * @var string
     *
     * @ORM\Column(name="text3", type="string", length=50)
     */
    private $text3 = '';

    /**
     * @var string
     *
     * @ORM\Column(name="text4", type="string", length=50)
     */
    private $text4 = '';

    /**
     * @var string
     *
     * @ORM\Column(name="text5", type="string", length=50)
     */
    private $text5 = '';

    /**
     * @var string
     *
     * @ORM\Column(name="text6", type="string", length=50)
     */
    private $text6 = '';

    /**
     * @var string
     *
     * @ORM\Column(name="text7", type="string", length=50)
     */
    private $text7 = '';

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
        $property = "status$index";
        $this->$property = \in_array($status, self::getStatusList()) ? $status : self::NONE;
    }

    /**
     * @param integer $index
     * @return string
     */
    public function getStatus($index)
    {
        if (!\in_array($index, [1, 2, 3, 4, 5, 6, 7])) {
            throw new \InvalidArgumentException("The status index is not valid.");
        }
        $property = "status$index";
        return $this->$property ?: self::NONE;
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
            1 => $this->status1 ?: self::NONE,
            2 => $this->status2 ?: self::NONE,
            3 => $this->status3 ?: self::NONE,
            4 => $this->status4 ?: self::NONE,
            5 => $this->status5 ?: self::NONE,
            6 => $this->status6 ?: self::NONE,
            7 => $this->status7 ?: self::NONE,
        ];
    }

    /**
     * @param integer $index
     * @param string  $text
     */
    public function setText($index, $text)
    {
        if (!\in_array($index, [1, 2, 3, 4, 5, 6, 7])) {
            throw new \InvalidArgumentException("The text index is not valid.");
        }
        $property = "text$index";
        $this->$property = $text;
    }

    /**
     * @param integer $index
     * @return string
     */
    public function getText($index)
    {
        if (!\in_array($index, [1, 2, 3, 4, 5, 6, 7])) {
            throw new \InvalidArgumentException("The text index is not valid.");
        }
        $property = "text$index";
        return $this->$property;
    }

    /**
     * @param array $texts
     */
    public function setTexts(array $texts)
    {
        foreach ($texts as $index => $text) {
            $this->setText($index, $text);
        }
    }

    /**
     * @return array
     */
    public function getTexts()
    {
        return [
            1 => $this->text1,
            2 => $this->text2,
            3 => $this->text3,
            4 => $this->text4,
            5 => $this->text5,
            6 => $this->text6,
            7 => $this->text7,
        ];
    }

    /**
     * @return array
     */
    public static function getStatusList()
    {
        return [
            self::NONE,
            self::PRESENT,
            self::MAYBE,
            self::BACKUP,
        ];
    }

}
