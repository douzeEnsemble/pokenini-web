<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminTriggerPipelineController;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AdminTriggerPipelineController::class)]
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

        $crawler = $client->request('GET', '/fr/istration/trigger_pipeline');

        $this->assertResponseIsSuccessful();

        $html = $crawler->outerHtml();
        $this->assertStringContainsString('Mergée', $html);
        $this->assertStringContainsString('Fermée', $html);
        $this->assertStringNotContainsString('admin.pipeline_status.state.closed', $html);

        // Crawler::siblings() only ever operates on the first matched node,
        // so it cannot distinguish the two panels' refresh links -- select
        // each one directly by its target fragment instead.
        $imagesHref = (string) $crawler->filterXPath("//a[contains(@href, '#trigger_update_images')]")->attr('href');
        $this->assertMatchesRegularExpression('/refresh_images=\d+/', $imagesHref);
        $this->assertStringNotContainsString('refresh_banners', $imagesHref);

        $bannersHref = (string) $crawler->filterXPath("//a[contains(@href, '#trigger_update_banners')]")->attr('href');
        $this->assertMatchesRegularExpression('/refresh_banners=\d+/', $bannersHref);
        $this->assertStringNotContainsString('refresh_images', $bannersHref);
    }
}
