<?php

namespace ColissimoLabel\Controller\Admin;

use ColissimoLabel\Model\ColissimoLabelProductCustoms;
use ColissimoLabel\Model\ColissimoLabelProductCustomsQuery;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Tools\URL;

#[Route('/admin/module/ColissimoLabel/product-customs', name: 'colissimo_label_product_customs_')]
class ProductCustomsController extends BaseAdminController
{
    #[Route('/save', name: 'save', methods: ['POST'])]
    public function saveAction(Request $request): Response
    {
        if (null !== $response = $this->checkAuth([AdminResources::PRODUCT], ['ColissimoLabel'], AccessManager::UPDATE)) {
            return $response;
        }

        $productId = (int) $request->request->get('product_id');
        $hsCode = trim((string) $request->request->get('hs_code', ''));

        if ($productId <= 0) {
            return $this->errorPage('Missing product_id', 400);
        }

        $customs = ColissimoLabelProductCustomsQuery::create()->findOneByProductId($productId)
            ?? (new ColissimoLabelProductCustoms())->setProductId($productId);

        $customs->setHsCode($hsCode)->save();

        return new RedirectResponse(
            URL::getInstance()->absoluteUrl(
                '/admin/products/update',
                ['product_id' => $productId, 'current_tab' => 'colissimo_label_product_customs']
            )
        );
    }
}
