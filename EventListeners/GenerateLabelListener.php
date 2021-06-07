<?php

namespace ColissimoLabel\EventListeners;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Service\LabelService;
use Picking\Event\GenerateLabelEvent;
use Picking\Picking;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Controller\Admin\BaseAdminController;

/**
 * Class GenerateLabelListener.
 *
 * This class is used only when you have the Picking module
 */
class GenerateLabelListener extends BaseAdminController implements EventSubscriberInterface
{
    protected $labelService;

    public function __construct(LabelService $labelService)
    {
        $this->labelService = $labelService;
    }

    public function generateLabel(GenerateLabelEvent $event)
    {
        $deliveryModuleCode = $event->getOrder()->getModuleRelatedByDeliveryModuleId()->getCode();
        if ('ColissimoHomeDelivery' === $deliveryModuleCode || 'ColissimoPickupPoint' === $deliveryModuleCode || 'SoColissimo' === $deliveryModuleCode) {
            $data = [];
            $orderId = $event->getOrder()->getId();
            $data['new_status'] = ColissimoLabel::getConfigValue('new_status', 'nochange');
            $data['order_id'][$orderId] = $orderId;
            $data['weight'][$orderId] = $event->getWeight();
            $data['signed'][$orderId] = $event->isSignedDelivery();
            $event->setResponse($this->labelService->generateLabel($data, true));
        }
    }

    public static function getSubscribedEvents()
    {
        $events = [];
        if (class_exists('Picking\Event\GenerateLabelEvent')) {
            $events[GenerateLabelEvent::PICKING_GENERATE_LABEL] = ['generateLabel', 65];
        }

        return $events;
    }
}
