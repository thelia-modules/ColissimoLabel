<?php

namespace ColissimoLabel\Controller\Admin;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Model\ColissimoLabel as ColissimoLabelModel;
use ColissimoLabel\Model\ColissimoLabelQuery;
use ColissimoLabel\Request\Helper\BordereauRequestAPIConfiguration;
use ColissimoLabel\Service\SOAPService;
use ColissimoWs\Model\ColissimowsLabel;
use ColissimoWs\Model\ColissimowsLabelQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Thelia\Action\File;
use Thelia\Controller\Admin\AdminController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Model\ModuleQuery;

class BordereauController extends AdminController
{
    /**
     * Render the bordereau list page
     *
     * @param null $error
     * @return \Thelia\Core\HttpFoundation\Response
     */
    public function listBordereauAction($error = null)
    {
        /** We make sure the folders exist, and create them otherwise */
        ColissimoLabel::checkLabelFolder();
        $lastBordereauDate = ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_LAST_BORDEREAU_DATE);

        /** We get every bordereau file from the bordereau folder */
        $finder = new Finder();
        $finder->files()->in(ColissimoLabel::BORDEREAU_FOLDER);

        /** We set a variable for the name and path of every found bordereau file, to be used in the template */
        $bordereaux = [];
        foreach ($finder as $file) {
            $bordereaux[] = [
                "name" => $file->getRelativePathname(),
                "path" => $file->getRealPath(),
            ];
        }

        /** We sort the bordereau by last created date */
        sort($bordereaux);
        $bordereaux = array_reverse($bordereaux);

        /** We render the page */
        return $this->render('colissimo-label/bordereau-list', compact("lastBordereauDate", "bordereaux", "error"));
    }

    /**
     * Render the label list page
     *
     * @return \Thelia\Core\HttpFoundation\Response
     */
    public function listLabelsAction()
    {
        ColissimoLabel::checkLabelFolder();

        return $this->render('colissimo-label/labels');
    }

    /**
     * Generate the bordereau, using the tracking/parcel numbers from the labels and the date since the
     * last time it was done
     *
     * @return \Thelia\Core\HttpFoundation\Response
     * @throws \Exception
     */
    public function generateBordereauAction()
    {
        /** Checking that the folder exists, and creates it otherwise */
        ColissimoLabel::checkLabelFolder();

        $lastBordereauDate = ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_LAST_BORDEREAU_DATE);

        /** We get the informations of all labels since the last time we created a bordereau with this method */
        $labels = ColissimoLabelQuery::create()
            ->filterByCreatedAt($lastBordereauDate, Criteria::GREATER_THAN)
            ->find();

        $parcelNumbers = [];

        /** @var ColissimoLabelModel $label */
        foreach ($labels as $label) {
            $parcelNumbers[] = $label->getTrackingNumber();
        }

        /** Compatibility with ColissimoWS < 2.0.0 */
        if (ModuleQuery::create()->findOneByCode('ColissimoWs')) {
            $labelsWs = ColissimowsLabelQuery::create()
                ->filterByCreatedAt($lastBordereauDate, Criteria::GREATER_THAN)
                ->find();

            /** @var ColissimowsLabel $label */
            foreach ($labelsWs as $labelWs) {
                $parcelNumbers[] = $labelWs->getTrackingNumber();
            }
        }

        $service = new SOAPService();
        $APIConfiguration = new BordereauRequestAPIConfiguration();
        $APIConfiguration->setContractNumber(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER));
        $APIConfiguration->setPassword(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_PASSWORD));

        $parseResponse = $service->callGenerateBordereauByParcelsNumbersAPI($APIConfiguration, $parcelNumbers);
        $resultAttachment = $parseResponse->attachments;
        if (!isset($resultAttachment[0])) {
            if (!isset($parseResponse->soapResponse['data'])) {
                return $this->listBordereauAction('No label found');
            }
            return $this->listBordereauAction('Error : ' . $this->getError($parseResponse->soapResponse['data']));
        }
        $bordereauContent = $resultAttachment[0];
        $fileContent = $bordereauContent['data'];

        if ('' == $fileContent) {
            throw new \Exception('File is empty');
        }

        /** We save the file on the server */
        $filePath = ColissimoLabel::getBordereauPath('bordereau_' .(new \DateTime())->format('Y-m-d_H-i-s'));
        $fileSystem = new Filesystem();
        $fileSystem->dumpFile(
            $filePath,
            $fileContent
        );

        /** We set the new date for the next time we want to use this method */
        ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_LAST_BORDEREAU_DATE, (new \DateTime())->format('Y-m-d H:i:s'));

        /** We reload the list of bordereau */
        return $this->listBordereauAction();
    }

    /**
     * Return the error message contained in the SOAP response from Colissimo
     *
     * @param $data
     * @return array
     */
    protected function getError($data) {
        $errorMessage = explode("<messageContent>", $data);
        $errorMessage = explode("</messageContent>", $errorMessage[1]);

        return $errorMessage[0];
    }

    /**
     * Retrieve a bordereau on the server given its filename passed in the request, and return it as a binary response
     *
     * @return BinaryFileResponse
     */
    public function downloadBordereauAction()
    {
        $filePath = $this->getRequest()->get('filePath');
        $filePathArray = explode('/', $filePath);
        $fileName = array_pop($filePathArray);
        $download = $this->getRequest()->get('stay');

        $response = new BinaryFileResponse($filePath);

        /** Download instead of opening the label in a window, if requested */
        if ($download) {
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $fileName
            );
        }


        return $response;
    }

    /**
     * Deletes a bordereau file, then reload the page
     *
     * @return \Thelia\Core\HttpFoundation\Response
     */
    public function deleteBordereauAction() {
        $fs = new Filesystem();
        $filePath = $this->getRequest()->get('filePath');

        $fs->remove($filePath);

        return $this->listBordereauAction();
    }
}