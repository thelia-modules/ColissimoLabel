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
use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Thelia\Module\BaseModule;
use Thelia\Install\Database;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class ColissimoLabel extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'colissimolabel';

    const LABEL_FOLDER = THELIA_LOCAL_DIR . 'colissimo-label';

    const BORDEREAU_FOLDER = self::LABEL_FOLDER . DIRECTORY_SEPARATOR . 'bordereau';

    const CONFIG_KEY_DEFAULT_LABEL_FORMAT = 'default-label-format';

    const CONFIG_KEY_CONTRACT_NUMBER = 'contract-number';

    const CONFIG_KEY_PASSWORD = 'password';

    const CONFIG_KEY_AUTO_SENT_STATUS = 'auto-sent-status';

    const CONFIG_DEFAULT_AUTO_SENT_STATUS = 1;

    const CONFIG_KEY_SENT_STATUS_ID = 'sent-status-id';

    const CONFIG_DEFAULT_SENT_STATUS_ID = 4;

    const CONFIG_KEY_PRE_FILL_INPUT_WEIGHT = 'pre-fill-input-weight';

    const CONFIG_DEFAULT_PRE_FILL_INPUT_WEIGHT = 1;

    const CONFIG_KEY_LAST_BORDEREAU_DATE = 'last-bordereau-date';

    const CONFIG_DEFAULT_KEY_LAST_BORDEREAU_DATE = 1970;

    /**
     * @param ConnectionInterface $con
     */
    public function postActivation(ConnectionInterface $con = null)
    {
        static::checkLabelFolder();

        if (!$this->getConfigValue('is_initialized', false)) {
            $database = new Database($con);
            $database->insertSql(null, [__DIR__ . "/Config/thelia.sql"]);
            $this->setConfigValue('is_initialized', true);
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

    protected function checkConfigurationsValues()
    {
        if (null === self::getConfigValue(self::CONFIG_KEY_DEFAULT_LABEL_FORMAT)) {
            self::setConfigValue(
                self::CONFIG_KEY_DEFAULT_LABEL_FORMAT,
                OutputFormat::OUTPUT_PRINTING_TYPE_DEFAULT
            );
        }

        if (null === self::getConfigValue(self::CONFIG_KEY_CONTRACT_NUMBER)) {
            self::setConfigValue(
                self::CONFIG_KEY_CONTRACT_NUMBER,
                ""
            );
        }

        if (null === self::getConfigValue(self::CONFIG_KEY_PASSWORD)) {
            self::setConfigValue(
                self::CONFIG_KEY_PASSWORD,
                ""
            );
        }

        if (null === self::getConfigValue(self::CONFIG_KEY_AUTO_SENT_STATUS)) {
            self::setConfigValue(
                self::CONFIG_KEY_AUTO_SENT_STATUS,
                self::CONFIG_DEFAULT_AUTO_SENT_STATUS
            );
        }

        if (null === self::getConfigValue(self::CONFIG_KEY_SENT_STATUS_ID)) {
            self::setConfigValue(
                self::CONFIG_KEY_SENT_STATUS_ID,
                self::CONFIG_DEFAULT_SENT_STATUS_ID
            );
        }

        if (null === self::getConfigValue(self::CONFIG_KEY_AUTO_SENT_STATUS)) {
            self::setConfigValue(
                self::CONFIG_KEY_AUTO_SENT_STATUS,
                self::CONFIG_DEFAULT_AUTO_SENT_STATUS
            );
        }

        if (null === self::getConfigValue(self::CONFIG_KEY_PRE_FILL_INPUT_WEIGHT)) {
            self::setConfigValue(
                self::CONFIG_KEY_PRE_FILL_INPUT_WEIGHT,
                self::CONFIG_DEFAULT_PRE_FILL_INPUT_WEIGHT
            );
        }

        if (null === self::getConfigValue(self::CONFIG_KEY_LAST_BORDEREAU_DATE)) {
            self::setConfigValue(
                self::CONFIG_KEY_LAST_BORDEREAU_DATE,
                self::CONFIG_DEFAULT_KEY_LAST_BORDEREAU_DATE
            );
        }

    }

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

    public static function getLabelPath($number, $extension)
    {
        return self::LABEL_FOLDER . DIRECTORY_SEPARATOR . $number . '.' . $extension;
    }

    public static function getLabelCN23Path($number, $extension)
    {
        return self::LABEL_FOLDER . DIRECTORY_SEPARATOR . $number . '.' . $extension;
    }

    public static function getBordereauPath($date)
    {
        return self::BORDEREAU_FOLDER . DIRECTORY_SEPARATOR . $date . '.pdf';
    }


    public static function getExtensionFile()
    {
        return strtolower(substr(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_DEFAULT_LABEL_FORMAT), 0, 3));
    }
}
