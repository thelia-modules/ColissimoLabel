<?php

namespace ColissimoLabel\Service;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Event\ColissimoLabelEvents;
use ColissimoLabel\Event\LabelRequestEvent;
use ColissimoLabel\Model\ColissimoLabelQuery;
use ColissimoLabel\Request\Helper\LabelRequestAPIConfiguration;
use ColissimoLabel\Request\LabelRequest;
use ColissimoPickupPoint\Model\OrderAddressColissimoPickupPointQuery;
use SoColissimo\Model\OrderAddressSocolissimoQuery as OrderAddressSoColissimoPickupPointQuery;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatusQuery;
use Thelia\Tools\URL;

class LabelService
{
    protected $dispatcher;

    /**
     * UpdateDeliveryAddressListener constructor.
     */
    public function __construct(EventDispatcherInterface $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;
    }

    public function generateLabel($data, $isEditPage, EventDispatcherInterface $dispatcher)
    {
        /** Check if status needs to be changed after processing */
        $newStatus = OrderStatusQuery::create()->findOneByCode($data['new_status']);

        $weightArray = $data['weight'];
        $signedArray = $data['signed'];

        foreach ($data['order_id'] as $orderId) {
            if (null !== $order = OrderQuery::create()->findOneById($orderId)) {
                /* DO NOT use strict comparison here */
                if (!isset($weightArray[$orderId]) || 0 == (float) $weightArray[$orderId]) {
                    $weight = $order->getWeight();
                } else {
                    $weight = (float) $weightArray[$orderId];
                }

                if (null === $weight) {
                    throw new \Exception('Please enter a weight for every selected order');
                }

                /** Check if the 'signed' checkbox was checked for this particular order */
                $signedDelivery = false;
                if (array_key_exists($orderId, $signedArray)) {
                    $signedDelivery = $signedArray[$orderId];
                }

                $APIConfiguration = new LabelRequestAPIConfiguration();
                $APIConfiguration->setContractNumber(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER));
                $APIConfiguration->setPassword(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_PASSWORD));

                /* Check if delivery is a relay point through SoColissimo. Use relay point address if it is */
                if (ColissimoLabel::AUTHORIZED_MODULES[1] === $order->getModuleRelatedByDeliveryModuleId()->getCode()) {
                    if (null !== $AddressColissimoPickupPoint = OrderAddressSoColissimoPickupPointQuery::create()
                            ->findOneById($order->getDeliveryOrderAddressId())) {
                        /* If the delivery is through a relay point, we create a new LabelRequest with the relay point and order infos */
                        if ($AddressColissimoPickupPoint) {
                            $colissimoRequest = new LabelRequest(
                                $order,
                                '0' == $AddressColissimoPickupPoint->getCode() ? null : $AddressColissimoPickupPoint->getCode(),
                                $AddressColissimoPickupPoint->getType()
                            );

                            $colissimoRequest->getLetter()->getService()->setCommercialName(
                                $colissimoRequest->getLetter()->getSender()->getAddress()->getCompanyName()
                            );
                        }
                    }
                }

                /* Same thing with ColissimoPickupPoint */
                if (ColissimoLabel::AUTHORIZED_MODULES[3] === $order->getModuleRelatedByDeliveryModuleId()->getCode()) {
                    if (null !== $AddressColissimoPickupPoint = OrderAddressColissimoPickupPointQuery::create()
                            ->findOneById($order->getDeliveryOrderAddressId())) {
                        /* If the delivery is through a relay point, we create a new LabelRequest with the relay point and order infos */
                        if ($AddressColissimoPickupPoint) {
                            $colissimoRequest = new LabelRequest(
                                $order,
                                '0' == $AddressColissimoPickupPoint->getCode() ? null : $AddressColissimoPickupPoint->getCode(),
                                $AddressColissimoPickupPoint->getType()
                            );

                            $colissimoRequest->getLetter()->getService()->setCommercialName(
                                $colissimoRequest->getLetter()->getSender()->getAddress()->getCompanyName()
                            );
                        }
                    }
                }

                /* If this is a domicile delivery, we only use the order information to create a Labelrequest, not the relay point */
                if (!isset($colissimoRequest)) {
                    $colissimoRequest = new LabelRequest($order, null, null, $signedDelivery);
                }

                /* We set the weight as the one indicated from the form */
                if (null !== $weight) {
                    $colissimoRequest->getLetter()->getParcel()->setWeight($weight);
                }

                /* We set whether the delivery is a signed one or not thanks to the 'signed' checkbox in the form */
                if (null !== $signedDelivery) {
                    $colissimoRequest->getLetter()->getParcel()->setSignedDelivery($signedDelivery);
                }

                $service = new SOAPService();

                $dispatcher->dispatch(
                    new LabelRequestEvent($colissimoRequest),
                    ColissimoLabelEvents::LABEL_REQUEST,
                );

                $response = $service->callAPI($APIConfiguration, $colissimoRequest);

                /* Handling what happens if the response from Colissimo is valid */
                if ($response->isValid()) {
                    $fileSystem = new Filesystem();

                    /* We dump / save the label on the server */
                    $fileSystem->dumpFile(
                        $labelName = ColissimoLabel::getLabelPath($order->getRef(), ColissimoLabel::getFileExtension()),
                        $response->getFile()
                    );

                    $files[] = $labelName;
                    $hasCustomsFile = 0;

                    /* Dump the CN23 customs file if there is one */
                    if ($response->hasFileCN23()) {
                        $fileSystem->dumpFile(
                            $customsFileName = ColissimoLabel::getLabelCN23Path($order->getRef().'CN23', 'pdf'),
                            $response->getFileCN23()
                        );
                        $files[] = $customsFileName;
                        $hasCustomsFile = 1;
                    }

                    /**
                     * Checking if an entry with an error already exists in the table for this order label, creates one otherwise
                     * This allows to modify only entry with errors, while creating new ones if none with error were found.
                     */
                    $colissimoLabelModel = ColissimoLabelQuery::create()
                        ->filterByOrder($order)
                        ->filterByError(1)
                        ->findOneOrCreate()
                    ;

                    /* Saving the label info in the table */
                    $colissimoLabelModel
                        ->setOrderId($order->getId())
                        ->setOrderRef($order->getRef())
                        ->setError(0)
                        ->setErrorMessage('')
                        ->setWeight($colissimoRequest->getLetter()->getParcel()->getWeight())
                        ->setTrackingNumber($response->getParcelNumber())
                        ->setSigned($signedDelivery)
                        ->setLabelType(ColissimoLabel::getFileExtension())
                        ->setWithCustomsInvoice($hasCustomsFile)
                    ;
                    $colissimoLabelModel->save();

                    $parcelNumbers[] = $response->getParcelNumber();

                    $order->setDeliveryRef($response->getParcelNumber());
                    $order->save();

                    /* Change the order status if it was requested by the user */
                    if ($newStatus) {
                        $newStatusId = $newStatus->getId();

                        if ((int) $order->getOrderStatus()->getId() !== $newStatusId) {
                            $order->setOrderStatus($newStatus);
                            $dispatcher->dispatch(
                                (new OrderEvent($order))->setStatus($newStatusId),
                                TheliaEvents::ORDER_UPDATE_STATUS
                            );
                        }
                    }

                    /* Return JSON response when the form is called from order edit page */
                    if ($isEditPage) {
                        return new JsonResponse([
                            'id' => $colissimoLabelModel->getId(),
                            'url' => URL::getInstance()->absoluteUrl('/admin/module/colissimolabel/label/'.$response->getParcelNumber()),
                            'number' => $response->getParcelNumber(),
                            'order' => [
                                'id' => $order->getId(),
                                'status' => [
                                    'id' => $order->getOrderStatus()->getId(),
                                ],
                            ],
                        ]);
                    }
                } else {
                    /** Handling errors when the response is invalid */
                    $colissimoLabelError = ColissimoLabelQuery::create()
                        ->filterByOrder($order)
                        ->filterByError(1)
                        ->findOneOrCreate()
                    ;

                    $colissimoLabelError
                        ->setError(1)
                        ->setErrorMessage($response->getError(true)[0])
                        ->setSigned($signedDelivery)
                        ->setWeight($colissimoRequest->getLetter()->getParcel()->getWeight())
                        ->save()
                    ;

                    /* Return JSON response when the form is called from the order edit page */
                    if ($isEditPage) {
                        return new JsonResponse([
                            'error' => $response->getError(),
                        ]);
                    }
                }
            }
        }
    }
}
