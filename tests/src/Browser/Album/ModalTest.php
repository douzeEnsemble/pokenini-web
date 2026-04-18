<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Security\User;
use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 */
#[CoversNothing]
#[Group('api-mocked-testing')]
final class ModalTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testModalOpenning(): void
    {
        $client = $this->getNewClient();

        $user = new User('109903422692691643666', 'TestProvider', new AccessToken(['access_token' => sha1('109903422692691643666')]));
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $this->assertSelectorIsNotVisible('#modal-blastoise-mega');

        $client->executeScript('document.querySelector(\'span[data-bs-target="#modal-blastoise-mega"]\').click()');

        $this->assertSelectorWillBeVisible('#modal-blastoise-mega');
    }

    public function testModalImageSwitch(): void
    {
        $client = $this->getNewClient();

        $user = new User('109903422692691643666', 'TestProvider', new AccessToken(['access_token' => sha1('109903422692691643666')]));
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/demolite');

        // Open the modal
        $script = <<<'SCRIPT'
            const modal = new bootstrap.Modal(document.getElementById('modal-blastoise-mega'));
            modal.show();
            SCRIPT;
        $client->executeScript($script);

        $this->assertSelectorIsVisible('#modal-blastoise-mega .album-modal-image-container-regular');
        $this->assertSelectorIsNotVisible('#modal-blastoise-mega .album-modal-image-container-shiny');

        $this->assertCount(1, $crawler->filter('#modal-blastoise-mega .album-modal-icon-regular.active'));
        $this->assertCount(1, $crawler->filter('#modal-blastoise-mega .album-modal-icon-regular'));
        $this->assertCount(0, $crawler->filter('#modal-blastoise-mega .album-modal-icon-shiny.active'));
        $this->assertCount(1, $crawler->filter('#modal-blastoise-mega .album-modal-icon-shiny'));

        $client->click(
            $client
                ->getCrawler()
                ->filter('#modal-blastoise-mega .album-modal-icon-shiny')
                ->link()
        );

        $this->assertSelectorIsNotVisible('#modal-blastoise-mega .album-modal-image-container-regular');
        $this->assertSelectorIsVisible('#modal-blastoise-mega .album-modal-image-container-shiny');

        $this->assertCount(0, $crawler->filter('#modal-blastoise-mega .album-modal-icon-regular.active'));
        $this->assertCount(1, $crawler->filter('#modal-blastoise-mega .album-modal-icon-regular'));
        $this->assertCount(1, $crawler->filter('#modal-blastoise-mega .album-modal-icon-shiny.active'));
        $this->assertCount(1, $crawler->filter('#modal-blastoise-mega .album-modal-icon-shiny'));

        $client->click(
            $client
                ->getCrawler()
                ->filter('#modal-blastoise-mega .album-modal-icon-shiny')
                ->link()
        );

        $this->assertSelectorIsNotVisible('#modal-blastoise-mega .album-modal-image-container-regular');
        $this->assertSelectorIsVisible('#modal-blastoise-mega .album-modal-image-container-shiny');

        $this->assertCount(0, $crawler->filter('#modal-blastoise-mega .album-modal-icon-regular.active'));
        $this->assertCount(1, $crawler->filter('#modal-blastoise-mega .album-modal-icon-regular'));
        $this->assertCount(1, $crawler->filter('#modal-blastoise-mega .album-modal-icon-shiny.active'));
        $this->assertCount(1, $crawler->filter('#modal-blastoise-mega .album-modal-icon-shiny'));

        $client->click(
            $client
                ->getCrawler()
                ->filter('#modal-blastoise-mega .album-modal-icon-regular')
                ->link()
        );

        $this->assertSelectorIsVisible('#modal-blastoise-mega .album-modal-image-container-regular');
        $this->assertSelectorIsNotVisible('#modal-blastoise-mega .album-modal-image-container-shiny');

        $this->assertCount(1, $crawler->filter('#modal-blastoise-mega .album-modal-icon-regular.active'));
        $this->assertCount(1, $crawler->filter('#modal-blastoise-mega .album-modal-icon-regular'));
        $this->assertCount(0, $crawler->filter('#modal-blastoise-mega .album-modal-icon-shiny.active'));
        $this->assertCount(1, $crawler->filter('#modal-blastoise-mega .album-modal-icon-shiny'));
    }
}
