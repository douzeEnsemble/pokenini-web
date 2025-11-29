<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ElectionTop;
use App\ResponseObject\Election\TopPokemon;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElectionTop::class)]
class ElectionTopTest extends TestCase
{
    use ResponseObjectTrait;

    public function testOk(): void
    {
        $object = new ElectionTop([
            $this->getStubTopPokemon(),
            $this->getStubTopPokemon(),
            $this->getStubTopPokemon(),
        ]);

        $this->assertCount(3, $object->getItems());
        $this->assertContainsOnlyInstancesOf(TopPokemon::class, $object->getItems());
    }
}
