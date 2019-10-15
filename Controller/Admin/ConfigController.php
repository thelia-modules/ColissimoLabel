<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 15/10/2019
 * Time: 16:11
 */

namespace ColissimoLabel\Controller\Admin;


use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Form\ConfigForm;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Tools\URL;

class ConfigController extends BaseAdminController
{
    public function updateConfigAction()
    {
        $form = new ConfigForm($this->getRequest());

        $vform = $this->validateForm($form);

        ColissimoLabel::setConfigValue(
            ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER,
            $vform->get(ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER)->getData()
        );

        ColissimoLabel::setConfigValue(
            ColissimoLabel::CONFIG_KEY_PASSWORD,
            $vform->get(ColissimoLabel::CONFIG_KEY_PASSWORD)->getData()
        );

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('admin/module/ColissimoLabel'));
    }
}