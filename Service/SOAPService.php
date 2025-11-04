<?php

namespace ColissimoLabel\Service;

use ColissimoLabel\Request\Helper\APIConfiguration;
use ColissimoLabel\Request\Helper\Article;
use ColissimoLabel\Request\LabelRequest;
use ColissimoLabel\Response\BordereauResponse;
use ColissimoLabel\Response\LabelResponse;
use SimpleXMLElement;
use SoapClient;
use SoapFault;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class SOAPService
{
    /**
     * @throws SoapFault
     */
    public function callGenerateBordereauByParcelsNumbersAPI(APIConfiguration $APIConfiguration, $parcelNumbers = []): BordereauResponse
    {
        //+ Generate SOAPRequest
        $xml = new SimpleXMLElement('<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" />');
        $xml->addChild('soapenv:Header');
        $children = $xml->addChild('soapenv:Body');
        $children = $children->addChild('sls:generateBordereauByParcelsNumbers', null, 'http://sls.ws.coliposte.fr');
        $children->addChild('contractNumber', $APIConfiguration->getContractNumber(), '');
        $children->addChild('password', $APIConfiguration->getPassword(), '');
        $children = $children->addChild('generateBordereauParcelNumberList', null, '');

        foreach ($parcelNumbers as $parcelNumber) {
            $children->addChild('parcelsNumbers', $parcelNumber, '');
        }

        $soap = new SoapClient($APIConfiguration->getWsdl());

        return new BordereauResponse($soap->__doRequest(
            $xml->asXML(),
            $APIConfiguration->getWsdl(),
            $APIConfiguration->getMethod(),
            $APIConfiguration->getVersion(),
            0
        ));
    }

    /**
     * @throws SoapFault
     */
    public function callAPI(APIConfiguration $APIConfiguration, LabelRequest $request): LabelResponse
    {
        $request->setContractNumber($APIConfiguration->getContractNumber());
        $request->setPassword($APIConfiguration->getPassword());

        //+ Generate SOAPRequest
        $xml = new SimpleXMLElement('<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" />');
        $xml->addChild('soapenv:Header');
        $children = $xml->addChild('soapenv:Body');
        $children = $children->addChild('sls:generateLabel', null, 'http://sls.ws.coliposte.fr');
        $children = $children->addChild('generateLabelRequest', null, '');

        $this->arrayToXml($request->generateArrayRequest(), $children, $request->getLetter()->getCustomsDeclarations()->getArticles());

        $soap = new SoapClient($APIConfiguration->getWsdl());

        return new LabelResponse($soap->__doRequest(
            $xml->asXML(),
            $APIConfiguration->getWsdl(),
            $APIConfiguration->getMethod(),
            $APIConfiguration->getVersion(),
            0
        ));
    }

    protected function arrayToXml(array $soapRequest, SimpleXMLElement $soapRequestXml, $articles): void
    {
        foreach ($soapRequest as $key => $value) {
            if (empty($value)) {
                continue;
            }
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subnode = $soapRequestXml->addChild($key);
                } else {
                    $subnode = $soapRequestXml->addChild('item'.$key);
                }
                $this->arrayToXml($value, $subnode, $articles);
            } else {
                if ('article' === $key) {
                    /** @var Article $article */
                    foreach ($articles as $article) {
                        $xmlArticle = $soapRequestXml->addChild($key);
                        $xmlArticle->addChild('description', $article->getDescription());
                        $xmlArticle->addChild('quantity', $article->getQuantity());
                        $xmlArticle->addChild('weight', $article->getWeight());
                        $xmlArticle->addChild('value', $article->getValue());
                        $xmlArticle->addChild('hsCode', $article->getHsCode());
                        $xmlArticle->addChild('originCountry', $article->getOriginCountry());
                        $xmlArticle->addChild('currency', $article->getCurrency());
                    }
                } else {
                    $soapRequestXml->addChild($key, htmlspecialchars($value));
                }
            }
        }
    }
}
