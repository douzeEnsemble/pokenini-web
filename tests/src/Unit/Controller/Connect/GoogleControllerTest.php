<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Connect;

use App\Controller\Connect\GoogleController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GoogleController::class)]
final class GoogleControllerTest extends TestCase
{
    use ConnectControllerTestTrait;

    public function testGotoDuringSilentReauthForcesConsent(): void
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

    public function testGotoOnNormalLoginDoesNotForceConsent(): void
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
