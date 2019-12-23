<?php

namespace ColissimoLabel\Request;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Request\Helper\Addressee;
use ColissimoLabel\Request\Helper\Letter;
use ColissimoLabel\Request\Helper\OutputFormat;
use ColissimoLabel\Request\Helper\Parcel;
use ColissimoLabel\Request\Helper\Sender;
use ColissimoLabel\Request\Helper\Service;
use ColissimoLabel\Request\Traits\MethodCreateAddressFromOrderAddress;
use ColissimoLabel\Request\Traits\MethodCreateAddressFromStore;
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

        $isPickup = null !== $pickupType ? $pickupType : $this->getProductCode($order);

        if (null === $productCode = $pickupType) {
            $productCode = $this->getProductCode($order, $signedDelivery);
        }

        $this->setLetter(new Letter(
            new Service(
                $productCode,
                (new \DateTime()),
                $order->getRef()
            ),
            new Sender(
                $this->createAddressFromStore()
            ),
            new Addressee(
                $this->createAddressFromOrderAddress(
                    $orderAddress,
                    $order->getCustomer()
                )
            ),
            new Parcel(
                $order->getWeight()
            )
        ));

        if (null !== $pickupCode) {
            $this->getLetter()->getParcel()->setPickupLocationId($pickupCode);
        }

        //$this->getLetter()->getAddressee()->setAddresseeParcelRef($order->getRef());
        $this->getLetter()->getSender()->setSenderParcelRef($order->getRef());

        $this->setOutputFormat(new OutputFormat());

        $this->getOutputFormat()->setOutputPrintingType(
            ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_DEFAULT_LABEL_FORMAT)
        );
    }

    protected function getProductCode(Order $order, $signedDelivery = false)
    {
        /** @var OrderAddress $deliveryAddress */
        $deliveryAddress = $order->getOrderAddressRelatedByDeliveryOrderAddressId();

        $code = $deliveryAddress->getCountry()->getIsocode();

        // france case
        if ($code == '250') {
            if ($signedDelivery) {
                return Service::PRODUCT_CODE_LIST[2];
            }
            return Service::PRODUCT_CODE_LIST[0];
        } elseif (in_array( // europe
            $code,
            [
                "040", "056", "100", "191", "196", "203", "208", "233", "246", "250", "276", "300", "348", "372", "380", "428", "440", "442", "470", "528", "616", "620", "642", "705", "724", "752", "826"
            ]
        )) {
            return Service::PRODUCT_CODE_LIST[0];
        } elseif (in_array( // europe
            $code,
            [
                "312", "254", "666", "474", "638", "175"
            ]
        )) { // outre mer
            return Service::PRODUCT_CODE_LIST[10];
        } else { // other
            return Service::PRODUCT_CODE_LIST[14];
        }
    }
}
