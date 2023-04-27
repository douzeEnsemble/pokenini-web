<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Service\ApiService;
use App\Validator\CatchStates;
use App\Validator\CatchStatesValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<CatchStatesValidator>
 */
class CatchStatesValidatorTest extends ConstraintValidatorTestCase
{
    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new CatchStates());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideInvalidConstraints
     */
    public function testTrueIsInvalid(CatchStates $constraint): void
    {
        $this->validator->validate('douze', $constraint);

        $this->buildViolation('"{{ string }}" is not a valid catch state')
            ->setParameter('{{ string }}', 'douze')
            ->assertRaised();
    }

    /**
     * @return \ArrayIterator<int, CatchStates[]>
     */
    public function provideInvalidConstraints(): iterable
    {
        yield [new CatchStates()];
    }

    /**
     * @dataProvider provideValidConstraints
     */
    public function testTrueIsValid(CatchStates $constraint): void
    {
        $this->validator->validate('maybenot', $constraint);

        $this->assertNoViolation();
    }

    /**
     * @return \ArrayIterator<int, CatchStates[]>
     */
    public function provideValidConstraints(): iterable
    {
        yield [new CatchStates()];
    }

    protected function createValidator(): CatchStatesValidator
    {
        $apiService = $this->createMock(ApiService::class);

        $apiService
            ->method('getCatchStates')
            ->willReturn([
                [
                  'name' => 'No',
                  'frenchName' => 'Non',
                  'slug' => 'no',
                  'color' => '#e57373',
                ],
                [
                  'name' => 'Maybe',
                  'frenchName' => 'Peut être',
                  'slug' => 'maybe',
                  'color' => '#9575cd',
                ],
                [
                  'name' => 'Maybe not',
                  'frenchName' => 'Peut être pas',
                  'slug' => 'maybenot',
                  'color' => '#9575cd',
                ],
                [
                  'name' => 'Yes',
                  'frenchName' => 'Oui',
                  'slug' => 'yes',
                  'color' => '#66bb6a',
                ],
              ])
        ;

        return new CatchStatesValidator($apiService);
    }
}
