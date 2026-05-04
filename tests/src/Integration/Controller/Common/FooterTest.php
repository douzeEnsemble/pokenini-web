<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Common;

use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversNothing]
final class FooterTest extends WebTestCase
{
  use TestNavTrait;

  public function testFooter(): void
  {
    $client = self::createClient();

    $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
    $user->addTrainerRole();
    $user->addAdminRole();
    $client->loginUser($user, 'web');

    $crawler = $client->request('GET', '/fr/album/dex');

    $this->assertCountFilter($crawler, 1, 'footer');

    $year = date('Y');
    $this->assertStringContainsString("© 2022-{$year}", $crawler->filter('footer #copyright')->text());
    $this->assertSame('Version 1.2.12', $crawler->filter('footer #copyright')->attr('title'));

    $this->assertCountFilter($crawler, 1, 'footer ul');
    $this->assertCountFilter($crawler, 4, 'footer ul li');

    $index = 0;
    $this->assertStringContainsString('Accueil', $crawler->filter('footer ul li')->eq($index)->text());
    ++$index;
    $this->assertStringContainsString('Politique de confidentialité', $crawler->filter('footer ul li')->eq($index)->text());
    ++$index;
    $this->assertStringContainsString('Mentions Légales', $crawler->filter('footer ul li')->eq($index)->text());
    ++$index;
    $this->assertStringContainsString('Cookies', $crawler->filter('footer ul li')->eq($index)->text());

    $this->assertSame(
      '/fr/album/dex',
      $crawler->filter('.navbar-logo')->attr('href') ?? ''
    );
  }

  public function testFooterAsGuest(): void
  {
    $client = self::createClient();

    $crawler = $client->request('GET', '/fr');

    $this->assertCountFilter($crawler, 1, 'footer');

    $year = date('Y');
    $this->assertStringContainsString("© 2022-{$year}", $crawler->filter('footer #copyright')->text());
    $this->assertSame('Version 1.2.12', $crawler->filter('footer #copyright')->attr('title'));

    $this->assertCountFilter($crawler, 1, 'footer ul');
    $this->assertCountFilter($crawler, 4, 'footer ul li');

    $index = 0;
    $this->assertStringContainsString('Accueil', $crawler->filter('footer ul li')->eq($index)->text());
    ++$index;
    $this->assertStringContainsString('Politique de confidentialité', $crawler->filter('footer ul li')->eq($index)->text());
    ++$index;
    $this->assertStringContainsString('Mentions Légales', $crawler->filter('footer ul li')->eq($index)->text());
    ++$index;
    $this->assertStringContainsString('Cookies', $crawler->filter('footer ul li')->eq($index)->text());

    $this->assertSame(
      '/fr',
      $crawler->filter('.navbar-logo')->attr('href') ?? ''
    );
  }
}
