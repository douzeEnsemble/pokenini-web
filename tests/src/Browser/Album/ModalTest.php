<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
#[Group('api-mocked-testing')]
final class ModalTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function modalOpenning(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $this->assertSelectorIsNotVisible('#modal-blastoise-mega');

        $client->executeScript('document.querySelector(\'span[data-bs-target="#modal-blastoise-mega"]\').click()');

        $this->assertSelectorWillBeVisible('#modal-blastoise-mega');
    }

    #[Test]
    public function modalImageSwitch(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
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
