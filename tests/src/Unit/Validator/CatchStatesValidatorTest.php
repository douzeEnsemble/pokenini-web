<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Service\Api\GetCatchStatesService;
use App\Validator\CatchStates;
use App\Validator\CatchStatesValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 *
 * @extends ConstraintValidatorTestCase<CatchStatesValidator>
 */
#[CoversClass(CatchStatesValidator::class)]
#[UsesClass(CatchStates::class)]
class CatchStatesValidatorTest extends ConstraintValidatorTestCase
{
    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new CatchStates());

        $this->assertNoViolation();
    }

    #[DataProvider('providerInvalidConstraints')]
    public function testTrueIsInvalid(CatchStates $constraint): void
    {
        $this->validator->validate('douze', $constraint);

        $this->buildViolation('"{{ string }}" is not a valid catch state')
            ->setParameter('{{ string }}', 'douze')
            ->assertRaised()
        ;
    }

    /**
     * @return CatchStates[][]
     */
    public static function providerInvalidConstraints(): iterable
    {
        return [
            [new CatchStates()],
        ];
    }

    #[DataProvider('providerValidConstraints')]
    public function testTrueIsValid(CatchStates $constraint): void
    {
        $this->validator->validate('maybenot', $constraint);

        $this->assertNoViolation();
    }

    /**
     * @return CatchStates[][]
     */
    public static function providerValidConstraints(): iterable
    {
        return [
            [new CatchStates()],
        ];
    }

    public function testUnexpectedType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('maybenot', new NotNull());
    }

    public function testUnexpectedValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(new \DateTime(), new CatchStates());
    }

    #[\Override]
    protected function createValidator(): CatchStatesValidator
    {
        $getService = $this->createMock(GetCatchStatesService::class);

        $getService
            ->method('get')
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

        return new CatchStatesValidator($getService);
    }
}
