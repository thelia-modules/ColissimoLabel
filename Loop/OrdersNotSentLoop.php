<?php

namespace ColissimoLabel\Loop;

use ColissimoLabel\Enum\AuthorizedModuleEnum;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Order;
use Thelia\Model\ModuleQuery;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;

class OrdersNotSentLoop extends Order
{
    public function getArgDefinitions(): ArgumentCollection
    {
        return new ArgumentCollection(Argument::createBooleanTypeArgument('with_prev_next_info', false));
    }

    /**
     * This method returns a Propel ModelCriteria.
     *
     * @return ModelCriteria
     */
    public function buildModelCriteria(): ModelCriteria
    {
        /** Get an array composed of the paid and processing order statuses */
        $status = OrderStatusQuery::create()
            ->filterByCode(
                [
                    OrderStatus::CODE_PAID,
                    OrderStatus::CODE_PROCESSING,
                ],
                Criteria::IN
            )
            ->find()
            ->toArray('code');

        /** Verify what modules are installed */
        $moduleIds = [];
        if ($colissimoHomeDelivery = ModuleQuery::create()->findOneByCode(AuthorizedModuleEnum::ColissimoHomeDelivery->value)) {
            $moduleIds[] = $colissimoHomeDelivery->getId();
        }
        if ($colissimoPickupPoint = ModuleQuery::create()->findOneByCode(AuthorizedModuleEnum::ColissimoPickupPoint->value)) {
            $moduleIds[] = $colissimoPickupPoint->getId();
        }

        return OrderQuery::create()
            ->filterByDeliveryModuleId(
                $moduleIds,
                Criteria::IN
            )
            ->filterByStatusId(
                [
                    $status[OrderStatus::CODE_PAID]['Id'],
                    $status[OrderStatus::CODE_PROCESSING]['Id'],
                ],
                Criteria::IN
            );
    }
}
