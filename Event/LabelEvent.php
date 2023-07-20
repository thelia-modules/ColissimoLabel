<?php

namespace ColissimoLabel\Event;

use ColissimoLabel\Model\ColissimoLabel;
use Thelia\Core\Event\ActionEvent;

class LabelEvent extends ActionEvent
{
    /** @var int */
    protected int $orderId;

    /** @var ColissimoLabel|null */
    protected ?ColissimoLabel $colissimoLabel = null;

    /** @var float|null */
    protected ?float $weight = null;

    /** @var bool|null */
    protected ?bool $signed = null;

    /**
     * LabelEvent constructor.
     *
     * @param int $orderId
     */
    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * @return ColissimoLabel|null
     */
    public function getColissimoLabel(): ?ColissimoLabel
    {
        return $this->colissimoLabel;
    }

    /**
     * @param ColissimoLabel $colissimoLabel
     *
     * @return $this
     */
    public function setColissimoLabel(ColissimoLabel $colissimoLabel): static
    {
        $this->colissimoLabel = $colissimoLabel;

        return $this;
    }

    public function hasLabel(): bool
    {
        return null !== $this->colissimoLabel;
    }

    /**
     * @return float|null
     */
    public function getWeight(): ?float
    {
        return $this->weight;
    }

    /**
     * @param float|null $weight
     *
     * @return $this
     */
    public function setWeight(?float $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getSigned(): ?bool
    {
        return $this->signed;
    }

    /**
     * @param bool|null $signed
     *
     * @return $this
     */
    public function setSigned(?bool $signed): static
    {
        $this->signed = $signed;

        return $this;
    }
}
