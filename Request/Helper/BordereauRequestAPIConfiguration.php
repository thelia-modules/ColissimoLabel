<?php

namespace ColissimoLabel\Request\Helper;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class BordereauRequestAPIConfiguration extends APIConfiguration
{
    public function __construct()
    {
        parent::__construct();

        $this->setMethod('generateBordereauByParcelsNumbers');
    }
}
