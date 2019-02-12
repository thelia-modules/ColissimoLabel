<?php

namespace ColissimoLabel\Request\Helper;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class Addressee
{
    /** @var string */
    protected $addresseeParcelRef;

    /** @var Address */
    protected $address;

    public function __construct(Address $address)
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getAddresseeParcelRef()
    {
        return $this->addresseeParcelRef;
    }

    /**
     * @param string $addresseeParcelRef
     */
    public function setAddresseeParcelRef($addresseeParcelRef)
    {
        $this->addresseeParcelRef = $addresseeParcelRef;
    }

    /**
     * @return Address
     */
    public function getAddress()
    {
        return $this->address;
    }
}
