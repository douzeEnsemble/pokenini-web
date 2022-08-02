<?php

namespace unit\Helper;

use App\Helper\PokeniniTokenHelper;
use PHPUnit\Framework\TestCase;

class PokeniniTokenHelperTest extends TestCase
{
    public function testGetFromDexSlug(): void
    {
        $this->assertEquals(
            'cb19dc668f0c426c8f3e319f9ea36ecc',
            PokeniniTokenHelper::getFromDexSlug('demo')
        );

        $this->assertEquals(
            '901e051a5cb4577f54ddb47d72ea1af4',
            PokeniniTokenHelper::getFromDexSlug('douze')
        );
    }
}
