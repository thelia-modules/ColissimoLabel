<?php

namespace ColissimoLabel\Loop;

use ColissimoLabel\Model\ColissimoLabelQuery;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;

/**
 * Class CreditNoteStatus
 * @package CreditNote\Loop
 * @author Gilles Bourgeat <gilles.bourgeat@gmail.com>
 *
 * @method int getOrderId()
 */
class ColissimoLabel extends BaseLoop implements PropelSearchLoopInterface
{
    protected $timestampable = true;

    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('order_id', null, true)
        );
    }

    /**
     * this method returns a Propel ModelCriteria
     *
     * @return \Propel\Runtime\ActiveQuery\ModelCriteria
     */
    public function buildModelCriteria()
    {
        $query = new ColissimoLabelQuery();

        $query->filterByOrderId($this->getOrderId());

        return $query;
    }

    /**
     * @param LoopResult $loopResult
     *
     * @return LoopResult
     */
    public function parseResults(LoopResult $loopResult)
    {
        /** @var \ColissimoLabel\Model\ColissimoLabel $entry */
        foreach ($loopResult->getResultDataCollection() as $entry) {
            $row = new LoopResultRow($entry);
            $row
                ->set('WEIGHT', $entry->getWeight())
                ->set("ID", $entry->getId())
                ->set("NUMBER", $entry->getNumber())
                ->set("ORDER_ID", $entry->getOrderId())
            ;
            $this->addOutputFields($row, $entry);
            $loopResult->addRow($row);
        }

        return $loopResult;
    }
}
