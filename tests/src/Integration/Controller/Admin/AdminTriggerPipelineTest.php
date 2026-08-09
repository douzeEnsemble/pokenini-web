<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminTriggerPipelineController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AdminTriggerPipelineController::class)]
#[Group('api-mocked-testing')]
final class AdminTriggerPipelineTest extends WebTestCase
{
    use TestNavTrait;

    public function testTriggerPipelineNotConnected(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/istration/trigger_pipeline');

        $this->assertResponseStatusCodeSame(307);
    }

    public function testTriggerPipelineNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/istration/trigger_pipeline');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testTriggerPipeline(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/trigger_pipeline');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Administration',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Administration',
            $crawler->filter('h1')->text()
        );

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 1, '.admin-item-description');
        $this->assertCountFilter($crawler, 1, '#trigger_update_images button.admin-item-cta');
        $this->assertCountFilter($crawler, 0, '.admin-item-cta.disabled');
        $this->assertCountFilter($crawler, 1, '.admin-pipeline-status');

        $this->assertSame(
            "Régénère le jeu d'images des Pokémon à partir des dernières données.",
            $crawler->filter('#trigger_update_images .admin-item-description')->text()
        );

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
        $this->assertStringNotContainsString('const types = JSON.parse', $crawler->outerHtml());
    }
}
