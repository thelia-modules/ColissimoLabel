<?php

namespace ColissimoLabel\Service;

use ColissimoLabel\Request\AbstractRequest;
use ColissimoLabel\Request\Helper\APIConfiguration;
use ColissimoLabel\Response\LabelResponse;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class SOAPService
{
    public function callAPI(APIConfiguration $APIConfiguration, AbstractRequest $request)
    {
        $request->setContractNumber($APIConfiguration->getContractNumber());
        $request->setPassword($APIConfiguration->getPassword());

        //+ Generate SOAPRequest
        $xml = new \SimpleXMLElement('<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" />');
        $xml->addChild("soapenv:Header");
        $children = $xml->addChild("soapenv:Body");
        $children = $children->addChild("sls:generateLabel", null, 'http://sls.ws.coliposte.fr');
        $children = $children->addChild("generateLabelRequest", null, "");

        $this->arrayToXml($request->generateArrayRequest(), $children);

        $soap = new \SoapClient($APIConfiguration->getWsdl());

        return new LabelResponse($soap->__doRequest(
            $xml->asXML(),
            $APIConfiguration->getWsdl(),
            $APIConfiguration->getMethod(),
            $APIConfiguration->getVersion(),
            0
        ));
    }

    protected function arrayToXml(array $soapRequest, \SimpleXMLElement $soapRequestXml)
    {
        foreach ($soapRequest as $key => $value) {
            if ($value === null || empty($value)) {
                continue;
            }
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subnode = $soapRequestXml->addChild($key);
                    $this->arrayToXml($value, $subnode);
                } else {
                    $subnode = $soapRequestXml->addChild("item" . $key);
                    $this->arrayToXml($value, $subnode);
                }
            } else {
                $soapRequestXml->addChild($key, htmlspecialchars($value));
            }
        }
    }
}
