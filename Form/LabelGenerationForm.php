<?php


namespace ColissimoLabel\Form;


use ColissimoLabel\ColissimoLabel;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
                        'nochange' => Translator::getInstance()->trans("Do not change", [], ColissimoLabel::DOMAIN_NAME),
                        'processing' => Translator::getInstance()->trans("Set orders status as processing", [], ColissimoLabel::DOMAIN_NAME),
                        'sent' => Translator::getInstance()->trans("Set orders status as sent", [], ColissimoLabel::DOMAIN_NAME)
                    ],
                    'required' => 'false',
                    'expanded' => true,
                    'multiple' => false,
                    'data'     => ColissimoLabel::getConfigValue("new_status", 'nochange')
                ]
            )
            ->add(
                'order_id',
                CollectionType::class,
                [
                    'required' => 'false',
                    'type' => 'integer',
                    'allow_add' => true,
                    'allow_delete' => true,
                ]
            )
            ->add(
                'weight',
                CollectionType::class,
                [
                    'required' => 'false',
                    'type' => 'number',
                    'allow_add' => true,
                    'allow_delete' => true,
                ]
            )
            ->add(
                'signed',
                CollectionType::class,
                [
                    'required' => 'false',
                    'type' => 'checkbox',
                    'label' => 'Signature',
                    'allow_add' => true,
                    'allow_delete' => true,
                ]
            )
        ;
    }

    /**
     * @return string the name of you form. This name must be unique
     */
    public function getName()
    {
        return "colissimolabel_export_form";
    }

}