<?php

namespace ColissimoLabel\Request\Helper;

use ColissimoLabel\Exception\InvalidArgumentException;

/**
 * @author Gilles Bourgeat >gilles.bourgeat@gmail.com>
 */
class OutputFormat
{
    const OUTPUT_PRINTING_TYPE = [
        0 => 'ZPL_10x15_203dpi',
        1 => 'ZPL_10x15_300dpi',
        2 => 'DPL_10x15_203dpi',
        3 => 'DPL_10x15_300dpi',
        4 => 'PDF_10x15_300dpi',
        5 => 'PDF_A4_300dpi'
    ];

    /** Default label format is : PDF_10x15_300dpi */
    const OUTPUT_PRINTING_TYPE_DEFAULT = 4;

    protected $x = 0;

    protected $y = 0;

    protected $outputPrintingType = self::OUTPUT_PRINTING_TYPE_DEFAULT;

    /**
     * @return int
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @param int $x
     * @return self
     */
    public function setX($x)
    {
        $this->x = (int) $x;
        return $this;
    }

    /**
     * @return int
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @param int $y
     * @return self
     */
    public function setY($y)
    {
        $this->y = (int) $y;
        return $this;
    }

    /**
     * @return string value of the list ColissimoAPI\Request\Helper\LabelOutputFormat::OUTPUT_PRINTING_TYPE
     */
    public function getOutputPrintingType()
    {
        return $this->outputPrintingType;
    }

    /**
     * @param string $outputPrintingType value of the list ColissimoAPI\Request\Helper\LabelOutputFormat::OUTPUT_PRINTING_TYPE
     * @return self
     */
    public function setOutputPrintingType($outputPrintingType)
    {
        if (\in_array($outputPrintingType, self::OUTPUT_PRINTING_TYPE)) {
            new InvalidArgumentException('Invalid value "' . $outputPrintingType . '" for argument $outputPrintingType');
        }

        $this->outputPrintingType = $outputPrintingType;
        return $this;
    }
}
