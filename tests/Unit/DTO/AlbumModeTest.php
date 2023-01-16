<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\AlbumMode;
use PHPUnit\Framework\TestCase;

class AlbumModeTest extends TestCase
{
    public function testShortLong(): void
    {
        $this->assertEquals('read', AlbumMode::MODES_SHORT_LONG['r']);
        $this->assertEquals('write', AlbumMode::MODES_SHORT_LONG['w']);
    }

    public function testLongShort(): void
    {
        $this->assertEquals('r', AlbumMode::MODES_LONG_SHORT['read']);
        $this->assertEquals('w', AlbumMode::MODES_LONG_SHORT['write']);
    }

    public function testLongOpposite(): void
    {
        $this->assertEquals('read', AlbumMode::MODES_LONG_OPPOSITE['write']);
        $this->assertEquals('write', AlbumMode::MODES_LONG_OPPOSITE['read']);
    }
}
