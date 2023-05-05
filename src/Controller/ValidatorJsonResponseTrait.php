<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ToJsonResponseException;
use Symfony\Component\Validator\Constraint;

trait ValidatorJsonResponseTrait
{
    private function validate(
        string $value,
        Constraint $constraint
    ): void {
        $errors = $this->validator->validate(
            $value,
            $constraint,
        );

        if (!$errors->count()) {
            return;
        }

        /** @var string $message */
        $message = $errors->get(0)->getMessage();

        throw new ToJsonResponseException($message, 400);
    }
}
