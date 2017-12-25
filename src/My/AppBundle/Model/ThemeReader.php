<?php

namespace My\AppBundle\Model;

/**
 * ThemeReader
 */
abstract class ThemeReader
{
    /**
     * @var \My\AppBundle\Entity\Theme
     */
    protected $theme;

    /**
     * @var \My\AppBundle\Entity\User
     */
    protected $reader;


    /**
     * Set theme
     *
     * @param \My\AppBundle\Entity\Theme $theme
     * @return ThemeReader
     */
    public function setTheme(\My\AppBundle\Entity\Theme $theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get theme
     *
     * @return \My\AppBundle\Entity\Theme 
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Set reader
     *
     * @param \My\AppBundle\Entity\User $reader
     * @return ThemeReader
     */
    public function setReader(\My\AppBundle\Entity\User $reader)
    {
        $this->reader = $reader;

        return $this;
    }

    /**
     * Get reader
     *
     * @return \My\AppBundle\Entity\User 
     */
    public function getReader()
    {
        return $this->reader;
    }
}
