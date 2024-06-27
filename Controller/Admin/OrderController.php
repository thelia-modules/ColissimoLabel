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

namespace ColissimoLabel\Controller\Admin;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Form\LabelGenerationForm;
use ColissimoLabel\Model\ColissimoLabelQuery;
use ColissimoLabel\Request\Helper\BordereauRequestAPIConfiguration;
use ColissimoLabel\Service\LabelService;
use ColissimoLabel\Service\SOAPService;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\AdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Exception\TheliaProcessException;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\OrderQuery;
use Thelia\Tools\URL;

#[Route('/admin/module/ColissimoLabel', name: 'colissimo_label_order_')]
class OrderController extends AdminController
{
    /**
     * @throws \Exception
     */
    #[Route('/export', name: 'export')]
    public function generateLabelAction(Request $request, LabelService $labelService, EventDispatcherInterface $eventDispatcher): JsonResponse|RedirectResponse|Response
    {
        if (null !== $this->checkAuth([AdminResources::MODULE], ['ColissimoLabel'], AccessManager::UPDATE)) {
            return new JsonResponse([
                'error' => Translator::getInstance()->trans("Sorry, you're not allowed to perform this action"),
            ], 403);
        }

        /* Make sure label and bordereau directories exist, creates them otherwise */
        ColissimoLabel::checkLabelFolder();

        $exportForm = $this->createForm(LabelGenerationForm::getName());
        $files = $params = $parcelNumbers = [];

        try {
            $form = $this->validateForm($exportForm);

            $data = $form->getData();

            $isEditPage = $request->query->get('edit-order');

            if (!$isEditPage) {
                ColissimoLabel::setConfigValue('new_status', $data['new_status']);
            }

            $responseData = $labelService->generateLabel($data, $eventDispatcher);

            if ($isEditPage) {
                return new JsonResponse($responseData[0]);
            }

            foreach ($data['order_id'] as $orderId) {
                if (null !== $order = OrderQuery::create()->findOneById($orderId)) {
                    $fileSystem = new Filesystem();
                    /* Generates and dump the invoice file if it is requested */
                    if (ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_GET_INVOICES)) {
                        $invoiceResponse = $this->generateOrderPdf($eventDispatcher, $orderId, ConfigQuery::read('pdf_invoice_file', 'invoice'));
                        $fileSystem->dumpFile(
                            $invoiceName = ColissimoLabel::getLabelPath($order->getRef().'-invoice', 'pdf'),
                            $invoiceResponse->getContent()
                        );
                        $files[] = $invoiceName;
                    }
                }
            }

            // If we get here, that means the form was called from the module label page,
            // so we put every file requested in a .zip
            if (\count($files) > 0) {
                $bordereau = null;
                if (ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_GENERATE_BORDEREAU)
                    && null !== $bordereau = $this->addBordereau($parcelNumbers)
                ) {
                    $files[] = $bordereau;
                }

                $zip = new \ZipArchive();
                $zipFilename = sys_get_temp_dir().DS.uniqid('colissimo-label-', true);

                if (true !== $zip->open($zipFilename, \ZipArchive::CREATE)) {
                    throw new TheliaProcessException("Cannot open zip file $zipFilename\n");
                }

                foreach ($files as $file) {
                    $zip->addFile($file, basename($file));
                }

                $zip->close();

                $params = ['zip' => base64_encode($zipFilename)];

                if ($bordereau) {
                    $fs = new Filesystem();
                    $fs->remove($bordereau);
                }
            }
        } catch (\Exception $ex) {
            Tlog::getInstance()->err('Failed to create Colissimo label : '.$ex->getMessage());
            $this->setupFormErrorContext('Generation étiquettes Colissimo', $ex->getMessage(), $exportForm, $ex);
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/ColissimoLabel/labels', $params));
    }

    /**
     * Add a bordereau to the labels generated, if requested.
     *
     * @throws \Exception
     */
    protected function addBordereau($parcelNumbers): ?string
    {
        $service = new SOAPService();
        $APIConfiguration = new BordereauRequestAPIConfiguration();
        $APIConfiguration->setContractNumber(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER));
        $APIConfiguration->setPassword(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_PASSWORD));

        try {
            $parseResponse = $service->callGenerateBordereauByParcelsNumbersAPI($APIConfiguration, $parcelNumbers);
            $resultAttachment = $parseResponse->attachments;

            if (!isset($resultAttachment[0])) {
                throw new TheliaProcessException('Bordereau request : no attached data.');
            }

            $bordereauContent = $resultAttachment[0];

            if (!isset($bordereauContent['data'])) {
                throw new TheliaProcessException('Bordereau request : attachment is empty.');
            }

            $fileContent = $bordereauContent['data'];

            if (empty($fileContent)) {
                throw new TheliaProcessException('Bordereau request : Bordereau file is empty');
            }

            $filePath = ColissimoLabel::getLabelPath('bordereau', 'pdf');

            $fileSystem = new Filesystem();
            $fileSystem->dumpFile(
                $filePath,
                $fileContent
            );

            return $filePath;
        } catch (\Exception $ex) {
            Tlog::getInstance()->error('Failed to get Bordereau : '.$ex->getMessage());
        }

        return null;
    }

    /**
     * Return a template with a list of all labels for a given order.
     */
    #[Route('/order/{orderId}/ajax-get-labels', name: '_labels')]
    public function getOrderLabelsAction($orderId): Response
    {
        if (null !== $this->checkAuth(AdminResources::ORDER, [], AccessManager::UPDATE)) {
            return new Response(Translator::getInstance()->trans("Sorry, you're not allowed to perform this action"), 403);
        }

        return $this->render('colissimo-label/label-list', ['order_id' => $orderId]);
    }

    /**
     * Delete a label file from server and delete its related table entry.
     *
     * Compatibility with ColissimoLabel < 1.0.0
     *
     * @throws PropelException
     */
    #[Route('/label/delete/{number}', name: 'delete', methods: 'GET')]
    public function deleteLabelAction(Request $request, string $number, LabelService $labelService): Response
    {
        $orderId = $labelService->deleteLabel($number);

        /* Handle the return when called from order edit */
        if ($request->get('edit-order')) {
            return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/order/update/'.$orderId.'?tab=bill'));
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('admin/module/ColissimoLabel/labels#order-'.$orderId));
    }

    /**
     * Download the CN23 customs invoice, given an order ID.
     */
    #[Route('/customs-invoice/{orderId}', name: 'customers_invoice')]
    public function getCustomsInvoiceAction($orderId, LabelService $labelService): Response
    {
        if (null !== $fileName = $labelService->getCustomsInvoicePath($orderId)) {
            return new Response(
                file_get_contents($fileName),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-disposition' => 'Attachement;filename='.basename($fileName),
                ]
            );
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/ColissimoLabel/labels'));
    }

    /**
     * Find the order label on the server and return it as a download response.
     */
    #[Route('/label/{number}', name: 'list', methods: 'GET')]
    public function getLabelAction(Request $request, string $number, LabelService $labelService): mixed
    {
        if (null !== $response = $this->checkAuth(AdminResources::ORDER, [], AccessManager::UPDATE)) {
            return $response;
        }

        $file = $labelService->getLabelPathByTrackingNumber($number, $fileName);

        $response = new BinaryFileResponse($file);

        if ($request->get('download')) {
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $fileName.'.'.ColissimoLabel::getFileExtension()
            );
        }

        return $response;
    }

    /**
     * Handles the download of the zip file given as hashed base 64 in the URL.
     */
    #[Route('/labels-zip/{base64EncodedZipFilename}', name: 'labels_zip')]
    public function getLabelZip($base64EncodedZipFilename): StreamedResponse|Response
    {
        $zipFilename = base64_decode($base64EncodedZipFilename);

        if (file_exists($zipFilename)) {
            return new StreamedResponse(
                function () use ($zipFilename): void {
                    readfile($zipFilename);
                    @unlink($zipFilename);
                },
                200,
                [
                    'Content-Type' => 'application/zip',
                    'Content-disposition' => 'attachement; filename=colissimo-labels.zip',
                    'Content-Length' => filesize($zipFilename),
                ]
            );
        }

        return new Response('File no longer exists');
    }
}
