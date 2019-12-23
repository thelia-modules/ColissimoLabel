<?php

namespace ColissimoLabel\Request\Helper;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class Parcel
{
    protected $weight = 0;

    protected $signedDelivery = false;

    protected $instructions = '';

    protected $pickupLocationId;

    public function __construct($weight)
    {
        $this->weight = (float) $weight;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param int $weight
     * @return self
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @return bool
     */
    public function getSignedDelivery()
    {
        return $this->signedDelivery;
    }

    /**
     * @param bool $signedDelivery
     * @return self
     */
    public function setSignedDelivery($signedDelivery)
    {
        $this->signedDelivery = $signedDelivery;
        return $this;
    }

    /**
     * @return string
     */
    public function getInstructions()
    {
        return $this->instructions;
    }

    /**
     * @param string $instructions
     * @return self
     */
    public function setInstructions($instructions)
    {
        $this->instructions = $instructions;
        return $this;
    }

    /**
     * @return string
     */
    public function getPickupLocationId()
    {
        return $this->pickupLocationId;
    }

    /**
     * @param string $pickupLocationId
     * @return self
     */
    public function setPickupLocationId($pickupLocationId)
    {
        $this->pickupLocationId = $pickupLocationId;
        return $this;
    }
}
