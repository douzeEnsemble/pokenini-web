<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Browser\AbstractBrowserTestCase;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @group browser-testing
 */
class LoadingTimeTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testLoadingTime(): void
    {
        $client = $this->getClient();

        $stopwatch = new Stopwatch();
        $stopwatch->start('request');

        $client->request('GET', '/fr/album/demo?t=f86cbe805674d85f7806b175b70647a6a9334631');

        $event = $stopwatch->stop('request');

        $this->assertLessThanOrEqual(
            5 * 1000,
            $event->getDuration()
        );
    }
}
