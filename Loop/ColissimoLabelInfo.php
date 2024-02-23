<?php

namespace ColissimoLabel\Loop;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Model\ColissimoLabel as ColissimoLabelModel;
use ColissimoLabel\Model\ColissimoLabelQuery;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\Finder\Finder;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\OrderQuery;
use Thelia\Tools\URL;

/**
 * @method int getOrderId()
 */
class ColissimoLabelInfo extends BaseLoop implements PropelSearchLoopInterface
{
    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions(): ArgumentCollection
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('order_id', null, true)
        );
    }

    public function buildModelCriteria(): ColissimoLabelQuery|ModelCriteria
    {
        return ColissimoLabelQuery::create()
            ->filterByOrderId($this->getOrderId());
    }

    /**
     * @param LoopResult $loopResult
     * @return LoopResult
     *
     * @throws PropelException
     */
    public function parseResults(LoopResult $loopResult): LoopResult
    {
        if (0 === $loopResult->getResultDataCollectionCount()) {
            if (null !== $order = OrderQuery::create()->findPk($this->getOrderId())) {
                $loopResultRow = new LoopResultRow();

                $defaultSigned = ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_DEFAULT_SIGNED);

                $loopResultRow
                    ->set('ORDER_ID', $this->getOrderId())
                    ->set('HAS_ERROR', false)
                    ->set('ERROR_MESSAGE', null)
                    ->set('WEIGHT', $order->getWeight())
                    ->set('SIGNED', $defaultSigned)
                    ->set('TRACKING_NUMBER', null)
                    ->set('HAS_LABEL', false)
                    ->set('LABEL_URL', null)
                    ->set('CLEAR_LABEL_URL', null)
                    ->set('CAN_BE_NOT_SIGNED', ColissimoLabel::canOrderBeNotSigned($order));

                $loopResult->addRow($loopResultRow);
            }
        } else {
            /** @var ColissimoLabelModel $result */
            foreach ($loopResult->getResultDataCollection() as $result) {
                /* Compatibility for ColissimoLabel < 1.0.0 */
                if ('' === $result->getOrderRef()) {
                    $finder = new Finder();
                    $finder->files()->name($result->getTrackingNumber().'.*')->in(ColissimoLabel::LABEL_FOLDER);
                    foreach ($finder as $file) {
                        $result->setLabelType($file->getExtension());
                    }
                }

                $loopResultRow = new LoopResultRow();
                $loopResultRow
                    ->set('ORDER_ID', $result->getOrderId())
                    ->set('HAS_ERROR', $result->getError())
                    ->set('ERROR_MESSAGE', $result->getErrorMessage())
                    ->set('WEIGHT', empty($result->getWeight()) ? $result->getOrder()->getWeight() : $result->getWeight())
                    ->set('SIGNED', $result->getSigned())
                    ->set('TRACKING_NUMBER', $result->getTrackingNumber())
                    ->set('HAS_LABEL', !empty($result->getLabelType()))
                    ->set('LABEL_TYPE', $result->getLabelType())
                    ->set('HAS_CUSTOMS_INVOICE', $result->getWithCustomsInvoice())
                    ->set('LABEL_URL', URL::getInstance()->absoluteUrl('/admin/module/ColissimoLabel/label/'.$result->getTrackingNumber().'?download=1'))
                    ->set('CUSTOMS_INVOICE_URL', URL::getInstance()->absoluteUrl('/admin/module/ColissimoLabel/customs-invoice/'.$result->getOrderId()))
                    ->set('CLEAR_LABEL_URL', URL::getInstance()->absoluteUrl('/admin/module/ColissimoLabel/label/delete/'.$result->getTrackingNumber().'?order='.$result->getOrderId()))
                    ->set('CAN_BE_NOT_SIGNED', ColissimoLabel::canOrderBeNotSigned($result->getOrder()))
                    ->set('ORDER_DATE', $result->getOrder()->getCreatedAt())
                    ->set('CREATED_AT', $result->getCreatedAt())
                ;

                $loopResult->addRow($loopResultRow);
            }
        }

        return $loopResult;
    }
}
