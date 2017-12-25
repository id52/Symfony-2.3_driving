<?php

namespace My\AppBundle\Service;

use Monolog\Logger;

class DevChannelLogger
{
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
