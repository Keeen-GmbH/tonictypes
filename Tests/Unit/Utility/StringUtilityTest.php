<?php

declare(strict_types=1);

namespace K3n\Tonictypes\Tests\Unit\Utility;

use K3n\Tonictypes\Utility\StringUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StringUtilityTest extends TestCase
{
    public function testExplodeSeparatedStringReturnsEmptyArrayForEmptyString(): void
    {
        self::assertSame([], StringUtility::explodeSeparatedString(''));
    }

    #[DataProvider('explodeSeparatedStringProvider')]
    public function testExplodeSeparatedStringSplitsAndTrims(string $input, array $expected): void
    {
        self::assertSame($expected, StringUtility::explodeSeparatedString($input));
    }

    public static function explodeSeparatedStringProvider(): array
    {
        return [
            'comma separated' => ['a, b, c', ['a', 'b', 'c']],
            'semicolon separated' => ['a; b; c', ['a', 'b', 'c']],
            'dot separated' => ['a.b.c', ['a', 'b', 'c']],
            'mixed separators' => ['a;b.c,d', ['a', 'b', 'c', 'd']],
        ];
    }

    public function testExplodeSeparatedStringRespectsAllowedSeparators(): void
    {
        self::assertSame(
            ['a', 'b', 'c'],
            StringUtility::explodeSeparatedString('a|b|c', ['|'])
        );
    }

    #[DataProvider('createCodeFromStringProvider')]
    public function testCreateCodeFromString(string $input, string $expected): void
    {
        self::assertSame($expected, StringUtility::createCodeFromString($input));
    }

    public static function createCodeFromStringProvider(): array
    {
        return [
            'simple label' => ['My Field Label', 'myfieldlabel'],
            'umlauts' => ['Größe & Änderung', 'groesseaenderung'],
            'leading digit' => ['123 Items', 'i123items'],
            'special characters' => ['foo@bar (test)', 'fooatbartest'],
        ];
    }
}
