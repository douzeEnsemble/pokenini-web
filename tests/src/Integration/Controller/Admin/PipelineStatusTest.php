<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminController;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AdminController::class)]
#[Group('api-mocked-testing')]
final class PipelineStatusTest extends WebTestCase
{
    public function testPipelineStatusRenders(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/actions');

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('Mergée', $crawler->outerHtml());

        $refreshLink = $crawler->filter('.admin-pipeline-status')->siblings()->filter('a.btn-outline-info')->first();
        $this->assertStringContainsString('refresh=1', (string) $refreshLink->attr('href'));
    }
}
