<?php

namespace ColissimoLabel\Controller\Admin;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Model\ColissimoLabel as ColissimoLabelModel;
use ColissimoLabel\Service\SOAPService;
use ColissimoLabel\Request\Helper\LabelRequestAPIConfiguration;
use ColissimoLabel\Request\LabelRequest;
use Propel\Runtime\ActiveQuery\Criteria;
use SoColissimo\Model\AddressSocolissimoQuery;
use SoColissimo\Model\OrderAddressSocolissimoQuery;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Thelia\Controller\Admin\AdminController;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatusQuery;
use Thelia\Tools\URL;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class OrderController extends AdminController
{
    public function generateLabelAction(Request $request, $orderId)
    {
        if (null !== $response = $this->checkAuth(AdminResources::ORDER, [], AccessManager::UPDATE)) {
            return new JsonResponse([
                'error' => $this->getTranslator()->trans("Sorry, you're not allowed to perform this action")
            ], 403);
        }

        ColissimoLabel::checkLabelFolder();

        $order = OrderQuery::create()->filterById((int) $orderId, Criteria::EQUAL)->findOne();

        $APIConfiguration = new LabelRequestAPIConfiguration();

        $APIConfiguration->setContractNumber(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_CONTRACT_NUMBER));
        $APIConfiguration->setPassword(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_PASSWORD));

        if ('SoColissimo' === $order->getModuleRelatedByDeliveryModuleId()->getCode()) {
            if (null !== $addressSocolissimo = OrderAddressSocolissimoQuery::create()
                ->findOneById($order->getDeliveryOrderAddressId())) {
                if ($addressSocolissimo) {
                    $colissimoRequest = new LabelRequest(
                        $order,
                        $addressSocolissimo->getCode() == '0' ? null : $addressSocolissimo->getCode(),
                        $addressSocolissimo->getType()
                    );

                    $colissimoRequest->getLetter()->getService()->setCommercialName(
                        $colissimoRequest->getLetter()->getSender()->getAddress()->getCompanyName()
                    );
                }
            }
        }

        if (!isset($colissimoRequest)) {
            $colissimoRequest = new LabelRequest($order);
        }

        if (null !== $weight = $request->get('weight')) {
            $colissimoRequest->getLetter()->getParcel()->setWeight($weight);
        }

        $service = new SOAPService();

        $response = $service->callAPI($APIConfiguration, $colissimoRequest);

        if ($response->isValid()) {
            $fileSystem = new Filesystem();

            $fileSystem->dumpFile(
                ColissimoLabel::getLabelPath($response->getParcelNumber(), ColissimoLabel::getExtensionFile()),
                $response->getFile()
            );

            if ($response->hasFileCN23()) {
                $fileSystem->dumpFile(
                    ColissimoLabel::getLabelCN23Path($response->getParcelNumber(), ColissimoLabel::getExtensionFile()),
                    $response->getFileCN23()
                );
            }

            $colissimoLabelModel = (new ColissimoLabelModel())
                ->setOrderId($order->getId())
                ->setWeight($colissimoRequest->getLetter()->getParcel()->getWeight())
                ->setNumber($response->getParcelNumber());

            $colissimoLabelModel->save();

            $order->setDeliveryRef($response->getParcelNumber());

            $order->save();

            if ((int) ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_AUTO_SENT_STATUS)) {
                $sentStatusId = (int) ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_SENT_STATUS_ID);

                if ((int) $order->getOrderStatus()->getId() !== (int) $sentStatusId) {
                    $order->setOrderStatus(
                        OrderStatusQuery::create()->findOneById((int) $sentStatusId)
                    );
                    $this->getDispatcher()->dispatch(
                        TheliaEvents::ORDER_UPDATE_STATUS,
                        (new OrderEvent($order))->setStatus((int) $sentStatusId)
                    );
                }
            }

            return new JsonResponse([
                'id' => $colissimoLabelModel->getId(),
                'url' => URL::getInstance()->absoluteUrl('/admin/module/colissimolabel/label/' . $response->getParcelNumber()),
                'number' => $response->getParcelNumber(),
                'order' => [
                    'id' => $order->getId(),
                    'status' => [
                        'id' => $order->getOrderStatus()->getId()
                    ]
                ]
            ]);
        } else {
            return new JsonResponse([
                'error' => $response->getError()
            ]);
        }
    }

    public function getOrderLabelsAction($orderId)
    {
        if (null !== $response = $this->checkAuth(AdminResources::ORDER, [], AccessManager::UPDATE)) {
            return new Response($this->getTranslator()->trans("Sorry, you're not allowed to perform this action"), 403);
        }

        return $this->render('colissimo-label/label-list', ['order_id' => $orderId]);
    }

    public function getLabelAction(Request $request, $number)
    {
        if (null !== $response = $this->checkAuth(AdminResources::ORDER, [], AccessManager::UPDATE)) {
            return $response;
        }

        $response = new BinaryFileResponse(
            ColissimoLabel::getLabelPath($number, ColissimoLabel::getExtensionFile())
        );

        $ext = strtolower(substr(ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_DEFAULT_LABEL_FORMAT), 3));

        if ($request->get('download')) {
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $number . '.' . ColissimoLabel::getExtensionFile()
            );
        }

        return $response;
    }
}
