<?php

namespace ColissimoLabel\Event;

use ColissimoLabel\Request\LabelRequest;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class LabelRequestEvent extends Event
{
    protected LabelRequest $labelRequest;

    public function __construct(LabelRequest $labelRequest)
    {
        $this->labelRequest = $labelRequest;
    }

    public function getLabelRequest(): LabelRequest
    {
        return $this->labelRequest;
    }
}
