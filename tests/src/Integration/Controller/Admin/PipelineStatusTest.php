<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminController;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AdminController::class)]
#[Group('api-mocked-testing')]
final class PipelineStatusTest extends WebTestCase
{
    #[Test]
    public function pipelineStatusRenders(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/actions');

        $this->assertResponseIsSuccessful();

        $html = $crawler->outerHtml();
        $this->assertStringContainsString('Mergée', $html);
        $this->assertStringContainsString('Fermée', $html);
        $this->assertStringNotContainsString('admin.pipeline_status.state.closed', $html);

        $refreshLink = $crawler->filter('.admin-pipeline-status')->siblings()->filter('a.btn-outline-info')->first();
        $href = (string) $refreshLink->attr('href');
        $this->assertMatchesRegularExpression('/refresh=\d+/', $href);
        $this->assertStringContainsString('#trigger_update_images', $href);
    }
}
