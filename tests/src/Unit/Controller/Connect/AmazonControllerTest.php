<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Connect;

use App\Controller\Connect\AmazonController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AmazonController::class)]
class AmazonControllerTest extends TestCase
{
    use ConnectControllerTestTrait;

    public function testGoto(): void
    {
        $controller = new AmazonController();

        $this->assertGoto($controller, 'profile', 'amazon');
    }
}
