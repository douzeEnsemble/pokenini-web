<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminActionController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AdminActionController::class)]
#[Group('api-mocked-testing')]
final class ActionTriggerTest extends WebTestCase
{
    use TestNavTrait;

    public function testAdminTriggerUpdateImages(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/actions');
        $form = $crawler->filter('#trigger_update_images form')->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();
        $this->assertSame('http://localhost/fr/istration/actions', $client->getRequest()->getUri());

        $this->assertCountFilter($crawler, 1, '.icon-square.bg-success');

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);
    }
}
