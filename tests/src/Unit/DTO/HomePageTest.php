<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\HomePage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(HomePage::class)]
final class HomePageTest extends TestCase
{
    #[Test]
    #[DataProvider('providerPages')]
    public function knownPage(string $value, string $route, bool $isHub): void
    {
        $homePage = new HomePage($value);

        $this->assertSame($value, $homePage->value);
        $this->assertSame($route, $homePage->getRoute());
        $this->assertSame($isHub, $homePage->isHub());
        $this->assertTrue(HomePage::isValid($value));
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function providerPages(): array
    {
        return [
            'hub' => ['hub', 'app_home_hub', true],
            'album' => ['album', 'app_albumdexlist_index', false],
            'election' => ['election', 'app_electiondexlist_index', false],
            'game' => ['game', 'app_game_index', false],
        ];
    }

    #[Test]
    #[DataProvider('providerUnknownValues')]
    public function unknownPageIsTheHub(string $value): void
    {
        $homePage = new HomePage($value);

        $this->assertSame('hub', $homePage->value);
        $this->assertSame('app_home_hub', $homePage->getRoute());
        $this->assertTrue($homePage->isHub());
        $this->assertFalse(HomePage::isValid($value));
    }

    /**
     * @return string[][]
     */
    public static function providerUnknownValues(): array
    {
        return [
            'empty' => [''],
            'unknown' => ['admin'],
            'case' => ['Album'],
        ];
    }
}
