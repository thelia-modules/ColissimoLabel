<?php


namespace ColissimoLabel\Controller\Admin;


use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Form\ConfigureColissimoLabel;
use Thelia\Controller\Admin\AdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Tools\URL;

class ConfigurationController extends AdminController
{
    public function renderConfigPageAction() {
        (new \ColissimoLabel\ColissimoLabel)->checkConfigurationsValues();

        return $this->render('colissimo-label/module-configuration');
    }

    public function saveConfig()
    {
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), array('ColissimoLabel'), AccessManager::UPDATE)) {
            return $response;
        }

        $form = new ConfigureColissimoLabel($this->getRequest());
        try {
            $vform = $this->validateForm($form);

            /** General config values */
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER, $vform->get(ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_PASSWORD, $vform->get(ColissimoLabel::CONFIG_KEY_PASSWORD)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_DEFAULT_SIGNED, (int)$vform->get(ColissimoLabel::CONFIG_KEY_DEFAULT_SIGNED)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_GENERATE_BORDEREAU, (int)$vform->get(ColissimoLabel::CONFIG_KEY_GENERATE_BORDEREAU)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_DEFAULT_LABEL_FORMAT, $vform->get(ColissimoLabel::CONFIG_KEY_DEFAULT_LABEL_FORMAT)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_LAST_BORDEREAU_DATE, $vform->get(ColissimoLabel::CONFIG_KEY_LAST_BORDEREAU_DATE)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_GET_INVOICES, (int)$vform->get(ColissimoLabel::CONFIG_KEY_GET_INVOICES)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_GET_CUSTOMS_INVOICES, (int)$vform->get(ColissimoLabel::CONFIG_KEY_GET_CUSTOMS_INVOICES)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_CUSTOMS_PRODUCT_HSCODE, $vform->get(ColissimoLabel::CONFIG_KEY_CUSTOMS_PRODUCT_HSCODE)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_ENDPOINT, $vform->get(ColissimoLabel::CONFIG_KEY_ENDPOINT)->getData());

            /** Sender's address values */
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_FROM_NAME, $vform->get(ColissimoLabel::CONFIG_KEY_FROM_NAME)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_FROM_ADDRESS_1, $vform->get(ColissimoLabel::CONFIG_KEY_FROM_ADDRESS_1)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_FROM_ADDRESS_2, $vform->get(ColissimoLabel::CONFIG_KEY_FROM_ADDRESS_2)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_FROM_CITY, $vform->get(ColissimoLabel::CONFIG_KEY_FROM_CITY)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_FROM_ZIPCODE, $vform->get(ColissimoLabel::CONFIG_KEY_FROM_ZIPCODE)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_FROM_COUNTRY, $vform->get(ColissimoLabel::CONFIG_KEY_FROM_COUNTRY)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_FROM_CONTACT_EMAIL, $vform->get(ColissimoLabel::CONFIG_KEY_FROM_CONTACT_EMAIL)->getData());
            ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_FROM_PHONE, $vform->get(ColissimoLabel::CONFIG_KEY_FROM_PHONE)->getData());
            if ($vform->get(ColissimoLabel::CONFIG_KEY_FROM_ADDRESS_2)->getData() === ' ') {
                ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_FROM_ADDRESS_2, null);
            }

            return $this->generateRedirect(
                URL::getInstance()->absoluteUrl('/admin/module/colissimolabel/configuration')
            );
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans("ColissimoLabel update config"),
                $e->getMessage(),
                $form,
                $e
            );

            return $this->renderConfigPageAction();
        }
    }
}