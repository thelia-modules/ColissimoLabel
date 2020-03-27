<?php


namespace ColissimoLabel\Loop;


use ColissimoWs\ColissimoWs;
use ColissimoLabel\ColissimoLabel;
use Propel\Runtime\ActiveQuery\Criteria;
use SoColissimo\SoColissimo;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Order;
use Thelia\Model\ModuleQuery;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;

class OrdersNotSentLoop extends Order
{
    public function getArgDefinitions()
    {
        return new ArgumentCollection(Argument::createBooleanTypeArgument('with_prev_next_info', false));
    }

    /**
     * This method returns a Propel ModelCriteria
     *
     * @return \Propel\Runtime\ActiveQuery\ModelCriteria
     */
    public function buildModelCriteria()
    {
        /** Get an array composed of the paid and processing order statuses */
        $status = OrderStatusQuery::create()
            ->filterByCode(
                array(
                    OrderStatus::CODE_PAID,
                    OrderStatus::CODE_PROCESSING,
                ),
                Criteria::IN
            )
            ->find()
            ->toArray("code");

        /** Verify what modules are installed */
        $moduleIds = [];
        if ($colissimoWS = ModuleQuery::create()->findOneByCode(ColissimoLabel::AUTHORIZED_MODULES[0])) {
            $moduleIds[] = $colissimoWS->getId();
        }
        if ($soColissimo = ModuleQuery::create()->findOneByCode(ColissimoLabel::AUTHORIZED_MODULES[1])) {
            $moduleIds[] = $soColissimo->getId();
        }
        $query = OrderQuery::create()
            ->filterByDeliveryModuleId(
                $moduleIds,
                Criteria::IN
            )
            ->filterByStatusId(
                array(
                    $status[OrderStatus::CODE_PAID]['Id'],
                    $status[OrderStatus::CODE_PROCESSING]['Id']
                ),
                Criteria::IN
            );

        return $query;
    }
}