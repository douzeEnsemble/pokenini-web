<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Controller\AlbumIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
class AdminReportsTest extends WebTestCase
{
    use TestNavTrait;

    public function testAdminHome(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration');

        $this->assertResponseStatusCodeSame(200);

        $this->assertCountFilter($crawler, 2, 'table.report-table');
        $this->assertCountFilter($crawler, 1, '.admin-item-invalidate_reports a.admin-item-cta');

        $this->assertCountFilter($crawler, 1, 'canvas#catch_state_counts_defined_by_trainer');
        $this->assertCountFilter($crawler, 1, 'table#report-table-catch_state_counts_defined_by_trainer');

        $this->assertCountFilter($crawler, 5, 'table#report-table-catch_state_counts_defined_by_trainer thead tr th');
        $this->assertCountFilter($crawler, 3, 'table#report-table-catch_state_counts_defined_by_trainer tbody tr');
        $this->assertCountFilter(
            $crawler,
            4,
            'table#report-table-catch_state_counts_defined_by_trainer tbody tr',
            0,
            'td'
        );
        $this->assertCountFilter(
            $crawler,
            1,
            'table#report-table-catch_state_counts_defined_by_trainer tbody tr',
            0,
            'th'
        );
        $this->assertCountFilter($crawler, 3, 'table#report-table-catch_state_counts_defined_by_trainer tbody button');
        $this->assertCountFilter($crawler, 3, 'table#report-table-catch_state_counts_defined_by_trainer tbody canvas');
        $this->assertCountFilter($crawler, 3, 'table#report-table-catch_state_counts_defined_by_trainer tbody a');
        $this->assertEquals(
            '94',
            $crawler
                ->filter('table#report-table-catch_state_counts_defined_by_trainer tbody tr')
                ->eq(1)
                ->filter('td')
                ->eq(2)
                ->text()
        );
        $this->assertEquals(
            '1.61',
            $crawler
                ->filter('table#report-table-catch_state_counts_defined_by_trainer tbody tr')
                ->eq(1)
                ->filter('td')
                ->eq(3)
                ->text()
        );

        $this->assertCountFilter($crawler, 1, 'canvas#dex_usage');

        $this->assertCountFilter($crawler, 1, 'canvas#catch_state_usage');
        $this->assertCountFilter($crawler, 1, 'table#report-table-catch_state_usage');
        $this->assertCountFilter($crawler, 5, 'table#report-table-catch_state_usage thead tr th');
        $this->assertCountFilter($crawler, 6, 'table#report-table-catch_state_usage tbody tr');

        $this->assertCountFilter($crawler, 4, 'table#report-table-catch_state_usage tbody tr', 0, 'td');
        $this->assertCountFilter($crawler, 1, 'table#report-table-catch_state_usage tbody tr', 0, 'th');
        $this->assertCountFilter($crawler, 6, 'table#report-table-catch_state_usage tbody button');
        $this->assertCountFilter($crawler, 6, 'table#report-table-catch_state_usage tbody canvas');
        $this->assertCountFilter($crawler, 6, 'table#report-table-catch_state_usage tbody a');
        $this->assertEquals(
            '28',
            $crawler
                ->filter('table#report-table-catch_state_usage tbody tr')
                ->eq(1)
                ->filter('td')
                ->eq(2)
                ->text()
        );
        $this->assertEquals(
            '0.48',
            $crawler
                ->filter('table#report-table-catch_state_usage tbody tr')
                ->eq(1)
                ->filter('td')
                ->eq(3)
                ->text()
        );
    }
}
