<?php

namespace ColissimoLabel\Request\Traits;

use ColissimoLabel\Request\Helper\Address;
use Thelia\Model\ConfigQuery;
use Thelia\Model\CountryQuery;
use Thelia\Model\LangQuery;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
trait MethodCreateAddressFromStore
{
    public function createAddressFromStore()
    {
        return (new Address())
            ->setCompanyName(ConfigQuery::read('store_name'))
            ->setCity(ConfigQuery::read('store_city'))
            ->setZipCode(ConfigQuery::read('store_zipcode'))
            ->setCountryCode(CountryQuery::create()->findOneById(ConfigQuery::read('store_country'))->getIsoalpha2())
            ->setLine2(ConfigQuery::read('store_address1'))
            ->setLine3(ConfigQuery::read('store_address2'))
            ->setEmail(trim(ConfigQuery::read('store_email')))
            ->setPhoneNumber(trim(str_replace(' ', '', ConfigQuery::read('store_phone'))))
            ->setLanguage(strtoupper(LangQuery::create()->filterByByDefault(true)->findOne()->getCode()))
            ;
    }
}
