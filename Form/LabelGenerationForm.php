<?php

namespace ColissimoLabel\Form;

use ColissimoLabel\ColissimoLabel;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class LabelGenerationForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                'new_status',
                ChoiceType::class, [
                    'label' => Translator::getInstance()->trans('Order status after export'),
                    'choices' => [
                        Translator::getInstance()->trans('Do not change', [], ColissimoLabel::DOMAIN_NAME) => 'nochange',
                        Translator::getInstance()->trans('Set orders status as processing', [], ColissimoLabel::DOMAIN_NAME) => 'processing',
                        Translator::getInstance()->trans('Set orders status as sent', [], ColissimoLabel::DOMAIN_NAME) => 'sent',
                    ],
                    'required' => 'false',
                    'expanded' => true,
                    'multiple' => false,
                    'data' => ColissimoLabel::getConfigValue('new_status', 'nochange'),
                ]
            )
            ->add(
                'order_id',
                CollectionType::class,
                [
                    'required' => 'false',
                    'entry_type' => IntegerType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                ]
            )
            ->add(
                'weight',
                CollectionType::class,
                [
                    'required' => 'false',
                    'entry_type' => NumberType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                ]
            )
            ->add(
                'signed',
                CollectionType::class,
                [
                    'required' => 'false',
                    'entry_type' => CheckboxType::class,
                    'label' => 'Signature',
                    'allow_add' => true,
                    'allow_delete' => true,
                ]
            )
        ;
    }
}
