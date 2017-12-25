<?php

namespace My\AppBundle\Entity;

use My\AppBundle\Model\OweStage as OweStageModel;
use My\AppBundle\Util\Time;

class OweStage extends OweStageModel
{
    protected $paid = false;

    public function getAllSecondsLeft()
    {
        $end_time =  $this->getEnd() ? $this->getEnd()->diff(new \DateTime('now')) : null;

        return Time::getAllSeconds($end_time);
    }
}
