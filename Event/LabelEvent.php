<?php


namespace ColissimoLabel\Event;


use ColissimoLabel\Model\ColissimoLabel;
use Thelia\Core\Event\ActionEvent;

class LabelEvent extends ActionEvent
{
    /** @var int */
    protected $orderId;

    /** @var ColissimoLabel */
    protected $colissimoLabel = null;

    /** @var float|null */
    protected $weight = null;

    /** @var bool|null */
    protected $signed = null;
    /**
     * LabelEvent constructor.
     * @param int $orderId
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @return int
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @return ColissimoLabel
     */
    public function getColissimoLabel()
    {
        return $this->colissimoLabel;
    }

    /**
     * @param ColissimoLabel $colissimoLabel
     * @return $this
     */
    public function setColissimoLabel($colissimoLabel)
    {
        $this->colissimoLabel = $colissimoLabel;
        return $this;
    }

    public function hasLabel()
    {
        return null !== $this->colissimoWsLabel;
    }

    /**
     * @return float|null
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param float|null $weight
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getSigned()
    {
        return $this->signed;
    }

    /**
     * @param bool|null $signed
     * @return $this
     */
    public function setSigned($signed)
    {
        $this->signed = $signed;
        return $this;
    }
}