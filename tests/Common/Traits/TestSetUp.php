<?php

declare(strict_types=1);

namespace App\Tests\Common\Traits;

trait TestSetUp
{
    protected function setUp(): void
    {
        exec('rm -Rf /srv/var/cache/test/*');
    }
}
