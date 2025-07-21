<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Service\Back\GetDexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetDexService::class)]
class GetDexServiceTest extends TestCase
{
    use BackServiceTrait;

    public const ENDPOINT = 'dex/123/list';
    public const RESPONSE_CONTENT = '/var/www/html/tests/resources/unit/service/back/dex_123.json';

    public function testGet(): void
    {
        /** @var GetDexService $service */
        $service = $this->getMockService(
            self::RESPONSE_CONTENT,
            self::ENDPOINT,
        );

        $expectedSlugs = [
            'homepokemongo',
            'alpha',
            'mega',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($service->get('123')),
        );
    }

    public function testGetWithUnreleased(): void
    {
        $expectedSlugs = [
            'redgreenblueyellow',
            'homepokemongo',
            'alpha',
            'mega',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($this->getServiceWithUnreleased('123')->getWithUnreleased('123')),
        );
    }

    public function testGetWithPremium(): void
    {
        $expectedSlugs = [
            'goldsilvercrystal',
            'homepokemongo',
            'alpha',
            'mega',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($this->getServiceWithPremium('123')->getWithPremium('123')),
        );
    }

    public function testGetWithUnreleasedAndPremium(): void
    {
        $expectedSlugs = [
            'redgreenblueyellow',
            'goldsilvercrystal',
            'homepokemongo',
            'alpha',
            'mega',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($this->getServiceWithUnreleasedAndPremium('123')->getWithUnreleasedAndPremium('123')),
        );
    }

    public function testWithoutLoggedUser(): void
    {
        /** @var GetDexService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetDexService::class,
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $expectedSlugs = [
            'homepokemongo',
            'alpha',
            'mega',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($service->get('123')),
        );
    }

    private function getServiceWithUnreleased(string $trainerId): GetDexService
    {
        return $this->getMockService(
            "/var/www/html/tests/resources/unit/service/back/dex_{$trainerId}_unreleased.json",
            "dex/{$trainerId}/list?include_unreleased_dex=1",
        );
    }

    private function getServiceWithPremium(string $trainerId): GetDexService
    {
        return $this->getMockService(
            "/var/www/html/tests/resources/unit/service/back/dex_{$trainerId}_premium.json",
            "dex/{$trainerId}/list?include_premium_dex=1",
        );
    }

    private function getServiceWithUnreleasedAndPremium(string $trainerId): GetDexService
    {
        return $this->getMockService(
            "/var/www/html/tests/resources/unit/service/back/dex_{$trainerId}_unreleased_and_premium.json",
            "dex/{$trainerId}/list?include_unreleased_dex=1&include_premium_dex=1",
        );
    }

    private function getMockService(
        string $filename,
        string $endpoint,
    ): GetDexService {
        /** @var GetDexService */
        return $this->getServiceWithLoggedUser(
            GetDexService::class,
            'GET',
            (string) file_get_contents($filename),
            $endpoint,
        );
    }

    /**
     * @param string[][] $items
     *
     * @return string[]
     */
    private static function extractSlugs(array $items): array
    {
        $slugs = [];

        foreach ($items as $item) {
            $slugs[] = $item['slug'];
        }

        return $slugs;
    }
}
