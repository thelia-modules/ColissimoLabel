<?php

namespace ColissimoLabel\Request\Helper;

use Thelia\Model\ConfigQuery;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
abstract class APIConfiguration
{
    protected $contractNumber = '';

    protected $password = '';

    protected $version = '2.0';

    protected $wsdl = '';

    protected $method = '';

    public function __construct()
    {
        $this->setContractNumber(ConfigQuery::read('colissimo.api.contract.number'));
        $this->setPassword(ConfigQuery::read('colissimo.api.password'));
        $this->setWsdl('https://ws.colissimo.fr/sls-ws/SlsServiceWS/2.0?wsdl');
    }

    /**
     * @return string
     */
    public function getContractNumber()
    {
        return $this->contractNumber;
    }

    /**
     * @param string $contractNumber
     * @return self
     */
    public function setContractNumber($contractNumber)
    {
        $this->contractNumber = $contractNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return self
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return self
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return string
     */
    public function getWsdl()
    {
        return $this->wsdl;
    }

    /**
     * @param string $wsdl
     * @return self
     */
    public function setWsdl($wsdl)
    {
        $this->wsdl = $wsdl;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return self
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }
}
