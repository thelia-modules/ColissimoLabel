<?php

namespace ColissimoLabel\Tests;

use ColissimoLabel\ColissimoLabel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ContractPasswordTest extends TestCase
{
    public static function submissions(): iterable
    {
        yield 'empty field keeps the saved password' => ['', 'saved', 'saved'];
        yield 'missing field keeps the saved password' => [null, 'saved', 'saved'];
        yield 'new password replaces the saved one' => ['new', 'saved', 'new'];
        yield 'first password is saved' => ['new', null, 'new'];
        yield 'nothing saved and nothing typed' => ['', null, null];
    }

    #[DataProvider('submissions')]
    public function testTheConfigurationNeverErasesTheSavedPassword(?string $submitted, ?string $saved, ?string $expected): void
    {
        self::assertSame($expected, ColissimoLabel::passwordToSave($submitted, $saved));
    }
}
