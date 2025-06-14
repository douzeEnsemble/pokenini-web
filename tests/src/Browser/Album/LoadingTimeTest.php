<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @internal
 */
#[CoversNothing]
#[Group('browser-testing')]
#[Group('time-testing')]
class LoadingTimeTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testLoadingTime(): void
    {
        $client = $this->getNewClient();

        // Initial request to put into caches
        $client->request('GET', '/fr/album/demo?t=f86cbe805674d85f7806b175b70647a6a9334631');

        $stopwatch = new Stopwatch();
        $stopwatch->start('request');

        $client->request('GET', '/fr/album/demo?t=f86cbe805674d85f7806b175b70647a6a9334631');

        $event = $stopwatch->stop('request');

        $this->assertLessThanOrEqual(
            6.5 * 1000,
            $event->getDuration()
        );
    }
}
