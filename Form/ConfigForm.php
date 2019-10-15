<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 15/10/2019
 * Time: 15:33
 */

namespace ColissimoLabel\Form;


use ColissimoLabel\ColissimoLabel;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class ConfigForm extends BaseForm
{
    protected function buildForm()
    {
        $contractNumber = ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER);
        $password = ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_PASSWORD);

        $this->formBuilder
            ->add(ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER, TextType::class,[
                'data' => $contractNumber,
                'required' => false,
                'label' => Translator::getInstance()->trans('Contract number', [], ColissimoLabel::DOMAIN_NAME),
                'label_attr' => [
                    'for' => ColissimoLabel::CONFIG_KEY_PASSWORD
                ]
            ])
            ->add(ColissimoLabel::CONFIG_KEY_PASSWORD, TextType::class,[
                'data' => $password,
                'required' => false,
                'label' => Translator::getInstance()->trans('Password', [], ColissimoLabel::DOMAIN_NAME),
                'label_attr' => [
                    'for' => ColissimoLabel::CONFIG_KEY_PASSWORD
                ]
            ]);
    }

    public function getName()
    {
        return 'colissimolabel-config_form';
    }

}