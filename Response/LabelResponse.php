<?php

namespace ColissimoLabel\Response;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class LabelResponse
{
    const UUID = '/--uuid:/'; //This is the separator of each part of the response
    const CONTENT = 'Content-';

    protected $soapResponse;
    protected $cacheAttachments = [];
    protected $cacheSoapResponse = [];
    protected $uuid;

    public function __construct($soapResponse)
    {
        $this->soapResponse = $soapResponse;

        $this->parseResponse($soapResponse);
    }

    public function getFile()
    {
        if ($this->isValid()) {
            return $this->cacheAttachments[0]["data"];
        }

        return null;
    }

    public function getParcelNumber()
    {
        if ($this->isValid()) {
            $pieces = explode("<parcelNumber>", $this->cacheSoapResponse["data"]);
            $pieces = explode("</parcelNumber>", $pieces[1]);

            return $pieces[0];
        }

        return null;
    }

    public function hasFileCN23()
    {
        if ($this->isValid()) {
            return isset($this->cacheAttachments[1]["data"]);
        }

        return false;
    }

    public function getFileCN23()
    {
        if ($this->isValid()) {
            if (\count($this->cacheAttachments) > 1) {
                $this->cacheAttachments[1]["data"];
            }
        }

        return null;
    }

    public function isValid()
    {
        if (!isset($this->cacheSoapResponse["data"])) {
            return false;
        }

        $soapResult = $this->cacheSoapResponse["data"];
        $errorCode = explode("<id>", $soapResult);
        $errorCode = explode("</id>", $errorCode[1]);
        //- Parse Web Service Response
        //+ Error handling and label saving
        if ($errorCode[0] == 0) {
            return true;
        }

        return false;
    }

    public function getError()
    {
        if (!isset($this->cacheSoapResponse["data"])) {
            return [$this->soapResponse];
        }

        if ($this->isValid()) {
            return [];
        }

        $soapResult = $this->cacheSoapResponse["data"];
        $errorCode = explode("<id>", $soapResult);
        $errorCode = explode("</id>", $errorCode[1]);

        $errorMessage = explode("<messageContent>", $this->cacheSoapResponse["data"]);
        $errorMessage = explode("</messageContent>", $errorMessage[1]);

        return [$errorCode[0] => $errorMessage];
    }

    protected function parseResponse($response)
    {
        $content = array ();
        $matches = array ();
        preg_match_all(self::UUID, $response, $matches, PREG_OFFSET_CAPTURE);

        for ($i = 0; $i < count($matches[0]) -1; $i++) {
            if ($i + 1 < count($matches[0])) {
                $content[$i] = substr($response, $matches[0][$i][1], $matches[0][$i + 1][1] - $matches[0][$i][1]);
            } else {
                $content[$i] = substr($response, $matches[0][$i][1], strlen($response));
            }
        }

        foreach ($content as $part) {
            if ($this->uuid == null) {
                $uuidStart = strpos($part, self::UUID, 0)+strlen(self::UUID);
                $uuidEnd = strpos($part, "\r\n", $uuidStart);
                $this->uuid = substr($part, $uuidStart, $uuidEnd-$uuidStart);
            }
            $header = $this->extractHeader($part);
            if (count($header) > 0) {
                if (false !== strpos($header['Content-Type'], 'type="text/xml"')) {
                    $this->cacheSoapResponse['header'] = $header;
                    $this->cacheSoapResponse['data'] = trim(substr($part, $header['offsetEnd']));
                } else {
                    $attachment['header'] = $header;
                    $attachment['data'] = trim(substr($part, $header['offsetEnd']));
                    array_push($this->cacheAttachments, $attachment);
                }
            }
        }

        return $this;
    }

    protected function extractHeader($part)
    {
        $header = array();
        $headerLineStart = strpos($part, self::CONTENT, 0);
        $endLine = 0;
        while (false !== $headerLineStart) {
            $header['offsetStart'] = $headerLineStart;
            $endLine = strpos($part, "\r\n", $headerLineStart);
            $headerLine = explode(': ', substr($part, $headerLineStart, $endLine-$headerLineStart));
            $header[$headerLine[0]] = $headerLine[1];
            $headerLineStart = strpos($part, self::CONTENT, $endLine);
        }
        $header['offsetEnd'] = $endLine;
        return $header;
    }
}
