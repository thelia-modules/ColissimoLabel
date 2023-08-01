<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 14/08/2020
 * Time: 11:07.
 */

namespace ColissimoLabel\EventListeners;

use ColissimoLabel\ColissimoLabel as ColissimoLabelModule;
use ColissimoLabel\Enum\AuthorizedModuleEnum;
use ColissimoLabel\Event\ColissimoLabelEvents;
use ColissimoLabel\Event\LabelRequestEvent;
use ColissimoLabel\Model\ColissimoLabel;
use ColissimoLabel\Model\ColissimoLabelQuery;
use ColissimoLabel\Request\Helper\LabelRequestAPIConfiguration;
use ColissimoLabel\Request\LabelRequest;
use ColissimoLabel\Service\SOAPService;
use ColissimoPickupPoint\Model\OrderAddressColissimoPickupPointQuery;
use Propel\Runtime\Exception\PropelException;
use SoapFault;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Event\Order\OrderAddressEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Order;

class UpdateDeliveryAddressListener extends BaseAdminController implements EventSubscriberInterface
{
    public function __construct(
        protected EventDispatcherInterface $dispatcher
    ) {}

    /**
     * @throws PropelException
     * @throws SoapFault
     */
    public function updateLabel(OrderAddressEvent $event)
    {
        $order = $event->getOrder();

        if ($labels = ColissimoLabelQuery::create()->filterByOrderId($order->getId())->find()) {
            foreach ($labels as $label) {
                $weight = $label->getWeight();
                $signedDelivery = $label->getSigned();
                $this->deleteLabel($label);
                $this->generateLabel($order, $weight, $signedDelivery);
            }
        }
    }

    /**
     * @throws PropelException
     */
    protected function deleteLabel(ColissimoLabel $label)
    {

        /* We check if the label is from this version of the module -- Compatibility with ColissimoLabel < 1.0.0 */
        if ('' !== $orderRef = $label->getOrderRef()) {
            $this->deleteLabelFile($orderRef);
            $label->delete();
        }

        /*
         * If we're here, it means the label was not from this module or module version, so we get it by other means
         * for compatibility reasons.
         */
    }

    protected function deleteLabelFile($fileName)
    {
        $finder = new Finder();
        $fileSystem = new Filesystem();

        $finder->files()->name($fileName.'*')->in(ColissimoLabelModule::LABEL_FOLDER);
        foreach ($finder as $file) {
            $fileSystem->remove(ColissimoLabelModule::LABEL_FOLDER.DS.$file->getFilename());
        }
    }

    /**
     * @param Order $order
     * @param $weight
     * @param $signedDelivery
     *
     * @throws PropelException
     * @throws SoapFault
     */
    protected function generateLabel(Order $order, $weight, $signedDelivery)
    {
        $APIConfiguration = new LabelRequestAPIConfiguration();
        $APIConfiguration->setContractNumber(ColissimoLabelModule::getConfigValue(ColissimoLabelModule::CONFIG_KEY_CONTRACT_NUMBER));
        $APIConfiguration->setPassword(ColissimoLabelModule::getConfigValue(ColissimoLabelModule::CONFIG_KEY_PASSWORD));

        /* Check if delivery is a relay point through ColissimoPickupPoint. Use relay point address if it is */
        if (AuthorizedModuleEnum::ColissimoPickupPoint->value === $order->getModuleRelatedByDeliveryModuleId()->getCode() &&
            null !== ($AddressColissimoPickupPoint = OrderAddressColissimoPickupPointQuery::create()
                ->findOneById($order->getDeliveryOrderAddressId()))
            && $AddressColissimoPickupPoint) {
            /** If the delivery is through a relay point, we create a new LabelRequest with the relay point and order infos */
            $colissimoRequest = new LabelRequest(
                $order,
                '0' === $AddressColissimoPickupPoint->getCode() ? null : $AddressColissimoPickupPoint->getCode(),
                $AddressColissimoPickupPoint->getType()
            );

            $colissimoRequest->getLetter()->getService()->setCommercialName(
                $colissimoRequest->getLetter()->getSender()->getAddress()->getCompanyName()
            );
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
        $this->dispatcher->dispatch(
            new LabelRequestEvent($colissimoRequest),
            ColissimoLabelEvents::LABEL_REQUEST
        );

        $response = $service->callAPI($APIConfiguration, $colissimoRequest);

        /* Handling what happens if the response from Colissimo is valid */
        if ($response->isValid()) {
            $fileSystem = new Filesystem();

            /* We dump / save the label on the server */
            $fileSystem->dumpFile(
                $labelName = ColissimoLabelModule::getLabelPath($order->getRef(), ColissimoLabelModule::getFileExtension()),
                $response->getFile()
            );

            $files[] = $labelName;
            $hasCustomsFile = 0;

            /* Dump the CN23 customs file if there is one */
            if ($response->hasFileCN23()) {
                $fileSystem->dumpFile(
                    $customsFileName = ColissimoLabelModule::getLabelCN23Path($order->getRef().'CN23', 'pdf'),
                    $response->getFileCN23()
                );
                $files[] = $customsFileName;
                $hasCustomsFile = 1;
            }

            /* Generates and dump the invoice file if it is requested */
            if (ColissimoLabelModule::getConfigValue(ColissimoLabelModule::CONFIG_KEY_GET_INVOICES)) {
                $invoiceResponse = $this->generateOrderPdf($this->dispatcher, $order->getId(), ConfigQuery::read('pdf_invoice_file', 'invoice'));
                $fileSystem->dumpFile(
                    $invoiceName = ColissimoLabelModule::getLabelPath($order->getRef().'-invoice', 'pdf'),
                    $invoiceResponse->getContent()
                );
                $files[] = $invoiceName;
            }

            /**
             * Checking if an entry with an error already exists in the table for this order label, creates one otherwise
             * This allows to modify only entry with errors, while creating new ones if none with error were found.
             */
            $colissimoLabelModel = ColissimoLabelQuery::create()
                ->filterByOrder($order)
                ->filterByError(1)
                ->findOneOrCreate();
            /* Saving the label info in the table */
            $colissimoLabelModel
                ->setOrderId($order->getId())
                ->setOrderRef($order->getRef())
                ->setError(0)
                ->setErrorMessage('')
                ->setWeight($colissimoRequest->getLetter()->getParcel()->getWeight())
                ->setTrackingNumber($response->getParcelNumber())
                ->setSigned($signedDelivery)
                ->setLabelType(ColissimoLabelModule::getFileExtension())
                ->setWithCustomsInvoice($hasCustomsFile);
            $colissimoLabelModel->save();

            $parcelNumbers[] = $response->getParcelNumber();

            $order->setDeliveryRef($response->getParcelNumber());
            $order->save();
        } else {
            /** Handling errors when the response is invalid */
            $colissimoLabelError = ColissimoLabelQuery::create()
                ->filterByOrder($order)
                ->filterByError(1)
                ->findOneOrCreate();

            $colissimoLabelError
                ->setError(1)
                ->setErrorMessage($response->getError(true)[0])
                ->setSigned($signedDelivery)
                ->setWeight($colissimoRequest->getLetter()->getParcel()->getWeight())
                ->save();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::ORDER_UPDATE_ADDRESS => ['updateLabel', 128],
        ];
    }
}
