<?php

declare(strict_types=1);

namespace App\Tests\Functional\Common;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversNothing]
class FooterTest extends WebTestCase
{
    use TestNavTrait;

    public function testFooter(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr');

        $this->assertCountFilter($crawler, 1, 'footer');

        $year = date('Y');
        $this->assertStringContainsString("© 2022-{$year}", $crawler->filter('footer #copyright')->text());
        $this->assertSame('Version 1.2.12', $crawler->filter('footer #copyright')->attr('title'));

        $this->assertCountFilter($crawler, 1, 'footer ul');
        $this->assertCountFilter($crawler, 4, 'footer ul li');

        $index = 0;
        $this->assertStringContainsString('Accueil', $crawler->filter('footer ul li')->eq($index++)->text());
        $this->assertStringContainsString('Politique de confidentialité', $crawler->filter('footer ul li')->eq($index++)->text());
        $this->assertStringContainsString('Mentions Légales', $crawler->filter('footer ul li')->eq($index++)->text());
        $this->assertStringContainsString('Cookies', $crawler->filter('footer ul li')->eq($index++)->text());
    }
}
