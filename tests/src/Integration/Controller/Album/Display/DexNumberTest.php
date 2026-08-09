<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Album\Display;

use App\Controller\AlbumIndexController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
#[Group('api-mocked-testing')]
final class DexNumberTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function displayDexNumber(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertCountFilter($crawler, 278, '.album-case');

        $this->assertEquals(
            'Germignon',
            $crawler->filter('.album-case')->first()->filter('.album-case-name')->text()
        );
        $this->assertEquals(
            '#1',
            $crawler->filter('.album-case')->first()->filter('.album-case-dex-number')->text()
        );

        $this->assertEquals(
            'Bulbizarre',
            $crawler->filter('.album-case')->eq(252)->filter('.album-case-name')->text()
        );
        $this->assertEquals(
            '#231',
            $crawler->filter('.album-case')->eq(252)->filter('.album-case-dex-number')->text()
        );
    }
}
