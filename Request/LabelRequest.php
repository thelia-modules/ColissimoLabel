<?php

namespace ColissimoLabel\Request;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Request\Helper\Addressee;
use ColissimoLabel\Request\Helper\Article;
use ColissimoLabel\Request\Helper\CustomsDeclarations;
use ColissimoLabel\Request\Helper\Letter;
use ColissimoLabel\Request\Helper\OutputFormat;
use ColissimoLabel\Request\Helper\Parcel;
use ColissimoLabel\Request\Helper\Sender;
use ColissimoLabel\Request\Helper\Service;
use ColissimoLabel\Request\Traits\MethodCreateAddressFromOrderAddress;
use ColissimoLabel\Request\Traits\MethodCreateAddressFromStore;
use DateTime;
use Thelia\Model\CurrencyQuery;
use Thelia\Model\Order;
use Thelia\Model\OrderAddress;
use Thelia\Model\OrderAddressQuery;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class LabelRequest extends AbstractLabelRequest
{
    use MethodCreateAddressFromStore;
    use MethodCreateAddressFromOrderAddress;

    public function __construct(Order $order, $pickupCode = null, $pickupType = null, $signedDelivery = false)
    {
        $orderAddress = OrderAddressQuery::create()->findOneById($order->getDeliveryOrderAddressId());

        /**
         * If a pickup type was given (relay point delivery), we set the delivery code $productCode as this.
         * Otherwise, we check in getProductCode which delivery type is necessary given the delivery country and whether this
         * is a signed delivery or not, and set $productCode as what ie returns
         */
        if (null === $productCode = $pickupType) {
            $productCode = $this->getProductCode($order, $signedDelivery);
        }

        $articles = [];
        foreach ($order->getOrderProducts() as $orderProduct) {
            $productPrice = $orderProduct->getWasInPromo() ? $orderProduct->getPromoPrice() : $orderProduct->getPrice();
            $articles[] = new Article(
                $orderProduct->getChapo(),
                $orderProduct->getQuantity(),
                $orderProduct->getWeight(),
                $productPrice,
                ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_CUSTOMS_PRODUCT_HSCODE),
                CurrencyQuery::create()->findOneById($order->getCurrencyId())->getCode()
            );
        }

        $this->setLetter(new Letter(
            /** We set the general delivery informations */
            new Service(
                $productCode,
                new DateTime(),
                $order->getRef(),
                $order->getPostage(),
                3
            ),
            /** We set the sender address */
            new Sender(
                $this->createAddressFromStore()
            ),
            /** We set the receiver address */
            new Addressee(
                $this->createAddressFromOrderAddress(
                    $orderAddress,
                    $order->getCustomer()
                )
            ),
            new Parcel(
                $order->getWeight()
            ),
            new CustomsDeclarations(
                (bool)ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_GET_CUSTOMS_INVOICES),
                3,
                $articles
            )
        ));

        /** If this is a pickup/relay point delivery, we set the pickup location ID */
        if (null !== $pickupCode) {
            $this->getLetter()->getParcel()->setPickupLocationId($pickupCode);
        }

        $this->getLetter()->getAddressee()->setAddresseeParcelRef($order->getRef());
        $this->getLetter()->getSender()->setSenderParcelRef($order->getRef());

        /** We initialize the label format */
        $this->setOutputFormat(new OutputFormat());

        /** We set the label format from the one indicated in the module config table */
        $this->getOutputFormat()->setOutputPrintingType(
            OutputFormat::OUTPUT_PRINTING_TYPE[ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_DEFAULT_LABEL_FORMAT)]
        );
    }

    /**
     * Return a domicile delivery code given the order country and whether this is a signed delivery or not
     *
     * @param Order $order
     * @param bool $signedDelivery
     * @return mixed
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function getProductCode(Order $order, $signedDelivery = false)
    {
        /** @var OrderAddress $deliveryAddress */
        $deliveryAddress = $order->getOrderAddressRelatedByDeliveryOrderAddressId();

        $code = $deliveryAddress->getCountry()->getIsocode();

        /** France Case */
        if ($code == '250') {
            if ($signedDelivery) {
                return Service::PRODUCT_CODE_LIST[2];
            }
            return Service::PRODUCT_CODE_LIST[0];
        }

        /** Europe Case */
        if (in_array($code, Service::EUROPE_ISOCODES, false)) {
            if ($signedDelivery) {
                return Service::PRODUCT_CODE_LIST[2];
            }
            return Service::PRODUCT_CODE_LIST[0];
        }

        /** DOM TOM case */
        if (in_array($code, Service::DOMTOM_ISOCODES, false)) {
            if ($signedDelivery) {
                return Service::PRODUCT_CODE_LIST[11];
            }
            return Service::PRODUCT_CODE_LIST[10];
        }

        /** Other cases */
        return Service::PRODUCT_CODE_LIST[14];
    }
}
