<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ElectionMetrics;
use App\DTO\ElectionMetricsCompletion;
use App\DTO\ElectionMetricsCounts;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * @internal
 */
#[CoversClass(ElectionMetrics::class)]
#[CoversClass(ElectionMetricsCounts::class)]
#[CoversClass(ElectionMetricsCompletion::class)]
final class ElectionMetricsTest extends TestCase
{
    #[Test]
    public function propertiesAreReadonly(): void
    {
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'viewCount'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'winCount'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'completion'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'dexTotalCount'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'roundCount'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'winnerAverage'))->isReadOnly());
        $this->assertTrue((new \ReflectionProperty(ElectionMetrics::class, 'totalRoundCount'))->isReadOnly());
    }

    #[Test]
    public function everythingOK(): void
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

        $this->assertSame(82, $object->getViewCount()->getSum());
        $this->assertSame(42, $object->getViewCount()->getMax());
        $this->assertSame(54, $object->getWinCount()->getSum());
        $this->assertSame(52, $object->getWinCount()->getMax());
        $this->assertSame(62, $object->getCompletion()->getUnderMaxCount());
        $this->assertSame(27, $object->getCompletion()->getAtMaxCount());
        $this->assertSame(50, $object->getDexTotalCount());
        $this->assertSame(7, $object->getRoundCount());
        $this->assertSame(7.71, $object->getWinnerAverage());
        $this->assertSame(13, $object->getTotalRoundCount());
    }

    #[Test]
    public function winnerAverageAcceptsInt(): void
    {
        $object = ElectionMetrics::createFromArray(
            [
                'view_count' => ['sum' => 5, 'max' => 1],
                'win_count' => ['sum' => 10, 'max' => 1],
                'completion' => ['under_max_count' => 15, 'at_max_count' => 15],
                'dex_total_count' => 21,
                'round_count' => 3,
                'winner_average' => 2,
                'total_round_count' => 7,
            ],
        );

        $this->assertSame(2.0, $object->getWinnerAverage());
    }

    /**
     * @param array<string, mixed> $values
     */
    #[DataProvider('providerMissingTopLevelProperty')]
    #[Test]
    public function missingTopLevelProperty(array $values): void
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
    public static function providerMissingTopLevelProperty(): array
    {
        $topLevelKeys = [
            'view_count',
            'win_count',
            'completion',
            'dex_total_count',
            'round_count',
            'winner_average',
            'total_round_count',
        ];

        $cases = [];
        foreach ($topLevelKeys as $key) {
            $data = self::validData();
            unset($data[$key]);
            $cases['missing_'.$key] = ['values' => $data];
        }

        return $cases;
    }

    /**
     * @param array<string, mixed> $values
     */
    #[DataProvider('providerBadTopLevelType')]
    #[Test]
    public function badTopLevelType(array $values): void
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
    public static function providerBadTopLevelType(): array
    {
        $topLevelKeys = [
            'view_count',
            'win_count',
            'completion',
            'dex_total_count',
            'round_count',
            'winner_average',
            'total_round_count',
        ];

        $cases = [];
        foreach ($topLevelKeys as $key) {
            $data = self::validData();
            $data[$key] = 'not-valid';
            $cases['bad_'.$key] = ['values' => $data];
        }

        return $cases;
    }

    /**
     * @param array<string, mixed> $values
     */
    #[DataProvider('providerMissingSubKey')]
    #[Test]
    public function missingSubKey(array $values): void
    {
        $this->expectException(\InvalidArgumentException::class);

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
    public static function providerMissingSubKey(): array
    {
        $subPaths = [
            ['view_count', 'sum'],
            ['view_count', 'max'],
            ['win_count', 'sum'],
            ['win_count', 'max'],
            ['completion', 'under_max_count'],
            ['completion', 'at_max_count'],
        ];

        $cases = [];
        foreach ($subPaths as $path) {
            $data = self::validData();

            /** @var array<string, mixed> $sub */
            $sub = $data[$path[0]];
            unset($sub[$path[1]]);
            $data[$path[0]] = $sub;
            $cases['missing_'.$path[0].'_'.$path[1]] = ['values' => $data];
        }

        return $cases;
    }

    /**
     * @param array<string, mixed> $values
     */
    #[DataProvider('providerBadSubKeyType')]
    #[Test]
    public function badSubKeyType(array $values): void
    {
        $this->expectException(\InvalidArgumentException::class);

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
    public static function providerBadSubKeyType(): array
    {
        $subPaths = [
            ['view_count', 'sum'],
            ['view_count', 'max'],
            ['win_count', 'sum'],
            ['win_count', 'max'],
            ['completion', 'under_max_count'],
            ['completion', 'at_max_count'],
        ];

        $cases = [];
        foreach ($subPaths as $path) {
            $data = self::validData();

            /** @var array<string, mixed> $sub */
            $sub = $data[$path[0]];
            $sub[$path[1]] = 'not-an-int';
            $data[$path[0]] = $sub;
            $cases['bad_'.$path[0].'_'.$path[1]] = ['values' => $data];
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
}
