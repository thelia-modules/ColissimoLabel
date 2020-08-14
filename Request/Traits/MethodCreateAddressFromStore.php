<?php

namespace ColissimoLabel\Request\Traits;

use ColissimoLabel\ColissimoLabel;
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
            ->setCompanyName(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_NAME))
            ->setCity(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_CITY))
            ->setZipCode(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_ZIPCODE))
            ->setCountryCode(CountryQuery::create()->findOneById(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_COUNTRY))->getIsoalpha2())
            ->setLine2(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_ADDRESS_1))
            ->setLine3(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_ADDRESS_2))
            ->setEmail(trim(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_CONTACT_EMAIL)))
            ->setPhoneNumber(trim(str_replace(' ', '', ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_PHONE))))
            ->setLanguage(strtoupper(LangQuery::create()->filterByByDefault(true)->findOne()->getCode()))
            ;
    }
}
