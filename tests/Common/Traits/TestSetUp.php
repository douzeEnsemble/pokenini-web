<?php

declare(strict_types=1);

namespace App\Tests\Common\Traits;

trait TestSetUp
{
    protected function setUp(): void
    {
        exec("rm -Rf /var/www/html/var/cache/test/*");
    }
}
