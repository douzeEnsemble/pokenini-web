<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ElectionMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * @internal
 */
#[CoversClass(ElectionMetrics::class)]
final class ElectionMetricsTest extends TestCase
{
    public function testPropertiesAreReadonly(): void
    {
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'viewCountSum'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'winCountSum'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'viewCountMax'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'winCountMax'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'underMaxViewCount'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'maxViewCount'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'dexTotalCount'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'roundCount'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'winnerAverage'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'totalRoundCount'))->isReadOnly());
    }

    public function testOk(): void
    {
        $object = ElectionMetrics::createFromArray(
            [
                'view_count_sum' => 82,
                'win_count_sum' => 54,
                'view_count_max' => 42,
                'win_count_max' => 52,
                'under_max_view_count' => 62,
                'max_view_count' => 27,
                'dex_total_count' => 50,
                'round_count' => 7,
                'winner_average' => 7.71,
                'total_round_count' => 13,
            ],
        );

        $this->assertSame(82, $object->viewCountSum);
        $this->assertSame(54, $object->winCountSum);
        $this->assertSame(42, $object->viewCountMax);
        $this->assertSame(52, $object->winCountMax);
        $this->assertSame(62, $object->underMaxViewCount);
        $this->assertSame(27, $object->maxViewCount);
        $this->assertSame(50, $object->dexTotalCount);
        $this->assertSame(7, $object->roundCount);
        $this->assertSame(7.71, $object->winnerAverage);
        $this->assertSame(13, $object->totalRoundCount);
    }

    #[DataProvider('providerMissingProperty')]
    public function testMissingProperty(string $missingProperty): void
    {
        $properties = [
            'view_count_sum' => '1',
            'win_count_sum' => 2,
            'view_count_max' => 3,
            'win_count_max' => 4,
            'under_max_view_count' => 5,
            'max_view_count' => 6,
            'dex_total_count' => 50,
        ];

        unset($properties[$missingProperty]);

        $this->expectException(MissingOptionsException::class);

        /**
         * @psalm-suppress InvalidArgument
         *
         * @phpstan-ignore argument.type
         */
        ElectionMetrics::createFromArray($properties);
    }

    /**
     * @return array<string, array{missingProperty: string}>
     */
    public static function providerMissingProperty(): array
    {
        return [
            'missing_view_count_sum' => [
                'missingProperty' => 'view_count_sum',
            ],
            'missing_win_count_sum' => [
                'missingProperty' => 'win_count_sum',
            ],
            'missing_view_count_max' => [
                'missingProperty' => 'view_count_max',
            ],
            'missing_win_count_max' => [
                'missingProperty' => 'win_count_max',
            ],
            'missing_under_max_view_count' => [
                'missingProperty' => 'under_max_view_count',
            ],
            'missing_max_view_count' => [
                'missingProperty' => 'max_view_count',
            ],
            'missing_dex_total_count' => [
                'missingProperty' => 'dex_total_count',
            ],
            'missing_round_count' => [
                'missingProperty' => 'round_count',
            ],
            'missing_winner_average' => [
                'missingProperty' => 'winner_average',
            ],
            'missing_total_round_count' => [
                'missingProperty' => 'total_round_count',
            ],
        ];
    }

    #[DataProvider('providerBadValue')]
    public function testBadValue(string $badlyTypedProperty): void
    {
        $properties = [
            'view_count_sum' => 82,
            'win_count_sum' => 54,
            'view_count_max' => 42,
            'win_count_max' => 52,
            'under_max_view_count' => 62,
            'max_view_count' => 27,
            'dex_total_count' => 50,
            'round_count' => 7,
            'winner_average' => 7.71,
            'total_round_count' => 13,
        ];

        $properties[$badlyTypedProperty] = (string) $properties[$badlyTypedProperty];

        $this->expectException(InvalidOptionsException::class);

        /**
         * @psalm-suppress InvalidArgument
         *
         * @phpstan-ignore argument.type
         */
        ElectionMetrics::createFromArray($properties);
    }

    /**
     * @return array<string, array{badlyTypedProperty: string}>
     */
    public static function providerBadValue(): array
    {
        return [
            'badly_typed_view_count_sum' => [
                'badlyTypedProperty' => 'view_count_sum',
            ],
            'badly_typed_win_count_sum' => [
                'badlyTypedProperty' => 'win_count_sum',
            ],
            'badly_typed_view_count_max' => [
                'badlyTypedProperty' => 'view_count_max',
            ],
            'badly_typed_win_count_max' => [
                'badlyTypedProperty' => 'win_count_max',
            ],
            'badly_typed_under_max_view_count' => [
                'badlyTypedProperty' => 'under_max_view_count',
            ],
            'badly_typed_max_view_count' => [
                'badlyTypedProperty' => 'max_view_count',
            ],
            'badly_typed_dex_total_count' => [
                'badlyTypedProperty' => 'dex_total_count',
            ],
            'badly_typed_round_count' => [
                'badlyTypedProperty' => 'round_count',
            ],
            'badly_typed_winner_average' => [
                'badlyTypedProperty' => 'winner_average',
            ],
            'badly_typed_total_round_count' => [
                'badlyTypedProperty' => 'total_round_count',
            ],
        ];
    }
}
