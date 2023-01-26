<?php

declare(strict_types=1);

namespace App\Tests\Functional\Trainer;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Functional\AbstractBrowserTestCase;

class CustomAlbumTrainerTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testSuccessTick(): void
    {
        $client = $this->getClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/trainer');

        file_put_contents('tests/last.html', $crawler->html());

        $this->assertSelectorIsNotVisible('#successToast-goldsilvercrystal');

        $form = $crawler->filter('form[data-dex="goldsilvercrystal"]')->form();
        $form['goldsilvercrystal-is_on_home']->tick();

        $this->assertSelectorWillBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#errorToast-goldsilvercrystal');
    }

    public function testSuccessUntick(): void
    {
        $client = $this->getClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/trainer');

        $this->assertSelectorIsNotVisible('#successToast-goldsilvercrystal');

        $form = $crawler->filter('form[data-dex="goldsilvercrystal"]')->form();
        $form['goldsilvercrystal-is_private']->untick();

        $this->assertSelectorWillBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#errorToast-goldsilvercrystal');
    }

    public function testErrorTick(): void
    {
        $client = $this->getClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/trainer');

        file_put_contents('tests/last.html', $crawler->html());

        $this->assertSelectorIsNotVisible('#errorToast-redgreenblueyellow');

        $form = $crawler->filter('form[data-dex="redgreenblueyellow"]')->form();
        $form['redgreenblueyellow-is_on_home']->tick();

        $this->assertSelectorWillBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#successToast-redgreenblueyellow');
    }

    public function testErrorUntick(): void
    {
        $client = $this->getClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/trainer');

        $this->assertSelectorIsNotVisible('#errorToast-redgreenblueyellow');

        $form = $crawler->filter('form[data-dex="redgreenblueyellow"]')->form();
        $form['redgreenblueyellow-is_private']->untick();

        $this->assertSelectorWillBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#successToast-redgreenblueyellow');
    }
}
