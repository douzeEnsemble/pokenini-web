<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Service\Back\GetElectionTopService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetElectionTopService::class)]
class GetElectionTopServiceTest extends TestCase
{
    use BackServiceTrait;

    public function testGet(): void
    {
        $items = $this->getService('home', 'fav', 5)->getTop('home', 'fav', 5);

        $this->assertCount(5, $items);
    }

    public function testGetBis(): void
    {
        $items = $this->getService('demo', 'pref', 10)->getTop('demo', 'pref', 10);

        $this->assertCount(10, $items);
    }

    public function testWithoutLoggedUser(): void
    {
        $filename = '/var/www/html/tests/resources/unit/service/back/election_top_5_home_fav.json';

        /** @var GetElectionTopService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetElectionTopService::class,
            'GET',
            (string) file_get_contents($filename),
            'election/top?dex_slug=home&election_slug=fav&count=5',
        );

        $items = $service->getTop('home', 'fav', 5);

        $this->assertCount(5, $items);
    }

    private function getService(
        string $dexSlug,
        string $electionSlug,
        int $count,
    ): GetElectionTopService {
        $filename = "/var/www/html/tests/resources/unit/service/back/election_top_{$count}_{$dexSlug}_{$electionSlug}.json";

        /** @var GetElectionTopService */
        return $this->getServiceWithLoggedUser(
            GetElectionTopService::class,
            'GET',
            (string) file_get_contents($filename),
            "election/top?dex_slug={$dexSlug}&election_slug={$electionSlug}&count={$count}",
        );
    }
}
