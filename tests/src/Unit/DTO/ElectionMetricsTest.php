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
                'view_count' => ['sum' => 82, 'max' => 42],
                'win_count' => ['sum' => 54, 'max' => 52],
                'completion' => ['under_max_count' => 62, 'at_max_count' => 27],
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

    /**
     * @param array<string, mixed> $values
     */
    #[DataProvider('providerMissingProperty')]
    public function testMissingProperty(array $values): void
    {
        $this->expectException(MissingOptionsException::class);

        /**
         * @psalm-suppress ArgumentTypeCoercion
         *
         * @phpstan-ignore argument.type
         */
        ElectionMetrics::createFromArray($values);
    }

    /**
     * @return array<string, array{values: array<string, mixed>}>
     */
    public static function providerMissingProperty(): array
    {
        $paths = [
            ['view_count'],
            ['view_count', 'sum'],
            ['view_count', 'max'],
            ['win_count'],
            ['win_count', 'sum'],
            ['win_count', 'max'],
            ['completion'],
            ['completion', 'under_max_count'],
            ['completion', 'at_max_count'],
            ['dex_total_count'],
            ['round_count'],
            ['winner_average'],
            ['total_round_count'],
        ];

        $cases = [];
        foreach ($paths as $path) {
            $cases['missing_'.implode('_', $path)] = ['values' => self::removePath($path)];
        }

        return $cases;
    }

    /**
     * @param array<string, mixed> $values
     */
    #[DataProvider('providerBadValue')]
    public function testBadValue(array $values): void
    {
        $this->expectException(InvalidOptionsException::class);

        /**
         * @psalm-suppress ArgumentTypeCoercion
         *
         * @phpstan-ignore argument.type
         */
        ElectionMetrics::createFromArray($values);
    }

    /**
     * @return array<string, array{values: array<string, mixed>}>
     */
    public static function providerBadValue(): array
    {
        $paths = [
            ['view_count'],
            ['view_count', 'sum'],
            ['view_count', 'max'],
            ['win_count'],
            ['win_count', 'sum'],
            ['win_count', 'max'],
            ['completion'],
            ['completion', 'under_max_count'],
            ['completion', 'at_max_count'],
            ['dex_total_count'],
            ['round_count'],
            ['winner_average'],
            ['total_round_count'],
        ];

        $cases = [];
        foreach ($paths as $path) {
            $cases['bad_'.implode('_', $path)] = ['values' => self::corruptPath($path)];
        }

        return $cases;
    }

    /**
     * @return array<string, mixed>
     */
    private static function validData(): array
    {
        return [
            'view_count' => ['sum' => 82, 'max' => 42],
            'win_count' => ['sum' => 54, 'max' => 52],
            'completion' => ['under_max_count' => 62, 'at_max_count' => 27],
            'dex_total_count' => 50,
            'round_count' => 7,
            'winner_average' => 7.71,
            'total_round_count' => 13,
        ];
    }

    /**
     * @param list<string> $path
     *
     * @return array<string, mixed>
     */
    private static function removePath(array $path): array
    {
        $data = self::validData();

        if (1 === \count($path)) {
            unset($data[$path[0]]);

            return $data;
        }

        /** @var array<string, mixed> $sub */
        $sub = $data[$path[0]];
        unset($sub[$path[1]]);
        $data[$path[0]] = $sub;

        return $data;
    }

    /**
     * @param list<string> $path
     *
     * @return array<string, mixed>
     */
    private static function corruptPath(array $path): array
    {
        $data = self::validData();

        if (1 === \count($path)) {
            $data[$path[0]] = 'not-an-array';

            return $data;
        }

        /** @var array<string, mixed> $sub */
        $sub = $data[$path[0]];
        $sub[$path[1]] = 'not-an-int';
        $data[$path[0]] = $sub;

        return $data;
    }
}
