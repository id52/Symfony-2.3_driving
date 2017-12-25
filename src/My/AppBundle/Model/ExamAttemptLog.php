<?php

namespace My\AppBundle\Model;

/**
 * ExamAttemptLog
 */
abstract class ExamAttemptLog
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var \DateTime
     */
    protected $created_at;

    /**
     * @var integer
     */
    protected $amount;

    /**
     * @var boolean
     */
    protected $is_free = false;

    /**
     * @var boolean
     */
    protected $is_passed = false;

    /**
     * @var \My\AppBundle\Entity\FinalExamLog
     */
    protected $final_exam_log;

    /**
     * @var \My\AppBundle\Entity\ExamLog
     */
    protected $exam_log;

    /**
     * @var \My\AppBundle\Entity\User
     */
    protected $user;

    /**
     * @var \My\AppBundle\Entity\Subject
     */
    protected $subject;

    /**
     * @var \My\AppBundle\Entity\AttemptsPackage
     */
    protected $attempts_package;


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
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return ExamAttemptLog
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     * @return ExamAttemptLog
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set is_free
     *
     * @param boolean $isFree
     * @return ExamAttemptLog
     */
    public function setIsFree($isFree)
    {
        $this->is_free = $isFree;

        return $this;
    }

    /**
     * Get is_free
     *
     * @return boolean 
     */
    public function getIsFree()
    {
        return $this->is_free;
    }

    /**
     * Set is_passed
     *
     * @param boolean $isPassed
     * @return ExamAttemptLog
     */
    public function setIsPassed($isPassed)
    {
        $this->is_passed = $isPassed;

        return $this;
    }

    /**
     * Get is_passed
     *
     * @return boolean 
     */
    public function getIsPassed()
    {
        return $this->is_passed;
    }

    /**
     * Set final_exam_log
     *
     * @param \My\AppBundle\Entity\FinalExamLog $finalExamLog
     * @return ExamAttemptLog
     */
    public function setFinalExamLog(\My\AppBundle\Entity\FinalExamLog $finalExamLog = null)
    {
        $this->final_exam_log = $finalExamLog;

        return $this;
    }

    /**
     * Get final_exam_log
     *
     * @return \My\AppBundle\Entity\FinalExamLog 
     */
    public function getFinalExamLog()
    {
        return $this->final_exam_log;
    }

    /**
     * Set exam_log
     *
     * @param \My\AppBundle\Entity\ExamLog $examLog
     * @return ExamAttemptLog
     */
    public function setExamLog(\My\AppBundle\Entity\ExamLog $examLog = null)
    {
        $this->exam_log = $examLog;

        return $this;
    }

    /**
     * Get exam_log
     *
     * @return \My\AppBundle\Entity\ExamLog 
     */
    public function getExamLog()
    {
        return $this->exam_log;
    }

    /**
     * Set user
     *
     * @param \My\AppBundle\Entity\User $user
     * @return ExamAttemptLog
     */
    public function setUser(\My\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \My\AppBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set subject
     *
     * @param \My\AppBundle\Entity\Subject $subject
     * @return ExamAttemptLog
     */
    public function setSubject(\My\AppBundle\Entity\Subject $subject = null)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return \My\AppBundle\Entity\Subject 
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set attempts_package
     *
     * @param \My\AppBundle\Entity\AttemptsPackage $attemptsPackage
     * @return ExamAttemptLog
     */
    public function setAttemptsPackage(\My\AppBundle\Entity\AttemptsPackage $attemptsPackage = null)
    {
        $this->attempts_package = $attemptsPackage;

        return $this;
    }

    /**
     * Get attempts_package
     *
     * @return \My\AppBundle\Entity\AttemptsPackage 
     */
    public function getAttemptsPackage()
    {
        return $this->attempts_package;
    }
}
