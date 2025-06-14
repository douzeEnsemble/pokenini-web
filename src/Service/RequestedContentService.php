<?php

namespace App\Service;

use App\Exception\EmptyContentException;
use App\Exception\InvalidJsonException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestedContentService
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly RequestStack $requestStack,
    ) {}

    public function getContent(Constraint $constraint): string
    {
        $content = $this->getContentFromRequest();

        $this->validate($content, $constraint);

        return $content;
    }

    private function getContentFromRequest(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            throw new EmptyContentException();
        }

        $content = $request->getContent();

        if (!$content) {
            throw new EmptyContentException();
        }

        return $content;
    }

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

        throw new InvalidJsonException($message);
    }
}
