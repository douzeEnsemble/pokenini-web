<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\ConnectController;
use PHPUnit\Framework\TestCase;

class ConnectControllerTest extends TestCase
{
    public function testLogout(): void
    {
        $controller = new ConnectController();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Don't forget to activate logout in security.yaml");

        $controller->logout();
    }
}
