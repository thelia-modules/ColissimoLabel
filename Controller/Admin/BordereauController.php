<?php

namespace ColissimoLabel\Controller\Admin;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Model\ColissimoLabel as ColissimoLabelModel;
use ColissimoLabel\Model\ColissimoLabelQuery;
use ColissimoLabel\Request\Helper\BordereauRequestAPIConfiguration;
use ColissimoLabel\Service\SOAPService;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Thelia\Controller\Admin\AdminController;

class BordereauController extends AdminController
{
    public function listBordereauAction()
    {
        ColissimoLabel::checkLabelFolder();
        $lastBordereauDate = ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_LAST_BORDEREAU_DATE);

        $finder = new Finder();
        $finder->files()->in(ColissimoLabel::BORDEREAU_FOLDER);


        $bordereaux = [];
        foreach ($finder as $file) {
            $bordereaux[] = [
                "name" => $file->getRelativePathname(),
                "path" => $file->getRealPath()
            ];
        }

        sort($bordereaux);
        return $this->render('colissimo-label/bordereau-list', compact("lastBordereauDate", "bordereaux"));
    }

    public function generateBordereauAction()
    {
        ColissimoLabel::checkLabelFolder();

        $lastBordereauDate = ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_LAST_BORDEREAU_DATE);

        $labels = ColissimoLabelQuery::create()
            ->filterByCreatedAt($lastBordereauDate, Criteria::GREATER_THAN)
            ->find();

        $parcelNumbers = [];

        /** @var ColissimoLabelModel $label */
        foreach ($labels as $label) {
            $parcelNumbers[] = $label->getNumber();
        }

        $service = new SOAPService();
        $APIConfiguration = new BordereauRequestAPIConfiguration();
        $APIConfiguration->setContractNumber(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER));
        $APIConfiguration->setPassword(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_PASSWORD));

        $parseResponse = $service->callGenerateBordereauByParcelsNumbersAPI($APIConfiguration, $parcelNumbers);
        $resultAttachment = $parseResponse->attachments;
        if (!isset($resultAttachment[0])) {
            throw new \Exception("No label found");
        }
        $bordereauContent = $resultAttachment[0];
        $fileContent = $bordereauContent["data"];

        if ("" == $fileContent) {
            throw new \Exception("File is empty");
        }

        $filePath = ColissimoLabel::getBordereauPath("bordereau_".(new \DateTime())->format("Y-m-d_H-i-s"));

        $fileSystem = new Filesystem();
        $fileSystem->dumpFile(
            $filePath,
            $fileContent
        );

        ColissimoLabel::setConfigValue(ColissimoLabel::CONFIG_KEY_LAST_BORDEREAU_DATE, (new \DateTime())->format("Y-m-d H:i:s"));

        return $this->listBordereauAction();
    }

    public function downloadBordereauAction()
    {
        $filePath = $this->getRequest()->get('filePath');
        return new BinaryFileResponse($filePath);
    }
}