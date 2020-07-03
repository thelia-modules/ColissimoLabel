<?php


namespace ColissimoLabel\Request\Helper;


use ColissimoLabel\ColissimoLabel;
use Thelia\Model\CountryQuery;
use Thelia\Model\Order;

class Article
{
    protected $description = '';

    protected $quantity = 0;

    protected $weight = 0;

    protected $value = 0;

    protected $hsCode = '';

    protected $currency = 'EUR';

    protected $authorizedCurrencies = [
        'USD',
        'EUR',
        'CHF',
        'GBP',
        'CNY',
        'JPY',
        'CAD',
        'AUD',
        'HKD',
    ];

    public function __construct($description, $quantity, $weight, $value, $hsCode, $currency)
    {

        $this->description = $description;
        $this->quantity = $quantity;
        $this->weight = (float) $weight;
        $this->value = (float) $value;
        $this->hsCode = $hsCode;
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC')->transliterate($this->description);
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return float|int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param float|int $weight
     * @return self
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @return float|int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float|int $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getHsCode()
    {
        return $this->hsCode;
    }

    /**
     * @param $hsCode
     * @return $this
     */
    public function setHsCode($hsCode)
    {
        $this->hsCode = $hsCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getOriginCountry() {
        return CountryQuery::create()->findOneById(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_COUNTRY))->getIsoalpha2();
    }

    /**
     * @param $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency() {
        return $this->currency;
    }
}