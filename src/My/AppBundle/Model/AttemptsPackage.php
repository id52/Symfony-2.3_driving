<?php

namespace My\AppBundle\Model;

/**
 * AttemptsPackage
 */
abstract class AttemptsPackage
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var integer
     */
    protected $number_of_attempts;

    /**
     * @var integer
     */
    protected $cost;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $exam_attempt_logs;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->exam_attempt_logs = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set name
     *
     * @param string $name
     * @return AttemptsPackage
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
     * Set number_of_attempts
     *
     * @param integer $numberOfAttempts
     * @return AttemptsPackage
     */
    public function setNumberOfAttempts($numberOfAttempts)
    {
        $this->number_of_attempts = $numberOfAttempts;

        return $this;
    }

    /**
     * Get number_of_attempts
     *
     * @return integer 
     */
    public function getNumberOfAttempts()
    {
        return $this->number_of_attempts;
    }

    /**
     * Set cost
     *
     * @param integer $cost
     * @return AttemptsPackage
     */
    public function setCost($cost)
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * Get cost
     *
     * @return integer 
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Add exam_attempt_logs
     *
     * @param \My\AppBundle\Entity\ExamAttemptLog $examAttemptLogs
     * @return AttemptsPackage
     */
    public function addExamAttemptLog(\My\AppBundle\Entity\ExamAttemptLog $examAttemptLogs)
    {
        $this->exam_attempt_logs[] = $examAttemptLogs;

        return $this;
    }

    /**
     * Remove exam_attempt_logs
     *
     * @param \My\AppBundle\Entity\ExamAttemptLog $examAttemptLogs
     */
    public function removeExamAttemptLog(\My\AppBundle\Entity\ExamAttemptLog $examAttemptLogs)
    {
        $this->exam_attempt_logs->removeElement($examAttemptLogs);
    }

    /**
     * Get exam_attempt_logs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getExamAttemptLogs()
    {
        return $this->exam_attempt_logs;
    }
}
