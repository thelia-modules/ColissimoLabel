<?php

namespace ColissimoLabel\Controller\Admin;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Event\ColissimoLabelEvents;
use ColissimoLabel\Event\LabelEvent;
use ColissimoLabel\Event\LabelRequestEvent;
use ColissimoLabel\Model\ColissimoLabel as ColissimoLabelModel;
use ColissimoLabel\Model\ColissimoLabelQuery;
use ColissimoLabel\Request\Helper\BordereauRequestAPIConfiguration;
use ColissimoLabel\Service\SOAPService;
use ColissimoLabel\Request\Helper\LabelRequestAPIConfiguration;
use ColissimoLabel\Request\LabelRequest;
use ColissimoPickupPoint\Model\OrderAddressColissimoPickupPointQuery;
use ColissimoWs\Controller\LabelController;
use ColissimoWs\Model\ColissimowsLabelQuery;
use Propel\Runtime\Exception\PropelException;
use SoColissimo\Model\OrderAddressSocolissimoQuery as OrderAddressSoColissimoPickupPointQuery;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Thelia\Controller\Admin\AdminController;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Exception\TheliaProcessException;
use Thelia\Model\ConfigQuery;
use Thelia\Model\ModuleQuery;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatusQuery;
use Thelia\Tools\URL;

class OrderController extends AdminController
{
    public function generateLabelAction(Request $request)
    {
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), array('ColissimoLabel'), AccessManager::UPDATE)) {
            return new JsonResponse([
                'error' => $this->getTranslator()->trans("Sorry, you're not allowed to perform this action")
            ], 403);
        }

        /** Make sure label and bordereau directories exist, creates them otherwise */
        ColissimoLabel::checkLabelFolder();

        $exportForm  = $this->createForm('colissimolabel_export_form');

        $files = $params = $parcelNumbers = [];

        try {
            $form = $this->validateForm($exportForm);

            $data = $form->getData();

            $isEditPage = $request->query->get('edit-order');

            ColissimoLabel::setConfigValue('new_status', $data['new_status']);

            $service = $this->getContainer()->get('colissimolabel.generate.label.service');
            $response = $service->generateLabel($data, $isEditPage);

            if ($isEditPage) {
                return $response;
            }
        } catch (\Exception $ex) {
            $this->setupFormErrorContext("Generation étiquettes Colissimo", $ex->getMessage(), $exportForm, $ex);
        }

        foreach ($data['order_id'] as $orderId) {
            if (null !== $order = OrderQuery::create()->findOneById($orderId)) {
                $fileSystem = new Filesystem();
                /** Generates and dump the invoice file if it is requested */
                if (ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_GET_INVOICES)) {
                    $invoiceResponse = $this->generateOrderPdf($orderId, ConfigQuery::read('pdf_invoice_file', 'invoice'));
                    $fileSystem->dumpFile(
                        $invoiceName = ColissimoLabel::getLabelPath($order->getRef() . '-invoice', 'pdf'),
                        $invoiceResponse->getContent()
                    );
                    $files[] = $invoiceName;
                }
            }
        }

        /** If we get here, that means the form was called from the module label page so we put every file requested in a .zip */
        if (count($files) > 0) {
            $bordereau = null;
            if (ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_GENERATE_BORDEREAU)) {
                $bordereau = $this->addBordereau($parcelNumbers);
                $files[] = $bordereau;
            }

            $zip = new \ZipArchive();
            $zipFilename = sys_get_temp_dir() . DS . uniqid('colissimo-label-', false);

            if (true !== $zip->open($zipFilename, \ZipArchive::CREATE)) {
                throw new TheliaProcessException("Cannot open zip file $zipFilename\n");
            }

            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }

            $zip->close();

            $params = [ 'zip' => base64_encode($zipFilename) ];

            if ($bordereau) {
                $fs = new Filesystem();
                $fs->remove($bordereau);
            }
        }

        /** We redirect to the module label page with parameters to download the zip file as well */
        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/colissimolabel/labels', $params));
    }

    /**
     * Add a bordereau to the labels generated, if requested
     *
     * @param $parcelNumbers
     * @return string
     * @throws \Exception
     */
    protected function addBordereau($parcelNumbers) {
        $service = new SOAPService();
        $APIConfiguration = new BordereauRequestAPIConfiguration();
        $APIConfiguration->setContractNumber(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER));
        $APIConfiguration->setPassword(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_PASSWORD));

        $parseResponse = $service->callGenerateBordereauByParcelsNumbersAPI($APIConfiguration, $parcelNumbers);
        $resultAttachment = $parseResponse->attachments;
        $bordereauContent = $resultAttachment[0];
        $fileContent = $bordereauContent['data'];

        if ('' == $fileContent) {
            throw new \Exception('File is empty');
        }

        $filePath = ColissimoLabel::getLabelPath('bordereau', 'pdf');

        $fileSystem = new Filesystem();
        $fileSystem->dumpFile(
            $filePath,
            $fileContent
        );

        return $filePath;
    }

    /**
     * Return a template with a list of all labels for a given order
     *
     * @param $orderId
     * @return Response
     */
    public function getOrderLabelsAction($orderId)
    {
        if (null !== $response = $this->checkAuth(AdminResources::ORDER, [], AccessManager::UPDATE)) {
            return new Response($this->getTranslator()->trans("Sorry, you're not allowed to perform this action"), 403);
        }

        return $this->render('colissimo-label/label-list', ['order_id' => $orderId]);
    }

    /**
     * Delete the label and invoice files on the server, given to the label name
     *
     * @param $fileName
     */
    protected function deleteLabelFile($fileName) {
        $finder = new Finder();
        $fileSystem = new Filesystem();

        $finder->files()->name($fileName . '*')->in(ColissimoLabel::LABEL_FOLDER);
        foreach ($finder as $file) {
            $fileSystem->remove(ColissimoLabel::LABEL_FOLDER . DS . $file->getFilename());
        }
    }

    /**
     * Delete a label file from server and delete its related table entry
     *
     * Compatibility with ColissimoLabel < 1.0.0
     * Compatibility with ColissimoWs < 2.0.0
     *
     * @param Request $request
     * @param $number
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws PropelException
     */
    public function deleteLabelAction(Request $request, $number) {
        $label = ColissimoLabelQuery::create()->findOneByTrackingNumber($number);
        /** We check if the label is from this module -- Compatibility with ColissimoWs */
        if ($label) {
            /** We check if the label is from this version of the module -- Compatibility with ColissimoLabel < 1.0.0 */
            if ('' !== $orderRef = $label->getOrderRef()) {
                $this->deleteLabelFile($orderRef);
                $label->delete();

                /** Handle the return when called from order edit */
                if ($editOrder = $request->get('edit-order')) {
                    return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/order/update/' . $label->getOrderId() . '?tab=bill'));
                }

                return $this->generateRedirect(URL::getInstance()->absoluteUrl('admin/module/colissimolabel/labels#order-' . $label->getOrderId()));
            }
        }

        /**
         * If we're here, it means the label was not from this module or module version, so we get it by other means
         * for compatibility reasons.
         */

        /** Trying to get it from ColissimoWs */
        if ($orderId = $request->get('order')) {
            /** Checking if ColissimoWs is installed */
            if (ModuleQuery::create()->findOneByCode(ColissimoLabel::AUTHORIZED_MODULES[0])) {
                /** Checking if the label entry exists in the deprecated ColissimoWsLabel table */
                if ($colissimoWslabel = ColissimowsLabelQuery::create()->findOneByOrderId($orderId)) {
                    $orderRef = $colissimoWslabel->getOrderRef();
                    $this->deleteLabelFile($orderRef);

                    $colissimoWslabel->delete();

                    /** Handle the return when called from order edit */
                    if ($editOrder = $request->get('edit-order')) {
                        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/order/update/' . $orderId . '?tab=bill'));
                    }

                    return $this->generateRedirect(URL::getInstance()->absoluteUrl('admin/module/colissimolabel/labels#order-' . $orderId));
                }
            }
        }

        /**
         * If we're here, it means the label is coming from a version of ColissimoLabel < 1.0.0
         * So we need to delete it with its tracking number instead of order ref, since it was named like that back then
         */
        $this->deleteLabelFile($number);
        $label->delete();

        /** Handle the return when called from order edit */
        if ($editOrder = $request->get('edit-order')) {
            return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/order/update/' . $label->getOrderId() . '?tab=bill'));
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('admin/module/colissimolabel/labels#order-' . $label->getOrderId()));
    }

    /**
     * Download the CN23 customs invoice, given an order Id
     *
     * @param $orderId
     * @return \Symfony\Component\HttpFoundation\Response|Response
     * @throws \Exception
     */
    public function getCustomsInvoiceAction($orderId) {
        if (null !== $order = OrderQuery::create()->findOneById($orderId)) {

            if ($label = ColissimoLabelQuery::create()->findOneByOrderId($orderId)) {
                $fileName = ColissimoLabel::getLabelCN23Path($label->getOrderRef() . 'CN23', 'pdf');
            } else {
                /** Compatibility with ColissimoWs < 2.0.0 */
                $label = new LabelController();
                $fileName = $label->createCustomsInvoice($orderId, $order->getRef());
            }

            return Response::create(
                file_get_contents($fileName),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-disposition' => 'Attachement;filename=' . basename($fileName)
                ]
            );
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/colissimolabel/labels'));
    }

    /**
     * Find the order label on the server and return it as a download response
     *
     * @param Request $request
     * @param $number
     * @return mixed|BinaryFileResponse
     */
    public function getLabelAction(Request $request, $number)
    {
        if (null !== $response = $this->checkAuth(AdminResources::ORDER, [], AccessManager::UPDATE)) {
            return $response;
        }

        $label = ColissimoLabelQuery::create()->findOneByTrackingNumber($number);

        /** Compatibility for ColissimoLabel < 1.0.0 */
        if ($label) {
            $file = ColissimoLabel::getLabelPath($number, ColissimoLabel::getFileExtension());
            $fileName = $number;

            $orderRef = $label->getOrderRef();
        }

        /** Compatibility for ColissimoWs < 2.0.0 */
        if (ModuleQuery::create()->findOneByCode(ColissimoLabel::AUTHORIZED_MODULES[0])) {
            if ($labelWs = ColissimowsLabelQuery::create()->findOneByTrackingNumber($number)) {
                $file = ColissimoLabel::getLabelPath($labelWs->getOrderRef(), $labelWs->getLabelType());
                $fileName = $labelWs->getOrderRef();
            }
        }

        /** The correct way to find the file for ColissimoLabel >= 1.0.0 */
        if ($orderRef && $orderRef !== '') {
            $file = ColissimoLabel::getLabelPath($label->getOrderRef(), ColissimoLabel::getFileExtension());
            $fileName = $label->getOrderRef();
        }

        $response = new BinaryFileResponse($file);


        if ($request->get('download')) {
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $fileName . '.' . ColissimoLabel::getFileExtension()
            );
        }

        return $response;
    }

    /**
     * Handles the download of the zip file given as hashed base 64 in the URL
     *
     * @param $orderId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function getLabelZip($base64EncodedZipFilename)
    {
        $zipFilename = base64_decode($base64EncodedZipFilename);

        if (file_exists($zipFilename)) {
            return new StreamedResponse(
                function () use ($zipFilename) {
                    readfile($zipFilename);
                    @unlink($zipFilename);
                },
                200,
                [
                    'Content-Type' => 'application/zip',
                    'Content-disposition' => 'attachement; filename=colissimo-labels.zip',
                    'Content-Length' => filesize($zipFilename)
                ]
            );
        }

        return new \Symfony\Component\HttpFoundation\Response('File no longer exists');
    }

}
