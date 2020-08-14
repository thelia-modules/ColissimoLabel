<?php

namespace ColissimoLabel\Request\Helper;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class Letter
{
    /** @var Sender */
    protected $sender;

    /** @var Addressee */
    protected $addressee;

    /** @var Parcel */
    protected $parcel;

    /** @var Service */
    protected $service;

    /** @var CustomsDeclarations */
    protected $customsDeclarations;

    public function __construct(
        Service $service,
        Sender $sender,
        Addressee $addressee,
        Parcel $parcel,
        CustomsDeclarations $customsDeclarations
    ) {
        $this->sender = $sender;
        $this->addressee = $addressee;
        $this->parcel = $parcel;
        $this->service = $service;
        $this->customsDeclarations = $customsDeclarations;
    }

    /**
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return Sender
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @return Addressee
     */
    public function getAddressee()
    {
        return $this->addressee;
    }

    /**
     * @return Parcel
     */
    public function getParcel()
    {
        return $this->parcel;
    }

    /**
     * @return CustomsDeclarations
     */
    public function getCustomsDeclarations()
    {
        return $this->customsDeclarations;
    }
}
