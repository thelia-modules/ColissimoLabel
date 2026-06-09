<?php

namespace ColissimoLabel\Hook\Back;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Enum\AuthorizedModuleEnum;
use ColissimoLabel\Form\LabelGenerationForm;
use ColissimoLabel\Model\ColissimoLabelQuery;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Form\TheliaFormFactory;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\Parser\ParserResolver;
use Thelia\Model\OrderQuery;
use Thelia\Tools\URL;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class OrderEditHook extends BaseHook
{
    public function __construct(
        private readonly TheliaFormFactory $formFactory,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    public static function getSubscribedHooks(): array
    {
        return [
            'order.edit-js' => [
                ['type' => 'back', 'method' => 'onOrderEditJs'],
            ],
        ];
    }

    public function onOrderEditJs(HookRenderEvent $event): void
    {
        $orderId = (int) $event->getArgument('order_id');

        $order = OrderQuery::create()->findPk($orderId);

        if (null === $order) {
            return;
        }

        /* Only display the Colissimo modal for orders shipped with an authorized Colissimo module. */
        $deliveryModuleCode = $order->getModuleRelatedByDeliveryModuleId()?->getCode();
        $enabled = \in_array($deliveryModuleCode, [
            AuthorizedModuleEnum::ColissimoHomeDelivery->value,
            AuthorizedModuleEnum::ColissimoPickupPoint->value,
        ], true);

        if (!$enabled) {
            return;
        }

        $form = $this->formFactory->createForm(LabelGenerationForm::getName());

        $event->add(
            $this->render('colissimo-label/hook/order-edit-js.html.twig', [
                'order_id' => $orderId,
                'form' => $form->createView()->getView(),
                'label_info' => $this->getLabelInfo($order),
                'labels' => $this->getExistingLabels($orderId),
                'ajax_get_labels_url' => URL::getInstance()->absoluteUrl('/admin/module/ColissimoLabel/order/'.$orderId.'/ajax-get-labels'),
            ])
        );
    }

    /**
     * Reproduces the colissimolabel.label-info loop for a single order: the default row when no label exists yet.
     */
    private function getLabelInfo(\Thelia\Model\Order $order): array
    {
        $existing = ColissimoLabelQuery::create()
            ->filterByOrderId($order->getId())
            ->orderById()
            ->findOne();

        if (null !== $existing) {
            return [
                'order_id' => $existing->getOrderId(),
                'weight' => empty($existing->getWeight()) ? $order->getWeight() : $existing->getWeight(),
                'signed' => (bool) $existing->getSigned(),
                'tracking_number' => $existing->getTrackingNumber(),
                'can_be_not_signed' => ColissimoLabel::canOrderBeNotSigned($order),
            ];
        }

        return [
            'order_id' => $order->getId(),
            'weight' => $order->getWeight(),
            'signed' => (bool) ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_DEFAULT_SIGNED),
            'tracking_number' => null,
            'can_be_not_signed' => ColissimoLabel::canOrderBeNotSigned($order),
        ];
    }

    /**
     * Reproduces the colissimolabel.label-info loop used in label-list: every label having a tracking number.
     */
    private function getExistingLabels(int $orderId): array
    {
        $labels = ColissimoLabelQuery::create()
            ->filterByOrderId($orderId)
            ->find();

        $rows = [];

        foreach ($labels as $label) {
            if (empty($label->getTrackingNumber())) {
                continue;
            }

            $rows[] = [
                'tracking_number' => $label->getTrackingNumber(),
                'weight' => empty($label->getWeight()) ? $label->getOrder()->getWeight() : $label->getWeight(),
                'created_at' => $label->getCreatedAt(),
                'order_id' => $label->getOrderId(),
                'has_customs_invoice' => (bool) $label->getWithCustomsInvoice(),
                'customs_invoice_url' => URL::getInstance()->absoluteUrl('/admin/module/ColissimoLabel/customs-invoice/'.$label->getOrderId()),
            ];
        }

        return $rows;
    }
}
