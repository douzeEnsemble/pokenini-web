<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Service\Back\GetElectionDexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetElectionDexService::class)]
class GetElectionDexServiceTest extends TestCase
{
    use BackServiceTrait;

    public const ENDPOINT = 'dex/can_hold_election';
    public const RESPONSE_CONTENT = '/var/www/html/tests/resources/unit/service/back/election_dex.json';

    public function testGet(): void
    {
        /** @var GetElectionDexService $service */
        $service = $this->getServiceWithLoggedUser(
            GetElectionDexService::class,
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $this->assertEquals(
            [
                'homeshiny',
            ],
            self::extractSlugs($service->get()),
        );
    }

    public function testGetWithPremium(): void
    {
        $expectedSlugs = [
            'home',
            'redgreenblueyellow',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($this->getServiceWithPremium()->getWithPremium()),
        );
    }

    public function testGetWithUnreleasedAndPremium(): void
    {
        $expectedSlugs = [
            'home',
            'homeshiny',
            'redgreenblueyellow',
            'redgreenblueyellowshiny',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($this->getServiceWithUnreleasedAndPremium()->getWithUnreleasedAndPremium()),
        );
    }

    public function testWithoutLoggedUser(): void
    {
        /** @var GetElectionDexService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetElectionDexService::class,
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $this->assertEquals(
            [
                'homeshiny',
            ],
            self::extractSlugs($service->get()),
        );
    }

    private function getServiceWithPremium(): GetElectionDexService
    {
        return $this->getMockService(
            '/var/www/html/tests/resources/unit/service/back/election_dex_premium.json',
            'dex/can_hold_election?include_premium_dex=1',
        );
    }

    private function getServiceWithUnreleasedAndPremium(): GetElectionDexService
    {
        return $this->getMockService(
            '/var/www/html/tests/resources/unit/service/back/election_dex_unreleased_and_premium.json',
            'dex/can_hold_election?include_unreleased_dex=1&include_premium_dex=1',
        );
    }

    private function getMockService(
        string $filename,
        string $endpoint,
    ): GetElectionDexService {
        /** @var GetElectionDexService */
        return $this->getServiceWithLoggedUser(
            GetElectionDexService::class,
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
