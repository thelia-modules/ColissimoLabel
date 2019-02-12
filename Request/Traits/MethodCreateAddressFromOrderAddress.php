<?php

namespace ColissimoLabel\Request\Traits;

use ColissimoLabel\Request\Helper\Address;
use Thelia\Model\Customer;
use Thelia\Model\LangQuery;
use Thelia\Model\OrderAddress;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
trait MethodCreateAddressFromOrderAddress
{
    public function createAddressFromOrderAddress(OrderAddress $orderAddress, Customer $customer)
    {
        return (new Address())
            ->setCompanyName($orderAddress->getCompany())
            ->setFirstName($orderAddress->getFirstname())
            ->setLastName($orderAddress->getLastname())
            ->setCity($orderAddress->getCity())
            ->setZipCode($orderAddress->getZipcode())
            ->setCountryCode($orderAddress->getCountry()->getIsoalpha2())
            ->setLine2($orderAddress->getAddress1())
            ->setLine3($orderAddress->getAddress2())
            ->setPhoneNumber(trim(str_replace(' ', '', $orderAddress->getPhone())))
            ->setMobileNumber(trim(str_replace(' ', '', $orderAddress->getCellphone())))
            ->setEmail($customer->getEmail())
            ->setLanguage(strtoupper(LangQuery::create()->filterByByDefault(true)->findOne()->getCode()))
            ;
    }
}
