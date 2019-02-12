<?php

namespace ColissimoLabel\Request;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
abstract class AbstractRequest
{
    protected $contractNumber = '';

    protected $password = '';

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

    public function generateArrayRequest()
    {
        return [
            'contractNumber' => $this->getContractNumber(),
            'password' => $this->getPassword()
        ];
    }
}
