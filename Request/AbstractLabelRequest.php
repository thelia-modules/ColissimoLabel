<?php

namespace ColissimoLabel\Request;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Request\Helper\Letter;
use ColissimoLabel\Request\Helper\OutputFormat;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
abstract class AbstractLabelRequest extends AbstractRequest
{
    /** @var OutputFormat|null */
    private $outputFormat;

    /** @var Letter */
    private $letter;

    /**
     * @return OutputFormat|null
     */
    public function getOutputFormat()
    {
        return $this->outputFormat;
    }

    /**
     * @param OutputFormat $outputFormat
     * @return self
     */
    protected function setOutputFormat(OutputFormat $outputFormat)
    {
        $this->outputFormat = $outputFormat;
        return $this;
    }

    /**
     * @return Letter
     */
    public function getLetter()
    {
        return $this->letter;
    }

    /**
     * @param Letter $letter
     * @return self
     */
    protected function setLetter(Letter $letter)
    {
        $this->letter = $letter;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function generateArrayRequest()
    {
        return array_merge_recursive(parent::generateArrayRequest(), [
            'outputFormat' => [
                'x' => $this->getOutputFormat()->getX(),
                'y' => $this->getOutputFormat()->getY(),
                'outputPrintingType' => $this->getOutputFormat()->getOutputPrintingType()
            ],
            'letter' => [
                'service' => [
                    'productCode' => $this->getLetter()->getService()->getProductCode(),
                    'depositDate' => $this->getLetter()->getService()->getDepositDate()->format('Y-m-d'),
                    'transportationAmount' => $this->getLetter()->getService()->getTransportationAmount(),
                    'totalAmount' => $this->getLetter()->getService()->getTransportationAmount(),
                    'orderNumber' => $this->getLetter()->getService()->getOrderNumber(),
                    'commercialName' => $this->getLetter()->getService()->getCommercialName(),
                    'returnTypeChoice' => $this->getLetter()->getService()->getReturnTypeChoice(),
                ],
                'parcel' => [
                    'weight' => $this->getLetter()->getParcel()->getWeight(),
                    'pickupLocationId' => $this->getLetter()->getParcel()->getPickupLocationId()
                ],
                'customsDeclarations' => [
                    'includeCustomsDeclarations' => $this->getLetter()->getCustomsDeclarations()->getIncludeCustomsDeclarations(),
                    'contents' => [
                        'article' => 'falseArticle',
                        'category' => [
                            'value' => $this->getLetter()->getCustomsDeclarations()->getCategory(),
                        ]
                    ]

                ],
                'sender' => [
                    'senderParcelRef' => $this->getLetter()->getSender()->getSenderParcelRef(),
                    'address' => [
                        'companyName' => $this->getLetter()->getSender()->getAddress()->getCompanyName(),
                        'lastName' => $this->getLetter()->getSender()->getAddress()->getLastName(),
                        'firstName' => $this->getLetter()->getSender()->getAddress()->getFirstName(),
                        'line0' => $this->getLetter()->getSender()->getAddress()->getLine0(),
                        'line1' => $this->getLetter()->getSender()->getAddress()->getLine1(),
                        'line2' => $this->getLetter()->getSender()->getAddress()->getLine2(),
                        'line3' => $this->getLetter()->getSender()->getAddress()->getLine3(),
                        'countryCode' => $this->getLetter()->getSender()->getAddress()->getCountryCode(),
                        'city' => $this->getLetter()->getSender()->getAddress()->getCity(),
                        'zipCode' => $this->getLetter()->getSender()->getAddress()->getZipCode(),
                        'phoneNumber' => $this->getLetter()->getSender()->getAddress()->getPhoneNumber(),
                        'mobileNumber' => $this->getLetter()->getSender()->getAddress()->getMobileNumber(),
                        'email'=> $this->getLetter()->getSender()->getAddress()->getEmail(),
                        'language' => $this->getLetter()->getSender()->getAddress()->getLanguage()
                    ]
                ],
                'addressee' => [
                    'addresseeParcelRef' => $this->getLetter()->getAddressee()->getAddresseeParcelRef(),
                    'address' => [
                        'companyName' => ColissimoLabel::removeAccents($this->getLetter()->getAddressee()->getAddress()->getCompanyName()),
                        'lastName' => ColissimoLabel::removeAccents($this->getLetter()->getAddressee()->getAddress()->getLastName()),
                        'firstName' => ColissimoLabel::removeAccents($this->getLetter()->getAddressee()->getAddress()->getFirstName()),
                        'line0' => ColissimoLabel::removeAccents($this->getLetter()->getAddressee()->getAddress()->getLine0()),
                        'line1' => ColissimoLabel::removeAccents($this->getLetter()->getAddressee()->getAddress()->getLine1()),
                        'line2' => ColissimoLabel::removeAccents($this->getLetter()->getAddressee()->getAddress()->getLine2()),
                        'line3' => ColissimoLabel::removeAccents($this->getLetter()->getAddressee()->getAddress()->getLine3()),
                        'countryCode' => $this->getLetter()->getAddressee()->getAddress()->getCountryCode(),
                        'city' => ColissimoLabel::removeAccents($this->getLetter()->getAddressee()->getAddress()->getCity()),
                        'zipCode' => $this->getLetter()->getAddressee()->getAddress()->getZipCode(),
                        'phoneNumber' => $this->getLetter()->getAddressee()->getAddress()->getPhoneNumber(),
                        'mobileNumber' => $this->getLetter()->getAddressee()->getAddress()->getMobileNumber(),
                        'email'=> $this->getLetter()->getAddressee()->getAddress()->getEmail(),
                        'language' => $this->getLetter()->getAddressee()->getAddress()->getLanguage()
                    ]
                ],
            ]
        ]);
    }
}
