<?php

declare(strict_types=1);

namespace K3n\Tonictypes\Tests\Unit\Utility;

use K3n\Tonictypes\Utility\ArrayUtility;
use PHPUnit\Framework\TestCase;

final class ArrayUtilityTest extends TestCase
{
    public function testGetArrayValueByPath(): void
    {
        $array = [
            'foo' => [
                'bar' => [
                    'baz' => 'value',
                ],
            ],
        ];

        self::assertSame('value', ArrayUtility::getArrayValueByPath($array, 'foo.bar.baz'));
    }

    public function testRecursiveFindKey(): void
    {
        $array = [
            'a' => 1,
            'nested' => [
                'target' => 'first',
                'deeper' => [
                    'target' => 'second',
                ],
            ],
        ];

        self::assertSame(['first', 'second'], ArrayUtility::recursiveFindKey('target', $array));
    }

    public function testIsMultidimensional(): void
    {
        self::assertFalse(ArrayUtility::isMultidimensional(['a', 'b', 'c']));
        self::assertTrue(ArrayUtility::isMultidimensional(['a', ['b']]));
    }

    public function testLowercaseArrayKeys(): void
    {
        $input = [
            'Foo' => [
                'Bar' => 'value',
            ],
        ];

        self::assertSame(
            [
                'foo' => [
                    'bar' => 'value',
                ],
            ],
            ArrayUtility::lowercaseArrayKeys($input)
        );
    }

    public function testCreateTree(): void
    {
        $flat = [
            ['id' => 1, 'parent' => 0, 'label' => 'Root'],
            ['id' => 2, 'parent' => 1, 'label' => 'Child'],
            ['id' => 3, 'parent' => 1, 'label' => 'Sibling'],
        ];

        $tree = ArrayUtility::createTree($flat);

        self::assertArrayHasKey(1, $tree);
        self::assertSame('Root', $tree[1]['label']);
        self::assertCount(2, $tree[1]['children']);
        self::assertSame('Child', $tree[1]['children'][2]['label']);
        self::assertSame('Sibling', $tree[1]['children'][3]['label']);
    }

    public function testArrayColumnMulti(): void
    {
        $input = [
            ['uid' => 1, 'title' => 'A', 'hidden' => 0],
            ['uid' => 2, 'title' => 'B', 'hidden' => 1],
        ];

        self::assertSame(
            [
                ['uid' => 1, 'title' => 'A'],
                ['uid' => 2, 'title' => 'B'],
            ],
            ArrayUtility::array_column_multi($input, ['uid', 'title'])
        );
    }
}
