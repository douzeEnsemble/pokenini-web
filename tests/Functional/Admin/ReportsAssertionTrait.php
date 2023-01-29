<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use Symfony\Component\DomCrawler\Crawler;

trait ReportsAssertionTrait
{
    /**
     * @param array<string, string> $expectedReport
     */
    protected function assertReport(Crawler $crawler, array $expectedReport): void
    {
        if (empty($expectedReport)) {
            $this->assertCountFilter($crawler, 0, '.admin-item-report');

            return;
        }

        $this->assertCountFilter($crawler, 1, '.admin-item-report');

        $index = 0;
        foreach ($expectedReport as $label => $value) {
            $this->assertEquals(
                $label,
                $crawler->filter('.admin-item-report dt')->eq($index)->text()
            );
            $this->assertEquals(
                $value,
                $crawler->filter('.admin-item-report dd')->eq($index)->text()
            );

            $index++;
        }
    }
}
