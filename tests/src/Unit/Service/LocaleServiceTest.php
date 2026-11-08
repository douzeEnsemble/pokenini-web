<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\LocaleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[CoversClass(LocaleService::class)]
final class LocaleServiceTest extends TestCase
{
    #[DataProvider('providerCurrent')]
    #[Test]
    public function current(?string $requestLocale, string $expected): void
    {
        $this->assertSame($expected, $this->service($requestLocale)->current());
    }

    /**
     * @return array<string, array{requestLocale: null|string, expected: string}>
     */
    public static function providerCurrent(): array
    {
        return [
            'no request' => ['requestLocale' => null, 'expected' => 'en'],
            'enabled locale' => ['requestLocale' => 'fr', 'expected' => 'fr'],
            'other enabled locale' => ['requestLocale' => 'de', 'expected' => 'de'],
            'not enabled locale' => ['requestLocale' => 'ja', 'expected' => 'en'],
        ];
    }

    /**
     * @param null|array<string, null|string> $names
     */
    #[DataProvider('providerLocalize')]
    #[Test]
    public function localize(?array $names, string $requestLocale, string $expected): void
    {
        $this->assertSame($expected, $this->service($requestLocale)->localize($names));
    }

    /**
     * @return array<string, array{names: null|array<string, null|string>, requestLocale: string, expected: string}>
     */
    public static function providerLocalize(): array
    {
        return [
            'no names' => ['names' => null, 'requestLocale' => 'fr', 'expected' => ''],
            'no name at all' => ['names' => [], 'requestLocale' => 'fr', 'expected' => ''],
            'current locale' => ['names' => ['en' => 'Bulbasaur', 'fr' => 'Bulbizarre'], 'requestLocale' => 'fr', 'expected' => 'Bulbizarre'],
            'fallback locale as current' => ['names' => ['en' => 'Bulbasaur', 'fr' => 'Bulbizarre'], 'requestLocale' => 'en', 'expected' => 'Bulbasaur'],
            'current locale is missing' => ['names' => ['en' => 'Bulbasaur'], 'requestLocale' => 'fr', 'expected' => 'Bulbasaur'],
            'current locale is null' => ['names' => ['en' => 'Bulbasaur', 'fr' => null], 'requestLocale' => 'fr', 'expected' => 'Bulbasaur'],
            'current locale is empty' => ['names' => ['en' => 'Bulbasaur', 'fr' => ''], 'requestLocale' => 'fr', 'expected' => 'Bulbasaur'],
            'current and fallback locales are missing' => ['names' => ['de' => 'Bisasam'], 'requestLocale' => 'fr', 'expected' => ''],
            'a name resolved by the API' => ['names' => ['*' => 'Combat'], 'requestLocale' => 'fr', 'expected' => 'Combat'],
            'the current locale prevails over a resolved name' => ['names' => ['fr' => 'Bulbizarre', '*' => 'Combat'], 'requestLocale' => 'fr', 'expected' => 'Bulbizarre'],
            'the fallback locale prevails over a resolved name' => ['names' => ['en' => 'Bulbasaur', '*' => 'Combat'], 'requestLocale' => 'fr', 'expected' => 'Bulbasaur'],
            'an empty resolved name' => ['names' => ['*' => ''], 'requestLocale' => 'fr', 'expected' => ''],
            'the name is not a fallback of itself' => ['names' => ['en' => '', 'fr' => ''], 'requestLocale' => 'fr', 'expected' => ''],
        ];
    }

    #[Test]
    public function locales(): void
    {
        $this->assertSame(['en', 'fr', 'de'], $this->service('fr')->locales());
    }

    /**
     * @param null|array<string, null|string> $names
     * @param array<string, string>           $expected
     */
    #[DataProvider('providerOrdered')]
    #[Test]
    public function ordered(?array $names, string $requestLocale, array $expected): void
    {
        $ordered = $this->service($requestLocale)->ordered($names);

        $this->assertSame($expected, $ordered);
    }

    /**
     * @return array<string, array{names: null|array<string, null|string>, requestLocale: string, expected: array<string, string>}>
     */
    public static function providerOrdered(): array
    {
        $names = ['en' => 'Bulbasaur', 'fr' => 'Bulbizarre', 'de' => 'Bisasam'];

        return [
            'no names' => ['names' => null, 'requestLocale' => 'fr', 'expected' => []],
            'current first, then configured order' => [
                'names' => $names,
                'requestLocale' => 'fr',
                'expected' => ['fr' => 'Bulbizarre', 'en' => 'Bulbasaur', 'de' => 'Bisasam'],
            ],
            'current is the last configured one' => [
                'names' => $names,
                'requestLocale' => 'de',
                'expected' => ['de' => 'Bisasam', 'en' => 'Bulbasaur', 'fr' => 'Bulbizarre'],
            ],
            'current is the first configured one' => [
                'names' => $names,
                'requestLocale' => 'en',
                'expected' => ['en' => 'Bulbasaur', 'fr' => 'Bulbizarre', 'de' => 'Bisasam'],
            ],
            'empty and null names are dropped' => [
                'names' => ['en' => '', 'fr' => null, 'de' => 'Bisasam'],
                'requestLocale' => 'fr',
                'expected' => ['de' => 'Bisasam'],
            ],
            'locales which are not enabled are dropped' => [
                'names' => ['en' => 'Bulbasaur', 'ja' => 'フシギダネ'],
                'requestLocale' => 'fr',
                'expected' => ['en' => 'Bulbasaur'],
            ],
        ];
    }

    /**
     * @param array<string, list<string>> $lists
     * @param list<string>                $expected
     */
    #[DataProvider('providerLocalizeList')]
    #[Test]
    public function localizeList(array $lists, string $requestLocale, array $expected): void
    {
        $this->assertSame($expected, $this->service($requestLocale)->localizeList($lists));
    }

    /**
     * @return array<string, array{lists: array<string, list<string>>, requestLocale: string, expected: list<string>}>
     */
    public static function providerLocalizeList(): array
    {
        return [
            'no list' => ['lists' => [], 'requestLocale' => 'fr', 'expected' => []],
            'current locale' => ['lists' => ['en' => ['Red'], 'fr' => ['Rouge']], 'requestLocale' => 'fr', 'expected' => ['Rouge']],
            'fallback locale as current' => ['lists' => ['en' => ['Red'], 'fr' => ['Rouge']], 'requestLocale' => 'en', 'expected' => ['Red']],
            'several items in the current locale' => ['lists' => ['en' => ['Black', 'White'], 'fr' => ['Noir', 'Blanc']], 'requestLocale' => 'fr', 'expected' => ['Noir', 'Blanc']],
            'several items in the fallback locale' => ['lists' => ['en' => ['Black', 'White']], 'requestLocale' => 'fr', 'expected' => ['Black', 'White']],
            'current locale is missing' => ['lists' => ['en' => ['Red']], 'requestLocale' => 'fr', 'expected' => ['Red']],
            'current locale is empty' => ['lists' => ['en' => ['Red'], 'fr' => []], 'requestLocale' => 'fr', 'expected' => ['Red']],
            'current and fallback locales are missing' => ['lists' => ['de' => ['Rot']], 'requestLocale' => 'fr', 'expected' => []],
        ];
    }

    private function service(?string $requestLocale): LocaleService
    {
        $requestStack = new RequestStack();
        if (null !== $requestLocale) {
            $request = new Request();
            $request->setLocale($requestLocale);
            $requestStack->push($request);
        }

        return new LocaleService($requestStack, ['en', 'fr', 'de'], 'en');
    }
}
