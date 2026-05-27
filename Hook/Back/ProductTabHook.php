<?php

namespace ColissimoLabel\Hook\Back;

use ColissimoLabel\ColissimoLabel;
use ColissimoLabel\Model\ColissimoLabelProductCustomsQuery;
use Thelia\Core\Event\Hook\HookRenderBlockEvent;
use Thelia\Core\Hook\BaseHook;

class ProductTabHook extends BaseHook
{
    public function onProductTab(HookRenderBlockEvent $event): void
    {
        $productId = (int) $event->getArgument('id');

        $customs = ColissimoLabelProductCustomsQuery::create()
            ->findOneByProductId($productId);

        $event->add([
            'id' => 'colissimo_label_product_customs',
            'title' => $this->trans('Douane Colissimo', [], ColissimoLabel::DOMAIN_NAME),
            'content' => $this->render('colissimo-label/hook/product-tab-customs.html', [
                'product_id' => $productId,
                'hs_code' => $customs?->getHsCode() ?? '',
                'default_hs_code' => (string) ColissimoLabel::getConfigValue(ColissimoLabel::CONFIG_KEY_CUSTOMS_PRODUCT_HSCODE),
            ]),
        ]);
    }
}
