<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminReportsController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AdminReportsController::class)]
#[Group('api-mocked-testing')]
final class AdminReportsTest extends WebTestCase
{
    use TestNavTrait;

    public function testAdminHome(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/reports');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Administration',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Administration',
            $crawler->filter('h1')->text()
        );

        $this->assertCountFilter($crawler, 2, 'table.report-table');
        $this->assertCountFilter($crawler, 1, '.admin-item-invalidate_reports button.admin-item-cta');

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
