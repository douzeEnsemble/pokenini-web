<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Validator\CatchStates;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CatchStates::class)]
final class CatchStatesTest extends TestCase
{
    #[Test]
    public function validatedBy(): void
    {
        $constraint = new CatchStates();

        $this->assertEquals('App\Validator\CatchStatesValidator', $constraint->validatedBy());
    }
}
