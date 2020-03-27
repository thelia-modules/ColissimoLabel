<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace ColissimoLabel;

use ColissimoLabel\Request\Helper\OutputFormat;
use ColissimoLabel\Request\Helper\Service;
use ColissimoWs\ColissimoWs;
use Propel\Runtime\Connection\ConnectionInterface;
use SoColissimo\SoColissimo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Thelia\Model\ConfigQuery;
use Thelia\Model\ModuleQuery;
use Thelia\Model\Order;
use Thelia\Module\BaseModule;
use Thelia\Install\Database;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class ColissimoLabel extends BaseModule
{
    /** Constants */
    const DOMAIN_NAME = 'colissimolabel';

    const LABEL_FOLDER = THELIA_LOCAL_DIR . 'colissimo-label';

    const BORDEREAU_FOLDER = self::LABEL_FOLDER . DIRECTORY_SEPARATOR . 'bordereau';

    const AUTHORIZED_MODULES = ['ColissimoWs', 'SoColissimo'];

    const CONFIG_KEY_DEFAULT_LABEL_FORMAT = 'default-label-format';

    const CONFIG_KEY_CONTRACT_NUMBER = 'contract-number';

    const CONFIG_KEY_PASSWORD = 'password';

    const CONFIG_KEY_LAST_BORDEREAU_DATE = 'last-bordereau-date';

    const CONFIG_DEFAULT_KEY_LAST_BORDEREAU_DATE = 1970;

    const CONFIG_KEY_DEFAULT_SIGNED = 'default-signed';

    const CONFIG_DEFAULT_KEY_DEFAULT_SIGNED = true;

    const CONFIG_KEY_GENERATE_BORDEREAU = 'generate-bordereau';

    const CONFIG_DEFAULT_KEY_GENERATE_BORDEREAU = false;

    const CONFIG_KEY_GET_INVOICES = 'get-invoices';

    const CONFIG_DEFAULT_KEY_GET_INVOICES = true;

    const CONFIG_KEY_GET_CUSTOMS_INVOICES = 'get-customs-invoices';

    const CONFIG_DEFAULT_KEY_GET_CUSTOMS_INVOICES = false;

    const CONFIG_KEY_CUSTOMS_PRODUCT_HSCODE = 'customs-product-hscode';

    const CONFIG_DEFAULT_KEY_CUSTOMS_PRODUCT_HSCODE = '';

    const CONFIG_KEY_STATUS_CHANGE = 'new_status';

    const CONFIG_DEFAULT_KEY_STATUS_CHANGE = 'nochange';

    const CONFIG_KEY_ENDPOINT = 'colissimolabel-endpoint';

    const CONFIG_DEFAULT_KEY_ENDPOINT = 'https://ws.colissimo.fr/sls-ws/SlsServiceWS/2.0?wsdl';

    const CONFIG_KEY_FROM_NAME = 'colissimolabel-company-name';

    const CONFIG_KEY_FROM_ADDRESS_1 = 'colissimolabel-from-address-1';

    const CONFIG_KEY_FROM_ADDRESS_2 = 'colissimolabel-from-address-2';

    const CONFIG_KEY_FROM_CITY = 'colissimolabel-from-city';

    const CONFIG_KEY_FROM_ZIPCODE = 'colissimolabel-from-zipcode';

    const CONFIG_KEY_FROM_COUNTRY = 'colissimolabel-from-country';

    const CONFIG_KEY_FROM_CONTACT_EMAIL = 'colissimolabel-from-contact-email';

    const CONFIG_KEY_FROM_PHONE = 'colissimolabel-from-phone';

    /**
     * @param ConnectionInterface $con
     */
    public function postActivation(ConnectionInterface $con = null)
    {
        static::checkLabelFolder();

        if (!self::getConfigValue('is_initialized', false)) {
            $database = new Database($con);
            $database->insertSql(null, [__DIR__ . "/Config/thelia.sql"]);
            self::setConfigValue('is_initialized', true);
        }

        $this->checkConfigurationsValues();
    }

    public function update($currentVersion, $newVersion, ConnectionInterface $con = null)
    {
        $finder = Finder::create()
            ->name('*.sql')
            ->depth(0)
            ->sortByName()
            ->in(__DIR__ . DS . 'Config' . DS . 'update');

        $database = new Database($con);

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            if (version_compare($currentVersion, $file->getBasename('.sql'), '<')) {
                $database->insertSql(null, [$file->getPathname()]);
            }
        }
    }

    /**
     * Check if config values exist in the module config table exists. Creates them with a default value otherwise
     */
    public function checkConfigurationsValues()
    {
        /** Check if the default label format config value exists, and sets it to PDF_10x15_300dpi is it doesn't exists */
        if (null === self::getConfigValue(self::CONFIG_KEY_DEFAULT_LABEL_FORMAT)) {
            self::setConfigValue(
                self::CONFIG_KEY_DEFAULT_LABEL_FORMAT,
                OutputFormat::OUTPUT_PRINTING_TYPE_DEFAULT
            );
        }

        /**
         * Check if the contract number config value exists, and sets it to either of the following :
         * The contract number of the ColissimoWS config, if the module is activated
         * Otherwise : the contract number of the SoColissimo config, if the module is activated
         * Otherwise : a blanck string : ""
         */
        if (null === self::getConfigValue(self::CONFIG_KEY_CONTRACT_NUMBER)) {

            $contractNumber = '';
            if (ModuleQuery::create()->findOneByCode(self::AUTHORIZED_MODULES[1])) {
                $contractNumber = SoColissimo::getConfigValue('socolissimo_username');
            }
            if (ModuleQuery::create()->findOneByCode(self::AUTHORIZED_MODULES[0])) {
                $contractNumber = ColissimoWs::getConfigValue('colissimo_username');
            }

            self::setConfigValue(
                self::CONFIG_KEY_CONTRACT_NUMBER,
                $contractNumber
            );
        }

        /**
         * Check if the contract password config value exists, and sets it to either of the following :
         * The contract password of the ColissimoWS config, if the module is activated
         * Otherwise : the contract password of the SoColissimo config, if the module is activated
         * Otherwise : a blank string : ""
         */
        if (null === self::getConfigValue(self::CONFIG_KEY_PASSWORD)) {

            $contractPassword = '';
            if (ModuleQuery::create()->findOneByCode(self::AUTHORIZED_MODULES[1])) {
                $contractPassword = SoColissimo::getConfigValue('socolissimo_password');
            }
            if (ModuleQuery::create()->findOneByCode(self::AUTHORIZED_MODULES[0])) {
                $contractPassword = ColissimoWs::getConfigValue('colissimo_password');
            }

            self::setConfigValue(
                self::CONFIG_KEY_PASSWORD,
                $contractPassword
            );
        }

        /** Check if the config value for the status change exists, creates it with a default value of 'nochange' otherwise */
        if (null === self::getConfigValue(self::CONFIG_KEY_STATUS_CHANGE)) {
            self::setConfigValue(
                self::CONFIG_KEY_STATUS_CHANGE,
                self::CONFIG_DEFAULT_KEY_STATUS_CHANGE
            );
        }

        /** Check if the config value for the endpoint exists, creates it with a default value otherwise */
        if (null === self::getConfigValue(self::CONFIG_KEY_ENDPOINT)) {
            self::setConfigValue(
                self::CONFIG_KEY_ENDPOINT,
                self::CONFIG_DEFAULT_KEY_ENDPOINT
            );
        }

        /** Check if the config value for the last bordereau date exists, creates it with a default value of 1970 otherwise */
        if (null === self::getConfigValue(self::CONFIG_KEY_LAST_BORDEREAU_DATE)) {
            self::setConfigValue(
                self::CONFIG_KEY_LAST_BORDEREAU_DATE,
                self::CONFIG_DEFAULT_KEY_LAST_BORDEREAU_DATE
            );
        }

        /** Check if the config value for the default signed state for labels exists, creates it with a value of true otherwise */
        if (null === self::getConfigValue(self::CONFIG_KEY_DEFAULT_SIGNED)) {
            self::setConfigValue(
                self::CONFIG_KEY_DEFAULT_SIGNED,
                self::CONFIG_DEFAULT_KEY_DEFAULT_SIGNED
            );
        }

        /** Check if the config value for whether bordereau should be generated with labels exists, creates it with a value of false otherwise */
        if (null === self::getConfigValue(self::CONFIG_KEY_GENERATE_BORDEREAU)) {
            self::setConfigValue(
                self::CONFIG_KEY_GENERATE_BORDEREAU,
                (int)self::CONFIG_DEFAULT_KEY_GENERATE_BORDEREAU
            );
        }

        /** Check if the config value for whether invoices should be automatically generated exists, creates it with a value of true otherwise */
        if (null === self::getConfigValue(self::CONFIG_KEY_GET_INVOICES)) {
            self::setConfigValue(
                self::CONFIG_KEY_GET_INVOICES,
                self::CONFIG_DEFAULT_KEY_GET_INVOICES
            );
        }

        /** Check if the config value for whether customs invoices should be automatically generated exists, creates it with a value of false otherwise */
        if (null === self::getConfigValue(self::CONFIG_KEY_GET_CUSTOMS_INVOICES)) {
            self::setConfigValue(
                self::CONFIG_KEY_GET_CUSTOMS_INVOICES,
                self::CONFIG_DEFAULT_KEY_GET_CUSTOMS_INVOICES
            );
        }

        /** Check if the config value for the customs product HsCode exists, creates it without value otherwise */
        if (null === self::getConfigValue(self::CONFIG_KEY_CUSTOMS_PRODUCT_HSCODE)) {
            self::setConfigValue(
                self::CONFIG_KEY_CUSTOMS_PRODUCT_HSCODE,
                self::CONFIG_DEFAULT_KEY_CUSTOMS_PRODUCT_HSCODE
            );
        }

        /** Check if the config values for the sender address exist, create them otherwise with the store address values otherwise */
        if (null === self::getConfigValue(self::CONFIG_KEY_FROM_NAME)) {
            self::setConfigValue(
                self::CONFIG_KEY_FROM_NAME,
                ConfigQuery::read('store_name')
            );
        }

        if (null === self::getConfigValue(self::CONFIG_KEY_FROM_ADDRESS_1)) {
            self::setConfigValue(
                self::CONFIG_KEY_FROM_ADDRESS_1,
                ConfigQuery::read('store_address1')
            );
        }

        if (null === self::getConfigValue(self::CONFIG_KEY_FROM_ADDRESS_2)) {
            self::setConfigValue(
                self::CONFIG_KEY_FROM_ADDRESS_2,
                ConfigQuery::read('store_address2')
            );
        }

        if (null === self::getConfigValue(self::CONFIG_KEY_FROM_CITY)) {
            self::setConfigValue(
                self::CONFIG_KEY_FROM_CITY,
                ConfigQuery::read('store_city')
            );
        }

        if (null === self::getConfigValue(self::CONFIG_KEY_FROM_ZIPCODE)) {
            self::setConfigValue(
                self::CONFIG_KEY_FROM_ZIPCODE,
                ConfigQuery::read('store_zipcode')
            );
        }

        if (null === self::getConfigValue(self::CONFIG_KEY_FROM_COUNTRY)) {
            self::setConfigValue(
                self::CONFIG_KEY_FROM_COUNTRY,
                ConfigQuery::read('store_country')
            );
        }

        if (null === self::getConfigValue(self::CONFIG_KEY_FROM_CONTACT_EMAIL)) {
            self::setConfigValue(
                self::CONFIG_KEY_FROM_CONTACT_EMAIL,
                ConfigQuery::read('store_email')
            );
        }

        if (null === self::getConfigValue(self::CONFIG_KEY_FROM_PHONE)) {
            self::setConfigValue(
                self::CONFIG_KEY_FROM_PHONE,
                ConfigQuery::read('store_phone')
            );
        }
        /** Sender address values check end here */
    }

    /**
     * Check if the label and bordereau folders exists. Creates them otherwise.
     */
    public static function checkLabelFolder()
    {
        $fileSystem = new Filesystem();

        if (!$fileSystem->exists(self::LABEL_FOLDER)) {
            $fileSystem->mkdir(self::LABEL_FOLDER);
        }
        if (!$fileSystem->exists(self::BORDEREAU_FOLDER)) {
            $fileSystem->mkdir(self::BORDEREAU_FOLDER);
        }
    }

    /** Get the path of a given label file, according to its number */
    public static function getLabelPath($fileName, $extension)
    {
        return self::LABEL_FOLDER . DS . $fileName . '.' . $extension;
    }

    /** Get the path of a given CN23 customs file, according to the order ref */
    public static function getLabelCN23Path($fileName, $extension)
    {
        return self::LABEL_FOLDER . DS . $fileName . '.' . $extension;
    }

    /** Get the path of a bordereau file, according to a date */
    public static function getBordereauPath($date)
    {
        return self::BORDEREAU_FOLDER . DS . $date . '.pdf';
    }

    /** Get the label files extension according to the file type indicated in the module config */
    public static function getFileExtension()
    {
        return strtolower(substr(OutputFormat::OUTPUT_PRINTING_TYPE[self::getConfigValue(self::CONFIG_KEY_DEFAULT_LABEL_FORMAT)], 0, 3));
    }

    /**
     * Check if order has to be signed or if it is optionnal (aka if its in Europe or not)
     *
     * @param Order $order
     * @return bool
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public static function canOrderBeNotSigned(Order $order)
    {
        $countryIsoCode = $order->getOrderAddressRelatedByDeliveryOrderAddressId()->getCountry()->getIsocode();

        /** Checking if the delivery country is in Europe or a DOMTOM. If not, it HAS to be signed */
        if (!in_array($countryIsoCode, Service::DOMTOM_ISOCODES, false)
        && !in_array($countryIsoCode, Service::EUROPE_ISOCODES, false))
        {
            return false;
        }

        return true;
    }

    /**
     * Remove the accentuated and special characters from a string an replace them with
     * latin ASCII characters. Does the same to cyrillic.
     *
     * @param $str
     * @return false|string
     */
    public static function removeAccents($str) {
        return iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", transliterator_transliterate('Any-Latin; Latin-ASCII; Upper()', $str));
    }
}
