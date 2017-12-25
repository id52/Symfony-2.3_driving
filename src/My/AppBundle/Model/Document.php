<?php

namespace My\AppBundle\Model;

/**
 * Document
 */
abstract class Document
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var boolean
     */
    protected $re_sent;

    /**
     * @var string
     */
    protected $comment;

    /**
     * @var \DateTime
     */
    protected $updated_at;

    /**
     * @var \My\AppBundle\Entity\User
     */
    protected $user;


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
     * Set file
     *
     * @param string $file
     * @return Document
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file
     *
     * @return string 
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Document
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Document
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set re_sent
     *
     * @param boolean $reSent
     * @return Document
     */
    public function setReSent($reSent)
    {
        $this->re_sent = $reSent;

        return $this;
    }

    /**
     * Get re_sent
     *
     * @return boolean 
     */
    public function getReSent()
    {
        return $this->re_sent;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return Document
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
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     * @return Document
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set user
     *
     * @param \My\AppBundle\Entity\User $user
     * @return Document
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
     * ORM\prePersist
     */
    public function preUpload()
    {
        // Add your code here
    }

    /**
     * ORM\postPersist
     */
    public function upload()
    {
        // Add your code here
    }

    /**
     * ORM\postUpdate
     */
    public function removeUploadCache()
    {
        // Add your code here
    }

    /**
     * ORM\postRemove
     */
    public function removeUpload()
    {
        // Add your code here
    }
}
