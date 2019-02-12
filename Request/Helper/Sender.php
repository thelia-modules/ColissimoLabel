<?php

namespace ColissimoLabel\Request\Helper;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class Sender
{
    /** @var string */
    protected $senderParcelRef;

    /** @var Address */
    protected $address;

    public function __construct(Address $address)
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getSenderParcelRef()
    {
        return $this->senderParcelRef;
    }

    /**
     * @param string $senderParcelRef
     * @return self
     */
    public function setSenderParcelRef($senderParcelRef)
    {
        $this->senderParcelRef = $senderParcelRef;
        return $this;
    }

    /**
     * @return Address
     */
    public function getAddress()
    {
        return $this->address;
    }
}
