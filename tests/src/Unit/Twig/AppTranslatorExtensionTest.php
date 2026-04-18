<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Twig\AppTranslatorExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\TwigFilter;

/**
 * @internal
 */
#[CoversClass(AppTranslatorExtension::class)]
final class AppTranslatorExtensionTest extends TestCase
{
    public function testGetFilters(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $extension = new AppTranslatorExtension($translator);

        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);

        $almostExactlyFilter = $filters[0];

        $this->assertInstanceOf(TwigFilter::class, $almostExactlyFilter);
        $this->assertEquals('almost_exactly', $almostExactlyFilter->getName());

        /** @var mixed[] $almostExactlyFilterCallable */
        $almostExactlyFilterCallable = $almostExactlyFilter->getCallable();
        $this->assertCount(2, $almostExactlyFilterCallable);
        $this->assertInstanceOf(AppTranslatorExtension::class, $almostExactlyFilterCallable[0]);
        $this->assertEquals('almostExactly', $almostExactlyFilterCallable[1]);
    }

    /**
     * @param array<string, int> $params
     */
    #[DataProvider('providerAlmostExactly')]
    public function testAlmostExactly(
        string $text,
        array $params,
        string $return,
        string $expected,
        float|int $value,
    ): void {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with($text, $params)
            ->willReturn($return)
        ;
        $extension = new AppTranslatorExtension($translator);

        $this->assertSame(
            $expected,
            $extension->almostExactly($value),
        );
    }

    /**
     * @return array<string, array{
     *  text: string,
     *  params: array<string, int>,
     *  return: string,
     *  expected: string,
     *  value: float,
     * }>
     */
    public static function providerAlmostExactly(): array
    {
        return array_merge(
            self::getProviderExactlyData(),
            self::getProviderAlmostData(),
            self::getProviderApproximatelyData(),
            self::getProviderBetweenData(),
        );
    }

    /**
     * @return array<string, array{
     *  text: string,
     *  params: array<string, int>,
     *  return: string,
     *  expected: string,
     *  value: float,
     * }>
     */
    public static function getProviderExactlyData(): array
    {
        return [
            'exactly4' => [
                'text' => 'number.exactly',
                'params' => [
                    'number' => 4,
                ],
                'return' => 'exactly_number4',
                'expected' => 'exactly_number4',
                'value' => 4,
            ],
            'exactly5' => [
                'text' => 'number.exactly',
                'params' => [
                    'number' => 5,
                ],
                'return' => 'exactly_number5',
                'expected' => 'exactly_number5',
                'value' => 5,
            ],
        ];
    }

    /**
     * @return array<string, array{
     *  text: string,
     *  params: array<string, int>,
     *  return: string,
     *  expected: string,
     *  value: float,
     * }>
     */
    public static function getProviderAlmostData(): array
    {
        return [
            'almost.1' => [
                'text' => 'number.almost',
                'params' => [
                    'number' => 4,
                ],
                'return' => 'almost_number4',
                'expected' => 'almost_number4',
                'value' => 4.1,
            ],
            'almost.2' => [
                'text' => 'number.almost',
                'params' => [
                    'number' => 4,
                ],
                'return' => 'almost_number4',
                'expected' => 'almost_number4',
                'value' => 4.2,
            ],
            'almost.8' => [
                'text' => 'number.almost',
                'params' => [
                    'number' => 5,
                ],
                'return' => 'almost_number5',
                'expected' => 'almost_number5',
                'value' => 4.8,
            ],
            'almost.9' => [
                'text' => 'number.almost',
                'params' => [
                    'number' => 5,
                ],
                'return' => 'almost_number5',
                'expected' => 'almost_number5',
                'value' => 4.9,
            ],
        ];
    }

    /**
     * @return array<string, array{
     *  text: string,
     *  params: array<string, int>,
     *  return: string,
     *  expected: string,
     *  value: float,
     * }>
     */
    public static function getProviderApproximatelyData(): array
    {
        return [
            'approximately.25' => [
                'text' => 'number.approximately',
                'params' => [
                    'number' => 4,
                ],
                'return' => 'approximately_number4',
                'expected' => 'approximately_number4',
                'value' => 4.25,
            ],
            'approximately.3' => [
                'text' => 'number.approximately',
                'params' => [
                    'number' => 4,
                ],
                'return' => 'approximately_number4',
                'expected' => 'approximately_number4',
                'value' => 4.3,
            ],
            'approximately.4' => [
                'text' => 'number.approximately',
                'params' => [
                    'number' => 4,
                ],
                'return' => 'approximately_number4',
                'expected' => 'approximately_number4',
                'value' => 4.4,
            ],
            'approximately.6' => [
                'text' => 'number.approximately',
                'params' => [
                    'number' => 5,
                ],
                'return' => 'approximately_number5',
                'expected' => 'approximately_number5',
                'value' => 4.6,
            ],
            'approximately.7' => [
                'text' => 'number.approximately',
                'params' => [
                    'number' => 5,
                ],
                'return' => 'approximately_number5',
                'expected' => 'approximately_number5',
                'value' => 4.7,
            ],
            'approximately.75' => [
                'text' => 'number.approximately',
                'params' => [
                    'number' => 5,
                ],
                'return' => 'approximately_number5',
                'expected' => 'approximately_number5',
                'value' => 4.75,
            ],
        ];
    }

    /**
     * @return array<string, array{
     *  text: string,
     *  params: array<string, int>,
     *  return: string,
     *  expected: string,
     *  value: float,
     * }>
     */
    public static function getProviderBetweenData(): array
    {
        return [
            'between.5' => [
                'text' => 'number.between',
                'params' => [
                    'low' => 4,
                    'high' => 5,
                ],
                'return' => 'between_low4_high5',
                'expected' => 'between_low4_high5',
                'value' => 4.5,
            ],
        ];
    }
}
