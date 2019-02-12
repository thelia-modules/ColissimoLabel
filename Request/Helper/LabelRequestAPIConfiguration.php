<?php

namespace ColissimoLabel\Request\Helper;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class LabelRequestAPIConfiguration extends APIConfiguration
{
    public function __construct()
    {
        parent::__construct();

        $this->setMethod('generateLabel');
    }
}
