<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Connect;

use App\Controller\Connect\DiscordController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DiscordController::class)]
class DiscordControllerTest extends TestCase
{
    use ConnectControllerTestTrait;

    public function testGoto(): void
    {
        $controller = new DiscordController();

        $this->assertGoto($controller, 'identify', 'discord');
    }
}
