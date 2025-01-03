<?php

namespace ColissimoLabel\EventListeners;


use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Service\LabelService;
use Picking\Event\GenerateLabelEvent;
use Picking\Picking;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;

/**
 * Class GenerateLabelListener
 *
 * This class is used only when you have the Picking module
 *
 * @package ColissimoLabel\EventListeners
 */
class GenerateLabelListener extends BaseAdminController implements EventSubscriberInterface
{
    public function __construct(
        private readonly LabelService $labelService,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function generateLabel(GenerateLabelEvent $event)
    {
        $deliveryModuleCode = $event->getOrder()->getModuleRelatedByDeliveryModuleId()?->getCode();
        if ($deliveryModuleCode === "ColissimoHomeDelivery" || $deliveryModuleCode === "ColissimoPickupPoint"|| $deliveryModuleCode === "SoColissimo") {
            $data = [];
            $orderId = $event->getOrder()->getId();
            $data['new_status'] = ColissimoLabel::getConfigValue("new_status", 'nochange');
            $data['order_id'][$orderId] = $orderId;
            $data['weight'][$orderId] = $event->getWeight();
            $data['signed'][$orderId] = $event->isSignedDelivery();
            $event->setResponse(new JsonResponse($this->labelService->generateLabel($data, $this->eventDispatcher)[0]));
        }
    }

    public static function getSubscribedEvents()
    {
        $events = [];
        if (class_exists('Picking\Event\GenerateLabelEvent')){
            $events[GenerateLabelEvent::PICKING_GENERATE_LABEL] = ['generateLabel', 65];
        }
        return $events;
    }
}