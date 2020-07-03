<?php

namespace ColissimoLabel\Request\Helper;

use ColissimoLabel\Exception\InvalidArgumentException;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class Service
{
    const PRODUCT_CODE_LIST = [
        0 => 'DOM',
        1 => 'COLD',
        2 => 'DOS',
        3 => 'COL',
        4 => 'BPR',
        5 => 'A2P',
        6 => 'CORE',
        7 => 'COLR',
        8 => 'J+1 ',
        9 => 'CORI',
        10 => 'COM',
        11 => 'CDS',
        12 => 'ECO',
        13 => 'CORI',
        14 => 'COLI',
        15 => 'ACCI',
        16 => 'CMT',
        17 => 'PCS',
        18 => 'DOM',
        19 => 'DOS',
        20 => 'BDP'
    ];

    const PRODUCT_CODE_LIST_COMMERCIAL_NAME = [
        0 => 'France Colissimo Domicile - sans signature',
        1 => 'France Colissimo Domicile - sans signature',
        2 => 'France Colissimo Domicile - avec signature',
        3 => 'France Colissimo Domicile - avec signature',
        4 => 'France Colissimo - Point Retrait – en Bureau de Poste ** ',
        5 => 'France Colissimo - Point Retrait – en relais Pickup ou en consigne Pickup Station',
        6 => 'France Colissimo Retour France',
        7 => 'France Colissimo Flash - sans signature',
        8 => 'Colissimo Flash – avec signature',
        9 => 'Colissimo Retour International ',
        10 => 'Outre-Mer Colissimo Domicile - sans signature ',
        11 => 'Outre-Mer Colissimo Domicile - avec signature',
        12 => 'Outre-Mer Colissimo Eco OM',
        13 => 'Outre-Mer Colissimo Retour OM',
        14 => 'International Colissimo Expert International',
        15 => 'International Offre Economique Grand Export (offre en test pour la Chine pour un client Pilote)',
        16 => 'International (Europe) Colissimo - Point Retrait – en relais ****',
        17 => 'International (Europe) Colissimo - Point Retrait – Consigne Pickup Station – Sauf France et Belgique',
        18 => 'International (Europe) Colissimo Domicile - sans signature ****',
        19 => 'International (Europe) Colissimo Domicile - avec signature ****',
        20 => 'International (Europe) Colissimo Point Retrait – en bureau de poste ****'
    ];

    const EUROPE_ISOCODES = [
        '040', /** Austria */
        '056', /** Belgium */
        '100', /** Bulgaria */
        '191', /** Croatia */
        '196', /** Cyprus */
        '203', /** Czech Republic */
        '208', /** Denmark */
        '233', /** Estonia */
        '246', /** Finland */
        '250', /** France */
        '276', /** Germany */
        '300', /** Greece */
        '348', /** Hungary */
        '352', /** Iceland */
        '372', /** Ireland */
        '380', /** Italy */
        '428', /** Latvia */
        '440', /** Lithuania */
        '442', /** Luxembourg */
        '470', /** Malta */
        '528', /** Netherlands */
        '578', /** Norway */
        '616', /** Poland */
        '620', /** Portugal */
        '642', /** Romania */
        '703', /** Slovakia */
        '705', /** Slovenia */
        '724', /** Spain */
        '752', /** Sweden */
        '756', /** Switzerland */
        '826', /** United Kingdom */
    ];

    const DOMTOM_ISOCODES = [
        '175', /** Mayotte */
        '254', /** Guyane Française */
        '258', /** Polynésie Française */
        '312', /** Guadeloupe */
        '474', /** Martinique */
        '540', /** Nouvelle-Calédonie */
        '638', /** Réunion(La) */
        '666', /** St Pierre et Miquelon */
        '876', /** Wallis et Futuna */
    ];

    protected $productCode = '';

    /** @var \DateTime */
    protected $depositDate;

    protected $orderNumber = '';

    protected $commercialName = '';

    protected $transportationAmount = '';

    protected $returnTypeChoice = 3;

    public function __construct($productCode, \DateTime $depositDate, $orderNumber, $transportationAmount, $returnTypeChoice)
    {
        if (empty($orderNumber)) {
            throw new InvalidArgumentException('Invalid argument orderNumber');
        }

        if (empty($productCode)) {
            throw new InvalidArgumentException('Invalid argument productCode');
        }

        $this->orderNumber = $orderNumber;
        $this->depositDate = $depositDate;
        $this->productCode = $productCode;
        $this->transportationAmount = $transportationAmount;
        $this->returnTypeChoice = $returnTypeChoice;
    }

    /**
     * @return string
     */
    public function getProductCode()
    {
        return $this->productCode;
    }

    /**
     * @param string $productCode
     * @return $this
     */
    public function setProductCode($productCode)
    {
        $this->productCode = $productCode;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDepositDate()
    {
        return $this->depositDate;
    }

    /**
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * @return string
     */
    public function getCommercialName()
    {
        return $this->commercialName;
    }

    /**
     * @param string $commercialName
     * @return Service
     */
    public function setCommercialName($commercialName)
    {
        $this->commercialName = $commercialName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransportationAmount()
    {
        /** DO NOT use strict comparison here */
        if ($this->transportationAmount == 0) {
            return '0';
        }

        /** Formatting the postage price in a XXXX format (price in cents without separator), as requested by the Colissimo API */
        $nbr = number_format($this->transportationAmount, 2, '.', '');
        if ((float)$nbr < 10) {
            $nbr = '0' . $nbr;
        }
        return str_pad(str_replace('.', '', $nbr), 4, '0', STR_PAD_RIGHT);
    }

    /**
     * @param $transportationAmount
     * @return $this
     */
    public function setTransportationAmount($transportationAmount)
    {
        $this->transportationAmount = $transportationAmount;
        return $this;
    }


    /**
     * @return int
     */
    public function getReturnTypeChoice()
    {
        return $this->returnTypeChoice;
    }

    /**
     * @param $returnTypeChoice
     * @return $this
     */
    public function setReturnTypeChoice($returnTypeChoice)
    {
        $this->returnTypeChoice = $returnTypeChoice;
        return $this;
    }
}
