<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ColissimoLabel\Service;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Enum\AuthorizedModuleEnum;
use ColissimoLabel\Event\ColissimoLabelEvents;
use ColissimoLabel\Event\LabelRequestEvent;
use ColissimoLabel\Model\ColissimoLabelQuery;
use ColissimoLabel\Request\Helper\LabelRequestAPIConfiguration;
use ColissimoLabel\Request\LabelRequest;
use ColissimoPickupPoint\Model\OrderAddressColissimoPickupPointQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Exception\TheliaProcessException;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatusQuery;
use Thelia\Tools\URL;

class LabelService
{
    /**
     * UpdateDeliveryAddressListener constructor.
     */
    public function __construct(
        protected EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @param $data
     * @param EventDispatcherInterface $dispatcher
     * @return array label data for each processed order
     * @throws PropelException
     * @throws \SoapFault
     */
    public function generateLabel($data, EventDispatcherInterface $dispatcher): array
    {
        /** Check if status needs to be changed after processing */
        $newStatus = OrderStatusQuery::create()->findOneByCode($data['new_status']);

        $weightArray = $data['weight'];
        $signedArray = $data['signed'];
        $isReturn = filter_var($data['return_label'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $results = [];

        foreach ($data['order_id'] as $orderId) {
            if (null !== $order = OrderQuery::create()->findOneById($orderId)) {
                if (!isset($weightArray[$orderId]) || (float) $weightArray[$orderId] === 0.0) {
                    $weight = $order->getWeight();
                } else {
                    $weight = (float) $weightArray[$orderId];
                }

                if (null === $weight) {
                    throw new TheliaProcessException('Please enter a weight for every selected order');
                }

                /** Check if the 'signed' checkbox was checked for this particular order */
                $signedDelivery = false;
                if (\array_key_exists($orderId, $signedArray)) {
                    $signedDelivery = $signedArray[$orderId];
                }

                $APIConfiguration = new LabelRequestAPIConfiguration();
                $APIConfiguration->setContractNumber(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER));
                $APIConfiguration->setPassword(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_PASSWORD));

                $colissimoRequest = null;

                /* Same thing with ColissimoPickupPoint */
                if (AuthorizedModuleEnum::ColissimoPickupPoint->value === $order->getModuleRelatedByDeliveryModuleId()->getCode()) {
                    if (null !== $AddressColissimoPickupPoint = OrderAddressColissimoPickupPointQuery::create()
                            ->findOneById($order->getDeliveryOrderAddressId())) {
                        /* If the delivery is through a relay point, we create a new LabelRequest with the relay point and order infos */
                        if ($AddressColissimoPickupPoint) {
                            $colissimoRequest = new LabelRequest(
                                $order,
                                '0' == $AddressColissimoPickupPoint->getCode() ? null : $AddressColissimoPickupPoint->getCode(),
                                $AddressColissimoPickupPoint->getType(),
                                $signedDelivery,
                                $isReturn
                            );

                            $colissimoRequest->getLetter()->getService()->setCommercialName(
                                $colissimoRequest->getLetter()->getSender()->getAddress()->getCompanyName()
                            );
                        }
                    }
                }

                /* If this is a domicile delivery, we only use the order information to create a Labelrequest, not the relay point */
                if (null === $colissimoRequest) {
                    $colissimoRequest = new LabelRequest($order, null, null, $signedDelivery, $isReturn);
                }

                /* We set the weight as the one indicated from the form */
                $colissimoRequest->getLetter()->getParcel()->setWeight($weight);

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
                        $labelName = ColissimoLabel::getLabelPath($response->getParcelNumber(), ColissimoLabel::getFileExtension()),
                        $response->getFile()
                    );

                    $files[] = $labelName;
                    $hasCustomsFile = 0;

                    /* Dump the CN23 customs file if there is one */
                    if ($response->hasFileCN23()) {
                        $fileSystem->dumpFile(
                            $customsFileName = ColissimoLabel::getLabelCN23Path($response->getParcelNumber().'-CN23', 'pdf'),
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

                    $order->setDeliveryRef($response->getParcelNumber());
                    $order->save();

                    /* Change the order status if it was requested by the user */
                    if ($newStatus) {
                        $newStatusId = $newStatus->getId();

                        if ($order->getOrderStatus()->getId() !== $newStatusId) {
                            $order->setOrderStatus($newStatus);
                            $dispatcher->dispatch(
                                (new OrderEvent($order))->setStatus($newStatusId),
                                TheliaEvents::ORDER_UPDATE_STATUS
                            );
                        }
                    }

                    $results[] = [
                        'id' => $colissimoLabelModel->getId(),
                        'url' => URL::getInstance()?->absoluteUrl('/admin/module/ColissimoLabel/label/'.$response->getParcelNumber()),
                        'number' => $response->getParcelNumber(),
                        'order' => [
                            'id' => $order->getId(),
                            'status' => [
                                'id' => $order->getOrderStatus()->getId(),
                            ],
                        ],
                    ];

                    continue;
                }

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

                $results[] = [ 'error' => $response->getError(true)[0]];
            }
        }

        return $results;
    }

    /**
     * @param int $orderId
     * @return string|null
     */
    public function getCustomsInvoicePath(int $orderId): ?string
    {
        if (null === $label = ColissimoLabelQuery::create()->orderByCreatedAt(Criteria::DESC)->findOneByOrderId($orderId)) {
            return null;
        }

        return ColissimoLabel::getLabelCN23Path($label->getTrackingNumber().'-CN23', 'pdf');
    }

    /**
     * @param int $orderId
     * @param $prettyFileName
     * @return string|null
     */
    public function getLabelPathByOrderId(int $orderId, &$prettyFileName = ''): ?string
    {
        if (null === $label = ColissimoLabelQuery::create()->findOneByOrderId($orderId)) {
            return null;
        }

        return $this->getLabelPathByTrackingNumber($label->getTrackingNumber(), $prettyFileName);
    }

    /**
     * @param string $trackingNumber
     * @param $prettyFileName
     * @return string|null
     */
    public function getLabelPathByTrackingNumber(string $trackingNumber, &$prettyFileName = ''): ?string
    {
        if (null === $label = ColissimoLabelQuery::create()->findOneByTrackingNumber($trackingNumber)) {
            throw new TheliaProcessException("No label information for tracking number $trackingNumber");
        }

        $prettyFileName = $label->getOrderRef().'-'.$label->getTrackingNumber();

        return ColissimoLabel::getLabelPath($label->getTrackingNumber(), $label->getLabelType() ?? ColissimoLabel::getFileExtension());
    }

    /**
     * @param string $trackingNumber
     * @return void
     * @throws PropelException
     */
    public function deleteLabel(string $trackingNumber): int
    {
        if (null === $label = ColissimoLabelQuery::create()->findOneByTrackingNumber($trackingNumber)) {
            throw new TheliaProcessException('The label for tracking number '.$trackingNumber.' was not found');
        }

        /* We check if the label is from this module */
        /* We check if the label is from this version of the module -- Compatibility with ColissimoLabel < 1.0.0 */
        if ($label && '' !== $trackNo = $label->getTrackingNumber()) {
            $this->deleteLabelFile($trackNo);
        }

        /*
         * If we're here, it means the label is coming from a version of ColissimoLabel < 1.0.0
         * So we need to delete it with its tracking number instead of order ref, since it was named like that back then
        */
        $this->deleteLabelFile($trackingNumber);

        $label->delete();

        return $label->getOrderId();
    }

    /**
     * Delete the label and invoice files on the server, given to the label name.
     * @param $fileName
     * @return void
     */
    protected function deleteLabelFile($fileName): void
    {
        $finder = new Finder();
        $fileSystem = new Filesystem();

        $finder->files()->name($fileName.'*')->in(ColissimoLabel::LABEL_FOLDER);
        foreach ($finder as $file) {
            $fileSystem->remove(ColissimoLabel::LABEL_FOLDER.DS.$file->getFilename());
        }
    }
}
