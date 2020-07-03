<?php


namespace ColissimoLabel\Form;


use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Request\Helper\OutputFormat;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class ConfigureColissimoLabel extends BaseForm
{
    protected function buildForm()
    {
        $translator = Translator::getInstance();
        $this->formBuilder
            ->add(
                ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER,
                TextType::class,
                [
                    'constraints' => [new NotBlank()],
                    'data'        => ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER),
                    'label'       => $translator->trans('Account number', [], ColissimoLabel::DOMAIN_NAME),
                    'label_attr'  => ['for' => ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER]
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_PASSWORD,
                TextType::class,
                [
                    'constraints' => [new NotBlank()],
                    'data'        => ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_PASSWORD),
                    'label'       => $translator->trans('Password', [], ColissimoLabel::DOMAIN_NAME),
                    'label_attr'  => ['for' => ColissimoLabel::CONFIG_KEY_PASSWORD]
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_DEFAULT_SIGNED,
                CheckboxType::class,
                [
                    'required' => false,
                    'data'        => (bool)ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_DEFAULT_SIGNED),
                    'value'        => (bool)ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_DEFAULT_SIGNED),
                    'label'       => $translator->trans('Default signed', [], ColissimoLabel::DOMAIN_NAME),
                    'label_attr'  => ['for' => ColissimoLabel::CONFIG_KEY_DEFAULT_SIGNED]
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_GENERATE_BORDEREAU,
                CheckboxType::class,
                [
                    'required' => false,
                    'data'        => (bool)ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_GENERATE_BORDEREAU),
                    'value'        => (bool)ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_GENERATE_BORDEREAU),
                    'label'       => $translator->trans('Generate bordereau with labels', [], ColissimoLabel::DOMAIN_NAME),
                    'label_attr'  => ['for' => ColissimoLabel::CONFIG_KEY_GENERATE_BORDEREAU]
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_DEFAULT_LABEL_FORMAT,
                ChoiceType::class,
                [
                    'constraints'   => [new NotBlank()],
                    'data'          => ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_DEFAULT_LABEL_FORMAT),
                    'choices'       => OutputFormat::OUTPUT_PRINTING_TYPE,
                    'label'         => $translator->trans('Label format', [], ColissimoLabel::DOMAIN_NAME),
                    'label_attr'    => ['for' => ColissimoLabel::CONFIG_KEY_DEFAULT_LABEL_FORMAT]
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_LAST_BORDEREAU_DATE,
                TextType::class,
                [
                    'constraints' => [new NotBlank()],
                    'data'        => ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_LAST_BORDEREAU_DATE),
                    'label'       => $translator->trans('Last bordereau date', [], ColissimoLabel::DOMAIN_NAME),
                    'label_attr'  => ['for' => ColissimoLabel::CONFIG_KEY_LAST_BORDEREAU_DATE]
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_GET_INVOICES,
                CheckboxType::class,
                [
                    'required'  => false,
                    'data'        => (bool)ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_GET_INVOICES),
                    'value'        => (bool)ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_GET_INVOICES),
                    'label'       => $translator->trans('Get the invoices', [], ColissimoLabel::DOMAIN_NAME),
                    'label_attr'  => ['for' => ColissimoLabel::CONFIG_KEY_GET_INVOICES]
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_GET_CUSTOMS_INVOICES,
                CheckboxType::class,
                [
                    'required' => false,
                    'data'        => (bool)ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_GET_CUSTOMS_INVOICES),
                    'value'        => (bool)ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_GET_CUSTOMS_INVOICES),
                    'label'       => $translator->trans('Get the customs invoices (Need a product HS code to work)', [], ColissimoLabel::DOMAIN_NAME),
                    'label_attr'  => ['for' => ColissimoLabel::CONFIG_KEY_GET_CUSTOMS_INVOICES]
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_CUSTOMS_PRODUCT_HSCODE,
                TextType::class,
                [
                    'required'      => false,
                    'data'          => ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_CUSTOMS_PRODUCT_HSCODE),
                    'label'         => $translator->trans('Product HS Code for Customs invoices', [], ColissimoLabel::DOMAIN_NAME),
                    'label_attr'    => ['for' => ColissimoLabel::CONFIG_KEY_CUSTOMS_PRODUCT_HSCODE]
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_ENDPOINT,
                TextType::class,
                [
                    'required'      => true,
                    'data'          => ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_ENDPOINT),
                    'label'         => $translator->trans('Endpoint', [], ColissimoLabel::DOMAIN_NAME),
                    'label_attr'    => ['for' => ColissimoLabel::CONFIG_KEY_ENDPOINT]
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_FROM_NAME,
                'text',
                [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'data'          => ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_NAME),
                    'label'       => $this->translator->trans('Nom de société', [], ColissimoLabel::DOMAIN_NAME),
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_FROM_ADDRESS_1,
                'text',
                [
                    'constraints' => [ new NotBlank() ],
                    'data'          => ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_ADDRESS_1),
                    'label' => $this->translator->trans('Adresse', [], ColissimoLabel::DOMAIN_NAME)
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_FROM_ADDRESS_2,
                'text',
                [
                    'constraints' => [ ],
                    'required' => false,
                    'data'     => $this->emptyAddress(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_ADDRESS_1)),
                    'label'  => $this->translator->trans('Adresse (suite)', [], ColissimoLabel::DOMAIN_NAME)
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_FROM_CITY,
                'text',
                [
                    'constraints' => [ new NotBlank() ],
                    'data'          => ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_CITY),
                    'label'  => $this->translator->trans('Ville', [], ColissimoLabel::DOMAIN_NAME)
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_FROM_ZIPCODE,
                'text',
                [
                    'constraints' => [ new NotBlank() ],
                    'data'          => ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_ZIPCODE),
                    'label'  => $this->translator->trans('Code postal', [], ColissimoLabel::DOMAIN_NAME)
                ]
            )
            ->add(
                ColissimoLabel::CONFIG_KEY_FROM_COUNTRY,
                'text',
                [
                    'constraints' => [ new NotBlank() ],
                    'data'          => ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_COUNTRY),
                    'label'  => $this->translator->trans('Pays', [], ColissimoLabel::DOMAIN_NAME)
                ]
            )->add(
                ColissimoLabel::CONFIG_KEY_FROM_CONTACT_EMAIL,
                'email',
                [
                    'constraints' => [ new NotBlank() ],
                    'data'          => ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_CONTACT_EMAIL),
                    'label'  => $this->translator->trans('Adresse e-mail de contact pour les expéditions', [], ColissimoLabel::DOMAIN_NAME)
                ]
            )->add(
                ColissimoLabel::CONFIG_KEY_FROM_PHONE,
                'text',
                [
                    'constraints' => [ new NotBlank() ],
                    'data'          => ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_FROM_PHONE),
                    'label'  => $this->translator->trans('Téléphone', [], ColissimoLabel::DOMAIN_NAME)
                ]
            )
        ;
    }

    protected function emptyAddress($value = null) {
        if (!$value) {
            return ' ';
        }
        return $value;
    }

    /**
     * @return string the name of you form. This name must be unique
     */
    public function getName()
    {
        return 'configure_colissimolabel';
    }
}