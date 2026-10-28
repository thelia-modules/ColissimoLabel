<?php

namespace ColissimoLabel\Service;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Enum\AuthorizedModuleEnum;
use ColissimoLabel\Model\ColissimoLabel as ColissimoLabelModel;
use ColissimoLabel\Model\ColissimoLabelQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Model\Country;
use Thelia\Model\CountryQuery;
use Thelia\Model\CurrencyQuery;
use Thelia\Model\ModuleQuery;
use Thelia\Model\Order;
use Thelia\Model\OrderAddress;
use Thelia\Model\OrderAddressQuery;
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

        if ($orders->isEmpty()) {
            return [];
        }

        /* The labels, addresses, countries and currencies of the whole list are loaded once, not once per order */
        $orderIds = [];
        $addressIds = [];
        foreach ($orders as $order) {
            $orderIds[] = $order->getId();
            $addressIds[] = $order->getDeliveryOrderAddressId();
        }

        $labels = [];
        foreach (ColissimoLabelQuery::create()->filterByOrderId($orderIds, Criteria::IN)->orderById()->find() as $label) {
            /* The first label of an order is the one shown, as before */
            $labels[$label->getOrderId()] ??= $label;
        }

        $addresses = [];
        $countryIds = [];
        foreach (OrderAddressQuery::create()->filterById($addressIds, Criteria::IN)->find() as $address) {
            $addresses[$address->getId()] = $address;
            $countryIds[$address->getCountryId()] = true;
        }

        $countries = [];
        foreach (CountryQuery::create()->filterById(array_keys($countryIds), Criteria::IN)->joinWithI18n($locale)->find() as $country) {
            $countries[$country->getId()] = $country;
        }

        $currencyCodes = CurrencyQuery::create()->find()->toKeyValue('Id', 'Code');

        $context = [
            'locale' => $locale,
            'default_signed' => (bool) ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_DEFAULT_SIGNED),
            'date_formatter' => new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT),
            'amount_formatter' => new \NumberFormatter($locale, \NumberFormatter::CURRENCY),
        ];

        $rows = [];
        foreach ($orders as $order) {
            $deliveryAddress = $addresses[$order->getDeliveryOrderAddressId()] ?? null;
            $rows[] = $this->buildRow(
                $order,
                $deliveryAddress,
                null !== $deliveryAddress ? ($countries[$deliveryAddress->getCountryId()] ?? null) : null,
                $labels[$order->getId()] ?? null,
                $currencyCodes[$order->getCurrencyId()] ?? 'EUR',
                $context
            );
        }

        return $rows;
    }

    /**
     * @param array{locale: string, default_signed: bool, date_formatter: \IntlDateFormatter, amount_formatter: \NumberFormatter} $context
     */
    private function buildRow(
        Order $order,
        ?OrderAddress $deliveryAddress,
        ?Country $country,
        ?ColissimoLabelModel $label,
        string $currencyCode,
        array $context
    ): array {
        $locale = $context['locale'];
        $countryTitle = '';
        $countryFlag = '';
        if (null !== $country) {
            $countryTitle = (string) $country->setLocale($locale)->getTitle();
            if ('' === $countryTitle) {
                $countryTitle = (string) $country->getIsoalpha2();
            }
            $countryFlag = $this->countryFlag((string) $country->getIsoalpha2());
        }

        $deliveryModule = $order->getModuleRelatedByDeliveryModuleId();
        $orderDate = $order->getCreatedAt();
        $totalTaxedAmount = $order->getTotalAmount();

        $row = [
            'id' => $order->getId(),
            'order_id' => $order->getId(),
            'ref' => $order->getRef(),
            'order_url' => URL::getInstance()->absoluteUrl('/admin/order/update/'.$order->getId(), ['tab' => 'bill']),
            'order_date' => $orderDate,
            'order_date_label' => $orderDate instanceof \DateTimeInterface ? (string) $context['date_formatter']->format($orderDate) : '',
            'city' => $deliveryAddress?->getCity() ?? '',
            'zipcode' => $deliveryAddress?->getZipcode() ?? '',
            'country' => $countryTitle,
            'country_flag' => $countryFlag,
            'total_taxed_amount' => $totalTaxedAmount,
            'amount_label' => (string) $context['amount_formatter']->formatCurrency((float) $totalTaxedAmount, $currencyCode),
            'module_code' => $deliveryModule?->getCode() ?? '',
            'module_title' => $deliveryModule?->setLocale($locale)->getTitle() ?: ($deliveryModule?->getCode() ?? ''),
            'can_be_not_signed' => ColissimoLabel::canCountryBeNotSigned((string) $country?->getIsocode()),
            'has_error' => false,
            'error_message' => null,
            'has_customs_invoice' => false,
            'customs_invoice_url' => null,
            'label_type' => null,
        ];

        if (null === $label) {
            return array_merge($row, [
                'weight' => $order->getWeight(),
                'signed' => $context['default_signed'],
                'tracking_number' => null,
                'has_label' => false,
                'label_url' => null,
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
        ]);
    }

    /**
     * Same emoji flag as the order list of the back office, built from the two regional indicator letters.
     */
    private function countryFlag(string $isoAlpha2): string
    {
        if (2 !== \strlen($isoAlpha2) || !ctype_alpha($isoAlpha2)) {
            return '';
        }

        $offset = 0x1F1E6 - \ord('A');
        $upper = strtoupper($isoAlpha2);

        return mb_chr(\ord($upper[0]) + $offset).mb_chr(\ord($upper[1]) + $offset);
    }
}
