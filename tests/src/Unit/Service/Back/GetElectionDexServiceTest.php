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
    public const RESPONSE_CONTENT = '/app/tests/resources/unit/service/back/election_dex.json';

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
