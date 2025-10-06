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

    public function testGet(): void
    {
        /** @var GetDexService $service */
        $service = $this->getMockService(
            '/var/www/html/tests/resources/unit/service/back/dex.json',
            'dex/list',
        );

        $expectedSlugs = [
            'homepokemongo',
            'alpha',
            'mega',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($service->get()),
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

        $service = $this->getMockService(
            '/var/www/html/tests/resources/unit/service/back/dex_unreleased.json',
            'dex/list?include_unreleased_dex=1',
        );

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($service->getWithUnreleased()),
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

        $service = $this->getMockService(
            '/var/www/html/tests/resources/unit/service/back/dex_premium.json',
            'dex/list?include_premium_dex=1',
        );

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($service->getWithPremium()),
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

        $service = $this->getMockService(
            '/var/www/html/tests/resources/unit/service/back/dex_unreleased_and_premium.json',
            'dex/list?include_unreleased_dex=1&include_premium_dex=1',
        );

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($service->getWithUnreleasedAndPremium()),
        );
    }

    public function testGetWithTrainerId(): void
    {
        /** @var GetDexService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetDexService::class,
            'GET',
            (string) file_get_contents('/var/www/html/tests/resources/unit/service/back/dex_123.json'),
            'dex/list?trainer_id=123',
        );

        $expectedSlugs = [
            'homepokemongo',
            'alpha',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($service->get('123')),
        );
    }

    public function testGetWithUnreleasedWithTrainerId(): void
    {
        /** @var GetDexService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetDexService::class,
            'GET',
            (string) file_get_contents('/var/www/html/tests/resources/unit/service/back/dex_123_unreleased.json'),
            'dex/list?trainer_id=123&include_unreleased_dex=1',
        );

        $expectedSlugs = [
            'redgreenblueyellow',
            'homepokemongo',
            'alpha',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($service->getWithUnreleased('123')),
        );
    }

    public function testGetWithPremiumWithTrainerId(): void
    {
        /** @var GetDexService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetDexService::class,
            'GET',
            (string) file_get_contents('/var/www/html/tests/resources/unit/service/back/dex_123_premium.json'),
            'dex/list?trainer_id=123&include_premium_dex=1',
        );

        $expectedSlugs = [
            'goldsilvercrystal',
            'homepokemongo',
            'alpha',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($service->getWithPremium('123')),
        );
    }

    public function testGetWithUnreleasedAndPremiumWithTrainerId(): void
    {
        /** @var GetDexService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetDexService::class,
            'GET',
            (string) file_get_contents('/var/www/html/tests/resources/unit/service/back/dex_123_unreleased_and_premium.json'),
            'dex/list?trainer_id=123&include_unreleased_dex=1&include_premium_dex=1',
        );

        $expectedSlugs = [
            'redgreenblueyellow',
            'goldsilvercrystal',
            'homepokemongo',
            'alpha',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($service->getWithUnreleasedAndPremium('123')),
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
