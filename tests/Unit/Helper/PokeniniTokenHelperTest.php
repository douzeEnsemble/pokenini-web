<?php

declare(strict_types=1);

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

        $this->assertEquals(
            '678aa9c6c4ccd4255b33af7268b4454d',
            PokeniniTokenHelper::getFromDexSlug('home')
        );

        $this->assertEquals(
            '2975a018d9e2cea6106d1c6a3c04b394',
            PokeniniTokenHelper::getFromDexSlug('homeshiny')
        );

        $this->assertEquals(
            '124c9f1ba2fa390341b14a380ea60834',
            PokeniniTokenHelper::getFromDexSlug('homepokemongo')
        );

        $this->assertEquals(
            '1da3b0d3289c4206fa74ac01142af4da',
            PokeniniTokenHelper::getFromDexSlug('redgreenblueyellow')
        );
    }
}
