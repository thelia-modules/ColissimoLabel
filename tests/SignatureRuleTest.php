<?php

namespace ColissimoLabel\Tests;

use ColissimoLabel\ColissimoLabel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SignatureRuleTest extends TestCase
{
    public static function countries(): iterable
    {
        yield 'France' => ['250', true];
        yield 'Germany' => ['276', true];
        yield 'Guyane, a DOMTOM' => ['254', true];
        yield 'United States' => ['840', false];
        yield 'Switzerland, outside the EU' => ['756', true];
        yield 'Japan' => ['392', false];
        yield 'Austria, migrated code without leading zero' => ['40', true];
        yield 'Belgium, migrated code without leading zero' => ['56', true];
        yield 'France, padded with spaces' => [' 250 ', true];
        yield 'unknown country' => ['', false];
    }

    #[DataProvider('countries')]
    public function testOnlyEuropeAndDomtomMayGoWithoutSignature(string $numericIsoCode, bool $mayGoWithoutSignature): void
    {
        self::assertSame($mayGoWithoutSignature, ColissimoLabel::canCountryBeNotSigned($numericIsoCode));
    }
}
