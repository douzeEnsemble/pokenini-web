<?php

declare(strict_types=1);

namespace App\Validator;

use App\Service\Api\GetCatchStatesService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CatchStatesValidator extends ConstraintValidator
{
    public function __construct(
        private readonly GetCatchStatesService $getCatchStatesService,
    ) {}

    /**
     * @param mixed $value
     */
    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof CatchStates) {
            throw new UnexpectedTypeException($constraint, CatchStates::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (!in_array($value, $this->getCatchStateSlugs())) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation()
            ;
        }
    }

    /**
     * @return string[]
     */
    private function getCatchStateSlugs(): array
    {
        $catchStates = $this->getCatchStatesService->get();

        $slugs = [];
        foreach ($catchStates as $catchState) {
            $slugs[] = $catchState['slug'];
        }

        return $slugs;
    }
}
