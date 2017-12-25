<?php

namespace My\AppBundle\Model;

/**
 * Image
 */
abstract class Image
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
     * @var \DateTime
     */
    protected $updated_at;

    /**
     * @var \My\AppBundle\Entity\Question
     */
    protected $question;

    /**
     * @var \My\AppBundle\Entity\Category
     */
    protected $category;

    /**
     * @var \My\AppBundle\Entity\Subject
     */
    protected $subject;

    /**
     * @var \My\AppBundle\Entity\FlashBlockItem
     */
    protected $flash_block_item;


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
     * @return Image
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
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     * @return Image
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
     * Set question
     *
     * @param \My\AppBundle\Entity\Question $question
     * @return Image
     */
    public function setQuestion(\My\AppBundle\Entity\Question $question = null)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question
     *
     * @return \My\AppBundle\Entity\Question 
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * Set category
     *
     * @param \My\AppBundle\Entity\Category $category
     * @return Image
     */
    public function setCategory(\My\AppBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \My\AppBundle\Entity\Category 
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set subject
     *
     * @param \My\AppBundle\Entity\Subject $subject
     * @return Image
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
     * Set flash_block_item
     *
     * @param \My\AppBundle\Entity\FlashBlockItem $flashBlockItem
     * @return Image
     */
    public function setFlashBlockItem(\My\AppBundle\Entity\FlashBlockItem $flashBlockItem = null)
    {
        $this->flash_block_item = $flashBlockItem;

        return $this;
    }

    /**
     * Get flash_block_item
     *
     * @return \My\AppBundle\Entity\FlashBlockItem 
     */
    public function getFlashBlockItem()
    {
        return $this->flash_block_item;
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
