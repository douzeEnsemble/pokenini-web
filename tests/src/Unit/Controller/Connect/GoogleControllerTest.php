<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Connect;

use App\Controller\Connect\GoogleController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GoogleController::class)]
final class GoogleControllerTest extends TestCase
{
    use ConnectControllerTestTrait;

    #[Test]
    public function gotoDuringSilentReauthForcesConsent(): void
    {
        $controller = new GoogleController();

        $this->assertGoto(
            $controller,
            'openid',
            'google',
            ['prompt' => 'consent'],
            $this->createRequestWithSession(true),
        );
    }

    #[Test]
    public function gotoOnNormalLoginDoesNotForceConsent(): void
    {
        $controller = new GoogleController();

        $this->assertGoto(
            $controller,
            'openid',
            'google',
            [],
            $this->createRequestWithSession(false),
        );
    }
}
