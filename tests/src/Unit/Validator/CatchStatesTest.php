<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Validator\CatchStates;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CatchStates::class)]
class CatchStatesTest extends TestCase
{
    public function testValidatedBy(): void
    {
        $constraint = new CatchStates();

        $this->assertEquals('App\Validator\CatchStatesValidator', $constraint->validatedBy());
    }
}
