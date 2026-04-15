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
            '/app/tests/resources/unit/service/back/dex.json',
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

    public function testGetWithEmptyTrainerId(): void
    {
        /** @var GetDexService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetDexService::class,
            'GET',
            (string) file_get_contents('/app/tests/resources/unit/service/back/dex.json'),
            'dex/list',
        );

        $expectedSlugs = [
            'homepokemongo',
            'alpha',
            'mega',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($service->get('')),
        );
    }

    public function testGetWithTrainerId(): void
    {
        /** @var GetDexService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetDexService::class,
            'GET',
            (string) file_get_contents('/app/tests/resources/unit/service/back/dex_123.json'),
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
