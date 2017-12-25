<?php

namespace My\AppBundle\Model;

/**
 * DrivingTicket
 */
abstract class DrivingTicket
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var \DateTime
     */
    protected $drive_date;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $comment;

    /**
     * @var integer
     */
    protected $rating;

    /**
     * @var \My\AppBundle\Entity\DrivingPackage
     */
    protected $package;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set drive_date
     *
     * @param \DateTime $driveDate
     * @return DrivingTicket
     */
    public function setDriveDate($driveDate)
    {
        $this->drive_date = $driveDate;

        return $this;
    }

    /**
     * Get drive_date
     *
     * @return \DateTime 
     */
    public function getDriveDate()
    {
        return $this->drive_date;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return DrivingTicket
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
     * Set comment
     *
     * @param string $comment
     * @return DrivingTicket
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set rating
     *
     * @param integer $rating
     * @return DrivingTicket
     */
    public function setRating($rating)
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * Get rating
     *
     * @return integer 
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Set package
     *
     * @param \My\AppBundle\Entity\DrivingPackage $package
     * @return DrivingTicket
     */
    public function setPackage(\My\AppBundle\Entity\DrivingPackage $package = null)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package
     *
     * @return \My\AppBundle\Entity\DrivingPackage 
     */
    public function getPackage()
    {
        return $this->package;
    }
}
