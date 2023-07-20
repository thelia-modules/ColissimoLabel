<?php

namespace ColissimoLabel\EventListeners;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Service\LabelService;
use Picking\Event\GenerateLabelEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Controller\Admin\BaseAdminController;

/**
 * Class GenerateLabelListener.
 *
 * This class is used only when you have the Picking module
 */
class GenerateLabelListener extends BaseAdminController implements EventSubscriberInterface
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected LabelService $labelService
    ) {}

    public function generateLabel(GenerateLabelEvent $event)
    {
        $deliveryModuleCode = $event->getOrder()->getModuleRelatedByDeliveryModuleId()->getCode();
        if ('ColissimoHomeDelivery' === $deliveryModuleCode || 'ColissimoPickupPoint' === $deliveryModuleCode) {
            $data = [];
            $orderId = $event->getOrder()->getId();
            $data['new_status'] = ColissimoLabel::getConfigValue('new_status', 'nochange');
            $data['order_id'][$orderId] = $orderId;
            $data['weight'][$orderId] = $event->getWeight();
            $data['signed'][$orderId] = $event->isSignedDelivery();
            $event->setResponse($this->labelService->generateLabel($data, true, $this->eventDispatcher));
        }
    }

    public static function getSubscribedEvents(): array
    {
        $events = [];
        if (class_exists('Picking\Event\GenerateLabelEvent')) {
            $events[GenerateLabelEvent::PICKING_GENERATE_LABEL] = ['generateLabel', 65];
        }

        return $events;
    }
}
