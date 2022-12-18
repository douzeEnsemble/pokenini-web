<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CommonControllerTest extends WebTestCase
{
    use TestNavTrait;

    public function testHome(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $this->assertCommonItems($client, '/fr/');
        $this->assertCommonItems($client, '/fr/connect/');
        $this->assertCommonItems($client, '/fr/istration/');
    }

    private function assertCommonItems(KernelBrowser $client, string $url): void
    {
        $crawler = $client->request('GET', $url);

        $this->assertCount(1, $crawler->filter('link[sizes="180x180"]'));
        $this->assertCount(1, $crawler->filter('link[sizes="32x32"]'));
        $this->assertCount(1, $crawler->filter('link[sizes="16x16"]'));
        $this->assertCount(1, $crawler->filter('link[rel="manifest"]'));

        $this->assertCount(1, $crawler->filter('.navbar-brand img'));
    }
}
