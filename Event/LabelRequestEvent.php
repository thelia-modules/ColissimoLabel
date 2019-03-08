<?php

namespace ColissimoLabel\Event;

use ColissimoLabel\Request\LabelRequest;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class LabelRequestEvent extends Event
{
    protected $labelRequest;

    public function __construct(LabelRequest $labelRequest)
    {
        $this->labelRequest = $labelRequest;
    }

    public function getLabelRequest()
    {
        return $this->labelRequest;
    }
}
