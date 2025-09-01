<?php

declare(strict_types=1);

namespace App\Tests\Unit\Utils;

use App\Utils\JsonDecoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(JsonDecoder::class)]
class JsonDecoderTest extends TestCase
{
    /**
     * @param mixed[] $expectedData
     */
    #[DataProvider('providerDecode')]
    public function testDecode(string $json, array $expectedData): void
    {
        $this->assertEquals(
            $expectedData,
            JsonDecoder::decode($json)
        );
    }

    /**
     * @return string[][]|string[][][]|string[][][][]|string[][][][][][]
     */
    public static function providerDecode(): array
    {
        return [
            [
                self::getOneColorJson(),
                [
                    'color' => '#66bb6a',
                    'name' => 'Yes',
                    'frenchName' => 'Oui',
                    'slug' => 'yes',
                ],
            ],
            [
                '{}',
                [
                ],
            ],
            [
                '[]',
                [
                ],
            ],
            [
                self::getManyColorsJson(),
                [
                    [
                        'color' => '#e57373',
                        'name' => 'No',
                        'frenchName' => 'Non',
                        'slug' => 'no',
                    ],
                    [
                        'color' => '#9575cd',
                        'name' => 'To evolve',
                        'frenchName' => 'af. évoluer',
                        'slug' => 'toevolve',
                    ],
                    [
                        'color' => '#4fc3f7',
                        'name' => 'To breed',
                        'frenchName' => 'af. reproduire',
                        'slug' => 'tobreed',
                    ],
                    [
                        'color' => '#ffd54f',
                        'name' => 'To transfer',
                        'frenchName' => 'à transférer',
                        'slug' => 'totransfer',
                    ],
                    [
                        'color' => '#ff9100',
                        'name' => 'To trade',
                        'frenchName' => 'À échanger',
                        'slug' => 'totrade',
                    ],
                    [
                        'color' => '#66bb6a',
                        'name' => 'Yes',
                        'frenchName' => 'Oui',
                        'slug' => 'yes',
                    ],
                ],
            ],
            [
                self::getMaxDepthJson(),
                [
                    'lvl1' => [
                        'lvl2' => [
                            'lvl3' => [
                                'value' => 'Douze',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('providerDecodeException')]
    public function testDecodeException(string $json): void
    {
        $this->expectException(\JsonException::class);

        JsonDecoder::decode($json);
    }

    /**
     * @return string[][]
     */
    public static function providerDecodeException(): array
    {
        return [
            [''],
            [self::getMaxDepthPlusOneJson()],
        ];
    }

    private static function getOneColorJson(): string
    {
        return <<<'JSON'
            {
                "color":"#66bb6a",
                "name":"Yes",
                "frenchName":"Oui",
                "slug":"yes"
            }
            JSON;
    }

    private static function getManyColorsJson(): string
    {
        return <<<'JSON'
            [
                {
                    "color":"#e57373",
                    "name":"No",
                    "frenchName":"Non",
                    "slug":"no"
                },
                {
                    "color":"#9575cd",
                    "name":"To evolve",
                    "frenchName":"af. \u00e9voluer",
                    "slug":"toevolve"
                },
                {
                    "color":"#4fc3f7",
                    "name":"To breed",
                    "frenchName":"af. reproduire",
                    "slug":"tobreed"
                },
                {
                    "color":"#ffd54f",
                    "name":"To transfer",
                    "frenchName":"\u00e0 transf\u00e9rer",
                    "slug":"totransfer"
                },
                {
                    "color":"#ff9100",
                    "name":"To trade",
                    "frenchName":"\u00c0 \u00e9changer",
                    "slug":"totrade"
                },
                {
                    "color":"#66bb6a",
                    "name":"Yes",
                    "frenchName":"Oui",
                    "slug":"yes"
                }
            ]
            JSON;
    }

    private static function getMaxDepthJson(): string
    {
        return <<<'JSON'
            {
                "lvl1": {
                    "lvl2": {
                        "lvl3": {
                            "value": "Douze"
                        }
                    }
                }
            }
            JSON;
    }

    private static function getMaxDepthPlusOneJson(): string
    {
        return <<<'JSON'
            {
                "lvl1": {
                    "lvl2": {
                        "lvl3": {
                            "lvl4": {
                                "value": "Douze"
                            }
                        }
                    }
                }
            }
            JSON;
    }
}
