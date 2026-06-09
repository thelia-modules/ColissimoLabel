<?php

namespace ColissimoLabel\Service;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Enum\AuthorizedModuleEnum;
use ColissimoLabel\Model\ColissimoLabelQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Model\ModuleQuery;
use Thelia\Model\Order;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;
use Thelia\Tools\URL;

/**
 * Reproduces the colissimolabel.orders-not-sent + colissimolabel.label-info loops for the labels list page.
 */
class OrdersNotSentProvider
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRows(string $locale): array
    {
        $statuses = OrderStatusQuery::create()
            ->filterByCode([OrderStatus::CODE_PAID, OrderStatus::CODE_PROCESSING], Criteria::IN)
            ->find()
            ->toArray('code');

        $moduleIds = [];
        foreach ([AuthorizedModuleEnum::ColissimoHomeDelivery->value, AuthorizedModuleEnum::ColissimoPickupPoint->value] as $code) {
            if ($module = ModuleQuery::create()->findOneByCode($code)) {
                $moduleIds[] = $module->getId();
            }
        }

        if ([] === $moduleIds || !isset($statuses[OrderStatus::CODE_PAID], $statuses[OrderStatus::CODE_PROCESSING])) {
            return [];
        }

        $orders = OrderQuery::create()
            ->filterByDeliveryModuleId($moduleIds, Criteria::IN)
            ->filterByStatusId(
                [$statuses[OrderStatus::CODE_PAID]['Id'], $statuses[OrderStatus::CODE_PROCESSING]['Id']],
                Criteria::IN
            )
            ->find();

        $rows = [];

        foreach ($orders as $order) {
            $rows[] = $this->buildRow($order, $locale);
        }

        return $rows;
    }

    private function buildRow(Order $order, string $locale): array
    {
        $deliveryAddress = $order->getOrderAddressRelatedByDeliveryOrderAddressId();
        $countryTitle = '';
        if (null !== $deliveryAddress && null !== $country = $deliveryAddress->getCountry()) {
            $countryTitle = $country->setLocale($locale)->getTitle();
        }

        $label = ColissimoLabelQuery::create()
            ->filterByOrderId($order->getId())
            ->orderById()
            ->findOne();

        $defaultSigned = (bool) ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_DEFAULT_SIGNED);

        $row = [
            'order_id' => $order->getId(),
            'ref' => $order->getRef(),
            'order_date' => $order->getCreatedAt(),
            'city' => $deliveryAddress?->getCity() ?? '',
            'zipcode' => $deliveryAddress?->getZipcode() ?? '',
            'country' => $countryTitle,
            'total_taxed_amount' => $order->getTotalAmount(),
            'module_code' => $order->getModuleRelatedByDeliveryModuleId()?->getCode() ?? '',
            'can_be_not_signed' => ColissimoLabel::canOrderBeNotSigned($order),
            'has_error' => false,
            'error_message' => null,
            'has_customs_invoice' => false,
            'customs_invoice_url' => null,
            'label_type' => null,
        ];

        if (null === $label) {
            return array_merge($row, [
                'weight' => $order->getWeight(),
                'signed' => $defaultSigned,
                'tracking_number' => null,
                'has_label' => false,
                'label_url' => null,
                'clear_label_url' => null,
            ]);
        }

        return array_merge($row, [
            'weight' => empty($label->getWeight()) ? $order->getWeight() : $label->getWeight(),
            'signed' => (bool) $label->getSigned(),
            'tracking_number' => $label->getTrackingNumber(),
            'has_label' => !empty($label->getLabelType()),
            'label_type' => $label->getLabelType(),
            'has_error' => (bool) $label->getError(),
            'error_message' => $label->getErrorMessage(),
            'has_customs_invoice' => (bool) $label->getWithCustomsInvoice(),
            'label_url' => URL::getInstance()->absoluteUrl('/admin/module/ColissimoLabel/label/'.$label->getTrackingNumber().'?download=1'),
            'customs_invoice_url' => URL::getInstance()->absoluteUrl('/admin/module/ColissimoLabel/customs-invoice/'.$label->getOrderId()),
            'clear_label_url' => URL::getInstance()->absoluteUrl('/admin/module/ColissimoLabel/label/delete/'.$label->getTrackingNumber().'?order='.$label->getOrderId()),
        ]);
    }
}
